<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class actions {

public function __construct()
{

}

function getAction($id='') {

	global $db;
	if(!$id) $id=$this->id;
	global $appearance_settings;
	global $lng;
	$date_format=$appearance_settings["date_format_long"];
	$row=$db->fetchAssoc("select *, date_format(`date`,'$date_format') as date_nice from ".TABLE_ACTIONS." where id=".$id);

	global $appearance_settings;
	$currency_pos=$appearance_settings["currency_pos"];
	$default_currency=$appearance_settings["default_currency"];

	if($currency_pos==0) $row['amount_nice']=$default_currency.$row['amount']; // left
	else $row['amount_nice']=$row['amount'].$default_currency; // right

	return $row;

}

function getAll($page, $no_per_page,$order,$order_way, $user_id='') {

}

function add($type, $object_id, $user_id, $invoice, $pending, $extra=''){

	global $db;
	$str_extra="";
	if($extra) $str_extra=", `extra`='$extra'";
	
	// if store, delete previous store actions
	if($type=="store") $db->query("delete from ".TABLE_ACTIONS." where type = '$type' and object_id='$object_id'");

	$timestamp = date("Y-m-d H:i:s");
	$res = $db->query("INSERT INTO ".TABLE_ACTIONS." SET date='$timestamp', type='$type', object_id='$object_id', user_id='$user_id', invoice='$invoice', pending = $pending ".$str_extra);
	return 1;

}

function delete($id='') {

	global $db;
	if(!$id) $id=$this->id;
	$res=$db->query("delete from ".TABLE_ACTIONS." where id=".$id);
	return 1;

}

static function deleteListing($ad_id) {

	global $db;
	$res=$db->query("delete from ".TABLE_ACTIONS." where object_id='$ad_id' and ( `type` like 'newad' or `type` like 'renewad' or `type` like 'featured' or `type` like 'highlited' or `type` like 'priority' or `type` like 'video' or `type` like 'urgent' or `type` like 'url' )");
	return 1;

}

static function deleteUser($user_id) {

	global $db;
	$res=$db->query("delete from ".TABLE_ACTIONS." where object_id='$user_id' and `type` like 'store'");
	return 1;

}

static function deleteSubscription($sub_id) {

	global $db;
	$res=$db->query("delete from ".TABLE_ACTIONS." where object_id='$sub_id' and  ( `type` = 'newpkg' or `type` like 'renewpkg' )");
	return 1;

}

function removePending($id) {

	global $db;
	if(!$id) $id=$this->id;
	$res=$db->query("update ".TABLE_ACTIONS." set pending=0 where id=".$id);
	return 1;

}

function getInvoiceActions($object_id, $type) {
	
	global $db;

	if($type=="invoice") $where_str = " where `invoice` = $object_id";
	else  $where_str = " where `object_id` = $object_id";

	if($type=="ad") $where_str .= " and ( `type` like 'newad' or `type` like 'renewad' or `type` like 'featured' or `type` like 'highlited' or `type` like 'priority' or `type` like 'video' or `type` like 'bump' or `type` like 'urgent' or `type` like 'url' )";
	else if($type=="sub")  $where_str .= " and ( `type` = 'newpkg' or `type` like 'renewpkg') ";
	else if($type=="user") $where_str .= " and ( `type` = 'newpkg' or `type` like 'renewpkg' or type like 'store' or type like 'showcase') ";
	else if($type=="new_creditspkg")  $where_str .= " and `type` = 'new_creditspkg' ";
	//else if($type=="invoice") $where_str = "";


	$array = $db->fetchAssocList("SELECT `".TABLE_ACTIONS."`.*, `".TABLE_PAYMENT_ACTIONS."`.completed from `".TABLE_ACTIONS."` left join `".TABLE_PAYMENT_ACTIONS."` on `".TABLE_PAYMENT_ACTIONS."`.id = `".TABLE_ACTIONS."`.`invoice` $where_str order by `invoice` ,".TABLE_ACTIONS.".`date` desc");

	$actions = array();
	$i=0;

	foreach ($array as $arr) {

		$actions[$i]['id'] = $arr['id'];
		$actions[$i]['user_id'] = $arr['user_id'];
		$actions[$i]['invoice'] = $arr['invoice'];
		$actions[$i]['completed'] = $arr['completed'];
		$actions[$i]['type'] = $arr['type'];
		$actions[$i]['pending'] = $arr['pending'];

		if ( $arr['type']=="newad" || $arr['type']=="renewad" ) {

			$actions[$i]['object_id'] = $arr['object_id'];

		}

		// only if subscription
		if ( $arr['type']=="newpkg" || $arr['type']=="renewpkg" ) {
			$up = new users_packages();
			$pkg_type = $up->getPackageType($arr['object_id']);

			if($pkg_type=="sub") {
			$actions[$i]['object_id'] = $arr['object_id'];
			$up = new users_packages();
			$actions[$i]['name'] = $up->getPackageName($arr['object_id']);
			} else continue;
		}

		// if credits package
		if ( $arr['type']=="new_creditspkg" ) {

			$actions[$i]['object_id'] = $arr['object_id'];
			$actions[$i]['name'] = credits::getPackageName($arr['object_id']);

		}

		if ( $arr['type']=="highlited" || $arr['type']=="video" || $arr['type']=="bump" || $arr['type']=="urgent" || $arr['type']=="url" ) {

			$actions[$i]['object_id'] = $arr['object_id'];

		}

		if ( $arr['type']=="featured" ) {

			$actions[$i]['object_id'] = $arr['object_id'];
			$actions[$i]['featured_plan_id'] = $arr['extra'];
			if($arr['extra']) $actions[$i]['no_days'] = featured_plans::getDaysByOrder($arr['extra']);

		}
		
		if ( $arr['type']=="priority" ) {

			$actions[$i]['object_id'] = $arr['object_id'];
			$actions[$i]['priority_id'] = $arr['extra'];
			if($arr['extra']) $actions[$i]['name'] = priorities::getNameByOrder($arr['extra']);

		}

		if ( $arr['type']=="store" ||  $arr['type']=="showcase" ) {

			$actions[$i]['object_id'] = $arr['user_id'];

		}

		$i++;

	}

	return $actions;
}



function activateInvoiceActions($object_id) {
	
	global $db;

	$array = $db->fetchAssocList("SELECT * from `".TABLE_ACTIONS."` where `invoice` = $object_id order by `date` desc");

	$actions = array();
	$i=0;

	foreach ($array as $arr) {

		$pending = $arr['pending'];
		if(!$pending) continue;

		if ( $arr['type']=="featured" ) {

			$listings = new listings;
			$listings->makeFeatured($arr['object_id'], $arr['extra']);

		}

		if ( $arr['type']=="highlited" ) {

			$listings = new listings;
			$listings->makeHighlited($arr['object_id']);

		}

		if ( $arr['type']=="urgent" ) {

			$listings = new listings;
			$listings->makeUrgent($arr['object_id']);

		}

		if ( $arr['type']=="priority" ) {

			$listings = new listings;
			$listings->enablePriority($arr['object_id'], $arr['extra']);

		}

		if ( $arr['type']=="video" ) {

			$listings = new listings;
			$listings->enableVideo($arr['object_id']);

		}

		if ( $arr['type']=="url" ) {

			$listings = new listings;
			$listings->enableUrl($arr['object_id']);

		}

		if ( $arr['type']=="bump" ) {

			global $config_abs_path;
			require_once $config_abs_path."/modules/bump/classes/bump.php";
			$ba = new bump;
			$ba->bumpAd($arr['object_id']);

		}

		if ( $arr['type']=="showcase" ) {

			global $config_abs_path;
			require_once $config_abs_path."/modules/showcase/classes/showcase.php";
			$sw = new showcase;
			$sw->addShowcase($arr['object_id']);

		}

		if ( $arr['type']=="newad" || $arr['type']=="renewad" ) {

			$listings = new listings;
			$listings->ActivatePending($arr['object_id']);

		}

		if ( $arr['type']=="newpkg" || $arr['type']=="renewpkg" ) {

			$pkg = new users_packages;
			$pkg->ActivatePending($arr['object_id']);

		}

		if ( $arr['type']=="store" ) {

			$usr = new users;
			$usr->enablePendingStore($arr['object_id']);

		}

		if ( $arr['type']=="new_creditspkg" ) {

			global $config_abs_path;
			require_once $config_abs_path."/classes/credits.php";
			require_once $config_abs_path."/classes/users.php";
			$cr = new credits;
			$cr->ActivatePending($arr['id'], $arr['object_id'], $arr['user_id']);

		}

	}

}

static function getPendingListingActions($id) {

	global $db;
	$result = $db->fetchAssocList("select * from ".TABLE_ACTIONS." where `type` in ('newad', 'renewad', 'featured', 'highlited', 'priority', 'video', 'urgent', 'url') and `object_id` = '$id' and `pending` = 1");
	return $result;

}

static function getNotCompletedListingInvoice($id) {

	global $db;
	$inv = $db->fetchRow("select `invoice` from ".TABLE_ACTIONS." where `type` in ('newad', 'renewad') and `object_id` = '$id' and `pending` = 0 order by `date` desc limit 1");
	return $inv;

}

}
?>
