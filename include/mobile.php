<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

function isMobile() {
//return 1;
	global $config_abs_path;
	require_once $config_abs_path."/libs/Mobile_Detect.php";
	
	$detect = new Mobile_Detect;
	$is_mobile = $detect->isMobile();
	if(!$is_mobile) $is_mobile=0;
	return $is_mobile;

}

function isTablet() {

	global $config_abs_path;
	require_once $config_abs_path."/libs/Mobile_Detect.php";
	
	$is_tablet = $detect->isTablet();
	if(!$is_tablet) $is_tablet=0;
	return $is_tablet;

}

function isPhone() {
//return 1;
	// check if mobile device but not tablet
	global $config_abs_path;
	require_once $config_abs_path."/libs/Mobile_Detect.php";
	
	$detect = new Mobile_Detect;
	$is_mobile = $detect->isMobile();
	if(!$is_mobile) return 0;
	
	$is_tablet = $detect->isTablet();
	
	if(!$is_tablet) return 1;
	
	return 0;

}

function checkMobile() {

	global $is_mobile, $mobile_settings;
	$default_is_mobile = $is_mobile;

	// if $is_mobile=1 but $_COOKIE['mobile']= 0 ->set mobile to 0
	if($is_mobile && isset($_COOKIE['mobile']) && $_COOKIE['mobile']==0 && !isset($_GET['set_mobile'])) {

		$is_mobile = 0;
	
	}

	// if $is_mobile=0 but $_COOKIE['mobile']= 1 ->set mobile to 1
	if(!$is_mobile && isset($_COOKIE['mobile']) && $_COOKIE['mobile']==1) {

		$is_mobile = 1;
	
	}

	// check if set_mobile=0 is set
	if($is_mobile && isset($_GET['set_mobile'])) {

		$is_mobile = $_GET['set_mobile'];

		global $main_domain;

		if($default_is_mobile) { // unset the cookie

			$expire = time() + 60*60*24*365;
			setcookie('mobile', $_GET['set_mobile'], $expire, '/', ".".$main_domain);

		} else {

			unset($_COOKIE['mobile']);
			setcookie ('mobile', "", time() - 3600, "/", ".".$main_domain);

		}

		// if accessed with m., redirect towards the domain without m.
		if($mobile_settings['enable_mobile_subdomain'] && !$_GET['set_mobile'] && isset($_GET['mobile']) && $_GET['mobile']==1) {

			global $main_domain;
			header("Location: http://".$main_domain.$_SERVER['REQUEST_URI']);
			exit(0);
			
		}

		return;

	}

	if($mobile_settings['enable_mobile_subdomain']) {

	// if is_mobile is not set but the subdomain is used
	if(!$is_mobile && isset($_GET['mobile']) && $_GET['mobile']==1) {

		$is_mobile = 1;

		global $config_live_site;

		if(!$_COOKIE['mobile']) { 

			global $main_domain;
			$expire = time() + 60*60*24*365;
			setcookie('mobile', 1, $expire, '/', ".".$main_domain);

		}

	} // if is_mobile=1 but the subdomain is not used, redirect to subdomain
	elseif( $is_mobile && (!isset($_GET['mobile']) || !$_GET['mobile'] ) && (!isset($_COOKIE['mobile']) || $_COOKIE['mobile']==1)) { 

		// delete cookie, mobile cookie is used only for special cases
		unset($_COOKIE['mobile']);
		setcookie ('mobile', "", time() - 3600, "/", ".".$main_domain);

		global $main_domain;
		header("Location: http://m.".$main_domain.$_SERVER['REQUEST_URI']);
		exit(0);

	}

	if($is_mobile) {

		global $config_live_site;
		$config_live_site = str_replace("www.", "", $config_live_site);
		$config_live_site = str_replace("http://", "http://m.", $config_live_site);
		$config_live_site = str_replace("https://", "https://m.", $config_live_site);
		$domain_name = $_SERVER["HTTP_HOST"];;

	}

	}// end if mobile subdomains

}

?>