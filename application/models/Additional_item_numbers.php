<?php
class Additional_item_numbers extends MY_Model
{
	/*
	Returns all the item numbers for a given item
	*/
	function get_item_numbers($item_id)
	{
		$this->db->from('additional_item_numbers');
		$this->db->where('item_id',$item_id);
		$this->db->where('item_variation_id',NULL);
		return $this->db->get();
	}
	
	/*
	Returns all the item numbers for item and variation for a given item
	*/
	function get_item_numbers_for_variation($item_id,$item_variation_id)
	{
		$this->db->from('additional_item_numbers');
		$this->db->where('item_id',$item_id);
		$this->db->where('item_variation_id',$item_variation_id);
		return $this->db->get();
	}
	
	
	function get_all()
	{
		$this->db->from('additional_item_numbers');
		$this->db->where('item_variation_id',NULL);
		
		$return = array();
		
		foreach($this->db->get()->result_array() as $result)
		{
			$return[$result['item_id']][] = $result['item_number'];
		}
		
		return $return;
	}
	
	function save_variation($item_id,$variation_id,$additional_item_numbers)
	{
		$this->db->trans_start();

		$this->db->delete('additional_item_numbers', array('item_id' => $item_id,'item_variation_id' => $variation_id));
		
		foreach($additional_item_numbers as $item_number)
		{
			if ($item_number!='')
			{
				$this->db->insert('additional_item_numbers', array('item_id' => $item_id,'item_variation_id' => $variation_id, 'item_number' => $item_number));
			}
		}
		
		$this->db->trans_complete();
		
		return $this->db->trans_status();
		
	}
	
	function get_item_for_supplier($item_id,$supplier_id)
	{
		$this->db->from('additional_item_numbers');
		$this->db->where('item_id',$item_id);
		$this->db->where('supplier_id',$supplier_id);
		$row = $this->db->get()->row_array();
		
		return isset($row['item_number']) ? $row['item_number'] : NULL;
	}
	
	function save($item_id, $additional_item_numbers,$additional_item_numbers_suppliers = array())
	{
		$this->db->trans_start();

		$this->db->delete('additional_item_numbers', array('item_id' => $item_id,'item_variation_id' => NULL));
		
		for($k=0;$k<count($additional_item_numbers);$k++)
		{
			$item_number = $additional_item_numbers[$k];
			$supplier_id = isset($additional_item_numbers_suppliers[$k]) && $additional_item_numbers_suppliers[$k] && $additional_item_numbers_suppliers[$k]!=-1 ? $additional_item_numbers_suppliers[$k] : NULL;
			if ($item_number!='')
			{
				$this->db->insert('additional_item_numbers', array('item_id' => $item_id, 'item_number' => $item_number,'supplier_id' => $supplier_id));
			}
		}
		$this->db->trans_complete();
		
		return $this->db->trans_status();
	}
	
	function delete($item_id)
	{
		return $this->db->delete('additional_item_numbers', array('item_id' => $item_id,'item_variation_id' => NULL));
	}
	
	function delete_variation($item_id,$item_variation_id)
	{
		return $this->db->delete('additional_item_numbers', array('item_id' => $item_id,'item_variation_id' => $item_variation_id));		
	}
	
	function cleanup()
	{
		return TRUE;
	}
	
	function get_item_id($item_number)
	{
		$this->db->from('additional_item_numbers');
		$this->db->where('item_number',$item_number);

		$query = $this->db->get();

		if($query->num_rows() >= 1)
		{
			$row = $query->row();
			$return = $row->item_id;
			
			if ($row->item_variation_id)
			{
				$return.='#'.$row->item_variation_id;
			}
			
			return $return;
		}
		
		return FALSE;
	}
}
?>
