<?php
// +----------------------------------------------------------------------
// | Description: 应用配置
// +----------------------------------------------------------------------
// | Author:
// +----------------------------------------------------------------------

namespace app\admin\model;

use app\admin\model\Common;
use think\Db;
class Configset extends Common
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
		$array = ['工作台','客户管理','项目管理','人资管理','财务管理'];
		$list = Db::name('AdminConfig')->order('type asc')->select();
		$dataary = array();
		foreach ($list as $key=>$value) {
			$dataary[$value['type']]['type_name'] = $array[$value['type']-1];
			$dataary[$value['type']]['typestatus'] = $value['typestatus'];
			$value['status'] = $value['typestatus']?$value['status']:'0';
			$dataary[$value['type']]['sublist'][] = $value;
		}
		return $dataary;
	}

	//获取模块列表
	public function typelist()
	{
		$array = ['工作台','客户管理','项目管理','人资管理','财务管理'];
		$list = Db::name('AdminConfig')->where(' typestatus = 1 ')->group('type')->select();
		$dataary = array();
		foreach ($list as $k=>$v) {
			$dataary[$k]['name'] = $array[$v['type']-1] ;
			$dataary[$k]['status'] = $v['typestatus'] ;
			$dataary[$k]['type'] = $v['type'] ;
		}
		return $dataary?:array();
	}

	//获取某个模块下列表
	public function getDataBytype($param)
	{
		$list = Db::name('AdminConfig')->where(' typestatus = 1 and type = '.$param['type'].'')->select();
		return $list?:array();
	}

	//新建
	public function createData($param)
	{
		$map = $param;
		$flag = $this->insertGetId($map);
		if ($flag) {
			return $flag;
		} else {
			$this->error = '操作失败';
			return false;
		}
	}

	//编辑
	public function updateDataById($param,$id =0)
	{
		$temp['status'] = $param['status'];
		$flag = $this->where('id = '.$id.'')->update($temp);
		if ($flag) {
			return true;
		} else {
			$this->error = '操作失败';
			return false;
		}
	}

	//类型编辑
	public function updateDatas($param,$type)
	{
		$temp['typestatus'] = $param['status'];
		$flag = $this->where('type = '.$type.'')->update($temp);
		if ($flag) {
			return true;
		} else {
			$this->error = '操作失败';
			return false;
		}
	}
}
