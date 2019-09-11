<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

global $config_abs_path;
require_once $config_abs_path."/classes/payment/hipay_mapi/mapi_package.php";
class hipay {

	var $params;
	var $item;
	var $order;
	var $result;
	var $sub;
	var $ins;
	var $isSub;
	var $postback_url;
	var $user_key;
	var $hipay_keys;
	var $pending;
	var $invoice_no;
	var $pay_settings;
	var $table;
	var $error;
	var $tmp;
	var $formTitle;
	var $subscrId;
	var $name;

	public function __construct()
	{
		global $db;
		global $config_table_prefix;
		$arr = $db->fetchAssoc("select `processor_table`, `processor_ret_table` from ".TABLE_PAYMENT_PROCESSORS." where `processor_code` like 'hipay'");

		$this->table = $config_table_prefix.$arr['processor_table'];
		$this->ret_table = $config_table_prefix.$arr['processor_ret_table'];
		$this->subscrAutoRenew = 0;
		$this->subscrId = 0;
		$this->name = "hipay";

	}
	

	function getTable() {
		
		global $db;
		global $config_table_prefix;
		$table = $config_table_prefix.$db->fetchRow("select `processor_table` from ".TABLE_PAYMENT_PROCESSORS." where `processor_code` like 'hipay'");
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
		$this->postback_url = $config_live_site."/payment_return/hipay.php";

		$this->pay_settings = $this->getSettings();
		
		$this->hipay_keys = array("operation", "status", "time",
			       "transid", "amount", "currency", "idformerchant", "merchantdatas",
			       "emailClient", "subscriptionId", "refProduct" );


		$this->params = new HIPAY_MAPI_PaymentParams();
		$this->params->setLogin($this->pay_settings['member_account'], $this->pay_settings['merchant_password']);
		// the amounts will be credited to the first account, except taxes which will be credited to second one 
		$this->params->setAccounts($this->pay_settings['member_account'], $this->pay_settings['member_account']);
		// default locale is French
		$this->params->setLocale($this->pay_settings['locale']);
		$this->params->setMedia("WEB");
		$this->params->setRating("+16");
		$this->params->setPaymentMethod(HIPAY_PAYMENT_METHOD_SIMPLE);
		$this->params->setCaptureDay(HIPAY_MAPI_CAPTURE_IMMEDIATE);
		$this->params->setCurrency($this->pay_settings['currency']);

		// !!!!!!????????
		$this->params->setIdForMerchant("REF6522");

		$this->params->setMerchantSiteId($this->pay_settings['website_id']);
		$this->params->setURLOk($this->postback_url.'?mode=success&amp;ukey='.$this->user_key);
		$this->params->setURLNok($this->postback_url.'?mode=cancel&amp;ukey='.$this->user_key);
		$this->params->setURLCancel($this->postback_url.'?mode=cancel&amp;ukey='.$this->user_key);
		$this->params->setURLAck($this->postback_url.'?mode=process&amp;ukey='.$this->user_key);

		$this->params->setEmailAck($this->pay_settings['notification_email']);


		$t = $this->params->check();
		if(!$t) {
			echo "Error initiating payment!";
		}

	}

	function getUserKey() {

		return $this->user_key;

	}

	function setDebug($val) {

		$this->debug = $val;

	}

	function getPost() {
	    return;
	}

	function setFormTitle($val) {

		$this->formTitle = $val;

	}

	// recurring payments
	function setSubscription($total, $days) {

		$total = number_format($total, 2, '.', '');

		$this->isSub = 1;

		$this->params->setPaymentMethod(HIPAY_PAYMENT_METHOD_MULTI);		
		$this->ins = new HIPAY_MAPI_Installment();
		$this->ins->setPrice($total);
		$this->ins->setTax(array());
		$this->ins->setFirst(true, '1H');
		$t = $this->ins->check();
		if(!$t) {
				echo "An error has occured creating an installment object";
				exit(0);			
			}
		
		$this->sub = new HIPAY_MAPI_Installment();
		$this->sub->setPrice($total);
		$this->sub->setTax(array());
		$this->sub->setFirst(false, $days.'D');
		$t = $this->sub->check();
		if(!$t) {
				echo "An error has occured creating subscription object";
				exit(0);			
			}


		// start subscription order
		$this->orderins = new HIPAY_MAPI_Order();

		global $lng, $settings;
		$this->orderins->setOrderTitle($settings['site_name']." ".$lng['packages']['subscription']);
		$this->orderins->setOrderInfo("");
		$this->orderins->setOrderCategory($this->pay_settings['category']);

		$t = $this->orderins->check();
		if(!$t) {
				echo "An error has occured creating an order object";
				exit(0);			
			}

		// subscription order
		$this->ordersub = new HIPAY_MAPI_Order();
		// !!!!!
		$this->ordersub->setOrderTitle($settings['site_name']." ".$lng['packages']['subscription']);
		$this->ordersub->setOrderInfo("");
		$this->ordersub->setOrderCategory($this->pay_settings['category']);

		$t = $this->ordersub->check();
		if(!$t) {
				echo "An error has occured creating an order object";
				exit(0);			
			}


		return 1;

	}

	function setFirstSubscription($amount, $days) {

		$amount = number_format($amount, 2, '.', '');

		$this->params->setPaymentMethod(HIPAY_PAYMENT_METHOD_MULTI);		
		$this->ins = new HIPAY_MAPI_Installment();
		$this->ins->setPrice($amount);
		$this->ins->setTax(array());
		$this->ins->setFirst(true, '1H');
		$t = $this->ins->check();
		if(!$t) {
				echo "An error has occured creating an installment object";
				exit(0);			
			}


		$this->orderins = new HIPAY_MAPI_Order();

		global $lng, $settings;
		$this->orderins->setOrderTitle($settings['site_name']." ".$lng['packages']['subscription']);
		$this->orderins->setOrderInfo("");
		$this->orderins->setOrderCategory($this->pay_settings['category']);

		$t = $this->orderins->check();
		if(!$t) {
				echo "An error has occured creating an order object";
				exit(0);			
			}


	}

	function setInvoiceNo($val) {

		$this->invoice_no = $val;

	}

	function setPostBack($str) {

		$this->postback_url = $str;

	}


	function setAmount($str) {

		$amount = number_format($str, 2, '.', '');
		$this->amount = $amount;

		$this->order = new HIPAY_MAPI_Order();

		global $lng, $settings;
		$this->order->setOrderTitle($settings['site_name']." ".$lng['general']['order']);
		$this->order->setOrderInfo("");
		$this->order->setOrderCategory($this->pay_settings['category']);

		$t = $this->order->check();

		// the product
		$this->item = new HIPAY_MAPI_Product();
		$this->item->setName($settings['site_name']);
		$this->item->setInfo("");
		$this->item->setQuantity(1);
		$this->item->setCategory($this->pay_settings['category']);
		$this->item->setPrice($amount);
		//$this->item->setTax(array($tax1, $tax2));
		$t = $this->item->check(); 
		if(!$t)
		{
			echo "Item error!";
		}

	}

	function setDemo($value) {

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

		try{
			if($this->isSub) {
			$payment = new HIPAY_MAPI_MultiplePayment($this->params, $this->orderins, $this->ins, $this->ordersub, $this->sub);
		}
		else 
			$payment = new HIPAY_MAPI_SimplePayment($this->params, $this->order, array($this->item)); 
		}
		catch(Exception $e) {echo "Error ".$e->getMessage();}	

		$xmlTx = $payment->getXML();
		$output = HIPAY_MAPI_SEND_XML::sendXML($xmlTx);

		$r = HIPAY_MAPI_COMM_XML::analyzeResponseXML($output, $url, $err_msg);

		if($r==true) {
			header ("Location: ".$url);
			exit(0);
		} else {
			echo $err_msg;
		}

		return $output;

	}

	function process()
	{

		$r = HIPAY_MAPI_COMM_XML::analyzeNotificationXML(cleanStr($_POST['xml']), $this->result['operation'], $this->result['status'], $this->result['date'], $this->result['time'], $this->result['transid'], $this->result['amount'], $this->result['currency'], $this->result['idformerchant'], $this->result['merchantdatas'], $this->result['emailClient'], $this->result['subscriptionId'], $this->result['refProduct']);

		$ret = $this->validateData();

		$success = $this->saveToDB();
		if(!$success) $this->log("saveToDB: ERROR: Cannot save to db!");

		$this->logIt($ret);

		return $ret;
		
	}

	function validateData() {
		

		if($this->result['status']!="ok")
		{
			$this->log("Payment failed with status:".$this->result['status']);
			return 0;
		}

		if( !isset($this->user_key) || strlen($this->user_key) == 0 )
		{
			$this->log("validateData: ERROR: Invalid ukey:".$this->user_key);
			return 0;
		}

		global $db;
		$res_ad = $db->query("select amount from ".TABLE_PAYMENT_ACTIONS." where ukey='".$this->user_key."'");
		if(!$db->numRows($res_ad)) return 0;
		$am = $db->fetchRow();
		// format so there are not more than 2 decimals
		$amount = number_format($am, 2, '.', '');

		if($this->result['amount'] != $amount) { 

			$this->log("validateData: ERROR: Invalid amount:".$this->result['amount']);
			return 0;
		
		}
		return 1;
	}
	
	
	function saveToDB()
	{

		global $db;
		$addtosql = '';
		$entirepost = '';

		global $config_abs_path;
		require_once $config_abs_path."/libs/JSON.php";

		foreach($this->result as $key => $val) 
		{
			if(in_array($key, $this->hipay_keys)) 
			{
				if($key=="merchantdatas" || $key=="refProduct") $val = json_encode($val);
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

		return $this->isSub;

	}

	function subscrAutoRenew() {

		return $this->isSub;

	}

	function getSubscriptionId() {

		return $this->subscrId;

	}

	function logIt($success)
	{
		if(!$this->debug) return;
		// Generate content
		$content = "-----------------------------------\n".date("r")."\n";

		$content .= "\nRECEIVED POST:\n";

		foreach($_POST as $key => $val)
		{
			$content .= $key."=".$val."\n";
		}
		$content .= "-----------------------------------\n";

		// Create File
		global $config_abs_path;
		$file = $config_abs_path."/log/hipay_transaction";
		if(!$success) $file = $config_abs_path."/log/hipay_error";

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
		$file = $config_abs_path."/log/hipay_debug";

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

	function saveSettings() {

		global $lng;
		$this->error='';
		$this->tmp=array();

		$this->clean=array();

		global $config_demo;
		if($config_demo==1) $this->addError($lng['general']['errors']['demo'].'<br />');

		global $db;
		//$this->clean['paypal_demo'] = checkbox_value("paypal_demo");	
		$sql = "update ".$this->table." set ";

		$array_fields = array( "member_account", "merchant_password", "website_id", "locale", "currency", "notification_email", "category" );
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

	function correctSettings() {
		return true;
	}

}
?>
