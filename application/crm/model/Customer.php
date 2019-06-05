<?php
// +----------------------------------------------------------------------
// | Description: 客户
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com
// +----------------------------------------------------------------------
namespace app\crm\model;

use think\Db;
use app\admin\model\Common;
use think\Request;
use think\Validate;

class Customer extends Common
{
	/**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如CRM模块用crm作为数据表前缀
     */
	protected $name = 'crm_customer';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
	protected $autoWriteTimestamp = true;

	protected $type = [
		// 'next_time' => 'timestamp',
	];

	/**
     * [getDataList 客户list]
     * @author Michael_xu
     * @param     [string]                   $map [查询条件]
     * @param     [number]                   $page     [当前页数]
     * @param     [number]                   $limit    [每页数量]
     * @return    [array]                    [description]
     */		
	public function getDataList($request)
    {
    	$userModel = new \app\admin\model\User();
    	$structureModel = new \app\admin\model\Structure();
    	$fieldModel = new \app\admin\model\Field();
		$search = $request['search'];
    	$user_id = $request['user_id'];
    	$scene_id = (int)$request['scene_id'];
    	$is_excel = $request['is_excel']; //导出
    	$action = $request['action']; //导出
		unset($request['scene_id']);
		unset($request['search']);
		unset($request['user_id']);	
		unset($request['is_excel']);	
		unset($request['action']);	

        $request = $this->fmtRequest( $request );
        $requestMap = $request['map'] ? : [];
		$sceneModel = new \app\admin\model\Scene();
		$sceneMap = [];
		if ($scene_id) {
			//自定义场景
			$sceneMap = $sceneModel->getDataById($scene_id, $user_id, 'customer') ? : [];
		} else {
			//默认场景
			$sceneMap = $sceneModel->getDefaultData('customer', $user_id) ? : [];
		}
		$searchMap = [];
		if ($search) {
			//普通筛选
			$searchMap = function($query) use ($search){
			        $query->where('customer.name',array('like','%'.$search.'%'))
			        	->whereOr('customer.mobile',array('like','%'.$search.'%'))
			        	->whereOr('customer.telephone',array('like','%'.$search.'%'));
			};
			// $sceneMap['name'] = ['condition' => 'contains','value' => $search,'form_type' => 'text','name' => '客户名称'];
		}
		$partMap = [];
		//优先级：普通筛选>高级筛选>场景
		if ($sceneMap['ro_user_id'] && $sceneMap['rw_user_id']) {
			//相关团队查询
			$map = $requestMap;
			$partMap = function($query) use ($sceneMap){
			        $query->where('customer.ro_user_id',array('like','%,'.$sceneMap['ro_user_id'].',%'))
			        	->whereOr('customer.rw_user_id',array('like','%,'.$sceneMap['rw_user_id'].',%'));
			};				
		} else {
			$map = $requestMap ? array_merge($sceneMap, $requestMap) : $sceneMap;
		}
		//高级筛选
		$map = where_arr($map, 'crm', 'customer', 'index');
		//公海
		$customerMap = [];
		$authMap = [];
		$poolMap = [];
		$requestData = $this->requestData();
		if ($requestData['a'] == 'pool' || $action == 'pool') {
			// unset($map);		
			//客户公海条件(没有负责人或已经到期)
        	$poolMap = $this->getWhereByPool();	
			if ($search) {
				//普通筛选
				$customerMap['customer.name'] = array('like','%'.$search.'%');
			}		
		} else {
			$customerMap = $this->getWhereByCustomer(); //默认条件
			if (!$partMap) {
				//权限
				$a = 'index';
				if ($is_excel) $a = 'excelExport';
				$auth_user_ids = $userModel->getUserByPer('crm', 'customer', $a);
			    //过滤权限
			    if (isset($map['customer.owner_user_id'])) {
			    	if (!is_array($map['customer.owner_user_id'][1])) {
						$map['customer.owner_user_id'][1] = [$map['customer.owner_user_id'][1]];
					}
					if ($map['customer.owner_user_id'][0] == 'neq') {
						$auth_user_ids = array_diff($auth_user_ids, $map['customer.owner_user_id'][1]) ? : [];	//取差集	
					} else {
						$auth_user_ids = array_intersect($map['customer.owner_user_id'][1], $auth_user_ids) ? : [];	//取交集	
					}
			        unset($map['customer.owner_user_id']);
					$auth_user_ids = array_merge(array_unique(array_filter($auth_user_ids))) ? : ['-1'];
				    //负责人、相关团队
				    $authMap['customer.owner_user_id'] = array('in',$auth_user_ids);      
			    } else {
					$authMapData = [];
			    	$authMapData['auth_user_ids'] = $auth_user_ids;
			    	$authMapData['user_id'] = $user_id;
			    	$authMap = function($query) use ($authMapData){
			    		$query->where(['customer.owner_user_id' => array('in',$authMapData['auth_user_ids'])])
			    			  	->whereOr(function ($query) use ($authMapData) {
			                        $query->where(['customer.ro_user_id' => array('like','%,'.$authMapData['user_id'].',%'),'customer.owner_user_id' => array('neq','')]);
			                    })
								->whereOr(function ($query) use ($authMapData) {
			                        $query->where(['customer.rw_user_id' => array('like','%,'.$authMapData['user_id'].',%'),'customer.owner_user_id' => array('neq','')]);
			                    });
				    };			    			    	
			    }
			}			
		}	
		//列表展示字段
		// $indexField = $fieldModel->getIndexField('crm_customer', $user_id) ? : array('name');
		$userField = $fieldModel->getFieldByFormType('crm_customer', 'user'); //人员类型
		$structureField = $fieldModel->getFieldByFormType('crm_customer', 'structure'); //部门类型
		//排序
		if ($request['order_type'] && $request['order_field']) {
			$order = 'customer.'.trim($request['order_field']).' '.trim($request['order_type']);
		} else {
			$order = 'customer.update_time desc';
		}
		$tops = Db::name('crm_top')->where(['module' => ['eq','customer'],'create_role_id' => ['eq',$user_id],'set_top' => ['eq',1]])->order('top_time asc')->column('module_id');
		$top_ids = implode(",", $tops);
		if ($tops) {
			$order_t = DB::raw("field(customer_id, $top_ids) desc");
		}
		$list = db('crm_customer')
				->alias('customer')
				->where($map)
				->where($searchMap)
				->where($customerMap)
				->where($authMap)
				->where($partMap)
				->where($poolMap)
        		->limit(($request['page']-1)*$request['limit'], $request['limit'])
        		// ->field('customer_id,'.implode(',',$indexField))
        		->order($order_t) /*置顶  自定义排序置顶*/
        		->order($order)
        		->select();
        $dataCount = db('crm_customer')->alias('customer')->where($map)->where($searchMap)->where($customerMap)->where($authMap)->where($partMap)->where($poolMap)->count('customer_id');

        //保护规则
		$configModel = new \app\crm\model\ConfigData();
        $configInfo = $configModel->getData();
        $paramPool = [];
        $paramPool['config'] = $configInfo['config'] ? : 0;
        $paramPool['follow_day'] = $configInfo['follow_day'] ? : 0;
        $paramPool['deal_day'] = $configInfo['deal_day'] ? : 0;

        $readAuthIds = $userModel->getUserByPer('crm', 'customer', 'read');
        $updateAuthIds = $userModel->getUserByPer('crm', 'customer', 'update');
        $deleteAuthIds = $userModel->getUserByPer('crm', 'customer', 'delete');
        foreach ($list as $k => $v) {
        	$list[$k]['create_user_id_info'] = isset($v['create_user_id']) ? $userModel->getUserById($v['create_user_id']) : [];
        	$list[$k]['owner_user_id_info'] = isset($v['owner_user_id']) ? $userModel->getUserById($v['owner_user_id']) : [];
        	foreach ($userField as $key => $val) {
        		$list[$k][$val.'_info'] = isset($v[$val]) ? $userModel->getListByStr($v[$val]) : [];
        	}
			foreach ($structureField as $key => $val) {
        		$list[$k][$val.'_info'] = isset($v[$val]) ? $structureModel->getDataByStr($v[$val]) : [];
        	} 
        	//商机数
        	$list[$k]['business_count'] = db('crm_business')->where(['customer_id' => $v['customer_id']])->count() ? : 0;
        	//距进入公海天数
        	if ($paramPool['config'] == 1 && $requestData['a'] !== 'pool') {
				$paramPool['update_time'] = $v['update_time'];
				$paramPool['deal_time'] = $v['deal_time'];
				$paramPool['is_lock'] = $v['is_lock'];
				$paramPool['deal_status'] = $v['deal_status'];
	        	$list[$k]['pool_day'] = $this->getPoolDay($paramPool);
        	}
        	//权限
        	$roPre = $userModel->rwPre($user_id, $v['ro_user_id'], $v['rw_user_id'], 'read');
        	$rwPre = $userModel->rwPre($user_id, $v['ro_user_id'], $v['rw_user_id'], 'update');
			$permission = [];
			$is_read = 0;
			$is_update = 0;
			$is_delete = 0;
			if (in_array($v['owner_user_id'],$readAuthIds) || $roPre || $rwPre) $is_read = 1;
			if (in_array($v['owner_user_id'],$updateAuthIds) || $rwPre) $is_update = 1;
			if (in_array($v['owner_user_id'],$deleteAuthIds)) $is_delete = 1;	        
	        $permission['is_read'] = $is_read;
	        $permission['is_update'] = $is_update;
	        $permission['is_delete'] = $is_delete;
	        $list[$k]['permission']	= $permission;        	
        }
        $data = [];
        $data['list'] = $list;
        $data['dataCount'] = $dataCount ? : 0;
        return $data;
    }

	/**
	 * 创建客户主表信息
	 * @author Michael_xu
	 * @param  
	 * @return                            
	 */	
	public function createData($param)
	{
		$fieldModel = new \app\admin\model\Field();
		//线索转客户
		if ($param['leads_id']) {
			$leadsData = $param;
			$leadsData['create_user_id'] = $param['create_user_id'];
			$leadsData['owner_user_id'] = $param['owner_user_id'];
			$leadsData['ro_user_id'] = '';
			$leadsData['rw_user_id'] = '';
			$leadsData['deal_time'] = time();
            $leadsData['deal_status'] = '未成交';			
			$param = $leadsData;
		} else {
			// 自动验证
			$validateArr = $fieldModel->validateField($this->name); //获取自定义字段验证规则
			$validate = new Validate($validateArr['rule'], $validateArr['message']);			
			$result = $validate->check($param);
			if (!$result) {
				$this->error = $validate->getError();
				return false;
			}	
		}
		//地址
		$param['address'] = $param['address'] ? implode(chr(10),$param['address']) : '';
		$param['deal_time'] = time();
		if (!$param['deal_status']) {
            $param['deal_status'] = '未成交';
        }	

		//处理部门、员工、附件、多选类型字段
		$arrFieldAtt = $fieldModel->getArrayField('crm_customer');
		foreach ($arrFieldAtt as $k=>$v) {
			$param[$v] = arrayToString($param[$v]);
		}
		if ($this->data($param)->allowField(true)->isUpdate(false)->save()) {
			//修改记录
			updateActionLog($param['create_user_id'], 'crm_customer', $this->customer_id, '', '', '创建了客户');			
			$data = [];
			$data['customer_id'] = $this->customer_id;
			$data['name'] = $param['name'];
			return $data;
		} else {
			$this->error = '添加失败';
			return false;
		}			
	}
	
	//根据IDs获取数组
	public function getDataByStr($idstr)
	{
		$idArr = stringToArray($idstr);
		if (!$idArr) {
			return [];
		}
		$list = Db::name('CrmCustomer')->where(['customer_id' => ['in',$idArr]])->select();
		return $list;
	}
	
	/**
	 * 编辑客户主表信息
	 * @author Michael_xu
	 * @param  
	 * @return                            
	 */	
	public function updateDataById($param, $customer_id = '')
	{
		$user_id = $param['user_id'];
		$dataInfo = $this->get($customer_id);
		if (!$dataInfo) {
			$this->error = '数据不存在或已删除';
			return false;
		}
		$param['customer_id'] = $customer_id;
		//过滤不能修改的字段
		$unUpdateField = ['create_user_id','is_deleted','delete_time','user_id'];
		foreach ($unUpdateField as $v) {
			unset($param[$v]);
		}
		
		$fieldModel = new \app\admin\model\Field();
		// 自动验证
		$validateArr = $fieldModel->validateField($this->name); //获取自定义字段验证规则
		$validate = new Validate($validateArr['rule'], $validateArr['message']);
		$result = $validate->check($param);
		if (!$result) {
			$this->error = $validate->getError();
			return false;
		}
		//地址
		$param['address'] = $param['address'] ? implode(chr(10),$param['address']) : '';
		if ($param['deal_status'] == '已成交' && $dataInfo->data['deal_status'] == '未成交') {
            $param['deal_time'] = time();
        }		

		//处理部门、员工、附件、多选类型字段
		$arrFieldAtt = $fieldModel->getArrayField('crm_customer');
		foreach ($arrFieldAtt as $k=>$v) {
			$param[$v] = arrayToString($param[$v]);
		}
		$param['follow'] = '已跟进';
		if ($this->allowField(true)->save($param, ['customer_id' => $customer_id])) {
			//修改记录
			updateActionLog($user_id, 'crm_customer', $customer_id, $dataInfo->data, $param);
			$data = [];
			$data['customer_id'] = $customer_id;
			return $data;
		} else {
			$this->error = '编辑失败';
			return false;
		}					
	}

	/**
     * 客户数据
     * @param  $id 客户ID
     * @return 
     */	
   	public function getDataById($id = '')
   	{  
   		$map['customer_id'] = $id;
		$dataInfo = $this->where($map)->find();
		if (!$dataInfo) {
			$this->error = '数据不存在或已删除';
			return false;
		}
		$userModel = new \app\admin\model\User();
		$dataInfo['create_user_id_info'] = isset($dataInfo['create_user_id']) ? $userModel->getUserById($dataInfo['create_user_id']) : [];
		$dataInfo['owner_user_id_info'] = isset($dataInfo['owner_user_id']) ? $userModel->getUserById($dataInfo['owner_user_id']) : []; 
		return $dataInfo;
   	}

	/**
     * [客户统计]
     * @author Michael_xu
     * @param
     * @return                  
     */		
	public function getStatistics($request)
    {
    	$userModel = new \app\admin\model\User();
    	$request = $this->fmtRequest( $request );
		$map = $request['map'] ? : [];
		unset($map['search']);
		$where = [];
		//时间段
		$start_time = $map['start_time'];
		$end_time = $map['end_time'] ? $map['end_time'] : time();
		$create_time = [];
		if ($start_time && $end_time) {
			$create_time = array('between',array($start_time,$end_time));
			$create_date = array('between',array(date('Y-m-d',$start_time),date('Y-m-d',$end_time)));
		}
		
		//员工IDS
		$map_user_ids = [];
		if ($map['user_id']) {
			$map_user_ids = array($map['user_id']);
		} else {
			if ($map['structure_id']) {
				$map_user_ids = $userModel->getSubUserByStr($map['structure_id'], 2);
			}
		}
		$perUserIds = $userModel->getUserByPer('bi', 'customer', 'read'); //权限范围内userIds
		$userIds = $map_user_ids ? array_intersect($map_user_ids, $perUserIds) : $perUserIds; //数组交集
		$where['id'] = array('in',$userIds);
		$where['type'] = 1;
		$userList = db('admin_user')->where($where)->field('id,username,thumb_img,realname')->select();
		foreach ($userList as $k=>$v) {
			$userList[$k]['thumb_img'] = $v['thumb_img'] ? getFullPath($v['thumb_img']) : '';
			$whereArr = [];
			$customer_num = 0; //客户数
			$deal_customer_num = 0; //成交客户数
			$deal_customer_rate = 0; //客户成交率
			$contract_money = '0.00'; //合同总金额
			$receivables_money = '0.00'; //回款金额
			$un_receivables_money = '0.00'; //未回款金额
			$deal_receivables_rate = 0; //回款完成率

			$whereArr['create_time'] = $create_time;
			$whereArr['owner_user_id'] = $v['id'];
			$userList[$k]['customer_num'] = $customer_num = $this->getDataCount($whereArr);
			$whereArr['deal_status'] = '已成交';
			$userList[$k]['deal_customer_num'] = $deal_customer_num = $this->getDataCount($whereArr);
			$userList[$k]['deal_customer_rate'] = $customer_num ? round(($deal_customer_num/$customer_num),4)*100 : 0;
			unset($whereArr['deal_status']);
			$whereArr['check_status'] = 2;
			unset($whereArr['create_time']);
			$whereArr['order_date'] = $create_date;			
			$userList[$k]['contract_money'] = $contract_money = db('crm_contract')->where($whereArr)->sum('money');
			unset($whereArr['order_date']);
			$whereArr['return_time'] = $create_date;
			$userList[$k]['receivables_money'] = $receivables_money = db('crm_receivables')->where($whereArr)->sum('money');
			$userList[$k]['un_receivables_money'] = $contract_money-$receivables_money >= 0 ? $contract_money-$receivables_money : '0.00';
			$userList[$k]['deal_receivables_rate'] = $contract_money ? round(($receivables_money/$contract_money), 2)*100 : 0;
		}
        return $userList ? : [];
    }  

	/**
     * [客户数量]
     * @author Michael_xu
     * @param 
     * @return                   
     */		
	public function getDataCount($map)
	{
		//非公海条件
		// $where = $this->getWhereByCustomer();
        $where = [];
        $dataCount = $this->where($map)->where($where)->count('customer_id');
        $count = $dataCount ? : 0;
        return $count;		
	}

	/**
     * [客户默认条件]
     * @author Michael_xu
     * @param 
     * @return                   
     */	
    public function getWhereByCustomer()
    {
		$configModel = new \app\crm\model\ConfigData();
		$userModel = new \app\admin\model\User();
        $configInfo = $configModel->getData();
    	$config = $configInfo['config'] ? : 0;
    	$follow_day = $configInfo['follow_day'] ? : 0;
    	$deal_day = $configInfo['deal_day'] ? : 0;
    	//默认条件(没有到期或已锁定)
    	$data['follow_time'] = time()-$follow_day*86400;
    	$data['deal_time'] = time()-$deal_day*86400;
    	if ($config == 1) {
			$whereData = function($query) use ($data){
			        		$query->where(function ($query) use ($data) {
		                        $query->where(['customer.update_time' => array('gt',$data['follow_time'])])
		                        	    ->whereOr(function ($query) use ($data) {
					                        $query->where(['customer.deal_time' => array('gt',$data['deal_time']),'customer.deal_status' => '已成交']);
					                    });
		                    })->whereOr(['customer.is_lock' => 1]);
						};
    	}
    	return $whereData ? : '';
    }	

	/**
     * [客户公海条件]
     * @author Michael_xu
     * @param 
     * @return                   
     */	
    public function getWhereByPool()
    {
		$configModel = new \app\crm\model\ConfigData();
        $configInfo = $configModel->getData();
    	$config = $configInfo['config'] ? : 0;
		$follow_day = $configInfo['follow_day'] ? : 0;
    	$deal_day = $configInfo['deal_day'] ? : 0;
    	$whereData = [];
    	//启用    	
    	if ($config == 1) {
			//默认公海条件(没有负责人或已经到期)
	    	$data['update_time'] = time()-$follow_day*86400;
	    	$data['deal_time'] = time()-$deal_day*86400;
	    	$data['deal_status'] = '未成交';
			$whereData = function($query) use ($data){
				        	$query->where(['customer.owner_user_id'=>0])
					        	->whereOr(function ($query) use ($data) {
									$query->where(function ($query) use ($data) {
				                        $query->where(['customer.update_time' => array('egt',$data['update_time'])])
											->where(function ($query) use ($data) {
						                        $query->where(['customer.deal_time' => array('lt',$data['deal_time']),'customer.deal_status' => '未成交']);
						                    });
				                    })->where(['customer.is_lock' => 0]);
								})->whereOr(function ($query) use ($data) {
									$query->where(function ($query) use ($data) {
				                        $query->where(['customer.update_time' => array('lt',$data['update_time'])])
											->where(function ($query) use ($data) {
						                        $query->where(['customer.deal_status' => '未成交']);
						                    });
				                    })->where(['customer.is_lock' => 0]);
								});							
							};
    	} else {
    		$whereData['customer.owner_user_id'] = 0;
    	}
    	return $whereData ? : '';
    }

	/**
     * 客户权限判断(是否客户公海)
     * @author Michael_xu
     * @param 
     * @return
     */       
    public function checkData($customer_id, $user_id)
    {
    	//权限范围
    	$userModel = new \app\admin\model\User();
    	$authIds = $userModel->getUserByPer(); //权限范围的user_id
    	//是否客户公海
    	$map = $this->getWhereByPool();
    	$where['customer_id'] = $customer_id;
    	$customerInfo = db('crm_customer')->alias('customer')->where($where)->where($map)->find();
    	if ($customerInfo) {
    		return true;
    	} else {
    		$customerInfo = db('crm_customer')->where(['customer_id' => $customer_id])->find();
    		if (in_array($user_id, $authIds)) {
    			return true;
    		}
    	}
    	$this->error = '没有权限';
    	return false;
    } 

	/**
     * 客户到期天数
     * @author Michael_xu
     * @param 
     * @return
     */     
    public function getPoolDay($param)
    {
    	$poolDay = '';
    	$is_lock = $param['is_lock'] ? : 0;
    	if (!$is_lock && $param['deal_status'] !== '已成交') {
    		$follow_time = time()-$param['follow_day']*86400;
	    	$deal_time = time()-$param['deal_day']*86400;
    		$sub_follow_day = ceil(($param['update_time']-$follow_time)/86400);
			$sub_deal_day = ceil(($param['deal_time']-$deal_time)/86400);
    		$poolDay = ($sub_deal_day > $sub_follow_day) ? $sub_follow_day : $sub_deal_day;
    		$poolDay = $poolDay ? : 0;
    	} else {
    		$poolDay = '-1'; //锁定
    	}
    	return $poolDay;
    }
}