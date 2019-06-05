<?php
// +----------------------------------------------------------------------
// | Description: 系统设置
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com
// +----------------------------------------------------------------------
namespace app\admin\model;

use think\Db;
use app\admin\model\Common;
use think\Request;
use think\Validate;

class Config extends Common
{
	/**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如CRM模块用crm作为数据表前缀
     */
	protected $name = 'admin_config';

	/**
	 * 修改配置信息
	 * @author Michael_xu
	 * @param
	 */
	public function updateConfig($param)
	{
		$where = $param['name'];
		$data['value'] = $param['value'] ? : 0;
		if ($this->where($where)->update($data)) {
			return true;
		} else {
			$this->error = '设置失败';
			return false;
		}
	}

    /**
     * 系统配置信息
     * @param $param
     * @param string $type
     * @return array
     */
	public function getDataList($param, $type = 'tree')
	{
		$cat = new \com\Category('admin_config', array('id', 'pid', 'name', 'value'));
		$data = $cat->getList('', 0, 'id');
		// 若type为tree，则返回树状结构
		if ($type == 'tree') {
			$tree = new \com\Tree();
			$data = $tree->list_to_tree($data, 'id', 'pid', 'child', 0, true, array('pid'));
		}
		return $data;
	}
}
