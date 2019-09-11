<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

require_once "include/include.php";
require_once $config_abs_path."/classes/languages.php";
require_once $config_abs_path.'/admin/include/util.php';
global $db;
global $lng;
$smarty = new Smarty;
$smarty = common($smarty);
$smarty->assign("tab","settings");
$smarty->assign("lng",$lng);
$lang = new languages();
$languages = $lang->getLanguages();
$smarty->assign("array_languages", $languages);
$total = count($languages);
$smarty->assign("total", $total);

if($db->error!='') { $db_error = $db->getError(); $smarty->assign('db_error',$db_error); }
$smarty->display('languages.html');

$db->close();
?>
