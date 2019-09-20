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
	 * @param  tree 1 属性
	 * @param  rules 1 二维数组
	 * @param  pid 分类：0客户自定义角色,1系统默认管理角色,2客户管理角色,3人力资源管理角色(原客户),4财务管理角色(原客户),5项目管理角色,6办公管理角色,7人力资源管理角色,8财务管理角色,9项目管理员角色
	 * @param  备注：原自定义角色0,人事管理角色3,财务管理角色4，划分至新客户管理角色中
	 * @param  rule：types 0系统设置1工作台2客户管理3项目管理4人力资源5财务管理6商业智能(客戶)
	 * @return    [array]            
	 */
	public function getDataList($param)
	{
		$ruleModel = new \app\admin\model\Rule();
		$map = [];	
		if ($param['tree'] == 1) {
			$list = $this->getTypeList();
			foreach ($list as $k=>$v) {
				$where = [];
				$where = $this->getNewGroupPid($v['pid']);
				$groupList = db('admin_group')->where($where)->select() ? : [];
				$list[$k]['list'] = $groupList ? : [];				
			}
		} else {
			$where = [];
			if (isset($param['type'])) {
				$where['pid'] = $param['pid'];
				$where['type'] = $param['type'];
			} else {
				$where = $this->getNewGroupPid($param['pid']);
			}
			$list = db('admin_group')->where($where)->select() ? : [];		
			if ($param['rules'] == 1) {
				//角色权限分类关系
				$ruleTypes = $ruleModel->groupsToRules($param['pid']);
				if ($ruleTypes) {
					foreach ($list as $key => $val) {
						$dataRules = [];
						$biRules = [];
						$rules = stringToArray($val['rules']) ? : [];
						foreach ($rules as $k1=>$v1) {
							$ruleInfo = [];
							$ruleInfo = db('admin_rule')->where(['id' => $v1])->find();
							if ($ruleInfo['types'] == $ruleTypes[0]) {
								$dataRules[] = $v1;
							} elseif ($ruleInfo['types'] == $ruleTypes[1]) {
								$biRules[] = $v1;
							}
						}
						$list[$key]['rules'] = [];
						$list[$key]['rules']['data'] = $dataRules ? : [];
						$list[$key]['rules']['bi'] = $biRules ? : [];
						if ($val['pid'] == 1 || $val['pid'] == 5 || $val['pid'] == 6 || $val['pid'] == 9) {
							$list[$key]['type'] = 0;
						}
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
		if ($param['pid'] == 5 && $param['type'] == 'work') {
			//项目模块下角色
			$param['type'] = 0;
		}
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

	/**
	 * [getTypeList 获取分类列表]
	 * @param  备注：原自定义角色0,人事管理角色3,财务管理角色4，划分至客户管理角色中
	 * @return    [array]            
	 */
	public function getTypeList()
	{
		$list = ['0' => ['name' => '系统管理角色','pid' => 1],'1' => ['name' => '办公管理角色','pid' => 6],'2' => ['name' => '客户管理角色','pid' => 2],'3' => ['name' => '项目管理角色','pid' => '9']];
		return $list ? : [];
	}

	/**
	 * [getNewGroupPid 兼容9.0.5版本group pid对应关系]
	 * @param  备注：原自定义角色0,人事管理角色3,财务管理角色4，划分至客户管理角色中
	 * @return    [array]            
	 */	
	protected function getNewGroupPid($pid)
	{
		switch ($pid) {
			case '1' : 
				$where['pid'] = 1;
				$where['types'] = ['not in',['7']];
				break;
			case '2' : 
				$where = function($query) {
				        		$query->where(['pid' => ['in',['0','2','3','4']]])
			                    ->whereOr('type != 0 AND pid = 5');
							};				
				break;	
			case '9' : 
				$where = function($query) {
				        		$query->where(['pid' => 9])
			                    ->whereOr('types = 7 AND pid = 1');
							};	
				break;											
			default : 
				$where['pid'] = $pid;
				break;
		}
		return $where ? : [];	
	}
}