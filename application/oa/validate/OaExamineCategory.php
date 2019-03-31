<?php

namespace app\oa\validate;
use think\Validate;
/**
* 设置模型
*/
class OaExamineCategory extends Validate{

	protected $rule = [
		'title'     => 'require',
	];
	protected $message = [
		'title.require'    	=> '审批名称必须填写',					
	];
	protected $scene = [
        'edit'  =>  [],
    ];
}