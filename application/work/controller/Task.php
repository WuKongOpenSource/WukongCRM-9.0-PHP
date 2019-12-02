<?php
// +----------------------------------------------------------------------
// | Description: 任务及基础
// +----------------------------------------------------------------------
// | Author:  yykun
// +----------------------------------------------------------------------

namespace app\work\controller;

use think\Request;
use think\Hook;
use app\admin\controller\ApiCommon;
use app\admin\model\Message;
use think\helper\Time;
use think\Db;

class Task extends ApiCommon
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
            'allow'=>['index','mytask','updatetop','updateorder','read','update','readloglist','updatepriority','updateowner','delownerbyid','delstruceurebyid','updatestoptime','updatelable','updatename','taskover','datelist','save','delmainuserid','rename','delete','archive','recover','archlist','archivetask','setover','updateclassorder']        
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());        
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
        //权限判断
        $param = $this->param;
        if ($param['task_id']) {
            $userInfo = $this->userInfo;
            $taskModel = model('Task'); 
            if (!$taskModel->checkTask($param['task_id'], $userInfo)) {
                header('Content-Type:application/json; charset=utf-8');
                exit(json_encode(['code'=>102,'error'=>'没有权限']));
            }
        }
    }

    /**
     * 项目下任务列表
     * @author yykun
     * @return 
     */ 
    public function index()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $taskModel = model('Task');
        if (!$param['work_id']) {
            return resultArray(['error' => '参数错误']);
        }
        $list = $taskModel->getDataList($param, $userInfo['id']);    
        return resultArray(['data' => $list]);
    }  

    /**
     * 我的任务
     * @author yykun
     * @return 
     */
    public function myTask()
    {
        $lableModel = model('WorkLable');
		$userModel = new \app\admin\model\User();
		$taskModel = model('Task');
        $userInfo = $this->userInfo;
        $str = ','.$userInfo['id'].',';
		
        $data = array();
        $data[0]['title'] = '收件箱';
        $data[1]['title'] = '今天要做';
        $data[2]['title'] = '下一步要做';
        $data[3]['title'] = '以后要做';
        for ($k=0 ; $k<4 ; $k++) {
            $where = [];
			$where['ishidden'] = 0;
			$where['status'] = 1;
            $where['is_top'] = $k;
			$where['pid'] = 0;
            $where['whereStr'] = ' ( task.create_user_id ='.$userInfo['id'].' or (  task.owner_user_id like "%'.$str.'%") or ( task.main_user_id = '.$userInfo['id'].' ) )';
            $resData = $taskModel->getTaskList($where);
            $data[$k]['is_top'] = $k;
            $data[$k]['list'] = $resData['list'] ? : [];
			$data[$k]['count'] = $resData['count'] ? : [];
        }
        return resultArray(['data' => $data]);
    }

    /**
     * 我的任务 拖拽改变分类
     * @author yykun
     * @return
     */
    public function updateTop()
    {   
        $param = $this->param;
        $tolist = $param['tolist'];
        $fromlist = $param['fromlist'];
        if ($param['to_top_id'] || $param['to_top_id'] == 0) {
            if ($tolist) {
                foreach ($tolist as $k1 => $v1) {
                    $toData = [];
                    $toData['is_top'] = $param['to_top_id'];
                    $toData['top_order_id'] = $k1+1;
                    Db::name('Task')->where(['task_id' => $v1])->update($toData);
                }
            }
        }
        if ($param['from_top_id'] || $param['from_top_id'] == 0) {
            if ($fromlist) {
                foreach ($fromlist as $k2 => $v2) {
                    $fromData = [];
                    $fromData['is_top'] = $param['from_top_id'];
                    $fromData['top_order_id'] = $k2+1;                    
                    Db::name('Task')->where(['task_id' => $v2])->update($fromData);
                }
            }
        } else {
            return resultArray(['error' => '参数错误' ]);
        }
        return resultArray(['data' => true ]);
    }

    /**
     * 项目 拖拽改变分类并排序
     * @author yykun
     * @return
     */
    public function updateOrder()
    {
        $param = $this->param;
        if ($param['tolist']) {
            $tolist = $param['tolist'];
            foreach ($tolist as $k1 => $v1) {
                $toData = [];
                $toData['class_id'] = $param['toid'];
                $toData['order_id'] = $k1+1;             
                Db::name('Task')->where(['task_id' => $v1])->update($toData);
            }
        }
        if ($param['fromlist']) {
            $fromlist = $param['fromlist'];
            foreach ($fromlist as $k2 => $v2) {
                $fromData = [];
                $fromData['class_id'] = $param['fromid'];
                $fromData['order_id'] = $k2+1;                 
                Db::name('Task')->where(['task_id' => $v2])->update($fromData);
            }
        }
        return resultArray(['data' => true ]);
    }

    /**
     * 项目下 拖拽整个分类排序
     * @author yykun
     * @return
     */
	public function updateClassOrder()
	{
		$param = $this->param;
        $classlist = $param['class_ids'];
		if (!$param['work_id'] || !$param['class_ids']) {
           return resultArray(['error'=>'参数错误']); 
        }
        foreach ($classlist as $k => $v) {
            $temp = [];
			$temp['order_id'] = $k+1;
            Db::name('WorkTaskClass')->where(['work_id' => $param['work_id'],'class_id' => $v])->update($temp);
		}
	}

    /**
     * 任务详情
     * @author yykun
     * @return
     */
    public function read()
    {   
        $param = $this->param;
        $userInfo = $this->userInfo;
        if (!$param['task_id']) {
            return resultArray(['error'=>'参数错误']);
        }
        $taskModel = model('Task');
        $taskData = $taskModel->getDataById($param['task_id'], $userInfo);
        if ($taskData) {
            return resultArray(['data'=>$taskData]);
        } else {
            return resultArray(['error'=>$taskModel->getError()]);
        }
    }

    /**
     * 任务编辑
     * @author yykun
     * @return
     */
    public function update()
    {
        $taskModel = model('Task');
        $param = $this->param;
		$userInfo = $this->userInfo;
        $param['create_user_id'] = $userInfo['id']; 
        $ary = array('owner_userid_del','owner_userid_add','stop_time','lable_id_add','lable_id_del','name','structure_id_del','structure_id_add');
        if ((in_array($param['type'],$ary))) {
            return resultArray(['error'=>'参数错误']);
        }
        if ($taskModel->updateDetTask($param)) {
            return resultArray(['data'=>'操作成功']);
        } else {
            return resultArray(['error'=>$taskModel->getError()]);
        }
    }
    
    /**
     * 任务操作记录
     * @author yykun
     * @return
     */ 
    public function readLoglist()
    {
        $param = $this->param;
        $taskModel = model('Task');
        if (!$param['task_id']) return resultArray(['error'=>'参数错误']);
        $list = $taskModel->getTaskLogList($param);
        return resultArray(['data'=>$list]);
    }
 
    /**
     * 优先级设置
     * @author yykun
     * @return
     */
    public function updatePriority()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['create_user_id'] = $userInfo['id']; 
        if (!isset($param['priority_id']) || !$param['task_id']) {
            return resultArray(['error'=>'参数错误']);
        }
        $flag = Db::name('Task')->where(['task_id' => $param['task_id']])->setField('priority',$param['priority_id']);
        if ($flag) {
            return resultArray(['data'=>'操作成功']);
        } else {
            return resultArray(['error'=>'操作失败']);
        }
    }
 
    /**
     * 参与人/参与部门编辑 
     * @author yykun
     * @return
     */
    public function updateOwner()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $task_id = $param['task_id'] ? : '';
        $param['create_user_id'] = $userInfo['id'];
        $taskInfo = db('task')->where(['task_id' => $param['task_id']])->find();
        if (!$taskInfo) {
            return resultArray(['error'=>'参数错误']);
        }
        $data = [];
        //部门编辑
        $structure_ids = '';
        if ($param['structure_ids']) {
            $structure_ids = arrayToString($param['structure_ids']);
        }
        $owner_user_id = '';
        $sendUserArr = [];
        if ($param['owner_userids']) {
            $owner_user_id = arrayToString($param['owner_userids']);
            foreach ($param['owner_userids'] as $k=>$v) {
                if (!in_array($v,stringToArray($taskInfo['owner_user_id']))) {
                    $sendUserArr[] = $v;
                } 
            }
            // $content = $userInfo['realname'].'邀请您参与《'.$taskInfo['name'].'》项目，请及时查看';
            // if ($sendUserArr) sendMessage($sendUserArr,$content,1);
            actionLog($param['task_id'],$param['owner_user_id'],$param['structure_ids'],'修改了参与人');
        }        
        $data['structure_ids'] = $structure_ids;
        $data['owner_user_id'] = $owner_user_id;
        $resUpdate = db('task')->where(['task_id' => $param['task_id']])->update($data); 
        if ($resUpdate) {
            //站内信
            if ($sendUserArr) {
                (new Message())->send(
                    Message::TASK_INVITE,
                    [
                        'title' => $taskInfo['name'],
                        'action_id' => $taskInfo['task_id']
                    ],
                    $sendUserArr
                );
            }
            return resultArray(['data'=>'修改成功']);
        }
        return resultArray(['error'=>'修改失败或数据无变化']);
    }

    /**
     * 单独删除参与人
     * @author yykun
     * @return
     */
    public function delOwnerById()
    {
        $taskModel = model('Task');
        $userInfo = $this->userInfo;
        $param = $this->param;
        $param['create_user_id'] = $userInfo['id']; 
        $ary = array('owner_userid_del','owner_userid_add');
        if (!in_array($param['type'], $ary)) {
            return resultArray(['error'=>'参数错误']);
        }
        $ret = $taskModel->updateDetTask($param);
        if ($ret) {
            return resultArray(['data'=>'操作成功']);
        } else {
            return resultArray(['error'=>$taskModel->getError()]);
        }
    }

    /**
     * 单独删除参与部门
     * @author yykun
     * @return
     */
    public function delStruceureById()
    {
        $taskModel = model('Task');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['create_user_id'] = $userInfo['id']; 
        $ary = array('structure_id_del','structure_id_add');
        if (!in_array($param['type'], $ary)) {
            return resultArray(['error'=>'参数错误']);
        }
        $res = $taskModel->updateDetTask($param);
        if ($res) {
            return resultArray(['data'=>'操作成功']);
        } else {
            return resultArray(['error'=>$taskModel->getError()]);
        }
    }

    /**
     * 设置任务截止时间
     * @author yykun
     * @return
     */
    public function updateStoptime()
    {
        $taskModel = model('Task');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['create_user_id'] = $userInfo['id'];
        if (!$param['stop_time']) {
            return resultArray(['error'=>'参数错误']);
        }
        if ($taskModel->updateDetTask($param)) {
            return resultArray(['data'=>'操作成功']);
        } else {
            return resultArray(['error'=>$taskModel->getError()]);
        }
    }

    /**
     * 修改任务标签
     * @author yykun
     * @return
     */
    public function updateLable()
    {
        $taskModel = model('Task');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['create_user_id'] = $userInfo['id']; 
        $ary = array('lable_id_add','lable_id_del');
        if (!in_array($param['type'], $ary)) {
            return resultArray(['error'=>'参数错误']);
        }
        if (isset($param['lable_id_add']) && !is_array($param['lable_id_add'])) {
            $label_id_arr[] = $param['lable_id_add'];
            $param['lable_id_add'] = $label_id_arr;
        }
        if (isset($param['lable_id_del']) && !is_array($param['lable_id_del'])) {
            $label_id_arr[] = $param['lable_id_del'];
            $param['lable_id_del'] = $label_id_arr;
        }        
        if ($taskModel->updateDetTask($param)) {
            return resultArray(['data'=>'操作成功']);
        } else {
            return resultArray(['error'=>$taskModel->getError()]);
        }
    }

    /**
     * 修改任务名称
     * @author yykun
     * @return
     */
    public function updateName()
    {
        $taskModel = model('Task');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['create_user_id'] = $userInfo['id']; 
        if ($param['type'] !== 'name') {
            return resultArray(['error'=>'参数错误']);
        }
        $res = $taskModel->updateDetTask($param);
        if ($res) {
            return resultArray(['data'=>'操作成功']);
        } else {
            return resultArray(['error'=>$taskModel->getError()]);
        }
    }

    /**
     * 任务标记结束
     * @author yykun
     * @return
     */
    public function taskOver()
    {
        $taskModel = model('Task');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['create_user_id'] = $userInfo['id']; 
        if (!$param['task_id'] || !$param['type'] ){
            return resultArray(['error'=>'参数错误']);
        }
		$taskInfo = Db::name('Task')->where(['task_id' => $param['task_id']])->find();
        if ($param['type'] == '1') {
            $flag = Db::name('Task')->where(['task_id' => $param['task_id']])->setField('status',5);
			if ($flag && !$taskInfo['pid']) {
				$temp['user_id'] = $userInfo['id'];
				$temp['content'] = '任务标记结束';
				$temp['create_time'] = time();
				$temp['task_id'] = $param['task_id'];
				Db::name('WorkTaskLog')->insert($temp);
				actionLog($taskInfo['task_id'],$taskInfo['owner_user_id'],$taskInfo['structure_ids'],'任务标记结束');
                //抄送站内信
                $sendUserArr = [];
                $sendUserArr[] = $taskInfo['create_user_id'];
                if ($taskInfo['main_user_id']) {
                    $sendUserArr[] = $taskInfo['main_user_id'];
                }
                if ($taskInfo['owner_user_id']) {
                   $sendUserArr = $sendUserArr ? array_merge($sendUserArr,stringToArray($taskInfo['owner_user_id'])) : stringToArray($taskInfo['owner_user_id']); 
                }
                if ($sendUserArr) {
                    (new Message())->send(
                        Message::TASK_OVER,
                        [
                            'title' => $taskInfo['name'],
                            'action_id' => $param['task_id']
                        ],
                        $sendUserArr
                    );
                }
			}
        } else {
            $flag = Db::name('Task')->where('task_id ='.$param['task_id'])->setField('status',1);
			if ($flag && !$taskInfo['pid']) {
				$temp['user_id'] = $userInfo['id'];
				$temp['content'] = '任务标记开始';
				$temp['create_time'] = time();
				$temp['task_id'] = $param['task_id'];
				Db::name('WorkTaskLog')->insert($temp);
				actionLog($taskInfo['task_id'],$taskInfo['owner_user_id'],$taskInfo['structure_ids'],'任务标记开始');
			}
        }
        if ($flag) {
            return resultArray(['data' => true ]);
        } else {
            return resultArray(['error' => '标记失败' ]);
        }
    }

    /**
     * 日历任务展示/月份
     * @author yykun
     * @return
     */
    public function dateList() 
    {
        $param = $this->param;
        $taskModel = model('Task');
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];
        $data = $taskModel->getDateList($param);
        return resultArray(['data'=>$data]);
    }

    /**
     * 添加任务
     * @author Michael_xu
     * @return
     */
    public function save()
    {
        $param = $this->param;
        $taskModel = model('Task');
        $workModel = model('Work');
        if (!$param['name']) {
            return resultArray(['error'=>'参数错误']);
        }
		$userInfo = $this->userInfo;
        $param['create_user_id'] = $userInfo['id']; 
		$param['create_user_name'] = $userInfo['realname']; 
        //权限判断
        if ($param['work_id'] && !$workModel->isCheck('work','task','save',$param['work_id'],$userInfo['id'])) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));            
        }        
        $res = $taskModel->createTask($param);
        if ($res) {
            return resultArray(['data'=>$res]);
        } else {
            return resultArray(['error'=>$taskModel->getError()]);
        }
    }

    /**
     * 删除主负责人
     * @author yykun
     * @return
     */
    public function delMainUserId()
    {
        $param = $this->param;
        $workModel = model('Task');
        if ($param['task_id']) {
            $userInfo = $this->userInfo;
            $param['create_user_id'] = $userInfo['id']; 
			$taskInfo = Db::name('Task')->where(['task_id' => $param['task_id']])->find();
            $data = [];
            $data['main_user_id'] = '';
            $data['status'] = 1;
			$flag = Db::name('Task')->where(['task_id' => $param['task_id']])->update($data);
			if ($flag && !$taskInfo['pid']) {
				actionLog($taskInfo['task_id'],$taskInfo['owner_user_id'],$taskInfo['structure_ids'],'删除负责人');
                return resultArray(['data'=>'操作成功']);
			}
            return resultArray(['error'=>'操作失败']);
        } else {
            return resultArray(['error'=>'参数错误']);
        }
    }

    /**
     * 重命名任务
     * @author yykun
     * @return
     */
    public function rename()
    {
        $param = $this->param;
        $workModel = model('Work');
        if (!$param['rename'] || !$param['work_id']) {
            return resultArray(['error'=>'参数错误']);
        }
		$userInfo = $this->userInfo;
		$param['create_user_id'] = $userInfo['id']; 
        $flag = $workModel->rename($param);
        if ($flag) {
            return resultArray(['data'=>'编辑成功']);
        } else {
            return resultArray(['error'=>$workModel->getError()]);
        }
    }
	
    /**
     * 删除任务
     * @author yykun
     * @return
     */
    public function delete()
    {
        $param = $this->param;
        $taskModel = model('Task');
        if (!$param['task_id']) {
            return resultArray(['error'=>'参数错误']);
        }
        $userInfo = $this->userInfo;
		$param['create_user_id'] = $userInfo['id']; 
        $flag = $taskModel->delTaskById($param);
        if ($flag) {
            return resultArray(['data'=>'删除成功']);
        } else {
            return resultArray(['error'=>$taskModel->getError()]);
        }
    }

    /**
     * 归档任务
     * @author yykun
     * @return
     */
    public function archive()
    {
        $param = $this->param;
        $taskModel = model('Task');
        if (!$param['task_id']) {
            return resultArray(['error'=>'参数错误']);
        }
        $userInfo = $this->userInfo;
		$param['create_user_id'] = $userInfo['id']; 
        $flag = $taskModel->archiveData($param);
        if ($flag) {
			$temp['user_id'] = $userInfo['id'];
			$temp['content'] = '归档任务';
			$temp['create_time'] = time();
			$temp['task_id'] = $param['task_id'];
			Db::name('WorkTaskLog')->insert($temp);
            return resultArray(['data'=>'归档成功']);
        } else {
            return resultArray(['error'=>$taskModel->getError()]);
        }
    }

    /**
     * 恢复归档任务
     * @author yykun
     * @return
     */
    public function recover()
    {
        $param = $this->param;
        $taskModel = model('Task');
        if (!$param['task_id']) {
            return resultArray(['error'=>'参数错误']);
        }
        $userInfo = $this->userInfo;
		$param['create_user_id'] = $userInfo['id']; 
        $flag = $taskModel->recover($param);
        if ($flag) {
			$temp['user_id'] = $userInfo['id'];
			$temp['content'] = '恢复归档任务';
			$temp['create_time'] = time();
			$temp['task_id'] = $param['task_id'];
			Db::name('WorkTaskLog')->insert($temp);
            return resultArray(['data'=>'操作成功']);
        } else {
            return resultArray(['error'=>$taskModel->getError()]);
        }
    }

    /**
     * 归档任务列表
     * @author yykun
     * @return
     */
    public function archList()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $taskModel = model('Task');
        if (!$param['work_id']) return resultArray(['error'=>'参数错误']);
        $request = [];
        $request['work_id'] = $param['work_id'];
        $request['is_archive'] = 1;
        $list = $taskModel->getTaskList($request);
        return resultArray(['data'=>$list]);
    }

    /**
     * 归档某一类已完成任务
     * @author yykun
     * @return
     */
    public function archiveTask()
    {   
        $param = $this->param;
        if (!$param['class_id']) return resultArray(['error'=>'参数错误']);
        $data = array();
        $data['is_archive'] = 1;
        $data['archive_time'] = time();
        $res = db('task')->where(['class_id' => $param['class_id'],'status' => '5'])->update($data);
        if ($res) {
            return resultArray(['data' => '操作成功']);
        } else {
            return resultArray(['error' => '暂无已完成任务，归档失败！']);
        }
    }
}