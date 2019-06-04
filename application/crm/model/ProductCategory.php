<?php
// +----------------------------------------------------------------------
// | Description: 产品类别
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com
// +----------------------------------------------------------------------
namespace app\crm\model;

use think\Db;
use app\admin\model\Common;
use think\Request;
use think\Validate;

class ProductCategory extends Common
{
    /**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如CRM模块用crm作为数据表前缀
     */
    protected $name = 'crm_product_category';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $autoWriteTimestamp = true;

    /**
     * [getDataList 产品分类list]
     * @author Michael_xu
     * @param     [string]                   $map [查询条件]
     * @param     [number]                   $page     [当前页数]
     * @param     [number]                   $limit    [每页数量]
     * @return    [array]                    [description]
     */
    public function getDataList($type)
    {
        $cat = new \com\Category('crm_product_category', array('category_id', 'pid', 'name', 'title'));
        $data = $cat->getList('', 0, 'category_id');
        // 若type为tree，则返回树状结构
        if ($type == 'tree') {
            $tree = new \com\Tree();
            $data = $tree->list_to_tree($data, 'category_id', 'pid', 'child', 0, true, array(''));
        }
        return $data;
    }

    /**
     * 创建产品分类信息
     * @author Michael_xu
     * @param
     * @return
     */
    public function createData($param)
    {
        // 自动验证
        $validate = validate($this->name);
        if (!$validate->check($param)) {
            $this->error = $validate->getError();
            return false;
        }

        if ($this->data($param)->allowField(true)->save()) {
            $data = [];
            $data['id'] = $this->category_id;
            return $data;
        } else {
            $this->error = '添加失败';
            return false;
        }
    }

    /**
     * 编辑产品分类主表信息
     * @author Michael_xu
     * @param
     * @return
     */
    public function updateDataById($param, $category_id = 0)
    {
        $dataInfo = $this->get($category_id);
        if (!$dataInfo) {
            $this->error = '数据不存在或已删除';
            return false;
        }
        $param['category_id'] = $category_id;

        // 自动验证
        $validate = validate($this->name);
        if (!$validate->check($param)) {
            $this->error = $validate->getError();
            return false;
        }

        if ($this->allowField(true)->save($param, ['category_id' => $category_id])) {
            return true;
        } else {
            $this->error = '编辑失败';
            return false;
        }
    }

    /**
     * [delDataById 根据id删除数据]
     * @param     array $param [主键]
     * @param     boolean $delSon [是否删除子孙数据]
     * @return    [type]                           [description]
     */
    public function delDataById($param = [], $delSon = false)
    {
        if (!$param['id']) {
            $this->error = '删除失败';
            return false;
        }
        //分类下已有产品，则不能删除
        $resDel = true;
        if (db('crm_product')->where(['category_id' => $param['id']])->find()) {
            $resDel = false;
        }
        if ($delSon && is_numeric($param['id'])) {
            $childIds = $this->getAllChild($param['id']);
            if ($childIds) {
                if (db('crm_product')->where(['category_id' => ['in', $childIds]])->find()) {
                    $resDel = false;
                }
            }
        }
        if ($resDel === false) {
            $this->error = '请先移除该类型及子类下的相关产品';
            return false;
        }
        //提交事务
        $this->startTrans();
        try {
            $this->where(['category_id' => $param['id']])->delete();
            if ($delSon && is_numeric($param['id'])) {
                // 删除子孙
                $childIds = $this->getAllChild($param['id']);
                if ($childIds) {
                    $this->where('category_id', 'in', $childIds)->delete();
                }
            }
            $this->commit();
            return true;
        } catch (\Exception $e) {
            $this->error = '删除失败';
            $this->rollback();
            return false;
        }
    }

    /**
     * 产品分类id
     * @author Michael_xu
     * @param
     * @return
     */
    public function getIdByStr($category_id_arr)
    {
        if ($category_id_arr) {
            $category_id = end($category_id_arr);
            return $category_id ?: '';
        } else {
            return '';
        }
    }
}
