<?php
//权限控制
\think\Hook::add('check_auth','app\\common\\behavior\\AuthenticateBehavior');

use think\Db;
use app\crm\model\Leads;

//添加关联关系  数据表名+业务ID+数组
function addRelation( $moudle ='',$id='',$param = array() ){
	switch ($module) {
		case 'event':
			$module = 'OaEventRelation';
			$fieldname = 'event_id';
			break;
		case 'task':
			$module = 'TaskRelation';
			$fieldname = 'task_id';
			break;
		case 'log':
			$module = 'OaLogRelation';
			$fieldname = 'log_id';
			break;
		default:
			$module = 'WorkRelation';
			$fieldname = 'work_id';
			break;
	}
	$rdata['customer_ids'] = count($param['customer_ids']) ? ','.implode(',',$param['customer_ids']).',' : ''; 
	$rdata['contacts_ids'] = count($param['contacts_ids']) ? ','.implode(',',$param['contacts_ids']).',' : ''; 
	$rdata['business_ids'] = count($param['business_ids']) ? ','.implode(',',$param['business_ids']).',' : ''; 
	$rdata['contract_ids'] = count($param['contract_ids']) ? ','.implode(',',$param['contract_ids']).',' : '';  
	
	$rdata['status'] = 1;
	$rdata[$fieldname] = $id;
	$rdata['create_time'] = time();
	$flag = Db::name($module)->insert($rdata);
	return true;
}