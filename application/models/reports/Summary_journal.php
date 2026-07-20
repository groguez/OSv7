<?php
require_once ("Report.php");
class Summary_journal extends Report
{
	function __construct()
	{
		parent::__construct();
		$this->load->model('Category');
		$this->load->model('Sale');
	}
	
	
	public function getInputData()
	{	
		$input_data = Report::get_common_report_input_data(TRUE);
			
		$category_entity_data = array();
		$category_entity_data['specific_input_name'] = 'category_id';
		$category_entity_data['specific_input_label'] = lang('reports_category');
		$category_entity_data['view'] = 'specific_entity';
		
		$categories = array();
		$categories[''] =lang('common_all');
		
		$categories_phppos = $this->Category->sort_categories_and_sub_categories($this->Category->get_all_categories_and_sub_categories());
		foreach($categories_phppos as $key=>$value)
		{
			$name = $this->config->item('show_full_category_path') ? str_repeat('&nbsp;&nbsp;', $value['depth']).$this->Category->get_full_path($key) : str_repeat('&nbsp;&nbsp;', $value['depth']).$value['name'];
			$categories[$key] = $name;
		}
		
		$category_entity_data['specific_input_data'] = $categories;
		$input_params = array();
		
		$input_params[] = array('view' => 'date_range', 'with_time' => TRUE);
		$input_params[] = array('view' => 'dropdown','dropdown_label' =>lang('reports_sale_type'),'dropdown_name' => 'sale_type','dropdown_options' =>array('all' => lang('reports_all'), 'sales' => lang('reports_sales'), 'returns' => lang('reports_returns')),'dropdown_selected_value' => 'all');
		$input_params[] = $category_entity_data;
		$input_params[] = array('view' => 'checkbox', 'checkbox_label' => lang('reports_show_top_level_categories_only'), 'checkbox_name' => 'show_top_level_categories');
		$input_params[] = array('view' => 'locations');
		$input_params[] = array('view' => 'excel_export');
		$input_params[] = array('view' => 'submit');
		
		$input_data['input_report_title'] = lang('reports_report_options');
		$input_data['input_params'] = $input_params;
		return $input_data;
	}
	
	function getOutputData()
	{
		
		$subtitle = date(get_date_format(), strtotime($this->params['start_date'])) .'-'.date(get_date_format(), strtotime($this->params['end_date']));

		$report_data = $this->getData();
				
		$summary_data = $this->getSummaryData();
		
		$this->setupDefaultPagination();
		$tabular_data = array();

		$index = 0;
		
		foreach($report_data as $row)
		{
			$data_row = array();
			foreach($row as $cell)
			{
				$data_row[] = array('data'=>$cell, 'align' => 'left');
			}
			$tabular_data[] = $data_row;				
		}
		
			
		$data = array(
			'view' => 'tabular',
			"title" => lang('reports_summary_journal'),
			"subtitle" => $subtitle,
			"headers" => $this->getDataColumns(),
			"data" => $tabular_data,
			"summary_data" => $summary_data,
			"export_excel" => $this->params['export_excel'],
			"pagination" => $this->pagination->create_links(),
		);
			
		
		return $data;
	}
	
	public function getDataColumns()
	{
		$payment_options = array_keys($this->Sale->get_payment_options_with_language_keys());
		$columns = array();
		
		$columns[] = array('data'=>lang('reports_date'), 'align'=> 'left');
		
		foreach($payment_options as $payment_option)
		{
			$columns[] = array('data'=>$payment_option, 'align'=> 'left');
		}
		
		$filtered_categories = $this->filter_categories_by_selected();
		foreach($filtered_categories as $cat_row)
		{
			$columns[] = array('data'=>$this->Category->get_full_path($cat_row['id']), 'align'=> 'left');
		}
		
		$columns[] = array('data'=>lang('common_tax'), 'align'=> 'left');
		
		return $columns;		
	}
	
	
	function get_sale_ids_for_payments()
	{
		$sale_ids = array();
		$location_ids = self::get_selected_location_ids();
		$location_ids_string = implode(',',$location_ids);
		
		$this->db->select('sales_payments.sale_id');
		$this->db->distinct();
		$this->db->from('sales_payments');
		$this->db->join('sales', 'sales.sale_id=sales_payments.sale_id');
		$this->db->where('payment_date BETWEEN '. $this->db->escape($this->params['start_date']). ' and '. $this->db->escape($this->params['end_date']).' and location_id IN('.$location_ids_string.')');
		
		foreach($this->db->get()->result_array() as $sale_row)
		{
			 $sale_ids[] = $sale_row['sale_id'];
		}
		
		return $sale_ids;
	}
	
	public function getCategoryData()
	{
		$category_ids = [];
		if (!empty($this->params['category_id'])) {
			$category_ids = [$this->params['category_id']];
			if ($this->params['show_top_level_categories'] ?? false) {
				$category_ids = $this->Category->get_category_id_and_children_category_ids_for_category_id($this->params['category_id']);
			}
		}
		if (!isset($this->params['show_top_level_categories']) || !$this->params['show_top_level_categories']) {
			$this->db->select('date(phppos_sales.sale_time) as sale_date,items.category_id, categories.name as category , sum('.$this->db->dbprefix('sales_items').'.subtotal) as subtotal, sum('.$this->db->dbprefix('sales_items').'.total) as total, sum('.$this->db->dbprefix('sales_items').'.tax) as tax, sum('.$this->db->dbprefix('sales_items').'.profit) as profit, sum('.$this->db->dbprefix('sales_items').'.quantity_purchased) as item_sold', false);
		} else {
			// When not including child categories, join the parent category (if any)
			// and use COALESCE to select the parent category if available.
			$this->db->select("DATE(phppos_sales.sale_time) AS sale_date, COALESCE(parent_categories.id, phppos_categories.id) AS category_id, COALESCE(parent_categories.name, phppos_categories.name) AS main_category, SUM(".$this->db->dbprefix('sales_items').".subtotal) AS subtotal, SUM(".$this->db->dbprefix('sales_items').".total) AS total, SUM(".$this->db->dbprefix('sales_items').".tax) AS tax, SUM(".$this->db->dbprefix('sales_items').".profit) AS profit, SUM(".$this->db->dbprefix('sales_items').".quantity_purchased) AS item_sold", false);
		}
		
		$this->db->from('sales_items');
		$this->db->join('sales', 'sales.sale_id = sales_items.sale_id');
		$this->db->join('price_tiers', 'sales.tier_id = price_tiers.id', 'left');
		$this->db->join('items', 'sales_items.item_id = items.item_id');
		$this->db->join('categories', 'categories.id = items.category_id');

		if (isset($this->params['show_top_level_categories']) && $this->params['show_top_level_categories']) {
			$this->db->join('categories as parent_categories', 'parent_categories.id = categories.parent_id', 'left');
		}
		
		$this->sale_time_where();
		$this->db->where('sales.deleted', 0);
		
		
		if ($this->config->item('hide_store_account_payments_from_report_totals'))
		{
			$this->db->where('sales.store_account_payment', 0);
		}

		if ($this->params['sale_type'] == 'sales')
		{
			$this->db->where('quantity_purchased > 0');
		}
		elseif ($this->params['sale_type'] == 'returns')
		{
			$this->db->where('quantity_purchased < 0');
		}
		if($this->params['category_id'])
		{
			$this->db->where_in('items.category_id', $category_ids);
		}
		
		$this->db->group_by('DATE(phppos_sales.sale_time),items.category_id');
		
		
		$items= $this->db->get()->result_array();	

		if (!isset($this->params['show_top_level_categories']) || !$this->params['show_top_level_categories']) {
			$this->db->select('date(phppos_sales.sale_time) as sale_date,item_kits.category_id, categories.name as category , sum('.$this->db->dbprefix('sales_item_kits').'.subtotal) as subtotal, sum('.$this->db->dbprefix('sales_item_kits').'.total) as total, sum('.$this->db->dbprefix('sales_item_kits').'.tax) as tax, sum('.$this->db->dbprefix('sales_item_kits').'.profit) as profit, sum('.$this->db->dbprefix('sales_item_kits').'.quantity_purchased) as item_sold', false);
		} else {
			// When not including child categories, join the parent category (if any)
			// and use COALESCE to select the parent category if available.
			$this->db->select("DATE(phppos_sales.sale_time) AS sale_date, COALESCE(parent_categories.id, phppos_categories.id) AS category_id, COALESCE(parent_categories.name, phppos_categories.name) AS main_category, SUM(".$this->db->dbprefix('sales_item_kits').".subtotal) AS subtotal, SUM(".$this->db->dbprefix('sales_item_kits').".total) AS total, SUM(".$this->db->dbprefix('sales_item_kits').".tax) AS tax, SUM(".$this->db->dbprefix('sales_item_kits').".profit) AS profit, SUM(".$this->db->dbprefix('sales_item_kits').".quantity_purchased) AS item_sold", false);
		}
		
		$this->db->from('sales_item_kits');
		$this->db->join('sales', 'sales.sale_id = sales_item_kits.sale_id');
		$this->db->join('price_tiers', 'sales.tier_id = price_tiers.id', 'left');
		$this->db->join('item_kits', 'sales_item_kits.item_kit_id = item_kits.item_kit_id');
		$this->db->join('categories', 'categories.id = item_kits.category_id');		
		if (isset($this->params['show_top_level_categories']) && $this->params['show_top_level_categories']) {
			$this->db->join('categories as parent_categories', 'parent_categories.id = categories.parent_id', 'left');
		}
		$this->sale_time_where();
		$this->db->where('sales.deleted', 0);
		if ($this->config->item('hide_store_account_payments_from_report_totals'))
		{
			$this->db->where('sales.store_account_payment', 0);
		}		
		
		if ($this->params['sale_type'] == 'sales')
		{
			$this->db->where('quantity_purchased > 0');
		}
		elseif ($this->params['sale_type'] == 'returns')
		{
			$this->db->where('quantity_purchased < 0');
		}

		if ($this->params['category_id'])
		{
			$this->db->where_in('item_kits.category_id', $category_ids);
		}	
		$this->db->group_by('item_kits.category_id');

		$item_kits = $this->db->get()->result_array();
		$items_and_kits = $this->merge_item_and_item_kits($items, $item_kits);
		
		return $items_and_kits;
		
	}
	
	private function merge_item_and_item_kits($items, $item_kits)
	{
		$location_ids = self::get_selected_location_ids();
		$merged = array();

		// Process items and item kits in a single loop
		foreach (array_merge($items, $item_kits) as $entry) {
			$sale_date = $entry['sale_date'];
			$category_id = $entry['category_id'];

			if (!isset($merged[$sale_date][$category_id])) {
				$merged[$sale_date][$category_id] = $entry;
			} else {
				$merged[$sale_date][$category_id]['subtotal'] += $entry['subtotal'];
				$merged[$sale_date][$category_id]['total'] += $entry['total'];
				$merged[$sale_date][$category_id]['tax'] += $entry['tax'];
				$merged[$sale_date][$category_id]['profit'] += $entry['profit'];
				$merged[$sale_date][$category_id]['item_sold'] += $entry['item_sold'];
			}
		}

		return $merged;
	}
	
	
	public function getPaymentsData()
	{
		$category_ids = [];
		if (!empty($this->params['category_id'])) {
			$category_ids = [$this->params['category_id']];
			if ($this->params['show_top_level_categories'] ?? false) {
				$category_ids = $this->Category->get_category_id_and_children_category_ids_for_category_id($this->params['category_id']);
			}
		}

		$location_ids = self::get_selected_location_ids();
		$location_ids_string = implode(',',$location_ids);
		$sale_ids_for_payments = $this->get_sale_ids_for_payments();
		
		$sales_totals = array();
		
		$this->db->select('sale_id, SUM(total) as total', false);
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
			$sales_totals[$sale_total_row['sale_id']] = to_currency_no_money($sale_total_row['total'], 2);
		}
		$this->db->select('sales_payments.sale_id, SUBSTRING_INDEX(phppos_sales_payments.payment_type, ":", 1) as payment_type, payment_amount, payment_id', false);
		$this->db->from('sales_payments');
		$this->db->join('sales', 'sales.sale_id=sales_payments.sale_id');
		if($this->params['category_id'])
		{
			$this->db->join('sales_items', 'sales.sale_id = sales_items.sale_id', 'left');
			$this->db->join('items', 'sales_items.item_id = items.item_id', 'left');
			$this->db->join('item_kits', 'sales_items.item_id = item_kits.item_kit_id', 'left');
		}
		$this->db->where('payment_date BETWEEN '. $this->db->escape($this->params['start_date']). ' and '. $this->db->escape($this->params['end_date']).' and location_id IN('.$location_ids_string.')');
		
		if ($this->config->item('hide_store_account_payments_in_reports'))
		{
			$this->db->where('store_account_payment',0);
		}
		
		if ($this->params['sale_type'] == 'sales')
		{
			$this->db->where('payment_amount > 0');
		}
		elseif ($this->params['sale_type'] == 'returns')
		{
			$this->db->where('payment_amount < 0');
		}
		
		if(isset($this->params['category_id']) && $this->params['category_id'] !== '')
		{
			$this->db->where_in('items.category_id', $category_ids);
		}
		$this->db->where($this->db->dbprefix('sales').'.deleted', 0);
		$this->db->order_by('sale_id, payment_date, payment_type');
				
		$sales_payments = $this->db->get()->result_array();
		
		$payments_by_sale = array();
		foreach($sales_payments as $row)
		{
        	$payments_by_sale[$row['sale_id']][] = $row;
		}
		
		$this->load->model('Sale');
		$payment_data = $this->Sale->get_payment_data_grouped_by_day($payments_by_sale,$sales_totals);
		
		
		return $payment_data;
	}
	
	
	public function getData()
	{	
		$payment_options = array_keys($this->Sale->get_payment_options_with_language_keys());
		
		$payment_data = $this->getPaymentsData();
		$category_data = $this->getCategoryData();
		$tax_data = $this->getTaxData();
		
		$return = array();
		
		foreach(array_keys($category_data) as $date)
		{
			$row = array();
			$row[] = date(get_date_format(), strtotime($date));
					
			foreach($payment_options as $payment_option)
			{
				$row[] = to_currency(isset($payment_data[$date][$payment_option]) ? $payment_data[$date][$payment_option] : 0);
			}
		
			$filtered_categories = $this->filter_categories_by_selected();
			foreach($filtered_categories as $cat_row)
			{
				$row[] = to_currency(isset($category_data[$date][$cat_row['id']]['subtotal']) ? $category_data[$date][$cat_row['id']]['subtotal'] : 0);
			}
		
			$row[] = to_currency(isset($tax_data[$date]) ? $tax_data[$date] : 0);
			
			$return[] = $row;
			
		}
		
		return $return;
	}
	
	public function getSummaryData()
	{
		$category_ids = [];
		if (!empty($this->params['category_id'])) {
			$category_ids = [$this->params['category_id']];
			if ($this->params['show_top_level_categories'] ?? false) {
				$category_ids = $this->Category->get_category_id_and_children_category_ids_for_category_id($this->params['category_id']);
			}
		}
		$this->db->select('sum('.$this->db->dbprefix('sales').'.subtotal) as subtotal, sum('.$this->db->dbprefix('sales').'.total) as total, sum('.$this->db->dbprefix('sales').'.tax) as tax, sum('.$this->db->dbprefix('sales').'.profit) as profit', false);
		$this->db->from('sales');
		if(isset($this->params['category_id']) && $this->params['category_id'] !== '') {
			$this->db->join('sales_items', 'sales.sale_id = sales_items.sale_id', 'left');
			$this->db->join('items', 'sales_items.item_id = items.item_id', 'left');
			$this->db->join('item_kits', 'sales_items.item_id = item_kits.item_kit_id', 'left');
		}
				
		if ($this->params['sale_type'] == 'sales')
		{
			$this->db->where('sales.total_quantity_purchased > 0');
		}
		elseif ($this->params['sale_type'] == 'returns')
		{
			$this->db->where('sales.total_quantity_purchased < 0');
		}
				
		if ($this->config->item('hide_store_account_payments_from_report_totals'))
		{
			$this->db->where('sales.store_account_payment', 0);
		}

		if(isset($this->params['category_id']) && $this->params['category_id'] !== '')
		{
			$this->db->where_in('items.category_id', $category_ids);
		}

		//If we are exporting NOT exporting to excel make sure to use offset and limit
		if (isset($this->params['export_excel']) && !$this->params['export_excel'])
		{
			$this->db->limit($this->report_limit);
			if (isset($this->params['offset']))
			{
				$this->db->offset($this->params['offset']);
			}
		}
		
		
		$this->sale_time_where();
		$this->db->where('sales.deleted', 0);
		
		$return = array(
			'subtotal' => 0,
			'total' => 0,
			'tax' => 0,
			'profit' => 0,
		);

		foreach($this->db->get()->result_array() as $row)
		{
			$return['subtotal'] += to_currency_no_money($row['subtotal'],2);
			$return['total'] += to_currency_no_money($row['total'],2);
			$return['tax'] += to_currency_no_money($row['tax'],2);
			$return['profit'] += to_currency_no_money($row['profit'],2);
		}
		
		if(!$this->has_profit_permission)
		{
			unset($return['profit']);
		}
		return $return;
	}
	
	function getTotalRows()
	{
		return 1;
	}
	
	function getTaxData()
	{
		$this->db->select('date(sale_time) as sale_date,sum(tax) as tax',false);
		$this->db->from('sales');
				
		if ($this->params['sale_type'] == 'sales')
		{
			$this->db->where('sales.total_quantity_purchased > 0');
		}
		elseif ($this->params['sale_type'] == 'returns')
		{
			$this->db->where('sales.total_quantity_purchased < 0');
		}
				
		if ($this->config->item('hide_store_account_payments_from_report_totals'))
		{
			$this->db->where('sales.store_account_payment', 0);
		}
		
		
		$this->sale_time_where();
		$this->db->where('deleted', 0);
		$this->db->group_by('date(sale_time)');		
		
		$return = array();
		
		foreach($this->db->get()->result_array() as $row)
		{
			$return[$row['sale_date']] = $row['tax'];
		}
		
		return $return;
	}

	/**
	 * Filters categories based on the selected category ID and its children (if configured).
	 *
	 * @return array An array of filtered categories that match the selected category ID and its children (if applicable).
	 */
	private function filter_categories_by_selected(): array
	{
		$categories = $this->Category->get_all_categories_including_children();
		$show_top_level = !empty($this->params['show_top_level_categories']);
	
		if ($show_top_level) {
			$categories = array_filter($categories, function ($category) {
				return is_null($category['parent_id']);
			});
		}
		
		if (!empty($this->params['category_id'])) {
			$selected_category_id = $this->params['category_id'];
			if($show_top_level) {
				$root_category_id   = $this->Category->get_root_parent_category_id($selected_category_id);
			} else {
				$root_category_id = $selected_category_id;
			}
			
			if (!empty($root_category_id)) {
				$filter_category_ids = [$root_category_id];
			} else {
				$filter_category_ids = $show_top_level
					? $this->Category->get_category_id_and_children_category_ids_for_category_id($selected_category_id)
					: [$root_category_id];
			}

			$categories = array_filter($categories, function ($category) use ($filter_category_ids) {
				return in_array($category['id'], $filter_category_ids, true);
			});

		}

		return $categories;
	}
}
?>