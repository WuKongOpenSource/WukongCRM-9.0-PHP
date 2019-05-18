<template>
  <slide-view v-loading="loading"
              class="d-view"
              :listenerIDs="['workbench-main-container']"
              :noListenerClass="noListenerClass"
              @side-close="hideView"
              :body-style="{padding: '10px 30px', height: '100%'}">
    <flexbox orient="vertical"
             style="height: 100%;">
      <flexbox class="detail-header">
        <div class="header-name">{{category_name}}</div>
        <img @click="hideView"
             class="header-close"
             src="@/assets/img/task_close.png" />
      </flexbox>
      <div class="detail-body">
        <!-- 基本信息 -->
        <flexbox :gutter="0"
                 align="stretch"
                 wrap="wrap">
          <flexbox-item :span="0.5"
                        v-for="(item, index) in list"
                        :key="index"
                        v-if="item.form_type !== 'examine_cause' && item.form_type !== 'business_cause'"
                        class="b-cell">
            <flexbox v-if="item.form_type === 'user'"
                     align="stretch"
                     class="b-cell-b">
              <div class="b-cell-name">{{item.name}}</div>
              <div class="b-cell-value">
                <flexbox :gutter="0"
                         wrap="wrap"
                         style="padding: 0px 10px 10px 0px;">
                  <div v-for="(item, index) in item.value"
                       :key="index">
                    {{item.realname}}&nbsp;&nbsp;
                  </div>
                </flexbox>
              </div>
            </flexbox>

            <flexbox v-else-if="item.form_type === 'structure'"
                     align="stretch"
                     class="b-cell-b">
              <div class="b-cell-name">{{item.name}}</div>
              <div class="b-cell-value">
                <flexbox :gutter="0"
                         wrap="wrap"
                         style="padding: 0px 10px 10px 0px;">
                  <div v-for="(item, index) in item.value"
                       :key="index">
                    {{item.name}}&nbsp;&nbsp;
                  </div>
                </flexbox>
              </div>
            </flexbox>

            <flexbox v-else-if="item.form_type === 'checkbox'"
                     align="stretch"
                     class="b-cell-b">
              <div class="b-cell-name">{{item.name}}</div>
              <div class="b-cell-value">
                <flexbox :gutter="0"
                         wrap="wrap"
                         style="padding: 0px 10px 10px 0px;">
                  <div v-for="(item, index) in item.value"
                       :key="index">
                    {{item}}&nbsp;&nbsp;
                  </div>
                </flexbox>
              </div>
            </flexbox>

            <flexbox v-else-if="item.form_type === 'file'"
                     align="stretch"
                     class="b-cell-b">
              <div class="b-cell-name">{{item.name}}</div>
              <div class="b-cell-value">
                <flexbox class="f-item"
                         v-for="(file, index) in item.value"
                         :key="index">
                  <img class="f-img"
                       src="@/assets/img/relevance_file.png" />
                  <div class="f-name">{{file.name.length > 15 ? (file.name.substring(0, 15) + '...'): file.name+'('+file.size+')'}}</div>
                  <el-button type="text"
                             @click.native="handleFile('preview', item.value, index)">预览</el-button>
                  <el-button type="text"
                             @click.native="handleFile('download', file, index)">下载</el-button>
                </flexbox>
              </div>
            </flexbox>

            <flexbox v-else
                     align="stretch"
                     class="b-cell-b">
              <div class="b-cell-name">{{item.name}}</div>
              <div class="b-cell-value">{{item.value}}</div>
            </flexbox>
          </flexbox-item>
        </flexbox>
        <!-- 图片 附件 -->
        <div class="accessory"
             v-if="fileList.length > 0 || imgList.length > 0">
          <!-- 图片 -->
          <div class="upload-img-box">
            <div v-for="(imgItem, k) in imgList"
                 :key="k"
                 class="img-list"
                 @click="imgZoom(imgList, k)">
              <img :key="imgItem.file_path_thumb"
                   v-lazy="imgItem.file_path_thumb">
            </div>
          </div>
          <!-- 附件 -->
          <div class="accessory-box">
            <file-cell v-for="(file, fileIndex) in fileList"
                       :key="fileIndex"
                       :data="file"
                       :cellIndex="fileIndex"></file-cell>
          </div>
        </div>
        <!-- 行程 报销 -->
        <create-sections title="行程"
                         class="create-sections"
                         v-if="category_id && category_id == 3 && travelList && travelList.length > 0">
          <el-table :data="travelList"
                    style="font-size: 13px;"
                    align="center"
                    header-align="center"
                    stripe>
            <el-table-column v-for="(item, index) in travelField"
                             :key="index"
                             show-overflow-tooltip
                             :prop="item.prop"
                             :formatter="fieldFormatter"
                             :label="item.label">
              <template slot="header"
                        slot-scope="scope">
                <div class="table-head-name">{{scope.column.label}}</div>
              </template>
            </el-table-column>
          </el-table>
        </create-sections>
        <create-sections title="报销"
                         class="create-sections"
                         v-if="category_id && category_id == 5 && travelList && travelList.length > 0">
          <el-table :data="travelList"
                    style="font-size: 13px;"
                    align="center"
                    header-align="center"
                    stripe>
            <el-table-column v-for="(item, index) in expensesField"
                             :key="index"
                             show-overflow-tooltip
                             :prop="item.prop"
                             :formatter="fieldFormatter"
                             :label="item.label">
              <template slot="header"
                        slot-scope="scope">
                <div class="table-head-name">{{scope.column.label}}</div>
              </template>
            </el-table-column>
            <el-table-column label="发票"
                             width="50">
              <template slot-scope="scope">
                <flexbox justify="center">
                  <el-button type="text"
                             @click.native="handleFile('preview', scope.row.imgList, 0)">{{scope.row.imgList.length}}张</el-button>
                </flexbox>
              </template>
            </el-table-column>
          </el-table>
        </create-sections>
        <!-- 关联业务 -->
        <create-sections v-if="relatedListData.contacts.length > 0 || relatedListData.customer.length > 0 || relatedListData.business.length > 0 || relatedListData.contract.length > 0"
                         title="关联业务"
                         class="create-sections">
          <div class="related-business create-sections-content">
            <div v-for="(items, key) in relatedListData"
                 :key="key">
              <related-business-cell v-for="(item, itemIndex) in items"
                                     :data="item"
                                     :cellIndex="itemIndex"
                                     :type="key"
                                     :key="itemIndex"
                                     :showFoot="false"
                                     @click.native="checkRelatedDetail(key, item)">
              </related-business-cell>
            </div>
          </div>
        </create-sections>
        <!-- 审核信息 -->
        <create-sections title="审核信息"
                         class="create-sections">
          <examine-info :id="id"
                        class="create-sections-content"
                        examineType="oa_examine"
                        :flow_id="detail.flow_id"
                        @on-handle="examineHandle">
          </examine-info>
        </create-sections>
      </div>
    </flexbox>
    <c-r-m-full-screen-detail :visible.sync="showRelatedDetail"
                              :crmType="relatedCRMType"
                              :id="relatedID"></c-r-m-full-screen-detail>
  </slide-view>
</template>

<script>
import { oaExamineRead } from '@/api/oamanagement/examine'
import { filedGetField } from '@/api/common'
import SlideView from '@/components/SlideView'
import CreateSections from '@/components/CreateSections'
import ExamineInfo from '@/components/Examine/ExamineInfo'
import RelatedBusinessCell from '@/views/OAManagement/components/relatedBusinessCell'
import FileCell from '@/views/OAManagement/components/fileCell'
import moment from 'moment'
import { downloadFile, timestampToFormatTime } from '@/utils'

export default {
  /** 审批详情 */
  name: 'examine-detail',
  components: {
    SlideView,
    CreateSections,
    ExamineInfo,
    RelatedBusinessCell,
    CRMFullScreenDetail: () =>
      import('@/views/customermanagement/components/CRMFullScreenDetail.vue'),
    FileCell
  },
  props: {
    // 详情信息id
    id: [String, Number],
    noListenerClass: {
      type: Array,
      default: () => {
        return ['list-box']
      }
    }
  },
  watch: {
    id: function(val) {
      this.getDetial()
    }
  },
  data() {
    return {
      loading: false,
      category_id: '',
      detail: {},
      list: [], // 基本信息
      category_name: '',

      fileList: [],
      imgList: [],

      travelList: [],
      travelField: [
        { prop: 'vehicle', label: '交通工具' },
        { prop: 'trip', label: '单程往返' },
        { prop: 'start_address', label: '出发城市' },
        { prop: 'end_address', label: '目的城市' },
        { prop: 'start_time', label: '开始时间' },
        { prop: 'end_time', label: '结束时间' },
        { prop: 'duration', label: '时长（天）' },
        { prop: 'description', label: '备注' }
      ],
      expensesField: [
        { prop: 'start_address', label: '出发城市' },
        { prop: 'end_address', label: '目的城市' },
        { prop: 'start_time', label: '开始时间' },
        { prop: 'end_time', label: '结束时间' },
        { prop: 'traffic', label: '交通费（元）' },
        { prop: 'stay', label: '住宿费（元）' },
        { prop: 'diet', label: '餐饮费（元）' },
        { prop: 'other', label: '其他费用（元）' },
        { prop: 'description', label: '费用明细描述' }
      ],

      // 相关详情的查看
      relatedID: '',
      relatedCRMType: '',
      showRelatedDetail: false
    }
  },
  computed: {
    relatedListData() {
      return {
        contacts: this.detail.contactsList || [],
        customer: this.detail.customerList || [],
        business: this.detail.businessList || [],
        contract: this.detail.contractList || []
      }
    }
  },
  mounted() {
    this.getDetial()
  },
  methods: {
    // 获取基础信息
    getBaseInfo() {
      this.loading = true
      filedGetField({
        types: 'oa_examine',
        module: 'oa',
        controller: 'examine',
        action: 'read',
        action_id: this.id,
        types_id: this.category_id
      })
        .then(res => {
          var self = this
          this.list = res.data.map(function(item, index) {
            return self.handleShowInfo(item)
          })
          this.loading = false
        })
        .catch(() => {
          this.loading = false
        })
    },
    handleShowInfo(item) {
      if (item.form_type === 'date' && item.value == '0000-00-00') {
        item.value = ''
      } else if (item.form_type === 'datetime') {
        if (item.value == 0) {
          item.value = ''
        } else {
          item.value = item.value
            ? timestampToFormatTime(item.value, 'YYYY-MM-DD HH:mm:ss')
            : ''
        }
      }
      return item
    },
    getDetial() {
      this.loading = true
      oaExamineRead({
        id: this.id
      })
        .then(res => {
          this.loading = false
          this.category_id = res.data.category_id
          this.getBaseInfo()
          this.detail = res.data
          this.category_name = this.detail.category_name

          this.fileList = this.detail.fileList
          this.imgList = this.detail.imgList

          this.travelList = this.detail.travelList
        })
        .catch(() => {
          this.loading = false
        })
    },
    /** 格式化字段 */
    fieldFormatter(row, column) {
      // 如果需要格式化 出差
      if (
        this.category_id == 3 &&
        (column.property == 'start_time' || column.property == 'end_time')
      ) {
        return row[column.property]
          ? timestampToFormatTime(row[column.property], 'YYYY-MM-DD HH:mm:ss')
          : ''
      } else if (
        this.category_id == 5 &&
        (column.property == 'start_time' || column.property == 'end_time')
      ) {
        return row[column.property]
          ? timestampToFormatTime(row[column.property], 'YYYY-MM-DD')
          : ''
      }
      return row[column.property]
    },
    //** 点击关闭按钮隐藏视图 */
    hideView() {
      this.$emit('hide-view')
    },
    // 查看关联业务详情
    checkRelatedDetail(crmType, item) {
      this.relatedID = item[crmType + '_id']
      this.relatedCRMType = crmType
      this.showRelatedDetail = true
    },
    /**
     * 附件查看
     */
    handleFile(type, files, index) {
      if (type === 'preview') {
        if (files && files.length > 0) {
          var previewList = files.map(element => {
            element.url = element.file_path
            return element
          })
          this.$bus.emit('preview-image-bus', {
            index: index,
            data: previewList
          })
        }
      } else if (type === 'download') {
        downloadFile({ path: files.file_path, name: files.name })
      }
    },
    // 放大图片
    imgZoom(images, k) {
      this.$bus.emit('preview-image-bus', {
        index: k,
        data: images.map(function(item, index, array) {
          return {
            url: item.file_path,
            name: item.name
          }
        })
      })
    },
    downloadFile(file) {
      downloadFile({ path: file.file_path, name: file.name })
    },
    // 审批操作
    examineHandle(data) {
      this.$store.dispatch('GetOAMessageNum', 'examine')
      this.$emit('on-examine-handle', data)
    }
  }
}
</script>

<style lang="scss" scoped>
@import '../../styles/content.scss';

.detail-header {
  position: relative;
  min-height: 60px;
  .header-name {
    font-size: 14px;
    color: #333333;
    flex: 1;
  }
  .header-close {
    display: block;
    width: 40px;
    height: 40px;
    margin-left: 20px;
    padding: 10px;
    cursor: pointer;
  }
}

.create-sections {
  padding: 5px 0;
  /deep/ .section-header {
    padding: 5px 0;
  }
}

.create-sections-content {
  padding: 0;
}

.related-business {
  margin: 15px 0;
  .label {
    font-size: 13px;
    margin-bottom: 7px;
  }
  p {
    cursor: pointer;
    color: #3e84e9;
    background: #f5f7fa;
    line-height: 30px;
    margin-bottom: 5px;
    font-size: 13px;
    padding-left: 7px;
    border-radius: 2px;
    img {
      width: 16px;
      @include v-align;
    }
  }
}

.b-cell {
  .b-cell-b {
    width: auto;
    padding: 8px;
    line-height: 22px;
    .b-cell-name {
      width: 100px;
      margin-right: 10px;
      font-size: 13px;
      flex-shrink: 0;
      color: #777;
    }
    .b-cell-value {
      font-size: 13px;
      color: #333;
    }
    .b-cell-foot {
      flex-shrink: 0;
      display: block;
      width: 15px;
      height: 15px;
      margin-left: 8px;
    }
  }
}

.f-item {
  padding: 3px 0;
  height: 25px;
  .f-img {
    position: block;
    width: 15px;
    height: 15px;
    padding: 0 1px;
    margin-right: 8px;
  }
  .f-name {
    color: #666;
    font-size: 12px;
    margin-right: 10px;
  }
}

// 图片 附件
.accessory {
  margin: 0 10px;
  .upload-img-box {
    margin: 10px 0;
    .img-list {
      display: inline-block;
      position: relative;
      margin-right: 10px;
      width: 80px;
      height: 60px;
      line-height: 60px;
      cursor: pointer;
      img {
        width: 80px;
        height: 60px;
      }
      .img-hover {
        position: absolute;
        top: 0;
        right: 0;
        left: 0;
        bottom: 0;
        background-color: rgba(0, 0, 0, 0.5);
        text-align: center;
        font-size: 12px;
        color: #fff;
        display: none;
        span {
          @include cursor;
          display: inline-block;
        }
      }
    }
    .img-list:hover {
      .img-hover {
        display: inline-block;
      }
    }
  }
}

// 表头
.table-head-name {
  color: #909399;
  font-size: 13px;
  line-height: 23px;
  padding: 0;
}

.detail-body {
  flex: 1;
  overflow-y: auto;
  width: 100%;
}

.d-view {
  position: fixed;
  width: 926px;
  top: 60px;
  bottom: 0px;
  right: 0px;
}
</style>

