<?php

namespace app\admin\validate;
use think\Validate;
/**
* 设置模型
*/
class AdminGroup extends Validate{

	protected $rule = [
		'title'   => 'require',
		'types'   => 'require',
	];
	protected $message = [
		'title.require'    => '角色名称必须填写',
		'types.require'    => '角色类型必须选择',
	];
	protected $scene = [
        'edit'  =>  ['id'],
    ];
}