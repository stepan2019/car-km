<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class manual_payment {

	var $post;
	var $user_key;
	var $pending;
	var $invoice_no;
	var $pay_settings;
	var $formTitle;
	var $error;

	public function __construct()
	{
		global $db;
		global $config_table_prefix;
		$arr = $db->fetchAssoc("select `processor_ret_table` from ".TABLE_PAYMENT_PROCESSORS." where `processor_code` like 'manual'");
		$this->ret_table = $config_table_prefix.$arr['processor_ret_table'];

	}

	function setTable($table) {

		$this->table = $table;
	
	}

	function init() {

		$this->pending = 0;
		$this->user_key = isset($_GET['ukey'])?$_GET['ukey']:md5(uniqid(rand(), true));
		//$this->pay_settings = $this->getSettings();
		$this->pay_settings['pending'] = 1;

	}

	function getUserKey() {

		return $this->user_key;

	}

	function getPost() {

		return '';

	}

	function setDebug($val) {

		$this->debug = $val;

	}

	function setInvoiceNo($val) {

		$this->invoice_no = $val;

	}

	function setFormTitle($val) {

		$this->formTitle = $val;

	}

	function setAmount($str) {

		$this->post['amount'] = $str;
		$this->amount = $str;

	}

	function setCurrency($str) {

		$this->post['currency_code'] = $str;

	}
	// recurring payments
	function setSubscription($total, $days) {

		return 1;

	}

	function setFirstSubscription($amount, $days) {


	}
	function getPending() {

		return $this->pending;

	}

	function getRetTable() {

		return $this->ret_table;

	}

	function getSettings() {

		global $db;
		$result = array();
		return $result;

	}

	function getForm()
	{
		$form = <<<ENDFORM
<form method="post" name="payment_form" id="payment_form" action="%s/payment_return/manual_payment.php?ukey=%s">
<input type="submit" name="submit_payment" value="%s">
</form>
ENDFORM;
		global $config_live_site;
		$str =  sprintf($form, $config_live_site, $this->user_key, $this->formTitle);
		return $str;

	}

	function dataCorrect()
	{
		if(!isset($_GET['ukey']) || strlen($_GET['ukey']) == 0) return 0;
		global $db;
		$res_ad = $db->query("select * from ".TABLE_PAYMENT_ACTIONS." where ukey='".$this->user_key."'");
		if(!$db->numRows($res_ad)) return 0;
		return 1;
	}

	function process()
	{
		global $db;
		if($this->dataCorrect()) { 
			// mark the transaction completed only if not pending
			if($this->pay_settings['pending']==0) {

				$res_upd = $db->query("update ".TABLE_PAYMENT_ACTIONS." SET completed='1' where ukey='".$this->user_key."'");
//				return 1;
			}

			$timestamp = date("Y-m-d H:i:s");
			$res = $db->query("INSERT INTO ".$this->ret_table." SET date='$timestamp', ukey='".$this->user_key."'");

			return 1;
		}
		return 0;
	}

	function correctSettings() {
		return true;
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

}
?>
