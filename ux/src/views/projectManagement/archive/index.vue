<template>
  <div class="archive-project">
    <div class="header">
      归档项目统计
    </div>
    <div
      v-loading="loading"
      class="content-body">
      <div
        v-empty="list.length == 0 && loading == false"
        class="content-body-items">
        <flexbox
          v-for="(item, index) of list"
          :key="index"
          class="archive-item">
          <i
            :style="{'color':item.color}"
            class="wukong wukong-subproject"/>
          <div class="title">{{ item.name }}</div>
          <div class="time-btn">
            <span>{{ item.archive_time | filterTimestampToFormatTime('YYYY-MM-DD') }}</span>
            <el-button
              type="text"
              @click="recoverProject(item, index)">恢复项目</el-button>
          </div>
        </flexbox>
      </div>
    </div>
  </div>
</template>

<script>
import particulars from '../components/particulars'
import {
  workWorkArchiveListAPI,
  workWorkArRecoverAPI
} from '@/api/projectManagement/archive'

export default {
  components: {
    particulars
  },
  data() {
    return {
      loading: false,
      list: []
    }
  },
  created() {
    this.getList()
  },
  methods: {
    /**
     * 获取列表
     */
    getList() {
      this.loading = true
      workWorkArchiveListAPI()
        .then(res => {
          this.list = res.data
          this.loading = false
        })
        .catch(() => {
          this.loading = false
        })
    },

    /**
     * 恢复项目
     */
    recoverProject(val, index) {
      this.$confirm('确定恢复?', '提示', {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning'
      })
        .then(() => {
          this.loading = true
          workWorkArRecoverAPI({
            work_id: val.work_id
          })
            .then(res => {
              this.list.splice(index, 1)
              this.$message.success('恢复成功')
              this.$bus.$emit('recover-project', val.name, val.work_id)
              this.loading = false
            })
            .catch(() => {
              this.loading = false
            })
        })
        .catch(() => {
          this.$message({
            type: 'info',
            message: '已取消'
          })
        })
    }
  }
}
</script>

<style scoped lang="scss">
.archive-project {
  height: 100%;
  overflow: hidden;
  position: relative;
  .header {
    height: 60px;
    line-height: 60px;
    position: relative;
    padding: 0 20px;
    font-size: 18px;
  }

  .content-body {
    background-color: white;
    position: absolute;
    top: 60px;
    right: 0;
    bottom: 0;
    left: 0;
    border-radius: 3px;
    overflow-y: auto;
    border: 1px solid #e6e6e6;
  }

  .content-body-items {
    height: 100%;
  }

  .archive-item {
    position: relative;
    padding: 8px 20px;
    .title {
      flex: 1;
      white-space: pre-wrap;
      word-wrap: break-word;
    }
  }

  .archive-item::before {
    content: ' ';
    position: absolute;
    bottom: 0;
    right: 15px;
    height: 1px;
    border-bottom: 1px solid #e5e5e5;
    color: #e5e5e5;
    -webkit-transform-origin: 0 0;
    transform-origin: 0 0;
    -webkit-transform: scaleY(0.5);
    transform: scaleY(0.5);
    left: 15px;
    z-index: 2;
  }

  .time-btn {
    span {
      text-align: center;
      margin-right: 30px;
      color: #999;
      font-size: 14px;
    }

    .el-button {
      margin-right: 30px;
    }
  }
}

.wukong-subproject {
  font-size: 22px;
  display: block;
  margin-right: 5px;
}
</style>
