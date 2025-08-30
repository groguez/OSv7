<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Migration_auto_tier_ranges extends MY_Migration
{
    public function up()
    {
        $this->execute_sql(realpath(dirname(__FILE__).'/'.'20230905147694_auto_tier_ranges.sql'));
    }

    public function down()
    {
    }
}
?>
