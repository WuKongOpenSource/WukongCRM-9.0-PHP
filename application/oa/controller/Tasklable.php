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
        $lableModel = new \app\work\model\WorkLable();
        if ($param) {
            $userInfo = $this->userInfo;
			$param['create_user_id'] = $userInfo['id']; 
            $flag = $lableModel->createData($param);
            if ($flag) {
                return resultArray(['data'=>'添加成功']);
            } else {
                return resultArray(['error'=>$lableModel->getError()]);
            }
        } else {
            return resultArray(['error'=>'参数错误']);
        }

    }
    /*
    * 返回标签列表   
    */
    public function index()
    {   
        $lableModel = new \app\work\model\WorkLable();
        $list = $lableModel->getDataList();
        if ($list) {
            return resultArray(['data'=>$list]);
        } else {
            return resultArray(['data'=>$lableModel->getError()]);
        }
    }

    /**
     * 根据标签获取项目及任务
     * @return [type] [description]
     */
    public function getWokList()
    {
        $param = $this->param;
        if ($param['lable_id']) {
            $lable_id = $param['lable_id'];
            $taskList = Db::name('Task')
                        ->field('task_id,name,work_id,lable_id,main_user_id,priority,stop_time')
                        ->where('FIND_IN_SET("'.$lable_id.'", lable_id)')
                        ->order('work_id desc')->select();
            $lableModel = model('WorkLable');
            foreach ($taskList as $k => $v) {
                $taskList[$k]['lableList'] = $lableModel->getDataByStr($v['lable_id']);
                if ($v['main_user_id']) {
                    $userDet = Db::name('AdminUser')->field('id,realname,thumb_img')->where('id ='.$v['main_user_id'])->find();
                    $taskList[$k]['main_user_name'] = $userDet['realname'];
                    $taskList[$k]['main_user_img'] = $userDet['thumb_img'];
                } else {
                    $taskList[$k]['main_user_name'] = '';
                    $taskList[$k]['main_user_img'] = '';
                }
                $taskList[$k]['stop_time'] = $v['stop_time']?:'';
            }
            
            $workArr = [];
            $workGroup = $this->group_same_key($taskList);
            $newWorkArr = [];
            $i = 0;
            foreach ($workGroup as $key => $value) {
                $workDet = Db::name('Work')->where('work_id ='.$key)->find();
                $newWorkArr[$i]['work_name'] = $workDet['name'];
                $newWorkArr[$i]['work_id'] = $key;
                $newWorkArr[$i]['list'] = $value;
                $i++;
            } 
            return resultArray(['data'=>$newWorkArr]);
        } else {
            return resultArray(['error'=>'参数错误']);
        }
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

    /*
    *编辑标签
    */
    public function update()
    {
        $param = $this->param;
        $lableModel = new \app\work\model\WorkLable();
        if ( $param['lable_id'] ) {
            $userInfo   			 = $this->userInfo;
			$param['create_user_id'] = $userInfo['id']; 
            $flag = $lableModel->updateDataById($param);
            if ($flag)  {
                return resultArray(['data'=>'编辑成功']);
            } else {
                return resultArray(['error'=>$lableModel->getError()]);
            }
        } else {
            return resultArray(['error'=>'参数错误']);
        }
    }

    /*
    *删除标签
     */
    public function delete()
    {
        $param = $this->param;
        $lableModel = new \app\work\model\WorkLable();
        if ($param['lable_id']) {
            $userInfo   			 = $this->userInfo;
			$param['create_user_id'] = $userInfo['id']; 
            $flag = $lableModel->delDataById($param);
            if ($flag) {
                return resultArray(['data'=>'删除成功']);
            } else {
                return resultArray(['error'=>$lableModel->getError()]);
            }
        } else {
            return resultArray(['error'=>'参数错误']);
        }
    }

    // miss 路由：处理没有匹配到的路由规则
    public function miss()
    {
        if (Request::instance()->isOptions()) {
            return ;
        } else {
            echo '悟空软件';
        }
    }      
}
 