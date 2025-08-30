<?php
	defined('BASEPATH') OR exit('No direct script access allowed');
	class Migration_sales_and_recv_created_at_and_updated_at extends MY_Migration 
	{

	    public function up() 
			{
				$this->execute_sql(realpath(dirname(__FILE__).'/'.'20230905146666_sales_and_recv_created_at_and_updated_at.sql'));
	    }

	    public function down() 
			{
	    }

	}