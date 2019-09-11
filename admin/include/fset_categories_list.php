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
require_once($config_abs_path.'/classes/fieldsets.php');

$id = get_numeric_only("id");

global $lng;
$smarty = new Smarty;
$smarty = common($smarty);
$smarty->assign("lng",$lng);

$fieldset_name=fieldsets::getName($id);
$smarty->assign("fieldset_name",$fieldset_name);

$categories_array=common::getCachedObject("base_short_categories_fset", array("fieldset" => $id));
if(!$categories_array) $smarty->assign("no_categories",1);
else { $smarty->assign("no_categories",0); $smarty->assign("categories_array",$categories_array); }

$smarty->display('fset_categories_list.html');
?>
