<?php
header('Content-Type: application/json');
session_start();
require_once('../models/user.class.php');
require_once ("./db.class.php");

$response = array('code'=>'error');
if ($_REQUEST['username'] && $_REQUEST['password']) {
	$user = new User('username', $_REQUEST['username']);
	if ($user->id) {
		$user->auth($_REQUEST['password']);
		if ($user->authenticated) {
			$_SESSION['userid']=$user->id;
			$_SESSION['user']=$user;
			$response['code']='success';
			$response['user']=$user;
		} else {
			$response['code']='error';
			$response['message']=$user->err;
		}
	} else {
		$response['code']='error';
		$response['message']='Invalid User. Could not authenticate.';
	}

}
echo json_encode($response);

?>