<?php

namespace app\admin\validate;
use think\Validate;
/**
* 设置模型
*/
class AdminField extends Validate{

	protected $rule = [
		'field'  		=> ['regex'=>'/^[a-z]([a-z]|_)+[a-z]$/i'],
		'name'      	=> 'require',
		'types'      	=> 'require',
		'form_type'      	=> 'require',
	];
	protected $message = [
		'field.regex'    	=> '字段名称格式不正确！',
		'name.require'    	=> '字段标识必须填写',
		'types.require'    	=> '分类必须填写',
		'form_type.require'    	=> '字段类型必须填写',
	];
}