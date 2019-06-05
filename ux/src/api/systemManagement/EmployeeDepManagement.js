import request from '@/utils/request'

// 部门列表、增、删、改 depTreeList depDelete depEdit depSave
export function depTreeList() {
  return request({
    url: 'admin/structures/index',
    method: 'post',
    data: {
      type: "tree"
    }
  })
}

export function depDelete(data) {
  return request({
    url: 'admin/structures/delete',
    method: 'post',
    data: data
  })
}

export function depEdit(data) {
  return request({
    url: 'admin/structures/update',
    method: 'post',
    data: data
  })
}

export function depSave(data) {
  return request({
    url: 'admin/structures/save',
    method: 'post',
    data: data
  })
}

// 用户列表
export function adminUsersIndex(data) {
  return request({
    url: 'admin/users/index',
    method: 'post',
    data: data
  })
}

export function usersAdd(params) {
  return request({
    url: 'admin/users/save',
    method: 'post',
    data: params
  })
}

export function usersUpdate(params) {
  return request({
    url: 'admin/users/update',
    method: 'post',
    data: params
  })
}

// 岗位列表
export function jobsList(data) {
  return request({
    url: 'admin/posts/index',
    method: 'post',
    data: data
  })
}

// 角色列表
export function roleList(data) {
  return request({
    url: 'admin/groups/index',
    method: 'post',
    data: data
  })
}

// 重置密码
export function resetPassword(data) {
  return request({
    url: 'admin/users/resetPassword',
    method: 'post',
    data: data
  })
}

/**
 * 批量修改密码接口
 * @param {*} data
 * password
 * id 用户数组
 */
export function adminUsersUpdatePwd(data) {
  return request({
    url: 'admin/users/updatePwd',
    method: 'post',
    data: data
  })
}

/**
 * 编辑登录名
 * @param {*} data
 * username
 * password
 * id
 */
export function adminUsersUsernameEditAPI(data) {
  return request({
    url: 'admin/users/usernameEdit',
    method: 'post',
    data: data
  })
}

// 用户状态修改
export function usersEditStatus(data) {
  return request({
    url: 'admin/users/enables',
    method: 'post',
    data: data
  })
}

/**
 * 人资员工导入
 * @param {*} data
 * userlist 员工ID 数组
 */
export function adminUsersTobeusers(data) {
  return request({
    url: 'admin/users/tobeusers',
    method: 'post',
    data: data
  })
}

/**
 * 部门列表数据（编辑时）
 * @param {*} data
 * id 部门ID
 * type update编辑、save添加
 */
export function adminStructuresListDialog(data) {
  return request({
    url: 'admin/structures/listDialog',
    method: 'post',
    data: data
  })
}
