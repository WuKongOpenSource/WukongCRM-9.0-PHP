import request from '@/utils/request'

// crm 新建商机
export function crmBusinessSave(data) {
  return request({
    url: 'crm/business/save',
    method: 'post',
    data: data
  })
}

// crm 列表
export function crmBusinessIndex(data) {
  return request({
    url: 'crm/business/index',
    method: 'post',
    data: data
  })
}

// 删除
export function crmBusinessDelete(data) {
  return request({
    url: 'crm/business/delete',
    method: 'post',
    data: data
  })
}

// crm 更新
export function crmBusinessUpdate(data) {
  return request({
    url: 'crm/business/update',
    method: 'post',
    data: data
  })
}

// crm 商机状态组
export function crmBusinessStatusList(data) {
  return request({
    url: 'crm/business/statusList',
    method: 'post',
    data: data
  })
}

// crm 详情
export function crmBusinessRead(data) {
  return request({
    url: 'crm/business/read',
    method: 'post',
    data: data
  })
}

/**
 * 商机转移
 * @param {*} data
 * business_id 	商机数组
 * owner_user_id 	变更负责人
 * is_remove 1移出，2转为团队成员
 * type 权限 1只读2读写
 */
export function crmBusinessTransfer(data) {
  return request({
    url: 'crm/business/transfer',
    method: 'post',
    data: data
  })
}

/**
 * 商机转移
 * @param {*} data
 * business_id 	商机
 * status_id 	商机状态ID
 * content 备注
 */
export function crmBusinessAdvance(data) {
  return request({
    url: 'crm/business/advance',
    method: 'post',
    data: data
  })
}

/**
 * 商机相关产品
 * @param {*} data
 * business_id 	商机ID
 */
export function crmBusinessProduct(data) {
  return request({
    url: 'crm/business/product',
    method: 'post',
    data: data
  })
}

/**
 * 商机导出
 * @param {*} data
 */
export function crmBusinessExcelExport(data) {
  return request({
    url: 'crm/business/excelExport',
    method: 'post',
    data: data,
    responseType: 'blob',
    timeout: 600000
  })
}
