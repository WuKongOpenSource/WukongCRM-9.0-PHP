<?php
// +----------------------------------------------------------------------
// | Description: 日程
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com
// +----------------------------------------------------------------------
namespace app\oa\model;

use think\Db;
use app\admin\model\Common;
use think\Request;
use think\Validate;
use think\helper\Time;

class Event extends Common
{
	/**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如CRM模块用crm作为数据表前缀
     */
	protected $name = 'oa_event';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
	protected $autoWriteTimestamp = true;

	//类型转换
	protected $dateFormat = 'Y-m-d H:i:s';
	protected $type = [
        'start_time'  =>  'timestamp',
        'end_time'  =>  'timestamp',
    ];

	/**
     * [getDataList 日程list]
     * @author Michael_xu
     * @param     [by]                       $by [查询时间段类型]
     * @return    [array]                    [description]
     */		
	public function getDataList($param)
    {
		$userModel = new \app\admin\model\User();
		$BusinessModel = new \app\crm\model\Business();
		$ContactsModel = new \app\crm\model\Contacts();
		$ContractModel = new \app\crm\model\Contract();
		$CustomerModel = new \app\crm\model\Customer();

		$user_id = $param['user_id'];
		if ($param['start_time'] && $param['end_time']) {
			$start_time = $param['start_time'];
			$end_time = $param['end_time'];
		} else {
			$start_time = mktime(0,0,0,date('m'),1,date('Y'));
			$end_time = mktime(23,59,59,date('m'),date('t'),date('Y'));
		}
		$where = '( ( start_time BETWEEN '.$start_time.' AND '.$end_time.' ) AND ( create_user_id = '.$user_id.' or owner_user_ids like "%,'.$user_id.',%" ) ) OR ( ( end_time BETWEEN '.$start_time.' AND '.$end_time.' ) AND  ( create_user_id = '.$user_id.' or owner_user_ids like "%,'.$user_id.',%" ) ) OR ( start_time < '.$start_time.' AND end_time > '.$end_time.' AND ( create_user_id = '.$user_id.' or owner_user_ids like "%,'.$user_id.',%" ) )';
		$event_date = Db::name('OaEvent')->where($where)->select();  
	
		foreach ($event_date as $k=>$v) {
			$event_date[$k]['create_user_info'] = $userModel->getDataById($v['create_user_id']);
			$event_date[$k]['ownerList'] = $userModel->getDataByStr($v['owner_user_ids']) ? : [];
			$relation = Db::name('OaEventRelation')->where('event_id ='.$v['event_id'])->find();
			$event_date[$k]['businessList'] = $relation['business_ids'] ? $BusinessModel->getDataByStr($relation['business_ids']) : []; //商机
			$event_date[$k]['contactsList'] = $relation['contacts_ids'] ? $ContactsModel->getDataByStr($relation['contacts_ids']) : []; //联系人
			$event_date[$k]['contractList'] = $relation['contract_ids'] ? $ContractModel->getDataByStr($relation['contract_ids']) : []; //合同
			$event_date[$k]['customerList'] = $relation['customer_ids'] ? $CustomerModel->getDataByStr($relation['customer_ids']) : []; //客户
			$event_date[$k]['remindtype'] = (int)$v['remindtype'];
			$noticeList = Db::name('OaEventNotice')->where('event_id = '.$v['event_id'].'')->find();
			if (!$noticeList) {
				$event_date[$k]['is_repeat'] = 0;
			} else {
				$event_date[$k]['is_repeat'] = 1;
			}
			$event_date[$k]['stop_time'] = $noticeList ? $noticeList['stop_time'] : '';
			$event_date[$k]['noticetype'] = $noticeList ? $noticeList['noticetype'] : '';
			if ($noticeList['noticetype'] == '2') {
				$event_date[$k]['repeat'] =  $noticeList['repeated'] ? explode('|||',$noticeList['repeated']) : [];
			} else {
				$event_date[$k]['repeat'] =  '';
			}
			//权限
			$is_update = 0;
			$is_delete = 0;
			if ($user_id == $v['create_user_id']) {
				$is_update = 1;
				$is_delete = 1;
			}        
	        $permission['is_update'] = $is_update;
	        $permission['is_delete'] = $is_delete;
	        $event_date[$k]['permission']	= $permission;
		}
		$count = Db::name('OaEvent')->where($where)->count();
        $data = [];
		$data['dataCount'] = $count;
        $data['list'] = $event_date;
        return $data;
    }

	/**
	 * 创建日程信息
	 * @author Michael_xu
	 * @param  
	 * @return                            
	 */	
	public function createData($param)
	{	
		$today_time = Time::today();
		$param['start_time'] = $param['start_time'] ? : $today_time[0];
		$param['end_time'] = $param['end_time'] ?$param['end_time'] : $today_time[1];
		$param['create_time'] = time();
		if (count($param['owner_user_ids'])) {
			$param['owner_user_ids'] = ','.implode(',',$param['owner_user_ids']).','; //参与人
		} else {
			$param['owner_user_ids'] = '';
		}
		
		$rdata['customer_ids'] = count($param['customer_ids']) ? arrayToString($param['customer_ids']) : ''; 
		$rdata['contacts_ids'] = count($param['contacts_ids']) ? arrayToString($param['contacts_ids']) : ''; 
		$rdata['business_ids'] = count($param['business_ids']) ? arrayToString($param['business_ids']) : ''; 
		$rdata['contract_ids'] = count($param['contract_ids']) ? arrayToString($param['contract_ids']) : '';  
		
		//重复设置
		$repeatData['noticetype'] = $param['noticetype']; //日程类型
		$repeatData['start_time'] = $param['start_time']; // 开始时间
		$repeatData['stop_time'] = $param['stop_time']; // 重复设置截至时间
		$repeat = $param['repeat'] ? implode('|||',$param['repeat']) : '';
		$arr = ['customer_ids','contacts_ids','business_ids','contract_ids','repeat','stop_time','noticetype'];
		foreach ($arr as $value) {
			unset($param[$value]);
		}
		if ($this->allowField(true)->save($param)) {
			$event_id =$this->event_id;
			$rdata['event_id'] = $event_id;
			$rdata['status'] = 1;
			$rdata['create_time'] = time();
			Db::name('OaEventRelation')->insert($rdata);
			//重复设置
			if($param['is_repeat']){
				$repeatData['event_id'] = $event_id; 
				if( $repeatData['noticetype'] == '1' ){ //天
					$repeatData['repeated'] = date("H:i:s",$param['start_time']);
				} else if ( $repeatData['noticetype'] == '2' ) { //周
					$repeatData['repeated'] = $repeat;  //周几
				} else if ( $repeatData['noticetype'] == '3' ) { //月
					$repeatData['repeated'] = date("d H:i:s",$param['start_time']); 
				} else if ( $repeatData['noticetype'] == '4' ) { //年
					$repeatData['repeated'] = date("m-d H:i:s",$param['start_time']); 
				}
				Db::name('OaEventNotice')->insert($repeatData);
			}
			actionLog($event_id ,$param['owner_user_ids'],'','创建了日程'); //
			$data = [];
			$data['event_id'] = $event_id;
			return $data;
		} else {
			$this->error = '添加失败';
			return false;
		}			
	}

	/**
	 * 编辑日程信息
	 * @author Michael_xu
	 * @param  
	 * @return                            
	 */	
	public function updateDataById($param, $event_id = '')
	{
		$dataInfo = $this->getDataById($event_id, $param);
		if (!$dataInfo) {
			$this->error = '数据不存在或已删除';
			return false;
		}
		
		if(  $dataInfo['create_user_id'] != $param['user_id'] ) {
			$this->error = '没有编辑权限';
			return false;
		}
		
		$rdata['customer_ids'] = count($param['customer_ids']) ? arrayToString($param['customer_ids']) : ''; 
		$rdata['contacts_ids'] = count($param['contacts_ids']) ? arrayToString($param['contacts_ids']) : ''; 
		$rdata['business_ids'] = count($param['business_ids']) ? arrayToString($param['business_ids']) : ''; 
		$rdata['contract_ids'] = count($param['contract_ids']) ? arrayToString($param['contract_ids']) : '';  

		$rdata['event_id'] = $event_id;
		
		//重复设置
		$repeatData['noticetype'] = $param['noticetype']; //日程类型
		$repeatData['start_time'] = $param['start_time']; // 开始时间
		$repeatData['stop_time'] = $param['stop_time']; // 重复设置截至时间
		$repeat = $param['repeat'];
		$arr = ['customer_ids','contacts_ids','business_ids','contract_ids','repeat','stop_time','noticetype'];
		foreach ($arr as $value) { 	//过滤不能修改的字段
			unset($param[$value]);
		}
	
		$today_time = Time::today();
		$param['start_time'] = $param['start_time'] ? : $today_time[0];
		$param['end_time'] = $param['end_time'] ?$param['end_time']: $today_time[1];
		$param['create_time'] = time();
		if( count($param['owner_user_ids']) ){
			$param['owner_user_ids'] = ','.implode(',',$param['owner_user_ids']).','; //参与人
		} else {
			$param['owner_user_ids'] = '';
		}
		if ($this->allowField(true)->save($param, ['event_id' => $event_id])) {
			actionLog($event_id,'',$param['owner_user_ids'],'修改了日程');
			if($param['is_repeat']){
				$repeatData['event_id'] = $event_id; //
				if( $repeatData['noticetype'] == '1' ){ //天
					$repeatData['repeated'] = date("H:i:s",$param['start_time']); // $param['repeat']; Y-m-d H:i:s
				} else if ( $repeatData['noticetype'] == '2' ) { //周
					$repeatData['repeated'] = $repeat;  //周几
				} else if ( $repeatData['noticetype'] == '3' ) { //月
					$repeatData['repeated'] = date("d H:i:s",$param['start_time']); 
				} else if ( $repeatData['noticetype'] == '4' ) { //年
					$repeatData['repeated'] = date("m-d H:i:s",$param['start_time']); 
				}
				Db::name('OaEventNotice')->where('event_id ='.$event_id)->update($repeatData);
			} else {
				Db::name('OaEventNotice')->where('event_id ='.$event_id)->delete();
			}
			$data = [];
			$data['event_id'] = $event_id;
			Db::name('OaEventRelation')->where('event_id ='.$event_id)->update($rdata);
			return $data;
		} else {
			$this->error = '编辑失败';
			return false;
		}					
	}

	/**
     * 日程数据
     * @param  $id 日程ID
     * @return 
     */	
   	public function getDataById($id = '', $param)
   	{   		
   		$map['event_id'] = $id;
   		$map['create_user_id'] = $param['user_id'];
		$dataInfo = $this->where($map)->find();
		if (!$dataInfo) {
			$this->error = '暂无此数据';
			return false;
		}
		$userModel = new \app\admin\model\User(); 
		$BusinessModel = new \app\crm\model\Business();
		$ContactsModel = new \app\crm\model\Contacts();
		$ContractModel = new \app\crm\model\Contract();
		$CustomerModel = new \app\crm\model\Customer();

	    $dataInfo['ownerList'] = $userModel->getDataByStr($dataInfo['owner_user_ids']);
		$relation = Db::name('OaEventRelation')->where('event_id ='.$id)->find();
		$dataInfo['businessList'] = $relation['business_ids']?$BusinessModel->getDataByStr($relation['business_ids']):''; //商机
		$dataInfo['contactsList'] = $relation['contacts_ids']?$ContactsModel->getDataByStr($relation['contacts_ids']):''; //联系人
		$dataInfo['contractList'] = $relation['contract_ids']?$ContractModel->getDataByStr($relation['contract_ids']):''; //合同
		$dataInfo['customerList'] = $relation['customer_ids']?$CustomerModel->getDataByStr($relation['customer_ids']):''; //客户
		$dataInfo['event_id'] = $id;
		return $dataInfo;
   	}
	
	//根据ID 删除日程
	public function delDataById($param)
	{
		$dataInfo = $this->get($param['event_id']);
		if(!$dataInfo){
			$this->error = '数据不存在或已删除';
			return false;
		}
		
		if( $dataInfo['create_user_id'] != $param['user_id'] ){
			$this->error = '没有编辑权限';
			return false;
		}
		
		$map['event_id'] = $param['event_id'];
		$map['create_user_id'] = $param['user_id'];
		$flag = $this->where($map)->delete();
		if ($flag) {
			actionLog($param['event_id'],$dataInfo['owner_user_ids'],'','删除了日程');
			return true;
		} else {
			$this->error = '删除失败';
			return false;
		}
	}
}