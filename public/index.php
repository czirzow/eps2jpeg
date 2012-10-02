<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../Eps2Jpeg.php';


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
