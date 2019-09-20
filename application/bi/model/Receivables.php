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

class Receivables extends Common
{
	/**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如CRM模块用crm作为数据表前缀
     */
	protected $name = 'crm_receivables';

	/**
     * [getDataList 根据回款金额排序]
     * @author Michael_xu
     * @param
     * @return
     */     
    function getSortByMoney($whereArr)
    {
        $money = db('crm_receivables')->group('owner_user_id')->field('owner_user_id,sum(money) as money')->order('money desc')->where($whereArr)->select();
        return $money;
    }

    /**
     * 获取回款金额
     * @return
     */
    function getDataMoney($whereArr){
        $money = db('crm_receivables')->where($whereArr)->sum('money');
        return $money;
    }
}