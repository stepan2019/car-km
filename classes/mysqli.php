<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class db_mysql
{
	var $link;
	var $recent_link = null;
	var $sql = '';
	var $query_count = 0;
	var $error = '';
	var $errorPath='';
	var $errorStr = '';
	var $errorQuery = array();
	var $show_errors = false;
	var $debug = 1;
	var $limit;
	var $offset;

	public function __construct($nodb = 0, $nocharset=0)
        {

		global $config_db_server, $config_db_server_username, $config_db_server_password, $config_db_database, $config_db_charset, $config_db_port;

		$password = str_replace("$", "\$", $config_db_server_password);

		$this->link = @mysqli_connect($config_db_server, $config_db_server_username, $password, '', $config_db_port);

		$this->errorQuery = array();
		$this->error = '';
		$this->limit = '';
		$this->offset = '';
		$this->errorPath='';

		if (!$this->link)
		{
			$this->error="Could not connect to server: $config_db_server Error: ".@mysqli_connect_error();
			$this->errorPath = $this->getErrorPath();
			return;
		}

		if(!$nodb) {
		if (!@mysqli_select_db($this->link, $config_db_database))
		{
			$this->errorPath = $this->getErrorPath();
			$this->error="Could not select database: $config_db_database";
			return;
		}
		}

		$this->recent_link =& $this->link;

		if(!$nocharset)$this->set_charset();
		$this->init_options();

		return $this->link;
	}

	function selectDB ($db_name) {

		global $config_db_database;
		if (!@mysqli_select_db($this->link, $config_db_database))
		{
			$this->errorPath = $this->getErrorPath();
			$this->error="Could not select database: $config_db_database";
			return;
		}
	
	}
	
	function init_options() {

		global $mysql_locale, $mysql_names, $mysql_big_selects;
		if($mysql_locale!='')
			@mysqli_query($this->link, "SET lc_time_names = '".$mysql_locale."'");

//		if($mysql_names!='')
//			mysqli_query($this->link, "SET NAMES $mysql_names");
		if($mysql_big_selects)
			@mysqli_query($this->link, "SET OPTION SQL_BIG_SELECTS = 1");

		//@mysqli_query($this->link, "SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
			
	}

	function set_charset() {

		global $config_db_charset, $config_db_collation, $set_charset;

		if(!$config_db_charset) return;

		if($set_charset) {
		
		if( function_exists( 'mysqli_set_charset' ) )  {

			@mysqli_set_charset( $this->link, $config_db_charset );

			if($config_db_collation) @mysqli_query($this->link, "COLLATE $config_db_collation");
		} 
		else {

			@mysqli_query($this->link, "SET NAMES $config_db_charset");

			if($config_db_collation) @mysqli_query($this->link, "COLLATE $config_db_collation");

		}
		} // end if set_charset
	}

	function test() {

		return 1;

	}

	function getError() {

		global $config_debug;
		$str="<span>Database query error. </span>";
		if($config_debug) {
			$str.="<span>The error was: </span>".$this->error;
			$str.="<br><span>SQL query/queries : </span>".$this->printErrorQuery()."<span>Error path:</span> ".$this->errorPath;
		}
		return $str;

	}

	function printErrorQuery() {

		$result='';
		foreach ($this->errorQuery as $str) $result.=$str."<br>";
		return $result;
	}

	function setSQL($sql) {
		$this->sql = $sql;
	}

	function getSQL() {

		return $this->sql;

	}

	function query($sql='')
	{
 		if(trim($sql)) $this->sql=$sql;
		if(!$this->sql) return false;
		if (!is_resource($this->link)) {
			//echo "Not resource!!".$this->sql;
			//$this->error(); 
			//$this->errorPath = $this->getErrorPath();

			//return false;
		}
		$this->recent_link =& $this->link;
		if ($this->limit > 0 || $this->offset > 0) {
			$this->sql .= ' LIMIT '.$this->offset.', '.$this->limit;
		}

		$result = @mysqli_query($this->link, $this->sql);
		$this->query_count++;

		if(!$result) {

			$this->error(); 
			$this->errorPath = $this->getErrorPath();
			return false;

		}

		return $result;
	}

	function fetchRow($sql='')
	{
		if($sql) $this->sql=$sql;
		if (!($result = $this->query())) {
			return null;
		}
		$ret = null;
		if ($row = @mysqli_fetch_row( $result )) {
			$ret = $row[0];
		}
		$this->freeResult( $result );
		return $ret;
	}

	function fetchRowRes($result)
	{
		$ret = null;
		if ($row = @mysqli_fetch_row( $result )) {
			$ret = $row[0];
		}
		return $ret;
	}

	function fetchAssocRes($result)
	{
		$ret = null;
		if ($row = @mysqli_fetch_assoc( $result )) {
			$ret = $row;
		}
		return $ret;
	}

	function fetchRowList($sql='')
	{
		if($sql) $this->sql=$sql;
		if (!($result = $this->query())) {
			return null;
		}
		$array = array();
		while ($row = @mysqli_fetch_row( $result )) {
			$array[] = $row[0];
		}
		$this->freeResult( $result );
		return $array;
	}

	function fetchAssoc($sql='')
	{
		if($sql) $this->sql=$sql;
		if (!($result = $this->query())) {
			return null;
		}
		$ret = null;
		if ($row = @mysqli_fetch_assoc( $result )) {
			$ret = $row;
		}
		$this->freeResult( $result );
		return $ret;
	}

	function fetchAssocList($sql='')
	{

		if($sql) $this->sql=$sql;
		if (!($result = $this->query())) {
			return null;
		}
		$array = array();
		while (null !== ($row = @mysqli_fetch_assoc( $result ))) {
			$array[] = $row;
		}
		$this->freeResult( $result );
		return $array;
	}

	function fetchArray($sql='')
	{
		if($sql) $this->sql=$sql;
		if (!($result = $this->query())) {
			return null;
		}
		$ret = null;
		if ($row = @mysqli_fetch_array( $result )) {
			$ret = $row;
		}
		$this->freeResult( $result );
		return $ret;
	}

	function numRows($result)
	{
		return @mysqli_num_rows($result);
	}

	function affectedRows()
	{
		return @mysqli_affected_rows($this->recent_link);
	}

	function numQueries()
	{
		return $this->query_count;
	}

	function insertId()
	{
		return @mysqli_insert_id($this->link);
	}

	function freeResult($result)
	{
		return @mysqli_free_result($result);
	}

	function setOffset($off)
	{
		$this->offset=$off;
	}

	function setLimit($lim)
	{
		$this->limit=$lim;
	}

	function close()
	{
		//$this->sql = '';
		//return $this->link->close();
		return mysqli_close($this->link);
	}

	function error($err='')
	{

		if($err) $this->error .= $err;
		else { 
			if(is_null($this->recent_link)) return;
			else $this->error = " errno : ".@mysqli_errno($this->recent_link)." ".@mysqli_error($this->recent_link);
		}
		$no = count($this->errorQuery);
		$this->errorQuery[$no] = $this->sql;

		// log error
/*
		global $config_abs_path;
		$mysql_error_log = $config_abs_path."/log/mysql_error.log";
		$mfile = @fopen($mysql_error_log, "a");
		if($mfile) { 

			fwrite($mfile, "\n".date('Y-m-d H:i:s')." - ".getRemoteIp()." - Error thrown in ".$_SERVER['PHP_SELF']." by query: ".$this->sql." \n         the error was: ".mysql_errno($this->recent_link)." ".mysql_error($this->recent_link));
			@fclose($mfile);
		}
*/
		return $this->error;
	}

	function getErrorPath()
	{
		if ($_SERVER['REQUEST_URI'])
		{
			$errorpath = $_SERVER['REQUEST_URI'];
		}
		else
		{
			if ($_SERVER['PATH_INFO'])
			{
				$errorpath = $_SERVER['PATH_INFO'];
			}
			else
			{
				$errorpath = $_SERVER['PHP_SELF'];
			}

			if ($_SERVER['QUERY_STRING'])
			{
				$errorpath .= '?' . $_SERVER['QUERY_STRING'];
			}
		}

		if (($pos = strpos($errorpath, '?')) !== false)
		{
			$errorpath = rawurldecode(substr($errorpath, 0, $pos)) . substr($errorpath, $pos);
		}
		else
		{
			$errorpath = rawurldecode($errorpath);
		}
		return $_SERVER['HTTP_HOST'] . $errorpath;
	}

	function getTableFields($table) {

		$array = array();
		$res = @mysqli_query($this->link, "DESCRIBE ".$table);
		$i = 0;
		while($row = @mysqli_fetch_row($res)) {
			$array[$i] = $row[0];
			$i++;
		}
		return $array;
	}

	function getFullTableFields($table) {

		$array = array();
		$res = @mysqli_query($this->link, "DESCRIBE ".$table);
		$i = 0;
		while($row = @mysqli_fetch_assoc($res)) {
			$array[$i]['Field'] = $row['Field'];
			$array[$i]['Type'] = $row['Type'];
			$i++;
		}
		return $array;

	}

	function getTableCSVFields($table) {

		$fields = '';
		$res = @mysqli_query($this->link, "DESCRIBE ".$table);
		$i = 0;
		while($row = @mysqli_fetch_row($res)) {
			if($i) $fields.=',';
			$fields .= $row[0];
			$i++;
		}
		return $fields;
	}

	function getTextTableFields($table) {

		$array = array();
		$res = @mysqli_query($this->link, "DESCRIBE ".$table);
		$i = 0;
		while($row = @mysqli_fetch_row($res)) {
			if(!strstr($row[1], "varchar") && $row[1]!="text") continue;
			$array[$i] = $row[0];
			$i++;
		}
		return $array;

	}

	function getTables($prefix='', $not_prefix='') {

		if (!($result = $this->fetchRowList('SHOW TABLES'))) return 0;

		if(!$prefix) return $result;

		$i=0;
		$arr = array();
		foreach($result as $row)
			if(preg_match("/^$prefix/", $row) && !preg_match("/^$not_prefix/", $row)) $arr[$i++] = $row;

		return $arr;

	}

	function db_version() {

		return preg_replace( '/[^0-9.].*/', '', @mysqli_get_server_info( $this->link ) );

	}

		
	function sql_error($link) {
	
		return @mysqli_error($link);
		
	}

	function my_mysql_real_escape_string($val) {

		return mysqli_real_escape_string($this->link, $val);
	
	}
	
}

?>