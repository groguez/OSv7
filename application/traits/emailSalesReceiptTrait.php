<?php
trait emailSalesReceiptTrait
{
	
	private function _get_shared_data()
	{
		$data = $this->cart->to_array();
		
		$modes = array('sale'=>lang('sales_sale'),'return'=>lang('sales_return'), 'estimate' => $this->config->item('user_configured_estimate_name') ? $this->config->item('user_configured_estimate_name') : lang('common_estimate'));
		$this->load->model('Employee');
		
		
		if ($this->Employee->get_logged_in_employee_info())
		{
			if (!$this->Employee->has_module_action_permission('sales', 'process_returns', $this->Employee->get_logged_in_employee_info()->person_id))
			{
				unset($modes['return']);
			}
		}

		if ($this->Employee->get_logged_in_employee_info())
		{
			if (!$this->Employee->has_module_action_permission('sales', 'can_create_estimate', $this->Employee->get_logged_in_employee_info()->person_id))
			{
				unset($modes['estimate']);
			}
		}

		if($this->config->item('customers_store_accounts')) 
		{
			$modes['store_account_payment'] = lang('common_store_account_payment');
		}
		$data['modes'] = $modes;
		
		foreach($this->view_data as $key=>$value)
		{
			$data[$key] = $value;
		}
		
		$this->load->model('Sale_types');
		$data['additional_sale_types_suspended'] = $this->Sale_types->get_all(!$this->config->item('ecommerce_platform') ? $this->config->item('ecommerce_suspended_sale_type_id') : NULL)->result_array();

		return $data;
	}
	
	function _does_discount_exists($cart)
	{
		foreach($cart as $line=>$item)
		{
			if( (isset($item->discount) && $item->discount >0 ) || (isset($item->flat_discount_amount) && $item->flat_discount_amount >0 ) || (is_array($item) && isset($item['discount_percent']) && $item['discount_percent'] >0 ) || (is_array($item) && isset($item['flat_discount_amount']) && $item['flat_discount_amount'] >0 ) )
			{
				return TRUE;
			}
		}
		
		return FALSE;
	}
	
	
	function email_receipt($sale_id, $return_response = 1) //1: echo json response , 2: return result
	{
		
		$receipt_cart = PHPPOSCartSale::get_instance_from_sale_id($sale_id);
		if ($this->config->item('sort_receipt_column'))
		{
			$receipt_cart->sort_items($this->config->item('sort_receipt_column'));
		}
		
		$data = $this->_get_shared_data();
		$data = array_merge($data,$receipt_cart->to_array());
		$this->lang->load('deliveries');
		$sale_info = $this->Sale->get_info($sale_id)->row_array();
		$data['deleted'] = $sale_info['deleted'];
		$data['is_sale_cash_payment'] = $receipt_cart->has_cash_payment();
		$tier_id = $sale_info['tier_id'];
		$tier_info = $this->Tier->get_info($tier_id);
		$data['tier'] = $tier_info->name;
		$data['register_name'] = $this->Register->get_register_name($sale_info['register_id']);
		$data['receipt_title']= $this->config->item('override_receipt_title') ? $this->config->item('override_receipt_title') : lang('sales_receipt');
		$data['sales_card_statement']= $this->config->item('override_signature_text') ? $this->config->item('override_signature_text') : lang('sales_card_statement','',array(),TRUE);
		
		$data['transaction_time']= date(get_date_format().' '.get_time_format(), strtotime($sale_info['sale_time']));
		$data['override_location_id'] = $sale_info['location_id'];
		$data['discount_exists'] = $this->_does_discount_exists($data['cart_items']);
		$customer_id=$receipt_cart->customer_id;
		$emp_info=$this->Employee->get_info($sale_info['employee_id']);
		$sold_by_employee_id=$sale_info['sold_by_employee_id'];
		$sale_emp_info=$this->Employee->get_info($sold_by_employee_id);
		
		$data['payment_type']=$sale_info['payment_type'];
		$data['amount_change']=$receipt_cart->get_amount_due_round($sale_id) * -1;
		$data['employee']=$emp_info->first_name.' '.$emp_info->last_name.($sold_by_employee_id && $sold_by_employee_id != $sale_info['employee_id'] ? '/'. $sale_emp_info->first_name.' '.$sale_emp_info->last_name: '');
		$data['employee_firstname']=$emp_info->first_name.($sold_by_employee_id && $sold_by_employee_id != $sale_info['employee_id'] ? '/'. $sale_emp_info->first_name: '');

		$data['ref_no'] = $sale_info['cc_ref_no'];
		$data['auth_code'] = $sale_info['auth_code'];
		
		$exchange_rate = $receipt_cart->get_exchange_rate() ? $receipt_cart->get_exchange_rate() : 1;
		
		$data['disable_loyalty'] = 0;
		
		
		$data['sale_id']=$this->config->item('sale_prefix').' '.$sale_id;
		$data['sale_id_raw']=$sale_id;
		$data['store_account_payment'] = FALSE;
		
		foreach($data['cart_items'] as $item)
		{
			if ($item->name == lang('common_store_account_payment'))
			{
				$data['store_account_payment'] = TRUE;
				break;
			}
		}
		
		if ($sale_info['suspended'] > 0)
		{
			if ($sale_info['suspended'] == 1)
			{
				$data['sale_type'] = ($this->config->item('user_configured_layaway_name') ? $this->config->item('user_configured_layaway_name') : lang('common_layaway'));
			}
			elseif ($sale_info['suspended'] == 2)
			{
				$data['sale_type'] = ($this->config->item('user_configured_estimate_name') ? $this->config->item('user_configured_estimate_name') : lang('common_estimate'));
			}
			else
			{
				$this->load->model('Sale_types');
				$data['sale_type'] = $this->Sale_types->get_info($sale_info['suspended'])->name;				
			}

		}
		
		if($receipt_cart->get_has_delivery())
		{
			$data['delivery_person_info'] = $receipt_cart->get_delivery_person_info();
						
			$data['delivery_info'] = $receipt_cart->get_delivery_info();
		}
		
		if (!empty($data['customer_email']))
		{
			$this->load->library('email');
			$config['mailtype'] = 'html';				
			$this->email->initialize($config);
			$this->email->from($this->Location->get_info_for_key('email') ? $this->Location->get_info_for_key('email') : $this->config->item('branding')['no_reply_email'], $this->config->item('company'));
			$this->email->to($data['customer_email']); 
			
			if($this->Location->get_info_for_key('cc_email'))
			{
				$this->email->cc($this->Location->get_info_for_key('cc_email'));
			}
			
			if($this->Location->get_info_for_key('bcc_email'))
			{
				$this->email->bcc($this->Location->get_info_for_key('bcc_email'));
			}
			$this->email->subject($sale_info['suspended'] == 2 ? ($this->config->item('user_configured_estimate_name') ? $this->config->item('user_configured_estimate_name') : lang('common_estimate')) : ($this->config->item('emailed_receipt_subject') ? $this->config->item('emailed_receipt_subject') : lang('sales_receipt')));
			
			if($this->config->item('enable_pdf_receipts')){
				$data['signature_file_id'] = $sale_info['signature_image_id'];
				$receipt_data = $this->load->view("sales/receipt_html", $data, true);
				
				if($this->config->item('receipt_download_filename_prefix')){
					$filename = $this->config->item('receipt_download_filename_prefix').'_receipt_'.$sale_id.'.pdf';
				}else{
					$filename = 'receipt_'.$sale_id.'.pdf';
				}
		    $this->load->library("m_pdf");
				$pdf_content = $this->m_pdf->generate_pdf($receipt_data);
								
				if(isset($pdf_content) && $pdf_content){
					$this->email->attach($pdf_content, 'attachment', $filename, 'application/pdf');
					$payment_link = '';
					
					if ( $this->config->item('allow_customers_to_pay_suspended_sales_online') && ($this->Location->get_info_for_key('credit_card_processor',$sale_info['location_id']) == 'coreclear2' || $this->Location->get_info_for_key('credit_card_processor',$sale_info['location_id']) == 'valor') && $receipt_cart->get_amount_due() > 0 ) 
					{
						$payment_link = '<br />'.anchor($this->Sale->get_processor_payment_link($sale_id), lang('common_pay'), array('style' => 'background: #3498db;background-image: linear-gradient(to bottom, #3498db, #2980b9);-webkit-border-radius: 28;-moz-border-radius: 28;border-radius: 28px;font-family: Arial;color: #ffffff;padding: 10px;text-decoration: none;font-weight: normal; margin-right:30px;')).'<br /><br /><br />';
					}
					
					$this->email->message($payment_link.nl2br($this->config->item('pdf_receipt_message')));
				}
			}else{
				$this->email->message($this->load->view("sales/receipt_email",$data, true));
			}
			$send_email_ret = $this->email->send();
			if($return_response == 1){
				if( !$send_email_ret ){
					echo json_encode(array(
						'status' => false,
						'message' => lang("common_email_send_failed"),
					));
				}else{
					echo json_encode(array(
						'status' => true,
						'message' => lang("common_email_send_success"),
					));
				}
			} else if( $return == 2){
				if( !$send_email_ret ){
					return array(
						'status' => false,
						'message' => lang("common_email_send_failed"),
					);
				}else{
					return array(
						'status' => true,
						'message' => lang("common_email_send_success"),
					);
				}
			}
		}
	}
}