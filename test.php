<?php

class Respond {
	const UNKNOWN   = -1;
	const PARAM     =  1;
	const INIT      =  2;
	const CONVERT   =  3;

	private static $response_codes = array(
		-1 => array('status' => 400, 'message' => 'Unknown error occured'),
		 1 => array('status' => 400, 'message' => 'Invalid Parameter'),
		 2 => array('status' => 400, 'message' => 'Problem with initializing parameters'),
		 3 => array('status' => 400, 'message' => 'Problem Converting Image'),
	);


	public function withError($message, $code=-1) {
		if(! $response = self::$response_codes[$code]) {
			$response = self::$response_codes[-1];
		}
		$r = "HTTP/1.1 {$response['status']} [{$code}]:{$response['message']}";
		header($r);
		$output = array(
			'status' => $response['status'],
			'code' => $code,
			'error' => $response['message'],
			'message' => $message,
		);
		header('Content-Type: application/json; charset=UTF-8');
		echo  json_encode($output);
		exit;

	}

}

require_once('Eps2Jpeg.php');


$eps_width = '823';
$eps_height = '648';
$eps_file = 'admin_1326964.eps';

if($_REQUEST['eps_width'] || $_REQUEST['eps_height']) {
	$eps_width = (int)$_REQUEST['eps_width'];
	$eps_height = (int)$_REQUEST['eps_height'];
	if(! ($eps_width && $eps_height) ) {
		Respond::withError("eps_width and eps_height must both be passe", Respond::PARAM);
		exit;
	}
}

if($_REQUEST['jpg_size']) {
	$jpg_size = (int)$_REQUEST['jpg_size'];
	if($jpg_size <= 100) {
		Respond::withError("jpg_size must be larger than 100", Respond::PARAM);
	}
}

$eps = new Eps2Jpeg($eps_file, $eps_width, $eps_height);

if(! $eps->init($jpg_size) ) {
	Respond::withError($eps->error, Respond::INIT);
}

$file = $eps->convert($jpg_size);
if(! $file) {
	Respond::withError($eps->error, Respond::CONVERT);
	exit;
}
//echo $file; exit;

//header('Content-Disposition: attachment; filename="results.jpg";');
header('Content-Type: image/jpeg');
//header('Content-Type: application/octet-stream');
//header('Content-Transfer-Encoding: binary');
ob_end_flush();

readfile($file);
