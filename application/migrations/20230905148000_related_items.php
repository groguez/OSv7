<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Migration_related_items extends MY_Migration
{
    public function up()
    {
        $this->execute_sql(realpath(dirname(__FILE__).'/20230905148000_related_items.sql'));
    }

    public function down()
    {
    }
}
?>
