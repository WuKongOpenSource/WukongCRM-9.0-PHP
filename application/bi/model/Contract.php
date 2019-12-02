<?php
// +----------------------------------------------------------------------
// | Description: 合同
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com
// +----------------------------------------------------------------------
namespace app\bi\model;

use think\Db;
use app\admin\model\Common;
use think\Request;
use think\Validate;

class Contract extends Common
{
	/**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如CRM模块用crm作为数据表前缀
     */
	protected $name = 'crm_contract';

	/**
     * [getDataList 合同金额]
     * @author Michael_xu
     * @return
     */		
	function getWhereByMoney($whereArr)
    {
        return db('crm_contract')->where($whereArr)->sum('money');
    }

    /**
     * [getSortByMoney 根据合同金额排序]
     * @author zhi
     * @param 
     * @return
     */
    function getSortByMoney($whereArr)
    {
        return $this->group('owner_user_id')->field('owner_user_id,sum(money) as money')->order('money desc')->where($whereArr)->select();
    }

    /**
     * [getDataList 根据合同签约数排序]
     * @author zhi
     * @param 
     * @return
     */     
    function getSortByCount($whereArr)
    {
        $money = db('crm_contract')->group('owner_user_id')->field('owner_user_id,count(contract_id) as count')->order('count desc')->where($whereArr)->select();
        return $money;
    }

    /**
     * 获取合同数量
     * @author zhi
     * @param 
     * @return
     */
    function getDataCount($whereArr){
        $count = db('crm_contract')->where($whereArr)->count('contract_id');
        return $count;
    }
    
    /**
     * 获取合同金额
     * @author zhi
     * @param 
     * @return
     */
    function getDataMoney($whereArr){
        $money = db('crm_contract')->where($whereArr)->sum('money');
        return $money;
    }
}