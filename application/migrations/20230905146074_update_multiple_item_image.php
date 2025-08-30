<?php
	defined('BASEPATH') OR exit('No direct script access allowed');
	class Migration_update_multiple_item_image extends MY_Migration 
	{

	    public function up() 
			{
				$this->execute_sql(realpath(dirname(__FILE__).'/'.'20230905146074_update_multiple_item_image.sql'));
	    }

	    public function down() 
			{
	    }

	}