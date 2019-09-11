<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/
require_once "include/include.php";
require_once '../classes/validator.php';
require_once '../classes/config/settings_config.php';
require_once '../classes/mails.php';
require_once '../classes/settings.php';
require_once $config_abs_path.'/admin/include/util.php';
global $db;
global $lng;
$smarty = new Smarty;
$smarty = common($smarty);
$smarty->assign("tab","settings");
$smarty->assign("lng",$lng);
$smarty->assign("smenu", "mails");

$errors_str='';
$successful = 0;
if(isset($_POST['Submit'])){
	$sc=new settings_config;
	if(!$sc->editMailSettings()) {
		$errors_str.=$sc->getError();
		$mail_settings=$sc->getTmp();
	}
	else {
		$successful = 1;
	}
}

if(!isset($_POST['Submit']) || $errors_str=='') {

	$mail_settings_cl=new settings();
	$mail_settings=$mail_settings_cl->getMailSettings();
}

$info = '';
if(isset($_POST['Test'])){

	$extra_info="";
	$mail = new mails();
	$mail->init();
	$mail->setSubject('Carpass Email');
	$mail->setMessage('That is the mail for mail option');
	$sent = $mail->send();
	if($sent) $info = $lng['mailto']['message_sent'];
	else $info = $lng['mailto']['sending_message_failed'];

	if(!$sent)	$extra_info = $mail->getSendError()."<br/>".$mail->getDebugMessage();

	$smarty->assign("extra_info",$extra_info);

} // end test

$smarty->assign("mail_settings",$mail_settings);

$smarty->assign("error",$errors_str);
$smarty->assign("info",$info);
$smarty->assign("successful", $successful);

if($db->error!='') { $db_error = $db->getError(); $smarty->assign('db_error',$db_error); }
$smarty->display('mails_settings.html');

$db->close();
close();
?>
