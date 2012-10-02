<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../Eps2Jpeg.php';


class Respond {
	const BAD       = -2;
	const UNKNOWN   = -1;
	const PARAM     =  1;
	const INIT      =  2;
	const CONVERT   =  3;

	private static $response_codes = array(
		-2 => array('status' => 400, 'message' => 'Bad Upload'),
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

$view = new \Slim\Extras\Views\Twig();

	$app = new Slim\Slim(array(
	'templates.path' => __DIR__ . '/../views',
	'view' => $view,
));


$app->get('/', function() use ($app) {
	$app->render('upload.html');
	}
);


$app->get('/convert/:type/', function($type)  use ($app) {


	$request = Eps2Jpeg::request($type);
	$rc =  $request->validate();
	if($rc !== true) {
		$app->response()->header('Content-Type', 'application/json');
		echo json_encode($rc);
		exit;
	}

	$converter =  Eps2Jpeg::converter($request);
	
	if(! $converter->init()) {
		$app->response()->header('Content-Type', 'application/json');
		echo json_encode(Eps2JpegResponse::error($converter->error, Eps2JpegResponse::INIT));
		exit;
	}

	$file = $converter->convert();
	if(! $file) {
		$app->response()->header('Content-Type', 'application/json');
		echo json_encode(Eps2JpegResponse::error($converter->error, Eps2JpegResponse::CONVERT));
		exit;
	}

	$app->response()->header('Content-Type', 'image/jpeg');
	ob_end_flush(); // just in case.. this ensurse readfile doesn't load file to memory
	readfile($file);
	exit;

});

$app->get('/test-install/', function() use ($app) {

	$rc = Eps2Jpeg::test();
	$app->response()->header('Content-Type', 'application/json');
	echo json_encode($rc);

});


$app->run();
