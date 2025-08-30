<?php
	defined('BASEPATH') OR exit('No direct script access allowed');
	class Migration_make_serial_number_field_larger extends MY_Migration 
	{

	    public function up() 
			{
				$this->execute_sql(realpath(dirname(__FILE__).'/'.'20230905146157_make_serial_number_field_larger.sql'));
	    }

	    public function down() 
			{
	    }

	}