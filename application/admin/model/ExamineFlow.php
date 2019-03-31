<?php
// +----------------------------------------------------------------------
// | Description: 审批流程
// +----------------------------------------------------------------------
// | Author: Michael_xu | gengxiaoxu@5kcrm.com
// +----------------------------------------------------------------------
namespace app\admin\model;

use think\Db;
use app\admin\model\Common;
use think\Request;
use think\Validate;

class ExamineFlow extends Common
{
	/**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如CRM模块用crm作为数据表前缀
     */
	protected $name = 'admin_examine_flow';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
	protected $autoWriteTimestamp = true;
	protected $typesArr = ['crm_contract','crm_receivables','oa_examine'];

	/**
     * [getDataList 审批流程list]
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
        $examineStepModel = new \app\admin\model\ExamineStep();
        $request = $this->fmtRequest( $request );
        $map = $request['map'] ? : [];
        if (isset($map['search'])) {
			//普通筛选
			$map['name'] = ['like', '%'.$map['search'].'%'];
			unset($map['search']);
		}
        $map['is_deleted'] = 0;
		$list_view = $this
					 ->where($map)
				     ->alias('examine_flow')
				     ->join('__ADMIN_USER__ user', 'user.id = examine_flow.update_user_id', 'LEFT');

		$list = $list_view
        		->page($request['page'], $request['limit'])
        		->field('examine_flow.*,user.realname,user.thumb_img')
        		->select();	
        foreach ($list as $k=>$v) {
            $list[$k]['user_ids_info'] = $userModel->getListByStr($v['user_ids']);
            $list[$k]['structure_ids_info'] = $structureModel->getListByStr($v['structure_ids']);
            $stepList = [];
            $stepList = $examineStepModel->getDataList($v['flow_id']);
            $list[$k]['stepList'] = $stepList ? : [];
        }
        $dataCount = $list_view->where($map)->count('flow_id');
        $data = [];
        $data['list'] = $list;
        $data['dataCount'] = $dataCount ? : 0;

        return $data;
    }

	/**
	 * 创建审批流程信息
	 * @author Michael_xu
	 * @param  
	 * @return                            
	 */	
	public function createData($param)
	{
		//验证
		if (!$param['name']) {
            $this->error = '请填写审批流名称1';
            return false;
        }

		if ($this->data($param)->allowField(true)->save()) {
			$data = [];
			$data['flow_id'] = $this->flow_id;
			return $data;
		} else {
			$this->error = '添加失败';
			return false;
		}			
	}

	/**
	 * 编辑审批流程信息
	 * @author Michael_xu
	 * @param  
	 * @return                            
	 */	
	public function updateDataById($param, $flow_id = '')
	{
		unset($param['id']);
		$dataInfo = $this->get($flow_id);
		if (!$dataInfo) {
			$this->error = '数据不存在或已删除';
			return false;
		}
		//过滤不能修改的字段
		$unUpdateField = ['create_user_id','is_deleted','delete_time'];
		foreach ($unUpdateField as $v) {
			unset($param[$v]);
		}
		
		//验证
		if (!$param['name']) {
            $this->error = '请填写审批流名称';
            return false;
        }
		$param['flow_id'] = $flow_id;

		if ($this->allowField(true)->save($param, ['flow_id' => $flow_id])) {		
			$data = [];
			$data['flow_id'] = $flow_id;
			return $data;
		} else {
			$this->error = '编辑失败,请重试';
			return false;
		}					
	}

    /**
     * 审批流程详情
     * @author Michael_xu
     * @param  
     * @return                            
     */ 
    public function getDataById($flow_id = '')
    {
        $userModel = new \app\admin\model\User();
        $dataInfo = $this->get($flow_id);
        if (!$dataInfo) {
            $this->error = '数据不存在或已删除';
            return false;
        }
        //审批步骤
        $stepList = db('admin_examine_step')->where(['flow_id' => $flow_id])->select();
        foreach ($stepList as $k=>$v) {
            $examine_user_id_arr = [];
            switch ($v['status']) {
                case 2 : 
                case 3 : $examine_user_id_arr = stringToArray($v['user_id']); break;
                default : $examine_user_id_arr = []; break;
            }
            $stepList[$k]['user_id_info'] = $userModel->getUserByIdArr($examine_user_id_arr);      
        }
        $dataInfo['stepList'] = $stepList ? : [];
        return $dataInfo;          
    }

	/**
     * 审批流程（根据对象获取需要执行的审批流程）
     * @param  types 审批对象
     * @param  types_id 审批对象ID(如OA审批类型ID)
     */ 
    public function getFlowByTypes($user_id, $types, $types_id = 0)
    {
        $userModel = new \app\admin\model\User();
    	if (!in_array($types, $this->typesArr)) {
    		$this->error = '参数错误';
    		return false;
    	}
    	$map['types'] = $types;
        $map['status'] = 1;
        $map['is_deleted'] = 0;
        if ($types !== 'oa_examine') {
            $types_id = 0;
        }
    	$map['types_id'] = $types_id;
    	//判断用户使用哪个流程（优先级：所属部门 > 全部）
        $userData = $userModel->getUserById($user_id);
        $userData['map'] = $map;

        $flowInfo = db('admin_examine_flow')
                    ->where(function ($query) use ($userData) {
                        $userData = $userData;
                        $query->where(['config' => 1])
                                ->where($userData['map'])
                                ->where(function ($query) use ($userData) {
                                    $query->where('structure_ids','like','%,'.$userData['structure_id'].',%')
                                            ->whereOr('user_ids','like','%,'.$userData['id'].',%');
                                });
                    })->whereOr(function ($query) use ($userData) {
                        $query->where(['config' => 1])
                                ->where($userData['map'])
                                ->where('structure_ids','eq','')
                                ->where('user_ids','eq','');                    
                    })->whereOr(function ($query) use ($userData) {
                        $query->where(['config' => 0])
                                ->where($userData['map']);
                    })->order('update_time desc')->find();
    	return $flowInfo ? : [];
    }

    /**
     * 审批流程权限(创建操作使用)
     * @param  types 审批对象
     * @param  user_id 审批对象申请人ID
     * @param category_id 审批类型ID，或其他类型ID
     */
    public function checkExamine($user_id, $types, $category_id = 0)
    {
        $examineStepModel = new \app\admin\model\ExamineStep();
        //符合条件的审批流
        $resFlow = $this->getFlowByTypes($user_id, $types, $category_id);
        if (!$resFlow) {
            return false;
        }
        if ($resFlow['config'] == 1) {
            //审批流是否为空
            $stepList = $examineStepModel->getStepList($resFlow['flow_id'], $user_id, $types);
            if (!$stepList) {
               return false; 
            }
        }
        return $resFlow;
    }  

    /**
     * 审批流程下所有审批人ID
     * @param
     * @return
     */  
    public function getUserByFlow($flow_id, $user_id, $check_user_id = '')
    {
        $flowInfo = db('admin_examine_flow')->where(['flow_id' => $flow_id])->find();
        $userIds = [];
        if ($flowInfo['config'] == 1) {
            $stepList = db('admin_examine_step')->where(['flow_id' => $flow_id])->select();
            foreach ($stepList as $k=>$v) {
                if ($v['status'] == 1) {
                    $userInfo = db('admin_user')->where(['id' => $user_id])->find();
                    if ($userInfo['parent_id']) {
                        $userIds[] = $userInfo['parent_id'];
                    } else {
                        $userIds[] = 1;
                    }
                }
                if (stringToArray($v['user_id'])) $userIds = $userIds ? array_merge($userIds, stringToArray($v['user_id'])) : stringToArray($v['user_id']);
            }
        } else {
            $userIds = [];
            $check_user_id = stringToArray($check_user_id);
            //查询已审批人ID（未失效的）
            $is_check_user_id = db('admin_examine_record')->where(['flow_id' => $flow_id,'is_end' => 0])->column('check_user_id');
            if ($check_user_id && $is_check_user_id) {
                $userIds = array_merge($check_user_id, $is_check_user_id);
            } elseif ($check_user_id) {
                $userIds = $check_user_id;
            } else {
                $userIds = $is_check_user_id;
            }
        }
        return $userIds ? : [];
    }     
}