<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class instamojo {

	var $postback_url;
	var $post;
	var $user_key;
	var $instamojo_keys;
	var $pending;
	var $invoice_no;
	var $pay_settings;
	var $table;
	var $error;
	var $tmp;
	var $formTitle;
	var $name;

	public function __construct()
	{
		global $db;
		global $config_table_prefix;
		$arr = $db->fetchAssoc("select `processor_table`, `processor_ret_table` from ".TABLE_PAYMENT_PROCESSORS." where `processor_code` like 'instamojo'");

		$this->table = $config_table_prefix.$arr['processor_table'];
		$this->ret_table = $config_table_prefix.$arr['processor_ret_table'];
		$this->subscrAutoRenew = 0;
		$this->subscrId = 0;
		$this->name = "instamojo";

		//$this->table = $this->getTable();

	}

	function getTable() {
		
		global $db;
		global $config_table_prefix;
		$table = $config_table_prefix.$db->fetchRow("select `processor_table` from ".TABLE_PAYMENT_PROCESSORS." where `processor_code` like 'instamojo'");
		return $table;
	}

	function setTable($table) {

		$this->table = $table;
	
	}

	function init($key = '') {

		global $config_live_site;
		$this->pending = 0;
		if($key) $this->user_key = $key;
		else $this->user_key = isset($_GET['ukey'])?$_GET['ukey']:md5(uniqid(rand(), true));
		$this->postback_url = $config_live_site."/payment_return/instamojo.php";

		$this->post['send_email'] = 'false';
		$this->post['send_sms'] = 'false';
		$this->post['allow_repeated_payments'] = 'false';
		
		$this->post['webhook'] = $this->postback_url."?mode=process";
		$this->post['redirect_url'] = $this->postback_url."?mode=success";

		$this->pay_settings = $this->getSettings();

		$this->setDemo($this->pay_settings["instamojo_test"]);

		$this->instamojo_keys = array("amount", "buyer", "buyer_name", "buyer_phone",
			       "currency", "fees", "longurl", "mac", "payment_id",
			       "payment_request_id", "purpose", "shorturl", "status");

	}

	function getUserKey() {

		return $this->user_key;

	}

	function getPost() {

		return $this->post;

	}

	function setDebug($val) {

		$this->debug = $val;

	}

	function setFormTitle($val) {

		$this->formTitle = $val;

	}
	function setInvoiceNo($val) {

		$this->invoice_no = $val;
		$this->post['purpose'] = $val; // unique value for purpose field

	}

	function setPostBack($str) {

		$this->postback_url = $str;

	}

	function setAmount($str) {

		$amount = number_format($str, 2, '.', '');
		$this->post['amount'] = $amount;
		$this->amount = $amount;

	}

	function setCurrency($str) {

		//$this->post['currency_code'] = $str;

	}

	function setItemName($str) {


	}

	function setDemo($value) {

		if($value==1) $this->instamojo_url = "https://test.instamojo.com/api/1.1/payment-requests/";
		else $this->instamojo_url = "https://www.instamojo.com/api/1.1/payment-requests/";
		
		return 1;

	}

	function setPending($val) {

		$this->pending = $val;

	}

	function getPending() {

		return $this->pending;

	}

	function getSettings() {

		global $db;
		$result = $db->fetchAssoc("select * from ".$this->table);
		return $result;

	}

	function getForm()
	{
		global $lng;
		
		$request_url = $this->instamojo_url;
		        
		$headers = array("X-Api-Key: {$this->pay_settings['instamojo_api_key']}", "X-Auth-Token: {$this->pay_settings['instamojo_auth_token']}");

		$request_url .= "?";
		$first= 1;
		$uri='';
	
		foreach($this->post as $key => $val) {
		
			if(!$first) $uri.="&";
			$uri.=$key."=".$val;
			$first=0;
		
		}
		//$request_url.=$uri;

		$options = array();
		$options[CURLOPT_HTTPHEADER] = $headers;
		$options[CURLOPT_RETURNTRANSFER] = true;


		$options[CURLOPT_POST] = 1;
		$options[CURLOPT_POSTFIELDS] = $uri;

		$options[CURLOPT_URL] = $request_url;
		$options[CURLOPT_SSL_VERIFYPEER] = 0;
		
		$this->curl = curl_init();
		$setopt = curl_setopt_array($this->curl, $options);
		$response2 = curl_exec($this->curl);
		
		$headers = curl_getinfo($this->curl);

		$error_number = curl_errno($this->curl);
		$error_message = curl_error($this->curl);
		curl_close($this->curl);
		
		$response_obj = json_decode($response2, true);
//_print_r($response_obj);
        if($error_number != 0){
            if($error_number == 60){
                throw new Exception("Something went wrong. cURL raised an error with number: $error_number and message: $error_message. " .
                                    "Please check http://stackoverflow.com/a/21114601/846892 for a fix." . PHP_EOL);
            }
            else{
                throw new Exception("Something went wrong. cURL raised an error with number: $error_number and message: $error_message." . PHP_EOL);
            }
        }

        if($response_obj['success'] == true) {
			
			//echo $response_obj['payment_request']['longurl'];
			
			// set ukey for return
			$payment_id = $response_obj['payment_request']['id'];
			global $db;
			$db->query("update ".TABLE_PAYMENT_ACTIONS." set `ukey`='$payment_id' where `id`=".$this->invoice_no);
			
			global $lng;
			$form = <<<ENDFORM
<form method="get" name="payment_form" id="payment_form" action="%s">
<input type="submit" name="submit_payment" value="{$lng['general']['submit']}" />
</form>
ENDFORM;

		$str =  sprintf($form, $response_obj['payment_request']['longurl']);
		return $str;
			
        } // end if success

	}

	
	function process()
	{

		// get payment_id and payment_request_id. 
		//$this->payment_id = $_GET['payment_id'];
		$this->user_key = $_POST['payment_request_id'];
	
		$processed=0;
		$this->response = $_POST;

		$success = 0;
		if(preg_match("/Credit/i",$this->response['status']) && $this->validateData())
		{
			$success = $this->saveToDB();
			if(!$success) $this->log("saveToDB: ERROR: Cannot save to db!");
			$processed=1;

		} 
		else 
			$processed=0;

		$this->logIt($success);

		return $processed;
	}

	function validateData()
	{

		if( !isset($this->user_key) || strlen($this->user_key) == 0 )
		{
			$this->log("validateData: ERROR: Invalid ukey:".$this->user_key);
			return 0;
		}

		if( !$this->dataCorrect())
		{
			$this->log("validateData: ERROR: Incorrect Data!");
			return 0;
		}
		return 1;

	}


	function dataCorrect()
	{
		
		global $db;
		$res_ad = $db->query("select amount from ".TABLE_PAYMENT_ACTIONS." where ukey='".$this->user_key."'");
		if(!$db->numRows($res_ad)) return 0;
		$am = $db->fetchRow();
		// format so there are not more than 2 decimals
		$amount = number_format($am, 2, '.', '');

		if(isset($_POST['amount']) && (float)$_POST['amount'] != (float)$amount) { 
			$this->log("(float)$ _POST['amount'] != (float)$ amount: ".(float)$_POST['amount'].' <> '.(float)$amount);
			return 0;
		}
		
		if(!isset($_POST['mac']) || !$_POST['mac'] || !$this->isValidMac()) {
			$this->log("validateData: ERROR: Invalid MAC!");
			return 0;
		}
		
		return 1;
	}
	
	function isValidMac() {
	
	
		$data = $_POST;
		$mac_provided = $data['mac'];  // Get the MAC from the POST data
		unset($data['mac']);  // Remove the MAC key from the data.
		$ver = explode('.', phpversion());
		$major = (int) $ver[0];
		$minor = (int) $ver[1];
		if($major >= 5 and $minor >= 4){
			ksort($data, SORT_STRING | SORT_FLAG_CASE);
		}
		else{
			uksort($data, 'strcasecmp');
		}

		$mac_calculated = hash_hmac("sha1", implode("|", $data), $this->pay_settings['instamojo_salt']);
		
		if($mac_provided != $mac_calculated) return 0;
	
		return 1;
	
	}

	function saveToDB()
	{

		global $db;
		$addtosql = '';
		$entirepost = '';

		foreach($_POST as $key => $val) 
		{
			if(in_array($key, $this->instamojo_keys)) 
			{
				$addtosql .= $key."='".$val."',";
			}
			$entirepost .= "[".$key."]=\'".$val."\',";
		}
		$addtosql = rtrim($addtosql,','); 

		$res_upd = $db->query("update ".TABLE_PAYMENT_ACTIONS." SET completed='1' where ukey='".$this->user_key."'");

		$timestamp = date("Y-m-d H:i:s");
		$res = $db->query("INSERT INTO ".$this->ret_table." SET date='$timestamp', entirepost='".$entirepost."', ".$addtosql.",ukey='".$this->user_key."'");

		return 1;

	}

	function logIt($success)
	{
		if(!$this->debug) return;
		// Generate content
		$content = "-----------------------------------\n".date("r")."\n";
		$content .= "LOCAL POST:\n";
		foreach($this->post as $key => $val)
		{
			$content .= $key."=".$val."\n";
		}
		$content .= "\nRECEIVED POST:\n";

		foreach($_POST as $key => $val)
		{
			$content .= $key."=".$val."\n";
		}
		$content .= "-----------------------------------\n";

		// Create File
		global $config_abs_path;
		$file = $config_abs_path."/log/instamojo_transaction";
		if(!$success) $file = $config_abs_path."/log/instamojo_error";

		$handle = fopen($file, "a");
		fwrite($handle, $content);
		fclose($handle);
	}

	function log($msg)
	{
		if(!$this->debug) return;

		$content = "-----------------------------------\n".date("r")."\n";
		$content .= $msg."\n";
		$content .= "-----------------------------------\n";

		global $config_abs_path;
		$file = $config_abs_path."/log/instamojo_debug";

		$handle = fopen($file, "a");
		fwrite($handle, $content);
		fclose($handle);
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

		if(!isset($_POST["instamojo_api_key"]) || !$_POST["instamojo_api_key"]) $this->addError('Please enter API Key!<br />');
		if(!isset($_POST["instamojo_auth_token"]) || !$_POST["instamojo_auth_token"]) $this->addError('Please enter Auth Token!<br />');
		if(!isset($_POST["instamojo_salt"]) || !$_POST["instamojo_salt"]) $this->addError('Please enter Private Salt!<br />');

		if($this->getError()!='') {

			if($_POST['instamojo_test']=="on") $this->tmp['instamojo_test'] = 1; else $this->tmp['instamojo_test'] = 0;

			$array_fields = array( "instamojo_api_key", "instamojo_auth_token", "instamojo_salt");

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
		$this->clean['instamojo_test'] = checkbox_value("instamojo_test");	
		$sql = "update ".$this->table." set instamojo_test = ".$this->clean['instamojo_test'];

		$array_fields = array( "instamojo_api_key", "instamojo_auth_token", "instamojo_salt" );

		foreach ($array_fields as $field) {

			if(isset($_POST[$field])) $this->clean[$field] = escape($_POST[$field]); else $this->clean[$field] = '';

			$sql.=", `$field` = '".$this->clean[$field]."'";

		}

		$db->query($sql);
		return 1;
	}

	function correctSettings() {
		
		global $lng;
		$array_settings = $this->getSettings();
		if(!$array_settings['instamojo_api_key'] || !$array_settings['instamojo_auth_token'] || !$array_settings['instamojo_salt']) {
			$this->setError("Please configure the Instamojo payment settings!");
			return 0;
		}
		return 1;
	}

}
?>
