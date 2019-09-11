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

$listing = new listings();
$listing->deleteFavourite($id);

$info = $lng['listings']['remove_fav_done'];

$smarty->assign("id",$id);
$smarty->assign("info",$info);

$smarty->display('remove_favorite.html');
close();
?>
