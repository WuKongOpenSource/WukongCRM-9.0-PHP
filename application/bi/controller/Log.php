<?php
// +----------------------------------------------------------------------
// | Description: 商业智能-日志分析
// +----------------------------------------------------------------------
// | Author: Michael_xu | gengxiaoxu@5kcrm.com 
// +----------------------------------------------------------------------

namespace app\bi\controller;

use app\admin\controller\ApiCommon;
use think\Hook;
use think\Request;

class Log extends ApiCommon
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
            'allow'=>['statistics','excelexport']     
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
     * 日志统计列表
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
        $loglModel = new \app\bi\model\Log();
        $list = $loglModel->getStatistics($param) ? : [];
        return resultArray(['data'=>$list]);
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
        $field_list = [
            '0' => ['field' => 'realname','name' => '员工'],
            '1' => ['field' => 'count','name' => '填写数'],
            '2' => ['field' => 'unReadCont','name' => '接收人未读数'],
            '3' => ['field' => 'unCommentCount','name' => '未评论数'],
            '4' => ['field' => 'commentCount','name' => '已评论数'],
        ];
        // 文件名
        $file_name = '5kcrm_log_'.date('Ymd');
        $excelModel->dataExportCsv($file_name, $field_list, function($list) use ($param){
            $loglModel = new \app\bi\model\Log();
            if ($param['type']) {
                $timeArr = getTimeByType($param['type']);
                $param['start_time'] = $timeArr[0];
                $param['end_time'] = $timeArr[1];
            }            
            $list = $loglModel->getStatistics($param);
            return $list ? : [];
        });
    }    
}