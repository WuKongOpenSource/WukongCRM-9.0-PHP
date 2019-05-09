import request from '@/utils/request'

// crm 新建联系人
export function crmContactsSave(data) {
  return request({
    url: 'crm/contacts/save',
    method: 'post',
    data: data
  })
}

// crm 列表
export function crmContactsIndex(data) {
  return request({
    url: 'crm/contacts/index',
    method: 'post',
    data: data
  })
}

// 删除
export function crmContactsDelete(data) {
  return request({
    url: 'crm/contacts/delete',
    method: 'post',
    data: data
  })
}

// crm 列表
export function crmContactsUpdate(data) {
  return request({
    url: 'crm/contacts/update',
    method: 'post',
    data: data
  })
}

// crm 详情
export function crmContactsRead(data) {
  return request({
    url: 'crm/contacts/read',
    method: 'post',
    data: data
  })
}

/**
 * 联系人转移
 * @param {*} data
 * contacts_id 	联系人数组
 * owner_user_id 	变更负责人
 * is_remove 1移出，2转为团队成员
 * type 权限 1只读2读写
 */
export function crmContactsTransfer(data) {
  return request({
    url: 'crm/contacts/transfer',
    method: 'post',
    data: data
  })
}

/**
 * 联系人导出
 * @param {*} data
 *
 */
export function crmContactsExcelExport(data) {
  return request({
    url: 'crm/contacts/excelExport',
    method: 'post',
    data: data,
    responseType: 'blob'
  })
}

/**
 * 联系人导入
 * @param {*} data
 *
 */
export function crmContactsExcelImport(data) {
  var param = new FormData()
  Object.keys(data).forEach(key => {
    param.append(key, data[key])
  })
  return request({
    url: 'crm/contacts/excelImport',
    method: 'post',
    data: param,
    headers: {
      'Content-Type': 'multipart/form-data'
    }
  })
}

/**
 * 联系人导入模板下载
 * @param {*} data
 *
 */
export const crmContactsExcelDownloadURL = 'crm/contacts/excelDownload'
