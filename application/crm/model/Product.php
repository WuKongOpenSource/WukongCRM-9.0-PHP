<?php
// +----------------------------------------------------------------------
// | Description: 产品
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com
// +----------------------------------------------------------------------
namespace app\crm\model;

use think\Db;
use app\admin\model\Common;
use think\Request;
use think\Validate;

class Product extends Common
{
	/**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如CRM模块用crm作为数据表前缀
     */
	protected $name = 'crm_product';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
	protected $autoWriteTimestamp = true;

	/**
     * [getDataList 产品list]
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
		unset($request['scene_id']);
		unset($request['search']);
		unset($request['user_id']);   	

        $request = $this->fmtRequest($request);
        $requestMap = $request['map'] ? : [];

		$sceneModel = new \app\admin\model\Scene();
		if ($scene_id) {
			//自定义场景
			$sceneMap = $sceneModel->getDataById($scene_id, $user_id, 'product') ? : [];
		} else {
			//默认场景
			$sceneMap = $sceneModel->getDefaultData('product', $user_id) ? : [];
		}
		if ($search) {
			//普通筛选
			$sceneMap['name'] = ['condition' => 'contains','value' => $search,'form_type' => 'text','name' => '产品名称'];
		}
		//优先级：普通筛选>高级筛选>场景
		$map = $requestMap ? array_merge($sceneMap, $requestMap) : $sceneMap;
		//高级筛选
		$map = where_arr($map, 'crm', 'product', 'index');
		if (isset($map['product.category_id'])) {
			$map['product_category.name'] = $map['product.category_id'];
			unset($map['product.category_id']);
		}
		if (!$map['product.status']) {
			$map['product.status'] = '上架';
		}
		//列表展示字段
		// $indexField = $fieldModel->getIndexField('crm_product', $user_id) ? : ['name'];
		$userField = $fieldModel->getFieldByFormType('crm_product', 'user'); //人员类型
		$structureField = $fieldModel->getFieldByFormType('crm_product', 'structure');  //部门类型		

		// $newIndexField = [];
		// foreach ($indexField as $k=>$v) {
		// 	$newIndexField[] = 'crm_product.'.$v;
		// }
		// $indexField  = $newIndexField;			

		$join = [
			['__CRM_PRODUCT_CATEGORY__ product_category', 'product_category.category_id = product.category_id', 'LEFT'],
		];
		$list_view = db('crm_product')
					 ->alias('product')
					 ->where($map)
				     ->join($join);
		$list = $list_view
        		->limit(($request['page']-1)*$request['limit'], $request['limit'])
        		// ->field('product_id,'.implode(',',$indexField).',product_category.name as category_name')
        		->field('product.*,product_category.name as category_name')
        		->select();
        $dataCount = db('crm_product')->alias('product')
					 ->where($map)
				     ->join($join)
				     ->count('product_id');
        foreach ($list as $k=>$v) {
        	$list[$k]['create_user_id_info'] = isset($v['create_user_id']) ? $userModel->getUserById($v['create_user_id']) : [];
        	$list[$k]['owner_user_id_info'] = isset($v['owner_user_id']) ? $userModel->getUserById($v['owner_user_id']) : [];
			foreach ($userField as $key => $val) {
        		$list[$k][$val.'_info'] = isset($v[$val]) ? $userModel->getListByStr($v[$val]) : [];
        	}
			foreach ($structureField as $key => $val) {
        		$list[$k][$val.'_info'] = isset($v[$val]) ? $structureModel->getDataByStr($v[$val]) : [];
        	}
        	//产品类型
        	$list[$k]['category_id_info'] = db('crm_product_category')->where(['category_id' => $v['category_id']])->value('name');
        }    
        $data = [];
        $data['list'] = $list;
        $data['dataCount'] = $dataCount ? : 0;

        return $data;
    }

	/**
	 * 创建产品主表信息
	 * @author Michael_xu
	 * @param  
	 * @return                            
	 */	
	public function createData($param)
	{
		$fieldModel = new \app\admin\model\Field();
		$productCategoryModel = model('ProductCategory');
		// 自动验证
		$validateArr = $fieldModel->validateField($this->name); //获取自定义字段验证规则
		$validate = new Validate($validateArr['rule'], $validateArr['message']);

		$result = $validate->check($param);
		if (!$result) {
			$this->error = $validate->getError();
			return false;
		}

		//处理部门、员工、附件、多选类型字段
		$arrFieldAtt = $fieldModel->getArrayField('crm_product');
		foreach ($arrFieldAtt as $k=>$v) {
			$param[$v] = arrayToString($param[$v]);
		}

		//产品分类
		$category_id = $param['category_id'];
		if (is_array($category_id)) {
			$param['category_id'] = $productCategoryModel->getIdByStr($category_id);
			$param['category_str'] = arrayToString($category_id);
		}

		if ($this->data($param)->allowField(true)->isUpdate(false)->save()) {
			$data = [];
			$data['product_id'] = $this->product_id;
			return $data;
		} else {
			$this->error = '添加失败';
			return false;
		}			
	}

	/**
	 * 编辑产品主表信息
	 * @author Michael_xu
	 * @param  
	 * @return                            
	 */	
	public function updateDataById($param, $product_id = '')
	{
		$dataInfo = $this->getDataById($product_id);
		$productCategoryModel = model('ProductCategory');
		if (!$dataInfo) {
			$this->error = '数据不存在或已删除';
			return false;
		}
		$param['product_id'] = $product_id;
		//过滤不能修改的字段
		$unUpdateField = ['create_user_id','is_deleted','delete_time'];
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

		//处理部门、员工、附件、多选类型字段
		$arrFieldAtt = $fieldModel->getArrayField('crm_product');
		foreach ($arrFieldAtt as $k=>$v) {
			$param[$v] = arrayToString($param[$v]);
		}

		//产品分类
		$category_id = $param['category_id'];
		if (is_array($category_id)) {
			$param['category_id'] = $productCategoryModel->getIdByStr($category_id);
			$param['category_str'] = arrayToString($category_id);
		}	

		if ($this->allowField(true)->save($param, ['product_id' => $product_id])) {
			//修改记录
			updateActionLog($param['user_id'], 'crm_product', $product_id, $dataInfo->data, $param);
			$data = [];
			$data['product_id'] = $product_id;
			return $data;
		} else {
			$this->rollback();
			$this->error = '编辑失败';
			return false;
		}					
	}

	/**
     * 产品数据
     * @param  $id 产品ID
     * @return 
     */	
   	public function getDataById($id = '')
   	{   		
   		$map['product_id'] = $id;
		$dataInfo = $this->where($map)->find();
		if (!$dataInfo) {
			$this->error = '暂无此数据';
			return false;
		}
		$userModel = new \app\admin\model\User();
		$dataInfo['create_user_id_info'] = $userModel->getUserById($dataInfo['create_user_id']);
        $dataInfo['category_id_info'] = db('crm_product_category')->where(['category_id' => $dataInfo['category_id']])->value('name');	
		return $dataInfo;
   	}

	/**
     * 相关产品创建（商机、合同相关产品数据）
     * @param  types 类型
     * @param  param['product'] 产品相关数据
     * @param  price 产品单价
     * @param  sales_price  销售价格
     * @param  num 数量
     * @param  discount 折扣
     * @param  subtotal 小计（折扣后价格）
     * @param  unit 单位
     * @param  total_price 折扣后整单总价
     * @param  discount_rate 整单折扣 
     * @param  objId 关联对象ID
     * @return 
     */ 
    public function createObject($types, $param, $objId)
    {
    	switch ($types) {
    		case 'crm_business' : $db = 'crm_business_product'; $rDb = 'crm_business'; $db_id = 'business_id'; break;
    		case 'crm_contract' : $db = 'crm_contract_product'; $rDb = 'crm_contract'; $db_id = 'contract_id'; break;
    		default : $this->error = '参数错误'; return false; break;
    	}

    	$total_price = 0;

    	if ($param['product']) {
			$product = [];
			// 启动事务
			Db::startTrans();
			try {
				foreach ($param['product'] as $key => $value) {
					$discount = 0;
			    	// $discount = ((100 - $value['discount']) > 0) ? (100 - $value['discount'])/100 : 0;	//折扣
					$product[$key]['product_id'] = $value['product_id'];
		    		$product[$key]['price'] = $value['price']; //产品单价
		    		$product[$key]['sales_price'] = $value['sales_price']; //售价
		    		$product[$key]['num'] = $value['num']; //数量
		    		$product[$key]['discount'] = $value['discount']; //折扣
		    		$product[$key]['unit'] = $value['unit'] ? : ''; //单位
		    		$product[$key]['subtotal'] = $value['subtotal'];
		    		// $total_price += $product[$key]['subtotal'] = round(($value['price'] * $value['num']) * $discount); //总价	
		    		$product[$key][$db_id] = $objId;
		    	}

		    	//删除
		    	db($db)->where([$db_id => $objId])->delete(); //原数据删除
				//新增
		    	db($db)->insertAll($product);

		    	$rData = [];
				//产品合计
				$rData['discount_rate'] = !empty($param['discount_rate']) ? $param['discount_rate'] : 0.00; //整单折扣
		    	$discount_rate = ((100 - $rData['discount_rate']) > 0) ? (100 - $rData['discount_rate'])/100 : 0;
		    	// $rData['total_price'] = $total_price ? $total_price*$discount_rate : '0.00'; //整单合计	
		    	$rData['total_price'] = $param['total_price'] ? : '0.00'; //整单合计	
		    	db($rDb)->where([$db_id => $objId])->update($rData);	    	
		    	
		    	// 提交事务
    			Db::commit();
		    	return true;    			
			} catch (\Exception $e) {
				$this->error = '产品数据创建出错';
				// 回滚事务
			    Db::rollback();					
	    		return false;   				    
			}
    	}
    }

	/**
     * [产品统计]
     * @author Michael_xu
     * @param     [string]                   $request [查询条件]
     * @return    [array]                    
     */		
	public function getStatistics($request)
    {
    	$userModel = new \app\admin\model\User();
		$where = [];
		//时间段
		$start_time = $request['start_time'];
		$end_time = $request['end_time'] ? $request['end_time']+86399 : '';
		if ($start_time && $end_time) {
			$where['contract.create_time'] = array('between',array($start_time,$end_time));
		}

		//员工IDS
		$map_user_ids = [];	
		if ($request['user_id']) {
			$map_user_ids = [$request['user_id']];
		} else {
			if ($request['structure_id']) {
                $map_user_ids = $userModel->getSubUserByStr($request['structure_id'], 2);
            }
		}
		$perUserIds = $userModel->getUserByPer('bi', 'product', 'read'); //权限范围内userIds
		$userIds = $map_user_ids ? array_intersect($map_user_ids, $perUserIds) : $perUserIds; //数组交集
		$where['contract.owner_user_id'] = array('in',$userIds);

		$join = [
			['__CRM_CONTRACT__ contract', 'contract.contract_id = a.contract_id', 'LEFT'],
			['__ADMIN_USER__ user', 'user.id = contract.owner_user_id', 'LEFT'],
			['__CRM_PRODUCT__ product', 'product.product_id = a.product_id', 'LEFT'],
			['__CRM_PRODUCT_CATEGORY__ product_category', 'product_category.category_id = product.category_id', 'LEFT'],
		];

		$list = db('crm_contract_product')
					 ->alias('a')
					 ->where($where)
				     ->join($join)
				     ->field('a.*,product.name as product_name,contract.customer_id,contract.owner_user_id,contract.name as contract_name,product_category.name as category_id_info,user.realname,product_category.category_id')
					 ->order('category_id,product_name')
				     ->select();
		foreach ($list as $k=>$v) {
			$customer_info = Db::name('CrmCustomer')->field('customer_id,name')->where('customer_id = '.$v['customer_id'])->field('customer_id,name')->find(); //客户
			$list[$k]['customer_id_info'] = $customer_info ? : array();
			$contract_info = Db::name('CrmContract')->field('contract_id,name,num')->where('contract_id = '.$v['contract_id'])->field('contract_id,name')->find(); //合同
			$list[$k]['contract_id_info'] = $contract_info ? : array();
			$product_info = Db::name('CrmProduct')->field('product_id,name')->where('product_id = '.$v['product_id'])->field('product_id,name')->find(); //产品
			$list[$k]['product_id_info'] = $product_info?:array();
			$owner_user_info = Db::name('AdminUser')->field('id,realname as name')->where('id = '.$v['owner_user_id'])->find(); //负责人
			$list[$k]['owner_user_id_info'] = $owner_user_info ? : array();
		}
        return $list;
    }  

	/**
     * [根据产品类别ID，查询父级ID]
     * @author Michael_xu
     * @param 
     * @return                   
     */		
	public function getPidStr($category_id, $idArr, $first = '')
	{
		if ($first == 1) $idArr = [];
		$idArr[] = $category_id;
		$pid = db('crm_product_category')->where(['category_id' => $category_id])->value('pid');
		if ($pid) {
			$idArr[] = $pid;
			$this->getPidStr($pid, $idArr);
		}
		$arr = array_reverse($idArr);
		$resStr = ','.implode(',',$arr).',';
		return $resStr;
	}         	
}