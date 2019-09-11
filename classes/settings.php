<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class settings {

	public function __construct()
	{
	
	}

	static function getSettings() {

		global $db;
		global $crt_lang;
		$array=$db->fetchAssoc("select * from ".TABLE_SETTINGS.", ".TABLE_SETTINGS."_lang where `lang_id`='$crt_lang'");
		foreach ($array as $key=>$value) {
			$array[$key] = cleanStr($value);
		}
		if(!$array) return null;
		if($array['location_fields']) $array['array_location_fields'] = explode(",", $array['location_fields']);
		return $array;

	}

	static function getAdsSettings() {

		global $db, $crt_lang;
		$array=$db->fetchAssoc("select * from ".TABLE_ADS_SETTINGS.", ".TABLE_ADS_SETTINGS."_lang where ".TABLE_ADS_SETTINGS."_lang.lang_id='$crt_lang'");
		if(!$array) return null;

		// get appearance settings for formatting price
		$app_array=$db->fetchAssoc("select `price_format_decimals`, `price_format_point`, `price_format_separator` from ".TABLE_APPEARANCE."_lang where `lang_id`='$crt_lang'");

		$array['array_allowed_html'] = explode(",", $array['allowed_html']);
		$array['array_search_in_fields'] = explode(",", $array['search_in_fields']);
		$array['array_location_fields'] = explode(",", $array['location_fields']);
		$array['array_search_location_fields'] = explode(",", $array['search_location_fields']);
		$array['array_prof_groups'] = explode(",", $array['prof_groups']);
		$array['array_ds_distances_list'] = explode("|", $array['ds_distances_list']);

		// cannot use format_price in the simple mode as $appearance_settings global does not exist 
		//$array['featured_price_formatted'] = format_price($array['featured_price'], $app_array['price_format_decimals'], $app_array['price_format_separator'], $app_array['price_format_point']);
		$array['highlited_price_formatted'] = format_price($array['highlited_price'], $app_array['price_format_decimals'], $app_array['price_format_separator'], $app_array['price_format_point']);
		$array['video_price_formatted'] = format_price($array['video_price'], $app_array['price_format_decimals'], $app_array['price_format_separator'], $app_array['price_format_point']);
		$array['urgent_price_formatted'] = format_price($array['urgent_price'], $app_array['price_format_decimals'], $app_array['price_format_separator'], $app_array['price_format_point']);
		$array['url_price_formatted'] = format_price($array['url_price'], $app_array['price_format_decimals'], $app_array['price_format_separator'], $app_array['price_format_point']);
		$array['store_price_formatted'] = format_price($array['store_price'], $app_array['price_format_decimals'], $app_array['price_format_separator'], $app_array['price_format_point']);
		
		if(trim($array['ds_distances_list'])) {
			$array['ds_distances_list_formatted'] = trim($array['ds_distances_list']);
			$array['ds_distances_list_formatted'] = str_replace('|', "\n",$array['ds_distances_list_formatted']);
		}

		global $config_table_prefix;
		$array['address_components'] = array();
		$array['address_components_fields'] = array();
		$comp_array=$db->fetchAssocList("select * from ".$config_table_prefix."address_components");
			
		$i=0;
		foreach($comp_array as $comp) 
			if($comp['field']) {
				$array['address_components'][$comp['component']] = array( 'field' => $comp['field'], "type" =>$comp['type']);
				$array['address_components_fields'][$i]=$comp['field'];
				$i++;
			}
			
		return $array;

	}

	function getAddressComponents() {
	
	}
	

	static function getAppearance() {

		global $db;
		global $crt_lang;
		$array=$db->fetchAssoc("select * from ".TABLE_APPEARANCE.", ".TABLE_APPEARANCE."_lang where `lang_id`='$crt_lang'");
		if(!$array) return null;
		$header_pic = $array['header_pic'];
		$footer_pic = $array['footer_pic'];

		$array['header_is_flash'] = 0;
		$array['footer_is_flash'] =0;
		global $config_abs_path;
		if(getExtension($header_pic)=='swf') $array['header_is_flash'] = 1;
		
		$size = @getimagesize($config_abs_path.'/images/'.$array['header_pic']);
		$array['header_pic_width'] = $size[0];
		$array['header_pic_height'] = $size[1];
		
		$size = @getimagesize($config_abs_path.'/images/'.$array['small_header_pic']);
		$array['small_header_pic_width'] = $size[0];
		$array['small_header_pic_height'] = $size[1];

		if(getExtension($footer_pic)=='swf') $array['footer_is_flash'] = 1;

		$size = @getimagesize($config_abs_path.'/images/'.$array['footer_pic']);
		$array['footer_pic_width'] = $size[0];
		$array['footer_pic_height'] = $size[1];
		
		foreach ($array as $key=>$value) {

			if($key!="lang_id" && $key!="default_currency" && $key!="number_format_separator" && $key!="price_format_separator") $array["$key"] = cleanStr($value);

		}
		
		if($array['maintenance_mode']==1) {
		
			$array['maintenance_ips_array'] = array();
			if($array['maintenance_ips']) $array['maintenance_ips_array'] = explode(",", $array['maintenance_ips']);

		}
		
		// get price field format 
		$ext = $db->fetchRow("select `extensions` from ".TABLE_FIELDS." where `type` like 'price' limit 1");
		if($ext) {
			$earray = explode("|", $ext);
			$array['price_field_format_decimals'] = $earray[0];
			$array['price_field_format_point'] = $earray[1];
			$array['price_field_format_separator'] = $earray[2];
		} else {
			$array['price_field_format_decimals'] = $array['price_format_decimals'];
			$array['price_field_format_point'] = $array['price_format_point'];
			$array['price_field_format_separator'] = $array['price_format_separator'];

		}

		return $array;
	}


	static function getMailSettings() {

		global $db;
		$array=$db->fetchAssoc("select * from ".TABLE_MAIL_SETTINGS);
		if(!$array) return null;
		return $array;
	}

	static function getSeoSettings() {

		global $db;
		$array=$db->fetchAssoc("select * from ".TABLE_SEO_SETTINGS);
		$array['sef_links'] = json_decode($array['sef_links'], true);
		return $array;

	}

	static function getSeoMetaInfo() {

		global $db, $self_noext, $crt_lang;

		//$array=$db->fetchAssoc("select * from ".TABLE_SEO_PAGES."  where `page`='$self_noext' and `lang_id`='$crt_lang'");
		$array=$db->fetchAssocList("select * from ".TABLE_SEO_PAGES."  where `lang_id`='$crt_lang'");

		if(!$array) return null;
		$result = array();

		foreach ($array as $a) {

			$result[$a['page']]['title'] = $a['title'];
			$result[$a['page']]['meta_keywords'] = $a['meta_keywords'];
			$result[$a['page']]['meta_description'] = $a['meta_description'];
			$result[$a['page']]['noindex'] = $a['noindex'];
			//if($key!="lang_id" && $key!="page") $array["$key"] = cleanHtml($value);
			

		}

		return $result;

	}

	static function getMobileSettings() {

		global $db;
		$array=$db->fetchAssoc("select * from ".TABLE_MOBILE_SETTINGS);
		if(!$array) return null;
		foreach ($array as $key=>$value) $array["$key"] = cleanStr($value);

		return $array;

	}

	static function getSecuritySettings() {

		global $db;
		$security_settings=$db->fetchAssoc("select * from ".TABLE_SECURITY_SETTINGS);

		return $security_settings;

	}

	function getVal($val) {
		return $array[$val];
	}


	function getAll() {

		return $array;
	}

	static function encode($str) {

		return md5($str);

	}

	static function getCreditsEnabled() {

		global $db;
		$enabled = $db->fetchRow("select `enabled` from ".TABLE_PAYMENT_PROCESSORS." where `processor_code`='credits'");
		return $enabled;

	}

	static function makeTimeOffset() {

		global $appearance_settings;

		// set default timezone
		if(!$appearance_settings['timezone'] || !function_exists('date_default_timezone_set')) return;

		//date_default_timezone_set("GMT");
		$date_base = @date('Y-m-d-H-i-s');
		$dbase_array = explode("-", $date_base);
		date_default_timezone_set($appearance_settings['timezone']);
		$date_tz = date('Y-m-d-H-i-s');
		$dtz_array = explode("-", $date_tz);
		$time_base = @mktime($dbase_array[3], $dbase_array[4], $dbase_array[5], $dbase_array[1], $dbase_array[2], $dbase_array[0]);
		$time_tz = @mktime($dtz_array[3], $dtz_array[4], $dtz_array[5], $dtz_array[1], $dtz_array[2], $dtz_array[0]);

		$time_offset = $time_tz - $time_base;
		return $time_offset;

	}

	static function updateTimeOffset() {

		global $db;
		$new_offset = settings::makeTimeOffset();
		$db->query("update ".TABLE_APPEARANCE." set `time_offset` = '$new_offset'");
		return;

	}

	static function getInvoiceSettings() {

		global $db, $crt_lang;
		$array=$db->fetchAssoc("select * from ".TABLE_INVOICE_SETTINGS);
		if(!$array) return null;

		$array['array_user_fields'] = explode(",", $array['user_fields']);
		return $array;

	}

}
?>
