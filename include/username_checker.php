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
require_once $config_abs_path."/classes/users.php";
require_once $config_abs_path."/classes/validator.php";

global $appearance_settings;
header('Content-type: text/html; charset='.$appearance_settings['charset']);

if((!isset($_POST['username']) || !$_POST['username'])) exit(0);

global $settings, $lng;

$error = '';
$info ='';

$username = escape($_POST['username']);

$usr = new users();
if($usr->user_exists($username) || $username== $settings["admin_username"]) 
	$error = $lng['users']['username_not_available'];

else 
if(!validator::valid_username($username)) 
	$error = $lng['users']['errors']['invalid_username'];

if(!$error) $info = $lng['users']['username_available'];

echo $error."|".$info;
?>