<?php
//权限控制
\think\Hook::add('check_auth','app\\common\\behavior\\AuthenticateBehavior');

use think\Db;

//获取一年中每个月开始结束时间
function getMonthStart($year)
{
    $i = 1;
    for($i=1;$i<13;$i++) {
        $time = $year.'-'.$i.'-01';
        $data[$i]= strtotime($time);
    }
    $newyear = $year+1;
    $data[13] = strtotime($newyear.'-01-01');
    return $data;
}
