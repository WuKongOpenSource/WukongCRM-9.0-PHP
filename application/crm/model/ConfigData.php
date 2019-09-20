<?php
// +----------------------------------------------------------------------
// | Description: 客户设置
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com
// +----------------------------------------------------------------------
namespace app\crm\model;

use think\Db;
use app\admin\model\Common;
use think\Request;
use think\Validate;

class ConfigData extends Common
{
	/**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如CRM模块用crm作为数据表前缀
     */
	protected $name = 'crm_config';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
	protected $autoWriteTimestamp = true;

	/**
	 * 保存相关信息
	 * @author Michael_xu
	 * @param  
	 * @return                            
	 */	
	public function createData($param)
	{
		$data = [];
		$config = $param['config'] ? 1 : 0;
		$remind_config = $param['remind_config'] ? 1 : 0;
		//启用
		if ($config == 1) {
			$follow_day = $param['follow_day'] ? : 0;
			$resFollow = db('crm_config')->where(['name' => 'follow_day'])->update(['value' => $follow_day]);
			$deal_day = $param['deal_day'] ? : 0;
			$resDeal = db('crm_config')->where(['name' => 'deal_day'])->update(['value' => $deal_day]);			
		}
		$resConfig = db('crm_config')->where(['name' => 'config'])->update(['value' => $config]);
		if ($remind_config == 1) {
			$remind_day = $param['remind_day'] ? : 0;
			$resRemind = db('crm_config')->where(['name' => 'remind_day'])->update(['value' => $remind_day]);		
		}		
		$resRemindConfig = db('crm_config')->where(['name' => 'remind_config'])->update(['value' => $remind_config]);
		return true;	
	}

	/**
	 * 获取相关信息
	 * @author Michael_xu
	 * @param  
	 * @return                            
	 */	
	public function getData()
	{
		$list = db('crm_config')->select();
        $data = [];
        foreach ($list as $k=>$v) {
            $data[$v['name']] = $v['value'];
        }
		return $data ? : [];
	}	
} 		