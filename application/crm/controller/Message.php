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
            'allow'=>['num','todaycustomer','followleads','followcustomer','checkcontract','checkreceivables','remindreceivablesplan','endcontract']            
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
        $param = $this->param;
        $userInfo = $this->userInfo;
        $configDataModel = model('ConfigData');
        $configData = $configDataModel->getData();
        $data = [];
        // $sysNum = db('admin_message')->where(['from_user_id' => 0,'to_user_id' => $userInfo['id'],'read_time' => ''])->count();
        $todayCustomer = $this->todayCustomer();
        $data['todayCustomer'] = $todayCustomer['dataCount'] ? : '';
        $followLeads = $this->followLeads();
        $data['followLeads'] = $followLeads['dataCount'] ? : '';
        $followCustomer = $this->followCustomer();
        $data['followCustomer'] = $followCustomer['dataCount'] ? : '';
        $checkContract = $this->checkContract();
        $data['checkContract'] = $checkContract['dataCount'] ? : ''; 
        $checkReceivables = $this->checkReceivables();
        $data['checkReceivables'] = $checkReceivables['dataCount'] ? : ''; 
        $remindReceivablesPlan = $this->remindReceivablesPlan();
        $data['remindReceivablesPlan'] = $remindReceivablesPlan['dataCount'] ? : '';
        if ($configData['contract_config'] == 1) {
            $endContract = $this->endContract();
            $data['endContract'] = $endContract['dataCount'] ? : '';  
        }                                   
        return resultArray(['data' => $data]);
    }

    /**
     * 今日需联系客户
     * @author Michael_xu
     * @return 
     */
    public function todayCustomer()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $types = $param['types'];
        $type = $param['type'] ? : 1;
        $isSub = $param['isSub'] ? : '';
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
            case '1' : $param['next_time'] = ['between',array($todayTime[0],$todayTime[1])]; break;
            case '2' : $param['next_time'] = ['between',array(1,time())]; break;
            case '3' : $param['next_time'] = ['between',array($todayTime[0],$todayTime[1])]; $param['follow'] = ['eq','已联系']; break;
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
    public function followCustomer()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $types = $param['types'];
        $type = $param['type'] ? : 1;
        $isSub = $param['isSub'] ? : '';
        unset($param['types']);
        unset($param['type']);  
        unset($param['isSub']);         
        $customerModel = model('Customer');

        $param['owner_user_id'] = $userInfo['id'];
        if ($isSub) {
            $param['owner_user_id'] = array('in',getSubUserId(false));
        }          
        switch ($type) {
            case '1' : $param['follow'] = ['neq','已跟进']; break;
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
        } else {
            $param['check_user_id'] = ['like','%,'.$userInfo['id'].',%'];
        }
        switch ($type) {
            case '1' : $param['check_status'] = ['lt','2']; break;
            case '2' : $param['check_status'] = ['egt','2']; break;
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
        } else {
            $param['check_user_id'] = ['like','%,'.$userInfo['id'].',%'];
        }          
        switch ($type) {
            case '1' : $param['check_status'] = ['lt','2']; break;
            case '2' : $param['check_status'] = ['egt','2']; break;
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
            case '1' : $param['receivables_id'] = 0; $param['remind_date'] = array('elt',date('Y-m-d',time())); $param['return_date'] = array('egt',date('Y-m-d',time())); break;
            case '2' : $param['receivables_id'] = array('gt',0); break;
            case '3' : $param['receivables_id'] = 0; $param['remind_date'] = array('lt',date('Y-m-d',time())); break;
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
}