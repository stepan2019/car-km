<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class fortumo {

	var $post;
	var $user_key;
	var $fortumo_keys;
	var $invoice_no;
	var $pay_settings;
	var $table;
	var $products_table;
	var $error;
	var $tmp;
	var $formTitle;
	var $name;

	public function __construct()
	{

		global $db;
		global $config_table_prefix;
		$arr = $db->fetchAssoc("select `processor_table`, `processor_ret_table` from ".TABLE_PAYMENT_PROCESSORS." where `processor_code` like 'fortumo'");

		$this->table = $config_table_prefix.$arr['processor_table'];
		$this->products_table = $config_table_prefix."fortumo_products";
		$this->ret_table = $config_table_prefix.$arr['processor_ret_table'];
		$this->name = "fortumo";
	}

	function getTable() {
		
		global $db;
		global $config_table_prefix;
		$table = $config_table_prefix.$db->fetchRow("select `processor_table` from ".TABLE_PAYMENT_PROCESSORS." where `processor_code` like 'fortumo'");
		return $table;
	}

	function setTable($table) {

		$this->table = $table;
	
	}

	function setAmount($str) {


		global $db;
		$product = $db->fetchAssoc("select * from ".$this->products_table." where `price` = '".$str."' limit 1");
		if(!$product) {
			$product = $db->fetchAssoc("select * from ".$this->products_table." where `price` > '".$str."' order by `price` asc limit 1");
		}
		$this->pay_settings['keyword'] = $product['keyword'];
		$this->pay_settings['short_code'] = $product['short_code'];
		$this->pay_settings['amount'] = $product['price'];

		$amount = number_format($product['price'], 2, '.', '');
		$this->post['amount'] = $amount;
		$this->amount = $amount;

		// update amount paid to payment action??????

	}

	function init($key = '') {

		global $config_live_site;
		$this->pending = 0;
		if($key) 
			$this->user_key = $key;
		else { 
			if(isset($_GET['ukey']) && $_GET['ukey']) $this->user_key = $_GET['ukey'];
			else if(isset($_GET['message']) && $_GET['message']) 
			$this->user_key = $this->getKey();
			else $this->user_key = $this->randCode();
		}
		$this->postback_url = $config_live_site."/payment_return/fortumo.php";

		$this->pay_settings = $this->getSettings();
		// set amount
		global $db;
		$amount = $db->fetchRow("select `amount` from ".TABLE_PAYMENT_ACTIONS." where ukey='".$this->user_key."'");
		$this->setAmount($amount);
		$this->setTest($this->pay_settings["test"]);

		$this->fortumo_keys = array("message", "sender", "country", "price",
			       "currency", "service_id", "message_id", "keyword", "shortcode",
			       "operator", "billing_type", "status", "test",
			       "sig" );

	}

	function randCode() {

		// generate random code to send in the message
		return $this->random_string(6);

	}

	function random_string($len=5, $str='')
	{
		for($i=1; $i<=$len; $i++)
		{
			$ord=rand(49, 104);
			if((($ord >= 49) && ($ord <= 57)) || (($ord >= 97) && ($ord <= 104)))
				$str.=chr($ord);
			else
				$str.=$this->random_string(1);	
		}
		return $str;
	}

	function getKey() {
		if(!$_GET['message'] || !$_GET['keyword']) return '';
		$msg = rawurldecode($_GET['message']);
		$keyword = rawurldecode($_GET['message']);
		// get the last part of the message
		$arr = explode(" ", $keyword);
		return $arr[count($arr)-1];
	}

	function getUserKey() {

		return $this->user_key;

	}

	function getPost() {

		//return $this->post;

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
/*
	function setCurrency($str) {

		$this->post['currency_code'] = $str;

	}
*/

	function setTest($value) {

		if($value==1) $this->test = 'true';
		else $this->test = 'false';
		return 1;

	}

	function getSettings() {

		global $db;
		$result = $db->fetchAssoc("select * from ".$this->table);
		$result['products'] = $db->fetchAssocList("select * from ".$this->products_table);
		return $result;

	}

	function getForm()
	{
		$form = <<<ENDFORM
<form method="post" name="payment_form" id="payment_form" action="%s/payment_return/fortumo.php?ukey=%s">
<input type="submit" name="submit_payment" value="%s">
</form>
ENDFORM;

		global $config_live_site;
		$str =  sprintf($form, $config_live_site, $this->user_key, $this->formTitle);
		return $str;

	}

	function info() {

		// return info like : send $settings['keyword']." ".$ukey to $settings['short_code']
		// the price is ....
		$str = info::getVal("fortumo_info");
		$key = $this->pay_settings['keyword']." ".$this->user_key;

		$str = str_replace("::KEY::", $key, $str);
		$str = str_replace("::SHORT_CODE::", $this->pay_settings['short_code'], $str);
		global $db;
		//$amount = $db->fetchRow("select `amount` from ".TABLE_PAYMENT_ACTIONS." where ukey='".$this->user_key."'");
		$str = str_replace("::AMOUNT::", $this->amount, $str);

		return $str;
	}

	function process()
	{

		$failed_info = info::getVal("fortumo_failed");
		$success_info = info::getVal("fortumo_success");

		if(!in_array(getRemoteIp(),
			array('79.125.125.1', '79.125.5.205', '79.125.5.95'))) {
			$this->log("validateData: ERROR: Invalid IP:".getRemoteIp());
			// error message 
			echo $failed_info;
			return 0;
		}

		// check the signature
		$secret = $this->pay_settings['secret']; // insert your secret between ''
		if(!empty($secret) && !$this->check_signature($_GET, $secret)) {
			$this->log("validateData: ERROR: Invalid signature!");
			// error message 
			echo $failed_info;
			return 0;
		}

		// payment processes message
		echo $success_info;

		$success = 0;
		$success = $this->saveToDB();
		if(!$success) $this->log("saveToDB: ERROR: Cannot save to db!");

		$this->logIt($success);

		return 1;
	}

	function check_signature($params_array, $secret) {
		ksort($params_array);

		$str = '';
		foreach ($params_array as $k=>$v) {
			if($k != 'sig' && $k!="mode") {
				$str .= "$k=$v";
			}
		}
		$str .= $secret;
		$signature = md5($str);

		return ($params_array['sig'] == $signature);
	}

	function saveToDB()
	{

		global $db;
		$addtosql = '';
		$entirepost = '';

		foreach($_GET as $key => $val) 
		{
			if(in_array($key, $this->fortumo_keys)) 
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
		$content .= "RECEIVED values:\n";
		foreach($_GET as $key => $val)
		{
			$content .= escape($key)."=".escape($val)."\n";
		}
		$content .= "-----------------------------------\n";

		// Create File
		global $config_abs_path;
		$file = $config_abs_path."/log/fortumo_transaction";
		if(!$success) $file = $config_abs_path."/log/fortumo_error";

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
		$file = $config_abs_path."/log/fortumo_debug";

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
		$this->clean['test'] = checkbox_value("test");
		$sql = "update ".$this->table." set test = ".$this->clean['test'];

		$array_fields = array( "currency" );

		foreach ($array_fields as $field) {
			if(isset($_POST[$field])) $this->clean[$field] = escape($_POST[$field]); else $this->clean[$field] = '';
			$sql.=", `$field` = '".$this->clean[$field]."'";
		}

		$db->query($sql);
		return 1;

	}

	function correctSettings() {
		
		return 1;
	}

	function getPending() {

		return $this->pending;

	}

	function deleteProduct($id) {

		global $config_demo;
		if($config_demo==1) return;
		
		global $db;
		$db->query("delete from ".$this->products_table." where id='$id'");
		return 1;
	}

	function addProduct() {

		global $lng;
		$this->error='';
		$this->tmp=array();

		$this->clean=array();
		$this->check_form();
		if($this->getError()!='') return 0;

		global $config_demo;
		if($config_demo==1) $this->addError($lng['general']['errors']['demo'].'<br />');

		global $db;
		$sql = "insert into ".$this->products_table." set ";

		$array_fields = array( "keyword", "short_code", "secret", "price" );

		$i = 0;
		foreach ($array_fields as $field) {
			if(isset($_POST[$field])) $this->clean[$field] = escape($_POST[$field]); else $this->clean[$field] = '';
			if($i) $sql.=", ";
			$sql.="`$field` = '".$this->clean[$field]."'";
			$i++;
		}

		$db->query($sql);
		return 1;

	}

}
?>