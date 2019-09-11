<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class to_checkout {

	var $postback_url;
	var $post;
	var $user_key;
	var $to_checkout_keys;
	var $pending;
	var $invoice_no;
	var $pay_settings;
	var $error;
	var $tmp;
	var $formTitle;

	public function __construct()
	{
		global $db;
		global $config_table_prefix;
		$arr = $db->fetchAssoc("select `processor_table`, `processor_ret_table` from ".TABLE_PAYMENT_PROCESSORS." where `processor_code` like '2co'");

		$this->table = $config_table_prefix.$arr['processor_table'];
		$this->ret_table = $config_table_prefix.$arr['processor_ret_table'];

		//$this->table = $this->getTable();

	}

	function getTable() {
		
		global $db;
		global $config_table_prefix;
		$table = $config_table_prefix.$db->fetchRow("select `processor_table` from ".TABLE_PAYMENT_PROCESSORS." where `processor_code` like '2co'");
		return $table;
	}

	function setTable($table) {

		$this->table = $table;
	
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

	function init($key='') {

		global $config_live_site;
		$this->pending = 0;

		$this->pay_url = 'https://www.2checkout.com/2co/buyer/purchase';
		if($key) $this->user_key = $key;
		else $this->user_key = isset($_POST['ukey'])?$_POST['ukey']:md5(uniqid(rand(), true));
		$this->postback_url = $config_live_site."/payment_return/to_checkout.php";

		$this->pay_settings = $this->getSettings();
		//$this->pending = $this->pay_settings['pending'];

		$this->post['id_type'] = '1';//Id_type can hold a value of 1 if you wish to reference your product from the Your Vendor ID value which you entered when you created your product or a value of 2 if you wish to reference the product based on the Product ID which is assigned to your product by the 2Checkout system.
		//$this->post['sid'] = $pay_settings->getVal('to_checkout_sid');
		$this->post['x_login'] = $this->pay_settings['to_checkout_sid'];
		$demo=$this->pay_settings['to_checkout_demo'];
		if($demo) $this->post['demo'] = 'Y'; else $this->post['demo'] = 'N';
		$this->post['ukey'] = $this->user_key;
		$this->post['quantity'] = 1;
		$this->post['fixed'] = 'Y';

		$this->post['x_receipt_link_url'] = $this->postback_url;

		$this->to_checkout_keys = array("x_login", "key", "X_State", "X_Email", "X_Address", "X_Phone", "X_Country", "ukey", "order_number", "merchant_order_id", "country", "ip_country", "lang", "cart_id", "demo", "pay_method", "X_City", "x_amount", "credit_card_processed", "X_Zip", "card_holder_name", "merchant_product_id", "x_invoice_num");

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

	function setSid($str) {

		$this->post['x_login'] = $str;

	}

	function setAmount($str) {

		//$this->post['total'] = $str;
		$this->post['x_amount'] = $str;
		$this->total = $str;

	}

	function getSettings() {

		global $db;
		$result = $db->fetchAssoc("select * from ".$this->table);
		return $result;

	}

	function setPending($val) {

		$this->pending = $val;

	}

	function getPending() {

		return $this->pending;

	}

	function getForm()
	{

		$this->post['x_Invoice_Num'] = $this->invoice_no;

		$form = <<<ENDFORM
<form method="post" name="payment_form" id="payment_form" action="%s">
%s
<center><input type="submit" name="submit_payment" value="%s"></center>
</form>
ENDFORM;
		$inputs = '';
		foreach($this->post as $key => $val) 
		{
			$inputs .= '<input type="hidden" name="'.$key.'" value="'.$val.'"/>
';
		}

		$str =  sprintf($form, $this->pay_url, $inputs, $this->formTitle);
		return $str;

	}

	function process()
	{
		if($this->validateData())
		{
			$success = $this->saveToDB();
			if(!$success) $this->log("saveToDB: ERROR: Cannot save to db!");
			$processed=1;
			//if($_POST['credit_card_processed']!='Y') $this->setPending(1);

		} else $processed=0;
		$this->logIt($success);

		return $processed;
	}

	function validateData()
	{
		if(!$this->dataCorrect())
		{
			$this->log("validateData: BAD dataCorrect");
			return 0;
		}
		return 1;
	}

	function dataCorrect()
	{
		global $db;
		$res_ad = $db->query("select amount from ".TABLE_PAYMENT_ACTIONS." where ukey='".$this->user_key."'");
		if(!$db->numRows($res_ad)) return 0;
		$arr = $db->fetchAssoc();
		$am = $arr['amount'];
		// format so there are not more than 2 decimals
		$amount = number_format($am, 2, '.', '');

		$sid = $this->pay_settings["to_checkout_sid"];
		$secret = $this->pay_settings["to_checkout_secret"];
		$demo = $this->pay_settings["to_checkout_demo"];

		if(isset($_POST['x_amount']) && (float)$_POST['x_amount'] != (float)$amount) { 
			$this->log("(float)$ _POST['x_amount'] != (float)$ amount: ".(float)$_POST['x_amount'].' <> '.(float)$amount);
			return 0;
		}

		if($demo==0) $order=$_POST['order_number']; else $order=1;
		$string_to_hash = $secret.$sid.$order.$_POST['x_amount'];
		$check_key = strtoupper(md5($string_to_hash));

		if($check_key != $_POST["x_MD5_Hash"]) { 
			$this->log("invalid x_MD5_Hash");
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
			if(in_array($key, $this->to_checkout_keys)) 
			{
				$addtosql .= "`".$key."`='".$val."',";
			}
			$entirepost .= "[".$key."]=\'".$val."\',";
		}
		$addtosql = rtrim($addtosql,','); 

		$res_upd = $db->query("update ".TABLE_PAYMENT_ACTIONS." SET completed='1' where ukey='".$this->user_key."'");

		$timestamp = date("Y-m-d H:i:s");
 		$res = $db->query("INSERT INTO ".$this->ret_table." SET ".$addtosql.", entirepost='".$entirepost."', date='$timestamp'");

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
		$file = $config_abs_path."/log/2co_transaction";
		if(!$success) $file = $config_abs_path."/log/2co_error";

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
		$file = $config_abs_path."/log/2co_debug";

		$handle = fopen($file, "a");
		fwrite($handle, $content);
		fclose($handle);
	}

	function saveSettings() {

		global $lng;
		$this->error='';
		$this->tmp=array();

		global $config_demo;
		if($config_demo==1) $this->addError($lng['general']['errors']['demo'].'<br />');

		global $db;
		$this->clean['to_checkout_demo'] = checkbox_value("to_checkout_demo");	
		$sql = "update ".$this->table." set to_checkout_demo = ".$this->clean['to_checkout_demo'];

		$array_fields = array( "to_checkout_sid", "to_checkout_secret" );

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
		if(!$array_settings['to_checkout_sid'] || !$array_settings['to_checkout_sid']) {
			$this->setError($lng['settings']['errors']['2co_settings_missing']);
			return 0;
		}
		return 1;
	}

}
?>
