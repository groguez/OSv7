<?php
/**
 * Modelo para generación de CFDI 4.0
 * Convierte datos de ventas en estructura JSON compatible con CFDI
 */
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'config/sat_catalogs/Sat_catalogs.php');

class Cfdi_model extends CI_Model {
    
    private $version_cfdi = '4.0';
    
    public function __construct() {
        parent::__construct();
        $this->load->database();
    }
    
    /**
     * Generar estructura JSON para CFDI a partir de una venta
     * @param int $sale_id ID de la venta
     * @return array Estructura JSON lista para convertir a XML
     */
    public function generate_cfdi_json($sale_id) {
        // Obtener datos de la venta
        $sale_data = $this->get_sale_data($sale_id);
        if (!$sale_data) {
            return null;
        }
        
        // Obtener datos del emisor (tienda/location)
        $emisor = $this->get_emisor_data($sale_data['location_id']);
        if (!$emisor) {
            return null;
        }
        
        // Obtener datos del receptor (cliente)
        $receptor = $this->get_receptor_data($sale_data['customer_id']);
        if (!$receptor) {
            return null;
        }
        
        // Obtener conceptos (productos de la venta)
        $conceptos = $this->get_conceptos_data($sale_id);
        
        // Calcular totales
        $sub_total = 0;
        $total_impuestos_traslado = 0;
        
        foreach ($conceptos as $concepto) {
            $sub_total += floatval($concepto['importe']);
            if (isset($concepto['Impuestos']['Traslados'])) {
                foreach ($concepto['Impuestos']['Traslados'] as $traslado) {
                    $total_impuestos_traslado += floatval($traslado['importe']);
                }
            }
        }
        
        $total = $sub_total + $total_impuestos_traslado;
        
        // Construir estructura CFDI
        $cfdi = [
            'version_cfdi' => $this->version_cfdi,
            'serie' => $emisor['serie'] ?? 'A',
            'folio' => str_pad($sale_id, 4, '0', STR_PAD_LEFT),
            'fecha' => date('Y-m-d\TH:i:s', strtotime($sale_data['sale_time'])),
            'formaPago' => $sale_data['forma_pago'] ?? '01',
            'condicionesDePago' => $sale_data['condiciones_pago'] ?? 'Pago en una sola exhibición',
            'subTotal' => number_format($sub_total, 2, '.', ''),
            'moneda' => 'MXN',
            'total' => number_format($total, 2, '.', ''),
            'tipoDeComprobante' => 'I', // Ingreso
            'exportacion' => '01', // No aplica
            'metodoPago' => $sale_data['metodo_pago'] ?? 'PUE',
            'LugarExpedicion' => $emisor['codigo_postal'] ?? '06000',
            'Emisor' => [
                'rfc' => $emisor['rfc'],
                'nombre' => $emisor['nombre'],
                'regimenFiscal' => $emisor['regimen_fiscal']
            ],
            'Receptor' => [
                'rfc' => $receptor['rfc'],
                'nombre' => $receptor['nombre'],
                'domicilioFiscalReceptor' => $receptor['codigo_postal'] ?? '06000',
                'regimenFiscalReceptor' => $receptor['regimen_fiscal'],
                'usoCFDI' => $receptor['uso_cfdi'] ?? 'G03'
            ],
            'Conceptos' => $conceptos
        ];
        
        // Validar contra catálogos del SAT
        $this->validate_sat_catalogs($cfdi);
        
        return $cfdi;
    }
    
    /**
     * Obtener datos de la venta
     */
    private function get_sale_data($sale_id) {
        $this->db->select('sales.sale_id, sales.sale_time, sales.location_id, sales.customer_id, 
                          sales.payment_type, sales.total, sales.comment');
        $this->db->from('sales');
        $this->db->where('sale_id', $sale_id);
        $query = $this->db->get();
        
        if ($query->num_rows() === 0) {
            return null;
        }
        
        $row = $query->row_array();
        
        // Mapear tipo de pago a forma de pago SAT
        $forma_pago = '01'; // Default efectivo
        $metodo_pago = 'PUE';
        
        if (stripos($row['payment_type'], 'cash') !== false) {
            $forma_pago = '01';
        } elseif (stripos($row['payment_type'], 'card') !== false || stripos($row['payment_type'], 'credit') !== false) {
            $forma_pago = '04'; // Tarjeta de crédito
            $metodo_pago = 'PUE';
        } elseif (stripos($row['payment_type'], 'debit') !== false) {
            $forma_pago = '28'; // Tarjeta de débito
            $metodo_pago = 'PUE';
        } elseif (stripos($row['payment_type'], 'transfer') !== false || stripos($row['payment_type'], 'bank') !== false) {
            $forma_pago = '03'; // Transferencia
            $metodo_pago = 'PUE';
        }
        
        $row['forma_pago'] = $forma_pago;
        $row['metodo_pago'] = $metodo_pago;
        $row['condiciones_pago'] = 'Pago en una sola exhibición';
        
        return $row;
    }
    
    /**
     * Obtener datos del emisor (tienda)
     */
    private function get_emisor_data($location_id) {
        $this->db->select('location_id, company_name, address, city, state, zip, phone, email,
                          rfc, regimen_fiscal, serie_fiscal');
        $this->db->from('locations');
        $this->db->where('location_id', $location_id);
        $query = $this->db->get();
        
        if ($query->num_rows() === 0) {
            return null;
        }
        
        $row = $query->row_array();
        
        return [
            'rfc' => $row['rfc'] ?? 'AAA010101XXX',
            'nombre' => $row['company_name'] ?? 'EMISOR DE PRUEBA SA DE CV',
            'regimen_fiscal' => $row['regimen_fiscal'] ?? '601',
            'codigo_postal' => $row['zip'] ?? '06000',
            'serie' => $row['serie_fiscal'] ?? 'A'
        ];
    }
    
    /**
     * Obtener datos del receptor (cliente)
     */
    private function get_receptor_data($customer_id) {
        $this->db->select('person_id, first_name, last_name, company_name, email, phone,
                          address, city, state, zip, rfc, regimen_fiscal, uso_cfdi');
        $this->db->from('customers');
        $this->db->join('people', 'customers.person_id = people.person_id');
        $this->db->where('customers.customer_id', $customer_id);
        $query = $this->db->get();
        
        if ($query->num_rows() === 0) {
            // Cliente genérico si no existe
            return [
                'rfc' => 'XAXX010101000', // RFC genérico para público en general
                'nombre' => 'PÚBLICO EN GENERAL',
                'regimen_fiscal' => '616', // Sin obligaciones fiscales
                'uso_cfdi' => 'S01', // Sin efectos fiscales
                'codigo_postal' => '06000'
            ];
        }
        
        $row = $query->row_array();
        $nombre = !empty($row['company_name']) ? $row['company_name'] : trim($row['first_name'] . ' ' . $row['last_name']);
        
        return [
            'rfc' => $row['rfc'] ?? 'XAXX010101000',
            'nombre' => $nombre ?? 'PÚBLICO EN GENERAL',
            'regimen_fiscal' => $row['regimen_fiscal'] ?? '616',
            'uso_cfdi' => $row['uso_cfdi'] ?? 'S01',
            'codigo_postal' => $row['zip'] ?? '06000'
        ];
    }
    
    /**
     * Obtener conceptos (productos de la venta)
     */
    private function get_conceptos_data($sale_id) {
        $this->db->select('sales_items.item_id, sales_items.description, sales_items.quantity_purchased,
                          sales_items.item_unit_price, sales_items.discount, sales_items.total,
                          items.name, items.product_id, items.clave_prod_serv, items.clave_unidad,
                          items.objeto_imp, items.impuesto_tasa');
        $this->db->from('sales_items');
        $this->db->join('items', 'sales_items.item_id = items.item_id');
        $this->db->where('sales_items.sale_id', $sale_id);
        $query = $this->db->get();
        
        $conceptos = [];
        foreach ($query->result_array() as $row) {
            $valor_unitario = floatval($row['item_unit_price']);
            $cantidad = floatval($row['quantity_purchased']);
            $importe = $valor_unitario * $cantidad;
            
            // Calcular impuestos (IVA 16% por defecto)
            $tasa_iva = floatval($row['impuesto_tasa']) > 0 ? floatval($row['impuesto_tasa']) : 0.16;
            $importe_iva = $importe * $tasa_iva;
            
            $concepto = [
                'claveProdServ' => $row['clave_prod_serv'] ?? '99999999',
                'cantidad' => number_format($cantidad, 2, '.', ''),
                'claveUnidad' => $row['clave_unidad'] ?? 'H87',
                'descripcion' => $row['description'] ?? $row['name'],
                'valorUnitario' => number_format($valor_unitario, 2, '.', ''),
                'importe' => number_format($importe, 2, '.', ''),
                'objetoImp' => $row['objeto_imp'] ?? '02',
                'Impuestos' => [
                    'Traslados' => [
                        [
                            'base' => number_format($importe, 2, '.', ''),
                            'impuesto' => '002', // IVA
                            'tipoFactor' => 'Tasa',
                            'tasaOCuota' => number_format($tasa_iva, 6, '.', ''),
                            'importe' => number_format($importe_iva, 2, '.', '')
                        ]
                    ]
                ]
            ];
            
            $conceptos[] = $concepto;
        }
        
        return $conceptos;
    }
    
    /**
     * Validar estructura contra catálogos del SAT
     */
    private function validate_sat_catalogs(&$cfdi) {
        // Validar forma de pago
        if (!Sat_catalogs::validate('formas_pago', $cfdi['formaPago'])) {
            $cfdi['formaPago'] = '01';
        }
        
        // Validar método de pago
        if (!Sat_catalogs::validate('metodos_pago', $cfdi['metodoPago'])) {
            $cfdi['metodoPago'] = 'PUE';
        }
        
        // Validar régimen fiscal emisor
        if (!Sat_catalogs::validate('regimenes_fiscales', $cfdi['Emisor']['regimenFiscal'])) {
            $cfdi['Emisor']['regimenFiscal'] = '601';
        }
        
        // Validar régimen fiscal receptor
        if (!Sat_catalogs::validate('regimenes_fiscales', $cfdi['Receptor']['regimenFiscalReceptor'])) {
            $cfdi['Receptor']['regimenFiscalReceptor'] = '616';
        }
        
        // Validar uso CFDI
        if (!Sat_catalogs::validate('usos_cfdi_morales', $cfdi['Receptor']['usoCFDI'])) {
            $cfdi['Receptor']['usoCFDI'] = 'S01';
        }
        
        // Validar conceptos
        foreach ($cfdi['Conceptos'] as &$concepto) {
            if (!Sat_catalogs::validate('claves_prod_serv', $concepto['claveProdServ'])) {
                $concepto['claveProdServ'] = '99999999';
            }
            
            if (!Sat_catalogs::validate('claves_unidad', $concepto['claveUnidad'])) {
                $concepto['claveUnidad'] = 'H87';
            }
            
            if (!Sat_catalogs::validate('objeto_imp', $concepto['objetoImp'])) {
                $concepto['objetoImp'] = '02';
            }
            
            if (isset($concepto['Impuestos']['Traslados'])) {
                foreach ($concepto['Impuestos']['Traslados'] as &$traslado) {
                    if (!Sat_catalogs::validate('impuestos', $traslado['impuesto'])) {
                        $traslado['impuesto'] = '002';
                    }
                    
                    if (!Sat_catalogs::validate('tipo_factor', $traslado['tipoFactor'])) {
                        $traslado['tipoFactor'] = 'Tasa';
                    }
                }
            }
        }
    }
    
    /**
     * Convertir array JSON a XML CFDI
     * Nota: Esta es una estructura básica. Para timbrado real se requiere un PAC
     */
    public function json_to_xml($cfdi_data) {
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;
        
        // Elemento raíz cfdi:Comprobante
        $comprobante = $xml->createElement('cfdi:Comprobante');
        $comprobante->setAttribute('xmlns:cfdi', 'http://www.sat.gob.mx/cfd/4');
        $comprobante->setAttribute('Version', $cfdi_data['version_cfdi']);
        $comprobante->setAttribute('Serie', $cfdi_data['serie']);
        $comprobante->setAttribute('Folio', $cfdi_data['folio']);
        $comprobante->setAttribute('Fecha', $cfdi_data['fecha']);
        $comprobante->setAttribute('FormaPago', $cfdi_data['formaPago']);
        $comprobante->setAttribute('CondicionesDePago', $cfdi_data['condicionesDePago']);
        $comprobante->setAttribute('SubTotal', $cfdi_data['subTotal']);
        $comprobante->setAttribute('Moneda', $cfdi_data['moneda']);
        $comprobante->setAttribute('Total', $cfdi_data['total']);
        $comprobante->setAttribute('TipoDeComprobante', $cfdi_data['tipoDeComprobante']);
        $comprobante->setAttribute('Exportacion', $cfdi_data['exportacion']);
        $comprobante->setAttribute('MetodoPago', $cfdi_data['metodoPago']);
        $comprobante->setAttribute('LugarExpedicion', $cfdi_data['LugarExpedicion']);
        
        // Emisor
        $emisor = $xml->createElement('cfdi:Emisor');
        $emisor->setAttribute('Rfc', $cfdi_data['Emisor']['rfc']);
        $emisor->setAttribute('Nombre', $cfdi_data['Emisor']['nombre']);
        $emisor->setAttribute('RegimenFiscal', $cfdi_data['Emisor']['regimenFiscal']);
        $comprobante->appendChild($emisor);
        
        // Receptor
        $receptor = $xml->createElement('cfdi:Receptor');
        $receptor->setAttribute('Rfc', $cfdi_data['Receptor']['rfc']);
        $receptor->setAttribute('Nombre', $cfdi_data['Receptor']['nombre']);
        $receptor->setAttribute('DomicilioFiscalReceptor', $cfdi_data['Receptor']['domicilioFiscalReceptor']);
        $receptor->setAttribute('RegimenFiscalReceptor', $cfdi_data['Receptor']['regimenFiscalReceptor']);
        $receptor->setAttribute('UsoCFDI', $cfdi_data['Receptor']['usoCFDI']);
        $comprobante->appendChild($receptor);
        
        // Conceptos
        $conceptos = $xml->createElement('cfdi:Conceptos');
        foreach ($cfdi_data['Conceptos'] as $concepto_data) {
            $concepto = $xml->createElement('cfdi:Concepto');
            $concepto->setAttribute('ClaveProdServ', $concepto_data['claveProdServ']);
            $concepto->setAttribute('Cantidad', $concepto_data['cantidad']);
            $concepto->setAttribute('ClaveUnidad', $concepto_data['claveUnidad']);
            $concepto->setAttribute('Descripcion', $concepto_data['descripcion']);
            $concepto->setAttribute('ValorUnitario', $concepto_data['valorUnitario']);
            $concepto->setAttribute('Importe', $concepto_data['importe']);
            $concepto->setAttribute('ObjetoImp', $concepto_data['objetoImp']);
            
            // Impuestos
            if (isset($concepto_data['Impuestos']['Traslados'])) {
                $impuestos = $xml->createElement('cfdi:Impuestos');
                $traslados = $xml->createElement('cfdi:Traslados');
                
                foreach ($concepto_data['Impuestos']['Traslados'] as $traslado_data) {
                    $traslado = $xml->createElement('cfdi:Traslado');
                    $traslado->setAttribute('Base', $traslado_data['base']);
                    $traslado->setAttribute('Impuesto', $traslado_data['impuesto']);
                    $traslado->setAttribute('TipoFactor', $traslado_data['tipoFactor']);
                    $traslado->setAttribute('TasaOCuota', $traslado_data['tasaOCuota']);
                    $traslado->setAttribute('Importe', $traslado_data['importe']);
                    $traslados->appendChild($traslado);
                }
                
                $impuestos->appendChild($traslados);
                $concepto->appendChild($impuestos);
            }
            
            $conceptos->appendChild($concepto);
        }
        $comprobante->appendChild($conceptos);
        
        $xml->appendChild($comprobante);
        
        return $xml->saveXML();
    }
}
