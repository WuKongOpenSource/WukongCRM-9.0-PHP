<?php

namespace app\oa\validate;
use think\Validate;
/**
* 设置模型
*/
class OaLog extends Validate{

	protected $rule = [
		//'title'  		=> 'require',
		'content'  => 'require',
	];
	protected $message = [
		//'title.require'    	=> '日志标题必须填写',
		'content.require'    	=> '日志内容必须填写',					
	];
	protected $scene = [
        'edit'  =>  [],
    ];
}