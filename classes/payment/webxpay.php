<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class webxpay {

	var $post;
	var $user_key;
	var $webxpay_keys;
	var $pending;
	var $invoice_no;
	var $pay_settings;
	var $table;
	var $error;
	var $tmp;
	var $formTitle;
	var $name;
	var $response;

	public function __construct()
	{
		global $db;
		global $config_table_prefix;
		$arr = $db->fetchAssoc("select `processor_table`, `processor_ret_table` from ".TABLE_PAYMENT_PROCESSORS." where `processor_code` like 'webxpay'");

		$this->table = $config_table_prefix.$arr['processor_table'];
		$this->ret_table = $config_table_prefix.$arr['processor_ret_table'];
		$this->subscrAutoRenew = 0;
		$this->name = "webxpay";

	}

	function getTable() {
		
		global $db;
		global $config_table_prefix;
		$table = $config_table_prefix.$db->fetchRow("select `processor_table` from ".TABLE_PAYMENT_PROCESSORS." where `processor_code` like 'webxpay'");
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

		$this->pay_settings = $this->getSettings();

		$this->webxpay_url = 'https://webxpay.com/index.php?route=checkout/billing';

		$this->post['secret_key'] = $this->pay_settings["secret_key"];
		$this->post['process_currency'] = $this->pay_settings["currency"];
		//$this->post['custom_fields'] = base64_encode($this->user_key);
		$this->post['cms'] = 'PHP';
		
		$this->post['first_name'] = 'Your Firstname';
		$this->post['last_name'] = 'Your Lastname';
		$this->post['email'] = 'Your Email';
		$this->post['contact_number'] = 'Your Phone Number';
//		$this->post['address_one_line'] = 'Address line 1';

		$this->webxpay_keys = array("order_id", "order_refference_number", "date_time_transaction", "payment_gateway_used", "status_code", "comment");

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

	// recurring payments
	function setSubscription($total, $days) {

		return 1;

	}

	function setInvoiceNo($val) {

		$this->invoice_no = $val;

		global $config_abs_path;
		include $config_abs_path.'/classes/payment/webxpay/RSA.php';
		//initialize RSA
		$rsa = new Crypt_RSA();
		//load public key for encrypting
		$rsa->loadKey($this->pay_settings["public_key"]);
		$encrypt = $rsa->encrypt($val."|".$this->amount);
	
		$this->post['payment'] = base64_encode($encrypt);

		// set contact fields
		
		// if user_id
		global $db;
		$res = $db->fetchAssoc("select `user_id`, `action` from ".TABLE_PAYMENT_ACTIONS." where id='$val'");
		if($res['user_id']) {
		
			global $settings;
			$contact_name_field = $settings['contact_name_field'];
			$email_field = "email";
			$phone_field = $this->pay_settings["phone_field"];
			
			$user_info = $db->fetchAssoc("select `$contact_name_field`, `$email_field`, `$phone_field` from ".TABLE_USERS." where id='{$res['user_id']}'");
			$this->setFirstLastName($user_info[$contact_name_field]);
			$this->post['email'] = $user_info[$email_field];
			$this->post['contact_number'] = $user_info[$phone_field];
		
		}
		else {
		// if no user
		
			$action = unserialize($res['action']);
			if(isset($action['ad_id']) && $action['ad_id']) {
				
				$contact_name_field = "mgm_name";
				$email_field = "mgm_email";
				$phone_field = $this->pay_settings["nologin_phone_field"];
				
				global $db;
				$user_info = $db->fetchAssoc("select `$contact_name_field`, `$email_field`, `$phone_field` from ".TABLE_ADS_EXTENSION." where id='{$action['ad_id']}'");
				$this->setFirstLastName($user_info[$contact_name_field]);
				$this->post['email'] = $user_info[$email_field];
				$this->post['contact_number'] = $user_info[$phone_field];
				
			}
		
		} // end if no user
	}
	
	function setFirstLastName($str) {
	
		$array = explode(" ", $str, 2);
		$this->post['first_name'] = $array[0];
		if($array[1]) $this->post['last_name'] = $array[1];
		else  $this->post['last_name'] = $array[0];
	
	}

	function setPostBack($str) {

		$this->postback_url = $str;

	}

	function setAmount($str) {

		$amount = number_format($str, 2, '.', '');
		$this->amount = $amount;

	}

	function setCurrency($str) {

	}

	function setItemName($str) {

	}

	function setDemo($value) {

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
		$form = <<<ENDFORM
<form method="post" name="payment_form" id="payment_form" action="%s">
%s
<input type="submit" name="submit_payment" value="{$lng['general']['submit']}" />
</form>
ENDFORM;

		$inputs = '';
		foreach($this->post as $key => $val) 
		{
			$inputs .= '<input type="hidden" name="'.$key.'" value="'.$val.'"/>
';
		}

		$str =  sprintf($form, $this->webxpay_url, $inputs);//, $this->formTitle
		return $str;

	}

	function process()
	{

		global $config_abs_path;
		include $config_abs_path.'/classes/payment/webxpay/RSA.php';

		$processed=0;

		$rsa = new Crypt_RSA();
		//decode & get POST parameters
		$payment = base64_decode($_POST ["payment"]);
		$signature = base64_decode($_POST ["signature"]);
		//$custom_fields = base64_decode($_POST ["custom_fields"]);
		
		//load public key for signature matching
		$rsa->loadKey($this->pay_settings["public_key"]);
		//verify signature
		$signature_status = $rsa->verify($payment, $signature) ? TRUE : FALSE;
		//get payment response in segments
		//payment format: order_id|order_refference_number|date_time_transaction|payment_gateway_used|status_code|comment;
		$res = explode('|', $payment);
		
		$this->response['order_id'] = $res[0];
		$this->response['order_refference_number'] = $res[1];
		$this->response['date_time_transaction'] = $res[2];
		$this->response['status_code'] = $res[3];
		$this->response['comment'] = $res[4];
		$this->response['payment_gateway_used'] = $res[5];

		$this->log("payment_str: ".$payment);
		
		$success = 0;
		if($signature_status && $this->validateData())
		{
			// set ukey 
			global $db;
			$this->user_key = $db->fetchRow("select `ukey` from ".TABLE_PAYMENT_ACTIONS." where id='{$this->response['order_id']}'");
			
			$this->log("found user key: ".$this->user_key);

			$success = $this->saveToDB();
			if(!$success) $this->log("saveToDB: ERROR: Cannot save to db!");
			$processed=1;

		} 
		else 
			$processed=0;

		$this->logIt($success);
		
		return $processed;
	}

	function validateData() {
	
		if($this->response['status_code']=="0") return 1;
		
		return 0;
	}
	
	function saveToDB()
	{

		global $db;
		$addtosql = '';
		$entirepost = '';

		foreach($this->response as $key => $val) 
		{
			if(in_array($key, $this->webxpay_keys)) 
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

	function isAutoRenew() {

		return 0;

	}

	function subscrAutoRenew() {


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
		$file = $config_abs_path."/log/webxpay_transaction";
		if(!$success) $file = $config_abs_path."/log/webxpay_error";

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
		$file = $config_abs_path."/log/webxpay_debug";

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

		if(!isset($_POST["secret_key"]) || !$_POST["secret_key"]) $this->addError('Please configure the secret key!<br />');

		if(!isset($_POST["public_key"]) || !$_POST["public_key"]) $this->addError('Please configure the public key!<br />');

		if(!isset($_POST["currency"]) || !$_POST["currency"]) $this->addError('Please select the currency!<br />');

		if($this->getError()!='') {

			$array_fields = array( "secret_key", "public_key", "currency", "name_field", "email_field", "phone_field", "nologin_name_field", "nologin_email_field", "nologin_phone_field" );

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

		$array_fields = array( "secret_key", "public_key", "currency", "phone_field", "nologin_phone_field");

		foreach ($array_fields as $field) {

			if(isset($_POST[$field])) $this->clean[$field] = escape($_POST[$field]); else $this->clean[$field] = '';

		}

		$sql = "update ".$this->table." set `secret_key`='{$this->clean['secret_key']}', `public_key`='{$this->clean['public_key']}', `currency`='{$this->clean['currency']}', `phone_field`='{$this->clean['phone_field']}'";
		
		if($this->clean['nologin_phone_field']) $sql.=", `nologin_phone_field`='{$this->clean['nologin_phone_field']}'";
		
		$db->query($sql);
		return 1;
	}

	function correctSettings() {
		
		global $lng;
		$array_settings = $this->getSettings();
		if(!$array_settings['secret_key'] || !$array_settings['public_key'] || !$array_settings['currency']) {
			$this->setError("Please configure Webxpay settings!");
			return 0;
		}
		return 1;
	}

}
?>