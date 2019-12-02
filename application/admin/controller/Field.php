<?php
// +----------------------------------------------------------------------
// | Description: 自定义字段
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com 
// +----------------------------------------------------------------------

namespace app\admin\controller;

use think\Hook;
use think\Request;
use think\Db;
use app\admin\model\User as UserModel;

class Field extends ApiCommon
{
    /**
     * 用于判断权限
     * @permission 无限制
     * @allow 登录用户可访问
     * @other 其他根据系统设置
    **/    
    public function _initialize()
    {
        $action = [
            'permission'=>[''],
            'allow'=>['index','getfield','update','read','config','validates','configindex','columnwidth','uniquefield']
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());        
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }        
    }
    
    /**
     * 自定义字段列表
     */
    public function index()
    {  
        //权限判断
        if (!checkPerByAction('admin', 'crm', 'field')) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }         
        $param = $this->param;
        $types_arr = [
            '0' => ['types' => 'crm_leads','name' => '线索管理'],
            '1' => ['types' => 'crm_customer','name' => '客户管理'],
            '2' => ['types' => 'crm_contacts','name' => '联系人管理'],
            '3' => ['types' => 'crm_product','name' => '产品管理'],
            '4' => ['types' => 'crm_business','name' => '商机管理'],
            '5' => ['types' => 'crm_contract','name' => '合同管理'],
            '6' => ['types' => 'crm_receivables','name' => '回款管理'],
        ];
        $examine_types_arr = [];    
        switch ($param['type']) {
            case 'crm' : $typesArr = $types_arr; break;
            case 'examine' : $typesArr = $examine_types_arr; break;
            default : $typesArr = $types_arr; break;
        }

        foreach ($typesArr as $k=>$v) {
            $typesArr[$k]['update_time'] = db('admin_field')->where(['types' => $v['types']])->max('update_time');
        }  
        return resultArray(['data' => $typesArr]);
    }    

    /**
     * 自定义字段数据
     */
    public function read()
    {        
        $fieldModel = model('Field');
        $param = $this->param;
        $data = $fieldModel->getDataList($param);    
        if ($data === false) {
            return resultArray(['error' => $fieldModel->getError()]);
        }  
        return resultArray(['data' => $data]);
    }

    /**
     * 自定义字段创建
     */
    public function update()
    {
        //权限判断
        if (!checkPerByAction('admin', 'crm', 'field')) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }        
        $fieldModel = model('Field');
        $param = $this->param;
        $types = $data['types'] = $param['types'];
        $types_id = $param['types_id'] ? : 0;
        //系统审批类型暂不支持编辑
        if ($types == 'oa_examine' && $types_id < 7) {
            return resultArray(['error' => '系统审批类型暂不支持编辑']);
        }
        $data = $param['data'];
        $saveParam = [];
        $updateParam = [];
        $delParam = [];
        $i = 0;
        foreach ($data as $k=>$v) {
            $i++;
            // $v = json_decode($v,true);
            if ($v['field_id']) {
                if ($v['is_deleted'] == '1') {
                    $delParam[] = $v['field_id']; //删除
                } else {
                    //编辑
                    $updateParam[$k] = $v;
                    $updateParam[$k]['order_id'] = $i;                    
                }
            } else {
                $saveParam[$k] = $v;
                $saveParam[$k]['order_id'] = $i;
                $saveParam[$k]['types_id'] = $types_id;
            }
        }
        $errorMessage = [];
        //新增
        if ($saveParam) {
            if (!$data = $fieldModel->createData($types, $saveParam)) {
                $errorMessage[] = $fieldModel->getError();
            }            
        }
        //编辑
        if ($updateParam) {
            if (!$data = $fieldModel->updateDataById($updateParam)) {
                $errorMessage[] = $fieldModel->getError();
            }
        }
        //删除
        if ($delParam) {
            if (!$data = $fieldModel->delDataById($delParam)) {
                $errorMessage[] = $fieldModel->getError();
            }
        }
        
        if ($errorMessage) {
            return resultArray(['error' => $errorMessage]);
        } else {
            return resultArray(['data' => '修改成功']);
        }
    }

    /**
     * 自定义字段数据获取
     * @param module 模块
     * @param controller 控制器
     * @param action 操作
     * @param action_id  操作ID
     */ 
    public function getField()
    {
        $fieldModel = model('Field');
        $userModel = model('User');
        $param = $this->param;
        $module = trim($param['module']);
        $controller = trim($param['controller']);
        $action = trim($param['action']);        
        
        if (!$module || !$controller || !$action) {
            return resultArray(['error' => '参数错误']);
        }
        //判断权限
        $userInfo = $this->userInfo;
        $user_id = $userInfo['id'];
        $types = $param['types'];
        $types_id = $param['types_id'] ? : '';
        $dataInfo = [];
        if ($action == 'read' || $action == 'update') {
            //获取详情数据
            if (($param['action'] == 'update' || $param['action'] == 'read') && $param['action_id']) {
                switch ($param['types']) {
                    case 'crm_customer' : 
                        $customerModel = new \app\crm\model\Customer();
                        $dataInfo = $customerModel->getDataById(intval($param['action_id']));
                        //判断权限
                        $auth_user_ids = $userModel->getUserByPer('crm', 'customer', $param['action']);
                        //读写权限
                        $roPre = $userModel->rwPre($user_id, $dataInfo['ro_user_id'], $dataInfo['rw_user_id'], 'read');
                        $rwPre = $userModel->rwPre($user_id, $dataInfo['ro_user_id'], $dataInfo['rw_user_id'], 'update');
                        //判断是否客户池数据
                        $wherePool = $customerModel->getWhereByPool();
                        $resPool = db('crm_customer')->alias('customer')->where(['customer_id' => $param['action_id']])->where($wherePool)->find();
                        if (!$resPool && !in_array($dataInfo['owner_user_id'],$auth_user_ids) && !$roPre && !$rwPre) {
                            header('Content-Type:application/json; charset=utf-8');
                            exit(json_encode(['code'=>102,'error'=>'无权操作']));
                        }
                        break;
                    case 'crm_leads' : 
                        $leadsModel = new \app\crm\model\Leads();
                        $dataInfo = $leadsModel->getDataById(intval($param['action_id']));
                        //判断权限
                        $auth_user_ids = $userModel->getUserByPer('crm', 'leads', $param['action']);
                        if (!in_array($dataInfo['owner_user_id'],$auth_user_ids)) {
                            header('Content-Type:application/json; charset=utf-8');
                            exit(json_encode(['code'=>102,'error'=>'无权操作']));
                        }                        
                        break;  
                    case 'crm_contacts' : 
                        $contactsModel = new \app\crm\model\Contacts();
                        $dataInfo = $contactsModel->getDataById(intval($param['action_id']));
                        //判断权限
                        $auth_user_ids = $userModel->getUserByPer('crm', 'contacts', $param['action']);
                        if (!in_array($dataInfo['owner_user_id'],$auth_user_ids)) {
                            header('Content-Type:application/json; charset=utf-8');
                            exit(json_encode(['code'=>102,'error'=>'无权操作']));
                        }                          
                        break;
                    case 'crm_business' : 
                        $businessModel = new \app\crm\model\Business();
                        $dataInfo = $businessModel->getDataById(intval($param['action_id']));
                        //判断权限
                        $auth_user_ids = $userModel->getUserByPer('crm', 'business', $param['action']);
                        //读写权限
                        $roPre = $userModel->rwPre($user_id, $dataInfo['ro_user_id'], $dataInfo['rw_user_id'], 'read');
                        $rwPre = $userModel->rwPre($user_id, $dataInfo['ro_user_id'], $dataInfo['rw_user_id'], 'update');
                        if (!in_array($dataInfo['owner_user_id'],$auth_user_ids) && !$roPre && !$rwPre) {
                            header('Content-Type:application/json; charset=utf-8');
                            exit(json_encode(['code'=>102,'error'=>'无权操作']));
                        }                          
                        break;
                    case 'crm_contract' : 
                        $contractModel = new \app\crm\model\Contract();
                        $dataInfo = $contractModel->getDataById(intval($param['action_id']));
                        //判断权限
                        $auth_user_ids = $userModel->getUserByPer('crm', 'contract', $param['action']);
                        //读写权限
                        $roPre = $userModel->rwPre($user_id, $dataInfo['ro_user_id'], $dataInfo['rw_user_id'], 'read');
                        $rwPre = $userModel->rwPre($user_id, $dataInfo['ro_user_id'], $dataInfo['rw_user_id'], 'update');
                        if (!in_array($dataInfo['owner_user_id'],$auth_user_ids) && !$roPre && !$rwPre) {
                            header('Content-Type:application/json; charset=utf-8');
                            exit(json_encode(['code'=>102,'error'=>'无权操作']));
                        }                         
                        break;
                    case 'crm_product' : 
                        $productModel = new \app\crm\model\Product();
                        $dataInfo = $productModel->getDataById(intval($param['action_id']));
                        break;
                    case 'crm_receivables' : 
                        $receivablesModel = new \app\crm\model\Receivables();
                        $dataInfo = $receivablesModel->getDataById(intval($param['action_id']));
                        //判断权限
                        $auth_user_ids = $userModel->getUserByPer('crm', 'receivables', $param['action']);
                        if (!in_array($dataInfo['owner_user_id'],$auth_user_ids)) {
                            header('Content-Type:application/json; charset=utf-8');
                            exit(json_encode(['code'=>102,'error'=>'无权操作']));
                        }                         
                        break;
                    case 'crm_receivables_plan' : 
                        $receivablesPlanModel = new \app\crm\model\ReceivablesPlan();
                        $dataInfo = $receivablesPlanModel->getDataById(intval($param['action_id']));
                        break; 
                    case 'oa_examine' :
                        $examineModel = new \app\oa\model\Examine();  
                        $examineFlowModel = new \app\admin\model\ExamineFlow();  
                        $dataInfo = $examineModel->getDataById(intval($param['action_id']));
                        $adminIds = $userModel->getAdminId(); //管理员
                        $checkUserIds = $examineFlowModel->getUserByFlow($dataInfo['flow_id'], $dataInfo['create_user_id'], $dataInfo['check_user_id']);
                        if (((int)$dataInfo['create_user_id'] != $user_id && !in_array($user_id,$adminIds) && !in_array($user_id,$checkUserIds))) {
                            header('Content-Type:application/json; charset=utf-8');
                            exit(json_encode(['code'=>102,'error'=>'无权操作']));
                        }                        
                        break;
                } 
            }
        }
        $param['user_id'] = $user_id;
        $action_id = $param['action_id'] ? : '';
        $data = $fieldModel->field($param, $dataInfo) ? : [];
        return resultArray(['data' => $data]);   
    }

    /**
     * 自定义字段数据验重
     * @param 
     */ 
    public function validates()
    {
        $param = $this->param;
        $fieldModel = model('Field');
        if (is_array($param['val'])) {
            //多选类型暂不验证
            return resultArray(['data' => '验证通过']);
        }
        $res = $fieldModel->getValidate(trim($param['field']), trim($param['val']), intval($param['id']), trim($param['types']));
        if (!$res) {
            return resultArray(['error' => $fieldModel->getError()]);
        }
        return resultArray(['data' => '验证通过']);
    }

    /**
     * 自定义字段列表设置（排序、展示、列宽度）
     * @param types 分类
     * @param value 值
     */
    public function config()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];
        $userFieldModel = model('UserField');
        $res = $userFieldModel->updateConfig($param['types'], $param);
        if (!$res) {
            return resultArray(['error' => $userFieldModel->getError()]);
        }
        return resultArray(['data' => '设置成功']);
    } 

    /**
     * 自定义字段列宽度设置
     * @param types 分类
     * @param field 字段名
     * @param width 列宽度
     */
    public function columnWidth()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $userFieldModel = model('UserField');
        $width = $param['width'] > 10 ? $param['width'] : '';
        $unField = array('pool_day');
        if (!in_array($param['field'],$unField)) {
            $res = $userFieldModel->setColumnWidth($param['types'], $param['field'], $width, $userInfo['id']);
            if (!$res) {
                return resultArray(['error' => $userFieldModel->getError()]);
            }            
        }
        return resultArray(['data' => '设置成功']);
    }

    /**
     * 自定义字段列表设置数据
     * @param types 分类
     * @param value 值
     */
    public function configIndex()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $userFieldModel = model('UserField');
        $res = $userFieldModel->getDataList($param['types'], $userInfo['id']);
        if (!$res) {
            return resultArray(['error' => $userFieldModel->getError()]);
        }
        return resultArray(['data' => $res]);
    }

    /**
     * 自定义验重字段
     * @param types 分类
     * @param
     */
    public function uniqueField()
    {
        $param = $this->param;
        if ($param['types'] == 'crm_user') {
            $list = array_filter(UserModel::$import_field_list, function ($val) {
                return $val['is_unique'] == 1;
            });
            $list = array_column($list, 'name');
        } else {
            $list = db('admin_field')->where(['types' => $param['types'],'is_unique' => 1])->column('name');
        }
        $list = $list ? implode(',',$list) : '无';
        return resultArray(['data' => $list]);
    }       
}
