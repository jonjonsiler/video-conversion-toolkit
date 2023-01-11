<?php
include_once('../configure.php');
class File
{
    public	$id, 
			$username, 
			$filename, 
			$file_size, 
			$file_type, 
			$categoryid, 
			$submit_date, 
			$completed, 
			$creator, 
			$title, 
			$subtitle, 
			$aspect, 
			$submitted_by, 
			$description, 
			$orig_broadcast_date, 
			$thumbnail_time, 
			$topics,
			$activate=0,
			$front=0;
	private $connection = null;
	private $allowed = array('mov','avi','vob','mp4','m4v', 'flv', 'wmv'); // These will be the types of file that will pass the validation.
	private $maxsize = 3145728000; //(1048576 * 3000)
	private $upload_path = 'files/';
	private $video_path	 = '/var/www/vidtool/files/';


	/**
	 * Magic Method constructor
	 * @method boolean
	 * @param null|string $database_link
	 */
	public function __construct($database_link=null)
    {
		if (is_null($database_link)) {
			$db = new Connection(REP_DATABASE);
			$this->connection = $db->link;
		} else {
			$this->connection= $database_link;
		}
    }

	/**
	 * Magic method setter
	 * @method boolean 
	 * @param string $name
	 * @param string $value
	 */
	public function __set($name, $value){
		$this->{$name} = $value;
	}

	/**
	 * Bid the $_POST array to the file object
	 * @access public
	 * @param array &$post
	 * @return boolean
	 */
	public function bind(&$post){
		$this->filename=str_replace(array(" ","'","&",'"',"/","\\"), "_", $this->orig_name);
		if ($this->file_size > $this->maxsize) {
			$this->err = "<p>The File uploaded is too large</p>";
			$this->sendMail($post['username'].' attempted to upload a file: "'.$this->filename."\" which wass too large.");
			return false;
		}
		if (!in_array(strtolower(pathinfo($this->filename, PATHINFO_EXTENSION)), $this->allowed) ) {
			$this->err = '<p>File of type "'.pathinfo($this->filename, PATHINFO_EXTENSION).'" is not allowed.</p>'.
						 '<p>Acceptable file formats to upload are: "'.implode($this->allowed, '", "').'" </p>';
			$this->sendMail($post['username'].' attempted to upload a file: "'.$this->filename."\" which was not an acceptable filetype.");
			return false;
		}
		foreach ($post as $postName => $postData) {
			$this->{$postName} = $postData;
		}
		$this->description= mysql_real_escape_string($this->description);
		$this->submit_date = date("Y-m-d H:i:s");
		$this->thumbnail_time = "00:".$this->thumb_min.":".$this->thumb_sec;
		$this->topics	= $post['topic']?implode($post['topic'],":"):'';
		$this->activate = (bool)$post['activation'];
		$this->front	= (bool)$post['featured'];
		if ($this->subcategoryid) {
			$this->categoryid = $this->subcategoryid;
		}
		return true;
	}

	/**
	 * Move uploaded file from temporary location in PHP to upload path
	 * @access private
	 * @return boolean if false sets err
	 */
	private function move() {
		$target_path = $this->upload_path . basename($this->filename);
		//check to see if directory is write protected
		if(!is_writable($this->upload_path)){
			$this->err = "Upload directory is not read/write enabled (777)";
			return false;
		}

		if(file_exists($target_path)) {
			$target_path = $this->upload_path.pathinfo($this->filename, PATHINFO_FILENAME )."_".substr(md5(rand(0,1000)),0,5).".".pathinfo($this->filename, PATHINFO_EXTENSION);
			$this->filename = basename($target_path);
		}

		//move uploaded file
		if(!move_uploaded_file($this->tmp_name, $target_path)) {
			$this->err = "There was an error renaming the temporary file to the new filename.";
			return false;
		}
		//make access to file more permissive
		if (!chmod($target_path, 0775)) {
			$this->err = "Could not change the permissions on the file after moving from temporary folder.";
			return false;
		} else {
			return true;
		}
	}
	public function getFormats() {
		return true;
	}

	/**
	 * Verify the uploaded file is a video file with intact metdata
	 * @access private
	 * @return boolean
	 */
	private function verify(){
		$ffprobe = exec('whereis ffprobe');
		if ($ffprobe != '') {
			//get rid of ffprobe prefix
			$ffprobe = substr($ffprobe, strpos($ffprobe,"/"));
		} else {
			//set it manually and hope for the best
			$ffprobe = "/usr/local/bin/ffprobe";
		}

		$exp="/(?:\[\w*\])[\r\n](.*)(?:\[\/\w*\])/s"; //this searches for anything in a bracketed expansion tag like [FORMAT][/FORMAT] or [STREAM][/STREAM]

		ob_start();
		passthru( $ffprobe . " -show_format -sexagesimal -convert_tags ".$this->video_path . basename($this->filename));
		$values = ob_get_contents();
		ob_end_clean();

		if(preg_match($exp, $values, $matches)) {
			//print_r($matches);
			$format= $matches[1];
			//echo $format;
			if(preg_match_all('/(?P<name>[^\n\r=]*)\s?=\s?(?P<value>.*)/',$format, $something, PREG_SET_ORDER)){
				foreach($something as $m){
					$m['value'] =trim($m['value']);
					$meta->{$m['name']}= is_numeric($m['value'])?(int)$m['value']:$m['value'];
				}
				if ($meta->bit_rate < 9000 && $meta->start_time != "N/A") { //There's something wrong with the video file
					$this->err = "Data was found in the file. This is a video file but it is invalid because it appears to have the video stream embedded in the file.";
					return false;
				} elseif ($meta->duration) { //if there's a duration add it to the file object
					list($hours, $mins, $secs) = explode(':', substr($meta->duration, 0, strpos($meta->duration, ".")));
					$this->duration = ((int)$hours * 3600) + ((int)$mins * 60) + (int)$secs;
					return true;
				}
			}
		} else {
			$this->err = "Data was not found for the video file:".$this->filename.". This is probably not a video file.";
			return false;
		}
	}

	/**
	 * Attempt to move and then verify uploaded file and then merge metadata into the files_uploaded database
	 * @return boolean Some fault protect may send email messages through the client application
	 */
	public function store() {
		if (!$this->move()){
			$this->sendMail($this->err);
			return false;
		}

		if (!$this->verify()){
			$this->sendMail($this->err);
			return false;
		}
		
		$query = "INSERT INTO files_uploaded ".
				 "(username, filename, file_size, file_type, categoryid, submit_date, creator, title, subtitle, aspect, submitted_by, description, orig_broadcast_date, thumbnail_time, topics, activate, front) ".
				 "VALUES ('".
				 	$this->username."','".
					$this->filename."',".
					$this->file_size.",'".
					$this->file_type."',".
					$this->categoryid.",'".
					$this->submit_date."','".
					$this->creator."','".
					$this->title."','".
					$this->subtitle."','".
					$this->aspect."','".
					$this->submitted_by."','".
					$this->description."','".
					$this->orig_broadcast_date."','".
					$this->thumbnail_time."','".
					$this->topics."',".
					(int)$this->activate.",".
					(int)$this->front.")";
		$insert = mysql_query($query, $this->connection);
        if (!$insert) {
			$this->err	= "There was a database error while attempting to add the file to the render queue. An administrator has been notified";
			$this->sendMail($this->err." ".mysql_error()." Query:".$query);
            return false;
		} else {
			$mailmsg =	"Title:".$this->title."<br />".
						"Filename: ".$this->orig_name.". <br />".
						"New video uploaded to: ".$this->categoryid."<br />".
						"Description: ".stripslashes($this->description)." <br />".
						"Encoding: ".mb_detect_encoding($this->description);
			$this->sendMail($mailmsg);
            return true;
        }
	}

	public function load($id){
	}
	

    /**
	 * Sanitize input for Database insert
	 * @param unknown $admin unknown what this field is for
	 * @param string $input the string to clean up for Database input
	 * @return string
	 */
	public function clean($admin,$input)
    {
    	$input = str_replace(chr(13), "<br />", $input);
    	$input = stripslashes($input);
    	$input = mysql_real_escape_string($input);
        return $input;
    }


	/**
	 * Placeholder function for editing metadata for uploaded files
	 * @abstract
	 * @param int $id
	 */
    public function edit($id){
        mysql_query("UPDATE files_uploaded SET filename='".$this->cFileName."', categoryid = " . $this->cCategoryId ."' WHERE id = " . $id, $this->connection) or die(mysql_error());
    }
	
    /**
	 * Delete a file from the files_uploaded record system
	 * @param integer $id
	 * @return boolean
	 */
	public function delete($id){
		return mysql_unbuffered_query("DELETE from files_uploaded WHERE id = " . $id, $this->connection) or die(mysql_error());
    }

	/**
	 * Conversion function for using ffmpeg
	 * @param string $method
	 * @return mixed
	 */
	public function convert($method="ffmpeg") {

		require_once('/controllers/encode.class.php');

		$filepath		= $this->video_path.$this->filename;
		$this->catpath	= $videopath.$file->folder;
		$this->base		= pathinfo($filepath, PATHINFO_FILENAME);

		// Calculate total seconds contained in the $thumbnail_time var
		$thumb_min	= date("i",strtotime($this->thumbnail_time));
		$thumb_sec	= date("s",strtotime($this->thumbnail_time));
		$totalThumbSec = (($thumb_min * 60) + $thumb_sec);

		//For FFmpeg all progress is monitored through stderr so by default we want to redirect to stdout
		// Create an m4v(H.264) video file for use in a video podcast
		$encoder = new ffmpegEncode($this);

		mysql_unbuffered_query("UPDATE `files_uploaded` SET `completed` = '2' WHERE `id` = ".$this->id.";", $this->connection);
		$encoder->convert("mp4");

		// Create an flv file if it doesn't exist otherwise upload the flv file.
		mysql_unbuffered_query("UPDATE `files_uploaded` SET `completed` = '3' WHERE `id` = ".$this->id.";", $this->connection);
		if (pathinfo($file->filename,PATHINFO_EXTENSION)=="flv") {
			echo "copying flv...";
			copy($filepath,$catpath."/".$file->filename);
		} else {
			$encoder->convert("flv");
		}

		// Create an Audio file for a Audio Podcast Feed
		mysql_unbuffered_query("UPDATE `files_uploaded` SET `completed` = '4' WHERE `id` = ".$this->id.";", $this->connection);
		$encoder->convert("mp3");
		// Create the Thumbnail for Video
		echo "creating thumbnail...";
		exec("/usr/local/bin/ffmpeg -itsoffset -".$totalThumbSec."  -i ".$filepath." -s ".$size." -vcodec mjpeg -vframes 1 -an -f rawvideo -y ".$catpath."/_thumbs/".$this->base.".jpg 2>&1", $error_array);
		// Check to see if thumbnail and audio file have been created
		if(!file_exists($catpath."/_thumbs/".$file->base.".jpg") || !file_exists($catypath."/".$this->base.".mp3")){
			$err_log=implode("\n",$error_array);
			fwrite($err_file, $err_log);
			fclose($err_file);
			return 3;
		}

		//If all steps completed and filecheck finds files then update the database as completed and return true
		mysql_unbuffered_query("UPDATE `files_uploaded` SET `completed` = '1' WHERE `id` = ".$this->id.";", $this->connection);
		echo "\nDone! \n\n";
		return true;
	}



	function sendMail($msg){
		// multiple recipients
		//$to  = 'root.operator@gmail.com'; // . ', '; // note the comma
		$to = 'web@oeta.tv';
		$subject = 'OETA Video Uploads';
		$message = '
<html>
<head>
  <title>OETA Video Uploader</title>
</head>
<body>
	<p>A Video was uploaded to the server:</p>
	<p>'.$msg.'</p>
</body>
</html>
';
		// To send HTML mail, the Content-type header must be set
		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
		// Additional headers
		$headers .= 'To: Jonathan Siler <web@oeta.tv>' . "\r\n";
		$headers .= 'From: OETA Video Uploader <web@oeta.tv>' . "\r\n";
		// Mail it
		mail($to, $subject, $message, $headers);
	}
}
?>
