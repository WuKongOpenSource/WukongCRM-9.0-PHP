<?php
// +----------------------------------------------------------------------
// | Description: 商业智能-商机分析
// +----------------------------------------------------------------------
// | Author: Michael_xu | gengxiaoxu@5kcrm.com 
// +----------------------------------------------------------------------

namespace app\bi\controller;

use app\admin\controller\ApiCommon;
use think\Hook;
use think\Request;

class Business extends ApiCommon
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
            'allow'=>['funnel']            
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());        
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
    } 
  
    /**
     * 销售漏斗
     * @author Michael_xu
     * @param 
     * @return
     */
    public function funnel()
    {
        if (!checkPerByAction('bi', 'business' , 'read')) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }
        $businessModel = new \app\crm\model\Business();
        $userModel = new \app\admin\model\User();
        $param = $this->param;

        //查询员工IDS
        $map_user_ids = [];
        if ($param['user_id']) {
            $map_user_ids = [$param['user_id']];
        } else {
            if ($param['structure_id']) {
                $map_user_ids = $userModel->getSubUserByStr($param['structure_id'], 2);
            }
        }
        $perUserIds = $userModel->getUserByPer('bi', 'business', 'read'); //权限范围内userIds
        $userIds = $map_user_ids ? array_intersect($map_user_ids, $perUserIds) : $perUserIds; //数组交集
        $param['userIds'] = $userIds ? : [];
		$param['start_time'] = $param['start_time'];
        $param['end_time'] = $param['end_time'] ? $param['end_time']+86399 : time();
        $list = $businessModel->getFunnel($param);
        return resultArray(['data' => $list]);
    }  
}
