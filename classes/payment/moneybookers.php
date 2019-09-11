<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class moneybookers {

	var $postback_url;
	var $post;
	var $user_key;
	var $mb_keys;
	var $user_id;
	var $pending;
	var $invoice_no;
	var $pay_settings;
	var $table;
	var $error;
	var $tmp;
	var $formTitle;
	var $mb_url;

	public function __construct()
	{

		global $db;
		global $config_table_prefix;
		$arr = $db->fetchAssoc("select `processor_table`, `processor_ret_table` from ".TABLE_PAYMENT_PROCESSORS." where `processor_code` like 'mb'");

		$this->table = $config_table_prefix.$arr['processor_table'];
		$this->ret_table = $config_table_prefix.$arr['processor_ret_table'];
	}

	function getTable() {
		
		global $db;
		global $config_table_prefix;
		$table = $config_table_prefix.$db->fetchRow("select `processor_table` from ".TABLE_PAYMENT_PROCESSORS." where `processor_code` like 'mb'");
		return $table;
	}

	function setTable($table) {

		$this->table = $table;
	
	}
	// test accounts: demoqco@sun-fish.com, demoqcoflexible@sun-fish.com, demoqcofixedhh@sun-fish.com
	// test cards: MC: 5438311234567890 , Visa: 4000001234567890 , Amex: 371234500012340

	function init($key = '') {

		global $config_live_site;
		$this->pending = 0;
		if($key) $this->user_key = $key;
		else $this->user_key = isset($_GET['ukey'])?$_GET['ukey']:md5(uniqid(rand(), true));
		$this->postback_url = $config_live_site."/payment_return/mb.php";

		$this->pay_settings = $this->getSettings();
		//$this->pending = $this->pay_settings['pending'];

		$this->post['pay_to_email'] = $this->pay_settings["mb_email"];
		$this->post['recipient_description'] = $settings['site_name'];
		$this->post['status_url'] = $this->pay_settings["mb_notify_email"];
		$this->post['language'] = $this->pay_settings["mb_language"]; //BG,CS,DA,DE,EL,EN,ES,FI,FR,IT,ZH,NL,PL,RO,RU,SV,TR,JA
		$this->post['currency'] = $this->pay_settings["mb_currency"];
		$this->post['detail1_description'] = $this->pay_settings["mb_pay_title"];
		$this->setDemo($this->pay_settings["mb_demo"]);

		// !!!!!!!
		//$this->post['transaction_id'] = $this->user_key;
		
		$this->post['return_url'] = $this->postback_url.'?mode=success&amp;ukey='.$this->user_key;
		$this->post['cancel_url'] = $this->postback_url.'?mode=cancel&amp;ukey='.$this->user_key;
		$this->post['status_url'] = $this->postback_url.'?mode=process&amp;ukey='.$this->user_key;
		$this->mb_keys = array("pay_to_email", "pay_from_email", "merchant_id", "customer_id",
			       "transaction_id", "mb_transaction_id", "mb_amount", "mb_currency", "status",
			       "md5sig", "amount", "currency", "payment_type", "failed_reason_code", "sha2sig", "neteller_id", "merchant_fields");

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

	function setAmount($str) {

		$this->post['amount'] = $str;
		$this->amount = $str;

	}

	function setCurrency($str) {

		$this->post['currency'] = $str;

	}

	function setItemName($str) {

		$this->post['detail1_description'] = $str;

	}

	function setUserId($id) {

		$this->user_id = $id;

	}

	function setAdId($id) {

		$this->ad_id = $id;

	}

	function setDemo($value) {

		if($value==1) $this->mb_url = 'https://www.skrill.com/app/test_payment.pl';
		else $this->mb_url = 'https://www.skrill.com/app/payment.pl';
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
		$form = <<<ENDFORM
<form method="post" name="payment_form" id="payment_form" action="%s">
%s
<input type="image" src="images/logo_mb.gif" border="0" name="submit_form" alt="%s">
</form>
ENDFORM;

		$inputs = '';
		foreach($this->post as $key => $val) 
		{
			$inputs .= '<input type="hidden" name="'.$key.'" value="'.$val.'"/>
';
		}

		$str =  sprintf($form, $this->mb_url, $inputs, $this->formTitle);

		return $str;

	}

	function process()
	{

		$processed=0;
		if($this->validateData())
		{
			$success = $this->saveToDB();
			if(!$success) $this->log("saveToDB: ERROR: Cannot save to db!");
			$processed=1;

		} else $processed=0;
		$this->logIt($success);

		return $processed;
	}

	function validateData()
	{

		if(!isset($_GET['ukey']) || strlen($_GET['ukey']) == 0)
		{
			$this->log("validateData: ERROR: Invalid ukey:".$_GET['ukey']);
			return 0;
		}

		if(!isset($_POST['status']) || ($_POST['status'] != "2" && $_POST['status'] != "0"))
		{
			$this->log("validateData: ERROR: Invalid status:".$_POST['status']);
			return 0;
		}
		if($this->isDuplicate())
		{
			$this->log("validateData: ERROR: Duplicate mb_transaction_id");
			return 0;
		}

		if(!$this->receiverOK())
		{
			$this->log("validateData: ERROR: Invalid receiver:".$_POST['pay_to_email']);
			return 0;
		}

		if(!$this->dataCorrect())
		{
			$this->log("validateData: ERROR: Incorrect Data!");
			return 0;
		}
		return 1;
	}


	function isDuplicate()
	{
		if(!isset($_POST['mb_transaction_id']))
		return 1;

		global $db;
		$mb_transaction_id = urlencode(addslashes($_POST['mb_transaction_id']));
		$no = $db->fetchRow("select count(id) from ".$this->ret_table." where mb_transaction_id='".$mb_transaction_id."'");

		if($no != 0) return 1;
		return 0;
	}

	function receiverOK()
	{
		global $db;
		if(!isset($_POST['pay_to_email'])) return 0;
		$line = $db->fetchAssoc("select * from ".TABLE_PAYMENT_ACTIONS." where ukey='".$this->user_key."'");
		$this->post = unserialize(stripslashes($line['post']));
		if($this->post['pay_to_email'] != $_POST['pay_to_email']) return 0;
		return 1;
	}

	function dataCorrect()
	{

		global $db;
		$res = $db->query("select * from ".TABLE_PAYMENT_ACTIONS." where ukey='".$this->user_key."'");
		if(!$db->numRows($res)) { 
			$this->log("validateData: ERROR: Incorrect User Key!");
			return 0;
		}

		$arr = $db->fetchAssoc();
		$am = $arr['amount'];
		$amount = number_format($am, 2, '.', '');
		//$type = $arr['type'];
		//$pay_settings = new pay_settings();
		$currency = $this->pay_settings["mb_currency"];

		if(isset($_POST['amount']) && $_POST['amount'] != $amount) { 
			$this->log("validateData: ERROR: Incorrect Amount!");
			return 0;
		}
		if(isset($_POST['currency']) && $_POST['currency'] != $currency) { 
			$this->log("validateData: ERROR: Incorrect Currency!");
			return 0;
		}
		if($this->pay_settings["mb_secret"]) {
			$string_to_hash = $_POST['merchand_id'].$_POST['transaction_id'].strtoupper(md5($secret)).$_POST['mb_amount'].$_POST['mb_currency'].$_POST['status'];
			$check_key = strtoupper(md5($string_to_hash));
			if($check_key != $_POST["md5sig"]) { 
				$this->log("validateData: ERROR: Incorrect Hash!");
				return 0;
			}
		}
		return 1;
	}

	function saveToDB()
	{
		$addtosql = '';
		$entirepost = '';

		foreach($_POST as $key => $val) 
		{
			if(in_array($key, $this->mb_keys)) 
			{
				$addtosql .= $key."='".$val."',";
			}
			$entirepost .= "[".$key."]=\'".$val."\',";
		}
		$addtosql = rtrim($addtosql,','); 

		global $db;
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
		$file = $config_abs_path."/log/mb_transaction";
		if(!$success) $file = $config_abs_path."/log/mb_error";

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
		$file = $config_abs_path."/log/mb_debug";

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

		global $db;
		$this->clean=array();
		$this->check_form();
		if($this->getError()!='') return 0;

		$array_required = array ("mb_email", "mb_currency");
		foreach ($array_required as $field) {

			if(!isset($_POST[$field]) || !$_POST[$field]) $this->addError($lng['settings']['errors']['required_'.$field].'<br />');

		}

		if($this->getError()!='') {

			if($_POST['mb_demo']=="on") $this->tmp['mb_demo'] = 1; else $this->tmp['mb_demo'] = 0;

			$array_fields = array( "mb_email", "mb_secret", "mb_currency", "mb_language", "mb_pay_title" );

			foreach ($array_fields as $field) {
				if(isset($_POST[$field])) $this->tmp[$field] = cleanStr($_POST[$field]);
			}
			
		}

	}

	function saveSettings() {

		global $lng;
		$this->error='';
		$this->tmp=array();

		global $config_demo;
		if($config_demo==1) $this->addError($lng['general']['errors']['demo'].'<br />');

		global $db;
		$this->clean['mb_demo'] = checkbox_value("mb_demo");	
		$sql = "update ".$this->table." set mb_demo = ".$this->clean['mb_demo'];

		$array_fields = array( "mb_email", "mb_secret", "mb_currency", "mb_language", "mb_pay_title" );

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
		if(!$array_settings['mb_email'] || !$array_settings['mb_currency']) {
			$this->setError($lng['settings']['errors']['mb_settings_missing']);
			return 0;
		}
		return 1;
	}
}
?>
