<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

require_once "../../classes/payment_actions.php";
require_once "../../classes/payment_processors.php";
require_once "../../classes/priorities.php";
require_once "../../classes/invoices.php";

$path=dirname(__FILE__);
require_once($path.'/include.php');

$id = get_numeric_only("id");
$array = array("id","sid","key","ukey","entirepost");

global $lng;
$smarty = new Smarty;
$smarty = common($smarty);
$smarty->assign("lng",$lng);

$pa = new payment_actions();
$payment_array_temp = $pa->getPaymentDetails($id);

$payment_array = array();

$action = $db->fetchRow("select `action` from ".TABLE_PAYMENT_ACTIONS." where id='$id'");
$action_str=$pa->actionsStr($action);
$payment_array[$lng['general']['action']] = $action_str;

if(count($payment_array_temp)>1) {
foreach ($payment_array_temp as $key => $value) {

	if(!in_array($key,$array)) { $payment_array[$key] = $value; }

}
}

$inv = new invoices();
$invoice = $inv->getInvoiceForPA($id);

$smarty->assign("id",$id);
$smarty->assign("invoice",$invoice);
$smarty->assign("payment_array",$payment_array);
$smarty->display('info_order.html');
?>
