<?php
class Item_related extends MY_Model
{
    function get_related_items($item_id)
    {
        $this->db->from('related_items');
        $this->db->where('item_id', $item_id);
        $this->db->order_by('sort_order');
        return $this->db->get();
    }

    function save($related_items, $item_id)
    {
        $this->db->trans_start();
        $this->delete($item_id);
        $sort = 0;
        foreach($related_items as $related_item_id)
        {
            $this->db->insert('related_items', array(
                'item_id' => $item_id,
                'related_item_id' => $related_item_id,
                'sort_order' => $sort++
            ));
        }
        $this->db->trans_complete();
        return true;
    }

    function delete($item_id)
    {
        $this->db->delete('related_items', array('item_id' => $item_id));
    }
}
?>
