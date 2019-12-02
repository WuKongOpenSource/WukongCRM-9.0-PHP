import request from '@/utils/request'

/**
 * 我的任务列表
 * @param {*} data
 */
export function workTaskMyTaskAPI(data) {
  return request({
    url: 'work/task/myTask',
    method: 'post',
    data: data
  })
}

/**
 * 新增任务
 * @param {*} data
 */
export function workTaskSaveAPI(data) {
  return request({
    url: 'work/task/save',
    method: 'post',
    data: data
  })
}

/**
 * 删除任务
 * @param {*} data
 */
export function workTaskDeleteAPI(data) {
  return request({
    url: 'work/task/delete',
    method: 'post',
    data: data
  })
}

/**
 * 拖拽改变分类
 * @param {*} data
 */
export function workTaskUpdateTopAPI(data) {
  return request({
    url: 'work/task/updateTop',
    method: 'post',
    data: data
  })
}

/**
 * 项目列表
 * @param {*} data
 */
export function workIndexWorkListAPI(data) {
  return request({
    url: 'work/index/workList',
    method: 'post',
    data: data
  })
}

/**
 * 任务详情
 * @param {*} data
 */
export function workTaskReadAPI(data) {
  return request({
    url: 'work/task/read',
    method: 'post',
    data: data
  })
}

/**
 * 任务编辑 -- 详情页总编辑
 * @param {*} data
 */
export function workTaskUpdateAPI(data) {
  return request({
    url: 'work/task/update',
    method: 'post',
    data: data
  })
}

/**
 * 编辑任务名
 * @param {*} data
 */
export function workTaskUpdateNameAPI(data) {
  return request({
    url: 'work/task/updateName',
    method: 'post',
    data: data
  })
}

/**
 * 设置截至日期
 * @param {*} data
 */
export function workTaskUpdateStoptimeAPI(data) {
  return request({
    url: 'work/task/updateStoptime',
    method: 'post',
    data: data
  })
}

/**
 * 任务参与人删除添加
 * @param {*} data
 */
export function workTaskUpdateOwnerAPI(data) {
  return request({
    url: 'work/task/updateOwner',
    method: 'post',
    data: data
  })
}

/**
 * 添加删除标签
 * @param {*} data
 */
export function workTaskUpdateLableAPI(data) {
  return request({
    url: 'work/task/updateLable',
    method: 'post',
    data: data
  })
}

/**
 * 任务归档
 * @param {*} data
 */
export function workTaskArchiveAPI(data) {
  return request({
    url: 'work/task/archive',
    method: 'post',
    data: data
  })
}

/**
 * 优先级
 * @param {*} data
 */
export function workTaskUpdatePriorityAPI(data) {
  return request({
    url: 'work/task/updatePriority',
    method: 'post',
    data: data
  })
}

/**
 * 子任务标记结束
 */
export function workTaskTaskOverAPI(data) {
  return request({
    url: 'work/task/taskOver',
    method: 'post',
    data: data
  })
}

/**
 * 操作记录
 * @param {*} data
 */
export function workTaskReadLoglistAPI(data) {
  return request({
    url: 'work/task/readLoglist',
    method: 'post',
    data: data
  })
}

/**
 * 单独删除参与人
 * @param {*} data
 */
export function workTaskDelOwnerByIdAPI(data) {
  return request({
    url: 'work/task/delOwnerById',
    method: 'post',
    data
  })
}

/**
 * 任务评论添加
 * @param {*} data
 */
export function workTaskcommentSaveAPI(data) {
  return request({
    url: 'work/taskcomment/save',
    method: 'post',
    data
  })
}

/**
 * 任务评论删除
 * @param {*} data
 */
export function workTaskcommentDeleteAPI(data) {
  return request({
    url: 'work/taskcomment/delete',
    method: 'post',
    data
  })
}

/**
 * 归档任务激活
 * @param {*} data
 */
export function workTaskRecoverAPI(data) {
  return request({
    url: 'work/task/recover',
    method: 'post',
    data
  })
}
