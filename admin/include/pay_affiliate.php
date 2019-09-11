<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

$path=dirname(__FILE__);
require_once($path.'/include.php');
global $config_abs_path;
require_once $config_abs_path.'/classes/affiliates.php';
require_once $config_abs_path."/classes/payment.php";
require_once $config_abs_path."/classes/payment_processors.php";

$id = get_numeric_only("id");

if(!$id) exit(0);

global $lng, $db;
$smarty = new Smarty;
$smarty = common($smarty);
$smarty->assign("lng",$lng);

$aff = new affiliates;
$payment_info = $aff->getPayment($id);

// mark as paid
$aff->markPaymentPaid($id);

require_once($config_abs_path."/classes/payment/{$payment_info['processor'] }.php");
$payment = new payment( $payment_info['processor'] );
$payment->setRecipient($payment_info['paid_to']);
$payment->disableReturn();
$payment->setNolog(1);
$payment->setAmount($payment_info['amount'] );
$payment_form = $payment->writeForm();
$smarty->assign("payment_form", $payment_form);

$smarty->display('pay_affiliate.html');
$db->close();
?>
