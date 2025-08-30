<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Migration_ecommerce_product_lookup_order extends MY_Migration
{
    public function up()
    {
        $item_lookup_order = unserialize($this->config->item('item_lookup_order'));
        if(!in_array('ecommerce_product_id',$item_lookup_order))
        {
            $item_lookup_order[] = 'ecommerce_product_id';
            $this->Appconfig->save('item_lookup_order',serialize($item_lookup_order));
        }
    }

    public function down()
    {
    }
}
