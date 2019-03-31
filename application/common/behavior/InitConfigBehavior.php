<?php
// +----------------------------------------------------------------------
// | Description: 加载动态配置
// +----------------------------------------------------------------------
// | Author:  Michael_xu <gengxiaoxu@5kcrm.com>
// +----------------------------------------------------------------------
namespace app\common\behavior;
class InitConfigBehavior
{
    public function run(&$content)
    {
        //读取数据库中的配置
        $system_config = []; 
        // config($system_config); //添加配置
    }
}