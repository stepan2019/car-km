<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class tpay {

	var $postback_url;
	var $post;
	var $user_key;
	var $tpay_keys;
	var $pending;
	var $invoice_no;
	var $pay_settings;
	var $table;
	var $error;
	var $tmp;
	var $products_table;

	public function __construct()
	{
		global $db;
		global $config_table_prefix;
		$arr = $db->fetchAssoc("select `processor_table`, `processor_ret_table` from ".TABLE_PAYMENT_PROCESSORS." where `processor_code` like 'tpay'");

		$this->table = $config_table_prefix.$arr['processor_table'];
		$this->ret_table = $config_table_prefix.$arr['processor_ret_table'];
		$this->products_table = $config_table_prefix."tpay_products";
		$this->subscrAutoRenew = 0;
		$this->subscrId = 0;
		$this->name = "tpay";

		//$this->table = $this->getTable();

	}

	function getTable() {
		
		global $db;
		global $config_table_prefix;
		$table = $config_table_prefix.$db->fetchRow("select `processor_table` from ".TABLE_PAYMENT_PROCESSORS." where `processor_code` like 'tpay'");
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
		$this->postback_url = $config_live_site."/payment_return/tpay.php";

		$this->pay_settings = $this->getSettings();
		$this->publicKey = $this->pay_settings['tpay_public_key'];
		$this->privateKey = $this->pay_settings['tpay_private_key'];
	//	$this->payment_link = "http://staging.tpay.me/api/TPay.svc/json/InitializeDirectPaymentTransaction";

		$this->post['orderInfo'] = $this->user_key;
		$this->post['action'] = "pay";
		$this->post['version'] = "1";
		$this->post['date'] = date("Y-m-d H:i:s")."Z";

		$this->tpay_url = "http://staging.tpay.me/widget/tpaywidget.aspx";
		
		//$this->tpay_keys = array( );

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

		global $db;
		$product = $db->fetchAssoc("select * from ".$this->products_table." where `price` = '".$str."' limit 1");
		if(!$product) {
			$product = $db->fetchAssoc("select * from ".$this->products_table." where `price` > '".$str."' order by `price` asc limit 1");
		}
		$this->post['productCatalogName'] = $product['catalog'];
		$this->post['productId'] = $product['product_id'];
		//$this->pay_settings['amount'] = $product['price'];

		$amount = number_format($product['price'], 2, '.', '');
		//$this->post['amount'] = $amount;
		$this->amount = $amount;

		// make signature
		//$action="pay";
		//$version="1";
		//$date = date("Y-m-d H:i:s")."Z";
		$locale = "";
		$returnUrl = $this->postback_url.'?mode=success';
		$orderInfo = $this->user_key;
		$msisdn='';
		$message = $this->post['action'] . $this->post['version'] . $this->post['date'] . $this->post['productCatalogName'] . $this->post['productId'] .
           $msisdn . $locale . $returnUrl . $orderInfo;
       //$digest = $this->publicKey . ":" . HexString(HMACSHA256($this->privateKey, $message))
        $this->post['signature'] = $this->publicKey . ":" . hash_hmac('sha256', $message, $this->privateKey);

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
		$result['products'] = $db->fetchAssocList("select * from ".$this->products_table);
		return $result;

	}

	function getForm()
	{

		global $lng;
		/*$form = <<<ENDFORM
		<script type="text/javascript" src="http://staging.tpay.me/widget/tpaywidget.ashx"></script>
<a href="%s?%s" class="tpaybutton">{$lng['general']['submit']}</a>
ENDFORM;*/

		$form = <<<ENDFORM
		<a href="%s?%s" class="tpaybutton">{$lng['general']['submit']}</a>
		<script type="text/javascript">var script = document.createElement('script'); script.type = 'text/javascript'; script.src = 'http://staging.tpay.me/widget/tpaywidget.ashx'; document.body.appendChild(script);</script>
ENDFORM;

		$inputs = '';
		foreach($this->post as $key => $val) 
		{
			$inputs .= $key."=".$val."&";
		}
		$inputs = rtrim($inputs, "&");

		$str =  sprintf($form, $this->tpay_url, $inputs);
		//echo $str;
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

		$url_parsed=parse_url($this->tpay_url);
		$fp = fsockopen("ssl://".$url_parsed['host'],"443",$err_num,$err_str,30);
		if(!$fp) {

			//$this->last_error = "fsockopen error no. $errnum: $errstr";
			//$this->log_ipn_results(false);
			return 0;

		} 
 
		// Post the data back to tpay
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

			if( strtolower(trim($_POST['business'])) != strtolower(trim($this->pay_settings["tpay_email"])) ) {

				$this->log("$ this->post['business'] != $ _POST['business']: ".$this->pay_settings["tpay_email"].' <> '.$_POST['business']);
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
		$currency = $this->pay_settings["tpay_currency"];
		$tpay_pay_title = $this->pay_settings["tpay_pay_title"];

		if(isset($_POST['mc_gross']) && ((float)$_POST['mc_gross'] - (float)$_POST['tax']) != (float)$amount) { 
			$this->log("(float)$ _POST['mc_gross'] - $ _POST['tax'] != (float)$ amount: ".(float)$_POST['mc_gross'].' - '.$_POST['tax'].' <> '.(float)$amount);
			return 0;
		}
		if(isset($_POST['mc_currency']) && $_POST['mc_currency'] != $currency) { 
			$this->log("$ _POST['mc_currency'] != $ currency: ".$_POST['mc_currency'].' <> '.$currency);
			return 0;
		}
		/*
		if(!isset($_POST['item_name']) || $_POST['item_name'] != $tpay_pay_title) { 
			$this->log("$ _POST['item_name'] != $ tpay_pay_title: ".$_POST['item_name'].' <> '.$tpay_pay_title);
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
			if(in_array($key, $this->tpay_keys)) 
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
		$file = $config_abs_path."/log/tpay_transaction";
		if(!$success) $file = $config_abs_path."/log/tpay_error";

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
		$file = $config_abs_path."/log/tpay_debug";

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

		if(!isset($_POST["tpay_public_key"]) || !$_POST["tpay_public_key"]) $this->addError('Please enter the Public Key<br />');
		if(!isset($_POST["tpay_private_key"]) || !$_POST["tpay_private_key"]) $this->addError('Please enter the Private Key<br />');

		if($this->getError()!='') {

			$array_fields = array( "tpay_public_key", "tpay_private_key" );

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

		$array_fields = array( "tpay_public_key", "tpay_private_key" );
		foreach ($array_fields as $field) {
			if(isset($_POST[$field])) $this->clean[$field] = escape($_POST[$field]); else $this->clean[$field] = '';
		}
		$sql = "update ".$this->table." set tpay_public_key = '".$this->clean['tpay_private_key']."', tpay_private_key = '".$this->clean['tpay_private_key']."'";

		$db->query($sql);
		return 1;
	}

	function correctSettings() {
		
		global $lng;
		$array_settings = $this->getSettings();
		if(!$array_settings['tpay_public_key'] || !$array_settings['tpay_private_key']) {
			$this->setError('If you wish to enable TPay payment, please edit TPay settings');
			return 0;
		}
		return 1;
	}

	function deleteProduct($id) {

		global $config_demo;
		if($config_demo==1) return;
		
		global $db;
		$db->query("delete from ".$this->products_table." where id='$id'");
		return 1;
	}

	function check_product_form() {

		global $lng;

		if(!isset($_POST["catalog"]) || !$_POST["catalog"]) $this->addError('Please enter the catalog!<br />');
		if(!isset($_POST["product_id"]) || !$_POST["product_id"]) $this->addError('Please enter the product id!<br />');
		if(!isset($_POST["price"]) || !$_POST["price"]) $this->addError('Please enter the price!<br />');

	}

	function addProduct() {

		global $lng;
		$this->error='';
		$this->tmp=array();

		$this->clean=array();
		$this->check_product_form();
		if($this->getError()!='') return 0;

		global $config_demo;
		if($config_demo==1) $this->addError($lng['general']['errors']['demo'].'<br />');

		global $db;
		$sql = "insert into ".$this->products_table." set ";

		$array_fields = array( "catalog", "product_id", "price" );

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
