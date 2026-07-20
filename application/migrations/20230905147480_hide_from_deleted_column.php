<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Migration_hide_from_deleted_column extends MY_Migration
{
    public function up()
    {
        $this->execute_sql(realpath(dirname(__FILE__).'/'.'20230905147480_hide_from_deleted_column.sql'));
    }

    public function down()
    {
    }
}
