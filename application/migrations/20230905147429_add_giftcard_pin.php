<?php
	defined('BASEPATH') OR exit('No direct script access allowed');
	class Migration_add_giftcard_pin extends MY_Migration 
	{

	    public function up() 
			{
				$this->execute_sql(realpath(dirname(__FILE__).'/'.'20230905147429_add_giftcard_pin.sql'));
	    }

	    public function down() 
			{
	    }

	}