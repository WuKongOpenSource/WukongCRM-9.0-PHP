import request from '@/utils/request'

// crm 新建产品
export function crmProductSave(data) {
  return request({
    url: 'crm/product/save',
    method: 'post',
    data: data
  })
}

// crm 列表
export function crmProductIndex(data) {
  return request({
    url: 'crm/product/index',
    method: 'post',
    data: data
  })
}

// crm 更新
export function crmProductUpdate(data) {
  return request({
    url: 'crm/product/update',
    method: 'post',
    data: data
  })
}

// crm 详情
export function crmProductRead(data) {
  return request({
    url: 'crm/product/read',
    method: 'post',
    data: data
  })
}

/**
 * 产品上架、下架
 * @param {*} data
 * id 产品ID数组
 * status 	上架、下架
 */
export function crmProductStatus(data) {
  return request({
    url: 'crm/product/status',
    method: 'post',
    data: data
  })
}

/**
 * 产品导出
 * @param {*} data
 *
 */
export function crmProductExcelExport(data) {
  return request({
    url: 'crm/product/excelExport',
    method: 'post',
    data: data,
    responseType: 'blob'
  })
}

/**
 * 产品导入
 * @param {*} data
 *
 */
export function crmProductExcelImport(data) {
  var param = new FormData()
  Object.keys(data).forEach(key => {
    param.append(key, data[key])
  })
  return request({
    url: 'crm/product/excelImport',
    method: 'post',
    data: param,
    headers: {
      'Content-Type': 'multipart/form-data'
    }
  })
}

/**
 * 产品导入模板下载
 * @param {*} data
 *
 */
export const crmProductExcelDownloadURL = 'crm/product/excelDownload'
