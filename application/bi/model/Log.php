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
use app\admin\model\User as UserModel;


class Log extends Common
{
	/**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如CRM模块用crm作为数据表前缀
     */
	protected $name = 'oa_log';

    /**
     * [getDataList 日志统计]
     * @author Michael_xu
     * @param 
     * @return 
     */
    public function getStatistics($param)
    {     
        //时间
        $start_time = $param['start_time'] ? : strtotime(date('Y-m-d',time()));
        $end_time = $param['end_time'] ? : strtotime(date('Y-m-d',time()))+86399;
        $create_time = array('between',array($start_time,$end_time));

        $userList = UserModel::where('type',1)
            ->field(['id','username','realname'])
            ->select();
        foreach ($userList as $k=>$v) {
            $log_list = [];
            $count = 0; //填写数
            $unReadCont = 0; //接收人未读数
            $unCommentCount = 0; //未评论数
            $commentCount = 0; //已评论数
            $log_list = $this->where(['create_time' => $create_time,'create_user_id' => $v['id']])->field('send_user_ids,read_user_ids,log_id')->select();
            $count = count($log_list);
            if ($log_list) {
                //获取评论过的日志id集合
                $w_c['ac.type'] = 'oa_log';
                $log_ids = $this->alias('l')
                    ->join('AdminComment ac', 'ac.type_id = l.log_id', 'LEFT')
                    ->where($w_c)
                    ->group('l.log_id')
                    ->column('log_id');
                foreach ($log_list as $key=>$val) {
                    if (stringToArray($val['send_user_ids']) && !array_intersect(stringToArray($val['send_user_ids']),stringToArray($val['read_user_ids']))) {
                        $unReadCont += 1;
                    }
                    //判断日志id是否在评论过的id集合内
                    if (in_array($val['log_id'], $log_ids) ) {
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