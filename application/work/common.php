<?php
use think\Db;

/**
 * 根据项目ID统计
 * @param  [type] $param [description]
 * @return [type]        [description]
 */
function statistic($param)
{
    $data = array();
    $workDet = Db::name('Work')->field('work_id,name')->find($param['work_id']);
    $data['work_id'] = $param['work_id'];
    $data['name'] = $workDet['name'];
    $data['all'] = Db::name('Task')->where('work_id ='.$param['work_id'])->count();
    if ($data['all'] == 0) {
        $data['todo'] = 0;
        $data['hasdone'] = 0;
        $data['overtime'] = 0;
        $data['donelv'] = 0;
        $data['overlv'] = 0;
    } else {
        $data['todo'] = Db::name('Task')->where('status =1 and work_id ='.$param['work_id'])->count();
        $data['hasdone'] = Db::name('Task')->where('status =5 and work_id ='.$param['work_id'])->count();
        $data['overtime'] = Db::name('Task')->where('status =2 and work_id ='.$param['work_id'])->count();
        $data['donelv'] = $data['hasdone']/$data['all'];
        $data['overlv'] = 1.00*$data['overtime']/$data['all'];
    }
    return $data;
}