<?php
//ini_set ("display_errors", "1");
//error_reporting(E_ALL);
//ini_set("session.gc_maxlifetime","10800");
//ini_set('memory_limit','256M'); 
//set_time_limit (300);
//max_input_time (300);
session_start();

include_once("./configure.php");
require_once "includes/config.inc.php";
require_once "controllers/db.class.php";
require_once 'controllers/view.class.php';
require_once 'models/user.class.php';
require_once "models/video.class.php";

//establish a view controller
$view = new ViewController();

$db = new Connection($config->database);
if (isset($db->err)){ //if critical failure!!!
	$view->error = $db->err;
	$tmpl = 'error';
}
$allowed_filetypes = array('mov','avi','vob','mp4','m4v');



//unset the session if the user has requested a logout
if (isset($_GET['logout'])){
	session_unset();
	session_destroy();
	$view->redirect('http://connect.oeta.tv');
}

//catch uploads here
if (is_uploaded_file($_FILES['uploadedfile']['tmp_name']) ) {
	require_once ("models/file.class.php");
	$file = new File();
	$file->file_size	= $_FILES['uploadedfile']['size'];
	$file->tmp_name		= $_FILES['uploadedfile']['tmp_name'];
	$file->orig_name	= $_FILES['uploadedfile']['name'];
	if ($file->bind($_POST) && $file->store()) {
		//move and rename the file
		$view->redirect('report.php');
	} else {
		$view->error = $file->err;
	}
}else{
	switch ($_FILES['uploadedfile']['error']){
		case 1:
           $oops = "The file is bigger than this PHP installation allows";
           break;
		case 2:
           $oops = "The file is bigger than this form allows";
           break;
		case 3:
           $oops = "Only part of the file was uploaded";
           break;
		case 4:
           $oops = "No file was uploaded";
           break;
 	}
	if(strlen($oops) > 1){
		$view->error = $oops;
	}
}

//Make sure we haven't already encountered and error
if (!isset($tmpl)) {
//check for a sid and if found reques the session info
	if ($_GET['sid']) {
		$session_id = $_GET['sid'];
		//Connect to the database to copy the session information
		$session_db= new Connection(CRT_DATABASE);
		$session_data = $session_db->loadObject('SELECT * FROM `jos_session` WHERE `session_id` = "'.$session_id.'" LIMIT 0,1');
		$session_db->close();

		//if they have a session; copy the user object and redirect
		if (($session_data->username != '') && ($session_data->gid >= 23)) {
			$_SESSION['userid'] = $session_data->userid;
		}
		$view->redirect('http://connect.oeta.tv/');
	}
	if ($_SESSION['userid']){
		//they've been authenticated pass them on through
		$view->user = new User('id',$_SESSION['userid']);
		$_SESSION['user'] = $view->user;
		$tmpl = ($view->error != '')?'error':'upload';
	} elseif (!isset($tmpl)) {
		//ask for authentication
		$tmpl= 'login';
	}
}
switch ($tmpl) {
	case "upload":  $view->title = "Upload File"; break;
	case "login":	$view->title = "System Login"; break;
	case "error":	$view->title = "Error"; break;
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<title>OETA Video Upload Toolkit :: <?=$view->title?></title>
	<link href="/includes/clean.css" rel="stylesheet" type="text/css" />
	<link href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.7.0/themes/smoothness/jquery-ui.css" rel="stylesheet" type="text/css" />
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<script src="http://www.google.com/jsapi?key=ABQIAAAAASzje6QhgOZyHnV563YqRhRGdLiO-cAyd9fCAg5Nb0QvOFyUJhRKnKYWwvzcCz3kY3IgrWpaJna58w" type="text/javascript"></script>
	<script language="Javascript" type="text/javascript">
		google.load("jquery", "1.4");
		google.load("jqueryui", "1.8");
	</script>
	<script src="/includes/core.js" type="text/javascript"></script>
</head>
<body>
<?php
$view->display($tmpl);
?>
</body>
</html>