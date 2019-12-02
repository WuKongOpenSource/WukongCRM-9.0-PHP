import request from '@/utils/request'

/**
 * 标签左侧列表
 * @param {*} data
 */
export function workTasklableIndexAPI(data) {
  return request({
    url: 'work/tasklable/index',
    method: 'post',
    data: data
  })
}

/**
 * 单个标签详情
 * @param {*} data
 */
export function workTasklableReadAPI(data) {
  return request({
    url: 'work/tasklable/read',
    method: 'post',
    data: data
  })
}

/**
 * 标签删除
 * @param {*} data
 */
export function workTasklableDeleteAPI(data) {
  return request({
    url: 'work/tasklable/delete',
    method: 'post',
    data: data
  })
}

/**
 * 标签编辑
 * @param {*} data
 */
export function workTasklableSaveAPI(data) {
  return request({
    url: 'work/tasklable/save',
    method: 'post',
    data: data
  })
}

/**
 * 获取项目及任务表
 * @param {*} data
 */
export function workTasklableGetWokListAPI(data) {
  return request({
    url: 'work/tasklable/getWokList',
    method: 'post',
    data: data
  })
}

/**
 * 编辑标签
 * @param {*} data
 */
export function workTasklableUpdateAPI(data) {
  return request({
    url: 'work/tasklable/update',
    method: 'post',
    data: data
  })
}
