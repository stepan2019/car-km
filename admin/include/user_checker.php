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
require_once $config_abs_path."/classes/users.php";
require_once $config_abs_path."/classes/validator.php";

global $appearance_settings;
header('Content-type: text/html; charset='.$appearance_settings['charset']);

if((!isset($_POST['username']) || !$_POST['username']) && (!isset($_POST['email']) || !$_POST['email'])) exit(0);

$usr = new users();
global $settings, $lng;
$error = '';
$info ='';
$user_id = 0;

// check username
if(isset($_POST['username']) && $_POST['username']) {

	$username = escape($_POST['username']);
	if(!$usr->user_exists($username) || $username== $settings["admin_username"]) {

		$error = $lng['users']['errors']['invalid_username'];
		
	}
	else { 
		$info = " ";
		$user_id = $usr->getUserId($username);
	}
}

// check email
if(isset($_POST['email']) && $_POST['email']) {

	$email = escape($_POST['email']);
	if(!$usr->email_exists($email) || $email== $settings["admin_email"])  {

		$error = $lng['users']['errors']['invalid_email'];
		
	}

	else { 
		$info = " ";
		$user_id = $usr->getIdByEmail($email);
	}

}

echo $error."|".$info."|".$user_id;

?>
