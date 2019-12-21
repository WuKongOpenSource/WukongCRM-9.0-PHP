<?php
// +----------------------------------------------------------------------
// | Description: 产品
// +----------------------------------------------------------------------
// | Author: Michael_xu | gengxiaoxu@5kcrm.com 
// +----------------------------------------------------------------------

namespace app\crm\controller;

use app\admin\controller\ApiCommon;
use app\crm\model\Product as ProductModel;
use app\admin\model\File as FileModel;
use think\Hook;
use think\Request;

class Product extends ApiCommon
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
            'permission'=>['exceldownload'],
            'allow'=>['']            
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());        
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
    } 

    /**
     * 产品列表
     * @author Michael_xu
     * @return
     */
    public function index()
    {
        $productModel = model('Product');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];        
        $data = $productModel->getDataList($param);       
        return resultArray(['data' => $data]);
    }

    /**
     * 添加产品
     * @author Michael_xu
     * @param  
     * @return
     */
    public function save()
    {
        $productModel = model('Product');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['create_user_id'] = $userInfo['id'];
        $param['owner_user_id'] = $userInfo['id'];

        if ($productModel->createData($param)) {
            return resultArray(['data' => '添加成功']);
        } else {
            return resultArray(['error' => $productModel->getError()]);
        }
    }

    /**
     * 产品详情
     * @author Michael_xu
     * @param  
     * @return
     */
    public function read()
    {
        $productModel = model('Product');
        $param = $this->param;
        $data = $productModel->getDataById($param['id']);
        if (!$data) {
            return resultArray(['error' => $productModel->getError()]);
        }
        return resultArray(['data' => $data]);
    }

    /**
     * 编辑产品
     * @author Michael_xu
     * @param 
     * @return
     */
    public function update()
    {    
        $productModel = model('Product');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];

        if ($productModel->updateDataById($param, $param['id'])) {
            return resultArray(['data' => '编辑成功']);
        } else {
            return resultArray(['error' => $productModel->getError()]);
        }      
    } 

    /**
     * 产品上架、下架
     * @author Michael_xu
     * @param 
     * @return
     */     
    public function status()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $data = [];
        $data['status'] = ($param['status'] == '上架') ? '上架' : '下架'; 
        $data['update_time'] = time();
        if (!is_array($param['id'])) {
            $productIds[] = $param['id'];
        } else {
            $productIds = $param['id'] ? : [];
        }
        if (!$productIds) {
            return resultArray(['error' => '参数错误']);
        }
        $res = db('crm_product')->where(['product_id' => ['in',$productIds]])->update($data);
        if (!$res) {
            return resultArray(['error' => '操作失败']);
        }
        return resultArray(['data' => $data['status'].'成功']);
    }

    /**
     * 产品导入模板
     * @author Michael_xu
     * @param string $save_path 本地保存路径     用于错误数据导出，在 Admin\Model\Excel::batchImportData()调用
     * @return
     */ 
    public function excelDownload($save_path = '') 
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $excelModel = new \app\admin\model\Excel();

        // 导出的字段列表
        $fieldModel = new \app\admin\model\Field();
        $fieldParam['types'] = 'crm_product'; 
        $fieldParam['action'] = 'excel'; 
        $field_list = $fieldModel->field($fieldParam);
        $excelModel->excelImportDownload($field_list, 'crm_product', $save_path);
    }  

    /**
     * 产品导出
     * @author Michael_xu
     * @param 
     * @return
     */
    public function excelExport()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];
        if ($param['product_id']) {
           $param['product_id'] = ['condition' => 'in','value' => $param['product_id'],'form_type' => 'text','name' => ''];
        }        

        $excelModel = new \app\admin\model\Excel();
        // 导出的字段列表
        $fieldModel = new \app\admin\model\Field();
        $field_list = $fieldModel->getIndexFieldConfig('crm_product', $userInfo['id']);
        // 文件名
        $file_name = '5kcrm_product_'.date('Ymd');
        
        $model = model('Product');
        $temp_file = $param['temp_file'];
        unset($param['temp_file']);
        $page = $param['page'] ?: 1;
        unset($param['page']);
        unset($param['export_queue_index']);
        return $excelModel->batchExportCsv($file_name, $temp_file, $field_list, $page, function($page, $limit) use ($model, $param, $field_list) {
            $param['page'] = $page;
            $param['limit'] = $limit;
            $data = $model->getDataList($param);
            $data['list'] = $model->exportHandle($data['list'], $field_list, 'product');
            return $data;
        });
    } 

    /**
     * 产品数据导入
     * @author Michael_xu
     * @param 
     * @return
     */
    public function excelImport()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $excelModel = new \app\admin\model\Excel();
        $param['types'] = 'crm_product';
        $param['create_user_id'] = $userInfo['id'];
        $param['owner_user_id'] = $param['owner_user_id'] ? : $userInfo['id'];
        $file = request()->file('file');
        // $res = $excelModel->importExcel($file, $param, $this);
        $res = $excelModel->batchImportData($file, $param, $this);
        if (!$res) {
            return resultArray(['error'=>$excelModel->getError()]);
        }
        return resultArray(['data' => $excelModel->getError()]);
    }

    /**
     * 删除
     *
     * @return void
     * @author Ymob
     * @datetime 2019-10-24 13:44:31
     */
    public function delete()
    {
        $id_list = (array) $this->param['id'];
        $id_list = array_map('intval', $id_list);

        // 错误信息
        $error_message = [];
        // 过滤后的ID
        $id_list_filter = ProductModel::where(['product_id' => ['IN', $id_list]])->column('product_id');

        $diff = array_diff($id_list, $id_list_filter);

        if (!empty($diff)) {
            foreach ($diff as $key => $val) {
                $error_message[] = sprintf('ID为 %d 的客户删除失败，错误原因：数据不存在或已删除。', $val);
            }
            array_unshift($error_message, '数据已更新，刷新页面后重试！');
            return resultArray(['error' => $error_message]);
        }

        // 开启事务
        ProductModel::startTrans();

        // 软删除数据
        $res = ProductModel::destroy(['product_id' => ['IN', $id_list]]);

        if ($res == count($id_list)) {
            // 事务提交
            ProductModel::commit();
            // 删除关联附件
            (new FileModel)->delRFileByModule('crm_product', $id_list);
            // 添加删除记录
            actionLog($id_list, '', '', '');
            return resultArray(['data' => '删除成功']);
        } else {
            // 事务回滚
            ProductModel::rollback();
            return resultArray(['error' => '删除失败']);
        }
    }
}
