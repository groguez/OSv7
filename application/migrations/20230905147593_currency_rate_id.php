<?php
	defined('BASEPATH') OR exit('No direct script access allowed');
	class Migration_currency_rate_id extends MY_Migration 
	{

	    public function up() 
			{
				$this->execute_sql(realpath(dirname(__FILE__).'/'.'20230905147593_currency_rate_id.sql'));
	    }

	    public function down() 
			{
	    }

	}