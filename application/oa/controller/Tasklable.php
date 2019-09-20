<?php
// +----------------------------------------------------------------------
// | Description: 任务标签及基础
// +----------------------------------------------------------------------
// | Author:  	yykun
// +----------------------------------------------------------------------
namespace app\oa\controller;

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
            'permission'=>[''],  //不登录可访问
            'allow'=>['index','getwoklist','grouplist','update','delete','save']  //需要登录才能访问          
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());        
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
    }

    /*
    * 添加新标签
    */
    public function save()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $lableModel = new \app\work\model\WorkLable();
        if (!$param) {
            return resultArray(['error'=>'参数错误']);
        }
		$param['create_user_id'] = $userInfo['id']; 
        if (!$lableModel->createData($param)) {
            return resultArray(['error'=>$lableModel->getError()]);
        }
        return resultArray(['data'=>'添加成功']);
    }

    /*
    * 返回标签列表   
    */
    public function index()
    {   
        $lableModel = new \app\work\model\WorkLable();
        $list = $lableModel->getDataList();
        return resultArray(['data'=>$list]);
    }

    /**
     * 根据标签获取项目及任务
     * @return 
     */
    public function getWokList()
    {
        $param = $this->param;
        $userModel = new \app\admin\model\User();
        $lable_id = $param['lable_id'];
        if (!$lable_id) {
            return resultArray(['error'=>'参数错误']);
        }
        $taskList = Db::name('Task')
                    ->field('task_id,name,work_id,lable_id,main_user_id,priority,stop_time')
                    ->where('FIND_IN_SET("'.$lable_id.'", lable_id)')
                    ->order('work_id desc')->select();
        $lableModel = model('WorkLable');
        foreach ($taskList as $k => $v) {
            $taskList[$k]['lableList'] = $v['lable_id'] ? $lableModel->getDataByStr($v['lable_id']) : [];
            $userDet = [];
            $userDet = isset($v['main_user_id']) ? $userModel->getUserById($v['main_user_id']) : [];
            $taskList[$k]['main_user_name'] = $userDet ? $userDet['realname'] : '';
            $taskList[$k]['main_user_img'] = $userDet ? $userDet['thumb_img'] : '';
            $taskList[$k]['stop_time'] = $v['stop_time'] ? : '';
        }
        
        $workArr = [];
        $workGroup = $this->group_same_key($taskList);
        $newWorkArr = [];
        $i = 0;
        foreach ($workGroup as $key => $value) {
            $workDet = Db::name('Work')->where(['work_id' => $key])->find();
            $newWorkArr[$i]['work_name'] = $workDet['name'];
            $newWorkArr[$i]['work_id'] = $key;
            $newWorkArr[$i]['list'] = $value;
            $i++;
        } 
        return resultArray(['data'=>$newWorkArr]);
    }

    public function group_same_key( $arr ) {
        $new_arr = array();
        foreach ($arr as $k => $v) {
            $new_arr[$v['work_id']][] = $v;
        }
        return $new_arr;
    }

    //分组列表
    public function groupList()
    {
        $lableModel = new \app\work\model\WorkLable();
        $workList = Db::name('Work')->field('name,work_id')->select();
        $temp = array();
        foreach ($workList as $key => $value) {
            $temp = array();
            $taskList = Db::name('Task')->field('task_id,lable_id')->where(['work_id' => $value['work_id']])->select();
            foreach ($taskList as $k => $v) {
                $temp_temp = $lableModel->getDataByStr($v['lable_id']);
                $temp = array_merge($temp,$temp_temp);
            }
            $temp = array_filter(array_unique($temp));
            $workList[$key]['taskList'] = $temp;
        }
        return resultArray(['data' => $workList]);
    }

    /*
    *编辑标签
    */
    public function update()
    {
        $param = $this->param;
        $lableModel = new \app\work\model\WorkLable();
        if (!$param['lable_id']) {
            return resultArray(['error'=>'参数错误']);
        }
        $userInfo = $this->userInfo;
		$param['create_user_id'] = $userInfo['id']; 
        if ($lableModel->updateDataById($param)) {
            return resultArray(['data'=>'编辑成功']);
        } else {
            return resultArray(['error'=>$lableModel->getError()]);
        }
    }

    /*
    *删除标签
     */
    public function delete()
    {
        $param = $this->param;
        $lableModel = new \app\work\model\WorkLable();
        if (!$param['lable_id']) {
           return resultArray(['error'=>'参数错误']); 
        }
        $userInfo = $this->userInfo;
		$param['create_user_id'] = $userInfo['id']; 
        if ($lableModel->delDataById($param)) {
            return resultArray(['data'=>'删除成功']);
        } else {
            return resultArray(['error'=>$lableModel->getError()]);
        }
    }     
}
 