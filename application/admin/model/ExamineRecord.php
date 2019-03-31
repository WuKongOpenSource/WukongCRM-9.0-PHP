<?php
// +----------------------------------------------------------------------
// | Description: 审批意见
// +----------------------------------------------------------------------
// | Author: Michael_xu | gengxiaoxu@5kcrm.com
// +----------------------------------------------------------------------
namespace app\admin\model;

use think\Db;
use app\admin\model\Common;
use think\Request;
use think\Validate;

class ExamineRecord extends Common
{
	/**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如CRM模块用crm作为数据表前缀
     */
	protected $name = 'admin_examine_record';

	/**
     * 审批意见(创建)
     * @param types 关联对象
     * @param types_id 联对象ID
     * @param flow_id 审批流程ID
     * @param step_id 审批步骤ID
     * @param user_id 审批人ID
     * @param status 1通过0驳回
     * @return 
     */
    public function createData($param)
    {
		if ($this->data($param)->allowField(true)->save()) {
			$data = [];
			$data['record_id'] = $this->record_id;
			return $data;
		} else {
			$this->error = '添加失败';
			return false;
		}    	
    }

	/**
     * 审批意见(列表)
     * @param types 关联对象
     * @param types_id 联对象ID
     * @return 
     */
    public function getDataList($param)
    {
		$userModel = new \app\admin\model\User();
        if (empty($param['types']) || empty($param['types_id'])) {
            return [];
        }
        $list = db('admin_examine_record')->where($param)->order('check_time asc')->select();
        foreach ($list as $k=>$v) {
            $list[$k]['check_user_id_info'] = $userModel->getUserById($v['check_user_id']);
        }
        return $list ? : [];  	
    } 

    /**
     * 审批意见(标记无效,撤销审批时使用)
     * @param types 关联对象
     * @param types_id 关联对象ID
     * @return 
     */
    public function setEnd($param)
    {
        if (empty($param['types']) || empty($param['types_id'])) {
            $this->error = '参数错误';
            return false;
        }        
        $res = $this->where(['types' => $param['types'],'types_id' => $param['types_id']])->update(['is_end' => 1]);
        return true;
    }      
} 