<?php

defined('BASEPATH') or exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
/** @noinspection PhpIncludeInspection */
require APPPATH . 'libraries/REST_Controller.php';

/**
 * This is an example of a few basic user interaction methods you could use
 * all done with a hardcoded array
 *
 * @package         CodeIgniter
 * @subpackage      Rest Server
 * @category        Controller
 * @author          Phil Sturgeon, Chris Kacerguis
 * @license         MIT
 * @link            https://github.com/chriskacerguis/codeigniter-restserver
 */
class Sale_types extends REST_Controller {
    
    protected $methods = [
        'index_get' => ['level' => 1, 'limit' => REST_LIMIT_VALUE],
    ];
    
    function __construct() {
        // Construct the parent class
        parent::__construct();
        $this->load->model('Sale_types');
    }
    
    private function _sale_types_result_to_array($sale_types) {
        $sale_types_return = array(
            'id'                               => (int)$sale_types['id'],
            'name'                             => $sale_types['id'] ? $sale_types['name'] : lang('common_sale'),
            'sort'                             => $sale_types['sort'],
            'update_inventory'                 => $sale_types['remove_quantity'],
        );
        return $sale_types_return;
    }
    
    public function index_get() {
        $sale_types = $this->Sale_types->get_all('sort', 'asc', true)->result_array();
        $total_records = count($sale_types);
        
        $sale_types_return = array();
        foreach ($sale_types as $sale_type) {
            $sale_types_return[] = $this->_sale_types_result_to_array($sale_type);
        }
        
        header("x-total-records: $total_records");
        
        $this->response($sale_types_return, REST_Controller::HTTP_OK); // OK (200) being the HTTP response code
    }
    
}