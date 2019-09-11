<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class payment_actions {

public function __construct()
{
	
}

function getNo($user_id='') {

	global $db;
	if($user_id!='' && is_numeric($user_id)) $user_str=" where user_id=".$user_id;
	else $user_str='';
	$no=$db->fetchRow("select count(*) from ".TABLE_PAYMENT_ACTIONS.$user_str);
	return $no;

}

function getInvoice($id) {

	global $db;
	$row=$db->fetchAssoc("select * from ".TABLE_PAYMENT_ACTIONS." where id=".$id);
	return $row;

}

function getPaymentAction($id='') {

	if(!$id) $id=$this->id;

	global $db;
	global $lng;
	global $appearance_settings;
	$date_format=$appearance_settings["date_format_long"];
	$row=$db->fetchAssoc("select *, date_format(`date`,'$date_format') as date_nice from ".TABLE_PAYMENT_ACTIONS." where id=".$id);

	global $appearance_settings;
	$currency_pos=$appearance_settings["currency_pos"];
	$default_currency=$appearance_settings["default_currency"];

	if($currency_pos==0) $row['amount_nice']=$default_currency.$row['amount']; // left
	else $row['amount_nice']=$row['amount'].$default_currency; // right

	$row['actions_str'] = $this->actionsStr($row['action']);

	return $row;

}

function getPaymentProcessor($id) {

	global $db;
	$row=$db->fetchRow("select processor from ".TABLE_PAYMENT_ACTIONS." where id=".$id);
	return $row;

}

function actionsStr($str) {

	$action = unserialize($str);
	$action_str = "";
	global $lng;
	global $config_live_site;

	// new ad or renew ad -> link to listing
	$ad_lnk='';
	if(isset($action['ad_id']) && $action['ad_id']) {

		global $seo_settings;
		if($seo_settings['enable_mod_rewrite']) {
			$seo = new seo();
			global $config_abs_path;
			require_once $config_abs_path."/classes/listings.php";
			$lnk=$seo->makeDetailsLink($action['ad_id'], cleanStr(listings::getTitle($action['ad_id'])));
		}
		else $lnk=$config_live_site.'/details.php?id='.$action['ad_id'];
		$ad_lnk = ": #".$action['ad_id']." [<a href='".$lnk."' target='_blank'>".$lng['general']['view']."</a>]";

	}

	if(isset($action['ad_id']) && $action['ad_id'] && ( (isset($action['newad']) && $action['newad']['value']==1) || ( isset($action['renewad']) && $action['renewad']['value']==1)) ) {

		$action_str.=$lng['order_history']['ad_no'].$ad_lnk."<br>";

	}

	// ad options
	if(isset($action['featured']['value']) || isset($action['highlited']['value']) || isset($action['priority']['value']) || isset($action['urgent']['value']) || isset($action['video']['value']) || isset($action['url']['value'])) {

		if(isset($action['featured']['value']) && $action['featured']['value']) { 
			global $config_abs_path;
			require_once $config_abs_path."/classes/featured_plans.php";
			$action_str.=$lng['order_history']['featured']." ".featured_plans::getDays($action['priority']['value'])." ".$lng['days'].$ad_lnk."<br>";
		}

		if(isset($action['highlited']['value']) && $action['highlited']['value']) $action_str.=$lng['order_history']['highlited'].$ad_lnk."<br>";

		if(isset($action['priority']['value']) && $action['priority']['value']) { 
			global $config_abs_path;
			require_once $config_abs_path."/classes/priorities.php";
			$action_str.=priorities::getNameByOrder($action['priority']['value'])." ".$lng['order_history']['priority'].$ad_lnk."<br>";
		}

		if(isset($action['urgent']['value']) && $action['urgent']['value']) $action_str.=$lng['order_history']['urgent'].$ad_lnk."<br>";

		if(isset($action['video']['value']) && $action['video']['value']) $action_str.=$lng['order_history']['video'].$ad_lnk."<br>";

		if(isset($action['url']['value']) && $action['url']['value']) $action_str.=$lng['order_history']['url'].$ad_lnk."<br>";
	
	}

	// new subscription or renew subscription -> plan name and usr_pkg id
	if(isset($action['pkg_id']) && $action['pkg_id'] && ( ( isset($action['newpkg']['value']) && $action['newpkg']['value']==1) || (isset($action['renewpkg']['value']) && $action['renewpkg']['value']==1 )) ) {
		global $config_abs_path;
		require_once $config_abs_path."/classes/users_packages.php";
		$up = new users_packages();
		$package_name = $up->getPackageName($action['pkg_id']);
		$pkg_type = $up->getPackageType($action['pkg_id']);
		if($pkg_type=="sub") { 
			$pkg_name_str = "";
			if($package_name) $pkg_name_str=" [".$package_name."]";
			$action_str.=$lng['order_history']['subscription_no'].": #".$action['pkg_id'].$pkg_name_str."<br>";
		}
	}

	if(isset($action['store']) && $action['store']['value']==1 ) {

		$action_str.=$lng['order_history']['buy_store']."<br>";

	}

	if(isset($action['discount_code']) && $action['discount_code'] ) {

		$action_str.=$lng['listings']['discount'].": <b>".$action['discount_code']."</b><br>";

	}

	// credits purchase
	if(isset($action['credits_pkg_id']) && $action['credits_pkg_id'] && ( isset($action['new_creditspkg']['value']) && $action['new_creditspkg']['value']==1) ) {
		global $config_abs_path;
		require_once $config_abs_path."/classes/credits.php";
		$cr = new credits();
		$package_name = $cr->getPackageName($action['credits_pkg_id']);
		$no_credits = $cr->getNoCredits($action['credits_pkg_id']);
		$action_str.=$lng['order_history']['credits_purchase'].": ".$package_name."<br>".$lng['credits']['credits'].": ".$no_credits;
	}

	do_action("payment_actions_make_actions_str", array($action, &$action_str, $ad_lnk));
	
	return $action_str;

}

function shortActionsStr($str) {

	$action = unserialize($str);
	$action_str = "";
	global $lng;
	global $config_live_site;

	// new ad or renew ad -> link to listing
	$newad=0;
	if(isset($action['ad_id']) && $action['ad_id'] && ( (isset($action['newad']) && $action['newad']['value']==1) || ( isset($action['renewad']) && $action['renewad']['value']==1)) ) {

		$newad=1;
	}

	// ad options
	$options=0;

	if(!empty($action['featured']['value']) || !empty($action['highlited']['value']) || !empty($action['priority']['value']) || !empty($action['urgent']['value']) || !empty($action['video']['value']) || !empty($action['url']['value']) ) {

		$options=1;
	
	}

	$ad_lnk='';
	if($newad || $options) {

		global $seo_settings;
		if($seo_settings['enable_mod_rewrite']) {

			$seo = new seo();
			$ad_lnk=$seo->makeDetailsLink($action['ad_id'], cleanStr(listings::getTitle($action['ad_id'])));

		}
		else $ad_lnk=$config_live_site.'/details.php?id='.$action['ad_id'];

		$action_str.="<a href='".$ad_lnk."' target='_blank'>".$lng['order_history']['ad_no'].": [#".$action['ad_id']."</a>]";

	}

	if($options && !$newad) $action_str.=' '.$lng['listings']['options'];

	// new subscription or renew subscription -> plan name and usr_pkg id
	if(isset($action['pkg_id']) && $action['pkg_id'] && ( ( isset($action['newpkg']['value']) && $action['newpkg']['value']==1) || (isset($action['renewpkg']['value']) && $action['renewpkg']['value']==1 )) ) {
		$up = new users_packages();
		$package_name = $up->getPackageName($action['pkg_id']);
		$pkg_type = $up->getPackageType($action['pkg_id']);
		if($pkg_type=="sub") { 
			if($action_str) $action_str.="<br>";
			$action_str.=$lng['order_history']['subscription_no'].": #".$action['pkg_id']." [".$package_name."]<br>";
		}
	}

	// credits purchase
	if(isset($action['credits_pkg_id']) && $action['credits_pkg_id'] && ( isset($action['new_creditspkg']['value']) && $action['new_creditspkg']['value']==1) ) {
		global $config_abs_path;
		require_once $config_abs_path."/classes/credits.php";
		$cr = new credits();
		$no_credits = $cr->getNoCredits($action['credits_pkg_id']);
		$action_str.=$lng['order_history']['credits_purchase'].": ".$no_credits." ".$lng['credits']['credits'];
	}

	do_action("payment_actions_make_actions_str", array($action, &$action_str, $ad_lnk));
	
	return $action_str;
}


function searchOrders($post_array, $page, $no_per_page,$order,$order_way) {

		global $db;
		global $lng;
		global $crt_lang;
		global $appearance_settings, $settings;
		$date_format=$appearance_settings["date_format_long"];
		$currency_pos=$appearance_settings["currency_pos"];
		$default_currency=$appearance_settings["default_currency"];

		$start=($page-1)*$no_per_page;

		$where = ''; $found = 0;
		$users_join = '';
		foreach($post_array as $key=>$value) {
			if( !$key || $value=='' ) continue;
			
			switch($key) {
				case "id": 
				case "processor": 
				case "user_id": 
					if($found) $where.=" and "; else $where = " where ";
					$where .= " ".TABLE_PAYMENT_ACTIONS.".`$key` = '$value' ";
					$found = 1;
				break;
				case "username": 
					if($found) $where.=" and "; else $where = " where ";
					$where .= " ".TABLE_USERS.".`$key` like '$value' ";
					$found = 1;
					$users_join = " left join ".TABLE_USERS." on ".TABLE_USERS.".id = ".TABLE_PAYMENT_ACTIONS.".user_id ";
				break;
				case "email": 
					if($found) $where.=" and "; else $where = " where ";
					$where .= " ".TABLE_USERS.".`$key` like '$value' ";
					$found = 1;
					$users_join = " left join ".TABLE_USERS." on ".TABLE_USERS.".id = ".TABLE_PAYMENT_ACTIONS.".user_id ";
				break;
				case "amount_from": 
					if($found) $where.=" and "; else $where = " where ";
					$where .= " ".TABLE_PAYMENT_ACTIONS.".`amount` >= '$value' ";
					$found = 1;
				break;
				case "amount_to": 
					if($found) $where.=" and "; else $where = " where ";
					$where .= " ".TABLE_PAYMENT_ACTIONS.".`amount` <= '$value' ";
					$found = 1;
				break;
				case "date_from": 
					if($found) $where.=" and "; else $where = " where ";
					$where .= " ".TABLE_PAYMENT_ACTIONS.".`date` >= '$value' ";
					$found = 1;
				break;
				case "date_to": 
					if($found) $where.=" and "; else $where = " where ";
					$where .= " ".TABLE_PAYMENT_ACTIONS.".`date` <= '$value' ";
					$found = 1;
				break;
			}
		}

		$no_users = $this->getNoSearchOrders($where, $users_join);
		$this->setNoOrders($no_users);


	$array=$db->fetchAssocList("select *, ".TABLE_PAYMENT_ACTIONS.".id as invoice, ".TABLE_PAYMENT_ACTIONS.".amount as action_amount, date_format(".TABLE_PAYMENT_ACTIONS.".`date`,'$date_format') as date_nice, ".TABLE_PAYMENT_PROCESSORS.".processor_name 
	from ".TABLE_PAYMENT_ACTIONS." 
	left join ".TABLE_USERS." on ".TABLE_PAYMENT_ACTIONS.".`user_id` = ".TABLE_USERS.".`id` 
	left join ".TABLE_PAYMENT_PROCESSORS." on ".TABLE_PAYMENT_ACTIONS.".`processor` = ".TABLE_PAYMENT_PROCESSORS.".`processor_code` 
	".$where." order by ".TABLE_PAYMENT_ACTIONS.".`$order` $order_way limit $start, $no_per_page");
	$i=0;

	$result=array();
	$inv = new invoices();
	foreach( $array as $row){

		$result[$i]=$row;
		$result[$i]['invoice_id'] = $inv->getInvoiceForPA($row['invoice']);


		// pending actions
		$result[$i]['pending_actions'] = array();

		$result[$i]['pending_actions'] = $db->fetchAssocList("select * from ".TABLE_ACTIONS." where `invoice` = ".$row['invoice']);

		if($settings['nologin_enabled'] && !$result[$i]['user_id']) {
			$result[$i]['actions'] = array();
			$result[$i]['actions'] = $db->fetchAssoc("select * from ".TABLE_ACTIONS." where `invoice` = ".$row['invoice']."");
		}

		// interpretate the actions table
		$result[$i]['action_str']=$this->actionsStr($result[$i]['action']);

		$result[$i]['amount_nice'] = add_currency($result[$i]['action_amount']);

		if($result[$i]['tax']) $result[$i]['tax_nice'] = add_currency($result[$i]['tax']);

		if($settings['nologin_enabled'] && !$result[$i]['user_id'] && $result[$i]['actions']['object_id']) {

			$user_details = listings::getOwnerInfo($result[$i]['actions']['object_id']);
			$result[$i]['username'] = $user_details['mgm_email'];

		}

		$i++;
	}

	return $result;

}

	function getNoSearchOrders($where='', $join='') {

		global $db;
		$total=$db->fetchRow('select count(*) from '.TABLE_PAYMENT_ACTIONS.$join.$where);
		return $total;
		
	}

	function getNoOrders() {

		return $this->no_orders;

	}

	function setNoOrders($no) {

		$this->no_orders = $no;

	}

function delete($id='') {

	global $db;
	if(!$id) $id=$this->id;
	$res=$db->query("delete from ".TABLE_PAYMENT_ACTIONS." where id=".$id);
	return 1;

}

function Activate($id='') {

	global $db;
	global $config_abs_path, $settings;
	if(!$id) $id=$this->id;
	// make completed=1
	$res=$db->query("update ".TABLE_PAYMENT_ACTIONS." set completed=1 where id=".$id);
	
	require_once $config_abs_path."/classes/actions.php";
	$action = new actions;
	$action->activateInvoiceActions($id);

	// calculate affiliate revenue if the case
	if($settings['enable_affiliates']) {
		require_once $config_abs_path."/classes/affiliates.php";
		affiliates::setAffiliateRevenue(0, "", $id);
	}

	// add invoice
	$this->addInvoice($id);

	return 1;

}

function ActivateInvoice($id='') {

	global $db, $settings;
	if(!$id) $id=$this->id;
	// make completed=1
	$res=$db->query("update ".TABLE_PAYMENT_ACTIONS." set completed=1 where id=".$id);

	// calculate affiliate revenue if the case
	if($settings['enable_affiliates']) {
		global $config_abs_path;
		require_once $config_abs_path."/classes/affiliates.php";
		affiliates::setAffiliateRevenue(0, "", $id);
	}

	// add invoice
	global $invoice_settings;
	if($invoice_settings['enable_invoices'])
		$this->addInvoice($id);
	
	return 1;

}

function addInvoice($id) {

	global $config_abs_path;
	require_once $config_abs_path."/classes/invoices.php";
	$inv = new invoices();
	$inv->add($id);

}

function Deactivate($id='') {

	global $db;
	if(!$id) $id=$this->id;
	// make completed=0
	$res=$db->query("update ".TABLE_PAYMENT_ACTIONS." set completed=0 where id=".$id);
	return 1;

}

function getAmount($id='') {

	global $db;
	if(!$id) $id=$this->id;
	$id=$db->fetchRow("select amount from ".TABLE_PAYMENT_ACTIONS." where id=".$id);
	if(!$id) return 0;
	return $id;

}

function getPaymentDetails($id) {

	global $db;
	global $config_table_prefix;
	$type=$this->getPaymentProcessor($id);
	if($type=="free" || $type=="manual") return array();

	$p = new payment_processors();
	$pp = $p->getPaymentProcessor($type);
	$ret_table = $config_table_prefix.$pp['processor_ret_table'];

	if($type=="2co") { // key instead of ukey

		$arr=$db->fetchAssoc("select $ret_table.* from $ret_table left join ".TABLE_PAYMENT_ACTIONS." on $ret_table.key=".TABLE_PAYMENT_ACTIONS.".key where ".TABLE_PAYMENT_ACTIONS.".id=$id;");

	} else {

		$arr=$db->fetchAssoc("select $ret_table.* from $ret_table left join ".TABLE_PAYMENT_ACTIONS." on $ret_table.ukey=".TABLE_PAYMENT_ACTIONS.".ukey where ".TABLE_PAYMENT_ACTIONS.".id=$id;");

	}

	return $arr;

}

function getLatestOrders($no) {

	global $db;
	global $appearance_settings;
	$date_format=$appearance_settings["date_format"];

	$currency_pos=$appearance_settings["currency_pos"];
	$default_currency=$appearance_settings["default_currency"];

	$array=$db->fetchAssocList("select *, ".TABLE_PAYMENT_ACTIONS.".id as invoice, ".TABLE_PAYMENT_ACTIONS.".amount as action_amount, date_format(".TABLE_PAYMENT_ACTIONS.".`date`,'$date_format') as date_nice 
	from ".TABLE_PAYMENT_ACTIONS." 
	left join ".TABLE_USERS." on ".TABLE_PAYMENT_ACTIONS.".`user_id` = ".TABLE_USERS.".`id` 
	where ".TABLE_PAYMENT_ACTIONS.".completed=1
	order by ".TABLE_PAYMENT_ACTIONS.".`date` desc limit $no");
	$i=0;

	$result=array();
	foreach( $array as $row){
		$result[$i]=$row;

		// pending actions
		$result[$i]['pending_actions'] = array();
		$result[$i]['pending_actions'] = $db->fetchAssocList("select * from ".TABLE_ACTIONS." where `invoice` = ".$row['invoice']." and pending = 1");

		// interpretate the actions table
		$result[$i]['action_str']=$this->shortActionsStr($result[$i]['action']);
		$result[$i]['action_str'] = str_replace("<br>", " | ", $result[$i]['action_str']);

		$result[$i]['amount_nice'] = add_currency($result[$i]['action_amount']);

		$i++;
	}
	return $result;

}

// obsolete !!!!!!!!!!

function recalculateAmount($user_id, $action) {

global $db;
global $ads_settings;

$amount = 0;
$discount_type="ads";
// post an ad case
if($action['newad']['value']==1 || $action['renewad']['value']==1) {

	if($user_id) $category_id = listings::getCategory($action['ad_id']);
	// if no user and no plan is bought, there must be a mistake
	if($action['newpkg']['value']==0 && !$user_id) return 0;
 
	if($action['newpkg']['value']==0) {
		// check if the user has a subscription $action['pkg_id'] and it can be used for $category_id
		$exists = $db->fetchRow("select `package_id` from ".TABLE_USERS_PACKAGES." where `user_id`='$user_id' and `id`='{$action['pkg_id']}' and ( ads_left>0 or ads_left=-1) and ( `active` = 1 or ( `active`=0 and `pending`=1 ))");

		if(!$exists) return 0;
		$pkg_cats = $db->fetchRow("select `categories` from ".TABLE_PACKAGES." where id='$exists'");
		if($pkg_cats!=0) {
			$cats = explode(",", $pkg_cats);
			if(!in_array($category_id, $cats) ) return 0;
		}

		$plan_id = $exists;

	}
	else {

		$plan = $db->fetchAssoc("select ".TABLE_PACKAGES.".`id`, `amount` from ".TABLE_PACKAGES." left join ".TABLE_USERS_PACKAGES." on ".TABLE_USERS_PACKAGES.".package_id = ".TABLE_PACKAGES.".id where ".TABLE_USERS_PACKAGES.".id='{$action['pkg_id']}'");

		if(!$plan) return 0;
		$amount += $plan['amount'];
		$plan_id = $plan['id'];

	}

	$plan_details = $db->fetchAssoc("select * from ".TABLE_PACKAGES." where id='$plan_id'");
	if(!$plan_details['featured'] && $action['featured']['value']>0) {
		$fp_amount = $db->fetchRow("select `price` from ".TABLE_FEATURED_PLANS." where `id`='{$action['featured']['value']}'");
		$amount+=$fp_amount;
	}
	if(!$plan_details['highlited'] && $action['highlited']['value']==1) {
		$amount+=$ads_settings['highlited_price'];
	}
	if(!$plan_details['video'] && $action['video']['value']==1) {
		$amount+=$ads_settings['video_price'];
	}
	if(!$plan_details['urgent'] && $action['urgent']['value']==1) {
		$amount+=$ads_settings['urgent_price'];
	}
	if(!$plan_details['url'] && $action['url']['value']==1) {
		$amount+=$ads_settings['url_price'];
	}
	if(!$plan_details['priority'] && $action['priority']['value']>0) {
		$pri_amount = $db->fetchRow("select `price` from ".TABLE_PRIORITIES." where `order_no`='{$action['priority']['value']}'");
		$amount+=$pri_amount;
	}

}
// buy or renew subscription case
else if($action['newpkg']['value']==1 || $action['renewpkg']['value']==1) {

	$plan = $db->fetchAssoc("select ".TABLE_PACKAGES.".`id`, `amount` from ".TABLE_PACKAGES." left join ".TABLE_USERS_PACKAGES." on ".TABLE_USERS_PACKAGES.".package_id = ".TABLE_PACKAGES.".id where ".TABLE_USERS_PACKAGES.".id='{$action['pkg_id']}'");
	if(!$plan) return 0;
	$amount += $plan['amount'];
	$plan_id = $plan['id'];

}
// buy extra options case
else if($action['featured']['value']==1 || $action['highlited']['value']==1 || $action['priority']['value']>0 || $action['urgent']['value']==1 || $action['video']['value']==1 || $action['url']['value']==1) {

	if($action['featured']['value']>0) {
		$fp_amount = $db->fetchRow("select `price` from ".TABLE_FEATURED_PLANS." where `id`='{$action['featured']['value']}'");
		$amount+=$fp_amount;
	}
	if($action['highlited']['value']==1) {
		$amount+=$ads_settings['highlited_price'];
	}
	if($action['urgent']['value']==1) {
		$amount+=$ads_settings['urgent_price'];
	}
	if($action['video']['value']==1) {
		$amount+=$ads_settings['video_price'];
	}
	if($action['url']['value']==1) {
		$amount+=$ads_settings['url_price'];
	}
	if($action['priority']['value']>0) {
		$pri_amount = $db->fetchRow("select `price` from ".TABLE_PRIORITIES." where `order_no`='{$action['priority']['value']}'");
		$amount+=$pri_amount;
	}
	
}
// store case
else if($action['store']['value']==1) {

	$amount = $ads_settings['store_price'];
	$discount_type="store";

}
// credits case
else if ($action['new_creditspkg']['value']==1) {
	$plan = $db->fetchAssoc("select `price`, `groups` from ".TABLE_CREDITS_PACKAGES." where id='{$action['credits_pkg_id']}'");
	if(!$plan) return 0;
	// check if the plan is allowed for the group
	if($plan['groups']!=0) {
		$groups = explode(",", $plan['groups']);
		$usr = new users();
		if(!in_array($usr->getGroup($user_id), $groups)) return 0;
	}
	$amount = $plan['price'];
}


// calculate the amount with discount
if($action['discount_code']) {

	//calculate $amount with discount
	$usr = new users();
	$group = $usr->getGroup($user_id);
	// check if valid code
	global $config_abs_path;
	require_once $config_abs_path."/classes/coupons.php";
	if($arr = coupons::codeValid($action['discount_code'], $discount_type, $user_id, $group)) {

		$def_amount=$amount;
		$discount = $arr['discount'];
		if($arr['type'] == "fixed") {

			$amount = $def_amount-$discount;
			if($amount<0) $amount = 0;

		} else { // percent

			if($discount==100) $amount=0;
			else $amount = $def_amount - ($discount*$def_amount)/100;

		}
	}
}

return $amount;
} // end recalculateAmount


function getAttachedListingId($id) {

	global $db;
	$action=$db->fetchRow("select action from ".TABLE_PAYMENT_ACTIONS." where id=".$id);
	$action = unserialize($action);
	
	if(isset($action['ad_id']) && $action['ad_id']) return $action['ad_id'];
	
	return 0;

}

function getSerializedAction($id) {

	global $db;
	$action=$db->fetchRow("select `action` from ".TABLE_PAYMENT_ACTIONS." where id=".$id);
	
	return $action;

}

}
?>
