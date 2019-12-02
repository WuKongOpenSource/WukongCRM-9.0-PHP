<template>
  <el-select
    v-model="dataValue"
    :disabled="disabled"
    style="width: 100%;"
    placeholder="请选择"
    @change="valueChange">
    <el-option
      v-for="item in options"
      :key="item.type_id"
      :label="item.name"
      :value="item.type_id"/>
  </el-select>
</template>
<script type="text/javascript">
import stringMixin from './stringMixin'

export default {
  name: 'XhBusinessStatus', // 商机状态
  components: {},
  mixins: [stringMixin],
  props: {},
  data() {
    return {
      options: []
    }
  },
  computed: {},
  watch: {
    item: {
      handler(val) {
        if (val && val.data.setting) {
          this.options = val.data.setting
        } else {
          this.options = []
        }
      },
      deep: true,
      immediate: true
    }
  },
  mounted() {},
  methods: {
    // 输入的值
    valueChange(val) {
      /** 商机组顺便回调筛选数据 */
      this.$emit('value-change', {
        index: this.index,
        value: val,
        data: this.options
      })
    }
  }
}
</script>
<style lang="scss" scoped>
</style>
