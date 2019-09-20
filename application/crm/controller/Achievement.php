<?php
// +----------------------------------------------------------------------
// | Description: 业绩目标设置及完成情况统计
// +----------------------------------------------------------------------
// | Author: yykun  
// +----------------------------------------------------------------------

namespace app\crm\controller;

use app\admin\controller\ApiCommon;
use think\Hook;
use think\Request;

class Achievement extends ApiCommon
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
            'allow'=>['index','indexforuser','save','read','update']            
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());        
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }

        $userInfo = $this->userInfo;
        //权限判断
        $unAction = [''];
        if (!in_array($a, $unAction) && !checkPerByAction('admin', 'crm', 'setting')) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }        
    }

    /**
     * 业绩目标列表
     * @author yykun
     * @return
     */
    public function index()
    {
        $model = model('Achievement');
        $param = $this->param;
        $data = $model->getDataList($param);       
        return resultArray(['data' => $data]);
    }
	
	//员工业绩目标列表
	public function indexForuser()
    {
        $model = model('Achievement');
        $param = $this->param;
        $data = $model->getDataListForUser($param);       
        return resultArray(['data' => $data]);
    }

    /**
     * 添加
     * @author yykun
     * @param  
     * @return
     */
    public function save()
    {  
        $model = model('Achievement');
        $param = $this->param;
        $userInfo = $this->userInfo;
        if ($model->createData($param)) {
            return resultArray(['data' => '添加成功']);
        } else {
            return resultArray(['error' => $model->getError()]);
        }
    }

    /**
     * 详情
     * @author yykun
     * @param  
     * @return
     */
    public function read()
    {
        $model = model('Achievement');
        $param = $this->param;
        $data = $model->getDataById($param['id']);
        if (!$data) {
            return resultArray(['error' => $model->getError()]);
        } else {
            return resultArray(['data' => $data]);
        }
    }

    /**
     * 编辑信息
     * @author yykun
     * @param 
     * @return
     */
    public function update()
    {    
        $model = model('Achievement');
      
        $param = $this->param;
        if ($model->updateData($param)) {
            return resultArray(['data' => '编辑成功']);
        } else {
            return resultArray(['error' => $model->getError()]);
        }      
    }
}