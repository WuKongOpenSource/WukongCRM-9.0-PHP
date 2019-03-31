import request from '@/utils/request'

/**
 * 应用列表接口
 * @param {*} data
 *
 */
export function adminConfigsetIndex(data) {
  return request({
    url: 'admin/configset/index',
    method: 'post',
    data: data
  })
}

/**
 * 应用状态改变
 * @param {*} data
 * id 应用ID
 * status 1开启 0关闭
 */
export function adminConfigsetUpdate(data) {
  return request({
    url: 'admin/configset/update',
    method: 'post',
    data: data
  })
}

/**
 * 应用类别状态
 * @param {*} data
 * type 应用类别ID
 * status 1开启 0关闭
 */
export function adminConfigsetUpdatetype(data) {
  return request({
    url: 'admin/configset/updatetype',
    method: 'post',
    data: data
  })
}

/**
 * 根据模块类获取应用列表
 * @param {*} data
 * type 应用类别ID
 */
export function adminConfigsetRead(data) {
  return request({
    url: 'admin/configset/read',
    method: 'post',
    data: data
  })
}

/**
 * 模块列表
 * @param {*} data
 */
export function adminConfigsetTypelist(data) {
  return request({
    url: 'admin/configset/typelist',
    method: 'post',
    data: data
  })
}
