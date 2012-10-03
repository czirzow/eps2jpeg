<?php

define('BASEDIR', dirname(__DIR__));
require_once(BASEDIR . '/vendor/autoload.php');
require_once(BASEDIR . '/lib/Eps2Jpeg.php');


$view = new \Slim\Extras\Views\Twig();

$app = new Slim\Slim(array(
	'templates.path' => BASEDIR . '/views',
	'view' => $view,
));

$app->get('/', function() use ($app) {
	$app->render('index.html', array('title' => 'Eps2Jpeg'));
	}
);

$app->get('/form/', function() use ($app) {
	$app->render('form.html');
	}
);


$app->post('/convert/:type/', function($type)  use ($app) {


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
		echo json_encode(Eps2Jpeg::response()->error($converter->error, Eps2Jpeg::INIT));
		exit;
	}

	$file = $converter->convert();
	if(! $file) {
		$app->response()->header('Content-Type', 'application/json');
		echo json_encode(Eps2Jpeg::response()->error($converter->error, Eps2Jpeg::CONVERT));
		exit;
	}

	header('Content-Type: image/jpeg');
	readfile($file);
	exit;

});

$app->get('/test-install/', function() use ($app) {

	$rc = Eps2Jpeg::test();
	$app->response()->header('Content-Type', 'application/json');
	echo json_encode($rc);

});


$app->run();
