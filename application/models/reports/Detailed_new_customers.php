<?php
require_once ("Report.php");
class Detailed_new_customers extends Report
{
	function __construct()
	{
		parent::__construct();
		$this->load->model('Tier');
		
	}
	
	public function getInputData()
	{
		
		$input_params = array();
			
		if ($this->settings['display'] == 'tabular')
		{
			$input_data = Report::get_common_report_input_data(TRUE);
			
			$input_params = array(
				array('view' => 'date_range', 'with_time' => TRUE),
				array('view' => 'excel_export'),
				array('view' => 'submit'),
			);
		}
		
		
		$input_data['input_report_title'] = lang('reports_report_options');
		$input_data['input_params'] = $input_params;
		return $input_data;
	}
	
	function getOutputData()
	{
		$do_compare = isset($this->params['compare_to']) && $this->params['compare_to'];		
		$subtitle = date(get_date_format(), strtotime($this->params['start_date'])) .'-'.date(get_date_format(), strtotime($this->params['end_date'])).($do_compare  ? ' '. lang('reports_compare_to'). ' '. date(get_date_format(), strtotime($this->params['start_date_compare'])) .'-'.date(get_date_format(), strtotime($this->params['end_date_compare'])) : '');

		$report_data = $this->getData();
		$tabular_data = array();
		foreach($report_data as $row)
		{
			$data_row = array();
		
			$data_row[] = array('data'=>$row['person_id'], 'align' => 'left');
			$data_row[] = array('data'=>date(get_date_format().' '.get_time_format(), strtotime($row['create_date'])), 'align' => 'left');
			$data_row[] = array('data'=>$row['first_name'].' '.$row['last_name'], 'align' => 'left');
			$data_row[] = array('data'=>$row['phone_number'], 'align' => 'left');
			$data_row[] = array('data'=>$row['email'], 'align' => 'left');
			$data_row[] = array('data'=>$row['address_1'], 'align' => 'left');
			$data_row[] = array('data'=>$row['address_2'], 'align' => 'left');
			$data_row[] = array('data'=>$row['city'], 'align' => 'left');
			$data_row[] = array('data'=>$row['state'], 'align' => 'left');
			$data_row[] = array('data'=>$row['zip'], 'align' => 'left');
			
			$tabular_data[] = $data_row;				
		}
			
 		$data = array(
			'view' => 'tabular',
			"title" => lang('reports_new_customers_report'),
			"subtitle" => $subtitle,
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
		$columns = array();
	
		$columns[] = array('data'=>lang('common_person_id'), 'align'=> 'left');
		$columns[] = array('data'=>lang('common_created_at'), 'align'=> 'left');
		$columns[] = array('data'=>lang('reports_customer'), 'align'=> 'left');
		$columns[] = array('data'=>lang('common_phone_number'), 'align'=> 'left');
		$columns[] = array('data'=>lang('common_email'), 'align'=> 'left');
		$columns[] = array('data'=>lang('common_address_1'), 'align'=> 'left');
		$columns[] = array('data'=>lang('common_address_2'), 'align'=> 'left');
		$columns[] = array('data'=>lang('common_city'), 'align'=> 'left');
		$columns[] = array('data'=>lang('common_state'), 'align'=> 'left');
		$columns[] = array('data'=>lang('common_zip'), 'align'=> 'left');
	
		return $columns;		
	}
	
	public function getData()
	{		
		$location_ids = Report::get_selected_location_ids();
		$location_ids = implode(',',$location_ids);
		
		$this->db->select('*', false);
		$this->db->from('customers');
		$this->db->join('people', 'people.person_id = customers.person_id');
		$where = "(location_id IN ($location_ids) or location_id is NULL) and create_date BETWEEN ".$this->db->escape($this->params['start_date']).' and '.$this->db->escape($this->params['end_date']);
		$this->db->where($where);
		return $this->db->get()->result_array();
	}
	
	
	function getTotalRows()
	{		
		return 1;
	}
	
	public function getSummaryData()
	{
		return array();
	}

}
?>