<?php
require_once("Report.php");
class Inventory_aging extends Report
{
	function __construct()
	{
		parent::__construct();
		$this->load->model('Tier');
	}

	public function getInputData()
	{
		$input_data = Report::get_common_report_input_data(TRUE);
		$this->load->model('Category');
		$this->load->model('Supplier');

		$supplier_entity_data = array();
		$supplier_entity_data['specific_input_name'] = 'supplier_id';
		$supplier_entity_data['specific_input_label'] = lang('reports_supplier');
		$supplier_entity_data['view'] = 'specific_entity';

		$suppliers = array();

		$suppliers[] = lang('common_all');
		foreach ($this->Supplier->get_all()->result() as $supplier) {
			$suppliers[$supplier->person_id] = $supplier->company_name . ' (' . $supplier->first_name . ' ' . $supplier->last_name . ')';
		}

		$supplier_entity_data['specific_input_data'] = $suppliers;

		$category_entity_data = array();
		$category_entity_data['specific_input_name'] = 'category_id';
		$category_entity_data['specific_input_label'] = lang('reports_category');
		$category_entity_data['view'] = 'specific_entity';

		$categories = array();
		$categories[] = lang('common_all');

		$categories_phppos = $this->Category->sort_categories_and_sub_categories($this->Category->get_all_categories_and_sub_categories());

		foreach ($categories_phppos as $key => $value) {
			$name = $this->config->item('show_full_category_path') ? str_repeat('&nbsp;&nbsp;', $value['depth']) . $this->Category->get_full_path($key) : str_repeat('&nbsp;&nbsp;', $value['depth']) . $value['name'];
			$categories[$key] = $name;
		}

		$category_entity_data['specific_input_data'] = $categories;

		$input_params = array();

		if ($this->settings['display'] == 'tabular') {

			$input_params = array(
				array('view' => 'date_range', 'with_time' => TRUE),
				$supplier_entity_data,
				$category_entity_data,
				array('view' => 'locations', 'can_view_inventory_at_all_locations' => $this->Employee->has_module_action_permission('reports', 'view_inventory_at_all_locations', $this->Employee->get_logged_in_employee_info()->person_id)),
				array('view' => 'submit'),
			);
		}

		$input_data['input_report_title'] = lang('reports_report_options');
		$input_data['input_params'] = $input_params;
		return $input_data;
	}

	function getOutputData()
	{
		$this->load->model('Sale');
		$this->load->model('Category');

		$this->setupDefaultPagination();

		$headers = $this->getDataColumns();

		$report_data = $this->getData();
		$location_count = $this->Location->count_all();
		$summary_data = array();

		foreach ($report_data as $key => $row) {
			$summary_data_row = array();

			$summary_data_row[] = array(
				'data' => anchor(
					'items/view/' . $row['item_id'],
					'<i class="ion-document-text"></i> <div>' . $row['item_id'] . '</div>',
					array('target' => '_blank', 'class' => 'hidden-print', 'style' => "display: flex; justify-content: center; align-items: center;")
				),
				'align' => 'left',
				'detail_id' => $row['trans_id']
			);
			$summary_data_row[] = array('data' => $row['item_name'], 'align' => 'left');

			if ($location_count > 1) {
				$summary_data_row[] = array('data' => $row['location_name'], 'align' => 'left');
			}

			$summary_data_row[] = array('data' => $row['category_name'], 'align' => 'left');
			$summary_data_row[] = array('data' => $row['supplier_name'], 'align' => 'left');
			$summary_data_row[] = array('data' => $row['item_number'], 'align' => 'left');
			$summary_data_row[] = array('data' => $row['item_product_id'], 'align' => 'left');
			$summary_data_row[] = array('data' => date(get_date_format() . '-' . get_time_format(), strtotime($row['trans_date'])), 'align' => 'left');
			$summary_data_row[] = array('data' => to_quantity($row['trans_current_quantity']), 'align' => 'left');
			$summary_data_row[] = array('data' => round_to_nearest_05($row['total_aged_length'] / $row['total_aged_qty']), 'align' => 'left');
			
			$summary_data[$key] = $summary_data_row;
		}

		$data = array(
			'view' => 'tabular_details_lazy_load',
			"title" => lang('reports_inventory_aging'),
			"subtitle" => date(get_date_format(), strtotime($this->params['start_date'])) . '-' . date(get_date_format(), strtotime($this->params['end_date'])),
			"headers" => $this->getDataColumns(),
			"summary_data" => $summary_data,
			"overall_summary_data" => $this->getSummaryData(),
			"pagination" => $this->pagination->create_links(),
			"export_excel" => 0,
			"report_model" => get_class($this),
		);

		return $data;
	}


	public function getDataColumns()
	{
		$return = array();

		$return['summary'] = array();
		$location_count = $this->Location->count_all();

		$return['summary'][] = array('data' => lang('common_item_id'), 'align' => 'left');
		$return['summary'][] = array('data' => lang('common_item_name'), 'align' => 'left');
		if ($location_count > 1) {
			$return['summary'][] = array('data' => lang('common_location'), 'align' => 'left');
		}
		$return['summary'][] = array('data' => lang('common_category'), 'align' => 'left');
		$return['summary'][] = array('data' => lang('common_supplier'), 'align' => 'left');
		$return['summary'][] = array('data' => lang('common_item_number'), 'align' => 'left');
		$return['summary'][] = array('data' => lang('common_product_id'), 'align' => 'left');
		$return['summary'][] = array('data' => lang('reports_inventory_last_updated_date'), 'align' => 'left');
		$return['summary'][] = array('data' => lang('common_quantity'), 'align' => 'left');
		$return['summary'][] = array('data' => lang('reports_inventory_aged_length'), 'align' => 'left');

		$return['details'] = $this->get_details_data_columns_sales();
		return $return;
	}

	public function get_aged_length($inventory_item)
	{
		$start_date = strtotime($this->params['start_date'] ? $this->params['start_date'] : "now");

		$top_history_item = $inventory_item;
		$trans_current_quantity = $top_history_item['trans_current_quantity'];
		$last_trans_id = $top_history_item['trans_id'];
		$receive_history_list = array();
		$age_history_list = array();

		$return_add_list = array();
		$history_cnt = 0;
		do {
			for ($rIndex = 0; $rIndex < count($return_add_list); $rIndex++) {
				if ($return_add_list[$rIndex]['trans_id'] > $top_history_item['trans_id']) {
					$trans_current_quantity += $return_add_list[$rIndex]['trans_inventory'];
					array_splice($return_add_list, $rIndex, 1);
					$rIndex--;
				}
			}

			if ($top_history_item['trans_inventory'] > 0) {

				if (strpos($top_history_item['trans_comment'], $this->config->item('sale_prefix')) > -1) {
					// sales 
					// todo add to receiving ...
					$_aa = explode(' ', $top_history_item['trans_comment']);
					$sale_id = end($_aa);
					if ($sale_id > 0) {
						$this->load->model('Sale');
						$sale_info = $this->Sale->get_info($sale_id)->row();
						if ($sale_info->return_sale_id > 0) { //return sales
							// $return_sale_info = $this->Sale->get_info($sale_info->return_sale_id);
							$trans_comment = $this->config->item('sale_prefix') . ' ' . $sale_info->return_sale_id;

							$this->db->select('inventory.*', false);
							$this->db->from('inventory');
							$this->db->where('trans_items', isset($top_history_item['item_id']) ? $top_history_item['item_id'] : $top_history_item['trans_items']);
							$this->db->where('item_variation_id', $top_history_item['item_variation_id']);
							$this->db->where('trans_comment', $trans_comment);
							$this->db->order_by('trans_id', 'desc');
							$this->db->limit(1);
							$return_item = $this->db->get()->row();
							$return_add_list[] = array(
								'trans_id' => $return_item->trans_id,
								'trans_inventory' => $top_history_item['trans_inventory']
							);
							$trans_current_quantity -= $top_history_item['trans_inventory'];
						}
					}
				} else {
					// receiving or sale

					$remaining_inventory = 0;
					if ($top_history_item['trans_inventory'] > $trans_current_quantity) {
						$remaining_inventory =  $trans_current_quantity;
					} else {
						$remaining_inventory =  $top_history_item['trans_inventory'];
					}

					$aged_length = ($start_date - strtotime($top_history_item['trans_date'])) / (60 * 60 * 24);
					if ($aged_length < 0) $aged_length = 0;

					$age_history_item = array(
						'trans_id' => $top_history_item['trans_id'],
						'trans_inventory' => $top_history_item['trans_inventory'],
						'remaining_inventory' => $remaining_inventory,
						'trans_date' => $top_history_item['trans_date'],
						'trans_comment' => $top_history_item['trans_comment'],
						'aged_length' => $aged_length
					);
					array_push($age_history_list, $age_history_item);
					$trans_current_quantity -= $top_history_item['trans_inventory'];
					if ($trans_current_quantity <= 0) {
						break;
					}
				}
			}

			if (count($receive_history_list) == 0) {

				$this->db->select('inventory.*', false);
				$this->db->from('inventory');
				$this->db->where('trans_id <', $last_trans_id);
				$this->db->where('trans_inventory >', 0);
				$this->db->where('trans_items', isset($top_history_item['item_id']) ? $top_history_item['item_id'] : $top_history_item['trans_items']);
				$this->db->where('item_variation_id', $top_history_item['item_variation_id']);
				$this->db->order_by('trans_id', 'desc');
				$this->db->limit(10);
				$this->db->offset($history_cnt);
				$receive_history_list = $this->db->get()->result_array();
				$history_cnt ++;
			}

			if (count($receive_history_list) == 0) {
				break;
			}

			$top_history_item = array_shift($receive_history_list);
		} while ($trans_current_quantity > 0);

		$total_aged_length = 0;
		$total_aged_qty = 0;
		foreach ($age_history_list as $age_history_item) {
			$total_aged_length += $age_history_item['remaining_inventory'] * $age_history_item['aged_length'];
			$total_aged_qty += $age_history_item['remaining_inventory'];
		}

		return array(
			'total_aged_length' => $total_aged_length,
			'total_aged_qty' => $total_aged_qty,
			'age_history_list' => $age_history_list
		);
	}
	public function getData()
	{
		// Subquery to get the max trans_id for each trans_items
		$subquery = $this->db->select('MAX(trans_id) AS latest_trans_id, trans_items, item_variation_id')
			->from('phppos_inventory')
			->where('trans_current_quantity >', 0)
			->group_by('trans_items, item_variation_id')
			->get_compiled_select();

		$this->db->select('inventory.*, inventory.item_variation_id as item_variation_id, items.item_id as item_id, locations.name as location_name, items.item_number, items.product_id as item_product_id, concat(' . $this->db->dbprefix('items') . '.name, " : ", IFNULL(' . $this->db->dbprefix('item_variations') . '.name, "")) as item_name, categories.name as category_name, suppliers.company_name as supplier_name', false);
		$this->db->from('inventory');
		$this->db->join('items', 'inventory.trans_items = items.item_id');
		$this->db->join('item_variations', 'inventory.item_variation_id = item_variations.id', 'left');
		$this->db->join('categories', 'items.category_id = categories.id', 'left');
		$this->db->join('suppliers', 'items.supplier_id = suppliers.person_id', 'left');
		$this->db->join('locations', 'inventory.location_id = locations.location_id');
		$this->db->join("($subquery) as latest", 'inventory.trans_id = latest.latest_trans_id and inventory.trans_items = latest.trans_items AND (
		`phppos_inventory`.`item_variation_id` = `latest`.`item_variation_id` OR(
            `phppos_inventory`.`item_variation_id` IS NULL AND `latest`.`item_variation_id` IS NULL)
		)');

		$location_ids = self::get_selected_location_ids();
		if (count($location_ids) > 0) {
			$this->db->where_in('inventory.location_id', $location_ids);
		}

		if (isset($this->params['supplier_id']) && $this->params['supplier_id']) {
			$this->db->where('items.supplier_id', $this->params['supplier_id']);
		}
		if (isset($this->params['category_id']) && $this->params['category_id']) {
			$this->db->where('items.category_id', $this->params['category_id']);
		}
		if (isset($this->params['end_date']) && $this->params['end_date']) {
			$this->db->where('inventory.trans_date <=', $this->params['end_date']);
		}

		$this->db->group_by('inventory.trans_items, inventory.item_variation_id');

		$this->db->limit($this->report_limit);
		// $this->db->limit(10);
		if (isset($this->params['offset'])) {
			$this->db->offset($this->params['offset']);
		}
		$ret = $this->db->get()->result_array();
		// echo $this->db->last_query(); exit;
		foreach ($ret as $r_k=> &$item) {
			$ret_age = $this->get_aged_length($item);
			$item['total_aged_length'] = $ret_age['total_aged_length'];
			$item['total_aged_qty'] = $ret_age['total_aged_qty'];
		}
		return $ret;
	}

	public function getTotalRows()
	{
		$subquery = $this->db->select('MAX(trans_id) AS latest_trans_id, trans_items, item_variation_id')
			->from('phppos_inventory')
			->where('trans_current_quantity >', 0)
			->group_by('trans_items, item_variation_id')
			->get_compiled_select();

		$this->db->select('inventory.trans_current_quantity, inventory.trans_items', false);
		$this->db->from('inventory');
		$this->db->join('items', 'inventory.trans_items = items.item_id');
		$this->db->join('item_variations', 'inventory.item_variation_id = item_variations.id', 'left');
		$this->db->join('categories', 'items.category_id = categories.id', 'left');
		$this->db->join('suppliers', 'items.supplier_id = suppliers.person_id', 'left');
		$this->db->join('locations', 'inventory.location_id = locations.location_id');
		$this->db->join("($subquery) as latest", 'inventory.trans_id = latest.latest_trans_id and inventory.trans_items = latest.trans_items AND (
		`phppos_inventory`.`item_variation_id` = `latest`.`item_variation_id` OR(
            `phppos_inventory`.`item_variation_id` IS NULL AND `latest`.`item_variation_id` IS NULL)
		)');

		$location_ids = self::get_selected_location_ids();
		if (count($location_ids) > 0) {
			$this->db->where_in('inventory.location_id', $location_ids);
		}
		if (isset($this->params['supplier_id']) && $this->params['supplier_id']) {
			$this->db->where('items.supplier_id', $this->params['supplier_id']);
		}
		if (isset($this->params['category_id']) && $this->params['category_id']) {
			$this->db->where('items.category_id', $this->params['category_id']);
		}
		if (isset($this->params['end_date']) && $this->params['end_date']) {
			$this->db->where('inventory.trans_date <=', $this->params['end_date']);
		}
		$this->db->group_by('inventory.trans_items, inventory.item_variation_id');

		return $this->db->count_all_results();
	}

	public function getSummaryData()
	{
		return array();
	}


	function get_details_data_columns_sales()
	{
		$details = array();
		$details[] = array('data' => lang('reports_inventory_receiving_date'), 'align' => 'left');
		$details[] = array('data' => lang('common_comments'), 'align' => 'left');
		$details[] = array('data' => lang('reports_inventory_receiving_qty'), 'align' => 'left');
		$details[] = array('data' => lang('reports_inventory_remaining_qty'), 'align' => 'left');
		$details[] = array('data' => lang('reports_inventory_aged_length'), 'align' => 'left');

		return $details;
	}

	function get_report_details($ids, $export_excel = 0)
	{
		$this->db->select('inventory.trans_id, inventory.trans_current_quantity, inventory.trans_date, inventory.trans_comment, inventory.item_variation_id as item_variation_id, inventory.trans_inventory, items.item_id as item_id, locations.name as location_name, items.item_number, items.product_id as item_product_id, concat(' . $this->db->dbprefix('items') . '.name, " ", IFNULL(' . $this->db->dbprefix('item_variations') . '.name, "")) as item_name, categories.name as category_name, suppliers.company_name as supplier_name', false);
		$this->db->from('inventory');
		$this->db->join('items', 'inventory.trans_items = items.item_id', 'left');
		$this->db->join('item_variations', 'inventory.item_variation_id = item_variations.id', 'left');
		$this->db->join('categories', 'items.category_id = categories.id', 'left');
		$this->db->join('suppliers', 'items.supplier_id = suppliers.person_id', 'left');
		$this->db->join('locations', 'inventory.location_id = locations.location_id');
		$location_ids = self::get_selected_location_ids();
		if (count($location_ids) > 0) {
			$this->db->where_in('inventory.location_id', $location_ids);
		}
		if (isset($this->params['supplier_id']) && $this->params['supplier_id']) {
			$this->db->where('items.supplier_id', $this->params['supplier_id']);
		}
		if (isset($this->params['category_id']) && $this->params['category_id']) {
			$this->db->where('items.category_id', $this->params['category_id']);
		}
		if (isset($this->params['end_date']) && $this->params['end_date']) {
			$this->db->where('inventory.trans_date <=', $this->params['end_date']);
		}

		if (!empty($ids)) {
			$inventory_ids_chunk = array_chunk($ids, 25);
			$this->db->group_start();
			foreach ($inventory_ids_chunk as $inventory_ids) {
				$this->db->or_where_in('inventory.trans_id', $inventory_ids);
			}
			$this->db->group_end();
		} else {
			$this->db->where('1', '2', FALSE);
		}

		$this->db->order_by('inventory.trans_date', 'desc');
		$this->db->order_by('items.name', 'asc');
		$this->db->group_by('inventory.trans_items, inventory.item_variation_id');

		$res = $this->db->get()->result_array();

		$details_data = array();
		$i = 0;
		foreach ($res as $key => &$drow) {
			$ret_age_info = $this->get_aged_length($drow);
			$ret_age_list = $ret_age_info['age_history_list'];
			foreach ($ret_age_list as $ret_age_item) {
				$details_data_row = array();
				$details_data_row[] = array('data' => $ret_age_item['trans_date'], 'align' => 'left');
				$details_data_row[] = array('data' => $ret_age_item['trans_comment'], 'align' => 'left');
				$details_data_row[] = array('data' => round_to_nearest_05($ret_age_item['trans_inventory']), 'align' => 'left');
				$details_data_row[] = array('data' => round_to_nearest_05($ret_age_item['remaining_inventory']), 'align' => 'left');
				$details_data_row[] = array('data' => round_to_nearest_05($ret_age_item['aged_length']), 'align' => 'left');
				$details_data[$i][$drow['trans_id']] = $details_data_row;
				$i++;
			}
		}


		$data = array(
			"headers" => $this->getDataColumns(),
			"details_data" => $details_data
		);

		return $data;
	}
}

