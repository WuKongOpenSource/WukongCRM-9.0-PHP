<?php
// +----------------------------------------------------------------------
// | Description: 业绩目标
// +----------------------------------------------------------------------
// | Author:  yykun
// +----------------------------------------------------------------------
namespace app\crm\model;

use think\Db;
use app\admin\model\Common;
use think\Request;
use think\Validate;
use app\crm\model\Contract as ContractModel;
use app\crm\model\Receivables as ReceivablesModel;

class Achievement extends Common
{
	/**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如CRM模块用crm作为数据表前缀
     */
	protected $name = 'crm_achievement';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
	protected $autoWriteTimestamp = true;

	//[getDataList 考核信息list] 部门
	public function getDataList($request)
    {  	
    	$userModel = new \app\admin\model\User();
 		if ($request['year']) {
 			$map['year'] = $request['year'];
 		}
        if ($request['status']) {
        	$map['status'] = $request['status'];
        }
        if ($request['structure_id']) { //部门
        	//查询是否有子部门
			$structlist = Db::name('AdminStructure')->field('id,name')->where('pid = '.$request['structure_id'])->select();
			$structret = Db::name('AdminStructure')->field('id,name')->where('id = '.$request['structure_id'])->find();
        	$map['type'] = 2;
			$result = array();
			$map['obj_id'] = $request['structure_id'];
			$nowret = Db::name('CrmAchievement')->where($map)->find();
			if (!$nowret) {
				Db::name('CrmAchievement')->insert($map);
				$nowret = Db::name('CrmAchievement')->where($map)->find();
			}
			$nowret['name'] = $structret['name'];
			$result[] = $nowret;
			foreach ($structlist as $k1=>$v1) {
				$map['obj_id'] = $v1['id'];
				$ret = Db::name('CrmAchievement')->where($map)->find();
				if (!$ret) {
					Db::name('CrmAchievement')->insert($map);
					$ret = Db::name('CrmAchievement')->where($map)->find();
				}
				$ret['name'] = $v1['name'];
				$result[] = $ret;
			}
			return $result;
        } else { //所有一级部门
			$structlist = Db::name('AdminStructure')->field('id,name')->where('pid = 0')->select();
			$result = array();
			foreach ($structlist as $k=>$v) {
				$map['type'] = 2;
				$map['obj_id'] = $v['id'];
				$ret = Db::name('CrmAchievement')->where($map)->find();
				if (!$ret) {
					Db::name('CrmAchievement')->insert($map);
					$ret = Db::name('CrmAchievement')->where($map)->find();
				}
				$ret['name'] = $v['name'];
				$result[] = $ret;
			}
			return $result;
		}
    }
	
	//员工目标列表
	public function getDataListForUser($request){
		$userModel = new \app\admin\model\User();
 		if ($request['year']) {
 			$map['year'] = $request['year'];
 		}
        if ($request['status']) {
        	$map['status'] = $request['status'];
        }
        if ($request['user_id']) { //员工
        	$map['obj_id'] = $request['user_id'];
			$userinfo = Db::name('AdminUser')->field('id,realname')->where('id = '.$request['user_id'].'')->find();
        	$map['type'] = 3;
			$ret = Db::name('CrmAchievement')->where($map)->find();
			if (!$ret) {
				Db::name('CrmAchievement')->insert($map);
				$ret = Db::name('CrmAchievement')->where($map)->find();
			}
			$ret['name'] = $userinfo['realname'];
			$data[] = $ret;
			return $data;
        } elseif ($request['structure_id']) {
			$map['type'] = 3;
			$result = array();
			$userlist = Db::name('AdminUser')->field('id,realname as name')->where('structure_id = '.$request['structure_id'].'')->select();
			if (!$userlist) {
				return array();
			}
			foreach ($userlist as $k=>$v) {
				$map['obj_id'] = $v['id'];
				$ret = Db::name('CrmAchievement')->where($map)->find();
				if(!$ret){
					Db::name('CrmAchievement')->insert($map);
					$ret = Db::name('CrmAchievement')->where($map)->find();
				}
				$ret['name'] = $v['name'];
				$result[]=$ret;
			}
			return $result;
		} else {
			$map['type'] = 3;
			$result = array();
			$userlist = Db::name('AdminUser')->field('id,realname as name')->select();
			foreach ($userlist as $k=>$v) {
				$map['obj_id'] = $v['id'];
				$ret = Db::name('CrmAchievement')->where($map)->find();
				if (!$ret) {
					Db::name('CrmAchievement')->insert($map);
					$ret = Db::name('CrmAchievement')->where($map)->find();
				}
				$ret['name'] = $v['name'];
				$result[]=$ret;
			}
			return $result;
		}
	}
	
    /**
     * 获取对象完成情况列表
     * @return [type] [description]
     */
    public function getList($param)
    {
    	$monthList = getMonthStart($param['year']);
    	$userModel = new \app\admin\model\User();
    	
    	$where = [];
    	//业绩目标
		if ($param['user_id']) {
			$dataList = Db::name('CrmAchievement')->where(['type' => 3,'obj_id' => $param['user_id'],'year' => $param['year'],'status' => $param['status']])->find();
			$where['owner_user_id'] = $param['user_id']; 
		} else {
			if ($param['structure_id']) {
				$dataList = Db::name('CrmAchievement')->where(['type' => 2,'obj_id' => $param['structure_id'],'year' => $param['year'],'status' => $param['status']])->find();
				$str = $userModel->getSubUserByStr($param['structure_id'], 1) ? : ['-1'];
				$where['owner_user_id'] = array('in',$str); 
			}
		}
    	$achiementList = [
    		'1' => [
    			'data' => $dataList['january'],
    			'month' => '一月'
    		],
    		'2' => [
    			'data' => $dataList['february'],
    			'month' => '二月'
    		],
    		'3' => [
    			'data' => $dataList['march'],
    			'month' => '三月'
    		],
    		'4' => [
    			'data' => $dataList['april'],
    			'month' => '四月'
    		],
    		'5' => [
    			'data' => $dataList['may'],
    			'month' => '五月'
    		],
    		'6' => [
    			'data' => $dataList['june'],
    			'month' => '六月'
    		],
    		'7' => [
    			'data' => $dataList['july'],
    			'month' => '七月'
    		],
    		'8' => [
    			'data' => $dataList['august'],
    			'month' => '八月'
    		],
    		'9' => [
    			'data' => $dataList['september'],
    			'month' => '九月'
    		],
    		'10' => [
    			'data' => $dataList['october'],
    			'month' => '十月'
    		],
    		'11' => [
    			'data' => $dataList['november'],
    			'month' => '十一月'
    		],
    		'12' => [
    			'data' => $dataList['december'],
    			'month' => '十二月'
    		]
    	];
        $where['check_status'] = 2;
        if($param['status'] == 1){
            $data_str = 'order_date';
        }else{
            $data_str = 'return_time';
        }
        $sql = [];
        for ($i = 1; $i <= 12; $i++) {
            $fields['(SUM(CASE WHEN '.$data_str.' BETWEEN "' . date('Y-m-d',$monthList[$i]) . '" AND "' . date('Y-m-d',$monthList[$i+1]) . '" THEN money ELSE 0 END))'] = 'money_'.$i;
        }
        // 合同 OR 回款
        if ($param['status'] == 1) {
            $where['order_date'] = ['between', [date('Y-m-d', $monthList[1]), date('Y-m-d', $monthList[13])]];
            $sql = ContractModel::where($where)->field($fields)->fetchSql()->select();
        } else {
            $sql = ReceivablesModel::where($where)->field($fields)->fetchSql()->select();
        }
        $list = queryCache($sql);
        
		for ($i = 1; $i <= 12; $i++) {
	    	$ret[$i]['month'] = $achiementList[$i]['month'];
            $money_id = 'money_'.$i;
	    	$ret[$i]['receivables'] = $list[0][$money_id];
	    	$ret[$i]['achiement'] = (float)$achiementList[$i]['data'] ? :'0';  // 目标
	    	$rate = 0.00;
			if ($ret[$i]['achiement']) {
				$rate = round(($ret[$i]['receivables']/$ret[$i]['achiement']),4)*100;
			}
	    	$ret[$i]['rate'] = $rate;
    	}
		return $ret;
    }

	/**
	 * 创建对象考核信息
	 * @author yykun
	 * @param  
	 * @return                            
	 */	
	public function createData($param)
	{
		if ( isset($param['ids']) ) { //多个部门同时添加 
            $temp = $param['ids'];
            unset($param['ids']);
            $param['type'] = 2;
            foreach ($temp as $key => $value) {
                $data['type'] = 2;
                $data['obj_id'] = $value;
                $flag = $this->where($data)->find();
                if ($flag) { //已存在的更新 
                    $this->where('achievement_id ='.$flag['achievement_id'])->update($param);
                } else { //不存在的添加
                    $param['obj'] = $value;
                    $this->insert($param);
                }
            }
        }	
        if ( isset($param['user_ids']) ) {
            $param['type'] = 3;
            $temp_user_ids = $param['user_ids'];
            unset($param['user_ids']);
            foreach ($temp_user_ids as $k =>$v) {
                $data2['type'] = 3;
                $data2['obj_id'] = $v;
                $ret = $this->where($data2)->find();
                if ($ret) {
                    $this->where('achievement_id ='.$flag['achievement_id'])->update($param);
                } else {
                    $param['obj'] = $v;
                    $this->insert($param);
                }
            }
        }
        return true;		
	}

	/**
	 * 编辑信息
	 * @author yykun
	 * @param  
	 * @return                            
	 */	
	public function updateData($param)
	{
		$fileary = ['first','second','third','fourth'];
		if($param['datalist']){
			foreach($param['datalist'] as $k=>$v){
				foreach($fileary as $value){
					unset($v[$value]);
				}
				$this->where('achievement_id = '.$v['achievement_id'].'')->update($v);
			}
		}
		return true;					
	}

	/**
     * 详情
     * @param  $id 
     * @return 
     */	
   	public function getDataById($id = '')
   	{   		
   		$map['achievement_id'] = $id;
		$dataInfo = Db::name('CrmAchievement')->where($map)->find();
		if (!$dataInfo) {
			$this->error = '暂无此数据';
			return false;
		}
		$userModel = new \app\admin\model\User();
		if ($dataInfo['type']=='3') {
    		$det = $userModel->getUserById($dataInfo['obj_id']);
    		$dataInfo['name'] = $det['realname'];
    	}

    	if ($dataInfo['type']=='2') {
    		$det = Db::name('AdminStructure')->where('id ='.$v['obj_id'])->find();
    		$dataInfo['name'] = $det['name'];
    	}

    	if ($dataInfo['type']=='3') {
    		$dataInfo['name'] = '公司';
    	} 	
		return $dataInfo;
   	}
}