<?php
// +----------------------------------------------------------------------
// | Description: 用户组
// +----------------------------------------------------------------------
// | Author:  
// +----------------------------------------------------------------------

namespace app\admin\model;

use app\admin\model\Common;

class Group extends Common 
{
    /**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如CRM模块用crm作为数据表前缀
     */
	protected $name = 'admin_group';

	/**
	 * [getDataList 获取列表]
	 * @param  tree 1 二维数组
	 * @return    [array]            
	 */
	public function getDataList($param)
	{
		if ($param['pid']) {
			$map['pid'] = $param['pid'];
		}
		if ($param['tree'] == 1) {
			$list = ['0' => ['name' => '管理角色','pid' => 1],'1' => ['name' => '客户管理角色','pid' => 2],'2' => ['name' => '人事角色','pid' => 3],'3' => ['name' => '财务角色','pid' => 4],'4' => ['name' => '项目角色','pid' => 5],'5' => ['name' => '自定义角色','pid' => 0]];
			foreach ($list as $k=>$v) {
				$where = [];
				$where['pid'] = $v['pid'];
				$groupList = db('admin_group')->where($where)->select() ? : [];
				foreach ($groupList as $key => $val) {
					$crmRules = [];
					$biRules = [];
					$rules = stringToArray($val['rules']) ? : [];
					foreach ($rules as $k1=>$v1) {
						$ruleInfo = [];
						$ruleInfo = db('admin_rule')->where(['id' => $v1])->find();
						if ($ruleInfo['types'] == 2) {
							$crmRules[] = $v1;
						} elseif ($ruleInfo['types'] == 6) {
							$biRules[] = $v1;
						}
					}
					$groupList[$key]['rules'] = [];
					$groupList[$key]['rules']['crm'] = $crmRules ? : [];
					$groupList[$key]['rules']['bi'] = $biRules ? : [];
				}
				$list[$k]['list'] = $groupList ? : [];				
			}
		} else {
			$list = db('admin_group')->where($map)->select();
			foreach($list as $key=>$value){
				if ($value['norules']) {
					$array = explode(',', substr($value['norules'],1,strlen($value['norules'])-2) );
					if (count($array)) {
						$temp = $value['rules'];
						foreach ($array as $v) {
							$str = ','.$v.',';
							$temp = str_replace($str,',',$temp);
						}
						$list[$key]['rules'] = $temp;
					}
				}
			}			
		}
		return $list ? : [];
	}
	
	//新建角色
	public function createData($param)
	{
		unset($param['types']);
		$flag = $this->insertGetId($param);
		if ($flag) {
			return $flag;
		} else {
			$this->error = '操作失败';
			return false;
		}
	}
	
	//编辑角色
	public function updateDataById($param,$group_id)
	{
		$dataInfo = $this->get($group_id);
		if(!$dataInfo){
			$this->error = '该角色不存在或已删除';
			return false;
		}
		unset($param['types']);
		$flag = $this->where('id = '.$group_id)->update($param);
		if ($flag) {
			return true;
		} else {
			$this->error = '操作失败';
			return false;
		}
	}
	
	//删除角色
	public function delGroupById($group_id = '')
	{
		$dataInfo = $this->get($group_id);
		if(!$dataInfo){
			$this->error = '该角色不存在或已删除';
			return false;
		}
		if ($dataInfo['types']) {
			$this->error = '系统角色不能删除';
			return false;			
		}
		$flag = $this->where('id = '.$group_id)->delete();
		if ($flag) {
			return true;
		} else {
			$this->error = '删除失败';
			return false;
		}
	}
}