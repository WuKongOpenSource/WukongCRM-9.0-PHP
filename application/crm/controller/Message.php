<?php
// +----------------------------------------------------------------------
// | Description: 消息模块
// +----------------------------------------------------------------------
// | Author: Michael_xu | gengxiaoxu@5kcrm.com 
// +----------------------------------------------------------------------

namespace app\crm\controller;

use app\admin\controller\ApiCommon;
use think\Hook;
use think\Request;

class Message extends ApiCommon
{
	/**
     * 用于判断权限
     * @permission 无限制
     * @allow 登录用户可访问
     * @other 其他根据系统设置
    **/    
    public function _initialize()
    {
        $action = [
            'permission'=>[''],
            'allow'=>['num','todaycustomer','followleads','followcustomer','checkcontract','checkreceivables','remindreceivablesplan','endcontract','remindcustomer']            
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());        
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
    } 

    /**
     * 系统通知
     * @author Michael_xu
     * @return 
     */
    public function index()
    {
    	$messageModel = model('Message');
		$param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id']; 
        $param['module_name'] = 'crm';       
        $data = $messageModel->getDataList($param);
        return resultArray(['data' => $data]);    	
    } 

    /**
     * 消息数
     * @author Michael_xu
     * @return 
     */
    public function num()
    {
        $userInfo = $this->userInfo;
        $configDataModel = model('ConfigData');
        $configData = $configDataModel->getData();
        $data = [];
        $todayCustomer = $this->todayCustomer(true);
        $data['todayCustomer'] = $todayCustomer['dataCount'] ? : 0;
        $followLeads = $this->followLeads();
        $data['followLeads'] = $followLeads['dataCount'] ? : 0;
        $followCustomer = $this->followCustomer(true);
        $data['followCustomer'] = $followCustomer['dataCount'] ? : 0;
        $checkContract = $this->checkContract();
        $data['checkContract'] = $checkContract['dataCount'] ? : 0; 
        $checkReceivables = $this->checkReceivables();
        $data['checkReceivables'] = $checkReceivables['dataCount'] ? : 0; 
        $remindReceivablesPlan = $this->remindReceivablesPlan();
        $data['remindReceivablesPlan'] = $remindReceivablesPlan['dataCount'] ? : 0;
        if ($configData['contract_config'] == 1) {
            $endContract = $this->endContract();
            $data['endContract'] = $endContract['dataCount'] ? : 0;  
        }
        //待进入公海提醒
        if ($configData['remind_config'] == 1) {
            $remindCustomer = $this->remindCustomer(true);
            $data['remindCustomer'] = $remindCustomer['dataCount'] ? : 0;            
        }                                          
        return resultArray(['data' => $data]);
    }

    /**
     * 今日需联系客户
     * @author Michael_xu
     * @return 
     */
    public function todayCustomer($getCount = '')
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $types = $param['types'];
        $type = $param['type'] ? : 1;
        $isSub = $param['isSub'] ? : '';
        if ($getCount == true) {
            $param['getCount'] = 1;
        }
        unset($param['types']);
        unset($param['type']);
        unset($param['isSub']);
        $customerModel = model('Customer');
        $todayTime = getTimeByType('today');

        $param['owner_user_id'] = $userInfo['id'];
        if ($isSub) {
            $param['owner_user_id'] = array('in',getSubUserId(false));
        }
        switch ($type) {
            case '1' : 
                $param['next_time'] = ['between',array($todayTime[0],$todayTime[1])]; 
                // $param['follow'] = ['neq','已跟进'];
                break;
            case '2' : 
                $param['next_time'] = ['between',array(1,time())]; 
                // $param['today_param'] = 'customer.next_time>record.update_time'; 
                break;
            case '3' : 
                $param['next_time'] = ['between',array($todayTime[0],$todayTime[1])];
                $param['follow'] = ['eq','已跟进'];
                break;
        }
        $data = $customerModel->getDataList($param);
        if ($types == 'list') {
            return resultArray(['data' => $data]);
        }
        return $data;
    } 

    /**
     * 待跟进线索
     * @author Michael_xu
     * @return 
     */
    public function followLeads()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $types = $param['types'];
        $type = $param['type'] ? : 1;
        $isSub = $param['isSub'] ? : '';
        unset($param['types']);
        unset($param['type']);        
        unset($param['isSub']);        
        $leadsModel = model('Leads');

        $param['owner_user_id'] = $userInfo['id'];
        if ($isSub) {
            $param['owner_user_id'] = array('in',getSubUserId(false));
        }        
        switch ($type) {
            case '1' : $param['follow'] = ['neq','已跟进']; break;
            case '2' : $param['follow'] = ['eq','已跟进']; break;
        }
        $data = $leadsModel->getDataList($param);
        if ($types == 'list') {
            return resultArray(['data' => $data]);
        }
        return $data;        
    }        

    /**
     * 待跟进客户
     * @author Michael_xu
     * @return 
     */
    public function followCustomer($getCount = '')
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $types = $param['types'];
        $type = $param['type'] ? : 1;
        $isSub = $param['isSub'] ? : '';
        if ($getCount == true) {
            $param['getCount'] = 1;
        }        
        unset($param['types']);
        unset($param['type']);  
        unset($param['isSub']);         
        $customerModel = model('Customer');

        $param['owner_user_id'] = $userInfo['id'];
        if ($isSub) {
            $param['owner_user_id'] = array('in',getSubUserId(false));
        }          
        switch ($type) {
            case '1' : $param['follow'] = ['eq','']; break;
            case '2' : $param['follow'] = ['eq','已跟进']; break;
        }
        $data = $customerModel->getDataList($param);
        if ($types == 'list') {
            return resultArray(['data' => $data]);
        }
        return $data;
    } 

    /**
     * 待审核合同
     * @author Michael_xu
     * @return 
     */
    public function checkContract()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $types = $param['types'];
        $type = $param['type'] ? : 1;
        $isSub = $param['isSub'] ? : '';
        unset($param['types']);
        unset($param['type']); 
        unset($param['isSub']);        
        $contractModel = model('Contract');

        // $param['owner_user_id'] = $userInfo['id'];
        if ($isSub) {
            $param['owner_user_id'] = array('in',getSubUserId(false));
        }
        switch ($type) {
            case '1' : 
                $param['check_status'] = ['lt','2']; 
                $param['check_user_id'] = ['like','%,'.$userInfo['id'].',%'];
                break;
            case '2' : 
                // $param['check_status'] = ['egt','2']; 
                $param['flow_user_id'] = ['like','%,'.$userInfo['id'].',%'];
                break;
        }
        $data = $contractModel->getDataList($param);
        if ($types == 'list') {
            return resultArray(['data' => $data]);
        }
        return $data;
    }

    /**
     * 待审核回款
     * @author Michael_xu
     * @return 
     */
    public function checkReceivables()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $types = $param['types'];
        $type = $param['type'] ? : 1;
        $isSub = $param['isSub'] ? : '';
        unset($param['types']);
        unset($param['type']); 
        unset($param['isSub']);          
        $receivablesModel = model('Receivables');

        // $param['owner_user_id'] = $userInfo['id'];
        if ($isSub) {
            $param['owner_user_id'] = array('in',getSubUserId(false));
        }
        switch ($type) {
            case '1' : 
                $param['check_status'] = ['lt','2']; 
                $param['check_user_id'] = ['like','%,'.$userInfo['id'].',%'];
                break;
            case '2' : 
                // $param['check_status'] = ['egt','2']; 
                $param['flow_user_id'] = ['like','%,'.$userInfo['id'].',%'];
                break;
        }
        $data = $receivablesModel->getDataList($param);
        if ($types == 'list') {
            return resultArray(['data' => $data]);
        }
        return $data;
    } 

    /**
     * 待回款提醒
     * @author Michael_xu
     * @return 
     */
    public function remindReceivablesPlan()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $types = $param['types'];
        $type = $param['type'] ? : 1;
        $isSub = $param['isSub'] ? : '';
        unset($param['types']);
        unset($param['type']);  
        unset($param['isSub']);        
        $receivablesPlanModel = model('ReceivablesPlan');

        $param['owner_user_id'] = $userInfo['id'];
        if ($isSub) {
            $param['owner_user_id'] = array('in',getSubUserId(false));
        }       
        switch ($type) {
            case '1' : $param['receivables_id'] = 0; 
                       $param['check_status'] = array('lt',2); 
                       $param['remind_date'] = array('elt',date('Y-m-d',time())); 
                       $param['return_date'] = array('egt',date('Y-m-d',time())); 
                       break;
            case '2' : $param['receivables_id'] = array('gt',0);
                        $param['check_status'] = 2; 
                        break;
            case '3' : $param['receivables_id'] = 0;
                        $param['remind_date'] = array('lt',date('Y-m-d',time())); 
                        break;
        }
        $data = $receivablesPlanModel->getDataList($param);
        if ($types == 'list') {
            return resultArray(['data' => $data]);
        }
        return $data;
    }

    /**
     * 即将到期合同
     * @author Michael_xu
     * @return 
     */
    public function endContract()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $types = $param['types'];
        $type = $param['type'] ? : 1;
        $isSub = $param['isSub'] ? : '';
        unset($param['types']);
        unset($param['type']);  
        unset($param['isSub']);       
        $contractModel = model('Contract');
        $configModel = new \app\crm\model\ConfigData();
        $configInfo = $configModel->getData();
        $expireDay = $configInfo['contract_day'] ? : '7';

        $param['owner_user_id'] = $userInfo['id'];
        if ($isSub) {
            $param['owner_user_id'] = array('in',getSubUserId(false));
        }         
        switch ($type) {
            case '1' : $param['end_time'] = array('between',array(date('Y-m-d',time()),date('Y-m-d',time()+86400*$expireDay))); break;
            case '2' : $param['end_time'] = array('lt',date('Y-m-d',time())); break;
        }
        $data = $contractModel->getDataList($param);
        if ($types == 'list') {
            return resultArray(['data' => $data]);
        }
        return $data;
    }  

    /**
     * 待进入客户池（默认5天）
     * @author Michael_xu
     * @return 
     */
    public function remindCustomer($getCount = '')
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $types = $param['types'];
        $isSub = $param['isSub'] ? : '';
        if ($getCount == true) {
            $param['getCount'] = 1;
        }        
        unset($param['types']);
        unset($param['type']);
        unset($param['isSub']);
        unset($param['deal_status']);
        unset($param['owner_user_id']);
        $customerModel = model('Customer');
        
        $whereData = $param ? : [];
        $whereData['is_remind'] = 1;
        $whereData['user_id'] = $userInfo['id'];
        $whereData['scene_id'] = db('admin_scene')->where(['types' => 'crm_customer','bydata' => 'me'])->value('scene_id');
        if ($isSub) {
            $whereData['scene_id'] = db('admin_scene')->where(['types' => 'crm_customer','bydata' => 'sub'])->value('scene_id');
        }
        $data = $customerModel->getDataList($whereData);
        if ($types == 'list') {
            return resultArray(['data' => $data]);
        }
        return $data;
    }             
}