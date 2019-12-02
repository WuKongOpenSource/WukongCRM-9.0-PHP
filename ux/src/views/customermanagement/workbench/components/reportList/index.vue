<template>
  <transition name="opacity-fade">
    <div
      :style="{'padding': padding+' 0', 'z-index': zIndex }"
      class="container">
      <div class="content">
        <div
          class="header"
          @click="showDview = false">
          <span class="title">{{ title }}</span>
          <el-input
            v-if="placeholder"
            :placeholder="placeholder"
            v-model="inputContent"
            class="search-input"
            @keyup.enter.native="searchInput">
            <el-button
              slot="append"
              icon="el-icon-search"
              @click.native="searchInput"/>
          </el-input>
          <img
            class="close"
            src="@/assets/img/task_close.png"
            @click="hideView" >
        </div>
        <div class="list-body">
          <el-table
            v-loading="loading"
            id="crm-table"
            :data="list"
            :height="tableHeight"
            :cell-style="cellStyle"
            stripe
            border
            highlight-current-row
            style="width: 100%"
            @row-click="handleRowClick"
            @sort-change="sortChange">
            <el-table-column
              v-for="(item, index) in showFieldList"
              :key="index"
              :sortable="sortable"
              :fixed="index==0"
              :prop="item.prop"
              :label="item.label"
              :width="item.width"
              :formatter="fieldFormatter"
              show-overflow-tooltip>
              <template
                slot="header"
                slot-scope="scope">
                <div class="table-head-name">{{ scope.column.label }}</div>
              </template>
            </el-table-column>
            <el-table-column
              v-if="showPoolDayField"
              :resizable="false"
              prop="pool_day"
              show-overflow-tooltip
              label="距进入公海天数"
              width="120">
              <template slot-scope="scope">
                <div v-if="scope.row.is_pool == 1">--</div>
                <div v-else-if="scope.row.pool_day != -1">{{ scope.row.pool_day }}</div>
                <i
                  v-else
                  class="wukong wukong-lock customer-lock"/>
              </template>
            </el-table-column>
            <el-table-column
              v-if="showExamineStatus"
              :resizable="false"
              show-overflow-tooltip
              prop="check_status_info"
              label="状态"
              width="100"
              align="center"
              fixed="right">
              <template
                slot="header"
                slot-scope="scope">
                <div class="table-head-name">{{ scope.column.label }}</div>
              </template>
              <template slot-scope="scope">
                <div
                  :style="getStatusStyle(scope.row.check_status)"
                  class="status_button">
                  {{ scope.row.check_status_info }}
                </div>
              </template>
            </el-table-column>
            <el-table-column v-if="showFillColumn"/>
          </el-table>
          <div v-if="showPagination" class="p-contianer">
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
      <!-- 相关详情页面 -->
      <c-r-m-all-detail
        :visible.sync="showDview"
        :crm-type="rowType"
        :id="rowID"
        class="d-view"
        @handle="handleHandle"/>

      <record-list
        v-if="recordShow"
        :crm-type="rowType"
        :params="recordParams"
        @handle="getList"
        @hide="recordShow = false"/>
    </div>
  </transition>
</template>
<script type="text/javascript">
import { filedGetField } from '@/api/customermanagement/common'
import { getMaxIndex } from '@/utils/index'
import { mapGetters } from 'vuex'
import Lockr from 'lockr'
import { getDateFromTimestamp } from '@/utils'
import moment from 'moment'
import CRMAllDetail from '@/views/customermanagement/components/CRMAllDetail'
import RecordList from './components/recordList'

export default {
  name: 'ReportList', // 简报列表
  components: {
    CRMAllDetail,
    RecordList
  },

  props: {
    /** 展示内容的上下padding */
    padding: {
      type: String,
      default: '0'
    },
    title: String,
    placeholder: {
      type: String,
      default: '请输入搜索内容'
    },
    crmType: String,
    fieldList: Array,
    request: {
      type: Function,
      required: true
    },
    params: Object
  },
  data() {
    return {
      zIndex: getMaxIndex(),
      inputContent: '',

      loading: false, // 加载动画
      tableHeight: document.documentElement.clientHeight - 115, // 表的高度
      list: [],
      showFieldList: [],
      sortData: {}, // 字段排序
      currentPage: 1,
      pageSize: Lockr.get('crmPageSizes') || 15,
      pageSizes: [15, 30, 60, 100],
      total: 0,

      /** 控制详情展示 */
      rowID: '', // 行信息
      rowType: '', // 详情类型
      showDview: false,
      /** 格式化规则 */
      formatterRules: {},

      recordParams: {},
      recordShow: false
    }
  },
  computed: {
    ...mapGetters(['crm', 'CRMConfig']),
    showPoolDayField() {
      if (this.crmType == 'customer' && this.CRMConfig.config == 1) {
        return true
      }
      return false
    },
    showExamineStatus() {
      if (this.crmType == 'contract' && this.crmType == 'receivables') {
        return true
      }
      return false
    },
    sortable() {
      if (this.fieldList && this.fieldList.length) {
        return false
      }
      return 'custom'
    },
    showFillColumn() {
      if (this.fieldList && this.fieldList.length) {
        return false
      }
      return true
    },
    showPagination() {
      if (this.fieldList && this.fieldList.length) {
        return false
      }
      return true
    }
  },
  mounted() {
    document.body.appendChild(this.$el)

    window.onresize = () => {
      self.tableHeight = document.documentElement.clientHeight - 115
    }
    if (this.fieldList) {
      this.showFieldList = this.fieldList
      this.getList()
    } else {
      this.getFieldList()
    }
  },

  destroyed() {
    // remove DOM node after destroy
    if (this.$el && this.$el.parentNode) {
      this.$el.parentNode.removeChild(this.$el)
    }
  },
  methods: {
    /**
     * 搜索
     */
    searchInput() {
      this.currentPage = 1
      this.getList()
    },

    /**
     * 获取列表数据
     */
    getList() {
      this.loading = true
      var params = {
        page: this.currentPage,
        limit: this.pageSize,
        search: this.inputContent,
        types: 'crm_' + this.crmType
      }

      if (this.sortData.order) {
        params.order_field = this.sortData.prop
        params.order_type = this.sortData.order == 'ascending' ? 'asc' : 'desc'
      }

      this.request({ ...params, ...this.params })
        .then(res => {
          if (res.data.list) {
            this.list = res.data.list
            this.total = res.data.dataCount
          } else {
            this.list = res.data
          }
          this.loading = false
        })
        .catch(() => {
          this.loading = false
        })
    },

    /**
     * 获取表头字段
     */
    getFieldList() {
      if (this.showFieldList.length == 0) {
        this.loading = true
        const crmType =
          this.crmType == 'business_status' ? 'business' : this.crmType
        var params = {
          types: 'crm_' + crmType,
          module: 'crm',
          action: 'index'
        }
        params.controller = crmType

        filedGetField(params)
          .then(res => {
            for (let index = 0; index < res.data.length; index++) {
              const element = res.data[index]
              /** 获取需要格式化的字段 和格式化的规则 */
              if (element.form_type === 'date') {
                this.formatterRules[element.field] = {
                  formatter: (time) => {
                    if (time == '0000-00-00') {
                      time = ''
                    }
                    return time
                  }
                }
              } else if (element.form_type === 'datetime') {
                this.formatterRules[element.field] = {
                  formatter: (time) => {
                    if (time == 0 || !time) {
                      return ''
                    }
                    return moment(getDateFromTimestamp(time)).format(
                      'YYYY-MM-DD HH:mm:ss'
                    )
                  }
                }
              } else if (
                element.field === 'create_user_id' ||
                element.field === 'owner_user_id'
              ) {
                this.formatterRules[element.field] = {
                  type: 'crm',
                  formatter: (info) => {
                    return info ? info.realname : ''
                  }
                }
              } else if (element.form_type === 'user') {
                this.formatterRules[element.field] = {
                  type: 'crm',
                  formatter: (info) => {
                    if (info) {
                      var content = ''
                      for (let index = 0; index < info.length; index++) {
                        const element = info[index]
                        content =
                        content +
                        element.realname +
                        (index === info.length - 1 ? '' : ',')
                      }
                      return content
                    }
                    return ''
                  }
                }
              } else if (element.form_type === 'structure') {
                this.formatterRules[element.field] = {
                  type: 'crm',
                  formatter: (info) => {
                    if (info) {
                      var content = ''
                      for (let index = 0; index < info.length; index++) {
                        const element = info[index]
                        content =
                        content +
                        element.name +
                        (index === info.length - 1 ? '' : ',')
                      }
                      return content
                    }
                    return ''
                  }
                }
                /** 联系人 客户 商机 合同*/
              } else if (
                element.field === 'contacts_id' ||
                element.field === 'customer_id' ||
                element.field === 'business_id' ||
                element.field === 'contract_id'
              ) {
                this.formatterRules[element.field] = {
                  type: 'crm',
                  formatter: (info) => {
                    return info ? info.name : ''
                  }
                }
              } else if (
                element.field === 'status_id' ||
                element.field === 'type_id' ||
                element.field === 'category_id'
              ) {
                this.formatterRules[element.field] = {
                  type: 'crm',
                  formatter: (info) => {
                    return info || ''
                  }
                }
              }

              var width = 0
              if (!element.width) {
                if (element.name && element.name.length <= 6) {
                  width = element.name.length * 15 + 45
                } else {
                  width = 140
                }
              } else {
                width = element.width
              }

              this.showFieldList.push({
                prop: element.field,
                label: element.name,
                width: width
              })
            }

            // 获取好字段开始请求数据
            this.getList()
          })
          .catch(() => {
            this.loading = false
          })
      } else {
        // 获取好字段开始请求数据
        this.getList()
      }
    },

    /**
     * 格式化字段
     */
    fieldFormatter(row, column) {
      // 如果需要格式化
      var aRules = this.formatterRules[column.property]
      if (aRules) {
        if (aRules.type === 'crm') {
          if (column.property) {
            return aRules.formatter(row[column.property + '_info']) || '--'
          } else {
            return ''
          }
        } else {
          return aRules.formatter(row[column.property]) || '--'
        }
      } else if (this.fieldList && this.fieldList.length) {
        if (column.property == 'types') {
          return {
            crm_leads: '线索',
            crm_customer: '客户',
            crm_contacts: '联系人',
            crm_business: '商机',
            crm_contract: '合同'
          }[row[column.property]]
        }
      }
      return row[column.property] || '--'
    },

    /**
     * 字段排序
     */
    sortChange(column, prop, order) {
      this.sortData = column
      this.getList()
    },

    /**
     * 更改每页展示数量
     */
    handleSizeChange(val) {
      Lockr.set('crmPageSizes', val)
      this.pageSize = val
      this.getList()
    },

    /**
     * 更改当前页数
     */
    handleCurrentChange(val) {
      this.currentPage = val
      this.getList()
    },

    /**
     * 0待审核、1审核中、2审核通过、3审核未通过
     */
    getStatusStyle(status) {
      if (status == 0) {
        return {
          'border-color': '#E6A23C',
          'background-color': '#FDF6EC',
          color: '#E6A23C'
        }
      } else if (status == 1) {
        return {
          'border-color': '#409EFF',
          'background-color': '#ECF5FF',
          color: '#409EFF'
        }
      } else if (status == 2) {
        return {
          'border-color': '#67C23A',
          'background-color': '#F0F9EB',
          color: '#67C23A'
        }
      } else if (status == 3) {
        return {
          'border-color': '#F56C6B',
          'background-color': '#FEF0F0',
          color: '#F56C6B'
        }
      } else if (status == 4) {
        return {
          'background-color': '#FFFFFF'
        }
      }
    },

    /**
     * 查看详情
     */
    handleRowClick(row, column, event) {
      if (this.crmType === 'leads') {
        if (column.property === 'name') {
          this.rowID = row.leads_id
          this.showDview = true
        } else {
          this.showDview = false
        }
      } else if (this.crmType === 'customer') {
        if (column.property === 'name') {
          this.rowID = row.customer_id
          this.rowType = 'customer'
          this.showDview = true
        } else {
          this.showDview = false
        }
      } else if (this.crmType === 'contacts') {
        if (column.property === 'customer_id') {
          this.rowID = row.customer_id_info.customer_id
          this.rowType = 'customer'
          this.showDview = true
        } else if (column.property === 'name') {
          this.rowID = row.contacts_id
          this.rowType = 'contacts'
          this.showDview = true
        } else {
          this.showDview = false
        }
      } else if (
        this.crmType === 'business' ||
        this.crmType === 'business_status'
      ) {
        if (column.property === 'customer_id') {
          this.rowID = row.customer_id_info.customer_id
          this.rowType = 'customer'
          this.showDview = true
        } else if (column.property === 'name') {
          this.rowID = row.business_id
          this.rowType = 'business'
          this.showDview = true
        } else {
          this.showDview = false
        }
      } else if (this.crmType === 'contract') {
        if (column.property === 'customer_id') {
          this.rowID = row.customer_id_info.customer_id
          this.rowType = 'customer'
          this.showDview = true
        } else if (column.property === 'business_id') {
          this.rowID = row.business_id_info.business_id
          this.rowType = 'business'
          this.showDview = true
        } else if (column.property === 'contacts_id') {
          this.rowID = row.contacts_id_info.contacts_id
          this.rowType = 'contacts'
          this.showDview = true
        } else if (column.property === 'num') {
          this.rowID = row.contract_id
          this.rowType = 'contract'
          this.showDview = true
        } else {
          this.showDview = false
        }
      } else if (this.crmType === 'product') {
        if (column.property === 'name') {
          this.rowID = row.product_id
          this.showDview = true
        } else {
          this.showDview = false
        }
      } else if (this.crmType === 'receivables') {
        if (column.property === 'customer_id') {
          this.rowID = row.customer_id_info.customer_id
          this.rowType = 'customer'
          this.showDview = true
        } else if (column.property === 'contract_id') {
          this.rowID = row.contract_id
          this.rowType = 'contract'
          this.showDview = true
        } else if (column.property === 'number') {
          this.rowID = row.receivables_id
          this.rowType = 'receivables'
          this.showDview = true
        } else {
          this.showDview = false
        }
      } else if (this.crmType === 'record') {
        if (column.property === 'dataCount' && row.dataCount) {
          this.rowType = row.types.replace('crm_', '')
          this.recordParams = row
          this.recordShow = true
        } else {
          this.recordShow = false
        }
      }
    },

    /**
     * 详情操作
     */
    handleHandle(data) {
      if (
        data.type === 'alloc' ||
        data.type === 'get' ||
        data.type === 'transfer' ||
        data.type === 'transform' ||
        data.type === 'delete' ||
        data.type === 'put_seas'
      ) {
        this.showDview = false
      }

      if (data.type !== 'edit') {
        this.getList()
      }
    },

    /**
     * 通过回调控制style
     */
    cellStyle({ row, column, rowIndex, columnIndex }) {
      if (
        (column.property === 'name' ||
          column.property === 'num' ||
          column.property === 'number' ||
          column.property === 'customer_id' ||
          column.property === 'business_id' ||
          column.property === 'contacts_id' ||
          column.property === 'dataCount') &&
        row[column.property]
      ) {
        return { color: '#3E84E9', cursor: 'pointer' }
      } else {
        return ''
      }
    },

    /**
     * 隐藏视图
     */
    hideView() {
      this.$emit('hide')
    }
  }
}
</script>
<style lang="scss" scoped>
.opacity-fade-enter-active,
.opacity-fade-leave-active {
  transition: all 0.2s;
}
.opacity-fade-enter,
.opacity-fade-leave-to {
  opacity: 0;
}
/** 容器布局 */
.container {
  position: fixed;
  left: 0;
  top: 0;
  bottom: 0;
  right: 0;
  background-color: white;
}

.content {
  position: relative;
  height: 100%;

  .header {
    position: relative;
    .title {
      padding: 0 20px;
      font-size: 18px;
      line-height: 60px;
    }

    .search-input {
      width: 300px;
      margin: -18px 0 0 -150px;
      position: absolute;
      left: 50%;
      top: 50%;
    }

    .close {
      display: block;
      float: right;
      width: 40px;
      height: 40px;
      margin-left: 20px;
      margin-top: 10px;
      margin-right: 10px;
      padding: 10px;
      cursor: pointer;
    }
  }
}

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

.customer-lock {
  color: #f15e64;
}

.status_button {
  padding: 1px 6px;
  border: 1px solid #e6e6e6;
  border-radius: 4px;
  display: inline-block;
  font-size: 12px;
  margin: 0 auto;
}

.table-head-name {
  font-size: 14px;
  color: #475059;
  line-height: 23px;
  padding: 0;
}

.el-table /deep/ thead th {
  background-color: #fafdff;
  font-weight: 400;
  border-color: #e6e6e6;
  color: #475059;
}

.el-table /deep/ thead .cell {
  height: 23px;
}

.el-table /deep/ .sort-caret.ascending {
  top: -6px;
}
.el-table /deep/ .caret-wrapper {
  height: 24px;
}
</style>
