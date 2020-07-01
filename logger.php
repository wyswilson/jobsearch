<?php

	include "define.php";

	$logstr = $_GET['s'];

	$status = logevent($conn,$stoplogging,$logstr);

	header('Content-type: application/json');
	header('Access-Control-Allow-Origin: *');

	$responseobj = array ('status'=>$status);
	echo json_encode($responseobj);

?>