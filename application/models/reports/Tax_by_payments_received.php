<?php
require_once ("Report.php");

class Tax_by_payments_received extends Report
{
	function __construct()
	{
		parent::__construct();
	}
	
	public function getInputData()
	{
		
		$input_params = array();

    	$CI =& get_instance();
		$CI->load->model('Sale');
		
		$input_data = Report::get_common_report_input_data(TRUE);
		
		$input_params = array(
			array('view' => 'date_range','date_range_label' =>lang('reports_payment_date_range'), 'with_time' => TRUE),
			array('view' => 'dropdown','dropdown_label' =>lang('reports_sale_type'),'dropdown_name' => 'sale_type','dropdown_options' =>array('all' => lang('reports_all'), 'sales' => lang('reports_sales'), 'returns' => lang('reports_returns')),'dropdown_selected_value' => 'all'),
			array('view' => 'payment_types'),
			array('view' => 'excel_export'),
			array('view' => 'locations'),
			array('view' => 'submit'),
		);
		
		$input_data['input_report_title'] = lang('reports_report_options');
		$input_data['input_params'] = $input_params;
		return $input_data;
	}
		
	public function getOutputData()
	{
		$this->load->model('Sale');
		
		$subtitle = date(get_date_format(), strtotime($this->params['start_date'])) .'-'.date(get_date_format(), strtotime($this->params['end_date']));

		$tabular_data = array();
		$report_data = $this->getData();
		$summary_data = $this->getSummaryData();
		
		$export_excel = $this->params['export_excel'];
		
		foreach($report_data as $row)
		{
			$tab_row = array();
			$tab_row[] = array('data'=>$row['sale_id'], 'align'=>'left');
			// $tab_row[] = array('data'=>$row['ecommerce_order_id'], 'align'=>'left');
			$tab_row[] = array('data'=>date(get_date_format(), strtotime($row['payment_date'])), 'align'=>'left');
			$tab_row[] = array('data'=>date(get_time_format(), strtotime($row['payment_date'])), 'align'=>'left');
			$tab_row[] = array('data'=>$row['payment_type'], 'align'=>'left');
			
			$payment_amount = $row['payment_amount'];
			
			$tab_row[] = array('data'=>to_currency($payment_amount), 'align'=>'left');

			$effective_tax_rate = $row['total'] != 0 ?round($row['tax']/$row['total'],4):0;
			$tab_row[] = array('data'=>$effective_tax_rate, 'align'=>'left');
			$tab_row[] = array('data'=>to_currency($effective_tax_rate*$payment_amount), 'align'=>'left');

			$tabular_data[] = $tab_row;;
		}
	
		$data = array(
			"view" => 'tabular',
			"title" => lang('reports_tax_by_payments_received'),
			"subtitle" => $subtitle,
			"data" => $tabular_data,
			"summary_data" => $summary_data,
			"export_excel" => $export_excel,
			"headers" => $this->getDataColumns(),
		);
		
		return $data;
	}
	
	public function getDataColumns()
	{
		$return = array(array('data'=>lang('common_id'), 'align'=>'left'),/*array('data'=>lang('common_internet_order_id'), 'align'=>'left'),*/ array('data'=>lang('reports_payment_date'), 'align'=>'left'),array('data'=>lang('reports_payment_time'), 'align'=>'left'), array('data'=>lang('common_payment_type'), 'align'=>'left'),array('data'=>lang('common_amount'), 'align'=>'left'),array('data'=>lang('reports_tax_rate'), 'align'=>'left'),array('data'=>lang('reports_tax_amount'), 'align'=>'left'));
		
		return $return;
	}
	
	public function getData()
	{
		$sale_ids_for_payments = $this->get_sale_ids_for_payments();
		
		$sales_totals = array();
		
		$this->db->select('sale_id, SUM(subtotal + tax) as total', false);
		$this->db->from('sales');
		if (count($sale_ids_for_payments))
		{
			$this->db->group_start();
			$sale_ids_chunk = array_chunk($sale_ids_for_payments,25);
			foreach($sale_ids_chunk as $sale_ids)
			{
				$this->db->or_where_in('sale_id',$sale_ids);
			}
			$this->db->group_end();
		}
		$this->db->where('deleted', 0);
		$this->db->group_by('sale_id');
		foreach($this->db->get()->result_array() as $sale_total_row)
		{
			$sales_totals[$sale_total_row['sale_id']] = to_currency_no_money($sale_total_row['total'],2);
		}

		$this->dataQuery();
		$sales_payments =  $this->db->get()->result_array();

		$summary_payment_data = array();
		$payments_by_sale = array();

		foreach($sales_payments as $key => $row)
		{
			$payments_by_sale[$row['sale_id']][] = $row;
		}
		
		$sale_ids = array_keys($payments_by_sale);
		$all_payments_for_sales = $this->Sale->_get_all_sale_payments($sale_ids);

		foreach($all_payments_for_sales as $sale_id => $payment_rows)
		{
			if (isset($sales_totals[$sale_id]))
			{
				$total_sale_balance = $sales_totals[$sale_id];
			
				foreach($payment_rows as $payment_row)
				{
					$exists = $this->Sale->_does_payment_exist_in_array($payment_row['payment_id'], $payments_by_sale[$sale_id]);

					if(strpos($payment_row['payment_type'], lang('common_credit')) !== false || strpos($payment_row['payment_type'], lang('common_manual')) !== false || $payment_row['payment_type'] == 'Online Payment'){
						$payment_amount = $payment_row['payment_amount'];

						if ($exists){
							$payment_row['payment_amount'] = $payment_amount;
							$payment_row['tax'] = $payments_by_sale[$sale_id][0]['tax'];
							$payment_row['subtotal'] = $payments_by_sale[$sale_id][0]['subtotal'];
							$payment_row['total'] = $payments_by_sale[$sale_id][0]['total'];
							$summary_payment_data[] = $payment_row;
						}
					}
					else{
						//Postive sale total, positive payment
						if ($sales_totals[$sale_id] >= 0 && $payment_row['payment_amount'] >=0)
						{
							$payment_amount = $payment_row['payment_amount'] <= $total_sale_balance ? $payment_row['payment_amount'] : $total_sale_balance;
						}//Negative sale total negative payment
						elseif ($sales_totals[$sale_id] < 0 && $payment_row['payment_amount']  < 0)
						{
							$payment_amount = $payment_row['payment_amount'] >= $total_sale_balance ? $payment_row['payment_amount'] : $total_sale_balance;
						}//Positive Sale total negative payment
						elseif($sales_totals[$sale_id] >= 0 && $payment_row['payment_amount']  < 0)
						{
							$payment_amount = $total_sale_balance != 0 ? $payment_row['payment_amount'] : 0;
						}//Negtive sale total postive payment
						elseif($sales_totals[$sale_id] < 0 && $payment_row['payment_amount']  >= 0)
						{
							$payment_amount = $total_sale_balance != 0 ? $payment_row['payment_amount'] : 0;
						}	

						if (($total_sale_balance != 0 || 
							($sales_totals[$sale_id] >= 0 && $payment_row['payment_amount']  < 0) ||
							($sales_totals[$sale_id] < 0 && $payment_row['payment_amount']  >= 0)) && $exists)
						{
							$payment_row['payment_amount'] = $payment_amount;
							$payment_row['tax'] = $payments_by_sale[$sale_id][0]['tax'];
							$payment_row['subtotal'] = $payments_by_sale[$sale_id][0]['subtotal'];
							$payment_row['total'] = $payments_by_sale[$sale_id][0]['total'];
							$summary_payment_data[] = $payment_row;
						}
					}		
				
					$total_sale_balance-=$payment_amount;
				}
			}
		}

		return $summary_payment_data;

	}
	
	function getTotalRows()
	{
		$this->dataQuery();
		return $this->db->count_all_results();
	}
	
	private function dataQuery()
	{
		$location_ids = isset($this->params['override_location_id']) ? array($this->params['override_location_id']) : self::get_selected_location_ids();
		$payment_types = self::get_selected_payment_types();
		
		$this->db->select('sales_payments.*,sales.tax,('.$this->db->dbprefix('sales').'.subtotal + '.$this->db->dbprefix('sales').'.tax) as total,sales.subtotal', false);
		$this->db->from('sales_payments');
		$this->db->join('sales', 'sales.sale_id=sales_payments.sale_id');
		$this->db->where('payment_date BETWEEN '. $this->db->escape($this->params['start_date']). ' and '. $this->db->escape($this->params['end_date']));

		if ($this->config->item('hide_store_account_payments_in_reports'))
		{
			$this->db->where('sales.store_account_payment',0);
		}
		
		$this->db->where_in('sales.location_id', $location_ids);
		$this->db->where('sales.deleted', 0);
		

		if ($this->params['sale_type'] == 'sales')
		{
			$this->db->where('sales.total_quantity_purchased > 0');
		}
		elseif ($this->params['sale_type'] == 'returns')
		{
			$this->db->where('sales.total_quantity_purchased < 0');
		}
	
		$this->db->group_start();
		foreach($payment_types as $payment_type){
			$this->db->or_like('sales_payments.payment_type', $payment_type, 'both');
		}
		$this->db->group_end();

		$this->db->order_by('sales_payments.sale_id,sales_payments.payment_date,sales_payments.payment_type');
	}
	public function getSummaryData()
	{
		$report_data = $this->getData();
		
		$return = array(
			'total_tax' => 0,
			'total_taxable_sales' => 0,
			'total_non_taxable_sales' => 0,
			'total_sales' => 0,
		);
		
		$total_tax = 0;
		$total_payment_amount = 0;

		foreach($report_data as $row)
		{
			$payment_amount = $row['payment_amount'];

			$effective_tax_rate = $row['total'] != 0?round($row['tax']/$row['total'],4):0;
			$tax_amount = $effective_tax_rate*$payment_amount;
			
			$total_tax += $tax_amount;
			$total_payment_amount += $payment_amount;
		}
		$return['total_tax'] = to_currency_no_money($total_tax);
		$return['total_sales'] = to_currency_no_money($total_payment_amount);

		$default_tax_class_id = $this->config->item('tax_class_id');
		if($default_tax_class_id){
			$total_tax_rate = 0;
			$this->load->model('Tax_class');
			$taxes = $this->Tax_class->get_taxes($default_tax_class_id);
			foreach($taxes as $tax)
			{
				$total_tax_rate += $tax['percent'];
			}

			$return['total_taxable_sales'] = to_currency_no_money(($return['total_tax']/$total_tax_rate)*100);
		}
		$return['total_non_taxable_sales'] = to_currency_no_money($total_payment_amount-$return['total_taxable_sales']);

		return $return;
	}

	function get_sale_ids_for_payments()
	{
		$sale_ids = array();
		$location_ids = self::get_selected_location_ids();
		$location_ids_string = implode(',',$location_ids);

		$payment_types = self::get_selected_payment_types();

		$this->db->select('sales_payments.sale_id');
		$this->db->distinct();
		$this->db->from('sales_payments');
		$this->db->join('sales', 'sales.sale_id=sales_payments.sale_id');
		$this->db->where('payment_date BETWEEN '. $this->db->escape($this->params['start_date']). ' and '. $this->db->escape($this->params['end_date']).' and location_id IN('.$location_ids_string.')');

		$this->db->group_start();
		foreach($payment_types as $payment_type){
			$this->db->or_like('sales_payments.payment_type', $payment_type, 'both');
		}
		$this->db->group_end();
		
		foreach($this->db->get()->result_array() as $sale_row)
		{
			 $sale_ids[] = $sale_row['sale_id'];
		}
		
		return $sale_ids;
	}
}
?>