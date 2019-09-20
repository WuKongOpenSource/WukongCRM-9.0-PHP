import Layout from '@/views/layout/projectLayout'
import {
  children
} from './project'

const workbenchRouter = {
  path: '/project',
  component: Layout,
  redirect: '/project/my-task',
  name: 'project',
  meta: {
    icon: 'workbench',
    title: '项目管理',
    requiresAuth: true,
    index: 0,
    type: 'work'
  },
  children: children
}

export default workbenchRouter
