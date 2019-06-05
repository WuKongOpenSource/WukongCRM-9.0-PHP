<?php
// +----------------------------------------------------------------------
// | Description: 客户
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com
// +----------------------------------------------------------------------
namespace app\bi\model;

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
     * [跟进统计]
     * @author Michael_xu
     * @param
     * @return                  
     */ 
    function getDataList($request){
        $userModel = new \app\admin\model\User();
        
        //员工IDS
        $map_user_ids = [];
        if ($request['user_id']) {
            $map_user_ids = array($request['user_id']);
        } else {
            if ($request['structure_id']) {
                $map_user_ids = $userModel->getSubUserByStr($request['structure_id'], 2);
            }
        }
        $perUserIds = $userModel->getUserByPer('bi', 'customer', 'read'); //权限范围内userIds
        $userIds = $map_user_ids ? array_intersect($map_user_ids, $perUserIds) : $perUserIds; //数组交集
        $where['id'] = array('in',$userIds);
        $where['type'] = 1;
        $userList = db('admin_user')->where($where)->field('id,username,realname')->select();
        foreach ($userList as $k=>$v) {
            $whereArr = [];
            $customer_num = 0; //跟进客户数
            $record_num = 0; //跟进次数
            $whereArr['create_user_id'] = $v['id'];

            $start_time = $request['start_time'];
            $end_time = $request['end_time'];

            if ($start_time && $end_time) {
                $create_time = array('between',array($start_time,$end_time));
            }
            $whereArr['create_time'] = $create_time;
            $userList[$k]['customer_num'] = $customer_num = $this->getCustomerNum($whereArr);
            $userList[$k]['record_num'] = $record_num = $this->getRecordNum($whereArr);
        }
        return $userList ? : [];
    }
    /**
     * 根据条件获取跟进客户数
     * @param  [type] $type [description]
     * @param  [type] $year [description]
     * @param  [type] $i    [description]
     * @return [type]       [description]
     */
    function getCustomerNum($whereArr){
        $dataCount = db('admin_record')->where($whereArr)->group('types_id')->count();
        return $dataCount;
    }
    /**
     * [根据条件获取跟进次数]
     * @author Michael_xu
     * @param
     * @return                  
     */ 
    function getRecordNum($whereArr){
        $dataCount = db('admin_record')->where($whereArr)->count();
        return $dataCount;
    }
    /**
     * 跟进次数排行
     * @param  [type] $whereArr [description]
     * @return [type]           [description]
     */
    function getSortByCount($whereArr)
    {
        $count = db('admin_record')->group('create_user_id')->field('create_user_id,count(record_id) as count')->order('count desc')->where($whereArr)->select();
        return $count;
    }
    /**
     * 跟进客户排行
     * @param  [type] $whereArr [description]
     * @return [type]           [description]
     */
    function getSortByCustomer($whereArr)
    {
        $list = db('admin_record')->group('create_user_id')->field('create_user_id')->where($whereArr)->select();
        foreach ($list as $key => $value) {
            $where = array();
            $where['create_user_id'] = array('eq',$value['create_user_id']);
            $list[$key]['count'] = count(db('admin_record')->group('types_id')->field('count(types_id) as count')->order('count desc')->where($where)->select());
        }
        return sort_select($list,'count');
    }
}