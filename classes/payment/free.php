<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class free {

	var $post;
	var $user_key;
	var $pending;
	var $invoice_no;
	var $pay_settings;
	var $formTitle;
	var $name;
	var $error;

	public function __construct()
	{
	}

	function setTable($table) { }
	function setDebug($debug) { }
	function setItemName($item) { }


	function init($key = '') { 

		if($key) $this->user_key = $key;
		else $this->user_key = isset($_GET['ukey'])?$_GET['ukey']:md5(uniqid(rand(), true));
		$this->pending = 0;

	}

	function setFormTitle($val) {

		$this->formTitle = $val;

	}

	function getUserKey() {

		return $this->user_key;

	}

	function getPost() {

		return '';

	}

	function getSettings() {

		return array();

	}

	function setAmount($str) {

		$this->amount = 0;

	}

	function setCurrency($str) {

		$this->post['currency_code'] = $str;

	}

	function getPending() {

		
		return $this->pending;

	}

	function setInvoiceNo($val) {

		$this->invoice_no = $val;

	}

	function getForm()
	{
		$form = <<<ENDFORM
<form method="post" name="payment_form" id="payment_form" action="%s/payment_return/free.php?ukey=%s">
<input type="submit" name="submit_payment" value="%s">
</form>
ENDFORM;
		global $config_live_site;
		$str =  sprintf($form, $config_live_site, $this->user_key, $this->formTitle);
		return $str;
	}

	function process()
	{
		// mark the transaction completed
		global $db;
		$res_upd = $db->query("update ".TABLE_PAYMENT_ACTIONS." SET completed='1' where ukey='".$this->user_key."'");

		return 1;
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
