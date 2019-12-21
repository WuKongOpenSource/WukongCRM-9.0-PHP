<?php
// +----------------------------------------------------------------------
// | Description: 自定义字段模块数据Excel导入导出
// +----------------------------------------------------------------------
// | Author: Michael_xu | gengxiaoxu@5kcrm.com 
// +----------------------------------------------------------------------

namespace app\admin\model;

use app\admin\model\Common;
use app\admin\model\Message;
use com\PseudoQueue as Queue;
use think\Cache;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class Excel extends Common
{
	/**
	 * 支持自定义字段的表，不包含表前缀
	 *
	 * @var array
	 */
	private $types_arr = [
		'crm_leads',
		'crm_customer',
		'crm_contacts',
		'crm_product',
		'admin_user'
	];
	
	/**
	 * 字段类型为 map_address 的地址类型字段，导入导出时占四个字段，四个单元格
	 */
	private $map_address = ['省', '市', '区/县', '详细地址'];

	/**
	 * 导入锁缓存名称
	 */
	const IMPORT_QUEUE = DB_NAME . 'IMPORT_QUEUE';
	
	/**
	 * 导出锁缓存名称
	 */
	const EXPORT_QUEUE = DB_NAME . 'EXPORT_QUEUE';

	/**
	 *获取excel相关列
	**/
	public function stringFromColumnIndex($pColumnIndex = 0)
	{
		static $_indexCache = array();
		if (!isset($_indexCache[$pColumnIndex])) {
			if ($pColumnIndex < 26) {
				$_indexCache[$pColumnIndex] = chr(65 + $pColumnIndex);
			} elseif ($pColumnIndex < 702) {
				$_indexCache[$pColumnIndex] = chr(64 + ($pColumnIndex / 26)) . chr(65 + $pColumnIndex % 26);
			} else {
				$_indexCache[$pColumnIndex] = chr(64 + (($pColumnIndex - 26) / 676)) . chr(65 + ((($pColumnIndex - 26) % 676) / 26)) . chr(65 + $pColumnIndex % 26);
			}
		}
		return $_indexCache[$pColumnIndex];
	}	

	/**
	 * 自定义字段模块导入模板下载
	 * @param $field_list 自定义字段数据
	 * @param $types 分类
	 * @author
	 **/
	public function excelImportDownload($field_list, $types, $save_path = ''){
		$fieldModel = new \app\admin\model\Field();	

 		//实例化主文件
  		$objPHPExcel = new Spreadsheet();        
		$objProps = $objPHPExcel->getProperties(); // 设置excel文档的属性
		$objProps->setCreator("5kcrm"); //创建人
		$objProps->setLastModifiedBy("5kcrm"); //最后修改人
		$objProps->setTitle("5kcrm"); //标题
		$objProps->setSubject("5kcrm data"); //题目
		$objProps->setDescription("5kcrm data"); //描述
		$objProps->setKeywords("5kcrm data"); //关键字
		$objProps->setCategory("5kcrm"); //种类
		$objPHPExcel->setActiveSheetIndex(0); //设置当前的sheet
		$objActSheet = $objPHPExcel->getActiveSheet();
		$objActSheet->setTitle('悟空软件导入模板'.date('Y-m-d',time())); //设置sheet的标题	

		//存储Excel数据源到其他工作薄
		$objPHPExcel->createSheet();
	    $subObject = $objPHPExcel->getSheet(1);
	    $subObject->setTitle('data');
	    //保护数据源
	    $subObject->getProtection()->setSheet(true);
	    $subObject->protectCells('A1:C1000',time());		

		//填充边框
        $styleArray = [
            'borders'=>[
                'outline'=>[
                    'style'=>\PHPExcel_Style_Border::BORDER_THICK, //设置边框
                    'color' => ['argb' => '#F0F8FF'], //设置颜色
                ],
            ],
        ];
        if ($save_path) {
            $objActSheet->setCellValue('A2', '错误原因(导入时需删除本列)');
            $objActSheet->getColumnDimension('A')->setWidth(40); //设置单元格宽度
            $k = 1;
        } else {
            $k = 0;
        }
        foreach ($field_list as $field) {
			if ($field['form_type'] == 'map_address' && $types == 'crm_customer') {
				for ($a=0; $a<=3; $a++){
					$objActSheet->getColumnDimension($this->stringFromColumnIndex($k))->setWidth(20); //设置单元格宽度
                    //如果是所在省的话
					$objActSheet->setCellValue($this->stringFromColumnIndex($k).'2', $this->map_address[$a]);
					$k++;
				}
			} else {
				$objActSheet->getColumnDimension($this->stringFromColumnIndex($k))->setWidth(20); //设置单元格宽度
				if ($field['form_type'] == 'select' || $field['form_type'] == 'checkbox' || $field['form_type'] == 'radio' || $field['form_type'] == 'category') {
                    //产品类别
                    if ($field['form_type'] == 'category' && $field['types'] == 'crm_product') {
                    	$setting = db('crm_product_category')->order('pid asc')->column('name');
                    } else {
                    	$setting = $field['setting'] ? : [];
                    }
                    $select_value = implode(',',$setting);

					//解决下拉框数据来源字串长度过大：将每个来源字串分解到一个空闲的单元格中
					$str_len = strlen($select_value);
					$selectList = array();
					if ($str_len >= 255) {
						$str_list_arr = explode(',', $select_value);   
						if ($str_list_arr) {
							foreach ($str_list_arr as $i1=>$d) {  
						        $c = $this->stringFromColumnIndex($k).($i1+1);  
						        $subObject->setCellValue($c,$d);
						        $selectList[$d]=$d;
						    }
							$endcell = $c;
						}
						for ($j=3; $j<=70; $j++) {	
							$objActSheet->getStyle($this->stringFromColumnIndex($k).$j)->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_TEXT);//设置单元格格式 (文本)	
							//数据有效性   start
							$objValidation = $objActSheet->getCell($this->stringFromColumnIndex($k).$j)->getDataValidation();
							$objValidation -> setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST)  
					           -> setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION)  
					           -> setAllowBlank(false)  
					           -> setShowInputMessage(true)  
					           -> setShowErrorMessage(true)  
					           -> setShowDropDown(true)  
					           -> setErrorTitle('输入的值有误')  
					           -> setError('您输入的值不在下拉框列表内.')  
					           -> setPromptTitle('--请选择--')  
					           -> setFormula1('data!$'.$this->stringFromColumnIndex($k).'$1:$'.$this->stringFromColumnIndex($k).'$'.count(explode(',',$select_value)));
					        //数据有效性  end
					    }
					} else {
						if ($select_value) {
	 						for ($j=3; $j<=70; $j++) {	
	 							$objActSheet->getStyle($this->stringFromColumnIndex($k).$j)->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_TEXT);//设置单元格格式 (文本)	
								//数据有效性   start
								$objValidation = $objActSheet->getCell($this->stringFromColumnIndex($k).$j)->getDataValidation();
								$objValidation -> setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST)  
						           -> setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION)  
						           -> setAllowBlank(false)  
						           -> setShowInputMessage(true)  
						           -> setShowErrorMessage(true)  
						           -> setShowDropDown(true)  
						           -> setErrorTitle('输入的值有误')  
						           -> setError('您输入的值不在下拉框列表内.')  
						           -> setPromptTitle('--请选择--')  
						           -> setFormula1('"'.$select_value.'"');
						        //数据有效性  end
						    }
						}
					}
				}
				$objActSheet->getStyle($this->stringFromColumnIndex($k))->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_TEXT);//设置单元格格式 (文本)
				//检查该字段若必填，加上"*"
				$field['name'] = sign_required($field['is_null'], $field['name']);
				$objActSheet->setCellValue($this->stringFromColumnIndex($k).'2', $field['name']);
				$k++;
			}
        }
        $max_row = $this->stringFromColumnIndex($k-1);
        $mark_row = $this->stringFromColumnIndex($k);
		
        $objActSheet->mergeCells('A1:'.$max_row.'1');
		$objActSheet->getStyle('A1:'.$mark_row.'1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER); //水平居中
		$objActSheet->getStyle('A1:'.$mark_row.'1')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER); //垂直居中
		$objActSheet->getRowDimension(1)->setRowHeight(28); //设置行高
		$objActSheet->getStyle('A1')->getFont()->getColor()->setARGB('FFFF0000');
		$objActSheet->getStyle('A1')->getAlignment()->setWrapText(true);

		$objActSheet->getStyle('A1')->getFont()->getColor()->setARGB('FFFF0000');
        $objActSheet->getStyle('A1')->getAlignment()->setWrapText(true);
        //设置单元格格式范围的字体、字体大小、加粗
        $objActSheet->getStyle('A1:'.$max_row.'1')->getFont()->setName("微软雅黑")->setSize(13)->getColor()->setARGB('#00000000');
        //给单元格填充背景色
        $objActSheet->getStyle('A1:'.$max_row.'1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('#ff9900');		

		switch ($types) {
			case 'crm_leads' : $types_name = '线索信息'; break;
			case 'crm_customer' : $types_name = '客户信息'; break;
			case 'crm_contacts' : $types_name = '联系人信息'; break;
			case 'crm_product' : $types_name = '产品信息'; break;
			case 'crm_bbusiness' : $types_name = '商机信息'; break;
			case 'crm_contract' : $types_name = '合同信息'; break;
			case 'crm_receivables' : $types_name = '回款信息'; break;
			case 'admin_user' : $types_name = '员工信息'; break;
			default : $types_name = '悟空软件'; break;
		}		
        $content = $types_name.'（*代表必填项；时间格式：2001-01-01 19:01:01）';
        $objActSheet->setCellValue('A1', $content);
		$objWriter = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($objPHPExcel, 'Xls');
		ob_end_clean();
		if ($save_path) {
			$objWriter->save($save_path);
		} else {
			header("Content-Type: application/vnd.ms-excel;");
			header("Content-Disposition:attachment;filename=" . $types_name . "导入模板" . date('Y-m-d', time()) . ".xls");
			header("Pragma:no-cache");
			header("Expires:0");
			$objWriter->save('php://output');
		}
	}

	/**
	 * 自定义字段模块导出csv
	 * @param $file_name 导出文件名称
	 * @param $field_list 导出字段列表
	 * @param $callback 回调函数，查询需要导出的数据
	 * @author
	 **/
	public function exportCsv($file_name, $field_list, $callback)
	{
		$fieldModel = new \app\admin\model\Field();
		ini_set('memory_limit','1024M');
		ini_set('max_execution_time','300');
	    // set_time_limit(0);

	    //调试时，先把下面这个两个header注释即可
	    header("Access-Control-Expose-Headers: Content-Disposition");  
	    header("Content-type:application/vnd.ms-excel;charset=UTF-8");  
		header("Content-Disposition:attachment;filename=" . $file_name . ".csv");

		header('Expires: 0');
		header('Cache-control: private');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Content-Description: File Transfer');
		header('Content-Encoding: UTF-8');
		// 加上bom头，防止用office打开时乱码
		echo "\xEF\xBB\xBF"; 	// UTF-8 BOM

		// 打开PHP文件句柄，php://output 表示直接输出到浏览器  
		$fp = fopen('php://output', 'a');

		// 将中文标题转换编码，否则乱码  
		foreach ($field_list as $i => $v) {    
		    $title_cell[$i] = $v['name'];    
		}
		// 将标题名称通过fputcsv写到文件句柄    
		fputcsv($fp, $title_cell);
	    // $export_data = $callback(0);
	    $round = round(1000,9999);
	    cache($file_name.$round, $callback(0));
	    $sheetContent = cache($file_name.$round)['list'];

	    $sheetCount = $callback(0)['dataCount'];
		$forCount = 1000; //每次取出1000个
		for ($i = 0; $i <= ceil(round($sheetCount/$forCount,2)); $i++){
			$_sub = array_slice($sheetContent, ($i)*$forCount, 1000);			
			foreach ($_sub as $kk => $item) {
				$rows = [];
		    	foreach ($field_list as $rule) {
		    		$rows[] = $fieldModel->getValueByFormtype($item[$rule['field']], $rule['form_type']);
		    	}
		        fputcsv($fp, $rows);				
			}
			ob_flush();//清除内存
        	flush();
		}
	    // 将已经写到csv中的数据存储变量销毁，释放内存占用  
		//$m = memory_get_usage();
		Cache::rm($file_name.$round); 
        ob_flush();
        flush();
		fclose($fp);
		exit();
	}

	/**
	 * 分批导出csv
	 *
	 * @param string 	$file_name 		下载文件名称
	 * @param string 	$temp_file		临时文件名称 （不带 .csv 后缀）
	 * @param array 	$field_list		字段表头 
	 * @param int 		$page			响应页码默认1
	 * @param callback  $callback		回调函数，返回数据
	 * 						($page, $page_size)		查询页码，查询数量
	 * @param array 	$config			设置信息
	 * 						- response_size		单次请求响应数量	默认2000
	 * 						- page_size			单次数据库查询数量	默认 500
	 * @author Ymob
	 */
	public function batchExportCsv($file_name, $temp_file, $field_list, $page, $callback, $config = [])
	{
		$queue = new Queue(self::EXPORT_QUEUE, 3);
		$export_queue_index = input('export_queue_index');

		if (!$export_queue_index) {
			if (!$export_queue_index = $queue->makeTaskId()) {
				return resultArray(['error' => $queue->error]);
			}
		} else {
			if (!$queue->setTaskId($export_queue_index)) {
				return resultArray(['error' => $queue->error]);
			}
		}
		
		// 已取消
		if ($page == -1) {
			$queue->dequeue();
			return resultArray([
				'data' => [
					'msg' => '导出已取消',
					'page' => -1
				]
			]);
		}

		// 排队中
		if (!$queue->canExec()) {
			return resultArray([
				'data' => [
					'page' => -2,
					'export_queue_index' => $export_queue_index,
					'info' => $queue->error
				]
			]);
		}

		// 没有临时文件名，代表第一次导出，生成临时文件名称，并写入表头数据
		if ($temp_file === null) {
			
			// 生成临时文件路径
			$file_path = tempFileName('csv');
			
			$fp = fopen($file_path, 'a');
			$title_cell = [];
			foreach ($field_list as $v) {    
				if ($v['form_type'] == 'customer_address') {
					$title_cell[] = $this->map_address[0];
					$title_cell[] = $this->map_address[1];
					$title_cell[] = $this->map_address[2];
				} else {
					$title_cell[] = $v['name'];    
				}
			}
			fputcsv($fp, $title_cell);
			$temp_file = \substr($file_path, strlen(TEMP_DIR));
		} else {

			$file_path = TEMP_DIR . $temp_file;
			if (!file_exists($file_path)) {
				return resultArray(['error' => '参数错误，临时文件不存在']);
			}
			$fp = fopen($file_path, 'a');
		}

		// 自定义字段模型
		$fieldModel = new \app\admin\model\Field();

		// 单次响应条数 (必须是单次查询条数的整数倍)
		$response_size = $config['response_size'] ?: 1000;

		// 单次查询条数
		$page_size = $config['page_size'] ?: 200;

		// 最多查询次数
		$max_query_count = $response_size / $page_size;

		// 总数
		$total = 0;

		for ($i = 1; $i <= $max_query_count; $i++) {
			// 两个参数，第一个参数是 page (传入 model\customer::getDataList 方法的参数),
			$data = $callback($i + ($page - 1) * ($response_size / $page_size), $page_size);
			$total = $data['dataCount'];
			foreach ($data['list'] as $val) {
				$rows = [];
		    	foreach ($field_list as $rule) {
					if ($rule['form_type'] == 'customer_address') {
						$address_arr = explode(chr(10), $val['address']);
						$rows[] = $address_arr[0] ?: '';
						$rows[] = $address_arr[1] ?: '';
						$rows[] = $address_arr[2] ?: '';
					} else {
						$rows[] = $fieldModel->getValueByFormtype($val[$rule['field']], $val['form_type']);
					}
				}
		        fputcsv($fp, $rows);				
			}
		}
		fclose($fp);
		
		// 已查询数据条数 小于 数据总数
		$done = $page * $response_size;
		if ($done < $total) {
			return resultArray([
				'data' => [
					'export_queue_index' => $export_queue_index,
					'temp_file' => $temp_file,
					// 总数
					'total' => $total,
					// 已完成
					'done' => $done,
					// 返回前端页码
					'page' => $page + 1
				]
			]);
		}

		$res = $queue->dequeue();

		// 所有数据已导入 csv 文件，返回文件流完成后删除
		return download($file_path, $file_name . '.csv', true);
	}
	
	/**
	 * 分批导入文件
	 *
	 * @param null|array|\think\File $file
	 * @param array $param
	 * @param Controller $controller
	 * @return bool
	 * 
	 * @author Ymob
	 */
	public function batchImportData($file, $param, $controller = null)
	{
		// 导入模块
		$types = $param['types'];
		if (!in_array($types, $this->types_arr)) {
			$this->error = '参数错误！';
			$queue->dequeue();
			return false;
		}

		// 采用伪队列  允许三人同时导入数据
		$queue = new Queue(self::IMPORT_QUEUE, 3);
		$import_queue_index = input('import_queue_index');

		// 队列任务ID
		if (!$import_queue_index) {
			if (!$import_queue_index = $queue->makeTaskId()) {
				$this->error = $queue->error;
				$queue->dequeue();
				return false;
			}
		} else {
			if (!$queue->setTaskId($import_queue_index)) {
				$this->error = $queue->error;
				$queue->dequeue();
				return false;
			}
		}

		// 取消导入
		if ($param['page'] == -1) {
			@unlink(UPLOAD_PATH . $param['temp_file']);
			$this->error = [
				'msg' => '导入已取消',
				'page' => -1
			];
			if ($param['error']) {
				$this->error['error_file_path'] = 'temp/' . $param['error_file'];
			} else {
				@unlink(TEMP_DIR . $param['error_file']);
			}

			$temp = $queue->cache('last_import_cache');

			(new ImportRecord())->createData([
				'type' => $types,
				'total' => $temp['total'],
				'done' => $temp['done'],
				'cover' => $temp['cover'],
				'error' => $temp['error'],
				'error_data_file_path' => $temp['error'] ? 'temp/' . $error_data_file_name : ''
			]);

			$queue->dequeue();
			return true;
		}

		if (!empty($file) || $param['temp_file']) {
			// 导入初始化  上传文件
			if (!empty($file)) {
				$save_name = $this->upload($file);
				if ($save_name === false) {
					$queue->dequeue();
					return false;
				}
			} else {
				$save_name = $param['temp_file'];
			}

			// 文件类型
			$ext = pathinfo($save_name, PATHINFO_EXTENSION);
			// 文件路径
			$save_path = UPLOAD_PATH . $save_name;

			// 队列-判断是否需要排队
			if (!$queue->canExec()) {
				$this->error = [
					'temp_file' => $save_name,
					'page' => -2,
					'import_queue_index' => $import_queue_index,
					'info' => $queue->error
				];
				return true;
			}

			// 加载类库
			vendor("phpexcel.PHPExcel");
			vendor("phpexcel.PHPExcel.Writer.Excel5");
			vendor("phpexcel.PHPExcel.Writer.Excel2007");
			vendor("phpexcel.PHPExcel.IOFactory"); 

			// 错误数据临时文件路径  错误数据开始行数
			if ($param['error_file']) {
				$error_path = TEMP_DIR . $param['error_file'];
				$error_row = $param['error'] + 3;
				$cover = $param['cover'] ?: 0;
			} else {
				// 生成临时文件名称
				$error_path = tempFileName($ext);
				// 将导入模板保存至临时路径
				$controller->excelDownload($error_path);					
				$error_row = 3;
				$cover = 0;
			}

			// 错误数据临时文件名称 相对于临时目录
			$error_data_file_name = \substr($error_path, strlen(TEMP_DIR));

			// 加载错误数据文件
			$err_PHPExcel = \PHPExcel_IOFactory::load($error_path);
			$error_sheet = $err_PHPExcel->setActiveSheetIndex(0);

			/**
			 * 添加错误数据到临时文件
			 * 
			 * @param array $data	原数据
			 * @param string $error 错误原因
			 * @return void
			 */
			$error_data_func = function ($data, $error) use ($error_sheet, &$error_row) {

				foreach ($data as $key => $val) {
					// 第一列为错误原因 所以+1
					$error_col = \PHPExcel_Cell::stringFromColumnIndex($key + 1);
					$error_sheet->setCellValue($error_col . $error_row, $val);
				}
				$error_sheet->setCellValue('A' . $error_row, $error);
				$error_sheet->getStyle('A' . $error_row)->getFont()->getColor()->setARGB('FF000000');
				$error_sheet->getStyle('A' . $error_row)->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('FFFF0000');

				$error_row++;
			};

			
			// 字段列表条件
			$fieldParam = [];
			// 导入模块
			switch ($types) {
				case 'crm_leads' : 
					$dataModel = new \app\crm\model\Leads(); 
					$db = 'crm_leads';
					$db_id = 'leads_id'; 
					break;
				case 'crm_customer' : 
					$dataModel = new \app\crm\model\Customer(); 
					$db = 'crm_customer'; 
					$db_id = 'customer_id'; 
					$fieldParam['form_type'] = ['not in', ['file','form','user','structure']]; 
					break;
				case 'crm_contacts' : 
					$dataModel = new \app\crm\model\Contacts(); 
					$db = 'crm_contacts'; 
					$db_id = 'contacts_id'; 
					break;
				case 'crm_product' : 
					$dataModel = new \app\crm\model\Product(); 
					$db = 'crm_product'; 
					$db_id = 'product_id';
					// 产品分类
					$productCategory = db('crm_product_category')->select();
					$productCategoryArr = array_column($productCategory, 'category_id', 'name');
					break;
				case 'admin_user' :
					$dataModel = new User(); 
					$db_id = 'id';
					break;
			}

			// 字段
			$fieldModel = new \app\admin\model\Field();
			$fieldParam['types'] = $types; 
			$fieldParam['action'] = 'excel'; 
			$field_list = $fieldModel->field($fieldParam);
			$field_list = array_map(function ($val) {
				if (method_exists($val, 'toArray')) {
					return $val->toArray();
				} else {
					return $val;
				}
			}, $field_list);
			$field_key_name_list = array_column($field_list, 'name', 'field');

			// 加载导入数据文件
			$objRender = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xls');
			$objRender->setReadDataOnly(true);
			$ExcelObj = $objRender->load($save_path);
			// 指定工作表
			$sheet = $ExcelObj->getSheet(0);
			// 总行数
			$max_row = $sheet->getHighestRow();
			// 最大列数
			$max_col_num = count($field_list) - 1;
			// customer_address地址类字段 占4列
			$max_col_num += 3 * array_count_values(array_column($field_list, 'form_type'))['map_address'];
			$max_col = \PHPExcel_Cell::stringFromColumnIndex($max_col_num);
			// 检测导入文件是否使用最新模板
			$header = $sheet->rangeToArray("A2:{$max_col}2")[0];
			$temp = 0;
			for ($i = 0; $i < count($field_list); $i++) {
				if (
					$header[$i] == $field_list[$i]['name']
					|| $header[$i] == '*' . $field_list[$i]['name']
				) {
					$temp++;
				// 字段为地址时，占四列
				} elseif ($field_list[$i]['form_type'] == 'map_address') {
					if (
						$header[$i] == $this->map_address[0]
						&& $header[$i + 1] == $this->map_address[1]
						&& $header[$i + 2] == $this->map_address[2]
						&& $header[$i + 3] == $this->map_address[3]
					) {
						$temp++;
					}
				} 
			}
			if ($temp !== count($field_list)) {
				$this->error = '请使用最新导入模板';
				@unlink($save_path);
				$queue->dequeue();
				return false;
			}

			// 每次导入条数
			$page_size = 100;

			// 当前页码
			$page = ((int) $param['page']) ?: 1;

			// 数据总数
			$total = $max_row - 2;

			// 总页数
			$max_page = ceil($total / $page_size);
			if ($page > $max_page) {
				// $this->error = 'page参数错误';
				// @unlink($save_path);
				// $queue->dequeue();
				// return false;
			}
			
			// 开始行  +3 跳过表头
			$start_row = ($page - 1) * $page_size + 3;
			// 结束行
			$end_row = $start_row + $page_size - 1;
			if ($end_row > $max_row) {
				$end_row = $max_row;
			}
			
			// 读取数据
			$dataList = $sheet->rangeToArray("A{$start_row}:{$max_col}{$end_row}");

			// 数据重复时的处理方式 0跳过  1覆盖
			$config = $param['config'] ?: 0;

			// 默认数据
			$default_data = [
				'create_user_id' => $param['create_user_id'],
				'owner_user_id' => $param['owner_user_id'],
				'create_time' => time(),
				'update_time' => time(),
			];
			
			// 开始导入数据
			foreach ($dataList as $val) {
				$data = [];
				$unique_where = [];
				$empty_count = 0;
				$not_null_field = [];
				$fk = 0;
				foreach ($field_list as $field) {
					if ($field['form_type'] == 'map_address') {
						$data['address'] = $address = [
							trim((string) $val[$fk]),
							trim((string) $val[$fk + 1]),
							trim((string) $val[$fk + 2]),
						];
						$data['detail_address'] = trim($val[$fk + 3]);
						$fk += 4;
						continue;
					} else {
						$temp_value = trim($val[$fk]);
					}
					

					if ($field['field'] == 'category_id' && $types == 'crm_product') {
						$data['category_id'] = $productCategoryArr[$temp_value] ?: 0;
						$data['category_str'] = $dataModel->getPidStr($productCategoryArr[$temp_value], '', 1);
					}

					// 特殊字段特殊处理
					$temp_value = $this->handleData($temp_value, $field);
					$data[$field['field']] = $temp_value;

					// 查重字段
					if ($field['is_unique'] && $temp_value) {
						$unique_where[$field['field']] = $temp_value;
					}
					if ($temp_value == '') {
						if ($field['is_null']) {
							$not_null_field[] = $field['name'];
						}
						$empty_count++;
					}
					$fk++;
				}
				if (!empty($not_null_field)) {
					$error_data_func($val, implode(', ', $not_null_field) . '不能为空');
					continue;
				}
				if ($empty_count == count($field_list)) {
					$error_data_func($val, '空行');
					continue;
				}


				$old_data_id_list = [];
				if ($unique_where) {
					$old_data_id_list = (array) $dataModel->whereOr($unique_where)->column($db_id);
				}
				
				// 数据重复时
				if ($old_data_id_list) {
					// 是否覆盖
					if ($config) {
						$data = array_merge($data, $default_data);
						$data['user_id'] = $param['create_user_id'];
						$data['update_time'] = time();
						$dataModel->startTrans();
						try {
							$up_success_count = 0;
							foreach ($old_data_id_list as $id) {
								$upRes = $dataModel->updateDataById($data, $id);
								if (!$upRes) {
									$temp_error = $dataModel->getError();
									if ($temp_error == '无权操作') {
										$temp_error = '当前导入人员对该数据无写入权限';
									}
									$error_data_func($val, $temp_error);
									$dataModel->rollback();
									break;
								}
								$up_success_count++;
							}
							// 全部更新完成
							if ($up_success_count === count($old_data_id_list)) {
								$cover++;
								$dataModel->commit();
							}
						} catch (\Exception $e) {
							$dataModel->rollback();
						}
					} else {
						// 重复字段标记
						$unique_field = [];
						foreach ($old_data_id_list as $id) {
							$old_data = $dataModel->getDataById($id);
							foreach ($unique_where as $k => $v) {
								if (trim($old_data[$k]) == $v) {
									$unique_field[] = $field_key_name_list[$k];
								}
							}
						}
						$unique_field = array_unique($unique_field);
						$error_data_func($val, implode(', ', $unique_field) . ' 重复，跳过');
					}
				} else {
					$data = array_merge($data, $default_data);
					if (!$resData = $dataModel->createData($data)) {
						$error_data_func($val, $dataModel->getError());
					}
				}
			}

			// 完成数(已导入数)
			$done = ($page - 1) * $page_size + count($dataList);
			if ($page == $max_page) {
				$done = $total;
			}

			// 错误数
			$error = $error_row - 3;

			// 错误数据文件保存
			$objWriter = \PHPExcel_IOFactory::createWriter($err_PHPExcel, 'Excel5');
			$objWriter->save($error_path);

			$this->error = [
				// 数据导入文件临时路径
				'temp_file' => $save_name,
				// 错误数据文件路径
				'error_file' => $error_data_file_name,
				// 文件总计条数
				'total' => $total,
				// 已完成条数
				'done' => $done,
				// 覆盖
				'cover' => $cover,
				// 错误数据写入行号
				'error' => $error,
				// 下次页码
				'page' => $page + 1,
				// 导入任务ID
				'import_queue_index' => $import_queue_index
			];

			$queue->cache('last_import_cache', [
				'total' => $total,
				'done' => $done,
				'cover' => $cover,
				'error' => $error
			]);

			// 执行完成
			if ($done >= $total) {
				// 出队
				$queue->dequeue();
				// 错误数据文件路径
				$this->error['error_file_path'] = 'temp/' . $error_data_file_name;
				// 删除导入文件
				@unlink($save_path);

				// 没有错误数据时，删除错误文件
				if ($error == 0) {
					@unlink($error_path);
				}

				(new ImportRecord())->createData([
					'type' => $types,
					'total' => $total,
					'done' => $done,
					'cover' => $cover,
					'error' => $error,
					'error_data_file_path' => $error ? 'temp/' . $error_data_file_name : ''
				]);
			}

	        return true;
		} else {
			$this->error = '请选择导入文件';
			$queue->dequeue();
            return false;    
		}
	}

	/**
	 * 导入数据时 读取xls表格数据
	 *
	 * @param PHPExcel_Worksheet $sheet
	 * @param integer $start	开始行
	 * @param integer $end		结束行 0时表示所有
	 * @param array $fields		字段名称
	 * @return array
	 * @author Ymob
	 */
	public function readSheet($sheet, $start = 1, $end = 0, $fields = [])
	{
		$data = [];
		for ($row = $start; $row <= $end; $row++) {
			$temp = [];
			foreach ($fields as $key => $field) {
				$col = Coordinate::stringFromColumnIndex($key);
				$temp[$field] = $sheet->getCell($col . $row);
			}
			$data[] = $temp;
		}

		return $data;
	}

	/**
	 * 上传文件导入数据文件
	 *
	 * @param [type] $file
	 * @return mixed 上传文件路径 | 上传失败错误信息
	 * @author Ymob
	 */
	public function upload($file)
	{
		$get_filesize_byte = get_upload_max_filesize_byte();
		$info = $file->validate(['size'=>$get_filesize_byte,'ext'=>'xls'])->move(FILE_PATH . 'public' . DS . 'uploads'); //验证规则
		if (!$info) {
			$this->error = $file->getError();
			return false;
		}
		$saveName = $info->getSaveName(); //保存路径	
		if (!$saveName) {
			$this->error = '文件上传失败，请重试！';
			return false;
		}
		return $saveName;
	}

	/**
	 * 自定义字段模块数据导入(默认2000行)
	 * @param $types 分类  
	 * @param $file 导入文件
	 * @param $create_user_id 创建人ID
	 * @param $owner_user_id 负责人ID
	 * @author Michael_xu
	 * @return 
	 */		
	public function importExcel($file, $param, $controller = null)
	{
		$queue = new Queue(self::IMPORT_QUEUE, 1);
		$import_queue_index = input('import_queue_index');

		if (!$import_queue_index) {
			if (!$import_queue_index = $queue->makeTaskId()) {
				$this->error = $queue->error;
				$queue->dequeue();
				return false;
			}
		} else {
			if (!$queue->setTaskId($import_queue_index)) {
				$this->error = $queue->error;
				$queue->dequeue();
				return false;
			}
		}

		if ($param['page'] == -1) {
			$queue->dequeue();
			$this->error = [
				'msg' => '导入已取消',
				'page' => -1
			];
			if ($param['error']) {
				$this->error['error_file_path'] = 'temp/' . $param['error_file'];
			}
			return true;
		}
		$config = $param['config'] ? : '';
		if (!empty($file) || $param['temp_file']) {
			$types = $param['types'];
			if (!in_array($types, $this->types_arr)) {
				$this->error = '参数错误！';
				$queue->dequeue();
				return false;				
			}

			// 导入初始化  上传文件
			if (!empty($file)) {
				$get_filesize_byte = get_upload_max_filesize_byte();
				$info = $file->validate(['size'=>$get_filesize_byte,'ext'=>'xls,xlsx,csv'])->move(UPLOAD_PATH); //验证规则
				if (!$info) {
					$this->error = $file->getError();
					$queue->dequeue();
					return false;				
				}
				$save_name = $info->getSaveName(); //保存路径	
				if (!$save_name) {
					$this->error = '文件上传失败，请重试！';
					$queue->dequeue();
					return false;
				}
			} else {
				$save_name = $param['temp_file'];
			}

			$ext = pathinfo($save_name, PATHINFO_EXTENSION); //文件后缀
			$save_path = UPLOAD_PATH . $save_name;

			if (!$queue->canExec()) {
				$this->error = [
					'temp_file' => $save_name,
					'page' => -2,
					'import_queue_index' => $import_queue_index,
					'info' => $queue->error
				];
				return true;
			}

			if ($param['error_file']) {
				$error_path = TEMP_DIR . $param['error_file'];
				$error_row = $param['error'] + 3;
			} else {
				$error_path = tempFileName($ext);
				// 生成错误数据文件
				$controller->excelDownload($error_path);					
				$error_row = 3;
			}

			vendor("phpexcel.PHPExcel");
			vendor("phpexcel.PHPExcel.Writer.Excel5");
			vendor("phpexcel.PHPExcel.Writer.Excel2007");
			vendor("phpexcel.PHPExcel.IOFactory"); 

			$err_PHPExcel = \PHPExcel_IOFactory::load($error_path);
			$sheet = $err_PHPExcel->setActiveSheetIndex(0);
			
			// 添加错误数据到临时文件
			$error_data_func = function ($data, $error) use ($sheet, &$error_row) {

				foreach ($data as $key => $val) {
					// 第一列为错误原因 所以+1
					$error_col = \PHPExcel_Cell::stringFromColumnIndex($key + 1);
					$sheet->setCellValue($error_col . $error_row, $val);
				}
				$sheet->setCellValue('A' . $error_row, $error);
				$sheet->getStyle('A' . $error_row)->getFont()->getColor()->setARGB('FF000000');
				$sheet->getStyle('A' . $error_row)->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('FFFF0000');

				$error_row++;
			};

			// 错误数据临时文件名称
			$error_data_file_name = \substr($error_path, strlen(TEMP_DIR));
            
            //实例化主文件
			set_time_limit(1800);
   			ini_set("memory_limit","256M");
        	$objPHPExcel = new Spreadsheet();

	        if ($ext =='xlsx') {
			    $objRender = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
			    // $objRender->setReadDataOnly(true);
			    $ExcelObj = $objRender->load($save_path);
			} elseif ($ext =='xls') {
			 	$objRender = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xls');
			    // $objRender->setReadDataOnly(true);
			    $ExcelObj = $objRender->load($save_path);
			} elseif ($ext=='csv') {
				$objWriter = new \PhpOffice\PhpSpreadsheet\Reader\Csv($objPHPExcel);
			    //默认输入字符集
			    $objWriter->setInputEncoding('UTF-8');
			    //默认的分隔符
			    $objWriter->setDelimiter(',');
			    //载入文件
			    $ExcelObj = $objWriter->load($save_path);
			}
			$currentSheet = $ExcelObj->getSheet(0);
			$data = $currentSheet->rangeToArray('A3:C12');

	        //查看有几个sheet
	        $sheetContent = $ExcelObj->getSheet(0)->toArray();
	        //获取总行数
	        $sheetCount = $ExcelObj->getSheet(0)->getHighestRow();
	   		
	   // 		if ($sheetCount > 2002) {
				// $this->error = '单文件一次最多导入2000条数据';
	   //  		return false;	        	
	   // 		}
			//读取表头
	        $excelHeader = $sheetContent[1];
	        unset($sheetContent[0]);
	        unset($sheetContent[1]);
	        //取出文件的内容描述信息，循环取出数据，写入数据库
			switch ($types) {
				case 'crm_leads' : 
					$dataModel = new \app\crm\model\Leads(); 
					$db = 'crm_leads';
					$db_id = 'leads_id'; 
					break;
				case 'crm_customer' : 
					$dataModel = new \app\crm\model\Customer(); 
					$db = 'crm_customer'; 
					$db_id = 'customer_id'; 
					$fieldParam['form_type'] = array('not in',['file','form','user','structure']); 
					break;
				case 'crm_contacts' : 
					$dataModel = new \app\crm\model\Contacts(); 
					$db = 'crm_contacts'; 
					$db_id = 'contacts_id'; 
					break;
				case 'crm_product' : 
					$dataModel = new \app\crm\model\Product(); 
					$db = 'crm_product'; 
					$db_id = 'product_id'; 
					break;
			}
			$contactsModel = new \app\crm\model\Contacts();        

	        //自定义字段
 			$fieldModel = new \app\admin\model\Field();
        	$fieldParam['types'] = $types; 
        	$field_list = $fieldModel->getDataList($fieldParam);
        	$fieldArr = [];
        	$uniqueField = []; //验重字段
        	foreach ($field_list as $k=>$v) {
        		$fieldArr[$v['name']]['field'] = $v['field'];
        		$fieldArr[$v['name']]['form_type'] = $v['form_type'];
        		if ($v['is_unique'] == 1) {
        			$uniqueField[] = $v['field'];
        		}
        	}
        	$field_num = count($field_list);
        	//客户导入联系人
        	if ($types == 'crm_customer') {
				$contacts_field_list = $fieldModel->getDataList(['types' => 'crm_contacts','field' => array('neq','customer_id')]);
	        	$contactsFieldArr = [];
	        	foreach ($contacts_field_list as $k=>$v) {
	        		$contactsFieldArr[$v['name']]['field'] = $v['field'];
	        		$contactsFieldArr[$v['name']]['form_type'] = $v['form_type'];
	        	}      		
        	}
	        $defaultData = []; //默认数据
	        $defaultData['create_user_id'] = $param['create_user_id'];
	        $defaultData['owner_user_id'] = $param['owner_user_id'];
	        $defaultData['create_time'] = time();
	        $defaultData['update_time'] = time();	        
	        //产品类别
	        if ($types == 'crm_product') {
	        	$productCategory = db('crm_product_category')->select();
	        	$productCategoryArr = [];
	        	foreach ($productCategory as $v) {
	        		$productCategoryArr[$v['name']] = $v['category_id'];
	        	}
			}
			// 表头行数
			$keys = 2;

			// 导入错误数据
			$errorMessage = [];
			   
			// 每次导入条数
			$forCount = 5;

			// 当前页码
			$page = $param['page'] ?: 1;

			// 数据总数
			$total = $sheetCount - $keys;

			// 总页数
			$max_page = ceil($total / $forCount);
			if ($page > $max_page) {
				$this->error = 'page参数错误';
				$queue->dequeue();
				return false;
			}

			$_sub = array_slice($sheetContent, ($page - 1) * $forCount, $forCount);			
			foreach ($_sub as $kk => $val){
				$data = '';
				$contactsData = '';
				$k = 0;        	
				$contacts_k = $field_num;        	
				$resNameIds = '';
				$keys++;
				$name = ''; //客户、线索、联系人等名称
				$contactsName = '';
				$data = $defaultData; //导入数据
				$contacts_data = $defaultData; //导入数据
				$resWhere = ''; //验重条件
				$resWhereNum = 0; //验重数
				$resContacts = false; //联系人是否有数据
				$resInfo = false; //Excel列是否有数据
				$resData = []; 
				$resContactsData = [];
				$row_error = false;
				foreach ($excelHeader as $aa => $header) {
					if (empty($header)) break; 					
					$fieldName = trim(str_replace('*','',$header));
					$info = '';
					$info = trim($val[$k]);
					if ($info) $resInfo = true;
					if ($types == 'crm_product' && $fieldName == '产品类别') {
						$data['category_id'] = $productCategoryArr[$info] ? : 0;
						$data['category_str'] = $dataModel->getPidStr($productCategoryArr[$info], '', 1);
					}
					//联系人
					if ($types == 'crm_contacts' && $fieldName == '客户名称') {
						if (!$info) {
							$error_data_func($val, '客户名称必填');		// 错误数据导出
							$errorMessage[] = '第'.$keys.'行导入错误,失败原因：客户名称必填';
							$row_error = true;
							continue;			        		
						}
						$customer_id = '';
						$customer_id = db('crm_customer')->where(['name' => $info])->value('customer_id');
						if (!$customer_id) {
							$error_data_func($val, '客户名称不存在');		// 错误数据导出
							$errorMessage[] = '第'.$keys.'行导入错误,失败原因：客户名称不存在';
							$row_error = true;
							continue;
						}
						$data['customer_id'] = $customer_id;
					}				
					if ($aa < $field_num) {
						if (empty($fieldArr[$fieldName]['field'])) continue; 
						// if ($fieldArr[$fieldName]['field'] == 'name') $name = $info;
						if (in_array($fieldArr[$fieldName]['field'], $uniqueField) && $info) {
							if ($resWhereNum > 0) $resWhere .= " OR ";
							$resWhere .= " `".$fieldArr[$fieldName]['field']."` = '".$info."'";
							$resWhereNum += 1;
						}
						$resList = [];
						$resList = $this->sheetData($k, $fieldArr, $fieldName, $info);
						$resData[] = $resList['data'];
						$k = $resList['k'];
					} else {
						//联系人
						if ($types == 'crm_customer' && $aa == (int)$contacts_k) {
							$contactsInfo = '';
							$contactsInfo = $val[$contacts_k];
							if ($contactsInfo) {
								$resContacts = true;
							}
							// if ($contactsFieldArr[$fieldName]['field'] == 'name') $contactsName = $contactsInfo;	
							$resContactsList = [];
							$resContactsList = $this->sheetData($contacts_k, $contactsFieldArr, $fieldName, $contactsInfo);
							$resContactsData[] = $resContactsList['data'];
							$contacts_k = $resContactsList['k'];	
						}
					}
				}
				if ($row_error) {
					continue;
				}
				$result = $this->changeArr($resData); //二维数组转一维数组
				$data = $result ? array_merge($data,$result) : [];
				if ($types == 'crm_customer' && $result) {
					$resultContacts = $this->changeArr($resContactsData);
					$contactsData = $resultContacts ? array_merge($contacts_data,$resultContacts) : []; //联系人
				}
				$resWhere = $resWhere ? : '';
				// $ownerWhere['owner_user_id'] = $param['owner_user_id'];
				if ($uniqueField && $resWhere) {
					$resNameIds = db($db)->where($resWhere)->where($ownerWhere)->column($db_id);
				}
				if ($resInfo == false) {
					continue;
				}
				if ($resNameIds && $data) {
					if ($config == 1 && $resNameIds) {
						$data['user_id'] = $param['create_user_id'];
						$data['update_time'] = time();
						//覆盖数据（以名称为查重规则，如存在则覆盖原数据）	
						foreach ($resNameIds as $nid) {
							$upRes = $dataModel->updateDataById($data, $nid);
							if (!$upRes) {
								$error_data_func($val, $dataModel->getError());		// 错误数据导出
								$errorMessage[] = '第'.$keys.'行导入错误,失败原因：'.$dataModel->getError();
								continue;	
							}
							if ($types == 'crm_customer' && $resContacts !== false) {
								$contactsData['customer_id'] = $upRes['customer_id'];
								if (!$contactsData['owner_user_id']) $contactsData['owner_user_id'] = $param['create_user_id'];
								if (!$resData = $contactsModel->createData($contactsData)) {
									$error_data_func($val, $contactsModel->getError());		// 错误数据导出
									$errorMessage[] = '第'.$keys.'行导入错误,失败原因：'.$contactsModel->getError();
									continue;
								}
							}								
						}
					} else {
						$error_data_func($val, '跳过');
					}				
				} else {
					if (!$resData = $dataModel->createData($data)) {
						$error_data_func($val, $dataModel->getError());		// 错误数据导出
						$errorMessage[] = '第'.$keys.'行导入错误,失败原因：'.$dataModel->getError();
						continue;
					}
					if ($types == 'crm_customer' && $resContacts !== false) {
						$contactsData['customer_id'] = $resData['customer_id'];
						if (!$contactsData['owner_user_id']) $contactsData['owner_user_id'] = $param['create_user_id'];
						if (!$resData = $contactsModel->createData($contactsData)) {
							$error_data_func($val, $contactsModel->getError());		// 错误数据导出
							$errorMessage[] = '第'.$keys.'行导入错误,失败原因：'.$contactsModel->getError();
							continue;
						}				
					}											
				}
			}
			
			// 完成数
			$done = ($page - 1) * $forCount + count($_sub);
			// 错误数
			$error = $error_row - 3;

			// 错误数据暂存
			$objWriter = \PHPExcel_IOFactory::createWriter($err_PHPExcel, 'Excel5');
			$objWriter->save($error_path);

	        $this->error = [
				'temp_file' => $save_name,
				'error_file' => $error_data_file_name,
				// 每行错误信息提示
				// 'error' => $errorMessage,
				// 文件总计条数
				'total' => $total,
				// 已完成条数
				'done' => $done,
				// 错误数据写入行号
				'error' => $error,
				// 下次页码
				'page' => $page + 1,
				'import_queue_index' => $import_queue_index
			];

			// 执行完成
			if ($done >= $total) {
				$queue->dequeue();
				$this->error['error_file_path'] = 'temp/' . $error_data_file_name;
			}

	        return true;
        } else {
			$this->error = '请选择导入文件';
			$queue->dequeue();
            return false;        	
        }
	}

	/**
	 * excel数据处理
	 * @param $k 需处理数据开始下标
	 * @author Michael_xu
	 * @return 
	 */	
	public function sheetData($k = 0, $fieldArr, $fieldName, $info)
	{
		if ($info) {
			if ($fieldArr[$fieldName]['form_type'] == 'address') {
				$address = array();
				for ($i=0; $i<4; $i++) {
					$address[] = $val[$k];
					$k++;
				}
				$data[$fieldArr[$fieldName]['field']] = implode(chr(10), $address);

				// 地址信息转地理坐标（仅处理系统初始的地址字段）
				if ($fieldArr[$fieldName]['field'] == 'address') {
					$address_arr = $address;
					if ($address_arr['3']) {
						$address_str = implode('', $address_arr);
						$ret = get_lng_lat($address_str);
						$data['lng'] = $ret['lng'] ?: 0;
						$data['lat'] = $ret['lat'] ?: 0;
					}
				}
			} elseif ($fieldArr[$fieldName]['form_type'] == 'date') {
				$data[$fieldArr[$fieldName]['field']] = $info ? date('Y-m-d',strtotime($info)) : '';
				$k++;
			} elseif ($fieldArr[$fieldName]['form_type'] == 'datetime') {
				$data[$fieldArr[$fieldName]['field']] = $info ? strtotime($info) : '';
				$k++;
			} elseif ($fieldArr[$fieldName]['form_type'] == 'customer') {
				$data[$fieldArr[$fieldName]['field']] = db('crm_customer')->where(['name' => $info])->value('customer_id') ? : '';
				$k++;
			} elseif ($fieldArr[$fieldName]['form_type'] == 'contacts') {
				$data[$fieldArr[$fieldName]['field']] = db('crm_contacts')->where(['name' => $info])->value('contacts_id') ? : '';
				$k++;
			} elseif ($fieldArr[$fieldName]['form_type'] == 'business') {
				$data[$fieldArr[$fieldName]['field']] = db('crm_business')->where(['name' => $info])->value('business_id') ? : '';
				$k++;
			} elseif ($fieldArr[$fieldName]['form_type'] == 'category') {
				$data[$fieldArr[$fieldName]['field']] = db('crm_product_category')->where(['name' => $info])->value('category_id') ? : '';
				$k++;	
			} elseif ($fieldArr[$fieldName]['form_type'] == 'business_type') {
				$data[$fieldArr[$fieldName]['field']] = db('crm_business_type')->where(['name' => $info])->value('type_id') ? : '';
				$k++;	
			} elseif ($fieldArr[$fieldName]['form_type'] == 'business_status') {
				$data[$fieldArr[$fieldName]['field']] = db('crm_business_status')->where(['name' => $info])->value('status_id') ? : '';
				$k++;		
			} else {				
				$data[$fieldArr[$fieldName]['field']] = $info ? : '';
				$k++;
			}						
		} else {
			$data[$fieldArr[$fieldName]['field']] = '';
			$k++;	
		}
		$res['data'] = $data;
		$res['k'] = $k;
		return $res;
	}

	/**
	 * 导入数据处理
	 *
	 * @param string $value
	 * @param array $field
	 * @return string
	 * @author Ymob
	 */
	public function handleData($value, $field)
	{
		switch ($field['form_type']) {
			case 'address':
				return $value;
			case 'date':
				return $value ? date('Y-m-d', strtotime($value)) : null;
			case 'datetime':
				return strtotime($value) ?: 0;
			case 'customer':
			case 'contacts':
			case 'business':
				$temp = db('crm_' . $field['form_type'])
					->where(['name' => $value])
					->value($field['form_type'] . '_id');
				return $temp ?: 0;
			case 'business_type':
				$temp = db('crm_business_type')
					->where(['name' => $value])
					->value('type_id');
				return $temp ?: 0;
			case 'business_status':
				$temp = db('crm_business_status')
					->where(['name' => $value])
					->value('status_id');
				return $temp ?: 0;
			default:
				return $value;
		}

	}

	//二维数组转一维数组
	public function changeArr($arr)
	{
		$newArr = [];
		foreach ($arr as $v) {
			if ($v && is_array($v)) {
				$newArr = array_merge($newArr,$v);
			} else {
				continue;
			}
		}
		return $newArr;
	}

	/**
	 * excel参数配置（备份）
	 * @param
	 * @author Michael_xu
	 * @return 
	 */	
	public function config()
	{
		vendor("PHPExcel.PHPExcel.PHPExcel");
		vendor("PHPExcel.PHPExcel.Writer.Excel5");
		vendor("PHPExcel.PHPExcel.Writer.Excel2007");
		vendor("PHPExcel.PHPExcel.IOFactory");	
		//实例化
		$objPHPExcel = new \phpexcel();
		$objWriter = new \PHPExcel_Writer_Excel5($objPHPExcel);
		$objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);

		$objProps = $objPHPExcel->getProperties(); // 设置excel文档的属性
		$objProps->setCreator("snowerp"); //创建人
		$objProps->setLastModifiedBy("snowerp"); //最后修改人
		$objProps->setTitle("snowerp"); //标题
		$objProps->setSubject("snowerp"); //题目
		$objProps->setDescription("snowerp"); //描述
		$objProps->setKeywords("snowerp"); //关键字
		$objProps->setCategory("snowerp"); //种类
		$objPHPExcel->setActiveSheetIndex(0); //设置当前的sheet
		$objActSheet = $objPHPExcel->getActiveSheet(); 
		$objActSheet->setTitle('snowerp'); //设置sheet的标题

		$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20); //设置单元格宽度
		$objPHPExcel->getActiveSheet()->getRowDimension($i)->setRowHeight(40); //设置单元格高度
		$objPHPExcel->getActiveSheet()->mergeCells('A18:E22'); //合并单元格
		$objPHPExcel->getActiveSheet()->unmergeCells('A28:B28'); //拆分单元格

		//设置保护cell,保护工作表
		$objPHPExcel->getActiveSheet()->getProtection()->setSheet(true); 
		$objPHPExcel->getActiveSheet()->protectCells('A3:E13', 'PHPExcel');
		//设置格式
		$objPHPExcel->getActiveSheet()->getStyle('E4')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE);
		$objPHPExcel->getActiveSheet()->duplicateStyle( $objPHPExcel->getActiveSheet()->getStyle('E4'), 'E5:E13' );
		//设置加粗
		$objPHPExcel->getActiveSheet()->getStyle('B1')->getFont()->setBold(true);
		//设置水平对齐方式（HORIZONTAL_RIGHT，HORIZONTAL_LEFT，HORIZONTAL_CENTER，HORIZONTAL_JUSTIFY）
		$objPHPExcel->getActiveSheet()->getStyle('D11')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		//设置垂直居中
		$objPHPExcel->getActiveSheet()->getStyle('A18')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
		//设置字号
		$objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setSize(10);
		//设置边框
		$objPHPExcel->getActiveSheet()->getStyle('A1:I20')->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN); 
		//设置边框颜色
		$objPHPExcel->getActiveSheet()->getStyle('D13')->getBorders()->getLeft()->getColor()->setARGB('FF993300');
		$objPHPExcel->getActiveSheet()->getStyle('D13')->getBorders()->getTop()->getColor()->setARGB('FF993300');
		$objPHPExcel->getActiveSheet()->getStyle('D13')->getBorders()->getBottom()->getColor()->setARGB('FF993300');
		$objPHPExcel->getActiveSheet()->getStyle('E13')->getBorders()->getTop()->getColor()->setARGB('FF993300');
		$objPHPExcel->getActiveSheet()->getStyle('E13')->getBorders()->getBottom()->getColor()->setARGB('FF993300');
		$objPHPExcel->getActiveSheet()->getStyle('E13')->getBorders()->getRight()->getColor()->setARGB('FF993300');

		//插入图像
		$objDrawing = new PHPExcel_Worksheet_Drawing();
		/*设置图片路径 切记：只能是本地图片*/ 
		$objDrawing->setPath('图像地址');
		/*设置图片高度*/ 
		$objDrawing->setHeight(180);//照片高度
		$objDrawing->setWidth(150); //照片宽度
		/*设置图片要插入的单元格*/
		$objDrawing->setCoordinates('E2');
		 /*设置图片所在单元格的格式*/
		$objDrawing->setOffsetX(5);
		$objDrawing->setRotation(5);
		$objDrawing->getShadow()->setVisible(true);
		$objDrawing->getShadow()->setDirection(50);
		$objDrawing->setWorksheet($objPHPExcel->getActiveSheet());

		//设置单元格背景色
		$objPHPExcel->getActiveSheet(0)->getStyle('A1')->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID);
		$objPHPExcel->getActiveSheet(0)->getStyle('A1')->getFill()->getStartColor()->setARGB('FFCAE8EA');

		//输入浏览器，导出Excel
		$savename='导出Excel示例';
		$ua = $_SERVER["HTTP_USER_AGENT"];
		$datetime = date('Y-m-d', time());        
		if (preg_match("/MSIE/", $ua)) {
		    $savename = urlencode($savename); //处理IE导出名称乱码
		} 

		// excel头参数  
		header('Content-Type: application/vnd.ms-excel');  
		header('Content-Disposition: attachment;filename="'.$savename.'.xls"');  //日期为文件名后缀  
		header('Cache-Control: max-age=0'); 
		$objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');  //excel5为xls格式，excel2007为xlsx格式  
		$objWriter->save('php://output');
	}

	/**
	 * 非自定义字段模块导出csv
	 * @param $file_name 导出文件名称
	 * @param $field_list 导出字段列表
	 * @param $callback 回调函数，查询需要导出的数据
	 * @author
	 **/
	public function dataExportCsv($file_name, $field_list, $callback)
	{
		ini_set('memory_limit','128M');
	    set_time_limit (0);

	    //调试时，先把下面这个两个header注释即可
	    header("Access-Control-Expose-Headers: Content-Disposition");  
	    header("Content-type:application/vnd.ms-excel;charset=UTF-8");  
		header("Content-Disposition:attachment;filename=" . $file_name . ".csv");

		header('Expires: 0');
		header('Cache-control: private');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Content-Description: File Transfer');
		header('Content-Encoding: UTF-8');
		// 加上bom头，防止用office打开时乱码
		echo "\xEF\xBB\xBF"; 	// UTF-8 BOM

		// 打开PHP文件句柄，php://output 表示直接输出到浏览器  
		$fp = fopen('php://output', 'a');

		// 将中文标题转换编码，否则乱码  
		foreach ($field_list as $i => $v) {    
		    $title_cell[$i] = $v['name'];    
		}
		// 将标题名称通过fputcsv写到文件句柄    
		fputcsv($fp, $title_cell);
	    $export_data = $callback(0);
		foreach ($export_data as $item) {
			$rows = [];
			foreach ($field_list as $rule) {
				$rows[] = $item[$rule['field']];
			}
	        fputcsv($fp, $rows);
	    }
	    // 将已经写到csv中的数据存储变量销毁，释放内存占用
        ob_flush();
        flush();
		fclose($fp);
		exit();
	}		
}
