<?php
// +----------------------------------------------------------------------
// | Description: 操作记录
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com 
// +----------------------------------------------------------------------

namespace app\bi\model;

use think\Db;
use app\admin\model\Common;
use think\Request;
use think\Validate;

class ActionRecord extends Common 
{
    /**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如CRM模块用crm作为数据表前缀
     */
	protected $name = 'admin_action_record';

	/**
	 * [getDataList 获取列表]
	 * @return    [array]                         
	 */
	public function getDataCount($request)
	{
		$dataCount = db('admin_action_record')->where($request)->count();
		return $dataCount;
	}   	
}