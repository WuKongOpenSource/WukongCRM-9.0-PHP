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
}