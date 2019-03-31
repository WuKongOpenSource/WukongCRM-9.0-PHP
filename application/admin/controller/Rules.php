<?php
// +----------------------------------------------------------------------
// | Description: 规则
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com
// +----------------------------------------------------------------------

namespace app\admin\controller;

use think\Hook;
use think\Request;

class Rules extends ApiCommon
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
            'allow'=>['index']            
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());        
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }  

        $m = $this->m;
        $c = $this->c;
        $a = $this->a;
    }    

    public function index()
    {   
        $ruleModel = model('Rule');
        $param = $this->param;
        $data = $ruleModel->getDataList($param);
        return resultArray(['data' => $data]);
    }

    /**
     * 新建规则
     * @param
     * @return
     */    
    public function save()
    {
        $ruleModel = model('Rule');
        $param = $this->param;
        $data = $ruleModel->createData($param);
        if (!$data) {
            return resultArray(['error' => $ruleModel->getError()]);
        } 
        return resultArray(['data' => '添加成功']);
    }

    /**
     * 编辑规则
     * @param
     * @return
     */
    public function update()
    {
        $ruleModel = model('Rule');
        $param = $this->param;
        $data = $ruleModel->updateDataById($param, $param['id']);
        if (!$data) {
            return resultArray(['error' => $ruleModel->getError()]);
        } 
        return resultArray(['data' => '编辑成功']);
    }
}
 