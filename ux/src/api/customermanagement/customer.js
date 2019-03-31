import request from '@/utils/request'

// crm 新建客户
export function crmCustomerSave(data) {
  return request({
    url: 'crm/customer/save',
    method: 'post',
    data: data
  })
}

// crm 客户列表
export function crmCustomerIndex(data) {
  return request({
    url: 'crm/customer/index',
    method: 'post',
    data: data
  })
}

// 删除
export function crmCustomerDelete(data) {
  return request({
    url: 'crm/customer/delete',
    method: 'post',
    data: data
  })
}

// crm 更新
export function crmCustomerUpdate(data) {
  return request({
    url: 'crm/customer/update',
    method: 'post',
    data: data
  })
}

// crm 公海列表
export function crmCustomerPool(data) {
  return request({
    url: 'crm/customer/pool',
    method: 'post',
    data: data
  })
}

// crm 详情
export function crmCustomerRead(data) {
  return request({
    url: 'crm/customer/read',
    method: 'post',
    data: data
  })
}

// 操作
/**
 * 客户锁定，解锁
 * @param {*} data
 * is_lock 1锁定，2解锁
 * customer_id 客户数组
 */
export function crmCustomerLock(data) {
  return request({
    url: 'crm/customer/lock',
    method: 'post',
    data: data
  })
}

/**
 * 客户放入公海
 * @param {*} data
 * customer_id 	客户数组
 */
export function crmCustomerPutInPool(data) {
  return request({
    url: 'crm/customer/putInPool',
    method: 'post',
    data: data
  })
}

/**
 * 客户转移
 * @param {*} data
 * customer_id 	客户数组
 * owner_user_id 	变更负责人
 * is_remove 1移出，2转为团队成员
 * types business,contract 相关模块
 * type 权限 1只读2读写
 */
export function crmCustomerTransfer(data) {
  return request({
    url: 'crm/customer/transfer',
    method: 'post',
    data: data
  })
}

/**
 * 客户导出
 * @param {*} data
 * customer_id 客户ID
 */
export function crmCustomerExcelExport(data) {
  return request({
    url: 'crm/customer/excelExport',
    method: 'post',
    data: data,
    responseType: 'blob'
  })
}

/**
 * 客户导入
 * @param {*} data
 * customer_id 客户ID
 */
export function crmCustomerExcelImport(data) {
  var param = new FormData()
  Object.keys(data).forEach(key => {
    param.append(key, data[key])
  })
  return request({
    url: 'crm/customer/excelImport',
    method: 'post',
    data: param,
    headers: {
      'Content-Type': 'multipart/form-data'
    }
  })
}

/**
 * 客户导入模板下载
 * @param {*} data
 *
 */
export const crmCustomerExcelDownloadURL = 'crm/customer/excelDownload'

/**
 * 客户分配
 * @param {*} data
 * customer_id 客户ID
 * owner_user_id 分配人ID
 */
export function crmCustomerDistribute(data) {
  return request({
    url: 'crm/customer/distribute',
    method: 'post',
    data: data
  })
}

/**
 * 客户领取
 * @param {*} data
 * customer_id 客户IDs
 */
export function crmCustomerReceive(data) {
  return request({
    url: 'crm/customer/receive',
    method: 'post',
    data: data
  })
}
