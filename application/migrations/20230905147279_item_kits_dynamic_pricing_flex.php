<?php
	defined('BASEPATH') OR exit('No direct script access allowed');
	class Migration_item_kits_dynamic_pricing_flex extends MY_Migration 
	{

	    public function up() 
			{
				$this->execute_sql(realpath(dirname(__FILE__).'/'.'20230905147279_item_kits_dynamic_pricing_flex.sql'));
	    }

	    public function down() 
			{
	    }

	}