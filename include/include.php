<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/
//require_once "../include/include.php";
$path = dirname(__FILE__);
if (!file_exists($path . '/../config.php')) {
    header("Location: install/");
    exit(0);
}
require_once($path . '/../config.php');
global $config_live_site;
if (!isset($config_abs_path) || !isset($config_live_site)) {
    header("Location: install/");
    exit(0);
}

// page load start time 
make_startload();

require_once($config_abs_path . '/include/tables.php');
require_once($config_abs_path . '/include/form.php');

if (mysqli_installed())
    require_once($config_abs_path . '/classes/mysqli.php');
else
    require_once($config_abs_path . '/classes/mysql.php');
require_once($config_abs_path . '/include/cache.php');
require_once($config_abs_path . '/classes/languages.php');
require_once($config_abs_path . '/classes/common.php');
require_once($config_abs_path . '/classes/slugs.php');
require_once($config_abs_path . '/include/seo.php');
require_once $config_abs_path . "/include/vars.php";

global $self, $self_noext;
$self = getScriptName();
$self_noext = getScriptNameNoExt();

$path = parse_url($config_live_site);
$self_path = '';
if (isset($path['path'])) $self_path = $path['path'];

require_once($config_abs_path . '/include/util.php');
require_once($config_abs_path . '/classes/listings.php');
require_once($config_abs_path . '/classes/auth.php');

global $db;
$db = new db_mysql();

if ($db->error != '') header("Location: $config_live_site/offline.php");

// stop access if ip is blocked 
if (common::IPisBlocked(escape(getRemoteIp())) && $self_noext != "access_restricted") {

    header("Location: access_restricted.php");
    exit(0);

}

global $crt_lang, $crt_lang_name, $crt_lang_flag, $crt_lang_code, $text_direction;

// set main domain 
setMainDomain();
global $main_domain;

// set language using GET method
$crt_lang = '';
setGetLanguage();

// get current language
if (!$crt_lang) $crt_lang = languages::getCurrent();
$lang = $config_abs_path . '/lang/' . $crt_lang . '.php';
if ($crt_lang) {
    $lang_arr = languages::getLanguage($crt_lang);
    $crt_lang_name = $lang_arr['name'];
    $crt_lang_flag = $lang_arr['image'];
    $crt_lang_code = $lang_arr['code'];
    $text_direction = $lang_arr['direction'];
}

// get base settings and modules list
//global $cached_vars;
common::getBaseCachedObjects();
global $settings, $mobile_settings, $appearance_settings;
global $is_mobile;
$is_mobile = 0;

if ($mobile_settings['enable_mobile_templates']) {

    require_once($config_abs_path . '/include/mobile.php');
    $is_mobile = common::isMobileVersion();

}

// affiliates links
if (isset($_GET['aff']) && $_GET['aff']) {
    $aff_id = slugs::getUserId(escape($_GET['aff']));
    $affiliate_settings = common::getAffiliateSettings($aff_id);
    $expire = time() + 60 * 60 * 24 * $affiliate_settings["affiliates_cookie_availability"];
    setcookie('affiliate', $aff_id, $expire, '/', "." . $main_domain);
}

require_once $config_abs_path . "/add_hooks.php";

// current location
if ($settings['enable_locations']) {
    require_once($config_abs_path . '/classes/locations.php');
    $lclass = new locations();
    $lclass->init();
}

// set default timezone
setTimezone();

// include language file
require_once($lang);

// do include actions
do_action("include", array());

// ******** simulate scheduler **********
// run once a day, first time when the script is accessed
// and the value of LAST_DAY in last.php is not today date.
//
simulateScheduler();

function setGetLanguage()
{

    global $main_domain;
    if (isset($_GET['lang'])) {
        $get_lang = escape(rawurldecode($_GET['lang']));
        if ($get_lang != $_COOKIE['default_lang']) {
            $expire = time() + 60 * 60 * 24 * 365;
            setcookie("default_lang", $get_lang, $expire, "/", "." . $main_domain);
            $crt_lang = $get_lang;
        }
    }

}

function simulateScheduler()
{
    // run once a day, first time when the script is accessed
    // and the value of LAST_DAY in last.php is not today date.
    //
    global $settings;
    if ($settings['cron_simulator']) {

        global $config_abs_path;
        require_once($config_abs_path . '/last.php');
        $day = date('d');
        if ((int)LAST_DAY != (int)$day) {
//            write_last($day);
            require_once($config_abs_path . '/periodic.php');
        }
    }
}

function make_startload()
{
    global $config_debug;
    if ($config_debug) {
        $time = microtime();
        $time = explode(" ", $time);
        $time = $time[1] + $time[0];
        global $startload;
        $startload = $time;
    }
}

// used in files which don't run the common() function from util.php but need auth information
function checkAuth()
{

    global $is_admin, $logged_in, $crt_usr;
    $auth = new auth();
    $logged_in = $auth->loggedIn();
    $is_admin = $auth->adminLoggedIn();
    if (!$logged_in && !$is_admin)
        $logged_in = $auth->checkCookieLogin();

    // vars for user account navbar
    if ($logged_in) $crt_usr = $auth->crtUserId();

}

?>
