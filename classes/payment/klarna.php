<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

global $config_abs_path;
require_once $config_abs_path."/classes/payment/Klarna/Checkout.php";

class klarna {

	var $order;
	var $create;

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

	public function __construct()
	{
		global $db;
		global $config_table_prefix;
		$arr = $db->fetchAssoc("select `processor_table`, `processor_ret_table` from ".TABLE_PAYMENT_PROCESSORS." where `processor_code` like 'klarna'");

		$this->table = $config_table_prefix.$arr['processor_table'];
		$this->ret_table = $config_table_prefix.$arr['processor_ret_table'];
		$this->subscrAutoRenew = 0;
		$this->subscrId = 0;
		$this->name = "klarna";

	}

	function getTable() {
		
		global $db;
		global $config_table_prefix;
		$table = $config_table_prefix.$db->fetchRow("select `processor_table` from ".TABLE_PAYMENT_PROCESSORS." where `processor_code` like 'klarna'");
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
		$this->postback_url = $config_live_site."/payment_return/klarna.php";

		$this->pay_settings = $this->getSettings();

		$this->setDemo($this->pay_settings["test"]);

		$this->create = array();
		$this->create['purchase_country'] = $this->pay_settings['country'];
		$this->create['purchase_currency'] = $this->pay_settings['currency'];
		$this->create['locale'] = $this->pay_settings['locale'];
		$this->create['merchant']['id'] = $this->pay_settings['merchant_id'];
		$this->create['merchant']['terms_uri'] = $this->pay_settings['terms_uri'];
		global $config_live_site;
		$this->create['merchant']['checkout_uri'] = $config_live_site.$_SERVER['REQUEST_URI']; // current uri???? ????????
		$this->create['merchant']['confirmation_uri'] = $this->postback_url.'?mode=success&ukey='.$this->user_key.'&klarna_order={checkout.order.uri}';
		$this->create['merchant']['push_uri'] = $this->postback_url.'?mode=process&ukey='.$this->user_key.'&klarna_order={checkout.order.uri}';

	}

	function getUserKey() {

		return $this->user_key;

	}

	function setFormTitle($str) {

	}

	function getPost() {

		return $this->create;

	}

	function setDebug($val) {

		$this->debug = $val;

	}

	function setInvoiceNo($val) {

		$this->create['cart']['items'][0]['reference'] = strval($val);

	}

	function setPostBack($str) {

		$this->postback_url = $str;

	}

	function setAmount($str) {

		// price in cents
		$amount = $str*100;

$cart = array(
    array(
        'reference' => '', // set invoice id in setInvoiceNo
        'name' => '', // set with setItemName
        'quantity' => 1,
        'unit_price' => $amount,
	'discount_rate' => 0,
        'tax_rate' => 0
    )
);

foreach ($cart as $item) {
    $this->create['cart']['items'][] = $item;
}

	}

	function setItemName($str) {

		$this->create['cart']['items'][0]['name'] = $str;

	}

	function setDemo($value) {

		if($value==1) 
			Klarna_Checkout_Order::$baseUri = 'https://checkout.testdrive.klarna.com/checkout/orders';
		else 
			Klarna_Checkout_Order::$baseUri = 'https://checkout.klarna.com/checkout/orders';
		Klarna_Checkout_Order::$contentType  = "application/vnd.klarna.checkout.aggregated-order-v2+json";

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

		$connector = Klarna_Checkout_Connector::create($this->pay_settings['sharedSecret']);
		$this->order = new Klarna_Checkout_Order($connector);

		$this->order->create($this->create);

		$this->order->fetch();

		// Store location of checkout session
		$_SESSION['klarna_checkout'] = $sessionId = $this->order->getLocation();

		// Display checkout
		$snippet = $this->order['gui']['snippet'];

		return "<div>{$snippet}</div>";

	}

	function process()
	{


		@$checkoutId = $_GET['klarna_order'];

		$connector = Klarna_Checkout_Connector::create($this->pay_settings['sharedSecret']);
		$this->order = new Klarna_Checkout_Order($connector, $checkoutId);

		$this->order->fetch();

		if ($this->order['status'] == "checkout_complete") {

			$success = $this->saveToDB();
			if(!$success) $this->log("saveToDB: ERROR: Cannot save to db!");
			$processed=1;

		}
		else
			$processed=0;

		$this->logIt($success);

		return $processed;
	}

	function getConfirmation()
	{

		Klarna_Checkout_Order::$contentType  = "application/vnd.klarna.checkout.aggregated-order-v2+json";

		$checkoutId = $_SESSION['klarna_checkout'];

		$connector = Klarna_Checkout_Connector::create($this->pay_settings['sharedSecret']);
		$order = new Klarna_Checkout_Order($connector, $checkoutId);

		$order->fetch();

		if ($order['status'] == 'checkout_incomplete')
 			return 0;

		$snippet = $order['gui']['snippet'];
		// DESKTOP: Width of containing block shall be at least 750px
		// MOBILE: Width of containing block shall be 100% of browser window (No
		// padding or margin)
		unset($_SESSION['klarna_checkout']);

		return "<div>{$snippet}</div>";


	}


// ???????
	function saveToDB()
	{

		global $db, $config_abs_path;
		require_once $config_abs_path."/libs/JSON.php";
		$entirepost = json_encode($_GET);

		$timestamp = date("Y-m-d H:i:s");
		$res = $db->query("INSERT INTO ".$this->ret_table." SET date='$timestamp', entirepost='".$entirepost."',ukey='".$this->user_key."'");

		$res_upd = $db->query("update ".TABLE_PAYMENT_ACTIONS." SET completed='1' where ukey='".$this->user_key."'");

		return 1;

	}


	function logIt($success)
	{
		if(!$this->debug) return;
		// Generate content
		/*$content = "-----------------------------------\n".date("r")."\n";
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
		$content .= "-----------------------------------\n";*/

		$content = print_r($this->order, true);

		// Create File
		global $config_abs_path;
		$file = $config_abs_path."/log/klarna_transaction";
		if(!$success) $file = $config_abs_path."/log/klarna_error";

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
		$file = $config_abs_path."/log/klarna_debug";

		$handle = fopen($file, "a");
		fwrite($handle, $content);
		fclose($handle);
	}


	function getError() {
	
		return $this->error;

	}

	function addError($str) {

		$this->error.=$str;

	}

	function setError($str) {

		$this->error=$str;

	}

	function getTmp() {
	
		return $this->tmp;

	}

	function check_form() {

		global $lng;

		if(!isset($_POST["merchant_id"]) || !$_POST["merchant_id"]) $this->addError('Please enter your merchant id!<br />');
		if(!isset($_POST["sharedSecret"]) || !$_POST["sharedSecret"]) $this->addError('Please enter your account shared secret!<br />');
		if(!isset($_POST["terms_uri"]) || !$_POST["terms_uri"]) $this->addError('Please enter your Terms page URI!<br />');

		if($this->getError()!='') {

			if($_POST['test']=="on") $this->tmp['test'] = 1; else $this->tmp['test'] = 0;

			$array_fields = array( "merchant_id", "sharedSecret", "klarna_currency", "klarna_country", "klarna_locale", "terms_uri" );

			foreach ($array_fields as $field) {
				if(isset($_POST[$field])) $this->tmp[$field] = cleanStr($_POST[$field]);
			}
			return 0;
		}
		return 1;
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
		$sql = "update ".$this->table." set `test` = ".$this->clean['test'];

		$array_fields = array( "merchant_id", "sharedSecret", "klarna_currency", "klarna_country", "klarna_locale", "terms_uri" );

		foreach ($array_fields as $field) {

			if(isset($_POST[$field])) $this->clean[$field] = escape($_POST[$field]); else $this->clean[$field] = '';

		}
		$sql.=", `merchant_id` = '".$this->clean['merchant_id']."'";
		$sql.=", `sharedSecret` = '".$this->clean['sharedSecret']."'";
		$sql.=", `currency` = '".$this->clean['klarna_currency']."'";
		$sql.=", `country` = '".$this->clean['klarna_country']."'";
		$sql.=", `locale` = '".$this->clean['klarna_locale']."'";
		$sql.=", `terms_uri` = '".$this->clean['terms_uri']."'";

		$db->query($sql);
		return 1;
	}

	function correctSettings() {
		
		return 1;
	}

}
?>
