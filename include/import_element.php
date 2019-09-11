<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/
$path=dirname(__FILE__);
require_once($path.'/../config.php');
global $config_abs_path;
require_once($config_abs_path.'/include/tables.php');
require_once($config_abs_path.'/include/include.php');
require_once($config_abs_path.'/classes/pictures.php');
require_once($config_abs_path.'/classes/images.php');
require_once($config_abs_path.'/classes/settings.php');
require_once($config_abs_path.'/classes/import_export.php');
require_once($config_abs_path.'/classes/packages.php');
require_once($config_abs_path.'/classes/slugs.php');
require_once($config_abs_path.'/classes/validator.php');
if(mysqli_installed())
	require_once($config_abs_path.'/classes/mysqli.php');
else 
	require_once($config_abs_path.'/classes/mysql.php');

global $db;
$db = new db_mysql();

$import_id = get_numeric_only("import_id");
$line = get_numeric_only("line");
$type = escape($_GET['type']); 
if($type!="csv" && $type!="xml") { echo "0"; exit(0); }

$ie = new import_export();
$ie->setType($type);
if($type=="csv")
	$new_line = $ie->CSVImportLine($import_id, $line);
else 	
	$new_line = $ie->XMLImportLine($import_id, $line);
$last_id = $ie->last_id;	

echo $new_line;
if($last_id) echo "|".$last_id;
if($new_line==0) {
	global $db;
	$arr = $db->fetchAssoc("select `succeeded`, `failed` from `".$config_table_prefix."temp_import` where id='$import_id'");
	$succeeded = $arr['succeeded'];
	$failed = $arr['failed'];
	$db->query("delete from `".$config_table_prefix."temp_import` where id='$import_id'");
	echo "|".$succeeded."|".$failed;
}

?>