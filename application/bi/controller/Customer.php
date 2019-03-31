<?php
// +----------------------------------------------------------------------
// | Description: 商业智能-客户分析
// +----------------------------------------------------------------------
// | Author: Michael_xu | gengxiaoxu@5kcrm.com 
// +----------------------------------------------------------------------

namespace app\bi\controller;

use app\admin\controller\ApiCommon;
use think\Hook;
use think\Request;

class Customer extends ApiCommon
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
            'allow'=>['statistics']            
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());        
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
    } 
  
    /**
     * 员工客户分析
     * @author Michael_xu
     * @param 
     * @return
     */
    public function statistics()
    {
        if (!checkPerByAction('bi', 'customer' , 'read')) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }        
        $customerModel = new \app\crm\model\Customer();
        $param = $this->param;
        $list = $customerModel->getStatistics($param);
        return resultArray(['data' => $list]);
    }    
}
