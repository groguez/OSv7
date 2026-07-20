<?php
require_once (APPPATH."libraries/php-qrcode/qrcode.php");


class Qrcodegenerator extends MY_Controller 
{
	function __construct()
	{
		parent::__construct();	
	}

	function index()
	{
		$qrcode = rawurldecode($this->input->get('qrcode'));
		$scale = rawurldecode($this->input->get('scale'));
		$width = rawurldecode($this->input->get('width'));
		$s = rawurldecode($this->input->get('s'));

		if(!$scale) {
			$scale = 2;
		}
		if(!$s) {
			$s = 'qr';
		}

		$width = $width * 96;

		$options = array(
			's'=>$s,
			// 'sf'=> $scale,
			'w'=> $width,
			'h'=> $width,
			'p'=> 0,
			'wq' => 0,
		);
		$generator = new QRCode($qrcode, $options);

		/* Output directly to standard output. */
		$generator->output_image();
	}
}
