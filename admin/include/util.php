<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

require_once $config_abs_path.'/libs/Smarty.class.php';

function common($smarty, $page="") {

	my_session_start();

	global $config_live_site, $config_abs_path, $config_demo, $config_data_set;
	$smarty->compile_dir=$config_abs_path.'/admin/temp/';
	$smarty->assign("live_site",$config_live_site);
	$smarty->assign("abs_path",$config_abs_path);
	$smarty->assign("demo", $config_demo);

	global $main_domain;
	$smarty->assign("main_domain",$main_domain);

	$auth=new auth();
	global $is_admin, $is_mod;
	$is_admin = $auth->adminLoggedIn();
	$is_mod = $auth->modLoggedIn();

//$is_admin=1;
	global $self, $self_noext;
	$smarty->assign("self",$self);
//	if(!$is_admin && !$is_mod) { header("Location: ".$config_live_site."/login.php?loc=admin/".$self); exit(0); }

	global $mod_sections;

	if($is_mod) {

		$usr = new users; 
		$mod_id = $auth->crtUserId();
		$mod_sections = $usr->getSections($mod_id);

		$sections_array = $usr->getSectionsArray($mod_sections);
		$smarty->assign("mod_sections", $mod_sections);
		$smarty->assign("mod_id",$mod_id);
		$smarty->assign("sections_array", $sections_array);

	}

	$smarty->assign("is_admin",$is_admin);
	$smarty->assign("is_mod",$is_mod);

	global $self_noext;
	if($is_mod && $self_noext!="not_authorized") {

		if($self_noext!="index" && $self_noext!="get_info" && $self_noext!="actions") {

			$tabs = array();
			$sections = array("listings", "users", "subscriptions", "searches", "alerts", "messages", "orders", "banners", "security", "custom_pages", "bulk_emails", "import_export", "news", "multicurrency");
			$pages = array(
			"adduser" => array("section"=>"users", "action"=>"add"),
			"add_user_subscription" => array("section"=>"subscriptions", "action"=>"add"),
			"blocked_ips" => array("section"=>"security", "action"=>"view"),
			"blocked_emails" => array("section"=>"security", "action"=>"view"),
			"forbidden_words" => array("section"=>"security", "action"=>"view"),
			"edit_ad_settings" => array("section"=>"listings", "action"=>"edit"),
			"edit_photos" => array("section"=>"listings", "action"=>"edit"),
			"edit_listing" => array("section"=>"listings", "action"=>"edit"),
			"edituser" => array("section"=>"users", "action"=>"edit"),
			"login_history" => array("section"=>"security", "action"=>"view"),
			"login_history_user" => array("section"=>"security", "action"=>"view"),
			"mailto" => array("section"=>"users", "action"=>"view"),
			"add_banner" => array("section"=>"banners", "action"=>"add"),
			"edit_banner" => array("section"=>"banners", "action"=>"edit"),
			"view_banner" => array("section"=>"banners", "action"=>"view"),
			"banners_categories_list" => array("section"=>"banners", "action"=>"view"),
			"manage_banners" => array("section"=>"banners", "action"=>"view"),
			"manage_listings" => array("section"=>"listings", "action"=>"view"),
			"manage_saved_searches" => array("section"=>"searches", "action"=>"view"),
			"manage_email_alerts" => array("section"=>"alerts", "action"=>"view"),
			"message_history" => array("section"=>"messages", "action"=>"view"),
			"messages" => array("section"=>"messages", "action"=>"view"),
			"edit_message" => array("section"=>"messages", "action"=>"edit"),
			"new_listing" => array("section"=>"listings", "action"=>"add"),
			"order_history" => array("section"=>"orders", "action"=>"view"),
			"info_order" => array("section"=>"orders", "action"=>"view"),
			"selective_invoice_accept" => array("section"=>"orders", "action"=>"edit"),
			"subscriptions" => array("section"=>"subscriptions", "action"=>"view"),
			"users_list" => array("section"=>"users", "action"=>"view"),
			"cat_users_list" => array("section"=>"users", "action"=>"view"),
			"change_password" => array("section"=>"users", "action"=>"edit"),
			"info_user" => array("section"=>"users", "action"=>"view"),
			"view_banner" => array("section"=>"banners", "action"=>"view"),
			"view_message" => array("section"=>"messages", "action"=>"view"),
			"whitelist_ips" => array("section"=>"security", "action"=>"view"),
			"whitelist_emails" => array("section"=>"security", "action"=>"view"),
			"manage_custom_pages" => array("section"=>"custom_pages", "action"=>"view"),
			"view_custom_page" => array("section"=>"custom_pages", "action"=>"view"),
			"add_custom_page" => array("section"=>"custom_pages", "action"=>"add"),
			"edit_custom_page" => array("section"=>"custom_pages", "action"=>"edit"),
			"edit_content" => array("section"=>"custom_pages", "action"=>"edit"),
			"news_index" => array("section"=>"news", "action"=>"view"),
			"add_news" => array("section"=>"news", "action"=>"add"),
			"edit_news" => array("section"=>"news", "action"=>"edit"),
			"bulk_emails" => array("section"=>"bulk_emails", "action"=>"add"),
			"import-export" => array("section"=>"import_export", "action"=>"add"),
			"import" => array("section"=>"import_export", "action"=>"add"),
			"multicurrency" => array("section"=>"multicurrency", "action"=>"edit"),
			"modules" => array("section"=>"any", "action"=>"any"),
			"review_pending_edited" => array("section"=>"listings", "action"=>"edit")
			);

			$page_section = $pages[$self_noext]["section"];
			$page_action = $pages[$self_noext]["action"];

			if(!$page_section) { header("Location: ".$config_live_site."/admin/not_authorized.php"); exit(0); }
			if( $page_section!= "any" && $mod_sections[$page_section][$page_action]==0) { 
				$fwd_to = array("users_list" => "subscriptions", "subscriptions" => "manage_saved_searches", "manage_saved_searches" => "email_alerts", "email_alerts" => "messages");
				foreach($fwd_to as $key => $value) {

					if($self_noext == $key) { header("Location: ".$config_live_site."/admin/".$value.".php"); exit(0);  }
				
				}
				header("Location: ".$config_live_site."/admin/not_authorized.php"); exit(0); 
			}
		}
	}


	$smarty->assign("data_set",$config_data_set);

	global $modules_array;
	$smarty->assign("modules_array",$modules_array);
	global $seo;
	$seo = new seo();
	$seo->init($smarty);
	$smarty->registerObject('seo', $seo, null, false);

	common::getInterfaceCachedObjects();

	// get settings
	global $appearance_settings, $ads_settings, $settings, $seo_settings, $mobile_settings;
	$smarty->assign("ads_settings",$ads_settings);
	$smarty->assign("settings",$settings);
	$smarty->assign("appearance",$appearance_settings);
	$smarty->assign("seo_settings",$seo_settings);
	$smarty->assign("mobile_settings",$mobile_settings);

	if($is_admin) $logged_in = $settings['admin_name'];
	else $logged_in = $is_mod;
	$smarty->assign("logged_in",$logged_in);


	// set default timezone
	if($appearance_settings['timezone'] && function_exists('date_default_timezone_set'))
		date_default_timezone_set($appearance_settings['timezone']);

	global $languages;
	$languages = common::getCachedObject("base_languages");
	$smarty->assign("languages",$languages);

	// do include actions
	do_action("init_admin_template", array($smarty, $self_noext));

	$smarty->debugging = false;
	$smarty->template_dir = $config_abs_path."/admin/templates/".$appearance_settings['admin_template'];
	$smarty->assign("template_path",$config_live_site."/admin/templates/".$appearance_settings['admin_template']."/");

	$crt_year=date("Y");
	$smarty->assign("crt_year",$crt_year);
	global $crt_lang;
	$smarty->assign("crt_lang",$crt_lang);

	return $smarty;

}


function common_no_template( $start_session = 1 ) {

	if($start_session) my_session_start();

	global $languages;
	if(empty($languages)) $languages = common::getCachedObject("base_languages");

	$auth=new auth();
	global $is_admin, $is_mod;
	$is_admin = $auth->adminLoggedIn();
	$is_mod = $auth->modLoggedIn();

	//$is_admin=1;
	global $self, $self_noext;
	if(!$is_admin && !$is_mod) { exit(0); }

}

function close() {
//		db_close();
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

?>
