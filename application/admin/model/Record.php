<?php
// +----------------------------------------------------------------------
// | Description: 跟进记录
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com
// +----------------------------------------------------------------------
namespace app\admin\model;

use think\Db;
use app\admin\model\Common;
use think\Request;
use think\Validate;

class Record extends Common
{
	/**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如CRM模块用crm作为数据表前缀
     */
	protected $name = 'admin_record';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
	protected $autoWriteTimestamp = true;
	protected $types_arr = ['crm_leads','crm_customer','crm_contacts','crm_product','crm_business','crm_contract','oa_log','admin_record'];

	/**
     * [getDataList 跟进记录list]
     * @author Michael_xu
     * @param     [string]                   $map [查询条件]
     * @param     [number]                   $page     [当前页数]
     * @param     [number]                   $limit    [每页数量]
     * @param     [string]                   $by    [分类]
     * @param     [string]                   $types    [类别]
     * @param     [number]                   $types_id    [类别Id]
     * @return    [array]                    
     */		
	public function getDataList($request, $by = '')
    {  	
    	$userModel = new \app\admin\model\User();
    	$commonModel = new \app\admin\model\Comment();
    	$fileModel = new \app\admin\model\File();
    	$structureModel = new \app\admin\model\Structure();
    	$lableModel = new \app\work\model\WorkLable();
    	$taskModel = new \app\work\model\Task();

        $request = $this->fmtRequest( $request );
        $map = $request['map'] ? : [];
		if (!$map['types'] || !$map['types_id']) {
			$this->error = '参数错误';
			return false;
		}
		switch ($by) {
			case 'record' : 
				$where_record = [];
				$where_record['types'] = $map['types'];
				$where_record['types_id'] = $map['types_id'];				
				//客户模块下包含被转化线索的跟进记录
				if ($map['types'] == 'crm_customer') {
					if ($leads_id = db('crm_leads')->where(['customer_id' => $map['types_id']])->value('leads_id')) {
						$whereOr = [];
						$whereOr['types'] = 'crm_leads';
						$whereOr['types_id'] = $leads_id;
					}
				}
				//联系人下包含关联的客户的跟进记录
				if ($map['types'] == 'crm_contacts') {
					$whereOr = [];
					$whereOr['contacts_ids'] = array('like','%,'.$map['types_id'].',%');
				}
				if ($map['types'] == 'crm_business') {
					$whereOr = [];
					$whereOr['business_ids'] = array('like','%,'.$map['types_id'].',%');
				}							
				$list = db('admin_record')
					->page($request['page'], $request['limit'])
					->order('create_time desc')
					->select(function($query) use ($where_record,$whereOr){
					    $query->where($where_record)->whereOr(function ($query) use ($whereOr) {
						    $query->where($whereOr);
						});
					});
				foreach ($list as $k=>$v) {
					$list[$k]['id'] = $v['record_id'];
					$list[$k]['cate'] = 1;
				}
				$dataCount = db('admin_record')
							->where(function($query) use ($where_record,$whereOr){
								$query->where($where_record)->whereOr(function ($query) use ($whereOr) {
									$query->where($whereOr);
								});
							})->count();
				break;
			case 'log' :
				$where_log = [];
				$r_logs = $this->getRelationIdsByType($map['types'], $map['types_id'], 'oa_log') ? : [];
				$where_log['log_id'] = ['in',$r_logs];
				$list = db('oa_log')
					->page($request['page'], $request['limit'])
					->order('create_time desc')				
					->where($where_log)
					->select();
				foreach ($list as $k=>$v) {
					$list[$k]['id'] = $v['log_id'];
					$list[$k]['cate'] = 2;
				}				
				$dataCount = db('oa_log')->where($where_log)->count();
				break;
			case 'examine' :
				$where_examine = [];
				$r_logs = $this->getRelationIdsByType($map['types'], $map['types_id'], 'oa_examine') ? : [];
				$where_examine['examine_id'] = ['in',$r_logs];			
				$list = db('oa_examine')
					->page($request['page'], $request['limit'])
					->order('create_time desc')				
					->where($where_examine)
					->select();
				foreach ($list as $k=>$v) {
					$list[$k]['id'] = $v['examine_id'];
					$list[$k]['cate'] = 3;
				}				
				$dataCount = db('oa_examine')->where($where_examine)->count();	
				break;
			case 'task' :
				$where_task = [];
				$r_logs = $this->getRelationIdsByType($map['types'], $map['types_id'], 'task') ? : [];
				$where_task['task_id'] = ['in',$r_logs];				
				$list = db('task')
					->page($request['page'], $request['limit'])
					->order('create_time desc')				
					->where($where_task)
					->select();
				foreach ($list as $k=>$v) {
					$list[$k]['id'] = $v['task_id'];
					$list[$k]['cate'] = 4;
				}				
				$dataCount = db('task')->where($where_task)->count();
				break;
			case 'event' :
				$where_event = [];
				$r_logs = $this->getRelationIdsByType($map['types'], $map['types_id'], 'oa_event') ? : [];
				$where_event['event_id'] = ['in',$r_logs];				
				$list = db('oa_event')
					->page($request['page'], $request['limit'])
					->order('create_time desc')				
					->where($where_event)
					->select();
				foreach ($list as $k=>$v) {
					$list[$k]['id'] = $v['event_id'];
					$list[$k]['cate'] = 5;
				}				
				$dataCount = db('oa_event')->where($where_event)->count();
				break;											
			default : 
				$where_log = [];
				$r_logs = $this->getRelationIdsByType($map['types'], $map['types_id'], 'oa_log') ? : [];
				$where_log['log_id'] = ['in',$r_logs];			
				$sqlArr[] = Db::table('__OA_LOG__')
	                ->where($where_log)
	                ->field(['log_id as id,create_time,create_user_id,2 as cate,content'])
	                ->buildSql();

				$where_examine = [];
				$r_logs = $this->getRelationIdsByType($map['types'], $map['types_id'], 'oa_examine') ? : [];
				$where_examine['examine_id'] = ['in',$r_logs];		            
				$sqlArr[] = Db::table('__OA_EXAMINE__')
	                ->where($where_examine)
	                ->field(['examine_id as id,create_time,create_user_id,3 as cate,content'])
	                ->buildSql();

				$where_task = [];
				$r_logs = $this->getRelationIdsByType($map['types'], $map['types_id'], 'task') ? : [];
				$where_task['task_id'] = ['in',$r_logs];	                
				$sqlArr[] = Db::table('__TASK__')
	                ->where($where_task)
	                ->field(['task_id as id,create_time,create_user_id,4 as cate,name as content'])
	                ->buildSql();	            	            

				$where_record = [];
				$where_record['types'] = $map['types'];
				$where_record['types_id'] = $map['types_id'];
				//客户模块下包含被转化线索的跟进记录
				if ($map['types'] == 'crm_customer') {
					if ($leads_id = db('crm_leads')->where(['customer_id' => $map['types_id']])->value('leads_id')) {
						$whereOr = [];
						$whereOr['types'] = 'crm_leads';
						$whereOr['types_id'] = $leads_id;
					}
				}				                
	            $e = Db::table('__ADMIN_RECORD__')
	            	->alias('record')
	            	->where($where_record)            	
	            	->whereOr($whereOr)            	
	            	->field(['record_id as id,create_time,create_user_id,1 as cate,content'])
	            	->union($sqlArr)
	            	->buildSql();

	            $list = Db::table($e.' a')
		            ->page($request['page'], $request['limit'])
					->order('create_time desc')
					->select();
				$dataCount = Db::table($e.' a')->count();
				break;
		}
		$admin_user_ids = $userModel->getAdminId();
		foreach ($list as $k=>$v) {
			$create_user_info = isset($v['create_user_id']) ? $userModel->getUserById($v['create_user_id']) : [];
			$list[$k]['create_user_info'] = $create_user_info;
			$content = '';
			$fileList = [];
			$imgList = [];
			$where = [];
			$where['module_id'] = $v['id'];
			$relation_list = [];

			switch ($v['cate']) {
				case '1' : 
					$where['module'] = 'admin_record';
					$relation_list = $this->getListByRelationId('record', $v['id']);
					$dataInfo = [];
					break;
				case '2' : 
					$where['module'] = 'oa_log';
					$dataInfo = db('oa_log')->where(['log_id' => $v['id']])->find();
					$dataInfo['create_user_info'] = $create_user_info;
					$dataInfo['sendUserList'] = $userModel->getDataByStr($dataInfo['send_user_ids']) ? : [];
					$dataInfo['sendStructList'] = $structureModel->getDataByStr($dataInfo['send_structure_ids']) ? : [];
					$param['type_id'] = $dataInfo['log_id'];
					$param['type'] = 'oa_log';
					$dataInfo['replyList'] = $commonModel->read($param);

					$is_update = 0;
					$is_delete = 0;
					//3天内的日志可删,可修改
					if (($dataInfo['create_user_id'] == $user_id) && date('Ymd',$dataInfo['create_time']) > date('Ymd',(strtotime(date('Ymd',time()))-86400*3))) {
						$is_update = 1;
						$is_delete = 1;			
					}
					$permission['is_update'] = $is_update;
					$permission['is_delete'] = $is_delete;
					$dataInfo['permission']  = $permission;					

					$relation_list = $this->getListByRelationId('log', $v['id']);
					break;
				case '3' : 
					$where['module'] = 'oa_examine';
					$dataInfo = db('oa_examine')->where(['examine_id' => $v['id']])->find();
					$dataInfo['category_name'] = db('oa_examine_category')->where(['category_id' => $dataInfo['category_id']])->value('title');
					$dataInfo['create_user_info'] = $create_user_info;
		       		$causeCount = 0;
		       		$causeTitle = '';
		       		$duration = $dataInfo['duration'] ? : '0.0';
		       		$money = $dataInfo['money'] ? : '0.00';
		       		if (in_array($dataInfo['category_id'],['3','5'])) {
		       			$causeCount = db('oa_examine_travel')->where(['examine_id' => $dataInfo['examine_id']])->count() ? : 0;
		       			if ($dataInfo['category_id'] == 3) $causeTitle = $causeCount.'个行程,共'.$duration.'天';
		       			if ($dataInfo['category_id'] == 5) $causeTitle = $causeCount.'个报销事项,共'.$money.'元';
		       		}
		       		$dataInfo['causeTitle'] = $causeTitle;
		       		$dataInfo['causeCount'] = $causeCount ? : 0;

					//权限
					//创建人或管理员有撤销权限
					$permission = [];
					$is_recheck = 0;
					$is_update = 0;
					$is_delete = 0;
			        if (((int)$dataInfo['create_user_id'] == $user_id || !in_array($userr_id, $admin_user_ids)) && (!in_array($dataInfo['check_status'],['2','3']) || (empty($dataInfo['check_status']) && empty($dataInfo['check_user_id'])))) {
			            $is_recheck = 1;
			        }
			        //创建人（待审状态且无审批人时可编辑）
					if (($user_id == (int)$dataInfo['create_user_id']) && $dataInfo['check_status'] == 0 && empty($dataInfo['check_user_id'])) {
				        $is_update = 1;
						$is_delete = 1;
				    }
				    $permission['is_recheck'] = $is_recheck;	        
			        $permission['is_update'] = $is_update;
			        $permission['is_delete'] = $is_delete;
			        $dataInfo['permission']	= $permission;

					$relation_list = $this->getListByRelationId('examine', $v['id']);
					break;
				case '4' : 
					$where['module'] = 'work_task';
					$relation_list = $this->getListByRelationId('task', $v['id']);
					$dataInfo = db('task')->where(['task_id' => $v['id']])->find();
					$dataInfo['task_name'] = $dataInfo['name'];
					if ($dataInfo['pid'] > 0) {
						$p_det = Db::name('Task')->field('task_id,name')->where('task_id ='.$dataInfo['pid'])->find();
						$dataInfo['pname'] = $p_det['name'];
					} else {
						$dataInfo['pname'] = '';
					}
					$subcount = Db::name('Task')->where(' ishidden =0 and ( status=1 ) and  pid ='.$dataInfo['task_id'])->count();
					$subdonecount = Db::name('Task')->where(' ishidden = 0 and status = 5 and pid ='.$dataInfo['task_id'])->count();
					$dataInfo['subcount'] = $subcount; //子任务
					$dataInfo['subdonecount'] = $subdonecount; //已完成子任务
					$dataInfo['commentcount'] = Db::name('AdminComment')->where('type=1 and type_id ='.$dataInfo['task_id'])->count();
					$dataInfo['filecount'] = Db::name('WorkTaskFile')->where('task_id ='.$dataInfo['task_id'])->count();	
					if ($dataInfo['lable_id']) {
						$dataInfo['lableList'] = $lableModel->getDataByStr($dataInfo['lable_id']);
					}else{
						$dataInfo['lableList'] = array();
					}
					//参与人列表数组
					//$userlist =$userModel->getDataByStr($value['owner_user_id']);
					//$dataInfo['own_list'] = $userlist?$userlist: array(); 
					//负责人信息
					$dataInfo['main_user'] = $dataInfo['main_user_id'] ? $userModel->getUserById($dataInfo['main_user_id']) : array();
					$dataInfo['relationCount'] = $taskModel->getRelationCount($dataInfo['task_id']);					
					break;					
				case '5' : 
					$where['module'] = 'oa_event';
					$relation_list = $this->getListByRelationId('event', $v['id']);

					$dataInfo = db('oa_event')->where(['event_id' => $v['id']])->find();
					$dataInfo['create_user_info'] = $userModel->getUserById($dataInfo['create_user_id']);
					$dataInfo['ownerList'] = $userModel->getDataByStr($dataInfo['owner_user_ids']) ? : [];
					$dataInfo['remindtype'] = (int)$dataInfo['remindtype'];
					$noticeList = Db::name('OaEventNotice')->where('event_id = '.$dataInfo['event_id'])->find();
					if (!$noticeList) {
						$dataInfo['is_repeat'] = 0;
					} else {
						$dataInfo['is_repeat'] = 1;
					}
					$dataInfo['stop_time'] = $noticeList ? $noticeList['stop_time'] : '';
					$dataInfo['noticetype'] = $noticeList ? $noticeList['noticetype'] : '';
					if ($noticeList['noticetype'] == '2') {
						$dataInfo['repeat'] =  $noticeList['repeated'] ? explode('|||',$noticeList['repeated']) : [];
					} else {
						$dataInfo['repeat'] =  '';
					}
					break;					
				case '6' : 
					$where['module'] = 'work';
					$relation_list = $this->getListByRelationId('work', $v['id']);
					break;														
			}
			$newFileList = [];
			$newFileList = $fileModel->getDataList($where, 'all');
			if ($newFileList['list']) {
				foreach ($newFileList['list'] as $val) {
					if ($val['types'] == 'file') {
						$fileList[] = $val;
					} else {
						$imgList[] = $val;
					}
				}				
			}
			$dataInfo['fileList'] = $fileList ? : [];
			$dataInfo['imgList'] = $imgList ? : [];
			$dataInfo['customerList'] = $relation_list['customerList'] ? : [];
			$dataInfo['contactsList'] = $relation_list['contactsList'] ? : [];
			$dataInfo['businessList'] = $relation_list['businessList'] ? : [];
			$dataInfo['contractList'] = $relation_list['contractList'] ? : [];
			$list[$k]['dataInfo'] = $dataInfo ? : [];
		}
        $data = [];
        $data['list'] = $list ? : [];
        $data['dataCount'] = $dataCount ? : 0;
        return $data;
    }

	/**
	 * 创建跟进记录信息
	 * @author Michael_xu
	 * @param  
	 * @return                            
	 */	
	public function createData($param)
	{
		$eventModel = new \app\oa\model\Event();
		if (!$param['types'] || !$param['types_id'] || !in_array($param['types'], $this->types_arr)) {
			$this->error = '参数错误';
			return false;
		}
		//验证
		$validate = validate($this->name);
		if (!$validate->check($param)) {
			$this->error = $validate->getError();
			return false;
		}
		$param['business_ids'] = arrayToString($param['business_ids']);
		$param['contacts_ids'] = arrayToString($param['contacts_ids']);

		$fileArr = $param['file_id']; //接收表单附件
		unset($param['file_id']);
		if ($this->data($param)->allowField(true)->save()) {
			//下次联系时间
			$this->updateNexttime($param['types'], $param['types_id'], $param['next_time']);

			//处理附件关系
	        if ($fileArr) {
	            $fileModel = new \app\admin\model\File();
	            $resData = $fileModel->createDataById($fileArr, 'admin_record', $this->record_id);
				if ($resData == false) {
		        	$this->error = '附件上传失败';
		        	return false;
		        }
	        }

			$data = [];
			$data['record_id'] = $this->record_id;
			return $data;
		} else {
			$this->error = '添加失败';
			return false;
		}			
	}

	/**
	 * 根据主键获取详情
	 * @param  array   $param  [description]
	 */ 
	public function getDataById($id = '')
	{
		$map['record_id'] = $id;
		$dataInfo = db('admin_record')->where($map)->find();
		if (!$dataInfo) {
			$this->error = '暂无此数据';
			return false;
		}
		$userModel = new \app\admin\model\User();
		$dataInfo['create_user_info'] = $userModel->getUserById($dataInfo['create_user_id']);
		return $dataInfo;		
	}  

	/**
	 * 相关业务ids
	 * @param $types 相关业务
	 * @param $types_id  相关业务ID
	 * @param $relation  相关模块
	 */ 	
	public function getRelationIdsByType($types, $types_id, $relation)
	{
		$rIds = [];
		switch ($relation) {
			case 'oa_log' : $dbName = db('oa_log_relation'); $relationId = 'log_id'; break; //相关日志
			case 'oa_event' : $dbName = db('oa_event_relation'); $relationId = 'event_id'; break; //相关日程
			case 'task' : $dbName = db('task_relation'); $relationId = 'task_id'; break; //相关任务
			case 'task_work' : $dbName = db('work_relation'); $relationId = 'work_id'; break; //相关项目
			case 'oa_examine' : $dbName = db('oa_examine_relation'); $relationId = 'examine_id'; break; //相关审批
			default : return []; break;
		}
		switch ($types) {
			case 'crm_customer' : $rIds = $dbName->where(['customer_ids' => ['like', '%,'.$types_id.',%']])->column($relationId); break;
			case 'crm_contacts' : $rIds = $dbName->where(['contacts_ids' => ['like', '%,'.$types_id.',%']])->column($relationId); break;
			case 'crm_business' : $rIds = $dbName->where(['business_ids' => ['like', '%,'.$types_id.',%']])->column($relationId); break;
			case 'crm_contract' : $rIds = $dbName->where(['contract_ids' => ['like', '%,'.$types_id.',%']])->column($relationId); break;
		}
		return $rIds ? : [];
	}	

	/**
	 * 相关业务list
	 * @param $types 相关业务
	 * @param $types_id  相关业务ID
	 * @param $relation  相关模块
	 */ 
	public function getListByRelationId($relation, $relation_id)
	{

		$BusinessModel = new \app\crm\model\Business();
		$ContactsModel = new \app\crm\model\Contacts();
		$ContractModel = new \app\crm\model\Contract();
		$CustomerModel = new \app\crm\model\Customer();	

		$data = [];
		switch ($relation) {
			case 'log' : $data = db('oa_log_relation')->where(['log_id' => $relation_id])->find(); break;
			case 'event' : $data = db('oa_event_relation')->where(['event_id' => $relation_id])->find(); break;
			case 'task' : $data = db('task_relation')->where(['task_id' => $relation_id])->find(); break;
			case 'work' : $data = db('work_relation')->where(['work_id' => $relation_id])->find(); break;
			case 'examine' : $data = db('oa_examine_relation')->where(['examine_id' => $relation_id])->find(); break;
			case 'record' : $data = db('admin_record')->where(['record_id' => $relation_id])->find(); break;
			default : $data = []; break;
		}
		$data['customerList'] = $data['customer_ids'] ? $CustomerModel->getDataByStr($data['customer_ids']) : [];
		$data['contactsList'] = $data['contacts_ids'] ? $ContactsModel->getDataByStr($data['contacts_ids']) : [];
		$data['businessList'] = $data['business_ids'] ? $BusinessModel->getDataByStr($data['business_ids']) : [];
		$data['contractList'] = $data['contract_ids'] ? $ContractModel->getDataByStr($data['contract_ids']) : [];
		return $data ? : [];
	}

	/**
	 * 相关模块下次联系时间
	 * @param types 类型
	 * @param types 类型ID
	 * @param next_time 下次联系时间
	 */ 
	public function updateNexttime($types, $types_id, $next_time)
	{
		switch ($types) {
			case 'crm_customer' : $dbName = db('crm_customer'); $dbId = 'customer_id'; break;
			case 'crm_leads' : $dbName = db('crm_leads'); $dbId = 'leads_id'; break;
			case 'crm_contacts' : $dbName = db('crm_contacts'); $dbId = 'contacts_id'; break;
			case 'crm_business' : $dbName = db('crm_business'); $dbId = 'business_id'; break;
			default : break;
		}
		if (!$dbName || !$dbId) {
			return true;
		}
		$data = [];
		if ($next_time) {
	      	$data['next_time'] = $next_time;
	    } else {
			// 如果未填写下次联系时间，并且 原下次联系时间为当天，则把下次联系时间置空
			$next_time = $dbName->where([$dbId => $types_id])->value('next_time');
			list($start, $end) = getTimeByType();
			if ($next_time >= $start && $next_time <= $end) {
				$data['next_time'] = 0;
			}
	    }
		$data['update_time'] = time();
		if (in_array($types,['crm_customer','crm_leads'])) $data['follow'] = '已跟进';
		$dbName->where([$dbId => $types_id])->update($data);
		return true;
	}

	/**
	 * 跟进记录删除
	 * @param types 类型
	 * @param types 类型ID数组
	 * @param 
	 */ 
	public function delDataByTypes($types, $types_id)
	{
		if (!is_array($types_id)) {
			$types_id[] = $types_id;
		}
		$fileModel = new \app\admin\model\File();
		$record_ids = $this->where(['types' => $types,'types_id' => ['in',$types_id]])->column('record_id');
		$this->where(['types' => $types,'types_id' => ['in',$types_id]])->delete();
		//删除关联附件
        $fileModel->delRFileByModule('admin_record',$record_ids);
		return true;
	}	

    /**
     * 查询最近更进记录
     *
     * @param string $types 关联类型
     * @param array $types_id_list 类型ID
     * @return array
     * @author Ymob
     * @datetime 2019-12-11 10:43:04
     */
    public static function getLastRecord($types, $types_id_list)
    {
		$prefix = config('database.prefix');
		$types_ids = implode(',', $types_id_list) ?: '-1';
        $list = self::field(['types_id', 'content'])
        ->where("
            `record_id` IN (
				SELECT
					MAX(`record_id`)
				FROM
					`{$prefix}admin_record`
				WHERE
					`types` = '{$types}'
					AND `types_id` IN ({$types_ids})
				GROUP BY
					`types_id`
            )
		")
		->select();
		$res = [];
		foreach ($list as $val) {
			$res[$val['types_id']] = $val['content'];
		}
		return $res;
    }
}
