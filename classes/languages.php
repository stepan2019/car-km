<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class languages {

	var $id;
	var $lang;

	public function __construct()
	{
	
	}

	static function getCurrent() {

		global $crt_lang, $languages_array;
		if(!$languages_array) $languages_array = common::getCachedObject("base_languages_list");
		if( isset($_COOKIE["default_lang"]) && $_COOKIE["default_lang"] && in_array($_COOKIE['default_lang'], $languages_array)) {
			$lang=$_COOKIE["default_lang"];
		}
		else { // get default language
			$lang = languages::getDefault();
		}
		return $lang;
	}

	static function getLanguage($id) {

		global $db;
		$array = $db->fetchAssoc("select * from ".TABLE_LANGUAGES." where id='$id'"); 
		$array['characters_map_array'] = explode(",", $array['characters_map']);
		return $array;

	}

	static function getDefault() {

		global $db;
		$default = $db->fetchRow("select `id` from ".TABLE_LANGUAGES." where `default`=1 and enabled=1 limit 1");
		if(!$default) $default = $db->fetchRow("select `id` from ".TABLE_LANGUAGES." limit 1");
		return $default;

	}

	static function getActiveLanguages() {

		global $db;
		$array = $db->fetchAssocList("select * from ".TABLE_LANGUAGES." where enabled=1 order by `order_no`"); 
		return $array;

	}

	static function languageExistsEnabled($lang) {

		global $db;
		$no = $db->fetchRow("select id from ".TABLE_LANGUAGES." where id='$lang' and enabled=1");
		if($no) return 1;
		return 0;

	}


	function getLanguages() {

		global $db;
		$array = $db->fetchAssocList("select * from ".TABLE_LANGUAGES." order by `order_no`");
		$result=array();
		$i=0;
		foreach ($array as $row) {
			$result[$i] = $row;
			$result[$i]['last'] = 0;	
			$i++;
		}
		if($i) $result[$i-1]['last'] = 1;
		return $result;

	}

	static function getLanguagesList() {

		global $db;
		$result = $db->fetchRowList("select id from ".TABLE_LANGUAGES." where `enabled`=1 order by `order_no`");
		return $result;

	}

	function getName($id) {

		global $db;
		$name = $db->fetchRow("select `name` from ".TABLE_LANGUAGES." where `id` like '$id'");
		return cleanStr($name);

	}

	function getFlag($id) {

		global $db;
		$flag = $db->fetchRow("select `image` from ".TABLE_LANGUAGES." where `id` like '$id'");
		return $flag;

	}

	function translateFieldsElements($result, $type) {

		global $crt_lang, $languages_array;

		// check if multiple languages and language is between the existing ones.
		if(in_array($result['language'], $languages_array)) {
	
			$el_fields = array("menu", "radio", "radio_group", "checkbox_group", "multiselect");

			global $config_abs_path;
			require_once $config_abs_path."/classes/fields.php";
			require_once $config_abs_path."/classes/depending_fields.php";

			$fields = new fields($type);
			$fields_array = $fields->getFieldsLang();

			// for each elements field replace the elements with the corresponding from the other language
			foreach ($fields_array as $f) {
				// if depending field
				if($f[$crt_lang]['type']=="depending") {
					$dep = new depending_fields;
					$dep_id = $f[$crt_lang]['dep_id'];
					$dep_field = $dep->getDependingField($dep_id);
					$lang_from = $result['language'];
					$lang_to = $crt_lang;	
					for($d=1;$d<=$dep_field['no'];$d++) {
						$caption = $dep_field['caption'.$d];
						if(isset($result[$caption])) $result[$caption] = $dep->translateField( $dep_id, $caption, $lang_from, $lang_to, escape($result[$caption]) );
					}
					continue;
				}

				if(in_array($f[$crt_lang]['type'], $el_fields) && $result[$f[$crt_lang]['caption']]) {

				$listing_lang_elem = $f[$result['language']]['elements'];
				$listing_lang_arr = explode("|", $listing_lang_elem);

				$crt_lang_elem = $f[$crt_lang]['elements'];
				$crt_lang_arr = explode("|", $crt_lang_elem);
				$count_elem = count($crt_lang_arr);

				$old_elem = $result[$f[$crt_lang]['caption']];
				$new_elem = array();

				if($f[$crt_lang]['type']=="checkbox_group" || $f[$crt_lang]['type']=="multiselect") {
					$i = 0;
					if(!is_array($old_elem)) $old_elem_exp = explode("|", $old_elem);
					else $old_elem_exp =$old_elem;
					foreach($listing_lang_arr as $el) {
						if(in_array($el, $old_elem_exp)) { 
							array_push($new_elem, $crt_lang_arr[$i]);
						}
						$i++;
					// if elements are incorrectly added and an equivalent does not exist for this language
					if($i==$count_elem) break;
					}

				} else {

					$i=0;
					$c = count($crt_lang_arr);
					foreach($listing_lang_arr as $el) { 
						if($el == $old_elem && $i<$c) { $new_elem = $crt_lang_arr[$i]; break; }
						$i++;
					}
					if(!$new_elem) $new_elem = $old_elem;
				}

				$result[$f[$crt_lang]['caption']] = $new_elem;

				}
			}
		}
		return $result;
	}

}
