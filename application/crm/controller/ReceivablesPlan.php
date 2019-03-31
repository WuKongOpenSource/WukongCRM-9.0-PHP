<?php
// +----------------------------------------------------------------------
// | Description: 回款计划
// +----------------------------------------------------------------------
// | Author: Michael_xu | gengxiaoxu@5kcrm.com 
// +----------------------------------------------------------------------

namespace app\crm\controller;

use app\admin\controller\ApiCommon;
use think\Hook;
use think\Request;

class ReceivablesPlan extends ApiCommon
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
            'allow'=>['index','save','read','update']            
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());        
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
    } 

    /**
     * 回款计划列表
     * @author Michael_xu
     * @return 
     */
    public function index()
    {
        $receivablesPlanModel = model('ReceivablesPlan');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];
        $data = $receivablesPlanModel->getDataList($param);       
        return resultArray(['data' => $data]);
    }

    /**
     * 添加回款计划
     * @author Michael_xu
     * @param 
     * @return 
     */
    public function save()
    {
        $receivablesPlanModel = model('ReceivablesPlan');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['create_user_id'] = $userInfo['id'];
        $param['owner_user_id'] = $userInfo['id'];

        $res = $receivablesPlanModel->createData($param);
        if ($res) {
            return resultArray(['data' => '添加成功']);
        } else {
            return resultArray(['error' => $receivablesPlanModel->getError()]);
        }
    }

    /**
     * 回款计划详情
     * @author Michael_xu
     * @param  
     * @return 
     */
    public function read()
    {
        $receivablesPlanModel = model('ReceivablesPlan');
        $param = $this->param;
        $data = $receivablesPlanModel->getDataById($param['id']);
        if (!$data) {
            return resultArray(['error' => $receivablesPlanModel->getError()]);
        }
        return resultArray(['data' => $data]);
    }

    /**
     * 编辑回款计划
     * @author Michael_xu
     * @param 
     * @return 
     */
    public function update()
    {    
        $receivablesPlanModel = model('ReceivablesPlan');
        $param = $this->param;
        $userInfo = $this->userInfo;

        $res = $receivablesPlanModel->updateDataById($param, $param['id']);
        if ($res) {
            return resultArray(['data' => '编辑成功']);
        } else {
            return resultArray(['error' => $receivablesPlanModel->getError()]);
        }       
    }   
}
