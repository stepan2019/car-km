<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class bulksms {

	var $table;
	var $error;
	var $tmp;
	var $name;

	public function __construct()
	{
		global $db;
		global $config_table_prefix;
		$arr = $db->fetchAssoc("select `gateway_table`, `gateway_ret_table` from ".TABLE_SMS_GATEWAYS." where `gateway_code` like 'bulksms'");

		$this->table = $config_table_prefix.$arr['gateway_table'];
		$this->ret_table = $config_table_prefix.$arr['gateway_ret_table'];
		$this->name = "bulksms";

	}

	function getTable() {
		
		global $db;
		global $config_table_prefix;
		$table = $config_table_prefix.$db->fetchRow("select `gateway_table` from ".TABLE_SMS_GATEWAYS." where `gateway_code` like 'bulksms'");
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

		if(!isset($_POST["bulksms_username"]) || !$_POST["bulksms_username"]) $this->addError($lng['users']['errors']['username_missing'].'<br />');
		if(!isset($_POST["bulksms_password"]) || !$_POST["bulksms_password"]) $this->addError($lng['users']['errors']['password_missing'].'<br />');

		if($this->getError()!='') {

			$array_fields = array( "bulksms_username", "bulksms_password" );

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

		$array_fields = array( "bulksms_username", "bulksms_password" );
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
		if(!$array_settings['bulksms_username'] || !$array_settings['bulksms_password']) {
			$this->setError(str_replace("::PROCESSOR::", "BulkSMS", $lng['settings']['errors']['incomplete_smsg_settings']));
			return 0;
		}
		return 1;
	}

	function send($phone_no, $message, $object_id, $type='user') {

		$smsgw_settings = $this->getSettings();
		$username = $smsgw_settings['bulksms_username'];
		$password = $smsgw_settings['bulksms_password'];

		$url = 'https://bulksms.vsms.net/eapi/submission/send_sms/2/2.0';
		$port = 443;

		$post_body = $this->seven_bit_sms( $username, $password, $message, $phone_no );
		$result = $this->send_message( $post_body, $url, $port );
		
		$this->saveToDB($result, $object_id, $type);
		
		return $result['success'];
	}
	
	function saveToDB($result, $object_id, $type)
	{

		global $db;
		$addtosql = '';

		$keys = array('success', 'api_batch_id', 'details', 'http_status_code', 'api_status_code', 'api_message', 'transient_error');
		
		foreach($keys as $key) 
		{
			$addtosql .= "`".$key."`='".$result[$key]."',";
			
		}
		$addtosql = rtrim($addtosql,','); 

		$timestamp = date("Y-m-d H:i:s");
		$res = $db->query("INSERT INTO ".$this->ret_table." SET date='$timestamp', `object_id`='$object_id', `type`='$type', ".$addtosql);

		return 1;

	}

	function formatted_server_response( $result ) {
		$this_result = "";

		if ($result['success']) {
		$this_result .= "Success: batch ID " .$result['api_batch_id']. "API message: ".$result['api_message']. "\nFull details " .$result['details'];
		}
		else {
			$this_result .= "Fatal error: HTTP status " .$result['http_status_code']. ", API status " .$result['api_status_code']. " API message " .$result['api_message']. " full details " .$result['details'];

			if ($result['transient_error']) {
			$this_result .=  "This is a transient error - you should retry it in a production environment";
		}
		}
	return $this_result;
	}
	
	function seven_bit_sms ( $username, $password, $message, $msisdn ) {
		$post_fields = array (
		'username' => $username,
		'password' => $password,
		'message'  => $this->character_resolve( $message ),
		'msisdn'   => $msisdn,
		'allow_concat_text_sms' => 0, # Change to 1 to enable long messages
		'concat_text_sms_max_parts' => 2
		);
		return $this->make_post_body($post_fields);
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
	
	function character_resolve($body) {
		$special_chrs = array(
		'?'=>'0xD0', '?'=>'0xDE', '?'=>'0xAC', '?'=>'0xC2', '?'=>'0xDB',
		'?'=>'0xBA', '?'=>'0xDD', '?'=>'0xCA', '?'=>'0xD4', '?'=>'0xB1',
		'¡'=>'0xA1', '£'=>'0xA3', '¤'=>'0xA4', '¥'=>'0xA5', '§'=>'0xA7',
		'¿'=>'0xBF', 'Ä'=>'0xC4', 'Å'=>'0xC5', 'Æ'=>'0xC6', 'Ç'=>'0xC7',
		'É'=>'0xC9', 'Ñ'=>'0xD1', 'Ö'=>'0xD6', 'Ø'=>'0xD8', 'Ü'=>'0xDC',
		'ß'=>'0xDF', 'à'=>'0xE0', 'ä'=>'0xE4', 'å'=>'0xE5', 'æ'=>'0xE6',
		'è'=>'0xE8', 'é'=>'0xE9', 'ì'=>'0xEC', 'ñ'=>'0xF1', 'ò'=>'0xF2',
		'ö'=>'0xF6', 'ø'=>'0xF8', 'ù'=>'0xF9', 'ü'=>'0xFC',
		);

		$ret_msg = '';
		if( mb_detect_encoding($body, 'UTF-8') != 'UTF-8' ) {
			$body = utf8_encode($body);
		}
		for ( $i = 0; $i < mb_strlen( $body, 'UTF-8' ); $i++ ) {
			$c = mb_substr( $body, $i, 1, 'UTF-8' );
			if( isset( $special_chrs[ $c ] ) ) {
				$ret_msg .= chr( $special_chrs[ $c ] );
			}
			else {
				$ret_msg .= $c;
			}
		}
		return $ret_msg;
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
		$curl_info = curl_getinfo( $ch );

		$sms_result = array();
		$sms_result['success'] = 0;
		$sms_result['details'] = '';
		$sms_result['transient_error'] = 0;
		$sms_result['http_status_code'] = $curl_info['http_code'];
		$sms_result['api_status_code'] = '';
		$sms_result['api_message'] = '';
		$sms_result['api_batch_id'] = '';

		if ( $response_string == FALSE ) {
			$sms_result['details'] .= "cURL error: " . curl_error( $ch ) . "\n";
		} elseif ( $curl_info[ 'http_code' ] != 200 ) {
			$sms_result['transient_error'] = 1;
			$sms_result['details'] .= "Error: non-200 HTTP status code: " . $curl_info[ 'http_code' ] . "\n";
		}
		else {
			$sms_result['details'] .= "Response from server: $response_string\n";
			$api_result = explode( '|', $response_string );
			$status_code = $api_result[0];
			$sms_result['api_status_code'] = $status_code;
			$sms_result['api_message'] = $api_result[1];
			if ( count( $api_result ) != 3 ) {
				$sms_result['details'] .= "Error: could not parse valid return data from server.\n" . count( $api_result );
			} else {
				if ($status_code == '0') {
				$sms_result['success'] = 1;
				$sms_result['api_batch_id'] = $api_result[2];
				$sms_result['details'] .= "Message sent - batch ID $api_result[2]\n";
			}
			else if ($status_code == '1') {
				# Success: scheduled for later sending.
				$sms_result['success'] = 1;
				$sms_result['api_batch_id'] = $api_result[2];
			}
			else {
				$sms_result['details'] .= "Error sending: status code [$api_result[0]] description [$api_result[1]]\n";
			}

		}
	}
	curl_close( $ch );

	return $sms_result;
	}
	
	function getReturnInfo($object_id, $type="user") {
	
		global $db;
		$result = $db->fetchAssoc("select * from ".$this->ret_table." where `type`='$type' and `object_id`='$object_id' order by `date` desc limit 1");
		return $result;
	
	}
	
}
?>
