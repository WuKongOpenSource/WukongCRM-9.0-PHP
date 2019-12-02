import request from '@/utils/request'

/**
 * 归档项目列表
 * @param {*} data
 */
export function workWorkArchiveListAPI(data) {
  return request({
    url: 'work/work/archiveList',
    method: 'post',
    data: data
  })
}

/**
 * 归档项目恢复
 * @param {*} data
 */
export function workWorkArRecoverAPI(data) {
  return request({
    url: 'work/work/arRecover',
    method: 'post',
    data: data
  })
}
