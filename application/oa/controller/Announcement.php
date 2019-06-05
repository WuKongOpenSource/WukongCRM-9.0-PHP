<?php
// +----------------------------------------------------------------------
// | Description: 公告
// +----------------------------------------------------------------------
// | Author: yyk
// +----------------------------------------------------------------------

namespace app\oa\controller;

use app\admin\controller\ApiCommon;
use think\Hook;
use think\Request;
use think\Db;

class Announcement extends ApiCommon
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
            'allow'=>['index','read','save','update','delete']            
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());        
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }

        $userInfo = $this->userInfo;
        $unAction = ['index','read'];
        $userInfo = $this->userInfo;
        $adminTypes = adminGroupTypes($userInfo['id']);
        if (!in_array(8,$adminTypes) && !in_array(1,$adminTypes) && !in_array($a, $unAction)) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }  

    }

    /**
     * 公告列表
     * @author Michael_xu
     * @return 
     */
    public function index()
    {	
        $announcementModel = model('Announcement');
        $param = $this->param;
        $userInfo = $this->userInfo;
        //公告列表权限
		$adminTypes = adminGroupTypes($userInfo['id']);
		
		$param['user_id'] = $userInfo['id'];
        $data = $announcementModel->getDataList($param);
		if( !in_array(8,$adminTypes) && !in_array(1,$adminTypes) ){
			$data['is_create'] = 0;
		} else {
			$data['is_create'] = 1;
		}
        return resultArray(['data' => $data]);
    }
	
    /**
     * 添加公告
     * @author Michael_xu
     * @return 
     */
    public function save()
    {
        $announcementModel = model('Announcement');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['create_user_id'] = $userInfo['id'];
        //权限判断
        $adminTypes = adminGroupTypes($userInfo['id']);
        if (!in_array(8,$adminTypes) && !in_array(1,$adminTypes)) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }
        $res = $announcementModel->createData($param);
        if ($res) {
			$res['realname'] = $userInfo['realname'];
            return resultArray(['data' => $res]);
        } else {
        	return resultArray(['error' => $announcementModel->getError()]);
        }
    }

    /**
     * 公告详情
     * @author Michael_xu
     * @return 
     */
    public function read()
    {
        $announcementModel = model('Announcement');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $user_id = $userInfo['id'];
		if(!$param['announcement_id']) {
			return resultArray(['error'=>'参数错误']);
		}      
        $data = $announcementModel->getDataById($param['announcement_id']);
        $adminTypes = adminGroupTypes($user_id);
        //判断权限
        if (!in_array($user_id, stringToArray($data['owner_user_ids'])) && !in_array($userInfo['structure_id'], stringToArray($data['structure_ids'])) && !in_array(1,$adminTypes) && ($data['owner_user_ids'] && $data['structure_id'])) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'没有权限']));
        }        
        if (!$data) {
            return resultArray(['error' => $announcementModel->getError()]);
        }
        //标记已读
        $read_user_ids = stringToArray($data['read_user_ids']) ? array_merge(stringToArray($data['read_user_ids']),array($user_id)) : array($user_id);
        $res = Db::name('OaAnnouncement')->where(['announcement_id' => $param['announcement_id']])->update(['read_user_ids' => arrayToString($read_user_ids)]);    
        return resultArray(['data' => $data]);
    }

    /**
     * 编辑公告
     * @author Michael_xu
     * @return 
     */
    public function update()
    {
        $announcementModel = model('Announcement');
        $param = $this->param;     
		if( !$param['announcement_id'] ){
			return resultArray(['error' => '参数错误']);
		}
        $res = $announcementModel->updateDataById($param, $param['announcement_id']);
        if ($res) {
            return resultArray(['data' => '编辑成功']);
        } else {
        	return resultArray(['error' => $announcementModel->getError()]);
        } 
    }

    /**
     * 删除公告
     * @author Michael_xu
     * @return 
     */ 
    public function delete()
    {
        $announcementModel = model('Announcement');
        $param = $this->param;
		$userInfo = $this->userInfo;
		$param['user_id'] = $userInfo['id'];
        //权限判断
        $adminTypes = adminGroupTypes($userInfo['id']);
        if (!in_array(8,$adminTypes) && !in_array(1,$adminTypes)) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }         
        $data = $announcementModel->delDataById($param);
        if (!$data) {
            return resultArray(['error' => $announcementModel->getError()]);
        }
        return resultArray(['data' => '删除成功']);
    }   
}