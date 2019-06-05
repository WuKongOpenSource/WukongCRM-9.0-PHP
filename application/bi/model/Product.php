<?php
// +----------------------------------------------------------------------
// | Description: 产品
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com
// +----------------------------------------------------------------------
namespace app\bi\model;

use think\Db;
use app\admin\model\Common;
use think\Request;
use think\Validate;

class Product extends Common
{
	/**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如CRM模块用crm作为数据表前缀
     */
	protected $name = 'crm_product';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
	protected $autoWriteTimestamp = true;

	/**
     * [产品分类销量分析]
     * @author Michael_xu
     * @param     [string]                   $request [查询条件]
     * @return    [array]                    
     */		
	public function getStatistics($request)
    {
    	// $userModel = new \app\admin\model\User();
    	$where = $this->getWhere($request);
		$join = [
			['__CRM_CONTRACT__ contract', 'contract.contract_id = a.contract_id', 'LEFT'],
			['__ADMIN_USER__ user', 'user.id = contract.owner_user_id', 'LEFT'],
			['__CRM_PRODUCT__ product', 'product.product_id = a.product_id', 'LEFT'],
			['__CRM_PRODUCT_CATEGORY__ product_category', 'product_category.category_id = product.category_id', 'LEFT'],
		];

		$list = db('crm_contract_product')
		 ->alias('a')
		 ->where($where)
	     ->join($join)
	     ->group('a.product_id')
	     ->field('a.product_id,sum(a.num) as num,product.name as product_name,product_category.name as category_id_info,product_category.category_id')
	     ->select();
        return $list;
    }
    /**
     * 产品成交客户数
     * @param  [type] $request [description]
     * @return [type]          [description]
     */
    function getDealByProduct($request)
    {
    	$where = $this->getWhere($request);
    	$where['customer.deal_status'] = '已成交';
    	$join = [
			['__CRM_CONTRACT__ contract', 'contract.contract_id = a.contract_id', 'LEFT'],
			['__ADMIN_USER__ user', 'user.id = contract.owner_user_id', 'LEFT'],
			['__CRM_PRODUCT__ product', 'product.product_id = a.product_id', 'LEFT'],
			['__CRM_PRODUCT_CATEGORY__ product_category', 'product_category.category_id = product.category_id', 'LEFT'],
			['__CRM_CUSTOMER__ customer', 'customer.customer_id = contract.customer_id', 'LEFT'],
		];

		$list = db('crm_contract_product')
		 ->alias('a')
		 ->where($where)
	     ->join($join)
	     ->group('a.product_id')
	     ->field('a.product_id,count(customer.customer_id) as num,product.name as product_name,product_category.name as category_id_info,product_category.category_id')
	     ->select();
        return $list;
    }
    /**
     * 产品成交周期
     * @param  [type] $request [description]
     * @return [type]          [description]
     */
    function getCycleByProduct($request,$product_id)
    {
    	$where = $this->getWhere($request);
    	$where['customer.deal_status'] = '已成交';
    	$where['a.product_id'] = $product_id;
    	$join = [
			['__CRM_CONTRACT__ contract', 'contract.contract_id = a.contract_id', 'LEFT'],
			['__ADMIN_USER__ user', 'user.id = contract.owner_user_id', 'LEFT'],
			['__CRM_PRODUCT__ product', 'product.product_id = a.product_id', 'LEFT'],
			['__CRM_PRODUCT_CATEGORY__ product_category', 'product_category.category_id = product.category_id', 'LEFT'],
			['__CRM_CUSTOMER__ customer', 'customer.customer_id = contract.customer_id', 'LEFT'],
		];

		$list = db('crm_contract_product')
		 ->alias('a')
		 ->where($where)
	     ->join($join)
	     ->order('order_date')
	     ->group('customer.customer_id')
	     ->field('customer.customer_id')
	     ->select();
	    $customer_ids = array();
	    foreach ($list as $key => $value) {
	    	$customer_ids[] = $value['customer_id'];
	    }
        return $customer_ids;
    }
    /**
     * 产品销量排行
     * @param  [type] $whereArr [description]
     * @return [type]           [description]
     */
    function getSortByProduct($request)
    {
		$where = $this->getWhere($request);

		$join = [
			['__CRM_CONTRACT__ contract', 'contract.contract_id = a.contract_id', 'LEFT'],
			['__ADMIN_USER__ user', 'user.id = contract.owner_user_id', 'LEFT'],
			['__CRM_PRODUCT__ product', 'product.product_id = a.product_id', 'LEFT'],
			['__CRM_PRODUCT_CATEGORY__ product_category', 'product_category.category_id = product.category_id', 'LEFT'],
		];

		$list = db('crm_contract_product')
		 ->alias('a')
		 ->where($where)
	     ->join($join)
	     ->order('num desc')
	     ->group('contract.owner_user_id')
	     ->field('sum(a.num) as num,contract.owner_user_id')
	     ->select();
        return $list;
    }
    /**
     * 获取条件
     * @param  [type] $request [description]
     * @return [type]          [description]
     */
    function getWhere($request)
    {
    	$userModel = new \app\admin\model\User();
		$where = [];
		//时间段
		if(empty($request['type']) && empty($request['start_time'])){
            $request['type'] = 'month';
        }
        if(!empty($request['start_time'])){
        	$where['contract.order_date'] = array('between',array($request['start_time'],$request['end_time']));
        }else{
        	$create_time = getTimeByType($request['type']);
			if ($create_time) {
	            $where['contract.create_time'] = array('between',array($create_time[0],$create_time[1]));
	        }
        }
        if(!empty($request['category_id'])){
        	$where['product_category.category_id'] = array('eq',$request['category_id']);
        }
        $where['contract.check_status'] = array('eq',2);
		//员工IDS
		$map_user_ids = [];	
		if ($request['user_id']) {
			$map_user_ids = [$request['user_id']];
		} else {
			if ($request['structure_id']) {
                $map_user_ids = $userModel->getSubUserByStr($request['structure_id'], 2);
            }
		}
		$perUserIds = $userModel->getUserByPer('bi', 'product', 'read'); //权限范围内userIds
		$userIds = $map_user_ids ? array_intersect($map_user_ids, $perUserIds) : $perUserIds; //数组交集
		$where['contract.owner_user_id'] = array('in',$userIds);
		return $where;
    } 	
}