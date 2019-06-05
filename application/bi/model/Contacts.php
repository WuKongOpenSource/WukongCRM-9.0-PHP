<?php
// +----------------------------------------------------------------------
// | Description: 联系人
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com
// +----------------------------------------------------------------------
namespace app\bi\model;

use think\Db;
use app\admin\model\Common;
use think\Request;
use think\Validate;

class Contacts extends Common
{
	/**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如CRM模块用crm作为数据表前缀
     */
	protected $name = 'crm_contacts';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
	protected $autoWriteTimestamp = true;

	/**
     * 根据新增联系人数排序
     * @param  [type] $whereArr [description]
     * @return [type]           [description]
     */
    function getSortByCount($whereArr)
    {
        $count = db('crm_contacts')->group('owner_user_id')->field('owner_user_id,count(contacts_id) as count')->order('count desc')->where($whereArr)->select();
        return $count;
    }   	
}