<?php
// +----------------------------------------------------------------------
// | Description: 任务
// +----------------------------------------------------------------------
// | Author:  yykun
// +----------------------------------------------------------------------

namespace app\work\model;

use think\Db;
use app\admin\model\Common;
use app\admin\model\Message;
use app\admin\model\User as UserModel;
use app\admin\model\Structure as StructureModel;
use app\admin\model\Comment as CommentModel;
use app\work\model\WorkLog as LogModel;
use app\work\model\WorkLable as lableModel;
use app\work\model\WorkClass as classModel;
use com\verify\HonrayVerify;
use think\Cache;

class Task extends Common
{
    /**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如微信模块用weixin作为数据表前缀
     */
	protected $name = 'task';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
	protected $autoWriteTimestamp = true;
	protected $insert = [
		'status' => 1,
	];

	/**
     * 项目下任务列表(看板视图)
     * @author yykun
     * @return
     */ 
	public function getDataList($request, $user_id)
	{
		//权限项目判断
		$workModel = model('Work');
		$userModel = new \app\admin\model\User();
		$work_id = $request['work_id'];
		$ret = $workModel->checkWork($work_id, $user_id);
		if (!$ret) {
			$this->error = $workModel->getError();
			return false;
		}
		$classModel = model('WorkClass');
		//删除还原的任务，归类至未分组列表下，此列表不可拖拽编辑
		if ($this->where(['class_id' => 0,'ishidden' => 0,'work_id' => $work_id])->find()) {
			$classArr = ['0' => ['name' => '未分组','class_id' => 0]];
		}
		$classList = $classModel->getDataList($work_id);
		if ($classArr && $classList['list']) {
			$newList = array_merge($classArr,$classList['list']);
		} elseif ($classArr) {
			$newList = $classArr;
		} else {
			$newList = $classList['list'];
		}
		
		if ($request['main_user_id']) {
			$map['main_user_id'] = ['in',$request['main_user_id']];
		}
		//截止时间
		if ($request['stop_time_type']) {
			if ($request['stop_time_type'] == '5') { //没有截至日期
				$map['stop_time'] = '0';
			} elseif ($request['stop_time_type'] == '6') { //延期的
				$map['stop_time'] = ['between',[1,time()]];
				$map['status'] = 1;
			} elseif ($request['stop_time_type'] == '7') { //今日更新
				$timeAry = getTimeByType('today');
				$map['update_time'] = ['between',[$timeAry[0],$timeAry[1]]];
			} else {
				switch ($request['stop_time_type']) {
					case '1': //今天到期
						$timeAry = getTimeByType('today');
						break;
					case '2': //明天到期
						$temp = getTimeByType('today');
						$timeAry[0] = $temp[1];
						$timeAry[1] = $temp[1]+3600*24;
						break;
					case '3': //一周内到期
						$timeAry = getTimeByType('week');
						break;
					case '4': //一月内到期
						$timeAry = getTimeByType('month');
						break;
					default:
						break;
				}
				$map['stop_time'] = ['between',[$timeAry[0],$timeAry[1]]];
			}
		}
		if ($request['lable_id']) {
			$taskIds = [];
			$task_ids = [];
			foreach ($request['lable_id'] as $v) {
				$task_id = [];
				$lableWhere = [];
				$lableWhere['lable_id'] = ['like','%,'.$v.',%'];
				$lableWhere['work_id'] = $work_id;
				$lableWhere['status'] = ['in',['1','5']]; 	
				$lableWhere['ishidden'] = 0;
				$lableWhere['pid'] = 0;
				$lableWhere['is_archive'] = 0;			
				$task_id = $this->where($lableWhere)->column('task_id');
				if ($task_id && $task_ids) {
					$task_ids = array_unique(array_filter(array_merge($task_ids,$task_id)));
				} elseif ($task_id) {
					$task_ids = $task_id;
				}
			}
			$map['task_id'] = ['in',$task_ids];
		}
		$data = array();
		foreach ($newList as $key => $value) {
			$data[$key]['class_id'] = $value['class_id'] ? : -1;	
			$data[$key]['class_name'] = $value['name'];

			$map['status'] = $map['status'] ? : ['in',['1','5']];
			$map['ishidden'] = 0;
			$map['work_id'] = $request['work_id'];
			$map['class_id'] = $value['class_id'];
			$map['pid'] = 0;
			$map['is_archive'] = 0;

			$taskList = [];
			$resTaskList = $this->getTaskList($map);
			$data[$key]['count'] = $resTaskList['count'];
			$data[$key]['list'] = $resTaskList['list'];
		}
		return $data;
	}

	/**
     * 根据任务ID 获取操作记录
     * @author yykun
     * @return
     */
	public function getTaskLogList($param)
	{
		$list = Db::name('WorkTaskLog')->alias('l')
				->join('AdminUser u','u.id = l.user_id','LEFT')
				->field('l.*,u.realname,u.thumb_img')
				->where('l.task_id ='.$param['task_id'])
				->order('l.log_id desc')
				->select();
		foreach ($list as $key => $value) {
			$list[$key]['thumb_img'] = $value['thumb_img'] ? getFullPath($value['thumb_img']) : '';
		}
		return $list ? : [];
	}

	/**
     * 根据主键获取详情
     * @author yykun
     * @return
     */	
	public function getDataById($id = '', $userInfo)
	{		
		//读取参与人
		$userModel = new UserModel();
		$structModel = new StructureModel();
		$recordModel = new \app\admin\model\Record();
		$taskInfo = $this->where(['task_id' => $id])->find();
		if (!$taskInfo) {
			$this->error = '任务不存在或已删除';
			return false;
		}
		
	    $userlist = $userModel->getDataByStr($taskInfo['owner_user_id']);
		$taskInfo['owner_list'] = $userlist ? : array(); 
		
		$workInfo = Db::name('Work')->where(['work_id' => $taskInfo['work_id']])->find();
		$taskInfo['work_name'] = $workInfo['name'] ? : '';
		
		//读取部门
		$structList  = $structModel->getDataByStr($taskInfo['structure_ids']);
		$taskInfo['struct_list'] = $structList ? : array(); 

		//负责人
		$mainData = [];
		if ($taskInfo['main_user_id']) { 
			$mainData = $userModel->getDataById($taskInfo['main_user_id']);
		}
		$taskInfo['main_user_name'] = $mainData['realname'] ? : '';
		$taskInfo['main_user_img'] = $mainData['thumb_img'] ? : '';

		$taskInfo['stop_time'] = $taskInfo['stop_time'] ? : '';
		$lablelist = [];
		if ($taskInfo['lable_id']) {
			$lableModel = new \app\work\model\WorkLable();
			$lablelist = $lableModel->getDataByStr($taskInfo['lable_id']);
		}
		$taskInfo['lable_list'] = $lablelist ? : array();

		$commonmodel = new \app\admin\model\Comment();
		$param['type_id'] = $taskInfo['task_id'];
		$param['type'] = 'task';
		$taskInfo['replyList'] = $commonmodel->read($param);
		$subTaskList = $this->alias('t')
						->join('AdminUser u','u.id = t.main_user_id','LEFT')
						->field('t.task_id,t.pid,t.name,t.main_user_id,t.stop_time,t.status,t.class_id,u.id as main_user_id,u.realname,u.thumb_img')
						->where(' t.ishidden = 0 and ( t.status=1 or t.status=5 ) and t.pid ='.$id)
						->select();
		foreach ($subTaskList as $key => $value) {
			$subTaskList[$key]['thumb_img'] = $value['thumb_img'] ? getFullPath($value['thumb_img']) : '';
		}
		$taskInfo['subTaskList'] = $subTaskList;
		//相关业务
		$relationArr = $recordModel->getListByRelationId('task', $id);
		$taskInfo['businessList'] = $relationArr['businessList'];
		$taskInfo['contactsList'] = $relationArr['contactsList'];
		$taskInfo['contractList'] = $relationArr['contractList'];
		$taskInfo['customerList'] = $relationArr['customerList'];

		$createUserInfo = $userModel->getDataById($taskInfo['create_user_id']);
		$createUserInfo['thumb_img'] = $createUserInfo['thumb_img'] ? getFullPath($createUserInfo['thumb_img']) : '';
		$taskInfo['create_user_info'] = $createUserInfo;
		return $taskInfo;
	}

	/**
     * 创建任务
     * @author yykun
     * @return
     */	
	public function createTask($param)
	{
		$param['status'] = 1;
		$rdata['customer_ids'] = !empty($param['customer_ids']) ? arrayToString($param['customer_ids']) : ''; 
		$rdata['contacts_ids'] = !empty($param['contacts_ids']) ? arrayToString($param['contacts_ids']) : ''; 
		$rdata['business_ids'] = !empty($param['business_ids']) ? arrayToString($param['business_ids']) : ''; 
		$rdata['contract_ids'] = !empty($param['contract_ids']) ? arrayToString($param['contract_ids']) : '';  
		$arr = ['customer_ids','contacts_ids','business_ids','contract_ids'];
		foreach ($arr as $value) {
			unset($param[$value]);
		}
		$main_user_id = $param['main_user_id'] ? : $param['create_user_id'];
		$param['main_user_id'] = $main_user_id; //负责人
		$param['owner_user_id'] = ','.$main_user_id.','; //参与人
		$param['start_time'] = $param['start_time'] ? : strtotime(date('Y-m-d',time()));
		if ($param['start_time'] == $param['stop_time']) {
			$param['stop_time'] = $param['start_time']+86399;
		}
		$this->data($param)->allowField(true)->save();
		$task_id = $this->task_id;
		if ($task_id) {
			$rdata['status'] = 1;
			$rdata['create_time'] = time();
			$rdata['task_id'] = $task_id;
			Db::name('TaskRelation')->insert($rdata);
			
			if (!$param['pid']) {
				$taskLog = new LogModel();
				$datalog['name'] = $param['name'];
				$datalog['user_id'] = $param['create_user_id']; 
				$datalog['task_id'] = $task_id;
				$datalog['work_id'] = $param['work_id'] ? : '';
				$ret = $taskLog->newTaskLog($datalog);
				//操作日志
				actionLog($task_id,'','','新建了任务');
				//抄送站内信
				(new Message())->send(
                    Message::TASK_ALLOCATION,
                    [
                        'title' => $param['name'],
                        'action_id' => $task_id
                    ],
                    $main_user_id
                );
			}
			return $task_id;
		} else {
			$this->error = '添加失败';
			return false;
		}
	}
	
	/**
     * 编辑任务
     * @author yykun
     * @param type 类型(字段名)
     * @return
     */	
	public function updateDetTask($param){
		$LogModel = new LogModel();
		$userModel = new UserModel();
		$lableModel =new lableModel();
		$StructureModel = new StructureModel();
		$type = $param['type'] ? : '';
		if (!$param['task_id']) {
			$this->error = '参数错误！';
			return false;			
		}
		//关联业务
		if(isset($param['customer_ids']) && !empty($param['customer_ids'])){
			$rdata['customer_ids'] = $param['customer_ids'] ? arrayToString($param['customer_ids']) : ''; 
		}
		if(isset($param['contacts_ids']) && !empty($param['contacts_ids'])){
			$rdata['contacts_ids'] = $param['contacts_ids'] ? arrayToString($param['contacts_ids']) : ''; 
		}
		if(isset($param['business_ids']) && !empty($param['business_ids'])){
			$rdata['business_ids'] = $param['business_ids'] ? arrayToString($param['business_ids']) : ''; 
		}
		if(isset($param['contract_ids']) && !empty($param['contract_ids'])){
			$rdata['contract_ids'] = $param['contract_ids'] ? arrayToString($param['contract_ids']) : ''; 
		}
		$rdata['task_id'] = $param['task_id'];
		$arr = ['customer_ids','contacts_ids','business_ids','contract_ids'];
		foreach ($arr as $value) {
			unset($param[$value]);
		}
		
		$data = array();
		$taskInfo = $this->get($param['task_id']);
		$taskInfo = json_decode(json_encode($taskInfo),true);
		$data['type'] = $param['type'];
		$data['before'] = $taskInfo[$param['type']] ? $taskInfo[$param['type']] : '空';
		switch ($type) {
			case 'name' : 
				$data['after'] = $param['name']; break;
			case 'stop_time' : 
				if ($taskInfo['start_time'] > $param['stop_time']) {
					$this->error = '截止时间不能在开始时间之前';
					return false;
				}
				if ($param['stop_time']) {
					$data['after'] = date("Y-m-d",$param['stop_time']);
				} else {
					$data['after'] = '无';
				}
				break;
			case 'class_id'	:
				//类型修改
				$classModel = model('WorkClass');
				$taskInfo = $classModel->getDataById($param['class_id']);
				$data['after'] = $taskInfo['name'];	
				break;	
			case 'lable_id_add' : 
				//标签添加
				$lable = $lableModel->getNameByIds($param['lable_id_add']);
				if ($taskInfo['lable_id'] && $param['lable_id_add']) {
					$param['lable_id_add'] = array_unique(array_merge(stringToArray($taskInfo['lable_id']),$param['lable_id_add']));
				}
				$param['lable_id'] = arrayToString($param['lable_id_add']);
				$data['after'] = $lable ? implode(',',$lable) : '';
				unset($param['lable_id_add']);
				break;	
			case 'lable_id_del' :	
				//标签删除
				$lable = $lableModel->getNameByIds($param['lable_id_del']);
				if ($param['lable_id_del']) {
					$lable_id = array_unique(array_diff(stringToArray($taskInfo['lable_id']),$param['lable_id_del']));
					$param['lable_id'] = arrayToString($lable_id);
				} else {
					$param['lable_id'] = $taskInfo['lable_id'];
				}
				$data['after'] = $lable ? implode(',',$lable) : '';
				unset($param['lable_id_del']);	
				break;
			case 'structure_id_del' :
				//删除参与部门
				$structuredet = $StructureModel->getDataById($param['structure_id']);
				$param['structure_ids'] = str_replace(','.$param['structure_id_del'].',',',',$taskInfo['structure_ids']); //删除
				$data['after'] = $structuredet['name'];
				unset( $param['structure_id_del'] );
				break;	
			case 'structure_id_add' :
				//添加参与部门 
				$structuredet = $StructureModel->getDataById($param['owner_userid_add']);
				if ($taskInfo['structure_ids']) {
					$param['structure_ids'] = $taskInfo['structure_ids'].$param['structure_id_add'].','; //追加
				} else {
					$param['structure_ids'] = ','.$param['structure_id_add'].','; //首次添加
				}
				$data['after'] = $structuredet['name'];
				unset( $param['structure_id_add'] );
				break;	
			case 'owner_userid_del' :
				//删除参与成员 
				$userdet = $userModel->getDataById($param['owner_userid_del']);
				$param['owner_user_id'] = str_replace(','.$param['owner_userid_del'].',',',',$taskInfo['owner_user_id']); //删除
				$data['after'] = $userdet['realname'];
				unset( $param['owner_userid_del'] );
				break;
			case 'owner_userid_add' :
				//添加参与成员 
				$userdet = $userModel->getDataById($param['owner_userid_add']);
				if ($taskInfo['owner_user_id'] ){
					$param['owner_user_id'] = $taskInfo['owner_user_id'].$param['owner_userid_add'].','; //追加
				} else {
					$param['owner_user_id'] = ','.$param['owner_userid_add'].','; //首次添加
				}
				$data['after'] = $userdet['realname'];
				unset($param['owner_userid_add']);
				break;
			case 'main_user_id' :
				//设置负责人
				$userdet = $userModel->getDataById($param['main_user_id']);
				$data['after'] = '设定'.$userdet['realname'].'为主要负责人！';
				(new Message())->send(
                    Message::TASK_ALLOCATION,
                    [
                        'title' => $taskInfo['name'],
                        'action_id' => $param['task_id']
                    ],
                    $param['main_user_id']
                );
				break;				
		}

		$param['update_time'] = time();
		$data['work_id'] = $param['work_id'];
		$data['task_id'] = $param['task_id'];
		$data['user_id'] = $param['create_user_id'];
		unset($param['type']);
		unset($param['create_user_id']);
		$flag = $this->where(['task_id' => $param['task_id']])->update($param);
		
		if ($flag || count($rdata)) {
			if ($param['owner_user_id']) {
				$this->where(['task_id' => $param['task_id']])->setField('owner_user_id',$param['owner_user_id']);
			} 
			if (!$param['pid']) {
				$LogModel = new LogModel();
				$taskInfo = $LogModel->taskLogAdd($data);   
				actionLog($param['task_id'],$param['owner_user_id'],$param['structure_ids'],'修改了任务');
				$resRelation = Db::name('TaskRelation')->where(['task_id' => $param['task_id']])->find();
				if ($resRelation) {
					Db::name('TaskRelation')->where(['task_id' => $param['task_id']])->update($rdata); //更新关联关系	
				} else {
					$rdata['create_time'] = time();
					$rdata['status'] = 1;
					Db::name('TaskRelation')->insert($rdata); //更新关联关系
				}
			}
			return true;
		} else {
			$this->error = '操作失败';
			return false;
		}
	}

	/**
     * 任务统计不同状态
     * @author yykun
     * @param 
     * @return
     */	
	public function getCount($status = 0)
	{
		$map = array();
		if ($status > 0) {
			$map['status'] = $status;
		}
		$count = $this->where($map)->count();
		return $count ? : 0;
	}

	/**
     * 获取某一月份任务列表
     * @author yykun
     * @param 
     * @return
     */
	public function getDateList($param)
	{
		$start_time = $param['start_time'];
		$stop_time = $param['stop_time'];
		$user_id = $param['user_id'];
		// $date_list = dateList($start_time, $stop_time, 1);
		$where = [];
		$where['ishidden'] = 0;
		$where['is_archive'] = 0;
		$where['status'] = 1;
		$where['pid'] = 0;
		$str = ','.$user_id.',';
		$whereStr = ' ( create_user_id = '.$user_id.' or ( owner_user_id like "%'.$str.'%") or ( main_user_id = '.$user_id.' ) )';
		$whereDate = '( stop_time > 0 and stop_time between '.$start_time.' and '.$stop_time.' ) or ( update_time between '.$start_time.' and '.$stop_time.' )';
		$list = db('task')
				->where($where)
				->where($whereStr)
				->where($whereDate)
				->field('task_id,name,priority,start_time,stop_time,priority,update_time')
				->select();
		return $list ? : [];
	}

	/**
     * 删除任务
     * @author yykun
     * @param 
     * @return
     */
	public function delTaskById($param)
	{	
		if (!$param['task_id']) {
			$this->error = '参数错误';
			return false;
		}		
		$taskInfo = $this->get($param['task_id']);
		if (!$taskInfo) {
			$this->error = '数据不存在或已删除';
			return false;
		}
		$map['task_id'] = $param['task_id'];
		$temp['ishidden'] = 1;
		$temp['hidden_time'] = time();
		$flag = $this->where($map)->update($temp);
		if ($flag) {
			if (!$taskInfo['pid']) {
				actionLog( $taskInfo['task_id'],$taskInfo['owner_user_id'],$taskInfo['structure_ids'],'删除了任务');
			}
			return true;
		} else {
			$this->error = '删除失败';
			return false;
		}
	}

	/**
     * 归档任务
     * @author yykun
     * @param 
     * @return
     */
	public function archiveData($param)
	{
		$data['is_archive'] = 1;
		$data['archive_time'] = time();
		$flag = $this->where(['task_id' => $param['task_id']])->update($data);
		if ($flag) {                          
			//添加归档日志
			actionLog($param['task_id'],'','','归档了任务'); 
			return true;
		} else {
			$this->error = '归档失败';
			return false;
		}
	}

	/**
     * 归档任务恢复
     * @author yykun
     * @param 
     * @return
     */	
	public function recover($param)
	{
		$flag = $this->where(['task_id' => $param['task_id']])->setField('is_archive',0);
		if ($flag) {
			//添加日志
			actionLog( $param['task_id'],'','','恢复归档任务'); 
			return true;
		} else {
			$this->error = '操作失败';
			return false;
		}
	}

	/**
     * 任务权限判断
     * @author Michael_xu
     * @param 
     * @return
     */
	public function checkTask($task_id, $userInfo)
	{
		$userModel = new \app\admin\model\User();		
		$taskInfo = $this->get($task_id);
		if (!$taskInfo) {
			$this->error = '该任务不存在或已删除';
			return false;
		}
		$user_id = $userInfo['id'];
		$structure_id = $userInfo['structure_id'];
		$adminTypes = adminGroupTypes($user_id);
        if (in_array(1,$adminTypes) || in_array(7,$adminTypes)) {
			return true;
        }		
		if (($taskInfo['create_user_id'] == $user_id) || ($taskInfo['main_user_id'] == $user_id) || in_array($user_id, stringToArray($taskInfo['owner_user_id'])) || in_array($structure_id, stringToArray($taskInfo['structure_ids']))) {
			return true;
		}
		$workInfo = db('work')->where(['work_id' => $taskInfo['work_id']])->find();
		if ($taskInfo['is_open'] == 1) {
			return true;
		} else {
			//私有项目(只有项目成员可以查看)
			$workUser = db('work_user')->where(['work_id' => $taskInfo['work_id']])->column('user_id');
			if ($workUser && in_array($user_id,$workUser)) {
				return true;	
			}
			return false;		
		}		
	}

	/**
     * 查看关联个数
     * @author yykun
     * @param 
     * @return
     */	
    public function getRelationCount($task_id)
    {
    	$relationInfo = Db::name('TaskRelation')->where(['task_id' => $task_id])->find();
    	$count = 0;
    	if ($relationInfo) {
    		$count1 = count(stringToArray($relationInfo['customer_ids']));
    		$count2 = count(stringToArray($relationInfo['contacts_ids']));
    		$count3 = count(stringToArray($relationInfo['business_ids']));
    		$count4 = count(stringToArray($relationInfo['contract_ids']));
    		$count = $count1+$count2+$count3+$count4;
    	} 
    	return $count;
    }

	/**
     * 任务列表
     * @author Michael_xu
     * @param 
     * @return
     */	
    public function getTaskList($request)
    {
    	$search = $request['search'];
    	$whereStr = $request['whereStr'] ? : [];
    	$lable_id = $request['lable_id'] ? : '';
    	unset($request['search']);
    	unset($request['whereStr']);
    	unset($request['lable_id']);
		$request = $this->fmtRequest($request);
        $requestMap = $request['map'] ? : [];    	
    	$userModel = new \app\admin\model\User();
    	$lableModel = new \app\work\model\WorkLable();
    	$map = $requestMap;
    	$map['ishidden'] = $requestMap['ishidden'] ? : 0;
		if ($search) {
			//普通筛选
			$map['name'] = ['like','%'.$search.'%'];
		}
		$map = where_arr($map, 'work', 'task', 'index');  
		if ($lable_id) {
			$map['task.lable_id'] = array('like','%'.$lable_id.'%');
		}
    	$dataCount = db('task')->alias('task')->where($map)->where($whereStr)->count();
		$taskList = [];
		if ($dataCount) {
			$taskList = db('task')
						->alias('task')
						->join('AdminUser u','u.id = task.main_user_id','LEFT') 
						->join('Work w','w.work_id = task.work_id','LEFT')
						->field('task.task_id,task.name,task.main_user_id,task.is_top,task.work_id,task.lable_id,task.priority,task.stop_time,task.status,task.pid,task.create_time,task.owner_user_id,u.realname as main_user_name,u.thumb_img,w.name as work_name,color')
						->where($map)
						->where($whereStr)
						// ->limit(($request['page']-1)*$request['limit'], $request['limit'])
						->order('task.status asc,task.order_id asc')
						->select();
			foreach ($taskList as $key => $value) {
				if ($value['pid'] > 0) {
					$p_det = $this->field('task_id,name')->where(['task_id' => $value['pid']])->find();
					$taskList[$key]['pname'] = $p_det['name'];
				} else {
					$taskList[$key]['pname'] = '';
				}
				$taskList[$key]['thumb_img'] = $value['thumb_img'] ? getFullPath($value['thumb_img']) : '';
				$subcount = $this->where(['ishidden' => 0,'status' => 1,'pid' => $value['task_id']])->count();
				$subdonecount = $this->where(['ishidden' => 0,'status' => 5,'pid' => $value['task_id']])->count();
				$taskList[$key]['subcount'] = $subcount; //子任务
				$taskList[$key]['subdonecount'] = $subdonecount; //已完成子任务
				$taskList[$key]['commentcount'] = Db::name('AdminComment')->where(['type' => 'task','type_id' => $value['task_id']])->count(); //评论
				$taskList[$key]['filecount'] = Db::name('WorkTaskFile')->where(['task_id' => $value['task_id']])->count();
				$lableList = [];	
				if ($value['lable_id']) {
					$lableList =  $lableModel->getDataByStr($value['lable_id']);
					$taskList[$key]['lableList'] = $lableList ? : array();
				}
				$taskList[$key]['lableList'] = $lableList ? : array();
				//参与人
				//负责人信息
				$taskList[$key]['main_user'] = $value['main_user_id'] ? $userModel->getDataById($value['main_user_id']) : NULL;
				$taskList[$key]['relationCount'] = $this->getRelationCount($value['task_id']);
				$is_end = 0;
				if (!empty($value['stop_time']) && (strtotime(date('Ymd'))+86399 > $value['stop_time'])) $is_end = 1;
				$taskList[$key]['is_end'] = $is_end;
				$taskList[$key]['checked'] = ($value['status']=='5') ? true : false;
			}
		}
		$data = [];
		$data['count'] = $dataCount ? : 0;
		$data['list'] = $taskList ? : [];
		return $data;	
    } 
}