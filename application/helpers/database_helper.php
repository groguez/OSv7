<?php
function create_and_execute_large_update_query_items($item_ids, $item_data)
{
	$CI =& get_instance();
	$items_table = $CI->db->dbprefix('items');
	
	$set_statements = array();
	foreach($item_data as $key=>$value)
	{
		$value = $CI->db->escape($value);
		$set_statements[] =" $key = $value "; 
	}
	$set_statements[] = ' last_modified = "'.date('Y-m-d H:i:s').'" ';
	
	$set = implode(',',$set_statements);	
	$in = implode(',',$item_ids);
	$query = "UPDATE $items_table SET $set WHERE item_id IN ($in)";
	return $CI->db->simple_query($query);
}

function create_and_execute_large_update_query_item_images_remove($item_ids = array(), $offset_id = 0)
{
	return TRUE;
}

function create_and_execute_large_update_query_items_main_image_set($item_ids, $image_id, $replace = 0)
{
	if (count($item_ids) > 0)
	{
		$CI =& get_instance();
		$items_table = $CI->db->dbprefix('items');

		$set_statements = array();
		$key = "main_image_id";
		$value = $image_id;
		$set_statements[] =" $key = $value "; 
		$set_statements[] = ' last_modified = "'.date('Y-m-d H:i:s').'" ';

		$set = implode(',',$set_statements);
		$in = implode(',',$item_ids);
		$query = "UPDATE $items_table SET $set WHERE item_id IN ($in) ". ($replace ? "" : " and main_image_id is null" );
		return $CI->db->simple_query($query);
	}
}

function create_and_execute_large_update_query_location_items($item_ids, $location_id, $item_location_data)
{
	$CI =& get_instance();
	$location_items_table = $CI->db->dbprefix('location_items');
	$items_table = $CI->db->dbprefix('items');
	
	$set_statements = array();
	foreach($item_location_data as $key=>$value)
	{
		$value = $CI->db->escape($value);
		$set_statements[] =" $key = $value "; 
	}
	
	$set = implode(',',$set_statements);
	$in = implode(',',$item_ids);
	
	$location_id = $CI->db->escape($location_id);
	
	$lm = date('Y-m-d H:i:s');
	$query = "UPDATE $items_table SET last_modified = '$lm' WHERE item_id IN ($in)";
	$CI->db->simple_query($query);
	
	$query = "UPDATE $location_items_table SET $set WHERE item_id IN ($in) and location_id=$location_id";
	return $CI->db->simple_query($query);
}

function create_and_execute_large_update_query_location_items_percent($item_ids, $cost_price_percent,$unit_price_percent,$promo_price_percent,$promo_price_use_selling_price = FALSE)
{
	$CI =& get_instance();
	$items_table = $CI->db->dbprefix('items');
		
	$set_statements = array();
	
	if ($cost_price_percent)
	{
		$set_statements[] = " cost_price = cost_price * (1+($cost_price_percent/100)) ";
	}

	if ($unit_price_percent)
	{
		$set_statements[] = " unit_price = unit_price * (1+($unit_price_percent/100)) ";
	}

	if ($promo_price_percent)
	{
		if ($promo_price_percent)
		{
			$set_statements[] = " promo_price = unit_price * (1+($promo_price_percent/100)) ";			
		}
		else
		{
			$set_statements[] = " promo_price = promo_price * (1+($promo_price_percent/100)) ";
		}
	}
	
	
	$set_statements[] = ' last_modified = "'.date('Y-m-d H:i:s').'" ';
	
	$set = implode(',',$set_statements);	
	$in = implode(',',$item_ids);
	$query = "UPDATE $items_table SET $set WHERE item_id IN ($in)";
	return $CI->db->simple_query($query);
}
?>