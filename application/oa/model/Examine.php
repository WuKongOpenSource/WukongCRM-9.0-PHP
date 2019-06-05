<?php
// +----------------------------------------------------------------------
// | Description: 审批
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com
// +----------------------------------------------------------------------
namespace app\oa\model;

use think\Db;
use app\admin\model\Common;
use think\Request;
use think\Validate;
use app\admin\model\Field;

class Examine extends Common
{
	/**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如CRM模块用crm作为数据表前缀
     */
	protected $name = 'oa_examine';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
	protected $autoWriteTimestamp = true;
	private $statusArr = ['0'=>'待审核','1'=>'审核中','2'=>'审核通过','3'=>'已拒绝','4'=>'已撤回'];

	/**
     * [getDataList 审批list]
     * @author Michael_xu
     * @param     [string]                   $map [查询条件]
     * @param     [number]                   $page     [当前页数]
     * @param     [number]                   $limit    [每页数量]
     * @return 
     */		
	public function getDataList($request)
    {  	
    	$userModel = new \app\admin\model\User();
    	$fileModel = new \app\admin\model\File();
		$businessModel = new \app\crm\model\Business();
        $contactsModel = new \app\crm\model\Contacts();
        $contractModel = new \app\crm\model\Contract();
        $customerModel = new \app\crm\model\Customer();    	

    	$by = $request['by'] ? : 'my'; //my我发起的,examine我审批的,all全部(下属发起的)
    	$user_id = $request['user_id'];
    	$check_status = $request['check_status'];
    	unset($request['by']);
    	unset($request['user_id']);
    	unset($request['check_status']);
        $request = $this->fmtRequest( $request );
        $map = $request['map'] ? : [];
        if (isset($map['search']) && $map['search']) {
			//普通筛选
			$map['examine.content'] = ['like', '%'.$map['search'].'%'];
		} else {
			$map = where_arr($map, 'oa', 'examine', 'index'); //高级筛选
		}
		unset($map['search']);
		//审批类型
		if (!$map['examine.category_id']) {
			$map['examine.category_id'] = array('gt',0); //审批类型
		}
		$map_str = '';
		switch ($by) {
			case 'my' : $map['examine.create_user_id'] = $user_id; break;
			case 'examine' : 
				$map_str = "(( `check_user_id` LIKE '%,".$user_id.",%' OR `check_user_id` = ".$user_id." ) OR ( `flow_user_id` LIKE '%,".$user_id.",%'  OR `flow_user_id` = ".$user_id." ) )";
				break;
			case 'already_examine' : $map['flow_user_id'] = [['like','%,'.$user_id.',%'],['eq',$user_id],'or']; break; //已审
			case 'stay_examine' : $map['check_user_id'] = [['like','%,'.$user_id.',%'],['eq',$user_id],'or']; break; //待审
			case 'all' : $auth_user_ids = getSubUserId(); $map['examine.create_user_id'] = array('in',$auth_user_ids); break; //全部
			default : $map['examine.create_user_id'] = $user_id; break;
		}
		$order = 'examine.update_time desc,examine.create_time asc';
		//发起时间
		if ($map['examine.between_time'][0] && $map['examine.between_time'][1]) {
			$start_time = $map['examine.between_time'][0];
			$end_time = $map['examine.between_time'][1];
			$map['examine.create_time'] = array('between',array($start_time,$end_time));
		}
		unset($map['examine.between_time']);
		//审核状态
		if ($check_status == 'all') {
			$map['examine.check_status'] = ['egt',0];
		} else {
			$map['examine.check_status'] = $check_status;
		}

		$join = [
			['__ADMIN_USER__ user', 'user.id = examine.create_user_id', 'LEFT'],
			['__OA_EXAMINE_CATEGORY__ examine_category', 'examine_category.category_id = examine.category_id', 'LEFT'],
		];
		$list_view = db('oa_examine')
					 ->alias('examine')
					 ->where($map_str)
					 ->where($map)
				     ->join($join);

		$list = $list_view
        		->page($request['page'], $request['limit'])
        		->field('examine.*,user.realname,user.thumb_img,examine_category.title as category_name')
        		->order($order)
        		->select();
        $admin_user_ids = $userModel->getAdminId();
       	foreach ($list as $k=>$v) {
       		$list[$k]['create_user_info'] = isset($v['create_user_id']) ? $userModel->getUserById($v['create_user_id']) : [];
       		$causeCount = 0;
       		$causeTitle = '';
       		$duration = $v['duration'] ? : '0.0';
       		$money = $v['money'] ? : '0.00';
       		if (in_array($v['category_id'],['3','5'])) {
       			$causeCount = db('oa_examine_travel')->where(['examine_id' => $v['examine_id']])->count() ? : 0;
       			if ($v['category_id'] == 3) $causeTitle = $causeCount.'个行程,共'.$duration.'天';
       			if ($v['category_id'] == 5) $causeTitle = $causeCount.'个报销事项,共'.$money.'元';
       		}
       		$list[$k]['causeTitle'] = $causeTitle;
       		$list[$k]['causeCount'] = $causeCount ? : 0;
			//关联业务
			$relation = [];
			$relation = db('oa_examine_relation')->where(['examine_id' => $v['examine_id']])->find();
			$list[$k]['businessList'] = $relation['business_ids'] ? $businessModel->getDataByStr($relation['business_ids']) : []; //商机
			$list[$k]['contactsList'] = $relation['contacts_ids'] ? $contactsModel->getDataByStr($relation['contacts_ids']) : []; //联系人
			$list[$k]['contractList'] = $relation['contract_ids'] ? $contractModel->getDataByStr($relation['contract_ids']) : []; //合同
			$list[$k]['customerList'] = $relation['customer_ids'] ? $customerModel->getDataByStr($relation['customer_ids']) : []; //客户

			//附件
			$fileList = [];
			$imgList = [];
			$where = [];
			$where['module'] = 'oa_examine';
			$where['module_id'] = $v['examine_id'];			
			$newFileList = [];
			$newFileList = $fileModel->getDataList($where);
			foreach ($newFileList['list'] as $val) {
				if ($val['types'] == 'file') {
					$fileList[] = $val;
				} else {
					$imgList[] = $val;
				}
			}
			$list[$k]['fileList'] = $fileList ? : [];
			$list[$k]['imgList'] = $imgList ? : [];

			//创建人或管理员有撤销权限
			$permission = [];
			$is_recheck = 0;
			$is_update = 0;
			$is_delete = 0;
	        //创建人或负责人或管理员有撤销权限
	        if ($v['create_user_id'] == $user_id || in_array($user_id, $admin_user_ids)) {
	            if (!in_array($v['check_status'],['2','3','4'])) {
	                $is_recheck = 1;
	            }
	        }
	        //创建人（失败、撤销状态时可编辑）
			if ($v['create_user_id'] == $user_id && in_array($v['check_status'],['3','4'])) {
		        $is_update = 1;
				$is_delete = 1;
		    }
		    $permission['is_recheck'] = $is_recheck;	        
	        $permission['is_update'] = $is_update;
	        $permission['is_delete'] = $is_delete;
	        $list[$k]['permission']	= $permission;
	        $list[$k]['check_status_info'] = $this->statusArr[$v['check_status']];   		
       	}
        $dataCount = $this->alias('examine')
					 ->where($map_str)
					 ->where($map)
				     ->join($join)
				     ->count('examine_id');  
        $data = [];
        $data['list'] = $list;
        $data['dataCount'] = $dataCount ? : 0;
        return $data;
    }

	/**
	 * 创建审批信息
	 * @author Michael_xu
	 * @param  
	 * @return                            
	 */	
	public function createData($param)
	{
		$fieldModel = new \app\admin\model\Field();
		$userModel = new \app\admin\model\User();
		$examineCategoryModel = new \app\oa\model\ExamineCategory();
		$examineDataModel = new \app\oa\model\ExamineData();
		if (!$param['category_id']) {
			$this->error = '参数错误';
			return false;
		}		
		// 自动验证
		$validateArr = $fieldModel->validateField($this->name,$param['category_id']); //获取自定义字段验证规则
		$validate = new Validate($validateArr['rule'], $validateArr['message']);			
		$result = $validate->check($param);
		if (!$result) {
			$this->error = $validate->getError();
			return false;
		}
		
		$categoryInfo = $examineCategoryModel->getDataById($param['category_id']);

		$fileArr = $param['file_id']; //接收表单附件
		unset($param['file_id']);
		if ($this->data($param)->allowField(true)->save()) {
			//处理自定义字段数据
			$resData = $examineDataModel->createData($param, $this->examine_id);
			if ($resData) {
				//处理附件关系
		        if ($fileArr) {
		            $fileModel = new \app\admin\model\File();
		            $resData = $fileModel->createDataById($fileArr, 'oa_examine', $this->examine_id);
					if ($resData == false) {
			        	$this->error = '附件上传失败';
			        	return false;
			        }
		        }
		        //相关业务
		        $rdata = [];
				$rdata['customer_ids'] = $param['customer_ids'] ? arrayToString($param['customer_ids']) : '';
				$rdata['contacts_ids'] = $param['contacts_ids'] ? arrayToString($param['contacts_ids']) : '';
				$rdata['business_ids'] = $param['business_ids'] ? arrayToString($param['business_ids']) : '';
				$rdata['contract_ids'] = $param['contract_ids'] ? arrayToString($param['contract_ids']) : '';
				$rdata['examine_id'] = $this->examine_id;
				$rdata['status'] = 1;
				$rdata['create_time'] = time();				
				Db::name('OaExamineRelation')->insert($rdata);						        

		        //处理差旅相关
	            $resTravel = true;
	            if (in_array($param['category_id'],['3','5']) && $param['cause']) {
	                $resTravel = $this->createTravelById($param['cause'], $this->examine_id);
	            }
	            if (!$resTravel) {
	                $this->error = '相关事项保存失败，请重试';
			        return false;
	            }
				//站内信
	            $createUserInfo = $userModel->getDataById($param['create_user_id']);
	            $send_user_id = stringToArray($param['check_user_id']);
	            $sendContent = $createUserInfo['realname'].'提交的【'.$categoryInfo['title'].'】,需要您审批';
	            if ($send_user_id) {
	            	sendMessage($send_user_id, $sendContent, $this->examine_id, 1);
	            }            

				$data = [];
				$data['examine_id'] = $this->examine_id;
				return $data;				
			} else {
				$this->error = $examineDataModel->getError();
				return false;				
			}
		} else {
			$this->error = '添加失败';
			return false;
		}			
	}

	/**
	 * 编辑审批信息
	 * @author Michael_xu
	 * @param  
	 * @return                            
	 */	
	public function updateDataById($param, $examine_id = '')
	{
		$examine_id = intval($examine_id);
		$userModel = new \app\admin\model\User();
		$examineCategoryModel = new \app\oa\model\ExamineCategory();
		unset($param['id']);
		$dataInfo = db('oa_examine')->where(['examine_id' => $examine_id])->find();
		if (!$dataInfo) {
			$this->error = '数据不存在或已删除';
			return false;
		}
		//过滤不能修改的字段
		$unUpdateField = ['create_user_id','is_deleted','delete_time'];
		foreach ($unUpdateField as $v) {
			unset($param[$v]);
		}
		$categoryInfo = $examineCategoryModel->getDataById($dataInfo['category_id']);
		
		//验证
		$fieldModel = new \app\admin\model\Field();
		$validateArr = $fieldModel->validateField($this->name, $dataInfo['category_id']); //获取自定义字段验证规则
		$validate = new Validate($validateArr['rule'], $validateArr['message']);
		$result = $validate->check($param);
		if (!$result) {
			$this->error = $validate->getError();
			return false;
		}
		
		$fileArr = $param['file_id']; //接收表单附件
		unset($param['file_id']);

		if ($this->allowField(true)->save($param, ['examine_id' => $examine_id])) {
			//处理附件关系
	        if ($fileArr) {
	            $fileModel = new \app\admin\model\File();
	            $resData = $fileModel->createDataById($fileArr, 'oa_examine', $examine_id);
				if ($resData == false) {
		        	$this->error = '附件上传失败';
		        	return false;
		        }
	        }			

			//站内信
            $createUserInfo = $userModel->getDataById($param['user_id']);
            $send_user_id = stringToArray($param['check_user_id']);
            $sendContent = $createUserInfo['realname'].'提交了【'.$categoryInfo['title'].'】,需要您审批';
            if ($send_user_id) {
            	sendMessage($send_user_id, $sendContent, $examine_id, 1);
            }

			//相关业务
	        $rdata = [];
			$rdata['customer_ids'] = $param['customer_ids'] ? arrayToString($param['customer_ids']) : [];
			$rdata['contacts_ids'] = $param['contacts_ids'] ? arrayToString($param['contacts_ids']) : [];
			$rdata['business_ids'] = $param['business_ids'] ? arrayToString($param['business_ids']) : [];
			$rdata['contract_ids'] = $param['contract_ids'] ? arrayToString($param['contract_ids']) : [];		
			Db::name('OaExamineRelation')->where('examine_id = '.$examine_id)->update($rdata);  

			//处理差旅相关
            $resTravel = true;
            if (in_array($dataInfo['category_id'],['3','5']) && $param['cause']) {
                $resTravel = $this->updateTravelById($param['cause'], $examine_id);
            }
            if (!$resTravel) {
                $this->error = '相关事项保存失败，请重试';
		        return false;
            }
			//站内信
            $createUserInfo = $userModel->getDataById($dataInfo['create_user_id']);
            $send_user_id = stringToArray($param['check_user_id']);
            $sendContent = $createUserInfo['realname'].'提交的【'.$categoryInfo['title'].'】,需要您审批';
            if ($send_user_id) {
            	sendMessage($send_user_id, $sendContent, $examine_id, 1);
            }             		       		

			$data = [];
			$data['examine_id'] = $examine_id;
			return $data;
		} else {
			$this->error = '编辑失败';
			return false;
		}					
	}

	/**
     * 审批数据
     * @param  $id 审批ID
     * @return 
     */	
   	public function getDataById($id = '')
   	{   
   		$examineData = new \app\oa\model\ExamineData();
   		$fieldModel = new \app\admin\model\Field();		
   		$fileModel = new \app\admin\model\File();		
   		$map['examine.examine_id'] = $id;
		$data_view = db('oa_examine')
					 ->where($map)
				     ->alias('examine')
				     ->join('__OA_EXAMINE_CATEGORY__ examine_category', 'examine_category.category_id = examine.category_id', 'LEFT');

		$dataInfo = $data_view
        			->field('examine.*,examine_category.title as category_name')
        			->find();
		if (!$dataInfo) {
			$this->error = '暂无此数据';
			return false;
		}
		//自定义字段信息
		$examineDataInfo = $examineData->getDataById($id);
		$dataInfo = $examineDataInfo ? array_merge($dataInfo,$examineDataInfo) : $dataInfo;
        //表格数据处理
        // $fieldList = $fieldModel->getFieldByFormType('oa_examine', 'form');
        // foreach ($fieldList as $k=>$v) {
        // 	$dataInfo[$v] = $fieldModel->getFormValueByField($v, $dataInfo[$v]);
        // }

        //关联业务
        $businessModel = new \app\crm\model\Business();
        $contactsModel = new \app\crm\model\Contacts();
        $contractModel = new \app\crm\model\Contract();
        $customerModel = new \app\crm\model\Customer();
		$relation = Db::name('OaExamineRelation')->where('examine_id ='.$id)->find();
		$dataInfo['businessList'] = $relation['business_ids'] ? $businessModel->getDataByStr($relation['business_ids']) : []; //商机
		$dataInfo['contactsList'] = $relation['contacts_ids'] ? $contactsModel->getDataByStr($relation['contacts_ids']) : []; //联系人
		$dataInfo['contractList'] = $relation['contract_ids'] ? $contractModel->getDataByStr($relation['contract_ids']) : []; //合同
		$dataInfo['customerList'] = $relation['customer_ids'] ? $customerModel->getDataByStr($relation['customer_ids']) : []; //客户  

		$travelList = [];
		if (in_array($dataInfo['category_id'],['3','5'])) {
			//行程、费用明细
			$whereTravel = [];
			$whereTravel['examine_id'] = $dataInfo['examine_id'];
			$travelList = db('oa_examine_travel')->where($whereTravel)->select() ? : [];
			foreach ($travelList as $k=>$v) {
				//附件
				$fileList = [];
				$imgList = [];
				$where = [];
				$where['module'] = 'oa_examine_travel';
				$where['module_id'] = $v['travel_id'];			
				$newFileList = [];
				$newFileList = $fileModel->getDataList($where, 'all');
				if ($newFileList['list']) {
					foreach ($newFileList['list'] as $val) {
						if ($val['types'] == 'file') {
							$fileList[] = $val;
						} else {
							$imgList[] = $val;
						}
					}					
				}				
				$travelList[$k]['fileList'] = $fileList ? : [];
				$travelList[$k]['imgList'] = $imgList ? : [];				
			}			
   		}
   		$dataInfo['travelList'] = $travelList;

		//附件
		$fileList = [];
		$imgList = [];
		$where = [];
		$where['module'] = 'oa_examine';
		$where['module_id'] = $id;			
		$newFileList = [];
		$newFileList = $fileModel->getDataList($where, 'all');
		foreach ($newFileList['list'] as $val) {
			if ($val['types'] == 'file') {
				$fileList[] = $val;
			} else {
				$imgList[] = $val;
			}
		}
		$dataInfo['fileList'] = $fileList ? : [];
		$dataInfo['imgList'] = $imgList ? : [];				   

		$userModel = new \app\admin\model\User();
		$dataInfo['create_user_info'] = $userModel->getUserById($dataInfo['create_user_id']);
		$dataInfo['examine_id'] = $id;
		return $dataInfo;
   	}

	/**
     * 审批差旅数据保存
     * @param  examine_id 审批ID
     * @return 
     */
    public function createTravelById($data = [], $examine_id)
    {
		if (!$examine_id) {
			$this->error = '参数错误';
			return false;
		}
		$successRes = true;
		foreach ($data as $k=>$v) {
			$newData = [];
			$fileArr = [];
			unset($v['files']);
			$newData = $v;
			$newData['examine_id'] = $examine_id;
			$fileArr = $v['file_id']; //接收表单附件
			unset($newData['file_id']);
			unset($newData['fileList']);
			unset($newData['fileList']);			
			if ($travel_id = db('oa_examine_travel')->insertGetId($newData)) {
				//处理附件关系
		        if ($fileArr) {
		            $fileModel = new \app\admin\model\File();
		            $resData = $fileModel->createDataById($fileArr, 'oa_examine_travel', $travel_id);
					if ($resData == false) {
			        	$successRes = false;
			        	return false;
			        }
		        }
			} else {
				$successRes = false;
				return false;
			}				
		}
		if (!$successRes) {
			$this->error = '审批事项创建失败';
			return false;
		}
		return true;		    	
    } 

	/**
     * 审批差旅数据编辑
     * @param  examine_id 审批ID
     * @return 
     */
    public function updateTravelById($data = [], $examine_id)
    {
		if (!$examine_id) {
			$this->error = '参数错误';
			return false;
		}
		$oldTravelIds = db('oa_examine_travel')->where(['examine_id' => $examine_id])->column('travel_id');
		$oldTravelFileIds = db('oa_examine_travel_file')->where(['travel_id' => ['in',$oldTravelIds]])->column('r_id');

		$successRes = true;
		foreach ($data as $k=>$v) {
			$newData = [];
			$fileArr = [];
			unset($v['files']);
			$newData = $v;
			$newData['examine_id'] = $examine_id;
			$fileArr = $v['file_id']; //接收表单附件
			unset($newData['file_id']);
			unset($newData['fileList']);
			unset($newData['imgList']);
			unset($newData['travel_id']);
			if ($travel_id = db('oa_examine_travel')->insertGetId($newData)) {
				//处理附件关系
		        if ($fileArr) {
		            $fileModel = new \app\admin\model\File();
		            $resData = $fileModel->createDataById($fileArr, 'oa_examine_travel', $travel_id);
					if ($resData == false) {
			        	$successRes = false;
			        	return false;
			        }
		        }
			} else {
				$successRes = false;
				return false;
			}				
		}
		if (!$successRes) {
			$this->error = '审批事项创建失败';
			return false;
		}
		//删除旧数据
		if ($oldTravelIds) db('oa_examine_travel')->where(['travel_id' => ['in',$oldTravelIds]])->delete();
		if ($oldTravelFileIds) db('oa_examine_travel_file')->where(['r_id' => ['in',$oldTravelFileIds]])->delete();		
		return true;		    	
    }    
}