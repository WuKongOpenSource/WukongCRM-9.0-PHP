<template>
  <el-select style="width: 100%;"
             v-model="dataValue"
             :disabled="disabled"
             @change="valueChange"
             placeholder="请选择">
    <el-option v-for="(item, index) in option"
               :key="index"
               :label="item.num"
               :value="item.plan_id">
    </el-option>
  </el-select>
</template>
<script type="text/javascript">
import stringMixin from './stringMixin'
import { crmReceivablesPlanIndex } from '@/api/customermanagement/money'

export default {
  name: 'xh-receivables-plan', // 回款 下的 回款计划
  components: {},
  mixins: [stringMixin],
  watch: {
    relation: function(val) {
      if (val.form_type) {
        this.getPlanList()
      } else {
        this.option = []
      }
    }
  },
  computed: {},
  data() {
    return {
      option: []
    }
  },
  props: {
    relation: {
      // 相关ID
      type: Object,
      default: () => {
        return {}
      }
    }
  },
  mounted() {
    if (this.relation.form_type) {
      this.getPlanList()
    }
  },
  methods: {
    getPlanList() {
      this.loading = true
      crmReceivablesPlanIndex({
        contract_id: this.relation.contract_id,
        pageType: 'all'
      })
        .then(res => {
          this.loading = false
          this.option = res.data.list
        })
        .catch(() => {
          this.loading = false
        })
    }
  }
}
</script>
<style lang="scss" scoped>
</style>
