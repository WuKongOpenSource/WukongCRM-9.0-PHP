<?php
// +----------------------------------------------------------------------
// | Description: 自定义字段模块数据Excel导入导出
// +----------------------------------------------------------------------
// | Author: Michael_xu | gengxiaoxu@5kcrm.com 
// +----------------------------------------------------------------------

namespace app\admin\model;

use app\admin\model\Common;
use PHPExcel_IOFactory;
use PHPExcel_Cell;
use PHPExcel;

class Excel extends Common
{
	private $types_arr = ['crm_leads','crm_customer','crm_contacts','crm_product']; //支持自定义字段的表，不包含表前缀

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
	public function excelImportDownload($field_list, $types){
		$fieldModel = new \app\admin\model\Field();	

 		//实例化主文件
        vendor("phpexcel.PHPExcel");
        vendor("phpexcel.PHPExcel.Writer.Excel5");
        vendor("phpexcel.PHPExcel.Writer.Excel2007");
        vendor("phpexcel.PHPExcel.IOFactory");         

		$objPHPExcel = new \phpexcel();
        $objWriter = new \PHPExcel_Writer_Excel5($objPHPExcel);
        $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);        

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

		$k = 0;
        foreach ($field_list as $field) {
        	$objActSheet->getColumnDimension($this->stringFromColumnIndex($k))->setWidth(20); //设置单元格宽度
			if ($field['form_type'] == 'address') {
				for ($a=0; $a<=3; $a++){
					$address = array('所在省','所在市','所在县','街道信息');
                    //如果是所在省的话
					$objActSheet->setCellValue($this->stringFromColumnIndex($k).'2', $address[$a]);
					$k++;
				}
			} else {
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
						for ($c=3; $c<=70; $c++) {		
							//数据有效性   start
							$objValidation = $objActSheet->getCell($this->stringFromColumnIndex($k).$c)->getDataValidation();
							$objValidation -> setType(\PHPExcel_Cell_DataValidation::TYPE_LIST)  
					           -> setErrorStyle(\PHPExcel_Cell_DataValidation::STYLE_INFORMATION)  
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
	 						for ($c=3; $c<=70; $c++) {		
								//数据有效性   start
								$objValidation = $objActSheet->getCell($this->stringFromColumnIndex($k).$c)->getDataValidation();
								$objValidation -> setType(\PHPExcel_Cell_DataValidation::TYPE_LIST)  
						           -> setErrorStyle(\PHPExcel_Cell_DataValidation::STYLE_INFORMATION)  
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
				//检查该字段若必填，加上"*"
				$field['name'] = sign_required($field['is_null'], $field['name']);
				$objActSheet->setCellValue($this->stringFromColumnIndex($k).'2', $field['name']);
				$k++;
			}
        }
        $max_row = $this->stringFromColumnIndex($k-1);
        $mark_row = $this->stringFromColumnIndex($k);
		
        $objActSheet->mergeCells('A1:'.$max_row.'1');
		$objActSheet->getStyle('A1:'.$mark_row.'1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER); //水平居中
		$objActSheet->getStyle('A1:'.$mark_row.'1')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER); //垂直居中
		$objActSheet->getRowDimension(1)->setRowHeight(28); //设置行高
		$objActSheet->getStyle('A1')->getFont()->getColor()->setARGB('FFFF0000');
		$objActSheet->getStyle('A1')->getAlignment()->setWrapText(true);

		$objActSheet->getStyle('A1')->getFont()->getColor()->setARGB('FFFF0000');
        $objActSheet->getStyle('A1')->getAlignment()->setWrapText(true);
        //设置单元格格式范围的字体、字体大小、加粗
        $objActSheet->getStyle('A1:'.$max_row.'1')->getFont()->setName("微软雅黑")->setSize(13)->getColor()->setARGB('#00000000');
        //给单元格填充背景色
        $objActSheet->getStyle('A1:'.$max_row.'1')->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('#ff9900');		

		switch ($types) {
			case 'crm_leads' : $types_name = '线索信息'; break;
			case 'crm_customer' : $types_name = '客户信息'; break;
			case 'crm_contacts' : $types_name = '联系人信息'; break;
			case 'crm_product' : $types_name = '产品信息'; break;
			case 'crm_bbusiness' : $types_name = '商机信息'; break;
			case 'crm_contract' : $types_name = '合同信息'; break;
			case 'crm_receivables' : $types_name = '回款信息'; break;
			default : $types_name = '悟空软件'; break;
		}		
        $content = $types_name.'（*代表必填项）';
        $objActSheet->setCellValue('A1', $content);
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		ob_end_clean();
		header("Content-Type: application/vnd.ms-excel;");
        header("Content-Disposition:attachment;filename=".$types_name."导入模板".date('Y-m-d',time()).".xls");
        header("Pragma:no-cache");
        header("Expires:0");
        $objWriter->save('php://output');
    }		

	/**
	 * 自定义字段模块导出csv
	 * @param $file_name 导出文件名称
	 * @param $field_list 导出字段列表
	 * @param $callback 回调函数，查询需要导出的数据
	 * @author
	 **/
	public function exportCsv($file_name, $field_list, callback $callback)
	{
		$fieldModel = new \app\admin\model\Field();
		ini_set('memory_limit','256M');
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

		foreach ($export_data['list'] as $item) {
	    	$rows = [];
	    	foreach ($field_list as $rule) {
	    		$rows[] = $fieldModel->getValueByFormtype($item[$rule['field']], $rule['form_type']);
	    	}
	        fputcsv($fp, $rows);
	    } 
	    // 将已经写到csv中的数据存储变量销毁，释放内存占用  
		//$m = memory_get_usage();
        ob_flush();
        flush();
		fclose($fp);
		exit();
	}
	
	/**
	 * 自定义字段模块数据导入
	 * @param $types 分类  
	 * @param $file 导入文件
	 * @param $create_user_id 创建人ID
	 * @param $owner_user_id 负责人ID
	 * @author Michael_xu
	 * @return 
	 */		
	public function importExcel($file, $param)
	{
		$get_filesize_byte = get_upload_max_filesize_byte();
		$config = $param['config'] ? : '';
		if (!empty($file)) {
			$types = $param['types'];
			if (!in_array($types, $this->types_arr)) {
				$this->error = '参数错误！';
            	return false;				
			}
			$info = $file->validate(['size'=>$get_filesize_byte,'ext'=>'xls,xlsx,csv'])->move(FILE_PATH . 'public' . DS . 'uploads'); //验证规则
			if (!$info) {
				$this->error = $file->getError();
            	return false;				
			}
			$saveName = $info->getSaveName(); //保存路径	
			$ext = $info->getExtension(); //文件后缀
			if (!$saveName) {
				$this->error = '文件上传失败，请重试！';
            	return false;
			}
			$savePath = FILE_PATH . 'public' . DS . 'uploads'. DS . $saveName;
            // require THINK_PATH.'vendor/PHPExcel/PHPExcelExcelToArrary.php';//导入excelToArray类
            // $ExcelToArrary = new ExcelToArrary();//实例化
            // //对上传的Excel数据进行处理生成编程数据,再进行数据库写入
            // $res = $ExcelToArrary->read($savePath, "UTF-8", $ext);//传参,判断office2007还是office2003
            
            //实例化主文件
			vendor("phpexcel.PHPExcel");

			// set_time_limit(300);
   			// ini_set("memory_limit","1024M");

			// $cacheMethod = \PHPExcel_CachedObjectStorageFactory::cache_to_wincache;  
			// $cacheSettings = array( 'memcacheServer'  => 'localhost',  
			//     'memcachePort'    => 11211,  
			//     'cacheTime'       => 600  
			// );  
			// \PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);

        	$objPHPExcel = new \phpexcel();

	        if ($ext =='xlsx') {
	        	$objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);
			    $objRender = \PHPExcel_IOFactory::createReader('Excel2007');
			    // $objRender->setReadDataOnly(true);
			    $ExcelObj = $objRender->load($savePath);
			} elseif ($ext =='xls') {
				$objWriter = new \PHPExcel_Writer_Excel5($objPHPExcel);
			    $objRender = \PHPExcel_IOFactory::createReader('Excel5');
			    // $objRender->setReadDataOnly(true);
			    $ExcelObj = $objRender->load($savePath);
			} elseif ($ext=='csv') {
				$objWriter = new \PHPExcel_Reader_CSV($objPHPExcel);
			    //默认输入字符集
			    $objWriter->setInputEncoding('UTF-8');
			    //默认的分隔符
			    $objWriter->setDelimiter(',');
			    //载入文件
			    $ExcelObj = $objWriter->load($savePath);
			}

	        $currentSheet = $ExcelObj->getSheet(0);
	        //查看有几个sheet
	        $sheetContent = $ExcelObj->getSheet(0)->toArray();
			//读取表头
	        $excelHeader = $sheetContent[1];
	        unset($sheetContent[0]);
	        unset($sheetContent[1]);
	        //取出文件的内容描述信息，循环取出数据，写入数据库
			switch ($types) {
				case 'crm_leads' : $dataModel = new \app\crm\model\Leads(); $db = 'crm_leads'; $db_id = 'leads_id'; break;
				case 'crm_customer' : $dataModel = new \app\crm\model\Customer(); $db = 'crm_customer'; $db_id = 'customer_id'; $fieldParam['form_type'] = array('not in',['file','form','user','structure']); break;
				case 'crm_contacts' : $dataModel = new \app\crm\model\Contacts(); $db = 'crm_contacts'; $db_id = 'contacts_id'; break;
				case 'crm_product' : $dataModel = new \app\crm\model\Product(); $db = 'crm_product'; $db_id = 'product_id'; break;
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
        		if ($v['is_unique'] == 1) $uniqueField[] = $v['field'];
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
	        if ($types == 'crm_customer') {
	        	$defaultData['deal_time'] = time();
	        }	        
	        //产品类别
	        if ($types == 'crm_product') {
	        	$productCategory = db('crm_product_category')->select();
	        	$productCategoryArr = [];
	        	foreach ($productCategory as $v) {
	        		$productCategoryArr[$v['name']] = $v['category_id'];
	        	}
	        }

	       	$keys = 2;
	       	$errorMessage = [];
	        foreach ($sheetContent as $kk => $val){
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
	            $resContacts = false; //联系人是否有数据
	            $resInfo = false; //Excel列是否有数据
				foreach ($excelHeader as $aa => $header) {
					if (empty($header)) break; 					
					$fieldName = trim(str_replace('*','',$header));
					$info = '';
					$info = $val[$k];
					if ($info) $resInfo = true;
					if ($types == 'crm_product' && $fieldName == '产品类别') {
						$data['category_id'] = $productCategoryArr[$info] ? : 0;
						$data['category_str'] = $dataModel->getPidStr($productCategoryArr[$info], '', 1);
					}
					//联系人
			        if ($types == 'crm_contacts' && $fieldName == '客户名称') {
			        	if (!$info) {
							$errorMessage[] = '第'.$keys.'行导入错误,失败原因：客户名称必填';
							continue;			        		
			        	}
			        	$customer_id = '';
			        	$customer_id = db('crm_customer')->where(['name' => $info])->value('customer_id');
			        	if (!$customer_id) {
							$errorMessage[] = '第'.$keys.'行导入错误,失败原因：客户名称不存在';
							continue;
			        	}
			        	$data['customer_id'] = $customer_id;
			        }					
					if ($aa < $field_num) {
						if (empty($fieldArr[$fieldName]['field'])) continue; 
						// if ($fieldArr[$fieldName]['field'] == 'name') $name = $info;
						if (in_array($fieldArr[$fieldName]['field'], $uniqueField) && $info) $resWhere .= " `".$fieldArr[$fieldName]['field']."` =  '".$info."' OR";

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
				$result = $this->changeArr($resData); //二维数组转一维数组
				$data = $result ? array_merge($data,$result) : [];
				if ($types == 'crm_customer' && $result) {
					$resultContacts = $this->changeArr($resContactsData);
					$contactsData = $resultContacts ? array_merge($contacts_data,$resultContacts) : []; //联系人
				}
				$resWhere = $resWhere ? substr($resWhere,0,-2) : '';
				$ownerWhere['owner_user_id'] = $param['owner_user_id'];
				if ($uniqueField && $resWhere) $resNameIds = db($db)->where($resWhere)->where($ownerWhere)->column($db_id);
				if ($resInfo == false) {
					continue;
				}
				if ($resNameIds && $data) {
					if ($config == 1 && $resNameIds) {
						$data['user_id'] = $param['create_user_id'];
						//覆盖数据（以名称为查重规则，如存在则覆盖原数据）	
						foreach ($resNameIds as $nid) {
							$upRes = $dataModel->updateDataById($data, $nid);
							if (!$upRes) {
								$errorMessage[] = '第'.$keys.'行导入错误,失败原因：'.$dataModel->getError();
								continue;	
							}
						}
					}					
				} else {
					if (!$resData = $dataModel->createData($data)) {
						$errorMessage[] = '第'.$keys.'行导入错误,失败原因：'.$dataModel->getError();
						continue;
					}
					if ($types == 'crm_customer' && $resContacts !== false) {
						$contactsData['customer_id'] = $resData['customer_id'];
						if (!$resData = $contactsModel->createData($contactsData)) {
							$errorMessage[] = '第'.$keys.'行导入错误,失败原因：'.$contactsModel->getError();
							continue;
						}						
					}						
				}		
	        }	       
	        if ($errorMessage) {
	        	$this->error = $errorMessage;
	        	return false;
	        }
	        return true;
        } else {
			$this->error = '请选择导入文件';
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
				$data[$fieldArr[$fieldName]['field']] =  implode(chr(10), $address);

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
}
