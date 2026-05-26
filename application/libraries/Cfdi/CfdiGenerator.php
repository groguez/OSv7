<?php
/**
 * Generador de CFDI 4.0 (Comprobante Fiscal Digital por Internet)
 * 
 * Genera archivos XML y JSON con la estructura oficial del SAT México
 * Compatible con facturación electrónica versión 4.0
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class CfdiGenerator {
    
    protected $CI;
    
    // Catálogos oficiales del SAT
    const VERSION_CFDI = '4.0';
    const TIPO_COMPROBANTE_INGRESO = 'I';
    const METODO_PAGO_PUE = 'PUE'; // Pago en una sola exhibición
    const METODO_PAGO_PPD = 'PPD'; // Pago en parcialidades
    
    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->model('Customer');
        $this->CI->load->model('Location');
        $this->CI->load->helper('xml');
    }
    
    /**
     * Generar CFDI completo desde una venta
     * 
     * @param int $saleId ID de la venta
     * @return array ['xml' => string, 'json' => array, 'success' => bool]
     */
    public function generateFromSale($saleId) {
        // Obtener datos de la venta
        $saleData = $this->getSaleData($saleId);
        
        if (!$saleData) {
            return ['success' => false, 'error' => 'Venta no encontrada'];
        }
        
        // Obtener datos del emisor (tienda/empresa)
        $emisor = $this->getEmisorData($saleData['location_id']);
        
        // Obtener datos del receptor (cliente)
        $receptor = $this->getReceptorData($saleData['customer_id']);
        
        // Obtener conceptos (items de la venta)
        $conceptos = $this->getConceptosData($saleData['items']);
        
        // Calcular impuestos
        $impuestos = $this->calcularImpuestos($conceptos);
        
        // Construir estructura completa
        $cfdi = [
            'Version' => self::VERSION_CFDI,
            'Serie' => $saleData['serie'] ?? 'A',
            'Folio' => str_pad($saleId, 5, '0', STR_PAD_LEFT),
            'Fecha' => date('Y-m-d\TH:i:s', strtotime($saleData['sale_time'])),
            'FormaPago' => $saleData['forma_pago'] ?? '01',
            'MetodoPago' => self::METODO_PAGO_PUE,
            'CondicionesDePago' => 'Pago en una sola exhibición',
            'SubTotal' => number_format($saleData['subtotal'], 2, '.', ''),
            'Moneda' => 'MXN',
            'Total' => number_format($saleData['total'], 2, '.', ''),
            'TipoDeComprobante' => self::TIPO_COMPROBANTE_INGRESO,
            'LugarExpedicion' => $emisor['codigo_postal'],
            'Exportacion' => '01', // Nacional
            'Emisor' => $emisor,
            'Receptor' => $receptor,
            'Conceptos' => $conceptos,
            'Impuestos' => $impuestos
        ];
        
        // Generar XML
        $xml = $this->generateXML($cfdi);
        
        // Generar JSON estructurado
        $json = $this->generateJSON($cfdi);
        
        return [
            'success' => true,
            'xml' => $xml,
            'json' => $json,
            'data' => $cfdi
        ];
    }
    
    /**
     * Obtener datos del emisor desde configuración de tienda
     */
    protected function getEmisorData($locationId) {
        $location = $this->CI->Location->get_info($locationId);
        
        // Datos fiscales de la empresa (deben estar en appconfig o location)
        $this->CI->load->model('Appconfig');
        
        return [
            'Rfc' => $location->rfc_emisor ?? 'AAA010101AAA',
            'Nombre' => $location->company_name ?? 'EMISOR EJEMPLO SA DE CV',
            'RegimenFiscal' => $location->regimen_fiscal ?? '601'
        ];
    }
    
    /**
     * Obtener datos del receptor desde información del cliente
     */
    protected function getReceptorData($customerId) {
        $customer = $this->CI->Customer->get_info($customerId);
        
        return [
            'Rfc' => $customer->rfc ?? 'BBB010101BBB',
            'Nombre' => $customer->company_name ?? ($customer->first_name . ' ' . $customer->last_name),
            'UsoCFDI' => $customer->uso_cfdi ?? 'G03',
            'DomicilioFiscalReceptor' => $customer->codigo_postal ?? '06000',
            'RegimenFiscalReceptor' => $customer->regimen_fiscal ?? '603'
        ];
    }
    
    /**
     * Obtener conceptos (items) de la venta
     */
    protected function getConceptosData($items) {
        $conceptos = [];
        
        foreach ($items as $item) {
            $impuestosTraslados = [];
            
            // Calcular impuestos por item
            if ($item['tax_percent'] > 0) {
                $base = $item['unit_price'] * $item['quantity'];
                $importe = $base * ($item['tax_percent'] / 100);
                
                $impuestosTraslados[] = [
                    'Base' => number_format($base, 2, '.', ''),
                    'Impuesto' => '002', // IVA
                    'TipoFactor' => 'Tasa',
                    'TasaOCuota' => number_format($item['tax_percent'] / 100, 6, '.', ''),
                    'Importe' => number_format($importe, 2, '.', '')
                ];
            }
            
            $concepto = [
                'ClaveProdServ' => $item['clave_prod_serv'] ?? '84111506',
                'NoIdentificacion' => $item['item_number'] ?? $item['item_id'],
                'Cantidad' => $item['quantity'],
                'ClaveUnidad' => $item['clave_unidad'] ?? 'ACT',
                'Unidad' => $item['unit'] ?? 'Actividad',
                'Descripcion' => $item['description'] ?? $item['name'],
                'ValorUnitario' => number_format($item['unit_price'], 2, '.', ''),
                'Importe' => number_format($item['unit_price'] * $item['quantity'], 2, '.', ''),
                'ObjetoImp' => $item['tax_percent'] > 0 ? '02' : '01' // 02=Sí objeto de impuesto, 01=No objeto
            ];
            
            // Agregar impuestos si existen
            if (!empty($impuestosTraslados)) {
                $concepto['Impuestos'] = [
                    'Traslados' => $impuestosTraslados
                ];
            }
            
            $conceptos[] = $concepto;
        }
        
        return $conceptos;
    }
    
    /**
     * Calcular totales de impuestos
     */
    protected function calcularImpuestos($conceptos) {
        $trasladosTotales = [];
        $totalImpuestosTrasladados = 0;
        
        foreach ($conceptos as $concepto) {
            if (isset($concepto['Impuestos']['Traslados'])) {
                foreach ($concepto['Impuestos']['Traslados'] as $traslado) {
                    $importe = (float)$traslado['Importe'];
                    $totalImpuestosTrasladados += $importe;
                    
                    // Agrupar por tipo de impuesto
                    $key = $traslado['Impuesto'] . '_' . $traslado['TasaOCuota'];
                    
                    if (!isset($trasladosTotales[$key])) {
                        $trasladosTotales[$key] = [
                            'Impuesto' => $traslado['Impuesto'],
                            'TipoFactor' => $traslado['TipoFactor'],
                            'TasaOCuota' => $traslado['TasaOCuota'],
                            'Importe' => 0
                        ];
                    }
                    
                    $trasladosTotales[$key]['Importe'] += $importe;
                }
            }
        }
        
        // Redondear importes
        foreach ($trasladosTotales as &$traslado) {
            $traslado['Importe'] = number_format($traslado['Importe'], 2, '.', '');
        }
        
        return [
            'TotalImpuestosTrasladados' => number_format($totalImpuestosTrasladados, 2, '.', ''),
            'Traslados' => array_values($trasladosTotales)
        ];
    }
    
    /**
     * Generar XML CFDI
     */
    protected function generateXML($data) {
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;
        
        // Elemento raíz cfdi:Comprobante
        $comprobante = $xml->createElement('cfdi:Comprobante');
        $comprobante->setAttribute('xmlns:cfdi', 'http://www.sat.gob.mx/cfd/4');
        $comprobante->setAttribute('xsi:schemaLocation', 'http://www.sat.gob.mx/cfd/4 http://www.sat.gob.mx/sitio_internet/cfd/4/cfdv40.xsd');
        
        // Atributos del comprobante
        $this->setAttributes($comprobante, [
            'Version' => $data['Version'],
            'Serie' => $data['Serie'],
            'Folio' => $data['Folio'],
            'Fecha' => $data['Fecha'],
            'FormaPago' => $data['FormaPago'],
            'MetodoPago' => $data['MetodoPago'],
            'CondicionesDePago' => $data['CondicionesDePago'],
            'SubTotal' => $data['SubTotal'],
            'Moneda' => $data['Moneda'],
            'Total' => $data['Total'],
            'TipoDeComprobante' => $data['TipoDeComprobante'],
            'LugarExpedicion' => $data['LugarExpedicion'],
            'Exportacion' => $data['Exportacion']
        ]);
        
        // Emisor
        $emisor = $xml->createElement('cfdi:Emisor');
        $this->setAttributes($emisor, $data['Emisor']);
        $comprobante->appendChild($emisor);
        
        // Receptor
        $receptor = $xml->createElement('cfdi:Receptor');
        $this->setAttributes($receptor, $data['Receptor']);
        $comprobante->appendChild($receptor);
        
        // Conceptos
        $conceptosNode = $xml->createElement('cfdi:Conceptos');
        
        foreach ($data['Conceptos'] as $conceptoData) {
            $concepto = $xml->createElement('cfdi:Concepto');
            $this->setAttributes($concepto, $conceptoData);
            
            // Impuestos del concepto
            if (isset($conceptoData['Impuestos'])) {
                $impuestosNode = $xml->createElement('cfdi:Impuestos');
                $trasladosNode = $xml->createElement('cfdi:Traslados');
                
                foreach ($conceptoData['Impuestos']['Traslados'] as $traslado) {
                    $trasladoNode = $xml->createElement('cfdi:Traslado');
                    $this->setAttributes($trasladoNode, $traslado);
                    $trasladosNode->appendChild($trasladoNode);
                }
                
                $impuestosNode->appendChild($trasladosNode);
                $concepto->appendChild($impuestosNode);
            }
            
            $conceptosNode->appendChild($concepto);
        }
        
        $comprobante->appendChild($conceptosNode);
        
        // Impuestos totales
        $impuestosNode = $xml->createElement('cfdi:Impuestos');
        $impuestosNode->setAttribute('TotalImpuestosTrasladados', $data['Impuestos']['TotalImpuestosTrasladados']);
        
        $trasladosNode = $xml->createElement('cfdi:Traslados');
        foreach ($data['Impuestos']['Traslados'] as $traslado) {
            $trasladoNode = $xml->createElement('cfdi:Traslado');
            $this->setAttributes($trasladoNode, $traslado);
            $trasladosNode->appendChild($trasladoNode);
        }
        
        $impuestosNode->appendChild($trasladosNode);
        $comprobante->appendChild($impuestosNode);
        
        $xml->appendChild($comprobante);
        
        return $xml->saveXML();
    }
    
    /**
     * Establecer atributos en elemento XML
     */
    protected function setAttributes($element, $attributes) {
        foreach ($attributes as $key => $value) {
            $element->setAttribute($key, $value);
        }
    }
    
    /**
     * Generar JSON estructurado
     */
    protected function generateJSON($data) {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * Obtener datos de venta
     */
    protected function getSaleData($saleId) {
        $this->CI->load->model('Sale');
        $saleInfo = $this->CI->Sale->get_info($saleId);
        
        if (!$saleInfo) {
            return null;
        }
        
        // Obtener items de la venta
        $this->CI->load->model('Sale_items');
        $items = [];
        
        foreach ($this->CI->Sale_items->get_items($saleId)->result() as $item) {
            $itemInfo = $this->CI->Item->get_info($item->item_id);
            
            $items[] = [
                'item_id' => $item->item_id,
                'item_number' => $itemInfo->item_number,
                'name' => $itemInfo->name,
                'description' => $itemInfo->description,
                'quantity' => $item->quantity_purchased,
                'unit_price' => $item->unit_price,
                'subtotal' => $item->unit_price * $item->quantity_purchased,
                'tax_percent' => $item->tax_rate,
                'tax_amount' => $item->tax,
                'total' => ($item->unit_price * $item->quantity_purchased) + $item->tax,
                'clave_prod_serv' => $itemInfo->clave_prod_serv ?? '84111506',
                'clave_unidad' => $itemInfo->clave_unidad ?? 'ACT',
                'unit' => $itemInfo->unit ?? 'Pieza'
            ];
        }
        
        return [
            'sale_id' => $saleId,
            'sale_time' => $saleInfo->sale_time,
            'customer_id' => $saleInfo->customer_id,
            'location_id' => $saleInfo->location_id,
            'subtotal' => $saleInfo->subtotal,
            'total' => $saleInfo->total,
            'payment_type' => $saleInfo->payment_type,
            'items' => $items
        ];
    }
    
    /**
     * Guardar XML en archivo
     */
    public function saveToFile($content, $filename) {
        $filepath = APPPATH . '../uploads/cfdi/' . $filename;
        
        if (!is_dir(APPPATH . '../uploads/cfdi')) {
            mkdir(APPPATH . '../uploads/cfdi', 0755, true);
        }
        
        return file_put_contents($filepath, $content);
    }
}
