import request from '@/utils/request'


export function roleListFun(data) {
  return request({
    url: 'admin/groups/index',
    method: 'post',
    data: data
  })
}

export function rulesList(data) {
  return request({
    url: 'admin/rules/index',
    method: 'post',
    data: data
  })
}

export function roleAdd(data) {
  return request({
    url: 'admin/groups/save',
    method: 'post',
    data: data
  })
}

export function roleDelete(data) {
  return request({
    url: 'admin/groups/delete',
    method: 'post',
    data: data
  })
}

// 角色复制
export function roleCopy(data) {
  return request({
    url: 'admin/groups/copy',
    method: 'post',
    data: data
  })
}


// 角色编辑
export function roleUpdate(data) {
  return request({
    url: 'admin/groups/update',
    method: 'post',
    data: data
  })
}

// 添加编辑员工
export function usersEdit(data) {
  return request({
    url: 'admin/users/groups',
    method: 'post',
    data: data
  })
}

// 删除员工
export function usersDelete(data) {
  return request({
    url: 'admin/users/groupsDel',
    method: 'post',
    data: data
  })
}

// 角色分类列表
export function adminGroupsTypeListAPI(data) {
  return request({
    url: 'admin/groups/typeList',
    method: 'post',
    data: data
  })
}
