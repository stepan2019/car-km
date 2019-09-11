<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class listings {

	var $id;
	var $no_listings;
	var $use_index;
	var $where;
	var $where_array;
	
	public function __construct($id=0)
	{

		global $db;
		if($id) {
			$this->id=$id;
			$this->array=array();
			$this->array=$db->fetchAssoc("select * from ".TABLE_ADS." where `id`=".$id);
		}
	}

	function getId() {
		return $this->id;
	}

	function setNoListings($no) {

		$this->no_listings = $no;

	}

	function noListings() {

		return $this->no_listings;

	}

	function delete($id=0) {

		global $db;
		global $config_abs_path;
		if(!$id || !is_numeric($id)) return;
		$this->decCat($id);
		$res_del=$db->query("delete from ".TABLE_ADS." where `id`='$id'");
		$pics = new pictures();
		$pics->deletePictures($id);

		// action 
		require_once $config_abs_path."/classes/actions.php";
		actions::deleteListing($id);

		// delete discounts 
		require_once $config_abs_path."/classes/coupons.php";
		coupons::deleteListing($id);

		// delete from TABLE_OPTIONS
		$res_del=$db->query("delete from ".TABLE_OPTIONS." where `object_id`='$id' and `option`!='store'");
		global $settings;
		if($settings['nologin_enabled']) $db->query("delete from ".TABLE_ADS_EXTENSION." where `id`=$id");

		//delete from favorites
		$res_del=$db->query("delete from ".TABLE_FAVOURITES." where `ad_id`='$id'");

		// delete pending edited
		$res_del=$db->query("delete from ".TABLE_PENDING_EDITED." where `ad_id`='$id'");

		// delete auctions
		require_once $config_abs_path."/classes/auctions.php";
		$ac = new auctions;
		$ac->deleteForAd($id);

		$slug = new slugs();
		$slug->deleteListing($id);

		// add hook
		do_action("delete_listing", array($id));

	}

	function isExpired($id=0) {

		global $db;
		if(!$id) $id=$this->id;
		$timestamp = date("Y-m-d H:i:s");
		$res=$db->query("select count(*) from ".TABLE_ADS." where `id`='$id' and date_expires<'$timestamp' and date_expires!='0000-00-00 00:00:00' ");
		if($db->numRows($res)>0) return 1;
		return 0;
	}

	static function getUrlTitle($id) {
		
		$title = cleanHtml(listings::getTitle($id));
		return _urlencode($title);

	}

	static function getTitle($id, $multiple=0) {

		global $db;

		global $crt_lang;
		global $ads_settings;
		$title_var ="`title`";
		if($ads_settings['translate_title_description']) {

			global $languages;
			if(empty($languages)) $languages = common::getCachedObject("base_languages");
			if(count($languages)>1) {
				$title_var = "`title_$crt_lang`";
			}
		}

		$title=$db->fetchRow("select $title_var from ".TABLE_ADS." where `id`='$id'");

		if($multiple) {

			$arr['title'] = $title;
			return $arr;
		}

		return $title;

	}

	function Activate($id=0) {

		global $db;
		if(!$id) $id=$this->id;
		$this->incCat($id);
		do_action("activate_ad", array($id));

		$res=$db->query("update ".TABLE_ADS." set `active`=1, `user_approved`=1 where `id`='$id'");
		$pending = $db->query("select pending from ".TABLE_ADS." where id='$id'");
		if($pending) $this->ActivatePending($id);
		// if expired renew
 		if($this->isExpired($id)) $this->renew($id);
		

	}

	// used after payment return
	// checks for immediate alerts
	function activateListing($id=0) {

		global $db;
		if(!$id) $id=$this->id;

		$this->incCat($id);
		do_action("activate_ad2", array($id));

		$res=$db->query("update ".TABLE_ADS." set `active`=1, pending=0, `user_approved`=1 where `id`='$id'");

		// if expired renew
		if($this->isExpired($id)) $this->renew($id);

		$res_actions = $db->query("update ".TABLE_ACTIONS." set pending=0 where type like 'newad' or type like 'renewad' and object_id=$id");

		// check for mail alerts
		global $ads_settings;
		if($ads_settings['alerts_enabled']) {
			global $config_abs_path;
			require_once $config_abs_path."/classes/alerts.php";
			$alert = new alerts;
			$alert->checkImmediate($id, $this->getShortListing($id));
		}

		return 1;

	}

	function ActivatePending($id) {

		global $db;
		global $lng;

		global $config_abs_path;
		require_once $config_abs_path."/classes/mails.php";
		require_once $config_abs_path."/classes/mail_templates.php";

		// increment no ads for categories
		$this->incCat($id);

		do_action("activate_pending_ad", array($id));
		
		// make db changes
		$db->query("update ".TABLE_ADS." set active=1, pending=0, `user_approved` = 1 where id=".$id);
		$db->query("update ".TABLE_ACTIONS." set pending=0 where (type='newad' or type='renewad') and object_id = $id");

		// get user details
		global $config_abs_path;
		require_once $config_abs_path."/classes/users.php";
		$user_details = listings::getUserDetails($id);

		$mail2send=new mails();
		$mail2send->init($user_details['email'], $user_details['name']);

		$plan_name = listings::getPackageName($id);
		$details_link = listings::makeDetailsLink($id, $user_details['key']);
		$array_subject = array();

		$array_message = array("id"=>$id, "ad_id"=>$id, "username"=>$user_details['username'], "contact_name"=>$user_details['name'],
		"nologin"=> $user_details['nologin'], "admin_activated"=>1, "active"=>1, "status"=>$lng['general']['active'], 
		"plan_name"=>$plan_name, "details_link"=>$details_link);

		$mail2send->composeAndSend("ad_publish_status", $array_message, $array_subject);

		// check for mail alerts
		global $ads_settings;
		if($ads_settings['alerts_enabled']) {
			global $config_abs_path;
			require_once $config_abs_path."/classes/alerts.php";
			require_once $config_abs_path."/classes/fields.php";
			require_once $config_abs_path."/classes/depending_fields.php";
			require_once $config_abs_path."/classes/pictures.php";

			$alert = new alerts;
			$alert->checkImmediate($id, $this->getShortListing($id));
		}

	}

	function Deactivate($id=0) {

		global $db;
		if(!$id) $id=$this->id;
		$this->decCat($id);
		
		do_action("deactivate_ad", array($id));

		$res=$db->query("update ".TABLE_ADS." set `active`=0 where `id`='$id'");

	}

	function userApprove ($id) {

		global $db;
		$res=$db->query("update ".TABLE_ADS." set `user_approved`=1 where `id`='$id'");

	}

	function nologinApprove ($id) {

		global $db;
		global $settings;
		global $config_abs_path;
		if(!$id) $id=$this->id;
		$this->userApprove ($id);

		// if sms verification change the activation key
		if($settings['nologin_activate_via_sms']==1) {
			$activation=generate_random();
			$db->query("update ".TABLE_ADS_EXTENSION." set `activation`='$activation' where `id`='$id'");
		}

		// check if an upgrade needs to be pending
		require_once $config_abs_path."/classes/actions.php";
		$actions = actions::getPendingListingActions($id);
		$array_upgrades = array("featured", "highlited", "priority", "urgent", "video", "url");
		$array_ad = array("newad", "renewad");
		$upgrade = 0;
		$ad_pending = 0;
		foreach($actions as $act) {

			if(in_array($act['type'], $array_upgrades)) $upgrade = 1;
			if(in_array($act['type'], $array_ad)) $ad_pending = 1;

		}

		if($ad_pending || $upgrade) {
			$this->makePending($id);

			if($settings['send_mail_to_admin_when_pending']) {
			$result = $db->fetchAssoc("SELECT `".TABLE_ACTIONS."`.invoice, `".TABLE_PAYMENT_ACTIONS."`.processor from `".TABLE_ACTIONS."` left join `".TABLE_PAYMENT_ACTIONS."` on `".TABLE_PAYMENT_ACTIONS."`.id = `".TABLE_ACTIONS."`.`invoice` where (`object_id` = ".$id." and (`type` like 'newad' or `type` like 'renewad'))");
			$processor = $result['processor'];
			$invoice_no = $result['invoice'];

			require_once $config_abs_path."/classes/mails.php";
			require_once $config_abs_path."/classes/mail_templates.php";

			$mail2send=new mails();
			$mail2send->init();

			$details_link = listings::makeDetailsLink($id);

			$array_subject = array("processor" => $processor);

			$array_message = array("id"=>$id, "ad_id"=>$id, "contact_name"=>cleanStr($user_details['mgm_name']), "email"=>cleanStr($user_details['mgm_email']), "details_link"=>$details_link, "ad_pending" => $ad_pending, "upgrade" => $upgrade, "invoice_no"=>$invoice_no, "processor" => $processor);

			$mail2send->composeAndSend("admin_announce_pending", $array_message, $array_subject);
			}

		} else { // activate listing

			$this->ActivatePending($id);

		}

		
	}

	function markSold($id=0) {

		global $db;
		if(!$id) $id=$this->id;
		$res=$db->query("update ".TABLE_ADS." set `sold`=1 where `id`='$id'");

	}

	function markUnsold($id=0) {

		global $db;
		if(!$id) $id=$this->id;
		$res=$db->query("update ".TABLE_ADS." set `sold`=0 where `id`='$id'");

	}

	function markRented($id=0) {

		global $db;
		if(!$id) $id=$this->id;
		$res=$db->query("update ".TABLE_ADS." set `rented`=1 where `id`='$id'");

	}

	function markUnrented($id=0) {

		global $db;
		if(!$id) $id=$this->id;
		$res=$db->query("update ".TABLE_ADS." set `rented`=0 where `id`='$id'");

	}

	function addOption($id, $type, $noexp=0) {

		global $db;
		global $config_abs_path;
		require_once $config_abs_path."/classes/featured_plans.php";
		$db->query("delete from ".TABLE_OPTIONS." where `object_id` = $id and `option` like '$type'");

		$timestamp = date("Y-m-d H:i:s");

		if(!$noexp) {
		global $ads_settings;
		switch ($type) {

			case "featured":
				$featured_plan = $db->fetchRow("select `featured` from ".TABLE_ADS." where `id`='$id'");
				$days_expires = featured_plans::getDays($featured_plan);
				break;
			case "highlited":
				$days_expires = $ads_settings['highlited_expires'];
				break;
			case "priority":
				$days_expires = $ads_settings['priorities_expires'];
				break;
			case "urgent":
				$days_expires = $ads_settings['urgent_expires'];
				break;
			case "video":
				$days_expires = $ads_settings['video_expires'];
				break;
			case "url":
				$days_expires = $ads_settings['url_expires'];
				break;

		}

		if($days_expires) $str_expires = " `date_expires` = date_add('$timestamp', interval '$days_expires' day)";
		else $str_expires = " `date_expires` = null";
		}
		else // never expires
			$str_expires = " `date_expires` = null"; 
	

		$db->query("insert into ".TABLE_OPTIONS." set `object_id` = '$id', `option` = '$type', `date_added` = '$timestamp', $str_expires ");

		return 1;

	}

	function makeFeatured($id, $featured_plan) {

		global $db;
		$res=$db->query("update ".TABLE_ADS." set `featured`=$featured_plan where `id`='$id'");

		$this->addOption($id, 'featured');

		$res_actions = $db->query("update ".TABLE_ACTIONS." set pending=0 where type like 'featured' and object_id=$id");

	}

	function makeHighlited($id) {

		global $db;
		$res=$db->query("update ".TABLE_ADS." set `highlited`=1 where `id`='$id'");

		$this->addOption($id, 'highlited');

		$res_actions = $db->query("update ".TABLE_ACTIONS." set pending=0 where type like 'highlited' and object_id=$id");

	}

	function makeUrgent($id) {

		global $db;
		$res=$db->query("update ".TABLE_ADS." set `urgent`=1 where `id`='$id'");

		$this->addOption($id, 'urgent');

		$res_actions = $db->query("update ".TABLE_ACTIONS." set pending=0 where type like 'urgent' and object_id=$id");

	}

	function enablePriority($id, $pri) {

		global $db;
		if(!$id) $id=$this->id;
		$res=$db->query("update ".TABLE_ADS." set `priority`=$pri where `id`='$id'");

		$this->addOption($id, 'priority');

		$res_actions = $db->query("update ".TABLE_ACTIONS." set pending=0 where type like 'priority' and object_id=$id");

	}

	function enableVideo($id) {

		global $db;
		if(!$id) $id=$this->id;

		$this->addOption($id, 'video');

		$res_actions = $db->query("update ".TABLE_ACTIONS." set pending=0 where type like 'video' and object_id=$id");

	}

	function enableUrl($id) {

		global $db;
		if(!$id) $id=$this->id;

		$this->addOption($id, 'url');

		$res_actions = $db->query("update ".TABLE_ACTIONS." set pending=0 where type like 'url' and object_id=$id");

	}
	
	static function videoEnabled($id) {

		global $db;
		$no = $db->fetchRow("select count(`object_id`) from ".TABLE_OPTIONS." where `option` like 'video' and `object_id` = '$id'");
		return $no;

	}

	static function urlEnabled($id) {

		global $db;
		$no = $db->fetchRow("select count(`object_id`) from ".TABLE_OPTIONS." where `option` like 'url' and `object_id` = '$id'");
		return $no;

	}

	function renew($id, $is_admin=1) {

		global $db;
		if(!$id) return;

		$pkg_id = listings::getPackage($id);
		if($pkg_id) {
			global $config_abs_path;
			require_once $config_abs_path."/classes/packages.php";
			$no_days = packages::getNoDays($pkg_id);
		}
		else { 
			global $ads_settings;
			$no_days = $ads_settings['expires'];
		}

		$timestamp = date("Y-m-d H:i:s");
		if($no_days!=0) $sql_exp="`date_expires` = date_add('$timestamp', interval '$no_days' day)";
		else $sql_exp="`date_expires` = null";

		if($is_admin) {
			$this->incCat($id);
			$res=$db->query("update ".TABLE_ADS." set `active`=1, user_approved=1, date_added='$timestamp', $sql_exp where `id`='$id'");
		} else {
			$res=$db->query("update ".TABLE_ADS." set date_added='$timestamp', $sql_exp where `id`='$id'");
		}

		do_action("renew_ad", array($id));

		return 1;
	}

	function renewOptions($id) {
	
		global $db;
		$pkg_id = listings::getPackage($id);

		$pkg = new packages();
		$pkg_det = $pkg->getPackage($pkg_id);
		$featured = $pkg_det['featured'];
		$highlited = $pkg_det['highlited'];
		$priority = priorities::getOrderNo($pkg_det['priority']);
		if(!$priority) $priority=0;
		$urgent = $pkg_det['urgent'];
		$video = $pkg_det['video'];
		$url = $pkg_det['url'];

		$db->query("update ".TABLE_ADS." set `featured`='$featured', `highlited`='$highlited', `priority`='$priority', `urgent`='$urgent', `video`='$video', `url`='$url' where id='$id'");
	
	}
	
	function renewUser($id) {

		$this->renew($id, 0);

	}

	function makePending($id) {

		global $db;
		$res=$db->query("update ".TABLE_ADS." set pending=1, active=0 where `id`='$id'");

	}

	function isPending($id) {

		global $db;
		$pending = $db->fetchRow("select `pending` from ".TABLE_ADS." where id='$id'");
		return $pending;

	}

	static function getCategory($id) {

		global $db;
		$row=$db->fetchRow("select `category_id` from ".TABLE_ADS." where id = '$id'");
		if(!$row) return 0;
		return $row;

	}

	static function getPackage($id) {

		global $db;
		$row=$db->fetchRow("select package_id from ".TABLE_ADS." where id = '$id'");
		if(!$row) return 0;
		return $row;

	}

	static function getPackageName($id) {

		global $db;
		global $crt_lang;
		if(!$id) return 0;
		$row=$db->fetchRow("select `name` from ".TABLE_PACKAGES."_lang left join ".TABLE_ADS." on ".TABLE_ADS.".package_id = ".TABLE_PACKAGES."_lang.`id` where ".TABLE_ADS.".id = '$id' and `lang_id` = '$crt_lang'");
		if(!$row) return 0;
		return cleanStr($row);

	}

	static function getUserPackage($id) {

		global $db;
		if(!$id) return 0;
		$row=$db->fetchRow("select `usr_pkg` from ".TABLE_ADS." where id = '$id'");
		if(!$row) return 0;
		return $row;

	}

	static function getUser($id) {
		
		global $db;
		if(!$id) return null;
		$row=$db->fetchRow("select `user_id` from ".TABLE_ADS." where id = '$id'");
		if(!$row) return 0;
		return $row;

	}

	function getAdOptions($id) {

		global $db;

		$result = $db->fetchAssoc("select `featured`, `highlited`, `priority`, `urgent`, ".TABLE_PRIORITIES."_lang.name as `priority_name`, ".TABLE_FEATURED_PLANS.".no_days as `featured_days` from ".TABLE_ADS." 
		left join ".TABLE_PRIORITIES." on ".TABLE_ADS.".`priority` = ".TABLE_PRIORITIES.".`order_no`
		left join ".TABLE_FEATURED_PLANS." on ".TABLE_ADS.".`featured` = ".TABLE_FEATURED_PLANS.".`id` 
		left join ".TABLE_PRIORITIES."_lang on ".TABLE_PRIORITIES.".`id` = ".TABLE_PRIORITIES."_lang.`id` 
		where ".TABLE_ADS.".`id` = $id");

		$result['enabled_video'] = $db->fetchRow("select count(*) from ".TABLE_OPTIONS." where `option` like 'video' and `object_id` =$id");
		
		$result['enabled_url'] = $db->fetchRow("select count(*) from ".TABLE_OPTIONS." where `option` like 'url' and `object_id` =$id");
		
		$result_ext = $db->fetchAssocList("select * from ".TABLE_ACTIONS." where `pending`=1 and `object_id` = $id and (`type` like 'featured' or `type` like 'highlited' or `type` like 'priority' or `type` like 'video' or `type` like 'urgent' or `type` like 'url')");

		foreach ($result_ext as $action) {

			if($action['type'] == "featured") {
				$result['pending_featured'] = 1;
				$result['pending_featured_days'] = featured_plans::getDays($action['extra']);
			}
			if($action['type'] == "highlited") $result['pending_highlited'] = 1;
			if($action['type'] == "urgent") $result['pending_urgent'] = 1;
			if($action['type'] == "priority") { 
				$result['pending_priority'] = 1;
				$result['pending_priority_order'] = $action['extra'];
				$result['pending_priority_name'] = priorities::getNameByOrder($action['extra']);
			}
			if($action['type'] == "video") $result['pending_video'] = 1;
			if($action['type'] == "url") $result['pending_url'] = 1;

		}

		return $result;
	}

	function getOptionExpirationDate($type, $object_id) {
	
		global $db, $appearance_settings;
		$date_format=$appearance_settings["date_format"];
		$expiration_date = $db->fetchRow("select date_format(`date_expires`,'$date_format') as date_nice from ".TABLE_OPTIONS." where `option`='$type' and `object_id`='$object_id'");
		if(!$expiration_date) return 0;
		
		return $expiration_date;
	
	}
	
	function getNoActive($categ) {

		global $db, $settings;
		if($categ>0) $cat_str=" and category_id='$categ'"; else $cat_str="";

		$locations_str="";
		if($settings['enable_locations'])
			$locations_str = locations::makeQueryStr();

		$no=$db->fetchRow("select count(*) from ".TABLE_ADS." where active=1".$cat_str.$locations_str);
		return $no;
	}

	function getNoRecent($sql_where='') {

		global $db;
		global $ads_settings, $settings;

		$locations_str="";
		if($settings['enable_locations'])
			$locations_str = locations::makeQueryStr();

		$days_recent=$ads_settings["days_recent"];

		$current_date = date("Y-m-d");

		$dn1 = $days_recent+1;
		$date_x_days_before_start = $db->fetchRow("select date_sub('$current_date', INTERVAL $dn1 DAY)");
		//$date_x_days_before_end = $db->fetchRow("select date_sub('$current_date', INTERVAL $days_recent DAY)");


		if($days_recent>0)
			$sql = "select count(*) from ".TABLE_ADS." where active=1 and `date_added` > '$date_x_days_before_start' ".$locations_str;
		else $sql = "select count(*) from ".TABLE_ADS." where active=1".$locations_str;
		if($sql_where) $sql.=$sql_where;
		$no = $db->fetchRow($sql);
		return $no;
	}

	function getStoreListings($page,$ads_per_page,$order_by,$order_way,$crt_usr='') {

		global $db;
		$start=($page-1)*$ads_per_page;
		$where = "where ".TABLE_ADS.".active = 1";
		if($crt_usr) $where.=" and ".TABLE_ADS.".user_id = $crt_usr";
		if(!$order_by) $order_by='date_added';
		if(!$order_way) $order_way='desc';
		
		global $ads_settings;
		if($ads_settings['enable_priorities'])
			$order_by_str="order by priority desc, ".$order_by." ".$order_way;
		else $order_by_str="order by ".$order_by." ".$order_way;

		$result=$this->getShortListings($where,$order_by_str,'',$start,$ads_per_page);
		return $result;

	}

	function getRecent($page, $ads_per_page, $order_by, $order_way, $sql_where = '') {

		global $db;
		$start=($page-1)*$ads_per_page;
		global $ads_settings, $settings;
		$days_recent=$ads_settings["days_recent"];

		$current_date = date("Y-m-d");

		$dn1 = $days_recent+1;
		$date_x_days_before_start = $db->fetchRow("select date_sub('$current_date', INTERVAL $dn1 DAY)");
		//$date_x_days_before_end = $db->fetchRow("select date_sub('$current_date', INTERVAL $days_recent DAY)");

		$locations_str="";
		if($settings['enable_locations'])
			$locations_str = locations::makeQueryStr();

		if($days_recent>0) {
			$where="where `date_added` > '$date_x_days_before_start' and ".TABLE_ADS.".active =1".$locations_str;
		}
		else {
			$where="where ".TABLE_ADS.".active = 1".$locations_str;
		}

		if($where) $where.=$sql_where;

		if(!$order_by) $order_by='date_added';
		$order_by_str="order by ".$order_by;
		if(!$order_way) $order_way='desc';

		$result=$this->getShortListings($where,$order_by_str,$order_way,$start,$ads_per_page);
		return $result;
	}
/*
	function getFeatured($no_featured, $where = '') {

		global $db, $settings;

		$locations_str="";
		if($settings['enable_locations'])
			$locations_str = locations::makeQueryStr();

		$where="where ".TABLE_ADS.".featured=1 and ".TABLE_ADS.".active = 1".$where.$locations_str;

		$order_by_str="order by rand()";
		$order_way='';
		$start=0;
		$ads_per_page=$no_featured;

		$result=$this->getShortListings($where,$order_by_str,$order_way,$start,$ads_per_page);
		return $result;
	}
*/
	function getFeatured($no_featured, $where = '') {

		
		global $db, $settings;

		$locations_str="";
		if($settings['enable_locations'])
			$locations_str = locations::makeQueryStr();

		$where="where ".TABLE_ADS.".featured>0 and ".TABLE_ADS.".active = 1".$where.$locations_str;

		// solution to avoid order by rand() 

		$total_featured = $db->fetchRow("select count(*) from ".TABLE_ADS." ".$where);

		if($total_featured<=$no_featured) {

			$result=$this->getShortListings($where,"","",0,0);
			shuffle ( $result );
			return $result;

		}

		// more than $no_featured results
		$t = $total_featured - $no_featured;
		$start = rand(0,$t);

		$result=$this->getShortListings($where,"","",$start,$no_featured);
		return $result;

	}

	function getLatest($no_latest, $where = '') {

		global $db, $settings;

		$locations_str="";
		if($settings['enable_locations'])
			$locations_str = locations::makeQueryStr();

		$where="where ".TABLE_ADS.".active = 1".$where.$locations_str;

		$order_by_str="order by date_added desc";
		$order_way='';
		$start=0;
		$ads_per_page=$no_latest;

		$result=$this->getShortListings($where,$order_by_str,$order_way,$start,$ads_per_page);
		return $result;
	}

	function getRandom($no_random) {

		global $db, $settings;

		$locations_str="";
		if($settings['enable_locations'])
			$locations_str = locations::makeQueryStr();

		$where="where ".TABLE_ADS.".active = 1".$locations_str;

		$order_by_str="order by rand()";
		$order_way='';
		$start=0;
		$ads_per_page=$no_random;

		$result=$this->getShortListings($where,$order_by_str,$order_way,$start,$ads_per_page);
		return $result;
	}

	function getList($list) {

		global $db;
		global $appearance_settings;
		$where="where ".TABLE_ADS.".`id` in ($list)";

		$order_by_str='';
		$order_way='';
		$start=0;
		$ads_per_page=$appearance_settings['ads_per_page'];

		$result=$this->getShortListings($where,$order_by_str,$order_way,$start,$ads_per_page);
		return $result;
	}


	function getListing($id, $not_formatted=0, $result= '') {

		global $db;
		global $lng;
		global $appearance_settings, $ads_settings, $settings;
		global $config_live_site;

		$date_format=$appearance_settings["date_format"];
		if(!$id) $id=$this->id;
		global $crt_lang, $admin_side;
		global $is_mobile;
		
		$mobile_pic_substr = '';
		if($is_mobile) $mobile_pic_substr="mobile_";

		// $result can be given as an array to format
		if(!$result) {
 
			$mlang_vars ='';
			$mlang=0;
			if($ads_settings['translate_title_description']) {
				global $languages;
				if(empty($languages)) $languages = common::getCachedObject("base_languages");
				if(count($languages)>1) {
					$mlang=1;
					$mlang_vars = ",`title_$crt_lang` as `title`, `description_$crt_lang` as `description` ";
				}
			}

			$timestamp = date("Y-m-d H:i:s");

			$sql = "select ".TABLE_ADS.".*$mlang_vars, date_format(".TABLE_ADS.".`date_added`,'$date_format') as date_nice, date_format(".TABLE_ADS.".`date_expires`,'$date_format') as date_expires_nice, UNIX_TIMESTAMP(".TABLE_ADS.".`date_added`) as `time_added`, (".TABLE_ADS.".`date_expires`<'$timestamp' and ".TABLE_ADS.".`date_expires`!='0000-00-00 00:00:00' and ".TABLE_ADS.".active=0) as expired from ".TABLE_ADS." 
			where ".TABLE_ADS.".`id`='$id'";

			$result=$db->fetchAssoc($sql);
//_print_r($result);
			if(!$result) return array();

		} // end if !$array_to_format
		else {
			// get category_id
			$result['category_id'] = listings::getCategory($id);
			$result['user_id'] = listings::getUser($id);
		}

		// clean slashes
		foreach($result as $key=>$value) { 
			if($key=="currency") continue;// allow spaces for currency
			//if($not_formatted) 
				$result[$key] = cleanStr($result[$key]);
			//else
			//	$result[$key] = cleanHtml($result[$key], 1);
		}
//_print_r($result);
		$result['fieldset'] = categories::getFieldset($result['category_id']);

		// special fields
		$fields = common::getCachedObject("base_listing_fields", array("fieldset" => $result['fieldset']));
		if(!$fields) $fields=array();
		
		$this->setFields($fields);
		
		$result['has_video'] = 0;
	
		$result['has_url'] = 0;

		foreach ($fields as $field) {

		$fname = $field['caption'];

		if($field['type'] == "checkbox_group" || $field['type'] == "multiselect") { 

			if($result[$fname]) $result[$fname] = explode("|",$result[$fname]);
			else $result[$fname] = array();

		}

		else if($field['type'] == "date" ) {

			if($result[$fname] && $result[$fname]!='0000-00-00') {
				$result['vis'][$fname] = format_date_str($result[$fname], $field['date_format']);
			} else $result[$fname] = '';

		} // end if date

		else if( $field['type'] == "price" ){

				$arr_ext=explode("|",$field['extensions']);
				$decimals=$arr_ext[0];
				$format_point=$arr_ext[1];
				$format_separator=$arr_ext[2];

				$result[$fname] = format_price_field($result[$fname], $decimals, $format_separator, $format_point);

		} 

		if(!$not_formatted && isset($result[$fname]) && $result[$fname]) {
			// format numeric fields
			if( $field['validation_type'] == "numeric"){

				$result[$fname] = format_numeric($result[$fname]);

			} 
			else if( $field['is_numeric'] && $field['type']!="price"){

				$result[$fname] = format_numeric_field($fname, $result[$fname], $field['extensions']);

			}
			else if ($field['type']=="textarea") {

				$result['formatted'][$fname] = str_replace("\n", "<br/>", $result[$fname]);

			}
			else if ($field['type']=="htmlarea") {

				$result['formatted'][$fname] = html_entity_decode($result[$fname]);

			}
			else if ($field['type']=="youtube" && $result[$fname]) {

				global $config_abs_path;
				require_once $config_abs_path."/include/patterns.php";
				$result[$fname] = formatVideo($result[$fname]);
				$result['has_video'] = 1;

			}
		}// end if not formatted
		} // foreach 
//_print_r($result);
		// video enabled
		$result['enable_video'] = $this->isVideoEnabled($id);

		// url enabled
		$result['enable_url'] = $this->isUrlEnabled($id);

		if($result['user_id']) {
			global $config_abs_path;
			require_once $config_abs_path."/classes/users.php";
			$users=new users();
			$result['user'] = $users->getUser($result['user_id']);

			$this->setUserFields($users->getFields($result['user_id']));
		} else {

			// nologin info
			$user_fields_array = common::getCachedObject("base_user_fields", array("group" => -1));
			$this->setUserFields($user_fields_array);
			if($nologin_info = $this->getNologinInfo($result['id'])) {
				$result['user'] = $nologin_info;
				foreach($nologin_info as $key => $value) {
					if($key=="id") continue;
					if($key=="formatted") {
						foreach($value as $k => $v) {
							if( !$k || !$v ) continue;
							$result['user']['formatted'][$k] = $result['user']['formatted'][$v];
						}
						continue;
					}
					$result['user'][$key] = $value;
				}
			}
		}

		if($result['language']!=$crt_lang) {
			$language = new languages;
			$result = $language->translateFieldsElements($result, "cf");
		}

		// auctions
		if($ads_settings['enable_auctions']) {

			global $config_abs_path;
			require_once $config_abs_path."/classes/auctions.php";
			$ac = new auctions;
			$result['auction'] = $ac->getAuctionForAd($id);
			
		}
		
		if($not_formatted) return $result;

		// x days ago
		if(!$admin_side && !$result) $result['date_nice'] = $this->makeTimeAgoDate($result['time_added'], $result['date_nice']);

		// get category
		$result['category'] = cleanStr(categories::getName($result['category_id']));

		if($result['enable_video'] && $result['video']) { 
			global $config_abs_path;
			require_once $config_abs_path."/include/patterns.php";

			$result['video'] = formatVideo($result['video']);
		}

		if(!$result && $mlang) {

			$default_title = '';
			$default_description = '';
			foreach($languages as $l) {
				$lang_id = $l['id'];
				$result[$lang_id] = array();
				$result[$lang_id]['title'] = $result['title_'.$lang_id];
				$result[$lang_id]['description'] = $result['description_'.$lang_id];
				if($l['default']==1) {
					$default_title = $result['title_'.$lang_id];
					$default_description = $result['description_'.$lang_id];
				}
			}

			if(!$result['title']) { 
				$result['title'] = $default_title;
				if(!$result['title']) { 
					foreach($languages as $l) 
					if($result['title_'.$l['id']]) { $result['title'] = $result['title_'.$l['id']]; break; }
				}
			}

			if(!$result['description']) { 
				$result['description'] = $default_description;
				if(!$result['description']) { 
					foreach($languages as $l) 
					if($result['description_'.$l['id']]) { $result['description'] = $result['description_'.$l['id']]; break; }
				}
			}

		}

		// format description
		$result['description_formatted'] = str_replace("\n", "<br>", $result['description']);

		$pictures=new pictures();
		$result['images']=$pictures->getPictures($id);
		$no_images=count($result['images']);

		// for "default images" module
		if(!$no_images) {
			$result['big_nopic'] = "/images/".$ads_settings['big_nopic'];
		}

		$result['max_height']=0;
		$str_preload = '';
		for($im=0; $im<$no_images;$im++){
			if($im) $str_preload .= ',';
			$folder_str = "";
			if($result['images'][$im]['folder']) $folder_str = "/".$result['images'][$im]['folder'];
			$str_preload .= $config_live_site."/images/listings".$folder_str.$mobile_pic_substr."/bigThmb/".$result['images'][$im]['picture'];
			if($result['images'][$im]['thmb_height']>$result['max_height']) $result['max_height'] = $result['images'][$im]['thmb_height'];

		}
		$result['preload_images'] = $str_preload;

		$result['stock']=sprintf("%04d", $id);

		$result['price_curr'] = add_currency($result['price'],$result['currency']);

		if($ads_settings['enable_priorities'] && isset($result['priority']) && $result['priority']>0) { 
			global $config_abs_path;
			require_once $config_abs_path."/classes/priorities.php";
			$result['priority_name'] = priorities::getNameByOrder($result['priority']);
		}

		// auctions
		if($ads_settings['enable_auctions']) {

			global $crt_usr;
			if( $result['user_id'] == $crt_usr && $result['auction']['no_bids'] ) {
					$result['bids'] = $ac->getBids($result['auction']['id']);
			}

		}

		do_action("listing_result", array(&$result));

		return $result;
	}

		function getShortListing($id) {

		global $db;
		global $lng;
		global $appearance_settings;
		global $ads_settings;
		global $settings;
		$date_format=$appearance_settings["date_format"];
		if(!$id) $id=$this->id;
		global $crt_lang;
	
		$mlang_vars ='';
		$mlang=0;
		if($ads_settings['translate_title_description']) {
			global $languages;
			if(empty($languages)) $languages = common::getCachedObject("base_languages");
			if(count($languages)>1) {
				$mlang=1;
				$mlang_vars = ",`title_$crt_lang` as `title`, `description_$crt_lang` as `description` ";
			}
		}

		$timestamp = date("Y-m-d H:i:s");

		$sql = "select ".TABLE_ADS.".*$mlang_vars, date_format(".TABLE_ADS.".`date_added`,'$date_format') as date_nice, date_format(".TABLE_ADS.".`date_expires`,'$date_format') as date_expires_nice, UNIX_TIMESTAMP(".TABLE_ADS.".`date_added`) as `time_added`, (".TABLE_ADS.".`date_expires`<'$timestamp' and ".TABLE_ADS.".`date_expires`!='0000-00-00 00:00:00' and ".TABLE_ADS.".active=0) as expired from ".TABLE_ADS." 
		where ".TABLE_ADS.".`id`='$id'";

		$result=$db->fetchAssoc($sql);

		if(!$result) return array();

		// clean slashes
		foreach($result as $key=>$value) { 
			if($key=="currency") continue;
			$result[$key] = cleanHtml($result[$key]);
		}

		// x days ago
		$result['date_nice'] = $this->makeTimeAgoDate($result['time_added'], $result['date_nice']);

		// get category
		$result['category'] = cleanStr(categories::getName($result['category_id']));

		// video enabled
		$result['enable_video'] = $this->isVideoEnabled($result['id']);

		// video enabled
		$result['enable_url'] = $this->isUrlEnabled($result['id']);

		if($mlang) {

			$default_title = '';
			$default_description = '';
			foreach($languages as $l) {
				$lang_id = $l['id'];
				$result[$lang_id] = array();
				$result[$lang_id]['title'] = $result['title_'.$lang_id];
				$result[$lang_id]['description'] = $result['description_'.$lang_id];
				if($l['default']==1) {
					$default_title = $result['title_'.$lang_id];
					$default_description = $result['description_'.$lang_id];
				}
			}

			if(!$result['title']) { 
				$result['title'] = $default_title;
				if(!$result['title']) { 
					foreach($languages as $l) 
					if($result['title_'.$l['id']]) { $result['title'] = $result['title_'.$l['id']]; break; }
				}
			}

			if(!$result['description']) { 
				$result['description'] = $default_description;
				if(!$result['description']) { 
					foreach($languages as $l) 
					if($result['description_'.$l['id']]) { $result['description'] = $result['description_'.$l['id']]; break; }
				}
			}

		}

		$pictures=new pictures();
		$result['images']=$pictures->getPictures($id);
		$no_images=count($result['images']);

		$result['stock']=sprintf("%04d", $id);

		return $result;

	}


	function getNoShortListings($where, $q_join='', $noloc = 0){

		global $db, $settings;
		$count = "*";

		$locations_str="";
		global $admin_side;
		if(!$noloc && $settings['enable_locations'] && !isset($admin_side)) {
			$locations_str = locations::makeQueryStr();
			if(!$where) $locations_str = " where ".substr($locations_str, 4);
		}

		do_action("search_listing_query", array(&$where));
		
		$join_users = '';
		if(strstr($where, TABLE_USERS)) 
			$join_users = "inner join ".TABLE_USERS." on ".TABLE_ADS.".user_id=".TABLE_USERS.".id ";

		// count(*) faster than count(val) **********
		$sql = "select count($count) 
		from ".TABLE_ADS."
		$q_join $join_users
		".$where.$locations_str;
		//echo $sql;
		$no_ads = $db->fetchRow($sql);

		return $no_ads;
	}

	function getShortListings($where, $order_by, $order_way, $general_row, $ads_per_page, $search='', $q_vars='', $q_join='', $join_cat='') {

		global $db;
		global $lng;
		global $appearance_settings, $ads_settings, $settings, $seo_settings;
		$date_format=$appearance_settings["date_format"];
 		global $crt_lang;
		global $is_mobile;
		global $admin_side, $include_user_info;
		global $config_abs_path;

		$mobile_pic_substr="";
		if($is_mobile) $mobile_pic_substr = "mobile_";
	
		$mlang=0;
		$mlang_vars ='';
		$group_by = '';

		global $languages;
		if(empty($languages)) $languages = common::getCachedObject("base_languages");
		$no_languages = count($languages);

		// translate title and description
		if($ads_settings['translate_title_description'] && $no_languages>1) {
			
			$mlang = 1;
			$mlang_vars = ",`title_$crt_lang` as `title`, `description_$crt_lang` as `description` ";
		}


		if($ads_settings['enable_map_search']) 
		{
			$search_location_fields = explode(",", $ads_settings['search_location_fields']);
		}

		$timestamp = date("Y-m-d H:i:s");

		do_action("search_listing_query", array(&$where));
		do_action("search_listing_order", array(&$order_by, &$order_way));

		if(!$where) $where=" where 1 ";

		$join_pictures = '';
		if(strstr($where, TABLE_ADS_PICTURES)) 
			$join_pictures = "inner join ".TABLE_ADS_PICTURES." on ".TABLE_ADS.".id=".TABLE_ADS_PICTURES.".ad_id ";

		$join_users = '';
		if(strstr($where, TABLE_USERS)) 
			$join_users = "inner join ".TABLE_USERS." on ".TABLE_ADS.".user_id=".TABLE_USERS.".id ";

		global $config_table_prefix;
		$group="";

		if(strstr($where, $config_table_prefix."zip")) 
			$group = " group by ".TABLE_ADS.".id ";

		$join_slugs='';
		$str_slugs='';
		$where_slugs='';
		if($seo_settings['enable_mod_rewrite']) {
			$join_slugs="inner join ".TABLE_SLUGS." on ".TABLE_ADS.".id=".TABLE_SLUGS.".object_id";
			$str_slugs=", ".TABLE_SLUGS.".slug ";
			$where_slugs=" and ".TABLE_SLUGS.".`type`='listing'";
		}
		$str_index = "";
		if($this->use_index) $str_index = " use index ({$this->use_index}) ";

		$sql = "select distinct ".TABLE_ADS.".*$mlang_vars, ".TABLE_ADS.".id as adid, date_format(".TABLE_ADS.".`date_added`,'$date_format') as date_nice, date_format(".TABLE_ADS.".`date_expires`,'$date_format') as date_expires_nice, UNIX_TIMESTAMP(".TABLE_ADS.".`date_added`) as `time_added`, (".TABLE_ADS.".date_expires < '$timestamp' and ".TABLE_ADS.".date_expires!='0000-00-00 00:00:00' and ".TABLE_ADS.".active=0) as expired
		$str_slugs
		$q_vars 
		from ".TABLE_ADS." $str_index
		$q_join $join_pictures $join_cat $join_users
		$join_slugs
		 ".$where.$where_slugs." ".$order_by." ".$order_way." ";
		if($ads_per_page>0) $sql .= " limit ".$general_row.", ".$ads_per_page;
//	echo $sql."<br/><br/>";
		$arr = $db->fetchAssocList($sql);
//_print_r($arr);
		$i=0;
		$result=array();
		$pictures=new pictures();

		if($is_mobile) {
			global $mobile_settings;
			$nopic = $mobile_settings['mobile_nopic'];
			$medNopic = $mobile_settings['mobile_nopic'];
		} else  {
			$nopic=$ads_settings["nopic"];
			$medNopic=$ads_settings["med_nopic"];
		}
			
		if($search) {

			// escape parantheses - if not it will cause an REG_EPAREN error for eregi_replace 
			$search = str_replace("(", "\(", $search);
			$search = str_replace(")", "\)", $search);

			$search_array = explode('%20', $search);
			$no_words = count($search_array);

		} else $no_words = 0;

		$price_extensions = $db->fetchRow("select `extensions` from ".TABLE_FIELDS." where `type` = 'price' limit 1");
		if($price_extensions) {
			$arr_ext=explode("|",$price_extensions);
			$price_decimals=$arr_ext[0];
			$price_point=$arr_ext[1];
			$price_separator=$arr_ext[2];
		} else {
			global $appearance_settings;
			$price_decimals = $appearance_settings['price_field_format_decimals'];
			$price_point = $appearance_settings['price_field_format_point'];
			$price_separator = $appearance_settings['price_field_format_separator'];
		}

		foreach ($arr as $row) {

			$result[$i]=$row;

			// x days ago
			$result[$i]['date_nice'] = $this->makeTimeAgoDate($result[$i]['time_added'], $result[$i]['date_nice']);

			// category
			if(!$is_mobile)
	    			$result[$i]['category'] = $db->fetchRow("select `name` from ".TABLE_CATEGORIES."_lang where id='".$row['category_id']."' and `lang_id`='$crt_lang'");

			// picture
			$pic_array = $db->fetchAssoc("select `picture`, `folder`, `id` as `picture_id` from ".TABLE_ADS_PICTURES." where ad_id='".$row['id']."' order by `order_no` asc limit 1");
			if($pic_array) {
				$result[$i]['picture_id'] = $pic_array['picture_id'];
				$result[$i]['picture'] = $pic_array['picture'];
				$result[$i]['picture_folder'] = $pic_array['folder'];
				$result[$i]['no_pictures']= $db->fetchRow("select count(*) from ".TABLE_ADS_PICTURES." where ad_id='".$row['id']."' ");
			} else { 	
				$result[$i]['picture']=null;
				$result[$i]['no_pictures']=0;
				}

			// priority
			if($ads_settings['enable_priorities'] && $row['priority']>0) {

				$result[$i]['priority_name'] = $db->fetchRow("select `name` from ".TABLE_PRIORITIES."_lang LEFT JOIN ".TABLE_PRIORITIES." on ".TABLE_PRIORITIES.".`id` = ".TABLE_PRIORITIES."_lang.`id` where `order_no`={$row['priority']} and `lang_id`='$crt_lang'");

			}
			// video
			if($ads_settings['enable_video'] && $row['video']) {
				$result[$i]['enable_video'] = $this->isVideoEnabled($row['id']);
//echo $row['id']."--".$result[$i]['enable_video']."<br/>";
			}

			// url
			if($ads_settings['enable_url'] && isset($row['url']) && $row['url']) {
				$result[$i]['enable_url'] = $this->isUrlEnabled($row['id']);
//echo $row['id']."--".$result[$i]['enable_video']."<br/>";
			}

			// clean slashes
			foreach($result[$i] as $key=>$value) {
				if($key=="currency") continue;
				$result[$i][$key] = cleanHtml($result[$i][$key]);
			}

			$result[$i]['id']=$result[$i]['adid'];

			if($result[$i]['picture']) { 
				$str_folder = '';
				if($result[$i]['picture_folder']) $str_folder = $result[$i]['picture_folder']."/";
				$result[$i]['image']="images/listings/".$str_folder.$mobile_pic_substr."thmb/".$result[$i]['picture'];

				$size=@getimagesize($config_abs_path."/".$result[$i]['image']);
				$result[$i]['thmb_width'] = $size[0];
				$result[$i]['thmb_height'] = $size[1];

				$result[$i]['medImage']="images/listings/".$str_folder.$mobile_pic_substr."medThmb/".$result[$i]['picture'];
				$result[$i]['bigImage']="images/listings/".$str_folder.$mobile_pic_substr."bigThmb/".$result[$i]['picture'];
				$result[$i]['image_id']=$result[$i]['picture_id'];
			}
			else {
				$result[$i]['image']="images/".$nopic;
				$result[$i]['medImage']="images/".$medNopic;
				$result[$i]['image_id']=0;
			}

			//$result[$i]['paid']=0;
			//if($result[$i]['amount']>0) $result[$i]['paid']=1;

			$result[$i]['price_formatted'] = format_price_field($result[$i]['price'], $price_decimals, $price_separator, $price_point);
			$result[$i]['price_curr'] = add_currency($result[$i]['price_formatted'], $result[$i]['currency']);

			if($result[$i]['language']!=$crt_lang) {
				$language = new languages;
				$result[$i] = $language->translateFieldsElements($result[$i], "cf");
			}

			if($ads_settings['location_fields']) {

				$result[$i]['location_str'] = $this->getLocationStr($result[$i]);
			}

			global $search_pages_description_width;
			if($mlang) {

				$result[$i]['title'] = strip_tags($result[$i]['title']);
				$result[$i]['description'] = strip_tags($result[$i]['description']);

				$default_title = '';
				$default_description = '';

				foreach($languages as $l) {
					$lang_id = $l['id'];
					if($l['default']==1) {
						$default_title = $result[$i]['title_'.$lang_id];
						$default_description = _substr($result[$i]['description_'.$lang_id], $search_pages_description_width);
						break;
					}
				}

				if(!$result[$i]['title']) { 
					$result[$i]['title'] = $default_title;
					if(!$result[$i]['title']) { 
						foreach($languages as $l) 
						if($result[$i]['title_'.$l['id']]) { $result[$i]['title'] = $result[$i]['title_'.$l['id']]; break; }
					}
				}

				if(!$result[$i]['description']) { 
					$result[$i]['description'] = $default_description;
					if(!$result[$i]['description']) { 
						foreach($languages as $l) 
						if($result[$i]['description_'.$l['id']]) { $result[$i]['description'] = _substr($result[$i]['description_'.$l['id']], $search_pages_description_width); break; }
					}
				}

			}
			else {
				$result[$i]['description'] = strip_tags($result[$i]['description']);
				$result[$i]['description'] = _substr($result[$i]['description'], $search_pages_description_width);
			}

			// mark search words
			global $mark_search_words;
			if($mark_search_words) {
			global $search_words_prefix, $search_words_postfix;
			for($k=$no_words; $k>0 ;$k--)
			{
				$w=str_replace("/", "\/", trim($search_array[$k-1]));
				if($w!='')
				{
					$result[$i]['description'] = preg_replace('/'.$w.'/i',$search_words_prefix.$search_array[$k-1].$search_words_postfix, $result[$i]['description']);
					$result[$i]['title'] = preg_replace('/'.$w.'/i',$search_words_prefix.$search_array[$k-1].$search_words_postfix, $result[$i]['title']);
				}
			}
			} // end mark search words

			$result[$i]['stock']=sprintf("%04d", $result[$i]['adid']);

			// make search_location_fields string
			if($ads_settings['enable_map_search']) {
				$result[$i]['search_map_location'] = '';
				foreach($search_location_fields as $s) {
					if($result[$i][$s]) $result[$i]['search_map_location'] .=" ".$result[$i][$s];
				} 
			}

			if(!$is_mobile && $admin_side && $row['user_id']) {
				$result[$i]['username'] = $db->fetchRow("select `username` from ".TABLE_USERS." where id='".$row['user_id']."'");
			}

			if(!$is_mobile && $include_user_info && $row['user_id']) {
				global $config_abs_path;
				require_once $config_abs_path."/classes/users.php";
				$u = new users();
				$result[$i]['user'] = $u->getUser($row['user_id']);
			}

			$i++;

		}

		do_action("short_listings_result", array(&$result));
//_print_r($result);

		return $result;
	}

	static function getOwnerInfo($id) {

		global $db;
		$array = $db->fetchAssoc("select * from ".TABLE_ADS_EXTENSION." where `id`=$id");
		return $array;

	}

	function getNologinInfo($id) {

		global $db;
		$result = $db->fetchAssoc("select * from ".TABLE_ADS_EXTENSION." where `id`=$id");
		$fields = $this->getUserFields();

		foreach ($fields as $field) {

			$fname = $field['caption'];

			if($field['type'] == "checkbox_group" || $field['type'] == "multiselect") { 

				if($result[$fname]) $result[$fname] = explode("|",$result[$fname]);
				else $result[$fname] = array();

			}

			else if($field['type'] == "date" ) {

				if($result[$fname] && $result[$fname]!='0000-00-00') {
					$result['vis'][$fname] = format_date_str($result[$fname], $field['date_format']);
				} else $result[$fname] = '';

			} // end if date

			// format numeric fields
			else if( $field['type'] == "price" ){

				$result['formatted'][$fname] = format_price($result[$fname]);

			} 
			else if( $field['validation_type'] == "numeric"){

				$result['formatted'][$fname] = format_numeric($result[$fname]);

			} 
			else if( $field['is_numeric']){

				$result['formatted'][$fname] = format_numeric_field($fname, $result[$fname], $field['extensions']);

			}
			else if ($field['type']=="textarea") {

				$result['formatted'][$fname] = str_replace("\n", "<br>", $result[$fname]);

			}

		} // foreach 

		return $result;

	}

	function correctKey($id, $key) {

		global $db;
		$exists = $db->fetchRow("select count(*) from ".TABLE_ADS_EXTENSION." where `id`=$id and `activation`='$key'");
		return $exists;

	}


function belongsToUser($id,$user_id) {
		
	global $db;
	$res=$db->query("select user_id from ".TABLE_ADS." where id = '$id'");
	if(!$db->numRows($res)) return 0;
	$user=$db->fetchRow();
	if($user==$user_id) return 1;
	return 0;

}

function incView($id=0) {

	global $db;
	if(!$id) $id=$this->id;
	$res=$db->query("update ".TABLE_ADS." set viewed=viewed+1 where id = '$id'");
	return 1;
}

static function idExists($id=0) {

	global $db;
	if(!$id) return 0;
	$no=$db->fetchRow("select count(*) from ".TABLE_ADS." where id = '$id'");
	if($no>0) return 1;
	return 0;
}

static function getViewed($user_id='') {

	global $db;
	if($user_id!='') $str_usr = " where user_id='$user_id'";
	else $str_usr='';
	$no=$db->fetchRow("select sum(viewed) from ".TABLE_ADS.$str_usr);
	if(!$no) return 0;
	return $no;

}

function strip_html_tags($str){
	$search = array('@<script[^>]*?>.*?</script>@si',  // Remove javascript
               '@<style[^>]*?>.*?</style>@siU',    // Remove style tags
               '@<[\/\!]*?[^<>]*?>@si',            // Remove HTML tags
               '@<![\s\S]*?--[ \t\n\r]*>@'         // Remove multi-line comments including CDATA
	);
	$text = preg_replace($search, '', $str);
	return $text;
}


function ActivatePendingPackage($usr_pkg) {

	global $db;

	// check if the listings for the user group should be pending or not or if the plan is ad based

	$array = $db->fetchAssocList("select ".TABLE_ADS.".`id` as `ad_id`, ".TABLE_ADS.".`active` as `ad_active`, ".TABLE_USER_GROUPS.".listing_pending, ".TABLE_PACKAGES.".`type` as `package_type` from ".TABLE_ADS." 
	left join ".TABLE_USERS." on ".TABLE_ADS.".`user_id` = ".TABLE_USERS.".`id` 
	left join ".TABLE_USER_GROUPS." on ".TABLE_USERS.".`group` = ".TABLE_USER_GROUPS.".id  
	left join ".TABLE_PACKAGES." on ".TABLE_ADS.".`package_id` = ".TABLE_PACKAGES.".id  
	where usr_pkg='$usr_pkg' and pending=1");

	foreach ( $array as $ad ) {
		if( (!$ad['listing_pending'] || $ad['package_type']=="ad") && !$ad['ad_active'] ) $this->Activate($ad['ad_id']);
	}

}

function getAllId($limit=0) {

	global $db;
	if($limit) $str_limit = " limit ".$limit; else $str_limit="";
	$array = $db->fetchRowList("select id from ".TABLE_ADS." where active=1 order by date_added desc".$str_limit);
	return $array;

}

function getAllSitemapQuery($limit=0) {

	global $db;

	global $crt_lang;
	global $ads_settings, $settings;
	$title_var ="`title`";
	if($ads_settings['translate_title_description']) {
		global $languages;
		if(empty($languages)) $languages = common::getCachedObject("base_languages");
		if(count($languages)>1) {
			$title_var = "`title_$crt_lang` as `title`";
		}
	}


	if($limit) $str_limit = " limit ".$limit; else $str_limit="";

	$subdomain_str = '';
	if($settings['enable_locations'] && $settings['enable_subdomains'])
		$subdomain_str = ", `{$settings['subdomain_field']}`";

	return "select id, $title_var $subdomain_str from ".TABLE_ADS." where active=1 order by date_added desc".$str_limit;

}


// ------------------- favourites -----------------------
	function addFavourite ($ad_id) {

		global $db;
		$auth=new auth();
		if($user_id = $auth->crtUserId()) {
			$res_exists=$db->query("select * from ".TABLE_FAVOURITES." where ad_id=$ad_id and user_id=$user_id");
			if(!$db->numRows($res_exists))
				$res=$db->query("insert into ".TABLE_FAVOURITES." values ($ad_id, $user_id)");
		} else {
			if(!isset($_SESSION['favourites'])) $i=0;
			else $i = count($_SESSION['favourites']);
			$exists=0;
			for($k=0; $k<$i;$k++) {
				if($_SESSION['favourites'][$k]==$ad_id) $exists=1;
			}
			if(!$exists) $_SESSION['favourites'][$i] = $ad_id;
		}
		return 1;
	}

	function getNoFavourites() {

		global $db, $crt_usr;
		if($crt_usr)
			$no=$db->fetchRow("select count(*) from ".TABLE_FAVOURITES." where user_id=$crt_usr");
		else {
			if(isset($_SESSION['favourites']))
				$no = count($_SESSION['favourites']);
			else $no = 0;	
		}

		return $no;

	}

	function deleteFavourite($ad_id) {

		global $db;
		$auth=new auth();
		if($user_id = $auth->crtUserId()) {
			$res_del=$db->query('delete from '.TABLE_FAVOURITES.' where `ad_id`="'.$ad_id.'" and user_id = "'.$user_id.'"');
		} else {
			if($_SESSION['favourites']) array_splice($_SESSION['favourites'], array_search($ad_id, $_SESSION['favourites']), 1);
		}
	}

	function getAllFavourites($page, $ads_per_page, $order_by, $order_way) {

		global $db;
		$exists = 0;
		$start=($page-1)*$ads_per_page;

		global $crt_usr;
		if($crt_usr) {

			$where = " LEFT JOIN ".TABLE_FAVOURITES." on ".TABLE_ADS.".id = ".TABLE_FAVOURITES.".ad_id where ".TABLE_FAVOURITES.".`user_id` = '$crt_usr'";
			$exists = 1;

		} else {

			if(isset($_SESSION['favourites'])) $no = count($_SESSION['favourites']); else $no = 0;
			$str_ids = ' (';
			for($k=0; $k<$no; $k++){
				if($k) $str_ids.=', ';
				$str_ids .= $_SESSION['favourites'][$k];
			}
			$str_ids .= ')';
			if($no>0) {
				$where = " where ".TABLE_ADS.".`id` in ".$str_ids." and ".TABLE_ADS.".`active` = 1";
				$exists = 1;
			} 
		}

		if(!$order_by) $order_by='date_added';
		$order_by = " order by ".$order_by;
		if(!$order_way) $order_way='desc';
	
		$result = array();

		if($exists) $result=$this->getShortListings($where,$order_by,$order_way,$start,$ads_per_page);
		return $result;
	}

	static function getFavouritesArray() {

		global $db, $crt_usr;
		$arr = array();
		if($crt_usr)
			$arr = $db->fetchRowList("select `ad_id` from ".TABLE_FAVOURITES." where `user_id` = '$crt_usr'");
		else 
		{
			if(isset($_SESSION['favourites']) && $_SESSION['favourites'])
			$arr = $_SESSION['favourites'];
		}
		return $arr;
	}

	function getTableFields() {

		global $db;

		$extra_fields = array("pictures", "date_formatted", "date_expires_formatted", "expired", "category", "plan", "plan_amount", "username", "pending_package", "invoice", "price_formatted", "stock");

		$fields = $db->getTableFields(TABLE_ADS);

		$fields = array_merge($fields, $extra_fields);

		return $fields;

	}

	function getTableCSVFields($extra=1) {

		global $db;
		$extra_fields = array("pictures", "picture", "date_formatted", "date_expires_formatted", "expired", "category", "plan", "plan_amount", "username", "pending_package", "invoice", "price_formatted", "stock");

		$fields = $db->getTableCSVFields(TABLE_ADS);

		if(!$extra) return $fields;
		foreach ($extra_fields as $f) $fields.=",".$f;

		return $fields;

	}

	function getOptions($id) {

		global $db;
		$array = $db->fetchAssoc("select `category_id`, `package_id`, `featured`, `highlited`,`priority`, `urgent` from ".TABLE_ADS." 
		where `id`=$id" );
		
		$video = $db->fetchRow("select count(*) from ".TABLE_OPTIONS." where `option` like 'video' and `object_id` = $id");
		if($video) $array['video'] = 1; else $array['video'] = 0;

		$url = $db->fetchRow("select count(*) from ".TABLE_OPTIONS." where `option` like 'url' and `object_id` = $id");
		if($url) $array['url'] = 1; else $array['url'] = 0;

		return $array;
	}

	function editOptions($id) {

		global $db;
		$featured = checkbox_value("featured");
		$highlited = checkbox_value("highlited");
		$urgent = checkbox_value("urgent");
		$res = $db->query("update ".TABLE_ADS." set `featured`='$featured', `highlited`='$highlited', `urgent`='$urgent' where id=$id ");

		if(isset($_POST['featured']) && is_numeric($_POST['featured'])) { 
			$featured = $_POST['featured'];
			$res1 = $db->query("update ".TABLE_ADS." set `featured`='$featured' where id=$id ");
			$this->addOption($id, 'featured');
		}

		if($highlited) $this->addOption($id, 'highlited');
		if($urgent) $this->addOption($id, 'urgent');

		if(isset($_POST['priority']) && is_numeric($_POST['priority'])) { 
			$priority = $_POST['priority'];
			$res1 = $db->query("update ".TABLE_ADS." set `priority`='$priority' where id=$id ");
			$this->addOption($id, 'priority');
		}

		$video = checkbox_value("video");
		$this->setVideo($id, $video);

		$url = checkbox_value("url");
		$this->setUrl($id, $url);
		
		return 1;
	}


	function deleteLanguageFields($lang_id) {

		global $ads_settings;
		global $db;
		$ad_fields = $db->getTableCSVFields(TABLE_ADS);
		$array_fields = explode(",", $ad_fields);
		if(in_array("description_$lang_id", $array_fields)) {
			$db->query("ALTER TABLE ".TABLE_ADS." drop `description_$lang_id`");
		}
		if(in_array("title_$lang_id", $array_fields)) {
			$db->query("ALTER TABLE ".TABLE_ADS." drop `title_$lang_id`");
		}

		global $languages;
		if(empty($languages)) $languages = common::getCachedObject("base_languages");
		if(count($languages)!=1) return 1;

		$def_id = languages::getDefault();
		if(!$def_id) return;
	
		// only one language! set unique description and title fields
		if(in_array("description_".$def_id, $array_fields)) {

			// transform "description_default_lang_id" field into "description"
			$db->query("ALTER TABLE ".TABLE_ADS." CHANGE `description_$def_id` `description` text NULL");

		}

		if(in_array("title_".$def_id, $array_fields)) {

			// transform "title_default_lang_id" field into "title"
			$db->query("ALTER TABLE ".TABLE_ADS." CHANGE `title_$def_id` `title` text NULL");

		}

		return 1;
	}


	function getNext($id, $categ) {

		global $db;
		if($categ) $where_str = "and category_id=$categ"; else $where_str="";
		$id = $db->fetchRow("select id from ".TABLE_ADS." where active=1 and id<$id ".$where_str." order by id desc limit 1");
		return $id;

	}

	function isNologinAd($id) {

		global $db;
		$user_id = $db->fetchRow("select user_id from ".TABLE_ADS." where `id` = $id ");
		if(!$user_id) return 1;
		return 0;
	}

	static function getVideo($id) {

		global $db;
		$video = $db->fetchRow("select `video` from ".TABLE_ADS." where `id` = $id ");
		return $video;

	}

	static function getUrl($id) {

		global $db;
		$video = $db->fetchRow("select `site_url` from ".TABLE_ADS." where `id` = $id ");
		return $video;

	}

	static function saveVideo($id, $video_code, $active) {

		global $db;
		if($active) listings::addOption($id, 'video', 1);
		$res = $db->query("update ".TABLE_ADS." set `video` = '$video_code' where `id` = $id ");
		return 1;

	}

	static function saveUrl($id, $url, $active) {

		global $db;
		if($active) listings::addOption($id, 'url', 1);
		$res = $db->query("update ".TABLE_ADS." set `site_url` = '$url' where `id` = $id ");
		return 1;

	}

	function incCat($ad_id, $nocond = 0, $cat_id = 0) {

		global $config_abs_path;
		require_once $config_abs_path."/classes/categories.php";
		global $db;
		if(!$nocond) {
			$already_active = $db->fetchRow("select `active` from ".TABLE_ADS." where id='$ad_id'");
			if($already_active) return;
		}
		if($cat_id == 0) $cat_id = $db->fetchRow("select `category_id` from ".TABLE_ADS." where id='$ad_id'");
		$res=$db->query("update ".TABLE_CATEGORIES." set `ads`=`ads`+1 where `id`='$cat_id'");
		$cats = new categories;
		$arr = $cats->catPathArray($cat_id, array());

		// increment locations ads number
		global $settings;
		if($settings['enable_locations'] && $settings['location_fields']) {
			global $config_abs_path;
			require_once $config_abs_path."/classes/locations.php";
			$this->incLocation($ad_id, $cat_id, 1);
		}

		foreach($arr as $parent) {
			if($parent['id']!=$cat_id) {
				$res=$db->query("update ".TABLE_CATEGORIES." set `ads`=`ads`+1 where `id`='".$parent['id']."'");

				// increment categories locations ads numbers
				if($settings['enable_locations'] && $settings['location_fields']) {
					$this->incLocation($ad_id, $parent['id']);
				}
			}
		}

		// do actions for "inc_cat"
		do_action("inc_cat", array($ad_id, $cat_id));

	}

	function decCat($ad_id, $nocond = 0, $cat_id = 0) {

		global $db;
		global $config_abs_path;
		require_once $config_abs_path."/classes/categories.php";
		if(!$nocond) {
			$already_inactive = $db->fetchRow("select `active` from ".TABLE_ADS." where id='$ad_id'");
			if($already_inactive==0) return;
		}
		if($cat_id == 0) $cat_id = $db->fetchRow("select `category_id` from ".TABLE_ADS." where id='$ad_id'");
		$res=$db->query("update ".TABLE_CATEGORIES." set `ads`=`ads`-1 where `id`='$cat_id'");
		$cats = new categories;
		$arr = $cats->catPathArray($cat_id, array());

		// decrement locations ads number
		global $settings;
		if($settings['enable_locations'] && $settings['location_fields']) {
			require_once $config_abs_path."/classes/locations.php";
			$this->decLocation($ad_id, $cat_id, 1);
		}

		foreach($arr as $parent) {
			if($parent['id']!=$cat_id) {
				$res=$db->query("update ".TABLE_CATEGORIES." set `ads`=`ads`-1 where `id`='".$parent['id']."'");

				// decrement categories locations ads numbers
				if($settings['enable_locations'] && $settings['location_fields']) {
					$this->decLocation($ad_id, $parent['id']);
				}
			}
		}


		// do actions for "inc_cat"
		do_action("dec_cat", array($ad_id, $cat_id));
	}


	static function getUserDetails($id, $user_id='') {

		// get user details
		$nologin=0;
		if(!$user_id) $user_id = listings::getUser($id);
		$user_details = array();
		global $settings;
		if($user_id) {

			$user_info = users::getUserInfo($user_id);
			$user_details['username'] = $user_info['username'];
			$user_details['email'] = $user_info['email'];
			$user_details['name'] = $user_info[$settings['contact_name_field']];
			//if(!$user_info['contact_name']) $user_details['name'] = $user_info['username'];
			$user_details['key'] = '';
			$user_details['nologin'] = 0;

		} else {

			$user_info = listings::getOwnerInfo($id);
			$user_details['username']='';
			$user_details['email'] = $user_info['mgm_email'];
			$user_details['name'] = $user_info['mgm_name'];
			$user_details['key'] = $user_info['activation'];
			$user_details['nologin'] = 1;

		}
		return $user_details;
	}

	static function makeDetailsLink($id, $key='') {

		global $seo_settings, $mail_settings;
		global $config_live_site;

		$nologin = 0;
		if($key) $nologin=1;

		$html_mails=$mail_settings['html_mails'];
		if($html_mails) $amp = "&amp;"; else $amp = "&";

		if($seo_settings['enable_mod_rewrite'] && !$nologin) {
			global $config_abs_path;
			$seo = new seo();
			$details_link = $seo->makeDetailsLink($id);
		}
		else { 
			
			$details_link=$config_live_site.'/details.php?id='.$id;
			if($nologin) $details_link.=$amp."key=".$key;

		}

		if($html_mails) $details_link = '<a href="'.$details_link.'">'.$details_link.'</a>';
		return $details_link;

	}

	function deleteCateg($categ) {

		global $db;
		$result=$db->fetchRowList("select id from ".TABLE_ADS." where category_id='$categ'");
		foreach($result as $row) $this->delete($row);

	}

	function deletePackage($pkg) {

		global $db;
		$result=$db->fetchRowList("select id from ".TABLE_ADS." where package_id='$pkg'");
		foreach($result as $row) $this->delete($row);

	}

	function deleteUser($user) {

		global $db;
		$result=$db->fetchRowList("select id from ".TABLE_ADS." where user_id='$user'");
		foreach($result as $row) $this->delete($row);

	}

	function setPackage($id,$pkg) {

		global $db;

		// change expire date
		$no_days = packages::getNoDays($pkg);
		if($no_days!=0) $expire=", `date_expires` = date_add(`date_added`, interval '$no_days' day)";
		else $expire=", `date_expires` = null";

		$res=$db->query("update ".TABLE_ADS." set `package_id`=$pkg $expire where `id`='$id'");
		return 1;

	}

	function setOptions($id, $pkg) {

		if(!$id) return;
		global $db;

		$pkg_options = $db->fetchAssoc("select * from ".TABLE_PACKAGES." where id='$pkg'");
		if(!$pkg_options) return;

		$db->query("update ".TABLE_ADS." set `featured` = '{$pkg_options['featured']}', `highlited`='{$pkg_options['highlited']}', `urgent`='{$pkg_options['urgent']}', `priority`='{$pkg_options['priority']}' where id='$id'"); 

		// delete all options
		$res_del=$db->query("delete from ".TABLE_OPTIONS." where `object_id`='$id' and `option`!='store'");

		// add video option if the case
		if($pkg_options['video']) listings::addOption($id, 'video', 1);

		// add url option if the case
		if($pkg_options['url']) listings::addOption($id, 'url', 1);
		
		return;

	}

	function setUserPackage($id,$pkg) {

		global $db;
		$res=$db->query("update ".TABLE_ADS." set `usr_pkg`=$pkg where `id`='$id'");
		
		$pkg_id = $db->fetchRow("select `package_id` from ".TABLE_USERS_PACKAGES." where `id`='$pkg' ");
		$res=$db->query("update ".TABLE_ADS." set `package_id`=$pkg_id where `id`='$id'");
		return 1;

	}


	function setCategory($id,$categ) {

		global $db;
		$this->decCat($id, 1);
		$res=$db->query("update ".TABLE_ADS." set `category_id`=$categ where `id`='$id'");
		$this->incCat($id, 1, $categ);
		return 1;

	}


	function setPriority($id,$pri) {

		global $db;
		$res=$db->query("update ".TABLE_ADS." set `priority`=$pri where `id`='$id'");
		return 1;

	}

	function setFeatured($id,$val) {

		global $db;
		$res=$db->query("update ".TABLE_ADS." set `featured`=$val where `id`='$id'");
		return 1;

	}

	function setHighlited($id,$val) {

		global $db;
		$res=$db->query("update ".TABLE_ADS." set `highlited`=$val where `id`='$id'");
		return 1;

	}

	function setUrgent($id,$val) {

		global $db;
		$res=$db->query("update ".TABLE_ADS." set `urgent`=$val where `id`='$id'");
		return 1;

	}

	function setVideo($id,$val) {

		global $db;

		if($val==0) $db->query("delete from ".TABLE_OPTIONS." where `object_id` = $id and `option` like 'video'");	// delete video option
		else $this->addOption($id, 'video');// add video option
		return 1;

	}

	function setUrl($id,$val) {

		global $db;

		if($val==0) $db->query("delete from ".TABLE_OPTIONS." where `object_id` = $id and `option` like 'url'");	// delete url option
		else $this->addOption($id, 'url');// add url option
		return 1;

	}

	static function getLastListings($no) {

		global $db;
		global $appearance_settings;
		$date_format=$appearance_settings["date_format"];

		global $crt_lang;
		global $ads_settings;
		$title_var ="`title`";
		if($ads_settings['translate_title_description']) {
			global $languages;
			if(empty($languages)) $languages = common::getCachedObject("base_languages");
			if(count($languages)>1) {
				$title_var = "`title_$crt_lang`";
			}
		}


		$sql = "select ".TABLE_ADS.".id, ".TABLE_ADS.".$title_var as `title`, ".TABLE_ADS.".user_id, date_format(`date_added`,'$date_format') as `date`, ".TABLE_USERS.".username as user, ".TABLE_USERS.".email, ".TABLE_ADS_EXTENSION.".mgm_email  from ".TABLE_ADS." left join ".TABLE_USERS." on ".TABLE_ADS.".user_id=".TABLE_USERS.".id left join ".TABLE_ADS_EXTENSION." on ".TABLE_ADS.".id = ".TABLE_ADS_EXTENSION.".id order by date_added desc limit ".$no;
		$array_listings=$db->fetchAssocList($sql);

		// get a substring for title
		$i = 0;
		foreach($array_listings as $l) {
			if(_strlen($l['title'])>20)
				$array_listings[$i]['title'] = _substr($l['title'], 20, "...");
			$i++;
		}

		return $array_listings;
	}


	function searchListing($id,$crt_usr='') {

		global $db;
		$array_listings=array();

		if($crt_usr) $where=" and `user_id`='$crt_usr'";
		else $where='';

		$res=$db->query("select id from ".TABLE_ADS." where id='$id'".$where);
		if($db->numRows($res)) {
 			$array_listings=$this->getListingsDetailed("where ".TABLE_ADS.".id=".$id);
		}

		return $array_listings;
	}

	function searchListings($post_array, $page,$ads_per_page,$order,$order_way) {

		$start=($page-1)*$ads_per_page;

		$found = 0; $where = '';
		$ext_join = ''; $join_users = '';

		foreach($post_array as $key=>$value) {
			if( !$key || $value=='' ) continue;
			
			switch($key) {
				case "id": 
				case "package_id": 
				case "active": 
				case "pending": 
				//case "featured": 
				case "highlited": 
				case "urgent": 
				case "user_id": 
					if($found) $where.=" and "; else $where = " where ";
					$where .= " ".TABLE_ADS.".`$key` = '$value' ";
					$found = 1;
				break;
				case "featured": 
					if($found) $where.=" and "; else $where = " where ";
					$where .= " ".TABLE_ADS.".`featured` > 0 ";
					$found = 1;
				break;
				case "category_id": 
					if($found) $where.=" and "; else $where = " where ";
					$categ = new categories;
					$s = "";
					$subcats = $categ->subcatList($value, $s);
					$where .= " ".TABLE_ADS.".`category_id` in ($subcats) ";
					$found = 1;
				break;
				case "inactive": 
					if($found) $where.=" and "; else $where = " where ";
					$where .= " ".TABLE_ADS.".`active` = '0' ";
					$found = 1;
				break;
				case "date_from": 
					if($found) $where.=" and "; else $where = " where ";
					$where .= " ".TABLE_ADS.".`date_added` >= '$value' ";
					$found = 1;
				break;

				case "date_to": 
					if($found) $where.=" and "; else $where = " where ";
					$where .= " ".TABLE_ADS.".`date_added` <= '$value' ";
					$found = 1;
				break;

				case "priorities": 
					if($found) $where.=" and "; else $where = " where ";
					$where .= " ".TABLE_ADS.".`priority` > 0 ";
					$found = 1;
				break;

				case "expired": 
					if($found) $where.=" and "; else $where = " where ";
					$timestamp = date("Y-m-d H:i:s");
					$where .= " (".TABLE_ADS.".`active`=0 and ".TABLE_ADS.".`date_expires` < '$timestamp' and ".TABLE_ADS.".`date_expires`!='0000-00-00 00:00:00') ";
					$found = 1;
				break;

				case "video": 
					if($found) $where.=" and "; else $where = " where ";
					$where .= " (".TABLE_OPTIONS.".`object_id` is not null and ".TABLE_OPTIONS.".`option` like 'video') ";
					$ext_join = " left join ".TABLE_OPTIONS." on ".TABLE_ADS.".id = ".TABLE_OPTIONS.".`object_id` ";
					$found = 1;
				break;

				case "url": 
					if($found) $where.=" and "; else $where = " where ";
					$where .= " (".TABLE_OPTIONS.".`object_id` is not null and ".TABLE_OPTIONS.".`option` like 'url') ";
					$ext_join = " left join ".TABLE_OPTIONS." on ".TABLE_ADS.".id = ".TABLE_OPTIONS.".`object_id` ";
					$found = 1;
				break;

				case "pe": 
					if($found) $where.=" and "; else $where = " where ";
					$where .= " ".TABLE_PENDING_EDITED.".`ad_id` is not null ";
					$ext_join = " left join ".TABLE_PENDING_EDITED." on ".TABLE_ADS.".id = ".TABLE_PENDING_EDITED.".`ad_id` ";
					$found = 1;
				break;

				case "auctions": 
					if($found) $where.=" and "; else $where = " where ";
					$where .= " ".TABLE_ADS.".`auction` = 1 ";
					$found = 1;
				break;

				case "keyword": 
					if($found) $where.=" and "; else $where = " where ";

					global $ads_settings;
					if($ads_settings['translate_title_description']) {
						global $languages;
						if(empty($languages)) $languages = common::getCachedObject("base_languages");
						$where_str = "";
						foreach($languages as $lang) {
							if($where_str) $where_str.=" or ";
							$where_str .= " `title_{$lang['id']}` like '%$value%' or `description_{$lang['id']}` like  '%$value%' ";
						}
					} else $where_str = TABLE_ADS.".`title` like '%$value%' or ".TABLE_ADS.".`description` like '%$value%'";

					$where .= " ( $where_str )";
					$found = 1;
				break;

				case "username": 
					if($found) $where.=" and "; else $where = " where ";
					$where .= " (".TABLE_ADS.".`user_id` and ".TABLE_USERS.".`username` like '%$value%')";
					$found = 1;
				break;

				case "email": 
					if($found) $where.=" and "; else $where = " where ";
					$ext_join = " left join ".TABLE_ADS_EXTENSION." on ".TABLE_ADS.".id = ".TABLE_ADS_EXTENSION.".`id` ";
					$where .= " (".TABLE_USERS.".`email` like '%$value%' or ".TABLE_ADS_EXTENSION.".`mgm_email` like '%$value%')";
					$found = 1;
				break;

				case "ip": 
					if($found) $where.=" and "; else $where = " where ";
					$ext_join = " left join ".TABLE_ADS_EXTENSION." on ".TABLE_ADS.".id = ".TABLE_ADS_EXTENSION.".`id` ";
					$where .= " (".TABLE_USERS.".`ip` like '$value' or ".TABLE_ADS_EXTENSION.".`ip` like '$value')";
					$found = 1;
				break;

				case "sold": 
					if($found) $where.=" and "; else $where = " where ";
					$where .= " `sold` = 1 ";
					$found = 1;
				break;

				case "rented": 
					if($found) $where.=" and "; else $where = " where ";
					$where .= " `rented` = 1 ";
					$found = 1;
				break;

			}
		}

		$no_listings = $this->getNoShortListings($where, $ext_join, 1);
		$this->setNoListings($no_listings);

		if($order) $order_by="order by ".$order; 
		else $order_by='';
		$result=$this->getListingsDetailed($ext_join.$where,$order_by,$order_way,$start,$ads_per_page);
		return $result;

	}

	static function getNoListings($crt_usr='') {

		global $db;
		$sql="select count(*) from ".TABLE_ADS;
		if($crt_usr) $sql.=' where user_id='.$crt_usr;
		$no=$db->fetchRow($sql);
		return $no;

	}

	static function getNoActiveListings($crt_usr='') {

		global $db;
		$sql="select count(*) from ".TABLE_ADS." where active";
		if($crt_usr) $sql.=' and user_id = '.$crt_usr;
		$no=$db->fetchRow($sql);
		return $no;
	}

	static function getNoInactiveListings($crt_usr='') {

		global $db;
		$sql="select count(*) from ".TABLE_ADS." where active=0";
		if($crt_usr) $sql.=' and user_id='.$crt_usr;
		$no=$db->fetchRow($sql);
		return $no;
	}

	static function getNoPendingListings($crt_usr='') {

		global $db;
		$sql="select count(*) from ".TABLE_ADS." where pending=1";
		if($crt_usr) $sql.=' and user_id='.$crt_usr;
		$no=$db->fetchRow($sql);
		return $no;
	}

	static function getNoPendingEditedListings($crt_usr='') {

		global $db;
		$sql="select count(*) from ".TABLE_ADS."  left join ".TABLE_PENDING_EDITED." on ".TABLE_ADS.".id = ".TABLE_PENDING_EDITED.".`ad_id` where ".TABLE_PENDING_EDITED.".`ad_id` is not null";
		if($crt_usr) $sql.=' and user_id='.$crt_usr;
		$no=$db->fetchRow($sql);
		return $no;
	}

	static function getNoUnfinishedListings($crt_usr='') {

		global $db;
		$usr_str='';
		if($crt_usr) $usr_str=' and user_id='.$crt_usr;
		$no=$db->fetchRow("select count(*) from ".TABLE_ADS." where user_approved=0".$usr_str);
		return $no;
	}

	static function getNoFeaturedListings($crt_usr='') {

		global $db;
		$sql="select count(*) from ".TABLE_ADS." where featured>0";
		if($crt_usr) $sql.=' and user_id='.$crt_usr;
		$no=$db->fetchRow($sql);
		return $no;
	}

	static function getNoHighlitedListings($crt_usr='') {

		global $db;
		$sql="select count(*) from ".TABLE_ADS." where highlited=1";
		if($crt_usr) $sql.=' and user_id='.$crt_usr;
		$no=$db->fetchRow($sql);

		return $no;
	}

	static function getNoUrgentListings($crt_usr='') {

		global $db;
		$sql="select count(*) from ".TABLE_ADS." where urgent=1";
		if($crt_usr) $sql.=' and user_id='.$crt_usr;
		$no=$db->fetchRow($sql);
		return $no;
	}

	static function getNoPrioritiesListings($crt_usr='') {

		global $db;
		$sql="select count(*) from ".TABLE_ADS." where priority>0";
		if($crt_usr) $sql.=' and user_id='.$crt_usr;
		$no=$db->fetchRow($sql);

		return $no;
	}

	static function getNoExpiredListings($crt_usr='') {

		global $db;
		$timestamp = date("Y-m-d H:i:s");

		$sql="select count(id) from ".TABLE_ADS." where active=0 and date_expires < '$timestamp' and date_expires!='0000-00-00 00:00:00' and date_expires is not null";
		if($crt_usr) $sql.=' and user_id='.$crt_usr;
		$no=$db->fetchRow($sql);
		return $no;
	}

	static function getNoVideoListings($crt_usr='') {

		global $db;
		$sql="select count(".TABLE_ADS.".id) from ".TABLE_ADS." left join ".TABLE_OPTIONS." on ".TABLE_ADS.".`id`=".TABLE_OPTIONS.".`object_id` where ".TABLE_OPTIONS.".`object_id` is not null and ".TABLE_OPTIONS.".`option` like 'video'";
		if($crt_usr) $sql.=' and user_id='.$crt_usr;
		$no=$db->fetchRow($sql);
		return $no;
	}

	static function getNoUrlListings($crt_usr='') {

		global $db;
		$sql="select count(".TABLE_ADS.".id) from ".TABLE_ADS." left join ".TABLE_OPTIONS." on ".TABLE_ADS.".`id`=".TABLE_OPTIONS.".`object_id` where ".TABLE_OPTIONS.".`object_id` is not null and ".TABLE_OPTIONS.".`option` like 'url'";
		if($crt_usr) $sql.=' and user_id='.$crt_usr;
		$no=$db->fetchRow($sql);
		return $no;
	}

	function getListingsDetailed($where,$order_by='',$order_way='',$general_row='',$ads_per_page=''){ 

		global $db;
		global $lng;
		global $appearance_settings;
		global $ads_settings, $seo_settings;

		$newLine="\r\n";
		$date_format=$appearance_settings["date_format"];
		global $crt_lang, $admin_side;

		global $is_mobile;
		$mobile_pic_substr="";
		if($is_mobile) $mobile_pic_substr="mobile_";

		$mlang=0;
		$mlang_vars ='';
		if($ads_settings['translate_title_description']) {
			global $languages;
			if(empty($languages)) $languages = common::getCachedObject("base_languages");
			if(count($languages)>1) {
				$mlang = 1;
				$mlang_vars = ",`title_$crt_lang` as `title`, `description_$crt_lang` as `description` ";
			}
		}

		if($where) $where.=" and "; else $where = " where ";
		$where.=" ( ".TABLE_ADS_PICTURES.".`id` is null or ".TABLE_ADS_PICTURES.".`order_no`<=1)";

		$join_users = '';
		if(strstr($where, TABLE_USERS)) 
			$join_users = " left join ".TABLE_USERS." on ".TABLE_ADS.".user_id=".TABLE_USERS.".id ";

		$join_slugs='';
		$str_slugs='';
		$where_slugs='';
		if($seo_settings['enable_mod_rewrite']) {
			$join_slugs="inner join ".TABLE_SLUGS." on ".TABLE_ADS.".id=".TABLE_SLUGS.".object_id";
			$str_slugs=", ".TABLE_SLUGS.".slug ";
			$where_slugs=" and ".TABLE_SLUGS.".`type`='listing'";
		}
	
			
		$timestamp = date("Y-m-d H:i:s");

		$sql = "select ".TABLE_ADS.".*$mlang_vars, ".TABLE_ADS.".id as adid, ".TABLE_ADS_PICTURES.".picture, ".TABLE_ADS_PICTURES.".folder, ".TABLE_ADS_PICTURES.".`id` as `picture_id`, date_format(".TABLE_ADS.".`date_added`,'$date_format') as date_nice, date_format(".TABLE_ADS.".`date_expires`,'$date_format') as date_expires_nice, UNIX_TIMESTAMP(".TABLE_ADS.".`date_added`) as `time_added`, (".TABLE_ADS.".date_expires<'$timestamp' and ".TABLE_ADS.".date_expires!='0000-00-00 00:00:00' and ".TABLE_ADS.".active=0) as expired $str_slugs
		from ".TABLE_ADS." 
		left join ".TABLE_ADS_PICTURES." on ".TABLE_ADS.".id=".TABLE_ADS_PICTURES.".ad_id "
		.$join_slugs.$join_users.$where.$where_slugs; 


		//$sql.=" group by ".TABLE_ADS.".id ";
		if($order_by) $sql.=" ".$order_by." ".$order_way;
		if($ads_per_page>0) $sql .= " limit ".$general_row.", ".$ads_per_page;

//		echo $sql;

		$arr = $db->fetchAssocList($sql);
		$i=0;
		$result=array();
//_print_r($arr);

		if($is_mobile) {
			global $mobile_settings;
			$nopic = $mobile_settings['mobile_nopic'];
		} else 
			$nopic=$ads_settings["nopic"];
		$currency_pos=$appearance_settings["currency_pos"];
		$price_extensions = $db->fetchRow("select `extensions` from ".TABLE_FIELDS." where `type` = 'price' limit 1");
		if($price_extensions) {
			$arr_ext=explode("|",$price_extensions);
			$price_decimals=$arr_ext[0];
			$price_point=$arr_ext[1];
			$price_separator=$arr_ext[2];
		} else {
			global $appearance_settings;
			$price_decimals = $appearance_settings['price_field_format_decimals'];
			$price_point = $appearance_settings['price_field_format_point'];
			$price_separator = $appearance_settings['price_field_format_separator'];
		}
		
		foreach ($arr as $row) {

			$result[$i]=$row;

			// clean slashes
			foreach($result[$i] as $key=>$value) { 
				if($key=="currency") continue;
				$result[$i][$key] = cleanHtml($result[$i][$key]);
			}

			// category
			$result[$i]['category'] = $db->fetchRow("select `name` from ".TABLE_CATEGORIES."_lang where id='".$row['category_id']."' and `lang_id`='$crt_lang'");

			// plan
			$result[$i]['package'] = $db->fetchRow("select `name` from ".TABLE_PACKAGES."_lang where id='".$row['package_id']."' and `lang_id`='$crt_lang'");

			// user
			if($row['user_id']) {
				$usr_res = $db->fetchAssoc("select `username`, `email`, `ip` from ".TABLE_USERS." where id='".$row['user_id']."'");
				$result[$i]['username'] = $usr_res['username'];
				$result[$i]['email'] = $usr_res['email'];
				$result[$i]['ip'] = $usr_res['ip'];
			}

			// pending plan
			$result[$i]['pending_package'] = $db->fetchRow("select pending from ".TABLE_USERS_PACKAGES." where id='{$row['usr_pkg']}'");

			// video option
			if($ads_settings['enable_video']) {
				$result[$i]['enable_video'] = $this->isVideoEnabled($row['id']);
			}

			// url option
			if($ads_settings['enable_url']) {
				$result[$i]['enable_url'] = $this->isUrlEnabled($row['id']);
			}

			// x days ago
			$result[$i]['date_nice'] = $this->makeTimeAgoDate($result[$i]['time_added'], $result[$i]['date_nice']);

			$result[$i]['id']=$result[$i]['adid'];

			// pending actions
			$result[$i]['pending_actions'] = $db->fetchAssocList("SELECT `".TABLE_ACTIONS."`.*, `".TABLE_PAYMENT_ACTIONS."`.completed from `".TABLE_ACTIONS."` left join `".TABLE_PAYMENT_ACTIONS."` on `".TABLE_PAYMENT_ACTIONS."`.id = `".TABLE_ACTIONS."`.`invoice` where (`object_id` = ".$row['id']." and `pending` = 1 and (`type` like 'featured' or `type` like 'highlited' or `type` like 'priority' or `type` like 'urgent' or `type` like 'video' or `type` like 'url' or `type` like 'newad' or `type` like 'renewad' or `type` like 'bump')) or (`object_id` = '".$row['usr_pkg']."' and `pending` = 1 and (`type` like 'newpkg' or `type` like 'renewpkg') ) order by `invoice` ,".TABLE_ACTIONS.".`date` desc");
			// show info about pending actions on over
			$result[$i]['pending_info'] = '';

			if($result[$i]['pending']) {
				$result[$i]['pending_info'] .= $lng['listings']['pending_ad'].'<br />';
			}
			foreach($result[$i]['pending_actions'] as $action) {
				if($action['type'] == "highlited") $result[$i]['pending_info'].=$lng['listings']['pending_highlited'].'<br />';
				//else if($action['type'] == "featured") $result[$i]['pending_info'].=$lng['listings']['pending_featured'].'<br />';
				else if($action['type'] == "urgent") $result[$i]['pending_info'].=$lng['listings']['pending_urgent'].'<br />';
				else if($action['type'] == "video") $result[$i]['pending_info'].=$lng['listings']['pending_video'].'<br />';
				else if($action['type'] == "url") $result[$i]['pending_info'].=$lng['listings']['pending_url'].'<br />';
				else if($action['type'] == "featured" && $action['extra']) { 
					$result[$i]['pending_info'].=$lng['listings']['pending_featured'].': ';
					//$result[$i]['pending_info'].= priorities::getNameByOrder($action['extra']).'<br />';
 					$no_days = $db->fetchRow("select `no_days` from ".TABLE_FEATURED_PLANS." where `id`={$action['extra']}");
					$result[$i]['pending_info'].= $no_days."&nbsp;".$lng['days']."<br/>";


				}
				else if($action['type'] == "priority" && $action['extra']) { 
					$result[$i]['pending_info'].=$lng['listings']['pending_priority'].': ';
					//$result[$i]['pending_info'].= priorities::getNameByOrder($action['extra']).'<br />';
 					$pri_name = $db->fetchRow("select `name` from ".TABLE_PRIORITIES."_lang LEFT JOIN ".TABLE_PRIORITIES." on ".TABLE_PRIORITIES.".`id` = ".TABLE_PRIORITIES."_lang.`id` where `order_no`={$action['extra']} and `lang_id`='$crt_lang'");
					$result[$i]['pending_info'].= $pri_name."<br/>";


				}
				else if($action['type'] == "newpkg" || $action['type'] == "renewpkg") $result[$i]['pending_info'].=$lng['listings']['pending_subscription'].'<br />';
				else if($action['type'] == "bump") $result[$i]['pending_info'].=$lng['listings']['pending_bump'].'<br />';
			}

			//$result[$i]['paid']=0;
			//if($result[$i]['amount']>0) $result[$i]['paid']=1;

			if($result[$i]['picture']) { 
				$str_folder = "";
				if($result[$i]['folder']) $str_folder = $result[$i]['folder']."/";
				$result[$i]['image']="images/listings/".$str_folder.$mobile_pic_substr."thmb/".$result[$i]['picture'];
				$result[$i]['bigImage']="images/listings/".$str_folder.$mobile_pic_substr."bigThmb/".$result[$i]['picture'];
				$result[$i]['image_id']=$result[$i]['picture_id'];
			}
			else {
				$result[$i]['image']="images/".$nopic;
				$result[$i]['image_id']=0;
			}

			if($result[$i]['price']>=0) {
			
				$result[$i]['price_formatted'] = format_price_field($result[$i]['price'], $price_decimals, $price_separator, $price_point);
				$result[$i]['price_curr'] = add_currency($result[$i]['price_formatted'], $result[$i]['currency']);
			} else {
				$result[$i]['price_formatted'] = '';
				$result[$i]['price_curr'] = '';
			}

			if($ads_settings['location_fields']) {

				$loc_fields = explode(",", $ads_settings['location_fields']);
				$result[$i]['location_str'] = '';
				$k = 0;
				foreach($loc_fields as $l) {

					if(!$l) continue;
					if($result[$i][$l]) {
						if($k) $result[$i]['location_str'].=", ";
						$result[$i]['location_str'].= $result[$i][$l];
						$k++;
					}
				}
			}

			if($ads_settings['enable_priorities'] && $result[$i]['priority']>0) $result[$i]['priority_name'] = $db->fetchRow("select `name` from ".TABLE_PRIORITIES."_lang LEFT JOIN ".TABLE_PRIORITIES." on ".TABLE_PRIORITIES.".`id` = ".TABLE_PRIORITIES."_lang.`id` where `order_no`={$result[$i]['priority']} and `lang_id`='$crt_lang'");

			if($ads_settings['enable_featured'] && $result[$i]['featured']>0 && $result[$i]['featured']!=100)
				$result[$i]['featured_no_days'] = $db->fetchRow("select `no_days` from ".TABLE_FEATURED_PLANS." where `id`={$result[$i]['featured']}");
			$result[$i]['stock']=sprintf("%04d", $result[$i]['adid']);

			if($mlang) {

				$default_title = '';
				foreach($languages as $l) {
					$lang_id = $l['id'];
					$result[$i][$lang_id] = array();
					$result[$i][$lang_id]['title'] = $result[$i]['title_'.$lang_id];
					if($l['default']==1) {
						$default_title = $result[$i]['title_'.$lang_id];
					}
				}

				if(!$result[$i]['title']) { 
					$result[$i]['title'] = $default_title;
					if(!$result[$i]['title']) { 
						foreach($languages as $l) 
						if($result[$i]['title_'.$l['id']]) { $result[$i]['title'] = $result[$i]['title_'.$l['id']]; break; }
					}
				}
			}

			if(!$result[$i]['user_id']) {

				$user_details = listings::getOwnerInfo($result[$i]['id']);
				$result[$i]['username'] = $user_details['mgm_email'];
				$result[$i]['email'] = $user_details['mgm_email'];
				$result[$i]['mgm_name'] = $user_details['mgm_name'];
				$result[$i]['ip'] = $user_details['ip'];
			}

			// check if ip is blocked
			if($result[$i]['ip'] && $admin_side) $result[$i]['blocked'] = blocked_ips::isBlocked($result[$i]['ip']);

			// auctions
			if($ads_settings['enable_auctions']) {

				global $config_abs_path;
				require_once $config_abs_path."/classes/auctions.php";
				$ac = new auctions;
				$result[$i]['auction'] = $ac->getAuctionForAd($result[$i]['id']);

			}

			// pending edited
			if($ads_settings['pending_edited'])
				$result[$i]['pending_edited'] = $db->fetchRow("select count(*) from ".TABLE_PENDING_EDITED." where `ad_id`='{$result[$i]['id']}'");

			// if not completed get the invoice id
			if(!$result[$i]['active'] && !$result[$i]['user_approved'] && !$result[$i]['expired']) {
				
				global $config_abs_path;
				require_once $config_abs_path."/classes/actions.php";
				$result[$i]['invoice'] = actions::getNotCompletedListingInvoice($result[$i]['id']);

			}

			$i++;

		}

		do_action("detailed_listings_result", array(&$result));

		return $result;
	}

	function moveAds($from, $to, $type) {

		global $db;
		if(!$from || !$to) return;

		if($type=="plan")
			$db->query("update ".TABLE_ADS." set `package_id` = $to where `package_id` = $from");
		else if($type=="categ") {
			$db->query("update ".TABLE_ADS." set `category_id` = $to where `category_id` = $from");
			$categ = new categories_config;
			$categ->Recount();
		}
		return 1;

	}


	function getDateExpires($id) {

		global $db;
		global $appearance_settings;
		$date_format=$appearance_settings["date_format"];

		$timestamp = date("Y-m-d H:i:s");

		$expires = $db->fetchAssoc("select `date_expires`, date_format(`date_expires`,'%e') as `day`, date_format(`date_expires`,'%c') as `month`, date_format(`date_expires`,'%Y') as `year`, (`date_expires`<'$timestamp' and `date_expires`!='0000-00-00 00:00:00' and active=0) as expired  from ".TABLE_ADS." where `id`=$id");

		return $expires;

	}

	function changeExpireDate($id) {

		global $db;
		global $appearance_settings;

		$was_expired = $this->isExpired($id);
		$crt_date_expires = $db->fetchRow("select date_format(`date_expires`, '%Y-%c-%e') from ".TABLE_ADS." where `id`=$id");

		if($_POST['expires']==1) $expires = 1; else $expires = 0;

		if($expires) {

			$day = escape($_POST['expires_day']);
			$month = escape($_POST['expires_month']);
			$year = escape($_POST['expires_year']);
			$new_date_expires = "$year-$month-$day";
		} else $new_date_expires='';

		if($crt_date_expires!=$new_date_expires) {

			$db->query("update ".TABLE_ADS." set `date_expires` = '$new_date_expires' where id=$id ");

		}

		// check if it was expired and it needs to be activated
		if($was_expired) {

			$timestamp = date("Y-m-d H:i:s");

			if($expires)
				$renew = $db->fetchRow("select '$new_date_expires'<'$timestamp' as renew");
			if(!$expires || $renew) $res=$db->query("update ".TABLE_ADS." set `active`=1, `user_approved`=1 where `id`='$id'");
		}

		return 1;
	}

	function checkLanguageFields() {

		global $db;
		global $ads_settings;
		$add_translation = $ads_settings['translate_title_description'];

		global $languages;
		if(empty($languages)) $languages = common::getCachedObject("base_languages");
		if(count($languages)==1) $add_translation=0;

		$ad_fields = $db->getTableCSVFields(TABLE_ADS);
		$array_fields = explode(",", $ad_fields);
		$def_id = languages::getDefault();
		if(!$def_id) return;

		if($add_translation) {

			// check if "description" field is among listing fields	
			if(in_array("description", $array_fields) && $add_translation) {
				// transform "description" field into "description_default_lang_id"
				$db->query("ALTER TABLE ".TABLE_ADS." CHANGE `description` `description_$def_id` text NULL");

			}

			// check if "title" field is among listing fields	
			if(in_array("title", $array_fields) && $add_translation) {

				// transform "title" field into "title_default_lang_id"
				$db->query("ALTER TABLE ".TABLE_ADS." CHANGE `title` `title_$def_id` varchar(150) NULL");

			}
		}
		else {
			if(in_array("description_".$def_id, $array_fields)) {

				// transform "description_default_lang_id" field into "description"
				$db->query("ALTER TABLE ".TABLE_ADS." CHANGE `description_$def_id` `description` text NULL");

			}

			if(in_array("title_".$def_id, $array_fields)) {

				// transform "title_default_lang_id" field into "title"
				$db->query("ALTER TABLE ".TABLE_ADS." CHANGE `title_$def_id` `title` varchar(150) NULL");

			}
	
			// if translate title and description==1, set it 0
			settings_config::disableTranslateTitleDescription();

		}

		foreach($languages as $l) {

			$lid = $l['id'];
			if($lid == $def_id) continue;
			if(!$lid) continue;

			if($add_translation && !in_array("description_$lid", $array_fields)) $db->query("ALTER TABLE ".TABLE_ADS." add `description_$lid` text NULL");
			else if(!$add_translation && in_array("description_$lid", $array_fields))  $db->query("ALTER TABLE ".TABLE_ADS." drop `description_$lid`");

			if($add_translation && !in_array("title_$lid", $array_fields)) $db->query("ALTER TABLE ".TABLE_ADS." add `title_$lid` text NULL");
			else if(!$add_translation && in_array("title_$lid", $array_fields))  $db->query("ALTER TABLE ".TABLE_ADS." drop `title_$lid`");

		}

	}


/**
 * 
 * @param  
 */
function getAdvSearch($post_array,$page, $ads_per_page, $extra_where='') {

	global $db;
	global $ads_settings, $settings, $config_abs_path;
	$str_radius = ''; $str_zip =''; $join_zip=''; $join_cat='';

	global $seo;
	global $keyword_name;

	$fields = explode(",",$ads_settings['search_in_fields']);
	if(in_array("category_name", $fields)) $join_cat = " inner join ".TABLE_CATEGORIES."_lang on ".TABLE_CATEGORIES."_lang.id = ".TABLE_ADS.".category_id ";

	// if translate title and description
	if($ads_settings['translate_title_description']) {

		$fields_new = array();
		foreach($fields as $f)
			if($f!="title" && $f!="description") array_push($fields_new, $f);

		if( in_array("title", $fields) ) {

			global $languages;
			foreach($languages as $lang)	
				array_push($fields_new, "title_".$lang['id']);

		}

		if( in_array("description", $fields) ) {

			global $languages;
			foreach($languages as $lang)	
				array_push($fields_new, "description_".$lang['id']);

		}

		$fields = $fields_new;

	}

	$custom_fields = common::getCachedObject("base_search_fields");
	
	require_once($config_abs_path.'/classes/fields.php');
	$cf = new fields('cf');
	$custom_fields = $cf->getSearchFieldsDepValues($custom_fields, $post_array);

	global $languages;
	if(empty($languages)) $languages = common::getCachedObject("base_languages");
	$no_lang = count($languages);

	$set = array();

	global $crt_lang;
	global $mbyte_characters_search;
	$el_fields = array("menu", "radio", "radio_group", "checkbox_group", "multiselect");

	//if($settings.enable_locations)
	$location_fields = explode(",", $ads_settings['search_location_fields']);

	// if order by priority use the index priority_2
	/*if($ads_settings['enable_priorities'])  
		$this->use_index="active_4";
	else*/
	if(isset($post_array['category']) && $post_array['category']) $this->use_index="active_2";

	$where=' where '.TABLE_ADS.'.active=1 '.$extra_where;
	$where_array = array();
	
	foreach ($post_array as $key=>$val) {
		if($val=='') continue;
		//if(!isset($custom_fields[$crt_lang][$key])) continue;

		switch($key) {

			case "id":
				$where.=' and '.TABLE_ADS.'.id = "'.$val.'"';
				break;

			case "category":
				if(is_numeric($val))
					$str=' and '.TABLE_ADS.'.category_id = '.$val;
				else 
					$str=' and '.TABLE_ADS.'.category_id in ('.$val.')';
				$where.=$str;	
				$where_array['category_id'] = $str;
				break;

			case "location":
				
				//if location autosuggest, more accurate location info can be in address component fields
				// in this case don't use this value
				if($ads_settings['enable_location_autosuggest']) { 
					$locf = 0;
					foreach($ads_settings['address_components_fields'] as $f) {
						if(isset($post_array[$f]) && $post_array[$f]) $locf++;
					}
					
					if($locf) break;
				}
				
				// if a distance search discard it
				if($ads_settings['enable_distance_search'] && $post_array['dist'] && $post_array['lat'] && $post_array['long']) break;
				
				if(!count($location_fields)) break;
				
				$a = 0;
				$str=' and ( ';
				foreach($location_fields as $loc) {
					if($a) $str.=' or ';
					$str.=' ('.TABLE_ADS.'.'.$loc.' like "'.$val.'" or '.TABLE_ADS.'.'.$loc.' like "all" )';
					$a++;
				}
				$str.=' )';
				
				$where.=$str;
				$where_array['location'] = $str;
				
				break;
			case "dist":
	
				if(!$ads_settings['enable_distance_search'] || !$val || !$post_array['lat'] || !$post_array['long']) break;
				
				$MIN_LAT = -90;   // -PI/2
				$MAX_LAT = 90;    //  PI/2
				$MIN_LON = -180;  // -PI
				$MAX_LON = 180;   //  PI

				$radLat =  deg2rad($post_array['lat']);
				$radLon = deg2rad($post_array['long']);
				$degLat = $post_array['lat'];
				$degLon = $post_array['long'];
				
				// angular radius
				$distance = $val;
				if($ads_settings['ds_measuring_unit']=="miles") $distance = $distance*1.609;

				$earthrad = 6371.01;
				if($ads_settings['ds_measuring_unit']=="miles") $earthrad = 3958.762079;
				
				$angular = ($distance)/$earthrad;

				$minLat = $radLat - $angular;
				$maxLat = $radLat + $angular;
				$degMinLat = rad2deg($minLat);
				$degMaxLat = rad2deg($maxLat);

				$minLon = 0;
				$maxLon = 0;
				if ($minLat > $MIN_LAT && $maxLat < $MAX_LAT) {

					$deltaLon= asin(sin($angular)/cos($radLat));
				
					$minLon = $radLon - $deltaLon;
					if ($minLon < $MIN_LON) $minLon += 2 * pi();
					$maxLon = $radLon + $deltaLon;
					if ($maxLon > $MAX_LON) $maxLon -= 2 * pi();
					
				} else {
		
					// a pole is within the distance
					$minLat = max($minLat, $MIN_LAT);
					$maxLat = min($maxLat, $MAX_LAT);
					$minLon = $MIN_LON;
					$maxLon= $MAX_LON;
				}
				
				$where.=" and (lat >= ".rad2deg($minLat)." AND lat <= ".rad2deg($maxLat).") AND (lng >= ".rad2deg($minLon)." AND lng <= ".rad2deg($maxLon).")
				and acos(sin($radLat) * sin(radians(lat)) + cos($radLat) * cos(radians(lat)) * cos(radians(lng) - ($radLon))) <= $angular ";
				
				break;
				
			case $keyword_name:

				if(!count($fields)) break; // if no fields to search in

				// to lowercase
				$val = _strtolower($val);

				// remove unwanted characters
				global $appearance_settings;
				if(isUnicodePropertiesFriendly() && strtolower($appearance_settings['charset'])=="utf-8")  {

					// \p{L} - letters
					// \p{N} - numbers
					// \p{Pd} - any kind of hyphen or dash.
					// \p{Zs} - a whitespace character that is invisible, but does take up space.
					// \p{M} - a character intended to be combined with another character (e.g. accents, umlauts, enclosing boxes, etc.).
					$val = preg_replace('#[^\p{L}\p{N}\p{Pd}\p{Zs}\p{M}]+#u','',$val);	
					$search_array = preg_split('/[^\p{L}\p{N}-]+/u', $val);

				}
				else {

					$pattern = array("+",",",".","-","'","\"","&","!","?",":",";","#","~","=","/","$","£","^","(",")","_","<",">");
					$val = str_replace($pattern, ' ', $val);
					$search_array = explode(" ", $val);

				}


				$t = 0;
				foreach($search_array as $s) {
					if(!$search_array[$t]) { array_splice($search_array, $t, 1); continue;  }
					$t++;
				}

				$no_words = count($search_array);
				if(!$no_words) break;

				$str=" and (";
				$k=0;

				if($ads_settings['search_type']=="exact") {

					foreach($fields as $f) {
						$stable = TABLE_ADS;
						if($f=="category_name") { $stable=TABLE_CATEGORIES."_lang"; $f = "name"; }
						if($k) $str.=" or ";
						if($mbyte_characters_search)
							$str.=" lower(".$stable.".$f) like '%".$val."%' ";
						else 
							$str.=" lower(".$stable.".$f) REGEXP '[[:<:]]".$val."[[:>:]]' ";
						$k++;
					}

				} // if exact search

				elseif($ads_settings['search_type']=="partial") {

					foreach($fields as $f) {
						$stable = TABLE_ADS;
						if($f=="category_name") { $stable=TABLE_CATEGORIES."_lang"; $f = "name"; }
						if($k) $str.=" or ";
						$str.=" lower(".$stable.".$f) like '%".$val."%' ";
						$k++;
					}

				} // if exact search
				else {

					if($ads_settings['search_type']=="any") {
						$op1="or";
						$op2="or";
					} else 
					if($ads_settings['search_type']=="all") {
						$op1="or";
						$op2="and";
					}
					foreach($fields as $f) {

						$stable = TABLE_ADS;
						if($f=="category_name") { $stable=TABLE_CATEGORIES."_lang"; $f = "name"; }

						if($k) $str.=" $op1";
						$str.=" (";
						$j=0; // no of real words
						for($i=$no_words; $i>0;$i--) {
							$w=trim($search_array[$i-1]);
							if($j) $str.=" $op2 ";
							if($w!='') {
								if($mbyte_characters_search)
									$str.=" lower(".$stable.".$f) like '%".$w."%' ";
								else
									$str.=" lower(".$stable.".$f) REGEXP '[[:<:]]".$w."[[:>:]]' ";
								$j++;
							}
						}
						$k++;// no for fields
						$str.=")";
					}
				} // end if not exact

				$str.=" )";
				$where.=$str;
				$where_array[$keyword_name] = $str;

				break;
			case "user_id":
				$str=' and '.TABLE_ADS.'.user_id like "'.$val.'"';
				$where.=$str;
				$where_array['user_id'] = $str;
				break;

			case "price_low":
			case "price_high":
			
				global $modules_array;
			
				if(isset($set['price']) && $set['price']) break;

				$price_field='price';
				if(in_array("multicurrency", $modules_array)) {
				
					$price_field='unit_price';
					$mc = new multicurrency();
					$mc_default_currency = $mc->getDefault();
					$ratios = $mc->getRatios();

					if(strtolower($post_array['currency']) != strtolower($mc_default_currency)) {
					
						// convert prices to correct price unit
						if($post_array['price_low']) {
					
							$post_array['price_low'] = $post_array['price_low']*$ratios[$post_array['currency']];

						} // end if if($post_array['price_low'])
					
						if($post_array['price_high']) {
					
							$post_array['price_high'] = $post_array['price_high']*$ratios[$post_array['currency']];
	
						} // end if if($post_array['price_low'])
				
					}
				}	

				$str='';
				if($post_array['price_low']!='' && $post_array['price_high']!='') { 
					if(is_numeric($post_array['price_low']) && is_numeric($post_array['price_high']))
					$str.=' and '.TABLE_ADS.'.'.$price_field.' between '.$post_array['price_low'].' and '.$post_array['price_high'].'';
				}
				else
				if($post_array['price_low']!='' && $post_array['price_high']=='') { 
					if(is_numeric($post_array['price_low']))
						$str.=' and '.TABLE_ADS.'.'.$price_field.' >= '.$post_array['price_low'];
				}
				else
				if($post_array['price_high']!='' && $post_array['price_low']=='') { 
					if(is_numeric($post_array['price_high']))
						$str.=' and '.TABLE_ADS.'.'.$price_field.' <= '.$post_array['price_high'];
				}
				$set['price'] = 1;
				$where.=$str;
				$where_array['price'] = $str;

				break;
			case "currency":
				global $modules_array;
			
				if(in_array("multicurrency", $modules_array)) break;
				
				$str=' and '.TABLE_ADS.'.currency like "'.$val.'"';
				$where.=$str;
				$where_array['currency'] = $str;
				
				break;

			case "with_pic":
				$str=' and '.TABLE_ADS_PICTURES.'.id is not null ';
				$where.=$str;
				$where_array['with_pic'] = $str;
				break;
			case "with_auction":
				if(!$ads_settings['enable_auctions']) break;
				$str=' and '.TABLE_ADS.'.auction = 1 ';
				$where.=$str;
				$where_array['with_auction'] = $str;
				break;
			case "zip":

				if($ads_settings['enable_location_autosuggest'] && in_array( "zip", $ads_settings['address_components_fields'])) {
					break;
				}

				// if areasearch is enabled
				global $modules_array;
				if( $post_array['zip'] ) {

				$str='';
				if( in_array("areasearch",$modules_array) && isset($post_array['area']) && is_numeric($post_array['area']) && $post_array['area'] > 0 ) { // zip area search

					$search_by_area = 1;
					global $config_table_prefix;
					$zip_loc = new areasearch();
					$zip_settings = $zip_loc->getSettings();
					if($zip_settings['um']=="miles") $radius = $zip_loc->miles_to_km($post_array['area']);
					else $radius = $post_array['area'];
					$coord = $zip_loc->getCoord($post_array['zip']);
					//_print_r($coord);
					if($coord!=0 && $coord['lat'] && $coord['lon']) {
					
						$str_zip = ', ((POW((69.1*('.$config_table_prefix.'zipcodes.lon-('.$coord['lon'].'))*cos('.$coord['lat'].'/57.3)),"2")+POW((69.1*('.$config_table_prefix.'zipcodes.lat-('.$coord['lat'].'))),"2"))/1.609)  as distance ';
						$str.=' and (POW((69.1*('.$config_table_prefix.'zipcodes.lon-('.$coord['lon'].'))*cos('.$coord['lat'].'/57.3)),"2")+POW((69.1*('.$config_table_prefix.'zipcodes.lat-('.$coord['lat'].'))),"2"))<('.$radius.'*'.$radius.')';

						$join_zip=' left join '.$config_table_prefix.'zipcodes on '.$config_table_prefix.'zipcodes.zipcode='.TABLE_ADS.'.zip';
					} else { // it is not a valid zip, search just the exact match for zip
						$str.=' and ('.TABLE_ADS.'.zip like "'.$post_array['zip'].'" )';
					}

				} else { // normal zip search
					//$where.=' and ('.TABLE_ADS.'.'.$key.' like "'.$val.'" )';
					$str.=' and replace('.TABLE_ADS.'.'.$key.', " ", "") = replace("'.$val.'", " ", "") ';
				}
				$where.=$str;
				$where_array['zip'] = $str;

				} // end if( $post_array['zip'] )
				break;
			case "account_type":
				if(strtolower($val)=="private") 
					$str=' and ('.TABLE_ADS.'.user_id=0 or '.TABLE_USERS.'.`group` not in ('.$ads_settings['prof_groups'].') )';
				else if(strtolower($val)=="professional")
					$str=' and '.TABLE_USERS.'.`group` in ('.$ads_settings['prof_groups'].')';
				$where.=$str;
				$where_array['account_type'] = $str;
				break;
			case "area":
			case "page":
			case "order":
			case "order_way":
			case "search_x":
			case "search_y":
			case "Search":
			case "search":
			case "Submit_x":
			case "Submit_y":
			case "Submit":
			case "x":
			case "y":
				break;
			default:

				$str='';
				if($ads_settings['enable_location_autosuggest'] && in_array( $key, $ads_settings['address_components_fields'])  && $post_array['dist']) {
					break;
				}

				// check if a module type
				global $default_fields_types;

				if(isset($custom_fields[$crt_lang][$key]['type']) && $custom_fields[$crt_lang][$key]['type'] && !in_array($custom_fields[$crt_lang][$key]['type'], $default_fields_types)) {

					$new_type = $custom_fields[$crt_lang][$key]['type'];
					$custom_obj = new $new_type;
					$str .= " and ".$custom_obj->getAdvSearch(TABLE_ADS, $val);
					break;

				}

				if(isset($custom_fields[$crt_lang][$key]['search_type']) && $custom_fields[$crt_lang][$key]['search_type']=="interval") {

					if($custom_fields[$crt_lang][$key]['type']=="date") $sep = '"'; else $sep='';

					// remove _low or _high from the end
					$skey = preg_replace("/_low$/", "", $key);
					$skey = preg_replace("/_high$/", "", $skey);

					if(isset($set[$skey]) && $set[$skey]) break;
					if( ( isset($post_array[$skey."_low"]) && $post_array[$skey."_low"]!='' && is_numeric($post_array[$skey."_low"])) && ( isset($post_array[$skey."_high"]) && $post_array[$skey."_high"]!='' && is_numeric($post_array[$skey."_high"]))) $str.=' and '.TABLE_ADS.'.'.$skey.' between '.$sep.$post_array[$skey."_low"].$sep.' and '.$sep.$post_array[$skey."_high"].$sep.'';
					else
					if( (isset($post_array[$skey."_low"]) && $post_array[$skey."_low"]!='' && is_numeric($post_array[$skey."_low"])) && ( !isset($post_array[$skey."_high"]) || $post_array[$skey."_high"]=='')) $str.=' and '.TABLE_ADS.'.'.$skey.' >= '.$sep.$post_array[$skey."_low"].$sep;
					else
					if( ( isset($post_array[$skey."_high"]) && $post_array[$skey."_high"]!='' && is_numeric($post_array[$skey."_high"])) && ( !isset($post_array[$skey."_low"]) || $post_array[$skey."_low"]=='')) $str.=' and '.TABLE_ADS.'.'.$skey.' <= '.$sep.$post_array[$skey."_high"].$sep;
					$set[$skey] = 1;

				} 
				else { 

					// if multiple languages add to search translated elements
					$q_translated = '';

					if(isset($custom_fields[$crt_lang][$key]['type']) && $custom_fields[$crt_lang][$key]['type'] && $no_lang>1 ) {

						if(in_array($custom_fields[$crt_lang][$key]['type'], $el_fields)) {

						$crt_lang_elem = $custom_fields[$crt_lang][$key]['elements'];

						$crt_lang_arr = explode("|", $crt_lang_elem);
						$index = -1;
						$i=0;

						foreach($crt_lang_arr as $el) {
							if($el == $val) { $index = $i; break; }
							$i++;
						}

						if($index!=-1) {

						// translate in all languages except current lang
						$arr_translations = array();
						foreach($languages as $l) {
							if($l['id'] == $crt_lang) continue;
							$alt_lang_elem = $custom_fields[$l['id']][$key]['elements'];

							$alt_lang_arr = explode("|", $alt_lang_elem);
							$alt_val = $alt_lang_arr[$index];

							if(!in_array(strtolower($alt_val), $arr_translations) && strtolower($alt_val)!=strtolower($val)) array_push($arr_translations, strtolower($alt_val));
						}

						// add to query
						foreach($arr_translations as $tr) {

							if($custom_fields[$crt_lang][$key]['search_type']=="keyword")
								$q_translated.=' or '.TABLE_ADS.'.'.$key.' like "%'.$tr.'%" ';
							else { 
								if(in_array($custom_fields[$crt_lang][$key]['type'], array("multiselect", "radio", "radio_group"))) {
									if($mbyte_characters_search)
									$q_translated.=" or ".TABLE_ADS.".".$key." like '%$tr%'";
									else
									$q_translated.=" or ".TABLE_ADS.".".$key." REGEXP '\[\[:<:\]\]$tr\[\[:>:\]\]'";
								}
								else
								$q_translated.=' or '.TABLE_ADS.'.'.$key.' like "'.$tr.'" ';
							}
						}
						}//if($index!=-1)
						} // end if(in_array($custom_fields[$crt_lang][$key]['type'], $el_fields))

						else if($custom_fields[$crt_lang][$key]['type']=="depending") {
						    // get the id for the value in depending table
						    $dep = new depending_fields;

						$arr_translations = array();

						// translate in all languages except current lang
						foreach($languages as $l) {

							if($l==$crt_lang) continue;
						        $alt_val = $dep->translateField( $custom_fields[$crt_lang][$key]['dep_id'], $key, $crt_lang, $l['id'], $val );
							if(!in_array(strtolower($alt_val), $arr_translations) && strtolower($alt_val)!=strtolower($val)) array_push($arr_translations, strtolower($alt_val));

						}

						// add to query
						foreach($arr_translations as $tr) {
							if($custom_fields[$crt_lang][$key]['search_type']=="keyword")
							    $q_translated.=' or '.TABLE_ADS.'.'.$key.' like "%'.$tr.'%" ';
							else { 
								$q_translated.=' or '.TABLE_ADS.'.'.$key.' like "'.$tr.'" ';
							}
						}

						} // end if depending
					}
					// end if multiple languages add to search translated elements

					if( isset($custom_fields[$crt_lang][$key]['search_type'])) {

//						if(in_array($custom_fields[$crt_lang][$key]['type'], array("multiselect", "radio", "radio_group")) && $custom_fields[$crt_lang][$key]['search_type']=="default") {
						if(in_array($custom_fields[$crt_lang][$key]['type'], array("multiselect", "radio_group")) && $custom_fields[$crt_lang][$key]['search_type']=="default") {

							if($mbyte_characters_search)
								$str.=" and (".TABLE_ADS.".".$key." like '%$val%' ".$q_translated.")";
							else
								$str.=" and (".TABLE_ADS.".".$key." REGEXP '\[\[:<:\]\]$val\[\[:>:\]\]' ".$q_translated.")";

						} else {

						$compare = 'like';
						if($custom_fields[$crt_lang][$key]['is_numeric'] || $custom_fields[$crt_lang][$key]['type']=="checkbox") 
							$compare = '=';
						if($custom_fields[$crt_lang][$key]['search_type']=="keyword")
							$str.=' and ( '.TABLE_ADS.'.'.$key.' like "%'.$val.'%" '.$q_translated.')';
						else if($custom_fields[$crt_lang][$key]['search_type']=="default") {
							$all_str="";
							if(in_array($custom_fields[$crt_lang][$key]['type'], array("menu", "depending")) && $custom_fields[$crt_lang][$key]['all_val']==1)
								$all_str = ' or '.TABLE_ADS.'.'.$key.' like "all" ';
							
							$val_array = explode("|", $val);
							
							$str_val="(";
							$j=0;
							foreach($val_array as $v) {
								if(!$v) continue;
								if($j) $str_val.=" or ";
								$str_val.=TABLE_ADS.".".$key." ".$compare." '".$v."'";
								$j=1;
							}
							$str_val.=")";
							
							$str.=" and ( $str_val $q_translated $all_str )";
						}
						} // else if multiselect field
					}
				}
				
				$where.=$str;
				$where_array[$key] = $str;
				
				break;
		} 

	}
//echo $where;
	$pri_ord = "";
	if($ads_settings['prioritize_featured']) $pri_ord = " `featured` desc, ";
	if($ads_settings['enable_priorities']) $pri_ord = " `priority` desc, ";

	if(isset($post_array['order']) && $post_array['order']) { 
		$order_by = " order by $pri_ord ".$post_array['order'];
		$order_by = " order by ".$post_array['order']; 
	}
	else $order_by=" order by $pri_ord date_added";

	if(isset($post_array['order_way']) && $post_array['order_way']) $order_way = $post_array['order_way']; 
	else $order_way = " desc";

	$start=($page-1)*$ads_per_page;

	// make the query without the location string
	$no_listings = $this->getNoShortListings($where, $join_zip, $join_cat);
	$this->setNoListings($no_listings);
	$this->setWhere($where);
	$this->setWhereArray($where_array);

	// $post_array already contains location fields
/*	$locations_str="";
	if($settings['enable_locations']) {
		$locations_str = locations::makeQueryStr();
		if(!$where) $locations_str = " where ".substr($locations_str, 4);
		$where .=$locations_str;
	}*/

	if(isset($post_array[$keyword_name]) && $post_array[$keyword_name]) $keyword = $post_array[$keyword_name]; else $keyword = '';

	$result=$this->getShortListings($where,$order_by,$order_way,$start,$ads_per_page, $keyword, $str_zip, $join_zip, $join_cat);
	return $result;

}

	function setFields($fields) {

		$this->fields = $fields;

	}

	function getFields() {

		return $this->fields;

	}

	function setUserFields($user_fields) {

		$this->user_fields = $user_fields;

	}

	function getUserFields() {

		return $this->user_fields;

	}

	function Block($id) {

		global $db;
		global $config_demo;
		if($config_demo==1) return;

		$user_id = listings::getUser($id);
		if($user_id) $ip = users::getIp($user_id);
		else { $ip = $db->fetchRow("select `ip` from `".TABLE_ADS_EXTENSION."` where `id` = '$id'"); }

		if(!$ip) return;

		$bi = new blocked_ips();
		$bi->add($ip, "Blocked listing");


	}

	function Unblock($id) {

		global $db;
		global $config_demo;
		if($config_demo==1) return;

		$user_id = listings::getUser($id);
		if($user_id) $ip = users::getIp($user_id);
		else { $ip = $db->fetchRow("select `ip` from `".TABLE_ADS_EXTENSION."` where `id` = '$id'"); }

		if(!$ip) return;

		$res=$db->query('delete from '.TABLE_BLOCKED_IPS.' where ip like "'.$ip.'"');

	}

	function incLocation($ad_id, $cat_id, $inc = 0) {

		global $db;
		global $settings;
		global $languages;

		$fields = $settings['location_fields'];
		$fields_arr = explode(",", $fields);
		$fields = str_replace(" ", "", $fields);
		$fields = str_replace(",", "`,`", $fields);
		$ad_arr = $db->fetchAssoc("select `$fields` from ".TABLE_ADS." where `id` = '$ad_id'");
		foreach($fields_arr as $f) {
			if(!$f) continue;
			$val = cleanStr($ad_arr[$f]);
			if($inc && $val) $this->incForField($ad_id, $f, $val);
			$this->incCategoryField($ad_id, $cat_id, $f, $val);

			// if multilanguage, increment for the translated location name
			if(count($languages>1)) {
				$l = new locations();
				$translated_locations = $l->translateLocation ($f, $val);
				foreach($translated_locations as $tr)
					$this->incCategoryField($ad_id, $cat_id, $f, $tr);
			}
		}

	}

	// increments number of ads for a custom field value
	function incForField($ad_id, $field, $val) {

		global $db;
		$val = escape($val);
		if(!$field || !$val) return;
		$no = $db->fetchRow("select count(*) from ".TABLE_LOCATION_NO_ADS." where `field` like '$field' and `val` like '$val'");
		if(!$no) 
			$db->query("insert into ".TABLE_LOCATION_NO_ADS." set `field` = '$field', `val` = '$val', `no` = 1");
		else 
			$db->query("update ".TABLE_LOCATION_NO_ADS." set `no` = `no`+1 where `field` = '$field' and `val` = '$val'");
		
	}

	// increments number of ads for a category and a custom field value
	function incCategoryField($ad_id, $category_id, $field, $val) {

		global $db;
		$val = escape($val);
		if(!$field || !$val) return;
		$no = $db->fetchRow("select count(*) from ".TABLE_CATEGORIES_NO_ADS." where `category_id`='$category_id' and `field` like '$field' and `val` like '$val'");
		if(!$no) 
			$db->query("insert into ".TABLE_CATEGORIES_NO_ADS." set `category_id`= '$category_id', `field` = '$field', `val` = '$val', `no` = 1");
		else 
			$db->query("update ".TABLE_CATEGORIES_NO_ADS." set `no` = `no`+1 where `category_id`='$category_id' and `field` = '$field' and `val` = '$val'");
		
	}

	function decLocation($ad_id, $cat_id, $dec = 0) {

		global $db;
		global $settings;
		global $languages;

		$fields = $settings['location_fields'];
		$fields_arr = explode(",", $fields);
		$fields = str_replace(" ", "", $fields);
		$fields = str_replace(",", "`,`", $fields);
		$ad_arr = $db->fetchAssoc("select `$fields` from ".TABLE_ADS." where `id` = '$ad_id'");
		foreach($fields_arr as $f) {
			if(!$f) continue;
			$val = cleanStr($ad_arr[$f]);
			if(!$val) continue;
			if($dec && $val) $this->decForField($ad_id, $f, $val);
			$this->decCategoryField($ad_id, $cat_id, $f, $val);

			// if multilanguage, decrement for the translated location name
			if(count($languages>1)) {
				$l = new locations();
				$translated_locations = $l->translateLocation ($f, $val);
				foreach($translated_locations as $tr)
					$this->decCategoryField($ad_id, $cat_id, $f, $tr);
			}
		}

	}

	// decrements number of ads for a custom field value
	function decForField($ad_id, $field, $val) {

		global $db;
		$val = escape($val);
		if(!$field || !$val) return;
		$db->query("update ".TABLE_LOCATION_NO_ADS." set `no` = `no`-1 where `field` = '$field' and `val` = '".escape($val)."'");
		
	}

	// decrements number of ads for a category and a custom field value
	function decCategoryField($ad_id, $category_id, $field, $val) {

		global $db;
		$val = escape($val);
		if(!$field || !$val) return;
		$db->query("update ".TABLE_CATEGORIES_NO_ADS." set `no` = `no`-1 where `category_id`='$category_id' and `field` = '$field' and `val` = '$val'");
		
	}

	function countLocationAds() {

		global $db;
		global $languages;
		$db->query("delete from ".TABLE_LOCATION_NO_ADS);
		$db->query("delete from ".TABLE_CATEGORIES_NO_ADS);

		$categories = $db->fetchRowList("select `id` from ".TABLE_CATEGORIES);

		global $settings;
		$fields = $settings['location_fields'];
		$fields_arr = explode(",", $fields);
		$fields = str_replace(" ", "", $fields);
		$fields = str_replace(",", "`,`", $fields);

		$cats = new categories;
		
		foreach($fields_arr as $f) {
			if(!$f) continue;

			// get field values
			$l = new locations();
			$vals = $l->getLocations($f, 1); 
			$values = explode("|", $vals);

			foreach($values as $val) {
 				$val = cleanStr($val);
				$val = escape($val);
				if(!$val) continue;
				$no_ads = 0;//$db->fetchRow("select count(*) from ".TABLE_ADS." where `$f` = '".escape($val)."'");

				$translated_locations = array();
				// if multilanguage, add for the translated location name
				if(count($languages>1)) {
					$l = new locations();
					$translated_locations = $l->translateLocation ($f, $val);
				}
				// add current value to translated locations
				array_push($translated_locations, $val);

				// add to number of ads for each translated location
				foreach($translated_locations as $tr) 
					$no_ads += $db->fetchRow("select count(*) from ".TABLE_ADS." where `$f` = '".escape($tr)."' and `active`=1");

				if($no_ads>0) { 
					// add to database for each translated value
					foreach($translated_locations as $tr) 
						$db->query("insert into ".TABLE_LOCATION_NO_ADS." set `field`='$f', `val` = '".escape($tr)."', `no` = '$no_ads'");
				}

				// add to categories ads no
				foreach($categories as $cat) {
					$no_ads = 0;
					foreach($translated_locations as $tr) 
						$no_ads += $db->fetchRow("select count(*) from ".TABLE_ADS." where `$f` = '".escape($tr)."' and `category_id`= '$cat' and `active`=1");
					if($no_ads) {
						foreach($translated_locations as $tr) {
						
							$old_no = $db->fetchRow("select `no` from ".TABLE_CATEGORIES_NO_ADS." where `field` = '$f' and `val` = '".escape($tr)."' and `category_id`= '$cat'");
							if($old_no)
								$db->query("update ".TABLE_CATEGORIES_NO_ADS." set `no`=`no`+$no_ads where `field` = '$f' and `val` = '".escape($tr)."' and `category_id`= '$cat'");
							else 
								$db->query("insert into ".TABLE_CATEGORIES_NO_ADS." set `category_id`= '$cat', `field`='$f', `val` = '".escape($tr)."', `no` = '$no_ads'");

							}

						// add for parent categories
						$arr = $cats->catPathArray($cat, array());

						foreach($arr as $parent) {

							if($parent['id']==$cat) continue;

							$old_no = $db->fetchRow("select `no` from ".TABLE_CATEGORIES_NO_ADS." where `field` = '$f' and `val` = '".escape($tr)."' and `category_id`= '{$parent['id']}'");
							if($old_no) {
								foreach($translated_locations as $tr) 
									$res=$db->query("update ".TABLE_CATEGORIES_NO_ADS." set `no`=`no`+$no_ads where `field` = '$f' and `val` = '".escape($tr)."' and `category_id`= '{$parent['id']}'");
								}
							else {
								foreach($translated_locations as $tr) 
									$res=$db->query("insert into ".TABLE_CATEGORIES_NO_ADS." set `category_id`= '{$parent['id']}', `field`='$f', `val` = '".escape($tr)."', `no` = '$no_ads'");
								}
						} // end foreach($arr as $parent)
					}// end if($no_ads)
				} // end foreach($categories as $cat)
			} // end foreach($values as $val)
		} // end foreach($fields_arr as $f)

	}
/*
// alternative function
	function makeTimeAgoDate($time_added, $date_formatted) {

		global $ads_settings, $admin_side, $appearance_settings;
		if($admin_side || !$ads_settings['date_time_ago_format']) return $date_formatted;

		global $lng;

		$time_diff = (float)(time() - $appearance_settings['time_offset'] - $time_added);

		$result_date=$lng['ago_prefix'];
		//X less than a minute ago
		if($time_diff<60) $result_date = $lng['less_than_a_minute'];
		//X minutes ago
		else if($time_diff<3600) { 
			$min = round($time_diff/60);
			if($min!=1) $m = $lng['minutes']; else $m = $lng['minute'];
			$result_date .= $min." ".$m." ".$lng['ago_postfix'];
		}
		//X hour ago
		else if($time_diff<86400) { 
			$hours = floor($time_diff/3600);
			$minutes = round(($time_diff%3600)/60);
			if($hours!=1) $h = $lng['hours']; else $h = $lng['hour'];
			$result_date .=  $hours." ".$h." ";

			if($minutes>0) {
				if($minutes!=1) $m = $lng['minutes']; else $m = $lng['minute'];
				$result_date .= $minutes." ".$m." ";
			}
			$result_date.=$lng['ago_postfix'];
		}
		else if($time_diff<604800) {  // less than a week
			
			$days = floor($time_diff/86400);
			$hours = round(($time_diff%86400)/3600);
			if($days!=1) $d = $lng['days']; else $d = $lng['day'];
			$result_date .=  $days." ".$d." ";

			if($hours>0) {
				if($hours!=1) $h = $lng['hours']; else $h = $lng['hour'];
				$result_date .= $hours." ".$h." ";
			}
			$result_date.=$lng['ago_postfix'];
			
		}
		//weeks
		else if($time_diff<2073600) {  // less than a month
		//echo $time_diff."  ";
			$weeks = floor($time_diff/604800);
			$days = round(($time_diff%604800)/86400);
			if($weeks!=1) $w = $lng['weeks']; else $w = $lng['week'];
 			$result_date .=  $weeks." ".$w." ";

			if($days>0) {
				if($days!=1) $d = $lng['days']; else $d = $lng['day'];
				$result_date .= $days." ".$d." ";
			}
			$result_date.=$lng['ago_postfix'];
		}
		else if($time_diff<24883200) {  // less than a year

			$months = floor($time_diff/2073600);
			$days = round(($time_diff%2073600)/86400);
			if($months!=1) $m = $lng['months']; else $m = $lng['month'];
 			$result_date .=  $months." ".$m." ";

			if($days>0) {
				if($days!=1) $d = $lng['days']; else $d = $lng['day'];
				$result_date .= $days." ".$d." ";
			}
			$result_date.=$lng['ago_postfix'];
			
		}
		else {
			$months = round($time_diff/2073600);
			if($months!=1) $m = $lng['months']; else $m = $lng['month'];
 			$result_date .=  $months." ".$m." ".$lng['ago_postfix'];
		}
		return $result_date;

	}
	*/
	
	function makeTimeAgoDate($time_added, $date_formatted) {

		global $ads_settings, $admin_side, $appearance_settings;
		if($admin_side || !$ads_settings['date_time_ago_format']) return $date_formatted;

		global $lng;

		$time_diff = (float)(time() - $appearance_settings['time_offset'] - $time_added);

		$result_date=$lng['ago_prefix'];
		//X less than a minute ago
		if($time_diff<60) $result_date = $lng['less_than_a_minute'];
		//X minutes ago
		else if($time_diff<3600) { 
			$min = round($time_diff/60);
			if($min!=1) $m = $lng['minutes']; else $m = $lng['minute'];
			$result_date .= $min." ".$m." ".$lng['ago_postfix'];
		}
		//X hour ago
		else if($time_diff<86400) { 
			$hours = round($time_diff/3600);
			if($hours!=1) $h = $lng['hours']; else $h = $lng['hour'];
			$result_date .=  $hours." ".$h." ".$lng['ago_postfix'];
		}
		//Yesterday
		else if($time_diff<172800) $result_date = $lng['yesterday'];
		//X days
		else {
			if($ads_settings['date_time_ago_days']!=0)
			{ 
				$sec = 86400*$ads_settings['date_time_ago_days'];
				if($time_diff<$sec) {
					$days = round($time_diff/86400);
					$result_date .= $days." ".$lng['days']." ".$lng['ago_postfix'];
				} 
				// default date format
				else $result_date = $date_formatted;
			}
			else {
				$days = round($time_diff/86400);
				$result_date .= $days." ".$lng['days']." ".$lng['ago_postfix'];
			}
		}

		return $result_date;

	}

	function getBriefListing($id) {

		global $db, $ads_settings, $crt_lang;

		$mlang_vars ='';
		if($ads_settings['translate_title_description']) {
			global $languages;
			if(empty($languages)) $languages = common::getCachedObject("base_languages");
			if(count($languages)>1) {
				$mlang_vars = ",`title_$crt_lang` as `title`, `description_$crt_lang` as `description` ";
			}
		}

		$sql = "select ".TABLE_ADS.".*$mlang_vars from ".TABLE_ADS." where ".TABLE_ADS.".`id`='$id'";
		$result=$db->fetchAssoc($sql);
		return $result;

	}

	function isVideoEnabled($id) {

		global $db;
		$result = $db->fetchRow("select count(*) from ".TABLE_OPTIONS." where `object_id` = {$id} and `option` like 'video'" );
		return $result;
	}

	function isUrlEnabled($id) {

		global $db;
		$result = $db->fetchRow("select count(*) from ".TABLE_OPTIONS." where `object_id` = {$id} and `option` like 'url'" );
		return $result;
	}

	static function getFieldset($id) {

		$category = listings::getCategory($id);
		$fieldset = categories::getFieldset($category);
		return $fieldset;

	}

	static function wasListingPostedAsPending($id) {

		global $db, $config_abs_path;

		// if not active return 0
		$l = new listings;
		$active = $l->isActive ($id);
		if(!$active) return 0;
		
		// get plan price
		require_once $config_abs_path."/classes/payment_processors.php";
		$pkg = new packages;
		$pp = new payment_processors();

		$pkg_id = listings::getPackage($id);
		$plan_amount = $pkg->getAmount($pkg_id);
		if($plan_amount==0) 
			$is_pending = $pp->isPending("free");
		else {

			// get action_id
			$invoice = $db->fetchRow("select `invoice` from ".TABLE_ACTIONS." where `type` like 'newad' or `type` like 'renewad' and `object_id` = '$id' order by `date` desc limit 1");

			$processor = $db->fetchRow("select `processor` from ".TABLE_PAYMENT_ACTIONS." where `id`='$invoice'");
			$is_pending = $pp->isPending($processor);

		}

		if($is_pending) return 1;
		
		require_once $config_abs_path."/classes/groups.php";
		require_once $config_abs_path."/classes/users.php";
		$gr = new groups; $usr = new users;
		$user_id = listings::getUser($id);
		$group_id = $usr->getGroup($user_id);
		$listings_pending = $gr->getListingPending($group_id);

		return $listings_pending;
		
	}

	function getPendingEdited($id) {

		global $db, $config_abs_path;
		require_once $config_abs_path."/libs/JSON.php";
		$new_content = $db->fetchRow("select `edited` from ".TABLE_PENDING_EDITED." where `ad_id` = '$id'");
		if(!$new_content) return array();
		$new_content_array = json_decode($new_content, true);

		return $new_content_array;

	}

	function isPendingEdited($id) {

		global $db;
		$is_pending = $db->fetchAssoc("select `edited` from ".TABLE_PENDING_EDITED." where `ad_id` = '$id'");
		if(!$is_pending) return 0;
		return 1;

	}

	function isActive($id) {

		global $db;
		$active = $db->fetchRow("select active from ".TABLE_ADS." where id='$id'");
		return $active;

	}

	function getPrice($id) {

		global $db;
		$result = $db->fetchRow("select `price` from ".TABLE_ADS." where `id` = {$id}" );
		return $result;

	}

	static function addAuction($id) {

		global $db;
		$db->query("update ".TABLE_ADS." set `auction`=1 where `id`='$id'" );
		return 1;

	}

	static function removeAuction($id) {

		global $db;
		$db->query("update ".TABLE_ADS." set `auction`=0 where `id`='$id'" );
		return 1;

	}

	function getLocationStr($result) {

		global $ads_settings;

		$loc_fields = explode(",", $ads_settings['location_fields']);
		$location_str = '';
		$k = 0;
		foreach($loc_fields as $l) {

			if(!$l || !isset($result[$l]) || !$result[$l]) continue;
			if($k) $location_str.=", ";
			$location_str.= $result[$l];
			$k++;

		}
		return $location_str;

	}

	function checkActivation($id, $activation) {
	
		global $db;
		$result = $db->fetchRow("select count(*) from ".TABLE_ADS_EXTENSION." where id='$id' and `activation` like '$activation'");
		return $result;
	
	}

	function sendSMSVerification($id) {
	
	
		global $db;

		$usr = new users();
		$phone_field = $usr->getRequiredIntlPhoneField(-1);
		if(!$phone_field) { 
			// !!!!!!!!
			return 0;
		}
		$arr = $db->fetchAssoc("select `$phone_field`, `activation` from ".TABLE_ADS_EXTENSION." where id='$id'");
		$phone_no = $arr[$phone_field];
		$activation_code = $arr['activation'];

		if($phone_no) {
				
			global $config_abs_path;
			require_once($config_abs_path.'/classes/sms_gateways.php');

			// get default SMS gateway
			$sg = new sms_gateways();
			$default = $sg->getDefault();
			if($default) {
			
				$class_name = sms_gateways::getSMSGatewayClass($default);
				require_once($config_abs_path.'/classes/sms_verification/'.$class_name.'.php');
				
				$gcl = new $class_name;
				$sent = $gcl->send($phone_no, $activation_code, $id, "listing");
				// !!!!!!!!!!!!!

			}  // end if default
				
		}// end if phone no

	
	}
	
	static function saveExtraOptions($id, $featured, $highlited, $priority, $video, $urgent, $url) {
	
		global $db;
		if(!isset($priority) && !$priority) $priority=0;
		$res=$db->query("update ".TABLE_ADS." set `featured`='$featured', `highlited`='$highlited', `priority`='$priority', `video`='$video', `urgent`='$urgent', `site_url`='$url' where `id`='$id'");
	
	}

	function setWhere($w){
	
		$this->where=$w;
		
	}
	
	function getWhere(){
	
		return $this->where;
		
	}
	function setWhereArray($w){
	
		$this->where_array=$w;
		
	}
	
	function getWhereArray(){
	
		return $this->where_array;
		
	}
}

?>
