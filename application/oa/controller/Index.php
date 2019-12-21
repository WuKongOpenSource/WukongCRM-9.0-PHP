<?php
// +----------------------------------------------------------------------
// | Description: OA工作台
// +----------------------------------------------------------------------
// | Author: Michael_xu | gengxiaoxu@5kcrm.com 
// +----------------------------------------------------------------------

namespace app\oa\controller;

use app\admin\controller\ApiCommon;
use think\Hook;
use think\Request;
use think\Db;

class Index extends ApiCommon
{
	/**
     * 用于判断权限
     * @permission 无限制
     * @allow 登录用户可访问
     * @other 其他根据系统设置
    **/    
    public function _initialize()
    {
        $action = [
            'permission'=>[''],
            'allow'=>['index','tasklist','eventlist','event']            
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());        
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }  
    } 

	/**
     * 工作圈
     * @author
     * @return 
     */    
    public function index()
    {
        $param = $this->param;
		$userInfo = $this->userInfo;
		$userModel = new \app\admin\model\User();
		$structureModel = new \app\admin\model\Structure();
		$fileModel = new \app\admin\model\File();
		$commonModel = new \app\admin\model\Comment();
		$BusinessModel = new \app\crm\model\Business();
		$ContactsModel = new \app\crm\model\Contacts();
		$ContractModel = new \app\crm\model\Contract();
		$CustomerModel = new \app\crm\model\Customer();

		if ($param['type'] == 1) { //日志
			$where = ' controller_name = "log" and  module_name = "oa" ';
		} elseif ($param['type'] == 2) { //日程
			$where = ' controller_name = "event" and  module_name = "oa" ';
		} elseif ($param['type'] == 3) { //公告
			$where = ' controller_name = "announcement" and  module_name = "oa" ';
		} elseif ($param['type'] == 4) { //任务
			$where = ' ( controller_name = "task" and  module_name = "oa" ) ';
		} elseif ($param['type'] == 5) { //审批
			$where = ' ( controller_name = "examine" and  module_name = "oa" ) ';			
		} else { //全部
			$where = ' ( module_name = "oa" ) ';
		}
		$limit = $param['limit'] ? : '15' ;
		$page = $param['page'] ? : '1' ;
		//获取权限范围内的员工
        $auth_user_ids = getSubUserId();
        $auth_user_ids = implode(',',$auth_user_ids);
		
		$actionList = Db::name('AdminActionLog')
			->where( $where.' and ( action_delete != 1 ) and ( user_id IN ('.$auth_user_ids.') or join_user_ids like "%,'.$userInfo['id'].',%" or structure_ids like "%,'.$userInfo['structure_id'].',%" )')
			->page($page, $limit)
			->order('create_time desc')
			->select();
		$actionCount = Db::name('AdminActionLog')
			->where( $where.' and ( action_delete != 1 ) and ( user_id IN ('.$auth_user_ids.') or join_user_ids like "%,'.$userInfo['id'].',%" or structure_ids like "%,'.$userInfo['structure_id'].',%" )')
			->count();
		foreach ($actionList as $key=>$value) {
			$actionList[$key]['create_user_info'] = $userModel->getUserById($value['user_id']);
			$actionList[$key]['action_content'] = $value['content'] ? : '';
			if ($value['controller_name'] == 'log') {
				$logInfo = [];
				$logInfo = Db::name('OaLog')->where('log_id = '.$value['action_id'].'')->find();		
				if ($logInfo) {
					$actionList[$key]['title'] = $logInfo['title'];
					$actionList[$key]['today'] = $logInfo['today'];
					$actionList[$key]['category_id'] = $logInfo['category_id'];
					$actionList[$key]['content'] = $logInfo['content'];
					$actionList[$key]['tomorrow'] = $logInfo['tomorrow'];
					$actionList[$key]['question'] = $logInfo['question'];
					//附件、图片
					$fileList = [];
					$imgList = [];
					$where = [];
					$where['module'] = 'oa_log';
					$where['module_id'] = $value['action_id'];			
					$newFileList = [];
					$newFileList = $fileModel->getDataList($where);
					foreach ($newFileList['list'] as $val) {
						if ($val['types'] == 'file') {
							$fileList[] = $val;
						} else {
							$imgList[] = $val;
						}
					}
					$actionList[$key]['fileList'] = $fileList ? : [];
					$actionList[$key]['imgList'] = $imgList ? : [];	
					//参与人 
					$actionList[$key]['sendUserList'] = $userModel->getDataByStr($logInfo['send_user_ids']) ? : [];
					//参与部门
					$actionList[$key]['sendStructList'] = $structureModel->getDataByStr($logInfo['send_structure_ids']) ? : [];	
					//评论
					$commonParam = [];
					$commonParam['type_id'] = $logInfo['log_id'];
					$commonParam['type'] = 'oa_log';
					$actionList[$key]['replyList'] = $commonModel->read($commonParam);
					$actionList[$key]['type'] = 1;
					$actionList[$key]['type_name'] = "日志";														
				} else {
					unset($actionList[$key]);
				}
			} elseif ($value['controller_name'] == 'event') { 
				//日程
				$eventInfo = Db::name('OaEvent')->field('event_id,title,remark,start_time,end_time,owner_user_ids')->where('event_id = '.$value['action_id'].'')->find();
				if ($eventInfo) {
					$actionList[$key]['title'] = $eventInfo['title'] ? : '';
					$actionList[$key]['remark'] = $eventInfo['remark'] ? : '';
					$actionList[$key]['start_time'] = $eventInfo['start_time'];
					$actionList[$key]['end_time'] = $eventInfo['end_time'];
					$actionList[$key]['ownerList'] =  $userModel->getDataByStr($eventInfo['owner_user_ids']);
					$actionList[$key]['type'] = 2;
					$actionList[$key]['type_name'] = "日程";
					$relation = Db::name('OaEventRelation')->where('event_id ='.$value['action_id'])->find();
					$actionList[$key]['businessList'] = $relation['business_ids'] ? $BusinessModel->getDataByStr($relation['business_ids']) : []; //商机
					$actionList[$key]['contactsList'] = $relation['contacts_ids'] ? $ContactsModel->getDataByStr($relation['contacts_ids']) : []; //联系人
					$actionList[$key]['contractList'] = $relation['contract_ids'] ? $ContractModel->getDataByStr($relation['contract_ids']) : []; //合同
					$actionList[$key]['customerList'] = $relation['customer_ids'] ? $CustomerModel->getDataByStr($relation['customer_ids']) : []; //客户
				} else {
					unset($actionList[$key]);
				}
			} elseif ($value['controller_name'] == 'announcement') { 
				//公告
				$announcementInfo = Db::name('OaAnnouncement')->field('announcement_id,title,content')->where('announcement_id = '.$value['action_id'].'')->find();
				if ($announcementInfo) {
					$actionList[$key]['title'] = $announcementInfo['title'] ? : '';
					$actionList[$key]['ann_content'] = $announcementInfo['content'] ? : '';
					$actionList[$key]['type'] = 3;
					$actionList[$key]['type_name'] = "公告";					
				} else {
					unset($actionList[$key]);
				}
			} elseif ($value['controller_name'] == 'task') { 
				//任务
				$taskInfo = Db::name('Task')->field('task_id,name')->where('ishidden =0 and task_id = '.$value['action_id'].'')->find();
				if (!$taskInfo || $taskInfo['pid']) {
					unset($actionList[$key]);
				} else {
					$actionList[$key]['pname'] = '';
					$actionList[$key]['title'] = $taskInfo['name'] ? : '查看详情';
					$actionList[$key]['type'] = 4;
					$actionList[$key]['type_name'] = "任务";					
				}
			} elseif ($value['controller_name'] == 'examine') {
				$examineInfo = db('oa_examine')->where(['examine_id' => $value['action_id']])->find();
				if ($examineInfo) {
					$actionList[$key]['title'] = $examineInfo['content'] ? : '查看详情';
					$actionList[$key]['type'] = 5;
					$actionList[$key]['type_name'] = "审批";						
				} else {
					unset($actionList[$key]);
				}
			}
		}
		$actionList = $actionList ? array_merge($actionList) : [];
		$data = [];
		$data['list'] = $actionList ? : [];
		$data['dataCount'] = $actionCount ? : 0;
		return resultArray(['data'=>$data]);	
    }
	
	/**
     * 任务展示
     * @author
     * @return 
     */ 
	public function taskList()
	{
		$userInfo = $this->userInfo;
		$count = Db::name('Task')->where('( main_user_id = '.$userInfo['id'].' or create_user_id ='.$userInfo['id'].' or owner_user_id like "%,'.$userInfo['id'].',%") and status=1 and ishidden=0')->count();
		$list = Db::name('Task')->field('task_id,name,stop_time,priority,create_time,pid')->where(' ( main_user_id = '.$userInfo['id'].' or create_user_id ='.$userInfo['id'].' or owner_user_id like "%,'.$userInfo['id'].',%") and status=1 and ishidden=0')->select();
		foreach ($list as $k=>$v) {
			if ($v['pid']) {
				$ptask = Db::name('Task')->field('task_id,name')->where('ishidden = 0 and task_id ='.$v['pid'].'')->find();
				if($ptask){
					$list[$k]['pname'] = $ptask['name'];
				} else {
					$list[$k]['pname'] = '';
				}
			} else {
				$list[$k]['pname'] = '';
			}
			if ($v['stop_time']) {
				if ($v['stop_time'] < time()) {
					$list[$k]['task_status'] = '2';
					$list[$k]['task_remark'] = '已逾期';
				} else {
					$list[$k]['task_status'] = '1';
					$list[$k]['task_remark'] = '进行中';
				}
			} else {
				$list[$k]['task_status'] = '0';
				$list[$k]['task_remark'] = '未设置截至时间';
			}	
		}
		$data['count'] = $count;
		$data['list'] = $list;
		return resultArray(['data'=>$data]);
	}
	
	/**
     * 日程展示
     * @author
     * @return 
     */
	public function eventList()
	{
		$param = $this->param;
		$userInfo = $this->userInfo;
		if($param['start_time'] && $param['end_time']) {
			$start_time = $param['start_time'];
			$end_time = $param['end_time'];
		} else {
			$start_time = mktime(0,0,0,date('m'),1,date('Y'));
			$end_time = mktime(23,59,59,date('m'),date('t'),date('Y'));
		}
		$where = '( ( start_time BETWEEN '.$start_time.' AND '.$end_time.' ) AND ( create_user_id = '.$userInfo['id'].' or owner_user_ids like "%,'.$userInfo['id'].',%" ) ) OR ( ( end_time BETWEEN '.$start_time.' AND '.$end_time.' ) AND  ( create_user_id = '.$userInfo['id'].' or owner_user_ids like "%,'.$userInfo['id'].',%" ) ) OR ( start_time < '.$start_time.' AND end_time > '.$end_time.' AND ( create_user_id = '.$userInfo['id'].' or owner_user_ids like "%,'.$userInfo['id'].',%" ) )';
        $event_date = Db::name('OaEvent')->where($where)->select();  
        //生成从开始日期到结束日期的日期数组
        $date_array = dateList($start_time,$end_time,2);
		$temp = array();
		foreach($date_array as $k1=>$v1){
			$temp[$k1]['date'] = date('Y-m-d',$v1['sdate']);
			$temp[$k1]['status'] = 0;
		}
        //获取该月日程日期数组
		$event_arr = array();
		foreach ($event_date as $val) {
			$date_arr = array();
			$date_arr = dateList($val['start_time'],$val['end_time'],2);
			if ($date_arr) {
				foreach ($date_arr as $k=>$v) {
					foreach($temp as $k2=>$v2){
						if( $temp[$k2]['date'] == date('Y-m-d',$v['sdate']) ){
							$temp[$k2]['status'] = 1;
						}
					}
				}
			} else {
				foreach ($temp as $k2=>$v2) {
					if (date('Y-m-d',$val['start_time'])) {
						if ($temp[$k2]['date'] == date('Y-m-d',$val['start_time'])) {
							$temp[$k2]['status'] = 1;
						}
					} elseif (date('Y-m-d',$val['end_time'] )) {
						if ($temp[$k2]['date'] == date('Y-m-d',$val['end_time'])) {
							$temp[$k2]['status'] = 1;
						}
					}
				}
			}
		}
		return resultArray(['data'=>$temp]);
	}
	
	/**
     * 日程详情
     * @author
     * @return 
     */
	public function event()
	{
		$param = $this->param;
		$userInfo = $this->userInfo;
		$userModel = new \app\admin\model\User();
		if ($param['start_time']){
			$where['start_time'] = ['<=',$param['start_time']+3600*24 ];
			$where['end_time'] = ['>=',$param['start_time']];
			$eventList = Db::name('OaEvent')
				->where($where)
				->where(function($query) use($userInfo){
					$query->where(['owner_user_ids' => ['like','%,'.$userInfo['id'].',%']])
					->whereOr(['create_user_id' => $userInfo['id']]);
				})->select(); 
			if (count($eventList)) {
				foreach ($eventList as $k=>$v){
					$eventList[$k]['ownList']= $userModel->getDataByStr($v['owner_user_ids']);
				}
			}
			return resultArray(['data' => $eventList]);
		} else {
			return resultArray(['error'=>'参数错误']);
		}
	}
}