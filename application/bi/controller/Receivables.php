<?php
// +----------------------------------------------------------------------
// | Description: 商业智能-回款分析
// +----------------------------------------------------------------------
// | Author: Michael_xu | gengxiaoxu@5kcrm.com 
// +----------------------------------------------------------------------

namespace app\bi\controller;

use app\admin\controller\ApiCommon;
use think\Hook;
use think\Request;

class Receivables extends ApiCommon
{
    /**
     * 用于判断权限
     * @permission 无限制
     * @allow 登录用户可访问
     * @other 其他根据系统设置
    **/    
    public function _initialize()
    {
        $action = [
            'permission'=>[''],
            'allow'=>['statistics','statisticlist']            
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());        
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
        if (!checkPerByAction('bi', 'receivables' , 'read')) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }         
    } 
  
    //回款统计列表
    public function statisticList()
    {
        $receivablesModel = new \app\crm\model\Receivables();
        $param = $this->param;
        $ret = $receivablesModel->getstatisticsData($param);
        return resultArray(['data'=>$ret]);
    }

    /**
     * 回款统计 柱状图
     * @author Michael_xu
     * @param year 年份 , month 月份
     * @return
     */
    public function statistics()
    {        
        $receivablesModel = new \app\crm\model\Receivables();
        $userModel = new \app\admin\model\User();
        $param = $this->param;
        $adminModel = new \app\admin\model\Admin(); 
        $perUserIds = $userModel->getUserByPer('bi', 'receivables', 'read'); //权限范围内userIds
        $whereData = $adminModel->getWhere($param, '', $perUserIds); //统计条件
        $userIds = $whereData['userIds'];       

        $year = $param['year'];
        //时间段
        if ($param['month']) {
            //根据月份计算天数
            $days = getmonthdays(strtotime($param['month']));
            //获取时间范围内的每日时间戳数组(当月)
            $start = strtotime($param['month'].'-'.'01');
            $end = (strtotime($param['month'].'-'.$days) > time()) ? time() : strtotime($date.'-'.$days);
            $create_time = array($start,$end);
        } elseif ($param['year']) {
            $next_year = $param['year']+1;
            $create_time = array(strtotime($param['year'].'-01-01'),strtotime($next_year.'-01-01'));
        } else {
            $create_time = getTimeByType('year');
        }
        unset($param['month']);
        unset($param['year']); 
        $param['create_time']['start'] = $create_time[0];
        $param['create_time']['end'] = $create_time[1];     
        $chartParam = $param;
        $chartParam['year'] = $year;
        $chartParam['userIds'] = $userIds ? : [];
        $chartList = $receivablesModel->getStatistics($chartParam); //柱状图
        return resultArray(['data' => $chartList]);
    }
}
