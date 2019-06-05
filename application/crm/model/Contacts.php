<?php
// +----------------------------------------------------------------------
// | Description: 联系人
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com
// +----------------------------------------------------------------------
namespace app\crm\model;

use think\Db;
use app\admin\model\Common;
use think\Request;
use think\Validate;

class Contacts extends Common
{
	/**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如CRM模块用crm作为数据表前缀
     */
	protected $name = 'crm_contacts';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
	protected $autoWriteTimestamp = true;

	/**
     * [getDataList 联系人list]
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
    	$business_id = $request['business_id'];
		unset($request['scene_id']);
		unset($request['search']);
		unset($request['user_id']);
		unset($request['is_excel']);		    	
		unset($request['business_id']);		    	

        $request = $this->fmtRequest( $request );
        $requestMap = $request['map'] ? : [];

		$sceneModel = new \app\admin\model\Scene();
		if ($scene_id) {
			//自定义场景
			$sceneMap = $sceneModel->getDataById($scene_id, $user_id, 'contacts') ? : [];
		} else {
			//默认场景
			$sceneMap = $sceneModel->getDefaultData('contacts', $user_id) ? : [];
		}
		$searchMap = [];
		if ($search) {
			//普通筛选
			$searchMap = function($query) use ($search){
			        $query->where('contacts.name',array('like','%'.$search.'%'))
			        	->whereOr('contacts.mobile',array('like','%'.$search.'%'))
			        	->whereOr('contacts.telephone',array('like','%'.$search.'%'));
			};			
			// $sceneMap['name'] = ['condition' => 'contains','value' => $search,'form_type' => 'text','name' => '联系人姓名'];
		}
		//优先级：普通筛选>高级筛选>场景
		$map = $requestMap ? array_merge($sceneMap, $requestMap) : $sceneMap;
		//高级筛选
		$map = where_arr($map, 'crm', 'contacts', 'index');		
		//权限
		$a = 'index';
		if ($is_excel) $a = 'excelExport';		
		$authMap = [];
		$auth_user_ids = $userModel->getUserByPer('crm', 'contacts', $a);
		if (isset($map['contacts.owner_user_id'])) {
			if (!is_array($map['contacts.owner_user_id'][1])) {
				$map['contacts.owner_user_id'][1] = [$map['contacts.owner_user_id'][1]];
			}
			if ($map['contacts.owner_user_id'][0] == 'neq') {
				$auth_user_ids = array_diff($auth_user_ids, $map['contacts.owner_user_id'][1]) ? : [];	//取差集	
			} else {
				$auth_user_ids = array_intersect($map['contacts.owner_user_id'][1], $auth_user_ids) ? : [];	//取交集	
			}
	        unset($map['contacts.owner_user_id']);
	    }		    
	    $auth_user_ids = array_merge(array_unique(array_filter($auth_user_ids))) ? : ['-1'];
	    //负责人、相关团队
	    $authMap['contacts.owner_user_id'] = ['in',$auth_user_ids];		
		//联系人商机
		if ($business_id) {
			$contacts_id = Db::name('crm_contacts_business')->where(['business_id' => $business_id])->column('contacts_id');
			if ($contacts_id) {
		    	$map['contacts.contacts_id'] = array('in',$contacts_id);
		    }else{
		    	$map['contacts.contacts_id'] = array('eq',-1);
		    }
		}	    
		//列表展示字段
		// $indexField = $fieldModel->getIndexField('crm_contacts', $user_id); 
		$userField = $fieldModel->getFieldByFormType('crm_contacts', 'user'); //人员类型
		$structureField = $fieldModel->getFieldByFormType('crm_contacts', 'structure');  //部门类型			

		if ($request['order_type'] && $request['order_field']) {
			$order = trim($request['order_field']).' '.trim($request['order_type']);
		} else {
			$order = 'contacts.update_time desc';
		}	
		$readAuthIds = $userModel->getUserByPer('crm', 'contacts', 'read');
        $updateAuthIds = $userModel->getUserByPer('crm', 'contacts', 'update');
        $deleteAuthIds = $userModel->getUserByPer('crm', 'contacts', 'delete');		
		$list = db('crm_contacts')
				->alias('contacts')
				->join('__CRM_CUSTOMER__ customer','contacts.customer_id = customer.customer_id','LEFT')
				->where($map)
				->where($searchMap)
				->where($authMap)
        		->limit(($request['page']-1)*$request['limit'], $request['limit'])
        		->field('contacts.*,customer.name as customer_name')
        		// ->field('contacts_id,'.implode(',',$indexField)
        		->order($order)
        		->select();	
        $dataCount = db('crm_contacts')
        			->alias('contacts')
        			->join('__CRM_CUSTOMER__ customer','contacts.customer_id = customer.customer_id','LEFT')
        			->where($map)->where($searchMap)->where($authMap)->count('contacts_id');
        foreach ($list as $k=>$v) {
        	$list[$k]['create_user_id_info'] = isset($v['create_user_id']) ? $userModel->getUserById($v['create_user_id']) : [];
        	$list[$k]['owner_user_id_info'] = isset($v['owner_user_id']) ? $userModel->getUserById($v['owner_user_id']) : [];
        	$list[$k]['customer_id_info']['customer_id'] = $v['customer_id'] ? : '';
        	$list[$k]['customer_id_info']['name'] = $v['customer_name'] ? : '';
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
	 * 创建联系人主表信息
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
		$arrFieldAtt = $fieldModel->getArrayField('crm_contacts');
		foreach ($arrFieldAtt as $k=>$v) {
			$param[$v] = arrayToString($param[$v]);
		}
		if ($this->data($param)->allowField(true)->isUpdate(false)->save()) {
			$data = [];
			$data['contacts_id'] = $this->contacts_id;
			return $data;
		} else {
			$this->error = '添加失败';
			return false;
		}			
	}
	
	//根据IDs获取数组
	public function getDataByStr($idstr)
	{
		$idArr = stringToArray($idstr);
		if (!$idArr) {
			return [];
		}
		$list = Db::name('CrmContacts')->where(['contacts_id' => ['in',$idArr]])->select();
		return $list;
	}
	
	/**
	 * 编辑联系人主表信息
	 * @author Michael_xu
	 * @param  
	 * @return                            
	 */	
	public function updateDataById($param, $contacts_id = '')
	{
		$dataInfo = $this->getDataById($contacts_id);
		if (!$dataInfo) {
			$this->error = '数据不存在或已删除';
			return false;
		}
		$param['contacts_id'] = $contacts_id;
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
		$arrFieldAtt = $fieldModel->getArrayField('crm_contacts');
		foreach ($arrFieldAtt as $k=>$v) {
			$param[$v] = arrayToString($param[$v]);
		}

		if ($this->allowField(true)->save($param, ['contacts_id' => $contacts_id])) {
			//修改记录
			updateActionLog($param['user_id'], 'crm_contacts', $contacts_id, $dataInfo->data, $param);
			$data = [];
			$data['contacts_id'] = $contacts_id;
			return $data;
		} else {
			$this->error = '编辑失败';
			return false;
		}					
	}

	/**
     * 联系人数据
     * @param  $id 联系人ID
     * @return 
     */	
   	public function getDataById($id = '')
   	{   		
   		$map['contacts_id'] = $id;
		$dataInfo = $this->where($map)->find();
		if (!$dataInfo) {
			$this->error = '暂无此数据';
			return false;
		}
		$userModel = new \app\admin\model\User();
		$dataInfo['create_user_id_info'] = isset($dataInfo['create_user_id']) ? $userModel->getUserById($dataInfo['create_user_id']) : [];
		$dataInfo['owner_user_id_info'] = isset($dataInfo['owner_user_id']) ? $userModel->getUserById($dataInfo['owner_user_id']) : []; 
		$dataInfo['customer_id_info'] = db('crm_customer')->where(['customer_id' => $dataInfo['customer_id']])->field('customer_id,name')->find();
		return $dataInfo;
   	}

	/**
     * [联系人转移]
     * @author Michael_xu
     * @param ids 联系人ID数组
     * @param owner_user_id 变更负责人
     * @param is_remove 1移出，2转为团队成员
     * @return            
     */	
    public function transferDataById($ids, $owner_user_id, $type = 1, $is_remove)
    {
	    $settingModel = new \app\crm\model\Setting();      	
    	foreach ($ids as $id) {
			$data = [];
	        $data['owner_user_id'] = $owner_user_id;
	        $data['update_time'] = time(); 
	        db('crm_contacts')->where(['contacts_id' => $id])->update($data);
    	}
    	return true;
    }      	
}