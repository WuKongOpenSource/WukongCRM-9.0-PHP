import request from '@/utils/request'

/**
 * 修改头像
 * @param {*} data
 * id
 * file
 */
export function adminUsersUpdateImg(data) {
  return request({
    url: 'admin/users/updateImg',
    method: 'post',
    data: data,
    headers: {
      'Content-Type': 'multipart/form-data'
    }
  })
}

/**
 * 个人详情
 * @param {*} data
 * id
 */
export function adminUsersRead(data) {
  return request({
    url: 'admin/users/read',
    method: 'post',
    data: data
  })
}

/**
 * 修改个人信息
 * @param {*} data
 * id
 */
export function adminUsersUpdate(data) {
  return request({
    url: 'admin/users/update',
    method: 'post',
    data: data
  })
}

/**
 * 修改密码
 * @param {*} data
 * id
 * old_pwd
 * new_pwd
 */
export function adminUsersResetPassword(data) {
  return request({
    url: 'admin/users/resetPassword',
    method: 'post',
    data: data
  })
}
