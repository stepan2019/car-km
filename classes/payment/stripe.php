<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

global $config_abs_path;
require_once $config_abs_path."/classes/payment/stripe/init.php";

class stripe {

	var $postback_url;
	var $post;
	var $user_key;
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
		$arr = $db->fetchAssoc("select `processor_table`, `processor_ret_table` from ".TABLE_PAYMENT_PROCESSORS." where `processor_code` like 'stripe'");

		$this->table = $config_table_prefix.$arr['processor_table'];
		$this->ret_table = $config_table_prefix.$arr['processor_ret_table'];
		$this->subscrAutoRenew = 0;
		$this->subscrId = 0;
		$this->name = "stripe";

		//$this->table = $this->getTable();

	}

	function getTable() {
		
		global $db;
		global $config_table_prefix;
		$table = $config_table_prefix.$db->fetchRow("select `processor_table` from ".TABLE_PAYMENT_PROCESSORS." where `processor_code` like 'stripe'");
		return $table;
	}

	function setTable($table) {

		$this->table = $table;
	
	}

	function init($key = '') {

		global $config_live_site;
		$this->pending = 0;
		$this->pay_settings = $this->getSettings();

		\Stripe\Stripe::setApiKey($this->pay_settings['secret_key']);
		
		
		if($key) $this->user_key = $key;
		else $this->user_key = isset($_GET['ukey'])?$_GET['ukey']:md5(uniqid(rand(), true));
		$this->postback_url = $config_live_site."/payment_return/stripe.php?mode=process&ukey=".$this->user_key;
		$this->post['item_name'] = $this->pay_settings["item_name"];

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

	// recurring payments
	function setSubscription($total, $days) {

		$total = number_format($total, 2, '.', '');

		$this->post['cmd'] = '_xclick-subscriptions';
		$this->post['a3'] = $total;
		
		if($days>90) { 
		    $this->post['p3'] = round($days/30);
		    $this->post['t3'] = "M";
		} else
		{
		    $this->post['p3'] = $days;
		    $this->post['t3'] = "D";
		}

		
		$this->post['src'] = "1"; // Recurring payments. If set to 1, the payment will recur unless your customer cancels the subscription before the end of the billing cycle
		$this->post['sra'] = "1";//Reattempt on failure. If set to 1, and the payment fails, the payment will be reattempted two more times. After the third failure, the subscription will be cancelled

		return 1;

	}

	function setFirstSubscription($amount, $days) {

		$amount = number_format($amount, 2, '.', '');

		$this->post['a1'] = $amount;
		if($days>90) { 
		    $this->post['p1'] = round($days/30);
		    $this->post['t1'] = "M";
		} else
		{
		    $this->post['p1'] = $days;
		    $this->post['t1'] = "D";
		}

	}

	function setInvoiceNo($val) {

		$this->invoice_no = $val;

	}

	function disableReturn() {

		$this->post['return'] = '';
		$this->post['cancel_return'] = '';
		$this->post['notify_url'] = '';

	}

	function setAmount($str) {

		$amount = $str*100;
		$amount = number_format($amount, 0, '', '');
		$this->post['amount'] = $amount;
		$this->amount = $amount;

	}

	function setCurrency($str) {

		//$this->post['currency_code'] = $str;

	}

	function setItemName($str) {

		$this->post['item_name'] = $str;

	}

	function setDemo($value) {

		$this->stripe_url = 'https://api.stripe.com';
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
		global $lng;
		$form = <<<ENDFORM
<form action="%s" method="post">
	<script src="https://checkout.stripe.com/checkout.js" class="stripe-button"
          data-key="%s"
          data-description="%s"
          data-amount="%s"
          data-locale="auto">
	</script>
</form>
ENDFORM;
		$str =  sprintf($form, $this->postback_url, $this->pay_settings["publishable_key"], $this->pay_settings["item_name"], $this->amount);



//https://gist.github.com/boucher/1750375
		$form = <<<ENDFORM
<form action="%s" method="post">
<script type="text/javascript">
var attr = {"class":"stripe-button", "data-key":"%s", "data-description":"%s", "data-amount":"%s", "data-locale":"auto"};
loadExtScript("https://checkout.stripe.com/checkout.js", function(){
    $( ".stripe-button-el" ).trigger("click");
    //$("#payment_form").submit();
     
}, attr);

$(document).ready(function() { 
//$("#payment-form").submit(); 
});
 
</script>
</form>
ENDFORM;

$str =  sprintf($form, $this->postback_url, $this->pay_settings["publishable_key"], $this->pay_settings["item_name"], $this->amount);

/*
//https://gist.github.com/ziadoz/5101836
		$form = <<<ENDFORM
<form action="%s" method="post">
<input 
            type="submit" 
            value="Pay with Card"
          data-key="%s"
          data-description="%s"
          data-amount="%s"
          data-locale="auto"/>

<script src="https://checkout.stripe.com/v2/checkout.js"></script>
<script>
        $(document).ready(function() {
			Stripe.setPublishableKey('%s');
            //$(':submit').on('click', function(event) {
                //event.preventDefault();
                var button = $("#Submit"),
                    form = $("#payment_form");
                var opts = $.extend({}, button.data(), {
                    token: function(result) {
                        form.append($('<input>').attr({ type: 'hidden', name: 'stripeToken', value: result.id })).submit();
                    }
                });
                StripeCheckout.open(opts);
            //});
        });
</script>
</form>
ENDFORM;


		$str =  sprintf($form, $this->postback_url, $this->pay_settings["publishable_key"], $this->pay_settings["item_name"], $this->amount, $this->pay_settings["publishable_key"]);
*/
		return $str;

	}

	function processError($e) {
	
		$body = $e->getJsonBody();
		$err  = $body['error'];

		$error_str = 'ERROR - Type:' . $err['type'] .' Message:' . $err['message'] . "\n";
		$this->log($error_str);
		$this->addError($err['message']);
  
		return;

	}
	
	function process()
	{

		global $db;
		$amount = $db->fetchRow("select `amount` from ".TABLE_PAYMENT_ACTIONS." where ukey='{$this->ukey}'");
		$this->setAmount($amount);

		if (!isset($_POST['stripeToken'])) {
			$this->addError("The Stripe Token was not generated correctly");
			return;
		}

		$token  = $_POST['stripeToken'];
		$email  = $_POST['stripeEmail'];

		try {

			$customer = \Stripe\Customer::create(array(
				'email' => $email,
				'source'  => $token
			));
  
		}
		catch(\Stripe\Error\Card $e) {
			// Since it's a decline, \Stripe\Error\Card will be caught
			$this->processError($e);
			
		} catch (\Stripe\Error\RateLimit $e) {
			// Too many requests made to the API too quickly
			$this->processError($e);

		} catch (\Stripe\Error\InvalidRequest $e) {
			// Invalid parameters were supplied to Stripe's API
			$this->processError($e);
		} catch (\Stripe\Error\Authentication $e) {
			// Authentication with Stripe's API failed
			// (maybe you changed API keys recently)
			$this->processError($e);
		} catch (\Stripe\Error\ApiConnection $e) {
			// Network communication with Stripe failed
			$this->processError($e);
		} catch (\Stripe\Error\Base $e) {
			// Display a very generic error to the user, and maybe send
			// yourself an email
			$this->processError($e);
		} catch (Exception $e) {
			// Something else happened, completely unrelated to Stripe
			$this->processError($e);
		}

		// store customer id if a subscription!!!!!!!!!
		// subscribe it to the plan
		/*
		\Stripe\Subscription::create(array(
		"customer" => "cus_4fdAW5ftNQow1a",
		"items" => array(
			array(
			"plan" => "basic-monthly",
			),
		),
		));
		*/

		try {
		
			$charge = \Stripe\Charge::create(array(
				'customer' => $customer->id,
				'amount'   => $this->amount,
				'currency' => strtolower($this->pay_settings['currency'])
			));
			$success = $this->saveToDB($charge);
			if(!$success) $this->log("saveToDB: ERROR: Cannot save to db!");
			
			$processed=1;
			
			$this->logIt($success);

			return $processed;
			
		}
		catch(\Stripe\Error\Card $e) {
			// Since it's a decline, \Stripe\Error\Card will be caught
			$this->processError($e);
			
		} catch (\Stripe\Error\RateLimit $e) {
			// Too many requests made to the API too quickly
			$this->processError($e);

		} catch (\Stripe\Error\InvalidRequest $e) {
			// Invalid parameters were supplied to Stripe's API
			$this->processError($e);
		} catch (\Stripe\Error\Authentication $e) {
			// Authentication with Stripe's API failed
			// (maybe you changed API keys recently)
			$this->processError($e);
		} catch (\Stripe\Error\ApiConnection $e) {
			// Network communication with Stripe failed
			$this->processError($e);
		} catch (\Stripe\Error\Base $e) {
			// Display a very generic error to the user, and maybe send
			// yourself an email
			$this->processError($e);
		} catch (Exception $e) {
			// Something else happened, completely unrelated to Stripe
			$this->processError($e);
		}

		$processed=0;

		return $processed;
	}

	function saveToDB($charge)
	{

		global $db;

		$addtosql = "`charge_id`='{$charge->id}', `amount`='{$charge->amount}', `currency`='{$charge->currency}', `customer`='{$charge->customer}', `livemode`='{$charge->livemode}', `paid`='{$charge->paid}' "; 

		$res_upd = $db->query("update ".TABLE_PAYMENT_ACTIONS." SET completed='1' where ukey='".$this->user_key."'");

		// set subscr_payment, subscr_signup and subscr_id fields
		// if signup subscription
/*if($_POST['txn_type'] == "subscr_signup") {

			$addtosql .= "payment_status='Completed',";
			$entirepost .= "[payment_status]=\'Completed\',";

			$res_upd = $db->query("update ".TABLE_PAYMENT_ACTIONS." SET `subscr_signup` = 1 where ukey='".$this->user_key."'");

			if($this->subscrId) $res_upd = $db->query("update ".TABLE_PAYMENT_ACTIONS." SET `subscr_id`='".$this->subscrId."' where ukey='".$this->user_key."'");
			
		}
*/
		$timestamp = date("Y-m-d H:i:s");
		$res = $db->query("INSERT INTO ".$this->ret_table." SET date='$timestamp', ".$addtosql.",ukey='".$this->user_key."'");

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
		$file = $config_abs_path."/log/stripe_transaction";
		if(!$success) $file = $config_abs_path."/log/stripe_error";

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
		$file = $config_abs_path."/log/stripe_debug";

		$handle = fopen($file, "a");
		fwrite($handle, $content);
		fclose($handle);
	}

	function getError() {

		return $this->error;

	}

	function addError($str) {

		$this->error.=	$str."<br/>";

	}

	function setError($str) {

		$this->error=$str;

	}

	function getTmp() {
	
		return $this->tmp;

	}

	function check_form() {

		global $lng;

		if(!isset($_POST["secret_key"]) || !$_POST["secret_key"]) $this->addError($lng['settings']['errors']['required_secret_key'].'<br />');
		
		if(!isset($_POST["publishable_key"]) || !$_POST["publishable_key"]) $this->addError($lng['settings']['errors']['required_publishable_key'].'<br />');

		if($this->getError()!='') {

			$array_fields = array( "secret_key", "publishable_key", "item_name" );

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

		$this->clean['secret_key'] = escape($_POST['secret_key']);
		$this->clean['publishable_key'] = escape($_POST['publishable_key']);
		if(isset($_POST['item_name']) && $_POST['item_name']) $this->clean['item_name'] = escape($_POST['item_name']);
		else $this->clean['item_name'] = '';
		$this->clean['currency'] = escape($_POST['currency']);
		
		$sql = "update ".$this->table." set secret_key = '".$this->clean['secret_key']."', `publishable_key` =  '".$this->clean['publishable_key']."', `item_name` =  '".$this->clean['item_name']."', `currency` =  '".$this->clean['currency']."'";

		$db->query($sql);
		return 1;
	}

	function correctSettings() {
		
		global $lng;
		$array_settings = $this->getSettings();
		if(!$array_settings['secret_key'] || !$array_settings['publishable_key']) {
			$this->setError($lng['settings']['errors']['stripe_settings_missing']);
			return 0;
		}
		return 1;
	}

}
?>
