<template>
  <div class="address-book oa-bgcolor">
    <div class="header">
      <el-tabs
        v-model="activeName"
        @tab-click="handleClick">
        <el-input
          v-model="inputModel"
          placeholder="搜索成员"
          prefix-icon="el-icon-search"
          @blur="blurFun"
          @keyup.enter.native="blurFun"/>
        <el-tab-pane
          label="员工"
          name="1">
          <v-staff
            v-loading="staffLoading"
            :staff-data="staffData"/>
        </el-tab-pane>
        <el-tab-pane
          label="部门"
          name="2">
          <v-department :dep-data="depData"/>
        </el-tab-pane>
      </el-tabs>
    </div>
  </div>
</template>

<script>
import { addresslist } from '@/api/oamanagement/addressBook'
import VStaff from './staff'
import VDepartment from './department'
export default {
  components: {
    VStaff,
    VDepartment
  },
  data() {
    return {
      activeName: '1',
      staffData: [],
      depData: [],
      inputModel: '',
      staffLoading: true
    }
  },
  created() {
    // 员工
    this.dataFun(1)
    // 部门
    this.dataFun()
  },
  methods: {
    dataFun(key, search) {
      this.staffLoading = true
      addresslist({
        type: key,
        search: search
      }).then(res => {
        if (key == 1) {
          for (const item in res.data) {
            this.staffData.push({
              letter: item,
              list: res.data[item]
            })
          }
          this.staffLoading = false
        } else {
          this.depData = res.data
        }
      })
    },
    handleClick(key) {
      this.inputModel = ''
    },
    blurFun() {
      this.staffData = []
      let num = 0
      num = this.activeName == '1' ? 1 : ''
      this.dataFun(num, this.inputModel)
    }
  }
}
</script>

<style scoped lang="scss">
@import '../styles/tabs.scss';
.address-book {
  .header {
    height: 100%;
    .el-tabs /deep/ {
      height: 100%;
      display: flex;
      flex-direction: column;
      .el-tabs__content {
        flex: 1;
        display: flex;
        flex-direction: column;
        .el-tab-pane {
          display: flex;
          flex-direction: column;
          flex: 1;
          min-height: 0;
        }
        .el-input {
          width: 230px;
          margin: 10px 30px;
        }
        .k-list {
          margin-bottom: 10px;
          .list-right,
          .header-circle {
            display: inline-block;
          }
          .header-circle {
            width: 42px;
            height: 42px;
            margin-right: 10px;
            vertical-align: middle;
          }
          .list-right {
            vertical-align: middle;
            .content {
              color: #777;
              img,
              span {
                vertical-align: middle;
              }
            }
            .content > div {
              display: inline-block;
              margin-right: 15px;
            }
            .k-realname {
              font-size: 14px;
              margin-right: 15px;
              width: 96px;
            }
            .k-realname,
            .content {
              display: inline-block;
            }
          }
        }
      }
    }
  }
}
</style>
