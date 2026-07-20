<?php
	defined('BASEPATH') OR exit('No direct script access allowed');
	class Migration_datacap_device_override_per_register extends MY_Migration 
	{

	    public function up() 
			{
				$this->execute_sql(realpath(dirname(__FILE__).'/'.'20230905146201_datacap_device_override_per_register.sql'));
	    }

	    public function down() 
			{
	    }

	}