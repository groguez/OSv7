<?php
	defined('BASEPATH') OR exit('No direct script access allowed');
	class Migration_add_item_sales_limits_per_location extends MY_Migration 
	{

	    public function up() 
			{
				$this->execute_sql(realpath(dirname(__FILE__).'/'.'20230905146882_add_item_sales_limits_per_location.sql'));
	    }

	    public function down() 
			{
	    }

	}