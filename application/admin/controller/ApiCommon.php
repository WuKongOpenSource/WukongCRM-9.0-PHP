<?php
// +----------------------------------------------------------------------
// | Description: Api基础类，验证权限
// +----------------------------------------------------------------------
// | Author:  
// +----------------------------------------------------------------------

namespace app\admin\controller;

use think\Request;
use think\Db;
use app\common\adapter\AuthAdapter;
use app\common\controller\Common;


class ApiCommon extends Common
{
    public function _initialize()
    {
        parent::_initialize();
        /*获取头部信息*/ 
        $header = Request::instance()->header();
        
        $authKey = $header['authkey'];
        $sessionId = $header['sessionid'];
        // $is_mobile = $header['is_mobile'];  
        // if ($is_mobile) {
        //     $cache = cache('Auth_'.$authKey.'_mobile');
        // } else {
            $cache = cache('Auth_'.$authKey);
        // }
        // 校验sessionid和authKey
        if (empty($sessionId) || empty($authKey) || empty($cache)) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>101, 'error'=>'登录已失效']));
        }
        //登录有效时间
        $cacheConfig = config('cache');
        $loginExpire = $cacheConfig['expire'] ? : '86400*7';

        // 检查账号有效性
        $userInfo = $cache['userInfo'];
        $map['id'] = $userInfo['id'];
        $map['status'] = array('in',['1','2']);
        $userData = Db::name('admin_user')->where($map)->find();
        if (!$userData) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>103, 'error'=>'账号已被删除或禁用']));   
        } 
        session('user_id', $userInfo['id']);
        // 更新缓存
        cache('Auth_'.$authKey, $cache, $loginExpire);           
        // $GLOBALS['userInfo'] = $userInfo;
    }
}
