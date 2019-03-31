import request from '@/utils/request'

// 公告添加
export function noticeList (data) {
    return request({
        url: 'oa/announcement/index',
        method: 'post',
        data: data
    })
}
// 公告添加
export function noticeAdd (data) {
    return request({
        url: 'oa/announcement/save',
        method: 'post',
        data: data
    })
}
// 公告编辑
export function noticeEdit (data) {
    return request({
        url: 'oa/announcement/update',
        method: 'post',
        data: data
    })
}
// 公告删除
export function noticeDelete (data) {
    return request({
        url: 'oa/announcement/delete',
        method: 'post',
        data: data
    })
}
