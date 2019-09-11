<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

$path=dirname(__FILE__);
require_once($path.'/../../config.php');
global $config_abs_path;

require_once $config_abs_path."/include/include.php";
require_once $config_abs_path."/classes/mails.php";
require_once $config_abs_path."/classes/groups.php";
require_once $config_abs_path."/classes/users.php";

global $db;
$db = new db_mysql();

$test = 0; $group = 0; $send_to = "all"; $last_id = 0;
if(isset($_POST['last_id'])) $last_id = escape($_POST['last_id']);
if(isset($_POST['test']) && $_POST['test']==1) $test = 1;
if(isset($_POST['no_sent']) && is_numeric($_POST['no_sent'])) $no_sent = $_POST['no_sent']; else $no_sent = 0;
if(isset($_POST['no_failed']) && is_numeric($_POST['no_failed'])) $no_failed = $_POST['no_failed']; else $no_failed = 0;
if(!$test) {
	$group = escape($_POST['group']);
	$send_to = escape($_POST['send_to']);
}

$subject = cleanStr(base64_decode($_POST['subject']));
$message = cleanStr(base64_decode($_POST['message']));

if($test) {
	
	$mail = new mails();
	$mail->init();
	$mail->setSubject($subject);
	$mail->setMessage($message);
	$sent = $mail->send();
	/*$info="";$error="";
	global $lng;
	if($sent) $info = $lng['mailto']['message_sent'];
	else $error = $lng['mailto']['sending_message_failed'];*/
	//echo "-1|".$info."|".$error;
	echo $sent."|-1|0|0";
	exit(0);
}

// get next id from last_id which matches the conditions
// return -1 if no such user
if($group==-1) $nologin = 1;

if(!$nologin) {

	$where = " where 1 = 1 ";
	if($group) $where .= " and `group` = '$group'";

	$group_str = " group by ".TABLE_USERS.".id";

	if($send_to=="all") {

		$join = " LEFT JOIN ".TABLE_ADS." on ".TABLE_USERS.".id = ".TABLE_ADS.".user_id ";
	}
	else
	if($send_to=="active_users") {

		$where .= " and ".TABLE_USERS.".`active` = 1 ";
		$join = " LEFT JOIN ".TABLE_ADS." on ".TABLE_USERS.".id = ".TABLE_ADS.".user_id ";
	
	}
	else
	if($send_to=="active_ads") {

		$join = " LEFT JOIN ".TABLE_ADS." on ".TABLE_USERS.".id = ".TABLE_ADS.".user_id";

	}
	else
	if($send_to=="active_for_sale") {

		$join = " LEFT JOIN ".TABLE_ADS." on ".TABLE_USERS.".id = ".TABLE_ADS.".user_id ";

	}
	else
		if($send_to=="active_for_rent") {

		$join = " LEFT JOIN ".TABLE_ADS." on ".TABLE_USERS.".id = ".TABLE_ADS.".user_id ";

	}

	$where.=" and ".TABLE_USERS.".`id` > $last_id ";

	$usr = new users();
	$sql = "select ".TABLE_USERS.".*, count(".TABLE_ADS.".user_id) as listings from ".TABLE_USERS.$join.$where.$group_str." order by ".TABLE_USERS.".`id` asc limit 1";

	$row = $db->fetchAssoc($sql);
	$row = $db->fetchAssoc($sql);
	if(!$row) { echo "-1"; exit(0); }
	$crt_id = $row['id'];

	if($send_to=="active_ads") {
		$no_ads = $db->fetchRow("select count(id) from ".TABLE_ADS." where user_id=".$crt_id." and `active`=1");
		if(!$no_ads) { echo $crt_id; exit(0); }
	}

	if($send_to=="active_for_sale") {
		$no_ads = $db->fetchRow("select count(id) from ".TABLE_ADS." where user_id=".$crt_id." and `active`=1 and sold=0");
		if(!$no_ads) { echo $crt_id; exit(0); }
	}

	if($send_to=="active_for_rent") {
		$no_ads = $db->fetchRow("select count(id) from ".TABLE_ADS." where user_id=".$crt_id." and `active`=1 and rented=0");
		if(!$no_ads) { echo $crt_id; exit(0); }
	}

	$email = $row['email'];
	global $settings;
	$contact_name = $row[$settings['contact_name_field']];

} // end not nologin
else {

	if($send_to=="active_ads") {

		$where .= " where ".TABLE_ADS.".`active` = 1 ";
	
	}
	else
	if($send_to=="active_for_sale") {

		$where .= " where ".TABLE_ADS.".`active` = 1 and sold = 0 ";

	}
	else
	if($send_to=="active_for_rent") {

		$where .= " where ".TABLE_ADS.".`active` = 1 and rented = 0 ";

	}
	if($where) $where.=" and ";
	else $where.=" where ";
	$where.=TABLE_ADS_EXTENSION.".`mgm_email` > '$last_id' ";

	$sql = "select ".TABLE_ADS_EXTENSION.".* from ".TABLE_ADS_EXTENSION." left join ".TABLE_ADS." on ".TABLE_ADS_EXTENSION.".id = ".TABLE_ADS.".id ".$where." group by ".TABLE_ADS_EXTENSION.".mgm_email order by ".TABLE_ADS_EXTENSION.".`mgm_email` asc limit 1";

	$row = $db->fetchAssoc($sql);
	$row = $db->fetchAssoc($sql);
	if(!$row) { echo "-1"; exit(0); }
	$crt_id = $row['mgm_email'];

	$email = $row['mgm_email'];
	$contact_name = $row['mgm_name'];
}


$mail = new mails();
$mail->init($email, $contact_name);
$mail->setSubject($subject);
$mail->setMessage($message);
$sent = $mail->send();

echo $sent."|".$crt_id."|".$no_sent."|".$no_failed;

?>