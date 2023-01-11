<?php

class VideoCategory {
	public	$id,
			$category,
			$parent,
			$ctitle, //the realname title of the category
			$image, //location of the image associated with the feed
			$annotation, //description of the video category
			$folder,  //location where the media assets live
			$creator, //owner of this playlist
			$NOLA, //the nola_base code which is linked to this video category
			$link, //url for the category
			$icat, //itunes classification category information - serialized
			$itunes; //link to the itunes subscription page
}

class VideoTopic {
	public	$id,
			$name,
			$parent;
}

class VideoFile {
	public	$id = 0,
			$username = '',
			$filename ='', //the name of the uploaded file
			$filesize = 0,
			$type = '',
			$categoryid = 0,
			$submit_date = '',
			$completed = 5, //change the default value to 0 when not testing.
			$creator = '', // the real name of the creator
			$title = '',
			$subtitle = '',
			$aspect = '16x9',
			$submitted_by = 0, //the userid of the submitter
			$description = '', //full text description of the content on the video (UTF-8)
			$orig_broadcast_date = '',
			$thumbnail_time = '00:00:15',
			$topics = ''; //a serialized list of numbers

	public function __construct(){
		
	}
}


?>