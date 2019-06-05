<?php
// +----------------------------------------------------------------------
// | Description: 办公消息模块
// +----------------------------------------------------------------------
// | Author: Michael_xu | gengxiaoxu@5kcrm.com 
// +----------------------------------------------------------------------

namespace app\oa\controller;

use app\admin\controller\ApiCommon;
use think\Hook;
use think\Request;
use think\Db;

class Message extends ApiCommon
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
            'allow'=>['num']            
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());        
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
    } 

    /**
     * 消息数
     * @author Michael_xu
     * @return 
     */
    public function num()
    {
    	$param = $this->param;
        $userInfo = $this->userInfo;
        $user_id = $userInfo['id'];
        $structure_id = $userInfo['structure_id'];
        $type = $param['type'] ? : 'all';
        $todayTime = getTimeByType('today');
        $start_time = $todayTime[0];
        $end_time = $todayTime[1];

        $eventNum = 0; //日程数
        $taskNum = 0; //任务数
        $announcementNum = 0; //公告数
        $logNum = 0; //日志数
        $examineNum = 0; //审批数
        $data = [];
        //日程
        if ($type == 'event' || $type == 'all') {
            $eventWhere = '( ( start_time BETWEEN '.$start_time.' AND '.$end_time.' ) AND ( create_user_id = '.$user_id.' or owner_user_ids like "%,'.$user_id.',%" ) ) OR ( ( end_time BETWEEN '.$start_time.' AND '.$end_time.' ) AND  ( create_user_id = '.$user_id.' or owner_user_ids like "%,'.$user_id.',%" ) ) OR ( start_time < '.$start_time.' AND end_time > '.$end_time.' AND ( create_user_id = '.$user_id.' or owner_user_ids like "%,'.$user_id.',%" ) )';
            $eventNum = db('oa_event')->where($eventWhere)->count();
            $data['eventNum'] = $eventNum ? : 0;
        }
        //任务（我负责的和我参与的未完成的任务提醒）
        if ($type == 'task' || $type == 'all') {
            $taskWhere = [];
            $str = ','.$userInfo['id'].',';
            $task = 'main_user_id ='.$userInfo['id'].' or create_user_id ='.$user_id.' or ( is_open = 1 and owner_user_id like "%'.$str.'%")';
            $taskWhere['pid'] = 0;
            $taskWhere['status'] = array('neq',5);
            $taskNum = db('task')->where(' ishidden=0 and ( '.$task.' )')->where($taskWhere)->count();
            $data['taskNum'] = $taskNum ? : 0;
        }
        //公告（未读公告）      
        if ($type == 'announcement' || $type == 'all') {
            $time = strtotime(date('Y-m-d',time()));
            $announcementWhere['start_time'] = array('elt',$time);
            $announcementWhere['end_time'] = array('egt',$time);
            $announcementWhere['read_user_ids'] = array('not like','%,'.$user_id.',%');
            $announcementNum = db('oa_announcement')->where(' ( owner_user_ids LIKE "%,'.$userInfo['id'].',%" OR structure_ids LIKE "%,'.$userInfo['structure_id'].',%" OR create_user_id = '.$user_id.' OR (owner_user_ids = "" AND structure_ids = ""))')->where($announcementWhere)->count();
            $data['announcementNum'] = $announcementNum ? : 0;
        }
        //日志（发送给自己并未读）
        if ($type == 'log' || $type == 'all') {
            $dataWhere['user_id'] = $user_id;
            $dataWhere['structure_id'] = $structure_id;
            $logMap = function($query) use ($dataWhere){
                    $query->where('send_user_ids',array('like','%,'.$dataWhere['user_id'].',%'))
                        ->whereOr('send_structure_ids',array('like','%,'.$dataWhere['structure_id'].',%'));
            };
            $logWhere['read_user_ids'] = ['not like','%,'.$user_id.',%']; 
            $logNum = db('oa_log')->where($logWhere)->where($logMap)->count();
            $data['logNum'] = $logNum ? : 0;
        }    
        //审批
        if ($type == 'examine' || $type == 'all') {
            // $examineWhere['check_status'] = array('not in',array('2','3'));
            $map_str = "( `check_user_id` LIKE '%,".$user_id.",%' OR `check_user_id` = ".$user_id." )";
            $examineNum = db('oa_examine')->where($map_str)->where($examineWhere)->count();
            $data['examineNum'] = $examineNum ? : 0;
        }
        return resultArray(['data'=>$data]);	
    }          
}