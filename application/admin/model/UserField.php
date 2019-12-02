<?php
// +----------------------------------------------------------------------
// | Description: 字段列表配置
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com
// +----------------------------------------------------------------------
namespace app\admin\model;

use think\Config;
use think\Db;
use think\Model;

class UserField extends Model
{
    protected $name = 'admin_user_field';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $autoWriteTimestamp = true;    

	protected $type = [
        'datas'    =>  'array',
    ];

    /**
     * 设置配置信息
     * @author Michael_xu
     * @param types 分类
     * @param param 参数
     * @param id 主键ID
     * @return                            
     */
    public function updateConfig($types, $param, $id = '')
    {
        if (!is_array($param['value']) || ($param['hide_value'] && !is_array($param['hide_value']))) {
            $this->error = '参数错误';
            return false;
        }
        $data = [];
        if ($id) {
            $resInfo = $this->where(['id' => $id])->find();
        } else {
            $resInfo = $this->where(['types' => $types,'user_id' => $param['user_id']])->find();
        }
        if (!$resInfo) {
            $data['types'] = $types;
            $data['user_id'] = $param['user_id'];
        }         
        //处理value
        $valueData = [];
        //展示列
        if ($param['value']) {
            foreach ($param['value'] as $k=>$v) {
                $valueData[$v['field']]['width'] = $v['width'];
                $valueData[$v['field']]['is_hide'] = 0;
            }
        }
        
        //隐藏列
        if ($param['hide_value']) {
            foreach ($param['hide_value'] as $k=>$v) {
                $valueData[$v['field']]['width'] = $v['width'];
                $valueData[$v['field']]['is_hide'] = 1;
            }
        }
        
        $data['datas'] = $valueData;
        if ($resInfo) {
            $data['id'] = $resInfo['id'];
            $res = $this->update($data);
        } else {
            $res = $this->save($data);
        }
        if ($res == false) {
            $this->error = '设置出错';
            return false;            
        }
        return true;
    }

    /**
     * 配置信息详情
     * @author Michael_xu
     * @param types 分类
     * @return                            
     */
    public function getConfig($types, $user_id)
    {
        $data = $this->where(['types' => $types,'user_id' => $user_id])->value('datas');
        return $data ? : [];
    }

    /**
     * 配置列宽度
     * @author Michael_xu
     * @param types 分类
     * @param field 字段
     * @param width 宽度
     * @return                            
     */
    public function setColumnWidth($types, $field, $width, $user_id)
    {
        $info = db('admin_user_field')->where(['types' => $types,'user_id' => $user_id])->find();
        if ($info) {
            $datas = json_decode($info['datas'], true);
            $datas[$field]['width'] = $width;
            $datas = json_encode($datas);
            $res = $this->where(['id' => $info['id']])->update(['datas' => $datas]);       
        } else {
            $newTypes = $types;
            if ($types == 'crm_customer_pool') $newTypes = 'crm_customer';
            $fieldArr = db('admin_field')->where(['types' => ['in',['',$newTypes]]])->field('field,name')->order('types desc,order_id asc')->select();
            $value = [];
            foreach ($fieldArr as $k=>$v) {
                $field_width = '';
                if ($field == $v['field']) {
                    $field_width = $width;
                }
                $value[$v['field']]['width'] = $field_width;
                $value[$v['field']]['is_hide'] = 0;
                $value[$v['field']]['field'] = $v['field'];
            }
            $param['value'] = $value;
            $param['hide_value'] = [];
            $param['user_id'] = $user_id;
            $res = $this->updateConfig($types, $param);
        }
        if (!$res) {
            $this->error = '设置出错，请重试！';
            return false;
        }
        return true;
    }

    /**
     * 列表数据
     * @author Michael_xu
     * @param types 分类
     * @return                            
     */
    public function getDataList($types, $user_id)
    {
        $fieldArr = (new Field)->getFieldList($types);
        $fieldList = [];
        $field_list = [];
        foreach ($fieldArr as $k=>$v) {
            $fieldList[] = $v['field'];
            $field_list[$v['field']]['name'] = $v['name'];
            $fieldArr[$k]['width'] = '';
        }
       
        //已设置字段
        $value = $this->where(['types' => $types,'user_id' => $user_id])->value('datas');
        $value = $value ? json_decode($value, true) : [];

        $valueList = [];
        $value_list = []; //显示列
        
        $hideList = [];
        $hide_list = []; //隐藏列

        if ($value) {
            $a = 0;
            $b = 0;
            foreach ($value as $k=>$v) {
                if (empty($v['is_hide'])) {
                    $valueList[] = $k;
                    $value_list[$a]['field'] = $k; 
                    $value_list[$a]['width'] = $v['width']; 
                    $value_list[$a]['name'] = $field_list[$k]['name']; 
                    $a++;
                } else {
                    $hideList[] = $k;
                    $hide_list[$b]['field'] = $k;
                    $hide_list[$b]['width'] = $v['width'];  
                    $hide_list[$b]['name'] = $field_list[$k]['name'];
                    $b++;
                }
            }
            $diffList = $valueList ? array_diff($fieldList, $valueList) : $fieldList;
            //隐藏的列(新增的字段数据)
            $hideList = $hideList ? array_diff($diffList,$hideList) : $diffList;
            foreach ($hideList as $k=>$v) {
                $hide_list[$b]['field'] = $v;
                $hide_list[$b]['width'] = '';
                $hide_list[$b]['name'] = $field_list[$v]['name'];
                $b++;
            }    
        } else {
            $value_list = array_values($fieldArr);
            $hide_list = [];
        }

        $data = [];
        $data['value_list'] = $value_list ? : []; //展示列
        $data['hide_list'] = $hide_list ? : []; //隐藏列
        return $data ? : [];
    }    
}