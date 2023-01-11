<?php
include_once('../configure.php');
class User {
	public 	$id = 0, 
			$name,
			$username,
			$email,
			$usertype;
	var		$authenticated = false;
	protected 	$password,
				$params,
				$block,
				$sendEmail,
				$gid,
				$registerDate,
				$lastvisitDate,
				$activation;
	
	public function __construct($type=null, $search=null){
		switch ($type) {
			case 'id':
				$user = $this->loadUserByID($search);
				break;
			case 'username':
				$user = $this->loadUserByUsername($search);
				break;
		}
		if ($user !== FALSE) {
			return false;
		}
		return true;
	}

	public function copyFromJoomla($user_object){
	    foreach ($user_object as $user_propertyName=>$user_propertyValue) {
		 if ( stripos($user_propertyName, '_') === FALSE){//don't copy private objects
		    $this->{$user_propertyName} = $user_propertyValue;
		 }

	    }
	    return true;
	}

	private function loadUserByID($uid) {
	    $db = new Connection(CRT_DATABASE, CRT_PORT, CRT_USER, CRT_PASS);
		$uid = mysql_real_escape_string($uid, $db->link);
	    $realuser = $db->loadObject('SELECT * from `jos_users` WHERE id="'.$uid .'" LIMIT 0,1', 'User');
		if (!$realuser) {
			//echo $db->err.":".$db->sql;
			return false;
		} else {
			$this->bind($realuser);
		}
		$db->close();
	    return $realuser;
	}

	private function loadUserByUsername($uname) {
	    $db = new Connection(CRT_DATABASE, CRT_PORT, CRT_USER, CRT_PASS);
		$uname = mysql_real_escape_string($uname, $db->link); //prevent sql injection attacks
	    $realuser = $db->loadObject('SELECT * from `jos_users` WHERE `username` = "'.$uname.'" LIMIT 0,1' , 'User');
		if (!$realuser) {
		//	echo $db->err;
			return false;
		} else {
			$this->bind($realuser);
		}
		$db->close();
	    return $realuser;
	}

	private function bind($userObject) {
		foreach ($userObject as $p=>$v) {
				$this->{$p} = $v;
		}
		return true;
	}
	
	public function auth($pw) {
		if ($this->id != 0){
			//do this
			if ($this->checkPassword($this->password, $pw)){
				$this->authenticated = true;
			} else {
				$this->err = "Your password has not been entered correctly.";
				$this->authenticated = false;
			}
		}
		return $this->authenticated;
	}

	private function checkPassword ($stored_password, $user_password) {
		$parts=@explode(":",$stored_password);
		$salt=$parts[1];
		$pw_hash = md5($user_password.$salt) . ":" . $salt;
		if ( $stored_password == $pw_hash){
		 return true;
		} else return false;
	}

}

class JUser extends User {
    
}


?>
