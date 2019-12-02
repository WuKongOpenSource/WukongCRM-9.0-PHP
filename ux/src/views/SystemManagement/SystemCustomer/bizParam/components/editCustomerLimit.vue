<template>
  <el-dialog
    :visible.sync="visible"
    :title="title"
    :append-to-body="true"
    :before-close="close"
    width="550px">
    <div class="position-relative">
      <flexbox
        class="handle-item"
        align="stretch">
        <div
          class="handle-item-name"
          style="margin-top: 8px;">适用范围：</div>
        <xh-struc-user-cell
          :users="users"
          :strucs="strucs"
          style="width: 100%;"
          @value-change="strcUserChange"/>
      </flexbox>
      <flexbox
        class="handle-item"
        align="stretch">
        <div
          class="handle-item-name"
          style="margin-top: 8px;">{{ valueLabel }}</div>
        <el-input
          v-model="value"
          placeholder="请输入内容"/>
      </flexbox>
      <flexbox v-if="showDeal" class="handle-item">
        <div class="handle-item-name">{{ dealLabel }}</div>
        <el-radio-group v-model="is_deal">
          <el-radio :label="1">是</el-radio>
          <el-radio :label="0">否</el-radio>
        </el-radio-group>
      </flexbox>
    </div>
    <span
      slot="footer"
      class="dialog-footer">
      <el-button @click.native="close">取消</el-button>
      <el-button
        type="primary"
        @click="sure">确 定</el-button>
    </span>
  </el-dialog>
</template>

<script>
import {
  crmSettingCustomerConfigSaveAPI,
  crmSettingCustomerConfigUpdateAPI
} from '@/api/systemManagement/SystemCustomer'
import { XhStrucUserCell } from '@/components/CreateCom'

export default {
  name: 'EditCustomerLimit',
  components: {
    XhStrucUserCell
  },
  props: {
    types: [String, Number], // 1拥有客户上限2锁定客户上限

    visible: {
      type: Boolean,
      default: false
    },

    action: {
      type: Object,
      default: () => {
        return {
          type: 'save'
        }
      }
    }
  },
  data() {
    return {
      is_deal: 1,
      value: '',
      users: [],
      strucs: []
    }
  },
  computed: {
    valueLabel() {
      return {
        1: '拥有客户数上限（个）',
        2: '锁定客户数上限（个）'
      }[this.types]
    },

    dealLabel() {
      return {
        1: '成交客户是否占有拥有客户数：',
        2: '成交客户是否占有锁定客户数：'
      }[this.types]
    },

    title() {
      return this.action.type == 'update' ? '编辑规则' : '添加规则'
    },

    // 展示是否
    showDeal() {
      return this.types == 1
    }
  },
  watch: {
    visible(val) {
      if (val) {
        if (this.action.type == 'save') {
          this.clearInfo()
        } else if (this.action.type == 'update') {
          const data = this.action.data
          this.is_deal = data.is_deal
          this.value = data.value
          this.$nextTick(() => {
            this.users = data.user_ids_info
            this.strucs = data.structure_ids_info
          })
        }
      }
    }
  },
  mounted() {},
  methods: {
    close() {
      this.$emit('update:visible', false)
    },

    strcUserChange(data) {
      this.users = data.value.users
      this.strucs = data.value.strucs
    },

    sure() {
      if ((!this.users.length && !this.strucs.length) || !this.value) {
        this.$message.error('请完善信息')
      } else {
        const request = {
          save: crmSettingCustomerConfigSaveAPI,
          update: crmSettingCustomerConfigUpdateAPI
        }[this.action.type]

        const params = {
          user_ids: this.users.map(item => {
            return item.id
          }),
          structure_ids: this.strucs.map(item => {
            return item.id
          }),
          value: this.value,
          types: this.types
        }

        if (this.showDeal) {
          params.is_deal = this.is_deal
        }

        if (this.action.type == 'update') {
          params.id = this.action.data.id
        }

        request(params)
          .then(res => {
            this.$emit('success')
            this.close()
          })
          .catch(() => {})
      }
    },

    clearInfo() {
      this.users = []
      this.strucs = []
      this.is_deal = 1
      this.value = ''
    }
  }
}
</script>

<style scoped lang="scss">
.position-relative {
  position: relative;
}

.handle-box {
  color: #333;
  font-size: 12px;
}
.handle-item {
  padding-bottom: 15px;
  position: relative;
  .handle-item-name {
    flex-shrink: 0;
    width: 150px;
  }
  .handle-item-content {
    flex: 1;
  }
}
</style>
