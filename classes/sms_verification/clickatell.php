<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class clickatell {

	var $table;
	var $error;
	var $tmp;
	var $name;

	public function __construct()
	{
		global $db;
		global $config_table_prefix;
		$arr = $db->fetchAssoc("select `gateway_table`, `gateway_ret_table` from ".TABLE_SMS_GATEWAYS." where `gateway_code` like 'clickatell'");

		$this->table = $config_table_prefix.$arr['gateway_table'];
		$this->ret_table = $config_table_prefix.$arr['gateway_ret_table'];
		$this->name = "clickatell";

	}

	function getTable() {
		
		global $db;
		global $config_table_prefix;
		$table = $config_table_prefix.$db->fetchRow("select `gateway_table` from ".TABLE_SMS_GATEWAYS." where `gateway_code` like 'clickatell'");
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

		if(!isset($_POST["clickatell_username"]) || !$_POST["clickatell_username"]) $this->addError($lng['users']['errors']['username_missing'].'<br />');
		if(!isset($_POST["clickatell_password"]) || !$_POST["clickatell_password"]) $this->addError($lng['users']['errors']['password_missing'].'<br />');
		if(!isset($_POST["clickatell_api_id"]) || !$_POST["clickatell_api_id"]) $this->addError($lng['settings']['errors']['clickatell_api_id_missing'].'<br />');

		if($this->getError()!='') {

			$array_fields = array( "clickatell_username", "clickatell_password", "clickatell_api_id" );

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

		$array_fields = array( "clickatell_username", "clickatell_password", "clickatell_api_id" );
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
		if(!$array_settings['clickatell_username'] || !$array_settings['clickatell_password'] || !$array_settings['clickatell_api_id']) {
			$this->setError(str_replace("::PROCESSOR::", "Clickatell", $lng['settings']['errors']['incomplete_smsg_settings']));
			return 0;
		}
		return 1;
	}

	function send($phone_no, $message, $object_id, $type='user') {

		$smsgw_settings = $this->getSettings();
		$username = urlencode($smsgw_settings['clickatell_username']);
		$password = urlencode($smsgw_settings['clickatell_password']);
		$api_id = urlencode($smsgw_settings['clickatell_api_id']);
		$to = urlencode($phone_no);
		$message = urlencode($message);

		$url = "http://api.clickatell.com/http/sendmsg"."?user=$username&password=$password&api_id=$api_id&to=$to&text=$message";
		$result = array("success"=>1, "details"=>'', 'error_code'=>'', 'error_string'=>'', 'message_id'=>'');
		
		if(_isCurl()) {
 
			$ch = curl_init();
		
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			if(curl_error($ch)) {  

				$result['success'] = 0;
				$result['error_string'] = "cURL error: " . curl_error( $ch );
				$result['details'] .= $result['error_string'] . "\n";

			}
		
			if($result['success'])
				$data = curl_exec($ch);
			curl_close($ch);

		} else 

			$data = file_get_contents($url);

		// interpret data
		if($data) {
		
			preg_match_all("/([A-Za-z]+):((.(?![A-Za-z]+:))*)/", $data, $matches);
			foreach ($matches[1] as $index => $status) {
				$result[$status] = trim($matches[2][$index]);
			}

			if(isset($result['ID'])) {
				$result['message_id'] = $result['ID'];
				unset($result['ID']);
				$result['details'] .= "Message sent - message ID {$result['message_id']}\n";
			}
			
			if (isset($result['ERR'])) {
	
				$error = explode(",", $result['ERR']);
				$result['success'] = 0;
				$result['error_code'] = count($error) == 2 ? $error[0] : 0;
				$result['error_string'] = (isset($error[1])) ? trim($error[1]) : $error[0];
				unset($result['ERR']);
				
				$result['details'] .= "Error sending message: error code {$result['error_code']}, description \"{$result['error_string']}\"";

			}
		} // end if data
		
		// log data
		$this->saveToDB($result, $object_id, $type);
	
	}

	function saveToDB($result, $object_id, $type)
	{

		global $db;
		$addtosql = '';

		$keys = array('success', 'message_id', 'error_code', 'error_string', 'details');
		
		foreach($keys as $key) 
		{
			$addtosql .= "`".$key."`='".$result[$key]."',";
			
		}
		$addtosql = rtrim($addtosql,','); 

		$timestamp = date("Y-m-d H:i:s");
		$res = $db->query("INSERT INTO ".$this->ret_table." SET date='$timestamp', `object_id`='$object_id', `type`='$type', ".$addtosql);

		return 1;

	}

	function getReturnInfo($object_id, $type="user") {
	
		global $db;
		$result = $db->fetchAssoc("select * from ".$this->ret_table." where `type`='$type' and `object_id`='$object_id' order by `date` desc limit 1");
		return $result;
	
	}
}
?>
