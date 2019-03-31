<?php

namespace app\admin\validate;
use think\Validate;
/**
* 设置模型
*/
class AdminStructure extends Validate{

	protected $rule = [
		'name'      => 'require',
	];
	protected $message = [
		'name.require' => '部门名称必须填写',
	];
}