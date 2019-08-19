<?php
// +----------------------------------------------------------------------
// | Description: 任务标签及基础
// +----------------------------------------------------------------------
// | Author:  	yykun
// +----------------------------------------------------------------------

namespace app\work\controller;

use think\Request;
use think\Session;
use think\Hook;
use think\Db;
use app\admin\controller\ApiCommon;

class Tasklable extends ApiCommon
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
            'allow'=>['index','getwoklist','grouplist','save','update','delete','read']         
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());        
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
    }

    /**
     * 添加新标签
     * @author yykun
     * @return
     */
    public function save()
    {
        $param = $this->param;
        $lableModel = model('WorkLable');
        $userInfo = $this->userInfo;
		$param['create_user_id'] = $userInfo['id']; 
        $flag = $lableModel->createData($param);
        if ($flag) {
            return resultArray(['data'=>'添加成功']);
        } else {
            return resultArray(['error'=>$lableModel->getError()]);
        }
    }
	
    /**
     * 标签列表
     * @author yykun
     * @return
     */
    public function index()
    {   
        $lableModel = model('WorkLable');
        $list = $lableModel->getDataList();
        return resultArray(['data'=>$list]);
    }

    /**
     * 根据标签获取项目及任务
     * @author yykun
     * @return
     */
    public function getWokList()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        if (!$param['lable_id']) {
            return resultArray(['error'=>'参数错误']);
        }
        $taskModel = model('Task');
        $adminTypes = adminGroupTypes($userInfo['id']);
        if (!in_array(1,$adminTypes) && !in_array(7,$adminTypes)) {
            $str = ','.$userInfo['id'].',';
            $param['whereStr'] = ' ( task.create_user_id ='.$userInfo['id'].' or (  task.owner_user_id like "%'.$str.'%") or ( task.main_user_id = '.$userInfo['id'].' ) )';
        }
        $resData = $taskModel->getTaskList($param);
        $taskList = $resData['list'] ? : [];
        
        $workArr = [];
        $workGroupList = $taskList ? $this->group_same_key($taskList) : [];
        return resultArray(['data'=>$workGroupList]);
    }

    public function group_same_key($arr)
    {
        $new_arr = array();
        foreach ($arr as $k => $v) {
            $new_arr[$v['work_id']]['work_name'] = $v['work_name'] ? : '我的任务';
            $new_arr[$v['work_id']]['color'] = $v['color'] ? : '#4AB8B8';
            $new_arr[$v['work_id']]['work_id'] = $v['work_id'];
            $new_arr[$v['work_id']]['list'][] = $v;
        }
        $ListArr = $new_arr ? sort_select(array_merge($new_arr), 'work_id', 2) : [];
        return $ListArr;
    }

    /**
     * 分组列表
     * @author yykun
     * @return
     */
    public function groupList()
    {
        $lableModel = model('WorkLable');
        $workList = $this->field('name,work_id')->select();
        $temp = array();
        foreach ($workList as $key => $value) {
            $temp = array();
            $taskList = Db::name('Task')->field('task_id,lable_id')->where('work_id ='.$value['work_id'])->select();
            foreach ($taskList as $k => $v) {
                $temp_temp = $lableModel->getDataByStr($v['lable_id']);
                $temp = array_merge($temp,$temp_temp);
            }
            $temp = array_filter(array_unique($temp));
            $workList[$key]['taskList'] = $temp;
        }
        return resultArray(['data' => $workList]);
    }

    /**
     * 编辑标签
     * @author yykun
     * @return
     */
    public function update()
    {
        $param = $this->param;
        $lableModel = model('WorkLable');
        if (!$param['lable_id']) {
            return resultArray(['error'=>'参数错误']);
        }
        $userInfo = $this->userInfo;
		$param['create_user_id'] = $userInfo['id']; 
        $flag = $lableModel->updateDataById($param);
        if ($flag)  {
            return resultArray(['data'=>'编辑成功']);
        } else {
            return resultArray(['error'=>$lableModel->getError()]);
        }
    }

    /**
     * 删除标签
     * @author yykun
     * @return
     */
    public function delete()
    {
        $param = $this->param;
        $taskLableModel = model('WorkLable');
        if (!$param['lable_id']) {
            return resultArray(['error'=>'参数错误']);
        }
        $userInfo = $this->userInfo;
		$param['create_user_id'] = $userInfo['id']; 
        $flag = $taskLableModel->delDataById($param);
        if ($flag) {
            return resultArray(['data'=>'删除成功']);
        } else {
            return resultArray(['error'=>$taskLableModel->getError()]);
        }
    } 

    /**
     * 标签详情
     * @author Michael_xu
     * @return
     */        
    public function read()
    {
        $param = $this->param;
        if (!$param['lable_id']) return resultArray(['error'=>'参数错误']);
        $taskLableModel = model('WorkLable');
        $data = $taskLableModel->getDataById($param['lable_id']);
        return resultArray(['data'=>$data]);
    }
}