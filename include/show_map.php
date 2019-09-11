<?php

/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/
require_once "include.php";
global $config_abs_path;
require_once $config_abs_path."/classes/categories.php";

global $db;
global $lng;

$ad_id = get_numeric_only("aid");

$smarty = new Smarty;
$smarty->caching = 0;
$smarty = common($smarty);
$smarty->assign("lng",$lng);

if(isset($_GET['caption']) && $_GET['caption']) { 

	$caption = $_GET['caption']; 
	$v = array();
	$v['caption'] = $caption;
	$smarty->assign("v", $v);
	
} else exit(0);

$l = new listings();
$listing = $l->getListing($ad_id, 1);
$smarty->assign("listing", $listing);

$smarty->display('show_map.html');
close();

?>