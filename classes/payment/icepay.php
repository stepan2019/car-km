<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

/*
Configuring icepay account:
Add a new website from Merchant Tools section and set the following:
Success URL: http://yoursite.com/payment_return/icepay.php?mode=success
Error URL: http://yoursite.com/payment_return/icepay.php?mode=cancel
Postback URL: http://yoursite.com/payment_return/icepay.php?mode=process
*/

class icepay {

	var $postback_url;
	var $post;
	var $user_key;
	var $pending;
	var $invoice_no;
	var $pay_settings;
	var $table;
	var $error;
	var $tmp;
	var $formTitle;
	var $name;
	var $fingerPrint;
	var $postback;

	public function __construct()
	{
		global $db;
		global $config_table_prefix;
		$arr = $db->fetchAssoc("select `processor_table`, `processor_ret_table` from ".TABLE_PAYMENT_PROCESSORS." where `processor_code` like 'icepay'");

		$this->table = $config_table_prefix.$arr['processor_table'];
		$this->ret_table = $config_table_prefix.$arr['processor_ret_table'];
		$this->name = "icepay";

	}

	function getTable() {
		
		global $db;
		global $config_table_prefix;
		$table = $config_table_prefix.$db->fetchRow("select `processor_table` from ".TABLE_PAYMENT_PROCESSORS." where `processor_code` like 'icepay'");
		return $table;
	}

	function setTable($table) {

		$this->table = $table;
	
	}

	function init($key = '') {

		global $config_live_site;
		$this->pending = 0;
		if($key) $this->user_key = $key;
		else {
			if(isset($_POST["Reference"]) && $_POST["Reference"]) $this->user_key = $_POST["Reference"];
			else  if(isset($_GET["Reference"]) && $_GET["Reference"]) $this->user_key = $_GET["Reference"];
			else { 
				$this->user_key = md5(uniqid(rand(), true));
				global $db;
				$this->orderID = $db->fetchRow("select id from ".TABLE_PAYMENT_ACTIONS." order by id desc limit 1");
			}
		}
		$this->postback_url = $config_live_site."/payment_return/icepay.php";

		$this->icepay_url = 'https://pay.icepay.eu/basic/';

		$this->pay_settings = $this->getSettings();

		$this->merchantID = $this->pay_settings["merchantID"];
		$this->secretCode = $this->pay_settings["secretCode"];
		$this->language = $this->pay_settings["ic_language"];
		$this->country = $this->pay_settings["ic_country"];
		$this->currency = $this->pay_settings["ic_currency"];
		$this->description = $this->pay_settings["description"];
		$this->reference = $this->user_key;
		//$this->orderID = '12348';//max 10 char

		$this->icepay_keys = array("Status", "StatusCode", "Merchant", "OrderID",
			       "PaymentID", "Reference", "TransactionID", "ConsumerName", "ConsumerAccountNumber",
			       "ConsumerAddress", "ConsumerHouseNumber", "ConsumerPostCode", "ConsumerCity",
			       "ConsumerCountry", "ConsumerEmail", "ConsumerPhoneNumber", "ConsumerIPAddress", "Amount",
			       "Currency", "Duration", "Checksum", "PaymentMethod");

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

		$amount = $str * 100;
		$amount = number_format($amount, 0, '', '');//remove decimals
		$this->amount = $amount;

	}

	function generateChecksum() {

		return sha1();

	}

	function generateChecksumForBasicMode()
	{

		return sha1
		(
			$this->merchantID . "|"	.
			$this->secretCode . "|" .
			$this->amount . "|" . 
			$this->orderID . "|" .
			$this->reference . "|" .
			$this->currency . "|" .
			$this->country
		);

	}

	function generateFingerPrint()
	{
		if ( $this->fingerPrint != "" ) return $this->fingerPrint;
		
		$content = "";

		foreach ( $this->GetCoreClasses() as $item )
			if ( false === ($content .= file_get_contents( dirname(__FILE__).'/'.$item )))
				//throw new Exception( "Could not generate fingerprint" );
				echo "Could not generate fingerprint";
		$this->fingerPrint = sha1($content);
		
		return $this->fingerPrint;
	}

	function generateChecksumForPostback()
	{
		return sha1
		(
			$this->secretCode . "|" .
			$this->merchantID . "|" .
			$this->postback->status . "|" .
			$this->postback->statusCode . "|" .
			$this->postback->orderID . "|" .
			$this->postback->paymentID . "|" .
			$this->postback->reference . "|" .
			$this->postback->transactionID . "|" .
			$this->postback->amount . "|" .
			$this->postback->currency . "|" .
			$this->postback->duration . "|" .
			$this->postback->consumerIPAddress
		);
	}

	//static public 
	function GetCoreClasses()
	{
		return array
		(
		 	'icepay.php',
		);
	}

	function setCurrency($str) {

		$this->currency = $str;

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

	$str = '
<form method="post" name="payment_form" id="payment_form" action="'.$this->icepay_url.'">
<input type="hidden" name="ic_country" value="'.$this->country.'"/>
<input type="hidden" name="ic_language" value="'.$this->language.'"/>
<input type="hidden" name="ic_merchantid" value="'.$this->merchantID.'"/>
<input type="hidden" name="ic_currency" value="'.$this->currency.'"/>
<input type="hidden" name="ic_amount" value="'.$this->amount.'"/>
<input type="hidden" name="ic_description" value="'.$this->description.'"/>
<input type="hidden" name="ic_reference" value="'.$this->reference.'"/>
<input type="hidden" name="ic_paymentmethod" value=""/>
<input type="hidden" name="ic_issuer" value=""/>
<input type="hidden" name="ic_orderid" value="'.$this->orderID.'"/>
<input type="hidden" name="ic_fp" value="'.$this->generateFingerPrint().'"/>
<input type="hidden" name="chk" value="'.$this->generateChecksumForBasicMode().'"/>
<input type="submit" border="0" name="submit_payment" />
</form>';
		return $str;
	}

	function process()
	{

		if ( $_SERVER['REQUEST_METHOD'] != 'POST' ) return false;

		$this->postback 						= NULL;
		$this->postback->status					= $_POST['Status'];
		$this->postback->statusCode				= $_POST['StatusCode'];
		$this->postback->merchant				= $_POST['Merchant'];
		$this->postback->orderID				= $_POST['OrderID'];
		$this->postback->paymentID				= $_POST['PaymentID'];
		$this->postback->reference				= $_POST['Reference'];
		$this->postback->transactionID			= $_POST['TransactionID'];
		$this->postback->consumerName			= $_POST['ConsumerName'];
		$this->postback->consumerAccountNumber	= $_POST['ConsumerAccountNumber'];
		$this->postback->consumerAddress		= $_POST['ConsumerAddress'];
		$this->postback->consumerHouseNumber	= $_POST['ConsumerHouseNumber'];
		$this->postback->consumerCity			= $_POST['ConsumerCity'];
		$this->postback->consumerCountry		= $_POST['ConsumerCountry'];
		$this->postback->consumerEmail			= $_POST['ConsumerEmail'];
		$this->postback->consumerPhoneNumber	= $_POST['ConsumerPhoneNumber'];
		$this->postback->consumerIPAddress		= $_POST['ConsumerIPAddress'];
		$this->postback->amount					= $_POST['Amount'];
		$this->postback->currency				= $_POST['Currency'];
		$this->postback->duration				= $_POST['Duration'];
		$this->postback->paymentMethod			= $_POST['PaymentMethod'];
		$this->postback->checksum				= $_POST['Checksum'];

		$processed=0;
		$success = 0;
		if( $_POST['Status']=="OK" && $this->validateData() )
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

		if( !isset($_POST['Reference']) || strlen($_POST['Reference']) == 0)
		{
			$this->log("validateData: ERROR: Invalid ukey:".$_POST['Reference']);
			return 0;
		} else $this->user_key = $_POST['Reference'];

		if ( !is_numeric($_POST['Merchant']) )
		{
			$this->log("validateData: ERROR: Invalid merchant:".$_POST['Merchant']);
			return 0;
		}
		if ( !is_numeric($_POST['Amount']) )
		{
			$this->log("validateData: ERROR: Invalid amount:".$_POST['Amount']);
			return 0;
		}

		if ( $this->merchantID != $_POST['Merchant'] )
		{
			$this->log("validateData: ERROR: Invalid merchantID:".$_POST['Merchant']);
			return 0;
		}

	
		$array_stats = array('OK', 'ERR', 'REFUND', 'CBACK', 'OPEN');
		if ( !in_array($_POST['Status'], $array_stats) )
		{
			$this->log("validateData: ERROR: Invalid status:".$_POST['Status']);
			return 0;
		}

		if ( $this->generateChecksumForPostback() != $_POST['Checksum'] )
		{
			$this->log("validateData: ERROR: Checksum does not match:".$_POST['Checksum']);
			return 0;
		}

		if ( !$this->dataCorrect() )
		{
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

		$amount = $am * 100;
		$amount = number_format($amount, 0, '', '');//remove decimals

		$currency = $this->pay_settings["ic_currency"];

		if(isset($_POST['Amount']) && (float)$_POST['Amount'] != (float)$amount) { 
			$this->log("(float)$ _POST['Amount'] != (float)$ amount: ".(float)$_POST['Amount'].' <> '.(float)$amount);
			return 0;
		}
		if(isset($_POST['Currency']) && $_POST['Currency'] != $currency) { 
			$this->log("$ _POST['Currency'] != $ currency: ".$_POST['Currency'].' <> '.$currency);
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
			if(in_array($key, $this->icepay_keys)) 
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
		$file = $config_abs_path."/log/icepay_transaction";
		if(!$success) $file = $config_abs_path."/log/icepay_error";

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
		$file = $config_abs_path."/log/icepay_debug";

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
		if(!isset($_POST['merchantID']) || !$_POST['merchantID']) $this->addError('Pealse enter merchant id!<br />');
		if(!isset($_POST['secretCode']) || !$_POST['secretCode']) $this->addError('Pealse enter secret code!<br />');
		if(!isset($_POST['ic_language']) || !$_POST['ic_language']) $this->addError('Pealse enter a default language!<br />');
		if(!isset($_POST['ic_country']) || !$_POST['ic_country']) $this->addError('Pealse enter your country!<br />');
		if(!isset($_POST['ic_currency']) || !$_POST['ic_currency']) $this->addError('Pealse enter a currency!<br />');

		if($this->getError()!='') {

			$array_fields = array( "merchantID", "secretCode", "ic_language", "ic_country", "ic_currency", "description" );

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
		$array_fields = array( "merchantID", "secretCode", "ic_language", "ic_country", "ic_currency", "description" );

		$i=0;
		$sql = $sql = "update ".$this->table." set ";
		foreach ($array_fields as $field) {
			if($i) $sql.=",";
			if(isset($_POST[$field])) $this->clean[$field] = escape($_POST[$field]); else $this->clean[$field] = '';
			$sql.=" `$field` = '".$this->clean[$field]."'";
			$i++;
		}

		$db->query($sql);
		return 1;
	}

	function correctSettings() {
		
		global $lng;
		$array_settings = $this->getSettings();
		if(!$array_settings['merchantID'] || !$array_settings['secretCode'] || !$array_settings['ic_language'] || !$array_settings['ic_country'] || !$array_settings['ic_currency']) {
			$this->setError($lng['settings']['errors']['paypal_settings_missing']);
			return 0;
		}
		return 1;
	}

}
?>
