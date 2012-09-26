<?php

require __DIR__ . '/../vendor/autoload.php';

$app = new Slim(array(
	'templates.path' => __DIR__ . '/../views',
));

require __DIR__ . '/../vendor/slim/extras/Views/TwigView.php';

$app->view('TwigView');

$app->get('/', function() use ($app) {
	$app->render('index.twig.html', array('title' => 'Hello World'));
	}
);

$app->run();

