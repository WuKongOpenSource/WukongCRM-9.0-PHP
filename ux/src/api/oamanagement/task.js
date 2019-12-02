import request from '@/utils/request'

// 我下属的任务列表
export function subTaskListAPI(data) {
  return request({
    url: 'oa/task/subTaskList',
    method: 'post',
    data: data
  })
}
// 我的任务列表
export function myTaskAPI(data) {
  return request({
    url: 'oa/task/myTask',
    method: 'post',
    data: data
  })
}

/** ******** oA任务 ********/
// 我的任务列表
export function myTaskList(data) {
  return request({
    url: 'oa/task/myTask',
    method: 'post',
    data: data
  })
}
// 新增任务
export function addTask(data) {
  return request({
    url: 'oa/task/save',
    method: 'post',
    data: data
  })
}
// 删除任务
export function deleteTask(data) {
  return request({
    url: 'oa/task/delete',
    method: 'post',
    data: data
  })
}
// 拖拽改变分类
export function dragChangeClassify(data) {
  return request({
    url: 'oa/task/updateTop',
    method: 'post',
    data: data
  })
}
// 任务详情
export function detailsTask(data) {
  return request({
    url: 'oa/task/read',
    method: 'post',
    data: data
  })
}
// 任务编辑 -- 详情页总编辑
export function editTask(data) {
  return request({
    url: 'oa/task/update',
    method: 'post',
    data: data
  })
}
// 编辑任务名
export function editTaskName(data) {
  return request({
    url: 'oa/task/updateName',
    method: 'post',
    data: data
  })
}
// 设置截至日期
export function updateStoptime(data) {
  return request({
    url: 'oa/task/updateStoptime',
    method: 'post',
    data: data
  })
}
// 任务参与人删除添加
export function updateOwner(data) {
  return request({
    url: 'oa/task/updateOwner',
    method: 'post',
    data: data
  })
}
// 添加删除标签
export function deleteAddTag(data) {
  return request({
    url: 'oa/task/updateLable',
    method: 'post',
    data: data
  })
}
// 创建标签
export function createTag(data) {
  return request({
    url: 'oa/tasklable/save',
    method: 'post',
    data: data
  })
}
// 编辑标签
export function editTagAPI(data) {
  return request({
    url: 'oa/tasklable/update',
    method: 'post',
    data: data
  })
}
// 删除标签
export function deleteTagAPI(data) {
  return request({
    url: 'oa/tasklable/delete',
    method: 'post',
    data: data
  })
}
// 标签列表
export function tagList(data) {
  return request({
    url: 'oa/tasklable/index',
    method: 'post',
    data: data
  })
}

// 优先级
export function updatePriority(data) {
  return request({
    url: 'oa/task/updatePriority',
    method: 'post',
    data: data
  })
}
// 子任务标记结束
export function taskOver(data) {
  return request({
    url: 'oa/task/taskOver',
    method: 'post',
    data: data
  })
}
// 操作记录
export function readLoglist(data) {
  return request({
    url: 'oa/task/readLoglist',
    method: 'post',
    data: data
  })
}
// 单独删除参与人
export function delOwnerById(data) {
  return request({
    url: 'oa/task/delOwnerById',
    method: 'post',
    data
  })
}
// 单独删除参与人
export function delStruceureById(data) {
  return request({
    url: 'oa/task/delStruceureById',
    method: 'post',
    data
  })
}
// 任务评论添加
export function comAdd(data) {
  return request({
    url: 'oa/taskcomment/save',
    method: 'post',
    data
  })
}
// 任务评论删除
export function comDelete(data) {
  return request({
    url: 'oa/taskcomment/delete',
    method: 'post',
    data
  })
}
// 归档任务激活
export function taskRecover(data) {
  return request({
    url: 'oa/task/recover',
    method: 'post',
    data
  })
}
// 取消关联
export function delrelation(data) {
  return request({
    url: 'oa/task/delrelation',
    method: 'post',
    data
  })
}
