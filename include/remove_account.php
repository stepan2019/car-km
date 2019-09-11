<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

require_once "../include/include.php";
global $config_abs_path;
require_once $config_abs_path."/classes/users.php";
require_once $config_abs_path."/classes/mails.php";
require_once $config_abs_path."/classes/mail_templates.php";
require_once $config_abs_path."/classes/info.php";

global $db;
global $lng;
$smarty = new Smarty;
$smarty = common($smarty);
$smarty->assign("lng",$lng);
$smarty->assign("section","other");

$error=''; $info='';
$users=new users();

global $settings;

if(isset($_GET['key'])) { 
	$key=escape($_GET['key']);
	// get user id by key
	$user_id = $users->getKeyUser($key, 2);
	if($user_id==-1) $error=$lng['password_recovery']['invalid_key'];
}
else { $key=''; $error=$lng['password_recovery']['invalid_key']; }

if( $key && !$error ) {

	global $mail_settings;
	$html_mails=$mail_settings["html_mails"];

	$mail2send=new mails();
	$mail2send->init();

	$usr = new users;
	$user = $usr->getUser($user_id);

	$array_subject = array("id"=>$user_id, "user"=>$user, "enable_username" =>$settings['enable_username']);
	$array_message = array("id"=>$user_id, "user"=>$user, "enable_username" =>$settings['enable_username']);

	$mail2send->composeAndSend("admin_account_removal", $array_message, $array_subject);

	$info=info::getVal("account_removal");

}

$smarty->assign("error", $error);
$smarty->assign("info", $info);

if($db->error!='') { $db_error = $db->getError(); $smarty->assign('db_error',$db_error); }
$smarty->display('remove_account.html');

$db->close();
close();
?>
