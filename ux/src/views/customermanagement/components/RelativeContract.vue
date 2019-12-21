<template>
  <div
    v-empty="nopermission"
    class="rc-cont"
    xs-empty-icon="nopermission"
    xs-empty-text="暂无权限">
    <flexbox
      v-if="!isSeas"
      class="rc-head"
      direction="row-reverse">
      <el-button
        class="rc-head-item"
        type="primary"
        @click.native="createClick">新建合同
      </el-button>
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
      :id="contractId"
      crm-type="contract"/>
    <c-r-m-create-view
      v-if="isCreate"
      :action="createActionInfo"
      crm-type="contract"
      @save-success="createSaveSuccess"
      @hiden-view="isCreate=false"/>
  </div>
</template>

<script type="text/javascript">
import loading from '../mixins/loading'
import CRMCreateView from './CRMCreateView'
import { crmContractIndex } from '@/api/customermanagement/contract'
import { moneyFormat } from '@/utils'

export default {
  name: 'RelativeContract', // 相关联系人  可能再很多地方展示 放到客户管理目录下
  components: {
    CRMFullScreenDetail: () => import('./CRMFullScreenDetail.vue'),
    CRMCreateView
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
    /** 是公海 默认是客户 */
    isSeas: {
      type: Boolean,
      default: false
    },
    /** 联系人人下 新建商机 需要联系人里的客户信息  合同下需要客户和商机信息 */
    detail: {
      type: Object,
      default: () => {
        return {}
      }
    }
  },
  data() {
    return {
      nopermission: false,
      list: [],
      fieldList: [],
      tableHeight: '400px',
      showFullDetail: false,
      isCreate: false, // 控制新建
      contractId: '', // 查看全屏联系人详情的 ID
      // 创建的相关信息
      createActionInfo: { type: 'relative', crmType: this.crmType, data: {}}
    }
  },
  computed: {},
  watch: {
    id: function(val) {
      this.list = []
      this.getDetail()
    }
  },
  mounted() {
    this.getDetail()
  },
  activated: function() {},
  deactivated: function() {},
  methods: {
    getFieldList() {
      this.fieldList.push({ prop: 'num', width: '200', label: '合同编号' })
      this.fieldList.push({ prop: 'name', width: '200', label: '合同名称' })
      this.fieldList.push({
        prop: 'customer_id',
        width: '200',
        label: '客户名称'
      })
      this.fieldList.push({ prop: 'money', width: '200', label: '合同金额' })
      this.fieldList.push({
        prop: 'start_time',
        width: '200',
        label: '开始日期'
      })

      this.fieldList.push({ prop: 'end_time', width: '200', label: '结束日期' })
      this.fieldList.push({ prop: 'check_status', width: '200', label: '状态' })
    },
    getDetail() {
      this.loading = true
      const params = { pageType: 'all' }
      params[this.crmType + '_id'] = this.id
      crmContractIndex(params)
        .then(res => {
          if (this.fieldList.length == 0) {
            this.getFieldList()
          }
          this.nopermission = false
          this.loading = false
          this.list = res.data.list
        })
        .catch(data => {
          if (data.code == 102) {
            this.nopermission = true
          }
          this.loading = false
        })
    },
    /** 格式化字段 */
    fieldFormatter(row, column, cellValue) {
      // 如果需要格式化
      if (column.property === 'customer_id') {
        return row.customer_id_info.name
      } else if (column.property === 'check_status') {
        return this.getStatusName(row.check_status)
      } else if (['money'].includes(column.property)) {
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

    // 当某一行被点击时会触发该事件
    handleRowClick(row, column, event) {
      this.contractId = row.contract_id
      this.showFullDetail = true
    },
    /** 通过回调控制表头style */
    headerRowStyle({ row, column, rowIndex, columnIndex }) {
      return { textAlign: 'center' }
    },
    /** 通过回调控制style */
    cellStyle({ row, column, rowIndex, columnIndex }) {
      return { textAlign: 'center' }
    },
    /** 新建 */
    createClick() {
      /** 客户 和 商机 下新建合同 */
      if (this.crmType == 'business') {
        this.createActionInfo.data['customer'] = this.detail.customer_id_info
        this.createActionInfo.data['business'] = this.detail
      } else if (this.crmType == 'customer') {
        this.createActionInfo.data['customer'] = this.detail
      }
      this.isCreate = true
    },
    createSaveSuccess() {
      this.getDetail()
    }
  }
}
</script>
<style lang="scss" scoped>
@import '../styles/relativecrm.scss';
</style>
