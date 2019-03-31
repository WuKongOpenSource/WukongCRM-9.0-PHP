<?php
// +----------------------------------------------------------------------
// | Description: 用户组
// +----------------------------------------------------------------------
// | Author:  
// +----------------------------------------------------------------------

namespace app\admin\controller;

use think\Hook;
use think\Request;

class Configset extends ApiCommon
{
    //用于判断权限
    public function _initialize()
    {
        $action = [
            'permission'=>[''],
            'allow'=>['index','typelist','read','updatetype']
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());        
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }         
    }        

    //应用状态列表
    public function index()
    {   
        $configModel = model('Configset');
        $data = $configModel->getDataList();
        return resultArray(['data' => $data]);
    }

	//模块列表
	public function typelist()
	{
		$configModel = model('Configset');
		$data = $configModel->typelist();
		return resultArray(['data' => $data]);
	}
	
    //头部导航栏数据
    public function read()
    {
        $param = $this->param;
		if($param['type']) {
			$configModel = model('Configset');
			$data = $configModel->getDataBytype($param);
			return resultArray(['data' => $data]);
		} else {
			return resultArray(['error'=>'参数错误']);
		}
    }
	
    //状态编辑    
    public function update()
    {
        $configModel = model('Configset');
        $param = $this->param;
		if($param['status'] =='0' || $param['status'] =='1')
		{
			$data = $configModel->updateDataById($param, $param['id']);
			if($data){
				return resultArray(['data' => '编辑成功']);
			} else {
				return resultArray(['error' => $configModel->getError()]);
			}
		} else {
			return resultArray(['error' => '参数错误']);
		}
    }

    //批量修改
    public function updatetype()
    {
		$configModel = model('Configset');
        $param = $this->param;
		// $res_per = checkPerByAction('admin', 'configset', 'update');
  //   	if (!$res_per) {
		// 	header('Content-Type:application/json; charset=utf-8');
  //           exit(json_encode(['code'=>102,'error'=>'无权操作']));
  //   	} 				
		if ($param['status'] =='0' || $param['status'] =='1') {
			$data = $configModel->updateDatas($param, $param['type']);  
			if (!$data) {
				return resultArray(['error' => $configModel->getError()]);
			} 
			return resultArray(['data' => '操作成功']); 
		} else {
			return resultArray(['error' => '参数错误']);
		}
    }
}
 