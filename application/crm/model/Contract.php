<?php
// +----------------------------------------------------------------------
// | Description: 合同
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com
// +----------------------------------------------------------------------
namespace app\crm\model;

use think\Db;
use app\admin\model\Common;
use think\Request;
use think\Validate;

class Contract extends Common
{
	/**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如CRM模块用crm作为数据表前缀
     */
	protected $name = 'crm_contract';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
	protected $autoWriteTimestamp = true;
	private $statusArr = ['0'=>'待审核','1'=>'审核中','2'=>'审核通过','3'=>'已拒绝','4'=>'已撤回'];

	/**
     * [getDataList 合同list]
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
    	$receivablesModel = new \app\crm\model\Receivables();
		$search = $request['search'];
    	$user_id = $request['user_id'];
    	$scene_id = (int)$request['scene_id'];
		unset($request['scene_id']);
		unset($request['search']);
		unset($request['user_id']);    	

        $request = $this->fmtRequest( $request );
        $requestMap = $request['map'] ? : [];

		$sceneModel = new \app\admin\model\Scene();
		if ($scene_id) {
			//自定义场景
			$sceneMap = $sceneModel->getDataById($scene_id, $user_id, 'contract') ? : [];
		} else {
			//默认场景
			$sceneMap = $sceneModel->getDefaultData('contract', $user_id) ? : [];
		}
		if ($search) {
			//普通筛选
			$sceneMap['name'] = ['condition' => 'contains','value' => $search,'form_type' => 'text','name' => '合同名称'];
		}
		$partMap = [];
		//优先级：普通筛选>高级筛选>场景
		if ($sceneMap['contract.ro_user_id'] && $sceneMap['contract.rw_user_id']) {
			//相关团队查询
			$map = $requestMap;
			$partMap = function($query) use ($sceneMap){
			        $query->where('contract.ro_user_id',array('like','%,'.$sceneMap['ro_user_id'].',%'))
			        	->whereOr('contract.rw_user_id',array('like','%,'.$sceneMap['rw_user_id'].',%'));
			};
		} else {
			$map = $requestMap ? array_merge($sceneMap, $requestMap) : $sceneMap;
		}
		//高级筛选
		$map = where_arr($map, 'crm', 'contract', 'index');
		$order = ['contract.update_time desc'];	
		$authMap = [];
		if (!$partMap) {
			$auth_user_ids = $userModel->getUserByPer('crm', 'contract', 'index');
			if (isset($map['contract.owner_user_id'])) {
				if (!is_array($map['contract.owner_user_id'][1])) {
					$map['contract.owner_user_id'][1] = [$map['contract.owner_user_id'][1]];
				}				
				if ($map['contract.owner_user_id'][0] == 'neq') {
					$auth_user_ids = array_diff($auth_user_ids, $map['contract.owner_user_id'][1]) ? : [];	//取差集	
				} else {
					$auth_user_ids = array_intersect($map['contract.owner_user_id'][1], $auth_user_ids) ? : [];	//取交集
				}
		        unset($map['contract.owner_user_id']);
		        $auth_user_ids = array_merge(array_unique(array_filter($auth_user_ids))) ? : ['-1'];
		        $authMap['contract.owner_user_id'] = array('in',$auth_user_ids); 
		    } else {
		    	$authMapData = [];
		    	$authMapData['auth_user_ids'] = $auth_user_ids;
		    	$authMapData['user_id'] = $user_id;
		    	$authMap = function($query) use ($authMapData){
			        $query->where('contract.owner_user_id',array('in',$authMapData['auth_user_ids']))
			        	->whereOr('contract.ro_user_id',array('like','%,'.$authMapData['user_id'].',%'))
			        	->whereOr('contract.rw_user_id',array('like','%,'.$authMapData['user_id'].',%'));
			    };
		    }
		}
		//列表展示字段
		// $indexField = $fieldModel->getIndexField('crm_contract', $user_id);	
		//人员类型
		$userField = $fieldModel->getFieldByFormType('crm_contract', 'user');
		$structureField = $fieldModel->getFieldByFormType('crm_contract', 'structure');  //部门类型
	
		$readAuthIds = $userModel->getUserByPer('crm', 'contract', 'read');
        $updateAuthIds = $userModel->getUserByPer('crm', 'contract', 'update');
        $deleteAuthIds = $userModel->getUserByPer('crm', 'contract', 'delete');			
		$list = db('crm_contract')
				->alias('contract')
				->join('__CRM_CUSTOMER__ customer','contract.customer_id = customer.customer_id','LEFT')		
				->join('__CRM_BUSINESS__ business','contract.business_id = business.business_id','LEFT')	
				->join('__CRM_CONTACTS__ contacts','contract.contacts_id = contacts.contacts_id','LEFT')	
				->join('__CRM_RECEIVABLES_PLAN__ plan','contract.contract_id = plan.contract_id','LEFT')	
				->where($map)
				->where($partMap)
				->where($authMap)
        		->limit(($request['page']-1)*$request['limit'], $request['limit'])
        		->field('contract.*,customer.name as customer_name,business.name as business_name,contacts.name as contacts_name')
        		// ->field('contract_id,'.implode(',',$indexField))
        		->order($order)
        		->group('contract.contract_id')
        		->select();	
        $dataCount = db('crm_contract')
        			->alias('contract')
					->join('__CRM_CUSTOMER__ customer','contract.customer_id = customer.customer_id','LEFT')		
					->join('__CRM_BUSINESS__ business','contract.business_id = business.business_id','LEFT')
					->join('__CRM_CONTACTS__ contacts','contract.contacts_id = contacts.contacts_id','LEFT')
					->join('__CRM_RECEIVABLES_PLAN__ plan','contract.contract_id = plan.contract_id','LEFT')		
        			->where($map)->where($partMap)->where($authMap)->group('contract.contract_id')->count('contract.contract_id');
        foreach ($list as $k=>$v) {
        	$list[$k]['create_user_id_info'] = isset($v['create_user_id']) ? $userModel->getUserById($v['create_user_id']) : [];
        	$list[$k]['owner_user_id_info'] = isset($v['owner_user_id']) ? $userModel->getUserById($v['owner_user_id']) : [];
			foreach ($userField as $key => $val) {
        		$list[$k][$val.'_info'] = isset($v[$val]) ? $userModel->getListByStr($v[$val]) : [];
        	}
			foreach ($structureField as $key => $val) {
        		$list[$k][$val.'_info'] = isset($v[$val]) ? $structureModel->getDataByStr($v[$val]) : [];
        	}
        	$list[$k]['business_id_info']['business_id'] = $v['business_id'];
        	$list[$k]['business_id_info']['name'] = $v['business_name'];
        	$list[$k]['customer_id_info']['customer_id'] = $v['customer_id'];
        	$list[$k]['customer_id_info']['name'] = $v['customer_name'];
			$list[$k]['contacts_id_info']['contacts_id'] = $v['contacts_id'];
        	$list[$k]['contacts_id_info']['name'] = $v['contacts_name'];        	
        	$moneyInfo = [];
        	$moneyInfo = $receivablesModel->getMoneyByContractId($v['contract_id']);
        	$list[$k]['unMoney'] = $moneyInfo['unMoney'] ? : 0.00;
        	$list[$k]['check_status_info'] = $this->statusArr[$v['check_status']]; 
			$planInfo = [];
			$planInfo = db('crm_receivables_plan')->where(['contract_id' => $val['contract_id']])->find();
			$list[$k]['receivables_id'] = $planInfo['receivables_id'] ? : '';
			$list[$k]['remind_date'] = $planInfo['remind_date'] ? : '';
			$list[$k]['return_date'] = $planInfo['return_date'] ? : '';
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
        $data['list'] = $list;
        $data['dataCount'] = $dataCount ? : 0;
        $data['data']['sumMoney'] = $sumMoney ? : 0.00;
        $data['data']['unReceivablesMoney'] = $unReceivablesMoney ? : 0.00;
        return $data;
    }

	//根据IDs获取数组
	public function getDataByStr($idstr)
	{
		$idArr = stringToArray($idstr);
		if (!$idArr) {
			return [];
		}
		$list = Db::name('CrmContract')->where(['contract_id' => ['in',$idArr]])->select();
		return $list;
	}
	
	/**
	 * 创建合同信息
	 * @author Michael_xu
	 * @param  
	 * @return                            
	 */	
	public function createData($param)
	{
		$fieldModel = new \app\admin\model\Field();
		$userModel = new \app\admin\model\User();
		$productModel = new \app\crm\model\Product();
		// 自动验证
		$validateArr = $fieldModel->validateField($this->name); //获取自定义字段验证规则
		$validate = new Validate($validateArr['rule'], $validateArr['message']);

		$result = $validate->check($param);
		if (!$result) {
			$this->error = $validate->getError();
			return false;
		}		

		//处理部门、员工、附件、多选类型字段
		$arrFieldAtt = $fieldModel->getArrayField('crm_contract');
		foreach ($arrFieldAtt as $k=>$v) {
			$param[$v] = arrayToString($param[$v]);
		}

		if ($this->data($param)->allowField(true)->save()) {
			if ($param['product']) {
				//产品数据处理
		        $resProduct = $productModel->createObject('crm_contract', $param, $this->contract_id);	        
		        if ($resProduct == false) {
		        	$this->error = '产品添加失败';
		        	return false;
		        }
			}
            //站内信
            $createUserInfo = $userModel->getDataById($param['create_user_id']);
            $send_user_id = stringToArray($param['check_user_id']);
            $sendContent = $createUserInfo['realname'].'提交了合同【'.$param['name'].'】,需要您审批';
            if ($send_user_id) {
            	sendMessage($send_user_id, $sendContent, $this->contract_id, 1);
            }

			$data = [];
			$data['contract_id'] = $this->contract_id;
			return $data;
		} else {
			$this->error = '添加失败';
			return false;
		}			
	}

	/**
	 * 编辑合同主表信息
	 * @author Michael_xu
	 * @param  
	 * @return                            
	 */	
	public function updateDataById($param, $contract_id = '')
	{
		$productModel = new \app\crm\model\Product();
		$userModel = new \app\admin\model\User();
		$dataInfo = db('crm_contract')->where(['contract_id' => $contract_id])->find();
		//过滤不能修改的字段
		$unUpdateField = ['create_user_id','is_deleted','delete_time'];
		foreach ($unUpdateField as $v) {
			unset($param[$v]);
		}
		$param['contract_id'] = $contract_id;
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
		$arrFieldAtt = $fieldModel->getArrayField('crm_contract');
		foreach ($arrFieldAtt as $k=>$v) {
			$param[$v] = arrayToString($param[$v]);
		}

		if ($this->allowField(true)->save($param, ['contract_id' => $contract_id])) {
			//产品数据处理
	        $resProduct = $productModel->createObject('crm_contract', $param, $contract_id);			
			//修改记录
			updateActionLog($param['user_id'], 'crm_contract', $contract_id, $dataInfo, $param);
			//站内信
            $createUserInfo = $userModel->getDataById($param['user_id']);
            $send_user_id = stringToArray($param['check_user_id']);
            $sendContent = $createUserInfo['realname'].'提交了合同【'.$param['name'].'】,需要您审批';
            if ($send_user_id) {
            	sendMessage($send_user_id, $sendContent, $contract_id, 1);
            }			
			$data = [];
			$data['contract_id'] = $contract_id;
			return $data;
		} else {
			$this->error = '编辑失败';
			return false;
		}					
	}

	/**
     * 合同数据
     * @param  $id 合同ID
     * @return 
     */	
   	public function getDataById($id = '')
   	{   
   		$receivablesModel = new \app\crm\model\Receivables();
   		$userModel = new \app\admin\model\User();	
   		$map['contract_id'] = $id;
		$dataInfo = $this->where($map)->find();
		if (!$dataInfo) {
			$this->error = '暂无此数据';
			return false;
		}
		$dataInfo['create_user_info'] = isset($dataInfo['create_user_id']) ? $userModel->getUserById($dataInfo['create_user_id']) : [];
		$dataInfo['owner_user_id_info'] = isset($dataInfo['owner_user_id']) ? $userModel->getUserById($dataInfo['owner_user_id']) : []; 
		$dataInfo['business_id_info'] = $dataInfo['business_id'] ? db('crm_business')->where(['business_id' => $dataInfo['business_id']])->field('business_id,name')->find() : [];
        $dataInfo['customer_id_info'] = $dataInfo['customer_id'] ? db('crm_customer')->where(['customer_id' => $dataInfo['customer_id']])->field('customer_id,name')->find() : [];		
        //回款金额
        $receivablesMoney = $receivablesModel->getMoneyByContractId($id);
        $dataInfo['receivablesMoney'] = $receivablesMoney ? : [];
		return $dataInfo;
   	}

	/**
     * [合同转移]
     * @author Michael_xu
     * @param ids 合同ID数组
     * @param owner_user_id 变更负责人
     * @param is_remove 1移出，2转为团队成员
     * @return            
     */	
    public function transferDataById($ids, $owner_user_id, $type = 1, $is_remove)
    {
	    $settingModel = new \app\crm\model\Setting();  
	    $errorMessage = [];  	
    	foreach ($ids as $id) {
    		$contractInfo = [];
    		$contractInfo = db('crm_contract')->where(['contract_id' => $id])->find();
			if (in_array($contractInfo['check_status'],['0','1'])) {
	            $errorMessage[] = '合同：'.$contractInfo['name'].'"转移失败，错误原因：审批中，无法转移；';
	            continue;
	        }	     		

			$data = [];
	        $data['owner_user_id'] = $owner_user_id;
	        $data['update_time'] = time(); 
	        if (!db('crm_contract')->where(['contract_id' => $id])->update($data)) {
				$errorMessage[] = '合同：'.$contractInfo['name'].'"转移失败，错误原因：数据出错；';
	            continue;				      	
	        }
	        //团队成员
	        $teamData = [];
            $teamData['type'] = $type; //权限 1只读2读写
            $teamData['user_id'] = [$contractInfo['owner_user_id']]; //协作人
            $teamData['types'] = 'crm_contract'; //类型
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

	/**
	 * 根据对象ID 获取该年各个月合同金额
	 * @return [year] [哪一年]
	 * @return [owner_user_id] [哪个员工]
	 * @return [start_time] [开始时间]
	 * @return [end_time] [结束时间]
	 */
	public function getDataByUserId($param)
	{	
		if ($param['obj_type']) {
			if ($param['obj_type'] == 1) { //部门
				$userModel = new \app\admin\model\User();
			    $str = $userModel->getSubUserByStr($param['obj_id'], 1) ? : ['-1'];
				$map['owner_user_id'] = array('in',$str); 
			} else { //员工
				$map['owner_user_id'] = $param['obj_id']; 
			}
		}
		//审核状态
		$start = date('Y-m-d',$param['start_time']);
		$stop = date('Y-m-d',$param['end_time']);
		$map['check_status'] = 2;
		$data = $this->where($map)->where(['order_date' => ['between',[$start, $stop]]])->sum('money');
		return $data;
	}     		
}