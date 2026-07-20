<?php
	defined('BASEPATH') OR exit('No direct script access allowed');
	class Migration_customers_additional_phone_numbers extends MY_Migration 
	{

	    public function up() 
			{
				$this->execute_sql(realpath(dirname(__FILE__).'/'.'20230905146954_customers_additional_phone_numbers.sql'));
				
				$this->db->query(" 
				 					CREATE TRIGGER enforce_phone_format_on_update
				 					 BEFORE UPDATE ON phppos_customers_phone_numbers 
				 					 FOR EACH ROW BEGIN  
				 					      SET NEW.additional_phone_number = alphanumplus(NEW.additional_phone_number);
				 					  END;");

				 				$this->db->query(" 
				 					CREATE TRIGGER enforce_phone_format_on_insert
				 					 BEFORE INSERT ON phppos_customers_phone_numbers 
				 					 FOR EACH ROW BEGIN  
				 					      SET NEW.additional_phone_number = alphanumplus(NEW.additional_phone_number);
				 					END;");	    
	    }

	    public function down() 
			{
	    }

	}