<template>
  <div v-loading="loading">
    <div v-empty="list.length === 0">
      <div class="log-items">
        <journal-cell
          v-for="(item, index) in list"
          :key="index"
          :data="item.dataInfo"
          class="list-cell"
          @on-handle="jourecallCellHandle"/>
        <div class="load">
          <el-button
            :loading="loadMoreLoading"
            type="text">{{ loadMoreLoading ? '加载更多' : '没有更多了' }}</el-button>
        </div>
      </div>
    </div>
    <c-r-m-full-screen-detail
      :visible.sync="showFullDetail"
      :crm-type="detailCRMType"
      :id="rowID"/>
    <new-dialog
      v-if="showNewDialog"
      :form-data="formData"
      :dialog-title="dialogTitle"
      :img-file-list="imgFileList"
      :accessory-file-list="accessoryFileList"
      :new-loading="newLoading"
      @close="showNewDialog=false"
      @submitBtn="submitBtn"/>
  </div>
</template>

<script>
// API
import { journalDelete, journalEdit } from '@/api/oamanagement/journal'
import JournalCell from '@/views/OAManagement/journal/journalCell' // 办公日志
import { crmRecordIndex } from '@/api/customermanagement/common'

export default {
  /** 日志 跟进记录*/
  name: 'JournalLog',
  components: {
    JournalCell,
    CRMFullScreenDetail: () =>
      import('@/views/customermanagement/components/CRMFullScreenDetail.vue'),
    NewDialog: () => import('@/views/OAManagement/journal/newDialog')
  },
  props: {
    /** 模块ID */
    id: [String, Number],
    /** 没有值就是全部类型 有值就是当个类型 */
    crmType: {
      type: String,
      default: ''
    }
  },
  data() {
    return {
      loading: false,
      loadMoreLoading: true,
      isPost: false,
      page: 1,
      list: [], // 跟进记录列表
      rowID: '', // 行信息
      // 详情
      showFullDetail: false,
      detailCRMType: '',
      // 编辑
      // 显示新建页面
      showNewDialog: false,
      // 新建数据
      formData: {},
      // 弹出框标题
      dialogTitle: '',
      // 图片数组
      imgFileList: [],
      // 附件数组
      accessoryFileList: [],
      newLoading: false
    }
  },
  computed: {},
  watch: {
    id: function(val) {
      this.refreshList()
    }
  },
  mounted() {
    // 分批次加载
    const dom = document.getElementById('follow-log-content')
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
  activated: function() {},
  deactivated: function() {},
  methods: {
    getList() {
      this.loading = true
      crmRecordIndex({
        page: this.page,
        limit: 10,
        types: 'crm_' + this.crmType,
        types_id: this.id,
        by: 'log' // 类型（record 跟进记录，log 日志、examine审批、task 任务、event日程、默认是全部）
      })
        .then(res => {
          for (const item of res.data.list) {
            item.dataInfo.allData = {}
            item.dataInfo.allData.business = item.dataInfo.businessList
            item.dataInfo.allData.contacts = item.dataInfo.contactsList
            item.dataInfo.allData.contract = item.dataInfo.contractList
            item.dataInfo.allData.customer = item.dataInfo.customerList
            if (
              item.dataInfo.businessList.length != 0 ||
              item.dataInfo.contactsList.length != 0 ||
              item.dataInfo.contractList.length != 0 ||
              item.dataInfo.customerList.length != 0
            ) {
              item.dataInfo.allDataShow = true
            } else {
              item.dataInfo.allDataShow = false
            }
          }
          this.list = this.list.concat(res.data.list)
          if (res.data.list.length < 10) {
            this.loadMoreLoading = false
          } else {
            this.loadMoreLoading = true
          }
          this.loading = false
          this.isPost = false
        })
        .catch(() => {
          this.isPost = false
          this.loading = false
        })
    },
    refreshList() {
      this.page = 1
      this.list = []
      this.getList()
    },
    jourecallCellHandle(data) {
      // 编辑按钮
      if (data.type == 'edit') {
        const val = data.data.item
        this.showNewDialog = true
        this.dialogTitle = '编辑日志'
        this.formData = val
        this.imgFileList = []
        if (val.imgList) {
          for (const item of val.imgList) {
            item.url = item.file_path_thumb
            this.imgFileList.push(item)
          }
        }
        // 附件
        this.accessoryFileList = []
        if (val.fileList) {
          for (const item of val.fileList) {
            item.url = item.file_path_thumb
            this.accessoryFileList.push(item)
          }
        }
        // 员工部门赋值
        this.formData.depData = val.sendStructList ? val.sendStructList : []
        this.formData.sentWhoList = val.sendUserList ? val.sendUserList : []
        // 删除按钮
      } else if (data.type == 'delete') {
        this.$confirm('确定删除?', '提示', {
          confirmButtonText: '确定',
          cancelButtonText: '取消',
          type: 'warning'
        })
          .then(() => {
            journalDelete({ log_id: data.data.item.log_id }).then(res => {
              this.$message({
                type: 'success',
                message: '删除成功!'
              })
              for (const i in this.list) {
                if (this.list[i].log_id == data.data.item.log_id) {
                  this.list.splice(i, 1)
                  break
                }
              }
            })
          })
          .catch(() => {
            this.$message({
              type: 'info',
              message: '已取消删除'
            })
          })
        // 相关详情
      } else if (data.type == 'related-detail') {
        this.rowID = data.data.item.key
        this.detailCRMType = data.data.type
        this.showFullDetail = true
      }
    },
    // 新建提交
    submitBtn(key, file, img, relevanceAll) {
      this.newLoading = true
      const imgList = []
      const fileList = []
      // 获取部门
      const dep = []
      if (this.formData.depData) {
        for (const j of this.formData.depData) {
          dep.push(j.id)
        }
      }
      // 获取员工
      const staff = []
      if (this.formData.sentWhoList) {
        for (const h of this.formData.sentWhoList) {
          staff.push(h.id)
        }
      }
      for (const item of this.imgFileList) {
        imgList.push(item.file_id)
      }
      for (const item of this.accessoryFileList) {
        fileList.push(item.file_id)
      }
      const pramas = {
        id: this.formData.log_id,
        category_id: key,
        content: this.formData.content,
        tomorrow: this.formData.tomorrow,
        question: this.formData.question,
        file: fileList.concat(imgList),
        send_user_ids: staff,
        send_structure_ids: dep,
        customer_ids: relevanceAll.customer_ids,
        contacts_ids: relevanceAll.contacts_ids,
        business_ids: relevanceAll.business_ids,
        contract_ids: relevanceAll.contract_ids
      }
      journalEdit(pramas)
        .then(res => {
          this.refreshList()
          this.showNewDialog = false
          this.$message.success('编辑成功')
          this.newLoading = false
        })
        .catch(() => {
          this.newLoading = false
          this.$message.error('编辑失败')
        })
    }
  }
}
</script>

<style lang="scss" scoped>
.log-items {
  min-height: 400px;
  position: relative;
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

.list-cell {
  margin-bottom: 20px;
  border-radius: 4px;
}
</style>
