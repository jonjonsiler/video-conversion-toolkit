<?php
/**
 * Description of encode
 *
 * @author jsiler
 */
class Encode {
	var $filename,
		$basename,
		$inputfile,
		$outputpath, //needs an trailing slash
		$bin;

	public function __construct($fileObject=null) {
		// Set size parameters
		$this->aspect = $fileObject->aspect;
		// Create the needed folders if they dont exist
		$this->outputpath = $fileObject->catpath;
		if(!is_dir($this->outputpath)) mkdir($this->outputpath, 0777);
		if(!is_dir($this->outputpath."/_thumbs")) mkdir($this->outputpath."/_thumbs", 0777);
		return true;
	}

	public function convert($type="flv"){
		$this->err_file		= fopen("/var/www/vidtool/archive/logfile.txt", "w+");
		switch (strtolower($type)) {
			case "mp3":case "mp4":case "flv":
				$this->convertTo{strtoupper($type)}();
				break;
			default:
				$this->convertToFLV();
				break;
		}
		fclose($this->err_file);
		return $this->checkFile($this->outputfile);
	}

	private function checkFile($file) {
		if(!file_exists($file)) {
			$this->err = "Encoder failed to create a file at the specified location";
			return false;
		}
		else return true;
	}
}

class ffmpegEncode extends Encode {
	var $bin = "/usr/local/bin/ffmpeg";

	private function convertToFLV() {
		$size		= ($this->aspect == "16x9")?"512x288":"320x240";
		$this->outputfile = $this->outputpath."/".$this->basename.".flv";
		echo "encoding flv...";
		exec("/usr/local/bin/ffmpeg -i ".$this->inputfile." -s ".$size." -ar 22050 -b 550k -async 1 -y -acodec libmp3lame -vcodec flv ".$this->outputfile." 2>&1", $error_array);
	}

	private function convertToMP4() {
		$podsize	= ($this->aspect == "16x9")?"640x360":"640x480";
		$xpodsize	= ($this->aspect == "16x9")?"16:9":"4:3";
		$this->outputfile = $this->outputpath."/".$this->basename.".m4v";
		echo "encoding h.264...";
		exec("$bin -i ".$this->inputfile." -an -pass 1 -s ".$podsize.
			 " -vcodec libx264 -vpre fastfirstpass -vpre ipod640 -b 1500k -bt 1500k -aspect ".$xpodsize." -threads 0 -f ipod -y /dev/null 2>&1 &&".
			/* Second pass */
			 "/usr/local/bin/ffmpeg -i ".$this->inputfile." -pass 2 -acodec libfaac -ab 128k -ac 2 -vcodec libx264 -vpre hq -vpre ipod640 -b 1500k -bt 1500k -s ".$podsize.
			 " -aspect ".$xpodsize." -threads 0 -f ipod -async 1 -y ".$this->outputfile." 2>&1", $error_array);
	}

	private function convertToMP3() {
		$this->outputfile = $this->outputpath."/".$this->basename.".mp3";
		echo "encoding audio...";
		exec("$bin -i ".$this->inputfile." -ar 44100 -ab 96k -y -acodec libmp3lame ".$this->outputfile." 2>&1", $error_array);
	}

	public function createThumb() {

	}

}
?>
