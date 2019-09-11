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

// page load start time 

global $config_abs_path;
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
require_once $config_abs_path."/include/seo.php";
require_once $config_abs_path."/include/vars.php";

global $admin_side;
$admin_side = 1;

global $self, $self_noext;
$self = getScriptName();
$self_noext = getScriptNameNoExt();


require_once $config_abs_path.'/classes/auth.php';

global $db;
$db = new db_mysql();
if($db->error!='') { header("Location: $config_live_site/offline.php"); exit(0); }

// get current language
$crt_lang = '';
$crt_lang = languages::getCurrent();

// set main domain
setMainDomain();
global $main_domain;
// get base cached objects: modules and settings
common::getBaseCachedObjects();

require_once $config_abs_path."/add_hooks.php";

global $appearance_settings;
// set default timezone
if($appearance_settings['timezone'] && function_exists('date_default_timezone_set'))
	date_default_timezone_set($appearance_settings['timezone']);

// include language file
$lang = $appearance_settings["admin_language"];

$lang='lang/'.$lang.'.php';


require_once $config_abs_path.'/admin/'.$lang;


// ------------------ MODULES --------------------

$extra_fields_types = array();

// do include actions
do_action("include", array());


?>
