<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

require_once "../include/include.php";

global $lng;
$info='';
$smarty = new Smarty;
$smarty = common($smarty);
$smarty->assign("lng",$lng);

global $appearance_settings;
header('Content-type: text/html; charset='.$appearance_settings['charset']);

$id = get_numeric_only("id");
$logged_in = escape($_GET['logged_in']);

$noindex=0;
if(!$logged_in) $noindex=1;
$smarty->assign("noindex", $noindex);

$listing = new listings();
$listing->addFavourite($id);

$info = $lng['listings']['add_to_fav_done'];
$error = "";
if(!$logged_in) $error = $lng['listings']['confirm_add_to_fav'];

$smarty->assign("id",$id);
$smarty->assign("error", $error);
$smarty->assign("info",$info);
$smarty->display('add_favorite.html');
close();
?>
