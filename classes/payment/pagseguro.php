<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/
class pagseguro {

	var $postback_url;
	var $post;
	var $user_key;
	var $keys;
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
	var $code;
	var $curr_element;

	public function __construct()
	{
		global $db;
		global $config_table_prefix;
		$arr = $db->fetchAssoc("select `processor_table`, `processor_ret_table` from ".TABLE_PAYMENT_PROCESSORS." where `processor_code` like 'pagseguro'");

		$this->table = $config_table_prefix.$arr['processor_table'];
		$this->ret_table = $config_table_prefix.$arr['processor_ret_table'];
		$this->subscrAutoRenew = 0;
		$this->subscrId = 0;
		$this->name = "pagseguro";

	}

	function getTable() {
		
		global $db;
		global $config_table_prefix;
		$table = $config_table_prefix.$db->fetchRow("select `processor_table` from ".TABLE_PAYMENT_PROCESSORS." where `processor_code` like 'pagseguro'");
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
		$this->postback_url = $config_live_site."/payment_return/pagseguro.php";

		$this->pay_settings = $this->getSettings();
		$this->setDemo($this->pay_settings["pagseguro_test"]);

		//caso sua aplicação ou loja não utilize o conjunto de caracteres ISO-8859-1, p.e.(UTF-8), é necessário substituir o parâmetro charset do exemplo acima.
		global $appearance_settings;
		if($appearance_settings['charset']!="ISO-8859-1") $this->post['charset'] = $appearance_settings['charset'];

		$this->post['email'] = $this->pay_settings["pagseguro_email"];
		$this->post['token'] = $this->pay_settings["pagseguro_token"]; //https://pagseguro.uol.com.br/integracao/token-de-seguranca.jhtml
		$this->post['itemId1'] = "001";
		$this->post['itemDescription1'] = $this->pay_settings["pagseguro_item_description"];
		$this->post['itemQuantity1'] = 1;
		$this->post['currency'] = $this->pay_settings["pagseguro_currency"];// BRL only
		// set ukey as reference parameter
		$this->post['reference'] = $this->user_key;

		$this->post['redirectURL '] = $this->postback_url.'?mode=success';
		$this->post['notificationURL'] = $this->postback_url.'?mode=process';

		$this->keys = array("code", "reference", "status", "grossAmount", "netAmount", "email", "name", "areaCode", "number");
		// the parsed xml answer
		$this->result = array();

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

	function setDemo($value) {

		if($value==1) { 
			$this->post_url = 'https://ws.sandbox.pagseguro.uol.com.br/v2/checkout';
			$this->payment_url = 'https://sandbox.pagseguro.uol.com.br/v2/checkout/payment.html';
			$this->notifications_url = 'https://ws.sandbox.pagseguro.uol.com.br/v3/transactions/notifications';
		}
		else { 
			$this->post_url = 'https://ws.pagseguro.uol.com.br/v2/checkout';
			$this->payment_url = 'https://pagseguro.uol.com.br/v2/checkout/payment.html';
			$this->notifications_url = 'https://ws.pagseguro.uol.com.br/v3/transactions/notifications';
		}
		return 1;

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

		$amount = number_format($str, 2, '.', '');
		$this->post['itemAmount1'] = $amount;
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

		global $lng;
	
		$postvars = "";
		$first =1 ;
		foreach($this->post as $key => $val) 
		{
			if(!$first) $postvars.="&";
			$postvars .= $key.'='.$val;
			$first=0;
		}
	
		if(_isCurl()) {

			$ch = curl_init();
		
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
			curl_setopt($ch, CURLOPT_URL, $this->post_url);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($ch, CURLOPT_POST, true);
		    $contentLength = "Content-length: " . strlen($postvars);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array( "Content-Type: application/x-www-form-urlencoded; charset=utf-8", $contentLength));
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postvars);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			$response = curl_exec($ch);

			 if($response === false)
			{
				$this->addError("Error Number: ".curl_errno($ch)." Error string: ".curl_error($ch));
				return;
			}

			curl_close($ch);

		} 
		else {
		
			$this->addError("This feature requires CURL installed!");
			return;
		
		}
		
		//echo "   response=".$response;
	
		// parse xml to get the payment code
		$this->parseXML($response);
		
		if(!$this->result['code']) {
			$this->addError("No payment code received!");
			return;
		}
		
//echo "   code=".$this->code;

		$form = <<<ENDFORM
<form method="post" name="payment_form" id="payment_form" action="%s">
<input type="submit" name="submit_payment" value="{$lng['general']['submit']}" />
</form>
ENDFORM;


		$str =  sprintf($form, $this->payment_url."?code=".$this->result['code']);
		return $str;

	}

	function parseXML($data) {
	
	
		$this->parser=xml_parser_create("UTF-8");

		xml_set_object($this->parser, $this);
		xml_set_default_handler($this->parser,"_default"); 

		//Specify Handlers to start and ending tag
		xml_set_element_handler($this->parser, "start_element", "end_element");
		//Data Handler
		xml_set_character_data_handler($this->parser, "character_data");
		//match the exact case
		//xml_parser_set_option($this->parser, XML_OPTION_TARGET_ENCODING, $encoding);
		xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, 0);
		xml_parser_set_option($this->parser, XML_OPTION_SKIP_WHITE, 0);

		xml_parse ( $this->parser , $data);
		xml_parser_free($this->parser);
	
	}
	
	
	function start_element($parser, $element_name, $element_attrs)
	{
		$this->curr_element = $element_name;

	}

	function end_element($parser, $element_name)
	{
		
	}

	function character_data($parser,$data)
	{

		if(in_array($this->curr_element, $this->keys) ) {
		
			if(!isset($this->result[$this->curr_element]) || !$this->result[$this->curr_element]) // protection to overwrite the code
				$this->result[$this->curr_element] = $data;
		
		}
	
	}

	function process()
	{

		if(isset($_POST['notificationCode']) && $_POST['notificationCode']) $notification_code = $_POST['notificationCode'];
		if(isset($_POST['notificationType']) && $_POST['notificationType']) $notification_type = $_POST['notificationType'];

		if(_isCurl()) {

			$ch = curl_init();
		
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
			curl_setopt($ch, CURLOPT_URL, $this->notifications_url."/".$notification_code."?email=".$this->pay_settings["pagseguro_email"]."&token=".$this->pay_settings["pagseguro_token"]);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			$response = curl_exec($ch);

			 if($response === false)
			{
				$this->addError("Error Number: ".curl_errno($ch)." Error string: ".curl_error($ch));
				return;
			}

			curl_close($ch);

		} else
			{
				$this->addError("This feature requires CURL!");
				return;
			}
		
		$processed=0;
		$success = 0;

		//process $response xml
		$this->parseXML($response);
		
		$this->log($response);

		$this->user_key = $this->result['reference'];
		//$this->log("user key= ".$this->user_key);

		// 3 or 4 - successful transaction
		if(!$this->result['status'] || ($this->result['status']!=3 && $this->result['status']!=4)) {
		
			$this->log("The transaction was not finalized!");
			return 0;
		
		}
		
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

		if(!$this->dataCorrect())
		{
			$this->log("validateData: ERROR: Incorrect Data!");
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

		if(!isset($this->result['grossAmount']) || !$this->result['grossAmount'] || $this->result['grossAmount'] != $amount ) {
		
			$this->log("result['grossAmount'] != amount: ".$this->result['grossAmount']." != ".$amount);
			return 0;
		
		}
		
		return 1;
	}

	function saveToDB()
	{

		global $db;
		$addtosql = '';
		$entirepost = '';

		foreach($this->result as $key => $val) 
		{
			if(in_array($key, $this->keys)) 
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
		$file = $config_abs_path."/log/pagseguro_transaction";
		if(!$success) $file = $config_abs_path."/log/pagseguro_error";

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
		$file = $config_abs_path."/log/pagseguro_debug";

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

		if(!isset($_POST["pagseguro_email"])) $this->addError('Please fill in Pagseguro email!<br />');
		if(!isset($_POST["pagseguro_token"])) $this->addError('Please fill in Pagseguro token!<br />');
		if(!isset($_POST["pagseguro_item_name"])) $this->addError('Please fill in Pagseguro item name!<br />');
		if(!isset($_POST["pagseguro_item_description"])) $this->addError('Please fill in Pagseguro item description!<br />');

		if($this->getError()!='') {

			$array_fields = array( "pagseguro_email", "pagseguro_token", "pagseguro_item_name", "pagseguro_item_description" );

			foreach ($array_fields as $field) {
				if(isset($_POST[$field])) $this->tmp[$field] = cleanStr($_POST[$field]);
			}
			
			$this->tmp['pagseguro_test'] = checkbox_value('pagseguro_test');
			
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


		$array_fields = array( "pagseguro_email", "pagseguro_token", "pagseguro_item_name", "pagseguro_item_description" );

		$this->clean['pagseguro_test'] = checkbox_value('pagseguro_test');
		global $db;
		$sql = "update ".$this->table." set `pagseguro_test` = ".$this->clean['pagseguro_test'];
		
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
		if(!$array_settings['pagseguro_email'] || !$array_settings['pagseguro_token'] || !$array_settings['pagseguro_item_name'] || !$array_settings['pagseguro_item_description']) {
			$this->setError("Please configure Pagseguro information!");
			return 0;
		}
		return 1;
	}

}
?>
