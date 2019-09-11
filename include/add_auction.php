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

$post = get_numeric("post", 0);
$ad_id = get_numeric("aid", 0);

if(!$post) {
$smarty = new Smarty;
$smarty->caching = 0;
$smarty = common($smarty);
$smarty->assign("lng",$lng);
}
else my_session_start();

if($post) {

	$ret = array("response" => 0, "error" => array(), "info" => null);

	if(empty($_POST['starting_price']) || !is_numeric($_POST['starting_price'])) {
		array_push($ret['error'], array("field"=> 'starting_price', "error" => $lng['useraccount']['error']['auction_start_price']));

		global $appearance_settings;
		if(strtolower($appearance_settings['charset'])!="utf-8") $ret = utf8_encode_all($ret);

		echo json_encode($ret);
		exit(0);
	}

	require_once "../classes/auctions.php";

	$starting_price = escape($_POST['starting_price']);
	$currency = escape($_POST['currency']);

	$ac = new auctions;
	$ac->add($ad_id, $starting_price, $currency);

	$ret['response'] = 1;
	$ret['info'] = $lng['useraccount']['info']['auction_added'];

	global $appearance_settings;
	if(strtolower($appearance_settings['charset'])!="utf-8") $ret = utf8_encode_all($ret);

	echo json_encode($ret);

}
else
{

	global $config_abs_path;
	require_once $config_abs_path."/classes/listings.php";
	$l = new listings;
	$listing_price = $l->getPrice($ad_id);
	if($listing_price<=0) $listing_price='';
	$smarty->assign("listing_price",$listing_price);
	$smarty->assign("ad_id",$ad_id);

	$smarty->display('add_auction.html');
	close();
}

?>
