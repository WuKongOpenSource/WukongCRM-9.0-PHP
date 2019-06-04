<?php
// +----------------------------------------------------------------------
// | Description: 岗位控制器
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com
// +----------------------------------------------------------------------

namespace app\admin\controller;

class Posts extends ApiCommon
{

    public function index()
    {
        $postModel = model('Post');
        $param = $this->param;
        $data = $postModel->getDataList($param);
        return resultArray(['data' => $data]);
    }

    public function read()
    {
        $postModel = model('Post');
        $param = $this->param;
        $data = $postModel->getDataById($param['id']);
        if (!$data) {
            return resultArray(['error' => $postModel->getError()]);
        }
        return resultArray(['data' => $data]);
    }

    public function save()
    {
        $postModel = model('Post');
        $param = $this->param;
        $data = $postModel->createData($param);
        if (!$data) {
            return resultArray(['error' => $postModel->getError()]);
        }
        return resultArray(['data' => '添加成功']);
    }

    public function update()
    {
        $postModel = model('Post');
        $param = $this->param;
        $data = $postModel->updateDataById($param, $param['id']);
        if (!$data) {
            return resultArray(['error' => $postModel->getError()]);
        }
        return resultArray(['data' => '编辑成功']);
    }

    public function delete()
    {
        $postModel = model('Post');
        $param = $this->param;
        $data = $postModel->delDataById($param);
        if (!$data) {
            return resultArray(['error' => $postModel->getError()]);
        }
        return resultArray(['data' => '删除成功']);
    }

    public function deletes()
    {
        $postModel = model('Post');
        $param = $this->param;
        $data = $postModel->delDatas($param['ids']);
        if (!$data) {
            return resultArray(['error' => $postModel->getError()]);
        }
        return resultArray(['data' => '删除成功']);
    }

    public function enables()
    {
        $postModel = model('Post');
        $param = $this->param;
        $data = $postModel->enableDatas($param['ids'], $param['status']);
        if (!$data) {
            return resultArray(['error' => $postModel->getError()]);
        }
        return resultArray(['data' => '操作成功']);
    }

}
