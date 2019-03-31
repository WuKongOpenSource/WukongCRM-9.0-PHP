import request from '@/utils/request'

// crm 新建联系人
export function crmContactsSave(data) {
  return request({
    url: 'crm/contacts/save',
    method: 'post',
    data: data
  })
}

// crm 列表
export function crmContactsIndex(data) {
  return request({
    url: 'crm/contacts/index',
    method: 'post',
    data: data
  })
}

// 删除
export function crmContactsDelete(data) {
  return request({
    url: 'crm/contacts/delete',
    method: 'post',
    data: data
  })
}

// crm 列表
export function crmContactsUpdate(data) {
  return request({
    url: 'crm/contacts/update',
    method: 'post',
    data: data
  })
}

// crm 详情
export function crmContactsRead(data) {
  return request({
    url: 'crm/contacts/read',
    method: 'post',
    data: data
  })
}

/**
 * 联系人转移
 * @param {*} data
 * contacts_id 	联系人数组
 * owner_user_id 	变更负责人
 * is_remove 1移出，2转为团队成员
 * type 权限 1只读2读写
 */
export function crmContactsTransfer(data) {
  return request({
    url: 'crm/contacts/transfer',
    method: 'post',
    data: data
  })
}
