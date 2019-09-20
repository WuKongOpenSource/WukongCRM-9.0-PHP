<?php
// +----------------------------------------------------------------------
// | Description: 应用状态
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com
// +----------------------------------------------------------------------

namespace app\admin\controller;

use think\Hook;
use think\Request;

class ConfigSet extends ApiCommon
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
            'allow'=>['']
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());        
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }               
    }        

    /**
     * 应用状态列表
     * @author Michael_xu
     * @return
     */
    public function index()
    {   
        $configModel = model('Config');
        $data = $configModel->getDataList();
        return resultArray(['data' => $data]);
    }
	
    /**
     * 状态编辑
     * @author Michael_xu
     * @return
     */    
    public function update()
    {
        $configModel = model('Config');
        $param = $this->param;
		if (!$param['id']) {
			return resultArray(['error' => '参数错误']);
		}
        if ($configModel->updateDataById($param, $param['id'])) {
            return resultArray(['data' => '编辑成功']);
        }
        return resultArray(['error' => $configModel->getError()]);        
    }

    
}
 