<?php
// +----------------------------------------------------------------------
// | Description: 客户扩展设置
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com
// +----------------------------------------------------------------------
namespace app\crm\model;

use think\Db;
use app\admin\model\Common;
use think\Request;
use think\Validate;

class CustomerConfig extends Common
{
	/**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如CRM模块用crm作为数据表前缀
     */
	protected $name = 'crm_customer_config';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
	protected $autoWriteTimestamp = true;

	/**
     * [getDataList]
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
		$request = $this->fmtRequest( $request );
        $map = $request['map'] ? : [];
        $order = 'update_time desc'; //排序

        $list = $this
                ->where($map)
                ->page($request['page'], $request['limit'])
                ->order($order)
                ->select(); 
        foreach ($list as $k=>$v) {
            $list[$k]['user_ids_info'] = $userModel->getListByStr($v['user_ids']);
            $list[$k]['structure_ids_info'] = $structureModel->getListByStr($v['structure_ids']);	
        }
        $dataCount = $this->where($map)->count('id');
        $data = [];
        $data['list'] = $list;
        $data['dataCount'] = $dataCount ? : 0;
        return $data;
    }		

	/**
	 * 保存相关信息
	 * @author Michael_xu
	 * @param  
	 * @return                            
	 */	
	public function createData($param)
	{
		if ($param['value'] <= 0) {
			$this->error = '数量上限必须大于0';
			return false;
		}
		$data = [];
		$param['types'] = $param['types'] ? : 1; //1拥有客户上限2锁定客户上限
		$param['user_ids'] = $param['user_ids'] ? arrayToString($param['user_ids']) : ''; //处理user_id
		$param['structure_ids'] = $param['structure_ids'] ? arrayToString($param['structure_ids']) : ''; //处理structure_id
		if ($this->data($param)->allowField(true)->isUpdate(false)->save()) {		
			$data = [];
			$data['id'] = $this->id;
			return $data;
		} else {
			$this->error = '创建失败';
			return false;
		}	
	}

	/**
	 * 编辑相关信息
	 * @author Michael_xu
	 * @param  
	 * @return                            
	 */	
	public function updateDataById($param, $id = '')
	{
		if (!$id) {
			$this->error = '参数错误';
			return false;
		}
		if ($param['value'] <= 0) {
			$this->error = '数量上限必须大于0';
			return false;
		}		
		unset($param['id']);
		$param['user_ids'] = is_array($param['user_ids']) ? arrayToString($param['user_ids']) : $param['user_ids']; //处理user_id
		$param['structure_ids'] = is_array($param['structure_ids']) ? arrayToString($param['structure_ids']) : $param['structure_ids']; //处理structure_id	

		if ($this->allowField(true)->save($param, ['id' => $id])) {
			$data = [];
			$data['id'] = $id;
			return $data;
		} else {
			$this->error = '编辑失败';
			return false;
		}					
	}

	/**
     * 相关信息数据
     * @param 
     * @return 
     */	
   	public function getDataById($id = '')
   	{   		
   		$map['id'] = $id;
		$dataInfo = $this->where($map)->find();
		return $dataInfo ? : [];
   	}

	/**
     * 验证相关信息
     * @param  $types 1拥有客户上限2锁定客户上限
     * @param  $is_update 1更改成交状态为 未成交
     * @return 
     */	
   	public function checkData($user_id, $types, $is_update = '')
   	{   
   		$userModel = new \app\admin\model\User();
   		$customerModel = new \app\crm\model\Customer();
   		$userInfo = $userModel->getUserById($user_id);
   		$dataInfo = $this->where(['types' => $types,'user_ids' => ['like','%,'.$user_id.',%']])->order('update_time desc')->find();
		if (!$dataInfo) {
			$dataInfo = $this->where(['types' => $types,'structure_ids' => ['like','%,'.$userInfo['structure_id'].',%']])->find();
		}
		switch ($types) {
			case '1' : $types_title = '拥有的客户数量'; break;
			case '2' : $types_title = '锁定的客户数量'; break;
		}
		if ($dataInfo) {
			$is_deal = $dataInfo['is_deal'] ? : 0;
			if (!$dataInfo['value']) {
				$this->error = $types_title.'超出限制：'.$dataInfo['value'].'个';
				return false;
			}
			//拥有数、锁定数
			$count = $customerModel->getCountByHave($user_id,$is_deal,$types);
			$error = false;
			if ($count >= $dataInfo['value']) {
				$error = true;			
			}		
			if ($is_update == 1 && $types == 1 && $dataInfo['is_deal'] == 1) {
				//更改成交状态
				if ($count = $dataInfo['value']) {
					$error = false;			
				}						
			}			
			if ($error == true) {
				$this->error = $userInfo['realname'].','.$types_title.'超出限制：'.$dataInfo['value'].'个';
				return false;	
			}
		}
		return true;
   	}   	
} 		