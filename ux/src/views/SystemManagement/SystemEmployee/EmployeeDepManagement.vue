<template>
  <div class="employee-dep-management">
    <p class="title"> 员工与部门管理 </p>
    <div class="system-content">
      <!-- 左边导航栏 -->
      <div
        v-loading="depLoading"
        class="system-view-nav">
        <el-tree
          ref="tree"
          :data="treeData"
          :expand-on-click-node="false"
          node-key="id"
          default-expand-all
          highlight-current
          @node-click="changeDepClick">
          <flexbox
            slot-scope="{ node, data }"
            class="node-data">
            <img
              v-if="node.expanded"
              class="node-img"
              src="@/assets/img/fold.png"
              @click="handleExpand('close',node, data)">
            <img
              v-if="!node.expanded"
              class="node-img"
              src="@/assets/img/unfold.png"
              @click="handleExpand('open',node, data)">
            <div class="node-label">{{ node.label }}</div>
            <div class="node-label-set">
              <el-button
                v-if="strucSaveAuth"
                type="text"
                size="mini"
                @click.stop="() => append(data)">
                <i class="el-icon-plus"/>
              </el-button>
              <el-button
                v-if="strucUpdateAuth"
                type="text"
                size="mini"
                @click.stop="() => edit(node, data)">
                <i class="el-icon-edit"/>
              </el-button>
              <el-button
                v-if="strucDeleteAuth"
                type="text"
                size="mini"
                @click.stop="() => remove(node, data)">
                <i class="el-icon-close"/>
              </el-button>
            </div>
          </flexbox>
        </el-tree>
      </div>
      <!-- 右边内容 -->
      <div class="system-view-table flex-index">
        <div
          v-if="selectionList.length === 0"
          class="table-top">
          <div class="icon-search lt">
            <el-input
              v-model="importInput"
              placeholder="请输入员工名称"
              @keyup.enter.native="searchClick"/>
            <i
              class="el-icon-search"
              @click="searchClick"/>
          </div>
          <div class="status">
            <span>状态</span>
            <el-select
              v-model="selectModel"
              :clearable="true"
              placeholder="请选择"
              @change="statusChange">
              <el-option
                v-for="item in statusOptions"
                :key="item.value"
                :label="item.label"
                :value="item.value"/>
            </el-select>
          </div>
          <el-dropdown
            v-if="moreTypes.length > 0"
            class="rt"
            trigger="click"
            @command="handleTypeDrop">
            <flexbox class="right-more-item">
              <div>更多</div>
              <i
                class="el-icon-arrow-down el-icon--right"
                style="color:#777;"/>
            </flexbox>
            <el-dropdown-menu slot="dropdown">
              <el-dropdown-item
                v-for="(item, index) in moreTypes"
                :key="index"
                :command="item.type">{{ item.name }}</el-dropdown-item>
            </el-dropdown-menu>
          </el-dropdown>
          <el-button
            v-if="userSaveAuth"
            type="primary"
            class="rt"
            @click="newBtn">新建员工</el-button>
        </div>
        <flexbox
          v-if="selectionList.length > 0"
          class="selection-bar">
          <div class="selected—title">已选中<span class="selected—count">{{ selectionList.length }}</span>项</div>
          <flexbox class="selection-items-box">
            <flexbox
              v-for="(item, index) in selectionInfo"
              :key="index"
              class="selection-item"
              @click.native="selectionBarClick(item.type)">
              <img
                :src="item.icon"
                class="selection-item-icon" >
              <div class="selection-item-name">{{ item.name }}</div>
            </flexbox>
          </flexbox>
        </flexbox>
        <flexbox class="content-table-header">
          <div class="content-table-header-reminder">
            <reminder
              v-if="userNoStructureGroup"
              :content="'未添加部门和角色的员工无法正常登录系统'"/>
          </div>
        </flexbox>
        <div class="flex-box">
          <el-table
            v-loading="loading"
            id="depTable"
            :data="tableData"
            :height="tableHeight"
            @selection-change="handleSelectionChange"
            @row-click="rowClick">
            <el-table-column
              v-if="tableUpdateAuth"
              type="selection"
              width="55"/>
            <el-table-column
              prop="realname"
              width="100"
              show-overflow-tooltip
              label="姓名">
              <template slot-scope="scope">
                <div class="status-name">
                  <div :style="{'background-color' : getStatusColor(scope.row.status)}"/>
                  {{ scope.row.realname }}
                </div>
              </template>
              <template
                slot="header"
                slot-scope="scope">
                <div class="table-head-name">{{ scope.column.label }}</div>
              </template>
            </el-table-column>
            <el-table-column
              v-for="(item, index) in fieldList"
              :key="index"
              :width="item.width"
              :prop="item.field"
              :label="item.value"
              show-overflow-tooltip>
              <template
                slot="header"
                slot-scope="scope">
                <div class="table-head-name">{{ scope.column.label }}</div>
              </template>
            </el-table-column>
            <el-table-column/>
          </el-table>
          <div class="p-contianer">
            <div class="status-des">
              <div
                v-for="item in statusOptions"
                :key="item.value"
                class="status-des-item">
                <div :style="{'background-color' : getStatusColor(item.value)}"/>
                {{ item.label }}
              </div>
            </div>
            <el-pagination
              :current-page="currentPage"
              :page-sizes="pageSizes"
              :page-size.sync="pageSize"
              :total="total"
              class="p-bar"
              layout="total, sizes, prev, pager, next, jumper"
              @size-change="handleSizeChange"
              @current-change="handleCurrentChange"/>
          </div>
        </div>
      </div>
    </div>
    <!-- 导航新增编辑弹出框 -->
    <el-dialog
      :visible.sync="depCreateDialog"
      :title="navBtnTitle"
      :before-close="navHandleClose"
      width="30%">
      <div class="nav-dialog-div">
        <label>{{ labelName }}：</label>
        <el-input
          v-model="treeInput"
          placeholder="请输入内容"/>
      </div>
      <div
        v-if="depSelect != 0"
        class="nav-dialog-div">
        <label>上级部门：</label>
        <el-select
          v-model="depSelect"
          :clearable="false"
          placeholder="请选择">
          <el-option
            v-for="item in dialogOptions"
            :key="item.id"
            :label="item.name"
            :value="item.id"/>
        </el-select>
      </div>
      <span
        slot="footer"
        class="dialog-footer">
        <el-button @click="depCreateDialog = false">取 消</el-button>
        <el-button
          type="primary"
          @click="submitDialog">确 定</el-button>
      </span>
    </el-dialog>
    <!-- 详情 -->
    <employee-detail
      v-if="employeeDetailDialog"
      :data="dialogData"
      @edit="editBtn"
      @command="handleCommand"
      @hide-view="employeeDetailDialog=false"/>
    <!-- 重置密码 -->
    <el-dialog
      v-loading="loading"
      :visible.sync="resetPasswordVisible"
      :close-on-click-modal="false"
      :modal-append-to-body="false"
      :before-close="resetPasswordClose"
      title="重置密码"
      width="30%">
      <div class="el-password">
        <el-form
          ref="passForm"
          :model="passForm"
          :rules="rules">
          <el-form-item
            label="密码"
            prop="new_pwd">
            <el-input
              v-model="passForm.password"
              type="password"/>
          </el-form-item>
        </el-form>
      </div>
      <span
        slot="footer"
        class="dialog-footer">
        <el-button @click="resetPasswordClose">取 消</el-button>
        <el-button
          type="primary"
          @click="passSubmit(passForm)">确 定</el-button>
      </span>
    </el-dialog>

    <!-- 重置登录账号 -->
    <el-dialog
      v-loading="loading"
      :visible.sync="resetUserNameVisible"
      :close-on-click-modal="false"
      :modal-append-to-body="false"
      :before-close="()=>{resetUserNameVisible = false}"
      title="重置登录账号"
      width="30%">
      <div class="el-password">
        <el-form
          ref="resetUserNameForm"
          :model="resetUserNameForm"
          :rules="dialogRules">
          <el-form-item
            label="新账号（手机号）"
            prop="username">
            <el-input v-model="resetUserNameForm.username"/>
          </el-form-item>
          <el-form-item
            label="新密码"
            prop="password">
            <el-input
              v-model="resetUserNameForm.password"
              type="password"/>
          </el-form-item>
        </el-form>
        <div
          class="tips"
          style="margin-top: 20px;">重置登录帐号后，员工需用新账号登录。请及时告知员工，确保正常使用</div>
      </div>
      <span
        slot="footer"
        class="dialog-footer">
        <el-button @click="()=>{resetUserNameVisible = false}">取 消</el-button>
        <el-button
          type="primary"
          @click="passUserNameSubmit(resetUserNameForm)">确 定</el-button>
      </span>
    </el-dialog>

    <!-- 新建和编辑 -->
    <el-dialog
      v-loading="loading"
      v-if="employeeCreateDialog"
      :title="dialogTitle"
      :visible.sync="employeeCreateDialog"
      :close-on-click-modal="false"
      :popper-append-to-body="false"
      :append-to-body="true"
      :before-close="newHandleClose"
      width="60%">
      <p class="new-dialog-title">基本信息</p>
      <el-form
        ref="dialogRef"
        :inline="true"
        :model="formInline"
        :rules="dialogRules"
        class="new-dialog-form"
        label-width="80px"
        label-position="top">
        <el-form-item
          v-for="(item, index) in tableList"
          :label="item.value"
          :prop="item.field"
          :key="index">
          <span slot="label">{{ item.value }}</span>
          <el-tooltip
            v-if="item.tips"
            slot="label"
            :content="item.tips"
            effect="dark"
            placement="top">
            <i class="wukong wukong-help_tips"/>
          </el-tooltip>
          <template v-if="item.type == 'select'">
            <el-select
              v-model="formInline[item.field]"
              filterable
              placeholder="请选择">
              <el-option
                v-for="optionItem in optionsList[item.field].list"
                :key="optionItem.id"
                :label="optionItem.name"
                :value="optionItem.id"/>
            </el-select>
          </template>
          <template v-else-if="item.type == 'selectCheckout'">
            <el-select
              v-model="formInline[item.field]"
              :popper-append-to-body="false"
              popper-class="select-popper-class"
              filterable
              multiple
              placeholder="请选择">
              <el-option-group
                v-for="group in groupsList"
                :key="group.pid"
                :label="group.name">
                <el-option
                  v-for="item in group.list"
                  :key="item.id"
                  :label="item.title"
                  :value="item.id"/>
              </el-option-group>
            </el-select>
          </template>
          <el-input
            v-else
            v-model="formInline[item.field]"
            :disabled="dialogTitle == '编辑员工' && item.field == 'username'"/>
        </el-form-item>
      </el-form>
      <span
        slot="footer"
        class="dialog-footer">
        <el-button
          type="primary"
          @click="newDialogSubmit">保 存</el-button>
        <el-button @click="employeeCreateDialog = false">取 消</el-button>
      </span>
    </el-dialog>

    <!-- 批量设置直属上级 -->
    <el-dialog
      v-loading="loading"
      :visible.sync="setParentVisible"
      :close-on-click-modal="false"
      :modal-append-to-body="false"
      :before-close="setParentClose"
      title="设置直属上级"
      width="30%">
      <div class="el-password">
        <el-form
          ref="parentForm"
          :model="parentForm"
          class="demo-ruleForm parentForm">
          <el-form-item
            :rules="[
              { required: true, message: '直属上级不能为空'}
            ]"
            prop="parent_id"
            label="直属上级">
            <el-select
              v-model="parentForm.parent_id"
              filterable
              placeholder="请选择">
              <el-option
                v-for="optionItem in optionsList['parent_id'].list"
                :key="optionItem.id"
                :label="optionItem.name"
                :value="optionItem.id"/>
            </el-select>
          </el-form-item>
        </el-form>
      </div>
      <span
        slot="footer"
        class="dialog-footer">
        <el-button @click="setParentClose">取 消</el-button>
        <el-button
          type="primary"
          @click="parentSubmit(parentForm)">确 定</el-button>
      </span>
    </el-dialog>

    <c-r-m-import
      :show="showCRMImport"
      :crm-type="'user'"
      @listRefresh="listRefresh"
      @close="showCRMImport=false"/>
  </div>
</template>

<script>
import {
  depTreeList,
  depDelete,
  depEdit,
  depSave,
  adminUsersIndex,
  usersAdd,
  roleList,
  usersUpdate,
  adminUsersUpdatePwd,
  userSetParent,
  adminUsersUsernameEditAPI,
  usersEditStatus,
  adminStructuresListDialog
} from '@/api/systemManagement/EmployeeDepManagement'
import { usersList as selectUsersList, depList } from '@/api/common' // 直属上级接口
import EmployeeDetail from './components/employeeDetail'
import { mapGetters } from 'vuex'
import CRMImport from '@/views/customermanagement/components/CRMImport'
import Reminder from '@/components/reminder'

export default {
  /** 系统管理 的 员工部门管理 */
  name: 'EmployeeDepManagement',
  components: {
    EmployeeDetail,
    CRMImport,
    Reminder
  },
  data() {
    return {
      // 右边导航
      navBtnTitle: '新建',
      depCreateDialog: false, // 控制部门新增 编辑 数据
      depSelect: '',
      dialogOptions: [],
      labelName: '',
      treeData: [],
      depLoading: false, // 左侧部门loading效果
      // 列表
      loading: false, // 表的加载动画
      importInput: '', // 搜索
      statusOptions: [
        { value: '0', label: '禁用' },
        { value: '1', label: '激活' },
        { value: '2', label: '未激活' }
      ],
      selectModel: '', // 状态值 用于筛选
      /** 列表 */
      fieldList: [
        { field: 'username', value: '手机号（登录名）', width: '150' },
        { field: 'sex', value: '性别', type: 'select', width: '50' },
        { field: 'email', value: '邮箱', width: '150' },
        { field: 's_name', value: '部门', type: 'select', width: '100' },
        { field: 'post', value: '岗位', width: '150' },
        {
          field: 'parent_name',
          value: '直属上级',
          type: 'select',
          width: '150'
        },
        { field: 'groups', value: '角色', type: 'selectCheckout', width: '150' }
      ],
      selectionList: [], // 批量勾选数据
      tableData: [],
      // tableHeight: document.documentElement.clientHeight - 260, // 表的高度
      /** 分页逻辑 */
      structureValue: '', // 左侧列表选中的值 用于筛选
      currentPage: 1,
      pageSize: 15,
      pageSizes: [15, 30, 45, 60],
      total: 0,
      /** ** */
      employeeDetailDialog: false,
      dialogData: {},
      // 新建和编辑
      employeeCreateDialog: false,
      dialogTitle: '新建员工',
      formInline: {},
      treeInput: '',
      // 编辑部门时id
      treeEditId: '',
      optionsList: {
        structure_id: {
          field: 'structure_id',
          list: []
        },
        parent_id: {
          field: 'parent_id',
          list: []
        },
        sex: {
          field: 'sex',
          list: [{ id: '男', name: '男' }, { id: '女', name: '女' }]
        }
      },
      groupsList: [],
      // 重置密码
      resetPasswordVisible: false,
      // 批量设置直属上级
      setParentVisible: false,
      rules: {
        password: [
          { required: true, message: '请输入旧密码', trigger: 'blur' },
          { min: 6, max: 12, message: '长度在 6 到 12 个字符', trigger: 'blur' }
        ],
        username: [
          { required: true, message: '手机号不能为空', trigger: 'blur' }
        ]
      },
      passForm: {},
      dialogRules: {
        realname: [
          { required: true, message: '姓名不能为空', trigger: 'blur' }
        ],
        password: [
          { required: true, message: '密码不能为空', trigger: 'blur' },
          { min: 6, message: '长度至少为6个字符', trigger: 'blur' }
        ],
        username: [
          { required: true, message: '手机号码不能为空', trigger: 'blur' },
          {
            pattern: /^1\d{10}/,
            message: '目前只支持中国大陆的手机号码',
            trigger: 'blur'
          }
        ],
        email: [
          {
            pattern: /^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/,
            message: '请输入正确的邮箱格式',
            trigger: 'blur'
          }
        ],
        structure_id: [
          { required: true, message: '部门不能为空', trigger: 'change' }
        ],
        group_id: [
          { required: true, message: '角色不能为空', trigger: 'change' }
        ]
      },
      // 重置登录账号
      resetUserNameVisible: false,
      resetUserNameForm: {
        username: '',
        password: ''
      },
      // 更多 导入，导出
      moreTypes: [
        {
          type: 'import',
          name: '导入'
        // },
        // {
        //   type: 'export',
        //   name: '导出'
        }
      ],
      // 导入组件展示
      showCRMImport: false,
      // 直属上级
      parentForm: {},
      parent_id: 0,
      tableHeight: document.documentElement.clientHeight - 260
    }
  },
  computed: {
    ...mapGetters(['admin']),
    // 员工创建权限
    userSaveAuth() {
      return this.admin && this.admin.users && this.admin.users.save
    },
    // 员工编辑操作权限
    userUpdateAuth() {
      return this.admin && this.admin.users && this.admin.users.update
    },
    // 员工禁用启用权限
    userEnablesAuth() {
      return this.admin && this.admin.users && this.admin.users.enables
    },
    // 员工列表的勾选编辑
    tableUpdateAuth() {
      return this.userEnablesAuth && this.userUpdateAuth
    },
    // 部门编辑权限
    strucSaveAuth() {
      return this.admin && this.admin.users && this.admin.users.structures_save
    },
    // 部门编辑权限
    strucUpdateAuth() {
      return (
        this.admin && this.admin.users && this.admin.users.structures_update
      )
    },
    // 部门编辑权限
    strucDeleteAuth() {
      return (
        this.admin && this.admin.users && this.admin.users.structures_delete
      )
    },
    selectionInfo: function() {
      let temps = []
      if (this.userEnablesAuth) {
        temps = [
          {
            name: '禁用',
            type: 'lock',
            icon: require('@/assets/img/selection_disable.png')
          },
          {
            name: '激活',
            type: 'unlock',
            icon: require('@/assets/img/selection_start.png')
          },
          {
            name: '设置直属上级',
            type: 'setParent',
            icon: require('@/assets/img/selection_alloc.png')
          }
        ]
      }
      if (this.userUpdateAuth) {
        if (this.selectionList.length === 1) {
          temps = temps.concat([
            {
              name: '编辑',
              type: 'edit',
              icon: require('@/assets/img/selection_edit.png')
            },
            {
              name: '重置密码',
              type: 'reset',
              icon: require('@/assets/img/selection_reset.png')
            },
            {
              name: '重置登录账号',
              type: 'resetName',
              icon: require('@/assets/img/section_reset_name.png')
            }
          ])
        } else {
          temps = temps.concat([
            {
              name: '重置密码',
              type: 'reset',
              icon: require('@/assets/img/selection_reset.png')
            }
          ])
        }
      }

      return temps
    },
    /** 添加列表 */
    tableList: function() {
      if (this.dialogTitle === '新建员工') {
        return [
          { field: 'username', value: '手机号（登录名）' },
          { field: 'password', value: '登录密码' },
          { field: 'realname', value: '姓名' },
          { field: 'sex', value: '性别', type: 'select' },
          { field: 'email', value: '邮箱' },
          { field: 'structure_id', value: '部门', type: 'select' },
          { field: 'post', value: '岗位' },
          { field: 'parent_id', value: '直属上级', type: 'select' },
          { field: 'group_id', value: '角色', type: 'selectCheckout' }
        ]
      } else {
        return [
          {
            field: 'username',
            value: '手机号（登录名）',
            tips: '如需修改登录名，请在列表勾选员工后进行操作'
          },
          { field: 'realname', value: '姓名' },
          { field: 'sex', value: '性别', type: 'select' },
          { field: 'email', value: '邮箱' },
          { field: 'structure_id', value: '部门', type: 'select' },
          { field: 'post', value: '岗位' },
          { field: 'parent_id', value: '直属上级', type: 'select' },
          { field: 'group_id', value: '角色', type: 'selectCheckout' }
        ]
      }
    },
    // 员工无部门、角色提醒
    userNoStructureGroup() {
      const temp = this.tableData.filter((val) => {
        return !val.groups || !val.s_name
      })
      return temp.length > 0
    }
  },
  watch: {
    userNoStructureGroup(val) {
      if (val) {
        this.tableHeight -= 38
      } else {
        this.tableHeight = document.documentElement.clientHeight - 260
      }
    }
  },
  mounted() {
    /** 控制table的高度 */
    window.onresize = () => {
      this.tableHeight = document.documentElement.clientHeight - 260
    }

    // 部门树形列表
    this.treeListFun()
    this.getSelectUserList() // 直属上级列表
    this.usersListFun()
    this.getDepList()
    // 角色列表
    roleList({ tree: 1 }).then(res => {
      this.groupsList = res.data
    })
    document.getElementsByClassName('el-select-dropdown')[0].style.color = 'red'
  },
  methods: {
    // 改变部门
    changeDepClick(data) {
      this.currentPage = 1
      this.structureValue = data.id
      this.usersListFun()
    },
    /**
     * 展开闭合操作
     */
    handleExpand(type, node, data) {
      if (type == 'close') {
        if (data.children) {
          node.expanded = false
        }
      } else if (type == 'open') {
        node.expanded = true
      }
    },
    handleClose() {
      this.employeeDetailDialog = false
    },
    // 第一列点击事件
    rowClick(row, column, event) {
      this.dialogData = row
      if (column.property == 'realname') {
        this.employeeDetailDialog = true
      }
    },
    // 新建和编辑
    newHandleClose() {
      this.employeeCreateDialog = false
    },
    // 新建用户
    newBtn() {
      this.employeeCreateDialog = true
      this.dialogTitle = '新建员工'
      this.formInline = {
        group_id: []
      }
    },
    // 详情 -- 编辑用户
    editBtn() {
      this.dialogTitle = '编辑员工'
      var detail = {}
      for (let index = 0; index < this.tableList.length; index++) {
        const element = this.tableList[index]
        if (element.field !== 'password') {
          if (element.field === 'group_id') {
            detail[element.field] = this.dialogData.groupids
              ? this.dialogData.groupids
                .split(',')
                .map(function(item, index, array) {
                  return parseInt(item)
                })
              : []
          } else {
            if (element.field == 'parent_id') {
              detail[element.field] =
                this.dialogData[element.field] == 0
                  ? ''
                  : this.dialogData[element.field]
            } else {
              detail[element.field] = this.dialogData[element.field]
            }
          }
        }
      }
      detail['id'] = this.dialogData.id
      this.formInline = detail
      this.employeeCreateDialog = true
    },
    // 增加组织架构
    // 部门非树形结构列表 用于部门添加
    getDepList() {
      depList().then(response => {
        this.optionsList['structure_id'].list = response.data
      })
    },
    append(data) {
      this.treeInput = ''
      this.labelName = '新增部门'
      this.navBtnTitle = '新增部门'
      this.depSelect = data.id
      this.getStructuresListBySuperior({ id: data.id, type: 'save' })
      this.depCreateDialog = true
    },
    // 获取新增部门 上级部门信息
    getStructuresListBySuperior(data) {
      this.dialogOptions = []
      adminStructuresListDialog(data).then(response => {
        this.dialogOptions = response.data
      })
    },
    // 编辑组织架构
    edit(node, data) {
      this.treeInput = data.label
      this.treeEditId = data.id
      this.depSelect = data.pid
      this.navBtnTitle = '编辑部门'
      this.labelName = '编辑部门'
      this.getStructuresListBySuperior({ id: data.id, type: 'update' })
      this.depCreateDialog = true
    },
    // 删除组织架构
    remove(node, data) {
      this.$confirm('此操作将永久删除, 是否继续?', '提示', {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning'
      })
        .then(() => {
          this.loading = true
          depDelete({ id: data.id })
            .then(res => {
              this.treeListFun()
              this.$message.success('删除成功')
              this.loading = false
            })
            .catch(() => {
              this.loading = false
            })
        })
        .catch(() => {
          this.$message({
            type: 'info',
            message: '已取消删除'
          })
        })
    },
    // 关闭新增或编辑
    navHandleClose() {
      this.depCreateDialog = false
    },
    // 新增或编辑确定按钮
    submitDialog() {
      if (this.labelName == '新增部门') {
        depSave({ name: this.treeInput, pid: this.depSelect }).then(res => {
          this.getDepList() // 增加了新部门 刷新数据
          this.treeListFun()
          this.navHandleClose()
        })
      } else {
        depEdit({
          name: this.treeInput,
          id: this.treeEditId,
          pid: this.depSelect
        }).then(res => {
          this.treeListFun()
          this.navHandleClose()
        })
      }
    },
    // 获取树形列表
    treeListFun() {
      this.depLoading = true
      depTreeList()
        .then(response => {
          this.treeData = response.data
          this.depLoading = false
        })
        .catch(() => {
          this.depLoading = false
        })
    },
    // 搜索框
    searchClick() {
      this.currentPage = 1
      this.usersListFun()
    },
    // 状态筛选
    statusChange() {
      this.currentPage = 1
      this.usersListFun()
    },
    // 用户新建
    newDialogSubmit() {
      this.$refs.dialogRef.validate(valid => {
        if (valid) {
          if (this.dialogTitle == '新建员工') {
            this.loading = true
            usersAdd(this.formInline)
              .then(res => {
                this.$message.success('新增成功')
                this.employeeCreateDialog = false
                this.usersListFun()
                this.getSelectUserList()
                this.loading = false
              })
              .catch(() => {
                this.loading = false
              })
          } else {
            this.loading = true
            usersUpdate(this.formInline)
              .then(res => {
                if (this.employeeDetailDialog) {
                  this.employeeDetailDialog = false
                }
                this.employeeCreateDialog = false
                this.$message.success('编辑成功')
                this.usersListFun()
                this.getSelectUserList()
                this.loading = false
              })
              .catch(() => {
                this.loading = false
              })
          }
        } else {
          return false
        }
      })
    },
    // 详情里面点击事件
    handleCommand(command) {
      switch (command) {
        case 'reset':
          // 当前登录用户ID
          this.passForm = {
            password: ''
          }
          this.resetPasswordVisible = true
          break
        case 'status':
          usersEditStatus({
            id: [this.dialogData.id],
            status: this.dialogData.status == 0 ? 1 : 0
          }).then(res => {
            this.employeeDetailDialog = false
            this.$message.success('修改成功')
            this.usersListFun()
          })
          break
      }
    },
    /** 操作 */
    selectionBarClick(type) {
      var ids = this.selectionList.map(function(item, index, array) {
        return item.id
      })
      if (type === 'lock' || type === 'unlock') {
        var message = type === 'lock' ? '禁用' : '激活'
        this.$confirm('这些员工账号将被' + message + ', 是否继续?', '提示', {
          confirmButtonText: '确定',
          cancelButtonText: '取消',
          type: 'warning'
        })
          .then(() => {
            this.loading = true
            usersEditStatus({
              id: ids,
              status: type === 'unlock' ? 1 : 0
            })
              .then(res => {
                this.loading = false
                this.$message.success('修改成功')
                this.usersListFun()
              })
              .catch(() => {
                this.loading = false
              })
          })
          .catch(() => {
            this.$message({
              type: 'info',
              message: '已取消删除'
            })
          })
      } else if (type === 'reset') {
        this.resetPasswordVisible = true
      } else if (type === 'resetName') {
        this.resetUserNameVisible = true
      } else if (type === 'edit') {
        this.dialogData = this.selectionList[0]

        this.dialogTitle = '编辑员工'
        var detail = {}
        for (let index = 0; index < this.tableList.length; index++) {
          const element = this.tableList[index]
          if (element.field !== 'password') {
            if (element.field === 'group_id') {
              detail[element.field] = this.dialogData.groupids
                ? this.dialogData.groupids
                  .split(',')
                  .map(function(item, index, array) {
                    return parseInt(item)
                  })
                : []
            } else {
              detail[element.field] = this.dialogData[element.field]
            }
          }
        }
        detail['id'] = this.dialogData.id
        this.formInline = detail
        this.employeeCreateDialog = true
      } else if (type === 'setParent') {
        this.setParentVisible = true
        this.parentForm = {}
        this.parent_id = null
      }
    },
    // 重置密码 -- 关闭按钮
    resetPasswordClose() {
      this.resetPasswordVisible = false
    },
    // 重置密码 -- 确定按钮
    passSubmit(val) {
      var ids = []
      if (this.selectionList.length > 0) {
        ids = this.selectionList.map(function(item, index, array) {
          return item.id
        })
      } else {
        ids = [this.dialogData.id]
      }
      val.id = ids
      this.loading = true
      adminUsersUpdatePwd(val)
        .then(res => {
          this.$message.success('重置成功')
          this.resetPasswordClose()
          this.loading = false
        })
        .catch(() => {
          this.loading = false
        })
    },
    // 设置直属上级 --关闭按钮
    setParentClose() {
      this.setParentVisible = false
    },
    // 设置直属上级 --确认按钮
    parentSubmit(param) {
      // console.log(param)
      // let data = {};
      // data.user
      this.$refs.parentForm.validate(valid => {
        if (valid) {
          if (this.selectionList.length > 0) {
            let id_list = []
            id_list = this.selectionList.map((val) => {
              return val.id
            })
            param.id_list = id_list
            this.loading = true
            userSetParent(param)
              .then(res => {
                this.$message.success(res.data)
                this.searchClick()
                this.setParentVisible = false
                this.loading = false
              })
              .catch(() => {
                this.loading = false
              })
            // this.$message('1111')
          } else {
            this.$message('2333')
            return false
          }
        }
      })
    },

    /**
     * 重置登录账号
     */
    passUserNameSubmit(val) {
      this.$refs.resetUserNameForm.validate(valid => {
        if (valid) {
          if (this.selectionList.length > 0) {
            val.id = this.selectionList[0].id
            this.loading = true
            adminUsersUsernameEditAPI(val)
              .then(res => {
                this.$message.success(res.data)
                this.searchClick()
                this.resetUserNameVisible = false
                this.loading = false
              })
              .catch(() => {
                this.loading = false
              })
          }
        } else {
          return false
        }
      })
    },
    // 更改每页展示数量
    handleSizeChange(val) {
      this.pageSize = val
      this.usersListFun()
    },
    // 更改当前页数
    handleCurrentChange(val) {
      this.currentPage = val
      this.usersListFun()
    },
    // 勾选
    handleSelectionChange(val) {
      this.selectionList = val // 勾选的行
    },
    // 用户列表
    usersListFun() {
      this.loading = true
      var params = {
        page: this.currentPage,
        limit: this.pageSize,
        search: this.importInput,
        structure_id: this.structureValue
      }
      if (this.selectModel) {
        params.status = this.selectModel
      } else {
        params.status = 'all'
      }
      adminUsersIndex(params)
        .then(res => {
          this.tableData = res.data.list.map((item, index, array) => {
            if (item.sex == '0') {
              item.sex = ''
            }
            return item
          })
          this.total = res.data.dataCount
          this.loading = false
        })
        .catch(() => {
          this.loading = false
        })
    },
    /** 获取选择直属上级列表 */
    getSelectUserList() {
      this.loading = true
      selectUsersList({})
        .then(res => {
          this.optionsList['parent_id'].list = []
          for (const i of res.data) {
            this.optionsList['parent_id'].list.push({
              id: i.id,
              name: i.realname
            })
          }
          this.loading = false
        })
        .catch(() => {
          this.loading = false
        })
    },
    // 获取状态颜色
    getStatusColor(status) {
      if (status == 0) {
        return '#FF6767'
      } else if (status == 1) {
        return '#46CDCF'
      } else if (status == 2) {
        return '#CCCCCC'
      }
    },
    // 更多按钮
    handleTypeDrop(command, params = {}) {
      if (command == 'import') {
        this.showCRMImport = true
      } else if (command == 'export') {
        console.log('export')
      }
    },
    // 刷新列表
    listRefresh() {
      this.usersListFun()
    }
  }
}
</script>

<style lang="scss" scoped>
@import '../styles/table.scss';

.employee-dep-management {
  /* padding: 0 20px 20px; */
  height: 100%;
  box-sizing: border-box;
  display: flex;
  flex-direction: column;
}
.system-content {
  position: relative;
  height: 100%;
  flex: 1;
  display: flex;
  overflow: hidden;
}
.system-view-nav {
  width: 200px;
  height: 100%;
  overflow: auto;
  margin-right: 10px;
  background: #fff;
  padding-top: 20px;
  border: 1px solid #e6e6e6;
}
.title {
  font-size: 18px;
  height: 40px;
  line-height: 40px;
  margin: 10px 0;
  color: #333;
  padding: 0 20px;
}
.system-view-table {
  background: #fff;
  border: 1px solid #e6e6e6;
  /* flex: 1; */
  position: absolute;
  top: 0;
  left: 210px;
  bottom: 0;
  right: 0;
}

.table-top {
  padding: 10px 0;
}

.status {
  display: inline-block;
  margin-left: 50px;
}
.status > span {
  margin-right: 10px;
}

.status-name {
  div {
    display: inline-block;
    width: 6px;
    height: 6px;
    border-radius: 3px;
  }
  color: $xr-color-primary;
  cursor: pointer;
}
/* 详情 */
.employee-dep-management /deep/ .el-dialog__wrapper {
  margin-top: 60px !important;
}
// .employee-dep-management /deep/ .position-flxed-animation {
//   left: 70%;
//   height: 100%;
//   color: red;
//   margin: 0 !important;
// }
.dialog-top > img {
  vertical-align: middle;
  margin-right: 10px;
  height: 36px;
}
.dialog-btn-group {
  float: right;
}
.dialog-remark {
  font-size: 14px;
  color: #999;
  margin-top: 10px;
}
.dialog-content {
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid #e6e6e6;
}
.dialog-content > div {
  padding: 10px 0;
}
.dialog-content > div > label {
  color: #777;
  width: 30%;
  display: inline-block;
}
/* 新建和编辑 */
.new-dialog-title {
  padding-left: 10px;
  margin-bottom: 3px;
  border-left: 2px solid #46cdcf;
}
.new-dialog-form {
  height: 47vh;
  overflow-y: auto;
  padding: 20px;
}
.new-dialog-form /deep/ .el-form-item {
  width: 50%;
  margin: 0;
  padding-bottom: 10px;
}
.new-dialog-form /deep/ .el-form-item .el-form-item__label {
  padding: 0;
}
.new-dialog-form /deep/ .el-form-item .el-form-item__content {
  width: 70%;
}
.nav-dialog-div {
  margin-bottom: 20px;
}
.nav-dialog-div /deep/ .el-input {
  width: auto;
}
/** 树形结构 */
.el-tree /deep/ .el-tree-node__expand-icon {
  display: none;
}
.el-tree /deep/ .el-tree-node__content {
  height: 30px;

  .node-data {
    .node-img {
      width: 15px;
      height: 15px;
      display: block;
      margin-right: 8px;
      margin-left: 24px;
    }
    .node-label {
      margin-right: 8px;
    }
    .node-label-set {
      display: none;
    }
  }

  .node-data:hover .node-label-set {
    display: block;
  }
}
.el-tree /deep/ .el-tree-node.is-current > .el-tree-node__content {
  background-color: #ebf3ff;
  border-right: 2px solid #46cdcf;
  .node-label-set {
    display: block;
  }
}
.system-view-nav /deep/ .el-tree-node > .el-tree-node__children {
  overflow: visible;
}
.system-view-nav /deep/ .el-tree > .el-tree-node {
  min-width: 100%;
  display: inline-block !important;
}
/* 搜索框图标按钮 */
.icon-search .el-icon-search {
  position: absolute;
  top: 0;
  right: 0;
  bottom: 0;
  width: 40px;
  line-height: 40px;
  text-align: center;
  cursor: pointer;
  font-size: 20px;
  color: #ccc;
}
/* 设置flex布局 */
.flex-index {
  display: flex;
  flex-direction: column;
}
/* 设置占位 */
.flex-box {
  flex: 1;
  border-bottom: 1px solid #e6e6e6;
}
/* 搜索框 */
.icon-search {
  width: 280px;
  position: relative;
}
.new-dialog-form /deep/ .el-select {
  display: block;
}

/** 分页布局 */
.p-contianer {
  position: relative;
  background-color: white;
  height: 44px;
  .p-bar {
    float: right;
    margin: 5px 100px 0 0;
    font-size: 14px !important;
  }
}

/** 勾选操作 */
.selection-bar {
  font-size: 12px;
  height: 54px;
  min-height: 54px;
  padding: 0 20px;
  color: #777;

  .selected—title {
    flex-shrink: 0;
    padding-right: 20px;
    border-right: 1px solid $--table-border-color;
    .selected—count {
      color: $xr-color-primary;
    }
  }
}

.selection-items-box {
  .selection-item {
    width: auto;
    padding: 15px;
    .selection-item-icon {
      display: block;
      margin-right: 5px;
      width: 15px;
      height: 15px;
    }
    .selection-item-name {
      cursor: pointer;
      color: #777;
    }
    .selection-item-name:hover {
      color: $xr-color-primary;
    }
  }
}
.new-dialog-form
  /deep/
  .el-form-item
  .el-form-item__content
  .el-select-group__wrap:not(:last-of-type)::after {
  display: none;
}
.new-dialog-form /deep/ .el-form-item .el-form-item__content .el-select-group {
  padding-left: 10px;
}
.new-dialog-form
  /deep/
  .el-form-item
  .el-form-item__content
  .el-select-group__title {
  border-bottom: 1px solid #e4e7ed;
  padding: 0 0 7px;
  margin: 0 20px 5px;
}

.status-des {
  font-size: 12px;
  color: #777777;
  margin: 0 5px;
  position: absolute;
  left: 0;
  top: 7px;
  .status-des-item {
    margin: 8px;
    display: inline-block;
    div {
      display: inline-block;
      width: 6px;
      height: 6px;
      border-radius: 3px;
      margin-right: 5px;
    }
  }
}

// 提示
// 提示标志
.wukong-help_tips {
  color: #999;
  font-size: 14px;
  margin-left: 3px;
  cursor: pointer;
}

.wukong-help_tips:hover {
  color: $xr-color-primary;
}

// 修改密码和修改登录名的样式
.el-password {
  .el-form-item {
    margin-bottom: 5px;
  }
}

.el-dialog__wrapper /deep/.el-dialog__body {
  padding: 20px;
}

.tips {
  font-size: 13px;
  color: #999;
}

.el-dropdown {
  border: 1px solid #dcdfe6;
  background-color: #fff;
  font-size: 13px;
  color: #777;
  padding: 8px 12px;
  border-radius: 2px;
  height: 31px;
  margin: 0 15px 0 -20px;
  cursor: pointer;
}

.parentForm /deep/ {
  .el-form-item__error {
    margin-left: 80px;
  }
}

.content-table-header-reminder {
  margin: 0 0 10px 30px;
}
</style>

