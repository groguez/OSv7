<?php
	defined('BASEPATH') OR exit('No direct script access allowed');
	class Migration_add_recurring_payments_store_account_option extends MY_Migration 
	{

	    public function up() 
			{
				$this->execute_sql(realpath(dirname(__FILE__).'/'.'20230905147171_add_recurring_payments_store_account_option.sql'));
	    }

	    public function down() 
			{
	    }

	}