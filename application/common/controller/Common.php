<?php
// +----------------------------------------------------------------------
// | Description: 解决跨域问题
// +----------------------------------------------------------------------
// | Author:  
// +----------------------------------------------------------------------

namespace app\common\controller;

use think\Controller;
use think\Request;

class Common extends Controller
{
    public $param;
    public $m;
    public $c;
    public $a;
    public function _initialize()
    {
        parent::_initialize();
        /*防止跨域*/      
        header('Access-Control-Allow-Origin: '.$_SERVER['HTTP_ORIGIN']);
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, authKey, sessionId");
        $param = Request::instance()->param(); 
        $platform = $param['platform'] ? '_'.$param['platform'] : ''; //请求平台(mobile,ding)         
        unset($param['platform']);         
        $this->param = $param;   
        $request = request();
        $header = $request->header();
        $authKey = $header['authkey'];
        $cache = cache('Auth_'.$authKey.$platform);
        if ($cache) $this->userInfo = $cache['userInfo'];

        $m = strtolower($request->module());
        $c = strtolower($request->controller());
        $a = strtolower($request->action());     
        $this->m = $m;          
        $this->c = $c;          
        $this->a = $a;          
    }

    public function object_array($array) 
    {  
        if (is_object($array)) {  
            $array = (array)$array;  
        } 
        if (is_array($array)) {  
            foreach ($array as $key=>$value) {  
                $array[$key] = $this->object_array($value);  
            }  
        }  
        return $array;  
    } 
}