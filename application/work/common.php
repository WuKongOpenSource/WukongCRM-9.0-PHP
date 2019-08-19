<?php
//权限控制
\think\Hook::add('check_auth','app\\common\\behavior\\AuthenticateBehavior');

use think\Db;

/**
 * 判断操作权限
 * @author Michael_xu 
 * @param  
 * @return       
 */    
function checkWorkPerByAction($m, $c, $a, $param)
{
	$user_id = $param['user_id'];
	$group_id = $param['group_id'];
	$mRuleId = db('admin_rule')->where(['name'=>$m,'level'=>1])->value('id');
	$cRuleId = db('admin_rule')->where(['name'=>$c,'level'=>2,'pid'=>$mRuleId])->value('id');
	$aRuleId = db('admin_rule')->where(['name'=>$a,'level'=>3,'pid'=>$cRuleId])->value('id');
	$resGroup = db('admin_group')->where(['id' => $group_id,'rules' => ['like','%,'.$aRuleId.',%']])->find();
	if ($resGroup) {
		return true;
	}
    return false;
}
