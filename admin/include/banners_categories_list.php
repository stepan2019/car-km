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
require_once($config_abs_path.'/classes/banners.php');
require_once($config_abs_path.'/classes/categories.php');

$id = get_numeric_only("id");

global $lng;
$smarty = new Smarty;
$smarty = common($smarty);
$smarty->assign("lng",$lng);

$banner = new banners($id);
$categories_array=$banner->getCategories();
if($categories_array==0) $smarty->assign("all_categories",1);
else { $smarty->assign("all_categories",0); $smarty->assign("categories_array",$categories_array); }

$sections_array=$banner->getSections();
if($sections_array==0) $smarty->assign("all_sections",1);
else { $smarty->assign("all_sections",0); $smarty->assign("sections_array",$sections_array); }

$smarty->display('banners_categories_list.html');
?>
