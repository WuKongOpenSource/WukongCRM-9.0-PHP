<?php
// +----------------------------------------------------------------------
// | Description: 应用配置
// +----------------------------------------------------------------------
// | Author: Michael_xu | gengxiaoxu@5kcrm.com
// +----------------------------------------------------------------------

namespace app\admin\model;

use app\admin\model\Common;
use think\Db;

class Config extends Common 
{
    /**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如CRM模块用crm作为数据表前缀
     */
	protected $name = 'admin_config';

	/**
	 * [getDataList 获取列表]
	 * @return    [array]            
	 */
	public function getDataList()
	{
		$list = Db::name('AdminConfig')->order('type asc')->select();
		return $list;
	}
	
	/**
     * 编辑
     * @author Michael_xu
     * @return
     */	
	public function updateDataById($param, $id)
	{
		$data = [];
		$data['status'] = $param['status'] ? : '0';
		if ($this->where(['id' => $id])->update($data)) {
			return true;
		}
		$this->error = '操作失败';
		return false;		
	}
}