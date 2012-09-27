<?php

require __DIR__ . '/../vendor/autoload.php';


$view = new \Slim\Extras\Views\Twig();

	$app = new Slim\Slim(array(
	'templates.path' => __DIR__ . '/../views',
	'view' => $view,
));


$app->get('/', function() use ($app) {
	$app->render('upload.html');
	}
);

$app->run();

