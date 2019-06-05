<template>
  <div v-loading="loading">
    <div class="content-title">
      <span>合同到期提醒设置</span>
      <el-button type="primary"
                 class="rt"
                 size="medium"
                 @click="save">保存</el-button>
    </div>
    <div class="content-body">
      <div class="tips">设置提前提醒天数之后，根据合同的”合同到期时间”计算提醒时间</div>
      <div class="set-content">
        <el-radio v-model="contractConfig"
                  label="0">不提醒</el-radio>
        <el-radio v-model="contractConfig"
                  label="1">提前提醒天数</el-radio>
        <div v-if="contractConfig == 1"
             class="time-set">
          <el-input v-model="contractDay"></el-input><span>天</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import {
  crmSettingConfigData,
  crmSettingContractDayAPI
} from '@/api/systemManagement/SystemCustomer'

export default {
  name: 'contract-expire-set',

  components: {},

  data() {
    return {
      loading: false, // 展示加载中效果

      contractDay: 0, // 合同到期提醒天数
      contractConfig: '0'
    }
  },

  created() {
    this.getDetail()
  },

  methods: {
    /**
     * 获取详情
     */
    getDetail() {
      this.loading = true
      crmSettingConfigData()
        .then(res => {
          this.loading = false
          this.contractDay = res.data.contract_day
          this.contractConfig = res.data.contract_config
        })
        .catch(() => {
          this.loading = false
        })
    },

    /**
     * 保存操作
     */
    save() {
      this.loading = true
      let params = {}
      if (this.contractConfig == 1) {
        params.contract_day = this.contractDay
        params.contract_config = 1
      } else {
        params.contract_config = 0
      }
      crmSettingContractDayAPI(params)
        .then(res => {
          this.loading = false

          this.$message.success(res.data)
        })
        .catch(() => {
          this.loading = false
        })
    }
  }
}
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
.content-title {
  padding: 10px;
  border-bottom: 1px solid #e6e6e6;
}

.content-title > span {
  display: inline-block;
  height: 36px;
  line-height: 36px;
  margin-left: 20px;
}

.content-body {
  height: calc(100% - 57px);
  padding: 30px;
  overflow-y: auto;
}

/* 合同 样式*/
.tips {
  font-size: 13px;
  color: #999;
}

.el-radio {
  display: block;
  padding: 10px 0;
}

.set-content {
  margin-top: 20px;
}

.time-set {
  padding-left: 20px;
  margin-top: 5px;

  .el-input {
    width: 200px;
  }

  span {
    margin-left: 5px;
    color: #333333;
    font-size: 13px;
  }
}
</style>
