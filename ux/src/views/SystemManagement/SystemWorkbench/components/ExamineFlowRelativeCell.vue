<template>
  <el-popover
    v-model="showPopover"
    :disabled="disabled"
    placement="bottom"
    width="700"
    popper-class="no-padding-popover"
    trigger="click">
    <examine-flow-relative
      ref="examineflowrelative"
      @close="showPopover=false"
      @changeCheckout="checkInfos"/>
    <flexbox
      slot="reference"
      :class="[disabled ? 'is_disabled' : 'is_valid']"
      wrap="wrap"
      class="user-container">
      <div
        v-for="(aitem, aindex) in dataValue"
        :key="aindex"
        class="user-item"
        @click.stop="deleteinfo(aindex)">{{ item.data.form_type==='contract' ? aitem.num : aitem.name }}
        <i class="delete-icon el-icon-close"/>
      </div>
      <div
        v-if="dataValue.length == 0"
        class="add-item">+添加</div>
    </flexbox>
  </el-popover>
</template>
<script type="text/javascript">
import ExamineFlowRelative from './ExamineFlowRelative'

export default {
  name: 'ExamineFlowRelatieveCell', // 关联审批流
  components: {
    ExamineFlowRelative
  },
  props: {
    value: {
      type: Array,
      default: () => {
        return []
      }
    },
    /** 索引值 用于更新数据 */
    index: Number,
    /** 包含数据源 */
    item: Object,
    disabled: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      showPopover: false, // 展示popover
      radio: true, // 是否单选
      dataValue: []
    }
  },
  computed: {},
  watch: {
    value: function(val) {
      this.dataValue = val
    }
  },
  mounted() {
    this.dataValue = this.value
  },
  methods: {
    /** 选中 */
    checkInfos(data) {
      this.dataValue = data.data
      this.$emit('value-change', {
        index: this.index,
        value: data.data
      })
    },
    /** 删除 */
    deleteinfo(index) {
      if (this.disabled) return
      if (this.radio) {
        // 如果单选告知删除
        this.$refs.examineflowrelative.clearAll()
      }
      if (this.dataValue.length === 1) {
        this.dataValue = []
      } else {
        this.dataValue.splice(index, 1)
      }

      this.$emit('value-change', {
        index: this.index,
        value: this.dataValue
      })
    }
  }
}
</script>
<style lang="scss" scoped>
.user-container {
  min-height: 34px;
  position: relative;
  border-radius: 3px;
  font-size: 12px;
  border: 1px solid #ddd;
  color: #333333;
  padding: 5px;
  line-height: 15px;
  .user-item {
    padding: 5px;
    background-color: #e2ebf9;
    border-radius: 3px;
    margin: 3px;
    cursor: pointer;
  }
  .add-item {
    padding: 5px;
    color: #3e84e9;
    cursor: pointer;
  }
  .delete-icon {
    color: #999;
    cursor: pointer;
  }
}

.user-container.is_disabled {
  background-color: #f5f7fa;
  border-color: #e4e7ed;
  cursor: not-allowed;
  .user-item {
    background-color: #f0f4f8ea;
    color: #c0c4cc;
    cursor: not-allowed;
  }
  .delete-icon {
    color: #c0c4cc;
    cursor: not-allowed;
  }
  .add-item {
    color: #c0c4cc;
    cursor: not-allowed;
  }
}

.user-container.is_valid:hover {
  border-color: #c0c4cc;
}
</style>
