<?php
	defined('BASEPATH') OR exit('No direct script access allowed');
	class Migration_shopify_variation_fix extends MY_Migration 
	{

	    public function up() 
			{
				$this->execute_sql(realpath(dirname(__FILE__).'/'.'20230905146455_shopify_variation_fix.sql'));
	    }

	    public function down() 
			{
	    }

	}