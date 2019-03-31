<template>
  <div class="journal oa-bgcolor">
    <el-button type="primary"
               class="new-btn"
               @click="newBtn">写日志</el-button>
    <el-tabs v-model="activeName"
             @tab-click="tabClick">
      <el-tab-pane :label="item.label"
                   :name="item.key"
                   v-for="(item, index) in tabsData"
                   :key="index">
        <v-content v-loading="contentLoading"
                   id="journal-list-box"
                   :activeName="activeName"
                   :journalData="journalData"
                   :selectAuthority="selectAuthority"
                   :depOptions="depOptions"
                   :nameOptions="nameOptions"
                   :journalLoading="journalLoading"
                   @selectChange="selectChange"
                   @editBtn="editBtn">
          <p class="load"
             slot="load">
            <el-button type="text"
                       :loading="loadMoreLoading">{{loadText}}</el-button>
          </p>
        </v-content>
      </el-tab-pane>
    </el-tabs>
    <new-dialog v-if="showNewDialog"
                :formData="formData"
                :dialogTitle="dialogTitle"
                :imgFileList="imgFileList"
                :accessoryFileList="accessoryFileList"
                :newLoading="newLoading"
                @close="newClose"
                @submitBtn="submitBtn">
    </new-dialog>
  </div>
</template>

<script>
import VContent from './content'
import newDialog from './newDialog'
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
      selectAuthority: false,
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
      contentLoading: true,
      nameOptions: [],
      depOptions: [],
      // 列表加loading
      journalLoading: false,
      newLoading: false,
      // 列表容器
      listBoxDom: null
    }
  },
  watch: {
    $route(to, from) {
      this.$router.go(0)
    },
    journalData: function(newData, oldVal) {
      for (let item of newData) {
        item.allData = {}
        item.allData.business = item.businessList
        item.allData.contacts = item.contactsList
        item.allData.contract = item.contractList
        item.allData.customer = item.customerList
        if (
          item.businessList.length != 0 ||
          item.contactsList.length != 0 ||
          item.contractList.length != 0 ||
          item.customerList.length != 0
        ) {
          item.allDataShow = true
        } else {
          item.allDataShow = false
        }
      }
    }
  },
  created() {
    this.dataList(this.pageNum)
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
  mounted() {
    // 分批次加载
    let _this = this
    for (let item of document.getElementsByClassName('list-box')) {
      item.onscroll = function(e) {
        if (e && e.target.id == 'list-box' + _this.activeName) {
          _this.$bus.emit('journal-list-box-scroll', e.target)
          let doms = item
          var scrollTop = doms.scrollTop
          var windowHeight = doms.clientHeight
          var scrollHeight = doms.scrollHeight //滚动条到底部的条件
          if (scrollTop + windowHeight == scrollHeight) {
            _this.loadMoreLoading = true
            if (_this.isPost) {
              _this.pageNum++
              _this.dataList(_this.pageNum)
            } else {
              _this.loadMoreLoading = false
            }
          }
        }
      }
    }
  },
  methods: {
    // 数据
    dataList(page) {
      journalList({
        page: page,
        limit: 15,
        search: ''
      })
        .then(res => {
          this.contentLoading = false
          if (res.data.list.length == 0 || res.data.list.length != 15) {
            this.loadText = '没有更多了'
            this.isPost = false
            this.loadMoreLoading = false
          } else {
            this.loadText = '加载更多'
            this.isPost = true
          }
          for (let item of res.data.list) {
            item.showComment = false
          }

          this.journalData = this.journalData.concat(res.data.list)
          this.createInitAwaitMessage()
          this.loadMoreLoading = false
        })
        .catch(err => {
          this.contentLoading = false
        })
    },
    dataAPI(key) {
      // 请求列表
      journalList({
        page: 1,
        limit: 15,
        by: this.byData
      })
        .then(res => {
          this.journalData = res.data.list
          this.contentLoading = false
          this.createInitAwaitMessage()
        })
        .catch(err => {
          this.contentLoading = false
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
      this.journalData = []
      this.contentLoading = true
      this.listBoxDom = null
      let byData = ''
      switch (this.activeName) {
        case '1':
          this.selectAuthority = false
          this.byData = ''
          break
        case '2':
          this.selectAuthority = true
          this.byData = 'me'
          break
        case '3':
          this.selectAuthority = false
          this.byData = 'other'
          break
        case '4':
          this.selectAuthority = false
          this.byData = 'notRead'
          break
      }
      this.dataAPI()
    },
    // 关闭新建页面
    newClose() {
      if (this.$route.query.routerKey == 1) {
        this.$router.push('journal')
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
      let dep = []
      if (this.formData.depData) {
        for (let j of this.formData.depData) {
          dep.push(j.id)
        }
      }
      // 获取员工
      let staff = []
      if (this.formData.sentWhoList) {
        for (let h of this.formData.sentWhoList) {
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

        let pramas = {
          category_id: key ? key : '',
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
            journalList({
              page: 1,
              limit: 15,
              search: ''
            }).then(res => {
              for (let item of res.data.list) {
                item.showComment = false
              }
              this.journalData = res.data.list
            })
            this.newLoading = false
            this.$message.success('新建成功')
            this.newClose()
          })
          .catch(err => {
            this.newLoading = false
            this.$message.error('新建失败')
          })
        // 编辑页面
      } else {
        let pramas = {
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
            this.dataAPI('edit')
            this.newClose()
            this.$message.success('编辑成功')
            this.newLoading = false
          })
          .catch(err => {
            this.newLoading = false
            this.$message.error('编辑失败')
          })
      }
    },
    // 编辑按钮
    editBtn(val) {
      this.dialogTitle = '编辑日志'
      this.formData = val
      this.imgFileList = []
      if (val.imgList) {
        for (let item of val.imgList) {
          item.url = item.file_path_thumb
          this.imgFileList.push(item)
        }
      }
      // 附件
      this.accessoryFileList = []
      if (val.fileList) {
        for (let item of val.fileList) {
          item.url = item.file_path_thumb
          this.accessoryFileList.push(item)
        }
      }
      // 员工部门赋值
      this.formData.depData = val.sendStructList ? val.sendStructList : []
      this.formData.sentWhoList = val.sendUserList ? val.sendUserList : []
      this.showNewDialog = true
    },
    selectChange(val) {
      this.journalLoading = true
      journalList({
        send_user_id: val.name,
        category_id: val.category_id,
        create_time: new Date(val.create_time).getTime() / 1000
      })
        .then(res => {
          this.contentLoading = false
          this.journalData = res.data.list
          if (res.data.list.length == 0) {
            this.loadText = '没有更多了'
          }
          this.journalLoading = false
        })
        .catch(err => {
          this.journalLoading = false
        })
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
</style>
