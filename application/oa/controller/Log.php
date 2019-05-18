<?php
// +----------------------------------------------------------------------
// | Description: 工作日志
// +----------------------------------------------------------------------
// | Author: Michael_xu | gengxiaoxu@5kcrm.com 
// +----------------------------------------------------------------------

namespace app\oa\controller;

use app\admin\controller\ApiCommon;
use think\Hook;
use think\Request;
use app\admin\model\Comment as CommentModel;
use think\Db;

class Log extends ApiCommon
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
            'allow'=>['index','save','read','update','delete','commentsave','commentdel','setread']            
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());        
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }

        $param = $this->param;
        $userInfo = $this->userInfo;
        $checkAction = ['update','delete'];
        if (in_array($a, $checkAction) && $param['log_id']) {
            $det = Db::name('OaLog')->where('log_id = '.$param['log_id'])->find();
            $auth_user_ids = getSubUserId();
            if (($det['create_user_id'] != $userInfo['id']) && in_array($v['create_user_id'],$auth_user_ids)) {
                header('Content-Type:application/json; charset=utf-8');
                exit(json_encode(['code'=>102,'error'=>'无权操作']));
            }
        } 
    }

    /**
     * 日志列表
     * @author Michael_xu
     * @return 
     */
    public function index()
    {
        $param = $this->param;
		$param['type'] = $this->type;
        $userInfo = $this->userInfo;
        $param['read_user_id'] = $userInfo['id'];
        $param['structure_id'] = $userInfo['structure_id'];
        $data = model('Log')->getDataList($param); 
        return resultArray(['data' => $data]);
    }
	
	//标记已读
	public function setread()
	{
		$param = $this->param;
        $userInfo = $this->userInfo;
        $user_id = $userInfo['id'];
		if ($param['log_id']) {
            $where = [];
            $where['log_id'] = $param['log_id'];
            $where['read_user_ids'] = array('like','%,'.$user_id.',%');
			$resData = Db::name('OaLog')->where($where)->find();
			if (!$resData) {
				$read_user_ids = stringToArray($resData['read_user_ids']) ? array_merge(stringToArray($resData['read_user_ids']),array($user_id)) : array($user_id);
				$res = Db::name('OaLog')->where(['log_id' => $param['log_id']])->update(['read_user_ids' => arrayToString($read_user_ids)]);
                return resultArray(['data'=>'操作成功']);
			}
			return resultArray(['data'=>'操作成功']);
		} else {
			return resultArray(['error'=>'参数错误']);
		}
	}

    /**
     * 添加日志
     * @author Michael_xu
     * @param 
     * @return 
     */
    public function save()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $logModel = model('Log');
        $param['create_user_id'] = $userInfo['id'];
        $res = $logModel->createData($param);
        if ($res) {
			$res['realname'] = $userInfo['realname'];
			$res['thumb_img'] = $userInfo['thumb_img'] ? getFullPath($userInfo['thumb_img']) : '';
			$data[] = $res;
            return resultArray(['data' => $data]);
        } else {
        	return resultArray(['error' => $logModel->getError()]);
        }
    }

    /**
     * 日志详情
     * @author Michael_xu
     * @param  
     * @return
     */
    public function read()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $logModel = model('Log');
        $data = $logModel->getDataById($param['id']);
        //权限判断
        $auth_user_ids = getSubUserId();
        if (!in_array($userInfo['id'], $auth_user_ids) && $data['create_user_id'] !== $userInfo['id'] && !in_array($userInfo['id'],stringToArray($data['send_user_ids']))) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }
        if (!$data) {
            return resultArray(['error' => $logModel->getError()]);
        }
        return resultArray(['data' => $data]);
    }

    /**
     * 编辑日志
     * @author Michael_xu
     * @param 
     * @return
     */
    public function update()
    {    
        $param = $this->param;
        $userInfo = $this->userInfo;
        $log_id = $param['id'];
        $logModel = model('Log'); 
        if ($log_id) {
            $dataInfo = db('oa_log')->where(['log_id' => $log_id])->find();
            //权限判断
            if ($dataInfo['create_user_id'] !== $userInfo['id']) {
                header('Content-Type:application/json; charset=utf-8');
                exit(json_encode(['code'=>102,'error'=>'无权操作']));                
            }
            $res = $logModel->updateDataById($param, $param['id']);
            if ($res) {
                return resultArray(['data' => '编辑成功']);
            } else {
                return resultArray(['error' => $logModel->getError()]);
            } 
        } else {
            return resultArray(['error'=>'参数错误']);
        }
    }

    /**
     * 删除日志 
     * @author Michael_xu
     * @param 
     * @return
     */
    public function delete()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $log_id = $param['log_id'];
		if ($log_id) {
            $dataInfo = db('oa_log')->where(['log_id' => $log_id])->find();  
            $adminTypes = adminGroupTypes($userInfo['id']);         
            //3天内的日志可删
            if (date('Ymd',$dataInfo['create_time']) < date('Ymd',(strtotime(date('Ymd',time()))-86400*3)) && !in_array(1,$adminTypes)) {
                return resultArray(['error' => '已超3天，不能删除']);
            } 
            //权限判断
            if ($dataInfo['create_user_id'] !== $userInfo['id'] && !in_array(1,$adminTypes)) {
                header('Content-Type:application/json; charset=utf-8');
                exit(json_encode(['code'=>102,'error'=>'无权操作']));                
            }                       
			$res = model('Log')->delDataById($param);
			if (!$res) {
				return resultArray(['error' => model('Log')->getError()]);
			}
			return resultArray(['data' => '删除成功']);
		} else {
			return resultArray(['error'=>'参数错误']);
		}
    }
	
    /**
     * 日志评论添加
     * @author 
     * @param  
     * @return
     */
	public function commentSave()
	{
		$param = $this->param;
		$logmodel = model('Log');
		$commentmodel = new CommentModel();
		if ($param['log_id']&&$param['content']) {
			$userInfo = $this->userInfo;
            $param['user_id'] = $userInfo['id'];
            $param['type'] = 'oa_log';
            $param['type_id'] = $param['log_id'];
			$flag = $commentmodel->createData($param);
			if ($flag) {
				$logInfo = $logmodel->getDataById($param['log_id']);
				//actionLog($param['log_id'],$logInfo['send_user_ids'],$logInfo['send_structure_ids'],'评论了日志');
				return resultArray(['data'=>$flag]);
			} else {
				return resultArray(['error'=>$commentmodel->getError()]);
			}
		} else {
			return resultArray(['error'=>'参数错误']);
		}
	}
	
    /**
     * 日志评论删除 comment_id删除单个  
     * @author 
     * @param  
     * @return
     */ 
	public function commentDel()
	{
		$param = $this->param;
		$logmodel = model('Log');
		if ($param['comment_id'] && $param['log_id']) {
            $det = Db::name('AdminComment')->where('comment_id = '.$param['comment_id'])->find();
            $userInfo = $this->userInfo;
            if ($det) {
                if ($det['user_id'] != $userInfo['id']) {
                    return resultArray(['error'=>'没有删除权限']);
                }
            } else {
                return resultArray(['error'=>'不存在或已删除']);
            }
			$model = new CommentModel();
			$temp['type'] = 2; 
			$temp['type_id'] = $param['log_id'];
			$temp['comment_id'] = $param['comment_id'];
			$ret = $model->delDataById($param);
			if ($ret) {
				$logInfo = $logmodel->getDataById($param['log_id']);
				//actionLog($param['log_id'],$logInfo['send_user_ids'],$logInfo['send_structure_ids'],'删除了日志评论');
				return resultArray(['data'=>'删除成功']);
			} else {
				return resultArray(['error'=>$model->getError()]);
			}
		} else {
			return resultArray(['error'=>'参数错误']);
		}
	}
}