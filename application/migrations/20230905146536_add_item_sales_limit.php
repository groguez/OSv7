<?php
	defined('BASEPATH') OR exit('No direct script access allowed');
	class Migration_add_item_sales_limit extends MY_Migration 
	{

	    public function up() 
			{
				$this->execute_sql(realpath(dirname(__FILE__).'/'.'20230905146536_add_item_sales_limit.sql'));
	    }

	    public function down() 
			{
	    }

	}