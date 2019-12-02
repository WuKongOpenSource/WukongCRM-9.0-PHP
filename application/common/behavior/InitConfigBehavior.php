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

        /**
         * 数据库名称，用于云平台区分项目
         */
        define('DB_NAME', \config('database.database'));

        // 生成临时目录
        if (!file_exists('./public/temp')) {
            mkdir('./public/temp', 0777, true);
        }

        /**
         * 自定义临时文件目录的绝对路径，暂时用于存放导入导出时的临时文件
         */
        define('TEMP_DIR', realpath('.' . DS . 'public' . DS . 'temp') . DS);

        $this->clearTemp();
    }

    /**
     * 清理自定义临时文件目录文件
     */
    public function clearTemp()
    {
        $cache = \cache('CLEAR_TEMP');
        if (!$cache) {
            $today = (int) date('Ymd');
            \cache('CLEAR_TEMP', true, new \DateTime(date('Y-m-d'). ' 23:59'));
            
            $dh = opendir(TEMP_DIR);
            while ($dir = readdir($dh)) {
                // 日期目录
                if (\strlen($dir) == 8 && is_numeric($dir)) {
                    // 超过一周的删除
                    if ($today - (int) $dir > 7) {
                        delDir(TEMP_DIR . $dir);
                    }
                }
            }
            closedir($dh);
        }
    }
}
