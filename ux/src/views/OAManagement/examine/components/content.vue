<template>
  <div
    v-loading="loading"
    class="content">
    <div class="select-box">
      <div class="select-group">
        <label>审核状态</label>
        <el-select
          v-model="check_status"
          size="small"
          placeholder="请选择"
          @change="searchBtn">
          <el-option
            v-for="item in statusOptions"
            :key="item.key"
            :label="item.label"
            :value="item.key"/>
        </el-select>
      </div>
      <div class="select-group">
        <label>发起时间</label>
        <el-date-picker
          v-model="between_time"
          type="daterange"
          style="padding: 0px 10px;width: 250px;"
          range-separator="-"
          value-format="yyyy-MM-dd"
          start-placeholder="开始日期"
          end-placeholder="结束日期"
          @change="searchBtn"/>
      </div>
    </div>
    <examine-section
      :id="'examine-list-box' + by"
      :list="list"
      class="list-box"
      @handle="searchBtn">
      <p
        slot="load"
        class="load">
        <el-button
          :loading="loadMoreLoading"
          type="text">{{ loadMoreLoading ? '加载更多' : '没有更多了' }}</el-button>
      </p>
    </examine-section>
  </div>
</template>

<script>
import { oaExamineIndex } from '@/api/oamanagement/examine'
import { formatTimeToTimestamp } from '@/utils'
import ExamineSection from './examineSection'

export default {
  components: {
    ExamineSection
  },
  props: {
    // 类型 my我发起的,examine我审批的
    by: String,
    // 审批类型ID
    category_id: [String, Number]
  },
  data() {
    return {
      loading: false,
      loadMoreLoading: true,
      check_status: this.by == 'examine' ? 'stay_examine' : 'all',
      between_time: [],
      list: [],
      // 判断是否发请求
      isPost: false,
      page: 1
    }
  },
  computed: {
    statusOptions() {
      if (this.by == 'examine') {
        return [
          { label: '待我审批的', key: 'stay_examine' },
          { label: '我已审批的', key: 'already_examine' }
        ]
      } else {
        return [
          { label: '全部', key: 'all' },
          { label: '待审', key: '0' },
          { label: '审批中', key: '1' },
          { label: '通过', key: '2' },
          { label: '失败', key: '3' },
          { label: '撤回', key: '4' }
        ]
      }
    }
  },
  watch: {
    category_id: function(params) {
      this.page = 1
      this.list = []
      this.getList()
    }
  },
  mounted() {
    // 分批次加载
    const dom = document.getElementById('examine-list-box' + this.by)
    dom.onscroll = () => {
      const scrollOff = dom.scrollTop + dom.clientHeight - dom.scrollHeight
      // 滚动条到底部的条件
      if (Math.abs(scrollOff) < 10 && this.loadMoreLoading == true) {
        if (!this.isPost) {
          this.isPost = true
          this.page++
          this.getList()
        } else {
          this.loadMoreLoading = false
        }
      }
    }

    this.getList()
  },
  methods: {
    /** 获取列表数据 */
    getList() {
      this.loading = true
      let by = ''
      let check_status = ''
      if (this.by == 'examine') {
        by = this.check_status
        check_status = 'all'
      } else {
        by = this.by
        check_status = this.check_status
      }

      const params = {
        by: by,
        limit: 15,
        category_id: this.category_id,
        check_status: check_status,
        page: this.page
      }

      if (this.between_time && this.between_time.length > 0) {
        params.between_time = this.between_time.map(item => {
          return formatTimeToTimestamp(item)
        })
      }

      oaExamineIndex(params)
        .then(res => {
          this.list = this.list.concat(res.data.list)
          if (res.data.list.length < 15) {
            this.loadMoreLoading = false
          } else {
            this.loadMoreLoading = true
          }
          this.isPost = false
          this.loading = false
        })
        .catch(() => {
          this.isPost = false
          this.loading = false
        })
    },
    // 搜索
    searchBtn() {
      this.list = []
      this.page = 1
      this.getList()
    },
    // 重置
    resetBtn() {
      this.check_status = 'all'
      this.between_time = []
      this.$emit('reset')
    }
  }
}
</script>

<style scoped lang="scss">
@import '../../styles/content.scss';
.content {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow-y: auto;
  .select-box {
    margin: 10px 0 20px;
    .select-group {
      margin-right: 20px;
      display: inline-block;
      label {
        @include color9;
        margin-right: 10px;
      }
      .el-select {
        width: 116px;
        height: 30px;
      }
    }
    .btn-box {
      float: right;
      margin-right: 10px;
    }
  }
  .list-box {
    overflow: auto;
    padding-right: 30px;
  }
}

.load {
  color: #999;
  font-size: 13px;
  margin: 0 auto 15px;
  text-align: center;
  .el-button,
  .el-button:focus {
    color: #ccc;
    cursor: auto;
  }
}
</style>
