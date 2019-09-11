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
require_once $config_abs_path."/classes/fields.php";
require_once $config_abs_path."/classes/depending_fields.php";


global $lng;
$smarty = new Smarty;
$smarty = common($smarty);
$smarty->assign("lng",$lng);

$disable = get_numeric("disable");
$delete = get_numeric("delete");

$smarty->assign("disable",$disable);
$smarty->assign("delete",$delete);

// get active user fields
$uf = new fields("uf");
$fields = $uf->getFieldsOfType('textbox,user_email,username', '', '', ' and `groups` !=-1');
$smarty->assign("fields",$fields);

if(isset($_POST['Submit']) && !empty($_POST['new_contact_field'])) {

	global $settings;
	$old_contact_name = $settings['contact_name_field'];
	$old_contact_name_id = $uf->getIdByCaption($old_contact_name);	

	// change contact name field
	require_once $config_abs_path."/classes/config/settings_config.php";
	settings_config::changeContactNameField(escape($_POST['new_contact_field']));

	if($disable) {
		// disable old contact name field
		require_once $config_abs_path."/classes/config/fields_config.php";
		$ufc = new fields_config("uf");
		$ufc->Disable($old_contact_name_id);
	}

	if($delete) {
		// delete old contact name field
		require_once $config_abs_path."/classes/config/fields_config.php";
		$ufc = new fields_config("uf");
		$ufc->delete($old_contact_name_id);
	}

	$smarty->assign("post", 1);
}

$smarty->display('change_contact_name.html');
?>
