<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class paytpv {

	var $postback_url;
	var $post;
	var $user_key;
	var $paytpv_keys;
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
		$arr = $db->fetchAssoc("select `processor_table`, `processor_ret_table` from ".TABLE_PAYMENT_PROCESSORS." where `processor_code` like 'paytpv'");

		$this->table = $config_table_prefix.$arr['processor_table'];
		$this->ret_table = $config_table_prefix.$arr['processor_ret_table'];
		$this->name = "paytpv";
		//$this->table = $this->getTable();

	}

	function getTable() {
		
		global $db;
		global $config_table_prefix;
		$table = $config_table_prefix.$db->fetchRow("select `processor_table` from ".TABLE_PAYMENT_PROCESSORS." where `processor_code` like 'paytpv'");
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
		$this->postback_url = $config_live_site."/payment_return/paytpv.php";


		// if processing
		if(isset($_REQUEST['r']) && $_REQUEST['r']) {
		    $order_id =  intval($_REQUEST['r']);

		    global $db;
		    $res_ad = $db->query("select `ukey` from ".TABLE_PAYMENT_ACTIONS." where id='".$order_id."'");
		    if($db->numRows($res_ad))
			$this->user_key = $db->fetchRow();
		} // end if processing

		$this->pay_settings = $this->getSettings();

		$this->paytpv_url = "https://www.paytpv.com/gateway/fsgateway.php";

		$this->post['ACCOUNT'] = $this->pay_settings["paytpv_account"];
		$this->post['USERCODE'] = $this->pay_settings["paytpv_usercode"];
		$this->post['TERMINAL'] = $this->pay_settings["paytpv_terminal"];
		$this->post['CURRENCY'] = $this->pay_settings["paytpv_currency"];
		$this->post['CONCEPT'] = $this->pay_settings["paytpv_concept"];
		//$this->setDemo($this->pay_settings["paytpv_demo"]);

		$this->post['OPERATION'] = '1';

		$this->post['URLOK'] = $this->postback_url.'?mode=process';
		$this->post['URLKO'] = $this->postback_url.'?mode=cancel';

		$this->paytpv_keys = array("i", "r", "ret", "deserror", "TransactionType", "TransactionName", "CardCountry", "BankDateTime",
			       "Signature", "Order", "Response", "ErrorID", "ErrorDescription",
			       "AuthCode", "Currency", "Amount", "AmountEur",
			       "Language", "AccountCode", "TpvID" );

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

		$amount = number_format($str, 2, '.', '') *100;
		$this->post['AMOUNT'] = $amount;
		$this->amount = $amount;

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
		$this->post['REFERENCE'] = $this->invoice_no;

		$str = $this->post['ACCOUNT'].$this->post['USERCODE'].$this->post['TERMINAL'].$this->post['OPERATION'].$this->post['REFERENCE'].$this->post['AMOUNT'].$this->post['CURRENCY'].md5($this->pay_settings["paytpv_password"]);

		$this->post['SIGNATURE'] = md5($str);


		$form = <<<ENDFORM
<form method="post" name="payment_form" id="payment_form" action="%s">
%s
<input type="image" src="images/logo_paytpv.gif" border="0" name="submit_payment" alt="Make payments with PayTPV">
</form>
ENDFORM;

		$inputs = '';
		foreach($this->post as $key => $val) 
		{
			$inputs .= '<input type="hidden" name="'.$key.'" value="'.$val.'"/>
';
		}

		$str =  sprintf($form, $this->paytpv_url, $inputs);//, $this->formTitle
		return $str;

	}

	function process()
	{

		$processed=0;
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

		$importe = number_format($_REQUEST['i']/100, 2);
		$order_id =  intval($_REQUEST['r']);
		$result =  intval($_REQUEST['ret']);

		global $db;
		$res_ad = $db->query("select `amount`, `ukey` from ".TABLE_PAYMENT_ACTIONS." where id='".$order_id."'");
		if(!$db->numRows($res_ad)) { 
			$this->log("validateData: invalid invoice id: $order_id");
			return 0;
		}
		$arr = $db->fetchAssoc();
		$amount = $arr['amount'];
		//$ukey = $arr['ukey'];
		//$this->user_key = $ukey;
//		$reference = $arr['id'];

		if($_REQUEST['ret']!= 0) {
			$this->log("validateData: error code: ".$_REQUEST['deserror']);
			return 0;
		}
/*
//ip list:
195.78.228.100
195.78.231.132
88.12.31.197
94.199.191.66
94.199.191.67
5.145.170.42
*/
		if( $_REQUEST['h'] !=md5($this->pay_settings["paytpv_usercode"].$_REQUEST['r'].$this->pay_settings["paytpv_password"].$_REQUEST['ret']))
		{
			$this->log("validateData: invalid signature ");
			return 0;
		}

		return 1;

	}

	function saveToDB()
	{

		global $db;
		$addtosql = '';
		$entirepost = '';

		foreach($_REQUEST as $key => $val) 
		{
			if(in_array($key, $this->paytpv_keys)) 
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

		foreach($_REQUEST as $key => $val)
		{
			$content .= $key."=".$val."\n";
		}
		$content .= "-----------------------------------\n";

		// Create File
		global $config_abs_path;
		$file = $config_abs_path."/log/paytpv_transaction";
		if(!$success) $file = $config_abs_path."/log/paytpv_error";

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
		$file = $config_abs_path."/log/paytpv_debug";

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
		$array_required = array ("paytpv_account", "paytpv_usercode", "paytpv_terminal", "paytpv_password");
		foreach ($array_required as $field) {

			if(!isset($_POST[$field]) || !$_POST[$field]) $this->addError($lng['settings']['errors']['required_'.$field].'<br />');

		}

		if($this->getError()!='') {

			$array_fields = array("paytpv_account", "paytpv_usercode", "paytpv_terminal", "paytpv_password", "paytpv_currency");

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
		$array_fields = array( "paytpv_account", "paytpv_usercode", "paytpv_terminal", "paytpv_password", "paytpv_currency" );

		$sql = "update ".$this->table." set ";

		$i=0;
		foreach ($array_fields as $field) {
			if(isset($_POST[$field])) $this->clean[$field] = escape($_POST[$field]); else $this->clean[$field] = '';
			if($field=="paytpv_currency" && $_POST[$field]=="other") $this->clean[$field] = escape($_POST['other_paytpv_currency']);
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
		if(!$array_settings['paytpv_account'] || !$array_settings['paytpv_usercode'] || !$array_settings['paytpv_terminal'] || !$array_settings['paytpv_password']) {
			$this->setError($lng['settings']['errors']['paytpv_settings_missing']);
			return 0;
		}
		return 1;
	}

}
?>
