<?php
//ini_set ("display_errors", "1");
//error_reporting(E_ALL);
header("Location:index.php");
session_start();
require_once ("includes/config.inc.php");
require_once ("controllers/db.class.php");

$db = new Connection($config['database']);
$allowed_filetypes = array('mov','avi','vob','mp4','m4v');

if (is_uploaded_file($_FILES['uploadedfile']['tmp_name']) ) {
	require_once ("includes/file.class.php");
	$file = new File($db->link);
	$file->file_size	= $_FILES['uploadedfile']['size'];
	$file->tmp_name		= $_FILES['uploadedfile']['tmp_name'];
	$file->orig_name	= $_FILES['uploadedfile']['name'];
	if ($file->bind($_POST) && $file->store()) {
		//move and rename the file
		header('Location: report.php');
	} else {
		$error = $file->err;
	}
}else{
	//default set
	//$oops = "_FILES['uploadedfile']['error'] didnt pass back an error code.";
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
		$error = $oops;
	}
}

$ROOT_query = "SELECT * FROM categories WHERE `parent` = 0 ORDER BY ctitle ASC;";
$ROOT_cats = mysql_query($ROOT_query, $db->link) or die(mysql_error());
if (mysql_num_rows($ROOT_cats) > 0) {
	while ($row = mysql_fetch_object($ROOT_cats)) {
		$ROOT_cat->{$row->id} = $row;
	}
}
mysql_free_result($ROOT_cats);

$SUB_query = "SELECT * FROM categories WHERE `parent` != 0 ORDER BY ctitle ASC;";
$SUB_cats = mysql_query($SUB_query, $db->link) or die(mysql_error());
if (mysql_num_rows($SUB_cats) > 0 ) {
	$SUB_cat= array();
	while ($row = mysql_fetch_object($SUB_cats)){
		$SUB_cat[$row->parent]->{$row->id} = $row;
	}
}
mysql_free_result($SUB_cats);

$topics_query = "SELECT * FROM `topics` WHERE 1 ORDER BY `parent` AND `id` ASC;";
$topics_results = mysql_query($topics_query, $db->link) or die(mysql_error());
if (mysql_num_rows($topics_results) > 0) {
	while($row = mysql_fetch_object($topics_results)) {
		if ($row->parent != 0 ) {
			$subtopics[$row->parent][]=$row;
		} else {
			$topics[]=$row;
		}
	}
}
mysql_free_result($topics_results);

$db->close();
?>
<html>
<head>
	<title>OETA Video Upload Toolkit - Upload File</title>
	<link href="includes/style.css" rel="stylesheet" type="text/css" />
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
</head>
<body>

<div id="intro" style="width:600px;">
<p class="userbox">You are logged in as: <span class="userinfo"><?=$_SESSION['name']?> (<?=$_SESSION['username']?>)</span></p>
<p><a href="<?php echo $_SESSION['adminsite']; ?>">Return to management screen</a></p>
<p><a href="report.php">Go to Render Queue</a></p>
<p>Fill in all the fields that pertain to your media file and press Upload.</p>
<p>Once you hit the Upload button, please wait while your file is uploaded to the internet. Depending on the size of your file, this may take a while, you will be notified when the upload process is complete.</p>
<p>Once the upload process is complete - you can submit another video if need be.</p>
</div>
<?php
if($error) {
?>
	<div id="error">
		<?=$error?>
	</div>
<?php
}
?>

<form id="upload" name="upload" accept-charset="utf-8" enctype="multipart/form-data" action="info.php" method="post" onSubmit="return vForms();">

<script type="text/javascript">
var subcat = <?=json_encode($SUB_cat)?> ;
//document.write(subcat[11][19].ctitle);
function loadSub(rootid) {
	var subcatstring = document.upload.subcategoryid;
	subcatstring.options.length = 0; 
	subcatstring.options[0] = new Option('--Select a subcategory--',0);
	document.getElementById("subcategoryid").selected = 0;
	if (subcat[rootid]) {
		for (var cat in subcat[rootid]) {
			var opt = new Option( subcat[rootid][cat].ctitle, subcat[rootid][cat].id);
			subcatstring.options.add(opt);
		}
		document.getElementById("subcategoryid").disabled = false;
	} else {
		document.getElementById("subcategoryid").disabled = true;
	}
	return true;
}
function vForms () {
	var required ={categoryid:"Select a category", title:"Enter a title", uploadedfile:"Select a file to upload"};
	var uploadform=document.upload;
	var selector;
	var er = "";
	for (x in required){
		selector=upload.elements[x];
		if (selector.options && (selector.options[selector.selectedIndex].value.length == 0)) {
			er += "\t" + required[x] + "\n";
		} else if (selector.type =="text" && selector.value.length == 0 ){
			er += "\t" + required[x] + "\n";
		} else if (selector.type =="file" && selector.value.length == 0 ){
			er += "\t" + required[x] + "\n";
		} 
	}
	if ( er != "") {
		alert("Please complete the following fields to continue:\n" + er);
		return false;
	} else return true;
}
</script>

<fieldset>
<legend>Video Uploader</legend>
<table width="100%" border="0">
  <tr>
    <td width="13%" align="right" valign="top" class="label">Category Playlist:</td>
    <td width="87%" align="left" valign="top">
    <select name="categoryid" size="1" onChange="loadSub(this.options[this.selectedIndex].value)" >
		<option selected="true" value="" >--Select a category--</option>
<?php
foreach ($ROOT_cat as $category){
?>
		<option value="<?=$category->id?>"><?=$category->ctitle?></option>
<?php
}
?>
      </select>
      <select name="subcategoryid" id="subcategoryid" disabled="disabled">
      	<option selected="true" value="0">--Select a subcategory--</option>
      </select><br />
      <div id="catdesc"></div>
      </td>
  </tr>
  <tr>
    <td align="right" valign="top" class="label">Media type:</td>
    <td align="left" valign="top"><select size="1" name="file_type">
      <option value="video" selected>video</option>
      <!-- <option value="audio">audio</option> -->
    </select></td>
  </tr>
  <tr>
    <td align="right" valign="top" class="label">Aspect ratio:</td>
    <td align="left" valign="top">
      <label><input name="aspect" type="radio" id="aspect_1" value="16x9" checked>16:9</label><label><input type="radio" name="aspect" value="4x3" id="aspect_0"> 4:3</label></td>
  </tr>
  <tr>
    <td align="right" valign="top" class="label">Series Title:</td>
    <td align="left" valign="top"><input type="text" name="title" maxlength="50" /><span class="caption">(i.e. "Gallery Art News", "Oklahoma News Report")</span></td>
  </tr>
  <tr>
    <td align="right" valign="top" class="label">Episode or Segment Title:</td>
    <td align="left" valign="top"><input type="text" name="subtitle" maxlength="50" /><span class="caption">(i.e. "Mississippi Flowers", "Friday Art Opening at OKCMOA")</span></td>
  </tr>
  <tr>
    <td align="right" valign="top" class="label">Content Description:</td>
    <td align="left" valign="top"><textarea name="description" cols="80" rows="8"></textarea></td>
  </tr>
  <tr>
    <td align="right" valign="top" class="label">Original Broadcast Date:<br>(YYYY-MM-DD)</td>
    <td align="left" valign="top"><input type="text" name="orig_broadcast_date" maxlength="20" value="<?=date('Y-m-d') ?>" /></td>
  </tr>
  <tr>
  	<td align="right" valign="top" class="label">Topic:<br>
	<span class="caption"></span></td>
  	<td align="left" valign="top">
    <select name="topic[]" size="5" multiple="true" id="topics">
    	<option selected value="">--Select Topics--</option>
<?php 
	foreach ($topics as $topic) {
?>
        <option value="<?=$topic->id?>"><?= htmlentities($topic->name);?></option>
<?			
		foreach ($subtopics[$topic->id] as $subtopic) {
?>
            <option value="<?=$subtopic->id?>"><?= "&nbsp;&raquo;&nbsp;".htmlentities($subtopic->name);?></option>
<?php			 
		}
	}
?>    </select> 
    <span class="caption">Multiple topics can be selected by holding down the control (CNTL or CTRL) key while clicking on the topics you would like to add.</span>
    </td>
  </tr>
  <tr>
    <td align="right" valign="top" class="label">Creator:</td>
    <td align="left" valign="top"><input type="text" name="creator" maxlength="50" value="<?=$_SESSION['name']?>" /></td>
  </tr>
  <tr align="right" valign="top" class="label">
    <td align="right" valign="top" class="label"><p>Choose Thumbnail time position:</p></td>
    <td align="left" valign="top"> <p><select name="thumb_min" size="1">
            <option value="0">00</option>
            <option value="1">01</option>
            <option value="2">02</option>
            <option value="3">03</option>
            <option value="4">04</option>
            <option value="5">05</option>
          </select>
          minutes (mm)
          <select name="thumb_sec" size="1">
            <option value="0">00</option>
            <option value="5">05</option>
            <option value="10">10</option>
            <option value="15" selected>15</option>
            <option value="20">20</option>
            <option value="25">25</option>
            <option value="30">30</option>
            <option value="35">35</option>
            <option value="40">40</option>
            <option value="45">45</option>
            <option value="50">50</option>
            <option value="55">55</option>
          </select>
          seconds (ss)</p></td>
  </tr>
  <tr>
	  <td align="right" valign="top"><input type="checkbox" name="activation" checked /></td>
	  <td><label for="activation">Activate on Completion</label></td>
  </tr>
  <tr>
	  <td align="right" valign="top"><input type="checkbox" name="featured" /></td>
	  <td><label for="featured">Push to front?</label></td>
  </tr>
  <tr>
    <td align="right" valign="top" class="label">Choose a file to upload:</td>
    <td align="left" valign="top"><input name="uploadedfile" type="file"  value="" /> <span class="caption">acceptable file types are: <?=implode($allowed_filetypes, ", ");?></span></td>
  </tr>
</table>
<input type="submit" name="uploadbutton" value="Upload File" />
<input type="hidden" name="submitted_by" value="<?=$_SESSION['userid']?>" />
<input type="hidden" name="username" value="<?=$_SESSION['username']?>" />
<input type="hidden" name="MAX_FILE_SIZE" value="2362232012" /> 
</fieldset>
</form>
</body>
</html>