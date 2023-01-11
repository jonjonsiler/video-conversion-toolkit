#! /usr/bin/php5 -q
<?php
include_once('../configure.php');
$activeID = array();
$conn 	= 	array(
				'host' => REP_HOST . ":" . REP_PORT,
				'user' => REP_USER, 
				'pass' => REP_PASS, 
				'name' => REP_DATABASE
			);
$dbconn = mysql_connect($conn['host'],$conn['user'],$conn['pass']);
mysql_select_db($conn['name'],$dbconn);

function is_xml($filename) {
	if (strpos($filename, ".xml")!==FALSE) {
		return TRUE;
	}
}
function downloadPlaylists(){
	$video_ftp = ftp_connect(FTP_HOST);
	ftp_login($video_ftp,FTP_USER,FTP_PASS);
	$remote_xml=ftp_nlist($video_ftp,".");
	$getList = array_filter($remote_xml, "is_xml");
	//$getList is an array of xml files from the ftp directory
	$i=0;
	foreach ($getList as $getKey => $getFile){
		$localFile ="/var/www/vidtool/xml/".$getFile;
		// try to download $getFile_file and save to $localFile
		ftp_get($video_ftp,$localFile, $getFile, FTP_ASCII);
		$getter=updateDB($localFile);
		if ($getter==2) {
			ftp_put($video_ftp, $getFile, $localFile, FTP_ASCII);
		}
		$i++;
	}
	echo $i . " files downloaded";
	$hosted = ftp_close($video_ftp);
	setInactive();
}


function updateDB($file){
	global $activeID, $dbconn;
	// load file
	$xml = simplexml_load_file($file) or die ("Unable to load XML file!");
	$ns=$xml->getDocNamespaces();
	
	$creator = $xml->creator;
	$category = basename($file,".xml");
	$sqlUPDATE = "UPDATE categories SET ctitle= '".addslashes($xml->title)."', annotation='".addslashes($xml->annotation)."' WHERE category = '".$category."';";
	$updateValid = mysql_query($sqlUPDATE, $dbconn) or die(mysql_error());

	$i = 0;
	if ($ns['ov']) {
		$xml->registerXPathNamespace('ov', $ns['ov']);
	}
	if(count($xml->trackList->track)> 0){
		foreach ($xml->trackList->track as $track) {
			$subtitle = @$track->xpath('ov:subtitle');
			if ($subtitle) {
				$track->sub = $subtitle[0];
			}
			var_dump($track)."\n";
			if(isset($track->identifier)){
				$activeID[]=$track->identifier;
			}
			$q = "UPDATE feed SET location='".addslashes($track->location)."'," .
					" image='".addslashes($track->image)."'," .
					" title='".addslashes($track->title)."'," .
					" subtitle = '".addslashes($track->sub)."', ".
					" creator='".addslashes(html_entity_decode($track->creator))."'," .
					" aspect='".addslashes(html_entity_decode($track->meta[1]))."'," .
					" description = '".addslashes(html_entity_decode($track->annotation))."', ".
					" active = '1', ".
					" `order` = ".(count($xml->trackList->track)-$i)." ".
					" WHERE id=".$track->identifier. ";";
			if($track->identifier > 0){
				$updateGood = mysql_query($q, $dbconn) or die("error in the sql stmnt:\n".$q."\n\n");
			} else {
				$q = "INSERT INTO feed ( location, image, title, subtitle, creator, catid, `order`, active, submit_date, aspect, description) ".
					 "VALUES ( '".
					 addslashes($track->location)."', '".	//track video file location
					 addslashes($track->image)."','".		//image location
					 addslashes($track->title)."','".		//track title
					 addslashes(html_entity_decode($track->subtitle))."','".	//track subtitle
					 addslashes(html_entity_decode($track->$creator))."', ".	//track creator
					 "0,".														//track category
					 (count($xml->trackList->track)-$i).", ".					//track number for ordering purposes
					 "1, ".														//set track to active
					 "CURRENT_DATE, '".											//submitted date, right now this is based on current date
					 addslashes($track->meta[1])."','".							//track aspect ratio
					 addslashes(html_entity_decode($track->annotation))."' )";	//track description
				if (mysql_unbuffered_query($q, $dbconn)) {
					echo "An item was added.\n";
					$newID=mysql_insert_id();
					$track->addChild('identifier', $newID);
					$activeID[]=$newID;
					$taker=true;
				}
			}
			$i ++;
		}
	}
	if ($taker) {
		$xml->asXML($file);
		return 2;
	}
	return true; //return count of processed tracks
}
function setInactive() {
	//any file identifier not in a playlist gets set to active=0 and order to NULL
	global $activeID, $dbconn;
	$q = "UPDATE feed SET `active`=0, `order`= 99 WHERE id NOT IN ('".implode("','",$activeID)."')";
	return mysql_unbuffered_query($q, $dbconn);
}



downloadPlaylists();
mysql_close();

?>
