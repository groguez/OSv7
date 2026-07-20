<?php


class Squarehooks extends MY_Controller 
{
	function __construct()
	{
		parent::__construct();	
		
	}
	
	function index()
	{
		if ($this->_validate_web_hook())
		{
			http_response_code(200);			
		}
	}
	
	function _validate_web_hook()
	{
		$headers = getallheaders();
		$headers = array_change_key_case($headers, CASE_UPPER);
		$signature = isset($headers["X-SQUARE-HMACSHA256-SIGNATURE"]) ? $headers["X-SQUARE-HMACSHA256-SIGNATURE"] : NULL;
		
		$body = file_get_contents('php://input');		
		$url = current_url();		
		$signature_key =  getenv('SQUARE_HOOKS_SIGNATURE_KEY');
		
		$hash = hash_hmac("sha256", $url.$body,$signature_key, true);
		
		if(base64_encode($hash) == $signature)
		{
			return TRUE;
		}
		
		return FALSE;
	}

}



?>