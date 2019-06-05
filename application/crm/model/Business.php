<?php
// +----------------------------------------------------------------------
// | Description: 商机
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com
// +----------------------------------------------------------------------
namespace app\crm\model;

use think\Db;
use app\admin\model\Common;
use think\Request;
use think\Validate;

class Business extends Common
{
	/**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如CRM模块用crm作为数据表前缀
     */
	protected $name = 'crm_business';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
	protected $autoWriteTimestamp = true;

	/**
     * [getDataList 商机list]
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
    	$contacts_id = $request['contacts_id'];
		unset($request['scene_id']);
		unset($request['search']);
		unset($request['user_id']);
		unset($request['contacts_id']);    	

        $request = $this->fmtRequest( $request );
        $requestMap = $request['map'] ? : [];
		$sceneModel = new \app\admin\model\Scene();
		if ($scene_id) {
			//自定义场景
			$sceneMap = $sceneModel->getDataById($scene_id, $user_id, 'business') ? : [];
		} else {
			//默认场景
			$sceneMap = $sceneModel->getDefaultData('business', $user_id) ? : [];
		}
		if ($search) {
			//普通筛选
			$sceneMap['name'] = ['condition' => 'contains','value' => $search,'form_type' => 'text','name' => '商机名称'];
		}
		if (isset($requestMap['type_id'])) {
			$requestMap['type_id']['value'] = $requestMap['type_id']['type_id'];
			if ($requestMap['type_id']['status_id']) $requestMap['status_id']['value'] = $requestMap['type_id']['status_id'];
		}
		$partMap = [];
		//优先级：普通筛选>高级筛选>场景
		if ($sceneMap['ro_user_id'] && $sceneMap['rw_user_id']) {
			//相关团队查询
			$map = $requestMap;
			$partMap = function($query) use ($sceneMap){
			        $query->where('business.ro_user_id',array('like','%,'.$sceneMap['ro_user_id'].',%'))
			        	->whereOr('business.rw_user_id',array('like','%,'.$sceneMap['rw_user_id'].',%'));
			};
		} else {
			$map = $requestMap ? array_merge($sceneMap, $requestMap) : $sceneMap;
		}
		//高级筛选
		$map = where_arr($map, 'crm', 'business', 'index');
		$authMap = [];
		if (!$partMap) {
			$auth_user_ids = $userModel->getUserByPer('crm', 'business', 'index');
			if (isset($map['business.owner_user_id'])) {
				if (!is_array($map['business.owner_user_id'][1])) {
					$map['business.owner_user_id'][1] = [$map['business.owner_user_id'][1]];
				}	
				if ($map['business.owner_user_id'][0] == 'neq') {
					$auth_user_ids = array_diff($auth_user_ids, $map['business.owner_user_id'][1]) ? : [];	//取差集	
				} else {
					$auth_user_ids = array_intersect($map['business.owner_user_id'][1], $auth_user_ids) ? : [];	//取交集	
				}
		        unset($map['business.owner_user_id']);
		        $auth_user_ids = array_merge(array_unique(array_filter($auth_user_ids))) ? : ['-1'];
		        $authMap['business.owner_user_id'] = array('in',$auth_user_ids); 
		    } else {
		    	$authMapData = [];
		    	$authMapData['auth_user_ids'] = $auth_user_ids;
		    	$authMapData['user_id'] = $user_id;
		    	$authMap = function($query) use ($authMapData){
			        $query->where('business.owner_user_id',array('in',$authMapData['auth_user_ids']))
			        	->whereOr('business.ro_user_id',array('like','%,'.$authMapData['user_id'].',%'))
			        	->whereOr('business.rw_user_id',array('like','%,'.$authMapData['user_id'].',%'));
			    };
		    }
		}
		//联系人商机
		if ($contacts_id) {
			$business_id = Db::name('crm_contacts_business')->where(['contacts_id' => $contacts_id])->column('business_id');
			if ($business_id) {
		    	$map['business.business_id'] = array('in',$business_id);
		    }else{
		    	$map['business.business_id'] = array('eq',-1);
		    }
		}		
		//列表展示字段
		// $indexField = $fieldModel->getIndexField('crm_business', $user_id);	
		$userField = $fieldModel->getFieldByFormType('crm_business', 'user'); //人员类型
		$structureField = $fieldModel->getFieldByFormType('crm_business', 'structure');  //部门类型	

		if ($request['order_type'] && $request['order_field']) {
			$order = trim($request['order_field']).' '.trim($request['order_type']);
		} else {
			$order = 'business.update_time desc';
		}

		$readAuthIds = $userModel->getUserByPer('crm', 'business', 'read');
        $updateAuthIds = $userModel->getUserByPer('crm', 'business', 'update');
        $deleteAuthIds = $userModel->getUserByPer('crm', 'business', 'delete');			
		$list = db('crm_business')
				->alias('business')
				->join('__CRM_CUSTOMER__ customer','business.customer_id = customer.customer_id','LEFT')		
				->where($map)
				->where($partMap)
				->where($authMap)
        		->limit(($request['page']-1)*$request['limit'], $request['limit'])
        		->field('business.*,customer.name as customer_name')
        		// ->field('business_id,'.implode(',',$indexField))
        		->order($order)
        		->select();	
        $dataCount = db('crm_business')
        			->alias('business')
        			->join('__CRM_CUSTOMER__ customer','business.customer_id = customer.customer_id','LEFT')
        			->where($map)->where($partMap)->where($authMap)->count('business_id');
        foreach ($list as $k=>$v) {
            $list[$k]['customer_id_info']['customer_id'] = $v['customer_id'];
            $list[$k]['customer_id_info']['name'] = $v['customer_name'];
        	$list[$k]['create_user_id_info'] = isset($v['create_user_id']) ? $userModel->getUserById($v['create_user_id']) : [];
        	$list[$k]['owner_user_id_info'] = isset($v['owner_user_id']) ? $userModel->getUserById($v['owner_user_id']) : [];  
			foreach ($userField as $key => $val) {
        		$list[$k][$val.'_info'] = isset($v[$val]) ? $userModel->getListByStr($v[$val]) : [];
        	}
			foreach ($structureField as $key => $val) {
        		$list[$k][$val.'_info'] = isset($v[$val]) ? $structureModel->getDataByStr($v[$val]) : [];
        	}
        	$statusInfo = [];
        	$status_count = 0;
        	$statusInfo = db('crm_business_status')->where('status_id',$v['status_id'])->find();
        	if ($statusInfo['order_id'] < 99) {
				$status_count = db('crm_business_status')->where('type_id',['eq',$v['type_id']],['eq',''],'or')->count();
        	}

        	$list[$k]['status_id_info'] = $statusInfo['name'];//销售阶段
        	$list[$k]['type_id_info'] = db('crm_business_type')->where('type_id',$v['type_id'])->value('name');//商机状态组 
        	//进度
        	$list[$k]['status_progress'] = [$statusInfo['order_id'], $status_count+1];
			//权限
        	$roPre = $userModel->rwPre($user_id, $v['ro_user_id'], $v['rw_user_id'], 'read');
        	$rwPre = $userModel->rwPre($user_id, $v['ro_user_id'], $v['rw_user_id'], 'update');
			$permission = [];
			$is_read = 0;
			$is_update = 0;
			$is_delete = 0;
			if (in_array($v['owner_user_id'],$readAuthIds) || $roPre || $rwPre) $is_read = 1;
			if (in_array($v['owner_user_id'],$updateAuthIds) || $rwPre) $is_update = 1;
			if (in_array($v['owner_user_id'],$deleteAuthIds)) $is_delete = 1;	        
	        $permission['is_read'] = $is_read;
	        $permission['is_update'] = $is_update;
	        $permission['is_delete'] = $is_delete;
	        $list[$k]['permission']	= $permission;
        }    
        $data = [];
        $data['list'] = $list ? : [];
        $data['dataCount'] = $dataCount ? : 0;

        return $data;
    }

	/**
	 * 创建商机主表信息
	 * @author Michael_xu
	 * @param  
	 * @return                            
	 */	
	public function createData($param)
	{
		$fieldModel = new \app\admin\model\Field();
		$productModel = new \app\crm\model\Product();
		// 自动验证
		$validateArr = $fieldModel->validateField($this->name); //获取自定义字段验证规则
		$validate = new Validate($validateArr['rule'], $validateArr['message']);
		$result = $validate->check($param);
		if (!$result) {
			$this->error = $validate->getError();
			return false;
		}
		if (!$param['customer_id']) {
			$this->error = '请选择相关客户';
			return false;
		}

		//处理部门、员工、附件、多选类型字段
		$arrFieldAtt = $fieldModel->getArrayField('crm_business');
		foreach ($arrFieldAtt as $k=>$v) {
			$param[$v] = arrayToString($param[$v]);
		}

		$param['money'] = $param['money'] ? : '0.00';
		$param['discount_rate'] = $param['discount_rate'] ? : '0.00';
		if ($this->data($param)->allowField(true)->save()) {
			$business_id = $this->business_id;
			if ($param['product']) {
		        //产品数据处理
		        $resProduct = $productModel->createObject('crm_business', $param, $business_id);
				if ($resProduct == false) {
		        	$this->error = '产品添加失败';
		        	return false;
		        }		       
 		    }
			//添加商机日志
			$data_log['business_id'] = $business_id;
			$data_log['is_end'] = 0;
			$data_log['status_id'] = $param['status_id'];
			$data_log['create_time'] = time();
			$data_log['owner_user_id'] = $param['owner_user_id'];
			$data_log['remark'] = '新建商机';
			Db::name('CrmBusinessLog')->insert($data_log);
			
			$data = [];
			$data['business_id'] = $business_id;
			return $data;
		} else {
			$this->error = '添加失败';
			return false;
		}			
	}

	/**
	 * 编辑商机主表信息
	 * @author Michael_xu
	 * @param  
	 * @return                            
	 */	
	public function updateDataById($param, $business_id = '')
	{
		$productModel = new \app\crm\model\Product();
		$dataInfo = $this->getDataById($business_id);
		if (!$dataInfo) {
			$this->error = '数据不存在或已删除';
			return false;
		}
		$param['business_id'] = $business_id;
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
		$arrFieldAtt = $fieldModel->getArrayField('crm_business');
		foreach ($arrFieldAtt as $k=>$v) {
			$param[$v] = arrayToString($param[$v]);
		}

		$param['money'] = $param['money'] ? : '0.00';
		$param['discount_rate'] = $param['discount_rate'] ? : '0.00';
		if ($this->allowField(true)->save($param, ['business_id' => $business_id])) {
			//产品数据处理
	        $resProduct = $productModel->createObject('crm_business', $param, $business_id);
			//修改记录
			updateActionLog($param['user_id'], 'crm_business', $business_id, $dataInfo->data, $param);
			$data = [];
			$data['business_id'] = $business_id;
			return $data;
		} else {
			$this->rollback();
			$this->error = '编辑失败';
			return false;
		}					
	}

	/**
     * 商机数据
     * @param  $id 商机ID
     * @return 
     */	
   	public function getDataById($id = '')
   	{
		$dataInfo = $this->get($id);
		if (!$dataInfo) {
			$this->error = '暂无此数据';
			return false;
		}
		$userModel = new \app\admin\model\User();
		$dataInfo['create_user_id_info'] = isset($dataInfo['create_user_id']) ? $userModel->getUserById($dataInfo['create_user_id']) : [];
		$dataInfo['owner_user_id_info'] = isset($dataInfo['owner_user_id']) ? $userModel->getUserById($dataInfo['owner_user_id']) : []; 
		$dataInfo['type_id_info'] = db('crm_business_type')->where(['type_id' => $dataInfo['type_id']])->value('name');
		$dataInfo['status_id_info'] = db('crm_business_status')->where(['status_id' => $dataInfo['status_id']])->value('name');
		$dataInfo['customer_id_info'] = db('crm_customer')->where(['customer_id' => $dataInfo['customer_id']])->field('customer_id,name')->find();
		// $dataInfo['remark'] = db('crm_business_log')->where(['business_id' => $id,'is_end' => ['gt',0]])->order('create_time desc')->value('remark'); //商机状态推进结束备注
		return $dataInfo;
   	}
	
	//根据IDs获取数组
	public function getDataByStr($idstr)
	{
		$idArr = stringToArray($idstr);
		if (!$idArr) {
			return [];
		}
		$list = Db::name('CrmBusiness')->where(['business_id' => ['in',$idArr]])->select();
		return $list;
	}
	
	/**
     * [商机漏斗]
     * @author Michael_xu
     * @param     [string]                   $request [查询条件]
     * @return    [array]                    
     */		
	public function getFunnel($request)
    {
    	$userModel = new \app\admin\model\User();
		$where = [];
		//时间段
		if(!empty($request['type'])){
            $between_time = getTimeByType($request['type']);
            $start_time = $between_time[0];
            $end_time = $between_time[1];
        }else{
        	$start_time = $request['start_time'];
			$end_time = $request['end_time'];
        }
        
		$create_time = [];
		if ($start_time && $end_time) {
			$where['create_time'] = array('between',array($start_time,$end_time));
		}
		$where['owner_user_id'] = array('in',$request['userIds']);

		//商机状态组
		$default_type_id = db('crm_business_type')->order('type_id asc')->value('type_id');
		
		$type_id = $request['type_id'] ? $request['type_id'] : $default_type_id;
		
		$statusList = db('crm_business_status')->where(['type_id' => $type_id])->select();
		$str = getFieldArray($statusList,'status_id');
		//$temmpp['status_id'] = ['in',$str]; 
		//$temmpp['create_time'] = $where['create_time'];
		//$temmpp['owner_user_id'] = $where['owner_user_id'];
		
		//$logList = Db::name('CrmBusinessLog')->where($temmpp)->order('business_id desc,create_time desc')->select();
		//赢单 
		$map['create_time'] = $where['create_time'];
		$map['owner_user_id'] = ['in',$request['userIds']];
		$sum_ying = Db::name('CrmBusiness')->where($map)->where('status_id=1')->sum('money');
		//输单
		$sum_shu = Db::name('CrmBusiness')->where($map)->where('status_id=2')->sum('money');
		$sum_money = 0;
		$dataAry = array();
		foreach ($statusList as $k=>$v) {
			$where['type_id'] = $type_id;
			$where['status_id'] = $v['status_id'];
			$statusList[$k]['status_name'] = $v['name'];
			$statusList[$k]['count'] = db('crm_business')->where($where)->count(); 
			$statusList[$k]['money'] = db('crm_business')->where($where)->sum('money'); //商机金额
			
			$sum_money += $statusList[$k]['money'];
			//$statusList[$k]['status_name'] = $v['name'];
			//根据商机查询 商机组
			/* if (!$logList) {
				$statusList[$k]['count'] += 0;
				$statusList[$k]['money'] += 0;
			} else {
				foreach ($logList as $key =>$value) {
					if ($value['status_id'] == $v['status_id']) {
						$statusList[$k]['count'] += 1; //商机数
						$statusList[$k]['money'] += db('crm_business')->where('business_id = '.$value['business_id'])->sum('money'); //商机金额
					} else {
						$statusList[$k]['count'] += 0;
						$statusList[$k]['money'] += 0;
					}
				}
			} */
		}
		$data['list'] = $statusList;
		$data['sum_ying'] = $sum_ying;
		$data['sum_shu'] = $sum_shu;
		$data['sum_money'] = $sum_money;
        return $data ? : [];
    } 

	/**
     * [商机转移]
     * @author Michael_xu
     * @param ids 商机ID数组
     * @param owner_user_id 变更负责人
     * @param is_remove 1移出，2转为团队成员
     * @return            
     */	
    public function transferDataById($ids, $owner_user_id, $type = 1, $is_remove)
    {
	    $settingModel = new \app\crm\model\Setting();   
	    $errorMessage = [];    	
    	foreach ($ids as $id) {
    		$businessInfo = db('crm_business')->where(['business_id' => $id])->find();
			$data = [];
	        $data['owner_user_id'] = $owner_user_id;
	        $data['update_time'] = time(); 
			if (!db('crm_business')->where(['business_id' => $id])->update($data)) {
	            $errorMessage[] = '商机：'.$businessInfo['name'].'"转移失败，错误原因：数据出错；';
	            continue;
	        }	        
	        //团队成员
	        $teamData = [];
            $teamData['type'] = $type; //权限 1只读2读写
            $teamData['user_id'] = [$businessInfo['owner_user_id']]; //协作人
            $teamData['types'] = 'crm_business'; //类型
            $teamData['types_id'] = $id; //类型ID
            $teamData['is_del'] = ($is_remove == 1) ? 1 : '';
            $res = $settingModel->createTeamData($teamData); 
    	}
    	if ($errorMessage) {
			return $errorMessage;
    	} else {
    		return true;
    	}
    }    	
}