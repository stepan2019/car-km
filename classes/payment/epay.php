<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class epay {

	var $postback_url;
	var $post;
	var $user_key;
	var $epay_keys;
	var $pending;
	var $invoice_no;
	var $pay_settings;
	var $table;
	var $error;
	var $tmp;
	var $formTitle;

	public function __construct()
	{
		global $db;
		global $config_table_prefix;
		$arr = $db->fetchAssoc("select `processor_table`, `processor_ret_table` from ".TABLE_PAYMENT_PROCESSORS." where `processor_code` like 'epay'");

		$this->table = $config_table_prefix.$arr['processor_table'];
		$this->ret_table = $config_table_prefix.$arr['processor_ret_table'];

	}

	function getTable() {
		
		global $db;
		global $config_table_prefix;
		$table = $config_table_prefix.$db->fetchRow("select `processor_table` from ".TABLE_PAYMENT_PROCESSORS." where `processor_code` like 'epay'");
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

		$this->postback_url = $config_live_site."/payment_return/epay.php";
		$this->epay_url = "https://ssl.ditonlinebetalingssystem.dk/popup/default.asp";

		$this->pay_settings = $this->getSettings();

		$this->post['merchantnumber'] = $this->pay_settings["epay_merchantnumber"];
		$this->post['language'] = $this->pay_settings["epay_language"];
		$this->post['currency'] = $this->pay_settings["epay_currency"];
		//$this->post['ordretext'] = $this->pay_settings["epay_ordretext"];
		//$this->post['cardtype'] = $this->pay_settings["epay_cardtype"];
		$this->post['windowstate'] = 2;
		$this->post['orderid'] = rand(1, 1000);
		$this->post['instantcapture'] = 1;

		$this->post['accepturl'] = $this->postback_url.'?mode=success&amp;ukey='.$this->user_key;
		$this->post['declineurl'] = $this->postback_url.'?mode=cancel&amp;ukey='.$this->user_key;
		$this->post['callbackurl'] = $this->postback_url.'?mode=process&amp;ukey='.$this->user_key;

		$this->epay_keys = array("tid", "orderid", "amount", "cur",
			       "date", "eKey", "fraud", "transfee", "HTTP_COOKIE",
			       "subscriptionid", "cardid");

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

	function setAmount($amount) {

		$total = number_format($amount, 2, '.', '');
		$total = $total*100;
		$this->post['amount'] = $total;

		//MD5(currency + amount + ordreID + "key set-up in ePay")
		if($this->pay_settings['epay_md5key']) $this->post['md5key'] = md5($this->pay_settings['epay_currency'].$total.$this->post['orderid'].$this->pay_settings['epay_md5key']);

	}

	function setCurrency($str) {

		$this->post['currency'] = $str;

	}

	function setItemName($str) {

		$this->post['item_name'] = $str;

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
		$form = <<<ENDFORM
<form name ="payment_form" id="payment_form" action="%s" method="post" target="_self" "ePay">
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

		$str =  sprintf($form, $this->epay_url, $inputs, $this->formTitle);
		return $str;

	}

	function process()
	{
		if($this->validateData())
		{
			$success = $this->saveToDB();
			if(!$success) $this->log("saveToDB: ERROR: Cannot save to db!");
			$processed=1;

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
		$amount = $arr['amount'];

		$tid = $_GET['tid'];
		$orderid = $_GET['orderid'];
		$amount_back = $_GET['amount'];

		if (!$tid)
		{
			// invalid transaction.
			$this->log("validateData: invalid transaction id");
			return 0;
		}

		if (!$orderid)
		{
			// invalid orderid.
			$this->log("validateData: invalid orderid");
			return 0;
		}

		if(!$this->pay_settings['epay_md5key']) return 1;

		//aamount + orderid + tid + secret key
		$md5source = $amount_back . $orderid . $tid . $this->pay_settings['epay_md5key'];
		$md5 = md5($md5source);

		if (strtoupper($md5) != strtoupper($_GET['eKey']))
		{
			$this->log("validateData: MD5 mismatch");
			return 0;
		} 

		return 1;
	}

	function saveToDB()
	{
		global $db;
		$addtosql = '';
		$entirepost = '';

		foreach($_GET as $key => $val) 
		{
			if($key=="amount") $val = $val/100;

			if(in_array($key, $this->epay_keys)) 
			{
				$addtosql .= $key."='".$val."',";
			}
			$entirepost .= "[".$key."]=\'".$val."\',";
		}
		$addtosql = rtrim($addtosql,','); 

		$res_upd = $db->query("update ".TABLE_PAYMENT_ACTIONS." SET completed='1' where ukey='".$this->user_key."'");

		$res = $db->query("INSERT INTO ".$this->ret_table." SET entirepost='".$entirepost."', ".$addtosql.",ukey='".$this->user_key."'");


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

		foreach($_GET as $key => $val)
		{
			$content .= $key."=".$val."\n";
		}
		$content .= "-----------------------------------\n";

		// Create File
		global $config_abs_path;
		$file = $config_abs_path."/log/epay_transaction";
		if(!$success) $file = $config_abs_path."/log/epay_error";

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
		$file = $config_abs_path."/log/epay_debug";

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
		$array_required = array ("epay_merchantnumber");
		foreach ($array_required as $field) {

			if(!isset($_POST[$field]) || !$_POST[$field]) $this->addError($lng['settings']['errors']['required_'.$field].'<br />');

		}

		if($this->getError()!='') {

			$array_fields = array ("epay_merchantnumber", "epay_language", "epay_currency", "epay_md5key");

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

		$array_fields = array ("epay_merchantnumber", "epay_language", "epay_currency", "epay_md5key");

		$i=0;
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
		
		global $lng;
		$array_settings = $this->getSettings();
		if(!$array_settings['epay_merchantnumber']) {
			$this->setError($lng['settings']['errors']['epay_settings_missing']);
			return 0;
		}
		return 1;
	}

}
?>
