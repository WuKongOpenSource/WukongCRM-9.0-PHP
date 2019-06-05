<?php
// +----------------------------------------------------------------------
// | Description: 系统员工
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com 
// +----------------------------------------------------------------------

namespace app\admin\controller;

use think\Request;
use think\Session;
use think\Hook;
use think\Db;

class Users extends ApiCommon
{
    /**
     * 用于判断权限
     * @permission 无限制
     * @allow 登录员工可访问
     * @other 其他根据系统设置
    **/    
    public function _initialize()
    {
        parent::_initialize();
        $action = [
            'permission'=>[],
            'allow'=>['index','save','update','updatepwd','enables','read','getuserlist','updateimg','resetpassword','userlistbystructid','groups','groupsdel','tobeusers','structureuserlist','getuserlist','usernameedit']
        ];
        Hook::listen('check_auth',$action);

        $request = Request::instance();
        $a = strtolower($request->action());        
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }

		$userInfo = $this->userInfo;
        //权限判断
        $unAction = ['index','read','getuserlist','structureuserlist','updateimg','resetpassword','update'];
        $adminTypes = adminGroupTypes($userInfo['id']);
        if (!in_array(3,$adminTypes) && !in_array(1,$adminTypes) && !in_array(2,$adminTypes) && !in_array($a, $unAction)) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }        
    }

    /**
     * 员工列表
     * @param 
     * @return
     */
    public function index()
    {   
        $userModel = model('User');
        $param = $this->param;  
        $data = $userModel->getDataList($param);
        return resultArray(['data' => $data]);
    }

    /**
     * 员工详情
     * @param 
     * @return
     */
    public function read()
    {   
        $userModel = model('User');
        $param = $this->param;
        $userInfo = $this->userInfo;
        if (!$param['id']) $param['id'] = $userInfo['id'];
        $data = $userModel->getDataById($param['id']);
        if (!$data) {
            return resultArray(['error' => $userModel->getError()]);
        } 
        return resultArray(['data' => $data]);
    }

    /**
     * 员工创建
     * @param 
     * @return
     */    
    public function save()
    {
        $userModel = model('User');
        $param = $this->param;
		$userInfo = $this->userInfo;
        $data = $userModel->createData($param);
        if (!$data) {
            return resultArray(['error' => $userModel->getError()]);
        }
        return resultArray(['data' => '添加成功']);
    }

    /**
     * 员工编辑
     * @param 
     * @return
     */
    public function update()
    {
        $userModel = model('User');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $userData = db('admin_user')->where(['id' => $param['id']])->find();
        if (!$param['id']) {
            //修改个人信息
            $param['user_id'] = $userInfo['id'];
        } else {
            //权限判断
            $adminTypes = adminGroupTypes($userInfo['id']);
            if (!in_array(3,$adminTypes) && !in_array(1,$adminTypes) && !in_array(2,$adminTypes)) {
                header('Content-Type:application/json; charset=utf-8');
                exit(json_encode(['code'=>102,'error'=>'无权操作']));
            }            
        }
        unset($param['username']);
        $data = $userModel->updateDataById($param, $param['id']);
        if (!$data) {
            return resultArray(['error' => $userModel->getError()]);
        }

        $param['userInfo'] = $userData;
        $resSync = model('Sync')->syncData($param);        

        return resultArray(['data' => '编辑成功']);
    }    

	//批量设置密码
	public function updatePwd()
	{
		$param = $this->param;
		if ($param['password'] && is_array($param['id'])) {
			$userModel = model('User');
			$ret = $userModel->updatePwdById($param);
			if ($ret) {
				return resultArray(['data'=>true]);
			} else {
				return resultArray(['error'=>$userModel->getError()]);
			}
		} else {
			return resultArray(['error'=>'参数错误']);
		}
	}
	
    /**
     * 员工状态
     * @param status   0禁用,1启用,2禁止登陆,3未激活
     * @return
     */
    public function enables()
    {
        $userModel = model('User');
        $param = $this->param;
        if (!is_array($param['id'])) {
            $ids[] = $param['id'];
        } else {
            $ids = $param['id'];
        }
        //顶级管理员不能修改
        foreach ($ids as $k=>$v) {
            if ((int)$v == 1 && $param['status'] == '0') {
                unset($ids[$k]);
            }
        }
        $data = $userModel->enableDatas($ids, $param['status']);  
        if (!$data) {
            return resultArray(['error' => $userModel->getError()]);
        } 
        return resultArray(['data' => '操作成功']);         
    }

    /**
     * 获取权限范围内的员工数组
     * @param  
     * @return
     */
    public function getUserList()
    {
        $userModel = model('User');
        $param = $this->param;
        $by = $param['by'] ? : '';
        if ($param['m'] && $param['c'] && $param['a']) {
            if ($param['m'] == 'oa' && $param['c'] == 'task') {
               $belowIds = getSubUserId(true, 1); 
            }
            $belowIds = $userModel->getUserByPer($param['m'], $param['c'], $param['a']); 
        } else {
            if ($by == 'sub') {
                $userInfo = $this->userInfo;
                $adminIds = $userModel->getAdminId();
                if (in_array($userInfo['id'],$adminIds)) {
                    $belowIds = getSubUserId(true, 1);
                } else {
                    //下属id
                    $belowIds = getSubUserId();
                } 
            } else {
                $belowIds = getSubUserId(true, 1);        
            }
            // $belowIds = getSubUserId(true, 1);
        }
        $userList = db('admin_user')
                    ->where(['user.id' => ['in',$belowIds]])
                    ->alias('user')
                    ->where('user.status>0 and user.type=1')
                    ->join('__ADMIN_STRUCTURE__ structure', 'structure.id = user.structure_id', 'LEFT')
                    ->field('user.id,user.realname,user.thumb_img,structure.name as s_name')
                    ->select();
        foreach ($userList as $k=>$v) {
            $userList[$k]['username'] = $v['realname'];
            $userList[$k]['thumb_img'] = $v['thumb_img'] ? getFullPath($v['thumb_img']) : '';
        }
        return resultArray(['data' => $userList ? : []]); 
    }

    /**
     * 修改头像
     * @param 
     * @return
     */ 
    public function updateImg()
    {
        $fileModel = model('File');
        $param = $this->param;
        $userInfo = $this->userInfo;
        //处理图片
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST');
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept"); 
        $param['file'] = request()->file('file');
		
        $resImg = $fileModel->updateByField($param['file'], 'User', $param['id'], 'img', 'thumb_img', 150, 150);
        if (!$resImg) {
            return resultArray(['error' => $fileModel->getError()]);
        }
        return resultArray(['data' => '上传成功']);
    }

    /**
     * 重置密码
     * @param 
     * @return
     */     
    public function resetPassword()
    {   
        $param = $this->param;
        $userInfo = $this->userInfo;
        $userModel = model('User');
        if ($param['id'] && (int)$param['id'] !== $userInfo['id']) {
            //权限判断
            $adminTypes = adminGroupTypes($userInfo['id']);
            if (!in_array(3,$adminTypes) && !in_array(1,$adminTypes) && !in_array(2,$adminTypes)) {
                header('Content-Type:application/json; charset=utf-8');
                exit(json_encode(['code'=>102,'error'=>'无权操作']));
            }  
            $user_id = $param['id'];
            if (!$param['new_pwd']) {
                $this->error = '请输入重置密码';
                return false;
            }

            $userInfo = $userModel->getDataById($user_id);
            if (user_md5($param['new_pwd'], $userInfo['salt'], $userInfo['username']) == $userInfo['password']) {
                $this->error = '密码没改变';
                return false;
            }
            if (db('admin_user')->where('id', $user_id)->setField('password', user_md5($param['new_pwd'], $userInfo['salt'], $userInfo['username']))) {
                $syncData = [];
                $syncModel = new \app\admin\model\Sync();
                $syncData['user_id'] = $userInfo['id'];
                $syncData['salt'] = $userInfo['salt'];
                $syncData['password'] = user_md5($param['new_pwd'], $userInfo['salt'], $userInfo['username']);
                $resSync = $syncModel->syncData($syncData);                
                return resultArray(['data' => '密码重置成功']);
            } else {
                return resultArray(['error' => '密码重置失败，请重试']);
            }      
        } else {
            $userModel = model('User');
            $old_pwd = $param['old_pwd'];
            $new_pwd = $param['new_pwd'];
            $data = $userModel->updatePaw($userInfo, $old_pwd, $new_pwd);
            if (!$data) {
                return resultArray(['error' => $userModel->getError()]);
            } 
            return resultArray(['data' => $data]);            
        }
    }

    /**
     * 员工角色关系
     * @param 
     * @return
     */
    public function groups()
    {
        $param = $this->param;
        if (!$param['users'] && !$param['structures']) {
            return resultArray(['error' => '请选择员工']);
        }
        if (!$param['groups']) {
            return resultArray(['error' => '请选择角色']);
        }
        $userModel = model('User');
        //部门下所有员工
        $userArr = [];
        if (is_array($param['structures'])) {
            foreach ($param['structures'] as $v) {
                $userArr[] = $userModel->getSubUserByStr($v);
            }            
        }
        if ($userArr) $userArr = call_user_func_array('array_merge', $userArr); //数组合并
        if ($userArr && $param['users']) {
            $userIds = array_merge($userArr, $param['users']);
        } elseif ($userArr) {
            $userIds = $userArr;
        } else {
            $userIds = $param['users'];
        }
        $userIds = array_unique($userIds);
        $groups = $param['groups'];
        $accessModel = model('Access');       
        $resData = true;
        foreach ($userIds as $k=>$v) {
            //角色员工关系处理
            $res = $accessModel->userGroup($v, $param['groups']);
            if (!$res) {
                $resData = false;
            }            
        }
        // if ($resData == false) {
        //     return resultArray(['error' => '操作失败，请重试']);      
        // }
        return resultArray(['data' => '创建成功']);       
    }

    /**
     * 员工角色关系（删除）
     * @param 
     * @return
     */
    public function groupsDel()
    {
        $param = $this->param;
        if (!$param['user_id']) {
            return resultArray(['error' => '请选择员工']);
        }
        if (!$param['group_id']) {
            return resultArray(['error' => '参数错误']);
        }
        $res = db('admin_access')->where(['user_id' => $param['user_id'],'group_id' => $param['group_id']])->delete();
        if (!$res) {
            return resultArray(['error' => '操作失败，请重试']);      
        }
        return resultArray(['data' => '删除成功']);         
    }

    /**
     * [structureUserList 部门员工混合数据]
     * @param 
     * @return
     */ 
    public function structureUserList()
    {
        $structure_list = db('admin_structure')->select();
        $structureList = getSubObj(0, $structure_list, '', 1);
        foreach ($structureList as $k=>$v) {
            $userList = [];
            $userList = db('admin_user')->where(['structure_id' => $v['id'],'status' => array('in',['1','3'])])->field('id,realname')->select();
            $structureList[$k]['userList'] = $userList;
        }
        return $structureList;
    }    
	
	//人资员工导入
	public function tobeusers(){
		$userModel = model('User');
		$param = $this->param;
		$flag = $userModel->beusers($param);
		if ($flag) {
			return resultArray(['data'=>$flag]);
		} else {
			return resultArray(['error'=>$userModel->getError()]);
		}
	}
	
	//根据部门ID获取员工列表
	public function userListByStructId()
	{
		$usermodel = model('User');
		$param = $this->param;
		$structure_id = $param['structure_id']?:'';
		$ret = $usermodel->getUserListByStructureId($structure_id) ? : [];
        return resultArray(['data'=>$ret]);
	}

    /**
     * 员工账号修改
     * @param 
     * @return
     */    
    public function usernameEdit()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        //权限判断
        $adminTypes = adminGroupTypes($userInfo['id']);
        if (!in_array(3,$adminTypes) && !in_array(1,$adminTypes) && !in_array(2,$adminTypes)) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }         
        if (!$param['id'] || !$param['username'] || !$param['password']) {
            return resultArray(['error' => '参数错误！']);
        }
        if (db('admin_user')->where(['id' => ['neq',$param['id']],'username' => $param['username']])->find()) {
            return resultArray(['error' => '手机号码已存在！']);
        }
        $userData = db('admin_user')->where(['id' => $param['id']])->field('username,salt,password')->find();
        $data = [];
        $data['username'] = $param['username'];
        $data['password'] = user_md5($param['password'], $userData['salt'], $param['username']);
        $data['userInfo'] = $userData;
        $resSync = model('Sync')->syncData($data);
        if ($resSync) {
            unset($data['userInfo']);
            $res = db('admin_user')->where(['id' => $param['id']])->update($data);
            return resultArray(['data' => '修改成功！']);
        } else {
            return resultArray(['error' => '修改失败，请重试！']);
        }
    }
}