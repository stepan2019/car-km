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
require_once $config_abs_path."/include/include.php";
require_once $config_abs_path."/classes/locations.php";

global $appearance_settings, $settings;
header('Content-type: text/html; charset='.$appearance_settings['charset']);

global $settings;
$fields = $settings['location_fields'];
$sarr = explode(",", $fields);
// refine search
if(isset($_POST['direct']) && $_POST['direct']) {

	if((!isset($_POST['field']) || !$_POST['field']) || !in_array($_POST['field'], $sarr) ) exit(0);
	$field = urldecode($_POST['field']);
	$crt_loc = urldecode($_POST['location']);
	$new_locations_array[$field] = $crt_loc;

}
elseif(isset($_POST['double_type']) && $_POST['double_type']) {

	if((!isset($_POST['field1']) || !$_POST['field1'] || !isset($_POST['field2']) || !$_POST['field2']) || !in_array($_POST['field1'], $sarr) || !in_array($_POST['field2'], $sarr) ) exit(0);
	$field1 = urldecode($_POST['field1']);
	$crt_loc1 = urldecode($_POST['location1']);
	$field2 = urldecode($_POST['field2']);
	$crt_loc2 = urldecode($_POST['location2']);
	$new_locations_array[$field1] = $crt_loc1;
	$new_locations_array[$field2] = $crt_loc2;

}
// location filter box
else {
	$new_locations_array = array();

	foreach($sarr as $s) {
	
		$new_locations_array[$s] = $_POST[$s];
	
	}
}
my_session_start();
$lclass = new locations();
$lclass->init();
$lclass->setPostLocation($new_locations_array);
?>