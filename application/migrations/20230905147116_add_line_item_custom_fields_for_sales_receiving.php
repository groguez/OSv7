<?php
	defined('BASEPATH') OR exit('No direct script access allowed');
	class Migration_add_line_item_custom_fields_for_sales_receiving extends MY_Migration 
	{

	    public function up() 
			{
				$this->execute_sql(realpath(dirname(__FILE__).'/'.'20230905147116_add_line_item_custom_fields_for_sales_receiving.sql'));
	    }

	    public function down() 
			{
	    }

	}