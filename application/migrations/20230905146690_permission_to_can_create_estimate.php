<?php
	defined('BASEPATH') OR exit('No direct script access allowed');
	class Migration_permission_to_can_create_estimate extends MY_Migration 
	{

	    public function up() 
			{
				$this->execute_sql(realpath(dirname(__FILE__).'/'.'20230905146690_permission_to_can_create_estimate.sql'));
	    }

	    public function down() 
			{
	    }

	}