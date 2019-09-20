<?php
// +----------------------------------------------------------------------
// | Description: CRM相关设置
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com
// +----------------------------------------------------------------------
namespace app\crm\model;

use think\Db;
use app\admin\model\Common;
use think\Request;
use think\Validate;

class Setting extends Common
{
	/**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如CRM模块用crm作为数据表前缀
     */
    
	/**
     * 团队成员
     * @author Michael_xu
     * @param types 类型
	 * @param types_id 类型ID数组
	 * @param type  权限 1只读2读写
	 * @param user_id [array] 协作人
	 * @param is_del 1 移除操作 
     * @return
     */  
    public function createTeamData($param)
    {
    	if (!is_array($param['user_id'])) {
    		$param['user_id'] = [intval($param['user_id'])];
    	}
        if (!is_array($param['types_id'])) {
            $param['types_id'] = [intval($param['types_id'])];
        }
        $res = teamUserId($param['types'], $param['types_id'], $param['type'], $param['user_id'], $param['is_del'], $param['owner_user_id']);
		if ($res == '1') {
            //同时关联其他模块(仅限客户模块)
            if (is_array($param['module']) && $param['types'] == 'crm_customer') {
                foreach ($param['module'] as $v) {
                    $where = [];
                    $where['customer_id'] = array('in',$param['types_id']);
                    // $where['owner_user_id'] = $param['owner_user_id'];
                    $moduleList = db($v)->where($where)->select();
                    switch ($v) {
                        case 'crm_contacts' : $module_id = 'contacts_id'; break;
                        case 'crm_business' : $module_id = 'business_id'; break;
                        case 'crm_contract' : $module_id = 'contract_id'; break;
                    }   
                    foreach ($moduleList as $val) {
                        teamUserId($v, $val[$module_id], $param['type'], $param['user_id'], $param['is_del'], $param['owner_user_id'], 0);
                    }                             
                }
            }
            return true; 
        } else {
        	return $res;
        }    	
    }    
} 		