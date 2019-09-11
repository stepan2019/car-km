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
global $config_abs_path;
require_once($config_abs_path.'/classes/fields.php');
require_once($config_abs_path.'/classes/depending_fields.php');
require_once($config_abs_path.'/classes/users.php');
require_once($config_abs_path.'/classes/groups.php');
require_once $config_abs_path."/include/gmaps_util.php";

$id = get_numeric_only("id");

global $lng;
$smarty = new Smarty;
$smarty = common($smarty);
$smarty->assign("lng",$lng);

$user = new users();

if(isset($_POST['Add_info'])) {

    $user-> updateUserInfo($id);

}

$user_array=$user->getUser($id);
$smarty->assign("tmp",$user_array);

$gr = new groups();
$group_settings = $gr->getGroup($user_array['group']);
$activate_via_sms = $group_settings['activate_via_sms'];
// get sms return info
if($activate_via_sms==2) {

	global $config_abs_path;
	require_once($config_abs_path.'/classes/sms_gateways.php');

	// get default SMS gateway
	$sg = new sms_gateways();
	$default = $sg->getDefault();
	if($default) {
			
		$class_name = sms_gateways::getSMSGatewayClass($default);
		require_once($config_abs_path.'/classes/sms_verification/'.$class_name.'.php');
				
		$gcl = new $class_name;
		$sms_result = $gcl->getReturnInfo($id);
		$smarty->assign("sms_result",$sms_result);
	}  // end if default

}

$smarty->assign("activate_via_sms",$activate_via_sms);

$group = $user_array['group'];
$uf=new fields('uf');
$user_fields_array=$uf->getFieldsArray($group, 0);
$smarty->assign("user_fields_array",$user_fields_array);

setGmaps('uf', $group, $smarty);
$smarty->assign("settings",$settings);

$smarty->display('info_user.html');
?>
