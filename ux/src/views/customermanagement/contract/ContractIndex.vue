<template>
  <div>
    <c-r-m-list-head title="合同管理"
                     placeholder="请输入合同名称"
                     @on-handle="listHeadHandle"
                     @on-search="crmSearch"
                     main-title="新建合同"
                     :crm-type="crmType">
    </c-r-m-list-head>
    <div v-empty="!crm.contract.index"
         xs-empty-icon="nopermission"
         xs-empty-text="暂无权限"
         class="crm-container">
      <c-r-m-table-head ref="crmTableHead"
                        :crm-type="crmType"
                        @filter="handleFilter"
                        @handle="handleHandle"
                        @scene="handleScene"></c-r-m-table-head>
      <el-table class="n-table--border"
                id="crm-table"
                v-loading="loading"
                :data="list"
                :height="tableHeight"
                stripe
                border
                highlight-current-row
                style="width: 100%"
                :cell-style="cellStyle"
                @row-click="handleRowClick"
                @header-dragend="handleHeaderDragend"
                @selection-change="handleSelectionChange">
        <el-table-column show-overflow-tooltip
                         type="selection"
                         align="center"
                         width="55">
        </el-table-column>
        <el-table-column v-for="(item, index) in fieldList"
                         :key="index"
                         show-overflow-tooltip
                         :fixed="index==0"
                         :prop="item.prop"
                         :label="item.label"
                         :width="item.width"
                         :formatter="fieldFormatter">
          <template slot="header"
                    slot-scope="scope">
            <div class="table-head-name">{{scope.column.label}}</div>
          </template>
        </el-table-column>
        <el-table-column show-overflow-tooltip
                         prop="check_status_info"
                         label="状态"
                         :resizable="false"
                         width="100"
                         align="center"
                         fixed="right">
          <template slot="header"
                    slot-scope="scope">
            <div class="table-head-name">{{scope.column.label}}</div>
          </template>
          <template slot-scope="scope">
            <div class="status_button"
                 :style="getStatusStyle(scope.row.check_status)">
              {{scope.row.check_status_info}}
            </div>
          </template>
        </el-table-column>
        <el-table-column>
        </el-table-column>
        <el-table-column fixed="right"
                         width="36">
          <template slot="header"
                    slot-scope="slot">
            <img src="@/assets/img/t_set.png"
                 @click="handleTableSet"
                 class="table-set" />
          </template>
        </el-table-column>
      </el-table>
      <div class="p-contianer">
        <el-pagination class="p-bar"
                       @size-change="handleSizeChange"
                       @current-change="handleCurrentChange"
                       :current-page="currentPage"
                       :page-sizes="pageSizes"
                       :page-size.sync="pageSize"
                       layout="slot, total, sizes, prev, pager, next, jumper"
                       :total="total">
          <span class="money-bar">合同总金额：{{moneyPageData.sumMoney}} / 已回款金额：{{moneyPageData.unReceivablesMoney}}</span>
        </el-pagination>
      </div>
    </div>
    <!-- 相关详情页面 -->
    <c-r-m-all-detail :visible.sync="showDview"
                      :crmType="rowType"
                      :id="rowID"
                      class="d-view">
    </c-r-m-all-detail>
    <fields-set :crmType="crmType"
                @set-success="setSave"
                :dialogVisible.sync="showFieldSet"></fields-set>
  </div>
</template>

<script>
import CRMAllDetail from '@/views/customermanagement/components/CRMAllDetail'
import table from '../mixins/table'

export default {
  /** 客户管理 的 合同列表 */
  name: 'contractIndex',
  components: {
    CRMAllDetail
  },
  mixins: [table],
  data() {
    return {
      crmType: 'contract',
      moneyData: null //合同列表金额
    }
  },
  computed: {
    moneyPageData() {
      // 未勾选展示合同总金额信息
      if (this.selectionList.length == 0 && this.moneyData) {
        return this.moneyData
      } else {
        let sumMoney = 0.0
        let unReceivablesMoney = 0.0
        for (let index = 0; index < this.selectionList.length; index++) {
          const element = this.selectionList[index]
          // 2 审核通过的合同
          if (element.check_status == 2) {
            sumMoney += parseFloat(element.money)
            unReceivablesMoney += parseFloat(element.unMoney)
          }
        }
        return {
          sumMoney: sumMoney.toFixed(2),
          unReceivablesMoney: unReceivablesMoney.toFixed(2)
        }
      }
    }
  },
  mounted() {},
  methods: {
    /** 通过回调控制style */
    cellStyle({ row, column, rowIndex, columnIndex }) {
      if (
        column.property === 'num' ||
        column.property === 'customer_id' ||
        column.property === 'business_id' ||
        column.property === 'contacts_id'
      ) {
        return { color: '#3E84E9', cursor: 'pointer' }
      } else {
        return ''
      }
    }
  }
}
</script>

<style lang="scss" scoped>
@import '../styles/table.scss';
.money-bar {
  color: #99a9bf;
  line-height: 44px !important;
  position: absolute;
  left: 20px;
  top: 0;
}
</style>
