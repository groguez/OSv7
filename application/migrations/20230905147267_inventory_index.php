<?php
	defined('BASEPATH') OR exit('No direct script access allowed');
	class Migration_inventory_index extends MY_Migration 
	{

	    public function up() 
			{
				$this->execute_sql(realpath(dirname(__FILE__).'/'.'20230905147267_inventory_index.sql'));
	    }

	    public function down() 
			{
	    }

	}