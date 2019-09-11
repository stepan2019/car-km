<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class paypal {

	var $postback_url;
	var $post;
	var $user_key;
	var $paypal_keys;
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
		$arr = $db->fetchAssoc("select `processor_table`, `processor_ret_table` from ".TABLE_PAYMENT_PROCESSORS." where `processor_code` like 'paypal'");

		$this->table = $config_table_prefix.$arr['processor_table'];
		$this->ret_table = $config_table_prefix.$arr['processor_ret_table'];
		$this->subscrAutoRenew = 0;
		$this->subscrId = 0;
		$this->name = "paypal";

		//$this->table = $this->getTable();

	}

	function getTable() {
		
		global $db;
		global $config_table_prefix;
		$table = $config_table_prefix.$db->fetchRow("select `processor_table` from ".TABLE_PAYMENT_PROCESSORS." where `processor_code` like 'paypal'");
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
		$this->postback_url = $config_live_site."/payment_return/paypal.php";

		$this->pay_settings = $this->getSettings();
		//$this->pending = $this->pay_settings['pending'];

		$this->post['business'] = $this->pay_settings["paypal_email"];
		$this->post['currency_code'] = $this->pay_settings["paypal_currency"];
		$this->post['item_name'] = $this->pay_settings["paypal_pay_title"];
		if($this->pay_settings["paypal_lc"]) $this->post['lc'] = $this->pay_settings["paypal_lc"];
		$this->setDemo($this->pay_settings["paypal_demo"]);

		$this->post['rm'] = '2';
		$this->post['cmd'] = '_xclick';
		global $appearance_settings;
		$this->post['charset'] = $appearance_settings['charset'];
		$this->post['return'] = $this->postback_url.'?mode=success&amp;ukey='.$this->user_key;
		$this->post['cancel_return'] = $this->postback_url.'?mode=cancel&amp;ukey='.$this->user_key;
		$this->post['notify_url'] = $this->postback_url.'?mode=process&amp;ukey='.$this->user_key;
		$this->paypal_keys = array("item_name", "receiver_email", "item_number", "mc_currency",
			       "invoice", "quantity", "custom", "payment_status", "mc_gross",
			       "pending_reason", "payment_date", "payment_gross", "payment_fee",
			       "txn_id", "txn_type", "first_name", "last_name", "address_street",
			       "address_city", "address_state", "address_zip", "address_country",
			       "address_status", "payer_email", "payer_status", "payment_type",
			       "subscr_date", "period1", "period2", "period3", "amount1",
			       "amount2", "amount3", "mc_amount1",
			       "mc_amount2", "mc_amount3", "recurring", "reattempt", "retry_at",
			       "recur_times", "username", "password", "subscr_id", "option_name1",
			       "option_selection1", "option_name2", "option_selection2",
			       "num_cart_items" );

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

		$this->post['cmd'] = '_xclick-subscriptions';
		$this->post['a3'] = $total;
		
		if($days>90) { 
		    $this->post['p3'] = round($days/30);
		    $this->post['t3'] = "M";
		} else
		{
		    $this->post['p3'] = $days;
		    $this->post['t3'] = "D";
		}

		
		$this->post['src'] = "1"; // Recurring payments. If set to 1, the payment will recur unless your customer cancels the subscription before the end of the billing cycle
		$this->post['sra'] = "1";//Reattempt on failure. If set to 1, and the payment fails, the payment will be reattempted two more times. After the third failure, the subscription will be cancelled

		return 1;

	}

	function setFirstSubscription($amount, $days) {

		$amount = number_format($amount, 2, '.', '');

		$this->post['a1'] = $amount;
		if($days>90) { 
		    $this->post['p1'] = round($days/30);
		    $this->post['t1'] = "M";
		} else
		{
		    $this->post['p1'] = $days;
		    $this->post['t1'] = "D";
		}

	}

	function setInvoiceNo($val) {

		$this->invoice_no = $val;

	}

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

	function setAmount($str) {

		$amount = number_format($str, 2, '.', '');
		$this->post['amount'] = $amount;
		$this->amount = $amount;

	}

	function setCurrency($str) {

		$this->post['currency_code'] = $str;

	}

	function setItemName($str) {

		$this->post['item_name'] = $str;

	}

	function setDemo($value) {

		if($value==1) $this->paypal_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
		else $this->paypal_url = 'https://www.paypal.com/cgi-bin/webscr';
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

		$str =  sprintf($form, $this->paypal_url, $inputs);//, $this->formTitle
		return $str;

	}

	function process()
	{

		$processed=0;
		$this->postBack();

		// check if subscription auto renew
		if( $_POST['txn_type'] == "subscr_payment") {

			$this->subscrAutoRenew = 1;

		}

		if( $_POST['txn_type'] == "subscr_payment" || $_POST['txn_type'] == "subscr_signup") {

			$this->subscrId = escape($_POST['subscr_id']);

		}

		$success = 0;
		if(preg_match("/VERIFIED/i",$this->ipn_response) && $this->validateData())
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

	function postBack()
	{

		$post = '';
		foreach($_POST as $key => $val) 
		{ 
			$v = stripslashes($val);
			//$this->post[$key] = $v;
			$post.= $key.'='.urlencode($v).'&';
		}
		$post .= "cmd=_notify-validate";

		$url_parsed=parse_url($this->paypal_url);
		$fp = fsockopen("ssl://".$url_parsed['host'],"443",$err_num,$err_str,30);
		if(!$fp) {

			//$this->last_error = "fsockopen error no. $errnum: $errstr";
			//$this->log_ipn_results(false);
			return 0;

		} 
 
		// Post the data back to paypal
		fputs($fp, "POST $url_parsed[path] HTTP/1.1\r\n"); 
		fputs($fp, "Host: $url_parsed[host]\r\n"); 
		fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n"); 
		fputs($fp, "Content-length: ".strlen($post)."\r\n"); 
		fputs($fp, "Connection: close\r\n\r\n"); 
		fputs($fp, $post . "\r\n\r\n"); 

		// loop through the response from the server and append to variable
		while(!feof($fp)) { 
			$this->ipn_response .= fgets($fp, 1024); 
		}
	
		fclose($fp); // close connection
	}

	function validateData()
	{

		if( !$this->subscrAutoRenew && ( !isset($_GET['ukey']) || strlen($_GET['ukey']) == 0) )
		{
			$this->log("validateData: ERROR: Invalid ukey:".$_GET['ukey']);
			return 0;
		}

		if($_POST['txn_type'] != "subscr_signup") { // if start subscription signup do not check for payment status !

		if(!isset($_POST['payment_status']) || $_POST['payment_status'] != "Completed")
		{
			$this->log("validateData: ERROR: Invalid payment_status:".$_POST['payment_status']);
			return 0;
		}

		if($this->isDuplicate())
		{
			$this->log("validateData: ERROR: Duplicate txn_id");
			return 0;
		}

		} // end if($_POST['txn_type'] != "subscr_signup")


		if( !$this->receiverOK() )
		{
			$this->log("validateData: ERROR: Invalid receiver:".$_POST['receiver_email']);
			return 0;
		}

		if($_POST['txn_type'] != "subscr_signup" && !$this->dataCorrect())
		{
			$this->log("validateData: ERROR: Incorrect Data!");
			return 0;
		}
		return 1;

	}

	function isDuplicate()
	{
		global $db;
		if(!isset($_POST['txn_id']))
		return 1;

		$txn_id = urlencode(addslashes($_POST['txn_id']));
		$no = $db->fetchRow("select count(id) from ".$this->ret_table." where txn_id='".$txn_id."'");
		if($no != 0) return 1;
		return 0;
	}

	function receiverOK()
	{
		global $db;
		if(!isset($_POST['business'])) return 0;

		// if recurring payment
		if($this->subscrAutoRenew) {

			if( strtolower(trim($_POST['business'])) != strtolower(trim($this->pay_settings["paypal_email"])) ) {

				$this->log("$ this->post['business'] != $ _POST['business']: ".$this->pay_settings["paypal_email"].' <> '.$_POST['business']);
				return 0;

			}
			return 1;
		}

		$line = $db->fetchAssoc("select * from ".TABLE_PAYMENT_ACTIONS." where ukey='".$this->user_key."'");
		$this->post = unserialize(stripslashes($line['post']));
		if(strtolower(trim($this->post['business'])) != strtolower(trim($_POST['business']))) { 
			$this->log("$ this->post['business'] != $ _POST['business']: ".$this->post['business'].' <> '.$_POST['business']);
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

			if(isset($_POST['mc_gross']) && (float)$_POST['mc_gross'] != (float)$amount) { 
				$this->log("Auto renew: (float)$ _POST['mc_gross'] != (float)$ amount: ".(float)$_POST['mc_gross'].' <> '.(float)$amount);
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
		$currency = $this->pay_settings["paypal_currency"];
		$paypal_pay_title = $this->pay_settings["paypal_pay_title"];

		if(isset($_POST['mc_gross']) && ((float)$_POST['mc_gross'] - (float)$_POST['tax']) != (float)$amount) { 
			$this->log("(float)$ _POST['mc_gross'] - $ _POST['tax'] != (float)$ amount: ".(float)$_POST['mc_gross'].' - '.$_POST['tax'].' <> '.(float)$amount);
			return 0;
		}
		if(isset($_POST['mc_currency']) && $_POST['mc_currency'] != $currency) { 
			$this->log("$ _POST['mc_currency'] != $ currency: ".$_POST['mc_currency'].' <> '.$currency);
			return 0;
		}
		/*
		if(!isset($_POST['item_name']) || $_POST['item_name'] != $paypal_pay_title) { 
			$this->log("$ _POST['item_name'] != $ paypal_pay_title: ".$_POST['item_name'].' <> '.$paypal_pay_title);
			return 0;
		}*/
		//if(!isset($_POST['item_number']) || $_POST['item_number'] != $this->post['item_number']) return 0;
		return 1;
	}

	function saveToDB()
	{

		global $db;
		$addtosql = '';
		$entirepost = '';

		foreach($_POST as $key => $val) 
		{
			if(in_array($key, $this->paypal_keys)) 
			{
				$addtosql .= $key."='".$val."',";
			}
			$entirepost .= "[".$key."]=\'".$val."\',";
		}
		$addtosql = rtrim($addtosql,','); 

		$res_upd = $db->query("update ".TABLE_PAYMENT_ACTIONS." SET completed='1' where ukey='".$this->user_key."'");

		// set subscr_payment, subscr_signup and subscr_id fields
		// if signup subscription
		if($_POST['txn_type'] == "subscr_signup") {

			$addtosql .= "payment_status='Completed',";
			$entirepost .= "[payment_status]=\'Completed\',";

			$res_upd = $db->query("update ".TABLE_PAYMENT_ACTIONS." SET `subscr_signup` = 1 where ukey='".$this->user_key."'");

			if($this->subscrId) $res_upd = $db->query("update ".TABLE_PAYMENT_ACTIONS." SET `subscr_id`='".$this->subscrId."' where ukey='".$this->user_key."'");
			
		}


		// check if data correct for subscription renewal
/*		if($this->subscrAutoRenew) {


		// if subscription payment
		if($_POST['txn_type'] == "subscr_payment") {

			$res_upd = $db->query("update ".TABLE_PAYMENT_ACTIONS." SET `subscr_payment` = 1 where ukey='".$this->user_key."'");
			$this->subscrAutoRenew = 1;
		}

		}// end if($_POST['txn_type'] == "subscr_payment")
*/
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
		$file = $config_abs_path."/log/paypal_transaction";
		if(!$success) $file = $config_abs_path."/log/paypal_error";

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
		$file = $config_abs_path."/log/paypal_debug";

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

		if(!isset($_POST["paypal_email"]) || !$_POST["paypal_email"]) $this->addError($lng['settings']['errors']['required_paypal_email'].'<br />');
		if((!isset($_POST["paypal_currency"]) || !$_POST["paypal_currency"]) && !isset($_POST["other_paypal_currency"])) $this->addError($lng['settings']['errors']['required_paypal_currency'].'<br />');

		if($this->getError()!='') {

			if($_POST['paypal_demo']=="on") $this->tmp['paypal_demo'] = 1; else $this->tmp['paypal_demo'] = 0;

			$array_fields = array( "paypal_email", "paypal_currency", "paypal_pay_title" );

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
		$this->clean['paypal_demo'] = checkbox_value("paypal_demo");	
		$sql = "update ".$this->table." set paypal_demo = ".$this->clean['paypal_demo'];

		$array_fields = array( "paypal_email", "paypal_currency", "paypal_pay_title", "paypal_lc" );

		foreach ($array_fields as $field) {

			if(isset($_POST[$field])) $this->clean[$field] = escape($_POST[$field]); else $this->clean[$field] = '';

			if($field=="paypal_currency" && $_POST[$field]=="other") $this->clean[$field] = escape($_POST['other_paypal_currency']);
			$sql.=", `$field` = '".$this->clean[$field]."'";

		}

		$db->query($sql);
		return 1;
	}

	function correctSettings() {
		
		global $lng;
		$array_settings = $this->getSettings();
		if(!$array_settings['paypal_email'] || !$array_settings['paypal_currency']) {
			$this->setError($lng['settings']['errors']['paypal_settings_missing']);
			return 0;
		}
		return 1;
	}

}
?>
