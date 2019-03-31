<?php
// +----------------------------------------------------------------------
// | Description: 角色员工关系
// +----------------------------------------------------------------------
// | Author:  
// +----------------------------------------------------------------------

namespace app\admin\model;

use app\admin\model\Common;
use think\Db;

class Access extends Common 
{
    /**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如CRM模块用crm作为数据表前缀
     */
	protected $name = 'admin_access';

	/**
	 * [getDataList 获取列表]
	 * @return    [array]            
	 */
	public function userGroup($user_id, $groups, $action = '')
	{
		if (!$user_id) {
			$this->error = '参数错误';
			return false;
		}
		$this->startTrans();
		try {
			if ($action == 'update') {
				$this->where('user_id', $user_id)->delete();
			}
			foreach ($groups as $k => $v) {
				if (!db('admin_access')->where(['user_id' => $user_id,'group_id' => $v])->find()) {
					$userGroup['user_id'] = $user_id;
					$userGroup['group_id'] = $v;
					$userGroups[] = $userGroup;
				}
			}
			$this->saveAll($userGroups);
			$this->commit();
			return true;
		} catch(\Exception $e) {
			$this->rollback();
			$this->error = '编辑失败';
			return false;
		}
	}	
}