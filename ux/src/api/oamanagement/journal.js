import request from '@/utils/request'

// 日志列表
export function journalList(data) {
  return request({
    url: 'oa/log/index',
    method: 'post',
    data: data
  })
}
// 新建日志
export function journalAdd(data) {
  return request({
    url: 'oa/log/save',
    method: 'post',
    data
  })
}
// 日志编辑
export function journalEdit(data) {
  return request({
    url: 'oa/log/update',
    method: 'post',
    data
  })
}
// 日志评论添加
export function journalCommentSave(data) {
  return request({
    url: 'oa/log/commentSave',
    method: 'post',
    data
  })
}
// 日志删除
export function journalDelete(data) {
  return request({
    url: 'oa/log/delete',
    method: 'post',
    data
  })
}
// 日志评论删除
export function journalCommentDelete(data) {
  return request({
    url: 'oa/log/commentDel',
    method: 'post',
    data
  })
}
// 日志标记已读
export function journalSetread(data) {
  return request({
    url: 'oa/log/setread',
    method: 'post',
    data
  })
}
