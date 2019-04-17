import request from '@/utils/request'

// crm 新建合同
export function crmContractSave(data) {
  return request({
    url: 'crm/contract/save',
    method: 'post',
    data: data
  })
}

// crm 列表
export function crmContractIndex(data) {
  return request({
    url: 'crm/contract/index',
    method: 'post',
    data: data
  })
}

// 删除
export function crmContractDelete(data) {
  return request({
    url: 'crm/contract/delete',
    method: 'post',
    data: data
  })
}

// crm 更新
export function crmContractUpdate(data) {
  return request({
    url: 'crm/contract/update',
    method: 'post',
    data: data
  })
}

// crm 详情
export function crmContractRead(data) {
  return request({
    url: 'crm/contract/read',
    method: 'post',
    data: data
  })
}

/**
 * 回款计划创建
 * @param {*} data
 */
export function crmReceivablesPlanSave(data) {
  return request({
    url: 'crm/receivables_plan/save',
    method: 'post',
    data: data
  })
}

/**
 * 合同审核
 * @param {*} data
 * id
 * status 1通过0拒绝
 * content
 */
export function crmContractCheck(data) {
  return request({
    url: 'crm/contract/check',
    method: 'post',
    data: data
  })
}

/**
 * 合同撤回审批
 * @param {*} data
 * id
 * status 1通过 0拒绝
 * 0失败，1通过，2撤回，3创建，4待审核 状态信息
 */
export function crmContractRevokeCheck(data) {
  return request({
    url: 'crm/contract/revokeCheck',
    method: 'post',
    data: data
  })
}

/**
 * 合同相关产品
 * @param {*} data
 * contract_id
 */
export function crmContractProduct(data) {
  return request({
    url: 'crm/contract/product',
    method: 'post',
    data: data
  })
}

// 转移
export function crmContractTransfer(data) {
  return request({
    url: 'crm/contract/transfer',
    method: 'post',
    data: data
  })
}


