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
    	$action = $request['action'];
    	$order_field = $request['order_field'];
    	$order_type = $request['order_type'];
    	$is_remind = $request['is_remind'];
    	$nearby = $request['nearby'];
    	$lng_lat = $request['lng_lat'];
    	$raidus = $request['raidus'];
		unset($request['scene_id']);
		unset($request['search']);
		unset($request['user_id']);	
		unset($request['is_excel']);	
		unset($request['action']);	
		unset($request['order_field']);	
		unset($request['order_type']);
		unset($request['is_remind']);
		unset($request['nearby']);
		unset($request['lng_lat']);
		unset($request['raidus']);

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
		}
		$partMap = [];
		//优先级：普通筛选>高级筛选>场景
		if (is_array($sceneMap)) {
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
		}
		//高级筛选
		$map = where_arr($map, 'crm', 'customer', 'index');
		//公海
		$customerMap = [];
		$authMap = [];
		$poolMap = [];
		$requestData = $this->requestData();
		if ($requestData['a'] == 'pool' || $action == 'pool') {	
			//客户公海条件(没有负责人或已经到期)
        	$poolMap = is_object($sceneMap) ? $sceneMap : $this->getWhereByPool();
			/*if ($search) {
				//普通筛选
				$customerMap['customer.name'] = array('like','%'.$search.'%');
			}*/		
		} else {
			$customerMap = ($is_remind == 1) ? $this->getWhereByRemind() : $this->getWhereByCustomer(); //默认条件
			//工作台仪表盘
			if ($requestData['a'] == 'indexlist' && $requestData['c'] == 'index') {
				$customerMap = [];
			}
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
		$indexField = $fieldModel->getIndexField('crm_customer', $user_id, 1) ? : array('name');
		$userField = $fieldModel->getFieldByFormType('crm_customer', 'user'); //人员类型
		$structureField = $fieldModel->getFieldByFormType('crm_customer', 'structure'); //部门类型
		//排序
		if ($order_type && $order_field) {
			$order = $fieldModel->getOrderByFormtype('crm_customer','customer',$order_field,$order_type);
		} else {
			$order = 'customer.update_time desc';
		}
		//置顶
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
        		->field(implode(',',$indexField))
        		->order($order_t) /*置顶*/
        		->orderRaw($order)
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
        if (count($list)) {
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
	        	$poolData = [];
	        	if ($paramPool['config'] == 1 && $requestData['a'] !== 'pool') {
					$paramPool['update_time'] = $v['update_time'];
					$paramPool['deal_time'] = $v['deal_time'];
					$paramPool['is_lock'] = $v['is_lock'];
					$paramPool['deal_status'] = $v['deal_status'];
					$poolData = $this->getPoolDay($paramPool);
	        	}
	        	$list[$k]['pool_day'] = $poolData ? $poolData['poolDay'] : '';
	        	$list[$k]['is_pool'] = $poolData ? $poolData['isPool'] : '';
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
	        $mapInfo = [];
	        if($nearby){
				$lng_lat = explode(',', $lng_lat);
				$lng = $mapInfo['center_lng'] = $lng_lat[0];
				$lat = $mapInfo['center_lat'] = $lng_lat[1];
				$raidus = $mapInfo['raidus'] = intval($raidus);
				foreach ($list as $k => $v) {
					//计算客户距中心点的距离
					$distance = getDistance($lat, $lng, $v['lat'], $v['lng']);
					if ($distance <= $raidus) {
						$list[$k]['distance'] = round($distance / 1000,2);  //单位转换为公里

						//格式化地址信息
						$address_arr = array();
						$address_arr = explode(chr(10), $v['address']);
						$list[$k]['address'] = implode('', $address_arr);

						//百度地图信息窗体js拼接信息
						$data_info .= '['.$v['lng'].','.$v['lat'].',"客户名称：'.$v['name'].'<br />&#12288;&#12288;地址：'.$list[$k]['address'].'<br />&#12288;负责人：'.$list[$k]['owner_user_id_info']['name'].'<br />&#12288;距离约：'.$list[$k]['distance'].'公里"],';

						$list[$k]['content'] = '客户名称：'.$v['name'].'<br />&#12288;&#12288;地址：'.$list[$k]['address'].'<br />&#12288;负责人：'.$list[$k]['owner_user_id_info']['name'].'<br />&#12288;距离约：'.$list[$k]['distance'].'公里';
					} else {
						unset($list[$k]);
					}
				}

				//根据相距距离由小到大，重新排序$list
				$temp_array = array();
				foreach ($list as $k => $v) {
					$temp_array[] = $v['distance'];
				}
				array_multisort($temp_array, SORT_ASC, $list);

				$mapInfo['data_info'] = '['.$data_info.']';
				$mapInfo['lng_lat'] = $lng_lat;
			}      	
        }
        $data = [];
        $data['list'] = $list ? : [];
        $data['dataCount'] = $dataCount ? : 0;
        $data['maoInfo'] = $mapInfo ? : [];
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
		$userModel = new \app\admin\model\User();
		$customerConfigModel = new \app\crm\model\CustomerConfig();
		//添加上限检测
		if (!$customerConfigModel->checkData($param['create_user_id'],1)) {
			$this->error = $customerConfigModel->getError();
			return false;
		}
		//地址
		$param['address'] = $param['address'] ? implode(chr(10),$param['address']) : '';
		$param['deal_time'] = time(); //领取、分配时间
		$param['deal_status'] = '未成交';		
		//线索转客户
		if ($param['leads_id']) {
			$leadsData = $param;
			$leadsData['create_user_id'] = $param['create_user_id'];
			$leadsData['owner_user_id'] = $param['owner_user_id'];
			$leadsData['ro_user_id'] = '';
			$leadsData['rw_user_id'] = '';
            $leadsData['detail_address'] = $param['detail_address']	? : '';			
			$param = $leadsData;
		} 
		// 自动验证
		$validateArr = $fieldModel->validateField($this->name); //获取自定义字段验证规则
		$validate = new Validate($validateArr['rule'], $validateArr['message']);			
		$result = $validate->check($param);
		if (!$result) {
			$this->error = $validate->getError();
			return false;
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

		//数据权限判断
        $userModel = new \app\admin\model\User();
        $auth_user_ids = $userModel->getUserByPer('crm', 'customer', 'update');
        //读写权限
        $rwPre = $userModel->rwPre($user_id, $dataInfo['ro_user_id'], $dataInfo['rw_user_id'], 'update');     
        //判断是否客户池数据
        $wherePool = $this->getWhereByPool();
        $resPool = db('crm_customer')->alias('customer')->where(['customer_id' => $param['id']])->where($wherePool)->find();
        if (!$resPool && !in_array($dataInfo['owner_user_id'],$auth_user_ids) && !$rwPre) {
            $this->error = '无权操作';
            return false;
        }		

		$param['customer_id'] = $customer_id;
		//过滤不能修改的字段
		$unUpdateField = ['create_user_id','is_deleted','delete_time','user_id'];
		foreach ($unUpdateField as $v) {
			unset($param[$v]);
		}
		$param['deal_status'] = $dataInfo['deal_status'];
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
		$param['aa'] = '111';
		if ($this->update($param, ['customer_id' => $customer_id], true)) {
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
		$dataInfo = $this->get($id);
		if (!$dataInfo) {
			$this->error = '数据不存在或已删除';
			return false;
		}
		$userModel = new \app\admin\model\User();
		$dataInfo['create_user_id_info'] = isset($dataInfo['create_user_id']) ? $userModel->getUserById($dataInfo['create_user_id']) : [];
		$dataInfo['owner_user_id_info'] = isset($dataInfo['owner_user_id']) ? $userModel->getUserById($dataInfo['owner_user_id']) : []; 
		
		//保护规则
		$configModel = new \app\crm\model\ConfigData();
        $configInfo = $configModel->getData();
        $paramPool = [];
        $paramPool['config'] = $configInfo['config'] ? : 0;
        $paramPool['follow_day'] = $configInfo['follow_day'] ? : 0;
        $paramPool['deal_day'] = $configInfo['deal_day'] ? : 0;
		//是否公海
    	$poolData = [];
    	if ($paramPool['config'] == 1) {
			$paramPool['update_time'] = $dataInfo['update_time'];
			$paramPool['deal_time'] = $dataInfo['deal_time'];
			$paramPool['is_lock'] = $dataInfo['is_lock'];
			$paramPool['deal_status'] = $dataInfo['deal_status'];
			$paramPool['owner_user_id'] = $dataInfo['owner_user_id'];
			$poolData = $this->getPoolDay($paramPool);
    	} else {
    		if (!$dataInfo['owner_user_id']) {
		        $poolData['isPool'] = 1;
		    }
    	}
    	$dataInfo['pool_day'] = $poolData ? $poolData['poolDay'] : '';
    	$dataInfo['is_pool'] = $poolData ? $poolData['isPool'] : '';
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
    		if ($follow_day < $deal_day) {
				$whereData = function($query) use ($data){
			        		$query->where(function ($query) use ($data) {
		                        $query->where(['customer.update_time' => array('gt',$data['follow_time']),'customer.deal_time' => array('gt',$data['deal_time'])]);
		                    })
		                    ->whereOr(['customer.deal_status' => '已成交'])
		                    ->whereOr(['customer.is_lock' => 1]);
						};    			
    		} else {
				$whereData = function($query) use ($data){
			        		$query->where(function ($query) use ($data) {
		                        $query->where(['customer.deal_time' => array('gt',$data['deal_time'])]);
		                    })
		                    ->whereOr(['customer.deal_status' => '已成交'])
		                    ->whereOr(['customer.is_lock' => 1]);
						};    			
    		}
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
	    	$data['follow_time'] = time()-$follow_day*86400;
	    	$data['deal_time'] = time()-$deal_day*86400;
	    	$data['deal_status'] = '未成交';
	    	if ($follow_day < $deal_day) {
				$whereData = function($query) use ($data){
				        	$query->where(['customer.owner_user_id'=>0])
					        	->whereOr(function ($query) use ($data) {
									$query->where(function ($query) use ($data) {
				                        $query->where(['customer.update_time' => array('elt',$data['follow_time'])])
											->whereOr(['customer.deal_time' => array('elt',$data['deal_time'])]);
				                    })
				                    ->where(['customer.is_lock' => 0])
				                    ->where(['customer.deal_status' => ['neq','已成交']]);
								});							
							};  		
	    	} else {
				$whereData = function($query) use ($data){
				        	$query->where(['customer.owner_user_id'=>0])
					        	->whereOr(function ($query) use ($data) {
									$query->where(function ($query) use ($data) {
				                        $query->where(['customer.deal_time' => array('elt',$data['deal_time'])]);
				                    })
				                    ->where(['customer.is_lock' => 0])
				                    ->where(['customer.deal_status' => ['neq','已成交']]);
								});							
							};	    		
	    	}
    	} else {
    		$whereData['customer.owner_user_id'] = 0;
    	}
    	return $whereData ? : '';
    }

	/**
     * 客户权限判断(是否客户公海)
     * @author Michael_xu
     * @param type 1 是公海返回false,默认是公海返回true
     * @return
     */       
    public function checkData($customer_id, $type = '')
    {
    	//权限范围
    	$userModel = new \app\admin\model\User();
    	$authIds = $userModel->getUserByPer(); //权限范围的user_id
    	//是否客户公海
    	$map = $this->getWhereByPool();
    	$where['customer_id'] = $customer_id;
    	$customerInfo = db('crm_customer')->alias('customer')->where($where)->where($map)->find();
    	if ($customerInfo && !$type) {
    		return true;
    	} else {
    		$customerInfo = db('crm_customer')->where(['customer_id' => $customer_id])->find();
    		if (in_array($customerInfo['owner_user_id'], $authIds)) {
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
    	$isPool = 0;
    	$is_lock = $param['is_lock'] ? : 0;
    	$deal_status = $param['deal_status'] ? : '未成交';
    	$update_time = $param['update_time'];
    	if (strtotime($param['update_time'])) {
    		$update_time = strtotime($param['update_time']);
    	}
    	if (!$is_lock && $deal_status !== '已成交') {
    		$follow_time = time()-$param['follow_day']*86400;
    	// p($param);
	    	$deal_time = time()-$param['deal_day']*86400;
	    	if (($update_time < $follow_time) || ($param['deal_time'] < $deal_time)) {
				$isPool = 1; //是公海
	    	} else {
				$sub_follow_day = ceil(($update_time-$follow_time)/86400);
				$sub_deal_day = ceil(($param['deal_time']-$deal_time)/86400);
	    		$poolDay = ($sub_deal_day > $sub_follow_day) ? $sub_follow_day : $sub_deal_day;
	    		$poolDay = $poolDay ? : 0;
	    		if ($poolDay < 0) {
	    			$isPool = 1; //是公海
	    		}	    		
	    	}
    	} elseif ($is_lock == 1) {
    		$poolDay = '-1'; //锁定
    	} elseif ($deal_status == '已成交') {
    		$poolDay = '';
    	}
    	if (!$param['owner_user_id']) {
    		$isPool = 1; //是公海
    	}
    	$data = [];
    	$data['poolDay'] = $poolDay;
    	$data['isPool'] = $isPool;
    	return $data;
    }

	/**
     * [待进入客户池条件]
     * @author Michael_xu
     * @param 
     * @return                   
     */	
    public function getWhereByRemind()
    {
		$configModel = new \app\crm\model\ConfigData();
        $configInfo = $configModel->getData();
        $config = $configInfo['config'] ? : 0;
        $follow_day = $configInfo['follow_day'] ? : 0;
        $deal_day = $configInfo['deal_day'] ? : 0;
		$remind_config = $configInfo['remind_config'] ? : 0;
		$remind_day = $configInfo['remind_day'] ? : 0;
        $whereData = [];    
		//启用        
        if ($config == 1 && $remind_config == 1) {
            //默认公海条件(没有负责人或已经到期)
            //通过提前提醒时间,计算查询时间段
            $remind_follow_day = ($follow_day-$remind_day > 0) ? ($follow_day-$remind_day) : $follow_day-1;
            $remind_deal_day = ($deal_day-$remind_day > 0) ? ($deal_day-$remind_day) : $deal_day-1;

            if (($follow_day > 0) && ($deal_day > 0)) {
				$follow_between = array(time()-$follow_day*86400,time()-$remind_follow_day*86400);
                $deal_between = array(time()-$deal_day*86400,time()-$remind_deal_day*86400);
                $data['update_between'] = $follow_between;
                $data['deal_between'] = $deal_between;
				if ($follow_day < $deal_day) {
					$whereData = function($query) use ($data){
					        	$query->where(function ($query) use ($data) {
										$query->where(function ($query) use ($data) {
					                        $query->where(['customer.update_time' => array('between',$data['update_between'])])
					                        ->whereOr(['customer.deal_time' => array('between',$data['deal_between'])]);
					                    })
					                    ->where(['customer.is_lock' => 0])
					                    ->where(['customer.deal_status' => ['neq','已成交']]);
									});							
								};  		
		    	} else {
					$whereData = function($query) use ($data){
					        	$query->where(function ($query) use ($data) {
										$query->where(function ($query) use ($data) {
					                        $query->where(['customer.deal_time' => array('between',$data['deal_between'])]);
					                    })
					                    ->where(['customer.is_lock' => 0])
					                    ->where(['customer.deal_status' => ['neq','已成交']]);
									});							
								};	    		
		    	}
            } else {
                $whereData['customer.customer_id'] = 0;
            }
        } else {
            $whereData['customer.customer_id'] = 0;
        }
        return $whereData ? : '';
    } 

	/**
     * [今日进入客户池条件]
     * @author Michael_xu
     * @param 
     * @return                   
     */	
    public function getWhereByToday()
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
            //通过提前提醒时间,计算查询时间段
            if (($follow_day > 0) && ($deal_day > 0)) {
                $follow_between = array(strtotime(date('Y-m-d',time()-$follow_day*86400)),time()-$follow_day*86400);
                $deal_between = array(strtotime(date('Y-m-d',time()-$deal_day*86400)),time()-$deal_day*86400);

                $data['update_time'] = time()-$follow_day*86400;
                $data['deal_time'] = time()-$deal_day*86400;
                $data['update_between'] = $follow_between;
                $data['deal_between'] = $deal_between;

				if ($follow_day < $deal_day) {
					$whereData = function($query) use ($data){
					        	$query->where(['customer.owner_user_id'=>0])
						        	->whereOr(function ($query) use ($data) {
										$query->where(function ($query) use ($data) {
					                        $query->where(['customer.update_time' => array('between',$data['update_between'])])
												->whereOr(['customer.deal_time' => array('between',$data['deal_between'])]);
					                    })
					                    ->where(['customer.is_lock' => 0])
					                    ->where(['customer.deal_status' => ['neq','已成交']]);
									});							
								};  		
		    	} else {
					$whereData = function($query) use ($data){
					        	$query->where(['customer.owner_user_id'=>0])
						        	->whereOr(function ($query) use ($data) {
										$query->where(function ($query) use ($data) {
					                        $query->where(['customer.deal_time' => array('between',$data['deal_between'])]);
					                    })
					                    ->where(['customer.is_lock' => 0])
					                    ->where(['customer.deal_status' => ['neq','已成交']]);
									});							
								};	    		
		    	}        
            } else {
                $whereData['customer.customer_id'] = 0;
            }
        } else {
        	$whereData['customer.owner_user_id'] = 0;
        	$whereData['customer.update_time'] = array('between',array(strtotime(date('Y-m-d',time())),time()));
        }
        return $whereData ? : '';
    }    

	/**
     * [客户拥有、锁定数]
     * @author Michael_xu
     * @param is_deal 1包含成交客户
     * @param types 1拥有客户上限2锁定客户上限 
     * @return                   
     */	
    public function getCountByHave($user_id, $is_deal = 0,$types = 1)
    {
		$where = [];
    	$where['owner_user_id'] = $user_id;    	
    	//公海逻辑
		$configModel = new \app\crm\model\ConfigData();
		$userModel = new \app\admin\model\User();
        $configInfo = $configModel->getData();
    	$config = $configInfo['config'] ? : 0;
    	$follow_day = $configInfo['follow_day'] ? : 0;
    	$deal_day = $configInfo['deal_day'] ? : 0;
    	//默认条件(没有到期或已锁定)
    	$data['follow_time'] = time()-$follow_day*86400;
    	$data['deal_time'] = time()-$deal_day*86400;
    	$whereData = '';
    	//公海开启
    	if ($config == 1) {
			switch ($types) {
				case '1' : 
					if ($is_deal !== 1) {
						//不包含成交客户
						$where['deal_status'] = ['neq','已成交'];
						if ($follow_day < $deal_day) {
							$whereData = function($query) use ($data){
						        		$query->where(function ($query) use ($data) {
					                        $query->where(['update_time' => array('gt',$data['follow_time']),'deal_time' => array('gt',$data['deal_time'])]);
					                    });
									};    			
			    		} else {
							$whereData = function($query) use ($data){
						        		$query->where(function ($query) use ($data) {
					                        $query->where(['deal_time' => array('gt',$data['deal_time'])]);
					                    });
									};    			
			    		}						
					} else {
						if ($follow_day < $deal_day) {
							$whereData = function($query) use ($data){
						        		$query->where(function ($query) use ($data) {
					                        $query->where(['update_time' => array('gt',$data['follow_time']),'deal_time' => array('gt',$data['deal_time'])]);
					                    })
					                    ->whereOr(['deal_status' => ['eq','已成交']]);
									};    			
			    		} else {
							$whereData = function($query) use ($data){
						        		$query->where(function ($query) use ($data) {
					                        $query->where(['deal_time' => array('gt',$data['deal_time'])]);
					                    })
					                    ->whereOr(['deal_status' => ['eq','已成交']]);
									};    			
			    		}						
					}
		    		break;    						
				case '2' : 
					$where['is_lock'] = ['eq',1]; 
					//默认不包含成交客户
					$where['deal_status'] = ['neq','已成交'];
		    		break;    					
			}
    	} else {
			//公海未开启
    		if ($is_deal !== 1) {
				//不包含成交客户
				$where['deal_status'] = ['neq','已成交'];					
    		} 
			switch ($types) {
				case '2' : 
					//锁定，默认不包含成交客户
					$where['deal_status'] = ['neq','已成交'];
					$where['is_lock'] = 1; 
					break;
			}
    	}
    	$count = $this->where($where)->where($whereData)->count();
    	return $count ? : 0;
    }	       
}