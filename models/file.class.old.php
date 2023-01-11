<?php

class FileModel {
	public $id = 0;
	public $username = '';
	public $filename =''; //the name of the uploaded file
	public $filesize = 0;
	public $type = '';
	public $categoryid = 0;
	public $submit_date = '';
	public $completed = 5; //change the default value to 0 when not testing.
	public $creator = ''; // the real name of the creator
	public $title = '';
	public $subtitle = '';
	public $aspect = '16x9';
	public $submitted_by = 0; //the userid of the submitter
	public $description = ''; //full text description of the content on the video (UTF-8)
	public $orig_broadcast_date = '';
	public $thumbnail_time = '00:00:15';
    public $topics = ''; //a serialized list of numbers

	public function __construct(){
		
	}
}

?>