<?php

namespace app\admin\validate;
use think\Validate;
/**
* 设置模型
*/
class AdminPost extends Validate{

	protected $rule = [
		'name'   => 'require',
	];
	protected $message = [
		'name.require'    => '岗位名称必须填写',
	];
}