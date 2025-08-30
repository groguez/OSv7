<?php
	defined('BASEPATH') OR exit('No direct script access allowed');
	class Migration_add_sales_item_kits_flat_discount_amount extends MY_Migration 
	{

	    public function up() 
			{
				$this->execute_sql(realpath(dirname(__FILE__).'/'.'20230905146345_add_sales_item_kits_flat_discount_amount.sql'));
	    }

	    public function down() 
			{
	    }

	}