<?php

namespace app\crm\validate;
use think\Validate;
/**
* 设置模型
*/
class CrmProductCategory extends Validate{

	protected $rule = [
		'name'      => 'require',
	];
	protected $message = [
		'name.require' => '类型名称必须填写',
	];
}