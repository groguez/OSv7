<?php
	defined('BASEPATH') OR exit('No direct script access allowed');
	class Migration_valor extends MY_Migration 
	{

	    public function up() 
			{
				$this->execute_sql(realpath(dirname(__FILE__).'/'.'20230905146363_valor.sql'));
	    }

	    public function down() 
			{
	    }

	}