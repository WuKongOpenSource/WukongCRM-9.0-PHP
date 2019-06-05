<?php
// +----------------------------------------------------------------------
// | Description: 客户
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com
// +----------------------------------------------------------------------
namespace app\bi\model;

use think\Db;
use app\admin\model\Common;
use think\Request;
use think\Validate;

class Customer extends Common
{
	/**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如CRM模块用crm作为数据表前缀
     */
	protected $name = 'crm_customer';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
	protected $autoWriteTimestamp = true;

	protected $type = [
		'next_time' => 'timestamp',
	];
    /**
     * 获取转化客户信息
     * @param  [type] $whereArr [description]
     * @return [type]           [description]
     */
    function getWhereByList($whereArr)
    {
        $userModel = new \app\admin\model\User();
        $receivables = new \app\bi\model\Receivables();
        
        $list = db('crm_customer')->field('customer_id,name,owner_user_id,create_user_id,industry,source,create_time')->where($whereArr)->select();
        foreach ($list as $key => $value) {
            $where_c = array();
            $where_c['customer_id'] = array('eq',$value['customer_id']);
            $contract = db('crm_contract')->field('contract_id,name,money,create_time,order_date')->order('contract_id asc')->limit(1)->where($where_c)->find();
            $list[$key]['create_time'] = date('Y-m-d',$value['create_time']);
            $list[$key]['contract_name'] = $contract['name'];
            $list[$key]['contract_money'] = $contract['money'];
            $list[$key]['order_time'] = $contract['order_date'];
            $owner_user = $userModel->getDataById($value['owner_user_id']);
            $list[$key]['owner_realname'] = $owner_user['realname'];
            $create_user = $userModel->getDataById($value['create_user_id']);
            $list[$key]['create_realname'] = $create_user['realname'];
            $where_c['contract_id'] = array('eq',$contract['contract_id']);
            $r_money = $receivables->getDataMoney($where_c);
            $list[$key]['r_money'] = $r_money;
        }
        return $list;
    }
	/**
	 * 根据条件获取开始、结束时间
	 * @param  [type] $type [description]
	 * @param  [type] $year [description]
	 * @param  [type] $i    [description]
	 * @return [type]       [description]
	 */
	function getStartAndEnd($param,$year,$i)
    {
        $timeArr = array();
        switch($param['type']) {
            case 'year'://本年度
                $timeArr['month'] = $i;
                $timeArr['next_month'] = $i+1;
                if($timeArr['next_month'] != 13){
                    $timeArr['year'] = $year;
                    $timeArr['next_year'] = $year;
                }else{
                    $timeArr['year'] = $year;
                    $timeArr['next_year'] = $year+1;
                    $timeArr['next_month'] = 1;
                }
                $timeArr['type'] = $year.'-'.$i;
            break;
            case 'lastYear'://上年度
                $timeArr['month'] = $i;
                $timeArr['next_month'] = $i+1;
                if($timeArr['next_month'] != 13){
                    $timeArr['year'] = $year;
                    $timeArr['next_year'] = $year;
                }else{
                    $timeArr['year'] = $year;
                    $timeArr['next_year'] = $year+1;
                    $timeArr['next_month'] = 1;
                }
                $timeArr['type'] = $year.'-'.$i;
            break;
            case 'quarter'://本季度
                $season = ceil(date('n')/3);
                $dates = mktime(0,0,0,($season-1)*3+1,1,date('Y'));
                $month = date('m',$dates);
                $timeArr['year'] = $year;
                $timeArr['month'] = $month+$i-1;
                if($month != 10){
                    $timeArr['next_year'] = $year;
                    $timeArr['next_month'] = $month+$i;
                    $timeArr['type'] = $year.'-'.($month+$i-1);
                }else{
                    if($month+$i <= 12){
                        $timeArr['next_year'] = $year;
                        $timeArr['next_month'] = $month+$i;
                        $timeArr['type'] = $year.$month+$i;
                    }else{
                        $timeArr['next_year'] = $year+1;
                        $timeArr['next_month'] = 1;
                        $timeArr['type'] = ($year+1).'-'.'01';
                    }
                }
            break;
            case 'lastQuarter'://上季度
                $season = ceil(date('n')/3);
                $dates = mktime(0,0,0,($season-1)*3+1,1,date('Y'));
                $month = date('m',$dates);
                if($month > 3){
                    $timeArr['year'] = $year;
                    $month = $month-3;
                    $timeArr['month'] = $month+$i-1;
                    $timeArr['next_year'] = $year;
                    $timeArr['next_month'] = $month+$i;
                    $timeArr['type'] = $year.'-'.($month+$i-1);
                }else{
                    $month = 10;
                    $timeArr['year'] = $year-1;
                    $timeArr['month'] = $month+$i-1;
                    if($month+$i <= 12){
                        $timeArr['next_year'] = $year-1;
                        $timeArr['next_month'] = $month+$i;
                    }else{
                        $timeArr['next_year'] = $year;
                        $timeArr['next_month'] = 1;
                    }
                    $timeArr['type'] = $year.'-'.$month+$i-1;
                }                                                         
            break;
            case 'month'://本月
                $timeArr['year'] = $year;
                $timeArr['month'] = date('m');
                $timeArr['next_year'] = $year;
                $timeArr['day'] = $i;
                if($i != date("t")){
                    $timeArr['next_month'] = date('m');
                    $timeArr['next_day'] = $i+1;
                }else{
                    $timeArr['next_month'] = date('m')+1;
                    $timeArr['next_day'] = 1;
                }  
                $timeArr['type'] = $year.'-'.date('m').'-'.$i;                                                            
            break;
            case 'lastMonth'://上月
                $timeArr['year'] = $year;
                $month = date('m');//当前月
                if($month != 1){
                    $month = $month-1;//上月
                    $days = date('t', strtotime(date('Y').'-'.$month.'-1'));//上月天数
                    $timeArr['next_year'] = $year;
                    if($i != $days){
                        $timeArr['day'] = $i;
                        $timeArr['next_day'] = $i+1;
                        $timeArr['month'] = $month;
                        $timeArr['next_month'] = $month;
                    }else{
                        $timeArr['day'] = $i;
                        $timeArr['next_day'] = 1;
                        $timeArr['month'] = $month;
                        if($month != 12){
                            $timeArr['next_month'] = $month+1;
                        }else{
                            $timeArr['next_month'] = 1;
                            $timeArr['next_year'] = $year;
                        }
                    }
                }else{
                    $month = 12;
                    $timeArr['year'] = $year-1;
                    $timeArr['next_year'] = $year;
                }        
                $timeArr['type'] = $year.'-'.$month.'-'.$i;                                                  
            break;
            case 'week'://本周
                date_default_timezone_set('PRC');
                $week = date("Y-m-d",strtotime("this week"));
                $day = strtotime($week)+($i-1)*(60*60*24);
                $lastDay = strtotime($week)+$i*(60*60*24);
                $timeArr['day'] = date("d",$day);
                $timeArr['next_day'] = date("d",$lastDay);
                $timeArr['month'] = date("m",$day);
                $timeArr['next_month'] = date("m",$lastDay);
                $timeArr['year'] = date("y",$day);
                $timeArr['next_year'] = date("y",$lastDay);    
                $timeArr['type'] = $year.'-'.date("m",$day).'-'.date("d",$day);                                  
            break;
            case 'lastWeek'://上周
                date_default_timezone_set('PRC');
                $week = date("Y-m-d",strtotime("this week"));
                $day = strtotime($week)+($i-8)*(60*60*24);
                $lastDay = strtotime($week)+($i-7)*(60*60*24);
                $timeArr['day'] = date("d",$day);
                $timeArr['next_day'] = date("d",$lastDay);
                $timeArr['month'] = date("m",$day);
                $timeArr['next_month'] = date("m",$lastDay);
                $timeArr['year'] = date("y",$day);
                $timeArr['next_year'] = date("y",$lastDay);  
                $timeArr['type'] = $year.'-'.date("m",$day).'-'.date("d",$day);                                    
            break;
            case 'today'://今天
                $today = time();
                $yesterday = time()+60*60*24;
                $timeArr['day'] = date("d",$today);
                $timeArr['next_day'] = date("d",$yesterday);
                $timeArr['month'] = date("m",$today);
                $timeArr['next_month'] = date("m",$yesterday);
                $timeArr['year'] = date("y",$today);
                $timeArr['next_year'] = date("y",$yesterday);     
                $timeArr['type'] = $year.'-'.date("m",$today).'-'.date("d",$today);     
            break;
            case 'yesterday'://昨天
                $today = time()-60*60*24;
                $yesterday = time();
                $timeArr['day'] = date("d",$today);
                $timeArr['next_day'] = date("d",$yesterday);
                $timeArr['month'] = date("m",$today);
                $timeArr['next_month'] = date("m",$yesterday);
                $timeArr['year'] = date("y",$today);
                $timeArr['next_year'] = date("y",$yesterday);      
                $timeArr['type'] = $year.'-'.date("m",$today).'-'.date("d",$today);    
            break;
            case 'month_k'://跨月
                $start_time_y = date('y',$param['start_time']);
                $timeArr['year'] = $start_time_y;
                $timeArr['next_year'] = $start_time_y;   
                $m = date('m',$param['start_time']);
                if($i > 1){
                    $timeArr['month'] = $m+$i-1;
                    $timeArr['next_month'] = $m+$i;
                    $timeArr['type'] = '20'.$start_time_y.'-'.($m+$i-1);
                }else{
                    $timeArr['month'] = $m;
                    $timeArr['next_month'] = $m+1;
                    $timeArr['type'] = '20'.$start_time_y.'-'.$m;
                }
            break;
            case 'year_k'://跨年
                $start_time = $param['start_time'];
                $end_time = $param['end_time'];

                $start_y = date('y',$start_time);
                $start_m = date('m',$start_time);
                $start_d = date('d',$start_time);

                $monthNum = $this->getMonthNum($start_time,$end_time);
                $y = ceil(($start_m+$i-1)/12);
                if(($start_m+$i-1)/12 <= $y){
                    if($i == 1){
                        $timeArr['day'] = $start_d;
                        $timeArr['end_d'] = 1;
                    }
                    $timeArr['year'] = $start_y+$y-1;                        
                    if($start_m+$i-1 < 12){
                        $timeArr['month'] = $start_m+$i-1;
                        $timeArr['next_year'] = $start_y+$y-1;
                        $timeArr['next_month'] = $start_m+$i;
                        $timeArr['type'] = '20'.($start_y+$y-1).'-'.($start_m+$i-1);
                    }else{
                        if(($start_m+$i-1)/12 == $y){
                            $timeArr['month'] = 12;
                            $timeArr['next_year'] = $start_y+$y;
                            $timeArr['next_month'] = 1;
                            $timeArr['type'] = '20'.($start_y+$y-1).'-12';
                        }else{
                            $timeArr['month'] = ($start_m+$i-1)-12*($y-1);
                            $timeArr['next_year'] = $start_y+$y-1;
                            if ($monthNum+1 != $i) {
                                $timeArr['next_month'] = ($start_m+$i)-12*($y-1);
                            }else{
                                $end_d = date('d',$param['end_time']);
                                $timeArr['next_day'] = $end_d;
                                $timeArr['next_month'] = ($start_m+$i-1)-12*($y-1);
                            }
                            $timeArr['type'] = '20'.($start_y+$y-1).'-'.(($start_m+$i-1)-12*($y-1));
                        }
                    }
                }
            break;
            default ://自定义时间
                $start_time = $param['start_time'];
                $end_time = $param['end_time'];
                $start_time_y = date('y',$start_time);
                $start_time_m = date('m',$start_time);
                $end_time_y = date('y',$end_time);
                $end_time_m = date('m',$end_time);
                if($end_time_y-$start_time_y == 0){//不跨年
                    if($end_time_m-$start_time_m > 0){
                        $param['type']='month_k';
                        $timeArr = $this->getStartAndEnd($param,$year,$i);
                    }else{//不跨月
                        $param['type']='month';
                        $timeArr = $this->getStartAndEnd($param,$year,$i);
                    }
                }else{//跨年
                    $start_time = date('Y-m-d',$start_time);
                    $end_time = date('Y-m-d',$end_time);
                    $monthNum = $this->getMonthNum($start_time,$end_time);
                    $param['type']='year_k';
                    $timeArr = $this->getStartAndEnd($param,$year,$i);
                }
            break;
        }
        return $timeArr;
    }
    
    /**
     * 根据条件获取单位
     * @return [type] [description]
     */
    function getParamByCompany($param)
    {
        $company['year'] = date('Y');
        $company['month'] = date('m');
        switch($param['type']) {
            case 'year'://本年度
                $company['j'] = 12;
            break;
            case 'lastYear'://上年度
                $company['j'] = 12;
                $company['year'] = date('Y')-1;
            break;
            case 'quarter'://本季度
                $company['j'] = 3;
            break;
            case 'lastQuarter'://上季度
                $company['j'] = 3;
            break;
            case 'month'://本月
                $company['j'] = date("t");
            break;
            case 'lastMonth'://上月
                if(date('m') == 1){
                    $m = 12;
                }else{
                    $m = date('m')-1;
                }
                $days = date('t', strtotime(date('Y').'-'.$m.'-1'));
                $company['j'] = $days;
            break;
            case 'week'://本周
                $company['j'] = 7;
            break;
            case 'lastWeek'://上周
                $company['j'] = 7;
            break;
            case 'today'://今天
                $company['j'] = 1;
            break;
            case 'yesterday'://昨天
                $company['j'] = 1;
            break;
            default ://自定义时间
                $start_time = $param['start_time'];
                $end_time = $param['end_time'];
                $company['start_time_y'] = $start_time_y = date('y',$start_time);
                $start_time_m = date('m',$start_time);
                $end_time_y = date('y',$end_time);
                $end_time_m = date('m',$end_time);
                if($end_time_y-$start_time_y == 0){//不跨年
                    if($end_time_m-$start_time_m > 0){//跨月
                        $company['type']='month_k';
                        $company['time_unit'] = '月';
                        $company['j'] = $end_time_m-$start_time_m+1;
                    }else{//不跨月
                        $company['type']='month';
                        $company['j'] = date("t");
                    }
                }else{//跨年
                    $start_time = date('Y-m-d',$start_time);
                    $end_time = date('Y-m-d',$end_time);
                    $monthNum = $this->getMonthNum($start_time,$end_time);
                    $company['type']='year_k';
                    $company['j'] = $monthNum+1;
                }
            break;
        }
        return $company;
    }
    function getMonthNum($start_m, $end_m){
        $date1 = explode('-',$start_m);
        $date2 = explode('-',$end_m);
        if($date1[1]<$date2[1]){ //判断月份大小，进行相应加或减
            $month_number= abs($date1[0] - $date2[0]) * 12 + abs($date1[1] - $date2[1]);
        }else{
            $month_number= abs($date1[0] - $date2[0]) * 12 - abs($date1[1] - $date2[1]);
        }
        return $month_number;
    }
    /**
     * 根据数据获取查询条件
     * @param  [type] $param [description]
     * @return [type]       [description]
     */
    function getParamByWhere($param,$type='')
    {
        $userModel = new \app\admin\model\User();
        $whereArr = array();
        if(empty($param['type']) && empty($param['start_time'])){
            $param['type'] = 'month';
        }
        //时间戳：客户跟进分析 、客户转化率分析
        if(!empty($param['start_time'])){
            $whereArr['create_time'] = array('between',array($param['start_time'],$param['end_time']));
            // $whereArr['create_time'] = array('between',array(date('Y-m-d',$param['start_time']),date('Y-m-d',$param['end_time'])));
        }else{
            $create_time = getTimeByType($param['type']);
            if ($create_time) {
                $whereArr['create_time'] = array('between',array($create_time[0],$create_time[1]));
            }
        }

        $map_user_ids = [];
        if ($param['user_id']) {
            $map_user_ids = array($param['user_id']);
        } else {
            if ($param['structure_id']) {
                $map_user_ids = $userModel->getSubUserByStr($param['structure_id'], 2);
            }
        }
        $perUserIds = $userModel->getUserByPer('bi', 'customer', 'read'); //权限范围内userIds
        $userIds = $map_user_ids ? array_intersect($map_user_ids, $perUserIds) : $perUserIds; //数组交集
        if($type && $type == 'record'){
            $whereArr['create_user_id'] = array('in',$userIds);
        }else{
            $whereArr['owner_user_id'] = array('in',$userIds);
        }
        return $whereArr;
    }
    /**
     * 根据自定义字段获取 下拉框数据
     */
    function getOptionByField($whereArr)
    {
        $setting = db('admin_field')->where($whereArr)->field('setting')->find();
        return explode(chr(10),$setting['setting']);
    }
    /**
     * 根据新增客户数排序
     * @param  [type] $whereArr [description]
     * @return [type]           [description]
     */
    function getSortByCount($whereArr)
    {
        $count = db('crm_customer')->group('owner_user_id')->field('owner_user_id,count(customer_id) as count')->order('count desc')->where($whereArr)->select();
        return $count;
    }
    /**
     * 获取成交周期
     * @return [type] [description]
     */
    function getWhereByCycle($whereArr)
    {
        $customer_ids = db('crm_customer')->field('customer_id')->where($whereArr)->select();
        //首次成交
        if(!empty($customer_ids)){
            $cycle_num = 0;
            $customer_num = 0;
            foreach ($customer_ids as $key => $value) {
                $where = array();
                $where['customer_id'] = array('eq',$value['customer_id']);
                $create_time = db('crm_customer')->field('create_time')->where($where)->find();
                $where['check_status'] = array('eq',2);
                $order_date = db('crm_contract')->where($where)->order('order_date asc')->field('order_date')->find();
                $cycle_time = ceil((strtotime($order_date['order_date'])-$create_time['create_time'])/86400);
                if($cycle_time < 0){
                    $cycle_num = $cycle_num+0;
                    $customer_num = $customer_num+0;
                }else{
                    $cycle_num += $cycle_time;
                    $customer_num ++;
                }
            }
            if($cycle_num==0 || $customer_num==0){
                $cycle = 0;
            }else{
                $cycle = ceil($cycle_num/$customer_num);
            }
            
        }
        return $cycle;
    }
}