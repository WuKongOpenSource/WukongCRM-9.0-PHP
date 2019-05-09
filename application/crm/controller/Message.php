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
            'allow'=>['']            
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
     * 待审核的合同
     * @author Michael_xu
     * @return 
     */   
    public function unCheckContract()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $contractModel = model('Contract');
        $where = [];
        $where['check_status'] = 0;
        $list = $contractModel->getDataList($where);
        return resultArray(['data' => $list]);
    } 

    /**
     * 待审核的回款
     * @author Michael_xu
     * @return 
     */   
    public function unCheckReceivables()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $receivablesModel = model('Receivables');
        $where = [];
        $where['check_status'] = 0;
        $list = $receivablesModel->getDataList($where);
        return resultArray(['data' => $list]);
    }     

    /**
     * 待审核的审批
     * @author Michael_xu
     * @return 
     */   
    public function unCheckExamine()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $examineModel = new \app\oa\model\Examine();
        $where = [];
        $where['check_status'] = 0;
        $list = $examineModel->getDataList($where);
        return resultArray(['data' => $list]);
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

        $sysNum = $unCheckContractNum = $unCheckReceivablesNum = $unCheckExamineNum = 0;
        $sysNum = db('admin_message')->where(['from_user_id' => 0,'to_user_id' => $userInfo['id'],'read_time' => ''])->count();
        // $unCheckContractNum = 
        return resultArray(['data' => $data]);
    }

    /**
     * 今日需联系客户
     * @author Michael_xu
     * @return 
     */
    public function todayCustomer()
    {
        $customerModel = model('Customer');
        $todayTime = getTimeByType('today');
        $where = [];
        $where['customer.next_time'] = ['between',$todayTime];
        $data = $customerModel->getDataList($where);
        return resultArray(['data' => $data]);
    } 

    /**
     * 待跟进线索
     * @author Michael_xu
     * @return 
     */
    public function followLeads()
    {
        $leadsModel = model('Leads');
        $where = [];
        $where['leads.follow'] = ['neq','已跟进'];
        $data = $leadsModel->getDataList($where);
        return resultArray(['data' => $data]);
    }        

    /**
     * 待跟进客户
     * @author Michael_xu
     * @return 
     */
    public function followCustomer()
    {
        $customerModel = model('Customer');
        $where = [];
        $where['customer.follow'] = ['neq','已跟进'];
        $data = $customerModel->getDataList($where);
        return resultArray(['data' => $data]);
    } 

    /**
     * 待审核合同
     * @author Michael_xu
     * @return 
     */
    public function checkContract()
    {
        $userInfo = $this->userInfo;
        $contractModel = model('Contract');
        $where = [];
        $where['contract.check_status'] = ['lt','2'];
        $where['contract.check_user_id'] = ['like',','.$userInfo['id'].','];
        $data = $contractModel->getDataList($where);
        return resultArray(['data' => $data]);
    }

    /**
     * 待审核回款
     * @author Michael_xu
     * @return 
     */
    public function checkReceivables()
    {
        $userInfo = $this->userInfo;
        $receivablesModel = model('Receivables');
        $where = [];
        $where['receivables.check_status'] = ['lt','2'];
        $where['receivables.check_user_id'] = ['like',','.$userInfo['id'].','];
        $data = $receivablesModel->getDataList($where);
        return resultArray(['data' => $data]);
    } 

    /**
     * 待回款(回款计划)
     * @author Michael_xu
     * @return 
     */
    public function remindReceivablesPlan()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $receivablesPlanModel = model('ReceivablesPlan');
        $status = $param['status'] ? : '待回款';
        $where = [];
        $where['user_id'] = $userInfo['id'];
        switch ($status) {
            case '待回款' : $where['receivables_id'] = 0; $where['remind_date'] = array('elt',date('Y-m-d',time())); $where['return_date'] = array('egt',date('Y-m-d',time())) break;
            case '已回款' : $where['receivables_id'] = array('gt',0); break;
            case '已逾期' : $where['receivables_id'] = 0; $where['return_date'] = array('lt',date('Y-m-d',time())); break;
        }
        $data = $receivablesPlanModel->getDataList($where);
        return resultArray(['data' => $data]);
    }

    /**
     * 即将到期合同
     * @author Michael_xu
     * @return 
     */
    public function endContract()
    {
        $param = $this->param;
        $contractModel = model('Contract');
        $configModel = new \app\crm\model\ConfigData();
        $configInfo = $configModel->getData();        
        $status = $param['status'] ? : '待回款';
        $expireDay = $configInfo['contract_day'] ? : '7';
        $where = [];
        switch ($status) {
            case '即将到期' : $where['end_time'] = array('between',array(date('Y-m-d',time()-86400*$expireDay),date('Y-m-d',time()))); break;
            case '已到期' : $where['end_time'] = array('lt',date('Y-m-d',time())); break;
        }
        $data = $contractModel->getDataList($where);
        return resultArray(['data' => $data]);
    }                      
}