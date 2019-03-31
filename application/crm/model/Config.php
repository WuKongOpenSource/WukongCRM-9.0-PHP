<?php
// +----------------------------------------------------------------------
// | Description: 客户设置
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com
// +----------------------------------------------------------------------
namespace app\crm\model;

use think\Db;
use app\admin\model\Common;
use think\Request;
use think\Validate;

class Config extends Common
{
	/**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如CRM模块用crm作为数据表前缀
     */
	protected $name = 'crm_config';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
	protected $autoWriteTimestamp = true;

	/**
	 * 保存相关信息
	 * @author Michael_xu
	 * @param  
	 * @return                            
	 */	
	public function createData($param)
	{
		//验证
		$validate = validate($this->name);
		if (!$validate->check($param)) {
			$this->error = $validate->getError();
			return false;
		}

		if ($this->data($param)->allowField(true)->save()) {
			return true;
		} else {
			$this->error = '添加失败';
			return false;
		}		
	}
} 		