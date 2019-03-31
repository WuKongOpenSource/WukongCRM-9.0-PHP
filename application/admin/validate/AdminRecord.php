<?php

namespace app\admin\validate;
use think\Validate;
/**
* 设置模型
*/
class AdminRecord extends Validate{

	protected $rule = [
		'category'  	=> 'require',
		'content'  => 'require',
	];
	protected $message = [
		'category.require'    => '跟进类型必须填写',
		'content.require'    	=> '日志内容必须填写',					
	];
	protected $scene = [
        'edit'  =>  [],
    ];
}