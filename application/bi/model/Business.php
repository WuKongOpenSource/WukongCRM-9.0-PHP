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

class Business extends Common
{
	/**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如CRM模块用crm作为数据表前缀
     */
	protected $name = 'crm_business';

	/**
     * [getDataCount 商机count]
     * @author Michael_xu
     * @param
     * @return
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
     * @param
     * @return
     */		
	function getDataMoney($whereArr)
    {
    	$where = [];
        $money = $this->where($whereArr)->where($where)->sum('money');
        return $money;		
    }

    /**
     * 获取商机list
     * @author zhi
     * @param 
     * @return
     */
    function getDataList($param)
    {
    	$userModel = new \app\admin\model\User();
        $adminModel = new \app\admin\model\Admin(); 
        $perUserIds = $userModel->getUserByPer('bi', 'business', 'read'); //权限范围内userIds
        $whereData = $adminModel->getWhere($param, '', $perUserIds); //统计条件
        $userIds = $whereData['userIds'];         
        $between_time = $whereData['between_time'];         
        $where['owner_user_id'] = array('in',$userIds);
        $where['create_time'] = array('between',$between_time);
        $sql = $this
            ->field('business_id,customer_id,money,type_id,status_id,deal_date,create_user_id,owner_user_id')
            ->where($where)
            ->fetchSql()
            ->limit(50)
            ->order(['money' => 'DESC'])
            ->select();

        return queryCache($sql);
   }
}