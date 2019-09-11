<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class auctions {

	var $id;
	var $clean;
	var $array;
	var $tmp;

	public function __construct()
	{
	
	}

	function getId() {

		return $this->id;

	}

	function delete($id) {

		global $db;
		$ad_id = $this->getAdId($id);
		$res_del=$db->query('delete from '.TABLE_AUCTIONS.' where `id`="'.$id.'"');
		$db->query("delete from ".TABLE_BIDS." where `aid` = '$id'");
		listings::removeAuction($ad_id);
		return 1;

	}

	function deleteForAd($ad_id) {

		global $db;
		$del=$db->fetchRow('select id from '.TABLE_AUCTIONS.' where `ad_id`="'.$ad_id.'"');
		if($del) $this->delete($del);

	}

	function Enable($id) {

		global $db;
		$res_del=$db->query('update '.TABLE_AUCTIONS.' set `active` = 1 where `id`="'.$id.'"');
		$ad_id = $this->getAdId($id);
		listings::addAuction($ad_id);
		return 1;
	}

	function Disable($id) {

		global $db;
		$ad_id = $this->getAdId($id);
		listings::removeAuction($ad_id);
		$res_del=$db->query('update '.TABLE_AUCTIONS.' set `active` = 0 where `id`="'.$id.'"');
		return 1;
	}

	function getAuction($id='') {
		
		if(!$id) $id=$this->id;
		global $db;
		$result=$db->fetchAssoc('select * from '.TABLE_AUCTIONS.' where `id`="'.$id.'"');
		return $result;

	}
	
	function getStartingPrice($id) {

		global $db;
		$result=$db->fetchRow('select `starting_price` from '.TABLE_AUCTIONS.' where `id`="'.$id.'"');
		return $result;

	}

	function getAuctionForAd($ad_id) {
		
		global $db, $appearance_settings;

		$date_format=$appearance_settings["date_format"];

		$result=$db->fetchAssoc("select *, date_format(`date`,'$date_format') as date_nice from ".TABLE_AUCTIONS." where `ad_id`='$ad_id' limit 1");

		if(!$result) return null;

		$result['starting_price_nc'] = $result['starting_price'];
		$result['starting_price'] = add_currency(format_price_field ($result['starting_price']), $result['currency']);
		$result['max_bid_nice'] = add_currency(format_price_field ($result['max_bid']), $result['currency']);
		$result['no_bids'] = $this->noBids($result['id']);

		return $result;

	}

	function adHasAuction($ad_id) {
		
		global $db, $appearance_settings;

		$exists=$db->fetchRow("select `id` from ".TABLE_AUCTIONS." where `ad_id`='$ad_id'");

		return $exists;

	}

	function getAdId($id) {
		
		global $db;
		$result=$db->fetchRow('select `ad_id` from '.TABLE_AUCTIONS.' where `id`="'.$id.'"');
		return $result;

	}

	function count() {
	
		global $db;
		$no=$db->fetchRow('select count(*) from '.TABLE_AUCTIONS);
		return $no;

	}

	function noBids($id) {
	
		global $db;
		$no=$db->fetchRow("select count(*) from ".TABLE_BIDS." where aid='$id'");
		return $no;

	}

	function getAll() {

		global $db;
		$result=$db->fetchAssocList('select * from '.TABLE_AUCTIONS.' order by id desc');
		return $result;
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

	function setInfo($str){

		$this->info = $str;

	}

	function getInfo() {

		return $this->info;

	}

	function add($ad_id, $starting_price, $currency) {
	
		global $db;

		// delete other auctions for the same ad
		$this->deleteForAd($ad_id);
		listings::addAuction($ad_id);

		$timestamp = date("Y-m-d H:i:s");
		
		global $modules_array;
		$str="";	
		if(in_array("multicurrency", $modules_array)) {
				
			$mc = new multicurrency();
			$mc_default_currency = $mc->getDefault();
			$ratios = $mc->getRatios();
			$unit_price = $starting_price*$ratios[$currency];
			$str=", `unit_starting_price`='$unit_price'";

		}
		
		$res = $db->query("INSERT INTO ".TABLE_AUCTIONS." SET date='$timestamp', `ad_id`='$ad_id', `starting_price`='$starting_price', `currency`='$currency', max_bid='0', active='1' ".$str);

		return 1;

	}

	function update($auction_id, $starting_price, $currency) {
	
		global $db;

		global $modules_array;
		$str="";	
		if(in_array("multicurrency", $modules_array)) {
				
			$mc = new multicurrency();
			$mc_default_currency = $mc->getDefault();
			$ratios = $mc->getRatios();
			$unit_price = $starting_price*$ratios[$currency];
			$str=", `unit_starting_price`='$unit_price'";

		}
		
		$res = $db->query("update ".TABLE_AUCTIONS." SET `starting_price`='$starting_price', `currency`='$currency' ".$str." where id='$auction_id'");

		return 1;

	}

	function checkMaxBid($aid, $bid, $unit_bid='', $currency='') {

		global $db;

		global $modules_array;

		$compare_with = $bid;
		
		$crt_auction = $this->getAuction($aid);
		$max_so_far = $crt_auction['max_bid'];
		
		if(in_array("multicurrency", $modules_array)) { 

			$compare_with = $unit_bid;
			$auction_currency = $crt_auction;

			$mc = new multicurrency();
			$mc_default_currency = $mc->getDefault();
			$ratios = $mc->getRatios();
			
			$max_so_far = $crt_auction['max_bid']*$ratios[$crt_auction['currency']];
			
		}
		
		if( $compare_with>$max_so_far) { $this->setMaxBid($aid, $bid, $unit_bid, $currency);  return $bid; }
		return 0;

	}

	function setMaxBid($aid, $bid, $unit_bid='', $currency = '') {

		global $db;
		global $modules_array;
		$str="";
		
		// transform from unit_bid to auction currency
		if(in_array("multicurrency", $modules_array)) {
			$mc = new multicurrency();
			$ratios = $mc->getRatios();
			$auction_currency = $this->getCurrency($aid);

			$bid = $unit_bid/$ratios[$auction_currency];
		}
		
		$db->query("update ".TABLE_AUCTIONS." set max_bid = '$bid' $str where id=$aid");
		return 1;

	}

	function belongsToUser($id, $user_id) {

		global $db;
		$ad_id = $this->getAdId($id);
		$l = new listings;
		return $l->belongsToUser($ad_id,$user_id);

	}

	function addBid($aid, $ad_id, $bid, $message, $currency='') {

		global $db, $ads_settings, $settings;
		$timestamp = date("Y-m-d H:i:s");
		$auth = new auth;
		$crt_usr = $auth->crtUserId();
		
				
		global $modules_array;
		$str="";	
		$unit_bid='';
		if(in_array("multicurrency", $modules_array)) {
				
			$mc = new multicurrency();
			$mc_default_currency = $mc->getDefault();
			$ratios = $mc->getRatios();
			$unit_bid = $bid*$ratios[$currency];
			$str=", `unit_bid`='$unit_bid', `currency`='$currency'";

		}

		
		$db->query("insert into ".TABLE_BIDS." set `aid`='$aid', `user_id`='$crt_usr', `bid`='$bid', `date` = '$timestamp', `message`='".escape($message)."'".$str);

		if($ads_settings['notify_when_new_bid']) {

			global $config_abs_path;
			require_once $config_abs_path."/classes/users.php";
			require_once $config_abs_path."/classes/mails.php";
			require_once $config_abs_path."/classes/mail_templates.php";
		
			$usr = new users;
			$to_details = $usr->getContactData(listings::getUser($ad_id));

			// send email
			$mail2send=new mails();
			$mail2send->init($to_details['email'], $to_details[$settings['contact_name_field']]);

			$ad_title = listings::getTitle($ad_id);
			$sender_name = cleanStr(users::getContactName($crt_usr));

			$array_subject = array("ad_id" => $ad_id);

			$array_message = array("ad_id" => $ad_id, "ad_title" => $ad_title, "message" => nl2br($message), "sender_name" => $sender_name, "contact_name" => cleanStr($to_details[$settings['contact_name_field']]));

			$sent = $mail2send->composeAndSend("new_auction_bid", $array_message, $array_subject);

		}

		// returns a positive value if it is the new max bid
		return $this->checkMaxBid($aid, $bid, $unit_bid, $currency);

	}

	function getBids($id, $currency='') {

		global $db, $appearance_settings;

		$date_format=$appearance_settings["date_format"];
		$bids = $db->fetchAssocList("select *, date_format(`date`,'$date_format') as date_nice from ".TABLE_BIDS." where aid=$id order by `date` desc");
		$i = 0;
		// get auction currency
		$auction_currency = $this->getCurrency($id);

		global $modules_array;
		$multicurrency = 0;
		if(in_array("multicurrency", $modules_array)) {
			$mc = new multicurrency();
			$mc_default_currency = $mc->getDefault();
			$ratios = $mc->getRatios();
			$multicurrency=1;
		}

		foreach($bids as $b) {
			if($multicurrency && $b['currency'] != $auction_currency) { 
					$bid = $b['unit_bid']/$ratios[$auction_currency];
					$bids[$i]['bid'] = add_currency(format_price_field($bid), $auction_currency);
			}
			else 
				$bids[$i]['bid'] = add_currency(format_price_field($b['bid']), $auction_currency);
				
			$bids[$i]['message'] = cleanStr($b['message']);
			$bids[$i]['message'] = str_replace("\r\n","<br />",$bids[$i]['message'] );
			$bids[$i]['message'] = str_replace("\n","<br />",$bids[$i]['message'] );
			$bids[$i]['contact'] = users::getContactName($b['user_id']);
			$i++;
		}

		return $bids;

	}

	function getCurrency($aid) {

		global $db;
		$currency = $db->fetchRow("select `currency` from ".TABLE_AUCTIONS." where `id`=$aid");
		return $currency;

	}

}
?>
