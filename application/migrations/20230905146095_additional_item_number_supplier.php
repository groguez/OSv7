<?php
	defined('BASEPATH') OR exit('No direct script access allowed');
	class Migration_additional_item_number_supplier extends MY_Migration 
	{

	    public function up() 
			{
				$this->execute_sql(realpath(dirname(__FILE__).'/'.'20230905146095_additional_item_number_supplier.sql'));
	    }

	    public function down() 
			{
	    }

	}