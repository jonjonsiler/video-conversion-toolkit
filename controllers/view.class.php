<?php
include_once('../configure.php');
/* 
 * This file is the view controller
 * it loads the appropriate view template.
 */
class ViewController {

    public $allowed_filetypes = array('.mov','.avi','.vob','.mp4','.m4v');

    public function loadData () {
		$this->db = new Connection(REP_DATABASE);
		$this->getCategories();
		$this->getSubcategories();
		$this->getTopics();
		$this->db->close();
		return true;
    }

    private function getCategories(){
        $categories 	=   $this->db->loadObjectList("SELECT * FROM categories WHERE `parent` = 0 ORDER BY ctitle ASC", 'VideoCategory');
		$this->categories = $categories;
		return true;
    }

    private function getSubcategories(){
		$subcategories 	= $this->db->loadObjectList("SELECT * FROM categories WHERE `parent` != 0 ORDER BY ctitle ASC", 'VideoCategory');
		foreach ($subcategories as $cat ){
			$subs[$cat->parent][] = $cat;
		}
		$this->subcategories = $subs;
		return true;
    }

    private function getTopics () {
		$alltopics  = $this->db->loadObjectList("SELECT * FROM `topics` WHERE 1 ORDER BY `parent` AND `id` ASC", 'VideoTopic');
		foreach ($alltopics as $topic){
			if ($topic->parent != 0) {
				$subtopics[$topic->parent][]= $topic;
			} else $topics[] = $topic;
		}
		$this->topics = $topics;
		$this->subtopics = $subtopics;
		return true;
    }

	public function redirect($url="http://www.oeta.tv/administrator/") {
		header("Location:".$url);
	}

    public function display($template='upload', $type='html'){
		if ($template != 'error') {
			$this->loadData();
		}
		include_once( "views/". $template .".". $type . ".php" );
    }
}



?>
