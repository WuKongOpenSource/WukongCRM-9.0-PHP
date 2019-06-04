<?php
// +----------------------------------------------------------------------
// | Description: 任务
// +----------------------------------------------------------------------
// | Author:
// +----------------------------------------------------------------------

namespace app\work\model;

use think\Db;
use app\admin\model\Common;
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
    protected $updateTime = false;
	protected $autoWriteTimestamp = true;
	protected $insert = [
		'status' => 1,
	];

	//[getDataList 项目下任务列表]
	public function getDataList($request, $user_id)
	{
		//权限项目判断
		$workModel = model('Work');
		$ret = $workModel->checkWork($request['work_id'], $user_id);
		if(!$ret){
			$this->error = $workModel->getError();
			return false;
		}
		$classModel = model('WorkClass');

		$list = $classModel->getDataList($request['work_id']);
		if( $request['search'] ) {
			$map['task.main_user_id'] = ['in',$request['search'] ];
		}
		if( $request['stop_time_type'] ) {
			if($request['stop_time_type']=='5'){ //没有截至日期
				$map['task.stop_time'] = '0';
			} else if($request['stop_time_type']=='6'){ //延期的
				$map['task.stop_time'] = ['lt',time()];
			} else if($request['stop_time_type']=='7'){ //今日更新
				$timeAry = getTimeByType('today');
				$map['task.create_time'] = ['between','"'.$timeAry[0].','.$timeAry[1].'"'];
			} else{
				switch ($request['stop_time_type']) {
					case '1': //今天到期
						$timeAry = getTimeByType('today');
						break;
					case 2: //明天到期
						$temp = getTimeByType('today');
						$timeAry[0] = $temp[1];
						$timeAry[1] = $temp[1]+3600*24;
						break;
					case 3: //一周内到期
						$timeAry = getTimeByType('week');
						break;
					case 4: //一月内到期
						$timeAry = getTimeByType('month');
						break;
					default:
						break;
				}
				$map['task.stop_time'] = ['between',''.$timeAry[0].','.$timeAry[1].''];
			}
		}
		if ( $request['lable_id'] ) {
			$map['task.lable_id'] = ['like','%,'.$request['lable_id'].',%' ];
		}
		$nullary['class_id'] = '';
		$nullary['name'] = '未分组';
		$list['list'][] = $nullary;
		$data = array();
		foreach ( $list['list'] as $key => $value ) {
			$data[$key]['class_id'] = $value['class_id'];
			$data[$key]['class_name'] = $value['name'];

			$map['task.status'] =  array(['=',1],['=',5],'or');
			$map['task.ishidden'] =0;
			$map['task.work_id'] = $request['work_id'];
			$map['task.class_id'] = $value['class_id'];
			$map['task.pid'] = 0;

			$data[$key]['count'] = Db::name('Task')->alias('task')
								->join('AdminUser user','user.id = task.main_user_id','LEFT')
								->field('task.*')
								->where( $map )
								->order('task.order_id asc')
								->count();
			if($data[$key]['count']==0){
				$data[$key]['list'] = array();
				continue;
			}
			$temp =  Db::name('Task')->alias('task')
					->join('AdminUser user','user.id = task.main_user_id','LEFT')
					->field('task.main_user_id,task.task_id,task.name,task.stop_time,task.lable_id,task.pid,task.class_id,task.order_id,task.status,task.create_time,user.realname as username ,user.thumb_img as userImg')
					->where( $map )
					->order('task.order_id asc')
					->select();

			foreach ($temp as $k => $v) {
				$temp[$k]['checked'] = $v['status']==5?true:false;
				if( $v['pid']>0 ){
                    $p_det = Db::name('Task')->field('task_id,name')->where('task_id ='.$v['pid'])->find();
                    $temp[$k]['pname'] = $p_det['name'];
                } else {
                    $temp[$k]['pname'] = '';
                }

                $subcount = Db::name('Task')->where(' status=1 and  pid ='.$v['task_id'])->count();
                $subdonecount = Db::name('Task')->where('status = 5 and pid ='.$v['task_id'])->count();
                $temp[$k]['subcount'] = $subcount; //子任务
                $temp[$k]['subdonecount'] = $subdonecount; //已完成子任务
                $temp[$k]['commentcount'] = Db::name('AdminComment')->where('type=1 and type_id ='.$v['task_id'])->count();
				$temp[$k]['filecount'] = Db::name('WorkTaskFile')->where('task_id ='.$v['task_id'])->count();
                if ($v['lable_id'] && $v['lable_id'] != ',,' ) {
                	$lableModel = model('WorkLable');
                    $temp[$k]['lableList'] = $lableModel->getDataByStr($v['lable_id']);
                } else {
					$temp[$k]['lableList'] = array();
				}
			}
			$data[$key]['list'] = $temp;
		}
		return $data;
	}

    //根据任务ID 获取操作记录
	public function getTaskLogList($param)
	{
		$list = Db::name('WorkTaskLog')->alias('l')
				->join('AdminUser u','u.id = l.user_id','LEFT')
				->field('l.*,u.realname,u.thumb_img')
				->where('l.task_id ='.$param['task_id'])
				->order('l.log_id desc')
				->select();
		foreach ($list as $key => $value) {
			$list[$key]['thumb_img'] = getFullPath($value['thumb_img']);
		}
		return $list ? : [];
	}

	//[getDataById 根据主键获取详情]
	public function getDataById($id = '', $userInfo = [])
	{
		//读取参与人
		$model = new UserModel();
		$data = Db::name('Task')->where(' ishidden =0 and task_id ='.$id)->find();
		if(!$data){
			$this->error = '任务不存在或已删除';
			return false;
		}

	    $userlist = $model->getDataByStr($data['owner_user_id']);
		$data['owner_list'] = $userlist ? $userlist : array();

		$workDet = Db::name('Work')->where('work_id = '.$data['work_id'].'')->find();
		$data['workname'] = $workDet['name']? $workDet['name']:'';

		//读取部门
		$structModel = new StructureModel();
		$structList  = $structModel->getDataByStr($data['structure_ids']);
		$data['struct_list'] = $structList?$structList: array();

		if ( $data['main_user_id'] ) { //负责人姓名
			$maindet = $model->getDataById($data['main_user_id']);
			$data['main_user_name'] = $maindet['realname'];
			$data['main_user_img'] = $maindet['thumb_img'];
		} else {
			$data['main_user_name'] = '';
			$data['main_user_name'] = '';
		}
		$data['stop_time'] = $data['stop_time']?$data['stop_time']:'';
		if ($data['lable_id']) {
			$lableModel = new \app\work\model\WorkLable();
			$lablelist = $lableModel->getDataByStr($data['lable_id']);
			$data['lable_list'] = $lablelist?$lablelist: array();
		} else {
			$data['lable_list'] = array();
		}
		$commonmodel = new \app\admin\model\Comment();
		$param['type_id'] = $data['task_id'];
		$param['type'] = 'task';
		$data['replyList'] = $commonmodel->read($param);
		$subtasktemp = Db::name('Task')->alias('t')
						->join('AdminUser u','u.id = t.main_user_id','LEFT')
						->field('t.task_id,t.pid,t.name,t.main_user_id,t.stop_time,t.status,t.class_id,u.id as main_user_id,u.realname,u.thumb_img')
						->where(' t.ishidden = 0 and ( t.status=1 or t.status=5 ) and t.pid ='.$id)
						->select();
		foreach ($subtasktemp as $key => $value) {
			$subtasktemp[$key]['thumb_img'] = $value['thumb_img']?getFullPath($value['thumb_img']):'';
		}
		$data['subTaskList'] = $subtasktemp;
		$relation = Db::name('TaskRelation')->where('task_id ='.$id)->find();
		$BusinessModel = new \app\crm\model\Business();
		$data['businessList'] = $relation['business_ids']?$BusinessModel->getDataByStr($relation['business_ids']):''; //商机
		$ContactsModel = new \app\crm\model\Contacts();
		$data['contactsList'] = $relation['contacts_ids']?$ContactsModel->getDataByStr($relation['contacts_ids']):''; //联系人
		$ContractModel = new \app\crm\model\Contract();
		$data['contractList'] = $relation['contract_ids']?$ContractModel->getDataByStr($relation['contract_ids']):''; //合同
		$CustomerModel = new \app\crm\model\Customer();
		$data['customerList'] = $relation['customer_ids']?$CustomerModel->getDataByStr($relation['customer_ids']):''; //客户

		$createUserInfo = Db::name('AdminUser')->field('id,realname,thumb_img')->where('id = '.$data['create_user_id'].'')->find();
		$createUserInfo['thumb_img'] = $createUserInfo['thumb_img']?getFullPath($createUserInfo['thumb_img']):'';
		$data['create_user_info'] = $createUserInfo;
		return $data;
	}

	//创建任务 ：任务名
	public function createTask($param)
	{
		$param['create_time'] = time();//创建时间
		$param['update_time'] = time();
		$param['status'] = 1;

		$rdata['customer_ids'] = !empty($param['customer_ids']) ? ','.implode(',',$param['customer_ids']).',' : '';
		$rdata['contacts_ids'] = !empty($param['contacts_ids']) ? ','.implode(',',$param['contacts_ids']).',' : '';
		$rdata['business_ids'] = !empty($param['business_ids']) ? ','.implode(',',$param['business_ids']).',' : '';
		$rdata['contract_ids'] = !empty($param['contract_ids']) ? ','.implode(',',$param['contract_ids']).',' : '';
		$arr = ['customer_ids','contacts_ids','business_ids','contract_ids'];
		foreach($arr as $value){
			unset($param[$value]);
		}
		if($param['main_user_id']){
			$param['owner_user_id'] = ','.$param['main_user_id'].',';
		}
		$task_id = $this->insertGetId($param); //添加任务

		if ($task_id) {
			$rdata['status'] = 1;
			$rdata['create_time'] = time();
			$rdata['task_id'] = $task_id;
			Db::name('TaskRelation')->insert($rdata);

			if(!$param['pid']){
				$taskLog = new LogModel();// model('TaskLog');
				$datalog['name'] = $param['name'];
				$datalog['user_id'] = $param['create_user_id'];
				$datalog['task_id'] = $task_id;//添加任务ID
				$datalog['work_id'] = $param['work_id']?:'';//添加任务ID

				$ret = $taskLog->newTaskLog($datalog);
				//操作日志
				actionLog($task_id,'','','新建了任务');
			}
			return $task_id;
		} else {
			$this->error = '添加失败';
			return false;
		}
	}

	/*
	*$param['type'] : 字段名如：name main_user_id 等
	*/
	public function createDetTask($param){
		$LogModel = new LogModel(); //日志模型
		$userModel = new UserModel(); //员工模型
		$lableModel =new lableModel(); //标签模型
		$StructureModel = new StructureModel(); //部门模型

		//模型
		if(isset($param['customer_ids']) && count($param['customer_ids']) ){
			$rdata['customer_ids'] = $param['customer_ids']?','.implode(',',$param['customer_ids']).',':'';
		}
		if(isset($param['contacts_ids']) && count($param['contacts_ids']) ){
			$rdata['contacts_ids'] = $param['contacts_ids']?','.implode(',',$param['contacts_ids']).',':'';
		}
		if(isset($param['business_ids']) && count($param['business_ids']) ){
			$rdata['business_ids'] = $param['business_ids']?','.implode(',',$param['business_ids']).',':'';
		}
		if(isset($param['contract_ids']) && count($param['contract_ids']) ){
			$rdata['contract_ids'] = $param['contract_ids']?','.implode(',',$param['contract_ids']).',':'';
		}
		$rdata['task_id'] = $param['task_id'];
		$arr = ['customer_ids','contacts_ids','business_ids','contract_ids'];
		foreach($arr as $value){
			unset($param[$value]);
		}

		$data = array();
		$det = $this->where('task_id = '.$param['task_id'])->find();
		$det = json_decode(json_encode($det),true);
		$data['type'] = $param['type'];
		$data['before'] = $det[$param['type']]?$det[$param['type']]:'空';
		if ( $param['type'] == 'name' ) {
			$data['after'] = $param['name'];
		}
		if ( $param['type'] == 'stop_time' ) {
			if( $det['start_time'] > $param['stop_time'] ) {
				$this->error = '截止时间不能在开始时间之前';
				return false;
			}
			if($param['stop_time']){
				$data['after'] = date("Y-m-d",$param['stop_time']);
			} else {
				$data['after'] = '无';
			}
		}
		if ( $param['type'] == 'class_id' )  {//类型修改
			$classModel = model('WorkClass');
			$det = $classModel->getDataById($param['class_id']);
			$data['after'] = $det['name'];
		}
		if ( $param['type'] == 'lable_id_add' ) {//标签添加
			$lable = $lableModel->getDataById($param['lable_id_add']);
			if ( $det['lable_id'] && ( $det['lable_id'] != ',,' ) ) {
				$param['lable_id']=$det['lable_id'].$param['lable_id_add'].','; //追加
			} else {
				$param['lable_id']=','.$param['lable_id_add'].','; //首次添加
			}
			$data['after'] = $lable['name'];
			unset($param['lable_id_add']);
		}
		if ( $param['type'] == 'lable_id_del' ) { //标签删除
			$lable = $lableModel->getDataById($param['lable_id_del']);
			$param['lable_id']=str_replace(','.$param['lable_id_del'].',',',',$det['lable_id']); //删除
			$data['after'] = $lable['name'];
			unset($param['lable_id_del']);
		}
		if ( $param['type'] == 'structure_id_del' ) {	 //删除参与部门
			$structuredet = $StructureModel->getDataById($param['structure_id']);
			$param['structure_ids']=str_replace(','.$param['structure_id_del'].',',',',$det['structure_ids']); //删除
			$data['after'] = $structuredet['name'];
			unset( $param['structure_id_del'] );
		}
		if ( $param['type'] == 'structure_id_add' )  {	//添加参与部门
			$structuredet = $StructureModel->getDataById($param['owner_userid_add']);
			if ( $det['structure_ids'] && ( $det['structure_ids'] != ',,' ) ) {
				$param['structure_ids'] = $det['structure_ids'].$param['structure_id_add'].','; //追加
			} else {
				$param['structure_ids'] = ','.$param['structure_id_add'].','; //首次添加
			}
			$data['after'] = $structuredet['name'];
			unset( $param['structure_id_add'] );
		}
		if ($param['type'] == 'owner_userid_del') {	//删除参与成员
			$userdet = $userModel->getDataById($param['owner_userid_del']);
			$param['owner_user_id']=str_replace(','.$param['owner_userid_del'].',',',',$det['owner_user_id']); //删除
			$data['after'] = $userdet['username'];
			unset( $param['owner_userid_del'] );
		}
		if ($param['type'] == 'owner_userid_add')  { //添加参与成员
			$userdet = $userModel->getDataById($param['owner_userid_add']);
			if ( $det['owner_user_id']&&($det['owner_user_id'] != ',,') ){
				$param['owner_user_id'] = $det['owner_user_id'].$param['owner_userid_add'].','; //追加
			} else {
				$param['owner_user_id'] = ','.$param['owner_userid_add'].','; //首次添加
			}
			$data['after'] = $userdet['username'];
			unset($param['owner_userid_add']);
		}

		if ( $param['type' == 'main_user_id'] ) { //设置负责人
			$userdet = $userModel->getDataById($param['main_user_id']);
			$data['after'] = '设定'.$userdet['username'].'为主要负责人！';
		}
		$param['update_time'] = time();
		$data['work_id'] = $param['work_id'];
		$data['task_id'] = $param['task_id'];
		$data['user_id'] = $param['create_user_id'];
		unset( $param['type'] );
		unset( $param['create_user_id']);
		$flag =  $this->where('task_id = '.$param['task_id'])->update($param);

		if ($flag || count($rdata)) {
			if ($param['main_user_id']) {
				$taskDet = Db::name('Task')->where('task_id ='.$param['task_id'])->find();
				$mainstr = ','.$param['main_user_id'].',';
				if ( $taskDet['owner_user_id'] && ( $taskDet['owner_user_id'] ==',' ) ){
					$new_owner_user_id = $mainstr;
				} else if( $taskDet['owner_user_id'] && ( strpos($taskDet['owner_user_id'],$mainstr) == false ) ) {
					$new_owner_user_id = ','.$param['main_user_id'].',';
				} else if( $taskDet['owner_user_id'] && ( strpos($taskDet['owner_user_id'],$mainstr) ) ) {
					$new_owner_user_id = $taskDet['owner_user_id'].$param['main_user_id'].',';
				} else {
					$new_owner_user_id = $mainstr;
				}
				Db::name('Task')->where('task_id = '.$param['task_id'])->setField('owner_user_id',$new_owner_user_id);
			}

			$LogModel = new LogModel();
			if (!$param['pid']) {
				$det = $LogModel->taskLogAdd($data);
				actionLog( $param['task_id'],$param['owner_user_id'],$param['structure_ids'],'修改了任务');
				$fflag = Db::name('TaskRelation')->where('task_id = '.$param['task_id'])->find();
				if($fflag){
					Db::name('TaskRelation')->where('task_id = '.$param['task_id'])->update($rdata); //更新关联关系
				} else {
					$rdata['create_time'] = time();
					$rdata['status'] = 1;
					Db::name('TaskRelation')->insert($rdata); //更新关联关系
				}
			}
			return true;
		} else {
			$this->error = '操作失败'; //
			return false;
		}
	}

	/**
	 * 任务统计不同状态
	 */
	public function getCount($status = 0)
	{
		$map = array();
		if ( $status > 0 ) {
			$map['status'] = $status;
		}
		$count = $this->where($map)->count();
		return $count?$count:0;
	}

	//获取某一月份任务列表
	public function getDateList($start_time, $end_time)
	{
		$date_list = dateList($start_time, $end_time, 1);
		$list = db('task')
				->where(['stop_time' => ['between',[$start_time,$end_time]],'status'=>1])
				->field('task_id,name,priority,stop_time')
				->select();
		$listArr = [];
		foreach ( $list as $key => $value ) {
			$listArr[date('Y-m-d', $value['stop_time'])][] = $value;
		}
		return $listArr ? : [];
	}

	//删除任务
	public function delTaskById($param)
	{
		$taskInfo = $this->get($param['task_id']);
		if (!$taskInfo) {
			$this->error = '数据不存在或已删除';
			return false;
		}
		if ($param['task_id']) {
			$map['task_id'] = $param['task_id'];
			$temp['ishidden'] = 1;
			$temp['hidden_time'] = time();
			$flag = $this->where($map)->update($temp);
			if ($flag) {
				if(!$taskInfo['pid']){
					// 添加删除日志
					/* $logModel = new LogModel();
					$data = array();
					$data['type'] = 3;
					$data['create_user_id'] = $param['create_user_id'];
					$data['task_id'] = $param['task_id'];
					$a = $logModel->workLogAdd($data); */
					actionLog( $taskInfo['task_id'],$taskInfo['owner_user_id'],$taskInfo['structure_ids'],'删除了任务');
				}
				return true;
			} else {
				$this->error = '删除失败';
				return false;
			}
		} else {
			$this->error = '参数错误';
			return false;
		}
	}

	//归档任务
	public function archiveData($param)
	{
		$data['status'] = 3;
		$data['archive_time'] = time();
		$flag = $this->where('task_id ='.$param['task_id'])->update($data);
		if ( $flag ) {
			//添加归档日志
			actionLog( $param['task_id'],'','','归档了任务');
			return true;
		} else {
			$this->error = '归档失败';
			return false;
		}
	}

	//归档任务恢复
	public function recover($param)
	{
		$flag = $this->where('task_id ='.$param['task_id'])->setField('status',1);
		if ( $flag ) {
			//添加日志
			actionLog( $param['task_id'],'','','恢复归档任务');
			return true;
		} else {
			$this->error = '操作失败';
			return false;
		}
	}

	//[checkTask 任务权限判断]
	public function checkTask($task_id, $userInfo)
	{
		$userModel = new \app\admin\model\User();
		$adminUserId = $userModel->getAdminId(); //管理员ID
		if(in_array($userInfo['id'],$adminUserId)){
			return true;
		}
		$info = $this->get($task_id);
		if (!$info) {
			$this->error = '该任务不存在或已删除';
			return false;
		}
		$resData = Db::name('Task')->where(' task_id = '.$task_id.' and ((create_user_id = '.$userInfo['id'].') or (main_user_id = '.$userInfo['id'].')  or (owner_user_id like "%,'.$userInfo['id'].',%") or (structure_ids like "%,'.$userInfo['structure_id'].',%" ) ) ')->find();
		if (!$resData) {
			$taskdet = Db::name('Task')->where('task_id = '.$task_id)->find();
			$userdet = Db::name('AdminUser')->where('id ='.$taskdet['create_user_id'])->find();
			if($userdet['parent_id'] == $userInfo['id']) {
				return true;
			}
			$this->error = '没有权限';
			return false;
		}
		return true;
	}

	//查看关联个数
    public function getRelationCount($task_id)
    {
    	$det = Db::name('TaskRelation')->where('task_id = '.$task_id)->find();
    	$count = 0;
    	if($det){
    		$count1 = count( array_filter( explode(',', $det['customer_ids']) ) );
    		$count2 = count( array_filter( explode(',', $det['contacts_ids']) ) );
    		$count3 = count( array_filter( explode(',', $det['business_ids']) ) );
    		$count4 = count( array_filter( explode(',', $det['contract_ids']) ) );
    		$count = $count1+$count2+$count3+$count4;
    	}
    	return $count;
    }
}
