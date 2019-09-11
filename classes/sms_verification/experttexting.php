<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class experttexting {

	var $table;
	var $error;
	var $tmp;
	var $name;

	public function __construct()
	{
		global $db;
		global $config_table_prefix;
		$arr = $db->fetchAssoc("select `gateway_table`, `gateway_ret_table` from ".TABLE_SMS_GATEWAYS." where `gateway_code` like 'experttexting'");

		$this->table = $config_table_prefix.$arr['gateway_table'];
		$this->ret_table = $config_table_prefix.$arr['gateway_ret_table'];
		$this->name = "experttexting";

	}

	function getTable() {
		
		global $db;
		global $config_table_prefix;
		$table = $config_table_prefix.$db->fetchRow("select `gateway_table` from ".TABLE_SMS_GATEWAYS." where `gateway_code` like 'experttexting'");
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

		if(!isset($_POST["experttexting_username"]) || !$_POST["experttexting_username"]) $this->addError($lng['users']['errors']['username_missing'].'<br />');
		if(!isset($_POST["experttexting_password"]) || !$_POST["experttexting_password"]) $this->addError($lng['users']['errors']['password_missing'].'<br />');
		if(!isset($_POST["experttexting_api_key"]) || !$_POST["experttexting_api_key"]) $this->addError($lng['settings']['errors']['experttexting_api_key_missing'].'<br />');
		if(!isset($_POST["experttexting_from"]) || !$_POST["experttexting_from"]) $this->addError($lng['settings']['errors']['experttexting_from_missing'].'<br />');

		if($this->getError()!='') {

			$array_fields = array( "experttexting_username", "experttexting_password", "experttexting_api_id", "experttexting_from" );

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

		$array_fields = array( "experttexting_username", "experttexting_password", "experttexting_api_key", "experttexting_from" );
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
		if(!$array_settings['experttexting_username'] || !$array_settings['experttexting_password'] || !$array_settings['experttexting_api_key'] || !$array_settings['experttexting_from']) {
			$this->setError(str_replace("::PROCESSOR::", "Experttexting", $lng['settings']['errors']['incomplete_smsg_settings']));
			return 0;
		}
		return 1;
	}

	function send($phone_no, $message, $object_id, $type='user') {

		$smsgw_settings = $this->getSettings();
		$username = urlencode($smsgw_settings['experttexting_username']);
		$password = urlencode($smsgw_settings['experttexting_password']);
		$api_key = urlencode($smsgw_settings['experttexting_api_key']);
 		$from = urlencode($smsgw_settings['experttexting_from']);
 		$phone_no = substr($phone_no, 1);
 		// remove the + sign
		$to = urlencode($phone_no);
		$message = urlencode($message);

		$url = "https://www.experttexting.com/ExptRestApi/sms/json/Message/Send"."?username=$username&password=$password&api_key=$api_key&from=$from&to=$to&text=$message";
		$result = array("success"=>1, "status"=>0, "message_id"=>'', 'price'=>0, 'error_message'=>'');

		if(_isCurl()) {
 			$ch = curl_init();
		
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			if(curl_error($ch)) {  
				$result['success'] = 0;
				$result['error_string'] = "cURL error: " . curl_error( $ch );
				$result['details'] .= $result['error_string'] . "\n";

			}
		
			if($result['success']) {
				$data = curl_exec($ch);
			}
			curl_close($ch);

		} else 

			$data = file_get_contents($url);

		// interpret data
		if($data) {
		
			$data = json_decode($data, true);
			$result['status'] = $data['Status'];
			
			if($result['status']==0) { // success
				$result['message_id'] = $data['Response']['message_id'];
				$result['price'] = $data['Response']['price'];
				$result['details'] = "Message sent - message ID {$result['message_id']}\n";
			}
			else {
				$result['error_message'] = $data['ErrorMessage'];
				$result['details'] = "Error sending message: \"{$result['error_message']}\"";
			}
		
		} // end if data

		// log data
		$this->saveToDB($result, $object_id, $type);
	
	}

	function saveToDB($result, $object_id, $type)
	{

		global $db;
		$addtosql = '';

		$keys = array('success', 'status', 'message_id', 'price', 'error_message', 'details');
		
		foreach($keys as $key) 
		{
			$addtosql .= "`".$key."`='".$result[$key]."',";
			
		}
		$addtosql = rtrim($addtosql,','); 

		$timestamp = date("Y-m-d H:i:s");
		$res = $db->query("INSERT INTO ".$this->ret_table." SET date='$timestamp', ".$addtosql);

		return 1;

	}

	function getReturnInfo($object_id, $type="user") {
	
		global $db;
		$result = $db->fetchAssoc("select * from ".$this->ret_table." where `type`='$type' and `object_id`='$object_id' order by `date` desc limit 1");
		return $result;
	
	}
}
?>
