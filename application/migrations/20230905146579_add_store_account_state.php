<?php
	defined('BASEPATH') OR exit('No direct script access allowed');
	class Migration_add_store_account_state extends MY_Migration 
	{

	    public function up() 
			{
				$this->execute_sql(realpath(dirname(__FILE__).'/'.'20230905146579_add_store_account_state.sql'));
	    }

	    public function down() 
			{
	    }

	}