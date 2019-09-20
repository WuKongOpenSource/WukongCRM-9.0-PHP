import {
  login,
  logout
} from '@/api/login'
import {
  adminIndexAuthList
} from '@/api/common'

import {
  adminUsersRead
} from '@/api/personCenter/personCenter'
import {
  addAuth,
  removeAuth
} from '@/utils/auth'
import Lockr from 'lockr'

const user = {
  state: {
    userInfo: null, // 用户信息
    // 权限信息
    allAuth: null, // 总权限信息 默认空 调整动态路由
    crm: {}, // 客户管理
    bi: {}, // 商业智能
    admin: {}, // 管理后台
    oa: {}, // 办公
    work: {} // 项目管理
  },

  mutations: {
    SET_USERINFO: (state, userInfo) => {
      state.userInfo = userInfo
    },
    SET_ALLAUTH: (state, allAuth) => {
      state.allAuth = allAuth
    },
    SET_CRM: (state, crm) => {
      state.crm = crm
    },
    SET_BI: (state, bi) => {
      state.bi = bi
    },
    SET_ADMIN: (state, admin) => {
      state.admin = admin
    },
    SET_OA: (state, oa) => {
      state.oa = oa
    },
    SET_WORK: (state, work) => {
      state.work = work
    }
  },

  actions: {
    // 登录
    Login({
      commit
    }, userInfo) {
      const username = userInfo.username.trim()
      return new Promise((resolve, reject) => {
        login(username, userInfo.password).then(response => {
          const data = response.data
          Lockr.set('authKey', data.authKey)
          Lockr.set('sessionId', data.sessionId)
          Lockr.set('userInfoId', data.userInfo.id)
          Lockr.set('loginUserInfo', data.userInfo)
          Lockr.set('authList', data.authList)

          addAuth(data.authKey, data.sessionId)
          commit('SET_USERINFO', data.userInfo)
          // 权限
          commit('SET_ALLAUTH', data.authList)
          commit('SET_CRM', data.authList.crm)
          commit('SET_BI', data.authList.bi)
          commit('SET_ADMIN', data.authList.admin)
          commit('SET_OA', data.authList.oa)
          commit('SET_WORK', data.authList.work)
          resolve(data)
        }).catch(error => {
          reject(error)
        })
      })
    },

    // 获取权限
    getAuth({
      commit
    }) {
      return new Promise((resolve, reject) => {
        adminIndexAuthList().then((response) => {
          const data = response.data
          Lockr.set('authList', data)

          commit('SET_ALLAUTH', data)
          commit('SET_CRM', data.crm)
          commit('SET_BI', data.bi)
          commit('SET_ADMIN', data.admin)
          commit('SET_OA', data.oa)
          commit('SET_WORK', data.work)

          resolve(response)
        }).catch(error => {
          reject(error)
        })
      })
    },

    // 获取用户信息
    GetUserInfo({
      commit,
      state
    }) {
      return new Promise((resolve, reject) => {
        adminUsersRead().then(response => {
          commit('SET_USERINFO', response.data)
          resolve(response)
        }).catch(error => {
          reject(error)
        })
      })
    },

    // 登出
    LogOut({
      commit
    }) {
      return new Promise((resolve, reject) => {
        logout().then(() => {
          /** flush 清空localStorage .rm('authKey') 按照key清除 */
          removeAuth()
          resolve()
        }).catch(error => {
          reject(error)
        })
      })
    }
  }
}

export default user
