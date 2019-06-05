<?php
// +----------------------------------------------------------------------
// | Description: WEB端权限判断
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com  
// +----------------------------------------------------------------------
namespace app\common\behavior;

use think\Request;
use think\Db;

class AuthenticateBehavior
{
	public function run(&$params)
	{
        /*防止跨域*/      
        header('Access-Control-Allow-Origin: '.$_SERVER['HTTP_ORIGIN']);
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, authKey, sessionId");        
        $request = Request::instance();
        $m = strtolower($request->module());
        $c = strtolower($request->controller());
        $a = strtolower($request->action());        
        //提交方式拦截
        $scan = new \com\Scan();
        $response = $scan->webscan_Check();            
		
		$allow = $params['allow']; //登录用户可访问
		$permission = $params['permission']; //无限制
		/*获取头部信息*/ 
        $header = $request->header();
        $authKey = $header['authkey'];
		// $is_mobile = $header['is_mobile'];  
  //       if ($is_mobile) {
  //           $cache = cache('Auth_'.$authKey.'_mobile');
  //       } else {
            $cache = cache('Auth_'.$authKey);
        // }
        $userInfo = $cache['userInfo'];
    	
    	if (in_array($a, $permission)) {
    		return true;
    	}   

    	if (empty($userInfo)) {
			header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>101,'error'=>'请先登录']));
    	}
		if ($userInfo['id'] == 1) {
    		return true;
    	}
    	if (in_array($a, $allow)) {
			return true;
    	}
        //管理员角色
        $adminTypes = adminGroupTypes($userInfo['id']);
        if (in_array(1,$adminTypes)) {
            return true;
        }        
        //操作权限
    	$res_per = checkPerByAction($m, $c, $a); 
    	if (!$res_per) {
			header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
    	}
	}
}
