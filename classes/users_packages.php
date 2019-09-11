<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class users_packages {

	var $id;
	var $error;
	var $array;
	var $tmp;

	public function __construct($id=0)
	{

		if($id) {
			global $db;
			$this->id=$id;
			$this->array=array();
			$this->array=$db->fetchAssoc('select * from '.TABLE_USERS_PACKAGES.' where `id`='.$id);
		}
	}

	function getId() {
		return $this->id;
	}

	function getNo($user_id='') {

		global $db;
		if($user_id!='' && is_numeric($user_id)) $user_str=" where user_id=".$user_id;
		else $user_str='';
		$no=$db->fetchRow("select count(*) from ".TABLE_USERS_PACKAGES.$user_str);
		if(!$no) return 0;
		return $no;

	}

	function getPackageId($id) {

		global $db;
		$result=$db->fetchRow('select `package_id` from '.TABLE_USERS_PACKAGES.' where `id`='.$id);
		if(!$result) return 0;
		return $result;

	}

	function getPackageName($id) {

		global $db;
		global $crt_lang;
		$result=$db->fetchRow("select `name` from ".TABLE_PACKAGES."_lang left join ".TABLE_USERS_PACKAGES." on ".TABLE_USERS_PACKAGES.".`package_id` = ".TABLE_PACKAGES."_lang.`id` where `lang_id`='$crt_lang' and  ".TABLE_USERS_PACKAGES.".`id`=".$id);
		if(!$result) return 0;
		return $result;

	}

	function getPackageType($id) {

		global $db;
		global $crt_lang;
		$result=$db->fetchRow("select `type` from ".TABLE_PACKAGES." left join ".TABLE_USERS_PACKAGES." on ".TABLE_USERS_PACKAGES.".`package_id` = ".TABLE_PACKAGES.".`id` where ".TABLE_USERS_PACKAGES.".`id`=".$id);
		if(!$result) return 0;
		return $result;

	}

	function getPackage($id) {

		global $db;
		global $crt_lang;

		$result=$db->fetchAssoc("select * from ".TABLE_PACKAGES." LEFT JOIN ".TABLE_PACKAGES."_lang on ".TABLE_PACKAGES.".`id` = ".TABLE_PACKAGES."_lang.`id` left join ".TABLE_USERS_PACKAGES." on ".TABLE_USERS_PACKAGES.".`package_id` = ".TABLE_PACKAGES."_lang.`id` where ".TABLE_USERS_PACKAGES.".id=$id and `lang_id`='$crt_lang'");
		if(!$result) return 0;

		$result['price_curr'] = add_currency(format_price($result['amount']));

		return $result;

	}

	function getUser($id) {

		global $db;
		$result=$db->fetchRow('select `user_id` from '.TABLE_USERS_PACKAGES.' where `id`='.$id);
		if(!$result) return 0;
		return $result;

	}
	function getAdsLeft($id) {

		global $db;
		$result=$db->fetchRow('select `ads_left` from '.TABLE_USERS_PACKAGES.' where `id`='.$id);
		if(!$result) return 0;
		return $result;

	}

	function isPending($id) {

		global $db;
		$result=$db->fetchRow('select `pending` from '.TABLE_USERS_PACKAGES.' where `id`='.$id);
		if(!$result) return 0;
		return $result;

	}

	function isActive ($id) {

		global $db;
		$arr=$db->fetchAssoc("select user_approved, active from ".TABLE_USERS_PACKAGES." where `id`='$id'");

		if(!$arr['user_approved'] || !$arr['active']) return 0;
		return 1;
	}

	function isApproved($id) {

		global $db;
		$approved=$db->fetchRow("select user_approved from ".TABLE_USERS_PACKAGES." where `id`='$id'");
		return $approved;
	}


	static function delete($id=0) {

		global $config_abs_path;

		global $config_demo;
		if($config_demo==1) return;
		if(!$id) $id=$this->id;
		global $db;
		$db->query('delete from '.TABLE_USERS_PACKAGES.' where `id`="'.$id.'"');

		// action 
		require_once $config_abs_path."/classes/actions.php";
		actions::deleteSubscription($id);

		// delete discounts 
		require_once $config_abs_path."/classes/coupons.php";
		coupons::deleteSubscription($id);

	}

	static function deleteUser($user_id) {

		global $config_demo;
		if($config_demo==1) return;
		global $db;
		$db->query('delete from '.TABLE_USERS_PACKAGES.' where `user_id`="'.$user_id.'"');

	}

	static function expired($id=0) {

		global $db;
		if(!$id) $id=$this->id;
		$timestamp = date("Y-m-d H:i:s");
		$res_del=$db->query("update ".TABLE_USERS_PACKAGES." set active=0, pending=0, date_end = '$timestamp' where `id`='$id'");
		return 1;
	}


	function Remove ($id) {

		global $db;
		$result = $db->fetchAssocList("select active, fixed from ".TABLE_USERS_PACKAGES." where `id`='$id'");

		if($result['active'] || $result['fixed']) return 0;

		$this->delete($id);
		return 1;
	}

	function makePending($id) {

		global $db;
		$db->query("update ".TABLE_USERS_PACKAGES." set `pending`=1, `active`=0 where id=$id");
		return 1;

	}

	function Enable($id='') {

		if(!$id) $id=$this->id;
		global $db;
		$db->query("update ".TABLE_USERS_PACKAGES." set active=1 where id=".$id);

		// check if the user package is pending
		$pending = $db->fetchRow("select pending from ".TABLE_USERS_PACKAGES." where id='$id'");
		if($pending) $this->ActivatePending($id);

		return 1;

	}

	function activatePackage($id='') {

		if(!$id) $id=$this->id;
		global $db;
		$db->query("update ".TABLE_USERS_PACKAGES." set active=1, pending=0 where id=".$id);

		$res_actions = $db->query("update ".TABLE_ACTIONS." set pending=0 where type like 'newpkg' or type like 'renewpkg' and object_id=$id");

		return 1;

	}

	function activateAdPackage($id='') {

		if(!$id) $id=$this->id;
		global $db;
		$db->query("update ".TABLE_USERS_PACKAGES." set active=0, pending=0 where id=".$id);

		$res_actions = $db->query("update ".TABLE_ACTIONS." set pending=0 where type like 'newpkg' or type like 'renewpkg' and object_id=$id");

		return 1;

	}

	function ActivatePending($id) {

		global $db;
		global $lng;
		global $config_abs_path, $settings;
		require_once $config_abs_path."/classes/settings.php";
		require_once $config_abs_path."/classes/mails.php";
		require_once $config_abs_path."/classes/mail_templates.php";
		require_once $config_abs_path."/classes/payment_processors.php";
		require_once $config_abs_path."/classes/listings.php";
		require_once $config_abs_path."/classes/users.php";

		$db->query("update ".TABLE_USERS_PACKAGES." set active=1, pending=0 where id=".$id);

		$processor_code = $db->fetchRow("select ".TABLE_PAYMENT_ACTIONS.".processor from ".TABLE_ACTIONS." left join ".TABLE_PAYMENT_ACTIONS." on ".TABLE_ACTIONS.".invoice=".TABLE_PAYMENT_ACTIONS.".id where ".TABLE_ACTIONS.".id = $id");

		$db->query("update ".TABLE_ACTIONS." set pending=0 where type='newpkg' or type='renewpkg' and object_id = $id");

		// activate listings for this package
		$listing = new listings();
		$listing->ActivatePendingPackage($id);

		// get user details
		$user_id = $this->getUser($id);
		$user = new users();
		$user_details = $user->getContactData($user_id);
		$username = $user_details['username'];
		$user_email = $user_details['email'];
		$user_contact = $user_details[$settings['contact_name_field']];
//		if(!$user_contact) $user_contact=$username;

		// get plan details
		$pkg_id = $this->getPackageId($id);
		$pkg = new packages();
		$plan = $pkg->getPackage($pkg_id);

		// send email
		$mail2send=new mails();
		$mail2send->init($user_email, $user_contact);

		$array_subject = array();

		$array_message = array( "username"=>$username,  "contact_name"=>$user_contact, "unlimited"=> $lng['general']['unlimited'], "processor"=>payment_processors::getName($processor_code), "plan"=>$plan, "status"=>$lng['general']['active']);

		$mail2send->composeAndSend("subscription_status", $array_message, $array_subject);

	}

	function Disable($id='') {

		global $config_demo;
		if($config_demo==1) return;
		if(!$id) $id=$this->id;
		global $db;
		$db->query("update ".TABLE_USERS_PACKAGES." set active=0 where id=".$id);
		return 1;

	}

	function userApprove ($id) {

		global $db;
		if(!$id) $id=$this->id;
		$res=$db->query("update ".TABLE_USERS_PACKAGES." set `user_approved`=1 where `id`='$id'");

	}

	function getUserSubscriptions($user_id) {

		global $appearance_settings;
		$date_format=$appearance_settings["date_format"];
		global $db;
		global $lng;
		global $crt_lang;

		$timestamp = date("Y-m-d H:i:s");

		$result=$db->fetchAssocList("select ".TABLE_USERS_PACKAGES.".*, ".TABLE_USERS_PACKAGES.".`id` as usr_pkg, ".TABLE_USERS_PACKAGES.".`active` as usr_pkg_active, date_format(`date_start`,'$date_format') as `date_start_nice`, date_format(`date_renew`,'$date_format') as `date_renew_nice`, date_format(`date_end`, '$date_format') as `date_end_nice`, (date_end<'$timestamp' and date_end!='0000-00-00 00:00:00') as expired, ".TABLE_PACKAGES."_lang.`name` as package_name, ".TABLE_PACKAGES."_lang.`description`, ".TABLE_USERS.".`group` as user_group from ".TABLE_USERS_PACKAGES." 
		left join ".TABLE_PACKAGES." on ".TABLE_USERS_PACKAGES.".package_id = ".TABLE_PACKAGES.".id
		left join ".TABLE_PACKAGES."_lang on ".TABLE_USERS_PACKAGES.".package_id = ".TABLE_PACKAGES."_lang.id 
		left join ".TABLE_USERS." on ".TABLE_USERS_PACKAGES.".user_id = ".TABLE_USERS.".`id` 
		where ".TABLE_USERS_PACKAGES.".`user_id`='$user_id' and `lang_id` = '$crt_lang' and ".TABLE_PACKAGES.".`type`='sub' order by `date_start` desc");

		if(!$result) return;

		$i = 0;
		$array = array();

		foreach($result as $row) {
			
			$array[$i] = $row;

			if($row['usr_pkg_active']==1) $array[$i]['state'] = $lng['listings']['active'];
			else if ($row['expired']) $array[$i]['state'] = $lng['listings']['expired'];
			else if ($row['pending']) $array[$i]['state'] = $lng['listings']['pending'];
			else if($row['user_approved']==0) $array[$i]['state'] = $lng['listings']['not_approved']." <a href='subscribe.php?id=".$row['id']."'>[".$lng['listings']['approve']."]</a>";
			else $array[$i]['state'] = $lng['listings']['inactive'];
			$i++;

		}

		return $array;

	}

	function getActiveUserSubscriptions($user_id, $category_id='') {

		global $appearance_settings;
		$date_format=$appearance_settings["date_format"];
		global $db;
		global $lng;
		global $crt_lang;

		$where_str = '';
		if($category_id)  $where_str = " and ( ".TABLE_PACKAGES.".`categories` REGEXP '\[\[:<:\]\]$category_id\[\[:>:\]\]'  or `categories` = 0 ) ";

		$result=$db->fetchAssocList("select ".TABLE_USERS_PACKAGES.".*, ".TABLE_USERS_PACKAGES.".`id` as usr_pkg, ".TABLE_PACKAGES."_lang.`name` as package_name,  date_format(`date_start`,'$date_format') as `date_start_nice`, date_format(`date_renew`,'$date_format') as `date_renew_nice`, date_format(`date_end`, '$date_format') as `date_end_nice`, ".TABLE_PACKAGES.".*, ".TABLE_PACKAGES."_lang.* from ".TABLE_USERS_PACKAGES." 
		left join ".TABLE_PACKAGES." on ".TABLE_USERS_PACKAGES.".package_id = ".TABLE_PACKAGES.".id
		left join ".TABLE_PACKAGES."_lang on ".TABLE_USERS_PACKAGES.".package_id = ".TABLE_PACKAGES."_lang.id 
		where ".TABLE_USERS_PACKAGES.".`user_id`='$user_id' and `lang_id` = '$crt_lang' and ".TABLE_PACKAGES.".`type`='sub' and ".TABLE_USERS_PACKAGES.".active=1 and ".TABLE_USERS_PACKAGES.".ads_left!=0 $where_str order by `date_start` desc");

		return $result;

	}


	function existingSubscription($user_id) {

		// !!! change here if you want no posting for pending user packages
		global $db;
		$result=$db->fetchAssoc("select * from ".TABLE_USERS_PACKAGES." where `user_id`='$user_id' and ( ads_left>0 or ads_left=-1) and ( `active` = 1 or ( `active`=0 and `pending`=1 ) )");
		return $result;

	}

	function getUserPackage($id='') {
		
		if(!$id) $id=$this->id;
		global $appearance_settings;
		$date_format=$appearance_settings["date_format"];

		global $db;
		$timestamp = date("Y-m-d H:i:s");
		$result=$db->fetchAssoc("select *, date_format(`date_start`,'$date_format') as `date_start_nice`, date_format(`date_renew`,'$date_format') as `date_renew_nice` , date_format(`date_end`, '$date_format') as `date_end_nice`, ".TABLE_USERS.".username, ".TABLE_PACKAGES.".name as package_name, (date_end is not null && date_end not like '0000-00-00 00:00:00' && '$timestamp'>=date_end && ".TABLE_USERS_PACKAGES.".active=0) as expired from ".TABLE_USERS_PACKAGES." where ".TABLE_USERS_PACKAGES.".`id`='$id' left join ".TABLE_USERS." on ".TABLE_USERS_PACKAGES.".user_id = ".TABLE_USERS.".id left join ".TABLE_PACKAGES." on ".TABLE_USERS_PACKAGES.".package_id = ".TABLE_PACKAGES.".id ");

		global $lng;
		if(!$result['date_end']) $result['date_end_nice'] = $lng['packages']['unlimited'];
		return $result;

	}


	function searchSubscriptions($post_array, $page, $no_per_page,$order,$order_way) {

		global $db;
		global $lng;
		global $appearance_settings, $settings;
		global $crt_lang;
		$date_format=$appearance_settings["date_format_long"];

		$timestamp = date("Y-m-d H:i:s");

		$start=($page-1)*$no_per_page;

		$where = '';
		foreach($post_array as $key=>$value) {
			if( !$key || $value=='' ) continue;
			
			switch($key) {
				case "id": 
				case "package_id": 
					$where .= " and ".TABLE_USERS_PACKAGES.".`$key` = '$value' ";
				break;

				case "username": 
				case "email": 
					$where .= " and ".TABLE_USERS.".`$key` like '$value' ";
				break;
				case "date_from": 
					$where .= " and ".TABLE_USERS_PACKAGES.".`date_start` >= '$value' ";
				break;

				case "date_to": 
					$where .= " and ".TABLE_USERS_PACKAGES.".`date_start` <= '$value' ";
				break;
			}
		}

		$no_subscriptions = $this->getNoSearchSubscriptions($where);
		$this->setNoSubscriptions($no_subscriptions);

		if($settings['enable_username']) $str1 = ", ".TABLE_USERS.".username as username";
		else	 $str1 = ", ".TABLE_USERS.".`email`, ".TABLE_USERS.".`".$settings['contact_name_field']."`";

		$array=$db->fetchAssocList("select ".TABLE_USERS_PACKAGES.".*, date_format(`date_start`,'$date_format') as date_start_nice, date_format(`date_renew`,'$date_format') as `date_renew_nice`, date_format(`date_end`,'$date_format') as date_end_nice, (date_end is not null && date_end not like '0000-00-00 00:00:00' && '$timestamp'>=date_end && ".TABLE_USERS_PACKAGES.".active=0) as expired, ".TABLE_PACKAGES."_lang.name as package_name $str1 
		from ".TABLE_USERS_PACKAGES." 
		left join ".TABLE_USERS." on ".TABLE_USERS_PACKAGES.".user_id=".TABLE_USERS.".id 
		left join ".TABLE_PACKAGES."_lang on ".TABLE_USERS_PACKAGES.".package_id = ".TABLE_PACKAGES."_lang.id 
		left join ".TABLE_PACKAGES." on ".TABLE_USERS_PACKAGES.".package_id = ".TABLE_PACKAGES.".id 
		where ".TABLE_PACKAGES.".`type`='sub' 
		and ".TABLE_PACKAGES."_lang.`lang_id`='$crt_lang' 
		$where
		order by `$order` $order_way 
		limit $start, $no_per_page");

		$i=0;
		$array_usr_pkg=array();
		foreach ($array as $result) {
			$array_usr_pkg[$i]=$result;
			$array_usr_pkg[$i]['invoice'] = $db->fetchRow("select `invoice` from ".TABLE_ACTIONS." where `object_id` = '".$result['id']."' and ( `type` = 'newpkg' or `type` = 'renewpkg' )");

			$i++;
		}

		return $array_usr_pkg;

	}

	function getNoSearchSubscriptions($where) {

		global $db;

		$no=$db->fetchRow("select count(*) from ".TABLE_USERS_PACKAGES." 
		left join ".TABLE_USERS." on ".TABLE_USERS_PACKAGES.".user_id = ".TABLE_USERS.".id 
		left join ".TABLE_PACKAGES." on ".TABLE_USERS_PACKAGES.".package_id = ".TABLE_PACKAGES.".id 
		where ".TABLE_PACKAGES.".`type`='sub' 
		".$where);
		if(!$no) return 0;
		return $no;

	}

	function getNoSubscriptions() {
		return $this->no_subscriptions;
	}

	function setNoSubscriptions($no) {

		$this->no_subscriptions = $no;

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

	function add($package_id, $user_id='', $active=0) {

		global $db;
		$pkg = new packages();
		$pkg_details = $pkg->getPackage($package_id);
		$type = $pkg_details['type'];

		$timestamp = date("Y-m-d H:i:s");

		if($type=="sub") {
			$ads_no = $pkg_details['no_ads'];
			if($ads_no==0) $ads_no = -1;
			$days = $pkg_details['subscription_time'];
			if($days) $days_str=", date_end=date_add('$timestamp', interval '$days' day)"; else $days_str="";
		} else { $days_str=", `date_end`='$timestamp'"; $ads_no=0; }

		$fixed=0;
		$allow = 0;
		
		if($pkg_details['allow']>0) { $fixed=1; $allow = $pkg_details['allow']; }

		$extra = '';
		if($active) $extra = ", `user_approved` = 1 ";

		$ip = getRemoteIp();

		$db->query("insert into ".TABLE_USERS_PACKAGES." set `user_id`='$user_id', `package_id` = '$package_id', `date_start`='$timestamp'".$days_str.", `active`='$active', `ads_left` = '$ads_no', `fixed`='$fixed', `ip` = '$ip', `allow`='$allow' ".$extra);
		$id=$db->insertId();
//echo "--insert into ".TABLE_USERS_PACKAGES." set `user_id`='$user_id', `package_id` = $package_id, `date_start`='$timestamp'".$days_str.", `active`=$active, `ads_left` = $ads_no, `fixed`='$fixed', `ip` = '$ip', `allow`='$allow' ".$extra."--".$id;
//exit(0);
		

		// delete old inactive subscriptions - subscriptions with active=0 and pending=0 and not recurring
/*
		$arr = $db->fetchRowList("select ".TABLE_USERS_PACKAGES.".id from ".TABLE_USERS_PACKAGES." 
		left join ".TABLE_PACKAGES." on ".TABLE_USERS_PACKAGES.".package_id = ".TABLE_PACKAGES.".id
		left join ".TABLE_USERS." on ".TABLE_USERS_PACKAGES.".user_id = ".TABLE_USERS.".`id` 
		where ".TABLE_USERS_PACKAGES.".`id`!='$id' and ".TABLE_USERS_PACKAGES.".`user_id`='$user_id' and ".TABLE_PACKAGES.".`type`='sub' and ".TABLE_USERS_PACKAGES.".`active` = 0 and ".TABLE_USERS_PACKAGES.".`pending` = 0 and ".TABLE_USERS_PACKAGES.".`recurring` = 0");
*/
		/*
		foreach($arr as $old_pkg) {
			$this->delete($old_pkg);
		}*/

		return $id;

	}

	function substractNoAds ($usr_pkg) {
	
		global $db;
		$ads_left = $this->getAdsLeft($usr_pkg);
		if($ads_left>0) $db->query("update ".TABLE_USERS_PACKAGES." set ads_left=ads_left-1 where id='$usr_pkg'");
		if($ads_left==1) // last possible ad
			$this->expired($usr_pkg);
		return 1;

	}

	function getLatestSubscriptions($no) {

		global $db;
		global $lng;
		global $appearance_settings;
		global $crt_lang;
		$date_format=$appearance_settings["date_format"];

		$timestamp = date("Y-m-d H:i:s");

		$array=$db->fetchAssocList("select ".TABLE_USERS_PACKAGES.".*, date_format(`date_start`,'$date_format') as date_start_nice, date_format(`date_renew`,'$date_format') as `date_renew_nice`, date_format(`date_end`,'$date_format') as date_end_nice, (date_end is not null && date_end not like '0000-00-00 00:00:00' && '$timestamp'>=date_end && ".TABLE_USERS_PACKAGES.".active=0) as expired, ".TABLE_PACKAGES."_lang.name as package_name, ".TABLE_USERS.".username as username, ".TABLE_USERS.".email as email from ".TABLE_USERS_PACKAGES."
		left join ".TABLE_USERS." on ".TABLE_USERS_PACKAGES.".user_id=".TABLE_USERS.".id 
		left join ".TABLE_PACKAGES."_lang on ".TABLE_USERS_PACKAGES.".package_id = ".TABLE_PACKAGES."_lang.id 
		left join ".TABLE_PACKAGES." on ".TABLE_USERS_PACKAGES.".package_id = ".TABLE_PACKAGES.".id 
		where ".TABLE_PACKAGES.".`type`='sub' and `user_approved` = 1
		and ".TABLE_PACKAGES."_lang.`lang_id`='$crt_lang' 
		order by `date_start` desc limit $no");

		return $array;

	}

	function setSubscriptionId( $subscr_id, $pkg_id ) {

		global $db;
		$db->query("update ".TABLE_USERS_PACKAGES." set `subscr_id` = '$subscr_id', `recurring` = 1 where `id` = '$pkg_id'");
		return 1;

	}

	function getPriceForSubscription($subscr_id) {

		// subscr_id = subscription id as received from online payment
		global $db;
		$pkg_id = $db->fetchRow("select `package_id` from ".TABLE_USERS_PACKAGES." where `subscr_id` like '$subscr_id'");
		$amount = packages::getAmount($pkg_id);
		return $amount;

	}

	function getRecurringSubscription( $subscr_id ) {

		global $db;
		$array = $db->fetchAssoc("select * from ".TABLE_USERS_PACKAGES." where `subscr_id` like '$subscr_id'");
		return $array;

	}

	function renewSubscriptionDate( $usr_pkg ) {

		global $db;
		$pkg_id = $this->getPackageId($usr_pkg);
		$pkg = new packages();
		$pkg_details = $pkg->getPackage($pkg_id);
		$type = $pkg_details['type'];

		$ads_no = $pkg_details['no_ads'];
		if($ads_no==0) $ads_no = -1;
		$days = $pkg_details['subscription_time'];
		$timestamp = date("Y-m-d H:i:s");
		if($days) $days_str=", date_end=date_add('$timestamp', interval '$days' day)"; else $days_str="";

		$active=1;

		$db->query("update ".TABLE_USERS_PACKAGES." set `date_start`='$timestamp'".$days_str.", `date_renew`='$timestamp', `active`=$active, `ads_left` = $ads_no where id = $usr_pkg");

		return 1;
	}

function belongsToUser($id,$user_id) {
		
	global $db;
	$res=$db->query("select user_id from ".TABLE_USERS_PACKAGES." where id = '$id'");
	if(!$db->numRows($res)) return 0;
	$user=$db->fetchRow();
	if($user==$user_id) return 1;
	return 0;

}

	function noActiveSubscriptions($user_id) {

		global $db;
		$no=$db->fetchAssoc("select count(*) from ".TABLE_USERS_PACKAGES." where `user_id`='$user_id' and ( ads_left>0 or ads_left=-1) and ( `active` = 1 or ( `active`=0 and `pending`=1 ) )");
		return $no;

	}

	static function makeSubDetails($sub) {

		global $lng;
		$sub_details = $lng['useraccount']['remaining_ads'].": ";
		if($sub['ads_left']==-1) $sub_details.=$lng['general']['unlimited'];
		else $sub_details.=$sub['ads_left'];
		if($sub['date_end']!='0000-00-00 00:00:00')
			$sub_details.=", ".$lng['general']['expires'].": ".$sub['date_end_nice'];
		else $sub_details.=", ".$lng['listings']['expires_on'].": ".$lng['general']['never'];
		return $sub_details;

	}

}
?>
