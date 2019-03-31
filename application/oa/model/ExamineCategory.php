<?php
// +----------------------------------------------------------------------
// | Description: 审批类型
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com
// +----------------------------------------------------------------------
namespace app\oa\model;

use think\Db;
use app\admin\model\Common;
use think\Request;
use think\Validate;

class ExamineCategory extends Common
{
	/**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如CRM模块用crm作为数据表前缀
     */
	protected $name = 'oa_examine_category';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
	protected $autoWriteTimestamp = true;

	/**
     * [getDataList 审批类型list]
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
    	$examineFlowModel = new \app\admin\model\ExamineFlow();
    	$examineStepModel = new \app\admin\model\ExamineStep();
        $request = $this->fmtRequest( $request );
        $map = $request['map'] ? : [];
        if (isset($map['search'])) {
			//普通筛选
			$map['title'] = ['like', '%'.$map['search'].'%'];
			unset($map['search']);
		}	
		$map['is_deleted'] = 0;	

		$list = $this
				->where($map)
        		->page($request['page'], $request['limit'])
        		->select();	
        foreach ($list as $k=>$v) {
        	$flowInfo = [];
        	$flowInfo = $examineFlowModel->getDataById($v['flow_id']);
        	$list[$k]['config'] = $flowInfo['config'];
			$stepList = [];
            $stepList = $examineStepModel->getDataList($v['flow_id']);
            $list[$k]['stepList'] = $stepList ? : []; 
            $list[$k]['user_ids_info'] = $userModel->getListByStr($v['user_ids']);
            $list[$k]['structure_ids_info'] = $structureModel->getListByStr($v['structure_ids']);	
        }
        $dataCount = $this->where($map)->count('category_id');  
        $data = [];
        $data['list'] = $list;
        $data['dataCount'] = $dataCount ? : 0;
        return $data;
    }

	/**
	 * 创建审批类型信息
	 * @author Michael_xu
	 * @param  
	 * @return                            
	 */	
	public function createData($param)
	{
		$fieldModel = new \app\admin\model\Field();
		//验证
		$validate = validate($this->name);
		if (!$validate->check($param)) {
			$this->error = $validate->getError();
			return false;
		}
		$param['is_sys'] = 0;
		$param['user_ids'] = $param['user_ids'] ? arrayToString($param['user_ids']) : ''; //处理user_id
		$param['structure_ids'] = $param['structure_ids'] ? arrayToString($param['structure_ids']) : ''; //处理structure_id

		$examineStep = $param['step']; //审批步骤
		$config = $param['config'] ? 1 : 0; //审批流程类型 1固定审批0授权审批
		if ($this->data($param)->allowField(true)->save()) {
			//添加基础自定义字段
			$fieldData = [];
			$fieldData[0]['types'] = 'oa_examine';
			$fieldData[0]['types_id'] = $this->category_id;
			$fieldData[0]['field'] = 'content';
			$fieldData[0]['name'] = '审批事由';
			$fieldData[0]['form_type'] = 'textarea';
			$fieldData[0]['is_null'] = '1';
			$fieldData[0]['order_id'] = '1';
			$fieldData[0]['operating'] = '1';
			$fieldData[0]['create_time'] = time();
			$fieldData[0]['update_time'] = time();

			$fieldData[1]['types'] = 'oa_examine';
			$fieldData[1]['types_id'] = $this->category_id;
			$fieldData[1]['field'] = 'remark';
			$fieldData[1]['name'] = '备注';
			$fieldData[1]['form_type'] = 'textarea';
			$fieldData[1]['is_null'] = '0';
			$fieldData[1]['order_id'] = '1';
			$fieldData[1]['operating'] = '1';
			$fieldData[1]['create_time'] = time();
			$fieldData[1]['update_time'] = time();
			if (!$fieldModel->createData('oa_examine', $fieldData)) {
				db('oa_examine_category')->where(['category_id' => $this->category_id])->delete();
				$this->error = '程序出错，请重试';
		        return false;				
			}	

			$data = [];
			$data['category_id'] = $this->category_id;			
			//创建审批流
			if (is_array($examineStep) && $examineStep) {
				$examineFlowModel = new \app\admin\model\ExamineFlow();
		        $examineStepModel = new \app\admin\model\ExamineStep();
		        $examineFlow = [];
		        $examineFlow['name'] = $param['title'].'流程';
		        $examineFlow['config'] = $config;
		        $examineFlow['types'] = 'oa_examine';
		        $examineFlow['types_id'] = $this->category_id;
				$examineFlow['user_ids'] = arrayToString($param['user_ids']);
        		$examineFlow['structure_ids'] = arrayToString($param['structure_ids']);		        
		        $examineFlow['update_user_id'] = $param['create_user_id'];	
		        $examineFlow['status'] = 1;	
		        $res = $examineFlowModel->createData($examineFlow);
		        if ($res) {
		            if ((int)$config == 1) {
		            	$resUpdate = db('oa_examine_category')->where(['category_id' => $this->category_id])->update(['flow_id' => $res['flow_id']]);
		                //固定审批流
		                $resStep = $examineStepModel->createStepData($examineStep, $res['flow_id']);
		                if ($resStep) {
		                	return $data;
		                } else {
		                    db('admin_examine_flow')->where(['flow_id' => $res['flow_id']])->delete();
		                    $this->error = $examineStepModel->getError();
		                    return false;
		                }                
		            } else {
		            	return $data;
		            }
		        } else {
		        	$this->error = $examineFlowModel->getError();
		        	return false;
		        }				
			} else {
				$this->error = '请添加审批步骤';
				return false;
			}
		} else {
			$this->error = '添加失败';
			return false;
		}			
	}

	/**
	 * 编辑审批类型信息
	 * @author Michael_xu
	 * @param  
	 * @return                            
	 */	
	public function updateDataById($param, $category_id = '')
	{
		$category_id = intval($category_id);
		unset($param['id']);
		//过滤不能修改的字段
		$unUpdateField = ['create_user_id','is_deleted','delete_user_id','delete_time','is_sys'];
		foreach ($unUpdateField as $v) {
			unset($param[$v]);
		}
		
		//验证
		$validate = validate($this->name);
		if (!$validate->check($param)) {
			$this->error = $validate->getError();
			return false;
		}
		$param['user_ids'] = is_array($param['user_ids']) ? arrayToString($param['user_ids']) : $param['user_ids']; //处理user_id
		$param['structure_ids'] = is_array($param['structure_ids']) ? arrayToString($param['structure_ids']) : $param['structure_ids']; //处理structure_id

		$examineStep = $param['step']; //审批步骤
		$config = $param['config'] ? 1 : 0; //审批流程类型 1固定审批0授权审批		

		if ($this->allowField(true)->save($param, ['category_id' => $category_id])) {
			$data = [];
			$data['category_id'] = $category_id;
			return $data;
		} else {
			$this->error = '编辑失败';
			return false;
		}					
	}

	/**
     * 审批类型数据
     * @param  $id 审批ID
     * @return 
     */	
   	public function getDataById($id = '')
   	{   		
   		$map['category_id'] = $id;
		$dataInfo = db('oa_examine_category')->where($map)->find();
		return $dataInfo ? : [];
   	}

	/**
	 * 逻辑删除,将数据标记为删除状态
	 * @author Michael_xu
	 */	
	public function signDelById($id, $user_id)
	{
		if (!$id) {
			$this->error = '删除失败';
			return false;
		}
		$info = $this->get($id);
		if ($info['is_sys'] == 1) {
			$this->error = '系统类型，不能删除';
			return false;			
		}
		//是否被使用
		$resCategory = db('oa_examine')->where(['category_id' => $info['category_id']])->find();
		if ($resCategory) {
			$this->error = '已有审批，不能删除';
			return false;			
		}
		$this->startTrans();
		try {
			$data['is_deleted'] = 1;
			$data['delete_time'] = time();
			$data['delete_user_id'] = $user_id;
			$this->allowField(true)->save($data, ['category_id' => $id]);
			$this->commit();
			return true;
		} catch(\Exception $e) {
			$this->error = '删除失败';
			$this->rollback();
			return false;
		}			
	}
}