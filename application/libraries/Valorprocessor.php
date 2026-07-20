<?php
require_once ("Creditcardprocessor.php");

require_once (APPPATH."libraries/mqtt/vendor/autoload.php");

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\Exceptions\MqttClientException;


class Valorprocessor extends Creditcardprocessor
{	
	public $disable_confirmation_option_for_emv_credit_card;
	
	function __construct($controller, $override_location_id = NULL)
	{
		parent::__construct($controller);
		$this->controller->load->helper('sale');

		$current_register_id = $this->controller->Employee->get_logged_in_employee_current_register_id();
		$register_info = $this->controller->Register->get_info($current_register_id);
		if (!$register_info || !$register_info->register_id)
		{
			$register_info = $this->controller->Register->get_default_register_info();
		}
		$this->epi = $register_info && property_exists($register_info,'emv_terminal_id') ? $register_info->emv_terminal_id : FALSE;
		$this->appkey = $register_info && property_exists($register_info,'valor_appkey') ? $register_info->valor_appkey : FALSE;	
		$this->appid = $this->controller->Location->get_info_for_key('valor_appid');
		$this->register_enable_tips = $register_info->enable_tips;
		$this->is_card_not_present = !$this->epi || $this->controller->session->userdata('use_manual_entry') 
		|| (isset($this->controller->cart->prompt_for_card) && $this->controller->cart->prompt_for_card);
		
		if($override_location_id > 0){
			$register_list = $this->controller->Register->get_all($override_location_id)->result();
			$register_info = $register_list[0];
			$this->epi = $register_info && property_exists($register_info,'emv_terminal_id') ? $register_info->emv_terminal_id : FALSE;
			$this->appkey = $register_info && property_exists($register_info,'valor_appkey') ? $register_info->valor_appkey : FALSE;	
			$this->appid = $this->controller->Location->get_info_for_key('valor_appid', $override_location_id);
			$this->register_enable_tips = $register_info->enable_tips;
		}

		$this->merchant_api_base_url = (!defined("ENVIRONMENT") or ENVIRONMENT == 'development') ? 'https://securelink-staging.valorpaytech.com:4430/': 'https://securelink.valorpaytech.com:4430/';		
		$this->valor_api_base_url = (!defined("ENVIRONMENT") or ENVIRONMENT == 'development') ? 'https://demo.valorpaytech.com/': 'https://online.valorpaytech.com/';		
		$this->mqtt_broker =  (!defined("ENVIRONMENT") or ENVIRONMENT == 'development') ? 'vc-staging.valorpaytech.com' : 'vc-prod.valorpaytech.com';
		$this->mqtt_port =  (!defined("ENVIRONMENT") or ENVIRONMENT == 'development') ? 28883 : 8883;
		$this->mqtt_env = (!defined("ENVIRONMENT") or ENVIRONMENT == 'development') ? 'UAT' : 'UAT';
		$this->mqtt_cert_path =  (!defined("ENVIRONMENT") or ENVIRONMENT == 'development') ? APPPATH.'libraries/valor/demo/client.crt' : APPPATH.'libraries/valor/production/client.crt';
		$this->mqtt_cert_key_path =  (!defined("ENVIRONMENT") or ENVIRONMENT == 'development') ? APPPATH.'libraries/valor/demo/client.key' : APPPATH.'libraries/valor/production/client.key';
		$this->mqtt_max_timeout =  180;
		$this->mqtt_channel_id =  (!defined("ENVIRONMENT") or ENVIRONMENT == 'development') ? 'bf54618df81f1d47ae3c5670549251f6' : 'af83319e1459266a5f1eafda6cb20be5';
		$this->disable_confirmation_option_for_emv_credit_card = $this->controller->Location->get_info_for_key('disable_confirmation_option_for_emv_credit_card') ? 1 : 0;
		
		if ($this->controller->Location->get_info_for_key('valor_channel_override'))
		{
			$this->mqtt_channel_id = $this->controller->Location->get_info_for_key('valor_channel_override');
		}
	}
	
	public function make_valor_api_request($api_endpoint, $data, $method_override=NULL)
	{
		$headers = array(  
		    'Accept: application/json',                                                                                
		    'Valor-App-ID: '.$this->appid,                                                                                
		    'Valor-App-Key: '.$this->appkey,                                                                           
		);
		
		
		$ch = curl_init($this->valor_api_base_url.$api_endpoint); 
	
		if (strtoupper($method_override) == 'DELETE')
		{
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
		}

		if (strtoupper($method_override) == 'PATCH')
		{
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
		}
		
		if (!empty($data))
		{              
		    $headers[] = 'Content-Type: application/json';                                                                                
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method_override ?? "POST");
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));                                                                        
		}
		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);   
		//Don't verify ssl...just in case a server doesn't have the ability to verify
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		$raw_response = curl_exec($ch);
    	$response = json_decode($raw_response, TRUE);
    	curl_close($ch);
		return $response;
	}
	
	public function make_valor_merchant_api_request($api_endpoint, $post_data)
	{
		$data_string = json_encode($post_data);                                                                                                                                                                                                        
		$ch = curl_init($this->merchant_api_base_url.$api_endpoint);                                                                      
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);                                                                  
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);   
		//Don't verify ssl...just in case a server doesn't have the ability to verify
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		
		$headers = array(  
		    'Content-Type: application/json',                                                                                
		    'Accept: application/json',                                                                                
		);
		
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		$raw_response = curl_exec($ch);
    	$response = json_decode($raw_response, TRUE);
    	curl_close($ch);
		return $response;
	}
	
	private function make_valor_merchant_api_request_status()
	{
		$headers = array(  
		    'Accept: application/json',                                                                                
		    'appid: '.$this->appid,                                                                                
		    'appkey: '.$this->appkey,                                                                           
		);


		$ch = curl_init($this->valor_api_base_url.'api/vc/devices/status'); 

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);   
		//Don't verify ssl...just in case a server doesn't have the ability to verify
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		$raw_response = curl_exec($ch);
    	$response = json_decode($raw_response, TRUE);
    	curl_close($ch);
		
		if ($response['code'] == '400')
		{
			return FALSE;
		}
		
		//If we do NOT get back devices assume their API is down an still try to process
		if (!isset($response['devices']))
		{
			return TRUE;
		}

		foreach($response['devices'] as $device)
		{
			if ($device['EPI'] == $this->epi)
			{
				return (boolean)$device['isOnline'];
			}
		}

		return false;
	}

	function is_terminal_online()
	{
		return $this->make_valor_merchant_api_request_status();
	}
	
	private function make_valor_connect_terminal_request($payload)
	{
	    $receivedMessage = null; // Initialize a variable to store the message received in the subscription callback.

	    try {
	        $mqtt = new \PhpMqtt\Client\MQTTClient($this->mqtt_broker, $this->mqtt_port);
	        $connectionSettings = new \PhpMqtt\Client\ConnectionSettings(
	            0,
	            false,
	            false,
	            5,
	            10,
	            10,
	            null,
	            null,
	            true,
	            false,
	            false,
	            $this->mqtt_cert_path,
	            $this->mqtt_cert_key_path,
	            null
	        );

	        $mqtt->registerLoopEventHandler(function (\PhpMqtt\Client\MqttClient $mqtt, float $elapsedTime) {
	            if ($elapsedTime >= $this->mqtt_max_timeout) {
	                $mqtt->interrupt();
	            }
	        });

	        $mqtt->connect(null, null, $connectionSettings, true);

	        $mqtt->subscribe($this->mqtt_channel_id."/".$this->mqtt_env."/VC/".$this->epi.'/APP', function ($topic, $message) use ($mqtt, &$receivedMessage) {
	            $receivedMessage = $message; // Store the received message in the variable.
	            $mqtt->interrupt(); // Interrupt the loop to stop waiting for messages once one is received.
	        }, 0);

	        $mqtt->publish(
	            // topic
	            $this->mqtt_channel_id."/".$this->mqtt_env."/VC/".$this->epi."/POS",
	            // payload
	            json_encode($payload)
	        );

	        $mqtt->loop(true);
	    } catch (MqttClientException $e) {
	        return false; // In case of an exception, you might want to handle it differently or log it.
	    }

	    return json_decode($receivedMessage, true); // Return the received message (or null if no message was received).
	}
			
	public function start_cc_processing()
	{
		$cc_amount = $this->controller->cart->get_payment_amount(lang('common_credit'));
	
		if (!$this->controller->cart->get_payment_amount(lang('common_credit')))
		{
			$cc_amount = to_currency_no_money($this->controller->cart->get_payment_amount(lang('common_debit')));
		}
		$ebt_amount = to_currency_no_money($this->controller->cart->get_payment_amount(lang('common_ebt')));
		$ebt_cash_amount = to_currency_no_money($this->controller->cart->get_payment_amount(lang('common_ebt_cash')));

		//When we charge a card on file we don't want to do manual checkout
		if ($this->is_card_not_present && !$this->controller->cart->use_cc_saved_info || ($cc_amount ==0 && $ebt_amount==0 && $ebt_cash_amount == 0))
		{
			$data['cc_amount'] = to_currency($cc_amount);
			$data['amount'] = $cc_amount;

			$this->controller->load->view('sales/valor_manual_checkout', $data);
		}
		else
		{
			$this->controller->load->view('sales/valor_start_cc_processing');
		}
	}
	
	function get_line_items()
	{
		$items = array();
		
		foreach($this->controller->cart->get_items() as $cart_item)
		{
			//Quantity is non zero and total is non zero
			if (abs(round($cart_item->quantity)) && abs($cart_item->unit_price*$cart_item->quantity-$cart_item->unit_price*$cart_item->quantity*$cart_item->discount/100) && $cart_item->name)
			{
				$item = array();
				$item['product_code'] = $this->format_product_code($cart_item->name);
				$item['quantity'] = (string)abs(round($cart_item->quantity));
				$item['total'] = (string)to_currency_no_money(abs($cart_item->unit_price*$cart_item->quantity-$cart_item->unit_price*$cart_item->quantity*$cart_item->discount/100));
			
				$items[] = $item;
			}
		}
		
		return $items;
		
	}
	
	function format_product_code($code)
	{
		//Non breaking space
		$code = str_replace("\u00A0", " ", $code);
		//https://stackoverflow.com/questions/1176904/how-to-remove-all-non-printable-characters-in-a-string
		$code = preg_replace('/[\x00-\x1F\x7F\xA0]/u', '', $code);
		$code = (string)character_limiter($code,12,'');
		
		return $code;
	}
		
	public function do_start_cc_processing( $public_sale = 0 )
	{			
		$cc_amount = $this->controller->cart->get_payment_amount(lang('common_credit'));
	
		if (!$this->controller->cart->get_payment_amount(lang('common_credit')))
		{
			$cc_amount = to_currency_no_money($this->controller->cart->get_payment_amount(lang('common_debit')));
		}
		$ebt_amount = to_currency_no_money($this->controller->cart->get_payment_amount(lang('common_ebt')));
		$ebt_cash_amount = to_currency_no_money($this->controller->cart->get_payment_amount(lang('common_ebt_cash')));
		
		$customer_id = $this->controller->cart->customer_id;
		
		$customer_name = '';
		if ($customer_id > 0)
		{
			$customer_info=$this->controller->Customer->get_info($customer_id);
			$customer_name = $customer_info->first_name.' '.$customer_info->last_name;
		}

		$prompt = $this->controller->cart->prompt_for_card;
		$charge_data = array();

		if (($this->controller->cart->save_credit_card_info) && $this->controller->cart->customer_id 
		|| ($this->controller->cart->customer_id && $this->controller->cart->use_cc_saved_info)
		|| $this->controller->cart->has_recurring_item())
		{
			if(!$customer_info->valor_vault_id){
				$vault_response = $this->make_valor_add_customer($customer_info);
				if(isset($vault_response['status']) && $vault_response['status'] != 'OK'){
					$TextResponse = $vault_response['errors'][0];
					$this->controller->_reload(array('error' => $TextResponse), false);
					return;
				}
				$customer_info->valor_vault_id = $vault_response['vault_customer_id'];
			}
		}

		//Just need token
		if( $cc_amount ==0 && $ebt_amount==0 && $ebt_cash_amount ==0)
		{
			$cc_number = $this->controller->input->post('cc_number');
			list($cc_month,$cc_year) = explode('/',$this->controller->input->post('cc_exp_date'));
			$charge_data = [
				'pan_num' => $this->controller->input->post('cc_number'),
				'expiry' => $cc_month.'/'.substr($cc_year, strlen($cc_year) - 2),
				'cardholder_name' => $customer_name
			];

			$response = NULL;

			$response = array();
			$response['STATE'] = '0';
			$response['AMOUNT'] = 0;
			$response['AUTH_RSP_TEXT'] = 'APPROVED';
			$response['CODE'] = NULL;
			$response['RRN'] = NULL;
			$response['TXN_ID'] = NULL;
			$response['TRAN_NO'] = NULL;
			$response['PARTIAL'] = 0;
			$response['MASKED_PAN'] = "XXXX" . substr($cc_number, strlen($cc_number) - 4);
			$response['ISSUER'] = @lang('sales_card_on_file');
			$response['EXPIRY_DATE'] = $this->controller->input->post('cc_exp_date');

			$EntryMethod = $prompt ? lang('sales_manual_entry') : lang('common_credit');
			$TranCode = lang('sales_card_transaction');

			$response['TRAN_TYPE'] = $TranCode;
			$response['ENTRY_MODE'] = $EntryMethod;

		} else {
			if(!$this->controller->cart->use_cc_saved_info)
			{
				if( $this->is_card_not_present || $public_sale == 1 ){
					
					if ($cc_amount <= 0)
					{
						$this->controller->_reload(array('error' => lang('sales_charging_card_failed_please_try_again')), false);
						return;
					}			
					
					list($cc_month,$cc_year) = explode('/',$this->controller->input->post('cc_exp_date'));
					$charge_data = [
						'pan_num' => $this->controller->input->post('cc_number'),
						'expiry' => $cc_month.'/'.substr($cc_year, strlen($cc_year) - 2),
						'cardholder_name' => $customer_name
					];

					$post_data = array(
						"appid" => $this->appid,
						"appkey" => $this->appkey,
						"epi" => $this->epi,
						'txn_type' => 'sale',
						"amount" => to_currency_no_money($cc_amount),
						"cardnumber" => $this->controller->input->post('cc_number'),
						'expirydate' => $cc_month.'/'.substr($cc_year, strlen($cc_year) - 2),
						'cvv' => $this->controller->input->post('cvv'),
						'shipping_country' => 'US'
					);
					$sale_response = $this->make_valor_merchant_api_request('?sale',$post_data);

					$response = array();
					$response['STATE'] = @$sale_response['error_code'] == '00' ? '0' : '-1';
					$response['AMOUNT'] = @$sale_response['partial_amount'];
					$response['AUTH_RSP_TEXT'] = @$sale_response['msg'];
					
					if ($response['STATE'] != '00')
					{
						$response['ERROR_MSG'] = @$sale_response['error_code'].' '.$sale_response['msg'];
					}
					$response['CODE'] = @$sale_response['approval_code'];
					$response['RRN'] = @$sale_response['rrn'];
					$response['TXN_ID'] = @$sale_response['txnid'];
					$response['TRAN_NO'] = @$sale_response['tran_no'];
					$response['PARTIAL'] = @(string)$sale_response['is_partial_approve'];
					$response['MASKED_PAN'] = @$sale_response['pan'];
					$response['ISSUER'] = @lang('common_credit');
					$response['EXPIRY_DATE'] = @$sale_response['expiry_date'];

					$EntryMethod = $prompt ? lang('sales_manual_entry') : lang('common_credit');
					$TranCode = lang('sales_card_transaction');

					$response['TRAN_TYPE'] = $TranCode;
					$response['ENTRY_MODE'] = $EntryMethod;
				}else{
					
						
						$tran_mode_credit_or_debit = $this->controller->cart->get_payment_amount(lang('common_debit')) > 0 ? "2" : "1";
						$payload = [
							"TRAN_MODE" => $tran_mode_credit_or_debit,
							"PAPER_RECEIPT" => "0",
						];
						
						if (!$this->disable_confirmation_option_for_emv_credit_card)
						{
							$payload["lineItems"] = $this->get_line_items();
							
						}
						if ($this->controller->config->item('enable_tips') || $this->register_enable_tips)
						{
							$payload['TIP_ENTRY'] = "1";					
						}
						
						
						if (!is_sale_integrated_ebt_sale($this->controller->cart))
						{
							$payload['AMOUNT'] = (string)((int)(round(abs($cc_amount)*100)));
							$payload['TRAN_CODE'] = $cc_amount > 0 ? "1" : "5";
						}
						else
						{
							
							if ($ebt_amount!=0)
							{
								$payload['TRAN_MODE'] = "3";
								$payload['AMOUNT'] = (string)((int)(round(abs($ebt_amount)*100)));
								$payload['TRAN_CODE'] = $ebt_amount > 0 ? "1" : ($this->controller->cart->ebt_voucher_no ?  "6" : "5");
							}
							if($ebt_cash_amount != 0)
							{
								$payload['TRAN_MODE'] = $ebt_cash_amount > 0 ? "4" : "3";
								$payload['AMOUNT'] = (string)((int)(round(abs($ebt_cash_amount)*100)));
								$payload['TRAN_CODE'] = $ebt_cash_amount > 0 ? "1" : "5";
							}
						}
						
						if (!$this->is_terminal_online())
						{
							$this->controller->_reload(array('error' => lang('sales_credit_card_terminal_offline')), false);
							return;
						}
						else
						{
							$response = $this->make_valor_connect_terminal_request($payload);
						}								
				}
			}
			elseif($customer_info->cc_token)
			{
				if ($cc_amount <= 0)
				{
					$this->controller->_reload(array('error' => lang('sales_charging_card_failed_please_try_again')), false);
					return;
				}			
				
				$payment_profiles = $this->make_valor_api_request('api/valor-vault/getpaymentprofile/'.$customer_info->valor_vault_id,array());
				
				$the_vault_token= '';
				foreach($payment_profiles['data'] as $pp)
				{
					if ($pp['payment_id'] == $customer_info->cc_token)
					{
						$the_vault_token = $pp['token'];
						break;
					}
				}
				$post_data = array(
					"appid" => $this->appid,
					"appkey" => $this->appkey,
					"epi" => $this->epi,
					"amount" => to_currency_no_money($cc_amount),
					'txn_type' => 'sale',
					'surchargeIndicator' => $this->controller->cart->get_fee_amount() == 0 ? 0 : 1,
					'shipping_country' => 'US',
					'token' => $the_vault_token,
				);
				
				$token_response = $this->make_valor_merchant_api_request('?pagesale',$post_data);			
				//Normalize response so it can be processed same way as terminal transaction
				$response = array();
				$response['STATE'] = @$token_response['error_code'] == '00' ? '0' : '-1';
				$response['AMOUNT'] = @$token_response['partial_amount'];
				$response['AUTH_RSP_TEXT'] = @$token_response['msg'];
				
				if ($response['STATE'] != '00')
				{
					$response['ERROR_MSG'] = @$token_response['error_code'].' '.$token_response['msg'];
				}
				$response['CODE'] = @$token_response['approval_code'];
				$response['RRN'] = @$token_response['rrn'];
				$response['TXN_ID'] = @$token_response['txnid'];
				$response['TRAN_NO'] = @$token_response['tran_no'];
				$response['PARTIAL'] = @(string)$token_response['is_partial_approve'];
				$response['MASKED_PAN'] = @$token_response['pan'];
				$response['TRAN_TYPE'] = @'TOKEN';
				$response['ISSUER'] = @lang('sales_card_on_file');
				$response['EXPIRY_DATE'] = @$token_response['expiry_date'];
			}

		}

		$TextResponse = @$response['AUTH_RSP_TEXT'];
		if ($response['STATE'] !== '0')
		{
			$TextResponse = @$response['ERROR_MSG'];
		}

		if($public_sale == 1){
			if ( isset($response['STATE']) && $response['STATE'] === '-1' )
			{
				$this->controller->session->set_userdata('card_error', $TextResponse);
				redirect($_SERVER['HTTP_REFERER']);
			}
		}
		
		if (isset($response['STATE']) && $response['STATE'] === '0') //Approved
		{
			$CardType = @$response['ISSUER'];
			$EntryMethod = @$response['ENTRY_MODE'];

			$AID = @$response['AID'];
			$TVR = @$response['TVR'];
			$IAD = @$response['IAD'];
			$TSI = @$response['TSI'];
			$ApplicationLabel = '';
						
			
			$MerchantID =  $this->appid;
			$AcctNo = $response['MASKED_PAN'];
			$TranCode = $response['TRAN_TYPE'];
			$AuthCode = $response['CODE'];
			$RefNo = $response['TXN_ID'];
			$TranNo = $response['TRAN_NO'];
			
			$actual_pos_charge = (float)$cc_amount;
			$actual_pos_charge = abs($actual_pos_charge);
			
			$calculated_final_charge = (float)($response['AMOUNT'] + $response['SURCHARGE_AMOUNT'] - $response['CASH_DISCOUNT'] ?? 0)/100;
			$calculated_final_charge = abs($calculated_final_charge);
			//Discount
			if ($actual_pos_charge > $calculated_final_charge)
			{
				$surcharge_amount = 0;
				$discount_amount = $calculated_final_charge - $actual_pos_charge;
				
			}
			elseif($actual_pos_charge < $calculated_final_charge) //Surcharge
			{
				$surcharge_amount = $calculated_final_charge - $actual_pos_charge;
				$discount_amount = 0;
			}
			else
			{
				$surcharge_amount = 0;
				$discount_amount = 0;
			}
			if ($surcharge_amount)
			{
				$fee_item_id = $this->controller->Item->create_or_update_fee_item();
				$fee_item = new PHPPOSCartItemSale(array('cart' => $this->controller->cart,'scan' => $fee_item_id.'|FORCE_ITEM_ID|','cost_price' => 0 ,'unit_price' =>round($surcharge_amount,2) ,'description' => $TranCode.' '.lang('common_fee'),'quantity' => 1));
				if ($fee_item_in_cart = $this->controller->cart->find_similiar_item($fee_item))
				{
					$current_surcharge = $fee_item_in_cart->unit_price;
					$fee_item->unit_price+=$current_surcharge;
					$this->controller->cart->delete_item($this->controller->cart->get_item_index($fee_item_in_cart));				
				}
				$this->controller->cart->add_item($fee_item);
			
			
				if (!empty($this->controller->cart->get_payment_ids(lang('common_credit'))))
				{
					$current_payment_index = $this->controller->cart->get_payment_ids(lang('common_credit'))[0];
				}
				else
				{
					$current_payment_index = $this->controller->cart->get_payment_ids(lang('common_debit'))[0];
				}
				$current_payment = $this->controller->cart->get_payments()[$current_payment_index];
				$this->controller->cart->edit_payment($this->controller->cart->get_payment_ids($current_payment_index)[0],array('payment_amount' => $current_payment->payment_amount+round($surcharge_amount,2)));
			}
			elseif($discount_amount)
			{
				$discount_item_id = $this->controller->Item->create_or_update_flat_discount_item();
				$discount_item = new PHPPOSCartItemSale(array('cart' => $this->controller->cart,'scan' => $discount_item_id.'|FORCE_ITEM_ID|','cost_price' => 0 ,'unit_price' =>round($discount_amount,2) ,'description' => $TranCode.' '.lang('common_discount'),'quantity' => 1));
				if ($discount_item_in_cart = $this->controller->cart->find_similiar_item($discount_item))
				{
					$current_discount = $discount_item_in_cart->unit_price;
					$discount_item->unit_price+=$current_discount;
					$this->controller->cart->delete_item($this->controller->cart->get_item_index($discount_item_in_cart));				
				}
				$this->controller->cart->add_item($discount_item);
			
			
				if (!empty($this->controller->cart->get_payment_ids(lang('common_credit'))))
				{
					$current_payment_index = $this->controller->cart->get_payment_ids(lang('common_credit'))[0];
				}
				else
				{
					$current_payment_index = $this->controller->cart->get_payment_ids(lang('common_debit'))[0];
				}
				$current_payment = $this->controller->cart->get_payments()[$current_payment_index];
				$this->controller->cart->edit_payment($this->controller->cart->get_payment_ids($current_payment_index)[0],array('payment_amount' => $current_payment->payment_amount+round($discount_amount,2)));
				
			}
		   
			$tip_amount = round((isset($response['TIP_AMOUNT']) ? $response['TIP_AMOUNT'] : '0')/100,2);
			$Purchase = $calculated_final_charge + $tip_amount;		
			$Authorize = $Purchase;
			
			
			$RecordNo = $response['RRN'];
			$CCExpire = $response['EXPIRY_DATE'];
		    $signature = '';
			
			$this->controller->session->set_userdata('ref_no', $RefNo);
			$this->controller->session->set_userdata('tran_no', $TranNo);
			$this->controller->session->set_userdata('tip_amount', $tip_amount);
			$this->controller->session->set_userdata('auth_code', $AuthCode);
			$this->controller->session->set_userdata('cc_token', $RecordNo);
			$this->controller->session->set_userdata('entry_method', $EntryMethod);
			
			$this->controller->session->set_userdata('aid', $AID);
			$this->controller->session->set_userdata('tvr', $TVR);
			$this->controller->session->set_userdata('iad', $IAD);
			$this->controller->session->set_userdata('tsi', $TSI);
			
			$this->controller->session->set_userdata('application_label', $ApplicationLabel);
			$this->controller->session->set_userdata('tran_type', $TranCode);
			$this->controller->session->set_userdata('text_response', $TextResponse);

			$this->controller->session->set_userdata('cc_signature', $signature);
			$this->controller->session->set_userdata('cash_discount', isset($response['CASH_DISCOUNT']) ? $response['CASH_DISCOUNT'] : 0);
			//Payment covers purchase amount
			if ((int)(round(abs($Authorize)*100)) == (int)(round(abs($Purchase)*100)))
			{
				$this->controller->session->set_userdata('masked_account', $AcctNo);
				$this->controller->session->set_userdata('card_issuer', $CardType);
						
				$info=$this->controller->Customer->get_info($this->controller->cart->customer_id);
				
				//We want to save/update card when we have a customer AND they have chosen to save OR we have a customer and they are using a saved card
				if (($this->controller->cart->save_credit_card_info) && $this->controller->cart->customer_id 
				|| ($this->controller->cart->customer_id && $this->controller->cart->use_cc_saved_info)
				|| ($this->controller->cart->has_recurring_item()))
				{
					
					$payment_id = '';
					$valor_vault_id = '';

					$options = array(
						'renew_token' => NULL,
						'api' => $cc_amount == 0 ? 'addpaymentprofile' : 'addpaymentprofiletxn',
						'RefNo' => $RefNo
					);
					$payment_response = $this->make_payment_profile($customer_info, $charge_data, $options);
					if($payment_response['state'] == 1){
						$payment_id = $payment_response['payment_id'];
						$valor_vault_id = $payment_response['valor_vault_id'];
						$this->controller->session->set_userdata('cc_token', $payment_id);
					}else{
						$TextResponse = $payment_response['message'];
						$this->controller->_reload(array('error' => $TextResponse), false);
						return;
					}

					$person_info = array('person_id' => $this->controller->cart->customer_id);
					$customer_info = array('valor_vault_id' => $valor_vault_id, 'cc_token' => $payment_id, 'cc_expire' => $CCExpire, 'cc_ref_no' => $RefNo, 'cc_preview' => $AcctNo);
					$this->controller->Customer->save_customer($person_info,$customer_info,$this->controller->cart->customer_id);
					
				}
				
				//If the sale payments cover the total, redirect to complete (receipt)
				if ($this->controller->_payments_cover_total())
				{
					$this->controller->session->set_userdata('CC_SUCCESS', TRUE);
					
					if ($public_sale)
					{
						$this->controller->session->set_userdata('paid_online', '1');
					}
					
					if($RefNo)
						$this->log_charge($RefNo,$cc_amount > 0 ? $Purchase : $Purchase*-1, true);

					if($public_sale == 1){
						$this->controller->cart->suspended = 0;
						$this->controller->Sale->save($this->controller->cart);
						
						// Send email alert for layaway payment
						$this->controller->Sale->send_layaway_payment_email_alert($this->controller->cart->sale_id, $Purchase, 0);
						
						$signature = $this->controller->Sale->get_receipt_signature($this->controller->cart->sale_id);

						require_once (APPPATH."libraries/hashids/vendor/autoload.php");
						$hashids = new Hashids\Hashids($this->controller->db->database);
						$sms_id = $hashids->encode($this->controller->cart->sale_id);

						redirect(site_url('r/' . $sms_id . '?signature=' . $signature));
					}

					redirect(site_url('sales/complete'));
				}
				else //Change payment type to Partial Credit Card and show sales interface
				{							
				
					$credit_card_amount = to_currency_no_money($this->controller->cart->get_payment_amount(lang('common_credit')));
					$partial_payment_type = lang('common_credit');
					if (!$this->controller->cart->get_payment_amount(lang('common_credit')))
					{
						$partial_payment_type = lang('common_debit');
					}
					
					if (!$this->controller->cart->get_payment_amount(lang('common_credit')))
					{
						$credit_card_amount = to_currency_no_money($this->controller->cart->get_payment_amount(lang('common_debit')));
					}
					
					$partial_transaction = array(
						'AuthCode' => $AuthCode,
						'Purchase' => $Purchase,
						'RefNo' => $RefNo,
						'RecordNo' => $RecordNo,
					);
														
					$this->controller->cart->delete_payment($this->controller->cart->get_payment_ids($partial_payment_type));												
				
					@$this->controller->cart->add_payment(new PHPPOSCartPaymentSale(array(
						'payment_type' => lang('sales_partial_credit'),
						'payment_amount' => $credit_card_amount,
						'payment_date' => date('Y-m-d H:i:s'),
						'truncated_card' => $AcctNo,
						'card_issuer' => $CardType,
						'auth_code' => $AuthCode,
						'ref_no' => $RefNo,
						'tran_no' => $TranNo,
						'cc_token' => $RecordNo,
						'entry_method' => $EntryMethod,
						'aid' => $AID,
						'tvr' => $TVR,
						'iad' => $IAD,
						'tsi' => $TSI,
						'tran_type' => $TranCode,
						'application_label' => $ApplicationLabel,
					)));
					
					$this->controller->cart->add_partial_transaction($partial_transaction);
					$this->controller->cart->save();
					$this->log_charge($RefNo,$credit_card_amount, false);
					$this->controller->_reload(array('warning' => lang('sales_credit_card_partially_charged_please_complete_sale_with_another_payment_method')), false);			
				}
			}
			elseif(abs($Authorize) < abs($Purchase))
			{
					$partial_transaction = array(
						'AuthCode' => $AuthCode,
						'Purchase' => $Authorize,
						'RefNo' => $RefNo,
						'RecordNo' => $RecordNo,
					);
					
					$partial_payment_type = lang('common_credit');
					if (!$this->controller->cart->get_payment_amount(lang('common_credit')))
					{
						$partial_payment_type = lang('common_debit');
					}
					
					$this->controller->cart->delete_payment($this->controller->cart->get_payment_ids($partial_payment_type));
					
					@$this->controller->cart->add_payment(new PHPPOSCartPaymentSale(array(
						'payment_type' => lang('sales_partial_credit'),
						'payment_amount' => $Authorize,
						'payment_date' => date('Y-m-d H:i:s'),
						'truncated_card' => $AcctNo,
						'card_issuer' => $CardType,
						'auth_code' => $AuthCode,
						'ref_no' => $RefNo,
						'tran_no' => $TranNo,
						'cc_token' => $RecordNo,
						'entry_method' => $EntryMethod,
						'aid' => $AID,
						'tvr' => $TVR,
						'iad' => $IAD,
						'tsi' => $TSI,
						'tran_type' => $TranCode,
						'application_label' => $ApplicationLabel,
					)));
					
					$this->controller->cart->add_partial_transaction($partial_transaction);
					$this->controller->cart->save();
					$this->log_charge($RefNo,$Authorize, false);
					$this->controller->_reload(array('warning' => lang('sales_credit_card_partially_charged_please_complete_sale_with_another_payment_method')), false);	
				}
		}
		else
		{
			
			//If we are using saved token and have a failed response remove token from customer
			if ($this->controller->cart->use_cc_saved_info && $this->controller->cart->customer_id)
			{
				//If we have failed, remove cc token and cc preview
				$person_info = array('person_id' => $this->controller->cart->customer_id);
				$customer_info = array('cc_token' => NULL, 'cc_expire' => NULL, 'cc_ref_no' => NULL, 'cc_preview' => NULL, 'card_issuer' => NULL);
				
				if (!$this->controller->config->item('do_not_delete_saved_card_after_failure'))
				{
					$this->controller->Customer->save_customer($person_info,$customer_info,$this->controller->cart->customer_id);
				}
								
				//Clear cc token for using saved cc info
				$this->controller->cart->use_cc_saved_info = NULL;
				$this->controller->cart->save();
			}
			
			$this->controller->_reload(array('error' => $TextResponse), false);
			
		}
	}
	
	public function finish_cc_processing()
	{
		//No need for this method as it is handled by start method all at once
		return TRUE;
	}
	
	public function cancel_cc_processing()
	{
		$this->controller->cart->delete_payment($this->controller->cart->get_payment_ids(lang('common_credit')));
		$this->controller->cart->delete_payment($this->controller->cart->get_payment_ids(lang('common_debit')));
		$this->controller->cart->save();
		$this->controller->_reload(array('error' => lang('sales_cc_processing_cancelled')), false);
	}
	
	
	private function void_sale_payment($payment_amount,$auth_code,$ref_no,$token,$acq_ref_data,$process_data,$tip_amount = 0)
	{
		$post_data = array(
			"appid" => $this->appid,
			"appkey" => $this->appkey,
			"epi" => $this->epi,
			'txn_type' => 'void',
			'ref_txn_id' => $ref_no,
			'surchargeIndicator' => $this->controller->cart->get_fee_amount() == 0 ? 0 : 1,
		);
		//try void first
		$response = $this->make_valor_merchant_api_request('?void',$post_data);
		if ($response['error_code'] != '00')
		{
			$post_data['amount'] = to_currency_no_money($payment_amount+$tip_amount);
			$post_data['sale_refund'] = "1";
			$post_data['txn_type'] = 'refund';
			$response = $this->make_valor_merchant_api_request('?refund',$post_data);
			return $response['error_code'] == '00';
		}
		return TRUE;
		
	}
	
	private function void_return_payment($payment_amount,$auth_code,$ref_no,$token,$acq_ref_data,$process_data)
	{
		$post_data = array(
			"appid" => $this->appid,
			"appkey" => $this->appkey,
			"epi" => $this->epi,
			'txn_type' => 'void',
			'ref_txn_id' => $ref_no,
			'surchargeIndicator' => $this->controller->cart->get_fee_amount() == 0 ? 0 : 1,
		);
		
		return  $this->make_valor_merchant_api_request('?void',$post_data);
	}
	
	public function void_partial_transactions()
	{
		$void_success = true;
		
		if ($partial_transactions = $this->controller->cart->get_partial_transactions())
		{
			for ($k = 0;$k<count($partial_transactions);$k++)
			{
				$partial_transaction = $partial_transactions[$k];
				@$void_success = $this->void_sale_payment(to_currency_no_money($partial_transaction['Purchase']),$partial_transaction['AuthCode'],$partial_transaction['RefNo'],$partial_transaction['RecordNo'],$partial_transaction['AcqRefData'],$partial_transaction['ProcessData']);
			}
		}
		return $void_success;
	}	
	public function void_sale($sale_id)
	{
		if ($this->controller->Sale->can_void_cc_sale($sale_id))
		{
			$void_success = true;
			
			$payments = $this->_get_cc_payments_for_sale($sale_id);
			
			foreach($payments as $payment)
			{				
				@$void_success = $this->void_sale_payment($payment['payment_amount'], $payment['auth_code'], $payment['ref_no'], $payment['cc_token'],$payment['acq_ref_data'], $payment['process_data']);
			}
			
			return $void_success;
		}
		
		return FALSE;
	}
	
	public function void_return($sale_id)
	{
		if ($this->controller->Sale->can_void_cc_return($sale_id))
		{
			$void_success = true;
			
			$payments = $this->_get_cc_payments_for_sale($sale_id);
			
			foreach($payments as $payment)
			{
				$void_success = $this->void_return_payment($payment['payment_amount'], $payment['auth_code'], $payment['ref_no'], $payment['cc_token'],$payment['acq_ref_data'], $payment['process_data']);
			}
			
			return $void_success;
		}
		
		return FALSE;	
	}		
	
	//Handled during transaction
	public function tip($sale_id,$tip_amount)
	{
		return FALSE;
	}

	//invoice
	public function do_start_cc_processing_without_login($cc_amount, $total, $id)
	{
		$cc_amount 			= to_currency_no_money($cc_amount);
		$total 				= to_currency_no_money($total);
		$id 				= $id;
		$invoice_detail 	= $this->controller->Invoice->get_invoice_detail($id);
		$customer_detail 	= $this->controller->Customer->get_info($invoice_detail->customer_id);

		$remaing_balance = $total - $cc_amount;

		$this->controller->load->helper('sale');

		$partial 		= false;
		$full_payment 	= false;
		if ($cc_amount == $total) 
		{
			$payment_type = lang('common_credit');
			$full_payment = true;
		} elseif($cc_amount < $total) {
			$payment_type = lang('sales_partial_credit');
			$partial = true;
		}

		list($cc_month,$cc_year) = explode('/',$this->controller->input->post('cc_exp_date'));
		
		$post_data = array(
			"appid" => $this->appid,
			"appkey" => $this->appkey,
			"epi" => $this->epi,
			'txn_type' => 'sale',
			"amount" => to_currency_no_money($cc_amount),
			"cardnumber" => $this->controller->input->post('cc_number'),
			'expirydate' => $cc_month.'/'.substr($cc_year, strlen($cc_year) - 2),
			'cvv' => $this->controller->input->post('cvv'),
			'shipping_country' => 'US'
		);
		$sale_response = $this->make_valor_merchant_api_request('?sale',$post_data);

		$response = array();
		$response['STATE'] = @$sale_response['error_code'] == '00' ? '0' : '-1';
		$response['AMOUNT'] = @$sale_response['partial_amount'];
		$response['AUTH_RSP_TEXT'] = @$sale_response['msg'];
		
		if ($response['STATE'] != '00')
		{
			$response['ERROR_MSG'] = @$sale_response['error_code'].' '.$sale_response['msg'];
		}
		$response['CODE'] = @$sale_response['approval_code'];
		$response['RRN'] = @$sale_response['rrn'];
		$response['TXN_ID'] = @$sale_response['txnid'];
		$response['TRAN_NO'] = @$sale_response['tran_no'];
		$response['PARTIAL'] = @(string)$sale_response['is_partial_approve'];
		$response['MASKED_PAN'] = @$sale_response['pan'];
		$response['ISSUER'] = @lang('sales_card_on_file');
		$response['EXPIRY_DATE'] = @$sale_response['expiry_date'];

		$EntryMethod = lang('common_credit');
		$TranCode = lang('sales_card_transaction');

		$response['TRAN_TYPE'] = $TranCode;
		$response['ENTRY_MODE'] = $EntryMethod;

		$TextResponse = @$response['AUTH_RSP_TEXT'];
		if ($response['STATE'] !== '0')
		{
			$TextResponse = @$response['ERROR_MSG'];
		}

		if ( isset($response['STATE']) && $response['STATE'] === '-1' )
		{
			$this->controller->session->set_userdata('card_error', $TextResponse);
			redirect($_SERVER['HTTP_REFERER']);
		}

		if (isset($response['STATE']) && $response['STATE'] === '0') //Approved
		{
			$CardType = @$response['ISSUER'];
			$EntryMethod = @$response['ENTRY_MODE'];

		   	$MerchantID 	=  '';
		   	$truncated_card = $response['MASKED_PAN'];
		   	$card_issuer 	= $response['TRAN_TYPE'];
		   	$tran_type 		= 'Card Transaction';
		   	$auth_code 		= $response['CODE'];
		   	$ref_no 		= $response['TXN_ID'];
		   	$entry_method 	= $EntryMethod;
		   	$amount 		= $cc_amount;

			$data = array(
				'invoice_id' 		=> $id,
			    'payment_type' 		=> $payment_type,
				'payment_amount' 	=> $amount,
				'payment_date' 		=> date('Y-m-d H:i:s'),
				'truncated_card' 	=> $truncated_card,
				'card_issuer' 		=> $card_issuer,
				'auth_code' 		=> $auth_code,
				'ref_no' 			=> $ref_no,
				'cc_token' 			=> '',
				'entry_method' 		=> $entry_method,
				'tran_type' 		=> $tran_type,
				'application_label' => '',
				'paid_online' => 1,
			);

			$invoice_info = $this->controller->Invoice->get_info('customer',$id);
			$store_account_payment_sale_id = $this->controller->Invoice->create_store_account_transaction('customer', $invoice_info, $data, '1');
			$invoice_payment_id = $this->controller->Invoice->add_payment('customer',$id,$data);

			//Update balance as we made a payment
			$invoice_data = array('balance' => $invoice_info->balance - $amount,'last_paid' => date('Y-m-d'));
			$this->controller->Invoice->save('customer', $invoice_data, $id, $store_account_payment_sale_id > 0 ? true : false);
			
			$encrypt = do_encrypt($invoice_payment_id,$this->controller->Appconfig->get_secure_key());

			if (isset($customer_detail->email)) {
				$this->send_invoice_email($cc_amount, $id);
			}

			// Send email alert for invoice payment
			$this->controller->Invoice->send_payment_email_alert('customer', $id, $amount, $invoice_data['balance']);
			
			redirect('payment_success'.'/'.$encrypt);
		}
	}

	//suspended sales
	public function do_start_cc_processing_without_login_suspended_sales($id)
	{
		$this->controller->load->helper('sale');

		$receipt_cart = PHPPOSCartSale::get_instance_from_sale_id($id, NULL, FALSE, TRUE, TRUE);
		$due_amount = $receipt_cart->get_amount_due();

		if($due_amount > 0){
			$receipt_cart->add_payment(new PHPPOSCartPaymentSale(array('payment_type' => lang('common_credit'), 'payment_amount' => $due_amount, 'paid_online' => 1)));

			$this->controller->cart = $receipt_cart;
			$this->do_start_cc_processing(1);
		}else{
			redirect($_SERVER['HTTP_REFERER']);
		}
	}

	public function send_invoice_email($cc_amount, $id)
	{

		$invoice_detail 	= $this->controller->Invoice->get_invoice_detail($id);
		$customer_detail 	= $this->controller->Customer->get_info($invoice_detail->customer_id);

		if (isset($customer_detail->email)) {
			$customer_email = $customer_detail->email;
			$subject 		= 'Invoice Payment Confirmation';
			$company 		= $this->controller->config->item('company');
			$message 		= sprintf(lang('invoices_confirm_greeting'), $customer_detail->full_name, $company) . '<br><br>' . lang('invoices_confirm_content'). '<br><br>' . sprintf(lang('invoices_confirm_amount'), $cc_amount);
			$this->controller->Common->send_email($customer_email,$subject,$message);
		}
	}
	
	public function make_valor_add_customer($customer_info){
		$post_data = array();

		$post_data['customer_name'] = $customer_info->first_name.' '.$customer_info->last_name;
		if ($customer_info->company_name)
		{
			$post_data['company_name'] = $customer_info->company_name;
		}

		if ($customer_info->phone_number)
		{
			$post_data['customer_phone'] = $customer_info->phone_number;
		}

		if ($customer_info->email)
		{
			$post_data['customer_email'] = $customer_info->email;
		}
		
		$vault_response = $this->make_valor_api_request('api/valor-vault/addcustomer',$post_data);
		return $vault_response;
	}

	public function make_payment_profile($customer_info, $charge_data, $options = array()){
		$valor_vault_id = "";
		if (!$customer_info->valor_vault_id)
		{
			$vault_response = $this->make_valor_add_customer($customer_info);
			if(isset($vault_response['status']) && $vault_response['status'] != 'OK'){
				$TextResponse = $vault_response['errors'][0];
				$ret = array('state' => 0, 'message' => $TextResponse);
				return $ret;
			}
			$valor_vault_id = $vault_response['vault_customer_id'];
		}
		else
		{
			$valor_vault_id = $customer_info->valor_vault_id;
		}
		
		$payment_id = "";
		if($valor_vault_id)
		{
			//Before adding a new payment profile, delete all existing profiles, except for subscription payment profiles.
			$payment_profiles = $this->make_valor_api_request('api/valor-vault/getpaymentprofile/'.$valor_vault_id,array());
			if ($payment_profiles)
			{
				$this->controller->load->model('Customer_subscription');

				foreach($payment_profiles['data'] as $pp)
				{
					if(!$this->controller->Customer_subscription->exists_card_on_file_token($pp['payment_id']) || $options['renew_token'] == $pp['payment_id']){
						$r = $this->make_valor_api_request('api/valor-vault/deletepaymentprofile/'.$valor_vault_id.'/'.$pp['payment_id'], array(), 'DELETE');
					}
				}
			}

			if($options['api'] == 'addpaymentprofile'){
				$add_payment_profile_response = $this->make_valor_api_request('api/valor-vault/addpaymentprofile/'.$valor_vault_id, $charge_data);
				if(isset($add_payment_profile_response['status']) && $add_payment_profile_response['status'] != 'OK'){
					$TextResponse = $add_payment_profile_response['errors'][0];
					$ret = array('state' => 0, 'message' => $TextResponse);
					return $ret;
				}
				$payment_id = $add_payment_profile_response['payment_id'];
			}else if($options['api'] == 'addpaymentprofiletxn'){
				$add_payment_from_txn_to_vault_response = $this->make_valor_api_request('api/valor-vault/addpaymentprofiletxn/'.$valor_vault_id,array('ref_txn_id' => $options['RefNo']));
				if(isset($add_payment_from_txn_to_vault_response['status']) && $add_payment_from_txn_to_vault_response['status'] != 'OK'){
					$TextResponse = $add_payment_from_txn_to_vault_response['errors'][0];
					$ret = array('state' => 0, 'message' => $TextResponse);
					return $ret;
				}
				$payment_id = $add_payment_from_txn_to_vault_response['payment_id'];
			}

		}

		$ret = array( 'state' => 1, 'message' => 'SUCCESS', 'payment_id' => $payment_id, 'valor_vault_id' => $valor_vault_id );
		return $ret;
	}
	
	public function get_emv_ebt_balance($CardType='Foodstamp')
	{
		$balance = 0;
		$payload = array();
		

		
		if ($CardType == 'Foodstamp')
		{
			$payload['TRAN_MODE'] = "3";
			$payload['TRAN_CODE'] = "8";
		}
		elseif($CardType== 'Cash')
		{
			$payload['TRAN_MODE'] = "4";
			$payload['TRAN_CODE'] = "8";
		}
		
		if (!$this->is_terminal_online())
		{
			$response = array();
			$response['EBT_BAL_AMOUNT'] = 0;
		}
		else
		{
			$response = $this->make_valor_connect_terminal_request($payload);
		}			
		
		$balance = (string)round(abs($response['EBT_BAL_AMOUNT']/100),2);
		
		$this->controller->load->view('sales/valor_ebt_balance_inquery', array('balance' => $balance));		
		
	}

	function get_transaction_history($params=array())
	{
		$transactions = array();
		try
		{
			$post_data = array(
				"appid" => $this->appid,
				"appkey" => $this->appkey,
				"epi" => $this->epi,
				"txn_type" => 'txnfetch_date',
				'start_date_range' => substr($params['startDate'], 0, 10),
				'end_date_range' => substr($params['endDate'], 0, 10),
				'source' => '0',
				'transaction_type' => '0',
				'card_type' => '0',
				'transaction_status' => !$params['show_declines'] ? 'APPROVED' : 'ALL',
				'devices' => '0',
				'filter' => '',
				'filter_text' => '',
				'limit' => $params['maxResults'] ?? 1000,
				'offset' => $params['startIndex'] ?? 0,
				'processor' => '0'
			);
			$result = $this->make_valor_merchant_api_request('?txnfetch',$post_data);

			if($result['status'] == 'SUCCESS'){
				$transactions['success'] = true;
				$transactions['totalResultCount'] = 1000;

				$transactions['transactions'] = array();
				foreach($result['data'] as $transaction_valor){

					$transaction = array();

					//mapping
					$transaction['success'] = true;
					$transaction['error'] = "";
					$transaction['responseDescription'] = $transaction_valor['ORDER_DESCRIPTION'];
 					$transaction['approved'] = $transaction_valor['RESPONSE_CODE'] == '00';//to do fix
					$transaction['authCode'] = $transaction_valor['AUTH_CODE'];
					$transaction['transactionId'] = $transaction_valor['REF_TXN_ID'];
					$transaction['batchID'] = 'N/A';
					$transaction['transactionRef'] = 'N/A';
					$transaction['transactionType'] = 'N/A';
					if($transaction_valor['IS_VOID'] )
					{
						$transaction['transactionType'] = "void";
					}
					elseif(($transaction_valor['TXN_TYPE'] == 'SALE' || $transaction_valor['TXN_TYPE'] == 'DEBIT SALE'))
					{
						$transaction['transactionType'] = "charge";
					}
					else if($transaction_valor['TXN_TYPE'] == 'REFUND')
					{
						$transaction['transactionType'] = "refund";
					}
					else
					{
						$transaction['transactionType'] = strtolower($transaction_valor['TXN_TYPE']);
					}

					// Parse UTC time
					$utc_timestamp = strtotime($transaction_valor['TXN_ORIG_DATE']);

					// Get current PHP default timezone offset in seconds
					$default_timezone = date_default_timezone_get();
					$timezone_offset = timezone_offset_get(new DateTimeZone($default_timezone), new DateTime("@$utc_timestamp"));

					// Adjust timestamp to local time
					$local_timestamp = $utc_timestamp + $timezone_offset;

					// Format the adjusted timestamp
					$transaction['timestamp'] = date(get_date_format().' '.get_time_format(), $local_timestamp);
					$transaction['tickblock'] = 'N/A';
					$transaction['test'] = 'N/A';
					$transaction['partialAuth'] = 'N/A';
					$transaction['altCurrency'] = 'N/A';
					$transaction['fsaAuth'] = 'N/A';
					$transaction['currencyCode'] = 'N/A';
					$transaction['requestedAmount'] = $transaction_valor['NET_AMOUNT']/100;
					$transaction['authorizedAmount'] = $transaction_valor['NET_AMOUNT']/100;
					$transaction['remainingbalance'] = 'N/A';
					$transaction['tipAmount'] = $transaction_valor['TIP_AMOUNT'];
					@$transaction['taxAmount'] = $transaction_valor['TAX_AMOUNT'];

					$transaction['requestCashBackAmountt'] = 'N/A';
					$transaction['authorizedCashBackAmount'] = 'N/A';
					$transaction['entryMode'] = $transaction_valor['POS_ENTRY_MODE'];
					$transaction['entryMethod'] = lang('common_credit');
					
					$transaction['paymentType'] = $transaction_valor['CARD_SCHEME'];
					$transaction['network'] = 'N/A';
					$transaction['logo'] = 'N/A';
					$transaction['maskedPan'] = $transaction_valor['PAN'];
					$transaction['cardHolder'] = $transaction_valor['CARDHOLDER_NAME'];
					$transaction['expMonth'] = 'N/A';
					$transaction['expYear'] = 'N/A';
					$transaction['avsResponse'] = 'N/A';
					$transaction['receiptSuggestions'] = 'N/A';
					$transaction['customer'] = 'N/A';
					$transaction['customers'] = 'N/A';
					$transaction['whiteListedCard'] = 'N/A';
					$transaction['storeAndForward'] = 'N/A';
					$transaction['status'] = 'N/A';

					$transaction['receiptSuggestions'] = array(
						'merchantName' => $transaction_valor['CARDHOLDER_NAME'],
						'applicationLabel' => $transaction_valor['CARD_SCHEME'],
						'maskedPan' => $transaction_valor['PAN'],
						'authorizedAmount' => $transaction_valor['NET_AMOUNT']/100,
						'transactionType' => $transaction['transactionType']
					);
					array_push($transactions['transactions'], $transaction);
				}
			}
		}
		catch(Exception $e)
		{
			
		}
		return $transactions;
	}

	function void_or_return_transaction_by_id($transaction_id, $amount = NULL){

		//try void first
		try{


			if (!$amount)
			{
				$void_post_data = array(
				"appid" => $this->appid,
				"appkey" => $this->appkey,
				"epi" => $this->epi,
				"txn_type" => 'void',
				'ref_txn_id' => $transaction_id,
				);
	
				$response = $this->make_valor_merchant_api_request('?void', $void_post_data);
			}
			
			//do refund if void failed
			if(!isset($response) || $response['error_code'] != "00"){
				$refund_post_data = array(
					"appid" => $this->appid,
					"appkey" => $this->appkey,
					"epi" => $this->epi,
					"txn_type" => 'refund',
					'amount' => $amount,
					'surchargeIndicator' => $this->controller->cart->get_fee_amount() == 0 ? 0 : 1,
					'ref_txn_id' => $transaction_id,
					'sale_refund' => "1",
				);

				$response = $this->make_valor_merchant_api_request('?refund', $refund_post_data);
				if($response['error_code'] == "00"){
					$response['transactionType'] = 'refund';
					$response['transactionId'] = $response['txnid'];
					$response['authorizedAmount'] = $response['amount'];
					$response['authCode'] = '';
					return $response;
				}
				
				return FALSE;

			}else{
				$response['transactionType'] = 'void';
				$response['transactionId'] = $response['txnid'];
				$response['authorizedAmount'] = $response['amount'];
				$response['authCode'] = $response['auth_code'];
				return $response;
			}
		} catch(Exception $e){
		}

		return FALSE;
	}
	
	function process_invoice_card_not_present_transaction($cc_number,$cc_amount,$expire_month,$expire_year, $cc_ccv)
	{
		$post_data = array(
			"appid" => $this->appid,
			"appkey" => $this->appkey,
			"epi" => $this->epi,
			'txn_type' => 'sale',
			"amount" => to_currency_no_money($cc_amount),
			"cardnumber" => $cc_number,
			'expirydate' => $expire_month.'/'.substr($expire_year, strlen($expire_year) - 2),
			'cvv' => $cc_ccv,
			'shipping_country' => 'US'
		);
		$sale_response = $this->make_valor_merchant_api_request('?sale',$post_data);
		
		$response = array();
		$response['STATE'] = @$sale_response['error_code'] == '00' ? '0' : '-1';
		$response['AMOUNT'] = @$sale_response['partial_amount'];
		$response['AUTH_RSP_TEXT'] = @$sale_response['msg'];
	
		if (!$sale_response || $response['STATE'] != '00')
		{
			return FALSE;
		}
		
		$response['CODE'] = @$sale_response['approval_code'];
		$response['RRN'] = @$sale_response['rrn'];
		$response['TXN_ID'] = @$sale_response['txnid'];
		$response['TRAN_NO'] = @$sale_response['tran_no'];
		$response['PARTIAL'] = @(string)$sale_response['is_partial_approve'];
		$response['MASKED_PAN'] = @$sale_response['pan'];
		$response['ISSUER'] = @lang('common_credit');
		$response['EXPIRY_DATE'] = @$sale_response['expiry_date'];

		$EntryMethod = $prompt ? lang('sales_manual_entry') : lang('common_credit');
		$TranCode = lang('sales_card_transaction');

		$response['TRAN_TYPE'] = $TranCode;
		$response['ENTRY_MODE'] = $EntryMethod;
		
		return $response;
		
	}
	
	function process_invoice_token_transaction($cc_amount,$customer_info)
	{
		$payment_profiles = $this->make_valor_api_request('api/valor-vault/getpaymentprofile/'.$customer_info->valor_vault_id,array());
		$the_vault_token= '';
		foreach($payment_profiles['data'] as $pp)
		{
			if ($pp['payment_id'] == $customer_info->cc_token)
			{
				$the_vault_token = $pp['token'];
				break;
			}
		}
		$post_data = array(
			"appid" => $this->appid,
			"appkey" => $this->appkey,
			"epi" => $this->epi,
			"amount" => to_currency_no_money($cc_amount),
			'txn_type' => 'sale',
			'surchargeIndicator' => $this->controller->cart->get_fee_amount() == 0 ? 0 : 1,
			'shipping_country' => 'US',
			'token' => $the_vault_token,
		);
		
		$token_response = $this->make_valor_merchant_api_request('?pagesale',$post_data);			
		//Normalize response so it can be processed same way as terminal transaction
		$response = array();
		$response['STATE'] = @$token_response['error_code'] == '00' ? '0' : '-1';
		$response['AMOUNT'] = @$token_response['partial_amount'];
		$response['AUTH_RSP_TEXT'] = @$token_response['msg'];
		
		if ($response['STATE'] != '00')
		{
			return FALSE;
		}
		
		$response['CODE'] = @$token_response['approval_code'];
		$response['RRN'] = @$token_response['rrn'];
		$response['TXN_ID'] = @$token_response['txnid'];
		$response['TRAN_NO'] = @$token_response['tran_no'];
		$response['PARTIAL'] = @(string)$token_response['is_partial_approve'];
		$response['MASKED_PAN'] = @$token_response['pan'];
		$response['TRAN_TYPE'] = @'TOKEN';
		$response['ISSUER'] = @lang('sales_card_on_file');
		$response['EXPIRY_DATE'] = @$token_response['expiry_date'];
	
		return $response;
	}
	
	
	function process_invoice_terminal_transaction($cc_amount)
	{
		$payload = [
			"TRAN_MODE" => "1",
			"PAPER_RECEIPT" => "0",
		];
		
		$payload['TIP_ENTRY'] = "0"; 
		$payload['AMOUNT'] = (string)((int)(round(abs($cc_amount)*100)));
		$payload['TRAN_CODE'] = $cc_amount > 0 ? "1" : "5";
		
		if ($cc_amount < 0 && $this->controller->cart->return_sale_id)
		{
			$payload['TXN_ID'] = $this->controller->Sale->get_highest_payment_ref_no($this->controller->cart->return_sale_id);
		}
		
		if (!$this->is_terminal_online())
		{
			return FALSE;
		}
		
		
		$response = $this->make_valor_connect_terminal_request($payload);
		$CardType = @$response['ISSUER'];
		$EntryMethod = @$response['ENTRY_MODE'];

		$AID = @$response['AID'];
		$TVR = @$response['TVR'];
		$IAD = @$response['IAD'];
		$TSI = @$response['TSI'];
		$ApplicationLabel = '';
					
		
		$MerchantID =  $this->appid;
		$AcctNo = $response['MASKED_PAN'];
		$TranCode = $response['TRAN_TYPE'];
		$AuthCode = $response['CODE'];
		$RefNo = $response['TXN_ID'];
		$TranNo = $response['TRAN_NO'];
		
		if ($response['STATE'] === '0')
		{
			return $response;
		}
		
		return FALSE;		
	}
	
}