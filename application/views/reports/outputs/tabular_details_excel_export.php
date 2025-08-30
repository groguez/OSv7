<?php
	//Clean all buffers
	while (ob_get_level())
	{
		ob_end_clean();
	}
       require_once APPPATH.'libraries/Spout/Autoloader/autoload.php';
       $format = $this->config->item('spreadsheet_format') == 'XLSX' ? 'XLSX' : 'CSV';
       $writer = ($format == 'XLSX') ? \Box\Spout\Writer\Common\Creator\WriterEntityFactory::createXLSXWriter() : \Box\Spout\Writer\Common\Creator\WriterEntityFactory::createCSVWriter();

       if (method_exists($writer,'setTempFolder'))
       {
               $writer->setTempFolder(ini_get('upload_tmp_dir') ? ini_get('upload_tmp_dir') : sys_get_temp_dir());
       }

       $writer->openToBrowser(strip_tags($title) . '.'.strtolower($format));

       $header_row = array();
       if (!empty($details_data))
       {
               foreach ($headers['details'] as $header)
               {
                       $header_row[] = strip_tags($header['data']);
               }
       }
       foreach ($headers['summary'] as $header)
       {
               $header_row[] = strip_tags($header['data']);
       }
       $writer->addRow(\Box\Spout\Writer\Common\Creator\WriterEntityFactory::createRowFromArray($header_row));

       $currency_symbol = $this->config->item('currency_symbol') ? $this->config->item('currency_symbol') : '$';
       $thousands_separator = $this->config->item('thousands_separator') ? $this->config->item('thousands_separator') : ',';

       foreach ($summary_data as $key=>$datarow)
       {
               if(isset($details_data[$key]))
               {
                       foreach($details_data[$key] as $datarow2)
                       {
                               $row = array();
                               foreach($datarow2 as $cell)
                               {
                                       $val = str_replace('<span style="white-space:nowrap;">-</span>', '-', strip_tags($cell['data'] ? $cell['data'] : ''));
                                       if (strpos($val, $currency_symbol) !== false && strpos($val,':') === false)
                                       {
                                               $th = preg_quote($thousands_separator);
                                               $cs = preg_quote($currency_symbol);
                                               $val = preg_replace("/[${th}${cs}]/", '', $val);
                                       }
                                       $hasleading_zero = substr($val,0,1) == '0';
                                       if(substr($val,0,2) == '0.')
                                       {
                                               $hasleading_zero = false;
                                       }
                                       if (is_numeric($val) && !$hasleading_zero && strlen($val) < 15)
                                       {
                                               $val = (double)$val;
                                       }
                                       $row[] = $val;
                               }

                               foreach($datarow as $cell)
                               {
                                       $val = str_replace('<span style="white-space:nowrap;">-</span>', '-', strip_tags($cell['data'] ? $cell['data'] : ''));
                                       if (strpos($val, $currency_symbol) !== false && strpos($val,':') === false)
                                       {
                                               $th = preg_quote($thousands_separator);
                                               $cs = preg_quote($currency_symbol);
                                               $val = preg_replace("/[${th}${cs}]/", '', $val);
                                       }
                                       $hasleading_zero = substr($val,0,1) == '0';
                                       if(substr($val,0,2) == '0.')
                                       {
                                               $hasleading_zero = false;
                                       }
                                       if (is_numeric($val) && !$hasleading_zero && strlen($val) < 15)
                                       {
                                               $val = (double)$val;
                                       }
                                       $row[] = $val;
                               }
                               $writer->addRow(\Box\Spout\Writer\Common\Creator\WriterEntityFactory::createRowFromArray($row));
                       }
               }
               else
               {
                       $row = array();
                       if (!empty($details_data))
                       {
                               foreach ($headers['details'] as $empty_row)
                               {
                                       $row[]=lang('common_na');
                               }
                       }
                       foreach($datarow as $cell)
                       {
                               $val = str_replace('<span style="white-space:nowrap;">-</span>', '-', strip_tags($cell['data'] ? $cell['data'] : ''));
                               if (strpos($val, $currency_symbol) !== false && strpos($val,':') === false)
                               {
                                       $th = preg_quote($thousands_separator);
                                       $cs = preg_quote($currency_symbol);
                                       $val = preg_replace("/[${th}${cs}]/", '', $val);
                               }
                               $hasleading_zero = substr($val,0,1) == '0';
                               if(substr($val,0,2) == '0.')
                               {
                                       $hasleading_zero = false;
                               }
                               if (is_numeric($val) && !$hasleading_zero && strlen($val) < 15)
                               {
                                       $val = (double)$val;
                               }
                               $row[] = $val;
                       }
                       $writer->addRow(\Box\Spout\Writer\Common\Creator\WriterEntityFactory::createRowFromArray($row));
               }
       }

       $writer->close();
       exit;
?>