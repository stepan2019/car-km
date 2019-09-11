<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class altiria {

	var $table;
	var $error;
	var $tmp;
	var $name;

	public function __construct()
	{
		global $db;
		global $config_table_prefix;
		$arr = $db->fetchAssoc("select `gateway_table`, `gateway_ret_table` from ".TABLE_SMS_GATEWAYS." where `gateway_code` like 'altiria'");

		$this->table = $config_table_prefix.$arr['gateway_table'];
		$this->ret_table = $config_table_prefix.$arr['gateway_ret_table'];
		$this->name = "altiria";

	}

	function getTable() {
		
		global $db;
		global $config_table_prefix;
		$table = $config_table_prefix.$db->fetchRow("select `gateway_table` from ".TABLE_SMS_GATEWAYS." where `gateway_code` like 'altiria'");
		return $table;
	}

	function setTable($table) {

		$this->table = $table;
	
	}

	
	function getSettings() {

		global $db;
		$result = $db->fetchAssoc("select * from ".$this->table);

		return $result;

	}

	function setDebug($val) {

		$this->debug = $val;

	}

	function getError() {
	
		return $this->error;

	}

	function addError($str) {

		$this->error.=	$str;

	}

	function setError($str) {

		$this->error=$str;

	}

	function getTmp() {
	
		return $this->tmp;

	}

	function check_form() {

		global $lng;

		if(!isset($_POST["domainId"]) || !$_POST["domainId"]) $this->addError('Please enter domain id!<br />');
		if(!isset($_POST["replacePOSTsms"]) || !$_POST["replacePOSTsms"]) $this->addError('Please enter replacement URI<br />');
		if(!isset($_POST["altiria_login"]) || !$_POST["altiria_login"]) $this->addError($lng['users']['errors']['username_missing'].'<br />');
		if(!isset($_POST["altiria_passwd"]) || !$_POST["altiria_passwd"]) $this->addError($lng['users']['errors']['password_missing'].'<br />');

		if($this->getError()!='') {

			$array_fields = array( "altiria_username", "altiria_password" );

			foreach ($array_fields as $field) {
				if(isset($_POST[$field])) $this->tmp[$field] = cleanStr($_POST[$field]);
			}
			
		}

	}

	function saveSettings() {

		global $lng;
		$this->error='';
		$this->tmp=array();

		$this->clean=array();
		$this->check_form();
		if($this->getError()!='') return 0;

		global $config_demo;
		if($config_demo==1) $this->addError($lng['general']['errors']['demo'].'<br />');

		global $db;
		$sql = "update ".$this->table." set ";

		$array_fields = array( "altiria_login", "altiria_passwd", "replacePOSTsms", "domainId" );
		$sep = "";
		
		foreach ($array_fields as $field) {

			if(isset($_POST[$field])) $this->clean[$field] = escape($_POST[$field]); else $this->clean[$field] = '';

			$sql.=$sep." `$field` = '".$this->clean[$field]."'";
			$sep=",";

		}

		$db->query($sql);
		return 1;
	}

	function correctSettings() {
		
		global $lng;
		$array_settings = $this->getSettings();
		if(!$array_settings['altiria_login'] || !$array_settings['altiria_passwd'] || !$array_settings['replacePOSTsms'] || !$array_settings['domainId']) {
			$this->setError(str_replace("::PROCESSOR::", "BulkSMS", $lng['settings']['errors']['incomplete_smsg_settings']));
			return 0;
		}
		return 1;
	}

	function send($phone_no, $message, $object_id, $type='user') {
		$smsgw_settings = $this->getSettings();
		
		$url = 'http://altiria.net'.$smsgw_settings['replacePOSTsms'];
		$port = 80;

				$post_fields = array (
		'cmd'   => 'sendsms',
		'domainId'   => $smsgw_settings['domainId'],
		'login' => $smsgw_settings['altiria_login'],
		'passwd' => $smsgw_settings['altiria_passwd'],
		'msg'  => utf8_encode(substr($message,0,160)),
		'dest'   => str_replace("+", "", $phone_no)
		);
	
		$post_body = $this->make_post_body($post_fields);
		
		$result = $this->send_message( $post_body, $url, $port );
		
		$result_array['success'] = 0;
		$result_array['error'] = '';
		
		if(strstr($result, "OK")) $result_array['success'] = 1;
		else  $result_array['error'] = $result;
		
		$this->saveToDB($result_array, $object_id, $type);

		return $result_array['success'];
	}
	
	function saveToDB($result, $object_id, $type)
	{

		global $db;
		$addtosql = '';

		$timestamp = date("Y-m-d H:i:s");
		$res = $db->query("INSERT INTO ".$this->ret_table." SET date='$timestamp', `object_id`=$object_id, `type`='$type', `success`={$result['success']}, `error_message`='{$result['error']}' ");

		return 1;

	}

	function make_post_body($post_fields) {
	
		/*$stop_dup_id = make_stop_dup_id();
		if ($stop_dup_id > 0) {
			$post_fields['stop_dup_id'] = make_stop_dup_id();
		}*/
		$post_body = '';
		foreach( $post_fields as $key => $value ) {
			$post_body .= urlencode( $key ).'='.urlencode( $value ).'&';
		}
		$post_body = rtrim( $post_body,'&' );

		return $post_body;
	}
	
	function send_message ( $post_body, $url, $port ) {
		/*
		* Do not supply $post_fields directly as an argument to CURLOPT_POSTFIELDS,
		* despite what the PHP documentation suggests: cUrl will turn it into in a
		* multipart formpost, which is not supported:
		*/

		$ch = curl_init( );
		curl_setopt ( $ch, CURLOPT_URL, $url );
		curl_setopt ( $ch, CURLOPT_PORT, $port );
		curl_setopt ( $ch, CURLOPT_POST, 1 );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt ( $ch, CURLOPT_POSTFIELDS, $post_body );
		// Allowing cUrl funtions 20 second to execute
		curl_setopt ( $ch, CURLOPT_TIMEOUT, 20 );
		// Waiting 20 seconds while trying to connect
		curl_setopt ( $ch, CURLOPT_CONNECTTIMEOUT, 20 );

		$response_string = curl_exec( $ch );
//		$curl_info = curl_getinfo( $ch );
	
		curl_close( $ch );

		return $response_string;
		
	}
	
}
?>
