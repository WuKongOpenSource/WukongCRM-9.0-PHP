<?php
// +----------------------------------------------------------------------
// | Description: 项目控制器
// +----------------------------------------------------------------------
// | Author:  yykun
// +----------------------------------------------------------------------

namespace app\work\controller;

use think\Request;
use think\Session;
use think\Hook;
use app\admin\controller\ApiCommon;
use think\Db;

class work extends ApiCommon
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
            'allow'=>['index','filelist','delete','read','archive','owneradd','ownerdel','ownerlist','leave','archivelist','arrecover','statistic','grouplist','addusergroup','update']            
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());        
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }        
    }

    /**
     * 添加新项目
     * @author yykun
     * @return
     */
    public function save()
    {   
        $param = $this->param;
        $workModel = model('Work');
        if (!$param['name']) {
            return resultArray(['error'=>'项目名称不能为空']);
        }
        $userInfo = $this->userInfo;      
		$param['create_user_id'] = $userInfo['id'];
        $owner_user_id = $param['owner_user_id'] ? : [$userInfo['id']]; //项目成员
        if (!in_array($userInfo['id'],$owner_user_id)) {
            $owner_user_id[] = $userInfo['id'];
        }
        $param['owner_user_id'] = $owner_user_id;
        $work_id = $workModel->createData($param);
        if ($work_id) {
            return resultArray(['data'=>$work_id]);
        } else {
            return resultArray(['error'=>$workModel->getError()]);
        }
    }
	
    /**
     * 编辑项目
     * @author yykun
     * @return
     */
    public function update()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $workModel = model('Work');
        if (!$param['work_id']) {
           return resultArray(['error'=>'参数错误']); 
        }
        //权限判断
		if (!$workModel->isCheck('work','work','update',$param['work_id'],$userInfo['id'])) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));            
        }
		$param['create_user_id'] = $userInfo['id']; 
        $flag = $workModel->updateDataById($param);
        if ($flag) {
            return resultArray(['data'=>'编辑成功']);
        } else {
            return resultArray(['error'=>$workModel->getError()]);
        }
    }

    /**
     * 项目详情
     * @author Michael_xu
     * @return
     */
    public function read()
    {   
        $param = $this->param;
        $userInfo = $this->userInfo;
        $workModel = model('Work');
        if (!$param['work_id']) {
           return resultArray(['error'=>'参数错误']); 
        }
        //权限判断
        $ret = $workModel->checkWork($param['work_id'], $userInfo['id']);
        if (!$ret) {
            return resultArray(['error'=>$workModel->getError()]); 
        }        
        $param['create_user_id'] = $userInfo['id']; 
        $workInfo = $workModel->getDataById($param['work_id']);

        //权限数据返回
        $authParam = [];
        $authParam['user_id'] = $userInfo['id'];
        $authParam['work_id'] = $param['work_id'];
        $authList = $workModel->authList($authParam);
        $workInfo['authList'] = $authList ? : [];
        if ($workInfo) {
            return resultArray(['data'=>$workInfo]);
        } else {
            return resultArray(['error'=>$workModel->getError()]);
        }
    }    
	
    /**
     * 删除项目
     * @author yykun
     * @return
     */ 
    public function delete()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $workModel = model('Work');
		//权限判断
        if (!$workModel->isCheck('work','work','update',$param['work_id'],$userInfo['id'])) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));            
        }
		$param['create_user_id'] = $userInfo['id']; 
        $resWork = $workModel->delWorkById($param);
        if ($resWork) {
            //删除项目下所有任务
            $resTask = db('task')->where(['work_id' => $param['work_id']])->delete();
            return resultArray(['data'=>'删除成功']);
        } else {
            return resultArray(['error'=>$workModel->getError()]);
        }
    }

    /**
     * 归档项目
     * @author yykun
     * @return
     */
    public function archive()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $workModel = model('Work');
        if (!$param['work_id']) {
            return resultArray(['error'=>'参数错误']);
        }
		//权限判断
        if (!$workModel->isCheck('work','work','update',$param['work_id'],$userInfo['id'])) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));            
        }
		$param['create_user_id'] = $userInfo['id']; 
        $flag = $workModel->archiveData($param);
        if ($flag) {
            return resultArray(['data'=>'归档成功']);
        } else {
            return resultArray(['error'=>$workModel->getError()]);
        }
    }

    /**
     * 参与人添加
     * @author yykun
     * @return
     */
    public function ownerAdd()
    {   
        $param = $this->param;
        $userInfo = $this->userInfo;
        if (!$param['work_id'] || !$param['owner_user_id']) {
            return resultArray(['error'=>'参数错误']);
        }
        $workModel = model('Work');
        //权限判断
        if (!$workModel->isCheck('work','work','update',$param['work_id'],$userInfo['id'])) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));            
        }        
        $res = $workModel->addOwner($param);
        if ($res) { 
            $temp['work_id'] = $param['work_id'];
            $list = $workModel->ownerList($temp); //获取参与人列表
            return resultArray(['data'=>$list]);
        } else {
            return resultArray(['error'=>'操作失败']);
        }
    }

    /**
     * 参与人删除
     * @author yykun
     * @return
     */
    public function ownerDel()
    {   
        $param = $this->param;
        $userInfo = $this->userInfo;
        if (!$param['work_id'] || !$param['owner_user_id']) {
            return resultArray(['error'=>'参数错误']);
        }
        $workModel = model('Work');
        //权限判断
        if (!$workModel->isCheck('work','work','update',$param['work_id'],$userInfo['id'])) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));            
        }      
        $res = $workModel->delOwner($param);
        if ($res) { 
            return resultArray(['data'=>'操作成功']);
        } else {
            return resultArray(['error'=>$workModel->getError()]);
        }
    }

    /**
     * 参与人列表
     * @author yykun
     * @return
     */
    public function ownerList()
    {   
        $param = $this->param;
        $workModel = model('Work');
        $list = $workModel->ownerList($param);
        return resultArray(['data'=>$list]);
    }

    /**
     * 退出项目
     * @author yykun
     * @return
     */
    public function leave()
    {
        $param = $this->param;
        $userInfo   = $this->userInfo;
        $workModel = model('Work');
        if (!$param['work_id']) {
            return resultArray(['error'=>'参数错误']);
        }
        $ret = $workModel->leaveById($param['work_id'],$userInfo['id']);
        if ($ret) {
            return resultArray(['data'=>'操作成功']);
        } else {
            return resultArray(['error'=>$workModel->getError()]);
        }
    }

    /**
     * 归档项目列表
     * @author yykun
     * @return
     */
    public function archiveList()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];
        $workModel = model('Work');
        $list = $workModel->archiveList($param);
        return resultArray(['data'=>$list]);
    }

    /**
     * 恢复归档项目
     * @author yykun
     * @return
     */
    public function arRecover()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        if (!$param['work_id']) {
           return resultArray(['error'=>'参数错误']); 
        }
        $workModel = Model('Work');
        //权限判断
        if (!$workModel->isCheck('work','work','update',$param['work_id'],$userInfo['id'])) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));            
        }         
        $ret = $workModel->arRecover($param['work_id']);
        if ($ret) {
            return resultArray(['data'=>'操作成功']);
        } else {
            return resultArray(['error'=>$workModel->getError()]);
        }
    }

    /**
     * 项目任务统计
     * @author Michael_xu
     * @return
     */
    public function statistic()
    {
        $param = $this->param;
        $userModel = new \app\admin\model\User();
        $workModel = model('work');
        $work_id = $param['work_id'];
        if (!$work_id) {
            return resultArray(['error'=>'参数错误']);
        }
        $dataCount = [];
        if ($work_id !== 'all') $workInfo = Db::name('Work')->where(['work_id' => $work_id])->find();
        $lableary = []; //标签
        $main_user_arr = []; //成员
        $allNum = 0; //总任务数
        $undoneNum = 0; //总未完成数
        $doneNum = 0; //总完成数
        $overtimeNum = 0; //总延期数
        $archiveNum = 0; //总归档数
        $completionRate = 0; //总完成率
        $delayRate = 0; //总延期率

        //公开项目
        if ($work_id !== 'all') {
            $taskList = Db::name('Task')->where(['work_id' => $work_id,'ishidden' => 0])->field('task_id,main_user_id,lable_id,status,owner_user_id,stop_time,is_archive')->select();
        } else {
            $taskList = Db::name('Task')->where(['work_id' => ['gt',0],'ishidden' => 0])->field('task_id,main_user_id,lable_id,status,owner_user_id,stop_time,is_archive')->select();
        }
        foreach ($taskList as $key => $value) {
            if (empty($value['is_archive'])) {
                $allNum += 1;
                if ($value['status'] == 1) {
                    $undoneNum += 1;
                }
                if ($value['status'] == 1 && $value['stop_time'] && ($value['stop_time'] < time())) {
                    $overtimeNum += 1;
                }                
            }
            if ($value['is_archive'] == 1) $archiveNum += 1;
            if ($value['status'] == 5) $doneNum += 1;            
            //获取项目下成员ID
            if ($value['owner_user_id'] && $workInfo['is_open'] == 1) $main_user_arr[] = $value['main_user_id']; //负责人
            if ($work_id == 'all') $main_user_arr[] = $value['main_user_id']; //负责人
            $lableArray = [];
            $lableArray = $value['lable_id'] ? stringToArray($value['lable_id']) : []; //标签
            $lableary = $lableArray ? array_merge($lableary,$lableArray) : $lableary;
        }
        $main_user_arr = $main_user_arr ? array_filter(array_unique($main_user_arr)) : [];
        $lableary = array_filter(array_unique($lableary));            

        $completionRate = $allNum ? round(($doneNum/$allNum),2)*100 : 0;
        $delayRate = $allNum ? round(($overtimeNum/$allNum),2)*100 : 0;

        $dataCount['allNum'] = $allNum ? : 0;
        $dataCount['undoneNum'] = $undoneNum ? : 0;
        $dataCount['doneNum'] = $doneNum ? : 0;
        $dataCount['overtimeNum'] = $overtimeNum ? : 0;
        $dataCount['archiveNum'] = $archiveNum ? : 0;
        $dataCount['completionRate'] = $completionRate ? : 0;
        $dataCount['delayRate'] = $delayRate ? : 0;

        //项目负责人
        $ownerArr = [];
        if ($workInfo && $workInfo['is_open'] == 0) {
            //私有项目
            $main_user_arr = db('work_user')->where(['work_id' => $work_id])->column('user_id');                    
            $ownerArr = db('work_user')->where(['work_id' => $work_id,'types' => 1])->column('user_id');                    
        } elseif ($work_id !== 'all') {
            $ownerArr[] = $workInfo['create_user_id'];
        }
        $ownerList = [];
        foreach ($ownerArr as $k3=>$v3) {
            $ownerList[] = $userModel->getUserById($v3);
        }
        $dataAry['ownerList'] = $ownerList ? : [];
        // $dataAry['workInfo'] = $workInfo ? : [];

        //成员统计
        $list = [];
        $i = 0;
        $main_user_arr = $main_user_arr ? array_merge($main_user_arr) : [];
        foreach ($main_user_arr as $key => $value) {
            //参与项目数量
            $userInfo = [];
            $userInfo = $userModel->getUserById($value);
            if (!$userInfo) continue;
            $list[$i]['userInfo'] = $userInfo ? : [];
            // $workCount = 0; //项目总数
            $allCount = 0; //任务总数
            $undoneCount = 0; //待完成任务总数
            $doneCount = 0; //已完成任务总数
            $overtimeCount = 0; //延期任务总数
            $archiveCount = 0; //归档任务总数
            $completionRate = 0; //完成率
            $taskArr = [];
            if ($work_id == 'all') {
                $taskArr = db('task')->where(['main_user_id' => $value,'ishidden' => 0])->field('status,stop_time,is_archive,task_id')->select();
            } else {
                $taskArr = db('task')->where(['work_id' => $work_id,'main_user_id' => $value,'ishidden' => 0])->field('status,stop_time,is_archive,task_id')->select();
            }
            foreach ($taskArr as $v) {
                $allCount += 1;
                if ($v['status'] == 1 && empty($v['is_archive'])) $undoneCount += 1;
                if (($v['status'] == 1 && empty($v['is_archive'])) && $v['stop_time'] && ($v['stop_time'] < time())) $overtimeCount += 1;
                if ($v['is_archive'] == 1) $archiveCount += 1;
                if ($v['status'] == 5) $doneCount += 1;
            }
            $completionRate = $allCount ? round(($doneCount/$allCount),2)*100 : 0;
            $list[$i]['allCount'] = $allCount ? : 0;
            $list[$i]['undoneCount'] = $undoneCount ? : 0;
            $list[$i]['doneCount'] = $doneCount ? : 0;
            $list[$i]['overtimeCount'] = $overtimeCount ? : 0;
            $list[$i]['archiveCount'] = $archiveCount ? : 0;
            $list[$i]['completionRate'] = $completionRate ? : 0;
            $i++;
        }
        $dataAry['dataCount'] = $dataCount;
        $dataAry['userList'] = $list;

        if ($work_id !== 'all') {
            //任务列表统计
            $dataAry['classList'] = $workModel->classList($work_id);
            //标签统计
            $dataAry['labelList'] = $workModel->labelList($work_id,$lableary);            
        }
        return resultArray(['data'=>$dataAry]);
    }

    /**
     * 参与人角色添加
     * @author yykun
     * @return
     */ 
	public function addUserGroup()
	{
        $param = $this->param;
		$userInfo = $this->userInfo;
		$workModel = model('Work');
        $list = $param['list'] ? : [];
        $work_id = $param['work_id'] ? : [];
		if (!is_array($list) || !$work_id) {
            return resultArray(['error'=>'参数错误']);
        }
        //权限判断
        if (!$workModel->isCheck('work','work','update',$param['work_id'],$userInfo['id'])) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作1']));            
        }          
		foreach ($list as $value) {
			$data = array();
            $types = 0;
			$data['work_id'] = $work_id;
			$data['user_id'] = $value['user_id'];
			$flag = db('work_user')->where($data)->find();

			$data['group_id'] = $value['group_id'];
            if ($value['group_id'] == 1) $types = 1; //项目管理员，不能删除
            $data['types'] = $types;
			if (!$flag) {
				db('work_user')->insert($data);
			} else {
				db('work_user')->where(['work_id' => $work_id,'user_id' => $value['user_id']])->update($data);
			}
		}
		$dataList = db('work_user')->where(['work_id' => $work_id])->select();
		return resultArray(['data'=>$dataList]);
	} 

    /**
     * 项目下附件列表
     * @param
     * @return 
     */
    public function fileList()
    {   
        $param = $this->param;
        $userInfo = $this->userInfo;
        $workModel = model('Work');
        $work_id = $param['work_id'];
        if (!$work_id) {
            return resultArray(['error'=>'参数错误']);
        }
        //判断权限
        $checkRes = $workModel->checkWork($work_id, $userInfo['id']);
        if ($checkRes !== true) {
            return resultArray(['error' => $workModel->getError()]);
        }

        $task_ids = db('task')->where(['work_id' => $work_id])->column('task_id');
        $request = [];
        $request['module'] = 'work_task';
        $request['module_id'] = $task_ids;
        $fileModel = new \app\admin\model\File();
        $data = $fileModel->getDataList($request, $param['by']);
        return resultArray(['data' => $data]);
    }

    /**
     * 项目角色列表
     * @author yykun
     * @return
     */ 
    public function groupList()
    {
        $list = array(
                '0'=>array('id' => 1,'title'=>'管理','remark' => '系统默认权限，包含项目所有权限,不可修改/删除'),
                );
        $groupList = db('admin_group')->where(['pid' => 5,'type' => 0])->order('system desc')->field('id,title,remark')->select();
        $listArr = array_merge($list, $groupList) ? : [];
        return resultArray(['data' => $listArr]);
    }       
}
 