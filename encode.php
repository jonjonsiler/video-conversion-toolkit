#! /usr/bin/php5 -q
<?php
include_once("./configure.php");
//session_start();
//this file will be run @ some point durring the file compression action
//this file is to look @ the files_uploaded table for files submitted today and
//insert the RSS tags / data needed by system playlist xml files and iTunes / RSS feeds

exec('ps w -C RUNME_ENCODE.php --no-heading',$process);
if (count($process) > 1 ){
	echo "An encode process is currently running. I will die now and try again later...\n";
	die();
}
	
$appRoot = "/var/www/vidtool/";

function scpUpload($filepath)
{
	$source_file = (stristr($filepath,".xml")!==FALSE)?"/var/www/vidtool/xml/".$filepath:"/var/www/vidtool/files/".$filepath;
	if (file_exists($source_file)){
		$connection = ssh2_connect("");
	} else{
		echo "(scpUpload) *** FILE DOESN'T EXIST: ".$source_file."\n";
		return false;
	}
}

function ftpUpload($filepath)
{
	//this should be updated with an scp function?
	$source_file = (stristr($filepath,".xml")!==FALSE)?"/var/www/vidtool/xml/".$filepath:"/var/www/vidtool/files/".$filepath;
	if (file_exists($source_file)){
		$ftp_server = FTP_HOST;
		$ftp_user_name = FTP_USER;
		$ftp_user_pass = FTP_PASS;
		$ftp_root = FTP_ROOT;
		$fname = pathinfo($filepath, PATHINFO_BASENAME);
		$fdir = pathinfo($filepath, PATHINFO_DIRNAME );
		$destination_file = $ftp_root.$filepath;
		$conn_id=ftp_connect($ftp_server);
		if(!$conn_id){
			echo "(ftpUpload) *** FTP Server is not responding. \n";
			return false;
		}
		$login=ftp_login($conn_id, $ftp_user_name, $ftp_user_pass);
		if(!$login){
			echo "(ftpUpload) *** Login Failed on FTP server. \n";
			return false;
		}
		//code check for directory
		if (!ftp_chdir($conn_id, $fdir)) {
			//create the directory
			if (ftp_mkdir($conn_id, $fdir) === FALSE) {
				echo "(ftpUpload) *** Could not create a new directory on the server. \n";
				return false;
			} else {
				if (!ftp_chdir($conn_id, $fdir)) {
					echo "(ftpUpload) *** Could not change directory on server. \n";
					return false;
				}
			}
		}
		if (ftp_put($conn_id, $fname, $source_file, FTP_BINARY)) {
			$uploadsuccess=true;
		} else {
			$uploadsuccess=false;
		}
		ftp_close($conn_id);
		return $uploadsuccess;
	}else{
		echo "(ftpUpload) *** Source file doesn't exist: ".$source_file.".\n";
		return false;
	}
}


function addToFeed(&$file)
{
	global $db;
	$l_aspect=($file->aspect == "4x3")?"SD":"HD";
	//get ordering
	$order = mysql_query('SELECT MAX(ordering) FROM feed WHERE catid = '.$file->categoryid, $db->connection);
	if ($order) {
		$next = (int)mysql_result($order,0);
		$ordering = $next + 1;
		mysql_free_result($order);
	} else {
		$ordering = 500;
	}
	//$fn=pathinfo($file->filename,PATHINFO_FILENAME);
	$rootloc = "http://video.oeta.tv/".$file->folder."/".$file->base;
	$thumb = "http://video.oeta.tv/".$file->folder."/_thumbs/".$file->base.".jpg";
	$query = "INSERT INTO feed ".
			 "(location, mp4location, mp3location, image, title, subtitle, creator, catid, active, front, submit_date, original_broadcast_date, aspect,description, ordering) ".
			 "VALUES ('".$rootloc.".flv','".$rootloc.".m4v','".$rootloc.".mp3', '".$thumb."','".cleanText($file->title)."','".cleanText($file->subtitle)."','".cleanText($file->creator)."', ".$file->categoryid.",".(int)$file->activate.",".(int)$file->front.",'".date("Y-m-d H:i:s")."','".date($file->orig_broadcast_date)."','".$l_aspect."','".cleanText($file->description)."', ".$ordering.")";
	$result = mysql_unbuffered_query($query, $db->connection) ;
	if ($result) {
		$file->id = mysql_insert_id($db->connection);
		if ($file->topics !='') {
			foreach (@explode(":",$file->topics) as $topic_id){
				mysql_unbuffered_query("INSERT INTO video_topical (`video_id`, `topic_id`) VALUES (".$file->id.",".$topic_id.")", $db->connection);
			}
		}
		return $file->id;
	} else return false;
}


function cleanText($mystring)
{
	global $db;
	$mystring = stripslashes($mystring);
    $mystring = mysql_real_escape_string($mystring, $db->connection);
	$mystring = mb_convert_encoding($mystring, 'UTF-8', 'auto');
    return $mystring;
}


function runXML($db)
{
	//iterate thru the categories and generate the XML playlist file for each that has data
	global $appRoot;
	//Select only categories with active videos
	$result_cat = mysql_query("SELECT c.id, c.category FROM categories as c JOIN (SELECT catid FROM feed WHERE `active`=1 GROUP BY catid) as f ON f.catid = c.id ORDER BY id asc;", $db->connection);
	if (mysql_num_rows($result_cat) > 0) {
		while ($row = mysql_fetch_assoc($result_cat)) {
			$catNums[$row["id"]] = $row["category"];
		}
	} else {
		return false;
	}
	mysql_free_result($result_cat);

	foreach($catNums as $catID=>$catName){
		if(file_exists($appRoot."xml/".$catName.".xml")) unlink($appRoot."xml/".$catName.".xml");
		if (!writeXML($catID,$db)) {
			$msg = "(writeXML) *** Unable to Create XML file for category ".$catName.". This is probably a permissions issue\n";
			echo $msg;
		} else {
			if(ftpUpload($catName.".xml")) {
				echo "FEED file uploaded: xml/".$catName.".xml\n";
			}else{
				$err = "(ftpUpload) *** Feed NOT uploaded: xml/".$catName.".xml\n";
				echo $err;
			}
		}
	}
	return true;
}


function writeXML($catid,$db)
{
	global $appRoot;
	$query = "SELECT `feed`.*, c.`category`, c.`ctitle`, c.`annotation`, c.`NOLA`  ".
			 "FROM `feed` JOIN `categories` as c ON c.`id` = `feed`.`catid` ".
			 "WHERE `feed`.`catid` = ".$catid." AND `feed`.`active`=1 ".
			 "ORDER BY IF(ISNULL(`feed`.`ordering`),0,1),`ordering` DESC";
	$feedresult = mysql_query($query, $db->connection);
	if (mysql_num_rows($feedresult) > 0) {
		$catDetails = mysql_fetch_object($feedresult);
		$categoryName = $catDetails->category;
		$xmlfile=$appRoot."xml/".$categoryName.".xml";
		if(is_file($xmlfile)){
			unlink($xmlfile);
		}
		$xml= new XMLWriter();
		$xml->openURI($xmlfile);
		$xml->startDocument('1.0', 'utf-8');
		$xml->setIndent(true);
		$xml->setIndentString("\t");
		$xml->startElement('playlist');
		$xml->writeAttribute("version","1");
		$xml->writeAttribute("xmlns","http://xspf.org/ns/0/");
		$xml->writeAttribute('xmlns:ov','http://www.oeta.tv/2009/ov/');
		$xml->writeElement('title',$catDetails->ctitle);
		$xml->writeElement('creator','OETA');
		$xml->writeElement('annotation',$catDetails->annotation);
		$xml->startElement('meta');
		$xml->writeAttribute("rel", "generator");
		$xml->text('MetaMorphIsis at OETA');
		$xml->endElement();
		$xml->startElement('meta');
		$xml->writeAttribute("rel", "generatorURL");
		$xml->text('http://www.oeta.tv');
		$xml->endElement();
		$xml->startElement("trackList");
		mysql_data_seek($feedresult,0);
       	while ($trackrow = mysql_fetch_object($feedresult))
		{
			$xml->startElement("track");
			$xml->writeElement("title", $trackrow->title);
			$xml->writeElement("ov:subtitle", $trackrow->subtitle);
			$xml->writeElement("location", $trackrow->location);
			$xml->writeElement("image", $trackrow->image);
			$xml->writeElement("identifier", $trackrow->id);
			$xml->startElement("annotation");
				$xml->writeCData($trackrow->description);
			$xml->endElement();
			$xml->writeElement("trackNum", $trackrow->ordering);
			$xml->startElement('meta');
				$xml->writeAttribute("rel", "type");
				$xml->text('flv');
			$xml->endElement();
			$xml->startElement('meta');
				$xml->writeAttribute("rel", "aspect");
				$xml->text($trackrow->aspect);
			$xml->endElement();
			$xml->endElement(); // end track element
		}
		$xml->endElement(); //end trackList
		$xml->endElement(); //end playlist element
		$xml->endDocument(); //end Document
		$xml->flush();
		mysql_free_result($feedresult);
		return true;
	} else {
		return false;
	}
}


//connect to the database
$config= array(
	'server'	=> REP_HOST,
	'user'		=> REP_USER,
	'pass'		=> REP_PASS,
	'database'	=> REP_DATABASE
);
require_once ($appRoot."includes/DBManager.class.php");
$db = new Connection();
$db->DbConnect($config['database']);

//create array holding new submitted files
$query = "SELECT f.*, c.id as cid, c.category, c.parent, c.ctitle, c.annotation, c.folder, c.creator as ccreator, c.NOLA ".
		 "FROM files_uploaded AS f, categories AS c ".
		 "WHERE f.categoryid = c.id AND f.completed = 0";
$result = mysql_query($query, $db->connection);

if (!$result) {
	mailAdmin("Could not make initial connection to database for conversion:".mysql_error());
} else {

	//main loop -
	while ($file = mysql_fetch_object($result)) {
		//catch time
		$begin = time();
		$file->base = pathinfo($file->filename,PATHINFO_FILENAME);
		echo "Beginning Conversion...\n";
		$ffmpeg_result = convertffmpeg($file);
		if (!$ffmpeg_result){
			$msg = "(convertffmpeg) *** ffmpeg encode failure";
			switch ($ffmpeg_result) {
				case 2:
					$msg .= "\n"."<p>There was a problem encoding the flash video file (flv). A log file was created at \"/var/www/vidtool/archive/logfile.txt\"</p>";
					break;
				case 3:
					$msg .= "\n"."<p>There was a problem encoding the thumbnail file (jpg). A log file was created at \"/var/www/vidtool/archive/logfile.txt\"</p>";
					break;
				case 4:
					$msg .= "\n"."<p>There was a problem encoding the mp4 video file (m4v). A log file was created at \"/var/www/vidtool/archive/logfile.txt\"</p>";
					break;
				default:
					$msg .= "\n"."<p>There was an undefined error which occurred with ffmpeg. Please check the logs for more details.</p>";
			}
			mailAdmin($msg);
			echo("FFMPEG conversion failed");
			break;
		}
		$ErrorCollector = '';
		//ftp files - found it easier to use iteration
		foreach(array("flv","m4v","mp3","jpg") as $ext) {
			if ( !ftpUpload($file->folder.(($ext != "jpg")?"/":"/_thumbs/").$file->base.".".$ext) ){
				$ErrorCollector .= "(ftpUpload) *** ".strtoupper($ext)." NOT uploaded: files/".$file->folder.(($ext != "jpg")?"/":"/_thumbs/").$file->base.".".$ext."\n";
			}
		}
	
		if ($ErrorCollector == '') {


		}
		if($ErrorCollector != ''){
			mailAdmin($ErrorCollector);
			echo "Errors occurred: ".$ErrorCollector;
			break;
		} else { 
			//if no other errors ----> add to the feed table
			if(!addToFeed(&$file)){
				$ErrorCollector .= "(addToFeed) *** Feed Entry not added to `feed table`:".$file->category." \n";
				mailAdmin($ErrorCollector);
				echo "Errors occurred: ".$ErrorCollector;
			}
			//move encoded flv to the Archive/category directory, creating the folder if it doesn't exist
			if(!file_exists($appRoot."archive/".$file->folder)){
				mkdir($appRoot."archive/".$file->folder, 0700);
			}
			rename($appRoot."files/".$file->filename, $appRoot."archive/".$file->folder."/".$file->filename);
			rename($appRoot."files/".$file->folder."/".$file->base.".flv",$appRoot."archive/".$file->folder."/".$file->base.".flv");
			rename($appRoot."files/".$file->folder."/".$file->base.".m4v",$appRoot."archive/".$file->folder."/".$file->base.".m4v");
			rename($appRoot."files/".$file->folder."/".$file->base.".mp3",$appRoot."archive/".$file->folder."/".$file->base.".mp3");
			//This is already done by the encoder
			/*
			if(!mysql_unbuffered_query("UPDATE `files_uploaded` set completed = 1 WHERE id =".$file->id.";", $db->connection)){
				echo "Could not UPDATE completed field";
			}
			*/
			$end = time();
			$totalTime = $end - $begin;
			
			//get user information
			$recp=null;
			if ($file->username && $file->submitted_by){
				//clone the db connection and use it to access the user database
				$user_db = new DBManager($config['server'], 'oeta_cr34t3', 'cr34t1v3', 'oeta_joomla15new');
				if (!$user_db) {
					echo "Database Error Encountered while getting user info: ".$user_db->errorCode." – ".$user_db->errorMsg;
				} else {
					$user_result = mysql_query("SELECT name, email FROM `jos_users` WHERE id=".$file->submitted_by." AND `username` LIKE '".$file->username."' LIMIT 0,1", $user_db->connection);
					$user = mysql_fetch_object($user_result);
					$recp = $user?$user:null;
					//var_dump($recp);
					mysql_free_result($user_result);
				}
				$user_db->DbClose();
			}
			$wrapUp = '<a href="http://www.oeta.tv/component/video/video/'.$file->id.'.html">'.$file->title."</a> (filename:".$file->filename.") submitted by ".$recp->name." (". $file->username . ") ".
						"to the ".'<a href="http://www.oeta.tv/component/video/category/'.$file->category.'.html">'.@$file->ctitle. "</a> (". @$file->cid.") ".
						"category has completed conversion and has updated the feed.\n <br>".
					    "Total time for encoding: ".gmdate("H:i:s",$totalTime)." (hr:min:sec)<br />";
			mailAdmin($wrapUp, $recp);

		}
		unset($ErrorCollector);
	}
	//end of main loop

	//Create the feeds for each category and then upload the feeds
	if(!runXML($db)){
		$msg = "(runXML) *** There were no active video files to create an XML file.\n";
		mailAdmin($msg);
	} else {
		echo "No more videos to encode.";
	}
}


function mailAdmin($msg, $recp=null){
	// multiple recipients
	$to = 'Jonathan Siler <jsiler@oeta.tv>';
	$subject = 'OETA Video Encoder Results';
	$message = '
<html>
<head>
  <title>OETA Video Encoder</title>
</head>
<body>
  <p>The video encoder has completed with the following message:</p>
  <p>'.$msg.'</p>
</body>
</html>
';
	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
	if (!is_null($recp)) {//add the to fields
		$to .= ', '.$recp->name."<".$recp->email.">";
	}
	$headers .= 'From: OETA MetaMorphISIS <web@oeta.tv>' . "\r\n";
	mail($to, $subject, $message, $headers);
}


//close db
$db->DbClose();
?>
