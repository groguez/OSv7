<?php
	defined('BASEPATH') OR exit('No direct script access allowed');
	class Migration_delete_zatca_integration extends MY_Migration 
	{

	    public function up() 
			{
				$this->execute_sql(realpath(dirname(__FILE__).'/'.'20230905146765_delete_zatca_integration.sql'));
	    }

	    public function down() 
			{
	    }

	}