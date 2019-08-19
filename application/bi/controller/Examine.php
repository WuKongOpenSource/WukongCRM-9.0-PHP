<?php
// +----------------------------------------------------------------------
// | Description: 商业智能-审核统计
// +----------------------------------------------------------------------
// | Author: zhi | zhijunfu@5kcrm.com 
// +----------------------------------------------------------------------

namespace app\bi\controller;

use app\admin\controller\ApiCommon;
use think\Hook;
use think\Request;
use think\Db;

class Examine extends ApiCommon
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
            'allow'=>['statistics','index','excelexport']            
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());        
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
        if (!checkPerByAction('bi', 'oa', 'read')) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }        
    }

    /**
     * 审核统计列表
     * @return 
     */
    public function statistics()
    {  
        $param = $this->param;
        if ($param['type']) {
            $timeArr = getTimeByType($param['type']);
            $param['start_time'] = $timeArr[0];
            $param['end_time'] = $timeArr[1];
        }
        $examineModel = new \app\bi\model\Examine();
        $list = $examineModel->getStatistics($param) ? : [];
        return resultArray(['data'=>$list]);
    }

    /**
     * 审核统计详情列表
     * @return 
     */    
    public function index()
    {       
        $examineModel = new \app\oa\model\Examine();
        $param = $this->param;
        $user_id = $param['user_id'];
        $category_id = $param['category_id'];
        $type = $param['type'];
        if (!$user_id || !$category_id) {
            return resultArray(['error'=>'参数错误']);
        }
        //时间
        if ($type) {
            $timeArr = getTimeByType($type);
            $start_time = $timeArr[0];
            $end_time = $timeArr[1];
        } else {
            $start_time = $param['start_time'] ? : strtotime(date('Y-m-d',time()));
            $end_time = $param['end_time'] ? : strtotime(date('Y-m-d',time()))+86399;            
        }
        $create_time = array('between',array($start_time,$end_time));        

        $where = [];
        $where['create_user_id'] = $user_id;
        $where['check_status'] = 2;
        $where['create_time'] = $create_time;
        $where['category_id'] = $category_id;
        $sumData = 0;
        $categoryName = '普通审批';
        switch ($category_id) {
            case '2' : 
                $sumData = db('oa_examine')->where($where)->sum('duration');
                $categoryName = '请假审批';
                break;
            case '3' : 
                $sumData = db('oa_examine')->where($where)->sum('duration');
                $categoryName = '出差审批';
                break;
            case '4' : 
                $sumData = db('oa_examine')->where($where)->sum('duration');
                $categoryName = '加班审批';
                break; 
            case '5' : 
                $sumData = db('oa_examine')->where($where)->sum('money');
                $categoryName = '差旅报销';
                break; 
            case '6' : 
                $sumData = db('oa_examine')->where($where)->sum('money');
                $categoryName = '借款申请';
                break;
            default :
                $categoryName = db('oa_examine_category')->where(['category_id' => $category_id])->value('title');
                break;     
        } 
        unset($where['create_time']);
        unset($where['create_user_id']);
        unset($where['create_user_id']);
        $where['check_status'] = 'all';
        $where['page'] = $param['page'];
        $where['limit'] = $param['limit'];
        $where['user_id'] = $user_id;
        $where['between_time'] = array($start_time,$end_time);
        $list = $examineModel->getDataList($where);

        $data = [];
        $data['list'] = $list ? : [];
        $data['sumData'] = $sumData;
        $data['categoryName'] = $categoryName;
        return resultArray(['data'=>$data]);
    }

    /**
     * 统计导出
     * @author Michael_xu
     * @param 
     * @return
     */
    public function excelExport()
    {
        $param = $this->param;
        $excelModel = new \app\admin\model\Excel();
        // 导出的字段列表
        $category_list = db('oa_examine_category')->where(['status' => 1,'is_deleted' => ['neq',1]])->field('title,category_id')->select();
        $field_list = [];
        $field_list[0]['name'] = '员工';
        $field_list[0]['field'] = 'realname';
        $i = 1;
        foreach ($category_list as $k=>$v) {
            $field_list[$i]['name'] = strstr($v['title'],'审批') ? str_replace('审批','次数',$v['title']) : $v['title'].'次数';
            $field_list[$i]['field'] = 'count_'.$v['category_id'];
            $i++;
        }
        // 文件名
        $file_name = '5kcrm_examine_'.date('Ymd');
        $excelModel->dataExportCsv($file_name, $field_list, function($list) use ($param){
            $examineModel = new \app\bi\model\Examine();
            if ($param['type']) {
                $timeArr = getTimeByType($param['type']);
                $param['start_time'] = $timeArr[0];
                $param['end_time'] = $timeArr[1];
            }            
            $list = $examineModel->getStatistics($param);
            return $list['userList'];
        });
    }
}
