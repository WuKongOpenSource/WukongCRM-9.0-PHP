<?php
// +----------------------------------------------------------------------
// | Description: 日志统计
// +----------------------------------------------------------------------
// | Author:  zhi | zhijunfu@5kcrm.com
// +----------------------------------------------------------------------
namespace app\bi\model;

use think\Db;
use app\admin\model\Common;
use think\Request;

class Log extends Common
{
	/**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如CRM模块用crm作为数据表前缀
     */
	protected $name = 'oa_log';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    /**
     * [getDataList 日志统计]
     * @author zhi
     * @param 
     * @return 
     */
    public function getStatistics($param)
    {
        $userModel = new \app\admin\model\User();
        $commentModel = new \app\admin\model\Comment();
        $where = [];
        
        //员工IDS
        $map_user_ids = [];
        if ($param['user_id']) {
            $map_user_ids = array($param['user_id']);
        } else {
            if ($param['structure_id']) {
                $map_user_ids = $userModel->getSubUserByStr($param['structure_id'], 2);
            }
        }
        $perUserIds = $userModel->getUserByPer('bi', 'oa', 'read'); //权限范围内userIds
        $userIds = $map_user_ids ? array_intersect($map_user_ids, $perUserIds) : $perUserIds; //数组交集    
        //时间
        $start_time = $param['start_time'] ? : strtotime(date('Y-m-d',time()));
        $end_time = $param['end_time'] ? : strtotime(date('Y-m-d',time()))+86399;
        $create_time = array('between',array($start_time,$end_time));

        $where['id'] = array('in',$userIds);
        $where['type'] = 1;
        $userList = db('admin_user')->where($where)->field('id,username,thumb_img,realname')->select();
        foreach ($userList as $k=>$v) {
            $userList[$k]['thumb_img'] = $v['thumb_img'] ? getFullPath($v['thumb_img']) : '';
            $log_list = [];
            $count = 0; //填写数
            $unReadCont = 0; //接收人未读数
            $unCommentCount = 0; //未评论数
            $commentCount = 0; //已评论数
            $log_list = $this->where(['create_time' => $create_time,'create_user_id' => $v['id']])->field('send_user_ids,read_user_ids,log_id')->select();
            $count = count($log_list);
            if ($log_list) {
                foreach ($log_list as $key=>$val) {
                    if (stringToArray($val['send_user_ids']) && !array_intersect(stringToArray($val['send_user_ids']),stringToArray($val['read_user_ids']))) {
                        $unReadCont += 1;
                    }
                    $commentInfo = 0;
                    $commentInfo = $commentModel->getCount('oa_log',$val['log_id']);
                    if ($commentInfo > 0) {
                        $commentCount += 1;
                    } else {
                        $unCommentCount += 1;
                    }
                }                
            }
            $userList[$k]['count'] = $count;
            $userList[$k]['unReadCont'] = $unReadCont;
            $userList[$k]['unCommentCount'] = $unCommentCount;
            $userList[$k]['commentCount'] = $commentCount;            
        }
        return $userList ? : [];
    }
}