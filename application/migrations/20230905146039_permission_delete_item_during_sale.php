<?php
	defined('BASEPATH') OR exit('No direct script access allowed');
	class Migration_permission_delete_item_during_sale extends MY_Migration 
	{

	    public function up() 
			{
				$this->execute_sql(realpath(dirname(__FILE__).'/'.'20230905146039_permission_delete_item_during_sale.sql'));
	    }

	    public function down() 
			{
	    }

	}