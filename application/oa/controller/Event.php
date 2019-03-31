<?php
// +----------------------------------------------------------------------
// | Description: 日程
// +----------------------------------------------------------------------
// | Author: Michael_xu | gengxiaoxu@5kcrm.com 
// +----------------------------------------------------------------------

namespace app\oa\controller;

use app\admin\controller\ApiCommon;
use think\Hook;
use think\Request;

class Event extends ApiCommon
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
            'allow'=>['index','save','read','update','delete']            
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());        
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
    } 

    //日程列表
    public function index()
    {
        $eventModel = model('Event');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];
        $data = $eventModel->getDataList($param);       
        return resultArray(['data' => $data]);
    }

    //添加日程
    public function save()
    {
        $eventModel = model('Event');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['create_user_id'] = $userInfo['id'];

        $res = $eventModel->createData($param);
        if ($res) {
            return resultArray(['data' => '添加成功']);
        } else {
        	return resultArray(['error' => $eventModel->getError()]);
        }
    }

    //日程详情
    public function read()
    {
        $eventModel = model('Event');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];
        $data = $eventModel->getDataById($param['id'], $param);
        if (!$data) {
            return resultArray(['error' => $eventModel->getError()]);
        }
        return resultArray(['data' => $data]);
    }

    //编辑日程
    public function update()
    {
        $eventModel = model('Event');
        $param = $this->param;
        $userInfo = $this->userInfo;
		if(!$param['event_id']) {
			return resultArray(['error' => '参数错误']);
		}
        $param['user_id'] = $userInfo['id'];
		
		$flag = $eventModel->getDataById($param['event_id'],$param);
		if($flag['create_user_id'] != $userInfo['id']) 
		{
			return resultArray(['error' => '没有修改权限']);
		}
		
        $res = $eventModel->updateDataById($param, $param['event_id']);
        if ($res) {
            return resultArray(['data' => '编辑成功']);
        } else {
        	return resultArray(['error' => $eventModel->getError()]);
        } 
    }

    //删除日程
    public function delete()
    {
        $eventModel = model('Event');
        $param = $this->param;
		if(!$param['event_id']){
			return resultArray(['error'=>'参数错误']);
		}
		$userInfo = $this->userInfo;
		$param['user_id'] = $userInfo['id'];
		
		$flag = $eventModel->getDataById($param['event_id'],$param);
		if($flag['create_user_id'] != $userInfo['id']) 
		{
			return resultArray(['error' => '没有修改权限']);
		}
        $ret = $eventModel->delDataById($param);
        if (!$ret) {
            return resultArray(['error' => $eventModel->getError()]);
        }
        return resultArray(['data' => '删除成功']);
    }   
}
