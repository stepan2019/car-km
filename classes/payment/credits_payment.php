<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class credits_payment {

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
		$arr = $db->fetchAssoc("select `processor_table`, `processor_ret_table` from ".TABLE_PAYMENT_PROCESSORS." where `processor_code` like 'credits'");
		$this->table = $config_table_prefix.$arr['processor_table'];
		$this->ret_table = $config_table_prefix."credits_return";

	}

	function setTable($table) {

		$this->table = $table;
	
	}

	function init() {

		$this->pending = 0;
		$this->user_key = isset($_GET['ukey'])?$_GET['ukey']:md5(uniqid(rand(), true));
		$this->pay_settings = $this->getSettings();
		$this->pay_settings['pending'] = 0;

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

	function getPending() {

		return $this->pending;

	}

	function getRetTable() {

		return $this->ret_table;

	}

	function getSettings() {

		global $db;
		$result = $db->fetchAssoc("select * from ".$this->table);
		$result['unit_curr'] = add_currency(format_price($result['unit']));
		$result['groups_array'] = explode(",", $result['groups']);
		
		return $result;

	}

	function getForm()
	{
		$form = <<<ENDFORM
<form method="post" name="payment_form" id="payment_form" action="%s/payment_return/credits.php?ukey=%s">
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

// check again if there are enough credits for the payment !!!!!!!!

		return 1;
	}

	function process()
	{
		global $db;
		if($this->dataCorrect()) { 
			// mark the transaction completed
			global $db;
			$res_upd = $db->query("update ".TABLE_PAYMENT_ACTIONS." SET completed='1' where ukey='".$this->user_key."'");

			$timestamp = date("Y-m-d H:i:s");
			$res = $db->query("INSERT INTO ".$this->ret_table." SET date='$timestamp', ukey='".$this->user_key."'");

			// substract credits
			$arr = $db->fetchAssoc("select `amount`, `user_id` from ".TABLE_PAYMENT_ACTIONS." where ukey='".$this->user_key."'");
			$no_credits = $arr['amount']/$this->pay_settings['unit'];
			$no_credits = format_price($no_credits, '', '', '.');
			$this->substractForUser($arr['user_id'], $no_credits);

			return 1;
		}
		return 0;
	}

	function correctSettings() {
		return true;
	}

	function substractForUser($user_id, $no_credits) {

		if(!$no_credits) return;
		global $db;
		$db->query("update ".TABLE_USERS." set `no_credits` = `no_credits`-$no_credits where id = '$user_id'");

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


	function check_form () {

		global $lng;
		$this->error='';
		$this->tmp=array();

		global $config_demo;
		if($config_demo==1) $this->addError($lng['general']['errors']['demo'].'<br />');

		if(!isset($_POST['unit']) || $_POST['unit']==0)
			$this->addError($lng['credits']['errors']['Invalid credits unit!']);

		if($this->getError()!='')
		{
			// fields with default 0 value
			$array_inputs_zero = array('unit');

			foreach ($array_inputs_zero as $field) {

				if(isset($_POST[$field])) $this->tmp[$field]=$_POST[$field]; else $this->tmp[$field]=0;

			}
		}

		return 1;
	}

	function saveSettings() {

		global $db;
		$this->clean=array();
		$this->check_form();
		if($this->getError()!='') return 0;

		$str_update ='';

		// fields with default 0 value
		$array_inputs_zero = array('unit');

		foreach ($array_inputs_zero as $field) {

			if(isset($_POST[$field]) && $_POST[$field]) $this->clean[$field]=escape($_POST[$field]); else $this->clean[$field]=0;
			$str_update.=" `$field` = '".$this->clean[$field]."',";

		}

		if(isset($_POST['choose_group']) && $_POST['choose_group']=="choose")
		{
			$this->clean['groups']='';
			$i=0;
			while (isset($_POST['groups'][$i])){
				$group=escape($_POST['groups'][$i]);
				if($i) $this->clean['groups'].=',';
				$this->clean['groups'].=$group;
				$i++;
			}
		} else $this->clean['groups']='0';

		$str_update.=" `groups` = '".$this->clean['groups']."'";

		$res=$db->query("update ".$this->table." set ".$str_update);

		return 1;

	}

		function setSubscription($total, $days) {

		}
	
}
?>
