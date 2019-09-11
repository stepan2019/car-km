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
require_once($config_abs_path.'/include/tables.php');
require_once($config_abs_path.'/classes/settings.php');
require_once($config_abs_path.'/classes/slugs.php');
require_once($config_abs_path.'/classes/users.php');
require_once($config_abs_path.'/classes/listings.php');
require_once($config_abs_path.'/include/form.php');
if(mysqli_installed())
	require_once($config_abs_path.'/classes/mysqli.php');
else 
	require_once($config_abs_path.'/classes/mysql.php');

global $db;
$db = new db_mysql();

global $ads_settings;
$ads_settings=settings::getAdsSettings();

$s=new slugs();
if(isset($_GET['type'])) $type=$_GET['type']; else $type='listing';

if(isset($_GET['crt']) && is_numeric($_GET['crt'])) 
	$crt = escape($_GET['crt']);
else { // first id
	$crt = $s->prepareRegenerate($type);
	$s->makeSlug($crt, $type);
	//echo $crt;
}

// generate 200 slugs at once
$no = 200;
if($type=="listing")
	$array_id = $db->fetchRowList("select `id` from ".TABLE_ADS." where id < $crt order by id desc limit $no");
else
	$array_id = $db->fetchRowList("select `id` from ".TABLE_USERS." where id < $crt order by id desc limit $no");
//_print_r($array_id);
if(!$array_id) { echo '0'; exit(0); }

foreach($array_id as $id) {

	$s->makeSlug($id, $type);

}

// return the last id
echo $id;

?>