<?php
// +----------------------------------------------------------------------
// | Description: CRM工作台
// +----------------------------------------------------------------------
// | Author: Michael_xu | gengxiaoxu@5kcrm.com 
// +----------------------------------------------------------------------

namespace app\crm\controller;

use app\admin\controller\ApiCommon;
use think\Hook;
use think\Request;

class Index extends ApiCommon
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
            'allow'=>['index','achievementdata','funnel','saletrend','search']            
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());        
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
    } 

    //月份数组
    protected $monthName = [
        '01'    =>  'january',
        '02'    =>  'february',
        '03'    =>  'march',
        '04'    =>  'april',
        '05'    =>  'may',
        '06'    =>  'june',
        '07'    =>  'july',
        '08'    =>  'august',
        '09'    =>  'september',
        '10'    =>  'october',
        '11'    =>  'november',
        '12'    =>  'december',
    ];   

    /**
     * CRM工作台（销售简报）
     * @author Michael_xu
     * @param 
     * @return 
     */
    public function index()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $userModel = new \app\admin\model\User();
        //员工IDS
        $map_user_ids = [];
        if ($param['user_id']) {
            $map_user_ids = $param['user_id'];
        } 
        if ($param['structure_id']) {
            $map_structure_user_ids = [];
            foreach ($param['structure_id'] as $v) {
                $map_structure_user_ids = $userModel->getSubUserByStr($v,2);
                if (!in_array($v,$map_structure_user_ids) && $map_structure_user_ids) {
                    $map_structure_user_ids = array_merge($map_structure_user_ids,$map_structure_user_ids);
                }
            } 
            if ($map_user_ids && $map_structure_user_ids) {
                $map_user_ids = array_merge($map_user_ids,$map_structure_user_ids);
            } elseif ($map_structure_user_ids) {
                $map_user_ids = $map_structure_user_ids;
            }
        }
        if (!$map_user_ids) $map_user_ids = getSubUserId(true);
        $perUserIds = getSubUserId(); //权限范围内userIds
        $userIds = $map_user_ids ? array_intersect($map_user_ids, $perUserIds) : $perUserIds; //数组交集
        $where['owner_user_id'] = array('in',$userIds);      
        if (!empty($param['type'])) {
            $between_time = getTimeByType($param['type']);
            $where['create_time'] = array('between',$between_time);
        } else {
            //自定义时间
            if (!empty($param['start_time'])) {
                $where['create_time'] = array('between',array($param['start_time'],$param['end_time']));
            }
        }
        $customerNum = 0; //录入客户
        $contactsNum = 0; //新增联系人
        $businessNum = 0; //新增商机
        $businessStatusNum = 0; //阶段变化的商机
        $contractNum = 0; //新增合同
        $recordNum = 0; //新增跟进记录
        $receivablesNum = 0; //新增回款

        $customerNum = db('crm_customer')->where($where)->count('customer_id');
        $contactsNum = db('crm_contacts')->where($where)->count('contacts_id');
        $businessNum = db('crm_business')->where($where)->count('business_id');
        $contractNum = db('crm_contract')->where($where)->count('contract_id');
        $receivablesNum = db('crm_receivables')->where($where)->count('receivables_id');

        unset($where['owner_user_id']);
        $where['create_user_id'] = array('in',$userIds);
        $recordNum = db('admin_record')->where($where)->count('record_id');

        $where['owner_user_id'] = array('in',$userIds);     
        unset($where['create_time']);
        $where['status_time'] = array('between',$between_time);
        $businessStatusNum = db('crm_business')->where($where)->count('business_id');

        $data = [];
        $data['customerNum'] = $customerNum;
        $data['contactsNum'] = $contactsNum;
        $data['businessNum'] = $businessNum;
        $data['contractNum'] = $contractNum;
        $data['recordNum'] = $recordNum;
        $data['receivablesNum'] = $receivablesNum;
        $data['businessStatusNum'] = $businessStatusNum;
        return resultArray(['data' => $data]);      
    }

    /**
     * 业绩指标
     * @author Michael_xu
     * @param 
     * @return 
     */
    public function achievementData()
    {
        $param = $this->param;
        $userModel = new \app\admin\model\User();
        $userInfo = $this->userInfo;
        $user_id = $param['user_id'] ? : ['-1'];
        $structure_id = $param['structure_id'] ? : ['-1'];
        $where = [];
        //员工IDS
        $map_user_ids = [];
        if ($param['user_id']) {
            $map_user_ids = $param['user_id'];
        } 
        if ($param['structure_id']) {
            $map_structure_user_ids = [];
            foreach ($param['structure_id'] as $v) {
                $map_structure_user_ids = $userModel->getSubUserByStr($v,2);
                if (!in_array($v,$map_structure_user_ids) && $map_structure_user_ids) {
                    $map_structure_user_ids = array_merge($map_structure_user_ids,$map_structure_user_ids);
                }
            } 
            if ($map_user_ids && $map_structure_user_ids) {
                $map_user_ids = array_merge($map_user_ids,$map_structure_user_ids);
            } elseif ($map_structure_user_ids) {
                $map_user_ids = $map_structure_user_ids;
            }
        }
        if (!$map_user_ids) $map_user_ids = getSubUserId(true);
        $status = $param['status'] ? : 1; //1合同目标2回款目标    

        $perUserIds = getSubUserId(); //权限范围内userIds
        $userIds = $map_user_ids ? array_intersect($map_user_ids, $perUserIds) : array($userInfo['id']); //数组交集
        $where['owner_user_id'] = array('in',$userIds);
        if (!empty($param['type'])) {
            $between_time = getTimeByType($param['type']);
            $start_time = $between_time[0];
            $end_time = $between_time[1];
        } else {
            //自定义时间
            $start_time = $param['start_time'] ? : strtotime(date('Y-01-01',time()));
            $end_time = $param['end_time'] ? strtotime(date('Y-m-01', $param['end_time']) . ' +1 month -1 day') : strtotime(date('Y-m-01', time()) . ' +1 month -1 day');
            $between_time = array($start_time,$end_time);
        }
        //合同金额
        $where_contract = $where;
        $where_contract['order_date'] = array('between',[date('Y-m-d',$between_time[0]),date('Y-m-d',$between_time[1])]);
        $where_contract['check_status'] = 2; //审核通过
        $contractMoney = db('crm_contract')->where($where_contract)->sum('money');

        //回款金额
        
        $where_receivables = $where;
        $where_contract['return_time'] = array('between',[date('Y-m-d',$between_time[0]),date('Y-m-d',$between_time[1])]);
        $where_receivables['check_status'] = 2; //审核通过
        $receivablesMoney = db('crm_receivables')->where($where_receivables)->sum('money');

        //业绩目标
        $where_achievement = [];
        $where_achievement['status'] = $status;
        //获取时间段包含年份
        $year = getYearByTime($start_time, $end_time);
        $where_achievement['year'] = array('in',$year);
        if(empty($param['user_id']) && empty($param['structure_id'])){
            $where_achievement_str = '( `obj_id` IN ('.implode(',',$map_user_ids).') AND `type` = 3 )';
        }else{
            $where_achievement_str = '(( `obj_id` IN ('.implode(',',$user_id).') AND `type` = 3 ) OR ( `obj_id` IN ('.implode(',',$structure_id).') AND `type` = 2 ) )';
        }
        $achievement = db('crm_achievement')->where($where_achievement)->where($where_achievement_str)->select();
        $achievementMoney = 0.00;
        //获取需要查询的月份
        $month = getmonthByTime($start_time, $end_time);
        foreach ($achievement as $k=>$v) {
            foreach ($month as $key=>$val) {
                if ($v['year'] == $key) {
                    foreach ($val as $key1=>$val1) {
                        $achievementMoney += $v[$this->monthName[$val1]];                      
                    }
                } 
            }
        }
        $data = [];
        $data['contractMoney'] = $contractMoney ? : '0.00';
        $data['receivablesMoney'] = $receivablesMoney ? : '0.00';
        $data['achievementMoney'] = $achievementMoney ? : '0.00';
        //完成率
        $rate = 0.00;
        if ($status == 1) {
            $rate = $achievementMoney ? round(($contractMoney/$achievementMoney),4) : 0.00;
        } else {
            $rate = $achievementMoney ? round(($receivablesMoney/$achievementMoney),4) : 0.00;
        }
        $data['rate'] = $rate *100;
        return resultArray(['data' => $data]);
    }

    /**
     * 销售漏斗
     * @author Michael_xu
     * @param 
     * @return
     */
    public function funnel()
    {
        $businessModel = new \app\crm\model\Business();
        $userModel = new \app\admin\model\User();
        $param = $this->param;
        $userInfo = $this->userInfo;
        //员工IDS
        $map_user_ids = [];
        if ($param['user_id']) {
            $map_user_ids = $param['user_id'];
        } 
        if ($param['structure_id']) {
            $map_structure_user_ids = [];
            foreach ($param['structure_id'] as $v) {
                $map_structure_user_ids = $userModel->getSubUserByStr($v,2);
                if (!in_array($v,$map_structure_user_ids) && $map_structure_user_ids) {
                    $map_structure_user_ids = array_merge($map_structure_user_ids,$map_structure_user_ids);
                }
            } 
            if ($map_user_ids && $map_structure_user_ids) {
                $map_user_ids = array_merge($map_user_ids,$map_structure_user_ids);
            } elseif ($map_structure_user_ids) {
                $map_user_ids = $map_structure_user_ids;
            }
        }
        if (!$map_user_ids) $map_user_ids = getSubUserId(true);
        unset($param['user_id']);
        unset($param['structure_id']);
        $perUserIds = getSubUserId(); //权限范围内userIds
        $userIds = $map_user_ids ? array_intersect($map_user_ids, $perUserIds) : array($userInfo['id']); //数组交集  
        $param['userIds'] = $userIds ? : [];        
        $param['end_time'] = $param['end_time']?$param['end_time']+3600*24:'';
        $list = $businessModel->getFunnel($param);
        return resultArray(['data' => $list]);
    }  

    /**
     * 销售趋势
     * @return [type] [description]
     */
    public function saletrend()
    {
        $receivablesModel = new \app\crm\model\Receivables();
        $userModel = new \app\admin\model\User();
        $biCustomerModel = new \app\bi\model\Customer();
        
        $param = $this->param;
        $userInfo = $this->userInfo;
        //员工IDS
        $map_user_ids = [];
        if ($param['user_id']) {
            $map_user_ids = $param['user_id'];
        } 
        if ($param['structure_id']) {
            $map_structure_user_ids = [];
            foreach ($param['structure_id'] as $v) {
                $map_structure_user_ids = $userModel->getSubUserByStr($v,2);
                if (!in_array($v,$map_structure_user_ids) && $map_structure_user_ids) {
                    $map_structure_user_ids = array_merge($map_structure_user_ids,$map_structure_user_ids);
                }
            } 
            if ($map_user_ids && $map_structure_user_ids) {
                $map_user_ids = array_merge($map_user_ids,$map_structure_user_ids);
            } elseif ($map_structure_user_ids) {
                $map_user_ids = $map_structure_user_ids;
            }
        }
        if (!$map_user_ids) $map_user_ids = getSubUserId(true);
        $perUserIds = getSubUserId(); //权限范围内userIds
        $userIds = $map_user_ids ? array_intersect($map_user_ids, $perUserIds) : array($userInfo['id']); //数组交集

        if(empty($param['type']) && empty($param['start_time'])){
            $param['type'] = 'month';
        }
        $company = $biCustomerModel->getParamByCompany($param);
        $list = array();
        $totlaContractMoney = '0.00';
        $totlaReceivablesMoney = '0.00';
        $biContractModel = new \app\bi\model\Contract();
        $receivablesModel = new \app\bi\model\Receivables();        
        for ($i=1; $i <= $company['j']; $i++) { 
            $whereArr = [];
            $item = array();
            //时间段
            $timeArr = $biCustomerModel->getStartAndEnd($param,$company['year'],$i);
            $item['type'] = $timeArr['type'];
            $day = $timeArr['day']?$timeArr['day']:'1';
            $start_time = strtotime($timeArr['year'].'-'.$timeArr['month'].'-'.$day);
            $next_day = $timeArr['next_day'] ? $timeArr['next_day']-1 : '1';
            $end_time = strtotime($timeArr['next_year'].'-'.$timeArr['next_month'].'-'.$next_day);
            $create_time = [];
            if ($start_time && $end_time) {
                $create_time = array('between',array(date('Y-m-d',$start_time),date('Y-m-d',$end_time)));
            }
            $whereArr['order_date'] = $create_time;
            $whereArr['check_status'] = array('eq',2);
            $whereArr['owner_user_id'] = array('in',$userIds);
            $totlaContractMoney += $item['contractMoney'] = $biContractModel->getDataMoney($whereArr);
            unset($whereArr['order_date']);
            $whereArr['return_time'] = $create_time;
            $totlaReceivablesMoney += $item['receivablesMoney'] = $receivablesModel->getDataMoney($whereArr);
            $list[] = $item;
        }
        $datas['list'] = $list;
        $datas['totlaContractMoney'] = $totlaContractMoney ? : '0.00';
        $datas['totlaReceivablesMoney'] = $totlaReceivablesMoney ? : '0.00';
        return resultArray(['data' => $datas]);
    }

    /**
     * 回款计划提醒
     * @author Michael_xu
     * @param day 最近7天 15天...
     * @return 
     */
    public function receivablesPlan()
    {
        $param = $this->param;
        $where = [];
        //员工IDS
        $map_user_ids = [];
        if ($param['user_id']) {
            $map_user_ids[] = $param['user_id'];
        } elseif ($param['structure_id']) {
            $map_user_ids = $userModel->getSubUserByStr($param['structure_id']);
        }

        $perUserIds = $userModel->getUserByPer(); //权限范围内userIds
        $userIds = $map_user_ids ? array_intersect($map_user_ids, $perUserIds) : array($userInfo['id']); //数组交集
        $where['owner_user_id'] = array('in',$userIds);        

        //已逾期
        $return_date = array('< time',date('Y-m-d',time()));
        $where['status'] = 0;
        if ($param['day']) {
            $return_date = array('between time',array(date('Y-m-d',time()),date('Y-m-d',strtotime(date('Y-m-d',time()))+86399+(86400*(int)$param['day']))));
        }
        $where['return_date'] = $return_date;
        $planList = db('crm_receivables_plan')->where($where)->select();

        return resultArray(['data' => $planList]);
    }

    /**
     * 待跟进客户
     * @author Michael_xu
     * @param day 最近3天 7天...
     * @return 
     */
    public function noFollowUp()
    {
        $param = $this->param;
        $where = [];
        //员工IDS
        $map_user_ids = [];
        if ($param['user_id']) {
            $map_user_ids[] = $param['user_id'];
        } elseif ($param['structure_id']) {
            $map_user_ids = $userModel->getSubUserByStr($param['structure_id']);
        }

        $perUserIds = $userModel->getUserByPer(); //权限范围内userIds
        $userIds = $map_user_ids ? array_intersect($map_user_ids, $perUserIds) : array($userInfo['id']); //数组交集
        $where['owner_user_id'] = array('in',$userIds);        

        $day = (int)$param['day'] ? : 3;
        $where['next_time'] = array('between',array(strtotime(date('Y-m-d',time())),strtotime(date('Y-m-d',time()))+86399+(86400*(int)$param['day'])));
        $customerList = db('crm_customer')->where($where)->select();
        return resultArray(['data' => $customerList]);
    }

    /**
     * 客户名称、联系人姓名、联系人手机号查询
     * @author Michael_xu
     * @param 
     * @return 
     */
    public function search()
    {
        $param = $this->param;
        $page = $param['page'] ? : 1;
        $limit = $param['limit'] ? : 15;
        $types = $param['types'] ? : '';
        $userModel = new \app\admin\model\User();

        //省数组
        $address_arr = array('北京','天津','河北','山西','内蒙古自治区','辽宁','吉林','黑龙江','上海','江苏','浙江','安徽','福建','江西','山东','河南','湖北','湖南','广东','广西壮族自治区','海南','重庆','四川','贵州','云南','西藏自治区','陕西','甘肃','青海','宁夏回族自治区','新疆维吾尔自治区','台湾','香港特别行政区','澳门特别行政区',);
        $addr_arr = array('北京','天津','河北省','山西省','内蒙古自治区','辽宁省','吉林省','黑龙江省','上海','江苏省','浙江省','安徽省','福建省','江西省','山东省','河南省','湖北省','湖南省','广东省','广西壮族自治区','海南省','重庆','四川省','贵州省','云南省','西藏自治区','陕西省','甘肃省','青海省','宁夏回族自治区','新疆维吾尔自治区','台湾省','香港特别行政区','澳门特别行政区',);
        $city_arr = array('石家庄','唐山','秦皇岛','邯郸','邢台','保定','张家口','承德','沧州','廊坊','衡水','太原','大同','阳泉','长治','晋城','朔州','晋中','运城','忻州','临汾','吕梁','呼和浩特','包头','乌海','赤峰','通辽','鄂尔多斯','呼伦贝尔','巴彦淖尔','乌兰察布','兴安盟','锡林郭勒盟','阿拉善盟','沈阳','大连','鞍山','抚顺','本溪','丹东','锦州','营口','阜新','辽阳','盘锦','铁岭','朝阳','葫芦岛','长春','吉林','四平','辽源','通化','白山','松原','白城','延边朝鲜族自治州','哈尔滨','齐齐哈尔','鸡西','鹤岗','双鸭山','大庆','伊春','佳木斯','七台河','牡丹江','黑河','绥化','大兴安岭地区','南京','无锡','徐州','常州','苏州','南通','连云港','淮安','盐城','扬州','镇江','泰州','宿迁','杭州','宁波','温州','嘉兴','湖州','绍兴','金华','衢州','舟山','台州','丽水','合肥','芜湖','蚌埠','淮南','马鞍山','淮北','铜陵','安庆','黄山','滁州','阜阳','宿州','巢湖','六安','亳州','池州','宣城','福州','厦门','莆田','三明','泉州','漳州','南平','龙岩','宁德','南昌','景德镇','萍乡','九江','新余','鹰潭','赣州','吉安','宜春','抚州','上饶','济南','青岛','淄博','枣庄','东营','烟台','潍坊','济宁','泰安','威海','日照','莱芜','临沂','德州','聊城','滨州','荷泽','郑州','开封','洛阳','平顶山','安阳','鹤壁','新乡','焦作','濮阳','许昌','漯河','三门峡','南阳','商丘','信阳','周口','驻马店','武汉','黄石','十堰','宜昌','襄樊','鄂州','荆门','孝感','荆州','黄冈','咸宁','随州','恩施土家族苗族自治州','长沙','株洲','湘潭','衡阳','邵阳','岳阳','常德','张家界','益阳','郴州','永州','怀化','娄底','湘西土家族苗族自治州','广州','韶关','深圳','珠海','汕头','佛山','江门','湛江','茂名','肇庆','惠州','梅州','汕尾','河源','阳江','清远','东莞','中山','潮州','揭阳','云浮','南宁','柳州','桂林','梧州','北海','防城港','钦州','贵港','玉林','百色','贺州','河池','来宾','崇左','海口','三亚','成都','自贡','攀枝花','泸州','德阳','绵阳','广元','遂宁','内江','乐山','南充','眉山','宜宾','广安','达州','雅安','巴中','资阳','阿坝藏族羌族自治州','甘孜藏族自治州','凉山彝族自治州','贵阳','六盘水','遵义','安顺','铜仁地区','黔西南布依族苗族自治州','毕节地区','黔东南苗族侗族自治州','黔南布依族苗族自治州','昆明','曲靖','玉溪','保山','昭通','丽江','思茅','临沧','楚雄彝族自治州','红河哈尼族彝族自治州','文山壮族苗族自治州','西双版纳傣族自治州','大理白族自治州','德宏傣族景颇族自治州','怒江傈僳族自治州','迪庆藏族自治州','拉萨','昌都地区','山南地区','日喀则地区','那曲地区','阿里地区','林芝地区','西安','铜川','宝鸡','咸阳','渭南','延安','汉中','榆林','安康','商洛','兰州','嘉峪关','金昌','白银','天水','武威','张掖','平凉','酒泉','庆阳','定西','陇南','临夏回族自治州','甘南藏族自治州','西宁','海东地区','海北藏族自治州','黄南藏族自治州','海南藏族自治州','果洛藏族自治州','玉树藏族自治州','海西蒙古族藏族自治州','银川','石嘴山','吴忠','固原','中卫','乌鲁木齐','克拉玛依','吐鲁番地区','哈密地区','昌吉回族自治州','博尔塔拉蒙古自治州','巴音郭楞蒙古自治州','阿克苏地区','克孜勒苏柯尔克孜自治州','喀什地区','和田地区','伊犁哈萨克自治州','塔城地区','阿勒泰地区','省直辖行政单位',);   

        $un_arr = ['中国','公司','有限公司','有限责任公司','股份有限公司'];
        $name = $param['name'] ? trim($param['name']) : '';
        if (in_array($name,$address_arr) || in_array($name,$addr_arr) || in_array($name,$city_arr) || in_array($name,$un_arr)) {
            return resultArray(['error' => '查询条件不符合规则']);
        }
        if ($types == 'crm_customer') {
            if (!$param['name'] && !$param['mobile'] && !$param['telephone']) return resultArray(['error' => '查询条件不能为空']);
            $resWhere = '';
            if ($param['name']) {
                $resWhere .= " `name` like '%".$param['name']."%' ";
            } elseif ($param['mobile']) {
                if ($resWhere) $resWhere .= 'OR';
                $resWhere .= " `mobile` = '".$param['mobile']."'";
            } elseif ($param['telephone']) {
                if ($resWhere) $resWhere .= 'OR';
                $resWhere .= " `telephone` = '".$param['telephone']."' ";
            }
            $dataList = db('crm_customer')
                            ->where($resWhere)
                            ->field('name,customer_id,owner_user_id')
                            ->limit(($page-1)*$limit, $limit)
                            ->select();
            $dataCount = db('crm_customer')
                            ->where($resWhere)
                            ->count();
            foreach ($dataList as $k=>$v) {
                $dataList[$k]['owner_user_id_info'] = isset($v['owner_user_id']) ? $userModel->getUserById($v['owner_user_id']) : [];
            }
        } elseif ($types == 'crm_contacts') {
            if (!$param['name'] && !$param['customer_name'] && !$param['telephone'] && !$param['mobile']) return resultArray(['error' => '查询条件不能为空']);
            if (in_array($param['customer_name'],$address_arr) || in_array($param['customer_name'],$addr_arr) || in_array($param['customer_name'],$city_arr) || in_array($param['customer_name'],$un_arr)) {
                return resultArray(['error' => '查询条件不符合规则']);
            }            

            $resWhere = '';
            if ($param['name']) {
                $resWhere .= " `contacts`.`name` like '%".$param['name']."%' ";
            } elseif ($param['mobile']) {
                if ($resWhere) $resWhere .= 'OR';
                $resWhere .= " `contacts`.`mobile` = '".$param['mobile']."' ";
            } elseif ($param['telephone']) {
                if ($resWhere) $resWhere .= 'OR';
                $resWhere .= " `contacts`.`telephone` = '".$param['telephone']."' ";
            } elseif ($param['customer_name']) {
                if ($resWhere) $resWhere .= 'OR';
                $resWhere .= " `customer`.`name` like '%".$param['customer_name']."%'";
            }       
            $dataList = db('crm_contacts')
                            ->alias('contacts')
                            ->join('__CRM_CUSTOMER__ customer','contacts.customer_id = customer.customer_id','LEFT')            
                            ->where($resWhere)
                            ->field('contacts.name,contacts.contacts_id,contacts.customer_id,contacts.owner_user_id,customer.name as customer_name')
                            ->page($page, $limit)
                            ->select();
            $dataCount = db('crm_contacts')
                            ->alias('contacts')
                            ->join('__CRM_CUSTOMER__ customer','contacts.customer_id = customer.customer_id','LEFT')            
                            ->where($resWhere)
                            ->count();
            foreach ($dataList as $k=>$v) {
                $dataList[$k]['owner_user_id_info'] = isset($v['owner_user_id']) ? $userModel->getUserById($v['owner_user_id']) : [];
            }
        } elseif ($types == 'crm_leads') {
            if (!$param['name'] && !$param['telephone'] && !$param['mobile']) return resultArray(['error' => '查询条件不能为空']);
            $resWhere = '';
            if ($param['name']) {
                $resWhere .= " `name` like '%".$param['name']."%' ";
            } elseif ($param['mobile']) {
                if ($resWhere) $resWhere .= 'OR';
                $resWhere .= " `mobile` = '".$param['mobile']."'";
            } elseif ($param['telephone']) {
                if ($resWhere) $resWhere .= 'OR';
                $resWhere .= " `telephone` = '".$param['telephone']."'";
            }            
            $dataList = db('crm_leads')
                        ->where($resWhere)
                        ->field('name,leads_id,owner_user_id')
                        ->page($page, $limit)
                        ->select();
            $dataCount = db('crm_leads')
                            ->where($resWhere)
                            ->count();
            foreach ($dataList as $k=>$v) {
                $dataList[$k]['owner_user_id_info'] = isset($v['owner_user_id']) ? $userModel->getUserById($v['owner_user_id']) : [];
            }
        }        
        $data = [];
        $data['dataList'] = $dataList ? : [];
        $data['dataCount'] = $dataCount ? : 0; 
        return resultArray(['data' => $data]);     
    }           
}