<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Migration_location_settings extends MY_Migration
{
    public function up()
    {
        $this->execute_sql(realpath(dirname(__FILE__).'/20230905148101_location_settings.sql'));
    }

    public function down()
    {
    }
}
?>
