<?php
// +----------------------------------------------------------------------
// | Description: 系统设置
// +----------------------------------------------------------------------
// | Author: Michael_xu | gengxiaoxu@5kcrm.com 
// +----------------------------------------------------------------------

namespace app\admin\controller;

use app\admin\controller\ApiCommon;
use think\Hook;
use think\Request;

class Setting extends ApiCommon
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
        $userInfo = $this->userInfo;
        //权限判断
        $unAction = [''];
        $adminTypes = adminGroupTypes($userInfo['id']);
        if (!in_array(2,$adminTypes) && !in_array(1,$adminTypes) && !in_array($a, $unAction)) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }           
    }
}