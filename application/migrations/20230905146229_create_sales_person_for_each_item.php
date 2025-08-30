<?php
	defined('BASEPATH') OR exit('No direct script access allowed');
	class Migration_create_sales_person_for_each_item extends MY_Migration 
	{

	    public function up() 
			{
				$this->execute_sql(realpath(dirname(__FILE__).'/'.'20230905146229_create_sales_person_for_each_item.sql'));
	    }

	    public function down() 
			{
	    }

	}