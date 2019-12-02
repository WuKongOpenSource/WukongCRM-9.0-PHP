<?php
// +----------------------------------------------------------------------
// | Description: 商机
// +----------------------------------------------------------------------
// | Author: Michael_xu | gengxiaoxu@5kcrm.com 
// +----------------------------------------------------------------------

namespace app\crm\controller;

use app\admin\controller\ApiCommon;
use think\Hook;
use think\Request;
use think\Db;

class Business extends ApiCommon
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
            'allow'=>['statuslist','advance','product']            
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());        
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
    } 

    /**
     * 商机列表
     * @author Michael_xu
     * @return
     */
    public function index()
    {
        $businessModel = model('Business');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id']; 
        $data = $businessModel->getDataList($param);       
        return resultArray(['data' => $data]);
    }

    /**
     * 添加商机
     * @author Michael_xu
     * @param 
     * @return 
     */
    public function save()
    {
        $businessModel = model('Business');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['create_user_id'] = $userInfo['id'];
        $param['owner_user_id'] = $userInfo['id'];

        if ($businessModel->createData($param)) {
            return resultArray(['data' => '添加成功']);
        } else {
            return resultArray(['error' => $businessModel->getError()]);
        }
    }

    /**
     * 商机详情
     * @author Michael_xu
     * @param  
     * @return
     */
    public function read()
    {
        $businessModel = model('Business');
        $businessStatusModel = model('BusinessStatus');
        $userModel = new \app\admin\model\User();
        $param = $this->param;
        $userInfo = $this->userInfo;
        $data = $businessModel->getDataById($param['id']);
        //判断权限
        $auth_user_ids = $userModel->getUserByPer('crm', 'business', 'read');
        //读权限
        $roPre = $userModel->rwPre($userInfo['id'], $data['ro_user_id'], $data['rw_user_id'], 'read');
        $rwPre = $userModel->rwPre($userInfo['id'], $data['ro_user_id'], $data['rw_user_id'], 'update');        
        if (!in_array($data['owner_user_id'],$auth_user_ids) && !$rwPre && !$roPre) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }        
        //商机状态组
        $data['status_list'] = $businessStatusModel->getDataById($data['type_id']);
        $data['lose_reason'] = Db::name('CrmBusinessLog')
            ->where(['business_id' => $data['business_id']])
            ->order(['id' => 'DESC'])
            ->value('remark');
        if (!$data) {
            return resultArray(['error' => $businessModel->getError()]);
        }
        return resultArray(['data' => $data]);
    }

    /**
     * 编辑商机
     * @author Michael_xu
     * @param 
     * @return
     */
    public function update()
    {    
        $businessModel = model('Business');
        $userModel = new \app\admin\model\User();
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];
        //判断权限
        $data = $businessModel->getDataById($param['id']);
        $auth_user_ids = $userModel->getUserByPer('crm', 'business', 'update');
        //读写权限
        $rwPre = $userModel->rwPre($userInfo['id'], $data['ro_user_id'], $data['rw_user_id'], 'update');        
        if (!in_array($data['owner_user_id'],$auth_user_ids) && !$rwPre) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }        
        if ($businessModel->updateDataById($param, $param['id'])) {
            return resultArray(['data' => '编辑成功']);
        } else {
            return resultArray(['error' => $businessModel->getError()]);
        }       
    }

    /**
     * 删除商机（逻辑删）
     * @author Michael_xu
     * @param 
     * @return
     */
    public function delete()
    {
        $param = $this->param; 
        $businessModel = model('Business');
        $recordModel = new \app\admin\model\Record();
        $fileModel = new \app\admin\model\File();
        $actionRecordModel = new \app\admin\model\ActionRecord();    
        if (!is_array($param['id'])) {
            $business_id[] = $param['id'];
        } else {
            $business_id = $param['id'];
        }
        $delIds = [];
        $errorMessage = [];

        //数据权限判断
        $userModel = new \app\admin\model\User();
        $auth_user_ids = $userModel->getUserByPer('crm', 'business', 'delete');
        foreach ($business_id as $k=>$v) {
            $isDel = true;
            //数据详情
            $data = $businessModel->getDataById($v);
            if (!$data) {
                $isDel = false;
                $errorMessage[] = 'id为'.$v.'的商机删除失败,错误原因：'.$businessModel->getError();
                continue;
            }
            if (!in_array($data['owner_user_id'],$auth_user_ids)) {
                $isDel = false;
                $errorMessage[] = '名称为'.$data['name'].'的商机删除失败,错误原因：无权操作';
                continue;
            }
            $delIds[] = $v;            
        }
        if ($delIds) {
            $data = $businessModel->delDatas($delIds);
            if (!$data) {
                return resultArray(['error' => $businessModel->getError()]);
            }
            //删除跟进记录
            $recordModel->delDataByTypes('crm_business',$delIds); 
            //删除关联附件
            $fileModel->delRFileByModule('crm_business',$delIds);
            //删除关联操作记录
            $actionRecordModel->delDataById(['types'=>'crm_business','action_id'=>$delIds]);           
            actionLog($delIds,'','','');         
        }
        if ($errorMessage) {
            return resultArray(['error' => $errorMessage]);
        } else {
            return resultArray(['data' => '删除成功']);
        }        
    }

    /**
     * 符合条件的商机状态组
     * @author Michael_xu
     * @param 
     * @return
     */
    public function statusList()
    {
        $businessStatusModel = model('BusinessStatus');
        $key = 'BI_queryCache_StatusList_Data';
        $list = cache($key);
        if (!$list) {
            $userInfo = $this->userInfo;
            $list = db('crm_business_type')
                    ->field(['name', 'status', 'structure_id', 'type_id'])
                    ->where(['structure_id' => ['like','%,'.$userInfo['structure_id'].',%'],'status' => 1])
                    ->whereOr('structure_id','')
                    ->select(); 
            foreach ($list as $k=>$v) {
                $list[$k]['statusList'] = $businessStatusModel->getDataList($v['type_id']); 
            }
            cache($key, $list, true);
        }

        return resultArray(['data' => $list]);
    }          
    
    /**
     * 商机转移
     * @author Michael_xu
     * @param owner_user_id 变更负责人
     * @param is_remove 1移出，2转为团队成员
     * @param type 权限 1只读2读写
     * @return
     */ 
    public function transfer()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $businessModel = model('Business');
        $settingModel = model('Setting');
        $userModel = new \app\admin\model\User();
        $authIds = $userModel->getUserByPer(); //权限范围的user_id

        if (!$param['owner_user_id']) {
            return resultArray(['error' => '变更负责人不能为空']);
        }
        if (!$param['business_id'] || !is_array($param['business_id'])) {
            return resultArray(['error' => '请选择需要转移的商机']); 
        }
        
        $is_remove = $param['is_remove'] == 2 ? : 1;
        $type = $param['type'] == 2 ? : 1;
        
        $data = [];
        $data['owner_user_id'] = $param['owner_user_id'];
        $data['update_time'] = time();

        $ownerUserName = $userModel->getUserNameById($param['owner_user_id']);
        $errorMessage = [];
        foreach ($param['business_id'] as $business_id) {
            $businessInfo = $businessModel->getDataById($business_id);

            if (!$businessInfo) {
                $errorMessage[] = '名称:为《'.$businessInfo['name'].'》的商机转移失败，错误原因：数据不存在；';
                continue;
            }
            //权限判断
            if (!in_array($businessInfo['owner_user_id'],$authIds)) {
                $errorMessage[] = $businessInfo['name'].'"转移失败，错误原因：无权限；';
                continue;
            }
            $resBusiness = db('crm_business')->where(['business_id' => $business_id])->update($data);
            if (!$resBusiness) {
                $errorMessage[] = $businessInfo['name'].'"转移失败，错误原因：数据出错；';
                continue;
            }
            //修改记录
            updateActionLog($userInfo['id'], 'crm_business', $business_id, '', '', '将商机转移给：'.$ownerUserName);       
        }
        if (!$errorMessage) {
            return resultArray(['data' => '转移成功']);
        } else {
            return resultArray(['error' => $errorMessage]);
        }
    } 

    /**
     * 相关产品
     * @author Michael_xu
     * @param 
     * @return
     */ 
    public function product()
    {
        $productModel = model('Product');
        $userModel = new \app\admin\model\User();
        $param = $this->param;
        $userInfo = $this->userInfo;
        if (!$param['business_id']) {
            return resultArray(['error' => '参数错误']);
        }
        $businessInfo = db('crm_business')->where(['business_id' => $param['business_id']])->find();     
        //判断权限
        $auth_user_ids = $userModel->getUserByPer('crm', 'business', 'read');
        //读写权限
        $roPre = $userModel->rwPre($userInfo['id'], $businessInfo['ro_user_id'], $businessInfo['rw_user_id'], 'read');
        $rwPre = $userModel->rwPre($userInfo['id'], $businessInfo['ro_user_id'], $businessInfo['rw_user_id'], 'update');
        if (!in_array($businessInfo['owner_user_id'],$auth_user_ids) && !$roPre && !$rwPre) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }
        $dataList = db('crm_business_product')->where(['business_id' => $param['business_id']])->select();
        foreach ($dataList as $k=>$v) {
            $where = [];
            $where['product_id'] = $v['product_id'];
            $productInfo = db('crm_product')->where($where)->field('name,category_id')->find();
            $category_name = db('crm_product_category')->where(['category_id' => $productInfo['category_id']])->value('name');
            $dataList[$k]['name'] = $productInfo['name'] ? : '';
            $dataList[$k]['category_id_info'] = $category_name ? : '';
        }
        $list['list'] = $dataList ? : [];
        $list['total_price'] = $businessInfo['total_price'] ? : '0.00';
        $list['discount_rate'] = $businessInfo['discount_rate'] ? : '0.00';        
        return resultArray(['data' => $list]);
    }  

    /**
     * 商机状态推进
     * @author Michael_xu
     * @param business_id 商机ID
     * @param status_id 推进商机状态ID
     * @return
     */ 
    public function advance()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $userModel = new \app\admin\model\User();
        $is_end = $param['is_end'] ? : 0; //1赢单2输单3无效
        if (!$param['business_id']) {
            return resultArray(['error' => '参数错误']);
        }
        $businessInfo = db('crm_business')->where(['business_id' => $param['business_id']])->find();
        if ($businessInfo['is_end']) {
            return resultArray(['error' => '已结束，不能推进']);
        }
        //判断权限
        $auth_user_ids = $userModel->getUserByPer('crm', 'business', 'update');
        //读写权限
        $rwPre = $userModel->rwPre($userInfo['id'], $businessInfo['ro_user_id'], $businessInfo['rw_user_id'], 'update');
        if (!in_array($businessInfo['owner_user_id'],$auth_user_ids) && !$rwPre) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }

        $status_id = $param['status_id'] ? : $businessInfo['status_id'];
        $statusInfo = db('crm_business_status')->where(['type_id' => $businessInfo['type_id'],'status_id' => $status_id])->find();
        if (!$statusInfo && !$is_end) {
            return resultArray(['error' => '参数错误']);
        }
        $data = [];
        $data['update_time'] = time();
        $data['is_end'] = $is_end;
        if ($is_end) {
            $status_id = $is_end;
        }
        $data['status_id'] = $status_id;
        $data['status_time'] = time();        
        $res = db('crm_business')->where(['business_id' => $param['business_id']])->update($data);
        if (!$res) {
            return resultArray(['error' => '推进失败，请重试']);
        } else {
			//推进记录添加
            $temp['status_id'] = $status_id ? : 0;
			$temp['is_end'] = $is_end ? : 0;
			$temp['business_id'] = $param['business_id'];
			$temp['create_time'] = time();
            $temp['owner_user_id'] = $userInfo['id'];
			$temp['remark'] = $param['remark'] ? : '';
			Db::name('CrmBusinessLog')->insert($temp);
            return resultArray(['data' => '推进成功']);
        }
    }
    
    /**
     * 商机导出
     * @author Michael_xu
     * @param 
     * @return
     */
    public function excelExport()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];
        if ($param['business_id']) {
           $param['business_id'] = ['condition' => 'in','value' => $param['business_id'],'form_type' => 'text','name' => ''];
           $param['is_excel'] = 1;
        }        

        $excelModel = new \app\admin\model\Excel();
        // 导出的字段列表
        $fieldModel = new \app\admin\model\Field();
        $field_list = $fieldModel->getIndexFieldConfig('crm_business', $userInfo['id']);
        // 文件名
        $file_name = '5kcrm_business_'.date('Ymd');

        $model = model('Business');
        $temp_file = $param['temp_file'];
        unset($param['temp_file']);
        $page = $param['page'] ?: 1;
        unset($param['page']);
        unset($param['export_queue_index']);
        return $excelModel->batchExportCsv($file_name, $temp_file, $field_list, $page, function($page, $limit) use ($model, $param, $field_list) {
            $param['page'] = $page;
            $param['limit'] = $limit;
            $data = $model->getDataList($param);
            $data['list'] = $model->exportHandle($data['list'], $field_list, 'business');
            return $data;
        });
    } 
}
