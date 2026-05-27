<?php
class Employee_subscription extends CI_Model 
{
    /*
    Determina si un empleado tiene una suscripción activa o trial válido
    */
    function has_active_subscription($employee_id)
    {
        $this->db->from('employee_subscriptions');
        $this->db->where('employee_id', $employee_id);
        $this->db->where('status IN (\'active\', \'trial\')');
        $this->db->where('(end_date IS NULL OR end_date >= CURDATE())');
        $query = $this->db->get();
        
        return ($query->num_rows() == 1);
    }
    
    /*
    Obtiene información de la suscripción de un empleado
    */
    function get_subscription_info($employee_id)
    {
        $this->db->from('employee_subscriptions');
        $this->db->where('employee_id', $employee_id);
        $query = $this->db->get();
        
        if ($query->num_rows() == 1)
        {
            return $query->row();
        }
        
        return FALSE;
    }
    
    /*
    Guarda o actualiza la suscripción de un empleado
    */
    function save_subscription($employee_id, $subscription_data)
    {
        $this->db->where('employee_id', $employee_id);
        $query = $this->db->get('employee_subscriptions');
        
        if ($query->num_rows() == 1)
        {
            // Actualizar existente
            $this->db->where('employee_id', $employee_id);
            return $this->db->update('employee_subscriptions', $subscription_data);
        }
        else
        {
            // Crear nueva
            $subscription_data['employee_id'] = $employee_id;
            return $this->db->insert('employee_subscriptions', $subscription_data);
        }
    }
    
    /*
    Elimina la suscripción de un empleado
    */
    function delete_subscription($employee_id)
    {
        return $this->db->delete('employee_subscriptions', array('employee_id' => $employee_id));
    }
    
    /*
    Verifica si la suscripción está en período de trial
    */
    function is_in_trial($employee_id)
    {
        $this->db->from('employee_subscriptions');
        $this->db->where('employee_id', $employee_id);
        $this->db->where('subscription_type', 'trial');
        $this->db->where('(end_date IS NULL OR end_date >= CURDATE())');
        $query = $this->db->get();
        
        return ($query->num_rows() == 1);
    }
    
    /*
    Verifica si el trial ha expirado
    */
    function is_trial_over($employee_id)
    {
        $this->db->from('employee_subscriptions');
        $this->db->where('employee_id', $employee_id);
        $this->db->where('subscription_type', 'trial');
        $this->db->where('end_date < ', date('Y-m-d'));
        $query = $this->db->get();
        
        return ($query->num_rows() == 1);
    }
    
    /*
    Verifica si la suscripción está cancelada pero dentro del período de gracia
    */
    function is_cancelled_within_grace_period($employee_id)
    {
        $grace_period = date('Y-m-d H:i:s', strtotime("-3 days"));
        
        $this->db->from('employee_subscriptions');
        $this->db->where('employee_id', $employee_id);
        $this->db->where('status', 'cancelled');
        $this->db->where('cancel_date >', $grace_period);
        $query = $this->db->get();
        
        return ($query->num_rows() == 1);
    }
    
    /*
    Verifica si la suscripción está cancelada fuera del período de gracia
    */
    function is_subscription_cancelled($employee_id)
    {
        $grace_period = date('Y-m-d H:i:s', strtotime("-3 days"));
        
        $this->db->from('employee_subscriptions');
        $this->db->where('employee_id', $employee_id);
        $this->db->where('status', 'cancelled');
        $this->db->group_start();
        $this->db->where('cancel_date IS NULL');
        $this->db->or_where('cancel_date <=', $grace_period);
        $this->db->group_end();
        $query = $this->db->get();
        
        return ($query->num_rows() == 1);
    }
    
    /*
    Verifica si el pago de la suscripción falló
    */
    function is_subscription_failed($employee_id)
    {
        $this->db->from('employee_subscriptions');
        $this->db->where('employee_id', $employee_id);
        $this->db->where('status', 'failed');
        $query = $this->db->get();
        
        return ($query->num_rows() == 1);
    }
    
    /*
    Obtiene todos los tipos de suscripción disponibles
    */
    function get_subscription_types()
    {
        return array(
            'trial' => 'Período de Prueba',
            'monthly' => 'Mensual',
            'annual' => 'Anual',
            'lifetime' => 'De Por Vida'
        );
    }
    
    /*
    Obtiene el estado de la suscripción formateado
    */
    function get_status_label($status)
    {
        $labels = array(
            'active' => 'Activa',
            'trial' => 'En Prueba',
            'cancelled' => 'Cancelada',
            'failed' => 'Fallida',
            'expired' => 'Expirada'
        );
        
        return isset($labels[$status]) ? $labels[$status] : $status;
    }
}
