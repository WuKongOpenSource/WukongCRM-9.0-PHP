<?php
// +----------------------------------------------------------------------
// | Description: 项目操作日志
// +----------------------------------------------------------------------
// | Author:  
// +----------------------------------------------------------------------

namespace app\work\model;

use think\Db;
use app\admin\model\Common;
use com\verify\HonrayVerify;
use think\Cache;

class WorkLog extends Common
{
    /**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如微信模块用weixin作为数据表前缀
     */
	protected $name = 'work_task_log';
    protected $createTime = 'create_time';
    protected $updateTime = false;
	protected $autoWriteTimestamp = true;
	protected $insert = [
		'status' => 1,
	];	
	protected $taskField = [
		'name' => '任务名',
		//'main_user_id' => '负责人ID',
		//'owner_user_id' => '团队成员',
		'class_id' => '任务类型',
		'lable_id' => '标签',
		'description' => '任务描述',
		'start_time' => '开始时间',
		'stop_time' => '截至时间',
		'work_id' => '项目',
		'is_top' => '工作台',
	];

	/**
	 * 项目日志
	 * @param  
	 * @return
	 */
	public function workLogAdd($param)
	{
		$data = array();
		$data['status'] = $param['type'];
		switch ($param['type']) {
			case '1' : $param['content'] = '新建了任务：'.$param['name']; break;
			case '2' : 
				$param['content'] = '重命名任务为：'.$param['name']; 
				$data['status'] = 3; 
				break;
			case '3' : 
				$param['content'] = '删除了任务！';  
				$data['status'] = 4;
				break;			
		}
		unset($param['type']);
		$data['user_id'] = $param['create_user_id'];
		$data['content'] = $param['content'];
		$data['create_time'] = time();
		$data['work_id'] = $param['work_id'];
		$data['task_id'] = $param['task_id'] ? : '0'; //任务编辑ID为空 
		
		$flag = $this->insert($data);
		if ($flag) {
			return true;
		} else {
			return false;
		}
	}	

	/**
     * 添加新任务
     * @author yykun
     * @param
     * @return
     */	
	public function newTaskLog($param)
	{
		$data['content'] =  '添加新任务:'.$param['name'];
		$data['user_id'] = $param['user_id'];
		$data['task_id'] = $param['task_id'];
		$data['work_id'] = $param['work_id'];
		$data['create_time'] = time();
		$flag = $this->insert($data);
		if ($flag) {
			return true;
		} else {
			return false;
		}
	}

	/**
     * 任务模块修改添加日志
     * @author yykun
     * @param
     * @return
     */	
    public function taskLogAdd($param)
    {
    	$taskField = $this->taskField;
    	switch ($param['type']) {
		    case 'name':
		    	$data['content'] =  '将任务名由'.$param['before'].'改为:'.$param['after'];
		        break;
			case 'main_user_id':  //负责人
		    	$data['content']  = $param['after'];
		        break;
		    case 'owner_user_id':
		    	$typename = $taskField['owner_user_id'];
				$data['content']  = '编辑任务参与人';
		        break;
			case 'class_id': //分类
		    	$data['content'] = '设定项目类型为：'.$param['after'];
		        break;
		    case 'lable_id_add': //新增标签
		    	$data['content'] = '新增项目标签为：'.$param['after'];;
		        break;
		    case 'lable_id_del': //删除标签
		    	$data['content'] = '删除项目标签：'.$param['after'];;
		        break;
			case 'description': //描述
		    	$data['content'] =  '将描述由'.$param['before'].'改为:'.$param['after'];
		        break;
		    case 'start_time': //开始时间
		    	$typename = $taskField['start_time'];
		        break;
			case 'stop_time':  //截至时间
		    	$data['content'] = '修改截至时间为：'.$param['after'];
		        break;
		    case 'work_id':	//项目ID
		    	$data['content'] = $taskField['work_id'];
		        break;
			case 'is_top': //工作台
		    	$data['content'] = $taskField['is_top'];
		        break;
		    case 'owner_userid_del':  //删除参与者
		    	$data['content'] = '将'.$param['after'].'从任务中移除！';
		        break;
		    case 'owner_userid_add':  //添加参与者
		    	$data['content'] = '添加'.$param['after'].'参与任务！';
		        break;
		    case 'structure_id_del':  //删除参与部门
		    	$data['content'] = '将'.$param['after'].'（部门）从任务中移除！';
		        break;
		    case 'structure_id_add':  //添加参与部门
		    	$data['content'] = '添加'.$param['after'].'（部门）参与任务！';
		        break;
		    default:
				return false;
		}
		$data['user_id'] = $param['user_id'];
		$data['task_id'] = $param['task_id'];
		$data['work_id'] = $param['work_id'];
		$data['create_time'] = time();
		$flag = $this->insert($data);
		if ($flag) {
			return true;
		} else {
			return false;
		}
    }
}