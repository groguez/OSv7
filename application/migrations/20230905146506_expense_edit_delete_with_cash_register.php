<?php
	defined('BASEPATH') OR exit('No direct script access allowed');
	class Migration_expense_edit_delete_with_cash_register extends MY_Migration 
	{

	    public function up() 
			{
				$this->execute_sql(realpath(dirname(__FILE__).'/'.'20230905146506_expense_edit_delete_with_cash_register.sql'));
	    }

	    public function down() 
			{
	    }

	}