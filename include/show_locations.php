<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

require_once "include.php";
require_once "../classes/locations.php";
require_once "../classes/fields.php";
require_once "../classes/depending_fields.php";

global $db;
global $lng;
$info='';
$smarty = new Smarty;
$smarty->caching = 0;
$smarty = common($smarty);
$smarty->assign("lng",$lng);
//_print_r($_COOKIE);

global $appearance_settings;

header('Content-type: text/html; charset='.$appearance_settings['charset']);

$fields = array();
$loc = new locations();
$fields = $loc ->getAllLocations();

$no_columns = 3;

//_print_r($fields);
$smarty->assign("fields", $fields);
$smarty->assign("no_columns", $no_columns);

if($db->error!='') { $db_error = $db->getError(); $smarty->assign('db_error',$db_error); }
$smarty->display('show_locations.html');

$db->close();
close();
?>