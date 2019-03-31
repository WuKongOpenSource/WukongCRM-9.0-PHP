import request from '@/utils/request'

/**
 * 部门业绩目标列表
 * @param {*} data
 * year 年
 * status 1销售（目标）2回款（目标）
 * id 部门ID
 */
export function crmAchievementIndex(data) {
  return request({
    url: 'crm/achievement/index',
    method: 'post',
    data: data
  })
}

/**
 * 业绩目标编辑接口
 * @param {*} data
 * datalist 对应数组
 */
export function crmAchievementUpdate(data) {
  return request({
    url: 'crm/achievement/update',
    method: 'post',
    data: data
  })
}

/**
 * 员工业绩目标列表
 * @param {*} data
 * year 年
 * status 1销售（目标）2回款（目标）
 * user_id 员工ID
 * structure_id 部门ID
 */
export function crmAchievementIndexForuser(data) {
  return request({
    url: 'crm/achievement/indexForuser',
    method: 'post',
    data: data
  })
}

/**
 * 业绩目标完成情况
 * @param {*} data
 * year 年
 * status 1销售（目标）2回款（目标）
 * user_id 员工ID
 * structure_id 部门ID
 */
export function crmAchievementDatalist(data) {
  return request({
    url: 'crm/achievement/datalist',
    method: 'post',
    data: data
  })
}

/**
 * 产品销售情况统计
 * @param {*} data
 * year 年
 * status 1销售（目标）2回款（目标）
 * user_id 员工ID
 * structure_id 部门ID
 */
export function crmProductStatistics(data) {
  return request({
    url: '/crm/product/statistics',
    method: 'post',
    data: data
  })
}

/**
 * 回款统计
 * @param {*} data
 * year 年
 * user_id 员工ID
 * structure_id 部门ID
 */
export function crmReceivablesStatistics(data) {
  return request({
    url: '/crm/receivables/statistics',
    method: 'post',
    data: data
  })
}

/**
 * 回款统计列表
 * @param {*} data
 * year 年
 * month 1-12
 * user_id 员工ID
 * structure_id 部门ID
 */
export function crmReceivablesStatisticList(data) {
  return request({
    url: '/crm/receivables/statisticList',
    method: 'post',
    data: data
  })
}

/**
 * 员工客户分析
 * @param {*} data
 * start_time
 * end_time
 * user_id 员工ID
 * structure_id 部门ID
 */
export function crmCustomerUserCustomer(data) {
  return request({
    url: '/crm/customer/userCustomer',
    method: 'post',
    data: data
  })
}

/**
 * 销售漏斗
 * @param {*} data
 * start_time
 * end_time
 * user_id 员工ID
 * structure_id 部门ID
 * type_id 商机组
 */
export function crmBusinessFunnel(data) {
  return request({
    url: '/crm/business/funnel',
    method: 'post',
    data: data
  })
}
