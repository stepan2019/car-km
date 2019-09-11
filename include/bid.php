<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

require_once "include.php";
require_once $config_abs_path."/libs/JSON.php";
require_once "../classes/auctions.php";

my_session_start();

$acid = get_numeric_only("acid");
$ad_id = get_numeric_only("aid");
$ret = array("response" => 0, "error" => array(), "info" => array(), "new_max_bid" =>0);
global $modules_array;

$ac = new auctions;

$bid=0;
if(empty($_POST['bid']))
	array_push($ret['error'], array("field"=> 'bid', "error" => $lng['useraccount']['error']['enter_bid']));
elseif (!is_numeric($_POST['bid'])) 
	array_push($ret['error'], array("field"=> 'bid', "error" => $lng['useraccount']['error']['incorrect_bid']));
elseif(in_array("multicurrency", $modules_array) && (!isset($_POST['bid_currency']) || !$_POST['bid_currency'])) {	
	array_push($ret['error'], array("field"=> 'bid_currency', "error" => "Please select a currency!"));
}
else{
	
	$sp = $ac->getStartingPrice($acid);
	$auction_currency = $ac->getCurrency($acid);
	
	$currency='';
	if(in_array("multicurrency", $modules_array)) {
	
	
		$mc = new multicurrency();
		$mc_default_currency = $mc->getDefault();
		$ratios = $mc->getRatios();
		$unit_starting_price = $sp*$ratios[$auction_currency];
		
		$currency = escape($_POST['bid_currency']);
		$unit_bid = escape($_POST['bid'])*$ratios[$currency];
		
		if( $unit_starting_price > $unit_bid) 
			array_push($ret['error'], array("field"=> 'bid', "error" => $lng['useraccount']['error']['bid_smaller_than_starting_price']));
		//echo $unit_starting_price.">".$unit_bid;
	}
	else {
		if( $sp > $_POST['bid']) 
			array_push($ret['error'], array("field"=> 'bid', "error" => $lng['useraccount']['error']['bid_smaller_than_starting_price']));
	}
}



if($ret['error']) {
 	$ret['response'] = 0;
 	
 	global $appearance_settings;
	if(strtolower($appearance_settings['charset'])!="utf-8") $ret = utf8_encode_all($ret);

	echo json_encode($ret);
	exit(0);
}

// correct
$bid = escape($_POST['bid']);
$message = '';
if(isset($_POST['bid_message']) && $_POST['bid_message']) $message = cleanStr($_POST['bid_message']);
$new_max_bid = $ac->addBid($acid, $ad_id, $bid, $message, $currency);
if($new_max_bid>0) $ret['new_max_bid'] = add_currency($new_max_bid, $auction_currency);

$ret['info'] = $lng['useraccount']['bid_placed'];
$ret['response'] = 1;

global $appearance_settings;
if(strtolower($appearance_settings['charset'])!="utf-8") $ret = utf8_encode_all($ret);

echo json_encode($ret);


?>