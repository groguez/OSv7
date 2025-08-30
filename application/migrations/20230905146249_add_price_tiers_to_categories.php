<?php
	defined('BASEPATH') OR exit('No direct script access allowed');
	class Migration_add_price_tiers_to_categories extends MY_Migration 
	{

	    public function up() 
			{
				$this->execute_sql(realpath(dirname(__FILE__).'/'.'20230905146249_add_price_tiers_to_categories.sql'));
	    }

	    public function down() 
			{
	    }

	}