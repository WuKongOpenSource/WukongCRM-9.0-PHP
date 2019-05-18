import request from '@/utils/request'

/**
 * 待审核合同
 * @param {*} data 
 */
export function crmMessageCheckContractAPI(data) {
  return request({
    url: 'crm/message/checkContract',
    method: 'post',
    data: data
  })
}

/**
 * 待审核回款
 * @param {*} data 
 */
export function crmMessageCheckReceivablesAPI(data) {
  return request({
    url: 'crm/message/checkReceivables',
    method: 'post',
    data: data
  })
}

/**
 * 今日需联系客户
 * @param {*} data 
 */
export function crmMessageTodayCustomerAPI(data) {
  return request({
    url: 'crm/message/todayCustomer',
    method: 'post',
    data: data
  })
}

/**
 * 待跟进线索
 * @param {*} data 
 */
export function crmMessageFollowLeadsAPI(data) {
  return request({
    url: 'crm/message/followLeads',
    method: 'post',
    data: data
  })
}


/**
 * 待跟进客户
 * @param {*} data 
 */
export function crmMessageFollowCustomerAPI(data) {
  return request({
    url: 'crm/message/followCustomer',
    method: 'post',
    data: data
  })
}

/**
 * 即将到期合同
 * @param {*} data 
 */
export function crmMessagEndContractAPI(data) {
  return request({
    url: 'crm/message/endContract',
    method: 'post',
    data: data
  })
}

/**
 * 待回款合同
 * @param {*} data 
 */
export function crmMessagRemindreceivablesplanAPI(data) {
  return request({
    url: 'crm/message/remindreceivablesplan',
    method: 'post',
    data: data
  })
}

/**
 * 待办消息数
 * @param {*} data 
 */
export function crmMessagNumAPI(data) {
  return request({
    url: 'crm/message/num',
    method: 'post',
    data: data
  })
}
