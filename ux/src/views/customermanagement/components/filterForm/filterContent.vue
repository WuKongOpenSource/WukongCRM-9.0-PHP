<template>
  <div class="wrapper">
    <ul class="list">
      <li
        v-for="(item, index) in showObj.form"
        :key="index"
        class="list-item">
        <span v-if="item.form_type == 'date' && item.value.length > 1">{{ item.name +'&nbsp;“' + item.value[0] + '-' + item.value[1] + '”' }}</span>
        <span v-else-if="item.form_type === 'datetime' && item.value.length > 1">{{ item.name +'&nbsp;“' + item.value[0] + '-' + item.value[1] + '”' }}</span>
        <span v-else-if="item.form_type === 'business_type' && item.status_id">{{ item.name +'&nbsp;“' + getTypesName(item) + getStatusName(item) + '”' }}</span>
        <span v-else-if="item.form_type === 'address'">{{ `${item.name} ${item.address.state} ${item.address.city} ${item.address.area}` }}</span>
        <span v-else-if="item.form_type === 'user' && item.value.length > 0">{{ item.name +'&nbsp;' + optionsNames[item.condition] + '“' + item.value[0].realname + '”' }}</span>
        <span v-else-if="item.form_type === 'category' && item.value.length > 0">{{ item.name +'&nbsp;“' + item.valueContent + '”' }}</span>
        <span v-else>{{ item.name + '&nbsp;' + optionsNames[item.condition] + '“' + item.value + '”' }}</span>
        <i
          class="el-icon-close icon"
          @click="handleDelete(item, index)"/>
      </li>
    </ul>
  </div>
</template>

<script>
export default {
  name: 'FilterContent',
  props: {
    obj: {
      type: Object,
      default: () => {
        return {}
      },
      required: true
    }
  },
  data() {
    return {
      // 获取条件名称
      optionsNames: {
        is: '等于',
        isnot: '不等于',
        contains: '包含',
        not_contain: '不包含',
        start_with: '开始于',
        end_with: '结束于',
        is_empty: '为空',
        is_not_empty: '不为空',
        eq: '等于',
        neq: '不等于',
        gt: '大于',
        egt: '大于等于',
        lt: '小于',
        elt: '小于等于'
      },
      // 展示信息
      showObj: {}
    }
  },
  computed: {},
  watch: {
    obj: function(val) {
      this.showObj = val
    }
  },
  mounted() {
    this.showObj = this.obj
  },
  methods: {
    /**
     * 删除高级筛选条件
     * @param index
     */
    handleDelete(item, index) {
      this.$delete(this.showObj.obj, item.field)
      this.showObj.form.splice(index, 1)
      this.$emit('delete', { item: item, index: index, obj: this.showObj })
    },
    // 商机组展示名称
    getTypesName(data) {
      if (data.type_id) {
        const obj = data.typeOption.find(item => {
          return item.type_id === data.type_id
        })
        return obj.name || ''
      }
      return ''
    },
    // 商机阶段展示名称
    getStatusName(data) {
      if (data.status_id) {
        const obj = data.statusOption.find(item => {
          return item.status_id === data.status_id
        })
        if (obj.name) {
          return '-' + obj.name
        }
        return ''
      }
      return ''
    }
  }
}
</script>

<style scoped lang="scss">
@mixin left() {
  display: flex;
  justify-content: flex-start;
  align-items: center;
}
@mixin center() {
  display: flex;
  justify-content: center;
  align-items: center;
}

.wrapper {
  width: 100%;
  min-height: 50px;
  background: white;
  border-top: 1px solid #e1e1e1;
  font-size: 13px;
  overflow-x: scroll;
  color: #aaa;
  @include left;
  .list {
    width: 100%;
    padding: 0 20px;
    margin-bottom: 10px;
    flex-shrink: 0;
    @include left;
    .list-item {
      height: 30px;
      padding: 0 10px;
      margin: 10px 15px 0 0;
      border: 1px solid #e1e1e1;
      border-radius: 3px;
      flex-shrink: 0;
      @include center;
      .icon {
        margin-left: 20px;
        cursor: pointer;
      }
    }
  }
}
</style>
