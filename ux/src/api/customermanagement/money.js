import request from '@/utils/request'

// crm 新建回款
export function crmReceivablesSave(data) {
  return request({
    url: 'crm/receivables/save',
    method: 'post',
    data: data
  })
}

// crm 回款编辑
export function crmReceivablesUpdate(data) {
  return request({
    url: 'crm/receivables/Update',
    method: 'post',
    data: data
  })
}

/**
 * 回款列表
 * @param {*} data
 * page 页码
 * limit 每页数量
 * search 普通搜索
 */
export function crmReceivablesIndex(data) {
  return request({
    url: 'crm/receivables/index',
    method: 'post',
    data: data
  })
}

/**
 * 删除
 * @param {*} data
 *
 */
export function crmReceivablesDelete(data) {
  return request({
    url: 'crm/receivables/delete',
    method: 'post',
    data: data
  })
}

/**
 * 回款详情
 * @param {*} data
 */
export function crmReceivablesRead(data) {
  return request({
    url: 'crm/receivables/read',
    method: 'post',
    data: data
  })
}

/**
 * 回款计划列表
 * @param {*} data
 * page 页码
 * limit 每页数量
 * search 普通搜索
 */
export function crmReceivablesPlanIndex(data) {
  return request({
    url: 'crm/receivables_plan/index',
    method: 'post',
    data: data
  })
}

/**
 * 回款审核
 * @param {*} data
 */
export function crmReceivablesCheck(data) {
  return request({
    url: 'crm/receivables/check',
    method: 'post',
    data: data
  })
}

/**
 * 回款撤回审批
 * @param {*} data
 */
export function crmReceivablesRevokeCheck(data) {
  return request({
    url: 'crm/receivables/revokeCheck',
    method: 'post',
    data: data
  })
}

