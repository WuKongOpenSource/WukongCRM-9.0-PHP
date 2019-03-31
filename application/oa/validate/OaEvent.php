<?php

namespace app\oa\validate;
use think\Validate;
/**
* 设置模型
*/
class OaEvent extends Validate{

	protected $rule = [
		'title'  		=> 'require',
	];
	protected $message = [
		'title.require'    	=> '日程标题必须填写',					
	];
	protected $scene = [
        'edit'  =>  [],
    ];
}