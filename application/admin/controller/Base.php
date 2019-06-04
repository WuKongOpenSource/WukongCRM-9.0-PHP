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
use EasyWeChat\Factory;

class Base extends Common
{
    public function login()
    {

        $param = $this->param;
        $userModel = model('User');
        if ($param['type']=='wechat'){
            $code = $param['code'];
            $wechatConfig = config('login.wechat');
            $app = Factory::miniProgram($wechatConfig);
            $login = $app->auth->session($code);
            if (!isset($login['openid'])){
                return resultArray(['error' => 'shao can shu']);
            }
            $data =$userModel->wechatLogin($login['openid']);

        }else{
            $username = $param['username'];
            $password = $param['password'];
            $verifyCode = !empty($param['verifyCode'])? $param['verifyCode']: '';
            $isRemember = !empty($param['isRemember'])? $param['isRemember']: '';
            $type = '';
            $authKey = '';
            $is_mobile = $param['is_mobile'] ? : '';

            $data = $userModel->login($username, $password, $verifyCode, $isRemember, $type, $authKey, $is_mobile);
        }
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
        if ($param['mobile'] == 1) {
            cache('Auth_'.$header['authkey'].'mobile', null);
        } else {
            cache('Auth_'.$header['authkey'], null);
        }
        session('null', 'admin');
        session('admin','null');
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
