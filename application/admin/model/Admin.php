<?php
// +----------------------------------------------------------------------
// | Description: 系统基础公共
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com
// +----------------------------------------------------------------------

namespace app\admin\model;

use app\admin\model\Common;
use think\Db;

class Admin extends Common 
{
	/**
     * 统计筛选条件
     * @author Michael_xu
     * @param  $merge 1 user,structure 合并查询，0 user_id 优先级高
     * @param  $perUserIds 权限范围
     * @return 
     */
    public function getWhere($param, $merge = '', $perUserIds = [])
    {
        $userModel = new \app\admin\model\User();
        //员工IDS
        $user_ids = [];      
        if ($param['user_id']) {
            $user_ids = is_array($param['user_id']) ? $param['user_id'] : array($param['user_id']);
        } 
		if ($merge == 1) {
			if ($param['structure_id']) {
	            $str_user_ids = $userModel->getSubUserByStr($param['structure_id'], 2);
	        }
	        //合并
	        if ($user_ids && $str_user_ids) {
	        	$user_ids = array_unique(array_merge($user_ids,$str_user_ids));
	        } elseif ($str_user_ids) {
	        	$user_ids = $str_user_ids;
	        }        		
    	} else {
    		if (!$user_ids) {
				if ($param['structure_id']) {
		            $user_ids = $userModel->getSubUserByStr($param['structure_id'], 2);
		        }    			
    		}
    	}
        if (!$user_ids) $user_ids = getSubUserId(true);
        $perUserIds = $perUserIds ? : getSubUserId(); //权限范围内userIds
        $userIds = [];
        if ($user_ids) {
            $userIds = $perUserIds ? array_intersect($user_ids, $perUserIds) : $perUserIds; //数组交集
        }
        $where['userIds'] = array_map('intval', $userIds);      
        if (!empty($param['type'])) {
            $between_time = getTimeByType($param['type']);
        } else {
            //自定义时间
            if (!empty($param['start_time'])) {
                $between_time = array($param['start_time'],$param['end_time']);
            }
        }
        $where['between_time'] = $between_time;
        return $where ? : [];      
    }   		
}