<?php
// +----------------------------------------------------------------------
// | Description: 场景
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com  
// +----------------------------------------------------------------------

namespace app\admin\model;

use think\Db;
use app\admin\model\Common;

class Scene extends Common 
{
    /**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如CRM模块用crm作为数据表前缀
     */
	protected $name = 'admin_scene';
	protected $createTime = 'create_time';
	protected $updateTime = false;
	protected $autoWriteTimestamp = true;

	private $types_arr = ['crm_leads','crm_customer','crm_customer_pool','crm_contacts','crm_product','crm_business','crm_contract','crm_receivables']; //支持场景的分类

	protected $type = [
        'data'    =>  'array',
    ];

	/**
	 * 创建场景
	 * @param  
	 * @return                            
	 */	
	public function createData($param, $types = '')
	{
		if (empty($types)) {
			$this->error = '参数错误';
			return false;
		}
		if (empty($param['name'])) {
			$this->error = '场景名称必填';
			return false;
		}
		$user_id = $param['user_id'];
		if ($this->where(['types'=>$types,'user_id'=>$user_id,'name'=>$param['name']])->find()) {
			$this->error = '场景名称已存在';
			return false;
		}
		$max_order_id = $this->getMaxOrderid($types, $user_id);
		$param['order_id'] = $max_order_id ? $max_order_id+1 : 0;
		$param['update_time'] = time();
		$param['type'] = 0;
		$param['bydata'] = '';
		$res = $this->allowField(true)->save($param);
		if ($res) {
			//设置默认
			if ($param['is_default']) {
				$defaultData = [];
				$defaultData['types'] = $types;
				$defaultData['user_id'] = $user_id;
				$this->defaultDataById($defaultData, $this->scene_id);
			}			
			return true;
		} else {
			$this->error = '添加失败';
			return false;			
		}			
	}

	/**
     * [getDataList 场景list]
     * @param  types 分类
     * @author Michael_xu
     * @return    [array]
     */		
	public function getDataList($types, $user_id)
    {
    	$fieldModel = new \app\admin\model\Field();
    	$userModel = new \app\admin\model\User();
    	if (!in_array($types, $this->types_arr)) {
			$this->error = '参数错误';
			return false;    		
    	}
        $map['user_id'] = $user_id;
        $map['is_hide'] = 0;
        $map['types'] = $types;
        $list = db('admin_scene')
        		->where($map)
        		->whereOr(function ($query) use ($types) {
				    $query->where(['types' => $types,'type' => 1]);
				})->order('order_id asc,scene_id asc')
        		->select();
        $defaultSceneId = db('admin_scene_default')->where(['types' => $types,'user_id' => $user_id])->value('scene_id');
        $fieldList = $fieldModel->getField(['types' => $types]);
        $newFieldList = [];
        foreach ($fieldList as $k=>$v) {
        	$field = $v['field'];
        	if ($v['field'] == 'customer_id') $field = 'customer_name'; 
        	$newFieldList[$field] = $v;
        }
        foreach ($list as $k=>$v) {
        	if ($v['scene_id'] == $defaultSceneId) {
        		$list[$k]['is_default'] = 1;
        	}
        	$data = $v['data'] ? json_decode($v['data'],true) : [];
        	if ($data) {
				foreach ($data as $key=>$val) {
					$setting = $newFieldList[$key]['setting'];
	    			$data[$key]['setting'] = $setting;
	    			if ($val['form_type'] == 'user' && $val['value']) {
	    				$userInfo = $userModel->getDataById($val['value']);
	    				$data[$key]['setting']['realname'] = $userInfo['realname'];
	    				$data[$key]['setting']['id'] = $userInfo['id'];
	    			} 
	    		}        		
        	}
    		$list[$k]['data'] = $data ? : [];
        }
        $map['is_hide'] = 1;
        $hideList = $this->where($map)->order('order_id asc')->select();
        $data = [];
        $data['list'] = $list;
        $data['hideList'] = $hideList;
        return $data;
    }

	/**
	 * 根据主键获取详情
	 * @param  array   $param  [description]
	 * @author Michael_xu
	 */ 
	public function getDataById($id = '', $user_id = '', $types = '')
	{
		$where = [];
		$where['scene_id'] = $id;
		// $where['user_id'] = [['=',$user_id],['=',0],'or'];
		$data = db('admin_scene')->where($where)->find();
		if (!$types) {
			$types = $data['types'] ? : '';
		}
		//处理data
		if ($data['bydata'] && $types) {
			$data = $this->getByData($types, $data['bydata'], $user_id);
		} else {
			$data = json_decode($data['data'],true);
			if (is_array($data)) {
				foreach ($data as $k=>$v) {
					if ($v['form_type'] == 'business_type') {
						$data[$k]['value'] = $v['type_id'];
					}
				}	
			}
		}
		return $data ? : [];		
	}   

	/**
	 * 根据主键修改
	 * @param  array   $param  [description]
	 * @author Michael_xu
	 */	
	public function updateDataById($param, $id)
	{
		$checkData = $this->get($id);
		$sceneInfo = $checkData->data;
		$user_id = $param['user_id'];
		if (!$sceneInfo) {
			$this->error = '暂无数据';
			return false;
		}
		//权限（只能编辑自己的）
		if ($sceneInfo['user_id'] !== $user_id) {
			$this->error = '参数错误';
			return false;			
		}
		if (empty($param['name'])) {
			$this->error = '场景名称必填';
			return false;
		}
		if ($this->where(['scene_id'=>['neq',$id],'types'=>$types,'user_id'=>$user_id,'name'=>$param['name']])->find()) {
			$this->error = '场景名称已存在';
			return false;
		}
		$param['update_time'] = time();		
		// $scene_data = $this->dataChangeString($param);
		//处理data数据
		$res = $this->allowField(true)->save($param, ['scene_id' => $id]);
		if ($res) {
			return true;
		} else {
			$this->error = '修改失败';
			return false;	
		}
	}

	/**
	 * 场景设置为默认
	 * @param types 类型
	 * @param user_id 人员ID
	 * @param id 场景ID
	 * @author Michael_xu
	 */	
	public function defaultDataById($param, $id)	
	{
		if (!$param['types'] || !$id) {
			$this->error = '参数错误';
			return false;
		}
		$resInfo = db('admin_scene_default')->where(['types' => $param['types'],'user_id' => $param['user_id']])->find();
		if ($resInfo) {
			$res = db('admin_scene_default')->where(['types' => $param['types'],'user_id' => $param['user_id']])->update(['scene_id' => $id]);
		} else {
			$data = [];
			$data['types'] = $param['types'];
			$data['user_id'] = $param['user_id'];
			$data['scene_id'] = $id;
			$res = db('admin_scene_default')->insert($data);
		}
		if (!$res) {
			$this->error = '设置失败或数据无变化';
			return false;
		}
		return true;
	}

	/**
	 * 场景数据转换(字符串形式存储)
	 * @param  
	 * @author Michael_xu
	 * @return                            
	 */
	public function dataChangeString($param = [])
	{
		die();
		$scene_data = '[';
		$field_arr = [];
		$i = 0;
		foreach ($param['data'] as $k=>$v) {
			if ($v != '' && !in_array($v['field'], $field_arr)) {
				$i++;
				if ($i == 1) {
					$scene_data .= $v['field']."=>[";
				} else {
					$scene_data .= ",".$v['field']."=>[";
				}
				$scene_data .= "field=>".$v['field'].",";
				//处理条件和值
				foreach ($param as $k1=>$v1) {
					switch ($k1) {
						case 'condition' : $scene_data .= "condition=>".$v1.","; break;
						case 'value' : $scene_data .= "value=>".$v1.","; break;
						case 'state' : 
						case 'city' : 
						case 'area' : 
							//处理地址类型数据
							$scene_data .= $k1."=>".$v1.","; break;
						case 'start' :	$scene_data .= "start=>".$v1.","; break;				
						case 'end' :	$scene_data .= "end=>".$v1.","; break;			
						case 'start_date' :	$scene_data .= "start_date=>".$v1.","; break;			
						case 'end_date' :	$scene_data .= "end_date=>".$v1.","; break;			
					}
				}
				$form_type = '';
				//处理字段类型
				if ($v['field'] == 'create_time' || $v['field'] == 'update_time') {
					$form_type = 'datetime';
				} else {
					$form_type = db('admin_fields')->where(['types'=>$param['types'],'field'=>$v['field'],'is_main'=>1])->column('form_type');
				}
				$scene_data .= "form_type=>".$form_type;
				$field_arr[] = $v['field'];
			}
			$scene_data .= ']';
		}
		$scene_data .= ']';
		return $scene_data;
	}

	/**
	 * 场景排序最大值
	 * @param  
	 * @author Michael_xu
	 * @return                            
	 */	
	public function getMaxOrderid($types, $user_id)
	{
		$maxOrderid = $this->where(['types' => $types, 'user_id' => $user_id])->max('order_id');
		return $maxOrderid ? : 0;
	}

	/**
	 * 场景数据（转数组格式），用于where条件
	 * @param  
	 * @author Michael_xu
	 * @return                            
	 */		
	public function dataChangeArray($string)
	{
		$data_arr = [];
		$where = [];
		eval('$data_arr = '.$string.';');
		foreach ($data_arr as $k=>$v) {
			if ($v['state']) {
				$address_where[] = '%'.$v['state'].'%';
				if ($v['city']) {
					$address_where[] = '%'.$v['city'].'%';
					if ($v['area']) {
						$address_where[] = '%'.$v['area'].'%';
					}
				}
				if ($v['condition'] == 'not_contain') {
					$where[$k] = ['notlike', $address_where, 'OR'];
				} else {
					$where[$k] = ['like', $address_where, 'AND'];
				}
			} elseif (!empty($v['start']) || !empty($v['end'])) {
				if ($v['start'] && $v['end']) {
					$where[$k] = ['between',[strtotime($v['start']),strtotime($v['end'])+86399]];
				} elseif ($v['start']) {
					$where[$k] = ['egt',strtotime($v['start'])];
				} else {
					$where[$k] = ['elt',strtotime($v['end'])+86399];
				}
			} elseif (!empty($v['value'])) {
				$where[$k] = field($v['value'],$v['condition']);
			}
		}
		return $where ? : [];
	}
	
	/**
	 * 场景排序
	 * @param  ids 场景id数组
	 * @param  hide_ids 隐藏场景id数组
	 * @author Michael_xu
	 * @return                            
	 */		  
	public function listOrder($param, $user_id)
	{
		$res = true;
		$resHide = true;
		//使用
		$data = [];
		foreach ($param['ids'] as $k=>$v) {
			$data[] = ['scene_id' => $v,'order_id' => $k,'update_time' => time(),'user_id' => $user_id,'is_hide' => 0];
		}
		if ($param['ids']) $res = $this->isUpdate()->saveAll($data);
		//隐藏
		$hideData = [];
		foreach ($param['hide_ids'] as $k=>$v) {
			$hideData[] = ['scene_id' => $v,'order_id' => $k,'update_time' => time(),'user_id' => $user_id,'is_hide' => 1];
		}
		if ($param['hide_ids']) $resHide = $this->isUpdate()->saveAll($hideData);
		if ($res == false || $resHide == false) {
			$this->error = '设置出错，请重试';
			return false;
		}	
		return true;
	}

	/**
     * [getDefaultData 默认场景]
     * @param  types 分类
     * @author Michael_xu
     * @return    [array]
     */		
	public function getDefaultData($types, $user_id)
	{
		$where = [];
		$where['types'] = $types;
		$where['user_id'] = $user_id;
		$scene_id = db('admin_scene_default')->where($where)->value('scene_id');
		if (!$scene_id && in_array($types,['leads','customer','business','contacts','contract','receivables'])) {
			$resData['bydata'] = 'all';
		} else {
			$resData = db('admin_scene')->where(['scene_id' => $scene_id])->find();
		}
		if ($resData['bydata']) {
			$data = $this->getByData($types, $resData['bydata'], $user_id);
		} else {
			//处理data
			$data = $resData ? json_decode($resData,true) : [];			
		}		
		return $data;				
	}

	/**
     * [getByData 系统场景数据]
     * @param  types 分类
     * @author Michael_xu
     * @return    [array]
     */	
    public function getByData($types, $bydata, $user_id)
    {
		$userModel = new \app\admin\model\User();
    	$map = [];
    	$auth_user_ids = [];
    	$part_user_ids = [];
    	switch ($bydata) {
    		case 'me' : $auth_user_ids[] = $user_id; break; //我负责的 
    		case 'mePart' : $part_user_ids = $user_id; break; //我参与的（即相关团队） 
    		case 'sub' : $auth_user_ids = getSubUserId(false) ? : ['-1']; break; //下属负责的 
    		// case 'subPart' : $part_user_ids = getSubUserId('false'); break; //下属参与的 
    		case 'all' : $auth_user_ids = ''; break; //全部
    		case 'is_transform' : $map['is_transform'] = ['condition' => 'eq','value' => 1,'form_type' => 'text','name' => '']; break; //已转化线索
    		// default : $auth_user_ids = $userModel->getUserByPer('crm', $types, 'index'); break;
    		default : $auth_user_ids = ''; break; //全部
    	}
    	$auth_user_ids = $auth_user_ids ? : [];
    	if ($auth_user_ids) {
    		$map['owner_user_id'] = ['condition' => 'in','value' => $auth_user_ids,'form_type' => 'text','name' => ''];
    	}
    	if ($part_user_ids) {
    		$map['ro_user_id'] = $part_user_ids ? : '';
    		$map['rw_user_id'] = $part_user_ids ? : '';
    	}
    	return $map;
    }

	/**
     * [updateData 跳过验证的编辑]
     * @param  types 分类
     * @author Michael_xu
     * @return    [array]
     */	
    public function updateData($data, $scene_id)
    {
    	$param['data'] = is_array($data) ? $data : '';
    	$param['update_time'] = time();
   		$res = $this->allowField(true)->save($param, ['scene_id' => $scene_id]);
		if ($res) {
			return true;
		} else {
			$this->error = '修改失败';
			return false;	
		}
    }
}