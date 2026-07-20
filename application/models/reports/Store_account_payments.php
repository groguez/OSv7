<?php
require_once("Report.php");
class Store_account_payments extends Report
{
	public $header_columns = array();

	function __construct()
	{
		parent::__construct();
	}

	public function getInputData()
	{
		$input_params = array();

		$exchange_data = array();
		$exchange_data['view'] = 'specific_entity';

		if ($this->settings['display'] == 'tabular') {
			$input_data = Report::get_common_report_input_data(TRUE);

			$input_params = array(
				array('view' => 'date_range', 'with_time' => TRUE),
				array('view' => 'excel_export'),
				array('view' => 'locations'),
				array('view' => 'submit'),
			);
		} elseif ($this->settings['display'] == 'graphical') {
			$input_data = Report::get_common_report_input_data(FALSE);
			$input_params = array(
				array('view' => 'date_range', 'with_time' => TRUE),
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
		$this->setupDefaultPagination();
		$report_data = $this->getData();
		$summary_data = $this->getSummaryData();

		if ($this->settings['display'] == 'tabular') {
			$start_date = $this->params['start_date'];
			$end_date = $this->params['end_date'];

			$export_excel = $this->params['export_excel'];

			$tabular_data = array();
			foreach ($report_data as $k_date => $row) {
				$tabular_data_row = array();
				foreach ($this->header_columns as $k_index => $header_column) {
					if ($k_index == 0) {
						$tabular_data_row[] = array(
							'data' => date(get_date_format(), strtotime($k_date)),
							'align' => $header_column['align']
						);
					} else {
						$tabular_data_row[] = array(
							'data' => $row[$header_column['data_field']][$header_column['index']],
							'align' => $header_column['align']
						);
					}
				}
				$tabular_data[] = $tabular_data_row;
			}
			$data = array(
				"view" => 'tabular',
				"title" => lang('reports_payments_summary_report'),
				"subtitle" => date(get_date_format(), strtotime($start_date)) . '-' . date(get_date_format(), strtotime($end_date)),
				"headers" => $this->getDataColumns(),
				"data" => $tabular_data,
				"summary_data" => $summary_data,
				"export_excel" => $export_excel,
				"pagination" => $this->pagination->create_links(),
			);
		} elseif ($this->settings['display'] == 'graphical') {
			$data = array(
				"view" => 'graphical',
				"title" => lang('reports_payments_summary_report'),
				"summary_data" => $summary_data,
			);
		}
		return $data;
	}

	public function getDataColumns()
	{
		return $this->header_columns;
	}

	public function getData()
	{

		$location_ids = self::get_selected_location_ids();
		$location_ids_string = implode(',', $location_ids);

		$this->db->select(
			'
			SUBSTRING_INDEX(phppos_sales_payments.payment_type, ":", 1) as payment_type,
			SUM(
				phppos_sales_payments.payment_amount
			) AS total_payment_amount,
			DATE(
				phppos_sales_payments.payment_date
			) AS grouped_date,
			sales.store_account_payment
			',
			false
		);
		$this->db->from('sales_payments');
		$this->db->join('sales', 'sales.sale_id=sales_payments.sale_id', 'left');
		$this->db->where('date(phppos_sales_payments.payment_date) BETWEEN ' . $this->db->escape($this->params['start_date']) . ' and ' . $this->db->escape($this->params['end_date']));
		$this->db->where('sales.location_id IN(' . $location_ids_string . ')');
		$this->db->where('sales.deleted', 0);
		$this->db->group_by('grouped_date,  phppos_sales_payments.payment_type, phppos_sales.store_account_payment');
		$this->db->order_by('grouped_date asc, phppos_sales_payments.payment_type asc');

		$sales_payments = $this->db->get()->result_array();

		$store_account_payments_data = array();

		$payment_types = $this->Sale->get_payment_options(NULL);
		$payment_type[lang('common_total_money_that_came_in')] = lang('common_total_money_that_came_in');

		$payments_dates = array();
		foreach ($sales_payments as $row) {
			if (!in_array($row['grouped_date'], $payments_dates)) {
				$payments_dates[] = $row['grouped_date'];
			}
		}
		sort($payments_dates);
		foreach ($payments_dates as $payments_date) {
			foreach ($payment_types as $payment_type) {
				$store_account_payments_data[$payments_date][$payment_type][0] = to_currency(0);
				$store_account_payments_data[$payments_date][$payment_type][1] = to_currency(0);
			}
		}

		foreach ($sales_payments as $row) {
			$store_account_payments_data[$row['grouped_date']][$row['payment_type']][$row['store_account_payment']] = to_currency($row['total_payment_amount']);

			if (!isset($store_account_payments_data[$row['grouped_date']][lang('common_total_money_that_came_in')])) {
				$store_account_payments_data[$row['grouped_date']][lang('common_total_money_that_came_in')][0] = 0;
			}

			if ($row['payment_type'] == lang('common_store_account')) {
				$store_account_payments_data[$row['grouped_date']][lang('common_total_money_that_came_in')][0] -= $row['total_payment_amount'];
			} else {
				$store_account_payments_data[$row['grouped_date']][lang('common_total_money_that_came_in')][0] += $row['total_payment_amount'];
			}
		}
		
		$store_account_payments_data[$row['grouped_date']][lang('common_total_money_that_came_in')][0] = to_currency($store_account_payments_data[$row['grouped_date']][lang('common_total_money_that_came_in')][0]);
		return $store_account_payments_data;
	}

	function getTotalRows()
	{
		$this->load->model('Sale');
		$location_ids = self::get_selected_location_ids();
		$location_ids_string = implode(',', $location_ids);

		$this->db->select(
			'
			SUBSTRING_INDEX(phppos_sales_payments.payment_type, ":", 1) as payment_type,
			SUM(
				phppos_sales_payments.payment_amount
			) AS total_payment_amount,
			DATE(
				phppos_sales_payments.payment_date
			) AS grouped_date,
			sales.store_account_payment
			',
			false
		);
		$this->db->from('sales_payments');
		$this->db->join('sales', 'sales.sale_id=sales_payments.sale_id', 'left');
		$this->db->where('date(phppos_sales_payments.payment_date) BETWEEN ' . $this->db->escape($this->params['start_date']) . ' and ' . $this->db->escape($this->params['end_date']));
		$this->db->where('sales.location_id IN(' . $location_ids_string . ')');
		$this->db->where('sales.deleted', 0);
		$this->db->group_by('grouped_date,  phppos_sales_payments.payment_type, phppos_sales.store_account_payment');
		$sales_payments = $this->db->get()->result_array();
		return count($sales_payments);
	}

	public function getSummaryData()
	{
		$this->load->model('Sale');
		$location_ids = self::get_selected_location_ids();
		$location_ids_string = implode(',', $location_ids);

		$this->db->select(
			'
			SUBSTRING_INDEX(phppos_sales_payments.payment_type, ":", 1) as payment_type,
			SUM(
				phppos_sales_payments.payment_amount
			) AS total_payment_amount,
			sales.store_account_payment
			',
			false
		);
		$this->db->from('sales_payments');
		$this->db->join('sales', 'sales.sale_id=sales_payments.sale_id', 'left');
		$this->db->where('date(phppos_sales_payments.payment_date) BETWEEN ' . $this->db->escape($this->params['start_date']) . ' and ' . $this->db->escape($this->params['end_date']));
		$this->db->where('sales.location_id IN(' . $location_ids_string . ')');
		$this->db->where('sales.deleted', 0);
		$this->db->group_by('phppos_sales_payments.payment_type, phppos_sales.store_account_payment');

		$sales_payments = $this->db->get()->result_array();

		$store_account_payments_summary_data = array();

		$payment_types = $this->Sale->get_payment_options(NULL);
		$payment_types[lang('common_total_money_that_came_in')] = lang('common_total_money_that_came_in');

		/* header column - start */
		$columns = array();
		$column = array(
			'data' => lang('reports_date'),
			'align' => 'center',
			'data_field' => 'grouped_date'
		);
		$columns[] = $column;

		foreach ($payment_types as $k => $payment_type) {
			$column_1 = array(
				'data' => ($payment_type == lang('common_total_money_that_came_in')) ? $payment_type : $payment_type . ' (' . lang('common_sales') . ')',
				'align' => 'left',
				'data_field' => $payment_type,
				'index' => 0,
			);
			if ($k == lang('common_store_account') || $k == lang('common_total_money_that_came_in')) {
				$column_1['align'] = 'right';
			}
			$columns[] = $column_1;

			if ($column_1['data_field'] != lang('common_store_account') && $column_1['data_field'] != lang('common_total_money_that_came_in')) {
				$column_2 = $column_1;
				$column_2['data'] = $column_2['data_field']." (".lang('common_store_account_payment').")";
				$column_2['index'] = 1;
				$columns[] = $column_2;
			}
			
		}
		$this->header_columns = $columns;
		/* header column - end */


		foreach ($payment_types as $payment_type) {
			$store_account_payments_summary_data[$payment_type][0] = 0;
			if ($payment_type != lang('common_store_account')) {
				$store_account_payments_summary_data[$payment_type][1] = 0;
			}
		}

		$sub_total = 0;
		foreach ($sales_payments as $row) {
			$store_account_payments_summary_data[$row['payment_type']][$row['store_account_payment']] = $row['total_payment_amount'];
			if ($row['payment_type'] == lang('common_store_account')) {
				$sub_total -= $row['total_payment_amount'];
			} else {
				$sub_total += $row['total_payment_amount'];
			}
		}

		foreach ($payment_types as $payment_type) {
			if ($payment_type != lang('common_store_account')) {
				$store_account_payments_summary_data[$payment_type] = to_currency($store_account_payments_summary_data[$payment_type][0]) . ' / ' . to_currency($store_account_payments_summary_data[$payment_type][1]);
			} else {
				$store_account_payments_summary_data[$payment_type] = to_currency($store_account_payments_summary_data[$payment_type][0]);
			}
		}
		$store_account_payments_summary_data[lang('common_total_money_that_came_in')] = to_currency($sub_total);
		return $store_account_payments_summary_data;
	}
}
