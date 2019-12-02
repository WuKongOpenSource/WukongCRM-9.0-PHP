<template>
  <div>
    <div class="handel-header">
      <el-popover
        v-model="showPopover"
        placement="bottom"
        width="700"
        style="padding: 0 !important;"
        trigger="click">
        <crm-relative
          v-if="showSelectView"
          ref="crmrelative"
          :radio="false"
          :selected-data="selectedData"
          crm-type="product"
          @close="showPopover=false"
          @changeCheckout="selectInfos"/>
        <el-button
          slot="reference"
          type="primary"
          @click="showSelectView=true">添加产品</el-button>
      </el-popover>
    </div>
    <el-table
      :data="productList"
      style="width: 620px;">
      <el-table-column
        prop="name"
        label="产品名称"/>
      <el-table-column
        prop="category_id_info"
        label="产品类别"/>
      <el-table-column
        prop="unit"
        label="单位"/>
      <el-table-column
        prop="price"
        label="标准价格"/>
      <el-table-column label="售价">
        <template slot-scope="scope">
          <el-input
            v-model="scope.row.sales_price"
            placeholder="请输入"
            @input="salesPriceChange(scope)"
            @blur="scope.row.sales_price || (scope.row.sales_price = 0)"/>
        </template>
      </el-table-column>
      <el-table-column label="数量">
        <template slot-scope="scope">
          <el-input
            v-model="scope.row.num"
            placeholder="请输入"
            @input="numChange(scope)"
            @blur="scope.row.num || (scope.row.num = 0)"/>
        </template>
      </el-table-column>
      <el-table-column label="折扣（%）">
        <template slot-scope="scope">
          <el-input
            v-model="scope.row.discount"
            placeholder="请输入"
            @input="discountChange(scope)"
            @blur="scope.row.discount || (scope.row.discount = 0)"/>
        </template>
      </el-table-column>
      <el-table-column
        prop="subtotal"
        label="合计"/>
      <el-table-column label="操作">
        <template slot-scope="scope">
          <el-button @click="removeItem(scope.$index)">删除</el-button>
        </template>
      </el-table-column>
    </el-table>
    <flexbox class="handle-footer">
      <div class="discount-title">整单折扣（%）：</div>
      <el-input
        v-model="discount_rate"
        style="width: 80px"
        placeholder="请输入"
        @blur="discount_rate || (discount_rate = 0)"
        @input="rateChange"/>
      <div class="total-info">已选中产品：
        <span class="info-yellow">{{ productList.length }}</span>&nbsp;种&nbsp;&nbsp;总金额：
        <el-input
          v-model="total_price"
          style="width: 80px"
          placeholder="请输入"
          @input="totalPriceChange"
          @blur="total_price || (total_price = 0)"/>&nbsp;元
      </div>
    </flexbox>
  </div>
</template>
<script type="text/javascript">
import objMixin from './objMixin'
import CrmRelative from '@/components/CreateCom/CrmRelative'

export default {
  name: 'XhProduct', // 关联产品
  components: {
    CrmRelative
  },
  mixins: [objMixin],
  props: {},
  data() {
    return {
      showPopover: false, // 展示产品框
      showSelectView: false, // 内容
      productList: [],
      total_price: 0,
      discount_rate: 0,
      selectedData: { product: [] }
    }
  },
  computed: {},
  watch: {
    dataValue: function(value) {
      this.refreshProductList()
    },
    productList() {
      this.selectedData = { product: this.productList || [] }
    }
  },
  mounted() {
    this.refreshProductList()
  },
  methods: {
    /**
     * 刷新数据
     */
    refreshProductList() {
      this.productList = this.dataValue.product
      this.total_price = this.dataValue.total_price
      this.discount_rate = this.dataValue.discount_rate
    },
    /** 选中 */
    selectInfos(data) {
      const self = this
      data.data.forEach(function(element) {
        const obj = self.productList.find(item => {
          return item.product_id == element.product_id
        })
        if (!obj) {
          self.productList.push(self.getShowItem(element))
        }
      })
    },
    getShowItem(data) {
      const item = {}
      item.name = data.name
      item.category_id_info = data.category_id_info
      item.unit = data.unit
      item.price = data.price
      item.sales_price = data.price
      item.num = 0
      item.discount = 0
      item.subtotal = 0
      item.product_id = data.product_id
      return item
    },
    // 单价
    salesPriceChange(data) {
      this.verifyNumberValue(data, 'sales_price')
      const item = data.row

      let discount =
        ((item.price - (item.sales_price || 0)) / item.price) * 100.0
      discount = discount.toFixed(2)
      if (item.discount !== discount) {
        item.discount = discount
      }
      this.calculateSubTotal(item)
      this.calculateToal()
    },
    // 数量
    numChange(data) {
      this.verifyNumberValue(data, 'num')
      const item = data.row
      this.calculateSubTotal(item)
      this.calculateToal()
    },
    // 折扣
    discountChange(data) {
      this.verifyNumberValue(data, 'discount')
      const item = data.row
      let sales_price =
        (item.price * (100.0 - parseFloat(item.discount || 0))) / 100.0
      sales_price = sales_price.toFixed(2)
      if (item.sales_price !== sales_price) {
        item.sales_price = sales_price
      }
      this.calculateSubTotal(item)
      this.calculateToal()
    },
    // 计算单价
    calculateSubTotal(item) {
      item.subtotal = (item.sales_price * parseFloat(item.num || 0)).toFixed(2)
    },
    // 计算总价
    calculateToal() {
      let totalPrice = this.getProductTotal()
      totalPrice =
        (totalPrice * (100.0 - parseFloat(this.discount_rate || 0))) / 100.0
      this.total_price = totalPrice.toFixed(2)
      this.updateValue() // 传递数据给父组件
    },
    /**
     * 获取产品总价(未折扣)
     */
    getProductTotal() {
      let totalPrice = 0.0
      for (let i = 0; i < this.productList.length; i++) {
        const item = this.productList[i]
        totalPrice += parseFloat(item.subtotal)
      }
      return totalPrice
    },
    // 总折扣
    rateChange() {
      if (/^\d+\.?\d{0,2}$/.test(this.discount_rate)) {
        this.discount_rate = this.discount_rate
      } else {
        this.discount_rate = this.discount_rate.substring(
          0,
          this.discount_rate.length - 1
        )
      }
      this.calculateToal()
    },
    /**
     * 总价更改 折扣更改
     */
    totalPriceChange() {
      if (/^\d+\.?\d{0,2}$/.test(this.total_price)) {
        this.total_price = this.total_price
      } else {
        this.total_price = this.total_price.substring(
          0,
          this.total_price.length - 1
        )
      }
      const totalPrice = this.getProductTotal()
      this.discount_rate = (
        100.0 -
        (parseFloat(this.total_price) / totalPrice) * 100
      ).toFixed(2)
      this.updateValue()
    },
    // 删除操作
    removeItem(index) {
      this.productList.splice(index, 1)
      this.calculateToal()
    },
    updateValue() {
      this.valueChange({
        product: this.productList,
        total_price: this.total_price,
        discount_rate: this.discount_rate
      })
    },
    /**
     * 验证数据数值是否符合
     */
    verifyNumberValue(data, field) {
      if (/^\d+\.?\d{0,2}$/.test(data.row[field])) {
        data.row[field] = data.row[field]
      } else {
        data.row[field] = data.row[field].substring(
          0,
          data.row[field].length - 1
        )
      }
    }
  }
}
</script>
<style lang="scss" scoped>
.handel-header {
  button {
    float: right;
    margin-bottom: 10px;
  }
}

.el-table /deep/ thead th {
  font-size: 12px;
}

.el-table /deep/ tbody tr td {
  font-size: 12px;
}

.handle-footer {
  position: relative;
  font-size: 12px;
  padding: 5px;
  .discount-title {
    color: #666;
  }
  .total-info {
    position: absolute;
    right: 20px;
    top: 5px;
    .info-yellow {
      color: #fd715a;
    }
  }
}
</style>
