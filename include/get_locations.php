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
require_once $config_abs_path."/classes/locations.php";

global $appearance_settings;
header('Content-type: text/html; charset='.$appearance_settings['charset']);

global $settings;

global $no_location_fields;
if(!$no_location_fields) { 
	$loc = new locations();
	$no_location_fields = $loc->noFields();
}

if($no_location_fields==1) {

	// can be menu only
	// get directly the field for subdomains
	$l = new locations;
	$location_str = $l->getLocations($settings['location_fields']);

} 

echo $location_str;

?>