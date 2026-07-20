<?php
require_once (APPPATH."models/cart/PHPPOSCartSale.php");

class Shopify_webhook extends MY_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->cart = new PHPPOSCartSale();
        $this->load->model('Appconfig');
        $this->load->model('Shopify');
				
		if (!is_cli())
		{
			if (!$this->config->item('ecommerce_realtime'))
			{
				die('webhooks disabled');
			}
		
			if ($this->config->item("ecommerce_platform") != 'shopify')
			{
				die('no shopify');
			}
			
			if (!$this->_validate_web_hook())
			{
				http_response_code(412);
				die('no validation');
			}
		}
		
    }
	
	function _validate_web_hook()
	{		
		$headers = getallheaders();
		$headers = array_change_key_case($headers, CASE_UPPER);
		$signature = isset($headers["X-SHOPIFY-HMAC-SHA256"]) ? $headers["X-SHOPIFY-HMAC-SHA256"] : NULL;
		if (empty($signature))
		{
			return FALSE;
		}
		
		$webhook_secret_key =  $this->_get_webhook_secret_key();
		$payload = file_get_contents('php://input');		

		$calculated_hmac = base64_encode(hash_hmac('sha256', $payload, $webhook_secret_key, true));

		if ($signature != $calculated_hmac) 
		{
			return FALSE;
		}		

		return TRUE;
	}
	
	function _get_webhook_secret_key()
	{
		$this->load->helper('config');
		return get_config_key_shared('shopify_private');
	}
	

	public function item_webhook_create_product()
	{
		$ecommerce_cron_sync_operations_settings = unserialize($this->config->item('ecommerce_cron_sync_operations'));
		if(in_array('import_ecommerce_items_into_phppos', $ecommerce_cron_sync_operations_settings))
		{
        	$item_request = json_decode(file_get_contents('php://input'), TRUE);
        	$this->_save_item($item_request);
        }
		http_response_code(200);		
	}
	
    public function item_webhook_delete_product()
    {
		$ecommerce_cron_sync_operations_settings = unserialize($this->config->item('ecommerce_cron_sync_operations'));
		if(in_array('import_ecommerce_items_into_phppos', $ecommerce_cron_sync_operations_settings))
		{
        	$item_request = json_decode(file_get_contents('php://input'), TRUE);
        	$this->_delete_item($item_request['id']);
		}
		http_response_code(200);
    }
	
	
	public function item_webhook_update_product()
	{
		$ecommerce_cron_sync_operations_settings = unserialize($this->config->item('ecommerce_cron_sync_operations'));
		if(in_array('import_ecommerce_items_into_phppos', $ecommerce_cron_sync_operations_settings))
		{
        	$item_request = json_decode(file_get_contents('php://input'), TRUE);
        	$this->_save_item($item_request);
		}
		http_response_code(200);		
	}
	
    public function order_webhook_create()
    {
		$ecommerce_cron_sync_operations_settings = unserialize($this->config->item('ecommerce_cron_sync_operations'));
		if(in_array('import_ecommerce_orders_into_phppos', $ecommerce_cron_sync_operations_settings))
		{
    		$order_request = json_decode(file_get_contents('php://input'), TRUE);
        	$this->_save_order($order_request);
		}
		http_response_code(200);
    }
	
	public function order_webhook_update()
    {
		$ecommerce_cron_sync_operations_settings = unserialize($this->config->item('ecommerce_cron_sync_operations'));
		if(in_array('import_ecommerce_orders_into_phppos', $ecommerce_cron_sync_operations_settings))
		{
        	$order_request = json_decode(file_get_contents('php://input'), TRUE);
        	$this->_save_order($order_request);
        }
		http_response_code(200);
    }
	
	public function order_webhook_edit()
	{
		//Do nothing for now
	}
	
    public function order_webhook_delete()
    {
		$ecommerce_cron_sync_operations_settings = unserialize($this->config->item('ecommerce_cron_sync_operations'));
		if(in_array('import_ecommerce_orders_into_phppos', $ecommerce_cron_sync_operations_settings))
		{
        	$order_request = json_decode(file_get_contents('php://input'), TRUE);
        	$this->_delete_order($order_request['id']);
		}
		http_response_code(200);
    }

    private function _save_item($item_request)
    {
		$this->load->helper('command');
		$this->load->helper('text');
		$db_user= $this->db->username;
		$account = str_replace($db_user.'_','',$this->db->database);
		$phppos_url = base_url();
		$graph_ql_from_item_request = $this->Shopify->get_graphql_from_product_id($item_request['id']);
		$graph_ql_from_item_request['data']['node'] = $graph_ql_from_item_request['data']['product'];
		
		unset($graph_ql_from_item_request['data']['product']);
				
		$command = 'php '.FCPATH."index.php shopify_webhook save_item_in_background";
		
		add_command_to_queue_background_queue($command,$account,$phppos_url,json_encode($graph_ql_from_item_request['data']));
    }
	
	public function save_item_in_background($base_url_encoded = '', $db_override = '',$background_job_id=NULL)
	{
		if (!is_cli())
		{
			die('must be cli');
		}
		sleep(6);
		$this->load->helper('text');

		$site_db = $this->load->database('site', TRUE);

		$job = $site_db->select('task_data')
		               ->where('id', $background_job_id)
		               ->limit(1)
		               ->get('background_jobs')
		               ->row();

		if ($job && $job->task_data)
		{
	       $this->Shopify->import_ecommerce_item_into_phppos(json_decode($job->task_data, TRUE));
		}
	}

    private function _save_order($order_request)
    {
		if ($this->config->item('ecommerce_only_sync_completed_orders'))
		{
			if ($order_request['fulfillment_status'] != 'fulfilled')
			{
				return;
			}
		}

		$this->load->helper('command');
		$this->load->helper('text');
		$db_user= $this->db->username;
		$account = str_replace($db_user.'_','',$this->db->database);
		$phppos_url = base_url();
		$command = 'php '.FCPATH."index.php shopify_webhook save_order_in_background";
		
		add_command_to_queue_background_queue($command, $account, $phppos_url, json_encode($order_request));
	}
	public function save_order_in_background($base_url_encoded='', $db_override='', $background_job_id=NULL)
	{
		if (!is_cli())
		{
			die('must be cli');
		}
		sleep(6);
		$this->load->helper('text');

		$site_db = $this->load->database('site', TRUE);

		$job = $site_db->select('task_data')
			->where('id', $background_job_id)
			->limit(1)
			->get('background_jobs')
			->row();

		if ($job && $job->task_data)
		{
	       $this->Shopify->process_import_order(json_decode($job->task_data, TRUE));
		}
	}
	
    private function _delete_item($ecommerce_id)
    {
        $this->db->from('items');
		$this->db->where('ecommerce_product_id', (string)$ecommerce_id);
		$result = $this->db->get();
		if ($result->num_rows() == 1)
		{
			$item=$result->row_array();
			$item_id = $item['item_id'];

            $this->db->where('item_id', $item_id)->update('items', array('deleted' => 1, 'last_modified' => date('Y-m-d H:i:s')));
		}
    }

    private function _delete_order($ecommerce_id)
    {
        $this->load->model('Sale');

        $this->db->from('sales');
		$this->db->where('ecommerce_order_id', (string)$ecommerce_id);
		$result = $this->db->get();
		if ($result->num_rows() == 1)
		{
			$sale = $result->row_array();
			$sale_id = $sale['sale_id'];

			$this->Sale->update(
				array(
					'ecommerce_restock' => NULL,
				),
				$sale_id
			);

            if ($sale && $sale_id && !$sale['deleted']) {
                $this->Sale->delete($sale_id);
            }
		}
    }

    private function get_item_id_for_ecommerce_product($ecommerce_product_id)
	{
		$this->db->from('items');
		$this->db->where('ecommerce_product_id', (string)$ecommerce_product_id);
		$result = $this->db->get();
		if ($result->num_rows() >= 1)
		{
			$item=$result->row_array();
			return $item['item_id'];
		}
		else
		{
			return $this->Item->create_or_update_ecommerce_item();
		}
		
		return null;
	}
}