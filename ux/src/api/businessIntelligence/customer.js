import request from '@/utils/request'

/**
 * 员工客户总量分析
 */
export function biCustomerTotalAPI(data) {
  return request({
    url: 'bi/customer/total',
    method: 'post',
    data: data
  })
}

export function biCustomerTotalListAPI(data) {
  return request({
    url: 'bi/customer/statistics',
    method: 'post',
    data: data
  })
}

/**
 * 员工客户跟进次数分析
 * @param {*} data
 */
export function biCustomerRecordTimesAPI(data) {
  return request({
    url: 'bi/customer/recordTimes',
    method: 'post',
    data: data
  })
}

/**
 * 员工客户跟进次数分析 具体员工列表
 * @param {*} data
 */
export function biCustomerRecordListAPI(data) {
  return request({
    url: 'bi/customer/recordList',
    method: 'post',
    data: data
  })
}

/**
 * 员工跟进方式分析
 * @param {*} data
 */
export function biCustomerRecordModeAPI(data) {
  return request({
    url: 'bi/customer/recordMode',
    method: 'post',
    data: data
  })
}

/**
 * 客户转化率分析具体数据
 * @param {*} data
 */
export function biCustomerConversionInfoAPI(data) {
  return request({
    url: 'bi/customer/conversionInfo',
    method: 'post',
    data: data
  })
}

/**
 * 客户转化率分析
 * @param {*} data
 */
export function biCustomerConversionAPI(data) {
  return request({
    url: 'bi/customer/conversion',
    method: 'post',
    data: data
  })
}


/**
 * 公海客户分析
 * @param {*} data
 */
export function biCustomerPoolAPI(data) {
  return request({
    url: 'bi/customer/pool',
    method: 'post',
    data: data
  })
}

/**
 * 公海客户分析
 * @param {*} data
 */
export function biCustomerPoolListAPI(data) {
  return request({
    url: 'bi/customer/poolList',
    method: 'post',
    data: data
  })
}

/**
 * 员工客户成交周期
 * @param {*} data
 */
export function biCustomerUserCycleAPI(data) {
  return request({
    url: 'bi/customer/userCycle',
    method: 'post',
    data: data
  })
}

/**
 * 地区成交周期
 * @param {*} data
 */
export function biCustomerAddressCycleAPI(data) {
  return request({
    url: 'bi/customer/addressCycle',
    method: 'post',
    data: data
  })
}

/**
 * 产品成交周期
 * @param {*} data
 */
export function biCustomerProductCycleAPI(data) {
  return request({
    url: 'bi/customer/productCycle',
    method: 'post',
    data: data
  })
}
