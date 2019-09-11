<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class robokassa {

	var $postback_url;
	var $post;
	var $user_key;
	var $robokassa_keys;
	var $pending;
	var $invoice_no;
	var $pay_settings;
	var $table;
	var $error;
	var $tmp;
	var $formTitle;
	var $name;
	var $invId;

	public function __construct()
	{
		global $db;
		global $config_table_prefix;
		$arr = $db->fetchAssoc("select `processor_table`, `processor_ret_table` from ".TABLE_PAYMENT_PROCESSORS." where `processor_code` like 'robokassa'");

		$this->table = $config_table_prefix.$arr['processor_table'];
		$this->ret_table = $config_table_prefix.$arr['processor_ret_table'];
		$this->name = "robokassa";
		$this->invId = 0;

	}

	function getTable() {
		
		global $db;
		global $config_table_prefix;
		$table = $config_table_prefix.$db->fetchRow("select `processor_table` from ".TABLE_PAYMENT_PROCESSORS." where `processor_code` like 'robokassa'");
		return $table;
	}

	function setTable($table) {

		$this->table = $table;
	
	}

	function init($key = '') {

		global $config_live_site;
		$this->pending = 0;
		if($key) $this->user_key = $key;
		elseif(isset($_REQUEST['InvId']))  { // received Robokassa server response
			$this->setUserKey(escape($_REQUEST['InvId']));
		}
		else $this->user_key=md5(uniqid(rand(), true));
		//else $this->user_key = isset($_REQUEST['Shp_item'])?$_REQUEST['Shp_item']:md5(uniqid(rand(), true));
		$this->postback_url = $config_live_site."/payment_return/robokassa.php";

		$this->pay_settings = $this->getSettings();

		$this->post['MrchLogin'] = $this->pay_settings["login"];
		$this->post['Desc'] = $this->pay_settings["payment_desc"];
		$this->post['Culture'] = $this->pay_settings["language"];
		$this->post['Encoding'] = $this->pay_settings["encoding"];
		// ukey parameter does not work, seems to work only with Shp in front !!!
		//$this->post['Shp_item'] =$this->user_key;

		$this->setDemo($this->pay_settings["test"]);

		$this->robokassa_keys = array( "OutSum", "InvId", "SignatureValue" );

	}

	function getUserKey() {

		return $this->user_key;

	}

	function setUserKey($val) {

		global $db;
		$ukey = $db->fetchRow("select `ukey` from ".TABLE_PAYMENT_ACTIONS." where id='$val'");
		$this->user_key = $ukey;

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
		$this->post['InvId'] = $val;

		// calculate and set the signature
		//$this->post['SignatureValue'] = md5("{$this->post['MrchLogin']}:{$this->post['OutSum']}:{$this->post['InvId']}:{$this->pay_settings['password1']}:Shp_item={$this->post['Shp_item']}");

		$this->post['SignatureValue'] = md5("{$this->post['MrchLogin']}:{$this->post['OutSum']}:{$this->post['InvId']}:{$this->pay_settings['password1']}");

	}

	function setAmount($str) {

		$amount = number_format($str, 6, '.', '');
		$this->post['OutSum'] = $amount;
		$this->amount = $amount;

		// calculate and set the signature
		$inv_id = 0;

	}

	function setDemo($value) {

		if($value==1) $this->robokassa_url = 'http://test.robokassa.ru/Index.aspx';
		else $this->robokassa_url = 'https://auth.robokassa.ru/Merchant/Index.aspx';
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

		$str =  sprintf($form, $this->robokassa_url, $inputs);
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

			// send back OKnInvId e.g.  OK5
			global $db;
			//$db->query("select `id` from ".TABLE_PAYMENT_ACTIONS." where ukey='".$this->user_key."'");
			echo "OK".$_REQUEST['InvId']."\n";

		} 
		else 
			$processed=0;

		$this->logIt($success);

		return $processed;
	}


	//function dataCorrect()
	function validateData()
	{

		$this->invId = escape($_REQUEST['InvId']);

		global $db;
		$res_ad = $db->query("select `amount` from ".TABLE_PAYMENT_ACTIONS." where id='".$this->invId."'");

		if(!$db->numRows($res_ad)) return 0;
		$amount = $db->fetchRow();
		// format so there are not more than 2 decimals
		$amount = number_format($amount, 6, '.', '');

		// check the amount
		if(isset($_REQUEST['OutSum']) && (float)$_REQUEST['OutSum'] != (float)$amount) { 
			$this->log("(float)$ _REQUEST['OutSum']  != (float)$ amount: ".(float)$_REQUEST['OutSum'].' <> '.(float)$amount);
			return 0;
		}

		$crc = strtoupper($_REQUEST["SignatureValue"]);

		//$my_crc = strtoupper(md5("$amount:{$result['id']}:{$this->pay_settings['password2']}:Shp_item={$this->user_key}"));
		$my_crc = strtoupper(md5("$amount:{$this->invId}:{$this->pay_settings['password2']}"));

		if ($my_crc != $crc) { 
			$this->log("Invalid signature! Calculated signature $my_crc != received signature $crc");
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
			if(in_array($key, $this->robokassa_keys)) 
			{
				$addtosql .= $key."='".$val."',";
			}
			$entirepost .= "[".$key."]=\'".$val."\',";
		}
		$addtosql = rtrim($addtosql,','); 

		$res_upd = $db->query("update ".TABLE_PAYMENT_ACTIONS." SET completed='1' where id='".$this->invId."'");

		$timestamp = date("Y-m-d H:i:s");
		$res = $db->query("INSERT INTO ".$this->ret_table." SET date='$timestamp', entirepost='".$entirepost."', ".$addtosql.",ukey='{$this->user_key}'");

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
		$file = $config_abs_path."/log/robokassa_transaction";
		if(!$success) $file = $config_abs_path."/log/robokassa_error";

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
		$file = $config_abs_path."/log/robokassa_debug";

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

		if(!isset($_POST["login"]) || !$_POST["login"]) $this->addError('Please enter a login name!<br />');
		if(!isset($_POST["password1"]) || !$_POST["password1"]) $this->addError('Please enter password1!<br />');
		if(!isset($_POST["password2"]) || !$_POST["password2"]) $this->addError('Please enter password2!<br />');

		if($this->getError()!='') {

			if($_POST['test']=="on") $this->tmp['test'] = 1; else $this->tmp['test'] = 0;

			$array_fields = array( "login", "password1", "password2", "language", "encoding", "payment_desc" );

			foreach ($array_fields as $field) {
				if(isset($_POST[$field])) $this->tmp[$field] = clean($_POST[$field]);
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
		$this->clean['test'] = checkbox_value("test");	
		$sql = "update ".$this->table." set test = ".$this->clean['test'];

		$array_fields = array( "login", "password1", "password2", "language", "encoding", "payment_desc" );

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
		if(!$array_settings['login'] || !$array_settings['password1'] || !$array_settings['password1']) {
			$this->setError("Please configure Robokassa settings first!");
			return 0;
		}
		return 1;
	}

}
?>
