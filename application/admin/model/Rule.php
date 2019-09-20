<?php
// +----------------------------------------------------------------------
// | Description: 规则
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com
// +----------------------------------------------------------------------

namespace app\admin\model;

use app\admin\model\Common;
use \think\Db;

class Rule extends Common 
{
    /**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如CRM模块用crm作为数据表前缀
     */
	protected $name = 'admin_rule';

	/**
	 * [getDataList 获取列表]
	 * @param     string   $type [是否为树状结构]
	 * @param     int   $pid 角色分类：0客户自定义角色,1系统默认管理角色,2客户管理角色,3人力资源管理角色,4财务管理角色,5项目管理角色,6办公管理角色
	 * @return    [array]                         
	 */
	public function getDataList($param)
	{
		$type = $param['type'];
		$groupPid = $param['pid'] ? : 0;
		$types = $this->groupsToRules($groupPid);
		// 若type为tree，则返回树状结构
		if ($type == 'tree') {
			$cat = new \com\Category('admin_rule', array('id', 'pid', 'title', 'title'));
			$data = $cat->getList('', 0, 'id');
			foreach ($data as $k => $v) {
				if ($v['id'] == '31') {
					unset($data[$k]); 
					continue;
				}
				$data[$k]['check'] = false;
				if ($types && !in_array((int)$v['types'], $types)) {
					unset($data[$k]);
				}
				if (empty($v['status'])) {
					unset($data[$k]);
				}		
			}
			$data = array_merge($data);
			$tree = new \com\Tree();
			$list = $tree->list_to_tree($data, 'id', 'pid', 'child', 0, true, array('pid'));
		} elseif ($types) {
			$list = Db::name('AdminRule')->where(['types' => ['in',$types]])->select();
		}
		return $list;
	}

	//添加规则
	public function createData($param)
	{
		if($param['pid'] && $param['name']&&$param['title']){
			$pdet = Db::name('AdminRule')->where('id ='.$param['pid'].'')->find();
			if ($pdet['level'] == 1) {
				$data['level'] = 2;
			} elseif ( $pdet['level'] == 2){
				$data['level'] = 3;
			} else {
				$this->error = '等级参数错误';
				return false;
			}
			$data['pid'] = $param['pid']; //上级ID
			$data['name'] = $param['name']; //方法名
			$data['title'] = $param['title'];//名称 
			$data['status'] = 1;  //状态1 显示

			//1超级管理员2系统设置管理员3部门与员工管理员4审批流管理员5工作台管理员6客户管理员7项目管理员8公告管理员
			$data['types'] = $param['types'];
			$flag = $this->insert($data);
			if ($flag) {
				return true;
			} else {
				$this->error = '添加失败';
				return false;
			}
		} else {
			$this->error = '参数错误';
			return false;
		}
	}

	//编辑规则
	public function updateDataById($param,$id)
	{
		if ($param['id']) {
			$flag = $this->where('id ='.$param['id'].'')->update($param);
			return true;
		} else {
			$this->error = '参数错误';
			return false;
		}
	}

	/**
     * 角色与规则对照表
     * @param $groupPid 角色分类：0客户自定义角色,1系统默认管理角色,2客户管理角色,3人力资源管理角色(原客户),4财务管理角色(原客户),5项目管理角色,6办公管理角色,7人力资源管理角色,8财务管理角色
     * @param  rule：types 0系统设置1工作台2客户管理3项目管理4人力资源5财务管理6商业智能(客戶)
     * @return
     */	
    public function groupsToRules($groupPid)
    {
    	$ruleTypes = [];
    	switch ($groupPid) {
    		case '0' : 
    		case '2' : 
    			$ruleTypes = ['2','6']; 
    			break;
    		case '1' : 
    			$ruleTypes = ['0']; 
    			break;  		
			case '5' : 
    			$ruleTypes = ['3']; 
    			break;  
			case '6' : 
    			$ruleTypes = ['1','7']; 
    			break;      		
    		default : $ruleTypes = [$groupPid]; 
    			break;			  			
    	}
    	return $ruleTypes;
    }
}