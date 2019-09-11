<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/
require_once "../include/include.php";
global $config_abs_path;
require_once $config_abs_path."/libs/dompdf/autoload.inc.php";
require_once $config_abs_path."/classes/settings.php";
require_once $config_abs_path."/classes/invoices.php";
require_once $config_abs_path."/classes/users.php";
require_once $config_abs_path."/classes/payment_actions.php";


$smarty = new Smarty;
$smarty = common($smarty);
global $lng;
$smarty->assign("lng", $lng);

$id = get_numeric_only("id");

// reference the Dompdf namespace
use Dompdf\Dompdf;
use Dompdf\Options;

// instantiate and use the dompdf class

$options = new Options();
$options->set('defaultFont', 'Courier');
$dompdf = new Dompdf($options);

$settings=new settings; 
$invoice_settings=settings::getInvoiceSettings();
	
$inv = new invoices();	
$invoice = 	$inv->getInvoice($id);
$usr = new users();
$user_details = $usr->getUserInfo($invoice['user_id']);

$pa = new payment_actions();

$action = $pa->getSerializedAction($invoice['payment_action']);
$action = unserialize($action);

//$payment_details = $inv->makePaymentDetails($action);
//_print_r($payment_details);
$subtotal = 0;
foreach($invoice['payment_details'] as $p) {

	$subtotal+=$p['amount'];

}

if($action['discount_code']) {
	$discount = $subtotal+$invoice['tax']-$invoice['amount'];
	$discount = add_currency($discount, $invoice['currency']);
} else $discount=0;

$smarty->assign("id", $id);
$smarty->assign("invoice_settings", $invoice_settings);
$smarty->assign("invoice", $invoice);
$smarty->assign("user_details", $user_details);
$smarty->assign("subtotal", $subtotal);
$smarty->assign("discount", $discount);

$html = $smarty->fetch("invoices/invoice.html");
//echo $html; exit(0);
$dompdf->loadHtml($html);

// (Optional) Setup the paper size and orientation
$dompdf->setPaper('A4', 'portrait');

// Render the HTML as PDF
$dompdf->render();

// Output the generated PDF to Browser
$dompdf->stream($invoice_settings['filename'].$id);

?>