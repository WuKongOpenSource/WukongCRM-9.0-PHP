<template>
  <div class="rc-cont">
    <flexbox
      v-if="!isSeas"
      class="rc-head"
      direction="row-reverse">
      <el-button
        class="rc-head-item"
        type="primary"
        @click.native="createClick('plan')">新建回款计划</el-button>
    </flexbox>
    <el-table
      :data="palnList"
      :height="tableHeight"
      :header-cell-style="headerRowStyle"
      :cell-style="cellStyle"
      stripe
      style="width: 100%;border: 1px solid #E6E6E6;">
      <el-table-column
        v-for="(item, index) in planFieldList"
        :key="index"
        :prop="item.prop"
        :formatter="fieldFormatter"
        :label="item.label"
        show-overflow-tooltip/>
      <el-table-column
        label="操作"
        width="100">
        <template slot-scope="scope">
          <flexbox justify="center">
            <el-button
              type="text"
              @click.native="handleFile('edit', scope)">编辑</el-button>
            <el-button
              type="text"
              @click.native="handleFile('delete', scope)">删除</el-button>
          </flexbox>
        </template>
      </el-table-column>
    </el-table>

    <flexbox
      class="rc-head"
      direction="row-reverse"
      style="margin-top: 15px;">
      <el-button
        v-if="!isSeas"
        class="rc-head-item"
        type="primary"
        @click.native="createClick('money')">新建回款</el-button>
    </flexbox>
    <el-table
      :data="list"
      :height="tableHeight"
      :header-cell-style="headerRowStyle"
      :cell-style="cellStyle"
      stripe
      style="width: 100%;border: 1px solid #E6E6E6;"
      @row-click="handleRowClick">
      <el-table-column
        v-for="(item, index) in fieldList"
        :key="index"
        :prop="item.prop"
        :formatter="fieldFormatter"
        :label="item.label"
        show-overflow-tooltip/>
    </el-table>
    <c-r-m-full-screen-detail
      :visible.sync="showFullDetail"
      :crm-type="showFullCrmType"
      :id="showFullId"/>
    <c-r-m-create-view
      v-if="isCreate"
      :crm-type="createCrmType"
      :action="createActionInfo"
      @save-success="saveSuccess"
      @hiden-view="isCreate=false"/>
  </div>
</template>

<script type="text/javascript">
import loading from '../mixins/loading'
import CRMCreateView from './CRMCreateView'
import {
  crmReceivablesIndex,
  crmReceivablesPlanIndex,
  crmReceivablesPlanDeleteAPI
} from '@/api/customermanagement/money'
import { timestampToFormatTime, moneyFormat } from '@/utils'

export default {
  name: 'RelativeReturnMoney', // 相关回款  可能再很多地方展示 放到客户管理目录下

  components: {
    CRMCreateView,
    CRMFullScreenDetail: () => import('./CRMFullScreenDetail.vue')
  },

  mixins: [loading],

  props: {
    /** 模块ID */
    id: [String, Number],
    /** 没有值就是全部类型 有值就是当个类型 */
    crmType: {
      type: String,
      default: ''
    },
    /** 客户和 合同下 可新建 回款计划 */
    detail: {
      type: Object,
      default: () => {
        return {}
      }
    },
    /** 是公海 默认是客户 */
    isSeas: {
      type: Boolean,
      default: false
    }
  },

  data() {
    return {
      list: [],
      fieldList: [],
      tableHeight: '250px',
      showFullDetail: false,
      showFullCrmType: 'receivables',
      showFullId: '', // 查看全屏详情的 ID
      createCrmType: 'receivables_plan', // 创建回款计划
      isCreate: false, // 新建回款回款
      palnList: [],
      planFieldList: [],
      createActionInfo: {} // 新建回款计划的时候 在客户 合同下导入关联信息
    }
  },

  computed: {},

  watch: {
    id: function(val) {
      this.list = []
      this.palnList = []
      this.getList()
      this.getPlanList()
    }
  },

  mounted() {
    this.planFieldList = [
      { prop: 'num', width: '200', label: '期数' },
      { prop: 'customer_id', width: '200', label: '客户名称' },
      { prop: 'contract_id', width: '200', label: '合同编号' },
      { prop: 'money', width: '200', label: '计划回款金额' },
      { prop: 'return_date', width: '200', label: '计划回款日期' },
      { prop: 'return_type', width: '200', label: '计划回款方式' },
      { prop: 'remind', width: '200', label: '提前几日提醒' },
      { prop: 'remark', width: '200', label: '备注' }
    ]

    this.getPlanList()

    this.fieldList = [
      { prop: 'number', width: '200', label: '回款编号' },
      { prop: 'contract_id', width: '200', label: '合同名称' },
      { prop: 'contract_money', width: '200', label: '合同金额' },
      { prop: 'money', width: '200', label: '回款金额' },
      { prop: 'plan_id', width: '200', label: '期数' },
      { prop: 'owner_user_id', width: '200', label: '负责人' },
      { prop: 'check_status', width: '200', label: '状态' },
      { prop: 'return_time', width: '200', label: '回款日期' }
    ]
    this.getList()
  },

  methods: {
    /**
     * 回款计划列表
     */
    getPlanList() {
      this.loading = true
      crmReceivablesPlanIndex(this.getParams())
        .then(res => {
          this.loading = false
          this.palnList = res.data.list
        })
        .catch(() => {
          this.loading = false
        })
    },

    /**
     * 回款列表
     */
    getList() {
      this.loading = true
      crmReceivablesIndex(this.getParams())
        .then(res => {
          this.loading = false
          this.list = res.data.list
        })
        .catch(() => {
          this.loading = false
        })
    },

    /**
     * 获取上传参数
     */
    getParams() {
      if (this.crmType === 'customer') {
        return { customer_id: this.id, pageType: 'all' }
      } else if (this.crmType === 'contract') {
        return { contract_id: this.id, pageType: 'all' }
      }
      return {}
    },

    /**
     * 当某一行被点击时会触发该事件
     */
    handleRowClick(row, column, event) {
      this.showFullId = row.receivables_id
      this.showFullCrmType = 'receivables'
      this.showFullDetail = true
    },

    /**
     * 通过回调控制style
     */
    cellStyle({ row, column, rowIndex, columnIndex }) {
      if (columnIndex == 1) {
        return { color: '#3E84E9' }
      } else {
        return { textAlign: 'center' }
      }
    },

    /**
     * 新建回款和回款计划
     */
    createClick(type) {
      this.createActionInfo = {
        type: 'relative',
        crmType: this.crmType,
        data: {}
      }
      if (type == 'money') {
        if (this.crmType === 'contract') {
          this.createActionInfo.data['customer'] = this.detail.customer_id_info
          this.createActionInfo.data['contract'] = this.detail
        } else if (this.crmType === 'customer') {
          this.createActionInfo.data['customer'] = this.detail
        }
        this.createCrmType = 'receivables'
        this.isCreate = true
      } else if (type == 'plan') {
        if (this.crmType === 'contract') {
          this.createActionInfo.data['customer'] = this.detail.customer_id_info
          this.createActionInfo.data['contract'] = this.detail
        } else if (this.crmType === 'customer') {
          this.createActionInfo.data['customer'] = this.detail
        }
        this.createCrmType = 'receivables_plan'
        this.isCreate = true
      }
    },

    /**
     * 新建成功
     */
    saveSuccess() {
      if (this.createCrmType == 'receivables') {
        this.getList()
      } else {
        this.getPlanList()
      }
    },

    /**
     * 编辑操作
     */
    handleFile(type, item) {
      if (type == 'edit') {
        this.createActionInfo = { type: 'update', id: item.row.plan_id }
        this.createCrmType = 'receivables_plan'
        this.isCreate = true
      } else if (type == 'delete') {
        this.$confirm('您确定要删除吗?', '提示', {
          confirmButtonText: '确定',
          cancelButtonText: '取消',
          type: 'warning'
        })
          .then(() => {
            crmReceivablesPlanDeleteAPI({
              id: item.row.plan_id
            })
              .then(res => {
                this.palnList.splice(item.$index, 1)
                this.$message.success(res.data)
              })
              .catch(() => {})
          })
          .catch(() => {
            this.$message({
              type: 'info',
              message: '已取消操作'
            })
          })
      }
    },

    /**
     * 格式化字段
     */
    fieldFormatter(row, column, cellValue) {
      // 如果需要格式化
      if (column.property === 'contract_id') {
        return row.contract_id_info.name
      } else if (column.property === 'customer_id') {
        return row.customer_id_info.name
      } else if (column.property === 'create_time') {
        return timestampToFormatTime(row.customer_id_info.create_time)
      } else if (column.property === 'owner_user_id') {
        return row.owner_user_id_info.realname
      } else if (column.property === 'plan_id') {
        return row.plan_id_info
      } else if (column.property === 'check_status') {
        return this.getStatusName(row.check_status)
      } else if (['contract_money', 'money', ''].includes(column.property)) {
        return moneyFormat(cellValue)
      }
      return row[column.property]
    },

    /**
     * 对应的状态名
     */
    getStatusName(status) {
      if (status > 5) {
        return ''
      }
      return ['待审核', '审核中', '审核通过', '已拒绝', '已撤回', '未提交'][
        status
      ]
    },

    /**
     * 通过回调控制表头style
     */
    headerRowStyle({ row, column, rowIndex, columnIndex }) {
      return { textAlign: 'center' }
    }
  }
}
</script>
<style lang="scss" scoped>
@import '../styles/relativecrm.scss';
</style>
