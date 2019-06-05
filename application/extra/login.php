<?php
/**
 * Created by PhpStorm.
 * User: kjb-02
 * Date: 2019/6/4
 * Time: 19:55
 */
return [
   'wechat' => [
       'app_id' => 'wx65cc57b99c153074',
       'secret' => '81acd8e771682429bf67f7f56d856c3d',

       'response_type' => 'array',

       'log' => [
           'level' => 'debug',
           'file' => RUNTIME_PATH.'/login/wechat.log',
       ],
   ]
];