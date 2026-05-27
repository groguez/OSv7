<?php
/**
 * Controlador para Facturación Electrónica CFDI 4.0
 * Permite generar, previsualizar y descargar XML de facturas
 */
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'controllers/Security.php');

class Facturacion_electronica extends Security {
    
    public function __construct() {
        parent::__construct();
        $this->load->model('Cfdi/Cfdi_model');
        $this->load->helper('sat_catalogs');
    }
    
    /**
     * Dashboard de facturación electrónica
     */
    public function index() {
        $data['page_title'] = 'Facturación Electrónica CFDI 4.0';
        $data['allowed_modules'] = $this->Module->get_allowed_modules();
        
        $this->load->view('facturacion/dashboard', $data);
    }
    
    /**
     * Generar CFDI para una venta específica
     * @param int $sale_id
     */
    public function generar($sale_id = NULL) {
        if (!$sale_id) {
            show_error('ID de venta no proporcionado');
            return;
        }
        
        // Verificar permisos
        if (!$this->Employee->has_module_grant('sales', $this->Employee->get_logged_in_employee_info()->person_id)) {
            redirect('no_access/sales');
            return;
        }
        
        // Generar JSON CFDI
        $cfdi_json = $this->Cfdi_model->generate_cfdi_json($sale_id);
        
        if (!$cfdi_json) {
            $this->session->set_flashdata('error', 'No se pudo generar el CFDI. Verifique que la tienda y cliente tengan datos fiscales completos.');
            redirect('sales/receipt/' . $sale_id);
            return;
        }
        
        // Guardar en sesión para descarga
        $this->session->set_userdata('cfdi_json_' . $sale_id, $cfdi_json);
        
        // Retornar vista de previsualización
        $data['sale_id'] = $sale_id;
        $data['cfdi_json'] = json_encode($cfdi_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $data['cfdi_xml'] = $this->Cfdi_model->json_to_xml($cfdi_json);
        $data['page_title'] = 'Previsualización CFDI - Venta #' . $sale_id;
        
        $this->load->view('facturacion/preview', $data);
    }
    
    /**
     * Descargar XML del CFDI
     * @param int $sale_id
     */
    public function descargar_xml($sale_id = NULL) {
        if (!$sale_id) {
            show_error('ID de venta no proporcionado');
            return;
        }
        
        // Obtener JSON de la sesión o regenerar
        $cfdi_json = $this->session->userdata('cfdi_json_' . $sale_id);
        
        if (!$cfdi_json) {
            $cfdi_json = $this->Cfdi_model->generate_cfdi_json($sale_id);
            if (!$cfdi_json) {
                show_error('No se pudo generar el CFDI');
                return;
            }
        }
        
        // Convertir a XML
        $xml_content = $this->Cfdi_model->json_to_xml($cfdi_json);
        
        // Forzar descarga
        $filename = 'CFDI_' . $sale_id . '.xml';
        
        header('Content-Type: application/xml');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($xml_content));
        
        echo $xml_content;
        exit;
    }
    
    /**
     * Descargar JSON del CFDI
     * @param int $sale_id
     */
    public function descargar_json($sale_id = NULL) {
        if (!$sale_id) {
            show_error('ID de venta no proporcionado');
            return;
        }
        
        // Obtener JSON de la sesión o regenerar
        $cfdi_json = $this->session->userdata('cfdi_json_' . $sale_id);
        
        if (!$cfdi_json) {
            $cfdi_json = $this->Cfdi_model->generate_cfdi_json($sale_id);
            if (!$cfdi_json) {
                show_error('No se pudo generar el CFDI');
                return;
            }
        }
        
        // Forzar descarga
        $filename = 'CFDI_' . $sale_id . '.json';
        $json_content = json_encode($cfdi_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($json_content));
        
        echo $json_content;
        exit;
    }
    
    /**
     * Mostrar catálogos del SAT para configuración
     */
    public function catalogos() {
        $this->load->config('sat_catalogs/Sat_catalogs');
        $catalogs = Sat_catalogs::get_all_catalogs();
        
        $data['catalogs'] = $catalogs;
        $data['page_title'] = 'Catálogos del SAT';
        
        $this->load->view('facturacion/catalogos', $data);
    }
}
