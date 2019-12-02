import request from '@/utils/request'

/**
 * 回收站列表
 * @param {*} data
 */
export function workTrashIndexAPI(data) {
  return request({
    url: 'work/trash/index',
    method: 'post',
    data: data
  })
}

/**
 * 回收站彻底删除
 * @param {*} data
 */
export function workTrashDeleteAPI(data) {
  return request({
    url: 'work/trash/delete',
    method: 'post',
    data: data
  })
}

/**
 * 回收站恢复
 * @param {*} data
 */
export function workTrashRecoverAPI(data) {
  return request({
    url: 'work/trash/recover',
    method: 'post',
    data: data
  })
}



