<?php

namespace app\crm\validate;
use think\Validate;
/**
* 设置模型
*/
class CrmReceivablesPlan extends Validate{

	protected $rule = [
		'contract_id' => 'require',
	];
	protected $message = [
		'contract_id.require' => '请先选择合同',
	];
}