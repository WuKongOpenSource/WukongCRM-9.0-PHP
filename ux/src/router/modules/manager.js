/** 项目管理路由 */
import Layout from '@/views/layout/managerLayout'

/**
 * 系统管理里的 客户管理
 */
const systemCustomerRouter = {
  name: 'system-customer',
  path: 'system-customer',
  meta: {
    title: '客户管理',
    icon: 'customer',
    requiresAuth: true,
    index: 1,
    type: 'admin',
    subType: 'crm'
  },
  children: [{
    path: 'custom-field',
    component: () => import('@/views/SystemManagement/SystemCustomer/customField'),
    meta: {
      title: '自定义字段设置',
      requiresAuth: true,
      index: 2,
      type: 'admin',
      subType: ['crm', 'field']
    }
  }, {
    path: 'customer',
    component: () => import('@/views/SystemManagement/SystemCustomer/customer'),
    meta: {
      title: '客户公海规则设置',
      requiresAuth: true,
      index: 2,
      type: 'admin',
      subType: ['crm', 'pool']
    }
  }, {
    path: 'biz-param',
    component: () => import('@/views/SystemManagement/SystemCustomer/bizParam'),
    meta: {
      title: '业务参数设置',
      requiresAuth: true,
      index: 2,
      type: 'admin',
      subType: ['crm', 'setting']
    }
  }, {
    path: 'biz-goals',
    component: () => import('@/views/SystemManagement/SystemCustomer/bizGoals'),
    meta: {
      title: '业绩目标设置',
      requiresAuth: true,
      index: 2,
      type: 'admin',
      subType: ['crm', 'achievement']
    }
  }]
}

/**
 * 不包含children的路由
 */
const systemEmployeeRouter = {
  path: '/manager',
  component: Layout,
  redirect: '/manager/systemconfig',
  name: 'manager',
  meta: {
    requiresAuth: true,
    title: '系统管理',
    index: 0,
    type: 'admin'
  },
  children: [{
    name: 'systemconfig',
    path: 'systemconfig', // 企业首页
    component: () => import('@/views/SystemManagement/SystemConfig/index'),
    meta: {
      requiresAuth: true,
      title: '企业首页',
      icon: 'systemconfig',
      index: 2,
      type: 'admin',
      subType: ['system', 'index']
    }
  },
  {
    name: 'application',
    path: 'application', // 应用管理
    component: () => import('@/views/SystemManagement/application/index'),
    meta: {
      requiresAuth: true,
      title: '应用管理',
      icon: 'product',
      index: 2,
      type: 'admin',
      subType: ['configset', 'index']
    }
  },
  {
    name: 'employee-dep',
    path: 'employee-dep', // 员工与部门管理
    component: () => import('@/views/SystemManagement/SystemEmployee/EmployeeDepManagement'),
    meta: {
      requiresAuth: true,
      title: '员工与部门管理',
      icon: 'department',
      index: 2,
      type: 'admin',
      subType: ['users', 'index']
    }
  }
  ]
}

const authRouter = {
  path: '/role-auth',
  component: Layout,
  name: 'role-auth',
  meta: {
    requiresAuth: true,
    title: '角色权限控制',
    index: 1,
    type: 'admin',
    subType: 'groups'
  },
  children: [{
    name: 'role-auth',
    path: 'role-auth/:pid/:title',
    component: () => import('@/views/SystemManagement/roleAuth/index'),
    meta: {
      requiresAuth: true,
      title: '角色权限管理',
      icon: 'contacts',
      index: 1,
      type: 'admin',
      subType: 'groups'
    }
  }
  ]
}

const authMenuRouter = {
  path: '/role-auth',
  component: Layout,
  name: 'role-auth',
  meta: {
    requiresAuth: true,
    title: '角色权限控制',
    icon: 'contacts',
    index: 1,
    type: 'admin',
    subType: 'groups'
  },
  children: [
  ]
}

const examineWorkbenchRouter = {
  path: '/manager',
  component: Layout,
  redirect: '/manager/systemconfig',
  name: 'manager',
  meta: {
    requiresAuth: true,
    title: '系统管理',
    index: 0,
    type: 'admin'
  },
  children: [
    {
      name: 'system-examine',
      path: 'system-examine',
      component: () => import('@/views/SystemManagement/SystemExamine/SystemExamine'),
      meta: {
        requiresAuth: true,
        title: '审批流程管理',
        icon: 'examine',
        index: 1,
        type: 'admin',
        subType: 'examine_flow'
      }
    },
    {
      name: 'system-workbench',
      path: 'system-workbench', // 工作台
      component: () => import('@/views/SystemManagement/SystemWorkbench'),
      meta: {
        requiresAuth: true,
        title: '工作台',
        icon: 'workbench',
        index: 1,
        type: 'admin',
        subType: 'oa'
      }
    },
    {
      name: 'system-project',
      path: 'system-project', // 项目管理
      component: () => import('@/views/SystemManagement/project'),
      meta: {
        requiresAuth: true,
        title: '项目管理',
        icon: 'project',
        index: 1,
        type: 'admin',
        subType: 'work'
      }
    }
  ]
}

const handlefieldRouter = {
  name: 'handlefield',
  path: 'custom-field/handlefield/:type/:id', // type customer contacts business contract money
  component: () => import('@/views/SystemManagement/SystemCustomer/HandleField'),
  meta: {
    changeMenu: false, // 跳转不更改路径
    menuSelct: 'system-customer',
    menuIndex: 'custom-field'
  },
  hidden: true
}

export const managerRouter = {
  path: '/manager',
  component: Layout,
  redirect: '/manager/systemconfig',
  name: 'manager',
  hidden: true,
  meta: {
    requiresAuth: true,
    title: '系统管理',
    index: 0,
    type: 'admin'
  },
  children: [
    ...systemEmployeeRouter.children,
    ...authRouter.children,
    ...examineWorkbenchRouter.children,
    ...systemCustomerRouter.children,
    handlefieldRouter
  ]
}

export const managerRouterMenu = [
  ...systemEmployeeRouter.children,
  authMenuRouter,
  ...examineWorkbenchRouter.children,
  systemCustomerRouter
]
