<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class authorize {

	var $postback_url;
	var $post;
	var $user_key;
	var $authorize_keys;
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
		$arr = $db->fetchAssoc("select `processor_table`, `processor_ret_table` from ".TABLE_PAYMENT_PROCESSORS." where `processor_code` like 'authorize'");

		$this->table = $config_table_prefix.$arr['processor_table'];
		$this->ret_table = $config_table_prefix.$arr['processor_ret_table'];

		//$this->table = $this->getTable();

	}

	function getTable() {
		
		global $db;
		global $config_table_prefix;
		$table = $config_table_prefix.$db->fetchRow("select `processor_table` from ".TABLE_PAYMENT_PROCESSORS." where `processor_code` like 'authorize'");
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

		if($key) $this->user_key = $key;
		else $this->user_key = isset($_POST['ukey'])?$_POST['ukey']:md5(uniqid(rand(), true));
		$this->postback_url = $config_live_site."/payment_return/authorize.php";

		$this->pay_settings = $this->getSettings();
		//$this->pending = $this->pay_settings['pending'];
		$secret = $this->pay_settings["authorize_tkey"];

		$this->pay_url = 'https://secure.authorize.net/gateway/transact.dll';

		$demo=$this->pay_settings['authorize_demo'];
		$this->setDemo($demo);

		//test mode
		//$this->pay_url = 'https://test.authorize.net/gateway/transact.dll';

		$this->post['x_Login'] = $this->pay_settings['authorize_login'];
		if($demo) $this->post['x_Test_Request'] = 'TRUE';  else $this->post['x_Test_Request'] = 'FALSE'; 

		$this->post['ukey'] = $this->user_key;
		$this->post['x_Description'] = $this->pay_settings['authorize_pay_title'];
		$this->post['x_Invoice_num'] = date('YmdHis');
		$this->post['x_fp_timestamp'] = time();
		$this->post['x_fp_sequence'] = rand(1, 1000);

		// Specify the url where authorize.net will send the user on success/failure
		$this->post['x_Receipt_Link_URL'] = $this->postback_url.'?mode=receipt&amp;ukey='.$this->user_key;
		// Specify the url where authorize.net will send the IPN
		$this->post['x_Relay_URL'] = $this->postback_url.'?mode=process&amp;ukey='.$this->user_key;
		$this->post['x_Version'] = "3.1";
		$this->post['x_type'] = "AUTH_CAPTURE";
		$this->post['x_delim_data'] = "TRUE";
		$this->post['x_delim_char'] = "|";
		//$this->post['x_relay_response'] = "FALSE";//best practice to further define the AIM transaction format
		$this->post['x_Relay_Response'] = "TRUE";//for SIM
		$this->post['x_Show_Form'] = "PAYMENT_FORM";

		$this->authorize_keys = array("ukey", "x_response_code", "x_response_subcode", "x_response_reason_code", "x_response_reason_text", "x_auth_code", "x_avs_code", "x_trans_id", "x_invoice_num", "x_description", "x_amount", "x_method", "x_type", "x_cust_id", "x_first_name", "x_last_name", "x_company", "x_address", "x_city",  "x_state", "x_zip", "x_country", "x_phone", "x_fax", "x_email", "x_ship_to_first_name", "x_ship_to_last_name", "x_ship_to_company", "x_ship_to_address", "x_ship_to_city", "x_ship_to_state", "x_ship_to_zip", "x_ship_to_country", "x_tax", "x_duty", "x_freight", "x_tax_exempt", "x_po_num", "x_MD5_Hash",  "x_cvv2_resp_code", "x_cavv_response", "x_test_request");

	}

	function setDemo($value) {

		if($value==0) $this->pay_url = 'https://secure.authorize.net/gateway/transact.dll';
		else $this->pay_url = 'https://test.authorize.net/gateway/transact.dll';
		return 1;

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

		$this->total = number_format($str, 2, '.', '');
		$this->post['x_Amount'] = $this->total;

		$data = $this->post['x_Login'] . '^' .
        	        $this->post['x_fp_sequence'] . '^' .
                	$this->post['x_fp_timestamp'] . '^' .
                	$this->post['x_Amount'] . '^';
		//$this->post['x_fp_hash'] = $this->hmac($this->pay_settings["authorize_secret"], $data);

		if( phpversion() >= '5.1.2' )
		{	
			$this->post['x_fp_hash'] = hash_hmac("md5", $data, $this->pay_settings["authorize_tkey"]); 
		}
		else
		{ 
			$this->post['x_fp_hash'] = bin2hex(mhash(MHASH_MD5, $data, $this->pay_settings["authorize_tkey"])); 
		}


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
		$amount = $arr['amount'];
		$secret = $this->pay_settings["authorize_secret"];
		$demo = $this->pay_settings["authorize_demo"];
		$login=$this->pay_settings['authorize_login'];

//		if(isset($_POST['x_amount']) && $_POST['x_amount'] != $amount) return 0;

		$invoice = intval($_POST['x_invoice_num']);
		$pnref = $_POST['x_trans_id'];
		$amount_back = $_POST['x_amount'];
		$result = intval($_POST['x_response_code']);
		$respmsg = $_POST['x_response_reason_text'];

		$md5source = $secret . $login . $pnref . $amount_back;
		$md5 = md5($md5source);

		if ($result != '1')
		{
			// inalid IPN transaction.
			$this->log("validateData: invalid response code");
			return 0;
		}
		if (strtoupper($md5) != $_POST['x_MD5_Hash'])
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

		foreach($_POST as $key => $val) 
		{
			if(in_array($key, $this->authorize_keys)) 
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

	function hmac ($key, $data)
	{
		$b = 64; // byte length for md5

		if (strlen($key) > $b) {
			$key = pack("H*",md5($key));
		}

		$key  = str_pad($key, $b, chr(0x00));
		$ipad = str_pad('', $b, chr(0x36));
		$opad = str_pad('', $b, chr(0x5c));
		$k_ipad = $key ^ $ipad ;
		$k_opad = $key ^ $opad;

		return md5($k_opad  . pack("H*", md5($k_ipad . $data)));
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
		$file = $config_abs_path."/log/authorize_transaction";
		if(!$success) $file = $config_abs_path."/log/authorize_error";

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
		$file = $config_abs_path."/log/authorize_debug";

		$handle = fopen($file, "a");
		fwrite($handle, $content);
		fclose($handle);
	}

	function check_form() {

		global $lng;
		$array_required = array ("authorize_login", "authorize_tkey");
		foreach ($array_required as $field) {

			if(!isset($_POST[$field]) || !$_POST[$field]) $this->addError($lng['settings']['errors']['required_'.$field].'<br />');

		}

		if($this->getError()!='') {

			if($_POST['authorize_demo']=="on") $this->tmp['authorize_demo'] = 1; else $this->tmp['authorize_demo'] = 0;

			$array_fields = array( "authorize_login", "authorize_tkey", "authorize_secret", "authorize_pay_title" );

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
		$this->clean['authorize_demo'] = checkbox_value("authorize_demo");	
		$sql = "update ".$this->table." set authorize_demo = ".$this->clean['authorize_demo'];

		$array_fields = array( "authorize_login", "authorize_tkey", "authorize_secret", "authorize_pay_title" );

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
		if(!$array_settings['authorize_login'] || !$array_settings['authorize_tkey']) {
			$this->setError($lng['settings']['errors']['authorize_settings_missing']);
			return 0;
		}
		return 1;
	}

}
?>
