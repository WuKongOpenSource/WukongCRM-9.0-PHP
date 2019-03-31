<template>
  <el-select style="width: 100%;"
             v-model="dataValue"
             @change="valueChange"
             placeholder="请选择"
             :disabled="disabled">
    <el-option v-for="item in options"
               :key="item.type_id"
               :label="item.name"
               :value="item.type_id">
    </el-option>
  </el-select>
</template>
<script type="text/javascript">
import stringMixin from './stringMixin'

export default {
  name: 'xh-business-status', // 商机状态
  components: {},
  mixins: [stringMixin],
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
  data() {
    return {
      options: []
    }
  },
  props: {},
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
