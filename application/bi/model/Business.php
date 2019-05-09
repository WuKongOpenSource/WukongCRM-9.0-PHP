<?php
// +----------------------------------------------------------------------
// | Description: 商机
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com
// +----------------------------------------------------------------------
namespace app\bi\model;

use think\Db;
use app\admin\model\Common;
use think\Request;
use think\Validate;

class Business extends Common
{
	/**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如CRM模块用crm作为数据表前缀
     */
	protected $name = 'crm_business';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
	protected $autoWriteTimestamp = true;

	/**
     * [getDataCount 商机count]
     * @author Michael_xu
     * @param     [string]                   $map [查询条件]
     * @param     [number]                   $page     [当前页数]
     * @param     [number]                   $limit    [每页数量]
     * @return    [array]                    [description]
     */		
	function getDataCount($whereArr)
    {
    	$where = [];
        $dataCount = $this->where($whereArr)->where($where)->count('business_id');
        $count = $dataCount ? : 0;
        return $count;		
    }  	
    /**
     * [getDataMoney 商机金额]
     * @author Michael_xu
     * @param     [string]                   $map [查询条件]
     * @param     [number]                   $page     [当前页数]
     * @param     [number]                   $limit    [每页数量]
     * @return    [array]                    [description]
     */		
	function getDataMoney($whereArr)
    {
    	$where = [];
        $money = $this->where($whereArr)->where($where)->sum('money');
        return $money;		
    }  	
    /**
     * 获取商机list
     * @return [type] [description]
     */
    function getDataList($request)
    {
    	$userModel = new \app\admin\model\User();
        $request = $this->fmtRequest( $request );
        $map = $request['map'] ? : [];
        //员工IDS
        $map_user_ids = [];
        if ($map['user_id']) {
            $map_user_ids = array($map['user_id']);
        } else {
            if ($map['structure_id']) {
                $map_user_ids = $userModel->getSubUserByStr($map['structure_id'], 2);
            }
        }
        $perUserIds = $userModel->getUserByPer('bi', 'customer', 'read'); //权限范围内userIds
        $userIds = $map_user_ids ? array_intersect($map_user_ids, $perUserIds) : $perUserIds; //数组交集
        $where['owner_user_id'] = array('in',$userIds);
        
        $start_time = $map['start_time'];
        $end_time = $map['end_time'];
        if ($start_time && $end_time) {
            $create_time = array('between',array($start_time,$end_time));
        }
        $where['create_time'] = $create_time;
        $dataList = db('crm_business')->field('business_id,customer_id,money,type_id,status_id,deal_date,create_user_id,owner_user_id')->where($where)->select();
        return $dataList;	
   }
}