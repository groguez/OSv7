<?php
require_once ("Report.php");
class Summary_employees extends Report
{
	function __construct()
	{
		parent::__construct();
	}
	
	public function getInputData()
	{
		
		$input_params = array();

		if ($this->settings['display'] == 'tabular')
		{
			$input_data = Report::get_common_report_input_data(TRUE);
			
			$input_params = array(
				array('view' => 'date_range', 'with_time' => TRUE),
				array('view' => 'dropdown','dropdown_label' =>lang('reports_sale_type'),'dropdown_name' => 'sale_type','dropdown_options' =>array('all' => lang('reports_all'), 'sales' => lang('reports_sales'), 'returns' => lang('reports_returns')),'dropdown_selected_value' => 'all'),
				array('view' => 'dropdown','dropdown_label' =>lang('reports_employee_type'),'dropdown_name' => 'employee_type','dropdown_options' =>array( 'sale_person' => lang('reports_sale_person'), 'logged_in_employee' => lang('common_logged_in_employee')),'dropdown_selected_value' => 'sale_person'),
				array('view' => 'excel_export'),
				array('view' => 'locations'),
				array('view' => 'submit'),
			);
		}
		elseif ($this->settings['display'] == 'graphical')
		{
			$input_data = Report::get_common_report_input_data(FALSE);
			
			$input_params = array(
				array('view' => 'date_range', 'with_time' => TRUE),
				array('view' => 'dropdown','dropdown_label' =>lang('reports_sale_type'),'dropdown_name' => 'sale_type','dropdown_options' =>array('all' => lang('reports_all'), 'sales' => lang('reports_sales'), 'returns' => lang('reports_returns')),'dropdown_selected_value' => 'all'),
				array('view' => 'dropdown','dropdown_label' =>lang('reports_employee_type'),'dropdown_name' => 'employee_type','dropdown_options' =>array( 'sale_person' => lang('reports_sale_person'), 'logged_in_employee' => lang('common_logged_in_employee')),'dropdown_selected_value' => 'sale_person'),
				array('view' => 'locations'),
				array('view' => 'submit'),
			);
		
		}
		
		$input_data['input_report_title'] = lang('reports_report_options');
		$input_data['input_params'] = $input_params;
		return $input_data;
	}
	public function getOutputData()
	{
	
		$report_data = $this->getData();
		$summary_data = $this->getSummaryData();
		$subtitle = date(get_date_format(), strtotime($this->params['start_date'])) .'-'.date(get_date_format(), strtotime($this->params['end_date']));
		
		if ($this->settings['display'] == 'tabular')
		{
			$this->setupDefaultPagination();
			
			$tabular_data = array();
				
			foreach($report_data as $row)
			{
				$data_row = array();
		
				$data_row[] = array('data'=>$row['employee'], 'align' => 'left');

				$elementsArray = explode(",", $row['count']);
				// Remove duplicate elements
				$distinctElements = array_unique($elementsArray);
				// Count the distinct elements
				$distinctCount = count($distinctElements);
				$data_row[] = array('data'=>to_quantity($distinctCount), 'align' => 'center');

				$data_row[] = array('data'=>to_quantity($row['item_count']), 'align' => 'center');
				$data_row[] = array('data'=>to_currency($row['subtotal']), 'align' => 'right');
				$data_row[] =  array('data'=>to_currency($row['total']), 'align' => 'right');
				$data_row[] = array('data'=>to_currency($row['tax']), 'align' => 'right');
				if($this->has_profit_permission)
				{
					$data_row[] = array('data'=>to_currency($row['profit']), 'align' => 'right');
				}
		
				$tabular_data[] = $data_row;
			}

			$data = array(
				'view' => 'tabular',
				"title" => lang('reports_employees_summary_report'),
				"subtitle" => $subtitle,
				"headers" => $this->getDataColumns(),
				"data" => $tabular_data,
				"summary_data" => $summary_data,
				"export_excel" => $this->params['export_excel'],
				"pagination" => $this->pagination->create_links(),
			);
		}
		elseif($this->settings['display'] == 'graphical')
		{
			$graph_data = array();
			foreach($report_data as $row)
			{
				$graph_data[$row['employee']] = to_currency_no_money($row['total']);
			}
	
			$currency_symbol = $this->config->item('currency_symbol') ? $this->config->item('currency_symbol') : '$';

			$data = array(
				'view' => 'graphical',
				'graph' => 'bar',
				"title" => lang('reports_employees_summary_report'),
				"data" => $graph_data,
				"summary_data" => $summary_data,
				"tooltip_template" => "<%=label %>: ".((!$this->config->item('currency_symbol_location') || $this->config->item('currency_symbol_location') =='before') ? $currency_symbol : '')."<%= parseFloat(Math.round(value * 100) / 100).toFixed(".$this->decimals.") %>".($this->config->item('currency_symbol_location') =='after' ? $currency_symbol: ''),
			   "legend_template" => "<ul class=\"<%=name.toLowerCase()%>-legend\"><% for (var i=0; i<segments.length; i++){%><li><span style=\"background-color:<%=segments[i].fillColor%>\"></span><%if(segments[i].label){%><%=segments[i].label%> (".((!$this->config->item('currency_symbol_location') || $this->config->item('currency_symbol_location') =='before') ? $currency_symbol : '')."<%=parseFloat(Math.round(segments[i].value * 100) / 100).toFixed(".$this->decimals.")%>".($this->config->item('currency_symbol_location') =='after' ?  $currency_symbol : '').")<%}%></li><%}%></ul>"
			);	
		}
		
		return $data;
	}
	
	public function getDataColumns()
	{
		$columns = array();
		
		$columns[] = array('data'=>lang('reports_employee'), 'align'=> 'left');
		$columns[] = array('data'=>lang('reports_total_number_of_sales'), 'align'=> 'left');
		$columns[] = array('data'=>lang('reports_total_number_of_items'), 'align'=> 'left');
		$columns[] = array('data'=>lang('reports_subtotal'), 'align'=> 'right');
		$columns[] = array('data'=>lang('reports_total'), 'align'=> 'right');
		$columns[] = array('data'=>lang('common_tax'), 'align'=> 'right');

		if($this->has_profit_permission)
		{
			$columns[] = array('data'=>lang('common_profit'), 'align'=> 'right');
		}
		
		return $columns;		
	}
	
	public function getData()
	{
	    $location_ids = self::get_selected_location_ids();
	    $employee_column = $this->params['employee_type'] == 'logged_in_employee' ? 'employee_id' : 'sold_by_employee_id';

	    // For sales_items
	    $select_employee_column_items = $employee_column == 'employee_id' ? 'phppos_sales.employee_id' : 'COALESCE(phppos_sales_items.sold_by_employee_id_2, phppos_sales.sold_by_employee_id, phppos_sales.employee_id)';

	    $this->db->select(
	        "$select_employee_column_items as employee_id,
	        GROUP_CONCAT(DISTINCT phppos_sales_items.sale_id ) as count,
	        count(phppos_sales_items.item_id) as item_count,
	        CONCAT(phppos_people.first_name, ' ', phppos_people.last_name) as employee,
	        sum(phppos_sales_items.subtotal) as subtotal,
	        sum(phppos_sales_items.total) as total,
	        sum(phppos_sales_items.tax) as tax,
	        sum(phppos_sales_items.profit) as profit",
	        false
	    );
	    $this->db->from('sales_items');
	    $this->db->join('sales', 'sales.sale_id = sales_items.sale_id');
	    $this->db->join('employees', 'employees.person_id = '.$select_employee_column_items, 'left');
	    $this->db->join('people', 'employees.person_id = people.person_id', 'left');
	    $this->db->where_in('sales.location_id', $location_ids);
	    $this->sale_time_where();

	    if ($this->params['sale_type'] == 'sales') {
	        $this->db->where('sales.total_quantity_purchased > 0');
	    } elseif ($this->params['sale_type'] == 'returns') {
	        $this->db->where('sales.total_quantity_purchased < 0');
	    }

	    $this->db->where('sales.deleted', 0);
	    $this->db->group_by('employee_id');
	    $qry1 = $this->db->get_compiled_select();

	    // For sales_item_kits
	    $select_employee_column_kits = $employee_column == 'employee_id' ? 'phppos_sales.employee_id' : 'COALESCE(phppos_sales_item_kits.sold_by_employee_id_2, phppos_sales.sold_by_employee_id, phppos_sales.employee_id)';

	    $this->db->select(
	        "$select_employee_column_kits as employee_id,
	        GROUP_CONCAT(DISTINCT phppos_sales_item_kits.sale_id ) as count,
	        count(phppos_sales_item_kits.item_kit_id) as item_count,
	        CONCAT(phppos_people.first_name, ' ', phppos_people.last_name) as employee,
	        sum(phppos_sales_item_kits.subtotal) as subtotal,
	        sum(phppos_sales_item_kits.total) as total,
	        sum(phppos_sales_item_kits.tax) as tax,
	        sum(phppos_sales_item_kits.profit) as profit",
	        false
	    );
	    $this->db->from('sales_item_kits');
	    $this->db->join('sales', 'sales.sale_id = sales_item_kits.sale_id');
	    $this->db->join('employees', 'employees.person_id = '.$select_employee_column_kits, 'left');
	    $this->db->join('people', 'employees.person_id = people.person_id', 'left');
	    $this->db->where_in('sales.location_id', $location_ids);
	    $this->sale_time_where();

	    if ($this->params['sale_type'] == 'sales') {
	        $this->db->where('sales.total_quantity_purchased > 0');
	    } elseif ($this->params['sale_type'] == 'returns') {
	        $this->db->where('sales.total_quantity_purchased < 0');
	    }

	    $this->db->where('sales.deleted', 0);
	    $this->db->group_by('employee_id');
	    $qry2 = $this->db->get_compiled_select();

	    // Combine the two queries using UNION ALL
	    $final_query = "SELECT employee_id,
	        GROUP_CONCAT(count) as count,
	        sum(item_count) as item_count,
	        employee,
	        sum(subtotal) as subtotal,
	        sum(total) as total,
	        sum(tax) as tax,
	        sum(profit) as profit
	        FROM ($qry1 UNION ALL $qry2) as alias
	        WHERE employee IS NOT NULL
	        GROUP BY employee_id
	        ORDER BY employee";

	    if (isset($this->params['export_excel']) && !$this->params['export_excel']) 
	    {
	       $limit = $this->report_limit;
	       $offset = isset($this->params['offset']) ? $this->params['offset'] : 0;

	       $final_query .= " LIMIT $offset, $limit";
	    }    
	    $query = $this->db->query($final_query);

	    return $query->result_array();
	}
	
	function getTotalRows()
	{
	    $location_ids = self::get_selected_location_ids();
	    $employee_column = $this->params['employee_type'] == 'logged_in_employee' ? 'employee_id' : 'sold_by_employee_id';

	    // For sales_items
	    $select_employee_column_items = $employee_column == 'employee_id' ? 'phppos_sales.employee_id' : 'COALESCE(phppos_sales_items.sold_by_employee_id_2, phppos_sales.sold_by_employee_id, phppos_sales.employee_id)';

	    $this->db->select("$select_employee_column_items as employee_id");
	    $this->db->from('sales_items');
	    $this->db->join('sales', 'sales.sale_id = sales_items.sale_id');
	    $this->db->join('employees', 'employees.person_id = sales.employee_id');
	    $this->db->where_in('sales.location_id', $location_ids);
	    $this->sale_time_where();
	    if ($this->params['sale_type'] == 'sales') {
	        $this->db->where('sales.total_quantity_purchased > 0');
	    } elseif ($this->params['sale_type'] == 'returns') {
	        $this->db->where('sales.total_quantity_purchased < 0');
	    }
	    $this->db->where('sales.deleted', 0);

	    $qry1 = $this->db->get_compiled_select();

	    // For sales_item_kits
	    $select_employee_column_kits = $employee_column == 'employee_id' ? 'phppos_sales.employee_id' : 'COALESCE(phppos_sales_item_kits.sold_by_employee_id_2, phppos_sales.sold_by_employee_id, phppos_sales.employee_id)';

	    $this->db->select("$select_employee_column_kits as employee_id");
	    $this->db->from('sales_item_kits');
	    $this->db->join('sales', 'sales.sale_id = sales_item_kits.sale_id');
	    $this->db->join('employees', 'employees.person_id = sales.employee_id');
	    $this->db->where_in('sales.location_id', $location_ids);
	    $this->sale_time_where();
	    if ($this->params['sale_type'] == 'sales') {
	        $this->db->where('sales.total_quantity_purchased > 0');
	    } elseif ($this->params['sale_type'] == 'returns') {
	        $this->db->where('sales.total_quantity_purchased < 0');
	    }
	    $this->db->where('sales.deleted', 0);

	    $qry2 = $this->db->get_compiled_select();

	    // Combine the two queries using UNION
	    $this->db->reset_query(); // Clear any previous query
	    $combined_query = "
	        SELECT COUNT(DISTINCT employee_id) as employee_count
	        FROM (
	            ($qry1)
	            UNION
	            ($qry2)
	        ) as combined
	    ";

	    $result = $this->db->query($combined_query)->row_array();
	    return $result['employee_count'];
	}
	
	
	public function getSummaryData()
	{
	    $location_ids = self::get_selected_location_ids();
	    $employee_column = $this->params['employee_type'] == 'logged_in_employee' ? 'employee_id' : 'sold_by_employee_id';

	    // For sales_items
	    $select_employee_column_items = $employee_column == 'employee_id' ? 'sales.employee_id' : 'COALESCE(sales_items.sold_by_employee_id_2, sales.sold_by_employee_id, sales.employee_id)';

	    $this->db->select(
	        "sum(phppos_sales_items.subtotal) as subtotal,
	         sum(phppos_sales_items.total) as total,
	         sum(phppos_sales_items.tax) as tax,
	         sum(phppos_sales_items.profit) as profit",
	        false
	    );
	    $this->db->from('sales_items');
	    $this->db->join('sales', 'sales.sale_id = sales_items.sale_id');
	    $this->db->join('employees', 'employees.person_id = sales.employee_id');
	    $this->db->join('people', 'employees.person_id = people.person_id');
	    $this->db->where_in('sales.location_id', $location_ids);
	    $this->sale_time_where();

	    if ($this->params['sale_type'] == 'sales') {
	        $this->db->where('sales.total_quantity_purchased > 0');
	    } elseif ($this->params['sale_type'] == 'returns') {
	        $this->db->where('sales.total_quantity_purchased < 0');
	    }

	    $this->db->where('sales.deleted', 0);
	    $qry1 = $this->db->get_compiled_select();

	    // For sales_item_kits
	    $select_employee_column_kits = $employee_column == 'employee_id' ? 'sales.employee_id' : 'COALESCE(sales_item_kits.sold_by_employee_id_2, sales.sold_by_employee_id, sales.employee_id)';

	    $this->db->select(
	        "sum(phppos_sales_item_kits.subtotal) as subtotal,
	         sum(phppos_sales_item_kits.total) as total,
	         sum(phppos_sales_item_kits.tax) as tax,
	         sum(phppos_sales_item_kits.profit) as profit",
	        false
	    );
	    $this->db->from('sales_item_kits');
	    $this->db->join('sales', 'sales.sale_id = sales_item_kits.sale_id');
	    $this->db->join('employees', 'employees.person_id = sales.employee_id');
	    $this->db->join('people', 'employees.person_id = people.person_id');
	    $this->db->where_in('sales.location_id', $location_ids);
	    $this->sale_time_where();

	    if ($this->params['sale_type'] == 'sales') {
	        $this->db->where('sales.total_quantity_purchased > 0');
	    } elseif ($this->params['sale_type'] == 'returns') {
	        $this->db->where('sales.total_quantity_purchased < 0');
	    }

	    $this->db->where('sales.deleted', 0);
	    $qry2 = $this->db->get_compiled_select();

	    // Combine the two queries using UNION ALL and calculate summary data
	    $final_query = "SELECT 
	        sum(subtotal) as subtotal,
	        sum(total) as total,
	        sum(tax) as tax,
	        sum(profit) as profit
	        FROM ($qry1 UNION ALL $qry2) as alias";

	    $result = $this->db->query($final_query)->row_array();

	    $return = array(
	        'subtotal' => to_currency_no_money($result['subtotal'], 2),
	        'total' => to_currency_no_money($result['total'], 2),
	        'tax' => to_currency_no_money($result['tax'], 2),
	        'profit' => to_currency_no_money($result['profit'], 2),
	    );

	    if (!$this->has_profit_permission) {
	        unset($return['profit']);
	    }

	    return $return;
	}
}
?>