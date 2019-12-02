import request from '@/utils/request'

// 日程列表
export function scheduleList(data) {
  return request({
    url: 'oa/event/index',
    method: 'post',
    data: data
  })
}
// 日程添加
export function scheduleAdd(data) {
  return request({
    url: 'oa/event/save',
    method: 'post',
    data: data
  })
}
// 日程删除
export function scheduleDelete(data) {
  return request({
    url: 'oa/event/delete',
    method: 'post',
    data: data
  })
}
// 日程编辑
export function scheduleEdit(data) {
  return request({
    url: 'oa/event/update',
    method: 'post',
    data: data
  })
}
