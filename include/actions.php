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

global $appearance_settings;
header('Content-type: text/html; charset='.$appearance_settings['charset']);

my_session_start();

global $config_live_site;
$arr_action= array("add", "delete", "sold", "unsold", "rented", "unrented", "favourite", "report", "delete_file", "enable", "disable");
$arr_object= array("listing", "picture", "favourite", "language", "alert", "search","usr_pkg", "msg", "user", "auction");

if(isset($_GET['object']))  {

	if(!in_array($_GET['object'], $arr_object)) exit(0);
	$object=$_GET['object'];
	
}
else exit(0);

if(isset($_GET['action']))  {

	if(!in_array($_GET['action'], $arr_action)) exit(0);
	$action=$_GET['action'];
	
}
else if($object!="language") exit(0);

$auth=new auth();
if( ( ( isset($action) && $action!="favourite") && ( (isset($action) && $action!="delete" && $action!="add") && $object!="favourite") && $object!="language" ) && !$auth->loggedIn()) { header("Location: ".$config_live_site."/not_authorized.php"); exit(0); }



switch ($object) {
	case 'listing':
		$id = get_numeric_only("id");

		require_once $config_abs_path."/classes/fields.php";
		require_once $config_abs_path."/classes/depending_fields.php";
		require_once $config_abs_path."/classes/categories.php";
		require_once $config_abs_path."/classes/packages.php";
		require_once $config_abs_path."/classes/pictures.php";

		$listing = new listings($id);
		// check if owner
		if(!$auth->loggedIn()) exit(0);
		$crt_usr = $auth->crtUserId();
		if(!$listing->belongsToUser($id, $crt_usr)) exit(0);

		if(!strcmp($action,"delete")) $listing->delete($id);
		else if(!strcmp($action,"sold")) $listing->markSold($id);
		else if(!strcmp($action,"unsold")) $listing->markUnsold($id);
		else if(!strcmp($action,"rented")) $listing->markRented($id);
		else if(!strcmp($action,"unrented")) $listing->markUnrented($id);

		elseif(!strcmp($action, "delete_file")) {

			// delete a file or image from a listing
			$field_name = escape($_GET['field_name']);
			if(!$field_name) exit(0);
			global $db;
			$fname = $db->fetchRow("select `$field_name` from ".TABLE_ADS." where id = '$id'");
			$db->query("update ".TABLE_ADS." set `$field_name`='' where id = '$id'");
			$file_name = $config_abs_path."/uploads/".$field_name."/".$fname;
			if($fname && file_exists($file_name)) @unlink($file_name);

		}

	break;

	case 'user':

		$id = get_numeric_only("id");
		if($action == "delete_file") {

				// delete a file or image from a user info
				$field_name = escape($_GET['field_name']);
				if(!$field_name) exit(0);
				global $db;
				$fname = $db->fetchRow("select `$field_name` from ".TABLE_USERS." where id = '$id'");
				$db->query("update ".TABLE_USERS." set `$field_name`='' where id = '$id'");
				$file_name = $config_abs_path."/uploads/".$field_name."/".$fname;
				if($fname && file_exists($file_name)) @unlink($file_name);

			}

	break;

	case 'picture':
		require_once $config_abs_path."/classes/pictures.php";
		$id = get_numeric_only("id");
		$picture = new pictures();
		if(!strcmp($action,"delete")) $picture->delete($id);
	break;

	case 'favourite':
		$id = get_numeric_only("id");
		$listing = new listings();
		if(!strcmp($action,"delete")) $listing->deleteFavourite($id);
		if(!strcmp($action,"add")) $listing->addFavourite ($id);
	break;

	case 'language':
		$id = get_numeric_only("id");
		global $config_live_site, $main_domain;
		$expire = time() + 60*60*24*365;
		$result = setcookie("default_lang", "$id", $expire, "/", ".".$main_domain);
	break;
	case 'alert':
		require_once $config_abs_path."/classes/alerts.php";
		$id = get_numeric_only("id");
		if($action=="delete") {
			if(!$auth->loggedIn()) exit(0);
			$crt_usr = $auth->crtUserId();
			$alert = new alerts;
			if(!$alert->belongsToUser($id,$crt_usr)) exit(0);
			$alert->delete($id);
		}
	break;
	case 'search':
		require_once $config_abs_path."/classes/saved_searches.php";
		$id = get_numeric_only("id");
		if($action=="delete") {
			if(!$auth->loggedIn()) exit(0);
			$crt_usr = $auth->crtUserId();
			$ss = new saved_searches;
			if(!$ss->belongsToUser($id, $crt_usr)) exit(0);
			$ss->delete($id);
		}
	break;
	case 'usr_pkg':

		require_once $config_abs_path."/classes/users_packages.php";

		$id = get_numeric_only("id");
		$usr_pkg = new users_packages();

		if($action=="delete") {
			// check if owner
			if(!$auth->loggedIn()) exit(0);
			$crt_usr = $auth->crtUserId();
			if(!$usr_pkg->belongsToUser($id, $crt_usr)) exit(0);
			$usr_pkg->delete($id);
		}
	break;
	case 'msg':
		$id = get_numeric_only("id");
		if($action=="delete") {
			require_once "../classes/messages.php";
			
			if(!$auth->loggedIn()) exit(0);
			$crt_usr = $auth->crtUserId();
			global $config_table_prefix;

			$msg = new messages();
			$msg->delete($id, $crt_usr);

		}
		if($action=="report") {

			require_once "../classes/mails.php";
			require_once "../classes/mail_templates.php";
			require_once "../classes/messages.php";

			if(!$auth->loggedIn()) exit(0);
			$crt_usr = $auth->crtUserId();
			$msg = new messages();
			$msg->report($id, $crt_usr);

		}
	break;

	case 'auction':
		$id = get_numeric_only("id");
		if($action=="delete") {
			require_once "../classes/auctions.php";
			
			if(!$auth->loggedIn()) exit(0);
			$crt_usr = $auth->crtUserId();

			$ac = new auctions();
			if(!$ac->belongsToUser($id, $crt_usr)) return 0;

			$ac = new auctions();
			$ac->delete($id);

		}

		if($action=="enable") {

			require_once "../classes/auctions.php";
			
			if(!$auth->loggedIn()) exit(0);
			$crt_usr = $auth->crtUserId();

			$ac = new auctions();
			if(!$ac->belongsToUser($id, $crt_usr)) return 0;

			$ac = new auctions();
			$ac->Enable($id);
			
		}

		if($action=="disable") {

			require_once "../classes/auctions.php";
			
			if(!$auth->loggedIn()) exit(0);
			$crt_usr = $auth->crtUserId();

			$ac = new auctions();
			if(!$ac->belongsToUser($id, $crt_usr)) return 0;

			$ac = new auctions();
			$ac->Disable($id);
			
		}

	break;

}

?>
