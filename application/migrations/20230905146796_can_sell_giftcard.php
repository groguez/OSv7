<?php
	defined('BASEPATH') OR exit('No direct script access allowed');
	class Migration_can_sell_giftcard extends MY_Migration 
	{

	    public function up() 
			{
				$this->execute_sql(realpath(dirname(__FILE__).'/'.'20230905146796_can_sell_giftcard.sql'));
	    }

	    public function down() 
			{
	    }

	}