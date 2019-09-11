<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class cinetpay {

	var $postback_url;
	var $post;
	var $user_key;
	var $cinetpay_keys;
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
	var $demo;

	public function __construct()
	{
		global $db;
		global $config_table_prefix;
		$arr = $db->fetchAssoc("select `processor_table`, `processor_ret_table` from ".TABLE_PAYMENT_PROCESSORS." where `processor_code` like 'cinetpay'");

		$this->table = $config_table_prefix.$arr['processor_table'];
		$this->ret_table = $config_table_prefix.$arr['processor_ret_table'];
		$this->subscrAutoRenew = 0;
		$this->subscrId = 0;
		$this->name = "cinetpay";
		$this->demo = 0;

		//$this->table = $this->getTable();

	}

	function getTable() {
		
		global $db;
		global $config_table_prefix;
		$table = $config_table_prefix.$db->fetchRow("select `processor_table` from ".TABLE_PAYMENT_PROCESSORS." where `processor_code` like 'cinetpay'");
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
		$this->postback_url = $config_live_site."/payment_return/cinetpay.php";

		$this->pay_settings = $this->getSettings();

		$this->post['cpm_trans_id'] = $this->user_key;
		$this->post['cpm_site_id'] = $this->pay_settings["cinetpay_siteId"];
		$this->post['cpm_currency'] = $this->pay_settings["cinetpay_currency"];
		$this->post['cpm_designation'] = $this->pay_settings["cinetpay_description"];
		$this->setDemo($this->pay_settings["cinetpay_test"]);

		$this->post['cpm_trans_date'] = time();
		$this->post['cpm_payment_config'] = 'SINGLE';
		$this->post['cpm_page_action'] = 'PAYMENT';
		$this->post['cpm_language'] = 'fr';
		$this->post['cpm_version'] = 'V1';
		$this->post['return_url'] = $this->postback_url.'?mode=success&amp;ukey='.$this->user_key;
		$this->post['cancel_url'] = $this->postback_url.'?mode=cancel&amp;ukey='.$this->user_key;
		$this->post['notify_url'] = $this->postback_url.'?mode=process&amp;ukey='.$this->user_key;
		$this->post['apikey'] = $this->pay_settings["cinetpay_apikey"];
		
		$this->cinetpay_keys = array("signature", "cpm_amount", "cpm_currency", "cpm_payid", "cpm_version", "cpm_payment_date", "cpm_payment_time", "cpm_error_message", "payment_method", "cpm_cel_phone_num", "cpm_cell_phone_prefixe", "cpm_ipn_ack", "created_at", "updated_at", "cpm_result", "cpm_trans_status", "cpm_designation", "buyer_name" );

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

	function disableReturn() {

		$this->post['return'] = '';
		$this->post['cancel_return'] = '';
		$this->post['notify_url'] = '';

	}

	function setAmount($str) {

		$amount = number_format($str, 2, '.', '');
		$this->post['cpm_amount'] = $amount;
		$this->amount = $amount;

		$this->setSignature();
		
	}

	function setSignature() {
	
	
		if($this->demo) $url = 'http://api.sandbox.cinetpay.com/v1/?method=getSignatureByPost';
		else $url = 'https://api.cinetpay.com/v1/?method=getSignatureByPost';
	
		$post_body = $this->make_post_body();
	
		$ch = curl_init( );
		curl_setopt ( $ch, CURLOPT_URL, $url );
		//curl_setopt ( $ch, CURLOPT_PORT, $port );
		curl_setopt ( $ch, CURLOPT_POST, 1 );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt ( $ch, CURLOPT_POSTFIELDS, $post_body );
		// Allowing cUrl funtions 20 second to execute
		curl_setopt ( $ch, CURLOPT_TIMEOUT, 20 );
		// Waiting 20 seconds while trying to connect
		curl_setopt ( $ch, CURLOPT_CONNECTTIMEOUT, 20 );

		$response_string = curl_exec( $ch );
//		$curl_info = curl_getinfo( $ch );

//echo $url.$post_body;

		curl_close( $ch );
//echo "response: ".$response_string;
		$this->post['signature'] = json_decode ($response_string, true);
	
//	echo "Received signature: ".$this->post['signature']."!!!!";
	
	}
	
	function make_post_body() {
	
		$post_body = '';
		
		$array = array("cpm_amount", "cpm_currency", "cpm_site_id", "cpm_trans_id", "cpm_trans_date", "cpm_payment_config", "cpm_page_action", "cpm_version", "cpm_language", "cpm_designation");
		
		foreach( $array as $key ) {
			$post_body .= '&'.urlencode( $key ).'='.urlencode( $this->post[$key] );
		}
		$post_body .= '&apikey='.urlencode( $this->pay_settings["cinetpay_apikey"] );

		return $post_body;
	}

	function setCurrency($str) {

		$this->post['cpm_currency'] = $str;

	}

	function setItemName($str) {

		$this->post['cpm_designation'] = $str;

	}

	function setDemo($value) {

		if($value==1) $this->cinetpay_url = 'http://secure.sandbox.cinetpay.com';
		else $this->cinetpay_url = 'https://secure.cinetpay.com';
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

		$str =  sprintf($form, $this->cinetpay_url, $inputs);//, $this->formTitle
		return $str;

	}

	function process()
	{

		$processed=0;
		$response = $this->postBack();

		$success = 0;
		if($response['transaction']['cpm_result']=='00' && $this->validateData($response))
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

		$post_body = '';
		
		$array = array("apikey", "cpm_site_id", "cpm_trans_id");
		
		$post_body .= '&apikey='.$this->pay_settings["cinetpay_apikey"].'&cpm_site_id='.$this->pay_settings["cinetpay_siteId"]."&cpm_trans_id=".$_POST['cpm_trans_id'];

	
		if($this->demo) $url = 'http://api.sandbox.cinetpay.com/v1/?method=checkPayStatus%20';
		else $url = 'https://api.cinetpay.com/v1/?method=checkPayStatus';
	
		$ch = curl_init( );
		curl_setopt ( $ch, CURLOPT_URL, $url );
		//curl_setopt ( $ch, CURLOPT_PORT, $port );
		curl_setopt ( $ch, CURLOPT_POST, 1 );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		
		curl_setopt ( $ch, CURLOPT_POSTFIELDS, $post_body );
		// Allowing cUrl funtions 20 second to execute
		curl_setopt ( $ch, CURLOPT_TIMEOUT, 20 );
		// Waiting 20 seconds while trying to connect
		curl_setopt ( $ch, CURLOPT_CONNECTTIMEOUT, 20 );

		$response_string = curl_exec( $ch );
//		$curl_info = curl_getinfo( $ch );

//echo $url.$post_body;

		curl_close( $ch );
//echo "response: ".$response_string;
		$response = json_decode ($response_string, true);

		return $response;
	}

	function validateData($response)
	{

		global $db;
		$res_ad = $db->query("select amount from ".TABLE_PAYMENT_ACTIONS." where ukey='".$this->user_key."'");
		if(!$db->numRows($res_ad)) return 0;
		$am = $db->fetchRow();
		// format so there are not more than 2 decimals
		$amount = number_format($am, 2, '.', '');//??????????

		if((float)$response['transaction']['cpm_amount'] != (float)$amount) { 
			$this->log("(float)$ response['cpm_amount'] != (float)$ amount: ".(float)$response['transaction']['cpm_amount'].' <> '.(float)$amount);
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
			if(in_array($key, $this->cinetpay_keys)) 
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
		$file = $config_abs_path."/log/cinetpay_transaction";
		if(!$success) $file = $config_abs_path."/log/cinetpay_error";

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
		$file = $config_abs_path."/log/cinetpay_debug";

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

		if( !isset($_POST["cinetpay_siteId"]) || !$_POST["cinetpay_siteId"] ||
		!isset($_POST["cinetpay_apikey"]) || !$_POST["cinetpay_apikey"] || 
		!isset($_POST["cinetpay_description"]) || !$_POST["cinetpay_description"] ) $this->addError('Please configure all fields!<br />');

		if($this->getError()!='') {

			if($_POST['cinetpay_test']=="on") $this->tmp['cinetpay_test'] = 1; else $this->tmp['cinetpay_test'] = 0;

			$array_fields = array( "cinetpay_siteId", "cinetpay_apikey", "cinetpay_description" );

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
		$this->clean['cinetpay_test'] = checkbox_value("cinetpay_test");	
		$sql = "update ".$this->table." set cinetpay_test = ".$this->clean['cinetpay_test'];

		$array_fields = array( "cinetpay_siteId", "cinetpay_currency", "cinetpay_description", "cinetpay_apikey" );

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
		
		
		if(!$array_settings['cinetpay_siteId'] || !$array_settings['cinetpay_currency'] || !$array_settings['cinetpay_apikey'] || !$array_settings['cinetpay_description']) {
			$this->setError($lng['settings']['errors']['cinetpay_settings_missing']);
			return 0;
		}
		return 1;
	}

}
?>
