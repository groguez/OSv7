<?php
        defined('BASEPATH') OR exit('No direct script access allowed');
        class Migration_include_child_categories_default extends MY_Migration
        {

            public function up()
                        {
                                $this->execute_sql(realpath(dirname(__FILE__).'/'.'20230905147500_include_child_categories_default.sql'));
            }

            public function down()
                        {
            }

        }
?>
