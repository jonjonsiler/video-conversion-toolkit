<?php
include_once("./configure.php");
$con = mysql_connect(REP_HOST . ":" . REP_PORT, REP_USER, REP_PASS) or die(mysql_error());
mysql_select_db(REP_DATABASE) or die(mysql_error());

$tmpArray = array();
$sql="SELECT * FROM files_uploaded, categories WHERE files_uploaded.categoryid = categories.id ORDER BY submit_date DESC LIMIT 0,20;";
	$result=mysql_query($sql,$con);
	if (mysql_num_rows($result) > 0) {
            while ($row = mysql_fetch_assoc($result)) {
            	$username = $row["username"];
            	$filename = $row["filename"];
            	$category = $row["category"];
				$completed = $row["completed"];
            	$tmpArray[] = array("username"=>$username, "filename"=>$filename, "category"=>$category, "completed"=>$completed);
            }
	}
	mysql_free_result($result);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<head>
<title>OETA Video Upload Toolkit - Status Page</title>
<meta http-equiv="refresh" content="300" />
<style type="text/css">
body{font-family:Arial, Helvetica, sans-serif; height:100%;font-size:1em; padding:0; }
p{text-align:left;}
p.notice {color:#440000; font-size:.7em; font-style:italic;}
#main{margin:20px 50px;text-align:left;width:600px;}
table {
	font: 11px/24px Verdana, Arial, Helvetica, sans-serif;
	border-collapse: collapse;
	}
table td {padding:2px 10px;}
table thead td {background-color:#e8e8f2; text-align:left;}
table td.incomplete {background-color:#FFFFCC;}
table td.complete {color:#999999;}
</style>
</head>
<body>
<div id="main">
<h2>OETA Video Upload Toolkit</h2>
<?php if ( !is_null($_SERVER['HTTP_REFERER']) && ($_SERVER['HTTP_REFERER'] == "info.php")){ ?>
<p>Your video has been uploaded...</p>
<?php
}
?>
<p>Below is a report showing the last 20 uploaded videos and their status.</p>
<p>Refresh this page to see an updated report at any time. You can even bookmark this page for use at any time.</p>
<?php $process = exec('ps -C RUNME_ENCODE.php');
	if (stristr($process, 'PID') === FALSE){?>
    <p class="notice">An encode process is currently running.</p>
<?php } else { ?>
<p>Time to next encode: <strong>
<?php 
$next=mktime(date("G")+1,0);
echo ((date("U",$next)-date("U"))/60);
?></strong> minutes </p>
<?php	}
?>
<script type="text/javascript">

</script>
<table border="0">
<thead>
<tr>
<td valign="top"><b>Username</b></td>
<td valign="top"><b>Filename</b></td>
<td valign="top"><b>Category</b></td>
<td valign="top"><b>Completed</b></td>
</tr>
</thead>
<tbody>
<?php

if(count($tmpArray)>0){
		
	foreach($tmpArray as $article){
			$bg = "#ffffff";
			$username = $article['username'];
			$filename = $article['filename'];
			$category = $article['category'];
			$completed = $article['completed'];
			switch ($completed){
				case 1:
					$com="yes";
					$class = "complete";
					break;
				case 2:
					$com="encoding mp4";
					$class = "incomplete";
					break;
				case 3:
					$com="encoding flv";
					$class="incomplete";
					break;
				case 4:
					$com="encoding mp3";
					$class="incomplete";
					break;
				case 0: default:
					$com = "no";
					$class="incomplete";
					break;
			}
			echo "<tr>";
			echo "<td class=\"".$class."\" valign=\"top\">".$username."</td>\n";
			echo "<td class=\"".$class."\" valign=\"top\">".$filename."</td>\n";
			echo "<td class=\"".$class."\" valign=\"top\">".$category."</td>\n";
			echo "<td class=\"".$class."\" valign=\"top\">".$com."</td>\n";
			echo "</tr>";
		
		}


}else{
	echo "<tr><td colspan=\"4\">No records to display</td></tr>";
}




?>
</tbody>
</table>
</div>
<?=mysql_client_encoding();?>
	<?php 
mysql_close($con);
	?>
</body>
</html>
