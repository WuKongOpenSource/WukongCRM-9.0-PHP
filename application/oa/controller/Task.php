<?php
// +----------------------------------------------------------------------
// | Description: 任务及基础
// +----------------------------------------------------------------------
// | Author:  yykun
// +----------------------------------------------------------------------

namespace app\oa\controller;

use think\Request;	
use think\Session;
use think\Hook;
use app\admin\controller\ApiCommon;
use think\helper\Time;
use think\Db;
use app\work\model\Task as TaskModel;

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
            'allow'=>['index','mytask','subtasklist','updatetop','updateorder','read','update','readloglist','updatepriority','updateowner','updatestructure','updateownerid','delownerbyid','delstruceurebyid','updatestoptime','updatelable','updatename','taskover','datelist','save','delmainuserid','rename','delete','archive','recover','archlist','archivetask','setover','worklist']  //需要登录才能访问          
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());
		
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
        $param = $this->param;
        if($param['task_id']){
            $userInfo = $this->userInfo;
            $taskModel = new TaskModel(); 
            $ret = $taskModel->checkTask($param['task_id'], $userInfo);
            if (!$ret) {
                header('Content-Type:application/json; charset=utf-8');
                exit(json_encode(['code'=>102,'error'=>'没有权限']));
            }
        }
    }
	
	//判断任务(需创建人和负责人才能编辑删除)
	public function checkSub($task_id)
	{
		$userInfo = $this->userInfo;
		$taskDet = Db::name('Task')->where('task_id = '.$task_id)->find();
		$main_user_ids = stringToArray($taskDet['main_user_id']);
		if ($taskDet['create_user_id'] == $userInfo['id'] || in_array($userInfo['id'],$main_user_ids)) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
     * 查看下属创建的任务 
     * @author 
     * @param   //负责和参与
     * @return
     */
	public function subTaskList()
	{
		$param = $this->param;
		$userInfo = $this->userInfo;
		$userModel = new \app\admin\model\User();
		$lableModel = new \app\work\model\WorkLable();
		$taskModel = new \app\work\model\Task();
		$subList = getSubUserId(false);
		$subStr = $subList ? implode(',',$subList) : '-1';
		$subValue = $subList ? arrayToString($subList) : '';

		$search = $param['search'];
		if ($search) {
			$where['name'] = array('like','%'.$search.'%');
		}		
		$where['ishidden'] = 0;
		$where['pid'] = 0;
		if ($param['work_id']) {
			$where['work_id'] = $param['work_id'];
		}
		if ($param['status']) {
			$where['status'] = $param['status'];
		} else {
			$where['status'] = [['=',1],['=',5],'OR'];
		}
		$priority = $param['priority'] ? : '0';
		if ($priority == 'all') {
			$where['priority'] = ['egt',0];
		} else {
			$where['priority'] = $priority;
		}
		if ($param['stop_type']) {
			switch ($param['stop_type']) {
				case '1': //今天到期
					$timeAry = getTimeByType('today');
					break;
				case 2: //明天到期
					$temp = getTimeByType('today');
					$timeAry[0] = $temp[1];
					$timeAry[1] = $temp[1]+3600*24;
					break;
				case 3: //一周内到期
					$timeAry = getTimeByType('week');
					break;
				case 4: //一月内到期
					$timeAry = getTimeByType('month');
					break;
				default:
					break;
			}
			$where['stop_time'] = ['between',''.$timeAry[0].','.$timeAry[1].''];
		}
		$type_temp = $param['type'] ? : 'mymain';
   		if ($param['main_user_id']) {
   			$where['main_user_id'] = $param['main_user_id'];
   		}
		if ($type_temp) {
			if ($type_temp == 'mycreate') {
				//我负责的
				$type = 'create_user_id in ('.$subStr.')';
			} elseif ($type_temp == 'mymain') {
				$type = 'main_user_id in ('.$subStr.')';
			} else {
				$type = 'owner_user_id like "%,'.$subValue.',%"';
			}
		} else {
			$type = ' main_user_id in ('.$subStr.') or create_user_id in ('.$subStr.') or owner_user_id like "%,'.$subValue.',%"';
		}
		$taskList = Db::name('Task')->field('task_id,name,create_user_id,main_user_id,owner_user_id,status,priority,pid,start_time,stop_time,work_id,order_id,create_time,lable_id')
			->where($where)
			->where(function($query) use($type){
				$query->where($type);
			})
			->page($param['page'], $param['limit'])
			->order('task_id desc')
			->select();
		$dataCount = db('task')
			->where($where)
			->where(function($query) use($type){
				$query->where($type);
			})->count();	
		foreach ($taskList as $k=>$v) {
			$temp = array();
			$temp = $v;
			if ($v['pid']) {
				$ptask = Db::name('Task')->field('name')->where('task_id ='.$v['pid'])->find();
				$taskList[$k]['pname'] = $ptask['name'];
			}
			$taskList[$k]['task_name'] = $v['name'];
			$subcount = Db::name('Task')->where('status=1 and pid = '.$v['task_id'])->count();
			$subdonecount = Db::name('Task')->where('status = 5 and pid = '.$v['task_id'])->count();
			$taskList[$k]['subcount'] = $subcount; //子任务
			$taskList[$k]['subdonecount'] = $subdonecount; //已完成子任
			$taskList[$k]['commentcount'] = Db::name('AdminComment')->where(['type' => 'task','type_id' => $v['task_id']])->count();
			$taskList[$k]['filecount'] = Db::name('WorkTaskFile')->where('task_id ='.$v['task_id'])->count();
			if ($v['lable_id']) {
				$taskList[$k]['lableList'] = $lableModel->getDataByStr($v['lable_id']);
			} else {
				$taskList[$k]['lableList'] = array();
			}
			$taskList[$k]['main_user'] = $v['main_user_id']?$userModel->getDataById($v['main_user_id']):array();
			$taskList[$k]['relationCount'] = $taskModel->getRelationCount($v['task_id']);
			$is_end = 0;
			if (!empty($v['stop_time']) && (strtotime(date('Ymd'))+86400 > $v['stop_time'])) $is_end = 1;
			$taskList[$k]['is_end'] = $is_end;			
		}
		$data = [];
        $data['list'] = $taskList ? : [];
        $data['dataCount'] = $dataCount ? : 0;		
		return resultArray(['data'=>$data]);
	}
	
	/**
     * 查看所有的项目
     * @author 
     * @param  
     * @return
     */	
	public function workList()
	{
		$count = Db::name('Work')->where('status =1')->count();
		$workList = Db::name('Work')->field('work_id,name')->where('status =1')->select();
		$data['list'] = $workList;
		$data['count'] = $count;
		return resultArray(['data'=>$data]);
	}
	
	/**
     * 查看某个项目下任务列表
     * @author 
     * @param  
     * @return
     */	
    public function index()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $taskModel = new \app\work\model\Task(); 
        if ( !$param['work_id'] ) {
            return resultArray(['error' => '参数错误']);
        }
        $list =  $taskModel->getDataList($param, $userInfo['id']);    
        if ( $list ) {
            return resultArray(['data' => $list]);
        } else {
            return resultArray(['error' => $taskModel->getError()]);
        }
    }    

	/**
     * 查看我的任务
     * @author 
     * @param  
     * @return
     */
    public function myTask()
    {	
		$userModel = new \app\admin\model\User();
        $lableModel = new \app\work\model\WorkLable();
        $taskModel = new \app\work\model\Task();
        $param = $this->param;
        $userInfo = $this->userInfo;
        $str = ','.$userInfo['id'].',';
        $search = $param['search'];
		if ($search) {
			$where = "t.name LIKE '%".$search."%' and ";
		}
        if (isset($param['type']) && $param['type']) {
        	$type = $param['type'];
        } else {
        	$type = ''; //mymain
        }
		if ($param['status'] =='1' || $param['status']=='5') {
			$where = $where.'t.status ='.$param['status'].' and ';
		} else {
			$where = $where.' (t.status =1 or t.status=5) and ';
		}
		if ($param['work_id']) {
			$where = $where.' t.work_id ='.$param['work_id'].' and ';
		}
		$priority = $param['priority'] ? : '0';
		if ($priority == 'all') {
			$where = $where.' t.priority >= 0 and ';
		} else {
			$where = $where.' t.priority = '.$priority.' and ';
		}

		if ($type) {
			if ($param['type']=='mycreate') { //我创建的
				$type = 't.create_user_id ='.$userInfo['id'].'';
			} elseif ($param['type']=='mymain') { //我负责的
				$type = 't.main_user_id ='.$userInfo['id'].'';
			} elseif ($param['type'] == 'myown'){ //我参与的
				$type = 't.owner_user_id like "%,'.$userInfo['id'].',%"';
			} else {
				$type = 't.main_user_id ='.$userInfo['id'].' or  ( t.is_open = 1 and t.owner_user_id like "%'.$str.'%")';
			}
		} else {
			$type = 't.main_user_id ='.$userInfo['id'].' or t.create_user_id ='.$userInfo['id'].' or  ( t.is_open = 1 and t.owner_user_id like "%'.$str.'%")';
		}
		if ($param['stop_type']) {
			switch ($param['stop_type']) {
				case '1': //今天到期
					$timeAry = getTimeByType('today');
					break;
				case 2: //明天到期
					$temp = getTimeByType('today');
					$timeAry[0] = $temp[1];
					$timeAry[1] = $temp[1]+3600*24;
					break;
				case 3: //一周内到期
					$timeAry = getTimeByType('week');
					break;
				case 4: //一月内到期
					$timeAry = getTimeByType('month');
					break;
				default:
					break;
			}
			$map['t.stop_time'] = ['between',''.$timeAry[0].','.$timeAry[1].''];
		}
		$map['t.pid'] = 0;
		$taskList = Db::name('Task')->alias('t')
				->join('AdminUser u','u.id = t.main_user_id','LEFT') 
				->join('Work w','w.work_id = t.work_id','LEFT')
				->field('t.task_id,t.name as task_name,t.main_user_id,t.is_top,t.work_id,t.lable_id,t.priority,t.stop_time,t.status,t.pid,t.create_time,t.owner_user_id,u.realname as main_user_name,u.thumb_img,w.name as work_name')
				->where( $where.' t.ishidden=0 and ( '.$type.' )')->where($map)
				->page($param['page'], $param['limit'])
				->order('t.task_id desc')
				->select();
		$dataCount = db('task')->alias('t')->where( $where.' t.ishidden=0 and ( '.$type.' )')->where($map)->count();	
		foreach ($taskList as $key => $value) {
			if ($value['pid']>0) {
				$p_det = Db::name('Task')->field('task_id,name')->where('task_id ='.$value['pid'])->find();
				$taskList[$key]['pname'] = $p_det['name'];
			} else {
				$taskList[$key]['pname'] = '';
			}
			$taskList[$key]['thumb_img'] = $value['thumb_img']?getFullPath($value['thumb_img']):'';
			$subcount = Db::name('Task')->where(' ishidden =0 and ( status=1 ) and  pid ='.$value['task_id'])->count();
			$subdonecount = Db::name('Task')->where(' ishidden = 0 and status = 5 and pid ='.$value['task_id'])->count();
			$taskList[$key]['subcount'] = $subcount; //子任务
			$taskList[$key]['subdonecount'] = $subdonecount; //已完成子任务
			$taskList[$key]['commentcount'] = Db::name('AdminComment')->where(['type' => 'task','type_id' => $value['task_id']])->count();
			$taskList[$key]['filecount'] = Db::name('WorkTaskFile')->where('task_id ='.$value['task_id'])->count();	
			if ($value['lable_id']) {
				$temp_lableList =  $lableModel->getDataByStr($value['lable_id']);
				$taskList[$key]['lableList'] = $temp_lableList?:array();
			} else {
				$taskList[$key]['lableList'] = array();
			}
			//参与人列表数组
			//$userlist =$userModel->getDataByStr($value['owner_user_id']);
			//$taskList[$key]['own_list'] = $userlist?$userlist: array(); 
			//负责人信息
			$taskList[$key]['main_user'] = $value['main_user_id']?$userModel->getDataById($value['main_user_id']):array();
			$taskList[$key]['relationCount'] = $taskModel->getRelationCount($value['task_id']);
			$is_end = 0;
			if (!empty($value['stop_time']) && (strtotime(date('Ymd'))+86399 > $value['stop_time'])) $is_end = 1;
			$taskList[$key]['is_end'] = $is_end;
		}
        $data = [];
        $data['list'] = $taskList ? : [];
        $data['dataCount'] = $dataCount ? : 0;		
		return resultArray(['data'=>$data]);
    }

	/**
     * 获取任务详情
     * @author 
     * @param  
     * @return
     */
    public function read()
    {   
        $param = $this->param;
        $userInfo = $this->userInfo;
        if ($param['task_id']) {
            $model = new \app\work\model\Task();
            $det = $model->getDataById($param['task_id'], $userInfo);
            if ($det) {
                return resultArray(['data'=>$det]);
            } else {
                return resultArray(['error'=>$model->getError()]);
            }
        } else {
            return resultArray(['error'=>'参数错误']);
        }
    }

	/**
     * 任务编辑保存 
     * @author 
     * @param  
     * @return
     */ 
    public function update()
    {
        $model 					 = new \app\work\model\Task(); 
        $param 					 = $this->param;
		$userInfo   			 = $this->userInfo;
        $param['create_user_id'] = $userInfo['id']; 
        
        $ary = array('owner_userid_del','owner_userid_add','stop_time','lable_id_add','lable_id_del','name','structure_id_del','structure_id_add');
        if ((in_array( $param['type'], $ary))) {
            return resultArray(['error'=>'参数错误']);
        }
        if(isset($param['main_user_id'])){
        	$rett = $this->checkSub($param['task_id']); //判断编辑权限
	        if(!$rett){
				return resultArray(['error'=>'没有权限']);
			}
        }
        $ret = $model->createDetTask($param);
        if ($ret) {
            return resultArray(['data'=>'操作成功']);
        } else {
            return resultArray(['error'=>$model->getError()]);
        }
    }
        
	/**
     * 解除关联关系
     * @author 
     * @param  
     * @return
     */ 
	public function delrelation()
	{
		$param = $this->param;
		if ($param['task_id'] && $param['type'] && $param['id']) {
			$taskInfo = Db::name('Task')->where('task_id = '.$param['task_id'])->find();
			$det = Db::name('TaskRelation')->where('task_id ='.$param['task_id'])->find();
			if ($param['type'] == '1') {
				$newstr = str_replace(','.$param['id'].',',',',$det['customer_ids']);
				$newdata['customer_ids'] = $newstr;
			} else if($param['type'] == '2') {
				$newstr = str_replace(','.$param['id'].',',',',$det['contacts_ids']);
				$newdata['contacts_ids'] = $newstr;
			} else if($param['type'] == '3') {
				$newstr = str_replace(','.$param['id'].',',',',$det['business_ids']);
				$newdata['business_ids'] = $newstr;
			} else if($param['type'] == '4') {
				$newstr = str_replace(','.$param['id'].',',',',$det['contract_ids']);
				$newdata['contract_ids'] = $newstr;
			}
			$flag = Db::name('TaskRelation')->where('task_id = '.$param['task_id'].'')->update($newdata);
			if ($flag) {
				if( $flag && !$taskInfo['pid']){
					actionLog( $taskInfo['task_id'],$taskInfo['owner_user_id'],$taskInfo['structure_ids'],'编辑关联关系'); 
				}
				return resultArray(['data'=>true]);
			} else {
				return resultArray(['error'=>'操作失败']);
			}
		} else {
			return resultArray(['error'=>'参数错误']);
		}
	}

	/**
     * 获取任务操作记录
     * @author 
     * @param  
     * @return
     */ 
    public function readLoglist()
    {
        $param = $this->param;
        $taskModel = new \app\work\model\Task(); 
        if ($param['task_id']) {
            $list = $taskModel->getTaskLogList($param);
            if ($list) {
                return resultArray(['data'=>$list]); 
            } else {
                return resultArray(['data'=>array()]);
            }
        } else {
            return resultArray(['error'=>'参数错误']);
        }
    }
 
	/**
     * 优先级设置
     * @author 
     * @param  
     * @return
     */
    public function updatePriority()
    {
        $model                   = new \app\work\model\Task(); // model('Task');
        $param                   = $this->param;
        $userInfo                = $this->userInfo;
        $param['create_user_id'] = $userInfo['id']; 
     	
        if ( isset( $param['priority_id'] ) && $param['task_id']) {
			$taskInfo = Db::name('Task')->where('task_id = '.$param['task_id'].'')->find();
            $flag = Db::name('Task')->where('task_id ='.$param['task_id'])->setField('priority',$param['priority_id']);
            if ($flag) {
				if( $flag && !$taskInfo['pid']){
					actionLog( $taskInfo['task_id'],$taskInfo['owner_user_id'],$taskInfo['structure_ids'],'修改优先级'); 
				}
                return resultArray(['data'=>'操作成功']);
            } else {
                return resultArray(['error'=>'操作失败']);
            }
        } else {
            return resultArray(['error'=>'参数错误']);
        }
    }
 
	/**
     * 参与人/参与部门编辑
     * @author 
     * @param  
     * @return
     */
    public function updateOwner()
    {
        $taskModel = new \app\work\model\Task(); 
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['create_user_id'] = $userInfo['id'];
		
		$ary = array('owner_userids','structure_ids');
        if (!isset($param['structure_ids'])) {
            //清除所有部门
            Db::name('Task')->where('task_id = '.$param['task_id'])->setField('structure_ids','');
        }
        if (!isset($param['owner_userids'])) {
            //清除所有参与人
            Db::name('Task')->where('task_id = '.$param['task_id'])->setField('owner_user_id','');
        }
        if ($param) {
            $taskDet = Db::name('Task')->field('task_id,structure_ids,owner_user_id')->where('task_id ='.$param['task_id'])->find();
            $tructure = substr($taskDet['structure_ids'],1,strlen($taskDet['structure_ids'])-2);
            if ($param['structure_ids']) {   //部门编辑
                if ($tructure) {
                    $oldstructure_ids = explode(',', $tructure); 
                } else {
                    $oldstructure_ids = array();
                }
                $structure_ary_temp = array_intersect($oldstructure_ids,$param['structure_ids']); //交集
                //删除
                $structure_ary_del = array_diff($oldstructure_ids,$structure_ary_temp);

                foreach ($structure_ary_del as $k1 => $v1) { 
                    //循环删除参与部门
                    $data1['type'] = 'structure_id_del';
                    $data1['structure_id_del'] = $v1;
                    $data1['task_id'] = $param['task_id'];
                    $this->updateStructure($data1);
                } 
                //添加
                $structure_ary_add = array_diff($param['structure_ids'],$structure_ary_temp);
                foreach ($structure_ary_add as $k2 => $v2) { 
                    //循环添加参与部门
                    $data2['type'] = 'structure_id_del';
                    $data2['structure_id_add'] = $v2;
                    $data2['task_id'] = $param['task_id'];
                    $this->updateStructure($data2);
                }
				
				actionLog( $param['task_id'],$param['owner_user_id'],$param['structure_ids'],'修改了部门');
            }  

            $ownerid = substr($taskDet['owner_user_id'],1,strlen($taskDet['owner_user_id'])-2);
            if ($param['owner_userids']) { //参与人编辑
                if ($ownerid) {
                    $oldowneridary = explode(',', $ownerid);
                } else {
                    $oldowneridary = array();
                }
                
                $owner_ary_temp = array_intersect($oldowneridary,$param['owner_userids']); //交集
                //删除
                $owner_ary_del = array_diff($oldowneridary,$owner_ary_temp);

                foreach ($owner_ary_del as $k3 => $v3) {
                    $data3['type'] = 'owner_userid_del';
                    $data3['owner_userid_del'] = $v3;
                    $data3['task_id'] = $param['task_id'];
                    $this->updateOwnerId($data3);
                }
				
                //添加
                $owner_ary_add = array_diff($param['owner_userids'],$owner_ary_temp);
                foreach ($owner_ary_add as $k4 => $v4) {
                    $data4['type'] = 'owner_userid_add';
                    $data4['owner_userid_add'] = $v4;
                    $data4['task_id'] = $param['task_id'];
                    $this->updateOwnerId($data4);
                }
				actionLog( $param['task_id'],$param['owner_user_id'],$param['structure_ids'],'修改了参与人');
            } 
            return resultArray(['data'=>true]);
        } else {
            return resultArray(['error'=>'参数错误']);
        }
    }

	/**
     * 任务参与部门保存更新
     * @author 
     * @param  
     * @return
     */    
    public function updateStructure($param)
    {
        $model                   = new \app\work\model\Task(); 
        $userInfo                = $this->userInfo;
        $ary = array('structure_id_del','structure_id_add');
        if ( in_array($param['type'], $ary) ) {
			//操作部门
            //$ret = $model->createDetTask($param);
			$det = Db::name('Task')->where('task_id = '.$param['task_id'])->find();
			if ( $param['type'] == 'structure_id_del' ) {	 //删除参与部门
				$temp['structure_ids']=str_replace(','.$param['structure_id_del'].',',',',$det['structure_ids']); //删除
			}
			if ( $param['type'] == 'structure_id_add' )  {	//添加参与部门 
				$structuredet = $StructureModel->getDataById($param['owner_userid_add']);
				if ( $det['structure_ids'] && ( $det['structure_ids'] != ',,' ) ) {
					$temp['structure_ids'] = $det['structure_ids'].$param['structure_id_add'].','; //追加
				} else {
					$temp['structure_ids'] = ','.$param['structure_id_add'].','; //首次添加
				}
			}
			unset($temp['create_user_id']);
			$ret = Db::name('Task')->where('task_id = '.$param['task_id'].'')->update($temp);
            if ($ret) {
                return true;
            } else {
                return false;
            }
        }
    }

	/**
     * 任务参与人保存更新 参与部门
     * @author 
     * @param  
     * @return
     */
    public function updateOwnerId($param)
    {
		$userModel = new \app\admin\model\User(); //员工模型
        $model                   = new \app\work\model\Task(); 
        $userInfo                = $this->userInfo;
        $temp['create_user_id'] = $userInfo['id']; 
		//操作参与人
		$det = Db::name('Task')->where('task_id = '.$param['task_id'])->find();
		if ($param['type'] == 'owner_userid_del') {	//删除参与成员 
			$temp['owner_user_id']=str_replace(','.$param['owner_userid_del'].',',',',$det['owner_user_id']); //删除
		
		}
		if ($param['type'] == 'owner_userid_add')  { //添加参与成员 
			if ( $det['owner_user_id']&&($det['owner_user_id'] != ',,') ){
				$temp['owner_user_id'] = $det['owner_user_id'].$param['owner_userid_add'].','; //追加
			} else {
				$temp['owner_user_id'] = ','.$param['owner_userid_add'].','; //首次添加
			}
		}
		unset($temp['create_user_id']);
		$ret = Db::name('Task')->where('task_id = '.$param['task_id'].'')->update($temp);	
        if ($ret) {
            return true;
        } else {
            return false;
        }
    } 
	
	/**
     * 单独删除参与人
     * @author 
     * @param  
     * @return
     */    
    public function delOwnerById()
    {
        $model                   = new \app\work\model\Task(); 
        $userInfo                = $this->userInfo;
        $param                   = $this->param;
        $param['create_user_id'] = $userInfo['id']; 
		
        $ary = array('owner_userid_del','owner_userid_add');
        if ( in_array($param['type'], $ary) ) {
            $ret = $model->createDetTask($param);
            if ($ret) {
                return resultArray(['data'=>'操作成功']);
            } else {
                return resultArray(['error'=>$model->getError()]);
            }
        } else {
            return resultArray(['error'=>'参数错误']);
        }
    }

	/**
     * 单独删除参与部门
     * @author 
     * @param  
     * @return
     */
    public function delStruceureById()
    {
        $model                   = new \app\work\model\Task(); 
        $param                   = $this->param;
        $userInfo                = $this->userInfo;
        $param['create_user_id'] = $userInfo['id']; 

        $ary = array('structure_id_del','structure_id_add');
        if ( in_array($param['type'], $ary) ) {
            $ret = $model->createDetTask($param);
            if ($ret) {
                return resultArray(['data'=>'操作成功']);
            } else {
                return resultArray(['error'=>$model->getError()]);
            }
        }
    }

	/**
     * 设置任务截止时间
     * @author 
     * @param  
     * @return
     */
    public function updateStoptime()
    {
        $model                   = new \app\work\model\Task(); 
        $param                   = $this->param;
        $userInfo                = $this->userInfo;
        $param['create_user_id'] = $userInfo['id']; 
        if ( !isset( $param['stop_time'] ) ) {
            return resultArray(['error'=>'参数错误']);
        }
		
		$rett = $this->checkSub($param['task_id']); //判断编辑权限
        if(!$rett){
			return resultArray(['error'=>'没有权限']);
		}
		
        $ret = $model->createDetTask($param);
        if ( $ret ) {
            return resultArray(['data'=>'操作成功']);
        } else {
            return resultArray(['error'=>$model->getError()]);
        }
    }

	/**
     * 添加删除标签 
     * @author 
     * @param  
     * @return
     */ 
    public function updateLable()
    {
        $model                   = new \app\work\model\Task(); 
        $param                   = $this->param;
        $userInfo                = $this->userInfo;
        $param['create_user_id'] = $userInfo['id']; 
        
        $ary = array('lable_id_add','lable_id_del');
        if ( in_array($param['type'], $ary) ) {
            $ret = $model->createDetTask($param);
            if ( $ret ) {
                return resultArray(['data'=>'操作成功']);
            } else {
                return resultArray(['error'=>$model->getError()]);
            }
        } else {
            return resultArray(['error'=>'参数错误']);
        }        
    
    }

	/**
     * 任务标题描述更新
     * @author 
     * @param  
     * @return
     */
    public function updateName()
    {
        $model                   = new \app\work\model\Task(); 
        $param                   = $this->param;
        $userInfo                = $this->userInfo;
        $param['create_user_id'] = $userInfo['id']; 
        if ( $param['type'] == 'name' ) {
            $ret = $model->createDetTask($param);
            if ($ret) {
                return resultArray(['data'=>'操作成功']);
            } else {
                return resultArray(['error'=>$model->getError()]);
            }
        } else {
            return resultArray(['error'=>'参数错误']);
        }
    }

	/**
     * 任务标记结束
     * @author 
     * @param  
     * @return
     */ 
    public function taskOver()
    {
        $model                   = new \app\work\model\Task(); 
        $param                   = $this->param;
        $userInfo                = $this->userInfo;
        $param['create_user_id'] = $userInfo['id']; 
        if ($param['task_id'] && $param['type']) {
			$taskInfo = Db::name('task')->where('task_id = '.$param['task_id'].'')->find();
            if ($param['type'] == '1') {
                $flag = Db::name('Task')->where('task_id ='.$param['task_id'])->setField('status',5);
				if( $flag && !$taskInfo['pid']){
					
					$temp['user_id'] = $userInfo['id'];
					$temp['content'] = '任务标记结束';
					$temp['create_time'] = time();
					$temp['task_id'] = $param['task_id'];
					Db::name('WorkTaskLog')->insert($temp);
					actionLog( $taskInfo['task_id'],$taskInfo['owner_user_id'],$taskInfo['structure_ids'],'任务标记结束'); //
				}
            } else {
                $flag = Db::name('Task')->where('task_id ='.$param['task_id'])->setField('status',1);
				if( $flag && !$taskInfo['pid']){
				
					$temp['user_id'] = $userInfo['id'];
					$temp['content'] = '任务标记开始';
					$temp['create_time'] = time();
					$temp['task_id'] = $param['task_id'];
					Db::name('WorkTaskLog')->insert($temp);
					actionLog( $taskInfo['task_id'],$taskInfo['owner_user_id'],$taskInfo['structure_ids'],'任务标记开始'); //
				}
            }
            return resultArray(['data' => true ]);
        } else {
            return resultArray(['error'=>'参数错误']);
        }
    }

	/**
     * 日历任务展示/月份
     * @author 
     * @param  
     * @return
     */ 
    public function dateList() 
    {
        $param = $this->param;
        $model = new \app\work\model\Task(); 
        $ret = $model->getDateList($param['start_time'], $param['stop_time']);
        if ($ret) {
            return resultArray(['data'=>$ret]);
        } else {
            return resultArray(['error'=>$model->getError()]);
        }
    }

	/**
     * 添加任务
     * @author 
     * @param  
     * @return
     */ 
    public function save()
    {
        $param = $this->param;
        $taskModel = new \app\work\model\Task(); 
        if ($param['name']) {
			$userInfo   			 = $this->userInfo;
			$param['create_user_id'] = $userInfo['id']; 
            $flag = $taskModel->createTask($param);
            if ($flag) {
                return resultArray(['data'=>$flag]);
            } else {
                return resultArray(['error'=>$workModel->getError()]);
            }
        } else {
            return resultArray(['error'=>'参数错误']);
        }
    }

	/**
     * 删除主负责人
     * @author 
     * @param  
     * @return
     */
    public function delMainUserId()
    {
        $param = $this->param;
        $workModel = new \app\work\model\Task(); 
        if ($param['task_id']) {
			$rett = $this->checkSub($param['task_id']); //判断编辑权限
			if(!$rett){
				return resultArray(['error'=>'没有权限']);
			}
            $userInfo                = $this->userInfo;
            $param['create_user_id'] = $userInfo['id']; 
			$taskInfo = Db::name('Task')->where('task_id = '.$param['task_id'])->find();
            $flag =  Db::name('Task')->where('task_id ='.$param['task_id'])->setField('main_user_id','');
            if ($flag) {
				if( !$taskInfo['pid']){
					actionLog( $taskInfo['task_id'],$taskInfo['owner_user_id'],$taskInfo['structure_ids'],'删除负责人'); //
				}
                return resultArray(['data'=>'操作成功']);
            } else {
                return resultArray(['error'=>'操作失败']);
            }
        } else {
            return resultArray(['error'=>'参数错误']);
        }
    }

	/**
     * 重命名任务
     * @author 
     * @param  
     * @return
     */
    public function rename()
    {
        $param = $this->param;
        $workModel = new \app\work\model\Task(); 
        if ($param['rename']&&$param['work_id']) {
			$userInfo   			 = $this->userInfo;
			$param['create_user_id'] = $userInfo['id']; 
            $flag = $workModel->rename($param);
            if ($flag) {
                return resultArray(['data'=>'编辑成功']);
            } else {
                return resultArray(['error'=>$workModel->getError()]);
            }
        } else {
            return resultArray(['error'=>'参数错误']);
        }
    }
	
	/**
     * 删除任务
     * @author 
     * @param  
     * @return
     */	
    public function delete()
    {
        $param = $this->param;
        $taskModel = new \app\work\model\Task(); 
        if ($param['task_id']) {
			$rett = $this->checkSub($param['task_id']); //判断编辑权限
			if(!$rett){
				return resultArray(['error'=>'没有权限']);
			}
            $userInfo   			 = $this->userInfo;
			$param['create_user_id'] = $userInfo['id']; 
            $flag = $taskModel->delTaskById($param);
            if ($flag) {
                return resultArray(['data'=>'删除成功']);
            } else {
                return resultArray(['error'=>$workModel->getError()]);
            }
        } else {
            return resultArray(['error'=>'参数错误']);
        }
    }

	/**
     * 归档任务 改变状态
     * @author 
     * @param  
     * @return
     */    
    public function archive()
    {
        $param = $this->param;
        $taskModel = new \app\work\model\Task(); 
        if ($param['task_id']) {
            $userInfo   			 = $this->userInfo;
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
        } else {
            return resultArray(['error'=>'参数错误']);
        }
    }

	/**
     * 恢复归档任务
     * @author 
     * @param  
     * @return
     */
    public function recover()
    {
        $param = $this->param;
        $taskModel = new \app\work\model\Task(); 
        if ($param['task_id']) {
            $userInfo   			 = $this->userInfo;
			$param['create_user_id'] = $userInfo['id']; 
            $flag = $taskModel->recover($param);
            if ($flag) {
				$temp['user_id'] = $userInfo['id'];
				$temp['content'] = '归档任务';
				$temp['create_time'] = time();
				$temp['task_id'] = $param['task_id'];
				Db::name('WorkTaskLog')->insert($temp);
                return resultArray(['data'=>'操作成功']);
            } else {
                return resultArray(['error'=>$taskModel->getError()]);
            }
        } else {
            return resultArray(['error'=>'参数错误']);
        }
    }

	/**
     * 归档任务列表
     * @author 
     * @param  
     * @return
     */ 
    public function archList()
    {
        $param =$this->param;
        if (!$param['work_id']) {
            return resultArray(['error'=>'参数错误']);
        }
        $list = Db::name('Task')->field('task_id,name,create_time,archive_time,stop_time')->where('status=3 and work_id='.$param['work_id'])->select();
		foreach ($list as $k=>$v) {
			$list[$k]['stop_time'] = $v['stop_time']?:'';
		}
        return resultArray(['data'=>$list]);
    }

	/**
     * 任务标记结束
     * @author 
     * @param  
     * @return
     */    
    public function setOver()
    {
        $param = $this->param;
        if ($param['task_id']) {
			$taskInfo = Db::name('Task')->where('task_id ='.$param['task_id'])->find();
            $flag = Db::name('Task')->where('task_id ='.$param['task_id'])->setField('status',5);
            if ($flag) {
				if( !$taskInfo['pid']){
					$userInfo = $this->userInfo;
					$temp['user_id'] = $userInfo['id'];
					$temp['content'] = '任务标记结束';
					$temp['create_time'] = time();
					$temp['task_id'] = $param['task_id'];
					Db::name('WorkTaskLog')->insert($temp);
					actionLog( $taskInfo['task_id'],$taskInfo['owner_user_id'],$taskInfo['structure_ids'],'任务标记结束'); //
				}
                return resultArray(['data'=>true]);
            } else {
                return resultArray(['error'=>'操作失败']);
            }
        } else {
            return resultArray(['error'=>'参数错误']);
        }
    } 
}