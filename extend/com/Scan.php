<?php
// +----------------------------------------------------------------------
// | Author: Michael_xu <gengxiaoxu@5kcrm.com>
// +----------------------------------------------------------------------

namespace com;

use think\Request;

class Scan {
	private $webscan_switch = 1;
	//提交方式拦截(1开启拦截,0关闭拦截,post,get,cookie,referre选择需要拦截的方式)
	private $webscan_post = 1;
	private $webscan_get = 1;
	private $webscan_cookie = 1;
	private $webscan_referre = 1;
	private $webscan_white_directory = 'admin';
	private $webscan_white_url = array('index.php' => 'm=admin');

	//get拦截规则
	private $getfilter = "<[^>]*?=[^>]*?&#[^>]*?>|iframe|\\b(alert\\(|confirm\\(|expression\\(|prompt\\()|<[^>]*?\\b(onerror|onmousemove|ondblclick|onmousedown|onmouseup|onmouseout|onscroll|onfocus|onsubmit|onblur|onchange|onload|onclick|onmouseover)\\b[^>]*?>|^\\+\\/v(8|9)|\\b(and|or)\\b\\s*?([\\(\\)'\"\\d]+?=[\\(\\)'\"\\d]+?|[\\(\\)'\"a-zA-Z]+?=[\\(\\)'\"a-zA-Z]+?|>|<|\s+?[\\w]+?\\s+?\\bin\\b\\s*?\(|\\blike\\b\\s+?[\"'])|\\/\\*.+?\\*\\/|<\\s*script\\b|<\\s*object\\b|\\bEXEC\\b|UNION.+?SELECT|UPDATE.+?SET|INSERT\\s+INTO.+?VALUES|INTO.+?FILE|(SELECT|DELETE).+?FROM|(CREATE|ALTER|DROP|TRUNCATE)\\s+(TABLE|DATABASE)";

	//post拦截规则
	private $postfilter = "<[^>]*?=[^>]*?&#[^>]*?>|iframe|\\b(alert\\(|confirm\\(|expression\\(|prompt\\()|<[^>]*?\\b(onerror|onmousemove|ondblclick|onmousedown|onmouseup|onmouseout|onscroll|onfocus|onsubmit|onblur|onchange|onload|onclick|onmouseover)\\b[^>]*?>|\\b(and|or)\\b\\s*?([\\(\\)'\"\\d]+?=[\\(\\)'\"\\d]+?|[\\(\\)'\"a-zA-Z]+?=[\\(\\)'\"a-zA-Z]+?|>|<|\s+?[\\w]+?\\s+?\\bin\\b\\s*?\(|\\blike\\b\\s+?[\"'])|\\/\\*.+?\\*\\/|<\\s*script\\b|<\\s*object\\b|\\bEXEC\\b|UNION.+?SELECT|UPDATE.+?SET|INSERT\\s+INTO.+?VALUES|INTO.+?FILE|(SELECT|DELETE).+?FROM|(CREATE|ALTER|DROP|TRUNCATE)\\s+(TABLE|DATABASE)";

	//cookie拦截规则
	private $cookiefilter = "\\b(and|or)\\b\\s*?([\\(\\)'\"\\d]+?=[\\(\\)'\"\\d]+?|[\\(\\)'\"a-zA-Z]+?=[\\(\\)'\"a-zA-Z]+?|>|<|\s+?[\\w]+?\\s+?\\bin\\b\\s*?\(|\\blike\\b\\s+?[\"'])|\\/\\*.+?\\*\\/|<\\s*script\\b|<\\s*object\\b|\\bEXEC\\b|UNION.+?SELECT|UPDATE.+?SET|INSERT\\s+INTO.+?VALUES|INTO.+?FILE|(SELECT|DELETE).+?FROM|(CREATE|ALTER|DROP|TRUNCATE)\\s+(TABLE|DATABASE)";

	/**
	 *  记录日志
	 */
	public function webscan_slog($logs) {
		// var_dump(RUNTIME_PATH);die();
	    $string = "\r\n================\r\n".implode("\r\n", $logs);
	    file_put_contents(RUNTIME_PATH.'input_error.txt', $string, FILE_APPEND);
	}

	/**
	 *  参数拆分
	 */
	public function webscan_arr_foreach($arr) {
		static $str;
		if (!is_array($arr)) {
			return $arr;
		}
		foreach ($arr as $key => $val ) {
			if (is_array($val)) {
				$this->webscan_arr_foreach($val);
			} else {
				$str[] = $val;
			}
		}
		return implode($str);
	}

	/**
	 *  获取ip
	 */	
	public function get_client_ip($type = 0) {
		$_SERVER = input('server.');
	    $type = $type ? 1 : 0;
	    static $ip = NULL;
	    if ($ip !== NULL) return $ip[$type];
	    if ($_SERVER['HTTP_X_REAL_IP']) {//nginx 代理模式下，获取客户端真实IP
	        $ip=$_SERVER['HTTP_X_REAL_IP'];     
	    } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {//客户端的ip
	        $ip = $_SERVER['HTTP_CLIENT_IP'];
	    } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {//浏览当前页面的用户计算机的网关
	        $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
	        $pos = array_search('unknown',$arr);
	        if(false !== $pos) unset($arr[$pos]);
	        $ip = trim($arr[0]);
	    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
	        $ip = $_SERVER['REMOTE_ADDR'];//浏览当前页面的用户计算机的ip地址
	    } else {
	        $ip = $_SERVER['REMOTE_ADDR'];
	    }
	    // IP地址合法验证
	    $long = sprintf("%u",ip2long($ip));
	    $ip = $long ? array($ip, $long) : array('0.0.0.0', 0);
	    return $ip[$type];
	}

	/**
	 *  攻击检查拦截
	 */
	public function webscan_StopAttack($StrFiltKey, $StrFiltValue, $ArrFiltReq, $method) {
		$_SERVER = input('server.');
	// var_dump($_SERVER) ;die();
	    $StrFiltValue = $this->webscan_arr_foreach($StrFiltValue);
	    if (preg_match("/".$ArrFiltReq."/is",$StrFiltValue) == 1){
	        $this->webscan_slog(array('ip' => $this->get_client_ip(),'time'=>strftime("%Y-%m-%d %H:%M:%S"),'page'=>$_SERVER["PHP_SELF"],'method'=>$method,'rkey'=>$StrFiltKey,'rdata'=>$StrFiltValue,'user_agent'=>$_SERVER['HTTP_USER_AGENT'],'request_url'=>$_SERVER["REQUEST_URI"]));
	        header('Content-Type:application/json; charset=utf-8');
	        exit(json_encode(['code'=>107,'error'=>'插入了被禁用的标签！']));
	    }
	    if (preg_match("/".$ArrFiltReq."/is",$StrFiltKey) == 1){
	        $this->webscan_slog(array('ip' => $this->get_client_ip(),'time'=>strftime("%Y-%m-%d %H:%M:%S"),'page'=>$_SERVER["PHP_SELF"],'method'=>$method,'rkey'=>$StrFiltKey,'rdata'=>$StrFiltKey,'user_agent'=>$_SERVER['HTTP_USER_AGENT'],'request_url'=>$_SERVER["REQUEST_URI"]));
	        header('Content-Type:application/json; charset=utf-8');
	        exit(json_encode(['code'=>107,'error'=>'插入了被禁用的标签！']));
	    }
	}

	public function webscan_Check() {
		$request = Request::instance();
		//var_dump(input('server.HTTP_REFERER'));die();
		//referer获取
		//$webscan_referer = empty(input('server.HTTP_REFERER')) ? array() : array('HTTP_REFERER'=>input('server.HTTP_REFERER'));
		return ;
		if ($this->webscan_switch) {
		    if ($this->webscan_get) {
		        foreach($request->get() as $key=>$value) {
		            $this->webscan_StopAttack($key, $value, $this->getfilter, "GET");
		        }
		    }
		    if ($this->webscan_post) {
		        // $module = strtolower($request->module());
		        // $un_strip_arr = array('knowledge','template');
		        foreach ($request->post() as $key=>$value) {
		            //过滤post数据 html标签
		            // if (!in_array($module, $un_strip_arr)) {
		                $request->param($key,'','strip_tags,strtolower'); 
		            // }
		            $this->webscan_StopAttack($key, $value, $this->postfilter, "POST");
		        } 
		    }
		    if ($this->webscan_cookie) {
		        foreach($request->cookie() as $key=>$value) {
		            $this->webscan_StopAttack($key, $value, $this->cookiefilter, "COOKIE");
		        }
		    }
		    if ($this->webscan_referre) {
		        foreach($webscan_referer as $key=>$value) {
		            $this->webscan_StopAttack($key, $value, $this->postfilter, "REFERRER");
		        }
		    }
		}
	}
}