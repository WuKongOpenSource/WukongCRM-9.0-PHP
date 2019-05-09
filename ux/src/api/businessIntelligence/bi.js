import request from '@/utils/request'

/**
 * 业绩目标完成情况
 * @param {*} data
 * year 年
 * status 1销售（目标）2回款（目标）
 * user_id 员工ID
 * structure_id 部门ID
 */
export function biAchievementStatistics(data) {
  return request({
    url: 'bi/achievement/statistics',
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
export function biProductStatistics(data) {
  return request({
    url: 'bi/product/statistics',
    method: 'post',
    data: data
  })
}
