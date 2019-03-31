<?php
// +----------------------------------------------------------------------
// | Description: 项目管理
// +----------------------------------------------------------------------
// | Author:  
// +----------------------------------------------------------------------

namespace app\work\model;

use think\Db;
use think\Model;
use app\work\model\WorkClass as ClassModel;
use com\verify\HonrayVerify;
use think\Cache;

class Work extends Model
{

    /**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如微信模块用weixin作为数据表前缀
     */
	protected $name = 'work';
    protected $createTime = 'create_time';
    protected $updateTime = false;
	protected $autoWriteTimestamp = true;
	protected $insert = [
		'status' => 1,
	];

	//[getDataList 列表] 
	public function getDataList()
	{
		$list = $this->field('work_id,name,status,create_time')->where('status =1')->select();
		return $list ;	
	}

	//退出项目 项目id,会员ID
	public function leaveById($work_id,$user_id)
	{
		$workDet = Db::name('Work')->where('work_id = '.$work_id)->find();
		if ( $user_id == $workDet['create_user_id'] ) {
			$this->error = '项目创建人不可以退出';
			return false;
		}

		$list = Db::name('Task')->where('work_id ='.$work_id)->select();

		foreach ( $list as $key => $value ) {
			$str = ','.$user_id.',';
			if ( strstr($str,$value['own_user_id']) ) {
				$newField = str_replace($str,',',$value['own_user_id']); 
				Db::name('Task')->where('task_id = '.$value['task_id'])->setField('own_user_id',$newField);
			}
			if( $value['main_user_id'] == $param['create_user_id'] ) {
				Db::name('Task')->where('task_id = '.$value['task_id'])->setField('main_user_id','');
			}
		}
		return true;
	}

	//添加参与人
	public function addOwner($param)
	{
		$newstr = implode(',',$param['owner_user_id']);
		$temp = ','.$newstr.',';
		
		$flag = $this->where('work_id ='.$param['work_id'])->setField('owner_user_id',$temp);
		if ($flag) {
			return true;
		} else {
			return false;
		}
	}

	//删除参与人
	public function delOwner($param)
	{
		$det = $this->get($param['work_id']);
		if ( $det['owner_user_id'] ){

			$temp = str_replace(','.$param['owner_user_id'].',',',',$det['owner_user_id']);
			$flag = $this->where('work_id ='.$param['work_id'])->setField('owner_user_id',$temp);

			if ($flag) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	
	//参与人列表
	public function ownerList($param)
	{
		$det = Db::name('Work')->where('work_id ='.$param['work_id'])->find();
		if ($det['owner_user_id'] ) {

			$idstr = $det['owner_user_id'];
			$idstr=substr($idstr,1,strlen($idstr)-2);
			if (!$idstr) {
				$this->error= '没有参与人';
				return false;
			}
			$list = Db::name('AdminUser')->field('id,username,realname,thumb_img')->where('id in ('.$idstr.')')->select();
			foreach($list as $k=>$v){
				//$det = Db::name('AdminUserAccess')->where('user_id = '.$v['id'])->find();
				//$list[$k]['group_id'] = $det['group_id']?(int)$det['group_id']:''; 
				$list[$k]['thumb_img'] = getFullPath($v['thumb_img']);
			}
			return $list;
		} else {
			$this->error= '无参与人';
			return false;
		}
	}

	//获取项目详情
	public function getDataById($id = '')
	{
		$data = $this->get($id);
		if (!$data) {
			$this->error = '暂无此数据';
			return false;
		}
		return $data;
	}

	//创建项目
	public function createData($param)
	{
		$this->startTrans();
		try {
			$rdata['customer_ids'] = count($param['customer_ids'])?','.implode(',',$param['customer_ids']).',':''; 
			$rdata['contacts_ids'] = count($param['contacts_ids'])?','.implode(',',$param['contacts_ids']).',':''; 
			$rdata['business_ids'] = count($param['business_ids'])?','.implode(',',$param['business_ids']).',':''; 
			$rdata['contract_ids'] = count($param['contract_ids'])?','.implode(',',$param['contract_ids']).',':'';  

			$arr = ['customer_ids','contacts_ids','business_ids','contract_ids'];
			foreach($arr as $value){
				unset($param[$value]);
			}
			
			$data['create_time'] = time();
			$data['create_user_id'] = $param['create_user_id'];
			$data['name'] = $param['name'];
			$data['owner_user_id'] = $param['owner_user_id']?','.implode(',', $param['owner_user_id']).',':'';
			$data['description'] = $param['description']?:'';
			$data['color'] = $param['color']?:'';
			$data['status'] = 1; 
			$flag = $this->insertGetId($data); 
			if ($flag) {
				$this->commit();
				$rdata['work_id'] = $flag;
				Db::name('WorkRelation')->insert($rdata);
				$create_time = time();
				$create_user_id = $param['create_user_id'];
				$prefix= config("database.prefix");
				$sql = 'INSERT INTO '.$prefix.'work_task_class (name,create_time,create_user_id,status,work_id,order_id) VALUES
					("要做",'.$create_time.','.$create_user_id.', 1,'.$flag.',1),
					("在做",'.$create_time.','.$create_user_id.', 1,'.$flag.',2),
					("待定",'.$create_time.','.$create_user_id.', 1,'.$flag.',3);';
				Db::query($sql);
				return $flag;
			} else {
				$this->rollback();
				$this->error = '添加失败';
				return false;
			}
			
		} catch(\Exception $e) {
			$this->rollback();
			$this->error = '添加失败';
			return false;
		}
	}

	//编辑保存
	public function updateDataById($param)
	{
		$rdata['customer_ids'] = count($param['customer_ids'])?','.implode(',',$param['customer_ids']).',':''; 
		$rdata['contacts_ids'] = count($param['contacts_ids'])?','.implode(',',$param['contacts_ids']).',':''; 
		$rdata['business_ids'] = count($param['business_ids'])?','.implode(',',$param['business_ids']).',':''; 
		$rdata['contract_ids'] = count($param['contract_ids'])?','.implode(',',$param['contract_ids']).',':''; 
		$rdata['work_id'] = $param['work_id'];
		$arr = ['customer_ids','contacts_ids','business_ids','contract_ids'];
		foreach($arr as $value){
			unset($param[$value]);
		}
	
		$map['work_id'] = $param['work_id'];
		$flag = $this->where($map)->update($param);
		if ($flag) {
			$logmodel = model('TaskLog');
			$datalog['type']=2; //重命名项目
			$datalog['name'] = $param['name'];//项目名
			$datalog['create_user_id'] = $param['create_user_id']; 
			$datalog['work_id'] = $flag;
			$ret = $logmodel->workLogAdd($datalog);
			Db::name('WorkRelation')->where('work_id = '.$param['work_id'])->update($rdata);
			return true;
		} else {
			$this->error = '重命名失败';
			return false;
		}
	}

	//删除项目
	public function delWorkById($param)
	{
		$map['work_id'] = $param['work_id'];
		Db::name('Task')->where($map)->delete();
		$flag = $this->where($map)->delete();
		
		if ($flag) {
			$logmodel = model('TaskLog');
			$datalog['type']=3; //删除项目
			//$datalog['name'] = $param['name'];//项目名
			$datalog['create_user_id'] = $param['create_user_id']; 
			$datalog['work_id'] = $map['work_id'];
			$datalog['content'] = '删除了项目';
			Db::name('')->insert($datalog);
			$ret = $logmodel->workLogAdd($datalog); 
			return true;
		} else {
			$this->error = '数据不存在或已被删除';
			return false;
		}
	}

	//归档项目
	public function archiveData($param)
	{
		$map['work_id'] = $param['work_id'];
		$flag = $this->where($map)->setField('status',0);
		$this->where($map)->setField('archive_time',time());
		if ($flag) {
			Db::name('task')->where($map)->setField('status',3);
			Db::name('task')->where($map)->setField('archive_time',time());
			return true;
		} else {
			$this->error = '归档失败';
			return false;
		}
	}

	//归档项目列表
	public function archiveList()
	{
		$map['status'] = 0;
		$map['ishidden'] = 0;
		$list = $this->where($map)->select();
		foreach ($list as $key => $value) {
			$list[$key]['tasklist'] = Db::name('task')->field('name')->where('ishidden =0 and work_id = '.$value['work_id'])->select();
		}
		
		return $list;
		
	}

	//归档恢复
	public function arRecover($work_id='')
	{
		if ($work_id) {
			$map['work_id'] =$work_id;
			$map['status'] = 0;
			$this->where($map)->setField('status',1);
			$map['status'] = 3;
			Db::name('Task')->where($map)->setField('status',1);
			return true;
		} else {
			$this->error = '参数错误';
			return false;
		}
	}

	//[checkWork 项目权限判断] 
	public function checkWork($work_id, $user_id)
	{
		$info = $this->get($work_id);
		if (!$info) {
			$this->error = '该项目不存在或已删除';
			return false;
		}
		//私有项目（成员可见）
		$resData = Db::name('Work')->where(' work_id = '.$work_id.' and ( (is_open=0 and owner_user_id like ",'.$user_id.'," ) or (is_open = 1 ) or ( create_user_id = '.$user_id.')  ) ')->find();
		if (!$resData) {
			$this->error = '没有权限';
			return false;
		}
		return true;
	}
}