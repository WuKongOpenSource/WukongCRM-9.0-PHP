<?php

namespace app\admin\validate;
use think\Validate;
/**
* 设置模型
*/
class AdminUser extends Validate{

	protected $rule = array(
		'realname'      	=> 'require',
		'username'      	=> 'require|unique:admin_user',
		'structure_id'      	=> 'require',
	);
	protected $message = array(
		'realname.require'    	=> '姓名必须填写',
		'username.require'    	=> '手机号码必须填写',
		'username.unique'    	=> '手机号码已存在',
		'structure_id.require'    	=> '请选择所属部门',
	);
}