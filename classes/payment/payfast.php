<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class payfast {

	var $postback_url;
	var $post;
	var $user_key;
	var $payfast_keys;
	var $pending;
	var $invoice_no;
	var $pay_settings;
	var $table;
	var $error;
	var $tmp;
	var $name;

	public function __construct()
	{
		global $db;
		global $config_table_prefix;
		$arr = $db->fetchAssoc("select `processor_table`, `processor_ret_table` from ".TABLE_PAYMENT_PROCESSORS." where `processor_code` like 'payfast'");

		$this->table = $config_table_prefix.$arr['processor_table'];
		$this->ret_table = $config_table_prefix.$arr['processor_ret_table'];
		$this->name = "payfast";

	}

	function getTable() {
		
		global $db;
		global $config_table_prefix;
		$table = $config_table_prefix.$db->fetchRow("select `processor_table` from ".TABLE_PAYMENT_PROCESSORS." where `processor_code` like 'payfast'");
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
		$this->postback_url = $config_live_site."/payment_return/payfast.php";

		$this->pay_settings = $this->getSettings();
		//$this->pending = $this->pay_settings['pending'];

		$this->post['merchant_id'] = $this->pay_settings["merchant_id"];
		$this->post['merchant_key'] = $this->pay_settings["merchant_key"];
		$this->post['item_name'] = $this->pay_settings["item_name"];
		$this->setDemo($this->pay_settings["demo"]);


		$this->post['return_url'] = $this->postback_url.'?mode=success&amp;ukey='.$this->user_key;
		$this->post['cancel_return'] = $this->postback_url.'?mode=cancel&amp;ukey='.$this->user_key;
		$this->post['notify_url'] = $this->postback_url.'?mode=process&amp;ukey='.$this->user_key;
		$this->payfast_keys = array("m_payment_id", "pf_payment_id", "payment_status", "item_name",
			       "item_description", "amount_gross", "amount_fee", "amount_net", "name_first",
			       "name_last", "email_address", "merchant_id");

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

	function setInvoiceNo($val) {

		$this->invoice_no = $val;

	}

	function setPostBack($str) {

		$this->postback_url = $str;

	}

	function setAmount($str) {

		$amount = number_format($str, 2, '.', '');
		$this->post['amount'] = $amount;
		$this->amount = $amount;

	}

	function setDemo($value) {

		if($value==1) $this->payfast_url = 'https://sandbox.payfast.co.za/eng/process';
		else $this->payfast_url = 'https://www.payfast.co.za/eng/process';
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

	function setFormTitle($val) {

		$this->formTitle = $val;

	}

	function getForm()
	{
		$form = <<<ENDFORM
<form method="post" name="payment_form" id="payment_form" action="%s">
%s
<input type="submit" name="submit_payment" />
</form>
ENDFORM;

		$inputs = '';
		foreach($this->post as $key => $val) 
		{
			$inputs .= '<input type="hidden" name="'.$key.'" value="'.$val.'"/>
';
		}

		$str =  sprintf($form, $this->payfast_url, $inputs);
		return $str;

	}

	function checkSignature() {

		$ps = '';
		foreach($_POST as $key => $val) 
		{ 
			if($key=="signature") continue;
			$v = stripslashes($val);
			$ps.= $key.'='.urlencode($v).'&';
		}

    	$ps = substr( $ps, 0, -1 );
    	$signature = md5( $ps );

		if($_POST['signature'] != $signature) {
			$this->log("validateData: ERROR: Invalid Signature:".$signature.' <> '.$_POST['signature']); 
			$this->log("received post:".$ps); 
			return 0;
		}	
		return 1;
		
	}

	function checkIP() {
		
    $validHosts = array(
        'www.payfast.co.za',
        'sandbox.payfast.co.za',
        'w1w.payfast.co.za',
        'w2w.payfast.co.za',
        );
 
    $validIps = array();
 
    foreach( $validHosts as $pfHostname )
    {
        $ips = gethostbynamel( $pfHostname );
 
        if( $ips !== false )
            $validIps = array_merge( $validIps, $ips );
    }
 
    // Remove duplicates
    $validIps = array_unique( $validIps );
 
    if( !in_array( getRemoteIp(), $validIps ) )
    {
			$this->log("validateData: ERROR: Invalid IP:".getRemoteIp());
			return 0;
    }

		return 1;
		
		}

	function process()
	{

		$processed=0;

		// check the signature
		$res = $this->checkSignature();
		if(!$res) return 0;

		// check the ip
		$res = $this->checkIP();
		if(!$res) return 0;

		$this->postBack();

		$success = 0;
		if(preg_match("/VALID/i",$this->itn_response) && $this->validateData())
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
			$post.= $key.'='.urlencode($v).'&';
		}

    	$post = substr( $post, 0, -1 );

		$url_parsed=parse_url($this->payfast_url);
		$fp = fsockopen("ssl://".$url_parsed['host'],"443",$err_num,$err_str,30);
		if(!$fp) {

			return 0;

		} 
 
		// Post the data back to payfast
		fputs($fp, "POST /eng/query/validate HTTP/1.1\r\n"); 
		fputs($fp, "Host: $url_parsed[host]\r\n"); 
		fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n"); 
		fputs($fp, "Content-length: ".strlen($post)."\r\n"); 
		fputs($fp, "Connection: close\r\n\r\n"); 
		fputs($fp, $post . "\r\n\r\n"); 

		// loop through the response from the server and append to variable
		while(!feof($fp)) { 
			$this->itn_response .= fgets($fp, 1024); 
		}
	
		fclose($fp); // close connection
	}

	function validateData()
	{

		if($this->isDuplicate())
		{
			$this->log("validateData: ERROR: Duplicate pf_payment_id");
			return 0;
		}


		if( !$this->receiverOK() )
		{
			$this->log("validateData: ERROR: Invalid receiver:".$_POST['merchant_id']);
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
		global $db;
		if(!isset($_POST['pf_payment_id']))
		return 1;

		$txn_id = urlencode(addslashes($_POST['pf_payment_id']));
		$no = $db->fetchRow("select count(id) from ".$this->ret_table." where pf_payment_id='".$txn_id."'");
		if($no != 0) return 1;
		return 0;
	}

	function receiverOK()
	{

		global $db;
		if(!isset($_POST['merchant_id'])) return 0;

		$line = $db->fetchAssoc("select * from ".TABLE_PAYMENT_ACTIONS." where ukey='".$this->user_key."'");
		$this->post = unserialize(stripslashes($line['post']));
		if(strtolower(trim($this->post['merchant_id'])) != strtolower(trim($_POST['merchant_id']))) { 
			$this->log("$ this->post['merchant_id'] != $ _POST['merchant_id']: ".$this->post['merchant_id'].' <> '.$_POST['merchant_id']);
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

		if(isset($_POST['amount_gross']) && (float)$_POST['amount_gross'] != (float)$amount) { 
			$this->log("(float)$ _POST['amount_gross'] != (float)$ amount: ".(float)$_POST['amount_gross'].' <> '.(float)$amount);
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
			if(in_array($key, $this->payfast_keys)) 
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
		$file = $config_abs_path."/log/payfast_transaction";
		if(!$success) $file = $config_abs_path."/log/payfast_error";

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
		$file = $config_abs_path."/log/payfast_debug";

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

		if(!isset($_POST["payfast_merchant_id"]) || !$_POST["payfast_merchant_id"]) $this->addError($lng['settings']['errors']['required_payfast_merchant_id'].'<br />');

		if(!isset($_POST["payfast_merchant_key"]) || !$_POST["payfast_merchant_key"]) $this->addError($lng['settings']['errors']['required_payfast_merchant_key'].'<br />');

		if(!isset($_POST["payfast_item_name"]) || !$_POST["payfast_item_name"]) $this->addError($lng['settings']['errors']['required_payfast_item_name'].'<br />');

		if($this->getError()!='') {

			$this->tmp['demo'] = checkbox_value("payfast_demo");	

			$array_fields = array( "merchant_id", "merchant_key", "item_name" );

			foreach ($array_fields as $field) {
				if(isset($_POST["payfast_".$field])) $this->tmp[$field] = cleanStr($_POST["payfast_".$field]);
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
		$this->clean['demo'] = checkbox_value("payfast_demo");	
		$sql = "update ".$this->table." set `demo` = ".$this->clean['demo'];

		$array_fields = array( "merchant_id", "merchant_key", "item_name" );

		foreach ($array_fields as $field) {

			if(isset($_POST["payfast_".$field])) $this->clean[$field] = escape($_POST["payfast_".$field]); else $this->clean[$field] = '';
			
			$sql.=", `$field` = '".$this->clean[$field]."'";
			
		}

		$db->query($sql);
		return 1;
	}

	function correctSettings() {
		
		return 1;

	}

}
?>
