<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// ini_set('session.cookie_domain', ".domain.com");//跨域访问Session
// [ 应用入口文件 ]

// 应用目录
define('APP_PATH', __DIR__.'/application/');
// 定义配置文件目录和应用目录同级
define('CONF_PATH', __DIR__.'/config/');
// 定义缓存目录
define('RUNTIME_PATH', __DIR__.'/runtime/');
// 加载框架引导文件
require './thinkphp/start.php';
