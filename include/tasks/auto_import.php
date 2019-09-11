<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

$path=dirname(__FILE__);
require_once $path."/../include.php";
global $config_abs_path;
require_once $config_abs_path."/classes/scheduled_imports.php";
require_once $config_abs_path."/classes/import_export.php";
require_once $config_abs_path."/classes/users.php";
require_once $config_abs_path."/classes/packages.php";
require_once $config_abs_path."/classes/validator.php";
require_once $config_abs_path."/classes/images.php";

if (defined('STDIN')) {

	// get the id of the import task
	if(isset($argv[1]) && is_numeric($argv[1])) $id = $argv[1]; else exit(0);

	// check if correct key
	if(isset($argv[2])) $key = escape($argv[2]); else exit(0);

}
else {

	// get the id of the import task
	if(isset($_GET['id']) && is_numeric($_GET['id'])) $id = $_GET['id']; else exit(0);

	// check if correct key
	if(isset($_GET['id'])) $key = escape($_GET['key']); else exit(0);
}

$si = new scheduled_imports();
if(!$si->correctKey($id, $key)) exit(0);

$si->autoImport($id);

//get error and write it to log !!!!!!!

?>