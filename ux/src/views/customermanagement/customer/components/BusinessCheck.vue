<template>
  <div
    v-empty="!canShowIndex"
    class="container"
    xs-empty-icon="nopermission"
    xs-empty-text="暂无权限">
    <flexbox class="header">
      <div class="name">{{ data.row.name }}</div>
      <div class="detail">商机（{{ list.length }}）</div>
      <img
        class="close"
        src="@/assets/img/task_close.png"
        @click="hidenView" >
    </flexbox>
    <el-table
      v-loading="loading"
      :data="list"
      :cell-style="cellStyle"
      :header-cell-style="headerCellStyle"
      height="250"
      stripe
      style="margin-right:3px;"
      highlight-current-row
      @row-click="handleRowClick">
      <el-table-column
        v-for="(item, index) in fieldList"
        :key="index"
        :formatter="fieldFormatter"
        :prop="item.prop"
        :label="item.label"
        align="center"
        header-align="center"
        show-overflow-tooltip/>
    </el-table>
  </div>
</template>

<script>
import { mapGetters } from 'vuex'
import { crmBusinessIndex } from '@/api/customermanagement/business'

export default {
  /** 客户管理 的 客户列表  相关商机列表*/
  name: 'BusinessCheck',
  components: {},

  props: {
    show: Boolean,
    data: {
      type: Object,
      default: () => {
        return {
          row: {
            name: ''
          }
        }
      }
    }
  },
  data() {
    return {
      loading: false,
      list: [],
      fieldList: [],
      /** 格式化规则 */
      formatterRules: {}
    }
  },
  computed: {
    ...mapGetters(['crm']),
    canShowIndex() {
      return this.crm.business && this.crm.business.index
    }
  },
  watch: {
    show: {
      handler(val) {
        if (
          this.canShowIndex &&
          val &&
          this.data.row &&
          this.data.row.business_count > 0 &&
          this.list.length == 0
        ) {
          this.getDetail()
        }
      },
      deep: true,
      immediate: true
    }
  },
  mounted() {
    this.fieldList.push({ prop: 'name', width: '200', label: '商机名称' })
    this.fieldList.push({
      prop: 'money',
      width: '200',
      label: '商机金额'
    })
    this.fieldList.push({
      prop: 'customer_id',
      width: '200',
      label: '客户名称'
    })
    this.fieldList.push({ prop: 'type_id', width: '200', label: '商机状态组' })
    this.fieldList.push({ prop: 'status_id', width: '200', label: '状态' })

    // 为客户名称 商机状态组 状态 加入字段格式化展示规则
    function crmFieldFormatter(info) {
      return info ? info.name : ''
    }
    this.formatterRules['customer_id'] = {
      type: 'crm',
      formatter: crmFieldFormatter
    }
    function fieldFormatter(info) {
      return info || ''
    }
    this.formatterRules['type_id'] = {
      type: 'crm',
      formatter: fieldFormatter
    }
    this.formatterRules['status_id'] = {
      type: 'crm',
      formatter: fieldFormatter
    }
  },
  methods: {
    getDetail() {
      this.loading = true
      crmBusinessIndex({
        pageType: 'all',
        customer_id: this.data.row.customer_id
      })
        .then(res => {
          this.loading = false
          this.list = res.data.list
        })
        .catch(() => {
          this.loading = false
        })
    },
    /** 格式化字段 */
    fieldFormatter(row, column) {
      // 如果需要格式化
      var aRules = this.formatterRules[column.property]
      if (aRules) {
        if (aRules.type === 'crm') {
          if (column.property) {
            return aRules.formatter(row[column.property + '_info'])
          } else {
            return ''
          }
        } else {
          return aRules.formatter(row[column.property])
        }
      }
      return row[column.property]
    },
    hidenView() {
      document.querySelector('#app').click()
      this.$emit('close', this.$el, this.data)
    },
    // 当某一行被点击时会触发该事件
    handleRowClick(row, column, event) {
      this.$emit('click', row)
    },
    /** 通过回调控制style */
    cellStyle({ row, column, rowIndex, columnIndex }) {
      return { fontSize: '12px', textAlign: 'center', cursor: 'pointer' }
    },
    headerCellStyle({ row, column, rowIndex, columnIndex }) {
      return { fontSize: '12px', textAlign: 'center' }
    }
  }
}
</script>

<style lang="scss" scoped>
.container {
  position: relative;
}

.header {
  height: 40px;
  padding: 0 10px;
  flex-shrink: 0;
  .name {
    font-size: 13px;
    padding: 0 10px;
    color: #333;
  }
  .detail {
    font-size: 12px;
    padding: 0 10px;
    color: #aaaaaa;
    border-left: 1px solid #aaaaaa;
  }
  .close {
    position: absolute;
    width: 40px;
    height: 40px;
    top: 0;
    right: 10px;
    padding: 10px;
  }
}
</style>
