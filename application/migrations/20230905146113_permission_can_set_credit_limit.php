<?php
	defined('BASEPATH') OR exit('No direct script access allowed');
	class Migration_permission_can_set_credit_limit extends MY_Migration 
	{

	    public function up() 
			{
				$this->execute_sql(realpath(dirname(__FILE__).'/'.'20230905146113_permission_can_set_credit_limit.sql'));
	    }

	    public function down() 
			{
	    }

	}