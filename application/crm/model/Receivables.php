<?php
// +----------------------------------------------------------------------
// | Description: 回款
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com
// +----------------------------------------------------------------------
namespace app\crm\model;

use think\Db;
use app\admin\model\Common;
use think\Request;
use think\Validate;

class Receivables extends Common
{
	/**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如CRM模块用crm作为数据表前缀
     */
	protected $name = 'crm_receivables';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
	protected $autoWriteTimestamp = true;
	private $statusArr = ['0'=>'待审核','1'=>'审核中','2'=>'审核通过','3'=>'已拒绝','4'=>'已撤回'];

	/**
     * [getDataList 回款list]
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
		unset($request['scene_id']);
		unset($request['search']);
		unset($request['user_id']);	    	

        $request = $this->fmtRequest( $request );
        $requestMap = $request['map'] ? : [];

		$sceneModel = new \app\admin\model\Scene();
		if ($scene_id) {
			//自定义场景
			$sceneMap = $sceneModel->getDataById($scene_id, $user_id, 'receivables') ? : [];
		} else {
			//默认场景
			$sceneMap = $sceneModel->getDefaultData('receivables', $user_id) ? : [];
		}
		if ($search) {
			//普通筛选
			$sceneMap['number'] = ['condition' => 'contains','value' => $search,'form_type' => 'text','name' => '回款编号'];
		}
		//优先级：普通筛选>高级筛选>场景
		$map = $requestMap ? array_merge($sceneMap, $requestMap) : $sceneMap;
		//高级筛选
		$map = where_arr($map, 'crm', 'receivables', 'index');

		//权限
		$authMap = [];
		$auth_user_ids = $userModel->getUserByPer('crm', 'receivables', 'index');
		if (isset($map['receivables.owner_user_id'])) {
			if (!is_array($map['receivables.owner_user_id'][1])) {
				$map['receivables.owner_user_id'][1] = [$map['receivables.owner_user_id'][1]];
			}
			if ($map['receivables.owner_user_id'][0] == 'neq') {
				$auth_user_ids = array_diff($auth_user_ids, $map['receivables.owner_user_id'][1]) ? : [];	//取差集	
			} else {
				$auth_user_ids = array_intersect($map['receivables.owner_user_id'][1], $auth_user_ids) ? : [];	//取交集
			}
	        unset($map['receivables.owner_user_id']);
	    }		    
	    $auth_user_ids = array_merge(array_unique(array_filter($auth_user_ids))) ? : ['-1'];
	    //负责人、相关团队
	    $authMap['receivables.owner_user_id'] = ['in',$auth_user_ids];

		//人员类型
		$userField = $fieldModel->getFieldByFormType('crm_receivables', 'user');
		$structureField = $fieldModel->getFieldByFormType('crm_receivables', 'structure');  //部门类型	    			

		if ($request['order_type'] && $request['order_field']) {
			$order = trim($request['order_field']).' '.trim($request['order_type']);
		} else {
			$order = 'receivables.update_time desc';
		}

		$readAuthIds = $userModel->getUserByPer('crm', 'receivables', 'read');
        $updateAuthIds = $userModel->getUserByPer('crm', 'receivables', 'update');
        $deleteAuthIds = $userModel->getUserByPer('crm', 'receivables', 'delete');			
		$list = db('crm_receivables')
				->alias('receivables')
				->join('__CRM_CUSTOMER__ customer','receivables.customer_id = customer.customer_id','LEFT')		
				->join('__CRM_CONTRACT__ contract','receivables.contract_id = contract.contract_id','LEFT')		
				->where($map)
				->where($authMap)
				->limit(($request['page']-1)*$request['limit'], $request['limit'])
				->field('receivables.*,customer.name as customer_name,contract.name as contract_name,contract.num as contract_num,contract.money as contract_money')
				->order($order)
				->select();	
        $dataCount = db('crm_receivables')
        			->alias('receivables')
        			->join('__CRM_CUSTOMER__ customer','receivables.customer_id = customer.customer_id','LEFT')		
					->join('__CRM_CONTRACT__ contract','receivables.contract_id = contract.contract_id','LEFT')
        			->where($map)->where($authMap)->count('receivables_id');
        foreach ($list as $k=>$v) {
        	$list[$k]['create_user_id_info'] = $v['create_user_id'] ? $userModel->getUserById($v['create_user_id']) : [];
        	$list[$k]['owner_user_id_info'] = $v['owner_user_id'] ? $userModel->getUserById($v['owner_user_id']) : [];
        	$list[$k]['customer_id_info']['customer_id'] = $v['customer_id'] ? : '';
        	$list[$k]['customer_id_info']['name'] = $v['customer_name'] ? : '';	
        	$list[$k]['contract_id_info']['contract_id'] = $v['contract_id'] ? : '';
        	$list[$k]['contract_id_info']['name'] = $v['contract_num'] ? : '';
        	$list[$k]['contract_id_info']['money'] = $v['contract_money'] ? : '0.00';
        	$list[$k]['contract_money'] = $v['contract_money'] ? : '0.00';  
			foreach ($userField as $key => $val) {
        		$list[$k][$val.'_info'] = isset($v[$val]) ? $userModel->getListByStr($v[$val]) : [];
        	}
			foreach ($structureField as $key => $val) {
        		$list[$k][$val.'_info'] = isset($v[$val]) ? $structureModel->getDataByStr($v[$val]) : [];
        	}
        	$list[$k]['check_status_info'] = $this->statusArr[$v['check_status']];
        	//期数
        	$plan_num = db('crm_receivables_plan')->where(['plan_id' => $v['plan_id']])->value('num');
        	$list[$k]['num'] = $plan_num ? : '';
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
	 * 创建回款信息
	 * @author Michael_xu
	 * @param  
	 * @return                            
	 */	
	public function createData($param)
	{
		if (!$param['customer_id']) {
			$this->error = '请先选择客户';
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
		$arrFieldAtt = $fieldModel->getArrayField('crm_receivables');
		foreach ($arrFieldAtt as $k=>$v) {
			$param[$v] = arrayToString($param[$v]);
		}
		if ($this->data($param)->allowField(true)->save()) {
			$data = [];
			$data['receivables_id'] = $this->receivables_id;
			return $data;
		} else {
			$this->error = '添加失败';
			return false;
		}			
	}

	/**
	 * 根据对象ID 获取该年各个月回款情况
	 * @param [year] [哪一年]
	 * @param [owner_user_id] [哪个员工]
	 * @param [start_time] [开始时间]
	 * @param [end_time] [结束时间]
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
		$data = $this->where($map)->where(['return_time' => ['between',[$start,$stop]]])->sum('money');
		return $data;
	}

	/**
	 * 编辑回款主表信息
	 * @author Michael_xu
	 * @param  
	 * @return                            
	 */	
	public function updateDataById($param, $receivables_id = '')
	{
		$userModel = new \app\admin\model\User();
		$dataInfo = db('crm_receivables')->where(['receivables_id' => $receivables_id])->find();
		if (!$dataInfo) {
			$this->error = '数据不存在或已删除';
			return false;
		}
		$param['receivables_id'] = $receivables_id;
		//过滤不能修改的字段
		$unUpdateField = ['create_user_id','is_deleted','delete_time','delete_user_id'];
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
		$arrFieldAtt = $fieldModel->getArrayField('crm_receivables');
		foreach ($arrFieldAtt as $k=>$v) {
			$param[$v] = arrayToString($param[$v]);
		}

		if ($this->allowField(true)->save($param, ['receivables_id' => $receivables_id])) {
			//修改记录
			updateActionLog($param['user_id'], 'crm_receivables', $receivables_id, $dataInfo, $param);
			//站内信
            $createUserInfo = $userModel->getDataById($param['user_id']);
            $send_user_id = stringToArray($param['check_user_id']);
            $sendContent = $createUserInfo['realname'].'提交了回款【'.$dataInfo['number'].'】,需要您审批';
            if ($send_user_id) {
            	sendMessage($send_user_id, $sendContent, $receivables_id, 1);
            }			

			$data = [];
			$data['receivables_id'] = $receivables_id;
			return $data;
		} else {
			$this->error = '编辑失败';
			return false;
		}					
	}

	/**
     * 回款数据
     * @param  $id 回款ID
     * @return 
     */	
   	public function getDataById($id = '')
   	{   		
   		$map['receivables_id'] = $id;
		$dataInfo = $this->where($map)->find();
		if (!$dataInfo) {
			$this->error = '暂无此数据';
			return false;
		}
		$userModel = new \app\admin\model\User();
		$dataInfo['create_user_id_info'] = isset($dataInfo['create_user_id']) ? $userModel->getUserById($dataInfo['create_user_id']) : [];
		$dataInfo['owner_user_id_info'] = isset($dataInfo['owner_user_id']) ? $userModel->getUserById($dataInfo['owner_user_id']) : []; 
        $dataInfo['customer_id_info'] = $dataInfo['customer_id'] ? db('crm_customer')->where(['customer_id' => $dataInfo['customer_id']])->field('customer_id,name')->find() : [];  		
        $dataInfo['contract_id_info'] = $dataInfo['contract_id'] ? db('crm_contract')->where(['contract_id' => $dataInfo['contract_id']])->field('contract_id,name,money')->find() : [];  		
		$dataInfo['receivables_id'] = $id;
		return $dataInfo;
   	}
	
	/**
     * 回款&&合同统计(列表)
     * @param 
     * @return 
     */
	public function getstatisticsData($request)
	{	
		$userModel = new \app\admin\model\User();
		$structureModel = new \app\admin\model\Structure();
    	$fieldModel = new \app\admin\model\Field();
    	$contractModel = new \app\crm\model\Contract();  // model('Contract');

		if (!$request['year']) {
			$request['year'] = date('Y');
		}
		
		if ($request['month']) {
			$start = strtotime($request['year'].'-'.$request['month'].'-01');
			if ($request['month'] == '12') {
				$next_year = $request['year']+1;
				$end = strtotime($next_year.'-01-01');
			} else {
				$next_month = $request['month']+1;
				$end = strtotime($request['year'].'-'.$next_month.'-01');
			}
		} else {
			$next_year = $request['year']+1;
			$start = strtotime($request['year'].'-01-01');
			$end = strtotime($next_year.'-01-01');
		}
		
		if ($request['user_id']) {
			$map_user_ids[] = $request['user_id'];
		} else if($request['structure_id']){
			$map_user_ids = $userModel->getSubUserByStr($request['structure_id']);
		}
		
		$perUserIds = $userModel->getUserByPer(); //权限范围内userIds
		$userIds = $map_user_ids ? array_intersect($map_user_ids, $perUserIds) : $perUserIds; //数组交集
		$useridstr = implode(',',$userIds);
		
		$map['owner_user_id'] = ['in',$useridstr];
		//$map['rec.check_status'] = 3;
		$map['create_time'] = array('between',array($start,$end));
		//合同有多个回款
		
		//根据时间 查合同
		if( $request['type'] == '1'){ 
			$map_type['contract.owner_user_id'] = ['in',$useridstr];
			$map_type['contract.create_time'] = array('between',array($start,$end));
			
			$userField = $fieldModel->getFieldByFormType('crm_contract', 'user');
			$structureField = $fieldModel->getFieldByFormType('crm_contract', 'structure');  //部门类型
	
			$list =  Db::name('CrmContract')->alias('contract')
					->join('__CRM_CUSTOMER__ customer','contract.customer_id = customer.customer_id','LEFT')		
					->join('__CRM_BUSINESS__ business','contract.contract_id = business.business_id','LEFT')
					->join('__CRM_CONTACTS__ contacts','contract.contract_id = contacts.contacts_id','LEFT')
					->field('contract.*,customer.name as customer_name,business.name as business_name,contacts.name as contacts_name')
					->where($map_type)->select();
			if($list){
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
					$list[$k]['contacts_id_info']['customer_id'] = $v['contacts_id'];
		        	$list[$k]['contacts_id_info']['name'] = $v['contacts_name'];        	
		        	$list[$k]['check_status_info'] = $this->statusArr[$v['check_status']]; 
		        	           	  		
		        }  	
				return $list;
			} else {
				return array();
			}
		} else { //回款
			$map_rec['receivables.owner_user_id'] = ['in',$useridstr];
			//$map['rec.check_status'] = 3;
			$map_rec['receivables.create_time'] = array('between',array($start,$end));
			$map_rec['receivables.check_status'] = 2; 

			$userField = $fieldModel->getFieldByFormType('crm_receivables', 'user');
			$structureField = $fieldModel->getFieldByFormType('crm_receivables', 'structure');  //部门类型	    			

			$list = db('crm_receivables')
					->alias('receivables')
					->join('__CRM_CUSTOMER__ customer','receivables.customer_id = customer.customer_id','LEFT')		
					->join('__CRM_CONTRACT__ contract','receivables.contract_id = contract.contract_id','LEFT')		
					->where($map_rec)
					->field('receivables.*,customer.name as customer_name,contract.name as contract_name,contract.num as contract_num,contract.money as contract_money')
					->select();
	        foreach ($list as $k=>$v) {
	        	$list[$k]['create_user_id_info'] = $v['create_user_id'] ? $userModel->getUserById($v['create_user_id']) : [];
	        	$list[$k]['owner_user_id_info'] = $v['owner_user_id'] ? $userModel->getUserById($v['owner_user_id']) : [];
	        	$list[$k]['customer_id_info']['customer_id'] = $v['customer_id'] ? : '';
	        	$list[$k]['customer_id_info']['name'] = $v['customer_name'] ? : '';	
	        	$list[$k]['contract_id_info']['contract_id'] = $v['contract_id'] ? : '';
	        	$list[$k]['contract_id_info']['name'] = $v['contract_name'] ? : '';
	        	$list[$k]['contract_id_info']['money'] = $v['contract_money'] ? : '0.00';
	        	$list[$k]['contract_money'] = $v['contract_money'] ? : '0.00';  
				foreach ($userField as $key => $val) {
	        		$list[$k][$val.'_info'] = isset($v[$val]) ? $userModel->getListByStr($v[$val]) : [];
	        	}
				foreach ($structureField as $key => $val) {
	        		$list[$k][$val.'_info'] = isset($v[$val]) ? $structureModel->getDataByStr($v[$val]) : [];
	        	}
	        	$list[$k]['check_status_info'] = $this->statusArr[$v['check_status']];	     	 	
	        } 
			return $list;
		}
	}
	
	/**
     * [回款统计] //柱状图
     * @author Michael_xu
	 * @param request [查询条件]
	 * @param 
     * @return    [array]                    
     */		
	public function getStatistics($request)
	{
		$userModel = new \app\admin\model\User();
		
		$useridstr = implode(',',$request['userIds']);
		$charMonthArr = []; //按照月份
		$charQuarterArr = []; //按照季度
		$quarter = 0;
		$contractMoneyTotal = '';
		$receivablesMoneyTotal = '';
		//按照月份
		for ($i = 1; $i < 13; $i++) {
			$contractMoney = '0.00';
			$receivablesMoney = '0.00';
			$conQuarterMoney = '0.00';
			$reQuarterMoney = '0.00';
			$start_time = strtotime($request['year'].'-'.$i.'-01');
			$next_i = $i+1;
			$end_time = strtotime($request['year'].'-'.$next_i.'-01')-1;
			if ($i == 12) {
				$next_year = $request['year']+1;
				$end_time = strtotime($next_year.'-01-01')-1;
			}
            $where_receivables = [];
			$where_contract = [];
    		$where_contract['owner_user_id'] = ['in',$useridstr];
			$where_receivables['owner_user_id'] = ['in',$useridstr];
    		$where_contract['create_time'] = array('between',array($start_time,$end_time));
			$where_receivables['return_time'] = array('between',array( date('Y-m-d',$start_time),date('Y-m-d',$end_time)));
			$where_receivables['owner_user_id'] = ['in',$useridstr];
    		$where_receivables['check_status'] = $where_contract['check_status'] = 2; //审核通过
    		$contractMoney = db('crm_contract')->where($where_contract)->sum('money'); 
			$receivablesMoney = db('crm_receivables')->where($where_receivables)->sum('money');
			
    		$conQuarterMoney += $charMonthArr[$i]['contractMoney'] = $contractMoney;
    		$reQuarterMoney += $charMonthArr[$i]['receivablesMoney'] = $receivablesMoney;
    		if (in_array($i, array('3','4','6','9','12'))) {    			
    			//季度
    			$quarter++;	
				$charQuarterArr[$quarter]['conQuarterMoney'] = $conQuarterMoney;
    			$charQuarterArr[$quarter]['reQuarterMoney'] = $reQuarterMoney;    			
    			$conQuarterMoney = '0.00';
    			$reQuarterMoney = '0.00';
    		}
    		$contractMoneyTotal += $contractMoney;
    		$receivablesMoneyTotal += $receivablesMoney;
    	}
    	$data['charMonthArr'] = $charMonthArr; //月度统计
    	$data['charQuarterArr'] = $charQuarterArr; //季度统计
    	$data['contractMoneyTotal'] = $contractMoneyTotal ? : 0.00;
    	$data['receivablesMoneyTotal'] = $receivablesMoneyTotal ? : 0.00;
    	return $data;
	} 

	/**
     * [合同回款金额]
     * @author Michael_xu
	 * @param contract_id 合同ID
	 * @param 
     * @return                    
     */		
	public function getMoneyByContractId($contract_id)
	{
		$doneMoney = $this->where(['contract_id' => $contract_id,'check_status' => 2])->sum('money');
		$contractMoney = db('crm_contract')->where(['contract_id' => $contract_id])->value('money');
		$unMoney = $contractMoney-$doneMoney;
		$data['doneMoney'] = $doneMoney ? : '0.00';
		$data['unMoney'] = $unMoney ? : '0.00';
		$data['contractMoney'] = $contractMoney ? : '0.00';
		return $data;
	}
}