<?php
// +----------------------------------------------------------------------
// | Description: 线索
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com
// +----------------------------------------------------------------------
namespace app\crm\model;

use think\Db;
use app\admin\model\Common;
use think\Request;
use think\Validate;

class Leads extends Common
{
	/**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如CRM模块用crm作为数据表前缀
     */
	protected $name = 'crm_leads';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
	protected $autoWriteTimestamp = true;

	/**
     * [getDataList 线索list]
     * @author Michael_xu
     * @param     [string]                   $map [查询条件]
     * @param     [number]                   $page     [当前页数]
     * @param     [number]                   $limit    [每页数量]
     * @return    [array]                    [description]
     */		
	public function getDataList($request)
    {
    	$userModel = new \app\admin\model\User();
    	$structureModel = new \app\admin\model\Structure();
    	$fieldModel = new \app\admin\model\Field();
		$search = $request['search'];
    	$user_id = $request['user_id'];
    	$scene_id = (int)$request['scene_id'];
    	$is_excel = $request['is_excel']; //导出
		unset($request['scene_id']);
		unset($request['search']);
		unset($request['user_id']); 
		unset($request['is_excel']);	   	

        $request = $this->fmtRequest( $request );
        $requestMap = $request['map'] ? : [];

		$sceneModel = new \app\admin\model\Scene();
		if ($scene_id) {
			//自定义场景
			$sceneMap = $sceneModel->getDataById($scene_id, $user_id, 'leads') ? : [];
		} else {
			//默认场景
			$sceneMap = $sceneModel->getDefaultData('leads', $user_id) ? : [];
		}
		$searchMap = [];
		if ($search) {
			//普通筛选
			$searchMap = function($query) use ($search){
			        $query->where('leads.name',array('like','%'.$search.'%'))
			        	->whereOr('leads.mobile',array('like','%'.$search.'%'))
			        	->whereOr('leads.telephone',array('like','%'.$search.'%'));
			};			
			// $sceneMap['name'] = ['condition' => 'contains','value' => $search,'form_type' => 'text','name' => '线索名称'];
		}
		//优先级：普通筛选>高级筛选>场景
		$map = $requestMap ? array_merge($sceneMap, $requestMap) : $sceneMap;
		//高级筛选
		$map = where_arr($map, 'crm', 'leads', 'index');
		//权限
		$a = 'index';
		if ($is_excel) $a = 'excelExport';
		$auth_user_ids = $userModel->getUserByPer('crm', 'leads', $a);
	    //过滤权限
	    if (isset($map['leads.owner_user_id'])) {
	    	if (!is_array($map['leads.owner_user_id'][1])) {
				$map['leads.owner_user_id'][1] = [$map['leads.owner_user_id'][1]];
			}
			if ($map['leads.owner_user_id'][0] == 'neq') {
				$auth_user_ids = array_diff($auth_user_ids, $map['leads.owner_user_id'][1]) ? : [];	//取差集	
			} else {
				$auth_user_ids = array_intersect($map['leads.owner_user_id'][1], $auth_user_ids) ? : [];	//取交集	
			}
	        unset($map['leads.owner_user_id']);
	    }		    
	    $auth_user_ids = array_merge(array_unique(array_filter($auth_user_ids))) ? : ['-1'];
	    //负责人
	    $authMap['leads.owner_user_id'] = ['in',$auth_user_ids];
		//列表展示字段
		// $indexField = $fieldModel->getIndexField('crm_leads', $user_id) ? : array('name');
		$userField = $fieldModel->getFieldByFormType('crm_leads', 'user'); //人员类型
		$structureField = $fieldModel->getFieldByFormType('crm_leads', 'structure');  //部门类型		
		
		//排序
		if ($request['order_type'] && $request['order_field']) {
			$order = trim($request['order_field']).' '.trim($request['order_type']);
		} else {
			$order = 'update_time desc';
		}
		//过滤已转化线索
		if (!$map['leads.is_transform']) {
			$map['leads.is_transform'] = array('neq',1);
		}

		$readAuthIds = $userModel->getUserByPer('crm', 'leads', 'read');
        $updateAuthIds = $userModel->getUserByPer('crm', 'leads', 'update');
        $deleteAuthIds = $userModel->getUserByPer('crm', 'leads', 'delete');		
		$list = db('crm_leads')
				->alias('leads')
				->where($map)
				->where($searchMap)
				->where($authMap)
        		->limit(($request['page']-1)*$request['limit'], $request['limit'])
        		// ->field('leads_id,'.implode(',',$indexField))
        		->order($order)
        		->select();	
        $dataCount = db('crm_leads')->alias('leads')->where($map)->where($searchMap)->where($authMap)->count('leads_id');
        foreach ($list as $k=>$v) {
        	$list[$k]['create_user_id_info'] = isset($v['create_user_id']) ? $userModel->getUserById($v['create_user_id']) : [];
        	$list[$k]['owner_user_id_info'] = isset($v['owner_user_id']) ? $userModel->getUserById($v['owner_user_id']) : [];
        	// $list[$k]['name'] = isset($v['name']) ? msubstr($v['name'], 0, 15) : '';
			foreach ($userField as $key => $val) {
        		$list[$k][$val.'_info'] = isset($v[$val]) ? $userModel->getListByStr($v[$val]) : [];
        	}
			foreach ($structureField as $key => $val) {
        		$list[$k][$val.'_info'] = isset($v[$val]) ? $structureModel->getDataByStr($v[$val]) : [];
        	} 
			//权限
			$permission = [];
			$is_read = 0;
			$is_update = 0;
			$is_delete = 0;
			if (in_array($v['owner_user_id'],$readAuthIds)) $is_read = 1;
			if (in_array($v['owner_user_id'],$updateAuthIds)) $is_update = 1;
			if (in_array($v['owner_user_id'],$deleteAuthIds)) $is_delete = 1;	        
	        $permission['is_read'] = $is_read;
	        $permission['is_update'] = $is_update;
	        $permission['is_delete'] = $is_delete;
	        $list[$k]['permission']	= $permission;        	
        }    
        $data = [];
        $data['list'] = $list;
        $data['dataCount'] = $dataCount ? : 0;

        return $data;
    }

	/**
	 * 创建线索主表信息
	 * @author Michael_xu
	 * @param  
	 * @return                            
	 */	
	public function createData($param)
	{
		$fieldModel = new \app\admin\model\Field();
		// 自动验证
		$validateArr = $fieldModel->validateField($this->name); //获取自定义字段验证规则
		$validate = new Validate($validateArr['rule'], $validateArr['message']);

		$result = $validate->check($param);
		if (!$result) {
			$this->error = $validate->getError();
			return false;
		}

		//处理部门、员工、附件、多选类型字段
		$arrFieldAtt = $fieldModel->getArrayField('crm_leads');
		foreach ($arrFieldAtt as $k=>$v) {
			$param[$v] = arrayToString($param[$v]);
		}

		if ($this->data($param)->allowField(true)->isUpdate(false)->save()) {
			//修改记录
			updateActionLog($param['create_user_id'], 'crm_leads', $this->leads_id, '', '', '创建了线索');			
			$data = [];
			$data['leads_id'] = $this->leads_id;
			return $data;
		} else {
			$this->error = '添加失败';
			return false;
		}			
	}

	/**
	 * 编辑线索信息
	 * @author Michael_xu
	 * @param  
	 * @return                            
	 */	
	public function updateDataById($param, $leads_id = '')
	{
		$dataInfo = $this->getDataById($leads_id);
		if (!$dataInfo) {
			$this->error = '数据不存在或已删除';
			return false;
		}
		$param['leads_id'] = $leads_id;
		//过滤不能修改的字段
		$unUpdateField = ['create_user_id','is_deleted','delete_time'];
		foreach ($unUpdateField as $v) {
			unset($param[$v]);
		}
		
		$fieldModel = new \app\admin\model\Field();
		// 自动验证
		$validateArr = $fieldModel->validateField($this->name); //获取自定义字段验证规则
		$validate = new Validate($validateArr['rule'], $validateArr['message']);

		$result = $validate->check($param);
		if (!$result) {
			$this->error = $validate->getError();
			return false;
		}

		//处理部门、员工、附件、多选类型字段
		$arrFieldAtt = $fieldModel->getArrayField('crm_leads');
		foreach ($arrFieldAtt as $k=>$v) {
			$param[$v] = arrayToString($param[$v]);
		}
		$param['follow'] = '已跟进';
		if ($this->allowField(true)->save($param, ['leads_id' => $leads_id])) {
			//修改记录
			updateActionLog($param['user_id'], 'crm_leads', $leads_id, $dataInfo->data, $param);
			$data = [];
			$data['leads_id'] = $leads_id;
			return $data;
		} else {
			$this->error = '编辑失败';
			return false;
		}					
	}

	/**
     * 线索数据
     * @param  $id 线索ID
     * @return 
     */	
   	public function getDataById($id = '')
   	{   		
   		$map['leads_id'] = $id;
		$dataInfo = $this->where($map)->find();
		if (!$dataInfo) {
			$this->error = '暂无此数据';
			return false;
		}
		$userModel = new \app\admin\model\User();
		$dataInfo['create_user_id_info'] = isset($dataInfo['create_user_id']) ? $userModel->getUserById($dataInfo['create_user_id']) : [];
		$dataInfo['owner_user_id_info'] = isset($dataInfo['owner_user_id']) ? $userModel->getUserById($dataInfo['owner_user_id']) : []; 
		return $dataInfo;
   	}
}