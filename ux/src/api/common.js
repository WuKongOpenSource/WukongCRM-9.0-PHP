import request from '@/utils/request'

// 公共接口 涉及 员工 部门 等 使用频率较高 但分散 的接口
// 权限范围内的用户列表
/**
 *
 * @param {*} data
 * 1.默认全部
 *
 * 2.模块下权限内
 * m
 * c
 * a
 *
 *
 */
export function usersList(data) {
  return request({
    url: 'admin/users/getUserList',
    method: 'post',
    data: data
  })
}

// 部门列表
export function depList(data) {
  return request({
    url: 'admin/structures/index',
    method: 'post',
    data: data
  })
}

// 获取权限范围内部门接口
export function adminStructuresSubIndex(data) {
  return request({
    url: 'admin/structures/subIndex',
    method: 'post',
    data: data
  })
}

// 部门下的员工 structure_id
export function userListByStructid(data) {
  return request({
    url: 'admin/users/userListByStructid',
    method: 'post',
    data: data
  })
}

// 根据部门获取权限范围内员工接口
export function getSubUserByStructrue(data) {
  return request({
    url: 'admin/structures/getSubUserByStructrue',
    method: 'post',
    data: data
  })
}

/**
 *
 * @param {*} data
 * 附件上传 file module module_id
 */
export const crmFileSaveUrl = 'admin/file/save'

export function crmFileSave(data) {
  var param = new FormData()
  Object.keys(data).forEach(key => {
    param.append(key, data[key])
  })
  return request({
    url: 'admin/file/save',
    method: 'post',
    data: param,
    headers: {
      'Content-Type': 'multipart/form-data'
    }
  })
}

// 附件列表
export function crmFileIndex(data) {
  return request({
    url: 'admin/file/index',
    method: 'post',
    data: data
  })
}

/** 暂时客户管理里面也有 */
// 附件删除接口
export function crmFileDelete(data) {
  return request({
    url: 'admin/file/delete',
    method: 'post',
    data: data
  })
}

// 附件重命名
export function crmFileUpdate(data) {
  return request({
    url: 'admin/file/update',
    method: 'post',
    data: data
  })
}

// crm 自定义字段的添加
/**
 *
 * @param {*} data
 * 操作方法 (save:添加、update:编辑、read:详情、index:列表)
 * 操作ID (如：客户ID)
 */
export function filedGetField(data) {
  return request({
    url: 'admin/field/getField',
    method: 'post',
    data: data
  })
}

// 权限数据返回
export function adminIndexAuthList(data) {
  return request({
    url: 'admin/index/authList',
    method: 'post',
    data: data
  })
}

// 文件静态资源下载
export function adminFileDownload(data) {
  return request({
    url: 'admin/file/download',
    method: 'post',
    responseType: 'blob',
    data: data
  })
}

/**
 * 员工导入模板下载
 * @param {*} data
 *
 */
export const userExcelDownloadURL = 'admin/users/excelDownload'


// 员工导入
export function userExcelImport(data) {
  var param = new FormData()
  Object.keys(data).forEach(key => {
    param.append(key, data[key])
  })
  return request({
    url: 'admin/users/import',
    method: 'post',
    data: param,
    headers: {
      'Content-Type': 'multipart/form-data'
    },
    timeout: 600000
  })
}


