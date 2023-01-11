<?php
include_once('../configure.php');

class Connection {

	/**
	 * Database host address
	 * @access private
	 * @var string
	 */
	private $host=REP_HOST;

	/**
	 * Database Port
	 * @access private
	 * @var string
	 */
	private $port=REP_PORT;

	/**
	 * Database User to connect as
	 * @access private
	 * @var string
	 */
	private $user=REP_USER;

	/**
	 * Database password or secret for user
	 * @access private
	 * @var string
	 */
	private $secret=REP_PASS;

	/**
	 * Database to access
	 * @access private
	 * @var string
	 */
	private $database=REP_DATABASE;

	/**
	 * Database link once connected
	 * @access private
	 * @var null|false|resource
	 */
	public	$link = null;
	
	/**
	 * Base constructor for a database object
	 *
	 * @access public
	 * @param string $db
	 * @param string $port
	 * @param string $u
	 * @param string $pw
	 * @return boolean if false sets err
	 */
	 public function __construct ($db=REP_DATABASE, $port=REP_PORT, $u=REP_USER, $pw=REP_PASS) {
		$this->port=$port;
		$this->user = $u;
		$this->secret = $pw;
		if ($this->port !== '3306') $this->host .= ":".$this->port;
		
		$this->link = @mysql_connect($this->host, $this->user, $this->secret);
		if (!$this->link){
			$this->err = 'Could not connect to server: ' . mysql_error();
			return false;
		}
		$this->database = $db;
		@mysql_set_charset('utf8'); //important because the server values are all over the place!

		if (!@mysql_select_db($this->database, $this->link)){
			$this->err = 'Could not connect to database: ' . mysql_error();
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Change to a different database
	 *
	 * @access public
	 * @param string $database
	 * @return boolean if false sets err
	 */
	public function changeDB($database){
		if (!mysql_select_db($database, $this->link)){
			$this->err = 'Could not connect to database, reconnected to default database ('.$this->database.'): ' . mysql_error();
			return false;
		} else {
			$this->database = $database;
			return true;
		}

		
	}

	/**
	 * Create a down and dirty SQL statement and store it in the object for query
	 * @param string $column a comma separated list of columns to return
	 * @param null|string $table The table name to read from
	 * @param string $params The parts that follow the WHERE portion of the statement
	 * @param string $order whether to order in ASC or DESC
	 * @return boolean Always returns true
	 */
	public function easy($column="*", $table=null, $params="1=1", $order='ASC') {
		$this->sql = "SELECT "			.	$column		.
					 " FROM "			.	$table		.
					 " WHERE "			.	$params		.
					 " ORDER BY id "	.	$order		;
		return true;

	}

	/**
	 * Store the SQL statement in the object
	 * @param string $sql The SQL statement
	 * @return boolean if false sets err
	 */
	public function setQuery($sql=null){
		if ($sql) {
			$this->sql = $sql;
			return true;
		} else {
			$this->err = "Please specify a query";
			return false;
		}
	}

	/**
	 * Run a query on the current stored statement and current connection
	 * @access private
	 * @return mixed if false sets err
	 */
	private function q() {
		if ($results = mysql_query($this->sql, $this->link)){
			return $results;
		} else {
			$this->err= "There was an error in your query:" . mysql_error($this->link);
			return false;
		}
	}

	public function loadObjectList($sql=null, $object_type='stdClass'){
		if ($sql) {
			$this->sql = $sql;
		}
		if (($results = $this->q()) && (mysql_num_rows($results) > 0 )){
			while ($row = mysql_fetch_object($results, $object_type /*'File' add a object here if needed*/)){
				$list[] = $row;
			}
			mysql_free_result($results);
			return $list;
		} else {
			$this->err .= "No results";
			return false;
		}
	}

	public function loadObject($sql=null, $object_type='stdClass'){
		if ($sql) {
			$this->sql = $sql;
		}
		if (($results = $this->q()) && (mysql_num_rows($results) > 0 )){
			$row = mysql_fetch_object($results, $object_type /*'File' add a object here if needed*/);
			$firstobj= $row;
			mysql_free_result($results);
			return $row;
		} else {
			$this->err .= "No results";
			return false;
		}
	}

	/**
	 * Close the current MySQL connection
	 */
	public function close() {
		mysql_close($this->link);
	}
	
}