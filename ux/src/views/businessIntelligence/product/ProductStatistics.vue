<template>
  <div v-loading="loading"
       class="main-container">
    <filtrate-handle-view class="filtrate-bar"
                          moduleType="product"
                          @load="loading=true"
                          @change="getProductDatalist">
    </filtrate-handle-view>
    <div class="content">
      <el-table id="crm-table"
                :data="newList"
                :max-height="tableHeight"
                :span-method="objectSpanMethod"
                border
                :cell-style="cellStyle"
                @row-click="handleRowClick">
        <el-table-column v-for="(item, index) in headFieldList"
                         :key="index"
                         align="center"
                         header-align="center"
                         show-overflow-tooltip
                         :formatter="fieldFormatter"
                         :prop="item.field"
                         :label="item.name">
        </el-table-column>
      </el-table>
    </div>
    <contract-detail v-if="showContractview"
                     :id="rowID"
                     @hide-view="showContractview=false"
                     class="d-view"></contract-detail>
    <customer-detail v-if="showCustomerView"
                     :id="rowID"
                     @hide-view="showCustomerView=false"
                     class="d-view"></customer-detail>
    <product-detail v-if="showProductview"
                    :id="rowID"
                    @hide-view="showProductview=false"
                    class="d-view"></product-detail>
  </div>
</template>

<script>
import { biProductStatistics } from '@/api/businessIntelligence/bi'
import ContractDetail from '@/views/customermanagement/contract/ContractDetail'
import CustomerDetail from '@/views/customermanagement/customer/CustomerDetail'
import ProductDetail from '@/views/customermanagement/product/ProductDetail'
import base from '../mixins/base'

function fieldFormatter(info) {
  if (info) {
    return info.num ? info.num : info.realname ? info.realname : info.name
  }
  return ''
}

export default {
  /** 产品销售情况统计 */
  name: 'product-statistics',
  components: {
    ContractDetail,
    CustomerDetail,
    ProductDetail
  },
  data() {
    return {
      loading: false,
      tableHeight: document.documentElement.clientHeight - 155,

      postParams: {}, // 筛选参数

      headFieldList: [
        { field: 'category_id_info', name: '产品分类', width: '115px' },
        { field: 'product_id', name: '产品名称', width: '115px' },
        { field: 'contract_id', name: '合同编号', width: '115px' },
        { field: 'owner_user_id', name: '负责人', width: '115px' },
        { field: 'customer_id', name: '客户名称', width: '115px' },
        { field: 'sales_price', name: '销售单价', width: '115px' },
        { field: 'num', name: '数量', width: '115px' },
        { field: 'subtotal', name: '订单产品小计', width: '115px' }
      ],
      infoFieldList: [
        { field: 'name1', name: '产品分类', width: '115px' },
        { field: 'name2', name: '产品名称', width: '115px' },
        { field: 'name3', name: '合同编号', width: '115px' },
        { field: 'name4', name: '负责人', width: '115px' },
        { field: 'count', name: '客户名称', width: '115px' },
        { field: 'money', name: '销售单价', width: '115px' }
      ],
      list: [],
      //
      spanList: [],
      newList: [],
      /** 格式化规则 */
      formatterRules: {
        product_id: fieldFormatter,
        contract_id: fieldFormatter,
        owner_user_id: fieldFormatter,
        customer_id: fieldFormatter
      },
      /** 控制详情展示 */
      showContractview: false,
      showCustomerView: false,
      showProductview: false,
      rowID: ''
    }
  },
  mixins: [base],
  computed: {},
  mounted() {
    var self = this
    /** 控制table的高度 */
    window.onresize = function() {
      var offsetHei = document.documentElement.clientHeight
      self.tableHeight = offsetHei - 155
    }
  },
  methods: {
    objectSpanMethod({ row, column, rowIndex, columnIndex }) {
      var item = this.spanList[rowIndex]
      if (columnIndex == 0) {
        if (item.rowspan == 0) {
          return {
            rowspan: 0,
            colspan: 0
          }
        } else {
          return {
            rowspan: item.rowspan,
            colspan: 1
          }
        }
      } else if (columnIndex == 1) {
        if (item.product_rowspan == 0) {
          return {
            rowspan: 0,
            colspan: 0
          }
        } else {
          return {
            rowspan: item.product_rowspan,
            colspan: 1
          }
        }
      }
    },
    /** 格式化字段 */
    fieldFormatter(row, column) {
      // 如果需要格式化
      var aRules = this.formatterRules[column.property]
      if (aRules) {
        if (column.property) {
          return aRules(row[column.property + '_info'])
        } else {
          return ''
        }
      }
      return row[column.property]
    },
    /** 列表操作 */
    // 当某一行被点击时会触发该事件
    handleRowClick(row, column, event) {
      if (column.property === 'customer_id') {
        if (this.showProductview) {
          this.showProductview = false
        }
        if (this.showContractview) {
          this.showContractview = false
        }
        this.rowID = row.customer_id
        this.showCustomerView = true
      } else if (column.property === 'product_id') {
        if (this.showCustomerView) {
          this.showCustomerView = false
        }
        if (this.showContractview) {
          this.showContractview = false
        }
        this.rowID = row.product_id
        this.showProductview = true
      } else if (column.property === 'contract_id') {
        if (this.showProductview) {
          this.showProductview = false
        }
        if (this.showCustomerView) {
          this.showCustomerView = false
        }
        this.rowID = row.contract_id
        this.showContractview = true
      }
    },
    cellStyle({ row, column, rowIndex, columnIndex }) {
      var item = this.spanList[rowIndex]
      if (item.isSum == true) {
        return { backgroundColor: '#FFF9F2' }
      } else if (item.isAllSum == true) {
        return { backgroundColor: '#FFF3E8' }
      } else if (columnIndex === 1 || columnIndex === 2 || columnIndex === 4) {
        return { color: '#3E84E9', cursor: 'pointer' }
      }
    },
    /** 获取部门业绩完成信息 */
    getProductDatalist(params) {
      this.loading = true
      biProductStatistics(params)
        .then(res => {
          this.list = res.data
          this.handleShowInfo()
          this.loading = false
        })
        .catch(() => {
          this.loading = false
        })
    },
    /** 处理展示数据 */
    handleShowInfo() {
      // 记录分类合并
      // 产品合并
      // 小产品
      /**
       * rowspan 数量
       *
       */

      var newList = []
      var spanList = []
      var seriesIndex = 0 // 操控span中元素
      var productIndex = 0

      var subCount = 0 // 产品
      var subMoney = 0
      var allCount = 0 // 系列
      var allMoney = 0

      var count
      for (let index = 0; index < this.list.length; index++) {
        const element = this.list[index]

        if (spanList.length == 0) {
          seriesIndex = 0 //一个新系列的开始
          productIndex = 0 //一个新产品的开始
          subCount = parseFloat(element.num) // 产品
          subMoney = parseFloat(element.subtotal)
          allCount = parseFloat(element.num) // 系列
          allMoney = parseFloat(element.subtotal)

          spanList.push({ rowspan: 1, product_rowspan: 1 })
          newList.push(element) // 真实数据
        } else if (element.category_id != this.list[index - 1].category_id) {
          // 系列改变时候的逻辑
          /** 上一个最后产品的处理 */
          var preItem = spanList[seriesIndex]
          preItem.rowspan += 1
          newList.push({ num: subCount, subtotal: subMoney }) // 产品小计数据
          spanList.push({ rowspan: 0, product_rowspan: 1, isSum: true }) // 产品小计style

          newList.push({ num: allCount, subtotal: allMoney }) // 系列小计数据
          spanList.push({ rowspan: 1, product_rowspan: 1, isAllSum: true }) // 系列小计style

          /*** 新系列开始 */
          spanList.push({ rowspan: 1, product_rowspan: 1 }) // 新系列 新产品的 展示数据开始 style
          subCount = parseFloat(element.num) // 新产品的值 所以取消了重置为0
          subMoney = parseFloat(element.subtotal)
          allCount = parseFloat(element.num) // 系列
          allMoney = parseFloat(element.subtotal)
          newList.push(element) // 真实数据
          seriesIndex = spanList.length - 1 //一个新系列的开始
          productIndex = spanList.length - 1 //一个新产品的开始
        } else {
          var preItem = spanList[seriesIndex]
          preItem.rowspan += 1
          /*** 相同产品 */
          if (element.product_id == this.list[index - 1].product_id) {
            var preProItem = spanList[productIndex]
            preProItem.product_rowspan += 1
            spanList.push({ rowspan: 0, product_rowspan: 0 }) // 产品 非第一条数据的style
            subCount += parseFloat(element.num) // 产品
            subMoney += parseFloat(element.subtotal)
            allCount += parseFloat(element.num) // 系列
            allMoney += parseFloat(element.subtotal)
            newList.push(element) // 真实数据
          } else {
            /*** 不相同产品 */
            // 需要添加一个小计
            preItem.rowspan += 1

            newList.push({ num: subCount, subtotal: subMoney }) // 产品小计数据
            spanList.push({ rowspan: 0, product_rowspan: 1, isSum: true }) // 产品小计Style

            spanList.push({ rowspan: 0, product_rowspan: 1 }) // 新产品 第一条数据style
            productIndex = spanList.length - 1 //一个新产品的开始=
            subCount = element.num
            subMoney = parseFloat(element.subtotal) //开始了一个新的产品  所以没有 清空数据
            allCount += parseFloat(element.num) // 系列 继续 叠加
            allMoney += parseFloat(element.subtotal)
            newList.push(element) // 真实数据
          }
        }

        if (this.list.length - 1 == index) {
          // 最后一个产品的处理
          var preItem = spanList[seriesIndex]
          preItem.rowspan += 1
          newList.push({ num: subCount, subtotal: subMoney }) // 产品小计数据
          subCount = 0
          subMoney = 0 // 完成一个产品统计 清空数据
          spanList.push({ rowspan: 0, product_rowspan: 1, isSum: true }) // 产品小计style

          newList.push({ num: allCount, subtotal: allMoney }) // 系列小计数据
          allCount = 0
          allMoney = 0 // 完成一个系列统计 清空数据
          spanList.push({ rowspan: 1, product_rowspan: 1, isAllSum: true }) // 系列小计style
        }
      }

      this.spanList = spanList
      this.newList = newList
    }
  }
}
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import '../styles/detail.scss';
@import '@/views/customermanagement/styles/detailview.scss';

.content {
  overflow: hidden;
}

.table-header {
  background-color: #f2f2f2;
  .header-item {
    text-align: center;
    height: 40px;
    line-height: 40px;
    border-left: 1px solid #e6e6e6;
  }
  .header-item:first-child {
    border-left: none;
  }
}
/** cell 信息 */
.table-cell {
  div {
    text-align: center;
  }
}
.series-info {
  .series-name {
    width: 115px;
    height: 100%;
  }
  .series-body {
    border-left: 1px solid #e6e6e6;
    flex: 1;
  }
}
.product-info {
  .product-name {
    width: 115px;
  }
  .product-body {
    border-left: 1px solid #e6e6e6;
    flex: 1;
  }
}

.money-cells {
  .money-cell {
    border-left: 1px solid #e6e6e6;
    width: 115px;
    height: 40px;
    line-height: 40px;
  }
  .money-cell:first-child {
    border-left: none;
  }
}
</style>
