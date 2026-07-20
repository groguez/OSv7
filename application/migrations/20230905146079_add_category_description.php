<?php
	defined('BASEPATH') OR exit('No direct script access allowed');
	class Migration_add_category_description extends MY_Migration 
	{

	    public function up() 
			{
				$this->execute_sql(realpath(dirname(__FILE__).'/'.'20230905146079_add_category_description.sql'));
	    }

	    public function down() 
			{
	    }

	}