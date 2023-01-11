<?php
//only display userbox if there is an user session
if(isset($_SESSION['user'])){
?>
<div class="userbox">
	<p>You are logged into Joomla! as: <span class="userinfo"><?=$_SESSION['user']->name?> (<?=$_SESSION['user']->username?>)</span> <a href="?logout">logout</a>
	<a href="http://www.oeta.tv/administrator/index.php?option=com_video" class="return" >Return to Joomla! Video management</a></p>
</div>
<?php
}
?>
<h1>OETA Video Upload Toolkit :: <?=$this->title?></h1>
<div id="error">
<p>There was an error which prevented your file from being uploaded:</p>
<p><?=$this->error?></p>
<p>An administrator has been notified of this error.</p>
</div>