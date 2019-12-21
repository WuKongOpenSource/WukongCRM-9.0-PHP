<?php
// +----------------------------------------------------------------------
// | Description: 自定义字段
// +----------------------------------------------------------------------
// | Author: Michael_xu | gengxiaoxu@5kcrm.com 
// +----------------------------------------------------------------------

namespace app\admin\model;

use think\Config;
use think\Db;
use think\Model;
use think\Request;
use think\Validate;

class Field extends Model
{
    /**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如CRM模块用crm作为数据表前缀
     */
	protected $name = 'admin_field';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
	protected $autoWriteTimestamp = true;

	private $tableName = ''; //表名
	private $queryStr = ''; //sql语句
	private $__db_prefix; //数据库表前缀

	private $types_arr = ['crm_leads','crm_customer','crm_contacts','crm_product','crm_business','crm_contract','oa_examine','hrm_parroll','admin_user','crm_receivables','crm_receivables_plan']; //支持自定义字段的表，不包含表前缀
	private $formtype_arr = ['text','textarea','mobile','email','number','floatnumber','radio','select','checkbox','date','datetime','address','user','file','structure'];
 
	protected $type = [
        'form_value'    =>  'array',
	]; 	
	

	/**
	 * 列表展示额外关联字段
	 */
	public $orther_field_list = [
		'crm_customer' => [
			[
		        'field' => 'last_record',
		        'name' => '跟进记录',
		        'form_type' => 'text',
		        'width' => ''
			],
			[
		        'field' => 'address',
				'name' => '省、市、区/县',
		        'form_type' => 'customer_address',
		        'width' => ''
			],
			[
		        'field' => 'detail_address',
		        'name' => '详细地址',
		        'form_type' => 'text',
		        'width' => ''
			]
		],
		'crm_leads' => [
			[
		        'field' => 'last_record',
		        'name' => '跟进记录',
		        'form_type' => 'text',
		        'width' => ''
			]
		],
		'crm_contract' => [
			[
		        'field' => 'done_money',
		        'name' => '已回款',
		        'form_type' => 'floatnumber',
		        'width' => ''
			],
			[
		        'field' => 'un_money',
		        'name' => '未回款',
		        'form_type' => 'floatnumber',
		        'width' => ''
		    ]
		],
		'crm_receivables' => [
			[
		        'field' => 'contract_money',
		        'name' => '合同金额',
		        'form_type' => 'floatnumber',
		        'width' => ''
		    ]
		]
	];

	protected  function initialize()
    {
    	$this->__db_prefix = Config::get('database.prefix');
    }

	/**
	 * [getDataList 获取列表] 
	 * @author Michael_xu
	 * @param types  分类
	 * @return    [array]                         
	 */
	public function getDataList($param)
	{
		$types = trim($param['types']);
		if (!in_array($types, $this->types_arr)) {
			$this->error = '参数错误';
			return false;
		}
		$map = $param;
        if ($types == 'oa_examine') {
        	$map['types_id'] = $param['types_id'];
        }
		if ($param['types'] == 'crm_customer') {
			$map['field'] = array('not in',['deal_status']);
		}        
        $list = Db::name('AdminField')->where($map)->order('order_id')->select();
        foreach ($list as $k=>$v) {
        	$list[$k]['setting'] = $v['setting'] ? explode(chr(10),$v['setting']) : [];
        	if ($v['form_type'] == 'checkbox') {
        		$list[$k]['default_value'] = $v['default_value'] ? explode(',',$v['default_value']) : array();
        	}
        }
        return $list ? : [];
	}

	/**
	 * [createData 创建自定义字段] 
	 * @author Michael_xu
	 * @param types 分类
	 * @param field 字段名
	 * @param name 字段标识名（字段注释）
	 * @param form_type 字段类型
	 * @param max_length 字段最大长度
	 * @param default_value 默认值
	 * @param setting 单选、下拉、多选类型的选项值
	 * @return    [array]                         
	 */	
	public function createData($types, $param)
	{
		if (!$types || !in_array($types, $this->types_arr) || !is_array($param)) {
			$this->error = '参数错误';
			return false;
		}
		
		$error_message = [];
		$i = 0;
		foreach ($param as $k=>$data) {
			$i++;
			$data['types'] = $types;
			if ($types == 'oa_examine' && !$data['types_id']) {		
				$error_message[] = $data['name'].'参数错误';	
			}			
			$data['types_id'] = $data['types_id'] ? : 0;
			if (!in_array($data['form_type'], $this->formtype_arr)) {
				$error_message[] = $data['name'].',字段类型错误';
			}
			//生成字段名
			if (!$data['field']) $data['field'] = $this->createField($types);

			$rule = [
				'field'  		=> ['regex'=>'/^[a-z]([a-z]|_)+[a-z]$/i'],
				'name'      	=> 'require',
				'types'      	=> 'require',
				'form_type'      	=> 'require',
			];
			$msg = [
				'field.regex'    	=> '字段名称格式不正确！',
				'name.require'    	=> '字段标识必须填写',
				'types.require'    	=> '分类必须填写',
				'form_type.require'    	=> '字段类型必须填写',
			];	
			// 验证
			// $validate = validate($this->name);			
			$validate = new Validate($rule, $msg);	
			
			if (!$validate->check($data)) {
				$error_message[] = $validate->getError();
			} else {
				//单选、下拉、多选类型(使用回车符隔开)
				if (in_array($data['form_type'], ['radio','select','checkbox']) && $data['setting']) {
					$data = $this->settingValue($data);	
				}

				//表格类型
				if ($data['form_type'] == 'form' && $data['form_value']) {
					$new_form_value = [];
					foreach ($data['form_value'] as $form => $fromVal) {
						$fromVal['field'] = 'form_'.$this->createField($types);
						if (in_array($fromVal['form_type'], ['radio','select','checkbox']) && $fromVal['setting']) {
							$fromVal = $this->settingValue($fromVal);	
						}
					}
					$new_form_value = $fromVal;
					$data['form_value'] = $new_form_value;
				}

				unset($data['field_id']);

				if ($i > 1) {
					$resField = $this->data($data)->allowField(true)->isUpdate(false)->save();
				} else {
					$resField = $this->data($data)->allowField(true)->save();
				}	
				if ($types !== 'oa_examine') {
					if ($resField) {
						actionLog($this->field_id,'','',''); //操作日志
						$this->tableName = $types;
						$maxlength = '255';
						$defaultvalue = $data['default_value'] ? "DEFAULT '".$data['default_value']."'" : "DEFAULT NULL";
						//根据字段类型，创建字段
						switch ($data['form_type']) {
							case 'address' :
								$this->queryStr = "ALTER TABLE `" . $this->__db_prefix . $this->tableName . "` ADD `".$data['field']."` VARCHAR(500) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '".$data['name']."'";
								break;
							case 'radio' :
							case 'select' :
							case 'checkbox' :
								$defaultvalue = $data['default_value'] ? "DEFAULT '".$data['default_value']."'" : '';
								$maxlength = 500;
								$this->queryStr = "ALTER TABLE `" . $this->__db_prefix . $this->tableName . "` ADD `".$data['field']."` VARCHAR( ".$maxlength." ) CHARACTER SET utf8 COLLATE utf8_general_ci ".$defaultvalue." COMMENT '".$data['name']."'";
								break;
							case 'textarea' :
								$this->queryStr = "ALTER TABLE `" . $this->__db_prefix . $this->tableName . "` ADD `".$data['field']."` TEXT COMMENT '".$data['name']."'";
								break;
							case 'number' :
								$defaultvalue = abs(intval($data['default_value'])) > 2147483647 ? 2147483647 : intval($data['default_value']);
			                    $maxlength = 11;
			                    $this->queryStr = "ALTER TABLE `" . $this->__db_prefix . $this->tableName . "` ADD `".$data['field']."` int ( ".$maxlength." ) DEFAULT '".$defaultvalue."' COMMENT '".$data['name']."'";
			                    break;
			                case 'floatnumber' :
								$defaultvalue = abs(intval($data['default_value'])) > 9999999999999999.99 ? 9999999999999999.99 : intval($data['default_value']);
			                    $maxlength = 18;
			                    $this->queryStr = "ALTER TABLE `" . $this->__db_prefix . $this->tableName . "` ADD `".$data['field']."` decimal (".$maxlength.",2) DEFAULT '".$defaultvalue."' COMMENT '".$data['name']."'";
			                    break;
							case 'date' :
								$this->queryStr = "ALTER TABLE `" . $this->__db_prefix . $this->tableName . "` ADD `".$data['field']."` DATE ".$defaultvalue." COMMENT '".$data['name']."'";
			                	break;
			                case 'datetime' :
			                	$defaultvalue = $data['default_value'] ? "DEFAULT '".strtotime($data['default_value'])."'" : '';
			                	$this->queryStr = "ALTER TABLE `" . $this->__db_prefix . $this->tableName . "` ADD `".$data['field']."` int (11) ".$defaultvalue." COMMENT '".$data['name']."'";
			                	break;
							case 'file' :
								$this->queryStr = "ALTER TABLE `" . $this->__db_prefix . $this->tableName . "` ADD `".$data['field']."` VARCHAR ( ".$maxlength." ) CHARACTER SET utf8 COLLATE utf8_general_ci COMMENT '".$data['name']."'";
			                	break;
			                case 'form' :
			                	$this->queryStr = "ALTER TABLE `" . $this->__db_prefix . $this->tableName . "` ADD `".$data['field']."` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci COMMENT '".$data['name']."'";
			                	break;              
			                default :
								$maxlength = 255;
			                    $this->queryStr = "ALTER TABLE `" . $this->__db_prefix . $this->tableName . "` ADD `".$data['field']."` VARCHAR( ".$maxlength." ) CHARACTER SET utf8 COLLATE utf8_general_ci ".$defaultvalue." COMMENT '".$data['name']."'"; 
			                    break;
						}
						$resData = Db::execute($this->queryStr);
						if ($resData === false) {
							$this->where(['field_id' => $this->field_id])->delete();
							$error_message[] = $data['name'].',添加失败';
						}	
					} else {
						$error_message[] = $data['name'].',添加失败';
					}						
				}			
			}			
		}
		if ($error_message) {
			$this->error = implode(';',$error_message);
			return false;			
		}
		return true;
	}

	/**
	 * [settingValue 单选、下拉、多选值] 
	 * @author Michael_xu
	 * @return    [array]                         
	 */		
	public function settingValue($data)
	{
		//将英文逗号转换为中文逗号
		$new_setting = [];
		foreach ($data['setting'] as $k => $v) {
			$v = str_replace(')', '）', $v);
			$v = str_replace('(', '（', $v);			
			$new_setting[] = str_replace(',', '，', $v);
		}
		$data['setting'] = implode(chr(10), $new_setting);
		//默认值
		$new_default_value = [];
		if ($data['default_value'] && $data['form_type'] == 'checkbox') {
			foreach ($data['default_value'] as $k => $v) {
				$new_default_value[] = str_replace(',', '，', $v);
			}
			$data['default_value'] = implode(',', $new_default_value);							
		}
		return $data;		
	}

	/**
	 * [updateDataById 編輯自定义字段] 
	 * @author Michael_xu
	 * @param types 分类
	 * @param field 字段名
	 * @param name 字段标识名（字段注释）
	 * @param form_type 字段类型
	 * @param max_length 字段最大长度
	 * @param default_value 默认值
	 * @return    [array]                         
	 */	
	public function updateDataById($param)
	{
		$error_message = [];
		if (!is_array($param)) {
			$this->error = '参数错误';
			return false;
		}
		$i = 0;
		foreach ($param as $data) {
			$i++;
			$field_id = intval($data['field_id']);
			if (!$field_id) {
				$error_message[] = $data['name'].',参数错误';
			}
			$dataInfo = $this->get($field_id);
			if (!$dataInfo) {
				$error_message[] = $data['name'].'参数错误';
			}
			// $error_message[] = $data['name'].',该字段不能编辑';
			$data['types'] = $dataInfo['types'];			
			//单选、下拉、多选类型(使用回车符隔开)
			if (in_array($data['form_type'], ['radio','select','checkbox']) && $data['setting']) {
				//将英文逗号转换为中文逗号
				$data = $this->settingValue($data);
			}
			// 验证
			$validate = validate($this->name);
			if (!$validate->check($data)) {
				$error_message[] = $validate->getError();
			} else {
				// unset($data['field']);
				$data['field'] = $dataInfo['field'];
				unset($data['operating']);
				$box_form_type = array('checkbox','select','radio');
				if ((in_array($dataInfo['form_type'], $box_form_type) && !in_array($data['form_type'], $box_form_type)) || !in_array($dataInfo['form_type'], $box_form_type)) {
					unset($data['form_type']);
				}	
				// $resField = $this->allowField(true)->save($data, ['field_id' => $field_id]);
				unset($data['showSetting']);
				unset($data['componentName']);
				unset($data['is_deleted']);
				$data['update_time'] = time();

				$resField = db('admin_field')->where(['field_id' => $field_id])->update($data);
				if ($dataInfo['types'] !== 'oa_examine') {
					if ($resField) {
						actionLog($field_id); //操作日志
						$this->tableName = $dataInfo['types'];
						$maxlength = '255';
						$defaultvalue = $data['default_value'] ? "DEFAULT '".$data['default_value']."'" : "DEFAULT NULL";
						//根据字段类型，创建字段
						switch ($dataInfo['form_type']) {
							case 'address' :
								$this->queryStr = "ALTER TABLE `" . $this->__db_prefix . $this->tableName . "` CHANGE `".$dataInfo['field']."` `".$data['field']."` VARCHAR( 500 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '".$data['name']."'";
								break;
							case 'radio' :
							case 'select' :
							case 'checkbox' :
								$defaultvalue = $data['default_value'] ? "DEFAULT '".$data['default_value']."'" : '';
								$maxlength = 500;
								$this->queryStr = "ALTER TABLE `" . $this->__db_prefix . $this->tableName . "` CHANGE `".$dataInfo['field']."` `".$data['field']."` VARCHAR( ".$maxlength." ) CHARACTER SET utf8 COLLATE utf8_general_ci ".$defaultvalue." COMMENT '".$data['name']."'";
								break;
							case 'textarea' :
								$this->queryStr = "ALTER TABLE `" . $this->__db_prefix . $this->tableName . "` CHANGE `".$dataInfo['field']."` `".$data['field']."` TEXT COMMENT '".$data['name']."'";
								break;
							case 'number' :
								$defaultvalue = abs(intval($data['default_value'])) > 2147483647 ? 2147483647 : intval($data['default_value']);
			                    $maxlength = 11;
			                    $this->queryStr = "ALTER TABLE `" . $this->__db_prefix . $this->tableName . "` CHANGE `".$dataInfo['field']."` `".$data['field']."` int ( ".$maxlength." ) DEFAULT '".$defaultvalue."' COMMENT '".$data['name']."'";
			                    break;
			                case 'floatnumber' :
								$defaultvalue = abs(intval($data['default_value'])) > 9999999999999999.99 ? 9999999999999999.99 : intval($data['default_value']);
			                    $maxlength = 18;
			                    $this->queryStr = "ALTER TABLE `" . $this->__db_prefix . $this->tableName . "` CHANGE `".$dataInfo['field']."` `".$data['field']."` decimal (".$maxlength.",2) DEFAULT '".$defaultvalue."' COMMENT '".$data['name']."'";
			                    break;
							case 'date' :
								$this->queryStr = "ALTER TABLE `" . $this->__db_prefix . $this->tableName . "` CHANGE `".$dataInfo['field']."` `".$data['field']."` DATE ".$defaultvalue." COMMENT '".$data['name']."'";
			                	break;	
			                case 'datetime' :
			               		$defaultvalue = $data['default_value'] ? "DEFAULT '".strtotime($data['default_value'])."'" : '';
			                	$this->queryStr = "ALTER TABLE `" . $this->__db_prefix . $this->tableName . "` CHANGE `".$dataInfo['field']."` `".$data['field']."` int (11) ".$defaultvalue." COMMENT '".$data['name']."'";
			                	break;
							case 'file' :
			                	$this->queryStr = "ALTER TABLE `" . $this->__db_prefix . $this->tableName . "` CHANGE `".$dataInfo['field']."` `".$data['field']."` VARCHAR ( ".$maxlength." ) CHARACTER SET utf8 COLLATE utf8_general_ci COMMENT '".$data['name']."' ";
			                	break;                
			                default :
								$maxlength = 255;
			                    $this->queryStr = "ALTER TABLE `" . $this->__db_prefix . $this->tableName . "` CHANGE `".$dataInfo['field']."` `".$data['field']."` VARCHAR( ".$maxlength." ) CHARACTER SET utf8 COLLATE utf8_general_ci ".$defaultvalue." COMMENT '".$data['name']."'"; 
			                    break;
						}
						$resData = Db::execute($this->queryStr);
						if ($resData === false) {
							$error_message[] = $data['name'].',修改失败';
						}
					} else {
						$error_message[] = $data['name'].',修改失败';
					}						
				}	
			}	
		}
		if ($error_message) {
			$this->error = implode(';', $error_message);
			return false;
		}
		return true;
	}

	/**
	 * [delDataById 删除自定义字段] 删除逻辑数据不可恢复，谨慎操作
	 * @author Michael_xu
	 * @param $id [array] 字段ID
	 * @param $types 分类
	 */	
	public function delDataById($ids)
	{
		if (!is_array($ids)) {
			$ids[] = $ids;
		}
		$delMessage = [];
		foreach ($ids as $id) {
			$dataInfo = [];
			$dataInfo = $this->get($id);				

			if ($dataInfo) {
				//operating ： 0改删，1改，2删，3无
				if (in_array($dataInfo['operating'], ['1','3'])) {
					$delMessage[] = $dataInfo['name'].',系统字段，不能删除';	
				} else {
					$resDel = $this->where(['field_id' => $id])->delete(); //删除自定义字段信息
					if ($resDel && $dataInfo['types'] !== 'oa_examine') {
						$this->tableName = $dataInfo['types'];
						if ($dataInfo['form_type'] == 'img') {
							//图片类型需删除两个字段
							// $this->queryStr = "ALTER TABLE `" . $this->__db_prefix . $this->tableName . "` DROP `".$dataInfo['field']."`,"." DROP `thumb_".$dataInfo['field']."`";
							$this->queryStr = "ALTER TABLE `" . $this->__db_prefix . $this->tableName . "` DROP `".$dataInfo['field']."`";
						} else {
							$this->queryStr = "ALTER TABLE `" . $this->__db_prefix . $this->tableName . "` DROP `".$dataInfo['field']."`";
						}
						$resData = Db::execute($this->queryStr); //删除表字段数据
						if (!$resData) {
							$delMessage[] = $dataInfo['name'].',删除失败';
						}
						//删除列表字段配置数据
						$userFieldList = db('admin_user_field')->where(['types' => $dataInfo['types']])->select();
						foreach ($userFieldList as $key=>$val) {
							$datas = [];
							$datas = json_decode($val['datas'],true);
							if ($datas) {
								foreach ($datas as $k=>$v) {
									$datas[$k]['field'] = $k;
									unset($datas[$dataInfo['field']]);
								}
								$dataUserField = [];
								$dataUserField['value'] = $datas;
								$dataUserField['hide_value'] = [];
								$resUserField = model('UserField')->updateConfig($dataInfo['types'], $dataUserField, $val['id']);
							}
						}
						//删除场景字段数据
						$sceneFieldList = db('admin_scene')->where(['types' => $dataInfo['types']])->select();
						foreach ($sceneFieldList as $key=>$val) {
							$data = [];
							$data = json_decode($val['data'],true);
							if ($data) {
								foreach ($data as $k=>$v) {
									unset($data[$dataInfo['field']]);
								}
								$data = $data ? : [];
								$sceneModel = new \app\admin\model\Scene();
								$resScene = $sceneModel->updateData($data, $val['scene_id']);
							}
						}				
					} else {
						$delMessage[] = $dataInfo['name'].',删除失败';
					}					
				}
			}		
		}
		return $delMessage ? implode(';', $delMessage) : '';
	}

	/**
	 * [createField 随机生成自定义字段名]
	 * @author Michael_xu 
	 * @param $field_str 字段名前缀
	 * @param $types 分类
	 */
	public function createField($types = '', $field_str = 'crm_')
	{
		for ($i = 1; $i <= 6; $i++) {
			$field_str .= chr(rand(97, 122));
		}
		//验证字段名是否已存在
		if ($this->where(['types' => $types,'field' => $field_str])->find()) {
			$this->createField($types);
		}
		return $field_str;		
	}

	/**
	 * [field 获取自定义字段信息]
	 * @author Michael_xu
	 * @param $types 分类
	 * @param $dataInfo 数据展示
	 * @param $map  查询条件
	 * @param form_type  字段类型 （’text’,’textarea’,’mobile’,’email’等） 
	 * @param default_value  默认值
	 * @param max_length  输入最大长度
	 * @param is_unique  1时，唯一性验证
	 * @param is_null  1时，必填
	 * @param input_tips  输入框提示内容
	 * @param setting  设置 （单选、下拉、多选的选项值，使用回车分隔）
	 */	
	public function field($param, $dataInfo = [])
	{
		$userModel = new \app\admin\model\User();
		$structureModel = new \app\admin\model\Structure();
		$fileModel = new \app\admin\model\File();
		$user_id = $param['user_id'];
		$types = $param['types'];
		$types_id = $param['types_id'] ? : 0;
		if ($types == 'crm_customer_pool') $types = 'crm_customer';
		$map = $param['map'] ? : [];
		if (!in_array($types, $this->types_arr)) {
			$this->error = '参数错误';
			return false;
		}
		if ($types == 'oa_examine' && !$types_id) {
			$this->error = '参数错误';
			return false;			
		} elseif ($types == 'admin_user') {
			return User::$import_field_list;
		}

		if (in_array($param['action'],array('index','view'))) {
			$map['types'] = array(array('eq',$types),array('eq',''),'or');
		} else {
			if ($param['types'] == 'crm_customer' && (in_array($param['action'],array('save','update','excel')))) {
				$map['field'] = array('not in',['deal_status']);
			}
			$map['types'] = $types;
		}
		if ($param['controller'] == 'customer' && $param['action'] == 'pool') {
			$map['field'] = array('not in',['owner_user_id']);
			$types = 'crm_customer_pool';
		}
		if ($param['action'] == 'excel') {
			$map['form_type'] = array('not in',['file','form','user','structure']);
		} elseif ($param['action'] == 'index') {
			$map['form_type'] = array('not in',['file','form']);
		}				

		$map['types_id'] = $types_id;
		$order = 'order_id asc, field_id asc';
		if ($param['action'] == 'index' || $param['action'] == 'pool') {
			$field_list = $this->getIndexFieldConfig($types, $param['user_id']); 
			// $order = new \think\db\Expression('field(field_id,'..')');
		} else {
			$field_list = $this->where($map)->field('field,types,name,form_type,default_value,is_unique,is_null,input_tips,setting')->order($order)->select();
			//客户
			if (in_array($param['types'],['crm_customer'])) {
				$new_field_list[] = [
					'field' => 'customer_address',
		            'name' => '地区定位',
		            'form_type' => 'map_address',
		            'default_value' => '',
		            'is_unique' => 0,
		            'is_null' => 0,
		            'input_tips' => '',
		            'setting' => [],
		            'value' => []
				];
			}
			//商机、合同下产品
			if (in_array($param['types'],['crm_business','crm_contract'])) {
				$new_field_list[] = [
					'field' => 'product',
		            'name' => '产品',
		            'form_type' => 'product',
		            'default_value' => '',
		            'is_unique' => 0,
		            'is_null' => 0,
		            'input_tips' => '',
		            'setting' => [],
		            'value' => []
				];				
			}
			if ($new_field_list) $field_list = array_merge(collection($field_list)->toArray(), $new_field_list);
			foreach ($field_list as $k=>$v) {
				//处理setting内容
				$setting = [];
				$default_value = $v['default_value'];
				$value = [];
				if (in_array($v['form_type'], ['radio','select','checkbox'])) {
					$setting = explode(chr(10), $v['setting']);
					if ($v['form_type'] == 'checkbox') $default_value = $v['default_value'] ? explode(',', $v['default_value']) : [];
				}
				if ($v['field'] == 'order_date') {
					$default_value = date('Y-m-d',time());
				}
				//地图类型
				if ($v['form_type'] == 'map_address') {
					$value = [
						'address' => $dataInfo['address'] ? explode(chr(10), $dataInfo['address']) : [],
						'location' => $dataInfo['location'],
						'detail_address' => $dataInfo['detail_address'],
						'lng' => $dataInfo['lng'],
						'lat' => $dataInfo['lat']
					];
				} elseif ($v['form_type'] == 'product') {
					//相关产品类型
					switch ($param['types']) {
						case 'crm_business' : 
							$rProduct = db('crm_business_product');
							$r_id = 'business_id';
							break;
						case 'crm_contract' : 
							$rProduct = db('crm_contract_product');
							$r_id = 'contract_id';
							break;
						default : break;
					}
					$newProductList = [];
					$productList = $rProduct->where([$r_id => $param['action_id']])->select();
					foreach ($productList as $key=>$product) {
						$product_info = [];
						$category_name = '';
						$product_info = db('crm_product')->where(['product_id' => $product['product_id']])->field('product_id,name,category_id')->find();
						$category_name = db('crm_product_category')->where(['category_id' => $product_info['category_id']])->value('name');
						$productList[$key]['name'] = $product_info['name'] ? : '';
						$productList[$key]['category_id_info'] = $category_name ? : '';
					}
					$value = [
						'product' => $productList,
						'total_price' => $dataInfo['total_price'],
						'discount_rate' => $dataInfo['discount_rate']
					];
				} elseif ($v['form_type'] == 'user') {
					$value = $userModel->getListByStr($dataInfo[$v['field']]) ? : [];
				} elseif ($v['form_type'] == 'structure') {
					$value = $structureModel->getListByStr($dataInfo[$v['field']]) ? : [];
				} elseif ($v['form_type'] == 'file') {
					$fileIds = [];
					$fileIds = stringToArray($dataInfo[$v['field']]);
					$whereFile = [];
					$whereFile['module'] = 'other';
					$whereFile['module_id'] = 1;
					$whereFile['file_id'] = ['in',$fileIds];
					$fileList = $fileModel->getDataList($whereFile, 'all');
					$value = $fileList['list'] ? : [];					
				} elseif ($v['form_type'] == 'customer') {
					$value = $dataInfo[$v['field']] ? db('crm_customer')->where(['customer_id' => $dataInfo[$v['field']]])->field('customer_id,name')->select() : [];	
				} elseif ($v['form_type'] == 'business') {
					$value = $dataInfo[$v['field']] ? db('crm_business')->where(['business_id' => $dataInfo[$v['field']]])->field('business_id,name')->select() : [];
				} elseif ($v['form_type'] == 'contacts') {
					$value = $dataInfo[$v['field']] ? db('crm_contacts')->where(['contacts_id' => $dataInfo[$v['field']]])->field('contacts_id,name')->select() : [];
				} elseif ($v['form_type'] == 'contract') {
					$value = $dataInfo[$v['field']] ? db('crm_contract')->where(['contract_id' => $dataInfo[$v['field']]])->field('contract_id,num')->select() : [];				
				} elseif ($v['form_type'] == 'category') {
					//产品类别
					if ($param['action'] == 'read') {
						$category_name = db('crm_product_category')->where(['category_id' => $dataInfo['category_id']])->value('name');	
						$value = $category_name ? : '';
					} elseif ($param['action'] == 'update') {
						$value = $dataInfo['category_str'] ? stringToArray($dataInfo['category_str']) : [];
					} else {
						$categoryModel = new \app\crm\model\ProductCategory();
				        $value = $categoryModel->getDataList('tree');
					}
				} elseif ($v['form_type'] == 'business_type') {
					//商机状态组
					$businessStatusModel = new \app\crm\model\BusinessStatus();
					$userInfo = $userModel->getUserById($user_id);
				    $setting = db('crm_business_type')
				            ->where(['structure_id' => ['like','%,'.$userInfo['structure_id'].',%'],'status' => 1])
				            ->whereOr('structure_id','')
				            ->select();	
				    foreach ($setting as $key=>$val) {
				        $setting[$key]['statusList'] = $businessStatusModel->getDataList($val['type_id'],1); 
				    }
				    $setting = $setting ? : [];					
					if ($param['action'] == 'read') {
						$value = $dataInfo[$v['field']] ? db('crm_business_type')->where(['type_id' => $dataInfo[$v['field']]])->value('name') : '';
					} else {
						$value = (int)$dataInfo[$v['field']] ? : '';
					}
				} elseif ($v['form_type'] == 'business_status') {
					//商机阶段
					if ($param['action'] == 'read') {
						$value = $dataInfo[$v['field']] ? db('crm_business_status')->where(['status_id' => $dataInfo[$v['field']]])->value('name') : '';
					} else {
						$businessStatusModel = new \app\crm\model\BusinessStatus();
						$setting = $businessStatusModel->getDataList($dataInfo['type_id'],1);				
						$value = (int)$dataInfo[$v['field']] ? : '';
					}
				} elseif ($v['form_type'] == 'receivables_plan') {
					//回款计划期数
					$value = $dataInfo[$v['field']] ? db('crm_receivables_plan')->where(['plan_id' => $dataInfo[$v['field']]])->value('num') : '';
				} elseif ($v['form_type'] == 'business_cause' || $v['form_type'] == 'examine_cause') {
					$whereTravel = [];
					$whereTravel['examine_id'] = $dataInfo['examine_id'];
					$travelList = db('oa_examine_travel')->where($whereTravel)->select() ? : [];
					foreach ($travelList as $key=>$val) {
						$where = [];
						$fileList = [];
						$imgList = [];
						$where['module'] = 'oa_examine_travel';
						$where['module_id'] = $val['travel_id'];			
						$newFileList = [];
						$newFileList = $fileModel->getDataList($where, 'all');
						if ($newFileList['list']) {
							foreach ($newFileList['list'] as $val1) {
								if ($val1['types'] == 'file') {
									$fileList[] = $val1;
								} else {
									$imgList[] = $val1;
								}
							}					
						}				
						$travelList[$key]['fileList'] = $fileList ? : [];
						$travelList[$key]['imgList'] = $imgList ? : [];				
					}
					$value = $travelList ? : [];	
				} elseif ($v['form_type'] == 'checkbox') {
					$value = isset($dataInfo[$v['field']]) ? stringToArray($dataInfo[$v['field']]) : [];
				} elseif ($v['form_type'] == 'date') {
					$value = ($dataInfo[$v['field']] && $dataInfo[$v['field']] !== '0000-00-00') ? $dataInfo[$v['field']] : '';
				} else {
					$value = isset($dataInfo[$v['field']]) ? $dataInfo[$v['field']] : '';
				}
				$field_list[$k]['setting'] = $setting;
				$field_list[$k]['default_value'] = $default_value;
				$field_list[$k]['value'] = $value;
			}
		}
		return $field_list ? : [];
	}

	/**
	 * [fieldSearch 获取自定义字段高级筛选信息]
	 * @author Michael_xu
	 * @param $types 分类
	 * @param $map  查询条件
	 * @param form_type  字段类型 （’text’,’textarea’,’mobile’,’email’等） 
	 * @param setting  设置 （单选、下拉、多选的选项值，使用回车分隔）
	 */	
	public function fieldSearch($param)
	{
		$types = $param['types'];
		if (!in_array($types, $this->types_arr)) {
			$this->error = '参数错误';
			return false;
		}
		$userModel = new \app\admin\model\User();
		$user_id = $param['user_id'];
		$map['types'] = ['in',['',$types]];
		$map['form_type'] = ['not in',['file','form','checkbox','structure','business_status']];
		$field_list = db('admin_field')
						->where($map)
						->whereOr(['types' => ''])
						->field('field,name,form_type,setting')
						->order('order_id asc, field_id asc, update_time desc')
						->select();
		if (in_array($types,['crm_contract','crm_receivables'])) {
			$field_arr = [
				'0' => [
					'field' => 'check_status',
					'name' => '审核状态',
					'form_type' => 'select',
					'setting' => '待审核'.chr(10).'审核中'.chr(10).'审核通过'.chr(10).'审核失败'.chr(10).'已撤回'.chr(10).'未提交'.chr(10).'已作废'
				]
			];
		}
		if (in_array($param['types'],['crm_customer'])) {
			$field_arr = [
				'0' => [
					'field' => 'address',
		            'name' => '地区定位',
		            'form_type' => 'address',
		            'setting' => []
		        ]
			];
		}
		if ($field_arr) $field_list = array_merge($field_list, $field_arr);	
		foreach ($field_list as $k=>$v) {
			//处理setting内容
			$setting = [];
			if (in_array($v['form_type'], ['radio','select','checkbox'])) {
				$setting = explode(chr(10), $v['setting']);
			}
			$field_list[$k]['setting'] = $setting;
			if ($v['field'] == 'customer_id') {
				$field_list[$k]['form_type'] = 'module'; $field_list[$k]['field'] = 'customer_name';
			} 
			if ($v['field'] == 'business_id') {
				$field_list[$k]['form_type'] = 'module'; $field_list[$k]['field'] = 'business_name';
			}
			if ($v['field'] == 'contract_id') {
				$field_list[$k]['form_type'] = 'module'; $field_list[$k]['field'] = 'contract_name';
			}
			if ($v['field'] == 'contacts_id') {
				$field_list[$k]['form_type'] = 'module'; $field_list[$k]['field'] = 'contacts_name';
			}			

			if ($v['form_type'] == 'category') {
				
			} elseif ($v['form_type'] == 'business_type') {
				//商机状态组
				$businessStatusModel = new \app\crm\model\BusinessStatus();
				$userInfo = $userModel->getUserById($user_id);
			    $setting = db('crm_business_type')
			            ->where(['structure_id' => $userInfo['structure_id'],'status' => 1])
			            ->whereOr('structure_id','')
			            ->select();	
			    foreach ($setting as $key=>$val) {
			        $setting[$key]['statusList'] = $businessStatusModel->getDataList($val['type_id'], 1); 
			    }
			    $setting = $setting ? : [];
			}
			$field_list[$k]['setting'] = $setting;		
		}
		return $field_list ? : [];		
	}

	/**
	 * [validateField 自定义字段验证规则] 
	 * @author Michael_xu
	 * @param 
	 */	
	public function validateField($types, $types_id = 0)
	{
		$unField = ['update_time','create_time','create_user_id','owner_user_id'];
		$fieldList = $this->where(['types' => ['in',['',$types]],'types_id' => $types_id,'field' => ['not in',$unField],'form_type' => ['not in',['checkbox','user','structure','file']]])->field('field,name,form_type,is_unique,is_null,max_length')->select();
		$validateArr = [];
		$rule = [];
		$message = [];		
		foreach ($fieldList as $field) {
			$rule_value = '';
			$scene_value = '';
			
			$max_length = $field['max_length'] ? : '';

			if ($field['is_null']) {
				$rule_value .= 'require';
				$message[$field['field'].'.require'] = $field['name'].'不能为空';				
			}
			if ($field['form_type'] == 'number') {
				if ($rule_value) $rule_value .= '|';
				$rule_value .= 'number';
				$message[$field['field'].'.number'] = $field['name'].'必须是数字';
			} elseif ($field['form_type'] == 'email') {
				if ($rule_value) $rule_value .= '|';
				$rule_value .= 'email';
				$message[$field['field'].'.email'] = $field['name'].'格式错误';
			} elseif ($field['form_type'] == 'mobile ') {
				if ($rule_value) $rule_value .= '|';
				$rule_value .= 'regex:^1[3456789][0-9]{9}?$';
				$message[$field['field'].'.regex'] = $field['name'].'格式错误';
			}
			if ($field['is_unique']) {
				if ($rule_value) $rule_value .= '|';
				$rule_value .= 'unique:'.$types;
				$message[$field['field'].'.unique'] = $field['name'].'已经存在,不能重复添加';
			}
			if ($max_length) {
				if ($rule_value) $rule_value .= '|';
				$rule_value .= 'max:'.$max_length;
				$message[$field['field'].'.max'] = $field['name'].'不能超过'.$max_length.'个字符';
			}					
			// if ($field['form_type'] == 'datetime') {
			// 	$rule_value .= 'date';
			// 	$message[$field['field'].'.date'] = $field['name'].'格式错误';
			// }					
			if ($rule_value == 'require|') $rule_value = 'require';		
			if (!empty($rule_value)) $rule[$field['field']] = $rule_value;

		}
		$validateArr['rule'] = $rule ? : [];
		$validateArr['message'] = $message ? : [];
		return $validateArr;		
	}

	/**
	 * [getIndexField 列表展示字段]
	 * @author Michael_xu 
	 * @param types 分类	
	 */
	public function getIndexFieldConfig($types, $user_id)
	{	
		$userFieldModel = new \app\admin\model\UserField();
		$userFieldData = $userFieldModel->getConfig($types, $user_id);
		$userFieldData = $userFieldData ? json_decode($userFieldData, true) : [];

		$fieldList = $this->getFieldList($types);
		
		$where = [];
		if ($userFieldData) {
			$fieldArr = [];
			$i = 0;
			foreach ($userFieldData as $k=>$v) {
				if (empty($v['is_hide'])) {
					$fieldArr[$i]['field'] = $k;
					$fieldArr[$i]['name'] = $fieldList[$k]['name'];
					$fieldArr[$i]['form_type'] = $fieldList[$k]['form_type'];
					$fieldArr[$i]['width'] = $v['width'] ?: '';
					$i++;
				}
			}
			$dataList = $fieldArr;
		} else {
			$dataList = $fieldList;			
		}

		return array_values($dataList) ? : [];
	}

	/**
	 * 获取列表展示字段
	 *
	 * @return void
	 * @author Ymob
	 * @datetime 2019-10-23 17:32:57
	 */
	public function getFieldList($types)
	{
		$newTypes = $types;
		$unField = ['-1'];
		if ($types == 'crm_customer_pool') {
			$newTypes = 'crm_customer';
			$unField = ['owner_user_id'];
		}
		
		$fieldArr = $this
			->where([
				'types' => ['IN', ['', $newTypes]],
				'form_type' => ['not in', ['file', 'form']],
				'field' => ['not in', $unField],
			])
			->field(['field', 'name', 'form_type'])
			->order('order_id asc')
			->select();

		$res = [];
		foreach ($fieldArr as $val) {
			$res[] = $val->toArray();
		}

		if (isset($this->orther_field_list[$newTypes])) {
			foreach ($this->orther_field_list[$newTypes] as $val) {
				$res[] = $val;
			}
		}
		
		return array_column($res, null, 'field');
	}

	/**
	 * [getIndexField 列表展示字段]
	 * @author Michael_xu 
	 * @param types 分类	
	 * @param is_data 1 取数据时
	 */
	public function getIndexField($types, $user_id, $is_data = '')
	{	
		$userFieldModel = new \app\admin\model\UserField();
		$userFieldData = $userFieldModel->getConfig($types, $user_id);
		$userFieldData = $userFieldData ? json_decode($userFieldData, true) : [];
		$othor_un_field = array_column($this->orther_field_list[$types], 'field');
		$unField = array_merge(['pool_day','business-check','call'], $othor_un_field);
		$where = [];
		if ($userFieldData) {
			$dataList = [];
			foreach ($userFieldData as $k=>$v) {
				if (empty($v['is_hide']) && !in_array($k,$unField)) {
					$dataList[] = $k;
				}
			}
		} else {
			$where['types'] = ['in',['',$types]];
			$dataList = $this->where($where)->column('field');		
		}
		$sysField = [];
		$newList = $dataList;
		if ($is_data == 1) {
			switch ($types) {
				case 'crm_leads' : 
					$sysField = ['leads_id','create_time','update_time','create_user_id','owner_user_id'];
					break;
				case 'crm_business' :
					$newList = [];
					foreach ($dataList as $v) {
						$newList[] = 'business.'.$v;
					}			
					$sysField = ['business.business_id','business.customer_id','business.create_time','business.update_time','business.status_id','business.type_id','business.create_user_id','business.owner_user_id','business.ro_user_id','business.rw_user_id'];
					break;
				case 'crm_customer' : 
					$sysField = ['customer_id','deal_time','create_time','update_time','is_lock','deal_status','create_user_id','owner_user_id','ro_user_id','rw_user_id', 'address', 'detail_address'];
					break;	
				case 'crm_contacts' : 
					$newList = [];
					foreach ($dataList as $v) {
						$newList[] = 'contacts.'.$v;
					}			
					$sysField = ['contacts.contacts_id','contacts.customer_id','contacts.create_time','contacts.update_time','contacts.create_user_id','contacts.owner_user_id'];
					break;
				case 'crm_contract' : 
					$newList = [];
					foreach ($dataList as $v) {
						$newList[] = 'contract.'.$v;
					}
					$sysField = ['contract.contract_id','contract.create_time','contract.update_time','contract.create_user_id','contract.owner_user_id','contract.check_status'];
					break;
				case 'crm_receivables' : 
					$newList = [];
					foreach ($dataList as $v) {
						$newList[] = 'receivables.'.$v;
					}
					$sysField = ['receivables.receivables_id','receivables.customer_id','receivables.contract_id','receivables.plan_id','receivables.create_time','receivables.update_time','receivables.create_user_id','receivables.owner_user_id','receivables.check_status'];
					break;	
				case 'crm_product' : 
					$newList = [];
					foreach ($dataList as $v) {
						$newList[] = 'product.'.$v;
					}
					$sysField = ['product.product_id','product.category_id','product.create_time','product.update_time','product.create_user_id','product.owner_user_id'];
					break;													
			}	
			$listArr = $sysField ? array_unique(array_merge($newList,$sysField)) : $dataList;		
		} else {
			$listArr = $dataList;
		}
		
		$type = array_pop(explode('_', $types));

		if (isset($this->orther_field_list[$types])) {
			foreach ($this->orther_field_list[$types] as $val) {
				if (in_array($type . '.' . $val['field'], $listArr)) {
					unset($listArr[array_search($type . '.' . $val['field'], $listArr)]);
				}
			} 
		}
		return $listArr ? : [];
	}	

	/**
	 * [checkFieldPer 判断权限]
	 * @author Michael_xu 
	 * @param types 分类	
	 */	
	public function checkFieldPer($module, $controller, $action, $user_id = '')
	{
		$userModel = new \app\admin\model\User();
		if (!checkPerByAction($module, $controller, $action)) return false;
		if ($user_id && !in_array($user_id, $userModel->getUserByPer($module, $controller, $action))) return false;
		return true;
	}

	/**
	 * [getField 获取字段属性]
	 * @author Michael_xu 
	 * @param types 分类	
	 */	
	public function getField($param)
	{
		$types = $param['types'];
		$unFormType = $param['unFormType'];
        if (!in_array($types, $this->types_arr)) {
            return resultArray(['error' => '参数错误']);
        }
        $field_arr = [];
        //模拟自定义字段返回
        switch ($types) {
            case 'admin_user' : $field_arr = \app\hrm\model\Userdet::getField(); break; 
            default : 
                $data = [];
                $data['types'] = $types;
                $data['user_id'] = $param['user_id'];
                if ($unFormType) $data['form_type'] = array('not in',$unFormType);
                $field_arr = $this->fieldSearch($data);
                break;
        }
        return $field_arr;	
	}
	
	/**
	 * 自定义字段验重
	 * @param $field 字段名, $val 值, $id 排除当前数据验重, $types 需要查询的模块名
	 * @author 
	 * @return 
	 */
	public function getValidate($field, $val, $id, $types) {
		$val = trim($val);
		if (!$val) {
			$this->error = '验证内容不能为空';
			return false;
		}
		if (!$field) {
			$this->error = '数据验证错误，请联系管理员！';
			return false;
		}
		if (!in_array($types, $this->types_arr)) {
			$this->error = '参数错误！';
			return false;
		}

		$field_info = db('admin_field')->where(['types' => $types,'field' => $field])->find();
		if (!$field_info) {
			$this->error = '数据验证错误，请联系管理员！';
			return false;
		}

		$dataModel = '';
		switch ($types) {
			case 'crm_leads' : $dataModel = new \app\crm\model\Leads(); break;
			case 'crm_customer' : $dataModel = new \app\crm\model\Customer(); break;
			case 'crm_contacts' : $dataModel = new \app\crm\model\Contacts(); break;
			case 'crm_business' : $dataModel = new \app\crm\model\Business(); break;
			case 'crm_product' : $dataModel = new \app\crm\model\Product(); break;
			case 'crm_contract' : $dataModel = new \app\crm\model\Contract(); break;
			case 'crm_receivables' : $dataModel = new \app\crm\model\Receivables(); break;
		}

		$where = [];
		$where[$field] = ['eq', $val];
		if ($id) {
			//为编辑时的验重
			$where[$dataModel->getpk()] = ['neq', $id];
		}
		if ($res = $dataModel->where($where)->find()) {
			$this->error = '该数据已存在，请修改后提交！';
			return false;
		}
		return true;
	}

	/**
	 * [getFieldByFormType 根据字段类型获取字段数组]
	 * @author Michael_xu 
	 * @param types 分类	
	 */	
	public function getFieldByFormType($types, $form_type)
	{
		$fieldArr = $this->where(['types' => $types,'form_type' => $form_type])->column('field');
		return $fieldArr ? : [];
	}

	/**
	 * [getFormValueByField 格式化表格字段类型值]
	 * @author Michael_xu 
	 * @param $field 字段名
	 * @param $value 字段值
	 */	
	public function getFormValueByField($field, $value)
	{
		$formValue = db('admin_field')->where(['field' => $field])->value('form_value');
		$formValue = sort_select($formValue,'order_id',1); //二维数组排序
		$field = [];
		foreach ($formValue as $k => $v) {
			$field[] = $v['field'];
		}
		$data = [];
		foreach ($value as $k => $v) {
			foreach ($field as $key => $val) {
				$data[$k][$val] = $v['value'];
			}
		}
		return $data;
	}

	/**
	 * 根据form_type处理数据
	 * @author lee
	 */
	public function getValueByFormtype($val, $form_type)
	{
		$userModel = new \app\admin\model\User();
		$structureModel = new \app\admin\model\Structure();
		switch ($form_type) {
			case 'datetime' : $val = $val > 0 ? date('Y-m-d H:i:s', $val) : ''; break;
			case 'user' : $val = ArrayToString($userModel->getUserNameByArr($val)); break;
			case 'structure' : $val = ArrayToString($structureModel->getStructureNameByArr($val)); break;
			case 'customer' : $val = db('crm_customer')->where(['customer_id' => $val])->value('name'); break;
			case 'business' : $val = db('crm_business')->where(['business_id' => $val])->value('name'); break;
			case 'category' : $val = db('crm_product_category')->where(['category_id' => $val])->value('name'); break;
			case 'business_type' : $val = db('crm_business_type')->where(['type_id' => $val])->value('name'); break;
			case 'business_status' : $val = db('crm_business_status')->where(['status_id' => $val])->value('name'); break;
		}
		return $val;
	}

	/**
	 * [getIndexFieldList 列表展示字段]
	 * @author Michael_xu 
	 * @param types 分类	
	 */
	public function getIndexFieldList($types, $user_id)
	{
		$fieldArr = $this->getIndexField($types, $user_id);
		$fieldList = db('admin_field')->where(['field' => array('in',$fieldArr)])->where('types',['eq',$types],['eq',''],'or')->order('order_id asc')->select();
		return $fieldList ? : [];
	}

	/**
	 * [getArrayField 数组类型字段]
	 * @author Michael_xu 
	 * @param types 分类	
	 */	
	public function getArrayField($types)
	{
		$arrayFormType = ['structure','user','checkbox','file'];
		$arrFieldAtt = db('admin_field')->where(['types' => $types,'form_type' => ['in',$arrayFormType]])->column('field');
		return $arrFieldAtt ? : [];		
	}

	/**
     * 字段对照关系处理
     * @author Michael_xu
     * @param  $types 分类
     * @param  $data 数据
     * @return 
     */	
   	public function getRelevantData($types , $data = [])
   	{
		$types_arr = ['crm_leads'];
		if (!in_array($types, $types_arr)) {
			$this->error = '参数错误';
			return false;
		}
		if (!$data) return $data;
		$list = $this->where(['types' => $types,'relevant' => ['neq','']])->field('field,relevant')->select();
		if (!$list) return $data;
		$newData = $data;
		foreach ($list as $k => $v) {
			$newData[$v['relevant']] = $data[$v['field']];
		}
		return $newData ? : [];
   	}	

	/**
     * 字段排序
     * @author Michael_xu
     * @param types 自定义字段分类
     * @param prefix 自定义字段前缀
     * @param field 自定义字段
     * @param order 排序规则
     * @return 
     */	
    public function getOrderByFormtype($types, $prefix, $field, $order_type)
    {
    	$form_type = $this->where(['types' => $types,'field' => $field])->value('form_type');
    	// if (!$form_type) {
    	// 	$this->error = '参数错误';
    	// 	return false;
		// }
		$temp_field = $field;
    	$field = $prefix ? $prefix.'.'.$field : $field;
    	switch ($form_type) {
    		case 'textarea' : 
    		case 'radio' : 
    		case 'select' : 
    		case 'checkbox' : 
    		case 'address' : 
    			$order = 'convert('.$field.' using gbk) '.trim($order_type);
    			break;
    		default : $order = $field.' '.$order_type;
    			break;
		}
		if (isset($this->orther_field_list[$types])) {
			foreach ($this->orther_field_list[$types] as $val) {
				// $res[] = $val;
				// $order
				$temp = trim($prefix.'.'.$val['field'], '.');
				if ($temp == $field) {
					$order = str_replace($temp, $val['field'], $order);
				}
			}
		}
    	return $order;
    }   	
}