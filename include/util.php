<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

require_once $config_abs_path.'/libs/Smarty.class.php';

function common($smarty, $start_session=true) {

	if($start_session) my_session_start();

	// is mobile
	global $is_mobile;
	$smarty->assign("is_mobile",$is_mobile);
	if($is_mobile==1 && !isset($_GET['mobile']) && !isset($_GET['set_mobile'])  && isset($_COOKIE['mobile']) && $_COOKIE['mobile']==0) $is_mobile=0;
	
	// script php name
	global $self_noext, $self, $self_path;
	$smarty->assign("self",$self);
	$smarty->assign("self_noext",$self_noext);
	$smarty->assign("self_path",$self_path);

	// do common1 action
	do_action("common1", array($smarty, $self_noext));

	global $config_live_site, $config_abs_path;//, $meta_format;

	// get meta information pattern
	// will be used to get the page meta information, based on the current page type
//	$meta_format = common::getCachedObject("base_meta_info", array("page" => $self_noext));

	global $cat;
	$cat = getCrtCat();
	$smarty->assign("cat",$cat);

	// assign some language variables
	global $crt_lang, $crt_lang_name, $crt_lang_flag, $crt_lang_code, $text_direction;
	$smarty->assign("crt_lang",$crt_lang);

	$cookie_lang = '';
	if(isset($_COOKIE['default_lang'])) 
		$cookie_lang = $_COOKIE['default_lang'];
	$smarty->assign("cookie_lang", $cookie_lang);

	$smarty->assign("crt_lang_name",$crt_lang_name);
	$smarty->assign("crt_lang_flag",$crt_lang_flag);
	$smarty->assign("crt_lang_code",$crt_lang_code);
	$smarty->assign("text_direction",$text_direction);

	global $config_data_set;
	$smarty->assign("data_set",$config_data_set);

	global $modules_array;
	$smarty->assign("modules_array",$modules_array);

	// get the domain name as it currently accessed 
	// used with ajax functions - avoid browsers xss blocking if the installation domain differs from the current domain
	$crt_site = $_SERVER["HTTP_HOST"];

	$uri = $_SERVER["PHP_SELF"];

	global $mobile_settings;
	if($mobile_settings['enable_mobile_templates']) 
		checkMobile();

	// add or remove www subdomain to $config_live_site depending on the way the site is accessed
	// fix for browsers blocking for cross scripting
	//if(!$is_mobile) {

		// check if accessed with m. subdomain and redirect towards the mobile version
		//if($mobile_settings['enable_mobile_subdomain']) checkIsMobile($smarty);

		if(stristr($crt_site, "www")) {  
			if(!stristr($config_live_site, "www")) {
				$config_live_site = str_replace("http://", "http://www.", $config_live_site);
				$config_live_site = str_replace("https://", "https://www.", $config_live_site);
			}
			$domain_name = str_replace("www.", "", $crt_site);
		}
		else {
			$config_live_site = str_replace("http://www.", "http://", $config_live_site);
			$config_live_site = str_replace("https://www.", "https://", $config_live_site);
			$domain_name = $crt_site;
		}

	//} 

	// current location 
	global $settings;

	if($settings['enable_locations']) {
		$lclass = new locations();
		if($settings['enable_subdomains'] && $settings['subdomain_field']) {

			if(isBot()) $lclass->setConfigLiveSiteNoCookie();
			else {
				// do not check location if bot
				if($self_noext!="show_locations") $lclass->checkCrtLocation($smarty);
				else $lclass->setConfigLiveSite();
			}

		}
		$lclass->initTemplatesVals($smarty);
	}

	if($is_mobile) {
		$smarty->setCompileDir($config_abs_path.'/temp_mobile/');
	} else {
		$smarty->setCompileDir($config_abs_path.'/temp/');
	}
	

	$smarty->assign("live_site",$config_live_site);
	$smarty->assign("crt_site",$crt_site);
	$smarty->assign("abs_path",$config_abs_path);
	if(!$is_mobile)
		$smarty->assign("domain_name",$domain_name);

	global $main_domain;
	$smarty->assign("main_domain",$main_domain);

	global $keyword_name;
	$smarty->assign("keyword_name",$keyword_name);

	// seo settings, allows to you modify search engine friendly links
	global $seo;
	$seo = new seo();
	$seo->init($smarty);
 	$smarty->registerObject('seo', $seo, null, false);

	// set meta info
	if($self_noext!="listings")
		$seo->setMetaInfo($self_noext, $smarty);

	// check if authenticated
	$auth=new auth();
	global $logged_in, $is_admin, $is_mod, $is_aff;
	$logged_in = $auth->loggedIn();
	if($logged_in) { 
		$is_mod = $auth->isMod();
		$is_aff = $auth->isAff();
	}

	if($self_noext!="logout" && !$logged_in && !$is_admin) {
		$logged_in = $auth->checkCookieLogin();
	}

	if(!$logged_in) $is_admin = $auth->adminLoggedIn();
//_print_r($_SESSION);
//echo $logged_in."-------";

	$smarty->assign("logged_in", $logged_in);
	$smarty->assign("is_admin", $is_admin);
	$smarty->assign("is_mod", $is_mod);
	$smarty->assign("is_aff", $is_aff);

	// vars for user account navbar
	if($logged_in) {

		global $crt_usr, $usr_sett;
		$crt_usr = $auth->crtUserId();
		
		// check if user blocked
		if(users::isBlocked($crt_usr) && $self_noext!="access_restricted") { header("Location: ".$config_live_site."/access_restricted.php"); exit(0); }

		
		$smarty->assign("crt_usr",$crt_usr);
		$usr_sett = common::getUserSettings($crt_usr);
		$smarty->assign("crt_usr_sett",$usr_sett);

		$subscription_exists = common::subscriptionExists($usr_sett['group']);
		$smarty->assign("subscription_exists",$subscription_exists);

		$smarty->assign("bulk_uploads",$usr_sett['bulk_uploads']);

	}

	common::getInterfaceCachedObjects();

	if(!$logged_in || $is_admin) $crt_group = '1000';
	else { $crt_group = $usr_sett['group']; }
	$result = common::getCachedObject("base_cp", array("group"=>$crt_group));
	global $main_navbar_links, $secondary_navbar_links;
	$main_navbar_links = $result['main_navbar_links'];
	$secondary_navbar_links = $result['secondary_navbar_links'];
	
	// number of auto register groups
	global $config_vars;
	$smarty->assign("no_groups", $config_vars['no_groups']);

	// assign settings
	global $ads_settings, $settings, $appearance_settings, $seo_settings, $invoice_settings;
	$smarty->assign("ads_settings",$ads_settings);
	$smarty->assign("settings",$settings);
	$smarty->assign("appearance",$appearance_settings);
	$smarty->assign("seo_settings",$seo_settings);
	$smarty->assign("invoice_settings",$invoice_settings);

	if($is_mobile) {
		$smarty->assign("mobile_settings",$mobile_settings);
	}

	// ---------- quick search ------------
	global $default_fields_types;
	$smarty->assign("default_fields_types", $default_fields_types);

	if(!$is_mobile || $self_noext=="listings" || $self_noext=="refine" || $self_noext=="newad") {

		global $short_categories;
		$short_categories=common::getCachedObject("base_short_categories");
		$smarty->assign("categories",$short_categories);
	
	} // end if not mobile

	$array_include_currencies = array("listings", "refine", "new_listing", "edit_listing", "details", "add_auction", "recent_ads");
	if( in_array($self_noext, $array_include_currencies) && $ads_settings['enable_price']) {
		global $currencies;
		$smarty->assign("currencies", $currencies);
	}

	//if(!$is_mobile) {
	global $qs_fields;
	$smarty->assign("qfields", $qs_fields);
	$smarty->assign("multi_depending", $config_vars['multi_depending']);
	//} // end if not mobile
	
	// ***** Uncomment if you want to use users/dealers search on quick search
	// ***** asign to group_id the id of user group you want to show for search
	/*
	$group_id=0;
	$qusers = users::getAll($group_id);
	$smarty->assign('qusers',$qusers);
	*/

	// ---------- end quick search ------------

	// footer categories links
	if(!$is_mobile && $appearance_settings['show_footer_categ']==1) {
		global $footer_links;
		$smarty->assign("footer_links", $footer_links);
	}

	$bloc='';
	$smarty->assign("bloc",$bloc);

	// banners
	global $banner_positions;
//	if($banner_positions) {
	$smarty->assign("banners_positions", $banner_positions);
	require_once($config_abs_path.'/classes/banners.php');
	$banner = new banners();
	$smarty->registerObject('banner', $banner, null, false);

//	}

	// navigation bar links
	//if(!$is_mobile) {
		$smarty->assign("main_navbar",$main_navbar_links);
		$smarty->assign("sec_navbar",$secondary_navbar_links);
	//}

	// get active languages
	global $languages;
	//if(empty($languages)) $languages = common::getCachedObject("base_languages");
	$smarty->assign("languages",$languages);

	// get rss feeds
	if(!$is_mobile) {

		global $rss;
		$smarty->assign("rss_array",$rss);
		$smarty->assign("no_rss",count($rss));

		// favorites array
		$fav_array = listings::getFavouritesArray();
		$smarty->assign("fav_array",$fav_array);


	}

	// check if phone
	require_once $config_abs_path."/include/mobile.php";
	$is_phone = isPhone();
	$smarty->assign("is_phone",$is_phone);
	
	$smarty->debugging = false;
	if($is_mobile) {
		$template_folder = "templates_mobile";
		$template_name = $mobile_settings['mobile_template'];
	} else {
		$template_folder = "templates";
		$template_name = $appearance_settings['template'];
	}
	$smarty->template_dir = $config_abs_path."/$template_folder/".$template_name;

	$smarty->assign("template_path",$config_live_site."/$template_folder/".$template_name."/");

	// if maintenance mode
	if( $appearance_settings['maintenance_mode']==1 && $self_noext!="login" && $self_noext!="logout") {
	
		if( !in_array(escape(getRemoteIp()), $appearance_settings['maintenance_ips_array']) ) {
		
			$smarty->display('maintenance.html');
			close();
			exit(0);
		
		}
	
	}

	do_action("init_template", array($smarty, $self_noext));
	
	return $smarty;
}


function close() {

	return;
	// ------------------ page load end time --------------------
	global $config_debug;
	if($config_debug) {
		$time = microtime();
		$time = explode(" ", $time);
		$time = $time[1] + $time[0];
		$finishload = $time;
		global $startload;
		$totaltime = ($finishload - $startload);
		printf ("This page took %f seconds to load.", $totaltime);
		$mem_usage=number_format(memory_get_usage()/1000000, 2);
		echo " Memory usage: ".$mem_usage." M";
	}

}

function getCrtCat() {

	global $self_noext, $seo_settings, $settings;
	$cat = '';
	$cat = get_numeric("category", 0);
	// if SEF urls are enabled and we are on listings.php page, get category id
	if($self_noext == "listings" && $seo_settings['enable_mod_rewrite'] && $seo_settings['sef_legacy_mode']) {

		$args = explode("/",$_SERVER['QUERY_STRING']);
		$kv = $args[0];
		if($settings['enable_locations'] && $settings['enable_subdomains']) {
			$keyval_arr = explode("&", $kv);
			$kv = $keyval_arr[count($keyval_arr)-1];
		}

		$keyval = explode("-",$kv);
		if(count($keyval)>=2 && is_numeric($keyval[0])) $cat = escape($keyval[0]);

	}
	// get category if listing details page
	if($self_noext == "details") {
		if(isset($_GET['category_slug']) && $_GET['category_slug'])
			$cat = slugs::getCategoryId($_GET['category_slug']);
		else	
			$cat = listings::getCategory(escape($_GET['id']));
	}
	// get category if listing index page
	if($self_noext == "index" && isset($_GET['category_slug']) && $_GET['category_slug']) {
		$cat = slugs::getCategoryId($_GET['category_slug']);
	}
	return $cat;
}



function common_no_template( $start_session = 1 ) {

	if($start_session) my_session_start();

	// stop access if ip is blocked 
	if(common::IPisBlocked(escape(getRemoteIp())) && $self_noext!="access_restricted") {

		exit(0);

	}

	global $languages;
	if(empty($languages)) $languages = common::getCachedObject("base_languages");

	// check if authenticated
	$auth=new auth();
	global $logged_in, $is_admin;
	$logged_in = $auth->loggedIn();
	global $self_noext, $self;
	$is_admin = $auth->adminLoggedIn();
	if($self_noext!="logout" && !$logged_in && !$is_admin) {
		$logged_in = $auth->checkCookieLogin();
	}

	if($logged_in) {

		global $crt_usr, $usr_sett;
		$crt_usr = $auth->crtUserId();
		$usr_sett = common::getUserSettings($crt_usr);

	}

}
?>
