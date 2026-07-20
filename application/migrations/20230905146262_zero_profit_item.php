<?php
	defined('BASEPATH') OR exit('No direct script access allowed');
	class Migration_zero_profit_item extends MY_Migration 
	{

	    public function up() 
			{
				$this->execute_sql(realpath(dirname(__FILE__).'/'.'20230905146262_zero_profit_item.sql'));
	    }

	    public function down() 
			{
	    }

	}