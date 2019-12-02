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

    /**
     * 获取转化客户信息 
     * @author zhi
     * @param $whereArr 
     * @return
     */
    function getWhereByList($where)
    {
        $userModel = new \app\admin\model\User();
        $prefix = config('database.prefix');
        $sql = $this->alias('a')
            ->field([
                'a.customer_id',
                'a.name',
                'a.owner_user_id',
                'a.create_user_id',
                'a.industry',
                'a.source',
                'a.create_time',
                'b.name' => 'contract_name',
                'b.money' => 'contract_money',
                'b.order_date' => 'order_time',
                'b.contract_id' => 'contract_id',
                'c.r_money'
            ])
            ->join(
                "(
                    SELECT
                        `contract_id`,
                        `customer_id`,
                        `name`,
                        `money`,
                        `create_time`,
                        `order_date`
                    FROM 
                        {$prefix}crm_contract 
                    WHERE 
                        `contract_id` IN (
                            SELECT 
                                MIN(`contract_id`) as `contract_id`
                            FROM 
                                `{$prefix}crm_contract` 
                            WHERE 
                                `create_time` > 1572537600
                                AND `check_status` = 2
                            GROUP BY
                                `customer_id`
                        )
                ) b",
                'b.customer_id = a.customer_id',
                'LEFT'
            )
            ->join(
                "(
                    SELECT
                        `contract_id`,
                        SUM(`money`) AS `r_money`
                    FROM 
                        `{$prefix}crm_receivables`
                    GROUP BY
                        `contract_id`
                ) c",
                'c.contract_id = b.contract_id',
                'LEFT'
            )
            ->where([
                'a.create_time' => $where['create_time'],
                'a.owner_user_id' => $where['owner_user_id'],
                'a.deal_status' => $where['deal_status'],
            ])
            ->fetchSql()
            ->order(['r_money' => 'DESC'])
            ->limit(50)
            ->select();
        $list = queryCache($sql);
        foreach ($list as &$val) {
            $val['create_time'] = date('Y-m-d',$val['create_time']);
            $owner_user = $userModel->getUserById($val['owner_user_id']);
            $val['owner_realname'] = $owner_user['realname'];
            $create_user = $userModel->getUserById($val['create_user_id']);
            $val['create_realname'] = $create_user['realname'];
        }
        return $list;
    }

	/**
	 * 根据条件获取开始、结束时间
     * @author zhi
	 * @param   $type 
	 * @param   $year 
	 * @param   $i    
	 * @return        
	 */
	function getStartAndEnd($param,$year,$i)
    {
        $timeArr = array();
        switch($param['type']) {
            case 'year'://本年度
            case 'lastYear'://上年度
                $type = $year.'-'.$i;
                $whereTime = $this->getMonthArray(strtotime($type.'-01'));
                break;
            case 'quarter'://本季度
                $timeType = getTimeByType($param['type']);
                $month = date('m',$timeType[0]);
                if ($month+$i <= 13) {
                    $type = $year.'-'.($month+$i-1);
                } else {
                    $type = ($year+1).'-'.'01';
                }
                $whereTime = $this->getMonthArray(strtotime($type.'-01'));
                break;
            case 'lastQuarter'://上季度
                $timeType = getTimeByType($param['type']);
                $month = date('m',$timeType[0]);
                $type = $year.'-'.($month+$i-1);
                $whereTime = $this->getMonthArray(strtotime($type.'-01'));                                                       
                break;
            case 'month'://本月
                $type = $year.'-'.date('m').'-'.$i;
                $dateArr = getDateRange(strtotime($type));
                $whereTime = [$dateArr['sdate'],$dateArr['edate']];
                break;
            case 'lastMonth'://上月
                $timeType = getTimeByType($param['type']);
                $type = date('Y-m',$timeType['0']).'-'.$i;
                $dateArr = getDateRange(strtotime($type));
                $whereTime = [$dateArr['sdate'],$dateArr['edate']];
                break;
            case 'week'://本周
            case 'lastWeek'://上周
                $timeType = getTimeByType($param['type']);
                $type = date('Y-m-d',$timeType['0']+($i-1)*86400);
                $dateArr = getDateRange(strtotime($type));
                $whereTime = [$dateArr['sdate'],$dateArr['edate']];
                break;
            case 'today'://今天
            case 'yesterday'://昨天
                $whereTime = getTimeByType($param['type']);
                $type = date('Y-m-d',$whereTime[0]);
                break;
            default ://自定义时间              
                $subDay = ceil(($param['end_time']-$param['start_time'])/86400);
                if ($subDay > 31) {
                    $param['type'] = 'year';
                    $res = $this->getStartAndEnd($param,$year,$i);
                    $whereTime[0] = $res['start_time']; 
                    $whereTime[1] = $res['end_time']; 
                    $type = $res['type']; 
                } else {
                    $type = date('Y-m-d',$param['start_time']+($i-1)*86400);
                    $dateArr = getDateRange(strtotime($type));
                    $whereTime = [$dateArr['sdate'],$dateArr['edate']];                    
                }
                break;
        }
        $timeArr['start_time'] = $whereTime[0];
        $timeArr['end_time'] = $whereTime[1];                
        $timeArr['type'] = $type;      
        return $timeArr;
    }
    
    /**
     * 根据条件获取单位
     * @author zhi
     * @param 
     * @return
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
                if (date('m') == 1) {
                    $m = 12;
                } else {
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
                $whereTime[0] = $param['start_time'];
                $whereTime[1] = $param['end_time'];
                $subDay = ceil(($param['end_time']-$param['start_time'])/86400);
                if ($subDay > 31) {
                    $company['j'] = 12;
                } else {
                    $company['j'] = $subDay;
                }
                break;
        }
        return $company;
    }

    function getMonthNum($start_m, $end_m){
        $date1 = explode('-',$start_m);
        $date2 = explode('-',$end_m);
        if ($date1[1] < $date2[1]) { //判断月份大小，进行相应加或减
            $month_number= abs($date1[0] - $date2[0]) * 12 + abs($date1[1] - $date2[1]);
        } else {
            $month_number= abs($date1[0] - $date2[0]) * 12 - abs($date1[1] - $date2[1]);
        }
        return $month_number;
    }

    /**
     * 根据数据获取查询条件
     * @author zhi
     * @param 
     * @return
     */
    function getParamByWhere($param,$type='')
    {
        $userModel = new \app\admin\model\User();
        $whereArr = array();
        if (empty($param['type']) && empty($param['start_time'])) {
            $param['type'] = 'month';
        }
        //时间戳：客户跟进分析 、客户转化率分析
        if (!empty($param['start_time'])) {
            $whereArr['create_time'] = array('between',array($param['start_time'],$param['end_time']));
        } else {
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
        if ($type && $type == 'record') {
            $whereArr['create_user_id'] = array('in',$userIds);
        } else {
            $whereArr['owner_user_id'] = array('in',$userIds);
        }
        return $whereArr;
    }

    /**
     * 根据自定义字段获取 下拉框数据
     * @author zhi
     * @param 
     * @return
     */
    function getOptionByField($whereArr)
    {
        $setting = db('admin_field')->where($whereArr)->field('setting')->find();
        return explode(chr(10),$setting['setting']);
    }

    /**
     * 根据新增客户数排序
     * @author zhi
     * @param 
     * @return
     */
    function getSortByCount($whereArr)
    {
        $count = db('crm_customer')->group('owner_user_id')->field('owner_user_id,count(customer_id) as count')->order('count desc')->where($whereArr)->select();
        echo db('crm_customer')->getLastSql();die();
        return $count;
    }

    /**
     * 获取成交周期
     * @author zhi
     * @param 
     * @return
     */
    function getWhereByCycle($whereArr)
    {
        $customerList = db('crm_customer')->field('customer_id,create_time,deal_time')->where($whereArr)->select();
        //首次成交
        if (!empty($customerList)) {
            $cycle_num = 0;
            $customer_num = 0;
            foreach ($customerList as $key => $value) {
                $cycle_time = 0;
                $where = array();
                $where['customer_id'] = $value['customer_id'];
                $create_time = $value['create_time'];
                $where['check_status'] = 2;
                $contractInfo = db('crm_contract')->where($where)->field('order_date,create_time')->order('order_date asc')->find();
                if ($contractInfo['order_date']) {
                    $cycle_time = ceil((strtotime($order_date)-$create_time)/86400);
                } else {
                    $cycle_time = ceil(($value['deal_time']-$create_time)/86400);
                }
                if ($cycle_time > 0) {
                    $cycle_num += $cycle_time;
                    $customer_num ++;
                }
            }
            if ($cycle_num == 0 || $customer_num == 0) {
                $cycle = 0;
            } else {
                $cycle = ceil($cycle_num/$customer_num);
            }
        }
        return $cycle;
    }

    /**
     * 根据时间获取该时间所在月份开始结束时间
     * @author zhi
     * @param     
     * @return        
     */ 
    public function getMonthArray($time)
    {
        $start_time = strtotime(date('Y-m-01',$time));
        $monthDay = getmonthdays($time);
        $end_time = strtotime(date('Y-m-'.$monthDay,$time))+86399;
        return array($start_time,$end_time);
    }   
}