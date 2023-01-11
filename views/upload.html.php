<script type="text/javascript">
	var subcat = <?=json_encode($this->subcategories)?>;
</script>
<div class="userbox">
	<p>You are logged into Joomla! as: <span class="userinfo"><?=$_SESSION['user']->name?> (<?=$_SESSION['user']->username?>)</span> <a href="?logout">logout</a>
	<a href="http://www.oeta.tv/administrator/index.php?option=com_video" class="return" >Return to Joomla! Video management</a>
	<a href="report.php" class="return">Go to Render Queue</a>
	</p>
</div>

<h1>OETA Video Upload Toolkit :: <?=$this->title?></h1>

<div id="intro" style="width:600px;">
	<p><a href="report.php">Go to Render Queue</a></p>
	<p>Fill in all the fields that pertain to your media file and press Upload.</p>
	<p>Once you hit the Upload button, please wait while your file is uploaded to the internet. Depending on the size of your file, this may take a while, you will be notified when the upload process is complete.</p>
	<p>Once the upload process is complete - you can submit another video.</p>
</div>

<form id="upload" name="upload" enctype="multipart/form-data" action="" method="post" onSubmit="return vForms();">
	<div id="category" class="step">
	<h2>Category Details</h2>
	<label>Category Playlist:</label>
    <select name="categoryid" size="1" onChange="loadSub(this.options[this.selectedIndex].value)" >
		<option selected="true" value="" >--Select a category--</option>
<?php
foreach ($this->categories as $category){
?>
		<option value="<?=$category->id?>"><?=$category->ctitle?></option>
<?php
}
?>
		</select>
		<select name="subcategoryid" id="subcategoryid" disabled="disabled">
			<option selected="true" value="0">--Select a subcategory--</option>
		</select>
		<br />
      	<!-- <div id="catdesc"></div> -->
		<label>Media type:</label>
		<select size="1" name="file_type">
			<option value="video" selected>video</option>
			<!-- <option value="audio">audio</option> -->
		</select>
	 </div>

	 <div class="step">
	 <h2>Metadata</h2>
	 <table border="0" cellpadding="0" cellspacing="0" width="90%">
	    <tr>
		<td>
		<label>Series Title:</label>
		<input type="text" name="title" maxlength="50" class="field" />
		<span class="caption">(i.e. "Gallery Art News", "Oklahoma News Report")</span>
		</td>

		<td>
		<label>Episode or Segment Title:</label>
		<input type="text" name="subtitle" maxlength="50" class="field" />
		<span class="caption">(i.e. "Mississippi Flowers", "Friday Art Opening at OKCMOA")</span>
		</td>
	 </tr>
	 </table>
		<label>Content Description:</label>
		<textarea name="description" cols="80" rows="8" ></textarea>
		<label>Creator:</label>
		<input type="text" name="creator" maxlength="50" class="field" value="<?=$this->user->name?>" />

		<label>Original Broadcast Date:</label>
		<input type="text" name="orig_broadcast_date" id="orig_broadcast_date" maxlength="20" value="<?=date('Y-m-d') ?>" class="field"  />
		<span class="caption">(YYYY-MM-DD)</span>

		<input name="activation" type="checkbox" checked><label for="activation" class ="inline">Publish after Conversion?</label>
		<input name="featured" type="checkbox"><label for="featured" class="inline">Publish to Featured?</label>
		<!-- <input name="notify" type="checkbox" onChange="$('#email').toggle();return true;"/><label class ="inline">Notify by e-mail?</label>
		<input name="email" id="email" type="text" value="<?=$_SESSION['user']->email?>" style="display:none;" /> -->
	</div>

	<div class="step">
	<h2>Topics</h2>
		<label>Topics:</label>
		<select name="topic[]" size="30" multiple="true" id="topics">
<?php
	foreach ($this->topics as $topic) {
?>
			<option value="<?=$topic->id?>" class="topic"><?= htmlentities($topic->name);?></option>
<?
		foreach ($this->subtopics[$topic->id] as $subtopic) {
?>
				<option value="<?=$subtopic->id?>" class="subtopic"><?= "&nbsp;&raquo;&nbsp;".htmlentities($subtopic->name);?></option>
<?php
		}
	}
?>
		</select>
		<span class="caption">Multiple topics can be selected by holding down the control (CNTL or CTRL) key while clicking on the topics you would like to add.</span>
    </div>

    <div class="step">
    <h2>File</h2>
    <label>Choose a file to upload:</label>
    <input name="uploadedfile" type="file"  value="" /> <div class="caption">acceptable file types are: <?=implode($this->allowed_filetypes, ", ");?></div>

    <label>Aspect ratio:</label>
    <input name="aspect" type="radio" id="aspect_1" value="16x9" checked>16:9<input type="radio" name="aspect" value="4x3" id="aspect_0"> 4:3

    <label>Choose Thumbnail time position:</label>
    <p>
    <select name="thumb_min" size="1">
	<option value="0">00</option>
        <option value="1">01</option>
        <option value="2">02</option>
        <option value="3">03</option>
        <option value="4">04</option>
        <option value="5">05</option>
    </select> minutes (mm)
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
    </select> seconds (ss)
    </p>

    <input type="submit" name="uploadbutton" value="Upload File" />
	<input type="hidden" name="submitted_by" value="<?=$this->user->id?>" />
	<input type="hidden" name="username" value="<?=$this->user->username?>" />
    <input type="hidden" name="MAX_FILE_SIZE" value="2362232012" />
    </div>
</form>