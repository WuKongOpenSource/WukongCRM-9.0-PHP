<?php
// +----------------------------------------------------------------------
// | Description: 用户
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com 
// +----------------------------------------------------------------------

namespace app\admin\model;

use think\Db;
use app\admin\model\Common;
use com\verify\HonrayVerify;
use think\Cache;
use think\Request;

class User extends Common
{
    /**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如CRM模块用crm作为数据表前缀
     */
	protected $name = 'admin_user';
    protected $createTime = 'create_time';
    protected $updateTime = false;
	protected $autoWriteTimestamp = true;
	protected $insert = [
		'status' => 2,
	];
	protected $statusArr = ['禁用','启用','未激活'];

	protected $dateFormat = 'Y-m-d';
    protected $type = [
        'create_time'    =>  'timestamp',
        'update_time'    =>  'timestamp',
    ];

	/**
	 * 获取用户所属所有用户组
	 * @param  array   $param  [description]
	 */
    public function groups()
    {
        return $this->belongsToMany('group', 'admin_access', 'group_id', 'user_id');
    }

    public function structureList($structure_id,$str)
    {
    	$str_ids = structureList($structure_id,$str);
    	return $str_ids;
    }

	/**
     * [getDataList 列表]
     * @AuthorHTL
     * @param     [string]                   $map [查询条件]
     * @param     [number]                   $page     [当前页数]
     * @param     [number]                   $limit    [每页数量]
     * @return                     [description]
     */	    
	public function getDataList($request)
	{
		$request = $this->fmtRequest( $request );
		$fieldarray = ['search','group_id','structure_id','status','type','page','limit'];
		$map = $request['map'] ? : [];
		if (isset($map['search']) && $map['search']) {
			$map['user.username|user.realname'] = ['like', '%'.$map['search'].'%'];
		}
		unset($map['search']);
		//角色员工
		if ($map['group_id']) {
			$group_user_ids = db('admin_access')->where(['group_id' => $map['group_id']])->column('user_id');
			if ($map['group_id'] == 1 && !$group_user_ids) {
				$group_user_ids = ['1'];
			}			
			$map['user.id'] = array('in',$group_user_ids);
		}
		$exp = new \think\db\Expression('field(user.status,1,2,0)');
		// 默认除去超级管理员
		// $map['user.id'] = array('neq', 1);
		if($map['structure_id']){
			//获取部门下员工列表
			$str_ids = structureList($map['structure_id'],'');
			$new_str_ids = rtrim($str_ids,',');
			$map['user.structure_id'] = ['in',$new_str_ids]; //$map['structure_id'];
		}
		unset($map['structure_id']);
		if ($map['status'] || $map['group_id']) {
			$map['user.status'] = ($map['status'] !== 'all') ? ($map['status'] ? : ['gt',0]) : ['egt',0];
		} else {
			$map['user.status'] = 0;
		}
		unset($map['status']);
		$map['user.type'] = 1;
		if(isset($map['type'])) $map['user.type'] == ($map['type'] == '0') ? 0 : 1;
		//过滤字段
		foreach($fieldarray as $value){
			unset($map[$value]);
		}
		//获取列表
		$dataCount = db('admin_user')
				->alias('user')
				->join('__ADMIN_STRUCTURE__ structure', 'structure.id = user.structure_id', 'LEFT')
				->join('HrmUserDet hud','hud.user_id = user.id','LEFT')
				->where($map)
				->count();
		$list = db('admin_user')
				->alias('user')
				->join('__ADMIN_STRUCTURE__ structure', 'structure.id = user.structure_id', 'LEFT')
				->join('HrmUserDet hud','hud.user_id = user.id','LEFT')
				->limit(($request['page']-1)*$request['limit'], $request['limit'])
				->where($map)
				->field('user.id,user.username,user.img,user.thumb_img,user.realname,user.num,user.email,user.mobile,user.sex,user.structure_id,user.post,user.status,user.parent_id,user.type,user.create_time,structure.name as s_name')
				->order($exp)
				->order('user.id asc')
				->select();
		foreach ($list as $k=>$v) {
			//直属上级
			$list[$k]['status_name'] = $v['status']=='1'?'启用':'禁用';
			$parentInfo = [];
			$parentInfo = $this->getUserById($v['parent_id']);
			$list[$k]['parent_name'] = $v['parent_id'] ? $parentInfo['realname'] : '';
			$list[$k]['status_name'] = $v['status'] ? $this->statusArr[$v['status']] : '停用';
			//角色
			$groupsArr = $this->get($v['id'])->groups;
			$groups = [];
			$groupids = [];
			foreach ($groupsArr as $key=>$val) {
				$groups[] = $val['title'];
				$groupids[] = $val['id'];
			}
			$list[$k]['groups'] = $groups ? implode(',',$groups) : '';
			$list[$k]['groupids'] = $groupids ? implode(',',$groupids) : '';
			$list[$k]['img'] = $v['img'] ? getFullPath($v['img']) : '';
			$list[$k]['thumb_img'] = $v['thumb_img'] ? getFullPath($v['thumb_img']) : '';
		}																	
		$data = [];			
		$data['list'] = $list;				
		$data['dataCount'] = $dataCount;
					
		return $data;
	}

	/*
	*根据字符串展示参与人 use by work
	*/
	public function getDataByStr($idstr)
	{
		$idArr = stringToArray($idstr);
		if (!$idArr) {
			return [];
		}
		$list = $this->field('id,username,realname,thumb_img')->where(['id' => ['in',$idArr]])->select();
		foreach($list as $key=>$value){
			$list[$key]['thumb_img'] = $value['thumb_img']?getFullPath($value['thumb_img']):'';
		}
		return $list;
	}

	/**
	 * [getDataById 根据主键获取详情]
	 * @param     string                   $id [主键]
	 * @return 
	 */
	public function getDataById($id = '')
	{
		$data = Db::name('AdminUser')->find($id);
		if (!$data) {
			$this->error = '暂无此数据';
			return false;
		}
		unset($data['password']);

		if($data['structure_id']) {
			$structureDet = Db::name('AdminStructure')->field('id,name')->where('id = '.$data['structure_id'].'')->find();
			$data['structure_name'] = $structureDet['name'];
		} else {
			$data['structure_name'] = '暂无';
		}
		if($data['parent_id']) {
			$parentDet = Db::name('AdminUser')->field('id,realname')->where('id = '.$data['parent_id'].'')->find();
			$data['parent_name'] = $parentDet['realname'];
		} else {
			$data['parent_name'] = '暂无';
		}
		$data['thumb_img'] = getFullPath($data['thumb_img']);
		$data['img'] = getFullPath($data['img']);
		//$data['groups'] = $this->get($id)->groups();
		return $data;
	}

	/**
	 * 创建用户
	 * @param  array   $param  [description]
	 */
	public function createData($param)
	{
		if (empty($param['group_id']) || !is_array($param['group_id'])) {
			$this->error = '请至少勾选一个用户组';
			return false;
		}		
		// 验证
		$validate = validate($this->name);
		if (!$validate->check($param)) {
			$this->error = $validate->getError();
			return false;
		}
		$syncModel = new \app\admin\model\Sync();

		$this->startTrans();
		try {
			$salt = substr(md5(time()),0,4);
			$param['salt'] = $salt;
			if (!$param['password']) {
				$password = $param['username'];
			} else {
				$password = $param['password'];
			}
			$param['password'] = user_md5($password, $salt, $param['username']);
			$param['type'] = 1;
			$param['mobile'] = $param['username'];
			$this->data($param)->allowField(true)->save();		
			$user_id = $this->id;
			//员工档案
			$data['user_id'] = $param['user_id'];
			unset($param['user_id']);
			$data['user_id'] = $user_id;
			$data['mobile'] = $param['username'];	 	
			$data['email'] = $param['email'] ? : '';	
			$data['sex'] = $param['sex'] ? : '';					
			$data['create_time'] = time();
			Db::name('HrmUserDet')->insert($data);
			
			foreach ($param['group_id'] as $k => $v) {
				$userGroup['user_id'] = $user_id;
				$userGroup['group_id'] = $v;
				$userGroups[] = $userGroup;
			}
			Db::name('admin_access')->insertAll($userGroups);
		
			$this->commit();
			$param['user_id'] = $data['user_id'];
	        $resSync = $syncModel->syncData($param);			
			return true;
		} catch(\Exception $e) {
			$this->rollback();
			$this->error = '添加失败';
			return false;
		}
	}
	
	//导入成为正式用户
	public function beusers($request)
	{
		if ($request['userlist']&&is_array($request['userlist'])) {
			$flag = true;
			foreach ($request['userlist'] as $value) {
				$userInfo = Db::name('AdminUser')->where('id = '.$value.'')->find();
				$userDet = Db::name('HrmUserDet')->where('user_id = '.$value.'')->find();
				$temp['status'] = 1;
				$temp['type'] = 1;
				$temp['username'] = $userDet['mobile'];
				$salt = substr(md5(time()),0,4);
				$temp['salt'] = $salt;
				$password = $userDet['mobile'];
				$temp['password'] = user_md5($password, $salt, $temp['username']);
				$flag = $flag && Db::name('AdminUser')->where('id ='.$value)->update($temp);
			}
			if ($flag) {
				return true;
			} else {
				$this->error = '操作失败';
				return false;
			}
		} else {
			$this->error = '参数错误';
			return false;
		}
	}
	
	/**
	 * 通过id修改用户
	 * @param  array
	 */
	public function updateDataById($param, $id)
	{
		if ($param['user_id']) {
			//修改个人信息
			$data['email'] = $param['email'];
			$data['sex'] = $param['sex'];
			// $data['mobile'] = $param['username'];
			if (db('admin_user')->where(['username' => $param['username'],'id' => ['neq',$param['user_id']]])->find()) {
				$this->error = '手机号已存在';
				return false;				
			}
			Db::name('HrmUserDet')->where(['user_id' => $param['user_id']])->update($data);
			$data['realname'] = $param['realname'];
			// $data['username'] = $param['username'];
			$flag = $this->where(['id' => $param['user_id']])->update($data);
			if ($flag) {
				return true;
			} else {
				$this->error = '保存失败';
				return false;
			}
		} else {
			// 不能操作超级管理员
			// if ($id == 1) {
			// 	$this->error = '非法操作';
			// 	return false;
			// }
			$checkData = $this->get($id);
			$userInfo = $checkData->data;
			if (!$checkData) {
				$this->error = '暂无此数据';
				return false;
			}
			if (empty($param['group_id'])) {
				$this->error = '请至少勾选一个用户组';
				return false;
			}
			if ($param['parent_id'] == $id) {
				$this->error = '直属上级不能是当前人';
				return false;
			}
			if (db('admin_user')->where(['id' => ['neq',$id],'username' => $param['username']])->find()) {
				$this->error = '手机号已存在';
				return false;			
			}
			
			$this->startTrans();
			try {
				$accessModel = model('Access');
				if ($param['group_id']) {
					//角色员工关系处理
					$accessModel->userGroup($id, $param['group_id'], 'update');
				}
				if (!empty($param['password'])) {
					$salt = $userInfo['salt'];
					$param['password'] = user_md5($param['password'], $salt, $param['username']);
				}
				$this->allowField(true)->save($param, ['id' => $id]);
				$this->commit();
				
				// $data['mobile'] = $param['username'];	 	
				$data['email'] = $param['email'];	
				$data['sex'] = $param['sex'];				
				$data['update_time'] = time();
				$flagg = Db::name('HrmUserDet')->where('user_id = '.$id)->update($data);
				return true;
			} catch(\Exception $e) {
				$this->rollback();
				$this->error = '编辑失败';
				return false;
			}			
		}
	}

	/**
	 * [login 登录]
	 * @AuthorHTL
	 * @DateTime
	 * @param     [string]                   $u_username [账号]
	 * @param     [string]                   $u_pwd      [密码]
	 * @param     [string]                   $verifyCode [验证码]
	 * @param     Boolean                  	 $isRemember [是否记住密码]
	 * @param     Boolean                    $type       [是否重复登录]
	 * @param     Boolean                    $mobile     [1手机登录]
	 * @return    [type]                               [description]
	 */
	public function login($username, $password, $verifyCode = '', $isRemember = false, $type = false, $authKey = '', $mobile = 0)
	{
        if (!$username) {
			$this->error = '帐号不能为空';
			return false;
		}
		if (!$password){
			$this->error = '密码不能为空';
			return false;
		}
        if (config('IDENTIFYING_CODE') && !$type) {
            if (!$verifyCode) {
				$this->error = '验证码不能为空';
				return false;
            }
            $captcha = new HonrayVerify(config('captcha'));
            if (!$captcha->check($verifyCode)) {
				$this->error = '验证码错误';
				return false;
            }
        }

		$map['username'] = $username;
		$map['type'] = 1;
		$userInfo = $this->where($map)->find();
		
    	if (!$userInfo) {
			$this->error = '帐号不存在';
			return false;
    	}
		$userInfo['thumb_img'] = $userInfo['thumb_img'] ? getFullPath($userInfo['thumb_img']) : '';
    	if (user_md5($password, $userInfo['salt'], $userInfo['username']) !== $userInfo['password']) {
			$this->error = '密码错误';
			return false;
    	}
    	if ($userInfo['status'] === 0) {
			$this->error = '帐号已被禁用';
			return false;
    	}
        // 获取菜单和权限
        $dataList = $this->getMenuAndRule($userInfo['id']);

        if ($isRemember || $type) {
        	$secret['username'] = $username;
        	$secret['password'] = $password;
            $data['rememberKey'] = encrypt($secret);
        }

		//登录有效时间
        $cacheConfig = config('cache');
        $loginExpire = $cacheConfig['expire'] ? : '86400*3';        

        // 保存缓存
        session_start();
        $info['userInfo'] = $userInfo;
        $info['sessionId'] = session_id();
        $authKey = user_md5($userInfo['username'].$userInfo['password'].$info['sessionId'], $userInfo['salt']);
       // $info['_AUTH_LIST_'] = $dataList['rulesList'];
        $info['authKey'] = $authKey;
        //手机登录
        // if ($mobile == 1) {
        // 	cache('Auth_'.$userInfo['authkey'].'_mobile', NULL);
        // 	cache('Auth_'.$authKey.'_mobile', $info, $loginExpire);
        // } else {
        	cache('Auth_'.$userInfo['authkey'], NULL);
			cache('Auth_'.$authKey, $info, $loginExpire);
        // }
        unset($userInfo['authkey']);
		
        // 返回信息
        $data['authKey']		= $authKey;
        $data['sessionId']		= $info['sessionId'];
        $data['userInfo']		= $userInfo;
        $data['authList']		= $dataList['authList'];
        $data['menusList']		= $dataList['menusList'];
             
        //保存authKey信息
        $userData = [];
        $userData['authkey'] = $authKey;
        $userData['authkey_time'] = time()+$loginExpire;
		//把状态未激活至为启用
    	if ($userInfo['status'] == 2) {
    		$userData['status'] = 1;
    	}
        db('admin_user')->where(['id' => $userInfo['id']])->update($userData);
        return $data;
    }

	/**
	 * 修改密码
	 * @param  array   $param  [description]
	 */
    public function updatePaw($userInfo, $old_pwd, $new_pwd)
    {
        if (!$old_pwd) {
			$this->error = '请输入旧密码';
			return false;
        }
        if (!$new_pwd) {
            $this->error = '请输入新密码';
			return false;
        }
        if ($new_pwd == $old_pwd) {
            $this->error = '新旧密码不能一致';
			return false;
        }

		//登录有效时间
        $cacheConfig = config('cache');
        $loginExpire = $cacheConfig['expire'] ? : '86400*3';         

        $password = $this->where('id', $userInfo['id'])->value('password');
        if (user_md5($old_pwd, $userInfo['salt'], $userInfo['username']) != $password) {
            $this->error = '原密码错误';
			return false;
        }
        if (user_md5($new_pwd, $userInfo['salt'], $userInfo['username']) == $password) {
            $this->error = '密码没改变';
			return false;
        }
        if ($this->where('id', $userInfo['id'])->setField('password', user_md5($new_pwd, $userInfo['salt'], $userInfo['username']))) {
			$syncData = [];
			$syncModel = new \app\admin\model\Sync();
	        $syncData['user_id'] = $userInfo['id'];
	        $syncData['salt'] = $userInfo['salt'];
	        $syncData['password'] = user_md5($new_pwd, $userInfo['salt'], $userInfo['username']);
	        $resSync = $syncModel->syncData($syncData);        	

            $userInfo = $this->where('id', $userInfo['id'])->find();
            // 重新设置缓存
            session_start();
            $cache['userInfo'] = $userInfo;
            $cache['authKey'] = user_md5($userInfo['username'].$userInfo['password'].session_id(), $userInfo['salt']);
            cache('Auth_'.$auth_key, null);
            cache('Auth_'.$cache['authKey'], $cache, $loginExpire);
            return $cache['authKey'];//把auth_key传回给前端
        }
        $this->error = '修改失败';
		return false;
    }

	//根据IDs批量设置密码
	public function updatePwdById($param)
	{
		$syncModel = new \app\admin\model\Sync();
		$flag = true;
		foreach ($param['id'] as $value) {
			$password = '';
			$userInfo = db('admin_user')->where(['id' => $value])->find();;
			$salt = substr(md5(time()),0,4);
			$temp['salt'] = $salt;
			$temp['password']= $password = user_md5($param['password'], $salt, $userInfo['username']);
			$flag = $flag && Db::name('AdminUser')->where('id ='.$value)->update($temp);

			$syncData = [];
	        $syncData['user_id'] = $value;
	        $syncData['salt'] = $salt;
	        $syncData['password'] = $password;
	        $resSync = $syncModel->syncData($syncData);			
		}
		if ($flag) {
			return $flag;
		} else {
			$this->error ='修改失败，请稍后重试';
			return false;
		}
	}

	/**
	 * 获取菜单和权限 protected
	 * @param  array   $param  [description]
	 */
    protected function getMenuAndRule($u_id)
    {
    	$menusList = [];
    	$ruleMap = [];
    	$adminTypes = adminGroupTypes($u_id);
        if (in_array(1,$adminTypes)) {
            $map['status'] = 1;
    		$menusList = Db::name('admin_menu')->where($map)->order('sort asc')->select();
        } else {
			$groups = $this->get($u_id)->groups;
	        $ruleIds = [];
			foreach($groups as $k => $v) {
				if (stringToArray($v['rules'])) {
					$ruleIds = array_merge($ruleIds, stringToArray($v['rules']));
				}
			}
			$ruleIds = array_unique($ruleIds);
	        $ruleMap['id'] = array('in', $ruleIds);
	        $ruleMap['status'] = 1;    	
        }
        $newRuleIds = [];
        // 重新设置ruleIds，除去部分已删除或禁用的权限。
        $rules = Db::name('admin_rule')->where($ruleMap)->select();
        foreach ($rules as $k => $v) {
        	$newRuleIds[] = $v['id'];
        	$rules[$k]['name'] = strtolower($v['name']);
        }
		// $menuMap['status'] = 1;
        // $menuMap['rule_id'] = array('in',$newRuleIds);
        // $menusList = Db::name('admin_menu')->where($menuMap)->order('sort asc')->select();
        $ret = [];
        //处理菜单成树状
        $tree = new \com\Tree();
        //处理规则成树状
        $rulesList = $tree->list_to_tree($rules, 'id', 'pid', 'child', 0, true, array('pid'));
        //权限数组
        $authList = rulesListToArray($rulesList, $newRuleIds);
		//系统设置权限（1超级管理员2系统设置管理员3部门与员工管理员4审批流管理员5工作台管理员6客户管理员7项目管理员8公告管理员）
		$settingList = ['0' => 'system','1' => 'user','2' => 'permission','3' => 'examineFlow','4' => 'oa','5' => 'crm'];
	    $adminTypes = adminGroupTypes($u_id);
	    $newSetting = [];
	    foreach ($settingList as $k=>$v) {
	    	$check = false;  	
	    	if (in_array('1', $adminTypes) || in_array('2', $adminTypes)) {
	    		$check = true;
	    	} else {
				if ($v == 'user' && in_array('3', $adminTypes)) $check = true;	    		
				if ($v == 'permission' && in_array('3', $adminTypes)) $check = true;	    		
				if ($v == 'examineFlow' && in_array('4', $adminTypes)) $check = true;	    		
				if ($v == 'oa' && in_array('5', $adminTypes)) $check = true;	    		
				if ($v == 'crm' && in_array('6', $adminTypes)) $check = true;	    		
	    	}
	    	if ($check == true) {
	    		$newSetting['manage'][$v] = $check;
	    	}
	    }
	    if ($authList && $newSetting) {
	    	$authList = array_merge($authList, $newSetting);
	    } elseif ($newSetting) {
			$authList = $newSetting;
	    }

	    $ret['authList'] = $authList;    
        return $ret;
    }

	/**
	 * 获取权限结构数组
	 * @param
	 */    
	public function getRulesList($uid)
	{
    	$ruleMap = [];
    	$adminTypes = adminGroupTypes($uid);
        if (in_array(1,$adminTypes)) {
            $map['status'] = 1;
        } else {
			$groups = $this->get($uid)->groups;
	        $ruleIds = [];
			foreach($groups as $k => $v) {
				if (stringToArray($v['rules'])) {
					$ruleIds = array_merge($ruleIds, stringToArray($v['rules']));
				}
			}
			$ruleIds = array_unique($ruleIds);
	        $ruleMap['id'] = array('in', $ruleIds);
	        $ruleMap['status'] = 1;    	
        }
        $newRuleIds = [];
        // 重新设置ruleIds，除去部分已删除或禁用的权限。
        $rules = Db::name('admin_rule')->where($ruleMap)->select();
        foreach ($rules as $k => $v) {
        	$newRuleIds[] = $v['id'];
        	$rules[$k]['name'] = strtolower($v['name']);
        }
        //处理规则成树状
        $tree = new \com\Tree();
        $rulesList = $tree->list_to_tree($rules, 'id', 'pid', 'child', 0, true, array('pid'));
        $rulesList = rulesDeal($rulesList);
        return $rulesList ? : [];		
	}

    /**
	 * 获取用户所属角色（用户组）
	 * @param
	 */
    public function getGroupTypeByAction($uid, $m, $c, $a)
    {  	
    	//根据$m,$c,$a 获取对应的$a 的rule_id
    	$rulesList = $this->getRulesList($uid);
    	if (!in_array($m.'-'.$c.'-'.$a, $rulesList)) {
    		return false;
    	}
    	$mRuleId = db('admin_rule')->where(['name'=>$m,'level'=>1])->value('id');
    	$cRuleId = db('admin_rule')->where(['name'=>$c,'level'=>2,'pid'=>$mRuleId])->value('id');
    	$aRuleId = db('admin_rule')->where(['name'=>$a,'level'=>3,'pid'=>$cRuleId])->value('id');
		//获取用户组
		$groups = $this->get($uid)->groups;
		if (!$groups) {
			return false;	
		}
		$groupTypes = [];
		foreach ($groups as $g) {
			if (in_array($aRuleId, explode(',', trim($g['rules'], ',')))) {
				$groupTypes[] = $g['type'];
			}
		}
		return $groupTypes ? : [];
    }

	/**
	 * 获取有此权限的角色
	 * @param
	 */
    public function getAllUserByAction($m, $c, $a)
    {
    	$mRuleId = db('admin_rule')->where(['name'=>$m,'level'=>1])->value('id');
    	$cRuleId = db('admin_rule')->where(['name'=>$c,'level'=>2,'pid'=>$mRuleId])->value('id');
    	$aRuleId = db('admin_rule')->where(['name'=>$a,'level'=>3,'pid'=>$cRuleId])->value('id');

    	$groups = db('admin_group')->where(['rules' => ['in',$aRuleId]])->column('id');
    	$userIds = db('admin_access')->where(['group_id' => ['in',$groups]])->column('user_id');
    	if (!$userIds) {
    		//查询管理员
    		$userIds = db('admin_user')->where(['id' => 1])->column('id');
    	}
		return $userIds;
    }    

    /**
	 * 根据部门获取部门的userId
	 * @param $strId  部门ID
	 * @param $type  2时包含所有下属部门
	 */
	public function getSubUserByStr($structure_id, $type = 1)
	{	
		if (is_array($structure_id)) {
			$allStrIds = $structure_id;
		} else {
			$allStrIds[] = $structure_id;
		}
		if ($type == 2) {
			$structureModel = new \app\admin\model\Structure();
			$allSubStrIds = $structureModel->getAllChild($structure_id);
			if ($allSubStrIds) {
				$allStrIds = array_merge($allStrIds, $allSubStrIds); //全部关联部门（包含下属部门）
			}
		}
	    $userIds = db('admin_user')->where(['structure_id' => ['in',$allStrIds]])->column('id');
	    return $userIds ? : [];
	}

	/**
	 * [getUserById 根据主键获取详情]
	 * @param 
	 * @return 
	 */
	public function getUserById($id = '')
	{
		$data = Db::name('AdminUser')
				->alias('user')
				->join('__ADMIN_STRUCTURE__ structure', 'structure.id = user.structure_id', 'LEFT')
				->where(['user.id' => $id])->field('user.id,username,img,thumb_img,realname,parent_id,structure.name as structure_name,structure.id as structure_id')->find();
		$data['img'] = $data['img'] ? getFullPath($data['img']) : '';
		$data['thumb_img'] = $data['thumb_img'] ? getFullPath($data['thumb_img']) : '';
		return $data ? : [];
	}

	/**
	 * [getUserNameById 根据主键获取详情]
	 * @param 
	 * @return 
	 */
	public function getUserNameById($id = '')
	{
		$data = $this->where(['id' => $id])->value('realname');
		return $data ? : '查看详情';
	}

	/**
	 * [getUserNameByArr 根据主键获取详情]
	 * @param 
	 * @return 
	 */
	public function getUserNameByArr($ids = [])
	{
		if (!is_array($ids)) {
			$idArr[] = $ids;
		} else {
			$idArr = $ids;
		}
		$data = $this->where(['id' => array('in', $idArr)])->column('realname');
		return $data ? : [];
	}	

	/**
	 * [getAdminId 获取管理员ID]
	 * @param 
	 * @return 
	 */	
	public function getAdminId()
	{
		$adminGroupUser = db('admin_access')->where(['group_id' => 1])->column('user_id');
		$userIDs = $adminGroupUser ? array_merge($adminGroupUser,['1']) : ['1'];
		return $userIDs ? : ['1'];
	}

	/**
	 * [getUserByIdArr 根据ID数组获取列表]
	 * @param 
	 * @return 
	 */
	public function getUserByIdArr($ids = [])
	{
		$list = $this
				->alias('user')
				->join('__ADMIN_STRUCTURE__ structure', 'structure.id = user.structure_id', 'LEFT')
				->where(['user.id' => ['in', $id]])->field('user.id,username,img,thumb_img,realname,parent_id,structure.name as structure_name,structure.id as structure_id')->select();
		return $list ? : [];
	}

	/**
	 * [getUserByPer 获取权限范围的user_id]
	 * @param   
	 * @return 
	 */
	public function getUserByPer($m = '', $c = '', $a = ''){
	    $request = Request::instance();
	    $header = $request->header();
	    $authKey = $header['authkey'];

		$m = $m ? strtolower($m) : strtolower($request->module());
		$c = $c ? strtolower($c) : strtolower($request->controller());
		$a = $a ? strtolower($a) : strtolower($request->action());

	    $cache = cache('Auth_'.$authKey);
	    if (!$cache) {
	        return false;
	    }
	    $userInfo = $cache['userInfo'];
	    //用户所属用户组类别（数组）
	    $groupTypes = $this->getGroupTypeByAction($userInfo['id'], $m, $c, $a);
	    //数组去重
	    $groupTypes = $groupTypes ? array_unique($groupTypes) : [];
	    //用户组类别（1本人，2本人及下属，3本部门，4本部门及下属部门，5全部）
	    $adminIds = $this->getAdminId();
	    $userIds = [];
	    if (in_array($userInfo['id'],$adminIds)) {
	        $userIds = getSubUserId(true, 1);
	    } else {
	        if (!$groupTypes) {
	            return [];
	        }    
	        if (in_array(5, $groupTypes)) {
	            $userIds = getSubUserId(true, 1);
	        } else {
	            foreach ($groupTypes as $v) {
	                if ($v == 1) {
	                    $userIds = [$userInfo['id']];
	                } elseif ($v == 2) {
	                    $userIds = getSubUserId();
	                } elseif ($v == 3) {
	                    $userIds = $this->getSubUserByStr($userInfo['structure_id']);
	                } elseif ($v == 4) {
	                    $userIds = $this->getSubUserByStr($userInfo['structure_id'], 2);
	                }
	            }       
	        }
	    }
	    return $userIds ? : [];
	} 	
	
	/*
	*根据部门ID获取员工列表
	*
	*/
	public function getUserListByStructureId($structure_id='')
	{
		$map =array();
		if($structure_id){
			$map['structure_id'] = $structure_id;
		}
		$list = Db::name('AdminUser')->field('id as user_id,realname,post,structure_id')->where($map)->select();
		return $list ? : [];
	}

	/*
	*根据字符串返回数组
	*
	*/
	public function getListByStr($str)
	{
		$idArr = stringToArray($str);
		$list = db('admin_user')->field('id,username,realname,thumb_img')->where(['id' => ['in',$idArr]])->select();
		return $list;
	}

	/*
	*读写权限
	*
	*/
	public function rwPre($user_id, $ro_user_id, $rw_user_id, $action = 'read')
	{
		if ($action == 'update') {
 			if (!in_array($user_id, stringToArray($rw_user_id))) {
 				return false;
 			}
		} else {
			if (!in_array($user_id, stringToArray($ro_user_id))) {
 				return false;
 			}			
		}
		return true;
	}		
}
