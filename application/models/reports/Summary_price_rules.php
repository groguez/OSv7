<?php
require_once ("Report.php");
class Summary_price_rules extends Report
{
	function __construct()
	{
		$this->times_rules_applied = 0;
		parent::__construct();
	}
	
	public function getInputData()
	{
		$input_data = Report::get_common_report_input_data(TRUE);
		
		$input_params = array();
		$specific_entity_data['specific_input_name'] = 'customer_id';
		$specific_entity_data['specific_input_label'] = lang('reports_customer');
		$specific_entity_data['search_suggestion_url'] = site_url('reports/customer_search/1');
		$specific_entity_data['view'] = 'specific_entity';
		
		if ($this->settings['display'] == 'tabular')
		{
			$input_params = array(
				array('view' => 'date_range', 'with_time' => TRUE),
				$specific_entity_data,
				array('view' => 'checkbox','checkbox_label' => lang('reports_group_by_customer'), 'checkbox_name' => 'group_by_customer'),
				array('view' => 'dropdown','dropdown_label' =>lang('reports_sale_type'),'dropdown_name' => 'sale_type','dropdown_options' =>array('all' => lang('reports_all'), 'sales' => lang('reports_sales'), 'returns' => lang('reports_returns')),'dropdown_selected_value' => 'all'),
				array('view' => 'excel_export'),
				array('view' => 'locations'),
				array('view' => 'submit'),
			);
		}
		
		$input_data['input_report_title'] = lang('reports_report_options');
		$input_data['input_params'] = $input_params;
		return $input_data;
	}
	
	function getOutputData()
	{
		$tabular_data = array();
		$report_data = $this->getData();
		
		foreach($report_data as $row)
		{
			$row_to_add = array();
			
		    if (!empty($this->params['group_by_customer'])) 
			{
	           // Check for empty customer value and provide a default text if needed.
	           $customer = !empty($row['customer']) ? $row['customer'] : lang('reports_no_customer');
	           $row_to_add[] = array('data' => $customer, 'align' => 'center');
		    }
            $row_to_add[] = array('data' => $row['name'], 'align' => 'left');
            $row_to_add[] = array('data'=>to_quantity($row['count']), 'align' => 'left');
            $row_to_add[] = array('data'=>to_currency($row['total']), 'align' => 'left');
            $row_to_add[] = array('data'=>to_currency($row['total_sales']), 'align' => 'center');
			$tabular_data[] = $row_to_add;
			$this->times_rules_applied+= $row['count'];
		}
 		$data = array(
			'view' => 'tabular',
			"title" => lang('price_rules_summmary_report'),
			"subtitle" => date(get_date_format(), strtotime($this->params['start_date'])) .'-'.date(get_date_format(), strtotime($this->params['end_date'])),
			"headers" => $this->getDataColumns(),
			"data" => $tabular_data,
			"summary_data" => $this->getSummaryData(),
			"export_excel" => $this->params['export_excel'],
			"pagination" => ''
		);
		
	return $data;
		
	}
	
	
	public function getDataColumns()
	{
		$this->lang->load('price_rules');
	    $columns = array();
	    if (!empty($this->params['group_by_customer'])) {
	        $columns[] = array('data'=>lang('common_customer'), 'align'=> 'center');
	    }
		
	    $columns[] = array('data'=>lang('price_rules_name'), 'align'=> 'center');
	    $columns[] = array('data'=>lang('common_count'), 'align'=> 'center');
	    $columns[] = array('data'=>lang('common_total'), 'align'=> 'center');
	    $columns[] = array('data'=>lang('reports_total_sales'), 'align'=> 'center');
    
	    return $columns;
	}	
	function _item_level_query()
	{
		$location_ids = self::get_selected_location_ids();
		$this->db->select("COALESCE(CONCAT_WS(' ', phppos_people.first_name, phppos_people.last_name), '".lang('reports_no_customer')."') as customer ,".'price_rules.name, count(DISTINCT('.$this->db->dbprefix('sales_items').'.sale_id)) as count, SUM((phppos_sales_items.regular_item_unit_price_at_time_of_sale * phppos_sales_items.quantity_purchased)- phppos_sales_items.subtotal) as total, SUM(phppos_sales_items.regular_item_unit_price_at_time_of_sale * phppos_sales_items.quantity_purchased) as total_sales', false);
		$this->db->from('sales');
		$this->db->join('sales_items', 'sales.sale_id = sales_items.sale_id');
		$this->db->join('locations', 'sales.location_id = locations.location_id');
		$this->db->join('price_rules', 'price_rules.id = sales_items.rule_id');
	    $this->db->join('customers', 'sales.customer_id = customers.person_id', 'left');
	    $this->db->join('people', 'customers.person_id = people.person_id', 'left');

		if ($this->params['sale_type'] == 'sales')
		{
			$this->db->where('total_quantity_purchased > 0');
		}
		elseif ($this->params['sale_type'] == 'returns')
		{
			$this->db->where('total_quantity_purchased < 0');
		}
		
		if (isset($this->params['customer_id']) && $this->params['customer_id'] !== '') {
		    $this->db->where('sales.customer_id', $this->params['customer_id']);
		}
		
		if (!empty($this->params['group_by_customer'])) 
		{
		    $this->db->group_by('sales.customer_id');
		}
		
		$this->sale_time_where();
		$this->db->where('sales.deleted', 0);
		$this->db->where_in('sales.location_id', $location_ids);
		$this->db->group_by('sales_items.rule_id');
		$this->db->order_by('sale_time', ($this->config->item('report_sort_order')) ? $this->config->item('report_sort_order') : 'asc');
	}
	
	public function getData()
	{		
		$this->_item_level_query();
		$item_return = $this->db->get()->result_array();

		$return = array();
		foreach($item_return as $item_row)
		{
			$return[] = array('customer' => $item_row['customer'], 'name' => $item_row['name'], 'count' => $item_row['count'], 'total' => $item_row['total'], 'total_sales' => $item_row['total_sales']);
		}

		return $return;
	}

	public function getTotalRows()
	{
		$this->_item_level_query();
		$item_return = $this->db->count_all_results();

		return $item_return;
		
	}
	public function getSummaryData()
	{
		return array('times_rules_applied' => $this->times_rules_applied);
	}
}

?>