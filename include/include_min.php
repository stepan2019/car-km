<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

$path=dirname(__FILE__);
global $minimal;
if(!file_exists($path.'/../config.php')) header("Location: install/");
require_once($path.'/../config.php');
if(!isset($config_abs_path) || !isset($config_live_site))  header("Location: install/");

require_once($config_abs_path.'/include/tables.php');
require_once($config_abs_path.'/include/form.php');

if(mysqli_installed())
	require_once($config_abs_path.'/classes/mysqli.php');
else 
	require_once($config_abs_path.'/classes/mysql.php');

require_once($config_abs_path.'/include/cache.php');
require_once($config_abs_path.'/classes/languages.php');
require_once($config_abs_path.'/classes/common.php');
require_once($config_abs_path.'/classes/slugs.php');
require_once($config_abs_path.'/include/seo.php');
require_once $config_abs_path."/include/vars.php";

global $db;
$db = new db_mysql();

if($db->error!='') header("Location: $config_live_site/offline.php");

// set main domain 
setMainDomain();

global $crt_lang;
$crt_lang = '';
$crt_lang = languages::getCurrent();

// include language file
$lang=$config_abs_path.'/lang/'.$crt_lang.'.php';
require_once($lang);

// get base settings and modules list
global $cached_vars;
common::getBaseCachedObjects();
global $settings;//, $mobile_settings;

/*global $is_mobile;
$is_mobile = 0;
if($mobile_settings['enable_mobile_templates']) {
	require_once($config_abs_path.'/include/mobile.php');
	$is_mobile = common::isMobileVersion();
	if($is_mobile==1 && !isset($_GET['mobile']) && isset($_COOKIE['mobile']) && $_COOKIE['mobile']==0) $is_mobile=0;
}
*/
//require_once $config_abs_path."/add_hooks.php";

// current location
if($settings['enable_locations']) {
	require_once($config_abs_path.'/classes/locations.php');
	$lclass = new locations();
	$lclass->init();
}

require_once $config_abs_path."/add_hooks.php";
// do include actions
do_action("include", array());


?>
