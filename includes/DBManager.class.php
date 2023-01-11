<?php
class DBManager
{
    var $connection = '';
    var $queryCounter = 0;
    var $totalTime = 0;
    var $errorCode = 0;
    var $errorMsg = '';
    var $resultSet = '';

    function DBManager($host, $user, $pass, $db=null)
    {
        $startTime = $this->getMicroTime();

        // Try to make a connection to the server
        if (!$this->connection = @mysql_connect($host, $user, $pass, true)) {
            $this->errorCode = mysql_errno();
            $this->errorMsg = mysql_error();
            return false;
        }
        $this->totalTime += $this->getMicroTime() - $startTime;
		mysql_set_charset('utf8');
		if ($db) {
			return $this->DbConnect($db) ;
		}
        return true;
    }

    function DbConnect($db)
    {
        // Now select the database
        if (!@mysql_select_db($db, $this->connection)) {
            $this->errorCode = mysql_errno();
            $this->errorMsg = mysql_error();
            @mysql_close($this->connection);
            return false;
        } else {
            return true;
        }
    }

    function executeQuery($sql)
    {
        $startTime = $this->getMicroTime();

        ++$this->queryCounter;

        if (!$this->resultSet = @mysql_query($sql, $this->connection)) {
            $this->errorCode = mysql_errno();
            $this->errorMsg = mysql_error();
            $this->totalTime = $this->getMicroTime() - $startTime;
            return false;
        }

        $this->totalTime += $this->getMicroTime() - $startTime;

        return $this->resultSet;
    }

    function getAffectedRows()
    {
        return @mysql_affected_rows($this->connection);
    }

    function getSelectedRows()
    {
        return @mysql_num_rows($this->resultSet);
    }

    function getInsertId()
    {
        return @mysql_insert_id($this->connection);
    }

    function loadResult()
    {
        $array = array();
        while ($row = mysql_fetch_object($this->resultSet)) {
            $array[] = $row;
        }
        mysql_free_result($this->resultSet);

        return $array;
    }

    function getErrrorCode()
    {
        return $this->errorCode;
    }

    function getErrorMessage()
    {
        return $this->errorMsg;
    }

    function getDBTime()
    {
        return round($this->totalTime, 6);
    }

    function getSqlCount()
    {
        return $this->queryCounter;
    }

    function getMicroTime()
    {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }

    function DbClose()
    {
        //close db if connected
        if ($this->connection) {
            @mysql_close($this->connection);
        }
    }
	
	/**
	* Get a quoted database escaped string
	*
	* @param    string    A string
	* @param    boolean    Default true to escape string, false to leave the string unchanged
	* @return    string
	* @access public
	*/
	function Quote( $text, $escaped = true )
	{
		return '\''.($escaped ? $this->getEscaped( $text ) : $text).'\'';
	}
	
	/**
	* Get a database escaped string
	*
	* @param    string    The string to be escaped
	* @param    boolean    Optional parameter to provide extra escaping
	* @return    string
	* @access    public
	* @abstract
	*/
	function getEscaped( $text )
	{
		$string = mysqli_real_escape_string($this->connection, $text );
		return $string;
	}


}
?>
