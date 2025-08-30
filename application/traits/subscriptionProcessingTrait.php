<?php
require_once (APPPATH."libraries/blockchyp/vendor/autoload.php");

use \BlockChyp\BlockChyp;

trait subscriptionProcessingTrait
{
	private function process_sub($sub)
	{
		$this->load->model('Customer_subscription');
		$this->load->model('Customer');

		if($sub['payment_option'] == 2){//store account payment option
			$customer_info = $this->Customer->get_info($sub['customer_id']);
			$response = array(
				'authorizedAmount' => to_currency_no_money($this->Customer_subscription->get_subscription_amount_with_tax($sub)),
			);

			$this->process_successful_charge($sub,$response);
			return;
		}

		$credit_card_processor = NULL;
		if ($this->Location->get_info_for_key('credit_card_processor', $sub['location_id']) == 'coreclear2')
		{
			BlockChyp::setApiKey($this->Location->get_info_for_key('blockchyp_api_key', $sub['location_id']));
			BlockChyp::setBearerToken($this->Location->get_info_for_key('blockchyp_bearer_token', $sub['location_id']));
			BlockChyp::setSigningKey($this->Location->get_info_for_key('blockchyp_signing_key', $sub['location_id']));
			$test_mode = (boolean)$this->Location->get_info_for_key('blockchyp_test_mode',$sub['location_id']);
			$this->session->set_userdata('employee_current_location_id', $sub['location_id']);

			$charge_data = array(
				'test' => $test_mode,
				'token' => $sub['card_on_file_token'],
				'amount' => to_currency_no_money($this->Customer_subscription->get_subscription_amount_with_tax($sub)),
			);			

			try
			{
				$response = BlockChyp::charge($charge_data);
				$failure = !($response['success'] && $response['approved']);
			}
			catch(Exception $e)
			{
				$failure = TRUE;
			}
		}
		elseif ($this->Location->get_info_for_key('credit_card_processor', $sub['location_id']) == 'valor')
		{
			require_once (APPPATH.'libraries/Valorprocessor.php');
			$credit_card_processor = new Valorprocessor($this, $sub['location_id']);
			$response = array();
			$failure = FALSE;

			$customer_info = $this->Customer->get_info($sub['customer_id']);
			$payment_profiles = $credit_card_processor->make_valor_api_request('api/valor-vault/getpaymentprofile/'.$customer_info->valor_vault_id ,array());
			$the_vault_token = '';
			foreach($payment_profiles['data'] as $pp)
			{
				if ($pp['payment_id'] == $sub['card_on_file_token'])
				{
					$the_vault_token = $pp['token'];
					break;
				}
			}

			$post_data = array(
				"appid" => $credit_card_processor->appid,
				"appkey" => $credit_card_processor->appkey,
				"epi" => $credit_card_processor->epi,
				"amount" => to_currency_no_money($this->Customer_subscription->get_subscription_amount_with_tax($sub)),
				'txn_type' => 'sale',
				'surchargeIndicator' => 0,
				'shipping_country' => 'US',
				'token' => $the_vault_token,
			);

			try{
				$token_response = $credit_card_processor->make_valor_merchant_api_request('?pagesale',$post_data);
				$failure = @$token_response['error_code'] == '00' ? FALSE : TRUE;
				if(!$failure){
					$response['authorizedAmount'] = @$token_response['amount'];
					$response['maskedPan'] = @$token_response['pan'];
					$response['authCode'] = @$token_response['approval_code'];
					$response['transactionId'] = @$token_response['tran_no'];
					$response['paymentType'] = lang('common_credit');
					$response['entryMethod'] = lang('common_credit');
					$response['receiptSuggestions'] = array(
						'applicationLabel' => '',
						'aid' => '',
						'tvr' => '',
						'iad' => '',
						'tsi' => '',
					);
				}
			}
			catch(Exception $e)
			{
				$failure = TRUE;
			}
		}

		if ($failure)
		{
			$this->process_failed_charge($sub,$response);				
		}
		else
		{
			$this->process_successful_charge($sub,$response);
		}
	}
	
	private function process_failed_charge($sub,$response)
	{
		$this->load->model('Customer_subscription');
		$sub['status'] = 'failed';
		$sub['retries_attempted']++;
		
		if($sub['retries_attempted'] >=3)
		{
			//cancel
			$sub['status'] = 'cancelled';
		}
		else
		{
			$sub['next_retry_date'] = date('Y-m-d',strtotime('+2 days'));
		}
		
		$this->Customer_subscription->save($sub,$sub['id']);
	}
	
	private function process_successful_charge($sub,$response)
	{
		$this->load->model('Customer_subscription');
		$sub['status'] = 'current';
		$sub['next_payment_date'] = $this->Customer_subscription->get_next_payment_date($sub['id']);
		$sub['next_retry_date'] = NULL;
		$sub['retries_attempted'] = 0;
		
		$this->Customer_subscription->save($sub,$sub['id']);
		$this->Customer_subscription->save_sale_for_charge($sub,$response);
	}
}
