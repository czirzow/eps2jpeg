<?php

require_once('Eps2Jpeg.php');


$eps_width = '823';
$eps_height = '648';
$eps_file = 'admin_1326964.eps';

if($_REQUEST['jpg_size']) {
	$jpg_size = $_REQUEST['jpg_size'];
	error_log("Using $jpg_size\n");
}

$eps = new EpsToJpeg($eps_file, $eps_width, $eps_height);

$file = $eps->convert($jpg_size);
if(! $file) {
	echo $eps->error, "\n";
	exit;
}

//header('Content-Disposition: attachment; filename="results.jpg";');
header('Content-Type: image/jpeg');
//header('Content-Type: application/octet-stream');
//header('Content-Transfer-Encoding: binary');
ob_end_flush();

readfile($file);
