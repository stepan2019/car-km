<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class locations {

public function __construct()
{
	
}

function noFields() {

	global $settings;
	$fields = $settings['location_fields'];
	$sarr = explode(",", $fields);
	return count($sarr);

}

function getFields() {

	global $settings;
	$fields = $settings['location_fields'];
	$sarr = explode(",", $fields);
	return $sarr;

}

// when location is represented by one field only
// the field must be a menu
function getLocations($field, $all = 0) {
	
	global $db;
	global $crt_lang;
	global $location_array;
	global $config_abs_path;
	require_once $config_abs_path."/classes/depending_fields.php";

	$locations = $db->fetchRow("select `elements` from ".TABLE_FIELDS." left join ".TABLE_FIELDS."_lang on ".TABLE_FIELDS.".id = ".TABLE_FIELDS."_lang.id where `caption` like '$field' and `lang_id` like '$crt_lang'");
	if($locations) return $locations;

	$dep_array = $db->fetchAssoc("select * from ".TABLE_DEPENDING_FIELDS." left join ".TABLE_DEPENDING_FIELDS."_lang on ".TABLE_DEPENDING_FIELDS.".id=".TABLE_DEPENDING_FIELDS."_lang.id where (`caption1` like '$field' or `caption2` like '$field' or `caption3` like '$field' or `caption4` like '$field') and `lang_id`='$crt_lang'");

	if($dep_array) {

		if($dep_array['caption1']==$field) $table = $dep_array['table1'];
		if($dep_array['caption2']==$field) { $table = $dep_array['table2']; $prev_field = $dep_array['caption1']; $prev_table = $dep_array['table1']; }
		if($dep_array['caption3']==$field) { $table = $dep_array['table3']; $prev_field = $dep_array['caption2']; $prev_table = $dep_array['table2']; }
		if($dep_array['caption4']==$field) { $table = $dep_array['table4']; $prev_field = $dep_array['caption3']; $prev_table = $dep_array['table3']; }
		$dep = new depending_fields();

		// for first field simply get all elements
		if($dep_array['caption1']==$field) $locations = $dep->getElements($table, $crt_lang);
		else {
			//$locations=array();
			// if previous depending field is set as a location
			if($location_array[$prev_field] || $all) {
				if(!$all) {
					$set = $dep->getIdByName($prev_table, escape($location_array[$prev_field]));
					$locations = $dep->getElements($table, $crt_lang, $set);
				} else {
					$locations = $dep->getElements($table, $crt_lang);
				}
			}

		}
		
	}

	return $locations;

}

function init() {

	global $no_location_fields;
	global $location_array, $settings;

	$array_loc = explode(",", $settings['location_fields']);
	foreach($array_loc as $l) {

		if(isset($_COOKIE['location_'.$l]) && $_COOKIE['location_'.$l]) {
			$location_array[$l] = $_COOKIE['location_'.$l];
		}
		else 
			$location_array[$l] = "";

	}

}

function initTemplatesVals(&$smarty) {
	
	global $no_location_fields;
	global $location_array, $settings;
	$crt_location = '';

	$array_loc = explode(",", $settings['location_fields']);
	foreach($array_loc as $l) {

		$smarty->assign("location_".$l, $location_array[$l] );

	}
	if($location_array) {
	// get last available field except zip
	foreach($location_array as $key=>$value) {
		if($key!="zip" && $key && $value) $crt_location = $value;
	}
	}

	$smarty->assign("location_fields", $array_loc);
	$smarty->assign("crt_location", $crt_location);
	$smarty->assign("location_array", $location_array);

}

static function makeQueryStr() {

	global $location_array;
	$str = '';
	if($location_array) {
	foreach($location_array as $key=>$value) {
		if($key && $value) $str .=" and ".TABLE_ADS.".`$key` = '".escape($value)."' ";
	}
	}
	return $str;
}

function setLocation($field, $val, $redirect=1) {

	global $config_abs_path, $settings, $main_domain;
	require_once $config_abs_path."/classes/depending_fields.php";

	$array_loc = $this->getFields();
	if(!in_array($field, $array_loc)) return 0;

	if($val) {
		$location_str = $this->getLocations($field);
		if(!$location_str) return 0;
		$array_loc1 = explode("|", $location_str);
		if(!in_array($val, $array_loc1)) return 0;
	}

	$found=0;
	for($i=0; $i<count($array_loc); $i++)
	{
		if($array_loc[$i]==$field) { $found=1; continue;}
		if(!$found) continue;
		// delete cookies for following location fields
		unset($_COOKIE['location_'.$array_loc[$i]]);
		setcookie ('location_'.$array_loc[$i], "", time() - 3600, "/", ".".$main_domain);

	}

	// set the location
	global $location_array;
	// only if location_subdomain field 
	if($settings['enable_subdomains'] && $field==$settings['subdomain_field'])
		$val = $this->buildLocationSubdomain($val);
	$location_array[$field] = $val;

	if( (!$val && isset($_COOKIE['location_'.$field])) || ($val && !isset($_COOKIE['location_'.$field])) || $val!=$_COOKIE['location_'.$field]) {

		$expire = time() + 60*60*24*365;
		setcookie('location_'.$field, $val, $expire, '/', ".".$main_domain);

		if( $settings['enable_subdomains'] && $field==$settings['subdomain_field'] ) 
			$_SESSION['location_set'] = 1;

		// redirect if subdomain field
		if( $redirect && $settings['enable_subdomains'] && $field==$settings['subdomain_field'] ) {
			header("Location: ".$this->getProto().$this->buildLocationSubdomain($val).".".$main_domain.$_SERVER['REQUEST_URI']);
			exit(0);
		}
	}

	return 1;
}

function setPostLocation($post_locations_array) {

	global $config_abs_path;
	global $settings;
	global $main_domain;

	require_once $config_abs_path."/classes/depending_fields.php";
	$array_loc = $this->getFields();

	foreach ($post_locations_array as $field => $val) {

		if(!in_array($field, $array_loc)) continue;
		$location_str = $this->getLocations($field);
		$array_loc1 = explode("|", $location_str);
		if($val && !in_array($val, $array_loc1)) continue;
		$found=0;
		for($i=0; $i<count($array_loc); $i++)
		{
			if($array_loc[$i]==$field) { $found=1; continue;}
			if(!$found) continue;
			// delete cookies for following location fields
			unset($_COOKIE['location_'.$array_loc[$i]]);
			setcookie ('location_'.$array_loc[$i], "", time() - 3600, "/", ".".$main_domain);

		}

		// set the location
		global $location_array;
		// only if location_subdomain field 
		//if($settings['enable_subdomains'] && $field==$settings['subdomain_field'])
		//	$val = $this->buildLocationSubdomain($val);
		$location_array[$field] = $val;

		if( (!$val && !empty($_COOKIE['location_'.$field])) || ($val && empty($_COOKIE['location_'.$field])) || ( !empty($_COOKIE['location_'.$field]) && $val!=$_COOKIE['location_'.$field])) {

			$expire = time() + 60*60*24*365;
			setcookie('location_'.$field, $val, $expire, '/', ".".$main_domain);

		}

		if( $settings['enable_subdomains'] && $field==$settings['subdomain_field'] ) {
			my_session_start();
			$_SESSION['location_set'] = 1;
		}

	} // end foreach

	return 1;
}

function checkPostArray($captions_array, $post_array) {
	
	global $location_array;
	$array_loc = $this->getFields();
	foreach($captions_array as $f) {
		if(in_array($f, $array_loc) && isset($location_array[$f]) && $location_array[$f]) {
			$post_array[$f] = $location_array[$f];
		}
	}
	return $post_array;
}

function remove($loc) {
	
	global $location_array;
	global $main_domain;
	$array_loc = $this->getFields();

	if(!$location_array) return;
	foreach ($location_array as $key => $value) {
		if($key==$loc) {
 			unset($location_array[$key]);
			unset($_COOKIE['location_'.$key]);
			setcookie ("location_".$key, "", time() - 3600, "/", ".".$main_domain);
		}
	}

	// check if depending and there are next fields
	$cf = new fields("cf");
	if(!$cf->getTypeByCaption($loc)) {
		global $db;
		$dep_array = $db->fetchAssoc("select * from ".TABLE_DEPENDING_FIELDS." where (`caption1` like '$loc' or `caption2` like '$loc' or `caption3` like '$loc' or `caption4` like '$loc')");
		if($dep_array) {

			if($dep_array['caption1']==$loc) { 

				if(in_array($dep_array['caption2'], $array_loc)) {
			 		unset($location_array[$dep_array['caption2']]);
					unset($_COOKIE['location_'.$dep_array['caption2']]);
					setcookie ("location_".$dep_array['caption2'], "", time() - 3600, "/", ".".$main_domain);
				}
	
				if($dep_array['caption3'] && in_array($dep_array['caption3'], $array_loc)) {
			 		unset($location_array[$dep_array['caption3']]);
					unset($_COOKIE['location_'.$dep_array['caption3']]);
					setcookie ("location_".$dep_array['caption3'], "", time() - 3600, "/", ".".$main_domain);
				}

				if($dep_array['caption4'] && in_array($dep_array['caption4'], $array_loc)) {
			 		unset($location_array[$dep_array['caption4']]);
					unset($_COOKIE['location_'.$dep_array['caption4']]);
					setcookie ("location_".$dep_array['caption4'], "", time() - 3600, "/", ".".$main_domain);
				}
			}
			if($dep_array['caption2']==$loc) { 
				if($dep_array['caption3'] && in_array($dep_array['caption3'], $array_loc)) {
			 		unset($location_array[$dep_array['caption3']]);
					unset($_COOKIE['location_'.$dep_array['caption3']]);
					setcookie ("location_".$dep_array['caption3'], "", time() - 3600, "/", ".".$main_domain);
				}

				if($dep_array['caption4'] && in_array($dep_array['caption4'], $array_loc)) {
			 		unset($location_array[$dep_array['caption4']]);
					unset($_COOKIE['location_'.$dep_array['caption4']]);
					setcookie ("location_".$dep_array['caption4'], "", time() - 3600, "/", ".".$main_domain);
				}
			}
			if($dep_array['caption3']==$loc) { 
				if($dep_array['caption4'] && in_array($dep_array['caption4'], $array_loc)) {
			 		unset($location_array[$dep_array['caption4']]);
					unset($_COOKIE['location_'.$dep_array['caption4']]);
					setcookie ("location_".$dep_array['caption4'], "", time() - 3600, "/", ".".$main_domain);
				}
			}
		}
	}
}

function initLiveSiteUrl() {

	global $location_array;
	global $settings;

	if(!$settings['enable_subdomains'] || !$settings['subdomain_field'] ) return;

	global $config_live_site;
	$get_city=''; $cookie_city="";
	
	//if(isset($_GET['crt_city']) && $_GET['crt_city']) $get_city = $_GET['crt_city']; 
	if(isset($location_array[$settings['subdomain_field']])) { 
		$cookie_city = $this->buildLocationSubdomain($location_array[$settings['subdomain_field']]); 
	}

	if($cookie_city) {
		if(stristr($config_live_site, "www"))
			$config_live_site = str_replace($this->getProto()."www.", $this->getProto().$cookie_city.".", $config_live_site);
		else 
			$config_live_site = str_replace($this->getProto(), $this->getProto().$cookie_city.".", $config_live_site);
	}

}

function checkCrtLocation(&$smarty) {

	global $location_array;
	global $settings;
	global $crt_city;
	global $main_domain;

	global $config_live_site;
	$get_city=''; $cookie_city = ''; $new_city='';


	if(isset($_GET['crt_city']) && $_GET['crt_city']) $get_city = $_GET['crt_city']; 
	if(isset($location_array[$settings['subdomain_field']])) 
		$cookie_city = $this->buildLocationSubdomain($location_array[$settings['subdomain_field']]); 

	// if other subdomain is set, change the cookie for that subdomain and redirect
	// or if a new location is just set, redirect to that subdomain
	if( (($get_city && $get_city!="m") || (isset($_SESSION['location_set']) && $_SESSION['location_set'])) && $get_city != $cookie_city) {

		// a new location is set using setLocation()
		if( isset($_SESSION['location_set']) && $_SESSION['location_set'] ){

			$new_city = $cookie_city;
			if(getScriptName()!="show_locations.php")
			    $_SESSION['location_set'] = 0;

		} 
		else 
		{
			// get correct city name
			$city_name="";
			if($get_city)
				$city_name = $this->getLocationName($settings['subdomain_field'], $get_city);

			// set cookie
			$expire = time() + 60*60*24*365;
			setcookie('location_'.$settings['subdomain_field'], $city_name, $expire, '/', ".".$main_domain);

			// if incorrect city name will redirect to main domain
			if($city_name) $new_city = $get_city;

		}

		// redirect
		if($new_city) {
			header("Location: ".$this->getProto().$new_city.".".$main_domain.$_SERVER['REQUEST_URI']);
		}
		else {
			$s = substr_count($config_live_site, "www")?"www.":"";
			header("Location: ".$this->getProto().$s.$main_domain.$_SERVER['REQUEST_URI']);
		}
		exit(0);
	}
	// if location cookie is set but the site is accessed without a subdomain
	else if($cookie_city && $get_city!="m" && $cookie_city!=$get_city) {

		header("Location: ".$this->getProto().$cookie_city.".".$main_domain.$_SERVER['REQUEST_URI']);
		exit(0);

	}
	
	
	if($cookie_city) {

		if(stristr($config_live_site, "www")){
			$config_live_site = str_replace("http://www.", "http://".$cookie_city.".", $config_live_site);
			$config_live_site = str_replace("https://www.", "https://".$cookie_city.".", $config_live_site);
			}
		else {
			$config_live_site = str_replace("http://", "http://".$cookie_city.".", $config_live_site);
			$config_live_site = str_replace("https://", "https://".$cookie_city.".", $config_live_site);
			}
	}

	$domain_name = str_replace("http://", "", $config_live_site);
	$domain_name = str_replace("https://", "", $domain_name);

	if($smarty) {
		$smarty->assign("live_site", $config_live_site);
		$smarty->assign("domain_name", $domain_name);
		$smarty->assign("main_domain", $main_domain);
	}

}

function buildLocationSubdomain($str) {
	
	return _urlencode($str, 1, 0, 1);

}

function getLocationName($field, $str) {

	$locations = $this->getLocations($field,1);
	$locations_arr = explode("|", $locations);
	foreach($locations_arr as $loc) {
		if($this->buildLocationSubdomain($loc)==$str) return $loc;
	}
	return "";

}

function translateLocation ($field, $val) {

	$translated = array(); $no = 0;
	global $crt_lang;
	global $db;
	global $languages;

	$el = $db->fetchRow("select `elements` from ".TABLE_FIELDS." left join ".TABLE_FIELDS."_lang on ".TABLE_FIELDS.".id = ".TABLE_FIELDS."_lang.id where `caption` like '$field' and `lang_id` like '$crt_lang'");

	// menu field
	if($el) {

		$lang_elem_arr = explode("|", $el);

		$i=0;
		$c = count($lang_elem_arr);
		foreach($languages as $l) {

			$new_lang_elem = $db->fetchRow("select `elements` from ".TABLE_FIELDS." left join ".TABLE_FIELDS."_lang on ".TABLE_FIELDS.".id = ".TABLE_FIELDS."_lang.id where `caption` like '$field' and `lang_id` like '$crt_lang'");
			$new_lang_elem_arr = explode("|", $new_lang_elem);

			foreach($lang_elem_arr as $el) { 
				if($el == $val && $el!=$new_lang_elem_arr[$i] && $i<$c) { $translated[$no++] = $new_lang_elem_arr[$i]; break; }
				$i++;
			}
		}

	}
	// depending field
	else 
	{
		global $config_abs_path;
		require_once $config_abs_path."/classes/depending_fields.php";
		$dep = new depending_fields();
		$dep_id = $db->fetchRow("select `id` from ".TABLE_DEPENDING_FIELDS." where `caption1` like '$field' or `caption2` like '$field' or `caption3` like '$field' or `caption4` like '$field'");
		foreach($languages as $lang) {
			$tr = $dep->translateField( $dep_id, $field, $crt_lang, $lang['id'], escape($val) );
			if($tr!=$val) $translated[$no++] = $tr;
		}
	}

	return $translated;

}

function setConfigLiveSite() {

	global $location_array, $settings;
	if(isset($location_array[$settings['subdomain_field']])) 
		$cookie_city = $this->buildLocationSubdomain($location_array[$settings['subdomain_field']]); 
	if(!$cookie_city) return;

	global $config_live_site;
	if(stristr($config_live_site, "www"))
		$config_live_site = str_replace($this->getProto()."www.", $this->getProto().$cookie_city.".", $config_live_site);
	else 
		$config_live_site = str_replace($this->getProto(), $this->getProto().$cookie_city.".", $config_live_site);

}

function setConfigLiveSiteNoCookie() {

	$get_city='';
	if(isset($_GET['crt_city']) && $_GET['crt_city']) $get_city = $_GET['crt_city']; 
	if(!$get_city) return;

	global $config_live_site;
	if(stristr($config_live_site, "www"))
		$config_live_site = str_replace($this->getProto()."www.", $this->getProto().$get_city.".", $config_live_site);
	else 
		$config_live_site = str_replace($this->getProto(), $this->getProto().$get_city.".", $config_live_site);

}

function getAllLocations() {

	global $db;
	global $appearance_settings, $settings, $crt_lang, $location_array;
	$i = 0;
	$loc_fields_array = explode(",", $settings['location_fields']);
	foreach($loc_fields_array as $loc) {
		// check what type of field it is
		$f = new fields("cf");
		$type = $f->getTypeByCaption($loc);
		if($type) { 
			global $db;
			$row = $db->fetchAssoc("select `name`, `elements` from ".TABLE_FIELDS." left join ".TABLE_FIELDS."_lang on ".TABLE_FIELDS.".id = ".TABLE_FIELDS."_lang.id where `caption`='$loc' and `lang_id` = '$crt_lang'");
			$elements = explode("|", cleanHtml($row['elements']));
			$name = $row['name'];
		}
		else
		{
			$type="depending";
			$elements='';
			global $db;
			$dep_array = $db->fetchAssoc("select * from ".TABLE_DEPENDING_FIELDS." left join ".TABLE_DEPENDING_FIELDS."_lang on ".TABLE_DEPENDING_FIELDS.".id=".TABLE_DEPENDING_FIELDS."_lang.id where (`caption1` like '$loc' or `caption2` like '$loc' or `caption3` like '$loc' or `caption4` like '$loc') and `lang_id`='$crt_lang'");

			if($dep_array) {
				$fields[$i]['dep_id'] = $dep_array['id'];
				$fields[$i]['prev_field'] = ''; $fields[$i]['next_field'] = '';

				if($dep_array['caption1']==$loc) { $table = $dep_array['table1']; if($dep_array['caption2']) $fields[$i]['next_field'] = $dep_array['caption2']; $name = $dep_array['name1'];}
				if($dep_array['caption2']==$loc) { $table = $dep_array['table2']; $fields[$i]['prev_field'] = $dep_array['caption1']; if($dep_array['caption3']) $fields[$i]['next_field'] = $dep_array['caption3']; $prev_table = $dep_array['table1']; $name = $dep_array['name2'];}
				if($dep_array['caption3']==$loc) { $table = $dep_array['table3']; $fields[$i]['prev_field'] = $dep_array['caption2']; if($dep_array['caption4']) $fields[$i]['next_field'] = $dep_array['caption4']; $prev_table = $dep_array['table2']; $name = $dep_array['name3'];}
				if($dep_array['caption4']==$loc) { $table = $dep_array['table4']; $fields[$i]['prev_field'] = $dep_array['caption3']; $prev_table = $dep_array['table3']; $name = $dep_array['name4']; }
				$dep = new depending_fields();

				// for first field simply get all elements
				if($dep_array['caption1']==$loc) $elements = $dep->getIdNameNo($loc, $table, $crt_lang); //explode("|", $dep->getElements($table, $crt_lang));
				else {
					$elements=array();
					// if previous depending field is set as a location
					if($location_array[$fields[$i]['prev_field']]) {
						$set = $dep->getIdByName($prev_table, escape($location_array[$fields[$i]['prev_field']]));
						$elements = $dep->getIdNameNo($loc, $table, $crt_lang, $set); //explode("|", $dep->getElements($table, $crt_lang, $set));
					}
				
				}
			
			}
		
		$fields[$i]['table'] = $table;
		}// end if depending

		$fields[$i]['caption'] = $loc;
		$fields[$i]['type'] = $type;
		$fields[$i]['elements'] = $elements;
		$fields[$i]['no_per_column'] = ceil(count($elements)/3);
		$fields[$i]['name'] = $name;
		/*$no_array = $db->fetchAssocList("select * from ".TABLE_LOCATION_NO_ADS." where `field` like '$loc'");
		foreach($no_array as $n) {
			$fields[$i]['no'][$n['val']] = $n['no'];
		}*/
		$i++;

	}

	return $fields;
	}

	function getProto() {
	
		global $config_live_site;
		if(strstr($config_live_site, "https://"))
			return "https://";
		return "http://";	
	
	}
}
?>