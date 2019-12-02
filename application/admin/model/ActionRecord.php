<?php
// +----------------------------------------------------------------------
// | Description: 字段修改记录
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com 
// +----------------------------------------------------------------------

namespace app\admin\model;

use app\admin\model\Common;

class ActionRecord extends Common 
{
    /**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如CRM模块用crm作为数据表前缀
     */
	protected $name = 'admin_action_record';
	public $typesArr = ['crm_leads','crm_customer','crm_contacts','crm_product','crm_business','crm_contract','crm_receivables'];

	/**
	 * [getDataList 获取列表]
	 * @return    [array]                         
	 */
	public function getDataList($request)
	{
		$types = trim($request['types']);
		$action_id = intval($request['action_id']);
		//判断权限
		if (!$this->checkData($types, $action_id, $request['user_id'])) {
			return [];
		}
		$dataList = db('admin_action_record')->where(['types' => $types,'action_id' => $action_id])->select();
		if($types == 'crm_customer') {
			$leads_id = db('crm_leads')->where(['customer_id' => $action_id, 'is_transform' => 1])->value('leads_id');
			if($leads_id){
				$leads_dataList = db('admin_action_record')->where(['types' => 'crm_leads','action_id' => $leads_id])->select();
				$dataList = array_merge($leads_dataList, $dataList);
			}
		}
		$userModel = model('User');
		foreach ($dataList as $k=>$v) {
			$dataList[$k]['user_id_info'] = isset($v['user_id']) ? $userModel->getUserById($v['user_id']) : [];
			$dataList[$k]['content'] = explode('.|.', $v['content']);
		}
		return $dataList;
	}

	/**
	 * [checkData 权限判断]
	 * @return    [array]                         
	 */	
	public function checkData($types, $action_id, $user_id)
	{
		if (!in_array($types, $this->typesArr)) {
			return false;
		}
		if (!$action_id) {
			return false;
		}
		$adminTypes = adminGroupTypes($user_id);
        if (in_array(1,$adminTypes)) {
            return true;
        }

		$checkRes = false;
		switch ($types) {
			case 'crm_leads' :
				$checkRes = checkPerByAction('crm', 'leads', 'read');
				break;			
			case 'crm_customer' :
				$checkRes = checkPerByAction('crm', 'customer', 'read');
				break;
			case 'crm_contacts' :
				$checkRes = checkPerByAction('crm', 'contacts', 'read');
				break;
			case 'crm_product' :
				$checkRes = checkPerByAction('crm', 'product', 'read');
				break;
			case 'crm_business' :
				$checkRes = checkPerByAction('crm', 'business', 'read');
				break;			
			case 'crm_contract' :
				$checkRes = checkPerByAction('crm', 'contract', 'read');
				break;	
			case 'crm_receivables' :
				$checkRes = checkPerByAction('crm', 'receivables', 'read');
				break;						
		}
		if ($checkRes !== false) {
			return true;
		}
	}

	/**
     * 删除字段修改记录
     * @param 
     * @return
     */
	public function delDataById($request)
	{
		$types = trim($request['types']);
		$action_id = $request['action_id'];
		if ($types && $action_id) {
			$res = db('admin_action_record')->where(['types' => $types,'action_id' => ['in',$action_id]])->delete();
		}
	}    	
}