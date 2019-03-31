import request from '@/utils/request'

// crm 新建线索
export function crmLeadsSave(data) {
  return request({
    url: 'crm/leads/save',
    method: 'post',
    data: data
  })
}

// crm 列表
export function crmLeadsIndex(data) {
  return request({
    url: 'crm/leads/index',
    method: 'post',
    data: data
  })
}

// 删除
export function crmLeadsDelete(data) {
  return request({
    url: 'crm/leads/delete',
    method: 'post',
    data: data
  })
}

// crm 更新
export function crmLeadsUpdate(data) {
  return request({
    url: 'crm/leads/update',
    method: 'post',
    data: data
  })
}

// crm 详情
export function crmLeadsRead(data) {
  return request({
    url: 'crm/leads/read',
    method: 'post',
    data: data
  })
}

/**
 * 线索转移
 * @param {*} data
 * leads_id 	线索数组
 * owner_user_id 	变更负责人
 * is_remove 1移出，2转为团队成员
 * type 权限 1只读2读写
 */
export function crmLeadsTransfer(data) {
  return request({
    url: 'crm/leads/transfer',
    method: 'post',
    data: data
  })
}

/**
 * 线索转换为客户
 * @param {*} data
 * leads_id 	线索数组
 */
export function crmLeadsTransform(data) {
  return request({
    url: 'crm/leads/transform',
    method: 'post',
    data: data
  })
}

/**
 * 线索导出
 * @param {*} data
 *
 */
export function crmLeadsExcelExport(data) {
  return request({
    url: 'crm/leads/excelExport',
    method: 'post',
    data: data,
    responseType: 'blob'
  })
}

/**
 * 线索导入
 * @param {*} data
 *
 */
export function crmLeadsExcelImport(data) {
  var param = new FormData()
  Object.keys(data).forEach(key => {
    param.append(key, data[key])
  })
  return request({
    url: 'crm/leads/excelImport',
    method: 'post',
    data: param,
    headers: {
      'Content-Type': 'multipart/form-data'
    }
  })
}

/**
 * 线索导入模板下载
 * @param {*} data
 *
 */
export const crmLeadsExcelDownloadURL = 'crm/leads/excelDownload'
