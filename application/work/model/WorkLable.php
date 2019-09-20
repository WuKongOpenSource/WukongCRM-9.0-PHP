<?php
// +----------------------------------------------------------------------
// | Description: 任务标签
// +----------------------------------------------------------------------
// | Author:  yykun
// +----------------------------------------------------------------------

namespace app\work\model;

use think\Db;
use app\admin\model\Common;
use com\verify\HonrayVerify;
use think\Cache;

class WorkLable extends Common
{
    /**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如微信模块用weixin作为数据表前缀
     */
	protected $name = 'work_task_lable';
    protected $createTime = 'create_time';
    protected $updateTime = false;
	protected $autoWriteTimestamp = true;
	protected $insert = [
		'status' => 1,
	];

	/**
     * [getDataList 标签列表]
     * @AuthorHTL
     * @param     [string]                   $map [查询条件]
     * @param     [number]                   $page     [当前页数]
     * @param     [number]                   $limit    [每页数量]
     * @return    [array]                    [description]
     */	    
	public function getDataList()
	{
		$map['status'] = 1;
		$dataCount = $this->field('lable_id,name,create_time')->where($map)->count();
		$list = $this->where($map)->select();
		$data = [];
		$data['list'] = $list ? : [];
		$data['dataCount'] = $dataCount ? : 0;
		return $data ? : [];
	}
	
	/**
     * 创建标签
     * @author yykun
     * @param
     * @return
     */	
	public function createData($param)
	{
		$this->startTrans();
		try {
			$data['create_time'] = time();
			$data['create_user_id'] = $param['create_user_id'];
			$data['name'] = $param['name'];
			$data['color'] = $param['color'];
			$data['status'] = 1; 
			$this->insert($data);
			$this->commit();
			return true;
		} catch(\Exception $e) {
			$this->rollback();
			$this->error = '添加失败';
			return false;
		}
	}

	/**
     * 编辑标签
     * @author yykun
     * @param
     * @return
     */
	public function updateDataById($param)
	{
		$map['lable_id'] = $param['lable_id'];
		unset($param['lable_id']);
		$flag = $this->where($map)->update($param);
		if ($flag) {
			return true;
		} else {
			$this->error = '操作失败';
			return false;
		}
	}

	/**
     * 删除标签
     * @author yykun
     * @param
     * @return
     */
	public function delDataById($param)
	{
		$map['lable_id'] = $param['lable_id'];	
		$this->startTrans();
		try {
			$ret = $this->where($map)->setField('status',0);
			if ($ret) {
				$this->commit();
				return true;
			} else {
				$this->rollback();
				$this->error = '删除失败';
				return false;
			}
		} catch (\Exception $e){
			$this->rollback();
			$this->error = '删除失败';
			return false;
		}		
	}
	
	/**
     * 任务标签
     * @author yykun
     * @param
     * @return
     */
	public function getDataByStr($idstr)
	{
		$idstr = stringToArray($idstr);
		$list = Db::name('WorkTaskLable')->field('lable_id,name,color')->where(['lable_id' => ['in',$idstr]])->select();
		return $list ? : [];
	}

	/**
     * 任务标签名称
     * @author yykun
     * @param
     * @return
     */
	public function getNameByIds($ids)
	{
		$list = Db::name('WorkTaskLable')->where(['lable_id' => ['in',$ids]])->column('name');
		return $list ? : [];
	}	
}
