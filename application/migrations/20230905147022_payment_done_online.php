<?php
	defined('BASEPATH') OR exit('No direct script access allowed');
	class Migration_payment_done_online extends MY_Migration 
	{

	    public function up() 
			{
				$this->execute_sql(realpath(dirname(__FILE__).'/'.'20230905147022_payment_done_online.sql'));
	    }

	    public function down() 
			{
	    }

	}