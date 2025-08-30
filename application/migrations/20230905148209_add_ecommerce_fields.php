<?php
	defined('BASEPATH') OR exit('No direct script access allowed');
	class Migration_add_ecommerce_fields extends MY_Migration 
	{

	    public function up() 
			{
				$this->execute_sql(realpath(dirname(__FILE__).'/'.'20230905148209_add_ecommerce_fields.sql'));
	    }

	    public function down() 
			{
	    }

	}