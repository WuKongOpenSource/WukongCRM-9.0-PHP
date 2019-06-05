<?php
// +----------------------------------------------------------------------
// | Description: 客户
// +----------------------------------------------------------------------
// | Author: Michael_xu | gengxiaoxu@5kcrm.com 
// +----------------------------------------------------------------------

namespace app\crm\controller;

use app\admin\controller\ApiCommon;
use think\Hook;
use think\Request;
use think\Db;
use PHPExcel_IOFactory;
use PHPExcel_Cell;
use PHPExcel;

class Customer extends ApiCommon
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
            'permission'=>['exceldownload','setfollow'],
            'allow'=>['']            
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());        
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        } else {
            $param = Request::instance()->param();          
            $this->param = $param;            
        }
    } 

    /**
     * 客户列表
     * @author Michael_xu
     * @return
     */
    public function index()
    {
        $customerModel = model('Customer');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id']; 
        $data = $customerModel->getDataList($param);       
        return resultArray(['data' => $data]);
    }

    /**
     * 客户公海(没有负责人或已经到期)
     * @author Michael_xu
     * @return
     */
    public function pool()
    {
        $param = $this->param;
        $data = model('Customer')->getDataList($param);
        return resultArray(['data' => $data]);
    }    

    /**
     * 添加客户
     * @author Michael_xu
     * @param 
     * @return
     */
    public function save()
    {
        $customerModel = model('Customer');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['create_user_id'] = $userInfo['id'];
        $param['owner_user_id'] = $userInfo['id'];
        if ($res = $customerModel->createData($param)) {
            return resultArray(['data' => $res]);
        } else {
            return resultArray(['error' => $customerModel->getError()]);
        }
    }

    /**
     * 客户详情
     * @author Michael_xu
     * @param  
     * @return
     */
    public function read()
    {
        $customerModel = model('Customer');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $data = $customerModel->getDataById($param['id']);
        if (!$data) {
            return resultArray(['error' => $customerModel->getError()]);
        }
        //数据权限判断
        $userModel = new \app\admin\model\User();
        $auth_user_ids = $userModel->getUserByPer('crm', 'customer', 'read');
        //读权限
        $roPre = $userModel->rwPre($userInfo['id'], $data['ro_user_id'], $data['rw_user_id'], 'read');
        $rwPre = $userModel->rwPre($userInfo['id'], $data['ro_user_id'], $data['rw_user_id'], 'update');
        //判断是否客户池数据
        $wherePool = $customerModel->getWhereByPool();
        $resPool = db('crm_customer')->alias('customer')->where(['customer_id' => $param['id']])->where($wherePool)->find();
        if (!$resPool && !in_array($data['owner_user_id'],$auth_user_ids) && !$roPre && !$rwPre) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }
        return resultArray(['data' => $data]);
    }

    /**
     * 编辑客户
     * @author Michael_xu
     * @param 
     * @return
     */
    public function update()
    {
        $customerModel = model('Customer');
        $param = $this->param;
        $userInfo = $this->userInfo;
        //数据详情
        $data = $customerModel->getDataById($param['id']);
        if (!$data) {
            return resultArray(['error' => $customerModel->getError()]);
        }
        //数据权限判断
        $userModel = new \app\admin\model\User();
        $auth_user_ids = $userModel->getUserByPer('crm', 'customer', 'update');
        //读写权限
        $rwPre = $userModel->rwPre($userInfo['id'], $data['ro_user_id'], $data['rw_user_id'], 'update');     
        //判断是否客户池数据
        $wherePool = $customerModel->getWhereByPool();
        $resPool = db('crm_customer')->alias('customer')->where(['customer_id' => $param['id']])->where($wherePool)->find();
        if (!$resPool && !in_array($data['owner_user_id'],$auth_user_ids) && !$rwPre) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }        

        $param['user_id'] = $userInfo['id'];
        if ($customerModel->updateDataById($param, $param['id'])) {
            return resultArray(['data' => '编辑成功']);
        } else {
            return resultArray(['error' => $customerModel->getError()]);
        }       
    }

    /**
     * 删除客户（逻辑删）
     * @author Michael_xu
     * @param 
     * @return
     */
    public function delete()
    {
        $customerModel = model('Customer');
        $param = $this->param;
        if (!is_array($param['id'])) {
            $customer_id[] = $param['id'];
        } else {
            $customer_id = $param['id'];
        }
        $delIds = [];
        $errorMessage = [];

        //数据权限判断
        $userModel = new \app\admin\model\User();
        $auth_user_ids = $userModel->getUserByPer('crm', 'customer', 'delete');
        //判断是否客户池数据(客户池数据只有管理员可以删)
        $adminId = $userModel->getAdminId();
        $wherePool = $customerModel->getWhereByPool();
        foreach ($customer_id as $k=>$v) {
            $isDel = true;
            //数据详情
            $data = db('crm_customer')->where(['customer_id' => $v])->find();
            if (!$data) {
                $isDel = false;
                $errorMessage[] = 'id为'.$v.'的客户删除失败,错误原因：'.$customerModel->getError();
                continue;
            }
            $resPool = db('crm_customer')->alias('customer')->where(['customer_id' => $v])->where($wherePool)->find();
            if (!$resPool && !in_array($data['owner_user_id'],$auth_user_ids)) {
                $isDel = false;
                $errorMessage[] = '名称为'.$data['name'].'的客户删除失败,错误原因：无权操作';
                continue;
            }
            //公海
            if ($resPool && !in_array($data['owner_user_id'],$adminId)) {
                $isDel = false;
                $errorMessage[] = '名称为'.$data['name'].'的客户删除失败,错误原因：无权操作';
                continue;
            }
            //有商机、合同、联系人则不能删除 
            $resBusiness = db('crm_business')->where(['customer_id' => $v])->find();
            $resContract = db('crm_contract')->where(['customer_id' => $v])->find();
            $resContacts = db('crm_contacts')->where(['customer_id' => $v])->find();
            if ($resBusiness) {
                $isDel = false;
                $errorMessage[] = '名称为'.$data['name'].'的客户删除失败,错误原因：客户下存在商机，不能删除';
                continue;
            }    
            if ($resContract) {
                $isDel = false;
                $errorMessage[] = '名称为'.$data['name'].'的客户删除失败,错误原因：客户下存在合同，不能删除';
                continue;
            }   
            if ($resContacts) {
                $isDel = false;
                $errorMessage[] = '名称为'.$data['name'].'的客户删除失败,错误原因：客户下存在联系人，不能删除';
                continue;
            } 
            $delIds[] = $v;            
        }
        if ($delIds) {
            $data = $customerModel->delDatas($delIds);
            if (!$data) {
                return resultArray(['error' => $customerModel->getError()]);
            }
            //删除操作记录
            $actionRecordModel = new \app\admin\model\ActionRecord();
            $res = $actionRecordModel->delDataById(['types' => 'crm_customer','action_id' => $delIds]);                    
        }
        if ($errorMessage) {
            return resultArray(['error' => $errorMessage]);
        } else {
            return resultArray(['data' => '删除成功']);
        }
    }

    /**
     * 客户转移
     * @author Michael_xu
     * @param owner_user_id 变更负责人
     * @param is_remove 1移出，2转为团队成员
     * @param types business,contract 相关模块
     * @param type 权限 1只读2读写
     * @return
     */ 
    public function transfer()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $customerModel = model('Customer');
        $businessModel = model('Business');
        $contractModel = model('Contract');
        $contactsModel = model('Contacts');
        $settingModel = model('Setting');      
        $userModel = new \app\admin\model\User();

        if (!$param['owner_user_id']) {
            return resultArray(['error' => '变更负责人不能为空']);
        }
        if (!$param['customer_id'] || !is_array($param['customer_id'])) {
            return resultArray(['error' => '请选择需要转移的客户']); 
        }   
        $is_remove = ($param['is_remove'] == 2) ? 2 : 1;
        $type = $param['type'] == 2 ? : 1;
        $types = $param['types'] ? : [];
        
        $data = [];
        $data['owner_user_id'] = $param['owner_user_id'];
        $data['update_time'] = time();
        $data['follow'] = '待跟进';

        $ownerUserName = $userModel->getUserNameById($param['owner_user_id']);
        $errorMessage = [];
        foreach ($param['customer_id'] as $customer_id) {
            $customerInfo = db('crm_customer')->where(['customer_id' => $customer_id])->find();

            if (!$customerInfo) {
                $errorMessage[] = 'id:为'.$customer_id.'的客户转移失败，错误原因：数据不存在；';
                continue;
            }
            $resCustomer = true;
            //权限判断
            if (!$customerModel->checkData($customer_id, $userInfo['id'])) {
                $errorMessage[] = $customerInfo['name'].'转移失败，错误原因：无权限；';
                continue;
            }
            $resCustomer = db('crm_customer')->where(['customer_id' => $customer_id])->update($data);
            if (!$resCustomer) {
                $errorMessage[] = $customerInfo['name'].'转移失败，错误原因：数据出错；';
                continue;
            } 
            
            if (in_array('crm_contacts',$types)) {
                $contactsIds = [];
                $contactsIds = db('crm_contacts')->where(['customer_id' => $customer_id])->column('contacts_id');
                if ($contactsIds) {
                    $resContacts = $contactsModel->transferDataById($contactsIds, $param['owner_user_id'], $type, $is_remove);
                    if ($resContacts !== true) {
                        $errorMessage[] = $resContacts;
                        continue;                        
                    }
                }                
            }            

            //商机、合同转移
            if (in_array('crm_business',$types)) {
                $businessIds = [];
                $businessIds = db('crm_business')->where(['customer_id' => $customer_id])->column('business_id');
                if ($businessIds) {
                    $resBusiness = $businessModel->transferDataById($businessIds, $param['owner_user_id'], $type, $is_remove);
                    if ($resBusiness !== true) {
                        $errorMessage = $errorMessage ? array_merge($errorMessage,$resBusiness) : $resBusiness;
                        continue;                        
                    }                    
                }
            }

            if (in_array('crm_contract',$types)) {
                $contractIds = [];
                $contractIds = db('crm_contract')->where(['customer_id' => $customer_id])->column('contract_id');
                if ($contractIds) {
                    $resContract = $contractModel->transferDataById($contractIds, $param['owner_user_id'], $type, $is_remove);
                    if ($resContract !== true) {
                        $errorMessage = $errorMessage ? array_merge($errorMessage,$resContract) : $resContract;
                        continue;                        
                    }
                }                
            }

            $teamData = [];
            $teamData['type'] = $type; //权限 1只读2读写
            $teamData['user_id'] = [$customerInfo['owner_user_id']]; //协作人
            $teamData['types'] = 'crm_customer'; //类型
            $teamData['types_id'] = $customer_id; //类型ID
            $teamData['is_del'] = ($is_remove == 1) ? 1 : '';
            $res = $settingModel->createTeamData($teamData);          
            //修改记录
            updateActionLog($userInfo['id'], 'crm_customer', $customer_id, '', '', '将客户转移给：'.$ownerUserName);        
        }
        if (!$errorMessage) {
            return resultArray(['data' => '转移成功']);
        } else {
            return resultArray(['error' => $errorMessage]);
        }
    } 

    /**
     * 客户放入公海(负责人至为0)
     * @author Michael_xu
     * @param 
     * @return
     */ 
    public function putInPool()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $customerModel = model('Customer');
        $settingModel = new \app\crm\model\Setting();
        if (!$param['customer_id'] || !is_array($param['customer_id'])) {
            return resultArray(['error' => '请选择需要放入公海的客户']); 
        }
        $data = [];
        $data['owner_user_id'] = 0;
        $data['is_lock'] = 0;
        $data['update_time'] = time();
        $errorMessage = [];
        foreach ($param['customer_id'] as $customer_id) {
            $customerInfo = [];
            $customerInfo = db('crm_customer')->where(['customer_id' => $customer_id])->find();
            if (!$customerInfo) {
                $errorMessage[] = 'id:为'.$customer_id.'的客户放入公海失败，错误原因：数据不存在；';
                continue;
            }
            //权限判断
            if (!$customerModel->checkData($customer_id, $userInfo['id'])) {
                $errorMessage[] = '"'.$customerInfo['name'].'"放入公海失败，错误原因：无权限';
                continue;
            }
            $resCustomer = db('crm_customer')->where(['customer_id' => $customer_id])->update($data);
            if (!$resCustomer) {
                $errorMessage[] = '"'.$customerInfo['name'].'"放入公海失败，错误原因：数据出错；';
                continue;
            }
            //修改记录
            updateActionLog($userInfo['id'], 'crm_customer', $customer_id, '', '', '将客户放入公海');
            //将原负责人转为团队普通成员
            $teamParam = [];
            $teamParam['user_id'] = $customerInfo['owner_user_id'];
            $teamParam['types'] = 'crm_customer';
            $teamParam['types_id'] = $customer_id;
            $teamParam['type'] = 1; //只读
            $teamParam['owner_user_id'] = $userInfo['id'];
            $teamParam['is_del'] = 3;
            $settingModel->createTeamData($teamParam);              
        }
        if (!$errorMessage) {
            return resultArray(['data' => '操作成功']);
        } else {
            return resultArray(['error' => $errorMessage]);
        }
    }

    /**
     * 客户锁定，解锁
     * @author Michael_xu
     * @param is_lock 1锁定，2解锁
     * @return
     */     
    public function lock()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $customerModel = model('Customer');
        $is_lock = ((int)$param['is_lock'] == 2) ? (int)$param['is_lock'] : 1;
        $lock_name = ($is_lock == 2) ? '解锁' : '锁定';
        if (!$param['customer_id'] || !is_array($param['customer_id'])) {
            return resultArray(['error' => '请选择需要'.$lock_name.'的客户']); 
        }
        $data = [];
        $data['is_lock'] = ($is_lock == 1) ? $is_lock : 0;
        $data['update_time'] = time();        
        $errorMessage = [];
        foreach ($param['customer_id'] as $customer_id) {
            $customerInfo = [];
            $customerInfo = $customerModel->getDataById($customer_id);
            if (!$customerInfo) {
                $errorMessage[] = 'id:为'.$customer_id.'的客户'.$lock_name.'失败，错误原因：数据不存在；';
                continue;
            }
            //权限判断
            if (!$customerModel->checkData($customer_id, $userInfo['id'])) {
                $errorMessage[] = $customerInfo['name'].$lock_name.'失败，错误原因：无权限';
                continue;
            }
            $resCustomer = db('crm_customer')->where(['customer_id' => $customer_id])->update($data);
            if (!$resCustomer) {
                $errorMessage[] = $customerInfo['name'].$lock_name.'失败，错误原因：数据出错；';
            }
            //修改记录
            updateActionLog($userInfo['id'], 'crm_customer', $customer_id, '', '', '将客户'.$lock_name);
        }
        if (!$errorMessage) {
            return resultArray(['data' => '操作成功']);
        } else {
            return resultArray(['error' => $errorMessage]);
        }  
    }

    /**
     * 客户领取
     * @author Michael_xu
     * @param 
     * @return
     */
    public function receive()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $customerModel = model('Customer');

        $customer_ids = $param['customer_id'];
        if (!$customer_ids || !is_array($customer_ids)) {
            return resultArray(['error' => '请选择需要领取的客户']); 
        }
        $errorMessage = [];
        $wherePool = $customerModel->getWhereByPool();
        foreach ($customer_ids as $k=>$v) {
            $dataName = db('crm_customer')->where(['customer_id' => $v])->value('name');
            //判断是否是客户池数据
            $resPool = db('crm_customer')->alias('customer')->where(['customer_id' => $v])->where($wherePool)->find();
            if (!$resPool) {
                $errorMessage[] = '客户《'.$dataName.'》领取失败，错误原因：非公海数据无权操作；';
                continue;
            }
            $data = [];
            $data['owner_user_id'] = $userInfo['id'];
            $data['update_time'] = time();
            $data['deal_time'] = time();
            $data['follow'] = '待跟进';
            $resCustomer = db('crm_customer')->where(['customer_id' => $v])->update($data);
            if (!$resCustomer) {
                $errorMessage[] = '客户《'.$dataName.'》领取失败，错误原因：数据出错；';
                continue;
            }
            //修改记录
            updateActionLog($userInfo['id'], 'crm_customer', $v, '', '', '领取了客户');                           
        }
        if (!$errorMessage) {
            return resultArray(['data' => '领取成功']);
        } else {
            return resultArray(['error' => $errorMessage]);
        }        
    } 

    /**
     * 客户分配
     * @author Michael_xu
     * @param 
     * @return
     */
    public function distribute()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $customerModel = model('Customer');
        $userModel = new \app\admin\model\User();

        $customer_ids = $param['customer_id'];
        $owner_user_id = $param['owner_user_id'];
        if (!$customer_ids || !is_array($customer_ids)) {
            return resultArray(['error' => '请选择需要分配的客户']); 
        }
        if (!$owner_user_id) {
            return resultArray(['error' => '请选择分配人']); 
        }
        $ownerUserName = $userModel->getUserNameById($owner_user_id);

        $errorMessage = [];
        $wherePool = $customerModel->getWhereByPool();
        foreach ($customer_ids as $k=>$v) {
            $dataName = db('crm_customer')->where(['customer_id' => $v])->value('name');
            //判断是否是客户池数据
            $resPool = db('crm_customer')->alias('customer')->where(['customer_id' => $v])->where($wherePool)->find();
            if (!$resPool) {
                $errorMessage[] = '客户《'.$dataName.'》分配失败，错误原因：非公海数据无权操作；';
                continue;
            }
            $data = [];
            $data['owner_user_id'] = $owner_user_id;
            $data['update_time'] = time();
            $data['deal_time'] = time();
            $data['follow'] = '待跟进';
            $resCustomer = db('crm_customer')->where(['customer_id' => $v])->update($data);
            if (!$resCustomer) {
                $errorMessage[] = '客户《'.$dataName.'》分配失败，错误原因：数据出错；';
            }
            //修改记录
            updateActionLog($userInfo['id'], 'crm_customer', $v, '', '', '将客户分配给：'.$ownerUserName);
            //站内信
            $send_user_id[] = $owner_user_id;
            $sendContent = $userInfo['realname'].'将客户《'.$dataName.'》,分配给您';
            if ($send_user_id) {
                sendMessage($send_user_id, $sendContent, $v, 1);
            }            
        }
        if (!$errorMessage) {
            return resultArray(['data' => '分配成功']);
        } else {
            return resultArray(['error' => $errorMessage]);
        }        
    }   

    /**
     * 客户导出
     * @author Michael_xu
     * @param 
     * @return
     */
    public function excelExport()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];
        if ($param['customer_id']) {
           $param['customer_id'] = ['condition' => 'in','value' => $param['customer_id'],'form_type' => 'text','name' => ''];
           $param['is_excel'] = 1;
        }
        $excelModel = new \app\admin\model\Excel();
        // 导出的字段列表
        $fieldModel = new \app\admin\model\Field();
        $field_list = $fieldModel->getIndexFieldList('crm_customer', $userInfo['id']);
        // 文件名
        $file_name = '5kcrm_customer_'.date('Ymd');
        $param['pageType'] = 'all'; 
        $excelModel->exportCsv($file_name, $field_list, function($list) use ($param){
            $list = model('Customer')->getDataList($param);
            return $list;
        });
    }

    /**
     * 客户导入模板下载
     * @author Michael_xu
     * @param 
     * @return
     */
    public function excelDownload()
    {
        $param = $this->param;
        $excelModel = new \app\admin\model\Excel();

        // 导出的字段列表
        $fieldModel = new \app\admin\model\Field();
        $fieldParam['types'] = 'crm_customer'; 
        $fieldParam['action'] = 'excel'; 
        $customer_field_list = $fieldModel->field($fieldParam);
        $contactsParam['types'] = 'crm_contacts'; 
        $contactsParam['field'] = array('neq','customer_id'); 
        $contacts_field_list = $fieldModel->getDataList($contactsParam);       
        // $contacts_field_list = [];
        //实例化主文件
        vendor("phpexcel.PHPExcel");
        vendor("phpexcel.PHPExcel.Writer.Excel5");
        vendor("phpexcel.PHPExcel.Writer.Excel2007");
        vendor("phpexcel.PHPExcel.IOFactory"); 

        $objPHPExcel = new \phpexcel();
        $objWriter = new \PHPExcel_Writer_Excel5($objPHPExcel);
        $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);

        //设置属性
        $objProps = $objPHPExcel->getProperties();
        $objProps->setCreator("5kcrm");
        $objProps->setLastModifiedBy("5kcrm");
        $objProps->setTitle("5kcrm");
        $objProps->setSubject("5kcrm data");
        $objProps->setDescription("5kcrm data");
        $objProps->setKeywords("5kcrm data");
        $objProps->setCategory("5kcrm");
        $objPHPExcel->setActiveSheetIndex(0);
        $objActSheet = $objPHPExcel->getActiveSheet();
        $objActSheet->setTitle('悟空软件客户导入模板'.date('Y-m-d',time()));

        //填充边框
        $styleArray = [
            'borders'=>[
                'outline'=>[
                    'style'=>\PHPExcel_Style_Border::BORDER_THICK, //设置边框
                    'color' => ['argb' => '#F0F8FF'], //设置颜色
                ],
            ],
        ];

        $row = 1000;
        $k = 0;
        foreach ($customer_field_list as $field) {
            $objActSheet->getColumnDimension($excelModel->stringFromColumnIndex($k))->setWidth(20); //设置单元格宽度
            if ($field['form_type'] == 'address') {
                for ($a=0; $a<=3; $a++){
                    $address = array('所在省','所在市','所在县','街道信息');//如果是所在省的话
                    // $objActSheet->getStyle($excelModel->stringFromColumnIndex($k).'2')->applyFromArray($styleArray);//填充样式
                    $objActSheet->setCellValue($excelModel->stringFromColumnIndex($k).'2', $address[$a]);
                    $k++;
                }
            } else {
                if ($field['form_type'] == 'select' || $field['form_type'] == 'checkbox' || $field['form_type'] == 'radio') {
                    // $setting = $field['setting'] ? explode(chr(10), $field['setting']) : [];
                    $setting = $field['setting'] ? : [];
                    $select_value = implode(',',$setting);
                    if ($select_value) {
                        for ($c=3; $c<=70; $c++) {
                            //数据有效性 start
                            $objValidation = $objActSheet->getCell($excelModel->stringFromColumnIndex($k).$c)->getDataValidation(); //这一句为要设置数据有效性的单元格
                            $objValidation -> setType(\PHPExcel_Cell_DataValidation::TYPE_LIST)  
                               -> setErrorStyle(\PHPExcel_Cell_DataValidation::STYLE_INFORMATION)  
                               -> setAllowBlank(false)  
                               -> setShowInputMessage(true)  
                               -> setShowErrorMessage(true)  
                               -> setShowDropDown(true)  
                               -> setErrorTitle('输入的值有误')  
                               -> setError('您输入的值不在下拉框列表内.')  
                               -> setPromptTitle('--请选择--')  
                               -> setFormula1('"'.$select_value.'"');
                            //数据有效性  end            
                        }
                    }
                }
                //检查该字段若必填，加上"*"
                $field['name'] = sign_required($field['is_null'], $field['name']);
                // $objActSheet->getStyle($excelModel->stringFromColumnIndex($k).'2')->applyFromArray($styleArray);//填充样式
                $objActSheet->setCellValue($excelModel->stringFromColumnIndex($k).'2', $field['name']);
                $k++;
            }
        }
        $max_customer_column = $excelModel->stringFromColumnIndex($k-1);
        $mark_customer = $excelModel->stringFromColumnIndex($k);   

        $contacts_start_mark = $excelModel->stringFromColumnIndex($k+1).'1';
        //联系人相关
        if ($contacts_field_list) {
            foreach ($contacts_field_list as $field) {
                $objActSheet->getColumnDimension($excelModel->stringFromColumnIndex($k))->setWidth(20); //设置单元格宽度
                if ($field['form_type'] == 'address') {
                     for ($a=0; $a<=3; $a++){
                         $address = array('所在省','所在市','所在县','街道信息');//如果是所在省的话
                         // $objActSheet->getStyle($excelModel->stringFromColumnIndex($k).'2')->applyFromArray($styleArray);//填充样式
                         $objActSheet->setCellValue($excelModel->stringFromColumnIndex($k).'2', $address[$a]);
                         $k++;
                     }
                } elseif ($field['form_type'] != 'customer') {
                    if ($field['form_type'] == 'select' || $field['form_type'] == 'checkbox' || $field['form_type'] == 'radio') {
                        $setting = $field['setting'] ? : [];
                        $select_value = implode(',',$setting);
                        if ($select_value) {
                            for ($c=3; $c<=70; $c++) {                  
                                //数据有效性 start
                                $objValidation = $objActSheet->getCell($excelModel->stringFromColumnIndex($k).'3')->getDataValidation(); //这一句为要设置数据有效性的单元格
                                $objValidation -> setType(\PHPExcel_Cell_DataValidation::TYPE_LIST)  
                                   -> setErrorStyle(\PHPExcel_Cell_DataValidation::STYLE_INFORMATION)  
                                   -> setAllowBlank(false)  
                                   -> setShowInputMessage(true)  
                                   -> setShowErrorMessage(true)  
                                   -> setShowDropDown(true)  
                                   -> setErrorTitle('输入的值有误')  
                                   -> setError('您输入的值不在下拉框列表内.')  
                                   -> setPromptTitle('--请选择--')  
                                   -> setFormula1('"'.$select_value.'"');
                                //数据有效性  end
                            }
                        }
                    }                
                     //检查该字段若必填，加上"*"
                    $field['name'] = sign_required($field['is_null'], $field['name']);
                    // $objActSheet->getStyle($excelModel->stringFromColumnIndex($k).'2')->applyFromArray($styleArray);//填充样式
                    $objActSheet->setCellValue($excelModel->stringFromColumnIndex($k).'2', $field['name']);
                    $k++;
                }
            }            
        }
        
        $mark_contacts = $excelModel->stringFromColumnIndex($k-1);

        $objActSheet->mergeCells('A1:'.$max_customer_column.'1');
        $objActSheet->mergeCells($mark_customer.'1:'.$mark_contacts.'1');
        $objActSheet->getStyle('A1:'.$mark_customer.'1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER); //水平居中
        $objActSheet->getStyle('A1:'.$mark_customer.'1')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER); //垂直居中
        $objActSheet->getRowDimension(1)->setRowHeight(28); //设置行高
        $objActSheet->getStyle('A1')->getFont()->getColor()->setARGB('FFFF0000');
        $objActSheet->getStyle('A1')->getAlignment()->setWrapText(true);
        //设置单元格格式范围的字体、字体大小、加粗
        $objActSheet->getStyle('A1:'.$mark_contacts.'1')->getFont()->setName("微软雅黑")->setSize(13)->getColor()->setARGB('#000000');
        //给单元格填充背景色
        $objActSheet->getStyle('A1:'.$max_customer_column.'1')->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('#ff9900');
        $objActSheet->getStyle($mark_customer.'1:'.$mark_contacts.'1')->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('#FFEBCD');
        $objActSheet->getStyle($contacts_start_mark)->getAlignment()->setWrapText(true);
        $content = '客户信息（*代表必填项）';
        $objActSheet->setCellValue('A1', $content);
        $objActSheet->getStyle('A1:'.$max_customer_column.'1')->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);         
        $objActSheet->getStyle('A1')->getBorders()->getRight()->getColor()->setARGB('#000000');        
        $objActSheet->setCellValue($mark_customer.'1', '联系人信息（*代表必填项）');
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        ob_end_clean();
        header("Content-Type: application/vnd.ms-excel;");
        header("Content-Disposition:attachment;filename=客户信息导入模板".date('Y-m-d',time()).".xls");
        header("Pragma:no-cache");
        header("Expires:0");
        $objWriter->save('php://output');
    }

    /**
     * 客户数据导入
     * @author Michael_xu
     * @param 
     * @return
     */
    public function excelImport()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $excelModel = new \app\admin\model\Excel();
        $param['create_user_id'] = $userInfo['id'];
        $param['owner_user_id'] = $param['owner_user_id'] ? : 0;
        // $param['deal_time'] = time();
        // $param['deal_status'] = '未成交';
        $param['types'] = 'crm_customer';
        $file = request()->file('file');
        $res = $excelModel->importExcel($file, $param);
        if (!$res) {
            return resultArray(['error'=>$excelModel->getError()]);
        }
        return resultArray(['data'=>'导入成功']);
    }

    /**
     * 客户标记为已跟进
     * @author Michael_xu
     * @param 
     * @return
     */
    public function setFollow(){
        $param = $this->param;
        $customerIds = $param['id'] ? : [];
        if (!$customerIds || !is_array($customerIds)) {
            return resultArray(['error'=>'参数错误']);
        }
        $data['follow'] = '已跟进';
        $data['update_time'] = time();
        $res = db('crm_customer')->where(['customer_id' => ['in',$customerIds]])->update($data);
        if (!$res) {
            return resultArray(['error'=>'操作失败，请重试']);
        }
        return resultArray(['data'=>'跟进成功']);        
    }

    /**
     * 置顶 / 取消置顶
     * @return [type] [description]
     */
    public function top()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['create_role_id'] = $userInfo['id'];
        $param['top_time'] = time();

        $top_id = Db::name('crm_top')->where(['module' => ['eq',$param['module']],'create_role_id' => ['eq',$userInfo['id']],'module_id' => ['eq',$param['module_id']]])->column('top_id');
        if ($top_id) {
            if ($res = Db::name('crm_top')->where('top_id',$top_id[0])->update($param)) {
                return resultArray(['data' => $res]);
            } else {
                return resultArray(['error' => Db::name('crm_top')->getError()]);
            }
        } else {
            if ($res =  Db::name('crm_top')->data($param)->insert()) {
                return resultArray(['data' => $res]);
            } else {
                return resultArray(['error' => $customerModel->getError()]);
            }
        }
    }

    /**
     * 客户公海导出
     * @author Michael_xu
     * @param 
     * @return
     */
    public function poolExcelExport()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];
        if ($param['customer_id']) {
           $param['customer_id'] = ['condition' => 'in','value' => $param['customer_id'],'form_type' => 'text','name' => ''];
           $param['is_excel'] = 1;
        }
        $excelModel = new \app\admin\model\Excel();
        // 导出的字段列表
        $fieldModel = new \app\admin\model\Field();
        $field_list = $fieldModel->getIndexFieldList('crm_customer', $userInfo['id']);
        // 文件名
        $file_name = '5kcrm_customer_'.date('Ymd');
        $param['pageType'] = 'all'; 
        $param['action'] = 'pool';
        $excelModel->exportCsv($file_name, $field_list, function($list) use ($param){
            $list = model('Customer')->getDataList($param);
            return $list;
        });
    }      
}