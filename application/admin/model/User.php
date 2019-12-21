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
	 * 导入字段
	 *
	 * @var array
	 * @author Ymob
	 * @datetime 2019-10-25 15:35:25
	 */
	public static $import_field_list = [
		[
			'field' => 'username',
			'name' => '手机号（登录名）',
			'form_type' => 'mobile',
			'is_null' => 1,
			'is_unique' => 1
		],
		[
			'field' => 'password',
			'name' => '密码',
			'form_type' => 'text',
			'is_null' => 1,
		],
		[
			'field' => 'realname',
			'name' => '姓名',
			'form_type' => 'text',
			'is_null' => 1,
		],
		[
			'field' => 'sex',
			'name' => '性别',
			'form_type' => 'select',
			'setting' => ['男', '女'],
			'is_null' => 0,
		],
		[
			'field' => 'email',
			'name' => '邮箱',
			'form_type' => 'email',
			'is_null' => 0,
		],
		[
			'field' => 'post',
			'name' => '岗位',
			'form_type' => 'text',
			'is_null' => 0,
		]
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
		unset($data['authkey']);

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
		// 非导入数据
		if (request()->action() == 'import') {
			$temp = [];
			foreach (self::$import_field_list as $key => $val) {
				$temp[$val['field']] = $param[$val['field']];
			}
			$param = $temp;
			$param['structure_id'] = 0;
		} else {
			if (empty($param['group_id']) || !is_array($param['group_id'])) {
				$this->error = '请至少勾选一个用户组';
				return false;
			}		
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
			$this->data($param)->allowField(true)->isUpdate(false)->save();		
			$user_id = (int) $this->getLastInsId();
			//员工档案
			$data['user_id'] = $param['user_id'];
			unset($param['user_id']);
			$data['user_id'] = $user_id;
			$data['mobile'] = $param['username'];	 	
			$data['email'] = $param['email'] ? : '';	
			$data['sex'] = $param['sex'] ? : '';					
			$data['create_time'] = time();
			Db::name('HrmUserDet')->insert($data);
			
			$userGroups = [];
			foreach ($param['group_id'] as $k => $v) {
				$userGroup['user_id'] = $user_id;
				$userGroup['group_id'] = $v;
				$userGroups[] = $userGroup;
			}
			if ($userGroups) {
				Db::name('admin_access')->insertAll($userGroups);
			}
		
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
			if (request()->action() != 'import') {
				if (empty($param['group_id'])) {
					$this->error = '请至少勾选一个用户组';
					return false;
				}
			}
			$subUserId = getSubUserId(true, 0, $id);
			if ((int)$param['parent_id'] == (int)$id) {
				$this->error = '直属上级不能是自己';
				return false;				
			}
			if ((int)$param['parent_id'] !== 1 && in_array($param['parent_id'],$subUserId)) {
				$this->error = '直属上级不能是自己或下属';
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
				Cache::rm('user_info' . $id);
				
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
	 * @param     array                      $paramArr 
	 * @return    [type]                     [description]
	 */
	public function login($username, $password, $verifyCode = '', $isRemember = false, $type = false, $authKey = '', $paramArr = [])
	{
		if ($paramArr['dingCode']) {
			$dingtalkModel = new \app\admin\model\Dingtalk();
            $username = $dingtalkModel->sign($paramArr['dingCode']);
			if (!$username) {
				$this->error = $dingtalkModel->getError();;
				return false;            	
            }            
		} else {
			if (!$password){
				$this->error = '密码不能为空';
				return false;
			}
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
		// 登录记录
		$login_record = new LoginRecord();
		$login_record->user_id = $userInfo['id'];

		// 三次出错，十五分钟禁止登录
		if (false === $error_count = $login_record->verify()) {
			$this->error = $login_record->error;
			return false;
		}

		$userInfo['thumb_img'] = $userInfo['thumb_img'] ? getFullPath($userInfo['thumb_img']) : '';
    	if (user_md5($password, $userInfo['salt'], $userInfo['username']) !== $userInfo['password'] && !$paramArr['dingCode']) {
			$this->error = '密码错误' . "，已错误 " . ($error_count[0] + 1) . " 次，错误 {$error_count[1]} 次之后将等待 {$error_count[2]} 分钟";
			$login_record->createRecord(LoginRecord::TYPE_PWD_ERROR);
			return false;
    	}
    	if ($userInfo['status'] === 0) {
			$this->error = '帐号已被禁用';
			$login_record->createRecord(LoginRecord::TYPE_USER_BANNED);
			return false;
		}
		
		$login_record->createRecord(LoginRecord::TYPE_SUCCESS);

        // 获取菜单和权限
        $dataList = $this->getMenuAndRule($userInfo['id']);

        if ($isRemember || $type) {
        	$secret['username'] = $username;
        	$secret['password'] = $password;
            $data['rememberKey'] = encrypt($secret);
        }

		//登录有效时间
        $cacheConfig = config('cache');
        $loginExpire = $cacheConfig['expire'] ? : 86400*3;        

        // 保存缓存
        session_start();
        $info['userInfo'] = $userInfo;
        $info['sessionId'] = session_id();
        $authKey = user_md5($userInfo['username'].$userInfo['password'].$info['sessionId'], $userInfo['salt']);
        // $info['_AUTH_LIST_'] = $dataList['rulesList'];
        $info['authKey'] = $authKey;
        
    	$platform = $paramArr['platform'] ? '_'.$paramArr['platform'] : ''; //请求平台(mobile,ding)
		//删除旧缓存
        if (cache('Auth_'.$userInfo['authkey'].$platform)) {
        	cache('Auth_'.$userInfo['authkey'].$platform, NULL);
        }      
        cache('Auth_'.$authKey.$platform, $info, $loginExpire);
        unset($userInfo['authkey']);
		
        // 返回信息
        $data['authKey']		= $authKey;
        $data['sessionId']		= $info['sessionId'];
        $data['userInfo']		= $userInfo;
        $data['authList']		= $dataList['authList'];
        $data['menusList']		= $dataList['menusList'];
        $data['loginExpire']	= $loginExpire;
             
        //保存authKey信息
        $userData = [];
        $userData['authkey'] = $authKey;
        $userData['authkey_time'] = time()+$loginExpire;
		//把状态未激活至为启用
    	if ($userInfo['status'] == 2) {
    		$userData['status'] = 1;
    	}
        $this->where(['id' => $userInfo['id']])->update($userData);
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
    public function getMenuAndRule($u_id)
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
			foreach ($groups as $k => $v) {
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
        //菜单管理(弃用)
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
		//应用控制
        $adminConfig = db('admin_config')->where(['pid' => 0,'status' => 1])->column('module');
        $adminConfig = $adminConfig ? array_merge($adminConfig,['bi','admin']) : ['bi','admin'];        
		foreach ($authList as $k=>$v) {
			if (!in_array($k,$adminConfig)) {
				unset($authList[$k]);
			}
			//商业智能权限细化
			if ($authList['bi']) {
				if (!in_array('oa',$adminConfig) && !in_array('crm',$adminConfig)) {
					unset($authList['bi']);
				} else {
					foreach ($authList['bi'] as $key=>$val) {
						if (!in_array('oa',$adminConfig)) {
							unset($authList['bi']['oa']);
						}
						if (!in_array('crm',$adminConfig)) {
							unset($authList['bi']['customer']);
							unset($authList['bi']['business']);
							unset($authList['bi']['product']);
							unset($authList['bi']['achievement']);
							unset($authList['bi']['contract']);
							unset($authList['bi']['portrait']);
							unset($authList['bi']['ranking']);
						}					
					}					
				}
			} else {
				unset($authList['bi']);
			}	
			if (in_array('oa',$adminConfig) && !$authList['oa']) {
				// $authList['oa'] = (object)array();
				$oaAuth =[]; //办公默认权限
				$oaAuth = ['announcement' => 'read'];
				$authList['oa'] = $oaAuth;
			}
			if (in_array('work',$adminConfig) && !$authList['work']) {
				// $authList['oa'] = (object)array();
				$oaAuth =[]; //项目默认权限
				$oaAuth = ['work' => 'read'];
				$authList['work'] = $oaAuth;
			}			
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
		$allStrIds = (array) $structure_id;
		if ($type == 2) {
			$structureModel = new \app\admin\model\Structure();
			foreach ($allStrIds as $v) {
				$allSubStrIds = [];
				$allSubStrIds = $structureModel->getAllChild($v);
				if ($allSubStrIds) {
					$allStrIds = array_merge($allStrIds, $allSubStrIds); //全部关联部门（包含下属部门）
				}				
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
				->where(['user.id' => $id])
				->field([
					'user.id',
					'username',
					'img',
					'thumb_img',
					'realname',
					'parent_id',
					'structure.name' => 'structure_name',
					'structure.id' => 'structure_id'
				])
				->cache('user_info' . $id, null, 'user_info')
				->find();
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
		$userIDs = $adminGroupUser ? array_merge($adminGroupUser, [1]) : [1];
		return $userIDs ? : [1];
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

	/**
     * [getUserThree 员工第三方扩展信息]
     * @param  key 分类
     * @author Michael_xu
     * @return    [array]
     */	
    public function getUserThree($key, $user_id)
    {
    	$resValue = db('admin_user_threeparty')->where(['key' => $key,'user_id' => $user_id])->value('value');
    	return $resValue ? : '';
	}			
	
	/**
	 * 获取当前登录用户信息
	 *
	 * @param string $key	默认返回所有信息
	 * @return mixed
	 * @author Ymob
	 * @datetime 2019-10-22 14:38:07
	 */
	public static function userInfo($key = '')
	{
        $request = Request::instance();
		$header = $request->header();

		$authKey = $header['authkey'];
		$sessionId = $header['sessionid'];
		$paramArr = $request->param();
		$platform = $paramArr['platform'] ? '_' . $paramArr['platform'] : ''; //请求平台(mobile,ding)
		$cache = cache('Auth_' . $authKey . $platform);
		if ($cache) {
			if ($key) {
				return $cache['userInfo'][$key];
			} else {
				return $cache['userInfo'];
			}
		} else {
			return false;
		}
	}

	/**
	 * 判断用户是否拥有 某(些) 角色
	 *
	 * @param array $group_list
	 * @param integer $user_id
	 * @return bool
	 * @author Ymob
	 * @datetime 2019-10-25 15:50:48
	 */
	public static function checkUserGroup($group_list = [], $user_id = 0)
	{
		$user_id = $user_id ?: self::userInfo('id');
		if (empty($group_list))
		return !!Access::where(['user_id' => $user_id, 'group_id' => ['IN', $group]])->value('user_id');
	}
}
