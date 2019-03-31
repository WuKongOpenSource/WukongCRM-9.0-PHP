<?php
// +----------------------------------------------------------------------
// | Description: 系统基础
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com
// +----------------------------------------------------------------------

namespace app\admin\model;

use app\admin\model\Common;
use think\Db;

class System extends Common 
{

	protected $name = 'admin_system';

	//列表
	public function getDataList()
	{
		$list = Db::name('AdminSystem')->select();
		$temp = array();
		foreach ($list as $key => $value) {
			$temp[$value['name']] = $value['value'];
		} 
		$temp['logo'] = getFullPath($temp['logo']);
		return $temp;
	}

	//新建
	public function createData($param)
	{
		if( isset($param['name'])){
			$data['name'] = 'name';
			$data['value'] = $param['name'];
			$data['description'] = '网站名称';
			$this->where('id=1')->update($data);
		} 
		return true;
	}
}