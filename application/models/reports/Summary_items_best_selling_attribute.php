<?php
require_once("Report.php");
class Summary_items_best_selling_attribute extends Report
{
	function __construct()
	{
		$this->load->model('Item_variations');

		parent::__construct();
	}

	public function getDataColumns()
	{
		$columns = array();

		$columns[] = array('data' => lang('common_variation'), 'align' => 'left');
		$columns[] = array('data' => lang('reports_subtotal'), 'align' => 'right');
		$columns[] = array('data' => lang('common_tax'), 'align' => 'right');
		$columns[] = array('data' => lang('reports_total'), 'align' => 'right');
		$columns[] = array('data' => lang('reports_quantity_purchased'), 'align' => 'right');

		return $columns;
	}

	public function getInputData()
	{
		$this->load->model('Category');
		$input_params = array();

		$category_entity_data = array();
		$category_entity_data['specific_input_name'] = 'category_id';
		$category_entity_data['specific_input_label'] = lang('reports_category');
		$category_entity_data['view'] = 'specific_entity';

		$categories = array();
		$categories[''] = lang('common_all');

		$categories_phppos = $this->Category->sort_categories_and_sub_categories($this->Category->get_all_categories_and_sub_categories());

		foreach ($categories_phppos as $key => $value) {
			$name = $this->config->item('show_full_category_path') ? str_repeat('&nbsp;&nbsp;', $value['depth']) . $this->Category->get_full_path($key) : str_repeat('&nbsp;&nbsp;', $value['depth']) . $value['name'];
			$categories[$key] = $name;
		}

		$category_entity_data['specific_input_data'] = $categories;

		$input_data = Report::get_common_report_input_data(TRUE);

		$specific_entity_data['specific_input_name'] = 'supplier_id';
		$specific_entity_data['specific_input_label'] = lang('reports_supplier');
		$specific_entity_data['search_suggestion_url'] = site_url('reports/supplier_search');
		$specific_entity_data['view'] = 'specific_entity';

		if ($this->settings['display'] == 'tabular') {
			$input_params = array();

			$input_params[] = array('view' => 'date_range', 'with_time' => TRUE);
			$input_params[] = $specific_entity_data;
			$input_params[] = $category_entity_data;

			$this->load->model('Item_attribute');

			$input_params[] = array('view' => 'text', 'name' => 'attribute_value', 'label' => lang('reports_attribute_value'), 'default' => '');
			$input_params[] = array('view' => 'excel_export');
			$input_params[] = array('view' => 'locations');
			$input_params[] = array('view' => 'submit');
		}
		$input_data['input_report_title'] = lang('reports_report_options');
		$input_data['input_params'] = $input_params;
		return $input_data;
	}

	public function getOutputData()
	{
		$this->load->model('Category');

		$tabular_data = array();
		$report_data = $this->getData();
		$summary_data = $this->getSummaryData();

		if ($this->settings['display'] == 'tabular') {

			foreach ($report_data as $row) {

				$data_row = array();
				$data_row[] = array('data' => $row['item_variation_attribute_value_name'], 'align' => 'left');
				$data_row[] = array('data' => to_currency($row['subtotal']), 'align' => 'right');
				$data_row[] = array('data' => to_currency($row['tax']), 'align' => 'right');
				$data_row[] = array('data' => to_currency($row['total']), 'align' => 'right');
				$data_row[] = array('data' => to_quantity($row['quantity_purchased']), 'align' => 'right');

				$tabular_data[] = $data_row;
			}

			$data = array(
				"view" => 'tabular',
				"title" => lang('reports_items_best_selling_attribute'),
				"subtitle" => date(get_date_format(), strtotime($this->params['start_date'])) . '-' . date(get_date_format(), strtotime($this->params['end_date'])),
				"headers" => $this->getDataColumns(),
				"data" => $tabular_data,
				"summary_data" => $summary_data,
				"export_excel" => $this->params['export_excel'],
				"pagination" => ''
			);
		}
		return $data;
	}

	public function getData()
	{
		$this->load->model('Category');

		if ($this->params['category_id']) {
			if ($this->config->item('include_child_categories_when_searching_or_reporting')) {
				$category_ids = $this->Category->get_category_id_and_children_category_ids_for_category_id($this->params['category_id']);
			} else {
				$category_ids = array($this->params['category_id']);
			}
		}

		$this->db->select(
			'
			sales_items.item_variation_id as item_variation_id,
			attribute_values.name as item_variation_attribute_value_name,
			suppliers.company_name as supplier,
			categories.id as category_id,
			categories.name as category,
			sum(' . $this->db->dbprefix('sales_items') . '.quantity_purchased) as quantity_purchased,
			sum(' . $this->db->dbprefix('sales_items') . '.subtotal) as subtotal,
			sum(' . $this->db->dbprefix('sales_items') . '.total) as total,
			sum(' . $this->db->dbprefix('sales_items') . '.tax) as tax',
			false
		);

		$this->db->from('sales');
		$this->db->join('sales_items', 'sales_items.sale_id = sales.sale_id');
		$this->db->join('items', 'items.item_id = sales_items.item_id');
		$this->db->join('categories', 'categories.id = items.category_id', 'left');
		$this->db->join('suppliers', 'suppliers.person_id = items.supplier_id', 'left');
		$this->db->join('item_variations', 'item_variations.id = sales_items.item_variation_id');
		$this->db->join('item_variation_attribute_values', 'item_variation_attribute_values.item_variation_id = item_variations.id');
		$this->db->join('attribute_values', 'attribute_values.id = item_variation_attribute_values.attribute_value_id');

		$this->db->where('sales.deleted', 0);
		$this->sale_time_where();

		// sale_type
		$this->db->where('quantity_purchased > 0');

		if (isset($this->params['category_id']) && $this->params['category_id']) {
			$this->db->where_in('items.category_id', $category_ids);
		}

		if (isset($this->params['supplier_id']) && $this->params['supplier_id']) {
			$this->db->where('items.supplier_id', $this->params['supplier_id']);
		}

		if (isset($this->params['attribute_value']) && $this->params['attribute_value']) {
			$this->db->where("attribute_values.name ='".$this->params['attribute_value']."'");
		}

		$this->db->group_by('item_variation_attribute_value_name');
		$this->db->order_by('quantity_purchased DESC, item_variation_attribute_value_name ASC');
		$result = $this->db->get()->result_array();
		return $result;
	}


	public function getSummaryData()
	{
		return array();
	}
}
