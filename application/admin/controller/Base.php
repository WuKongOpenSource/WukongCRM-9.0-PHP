<?php
// +----------------------------------------------------------------------
// | Description: 基础类，无需验证权限。
// +----------------------------------------------------------------------
// | Author:  
// +----------------------------------------------------------------------

namespace app\admin\controller;

use com\verify\HonrayVerify;
use app\common\controller\Common;
use think\Request;
use think\Session;

class Base extends Common
{
    public function login()
    {
        $request = Request::instance();
        $paramArr = $request->param();        
        $userModel = model('User');
        $param = $this->param;
        $username = $param['username'];
        $password = $param['password'];
        $verifyCode = !empty($param['verifyCode']) ? $param['verifyCode']: '';
        $isRemember = !empty($param['isRemember']) ? $param['isRemember']: '';
        $data = $userModel->login($username, $password, $verifyCode, $isRemember, $type, $authKey, $paramArr);
        
        Session::set('user_id', $data['userInfo']['id']);
        if (!$data) {
            return resultArray(['error' => $userModel->getError()]);
        }
        return resultArray(['data' => $data]);
    }     

    //退出登录
    public function logout()
    {
        $param = $this->param;
        $header = Request::instance()->header();
        $request = Request::instance();
        $paramArr = $request->param();
        $platform = $paramArr['platform'] ? '_'.$paramArr['platform'] : ''; //请求平台(mobile,ding)
        $cache = cache('Auth_'.$authKey.$platform,null);
        cookie(null, '72crm_');
        cookie(null, '5kcrm_');
        session('user_id','null');
        return resultArray(['data'=>'退出成功']);
    }

    //获取图片验证码
    public function getVerify()
    {
        $captcha = new HonrayVerify(config('captcha'));
        return $captcha->entry();
    }

	//网站信息
    public function index()
    {   
        $systemModel = model('System');
        $data = $systemModel->getDataList();
        return  resultArray(['data' => $data]);
    }    
	
    // miss 路由：处理没有匹配到的路由规则
    public function miss()
    {
        if (Request::instance()->isOptions()) {
            return ;
        } else {
            echo '悟空软件';
        }
    }
}
 