<template>
  <el-select
    v-model="dataValue"
    :disabled="disabled"
    style="width: 100%;"
    placeholder="请选择"
    @change="valueChange(dataValue, option)">
    <el-option
      v-for="(item, index) in option"
      :key="index"
      :label="item.num"
      :value="item.plan_id"/>
  </el-select>
</template>
<script type="text/javascript">
import stringMixin from './stringMixin'
import { crmReceivablesPlanIndex } from '@/api/customermanagement/money'

export default {
  name: 'XhReceivablesPlan', // 回款 下的 回款计划
  components: {},
  mixins: [stringMixin],
  props: {
    relation: {
      // 相关ID
      type: Object,
      default: () => {
        return {}
      }
    }
  },
  data() {
    return {
      option: []
    }
  },
  computed: {},
  watch: {
    relation: function(val) {
      if (val.form_type) {
        this.getPlanList()
      } else {
        this.option = []
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
        types: 1,
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
