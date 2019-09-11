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
require_once($config_abs_path.'/classes/categories.php');
require_once($config_abs_path.'/classes/groups.php');

$id = get_numeric_only("id");

global $lng;
$smarty = new Smarty;
$smarty = common($smarty);
$smarty->assign("lng",$lng);

$categ_name=cleanStr(categories::getName($id));
$smarty->assign("categ_name",$categ_name);
$category = new categories;
$groups_array=$category->getGroups($id);
if($groups_array==0) $smarty->assign("all_groups",1);
else { $smarty->assign("all_groups",0); $smarty->assign("groups_array",$groups_array); }

$smarty->display('cat_groups_list.html');
?>
