<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class payu {

	var $postback_url;
	var $post;
	var $user_key;
	var $payu_keys;
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
		$arr = $db->fetchAssoc("select `processor_table`, `processor_ret_table` from ".TABLE_PAYMENT_PROCESSORS." where `processor_code` like 'payu'");

		$this->table = $config_table_prefix.$arr['processor_table'];
		$this->ret_table = $config_table_prefix.$arr['processor_ret_table'];
		$this->name = "payu";

		//$this->table = $this->getTable();

	}

	function getTable() {
		
		global $db;
		global $config_table_prefix;
		$table = $config_table_prefix.$db->fetchRow("select `processor_table` from ".TABLE_PAYMENT_PROCESSORS." where `processor_code` like 'payu'");
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
		$this->postback_url = $config_live_site."/payment_return/payu.php";

		$this->pay_settings = $this->getSettings();
		//$this->pending = $this->pay_settings['pending'];

		$this->post['merchantId'] = $this->pay_settings["payu_merchantId"];
		$this->post['accountId'] = $this->pay_settings["payu_accountId"];
		$this->post['currency'] = $this->pay_settings["payu_currency"];
		$this->post['description'] = $this->pay_settings["payu_description"];
		$this->post['referenceCode'] = $this->user_key;
		$this->setDemo($this->pay_settings["payu_test"]);
//buyerEmail
		$this->post['responseUrl'] = $this->postback_url.'?mode=success&amp;ukey='.$this->user_key;
		$this->post['confirmationUrl'] = $this->postback_url.'?mode=process&amp;ukey='.$this->user_key;
		$this->payu_keys = array( "merchant_id",	"state_pol", "risk", "response_code_pol", "reference_sale", "reference_pol", "sign", "extra1", "extra2", "payment_method", "payment_method_type", "installments_number", "value", "tax", "additional_value", "transaction_date", "currency", "email_buyer",	"cus", "pse_bank", "test",	"description",	"billing_address", "shipping_address",	"phone",	"office_phone",	"administrative_fee", "administrative_fee_base", "administrative_fee_tax", "airline_code", "attempts", "authorization_code", "bank_id", "billing_city", "billing_country", "commision_pol", "commision_pol_currency", "customer_number", "date",	"error_code_bank", "error_message_bank", "exchange_rate", "ip", "nickname_buyer", "nickname_seller", "payment_method_id", "payment_request_state", "pseReference1", "pseReference2", "pseReference3", "response_message_pol", "shipping_city", "shipping_country", "transaction_bank_id", "transaction_id", "payment_method_name" );

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

	}

	function setPostBack($str) {

		$this->postback_url = $str;

	}

	function disableReturn() {

		$this->post['responseUrl'] = '';
		$this->post['confirmationUrl'] = '';

	}

	function setAmount($str) {

		$amount = number_format($str, 2, '.', '');
		$this->post['amount'] = $amount;
		$this->post['tax'] = 0;
		$this->post['taxReturnBase'] = 0;
		$this->amount = $amount;

		$this->post['signature'] = hash("md5", $this->pay_settings['payu_apikey']."~".$this->pay_settings['payu_merchantId']."~".$this->post['referenceCode']."~".$this->post['amount']."~".$this->post['currency']);
		
	}

	function setDemo($value) {

		$this->post['test'] = $value;
		if($value==1) $this->payu_url = 'https://sandbox.gateway.payulatam.com/ppp-web-gateway/';
		else $this->payu_url = 'https://gateway.payulatam.com/ppp-web-gateway/';
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

		$str =  sprintf($form, $this->payu_url, $inputs);//, $this->formTitle
		return $str;

	}

	function process()
	{

		$processed=0;
		$success = 0;
		if($_POST['state_pol'] == 4 && $this->validateData())
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

		// if is integer format with one decimal, otherwise with 2
		if ( strval($_POST['value']) != strval(intval($_POST['value'])) )
			$received_amount = number_format($_POST['value'], 2, '.', '');
		else
			$received_amount = number_format($_POST['value'], 1, '.', '');	
		
		//"ApiKey~merchant_id~reference_sale~new_value~currency~state_pol"
		$signature = hash("md5", $this->pay_settings['payu_apikey']."~".$_POST['merchant_id']."~".$_POST['reference_sale']."~".$received_amount."~".$_POST['currency']."~".$_POST['state_pol']);

		if(strtoupper($_POST['sign']) != strtoupper($signature))
		{
			$this->log("validateData: ERROR: Invalid signature:".$_POST['sign']);
			$this->log("compiled signature: hash(".$this->pay_settings['payu_apikey']."~".$_POST['merchant_id']."~".$_POST['reference_sale']."~".$received_amount."~".$_POST['currency']."~".$_POST['state_pol'].")");
			return 0;
		}

		global $db;
		$res_ad = $db->query("select `amount` from ".TABLE_PAYMENT_ACTIONS." where ukey='".$this->user_key."'");
		
		if(!$db->numRows($res_ad)) {
			$this->log("Invalid ukey: ".$this->user_key."!");
			return 0;
		}	
		
		$am = $db->fetchRow();

		$amount = number_format($am, 2, '.', '');
		$amount = str_replace(".00", ".0", $amount);

		if($received_amount!=$amount) { 
			$this->log("received amount != amount: ".$received_amount.' <> '.$amount);
			return 0;
		}

		return 1;

	}

	function saveToDB()
	{

		global $db;
		$addtosql = '';
		$entirepost = '';

		foreach($_POST as $key => $val) 
		{
			if(in_array($key, $this->payu_keys) && $key != "date") 
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
		$file = $config_abs_path."/log/payu_transaction";
		if(!$success) $file = $config_abs_path."/log/payu_error";

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
		$file = $config_abs_path."/log/payu_debug";

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

		if(!isset($_POST["payu_merchantId"]) || !$_POST["payu_merchantId"]) $this->addError('Please enter PayU merchant id!<br />');
		if(!isset($_POST["payu_accountId"]) || !$_POST["payu_accountId"]) $this->addError('Please enter PayU account id!<br />');
		if(!isset($_POST["payu_apikey"]) || !$_POST["payu_apikey"]) $this->addError('Please enter PayU API key!<br />');
		if(!isset($_POST["payu_description"]) || !$_POST["payu_description"]) $this->addError('Please enter PayU payment description!<br />');

		if($this->getError()!='') {

			if($_POST['payu_test']=="on") $this->tmp['payu_test'] = 1; else $this->tmp['payu_test'] = 0;

			$array_fields = array( "payu_merchantId", "payu_accountId", "payu_apikey", "payu_currency", "payu_description" );

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
		$this->clean['payu_test'] = checkbox_value("payu_test");	
		$sql = "update ".$this->table." set payu_test = ".$this->clean['payu_test'];

		$array_fields = array( "payu_merchantId", "payu_accountId", "payu_apikey", "payu_currency", "payu_description" );

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
		if(!$array_settings['payu_merchantId'] || !$array_settings['payu_accountId'] || !$array_settings['payu_apikey'] || !$array_settings['payu_description']) {
			$this->setError('If you wish to enable PayU payment, please edit PayU settings');
			return 0;
		}
		return 1;
	}

}
?>
