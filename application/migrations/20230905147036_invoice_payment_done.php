<?php
	defined('BASEPATH') OR exit('No direct script access allowed');
	class Migration_invoice_payment_done extends MY_Migration 
	{

	    public function up() 
			{
				$this->execute_sql(realpath(dirname(__FILE__).'/'.'20230905147036_invoice_payment_done.sql'));
	    }

	    public function down() 
			{
	    }

	}