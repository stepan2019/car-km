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
require_once($config_abs_path.'/include/cache.php');
require_once($config_abs_path.'/classes/common.php');
require_once($config_abs_path.'/classes/pictures.php');
require_once($config_abs_path.'/classes/images.php');
require_once($config_abs_path.'/classes/settings.php');
require_once($config_abs_path.'/classes/languages.php');
require_once($config_abs_path.'/include/form.php');
if(mysqli_installed())
	require_once($config_abs_path.'/classes/mysqli.php');
else 
	require_once($config_abs_path.'/classes/mysql.php');

global $db;
$db = new db_mysql();
global $crt_lang;
if(!$crt_lang) $crt_lang = languages::getCurrent();

global $ads_settings;
$ads_settings=settings::getAdsSettings();

$pic = new pictures;

if(isset($_GET['image']) && is_numeric($_GET['image'])) 
	$image = escape($_GET['image']); 
else { // first image
	$image = $pic->prepareRegenerate();
}
$watermark = 0;
if($_GET['watermark']==0 || $_GET['watermark']==1) $watermark = $_GET['watermark'];

$pic->regeneratePicture($image, $watermark);
$image = $db->fetchRow("select `id` from ".TABLE_ADS_PICTURES." where id < $image order by id desc limit 1");
if($image) echo $image;
else echo '0';

?>