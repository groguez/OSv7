<?php
class Auto_tier_range extends MY_Model
{
    function get_all()
    {
        $this->db->from('auto_tier_ranges');
        $this->db->order_by('min_sales');
        return $this->db->get();
    }

    function exists($id)
    {
        $this->db->from('auto_tier_ranges');
        $this->db->where('id', $id);
        return ($this->db->get()->num_rows() == 1);
    }

    function save(&$data, $id = false)
    {
        if (!$id || !$this->exists($id))
        {
            if ($this->db->insert('auto_tier_ranges', $data))
            {
                $data['id'] = $this->db->insert_id();
                return true;
            }
            return false;
        }

        $this->db->where('id', $id);
        return $this->db->update('auto_tier_ranges', $data);
    }

    function delete($id)
    {
        $this->db->where('id', $id);
        return $this->db->delete('auto_tier_ranges');
    }
}
?>
