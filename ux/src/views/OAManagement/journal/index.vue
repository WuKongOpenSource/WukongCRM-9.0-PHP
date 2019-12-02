<template>
  <div class="journal oa-bgcolor">
    <el-button
      type="primary"
      class="new-btn"
      @click="newBtn">写日志</el-button>
    <el-tabs
      v-model="activeName"
      @tab-click="tabClick">
      <el-tab-pane
        v-for="(item, index) in tabsData"
        :name="item.key"
        :key="index">
        <el-badge
          slot="label"
          :hidden="item.key != '3' || messageOANum.logNum == 0"
          :max="99"
          :value="messageOANum.logNum">
          <span>{{ item.label }}</span>
        </el-badge>
        <v-content
          id="journal-list-box"
          :ref="'log-list' + item.key"
          :active-name="activeName"
          :journal-data="journalData"
          :dep-options="depOptions"
          :name-options="nameOptions"
          :journal-loading="journalLoading"
          @selectChange="refreshLogList"
          @editBtn="editBtn">
          <p
            slot="load"
            class="load">
            <el-button
              :loading="loadMoreLoading"
              type="text">{{ loadText }}</el-button>
          </p>
        </v-content>
      </el-tab-pane>
    </el-tabs>
    <new-dialog
      v-if="showNewDialog"
      :form-data="formData"
      :dialog-title="dialogTitle"
      :img-file-list="imgFileList"
      :accessory-file-list="accessoryFileList"
      :new-loading="newLoading"
      @close="newClose"
      @submitBtn="submitBtn"/>
  </div>
</template>

<script>
import VContent from './content'
import newDialog from './newDialog'
import { mapGetters } from 'vuex'
import { objDeepCopy } from '@/utils'

// API
import {
  journalList,
  journalAdd,
  journalEdit
} from '@/api/oamanagement/journal'
import { depList, usersList } from '@/api/common'

export default {
  components: {
    VContent,
    newDialog
  },
  data() {
    return {
      activeName: '1',
      tabsData: [
        { label: '全部', key: '1' },
        { label: '我发出的日志', key: '2' },
        { label: '我收到的', key: '3' },
        { label: '未读', key: '4' }
      ],
      // 日志数据
      journalData: [],
      // 显示新建页面
      showNewDialog: false,
      // 新建数据
      formData: {},
      // 弹出框标题
      dialogTitle: '',
      // 页数
      pageNum: 1,
      loadText: '加载更多',
      loadMoreLoading: true,
      // 判断是否还有数据
      isPost: true,
      // 图片数组
      imgFileList: [],
      // 附件数组
      accessoryFileList: [],
      nameOptions: [],
      depOptions: [],
      // 列表加loading
      journalLoading: false,
      newLoading: false,
      // 列表容器
      listBoxDom: null
    }
  },
  computed: {
    ...mapGetters(['messageOANum']),
    byData() {
      return { '1': '', '2': 'me', '3': 'other', '4': 'notRead' }[
        this.activeName
      ]
    }
  },
  watch: {
    $route(to, from) {
      this.$router.go(0)
    }
  },
  beforeRouteUpdate(to, from, next) {
    if (to.query.routerKey == 1) {
      this.newBtn()
    }
    next()
  },
  mounted() {
    this.initControlPage()
    this.getLogList()
    if (this.$route.query.routerKey == 1) {
      this.newBtn()
    }
    // 部门列表
    depList().then(res => {
      this.depOptions = res.data
    })
    // 用户列表
    usersList().then(res => {
      this.nameOptions = res.data
    })
  },
  methods: {
    initControlPage() {
      // 分批次加载
      for (const dom of document.getElementsByClassName('list-box')) {
        dom.onscroll = e => {
          if (e && e.target.id == 'list-box' + this.activeName) {
            this.$bus.emit('journal-list-box-scroll', e.target)
            const scrollOff = dom.scrollTop + dom.clientHeight - dom.scrollHeight
            // 滚动条到底部的条件
            if (Math.abs(scrollOff) < 10 && this.loadMoreLoading == true) {
              if (!this.isPost) {
                this.isPost = true
                this.pageNum++
                this.getLogList()
              } else {
                this.loadMoreLoading = false
              }
            }
          }
        }
      }
    },
    // 数据
    getLogList() {
      const params = objDeepCopy(
        this.$refs['log-list' + this.activeName][0].fromData
      )
      if (params.create_time) {
        params.create_time = new Date(params.create_time).getTime() / 1000
      } else {
        params.create_time = ''
      }
      params.page = this.pageNum
      params.limit = 15
      params.by = this.byData
      this.journalLoading = true
      journalList(params)
        .then(res => {
          this.journalLoading = false
          if (res.data.list.length < 15) {
            this.loadText = '没有更多了'
            this.loadMoreLoading = false
          } else {
            this.loadText = '加载更多'
            this.loadMoreLoading = true
          }
          for (const item of res.data.list) {
            item.showComment = false
          }

          this.journalData = this.journalData.concat(res.data.list)
          this.createInitAwaitMessage()
          this.isPost = false
        })
        .catch(() => {
          this.journalLoading = false
          this.isPost = false
        })
    },
    createInitAwaitMessage() {
      if (!this.listBoxDom) {
        this.$nextTick(() => {
          this.listBoxDom = document.getElementsByClassName('list-box')[
            parseInt(this.activeName) - 1
          ]
          this.$bus.emit(
            'journal-list-box-scroll',
            document.getElementsByClassName('list-box')[
              parseInt(this.activeName) - 1
            ]
          )
        })
      }
    },
    // 写日志
    newBtn() {
      this.dialogTitle = '写日志'
      this.showNewDialog = true
      this.formData = {}
      this.imgFileList = []
      this.accessoryFileList = []
    },
    tabClick(val) {
      this.listBoxDom = null
      this.refreshLogList()
    },
    // 刷新列表
    refreshLogList() {
      this.pageNum = 1
      this.journalData = []
      this.getLogList()
    },
    // 关闭新建页面
    newClose() {
      if (this.$route.query.routerKey == 1) {
        this.showNewDialog = false
        this.$router.go(-1)
      } else {
        this.showNewDialog = false
      }
    },
    // 新建提交
    submitBtn(key, file, img, relevanceAll) {
      this.newLoading = true
      let imgList = []
      let fileList = []
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

      if (img) {
        imgList = img.map(function(file, index, array) {
          if (file.response) {
            return file.response.data[0].file_id
          } else if (file.file_id) {
            return file.file_id
          }
          return ''
        })
      }
      // 附件
      if (file) {
        fileList = file.map(function(file, index, array) {
          if (file.response) {
            return file.response.data[0].file_id
          } else if (file.file_id) {
            return file.file_id
          }
          return ''
        })
      }
      if (this.dialogTitle == '写日志') {
        // 图片

        const pramas = {
          category_id: key || '',
          content: this.formData.content,
          tomorrow: this.formData.tomorrow,
          question: this.formData.question,
          file: fileList.concat(imgList),
          // img: imgList,
          send_user_ids: staff,
          send_structure_ids: dep,
          customer_ids: relevanceAll.customer_ids,
          contacts_ids: relevanceAll.contacts_ids,
          business_ids: relevanceAll.business_ids,
          contract_ids: relevanceAll.contract_ids
        }
        if (key) {
          this.formData.category_id = key
        }
        journalAdd(pramas)
          .then(res => {
            this.refreshLogList()
            this.newLoading = false
            this.$message.success('新建成功')
            this.newClose()
          })
          .catch(() => {
            this.newLoading = false
            this.$message.error('新建失败')
          })
        // 编辑页面
      } else {
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
            this.newClose()
            this.$message.success('编辑成功')
            this.newLoading = false
          })
          .catch(() => {
            this.newLoading = false
          })
      }
    },
    // 编辑按钮
    editBtn(val) {
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
      this.showNewDialog = true
    }
  }
}
</script>

<style scoped lang="scss">
@import '../styles/tabs.scss';

.journal {
  overflow: auto;
  position: relative;
  .new-btn {
    position: absolute;
    top: 10px;
    right: 40px;
    z-index: 999;
  }
  .el-tabs {
    height: 100%;
    display: flex;
    flex-direction: column;
  }
  .el-tabs /deep/ .el-tabs__content {
    padding: 0 30px;
    flex: 1;
    display: flex;
    flex-direction: column;
    margin-bottom: 20px;
    min-height: 0;
    .el-tab-pane {
      flex: 1;
      display: flex;
      flex-direction: column;
      min-height: 0;
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
}

// 消息效果
.el-badge /deep/ .el-badge__content.is-fixed {
  top: 15px;
}
</style>
