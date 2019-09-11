<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class ccbill {

	var $postback_url;
	var $post;
	var $user_key;
	var $ccbill_keys;
	var $pending;
	var $invoice_no;
	var $pay_settings;
	var $table;
	var $error;
	var $tmp;
	var $formTitle;
	var $subscrAutoRenew;
	var $subscrId;
	var $name;

	public function __construct()
	{
		global $db;
		global $config_table_prefix;
		$arr = $db->fetchAssoc("select `processor_table`, `processor_ret_table` from ".TABLE_PAYMENT_PROCESSORS." where `processor_code` like 'ccbill'");

		$this->table = $config_table_prefix.$arr['processor_table'];
		$this->ret_table = $config_table_prefix.$arr['processor_ret_table'];
		$this->subscrAutoRenew = 0;
		$this->subscrId = 0;
		$this->name = "ccbill";

		//$this->table = $this->getTable();

	}

	function getTable() {
		
		global $db;
		global $config_table_prefix;
		$table = $config_table_prefix.$db->fetchRow("select `processor_table` from ".TABLE_PAYMENT_PROCESSORS." where `processor_code` like 'ccbill'");
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
		$this->post['ukey'] = $this->user_key;

		$this->postback_url = $config_live_site."/payment_return/ccbill.php";

		$this->pay_settings = $this->getSettings();

		$this->post['clientAccnum'] = $this->pay_settings["account_no"];
		$this->post['clientSubacc'] = $this->pay_settings["subaccount_no"];
		$this->post['formName'] = $this->pay_settings["form_name"];
		$this->post['currencyCode'] = $this->pay_settings["currency"];
		$this->ccbill_url = 'https://bill.ccbill.com/jpost/signup.cgi';

		$this->ccbill_keys = array( "ukey", "accountingAmount", "address1", "affiliate", "affiliate_id", "affiliate_system", "allowedTypes",
  "baseCurrency", "cardType", "ccbill_referer", "city", "clientAccnum", "clientDrivenSettlement", "clientSubacc", "consumerUniqueId",
  "country", "currencyCode", "customer_fname", "customer_lname", "denialId", "email", "formName", "initialFormattedPrice", "initialPeriod",
  "initialPrice", "ip_address", "lifeTimeSubscription", "password", "paymentAccount", "phone_number", "price", "productDesc", "reasonForDecline",
  "reasonForDeclineBeforeOverride", "reasonForDeclineCode", "reasonForDeclineCodeBeforeOverride", "rebills", "recurringFormattedPrice",
  "recurringPeriod", "recurringPrice", "referer", "referringUrl", "reservationId", "responseDigest", "start_date", "state", "subscription_id",
  "typeId", "username", "zipcode" );

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

		$total = number_format($total, 2, '.', '');

		$this->post['formPrice'] = $total;
		$this->post['formPeriod'] = $days;

		$this->post['formRecurringPrice'] = $total;
		$this->post['formRecurringPeriod'] = $days;
		$this->post['formRebills'] = 99; // rebill indefinitelly

		return 1;

	}

	function setFirstSubscription($total, $days) {

		$total = number_format($total, 2, '.', '');
		$this->post['formPrice'] = $total;
		$this->post['formPeriod'] = $days;
		
		return 1;

	}

	function setInvoiceNo($val) {

		$this->invoice_no = $val;

	}
/*
	function setPostBack($str) {

		$this->postback_url = $str;

	}

	function setRecipient($str) {

		$this->post['business'] = $str;

	}

	function disableReturn() {

		$this->post['return'] = '';
		$this->post['cancel_return'] = '';
		$this->post['notify_url'] = '';

	}
*/
	function setAmount($str) {

		$amount = number_format($str, 2, '.', '');
		$this->post['formPrice'] = $amount;
		$this->post['formPeriod'] = 1;
		$this->amount = $amount;

	}

	function setCurrency($str) {

		$this->post['currencyCode'] = $str;

	}
/*
	function setItemName($str) {

		$this->post['item_name'] = $str;

	}
*/
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
	
		$this->createHash();
		
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

		$str =  sprintf($form, $this->ccbill_url, $inputs);//, $this->formTitle
		return $str;

	}

	function createHash() {
	
	 // formPrice formPeriod currencyCode salt
	 // for recurring:
	 // formPrice   formPeriod   formRecurringPrice   formRecurringPeriod   formRebills currencyCode salt
	  
  	  $salt = $this->pay_settings['salt'];

  	  if(isset($this->post['formRecurringPeriod']) && $this->post['formRecurringPeriod']) {
			$stringToHash = '' . $this->post['formPrice'] 
  	                     . $this->post['formPeriod'] 
  	                     . $this->post['formRecurringPrice'] 
  	                     . $this->post['formRecurringPeriod'] 
  	                     . $this->post['formRebills'] 
  	                     . $this->pay_settings['currency']
  	                     . $salt;
		}
  	  else {
  	   	  $stringToHash = '' . $this->post['formPrice'] 
  	                     . $this->post['formPeriod']
  	                     . $this->pay_settings['currency']
  	                     . $salt;
  	  }
  	  
  	  $hash = md5($stringToHash);
  	  $this->post['formDigest'] = $hash;
  	  
	}
	
	function process()
	{

		if( isset($_POST['subscription_id']) && $_POST['subscription_id'] ) {

			$this->subscrId = escape($_POST['subscription_id']);

		}

		$success = 0;
		if($this->validateData())
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

		if( !$this->subscrAutoRenew && ( !isset($_POST['ukey']) || strlen($_POST['ukey']) == 0) )
		{
			$this->log("validateData: ERROR: Invalid ukey:".$_POST['ukey']);
			return 0;
		}

		if( !isset($_POST['responseDigest']) || !$_POST['responseDigest'] ) 
		{
			$this->log("validateData: ERROR: no responseDigest received!");
			return 0;
		}

		if( !$this->correctResponseDigest() ) 
		{
			$this->log("validateData: ERROR: incorrect responseDigest received!");
			return 0;
		}
		
		if( !$this->receiverOK() )
		{
			$this->log("validateData: ERROR: Invalid receiver:".$_POST['clientAccnum'].", ".$_POST['clientSubacc']);
			return 0;
		}

		if(!$this->dataCorrect())
		{
			$this->log("validateData: ERROR: Incorrect Data!");
			return 0;
		}
		return 1;

	}

	function correctResponseDigest() {
	
		$salt = $this->pay_settings['salt'];
		$stringToHash = '' . $this->$_POST['subscription_id'] 
  	                     . '1'
  	                     . $salt;
  	  
		$hash = md5($stringToHash);

		if( strtolower($hash) != strtolower($_POST['responseDigest']) ) {

				$this->log("$ hash != $ _POST['responseDigest']: ".$hash.' <> '.$_POST['responseDigest']);
				return 0;

			}
	
	}
	
	function receiverOK()
	{
		global $db;
		if(!isset($_POST['clientAccnum']) || !isset($_POST['clientSubacc'])) return 0;

		// if recurring payment
		if($this->subscrAutoRenew) {

			if( strtolower(trim($_POST['clientAccnum'])) != strtolower(trim($this->pay_settings["account_no"])) ) {

				$this->log("$ this->post['account_no'] != $ _POST['clientAccnum']: ".$this->pay_settings["account_no"].' <> '.$_POST['clientAccnum']);
				return 0;

			}
			if( strtolower(trim($_POST['clientSubacc'])) != strtolower(trim($this->pay_settings["subaccount_no"])) ) {

				$this->log("$ this->post['subaccount_no'] != $ _POST['clientSubacc']: ".$this->pay_settings["subaccount_no"].' <> '.$_POST['clientSubacc']);
				return 0;

			}
			return 1;
		}

		$line = $db->fetchAssoc("select * from ".TABLE_PAYMENT_ACTIONS." where ukey='".$this->user_key."'");
		
		$this->post = unserialize(stripslashes($line['post']));
		
		if(strtolower(trim($this->post['account_no'])) != strtolower(trim($_POST['clientAccnum']))) { 
			$this->log("$ this->post['account_no'] != $ _POST['clientAccnum']: ".$this->post['account_no'].' <> '.$_POST['clientAccnum']);
			return 0;
		}

		if(strtolower(trim($this->post['subaccount_no'])) != strtolower(trim($_POST['clientSubacc']))) { 
			$this->log("$ this->post['subaccount_no'] != $ _POST['clientSubacc']: ".$this->post['subaccount_no'].' <> '.$_POST['clientSubacc']);
			return 0;
		}
		
		return 1;
	}

	function dataCorrect()
	{

		// check if data correct for subscription renewal
		if($this->subscrAutoRenew) {

			// get plan amount for this subscription and compare it with the received amount
			$up = new users_packages;
			$amount = number_format($up->getPriceForSubscription($this->subscrId), 2, '.', '');

			if(isset($_POST['recurringPrice']) && (float)$_POST['recurringPrice'] != (float)$amount) { 
				$this->log("Auto renew: (float)$ _POST['recurringPrice'] != (float)$ amount: ".(float)$_POST['recurringPrice'].' <> '.(float)$amount);
				return 0;
			}
			return 1;
		}
		
		// normal payments
		global $db;
		$res_ad = $db->query("select amount from ".TABLE_PAYMENT_ACTIONS." where ukey='".$this->user_key."'");
		if(!$db->numRows($res_ad)) return 0;
		$am = $db->fetchRow();
		// format so there are not more than 2 decimals
		$amount = number_format($am, 2, '.', '');
		$currency = $this->pay_settings["currency"];

		if(isset($_POST['initialPrice']) && (float)$_POST['initialPrice'] != (float)$amount) { 
			$this->log("(float)$ _POST['initialPrice'] != (float)$ amount: ".(float)$_POST['initialPrice'].' <> '.(float)$amount);
			return 0;
		}
		if(isset($_POST['baseCurrency']) && $_POST['baseCurrency'] != $currency) { 
			$this->log("$ _POST['baseCurrency'] != $ currency: ".$_POST['baseCurrency'].' <> '.$currency);
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
			if(in_array($key, $this->ccbill_keys)) 
			{
				$addtosql .= $key."='".$val."',";
			}
			$entirepost .= "[".$key."]=\'".$val."\',";
		}
		$addtosql = rtrim($addtosql,','); 

		$res_upd = $db->query("update ".TABLE_PAYMENT_ACTIONS." SET completed='1' where ukey='".$this->user_key."'");

		// set subscr_payment, subscr_signup and subscr_id fields
		// if signup subscription
		if(isset($_POST['subscription_id']) && $_POST['subscription_id']) {

			$res_upd = $db->query("update ".TABLE_PAYMENT_ACTIONS." SET `subscr_signup` = 1 where ukey='".$this->user_key."'");

			if($this->subscrId) $res_upd = $db->query("update ".TABLE_PAYMENT_ACTIONS." SET `subscr_id`='".$this->subscrId."' where ukey='".$this->user_key."'");
			
		}

		$timestamp = date("Y-m-d H:i:s");
		$res = $db->query("INSERT INTO ".$this->ret_table." SET date='$timestamp', entirepost='".$entirepost."', ".$addtosql.",ukey='".$this->user_key."'");

		return 1;

	}

	function isAutoRenew() {

		if( $_POST['txn_type'] == "subscr_payment") return 1;
		return 0;

	}

	function subscrAutoRenew() {

		return $this->subscrAutoRenew;

	}

	function getSubscriptionId() {

		return $this->subscrId;

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
		$file = $config_abs_path."/log/ccbill_transaction";
		if(!$success) $file = $config_abs_path."/log/ccbill_error";

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
		$file = $config_abs_path."/log/ccbill_debug";

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

		if(!isset($_POST["account_no"]) || !$_POST["account_no"]) $this->addError('Please enter Account number!<br />');
		if(!isset($_POST["subaccount_no"]) || !$_POST["subaccount_no"]) $this->addError('Please enter Subaccount number!<br />');
		if((!isset($_POST["form_name"]) || !$_POST["form_name"])) $this->addError('Please select the Form Name!<br />');
		if((!isset($_POST["currency"]) || !$_POST["currency"])) $this->addError('Please select the currency!<br />');
		if((!isset($_POST["salt"]) || !$_POST["salt"])) $this->addError('Please enter the Salt value!<br />');

		if($this->getError()!='') {

			$array_fields = array( "account_no", "subaccount_no", "form_name", "currency", "salt" );

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

		$array_fields = array( "account_no", "subaccount_no", "form_name", "currency", "salt" );

		$first=1;
		foreach ($array_fields as $field) {

			if(isset($_POST[$field])) $this->clean[$field] = escape($_POST[$field]); else $this->clean[$field] = '';

			if(!$first) $sql.=",";
			$sql.=" `$field` = '".$this->clean[$field]."'";
			$first=0;

		}

		$db->query($sql);
		return 1;
	}

	function correctSettings() {
		
		global $lng;
		$array_settings = $this->getSettings();
		if(!$array_settings['account_no'] || !$array_settings['subaccount_no'] || !$array_settings['form_name'] || !$array_settings['currency'] || !$array_settings['salt']) {
			$this->setError('If you wish to enable CCBill payment, please edit CCBill settings');
			return 0;
		}
		return 1;
	}

}
?>
