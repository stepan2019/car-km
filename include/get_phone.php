<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

require_once "../config.php";
global $config_abs_path;
require_once $config_abs_path."/include/include_min.php";

global $appearance_settings;
header('Content-type: text/html; charset='.$appearance_settings['charset']);

if(isset($_GET['id']) && is_numeric($_GET['id'])) $user_id = $_GET['id'];
if(isset($_GET['aid']) && is_numeric($_GET['aid'])) $ad_id = $_GET['aid'];
if(isset($_GET['type'])) $type = $_GET['type']; else $type='user';

if(!isset($user_id) && !isset($ad_id)) exit(0);

if(isset($_GET['field'])) 
	$field = escape($_GET['field']);
else exit(0);

global $db;
if(isset($user_id))
	$phone = $db->fetchRow("select `$field` from ".TABLE_USERS." where id='$user_id'");
else {
	if($type=="listing")
		$phone = $db->fetchRow("select `$field` from ".TABLE_ADS." where id='$ad_id'");
	else
		$phone = $db->fetchRow("select `$field` from ".TABLE_ADS_EXTENSION." where id='$ad_id'");
}
echo $phone;

?>