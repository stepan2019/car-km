<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class common {

	public function __construct()
	{
	
	}

//
// ******** modules ********
//

	static function getModulesList() {
		global $db;
		$result = $db->fetchRowList("select `id` from ".TABLE_MODULES." where `enabled`=1");
		return $result;
	}

//
// ******** blocked IPs ********
//

	static function IPisBlocked($ip) {
		
		global $db;
		$res = $db->query("select * from ".TABLE_BLOCKED_IPS." where ip like '$ip' ");
		if($db->numRows($res)) return 1;
		return 0;
	}
//
// ******** groups ********
//

	static function noAutoRegisterGroups() {

		global $db, $settings;

		$str = "";
		if(!$settings['enable_affiliates'])  $str = " and `affiliates`=0"; 

		$no=$db->fetchRow('select count(*) from '.TABLE_USER_GROUPS.' where auto_register=1 and active=1 '.$str);
		return $no;
	}

	static function getCurrencies() {

		global $db;
		$arr=$db->fetchAssocList("select * from ".TABLE_CURRENCIES." order by `order_no`");
		return $arr;
	}

	static function getRss() {

		global $db;
		global $crt_lang;
		$rss_array=$db->fetchAssocList("select * from ".TABLE_RSS." LEFT JOIN ".TABLE_RSS."_lang on ".TABLE_RSS.".`id` = ".TABLE_RSS."_lang.`id` where `lang_id`='$crt_lang' and enabled=1");

		if(!$rss_array) return array();

		$no = count($rss_array);
		$result = array();
		$i=0;
		global $config_live_site;
		foreach ($rss_array as $row) {
			$result[$i] = $row;
			$result[$i]['rss_link'] = $config_live_site."/feed.php";
			$result[$i]['title']=cleanStr($result[$i]['title']);
			$result[$i]['short_title']=cleanStr($result[$i]['short_title']);
			if($no>1) $result[$i]['rss_link'].="?id=".$result[$i]['id'];
			$i++;
		}
	
		return $result;

	}

	static function getNavbarLinks($type, $group_id=0) {

		global $db;
		global $crt_lang;

		$group_regexp="";
		if($group_id!=-1) {
		
		if($group_id)
			$group_regexp = " and (`groups` REGEXP '\[\[:<:\]\]$group_id\[\[:>:\]\]' or `groups`=0 )";
		else 
			$group_regexp = " and `groups`=0 ";
		
		} // end if -1
			
		$result=$db->fetchAssocList("select ".TABLE_CUSTOM_PAGES.".*, ".TABLE_CUSTOM_PAGES."_lang.title, ".TABLE_SLUGS.".slug 
		from ".TABLE_CUSTOM_PAGES." 
		LEFT JOIN ".TABLE_CUSTOM_PAGES."_lang on ".TABLE_CUSTOM_PAGES.".`id` = ".TABLE_CUSTOM_PAGES."_lang.`id`
		LEFT JOIN ".TABLE_SLUGS." on ".TABLE_CUSTOM_PAGES.".`id` = ".TABLE_SLUGS.".`object_id` 
		where ".TABLE_CUSTOM_PAGES.".navlink='$type' and active=1 and `lang_id`='$crt_lang' $group_regexp and ".TABLE_SLUGS.".type='content' order by `order_no`");

		$no = count($result);

		for($i=0; $i<$no; $i++) { 
			$result[$i]['moved'] = 0;
			$result[$i]['parent'] = 0;
			$result[$i]['str']='';
			$result[$i]['title']=cleanStr($result[$i]['title']);
			$result[$i]['url_title'] = _urlencode($result[$i]['title']);
			$result[$i]['level']=1;
		}

		$found=1;
		while($found) {
		$found=0;
		for($i=0; $i<$no; $i++) {
			
			if($result[$i]['parent_id'] && !$result[$i]['moved']) {
				$found=1;
				$element = $result[$i];
				// delete from that position
				array_splice($result, $i, 1);
				$found=0;
				// search for father position, set parent flag=1
				// there are only no-1 elements now
				$level = 1;
				for($j=0; $j<$no-1 && !$found;$j++) { 
					if($result[$j]['id'] == $element['parent_id'] ) {
						$pos_parent = $j;
						$pos = $j+1;
						while($pos<$no-1 && $result[$pos]['parent_id']==$element['parent_id'] && $result[$pos]['order_no']<$element['order_no']) { 
							$pos++;
							if($pos<$no-1 && $result[$pos]['parent_id']==$result[$pos-1]['id']) {
							$p=$result[$pos]['parent_id'];
							while($pos<$no-1 && $result[$pos]['parent_id']==$p) $pos++;
							}
						}
						$ss= $result[$pos_parent]['str']; 
						$result[$pos_parent]['parent'] = 1;
						$found=1;
						$level = $result[$pos_parent]['level'];
					}
				}
				// insert after father position
				if($found) {
				array_splice($result, $pos, 0, array($element));
				$result[$pos]['str']=$ss.'&nbsp;&nbsp;&nbsp;';
				$result[$pos]['moved'] = 1;
				$result[$pos]['level'] = $level+1;
				}

				// set subcategories as not moved 
				for($j=0; $j<$no; $j++) { 
					if($result[$j]['parent_id']==$element['id']) $result[$j]['moved'] = 0;
				}

			} else if($result[$i]['parent_id']==0) {
				$result[$i]['str']='';
			}

		} //end for
		} //while found
		return $result;
	}

	static function getMainNavbarLinks($group_id) {

		return common::getNavbarLinks(1, $group_id);

	}

	static function getSecNavbarLinks($group_id) {

		return common::getNavbarLinks(2, $group_id);

	}

	static function getCharacterMaps() {

		global $languages;
		if(empty($languages)) $languages = common::getCachedObject("base_languages");

		$maps = array();

		foreach($languages as $lang) {
			if(!$lang["characters_map"]) continue;
			$map_arr = explode(",", $lang["characters_map"]);
			foreach($map_arr as $map) array_push($maps, $map); 
		}
		return $maps;

	}

	static function getUserSettings($id) {

		global $db, $settings;

		$contact_str="";
		if($settings['contact_name_field']!='email' && $settings['contact_name_field']!='username') $contact_str=", `{$settings['contact_name_field']}`";

		$sett = $db->fetchAssoc("select `group`, `bulk_uploads`, `email`".$contact_str.", `username`, `moderator`, `affiliate` from ".TABLE_USERS." where `id`='$id'");

		// determine wether is allowed to post ads or not
		$sett['post_ads'] = $db->fetchRow("select `post_ads` from ".TABLE_USER_GROUPS." where `id`='{$sett['group']}'");
		
		// affiliate id
		if(isset($sett['affiliate']) && $sett['affiliate']) { 
			global $config_abs_path;
			require_once $config_abs_path."/classes/affiliates.php";
			$sett['affiliate_id'] = affiliates::getAffiliateId($id);
		}
		return $sett;

	}

	static function subscriptionExists($group='') {

		global $db;
		$subscr=$db->fetchAssocList("select `id`, `groups` from ".TABLE_PACKAGES." where no_ads!=1 and `active`=1");
		if(!count($subscr)) return 0;

		foreach($subscr as $sub) {
	
			if ($sub['groups']==0) return 1;
			$arr = explode(",", $sub['groups']);
			if(in_array($group, $arr)) return 1;
			
		}

		return 0;
	}

	static function getCurrentVersion($m = 0) {

		global $db;
		global $config_table_prefix;
		if(!$m) {
			// if already checked today
			$arr = $db->fetchAssoc("select date_add(`last_check`, interval 1 day) > now() as `checked`, `last_checked_version`, `last_checked_bugfix` from ".$config_table_prefix."version");
			$checked = $arr['checked'];

			if($checked) return $arr['last_checked_version']."|".$arr['last_checked_bugfix'];
		}

		global $config_live_site;

		$ver_info = $db->fetchAssoc("select * from ".$config_table_prefix."version");

		$str = base64_encode($config_live_site."|".$ver_info['ver']."|".$ver_info['subver']);

		// older versions than 9 use the url:
		//http://www.oxyclassifieds.com/version/current.php?req=$str
		$crt_version_url = "http://www.oxyclassifieds.com/version/current2.php?req=".$str;

		if ( function_exists('curl_version') )
		{

			$ch = curl_init();
			
			curl_setopt( $ch, CURLOPT_URL, $crt_version_url );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
				
			$version_str = trim(curl_exec($ch));
				
			curl_close($ch);
		}
		else 
		if ( ini_get('allow_url_fopen') == 1 )
		{
			$version_str = trim(@file_get_contents( $crt_version_url ));
		}

		$array_versions = explode("|", $version_str);
		$current_version = $array_versions[0];
		$current_bugfix = $array_versions[1];
		
		if(!$current_version) $current_version = "Could not access oxyclassifieds.com";
		else {
			// check if the last bugfix was applied
			global $config_abs_path;
			$f = fopen($config_abs_path."/include/version","r");
			$local_version_str = fread($f, 4096);
			if(substr( $local_version_str, 0, strlen($current_version) ) === $current_version) {
				$local_bugfix = substr( $local_version_str, strlen($current_version)+1);
				if($local_bugfix<$current_bugfix) {
					if($current_bugfix-$local_bugfix>1) {
						$bugfix_str = ($local_bugfix+1)." &rarr; ".$current_bugfix;
					} else {
						$bugfix_str = $current_bugfix;
					}
				}
				else $bugfix_str="";
			}
			else {
				$bugfix_str="";
			}
		}

		$db->query("update ".$config_table_prefix."version set `last_check`=now(), `last_checked_version`='$current_version', `last_checked_bugfix`='$bugfix_str'");
		
		return $current_version."|".$bugfix_str;
	}

	static function isMobileVersion() {

		global $db;
		$enabled = $db->fetchRow("select `enable_mobile_templates` from ".TABLE_MOBILE_SETTINGS."");
		if(!$enabled) return 0;
		return isMobile();

	}

	static function getCachedObject($object, $param = null) {

		$lc_cache = new cache();

		// language depending caches
		$lang_dep_objects = array("base_settings", "base_meta_info",  "base_interface", "base_short_categories", "base_short_categories_fset", "base_categories", "base_refine_fields", "base_refine_fields_list", "base_user_fields", "base_listing_fields", "base_short_listing_fields", "base_short_user_fields", "base_short_plans", "base_priorities", "base_groups", "base_short_groups", "base_autoregister_groups", "base_cp", "base_featured_plans");

		// try to get the object from the cache
		global $crt_lang;
		// send also language if language depending
		$lang_name = "";
		if(in_array($object, $lang_dep_objects) ) $lang_name = $crt_lang;

//		if($result = $lc_cache->readCache($object, $lang_name, $param))
//			return $result;

		// get the object directly from database
		global $config_abs_path;
		switch($object) {

			case "base_settings":

				require_once($config_abs_path.'/classes/settings.php');

				$result['appearance'] = settings::getAppearance();
				$result['modules'] = common::getModulesList();
				$result['settings'] = settings::getSettings();
				$result['ads_settings'] = settings::getAdsSettings();
				$result['seo_settings'] = settings::getSeoSettings();
				$result['mobile_settings'] = settings::getMobileSettings();
				$result['mail_settings'] = settings::getMailSettings();
				$result['invoice_settings'] = settings::getInvoiceSettings();

				break;

			case "base_meta_info":

				require_once($config_abs_path.'/classes/settings.php');
				$result = settings::getSeoMetaInfo();

				break;

			case "base_interface":

				require_once($config_abs_path.'/classes/categories.php');
				require_once($config_abs_path.'/classes/fields.php');
				require_once($config_abs_path.'/classes/depending_fields.php');
				require_once($config_abs_path.'/classes/banners.php');
				require_once($config_abs_path.'/classes/languages.php');
				require_once($config_abs_path.'/classes/fields.php');
				require_once($config_abs_path.'/classes/groups.php');
				require_once($config_abs_path.'/classes/settings.php');

				$result = array();

				$categ = new categories();
				$result['short_categories'] = $categ->getAllOptions();

				$cf = new fields("cf");
				$result['qs_fields'] = $cf->getSearch("quick");
// has a seo object which is not always available
				$result['footer_links'] = $categ->makeFooterLinks();
				$result['banner_positions'] = banners::getBannerPositions();
				//$result['main_navbar_links'] = common::getMainNavbarLinks();
				//$result['secondary_navbar_links'] = common::getSecNavbarLinks();
				$result['rss'] = common::getRss();
				$result['languages'] = languages::getActiveLanguages();
				$result['currencies'] = common::getCurrencies();

				
				$result['config_vars']['multi_depending'] = $cf->getMultiDepending();
				$result['config_vars']['no_groups'] = common::noAutoRegisterGroups();
				$result['config_vars']['no_active_groups'] = groups::getNoActive();
				// default register group
				if($result['config_vars']['no_groups']<=1)
					$result['config_vars']['default_register_group'] = groups::noDefRegisterGroup();

				$result['config_vars']['credits_enabled'] = settings::getCreditsEnabled();

				break;

			case "base_cp":	

				if(isset($param['group']) && $param['group']) $group_id=$param['group']; else $group_id = 0;
				$result['main_navbar_links'] = common::getMainNavbarLinks($group_id);
				$result['secondary_navbar_links'] = common::getSecNavbarLinks($group_id);
				
				break;
				
			case "base_short_categories":

				require_once($config_abs_path.'/classes/categories.php');
				$categ = new categories();
				if(isset($param['group']) && $param['group']) $group_id=$param['group']; else $group_id = 0;
				if(isset($param['parent']) && $param['parent']) $parent_id=$param['parent']; else $parent_id = 0;
				$result=$categ->getAllOptions($group_id, $parent_id);

				break;

			case "base_short_categories_fset":

				require_once($config_abs_path.'/classes/categories.php');
				$categ = new categories();
				if(isset($param['fieldset']) && $param['fieldset']) $fieldset_id=$param['fieldset']; else $fieldset_id = 0;
				$result=$categ->getAllSet($fieldset_id);

				break;

			case "base_categories":

				require_once($config_abs_path.'/classes/categories.php');
				$categ = new categories();
				if(isset($param['parent']) && $param['parent']) $parent_id=$param['parent']; else $parent_id = 0;
				$result=$categ->getCategories($parent_id);

				break;

			case "base_refine_fields":

				require_once($config_abs_path.'/classes/fields.php');
				require_once($config_abs_path.'/classes/depending_fields.php');
				$cf = new fields("cf");
				if(isset($param['fieldset']) && $param['fieldset']) $fieldset_id=$param['fieldset']; else $fieldset_id = 0;
				$result = $cf->getSearch("advanced", $fieldset_id);

				break;

			// array containing the captions of all fields for refine search 
			// ex: array("make", "model" ...)
			case "base_refine_fields_list":

				require_once($config_abs_path.'/classes/fields.php');
				require_once($config_abs_path.'/classes/depending_fields.php');
				$cf = new fields("cf");
				if(isset($param['fieldset']) && $param['fieldset']) $fieldset_id=$param['fieldset']; else $fieldset_id = 0;
				$result = $cf->getSearchCaptions("advanced", $fieldset_id);

				break;

			case "base_search_fields":

				require_once($config_abs_path.'/classes/fields.php');
				require_once($config_abs_path.'/classes/depending_fields.php');
				$cf = new fields("cf");
				$result = $cf->getSearchFields();

				break;

			case "base_listing_fields":

				require_once($config_abs_path.'/classes/fields.php');
				require_once($config_abs_path.'/classes/depending_fields.php');
				$cf = new fields("cf");
				if(isset($param['fieldset']) && $param['fieldset']) $fieldset_id=$param['fieldset']; else $fieldset_id = 0;
				$result = $cf->getAll($fieldset_id);

				break;

			case "base_user_fields":

				require_once($config_abs_path.'/classes/fields.php');
				require_once($config_abs_path.'/classes/depending_fields.php');
				$uf = new fields("uf");
				if(isset($param['group']) && $param['group']) $group_id=$param['group']; else $group_id = 0;
				$result = $uf->getAll($group_id);

				break;

			case "base_gmap_fields":

				require_once($config_abs_path.'/classes/fields.php');
				$cf = new fields("cf");
				if(isset($param['fieldset']) && $param['fieldset']) $fieldset_id=$param['fieldset']; else $fieldset_id = 0;
				$result = $cf->getGmapsField($fieldset_id);

				break;

			case "base_short_listing_fields":

				require_once $config_abs_path."/classes/config/fields_config.php";
				$cf=new fields_config('cf');
				$result=$cf->getAllForm();

				break;

			case "base_short_user_fields":

				require_once $config_abs_path."/classes/config/fields_config.php";
				$cf=new fields_config('uf');
				$result=$cf->getAllForm();

				break;

			case "base_short_plans":

				require_once $config_abs_path."/classes/packages.php";
				$result=packages::getAllForm();

				break;

			case "base_priorities":

				require_once $config_abs_path."/classes/priorities.php";
				$pri = new priorities();
				$result = $pri->getPriorities();

				break;

			case "base_featured_plans":

				require_once $config_abs_path."/classes/featured_plans.php";
				$fp = new featured_plans();
				$result = $fp->getFeaturedPlans();

				break;

			case "base_groups":

				require_once $config_abs_path."/classes/groups.php";
				$gr = new groups();
				$result = $gr->getAll();

				break;

			case "base_short_groups":

				require_once $config_abs_path."/classes/groups.php";
				$gr = new groups();
				$result = $gr->getShortGroups();

				break;

			case "base_autoregister_groups":

				require_once $config_abs_path."/classes/groups.php";
				$gr = new groups();
				$result = $gr->getAllRegister();

				break;

			case "base_languages":
				require_once($config_abs_path.'/classes/languages.php');
				$result = languages::getActiveLanguages();

				break;

			case "base_languages_list":

				require_once($config_abs_path.'/classes/languages.php');
				$result = languages::getLanguagesList();

				break;

			case "base_security":

				require_once($config_abs_path.'/classes/settings.php');
				$result = settings::getSecuritySettings();

				break;
			case "special_user_fields":
				require_once($config_abs_path.'/classes/fields.php');
				$uf = new fields("uf");
				if(isset($param['group']) && $param['group']) $group_id=$param['group']; else $group_id = 0;
				$result = $uf->getUserSpecialCF($group_id);
				
				break;
			case "special_listing_fields":
				require_once($config_abs_path.'/classes/fields.php');
				$uf = new fields("cf");
				if(isset($param['fset']) && $param['fset']) $fset=$param['fset']; else $fset = 0;
				$result = $uf->getListingSpecialCF($fset);
				
				break;
			default:
				break;
 	}
		if($result==null) return null;
		$lc_cache->writeCache($object, $result, $lang_name, 0, $param);

		return $result;
	}

	// gets some common settings values
	// ran before the template engine initializes
	static function getBaseCachedObjects() {
		global $modules_array, $appearance_settings, $ads_settings, $settings, $seo_settings, $mobile_settings, $mail_settings, $invoice_settings;

		$result = common::getCachedObject("base_settings");
		$modules_array = $result['modules'];
		$settings = $result['settings'];
		$ads_settings = $result['ads_settings'];
		$seo_settings = $result['seo_settings'];
		$appearance_settings = $result['appearance'];
		$mobile_settings = $result['mobile_settings'];
		$mail_settings = $result['mail_settings'];
		$invoice_settings = $result['invoice_settings'];

	}


	// gets objects commonly displayed in the interface
	// this will run after the template engine is initialized
	static function getInterfaceCachedObjects() {

		global $config_vars, $short_categories, $qs_fields, $footer_links, $banner_positions, $rss, $languages, $currencies;
		
		$result = common::getCachedObject("base_interface");
		$config_vars = $result['config_vars'];
		$short_categories = $result['short_categories'];
		$qs_fields = $result['qs_fields'];
		$footer_links =  $result['footer_links'];
		$banner_positions = $result['banner_positions'];
		//$main_navbar_links = $result['main_navbar_links'];
		//$secondary_navbar_links = $result['secondary_navbar_links'];
		$rss = $result['rss'];
		$languages = $result['languages'];
		$currencies = $result['currencies'];

	}

	static function getAffiliateSettings($affiliate_id) {
	
		global $db;
		$group_id = $db->fetchRow("select `group` from ".TABLE_USERS." where id='$affiliate_id'");
		$aff_settings = $db->fetchAssoc("select `affiliates_cookie_availability`, `affiliates_percentage`, `affiliates_payment_cycle` from ".TABLE_USER_GROUPS." where id='$group_id'");
		return $aff_settings;
	
	}
	
}

?>
