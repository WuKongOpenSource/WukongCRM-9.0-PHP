<?php

namespace app\oa\validate;
use think\Validate;
/**
* 设置模型
*/
class OaAnnouncement extends Validate{

	protected $rule = [
		'title'  		=> 'require',
		'content'  => 'require|number',
	];
	protected $message = [
		'title.require'    	=> '公告标题必须填写',
		'content.require'    => '公告内容必须填写',					
	];
	protected $scene = [
        'edit'  =>  [],
    ];
}