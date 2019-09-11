<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class settings_config {

	var $error;
	var $tmp;

	public function __construct()
	{
	
		$this->error=''; $this->tmp='';

	}

	function getAllLangSettings() {

		global $db;

		$row=$db->fetchAssoc("select * from ".TABLE_SETTINGS);
		$array_lang=$db->fetchAssocList("select * from ".TABLE_SETTINGS."_lang");

		foreach($array_lang as $cp_lang) {

			$lang_id = $cp_lang['lang_id'];
			foreach ($cp_lang as $key=>$value) {
				if($key=='lang_id') continue;
				$row[$key][$lang_id] = cleanStr($value);
			}
		}
		$row['array_location_fields'] = explode(",", $row['location_fields']);
		return $row;
	}

	function getAllLangAppearance() {

		global $db;

		$row=$db->fetchAssoc("select * from ".TABLE_APPEARANCE);
		$array_lang=$db->fetchAssocList("select * from ".TABLE_APPEARANCE."_lang");

		foreach($array_lang as $cp_lang) {

			$lang_id = $cp_lang['lang_id'];
			foreach ($cp_lang as $key=>$value) {
				if($key=='lang_id') continue;
				if($key=="default_currency") $row[$key][$lang_id] = $value;
				else $row[$key][$lang_id] = cleanStr($value);
			}
		}

		return $row;

	}

	function getAllLangAdsSettings() {

		global $db;

		$row=$db->fetchAssoc("select * from ".TABLE_ADS_SETTINGS);
		$array_lang=$db->fetchAssocList("select * from ".TABLE_ADS_SETTINGS."_lang");

		foreach($array_lang as $cp_lang) {

			$lang_id = $cp_lang['lang_id'];
			foreach ($cp_lang as $key=>$value) {
				if($key=='lang_id') continue;
				$row[$key][$lang_id] = cleanStr($value);
			}
		}

		return $row;

	}

	function getAllLangSeoSettings() {

		global $db, $config_abs_path;

		$seo_pages=$db->fetchAssocList("select * from ".TABLE_SEO_PAGES." order by `order_no` asc");
		$result = array();

		require_once $config_abs_path."/classes/languages.php";
		foreach($seo_pages as $p) {

			$lang_id = $p['lang_id'];
			if(!languages::languageExistsEnabled($lang_id)) continue;
			
			$order_no = $p['order_no'];

			$result[$order_no]['page_description'] = $p['page_description'];
			$result[$order_no]['page'] = $p['page'];
			$result[$order_no]['title'][$lang_id] = $p['title'];
			$result[$order_no]['meta_keywords'][$lang_id] = $p['meta_keywords'];
			$result[$order_no]['meta_description'][$lang_id] = $p['meta_description'];
			$result[$order_no]['noindex'] = $p['noindex'];

		}

		return $result;
	}

	function getError() {
	
		return $this->error;

	}

	function addError($str) {

		$this->error.=	$str;

	}

	function setError($str) {

		$this->error=$str;

	}

	function getTmp() {

		$this->tmp['array_allowed_html'] = array();
		if(!empty( $this->tmp['allowed_html'])) $this->tmp['array_allowed_html'] = explode(",", $this->tmp['allowed_html']);


		$this->tmp['array_search_in_fields'] = array();
		if(!empty( $this->tmp['search_in_fields'])) $this->tmp['array_search_in_fields'] = explode(",", $this->tmp['search_in_fields']);

		$this->tmp['array_location_fields'] = array();
		if(!empty( $this->tmp['location_fields'])) $this->tmp['array_location_fields'] = explode(",", $this->tmp['location_fields']);

		$this->tmp['array_search_location_fields'] = array();
		if(!empty( $this->tmp['search_location_fields'])) $this->tmp['array_search_location_fields'] = explode(",", $this->tmp['search_location_fields']);
	
		return $this->tmp;

	}

	function deleteWatermark() {

		global $db;
		global $config_abs_path;
		$pic = $db->fetchRow("select `watermark` from ".TABLE_ADS_SETTINGS);
		if($pic) @unlink($config_abs_path."/images/".$pic);
		$db->query("update ".TABLE_ADS_SETTINGS." set `watermark` =''");

		$this->clearAdsSettingsCache();

		return 1;

	}
	function deleteExample($type) {

		global $db;
		global $config_abs_path;
		$pic = $db->fetchRow("select `".$type."_example` from ".TABLE_ADS_SETTINGS);
		if($pic) @unlink($config_abs_path."/images/".$pic);
		$db->query("update ".TABLE_ADS_SETTINGS." set `".$type."_example` =''");

		$this->clearAdsSettingsCache();

		return 1;

	}

	function deleteHeaderPic() {

		global $db;
		global $config_abs_path;
		$pic = $db->fetchRow("select `header_pic` from ".TABLE_APPEARANCE);
		if($pic) @unlink($config_abs_path."/images/".$pic);
		$db->query("update ".TABLE_APPEARANCE." set `header_pic` =''");
		$this->clearAppearanceCache();
		return 1;

	}

	function deleteSmallHeaderPic() {

		global $db;
		global $config_abs_path;
		$pic = $db->fetchRow("select `small_header_pic` from ".TABLE_APPEARANCE);
		if($pic) @unlink($config_abs_path."/images/".$pic);
		$db->query("update ".TABLE_APPEARANCE." set `small_header_pic` =''");
		$this->clearAppearanceCache();
		return 1;

	}

	function deleteFooterPic() {

		global $db;
		global $config_abs_path;
		$pic = $db->fetchRow("select `footer_pic` from ".TABLE_APPEARANCE);
		if($pic) @unlink($config_abs_path."/images/".$pic);
		$db->query("update ".TABLE_APPEARANCE." set `footer_pic` =''");
		$this->clearAppearanceCache();
		return 1;

	}

	function deleteMobileHeaderPic() {

		global $db;
		global $config_abs_path;
		$pic = $db->fetchRow("select `mobile_header_pic` from ".TABLE_MOBILE_SETTINGS);
		if($pic) @unlink($config_abs_path."/images/".$pic);
		$db->query("update ".TABLE_MOBILE_SETTINGS." set `mobile_header_pic` =''");
		$this->clearMobileSettingsCache();
		return 1;

	}

	function deleteInvoiceLogo() {

		global $db;
		global $config_abs_path;
		$pic = $db->fetchRow("select `invoice_logo` from ".TABLE_INVOICE_SETTINGS);
		if($pic) @unlink($config_abs_path."/images/".$pic);
		$db->query("update ".TABLE_INVOICE_SETTINGS." set `invoice_logo` =''");
		$this->clearMobileSettingsCache();
		return 1;

	}

	function check_form_ads_settings() {

		global $lng;
		$this->error='';
		$this->tmp=array();

		global $config_demo;
		if($config_demo==1) $this->addError($lng['general']['errors']['demo'].'<br />');

		if(!$_POST['thmb_width'] || !$_POST['thmb_height'] || !$_POST['big_thmb_width'] || !$_POST['big_thmb_height']) $this->addError($lng['settings']['errors']['incorrect_thumb_dimmensions'].'<br />');

		if(!$_POST['pic_max_size'] || !$_POST['pic_max_width'] || !$_POST['pic_max_height']) $this->addError($lng['settings']['errors']['incorrect_image_dimmensions'].'<br />');

		if($_FILES['nopic']['name']) {
			$this->nopic=new image('nopic','../images','nopic');
			if(!$this->nopic->verify()) $this->addError($lng['settings']['errors']['nopic_image'].$this->nopic->getError());
		}

		if($_FILES['med_nopic']['name']) {
			$this->med_nopic=new image('med_nopic','../images','med_nopic');
			if(!$this->med_nopic->verify()) $this->addError($lng['settings']['errors']['med_nopic_image'].$this->med_nopic->getError());
		}

		if($_FILES['big_nopic']['name']) {
			$this->big_nopic=new image('big_nopic','../images','big_nopic');
			if(!$this->big_nopic->verify()) $this->addError($lng['settings']['errors']['big_nopic_image'].$this->big_nopic->getError());
		}

		if($_FILES['watermark']['name']) {
			$this->watermark=new image('watermark','../images','watermark');
			if(!$this->watermark->verify()) $this->addError($lng['settings']['errors']['watermark_image'].$this->watermark->getError());
		}

		if(isset($_POST['watermark_transparency']) && ( !is_numeric($_POST['watermark_transparency']) || $_POST['watermark_transparency']<0 || $_POST['watermark_transparency'] > 100 ) ) $this->addError($lng['settings']['errors']['invalid_transparency'].'<br />');


		// if resize is on and no resize dimmensions 
		if(isset($_POST['resize_image']) && $_POST['resize_image']=="on" && (!isset($_POST['resize_width']) || !$_POST['resize_width'] || !is_numeric($_POST['resize_width']) || $_POST['resize_width']<0   || !isset($_POST['resize_height']) || !$_POST['resize_height'] || !is_numeric($_POST['resize_height']) || $_POST['resize_height']<0))
			$this->addError($lng['settings']['errors']['invalid_resize_dimmensions']);

		if( isset($_POST['enable_distance_search']) && $_POST['enable_distance_search']=="on" ) {
		
			// check if a required google maps field exists
			global $config_abs_path;
			require_once $config_abs_path."/classes/fields.php";
			$f = new fields("cf");
			if(!$f->getFieldsOfTypeShort("google_maps", " and `required`= 1 ")) {
				$this->addError($lng['settings']['errors']['google_maps_field_required'].'<br />');
			}
		
		}
			
		if($this->getError()!='')
		{
			// fields with default 0 value
			$array_inputs_zero = array('days_recent', 'badwords_check_type', 'no_latest', 'resize_width', 'resize_height', 'watermark_transparency', 'alerts_days_delete', 'alerts_activation', 'date_time_ago_days');

			foreach ($array_inputs_zero as $field) {

				if(isset($_POST[$field]) && $_POST[$field]) $this->tmp[$field]=$_POST[$field]; else $this->tmp[$field]=0;

			}

			// fields with default 1 value
			$array_inputs_one = array('no_latest_on_row');
			foreach ($array_inputs_one as $field) {

				if(isset($_POST[$field])) $this->tmp[$field]=$_POST[$field]; else $this->tmp[$field]=1;

			}

			// fields with default null value
			$array_inputs_null = array('thmb_width', 'thmb_height', 'big_thmb_width', 'big_thmb_height', 'pic_max_size', 'pic_max_width', 'pic_max_height', 'watermark_position', 'default_search_view', 'search_type', 'ds_distances_list', 'ds_measuring_unit', 'limit_location_autosuggest', 'gm_api_lang');
			foreach ($array_inputs_null as $field) {

				if(isset($_POST[$field])) $this->tmp[$field]=cleanStr($_POST[$field]); else $this->tmp[$field]='';

			}

			// checkbox fields
			$array_checkboxes = array('badwords_check', 'enable_stock', 'enable_sold', 'enable_rented', 'show_more_link', 'add_meta_with_listings', 'resize_image', 'description_editor', 'hide_contact_when_sold', 'hide_contact_when_rented', 'translate_title_description', 'show_ad_date_for_everybody', 'alerts_enabled', 'alerts_ask_adv_key', 'alerts_require_login', 'saved_searches_enabled', 'enable_latest', 'enable_map_search', 'map_visible', 'hide_contact_when_not_logged', 'date_time_ago_format', 'pending_edited', 'enable_auctions', 'notify_when_new_bid', 'search_by_account_type', 'enable_distance_search', 'enable_location_autosuggest', 'count_refine_results');

			foreach ($array_checkboxes as $field) {

				if(isset($_POST[$field]) && $_POST[$field]=="on") $this->tmp[$field]=1; else $this->tmp[$field]=0;

			}

			//allowed_html
			if(isset($_POST['allowed_html'])) {

				$this->tmp['allowed_html'] = cleanStr($_POST['allowed_html']);

			}

			//search_in_fields
			if(isset($_POST['search_in_fields'])) {

				$this->tmp['search_in_fields'] = cleanStr($_POST['search_in_fields']);

			}

			//location_fields
			if(isset($_POST['location_fields'])) {
				$this->tmp['location_fields'] = cleanStr($_POST['location_fields']);
			}

			//search_location_fields
			if(isset($_POST['search_location_fields'])) {
				$this->tmp['search_location_fields'] = cleanStr($_POST['search_location_fields']);
			}

		}

		return 1;
	}

	function editAdsSettings() {

		global $db;
		global $ads_settings;
		$this->clean=array();
		$this->check_form_ads_settings();
		if($this->getError()!='') return 0;

		$str_update ='';

		// fields with default 0 value
		$array_inputs_zero = array('days_recent', 'no_latest', 'resize_width', 'resize_height', 'watermark_transparency', 'alerts_days_delete', 'alerts_activation', 'date_time_ago_days');

		foreach ($array_inputs_zero as $field) {

			if(isset($_POST[$field]) && $_POST[$field]) $this->clean[$field]=escape($_POST[$field]); else $this->clean[$field]=0;
			$str_update.=" `$field` = '".$this->clean[$field]."',";

		}

		// fields with default 1 value
		$array_inputs_one = array('no_latest_on_row');
		foreach ($array_inputs_one as $field) {

			if(isset($_POST[$field]) && $_POST[$field]) $this->clean[$field]=escape($_POST[$field]); else $this->clean[$field]=1;
			$str_update.=" `$field` = '".$this->clean[$field]."',";

		}

		// fields with default null value
		$array_inputs_null = array('thmb_width', 'thmb_height', 'big_thmb_width', 'big_thmb_height', 'pic_max_size', 'pic_max_width', 'pic_max_height', 'watermark_position', 'default_search_view', 'search_type', 'ds_distances_list', 'ds_measuring_unit', 'limit_location_autosuggest', 'gm_api_lang');
		foreach ($array_inputs_null as $field) {

			$this->clean[$field] = escape($_POST[$field]);
			
			if($field=="ds_distances_list") {
				$this->clean[$field]=str_replace('\n', "|", $this->clean[$field]);
				$this->clean[$field]=str_replace('\r', "", $this->clean[$field]);
			}

			$str_update.=" `$field` = '".$this->clean[$field]."',";

		}

		// checkbox fields
		$array_checkboxes = array('badwords_check', 'enable_stock', 'enable_sold', 'enable_rented', 'show_more_link', 'add_meta_with_listings', 'resize_image', 'description_editor', 'hide_contact_when_sold', 'hide_contact_when_rented', 'translate_title_description', 'show_ad_date_for_everybody', 'alerts_enabled', 'alerts_ask_adv_key', 'alerts_require_login', 'saved_searches_enabled', 'enable_latest', 'enable_map_search', 'map_visible', 'hide_contact_when_not_logged', 'date_time_ago_format', 'pending_edited', 'enable_auctions', 'notify_when_new_bid', 'search_by_account_type', 'enable_distance_search', 'enable_location_autosuggest', 'count_refine_results');

		foreach ($array_checkboxes as $field) {

			$this->clean[$field] = checkbox_value($field);
			$str_update.=" `$field` = '".$this->clean[$field]."',";

		}

		//allowed_html
		if(isset($_POST['allowed_html'])) {

			$this->clean['allowed_html'] = cleanStr($_POST['allowed_html']);
			$str_update.=" `allowed_html` = '".$this->clean['allowed_html']."',";
		}

		//location_fields
		if(isset($_POST['location_fields'])) {
			$this->clean['location_fields'] = cleanStr($_POST['location_fields']);
			$str_update.=" `location_fields` = '".$this->clean['location_fields']."',";
		}

		//prof_groups
		if(isset($_POST['prof_groups'])) {
			$this->clean['prof_groups'] = cleanStr($_POST['prof_groups']);
			$str_update.=" `prof_groups` = '".$this->clean['prof_groups']."',";
		}

		//search_location_fields
		if(isset($_POST['search_location_fields'])) {
			$this->clean['search_location_fields'] = cleanStr($_POST['search_location_fields']);
			$str_update.=" `search_location_fields` = '".$this->clean['search_location_fields']."',";
		}

		if(isset($_FILES['nopic']['name']) && $_FILES['nopic']['name']) {
			if($this->nopic->upload()) { 
				$this->clean['nopic']=$this->nopic->getUploadedFile();
				$res=$db->query("update ".TABLE_ADS_SETTINGS." set nopic='".$this->clean['nopic']."'");
			}
			else  $this->addError($this->nopic->getError());
		}

		if(isset($_FILES['med_nopic']['name']) && $_FILES['med_nopic']['name']) {
			if($this->med_nopic->upload()) { 
				$this->clean['med_nopic']=$this->med_nopic->getUploadedFile();
				$res=$db->query("update ".TABLE_ADS_SETTINGS." set med_nopic='".$this->clean['med_nopic']."'");
			}
			else  $this->addError($this->med_nopic->getError());
		}

		if(isset($_FILES['big_nopic']['name']) && $_FILES['big_nopic']['name']) {
			if($this->big_nopic->upload()) { 
				$this->clean['big_nopic']=$this->big_nopic->getUploadedFile();
				$res=$db->query("update ".TABLE_ADS_SETTINGS." set big_nopic='".$this->clean['big_nopic']."'");
			}
			else  $this->addError($this->big_nopic->getError());
		}

		if(isset($_FILES['watermark']['name']) && $_FILES['watermark']['name']) {
			if($this->watermark->upload()) { 
				$this->clean['watermark']=$this->watermark->getUploadedFile();
				$res=$db->query("update ".TABLE_ADS_SETTINGS." set watermark='".$this->clean['watermark']."'");
			}
			else  $this->addError($this->watermark->getError());
		}

		if($this->clean['badwords_check']==1) {

			if(isset($_POST['badwords_check_type']) && $_POST['badwords_check_type']) $this->clean['badwords_check_type'] = escape($_POST['badwords_check_type']);
			else $this->clean['badwords_check_type']=0;

		} else $this->clean['badwords_check_type']=0;

		//$str_update.=" `badwords_check_type` = '".$this->clean['badwords_check_type']."'";

		//search_in_fields
		if(isset($_POST['search_in_fields'])) {
			$this->clean['search_in_fields'] = cleanStr($_POST['search_in_fields']);
		}

		$search_fields_changed = 0;

		if($this->clean['translate_title_description'] != $ads_settings['translate_title_description']) {

			// check search fields
			if($this->clean['translate_title_description']==1) {

				$this->clean['search_in_fields'] = $this->modSearchInFields(1, $this->clean['search_in_fields']);

				$search_fields_changed=1;

			}

			// check search fields
			if($this->clean['translate_title_description']==0) {

				$this->clean['search_in_fields'] = $this->modSearchInFields(0, $this->clean['search_in_fields']);

				$search_fields_changed=1;

			}

		}

		// search_in_fields
		// after translate title description to update title and description fields if it must
		if(isset($_POST['search_in_fields']) && !$search_fields_changed) {
			$str_update.=" `search_in_fields` = '".$this->clean['search_in_fields']."', ";
		}

		$res=$db->query("update ".TABLE_ADS_SETTINGS." set ".$str_update." `badwords_check_type` = '".$this->clean['badwords_check_type']."'");

		if($this->clean['translate_title_description'] != $ads_settings['translate_title_description']) {
			$ads_settings['translate_title_description'] = $this->clean['translate_title_description'];
			global $config_abs_path;
			require_once $config_abs_path."/classes/listings.php";
			$listing = new listings;
			$listing->checkLanguageFields();

		}

		// update address_components
		if($this->clean["enable_location_autosuggest"]) {
		
			$array_components = array("country", "administrative_area_level_1", "administrative_area_level_2", "locality", "postal_code");
			
			global $config_table_prefix;
			foreach($array_components as $component) {
			
				$field= escape($_POST['la_'.$component]);
				$type= escape($_POST['la_'.$component."_type"]);
				$db->query("update ".$config_table_prefix."address_components set `type`='$type', `field`='$field' where `component` like '$component'");
				
			}
		
		}
		
		$this->clearAdsSettingsCache();

		return 1;

	}

	function check_form_visibility () {

		global $lng;
		$this->error='';
		$this->tmp=array();

		global $config_demo;
		if($config_demo==1) $this->addError($lng['general']['errors']['demo'].'<br />');

		if(isset($_POST['resize_store_image']) && $_POST['resize_store_image']=="on" && (!isset($_POST['resize_store_width']) || !$_POST['resize_store_width'] || !is_numeric($_POST['resize_store_width']) || $_POST['resize_store_width']<0   || !isset($_POST['resize_store_height']) || !$_POST['resize_store_height'] || !is_numeric($_POST['resize_store_height']) || $_POST['resize_store_height']<0))
			$this->addError($lng['settings']['errors']['invalid_resize_dimmensions']);

		$array_options=array("featured", "highlited", "priorities", "video", "urgent", "url");	
		foreach($array_options as $opt) {
			if($_FILES[$opt.'_example']['name']) {
				$this->options[$opt]=new image($opt.'_example','../images',$opt);
				if(!$this->options[$opt]->verify()) $this->addError($lng['packages'][$opt]." ".$lng['settings']['errors']['example'].$this->options[$opt]->getError());
			}
		}
		
		if($this->getError()!='')
		{
			// fields with default 0 value
			$array_inputs_zero = array('no_featured', 'highlited_expires', 'priorities_expires', 'highlited_price', 'video_expires', 'video_price', 'highlited_color', 'store_availability', 'store_price', 'resize_store_width', 'resize_store_height', 'urgent_expires', 'urgent_price', 'url_expires', 'url_price');

			foreach ($array_inputs_zero as $field) {

				if(isset($_POST[$field])) $this->tmp[$field]=$_POST[$field]; else $this->tmp[$field]=0;

			}

			// fields with default 1 value
			$array_inputs_one = array('no_featured_on_row');
			foreach ($array_inputs_one as $field) {

				if(isset($_POST[$field])) $this->tmp[$field]=$_POST[$field]; else $this->tmp[$field]=1;

			}

			// checkbox fields
			$array_checkboxes = array('enable_featured', 'enable_highlited', 'enable_priorities', 'enable_video', 'resize_store_image', 'featured_autoscroll', 'random_priorities', 'prioritize_featured', 'dealer_subdomain', 'enable_urgent', 'enable_url');

			foreach ($array_checkboxes as $field) {

				if(isset($_POST[$field]) && $_POST[$field]=="on") $this->tmp[$field]=1; else $this->tmp[$field]=0;

			}
			
			// fields with default null value
			$array_inputs_null = array('featured_description', 'highlited_description', 'priorities_description', 'video_description', 'urgent_description', 'url_description');
			global $languages;
			foreach ($array_inputs_null as $field) {

				foreach ($languages as $lang) {

					$lang_id = $lang['id'];
					$f = $field."_".$lang_id;
					if(isset($_POST[$f])) 
						$this->tmp[$field][$lang_id]=cleanStr($_POST[$f]); 
					else $this->tmp[$field][$lang_id]='';
				}

			}

			
		}

		return 1;
	}

	function editVisibilityOptions() {

		global $db;
		$this->clean=array();
		$this->check_form_visibility();
		if($this->getError()!='') return 0;

		$str_update ='';

		// fields with default 0 value
		$array_inputs_zero = array('no_featured', 'highlited_expires', 'priorities_expires', 'highlited_price', 'video_expires', 'video_price', 'highlited_color', 'store_availability', 'store_price', 'resize_store_width', 'resize_store_height', 'urgent_expires', 'urgent_price', 'url_expires', 'url_price');

		foreach ($array_inputs_zero as $field) {

			if(isset($_POST[$field]) && $_POST[$field]) $this->clean[$field]=escape($_POST[$field]); else $this->clean[$field]=0;
			$str_update.=" `$field` = '".$this->clean[$field]."',";

		}

		// fields with default 1 value
		$array_inputs_one = array('no_featured_on_row');
		foreach ($array_inputs_one as $field) {

			if(isset($_POST[$field]) && $_POST[$field]) $this->clean[$field]=escape($_POST[$field]); else $this->clean[$field]=1;
			$str_update.=" `$field` = '".$this->clean[$field]."',";

		}

		// checkbox fields
		$array_checkboxes = array('enable_featured', 'enable_highlited', 'enable_priorities', 'enable_video', 'resize_store_image', 'featured_autoscroll', 'random_priorities', 'prioritize_featured', 'dealer_subdomain', 'enable_urgent', 'enable_url');

		$i=1;
		$no = count($array_checkboxes);
		foreach ($array_checkboxes as $field) {

			$this->clean[$field] = checkbox_value($field);
			$str_update.=" `$field` = '".$this->clean[$field]."'";
			if($i<$no) $str_update.=", ";
			$i++;

		}

		$res=$db->query("update ".TABLE_ADS_SETTINGS." set ".$str_update);

		$array_options=array("featured", "highlited", "priorities", "video", "urgent", "url");	
		foreach($array_options as $opt) {
			if(isset($_FILES[$opt.'_example']['name']) && $_FILES[$opt.'_example']['name']) {
			if($this->options[$opt]->upload()) { 
				$this->clean[$opt.'_example']=$this->options[$opt]->getUploadedFile();
				$res=$db->query("update ".TABLE_ADS_SETTINGS." set ".$opt."_example='".$this->clean[$opt.'_example']."'");
			}
			else  $this->addError($this->options[$opt]->getError());
			}
		}	

		$array_inputs_null_lang = array('featured_description', 'highlited_description', 'priorities_description', 'video_description', 'urgent_description', 'url_description');

		global $languages;
		//_print_r($_POST);
		foreach ($array_inputs_null_lang as $field) {

			foreach ($languages as $lang) {
				
				$lang_id = $lang['id'];
				$this->clean[$field][$lang_id] = escape($_POST[$field.'_'.$lang_id]);
			}

		}

		foreach ($languages as $lang) {

			$lang_id = $lang['id'];

			$sql = "update ".TABLE_ADS_SETTINGS."_lang set ";

			$i=0;
			foreach ($array_inputs_null_lang as $field) {

				if($i) $sql.=", ";
				$sql .="`$field` = '".$this->clean[$field][$lang_id]."'";
				$i++;
			}
			$sql.=" where `lang_id` = '$lang_id'";
//echo $sql;
//exit(0);
			$res=$db->query($sql);

		}
		
		$this->clearAdsSettingsCache();

		return 1;

	}

	static function setEnablePrice($val) {

		global $db;
		$res=$db->query("update ".TABLE_ADS_SETTINGS." set `enable_price`=$val");
		self::clearAdsSettingsCache();

	}

	static function setEnableUsername($val) {

		global $db;
		$res=$db->query("update ".TABLE_SETTINGS." set `enable_username`=$val");
		self::clearSettingsCache();

	}

	static function changeContactNameField($val) {

		global $db;
		$res=$db->query("update ".TABLE_SETTINGS." set `contact_name_field`='$val'");
		// set the contact name field required !!
		$db->query("update ".TABLE_USER_FIELDS." set `required`= 1 where `caption` like '$val'");
		self::clearSettingsCache();

	}

	function check_form_appearance () {

		global $lng;
		$this->error='';
		$this->tmp=array();

		global $config_demo;
		if($config_demo==1) $this->addError($lng['general']['errors']['demo'].'<br />');

		if(!$_POST['template']) $this->addError($lng['settings']['errors']['template_missing'].'<br />');
		if(!$_POST['admin_template']) $this->addError($lng['settings']['errors']['template_missing'].'<br />');
		if($_FILES['header_pic']['name']) {
			$this->header_pic=new image('header_pic','../images','header_pic');
			$allowedtypes=array("jpeg", "jpg", "png", "gif", "swf");
			$this->header_pic->setTypes($allowedtypes);
			if(!$this->header_pic->verify()) $this->addError($lng['settings']['errors']['header_pic_image'].$this->header_pic->getError());
		}

		if($_FILES['small_header_pic']['name']) {
			$this->small_header_pic=new image('small_header_pic','../images','small_header_pic');
			$allowedtypes=array("jpeg", "jpg", "png", "gif", "swf");
			$this->small_header_pic->setTypes($allowedtypes);
			if(!$this->small_header_pic->verify()) $this->addError($lng['settings']['errors']['header_pic_image'].$this->small_header_pic->getError());
		}

		if($_FILES['footer_pic']['name']) {
			$this->footer_pic=new image('footer_pic','../images','footer_pic');
			$allowedtypes=array("jpeg", "jpg", "png", "gif", "swf");
			$this->footer_pic->setTypes($allowedtypes);
			if(!$this->footer_pic->verify()) $this->addError($lng['settings']['errors']['footer_pic_image'].$this->footer_pic->getError());
		}


		global $languages;

		if($this->getError()!='')
		{

			$array_fields = array("template", "admin_template", "admin_language", "header_pic", "small_header_pic", "header_pic_link", "footer_pic", "footer_pic_link", "outer_table", "max_cat_per_row", "ads_per_page", "first_page_type", "template_colorscheme");

			foreach ($array_fields as $field) {

				if(isset($_POST[$field])) $this->tmp[$field]=cleanStr($_POST[$field]); else $this->tmp[$field]='';

			}

			// checkbox fields
			$array_checkboxes = array('show_footer_categ', 'show_footer', 'show_header', 'categ_count_ads');

			foreach ($array_checkboxes as $field) {

				if(isset($_POST[$field]) && $_POST[$field]=="on") $this->tmp[$field]=1; else $this->tmp[$field]=0;

			}
	
			$array_fields_lang = array("footer_text");

			foreach ($array_fields_lang as $field) {

				foreach ($languages as $lang) {

					$lang_id = $lang['id'];
					$f = $field."_".$lang_id;
					if(isset($_POST[$f])) 
						$this->tmp[$field][$lang_id]=cleanStr($_POST[$f]); 
					else $this->tmp[$field][$lang_id]='';
				}

			}

		}
		return 1;
	}

	function editAppearance() {

		global $db;
		$this->clean=array();
		$this->check_form_appearance();
		if($this->getError()!='') return 0;
		global $languages;

		$old_template = $db->fetchAssoc("select template, template_colorscheme, admin_template from ".TABLE_APPEARANCE);

		if(isset($_FILES['header_pic']['name']) && $_FILES['header_pic']['name']) {
			if($this->header_pic->upload()) { 
				$this->clean['header_pic']=$this->header_pic->getUploadedFile();
				$res=$db->query("update ".TABLE_APPEARANCE." set header_pic='".$this->clean['header_pic']."'");
			}
			else  $this->addError($this->header_pic->getError());
		}

		if(isset($_FILES['small_header_pic']['name']) && $_FILES['small_header_pic']['name']) {
			if($this->small_header_pic->upload()) { 
				$this->clean['small_header_pic']=$this->small_header_pic->getUploadedFile();
				$res=$db->query("update ".TABLE_APPEARANCE." set small_header_pic='".$this->clean['small_header_pic']."'");
			}
			else  $this->addError($this->small_header_pic->getError());
		}

		if(isset($_FILES['footer_pic']['name']) && $_FILES['footer_pic']['name']) {
			if($this->footer_pic->upload()) { 
				$this->clean['footer_pic']=$this->footer_pic->getUploadedFile();
				$res=$db->query("update ".TABLE_APPEARANCE." set footer_pic='".$this->clean['footer_pic']."'");
			}
			else  $this->addError($this->footer_pic->getError());
		}

		$str_update ='';

		// fields with default null value

		$array_inputs_null = array('template', 'template_colorscheme', 'admin_template', 'admin_language', 'header_pic_link', 'footer_pic_link', 'outer_table', 'max_cat_per_row', 'ads_per_page', 'first_page_type');
		$i=0;
		foreach ($array_inputs_null as $field) {

			if(isset($_POST[$field])) $this->clean[$field] = escape($_POST[$field]);
			else continue;
			if($i) $str_update.=",";
			$str_update.=" `$field` = '".$this->clean[$field]."'";
			$i++;

		}

		// checkbox fields
		$array_checkboxes = array('show_footer_categ', 'show_footer', 'show_header', 'categ_count_ads');
		foreach ($array_checkboxes as $field) {
			$this->clean[$field] = checkbox_value($field);
			$str_update.=", `$field` = '".$this->clean[$field]."'";
		}

		$res=$db->query("update ".TABLE_APPEARANCE." set ".$str_update);


		//$str_update_lang ='';

		$array_inputs_null_lang = array('footer_text');
		foreach ($array_inputs_null_lang as $field) {

			foreach ($languages as $lang) {
				
				$lang_id = $lang['id'];
				$this->clean[$field][$lang_id] = escape($_POST[$field.'_'.$lang_id]);
			}

		}

		$array_fields_lang = array("footer_text");

		foreach ($languages as $lang) {

			$lang_id = $lang['id'];

			$sql = "update ".TABLE_APPEARANCE."_lang set ";

			$i=0;
			foreach ($array_fields_lang as $field) {

				if($i) $sql.=", ";
				$sql .="`$field` = '".$this->clean[$field][$lang_id]."'";
				$i++;
			}
			$sql.=" where `lang_id` = '$lang_id'";

			$res=$db->query($sql);

		}

		// check if the template changed
		if( ($old_template['template']!=$this->clean['template']) || (isset($this->clean['template_colorscheme']) && ($old_template['template_colorscheme']!=$this->clean['template_colorscheme']))) 
		// empty the cache
			self::emptyCompiled();

		// check if admin template changed
		if($old_template['admin_template']!=$this->clean['admin_template']) 
		// empty the cache
			self::emptyCompiled(1);

		$this->clearAppearanceCache();
		
		//$this->minifyCSS();

		return 1;

	}


	function check_form_locales () {

		global $lng;
		$this->error='';
		$this->tmp=array();

		global $config_demo;
		if($config_demo==1) $this->addError($lng['general']['errors']['demo'].'<br />');

		global $languages;

		foreach ($languages as $lang) {

			if(!$_POST['charset_'.$lang['id']] && !$_POST['other_charset']) { $this->addError($lng['settings']['errors']['charset_missing'].'<br />');
			break;
			}
		}

		foreach ($languages as $lang) {

			if(!$_POST['date_format_'.$lang['id']]) { 
				$this->addError($lng['settings']['errors']['date_format_missing'].'<br />');
				break;
			}
		}

		foreach ($languages as $lang) {
			if(!$_POST['date_format_long_'.$lang['id']]) {
				$this->addError($lng['settings']['errors']['date_format_long_missing'].'<br />');
				break;
			}
		}

		foreach ($languages as $lang) {
			if($_POST['number_format_decimals_'.$lang['id']] && !is_numeric($_POST['number_format_decimals_'.$lang['id']])) {
				$this->addError($lng['settings']['errors']['incorrect_number_format_decimals'].'<br />');
				break;
			}
			if($_POST['price_format_decimals_'.$lang['id']] && !is_numeric($_POST['price_format_decimals_'.$lang['id']])) {
				$this->addError($lng['settings']['errors']['incorrect_price_format_decimals'].'<br />');
				break;
			}
		}

		if($this->getError()!='')
		{

			$array_fields_lang = array("charset", "default_currency", "currency_pos", "date_format", "date_format_long", "number_format_point", "number_format_separator", "price_format_point", "price_format_separator");

			foreach ($array_fields_lang as $field) {

				foreach ($languages as $lang) {

					$lang_id = $lang['id'];
					$f = $field."_".$lang_id;
					if(isset($_POST[$f])) 
						$this->tmp[$field][$lang_id]=cleanStr($_POST[$f]); 
					else $this->tmp[$field][$lang_id]='';
				}
			}

			$array_inputs_zero = array('number_format_decimals', 'price_format_decimals');

			// fields with default 0 value
			foreach ($array_inputs_zero as $field) {

				foreach ($languages as $lang) {

					$lang_id = $lang['id'];
					$f = $field."_".$lang_id;
					if(isset($_POST[$f])) 
						$this->tmp[$field][$lang_id]=cleanStr($_POST[$f]); 
					else $this->tmp[$field][$lang_id]=0;
				}
			}

			$array_inputs_null = array('timezone');

			// fields with default null value
			foreach ($array_inputs_null as $field) {

				if(isset($_POST[$field])) 
					$this->tmp[$field]=cleanStr($_POST[$field]); 
				else $this->tmp[$field]='';
			}

		}
		return 1;
	}

	function editLocales() {

		global $db;
		$this->clean=array();
		$this->check_form_locales();
		if($this->getError()!='') return 0;
		global $languages;

		foreach ($languages as $lang) {

			$lang_id = $lang['id'];
			if($_POST['charset_'.$lang_id]==-1 && isset($_POST['other_charset_'.$lang_id])) $this->clean['charset'][$lang_id] = cleanStr($_POST['other_charset_'.$lang_id]); else $this->clean['charset'][$lang_id] = cleanStr($_POST['charset_'.$lang_id]);

			if(isset($_POST['number_format_decimals_'.$lang_id]) && $_POST['number_format_decimals_'.$lang_id]) $this->clean['number_format_decimals'][$lang_id]=escape($_POST['number_format_decimals_'.$lang_id]); else $this->clean['number_format_decimals'][$lang_id]=0;

			if(isset($_POST['price_format_decimals_'.$lang_id]) && $_POST['price_format_decimals_'.$lang_id]) $this->clean['price_format_decimals'][$lang_id]=escape($_POST['price_format_decimals_'.$lang_id]); else $this->clean['price_format_decimals'][$lang_id]=0;

		}

		$array_inputs_null = array("timezone");
		foreach ($array_inputs_null as $field) {

			$this->clean[$field] = escape($_POST[$field]);

		}

		$array_inputs_null_lang = array('default_currency', 'currency_pos', 'date_format', 'date_format_long', 'number_format_point', 'number_format_separator', 'price_format_point', 'price_format_separator');
		foreach ($array_inputs_null_lang as $field) {

			foreach ($languages as $lang) {
				
				$lang_id = $lang['id'];
				if($field=="default_currency" || $field=="number_format_separator" || $field=="price_format_separator") // allow currency and number format separator to contain space 
				{
					$this->clean[$field][$lang_id] = $db->my_mysql_real_escape_string($_POST[$field.'_'.$lang_id]);
				}	
				else
					$this->clean[$field][$lang_id] = escape($_POST[$field.'_'.$lang_id]);
			}

		}

		$array_fields = array("timezone");
		$db->query("update ".TABLE_APPEARANCE." set `timezone` = '".$this->clean['timezone']."'");

		$array_fields_lang = array("default_currency", "currency_pos", "date_format", "date_format_long", "number_format_point", "number_format_separator", "price_format_point", "price_format_separator");

		foreach ($languages as $lang) {

			$lang_id = $lang['id'];

			$sql = "update ".TABLE_APPEARANCE."_lang set `charset` = '".$this->clean['charset'][$lang_id]."', `number_format_decimals` = '".$this->clean['number_format_decimals'][$lang_id]."', `price_format_decimals` = '".$this->clean['price_format_decimals'][$lang_id]."' ";

			foreach ($array_fields_lang as $field) {

				$sql .=", `$field` = '".$this->clean[$field][$lang_id]."'";

			}
			$sql.=" where `lang_id` = '$lang_id'";

			$res=$db->query($sql);
		}
		$this->clearAppearanceCache();

		// update time offset
		settings::updateTimeOffset();

		return 1;

	}

	function check_form_mail_settings () {

		global $lng;
		$this->error='';
		$this->tmp=array();

		global $config_demo;
		if($config_demo==1) $this->addError($lng['general']['errors']['demo'].'<br />');

		if(isset($_POST['use_smtp_auth']) && $_POST['use_smtp_auth'] && ( !$_POST['smtp_server'] || !$_POST['port'] || !$_POST['mail_username'])) $this->addError($lng['settings']['errors']['mail_auth_settings_missing'].'<br />');

		if(isset($_POST['port']) && $_POST['port'] && (!is_numeric($_POST['port']) || $_POST['port'] > 65535)) $this->addError($lng['settings']['errors']['invalid_port_number'].'<br />');

		if(isset($_POST['bcc_to']) && $_POST['bcc_to'] && (!validator::valid_email($_POST['bcc_to']))) $this->addError($lng['errors']['invalid_email'].'<br />');

		if($this->getError()!='')
		{
			if(isset($_POST['use_smtp_auth']) && $_POST['use_smtp_auth']=="on") $this->tmp['use_smtp_auth']=1; else $this->tmp['use_smtp_auth']=0;

			$this->tmp['html_mails'] = checkbox_value('html_mails');

			$this->tmp['send_using_admin_email'] = checkbox_value('send_using_admin_email');
			

			if(isset($_POST['encryption'])) $this->tmp['encryption']=$_POST['encryption']; else $this->tmp['encryption']='';

			if(isset($_POST['smtp_server'])) $this->tmp['smtp_server']=$_POST['smtp_server']; else $this->tmp['smtp_server']='';

			if(isset($_POST['port'])) $this->tmp['port']=$_POST['port']; else $this->tmp['port']='';

			if(isset($_POST['mail_username'])) $this->tmp['username']=$_POST['mail_username']; else $this->tmp['username']='';

			if(isset($_POST['mail_password'])) $this->tmp['password']=$_POST['mail_password']; else $this->tmp['password']='';

			if(isset($_POST['bcc_to'])) $this->tmp['bcc_to']=$_POST['bcc_to']; else $this->tmp['bcc_to']='';

		}

		return 1;
	}

	function editMailSettings() {

		global $db;
		$this->clean=array();
		$this->check_form_mail_settings();
		if($this->getError()!='') return 0;

		$this->clean['use_smtp_auth'] = checkbox_value('use_smtp_auth');

		$this->clean['html_mails'] = checkbox_value('html_mails');

		$this->clean['send_using_admin_email'] = checkbox_value('send_using_admin_email');

		if(isset($_POST['encryption']) && $_POST['encryption']) $this->clean['encryption'] = escape($_POST['encryption']); else $this->clean['encryption'] = '';

		if(isset($_POST['smtp_server']) && $_POST['smtp_server']) $this->clean['smtp_server'] = escape($_POST['smtp_server']); else $this->clean['smtp_server'] = '';

		if(isset($_POST['port']) && $_POST['port']) $this->clean['port'] = escape($_POST['port']); else $this->clean['port'] = '';

		if(isset($_POST['mail_username']) && $_POST['mail_username']) $this->clean['username'] = escape($_POST['mail_username']); else $this->clean['username'] = '';

		if(isset($_POST['mail_password']) && $_POST['mail_password']) $this->clean['password'] = escape($_POST['mail_password']); else $this->clean['password'] = '';

		if(isset($_POST['bcc_to']) && $_POST['bcc_to']) $this->clean['bcc_to'] = escape($_POST['bcc_to']); else $this->clean['bcc_to'] = '';

		$res=$db->query('update '.TABLE_MAIL_SETTINGS.' set `html_mails` = "'.$this->clean['html_mails'].'", `use_smtp_auth` = "'.$this->clean['use_smtp_auth'].'", `encryption` = "'.$this->clean['encryption'].'", `send_using_admin_email` = "'.$this->clean['send_using_admin_email'].'", `smtp_server` = "'.$this->clean['smtp_server'].'", `port` = "'.$this->clean['port'].'", `username` = "'.$this->clean['username'].'", `password` = "'.$this->clean['password'].'", `bcc_to` = "'.$this->clean['bcc_to'].'";');

		$this->clearMailSettingsCache();

		return 1;

	}

	function check_form_seo_settings() {
	
		$array_sef_links = array("listings", "user_listings", "store", "content", "affiliate", "recent_ads", "featured_ads", "auctions", "login", "register", "logout", "favorites", "contact", "pre_submit", "refine", "contact_details");
	
		$err = 0;
		foreach($array_sef_links as $link) {
		
			if(!isset($_POST[$link]) || !$_POST[$link]) $err=1;
		
		}
		global $lng;

		if($err) $this->addError($lng['seo_settings']['error']['links_structure']);
		
		if($this->getError()!='') {
		
			$this->tmp = array();
			$this->tmp['enable_mod_rewrite'] = checkbox_value('enable_mod_rewrite');
			$this->tmp['analytics_code'] = escape($_POST['analytics_code']);
			$this->tmp['sef_legacy_mode'] = checkbox_value('sef_legacy_mode');
			$this->tmp['maximum_slug_width'] = escape($_POST['maximum_slug_width']);
			
			$this->tmp['sef_links'] = array();
			foreach($array_sef_links as $link) {
				$this->tmp['sef_links'][$link] = $_POST[$link];
			}
		}
	
	}
	
	function editSeoSettings() {

		global $db;
		global $config_demo;
		if($config_demo==1) return;

		$this->check_form_seo_settings();
		if($this->getError()!='') return 0;

		global $languages;
		$this->clean=array();
		$this->clean['enable_mod_rewrite'] = checkbox_value('enable_mod_rewrite');
		$this->clean['analytics_code'] = escape($_POST['analytics_code']);
		$this->clean['sef_legacy_mode'] = checkbox_value('sef_legacy_mode');
		$this->clean['maximum_slug_width'] = escape($_POST['maximum_slug_width']);
		
		$array_sef_links = array("listings", "user_listings", "store", "content", "affiliate", "recent_ads", "featured_ads", "auctions", "login", "register", "logout", "favorites", "contact", "pre_submit", "refine", "contact_details");
		
		foreach($array_sef_links as $link) {
			$sef_links[$link] = $_POST[$link];
		}
		$this->clean['sef_links'] = json_encode($sef_links);

		$res=$db->query('update '.TABLE_SEO_SETTINGS.' set `enable_mod_rewrite` = "'.$this->clean['enable_mod_rewrite'].'", `analytics_code`="'.$this->clean['analytics_code'].'", `sef_legacy_mode`="'.$this->clean['sef_legacy_mode'].'", `sef_links`="'.escape($this->clean['sef_links']).'", `maximum_slug_width`="'.escape($this->clean['maximum_slug_width']).'";');

		$this->clearSeoSettingsCache();

		return 1;

	}

	function editSeoPage() {

		$page_name = escape($_POST['page']);
		global $db;
		$languages = common::getCachedObject("base_languages");
		foreach ($languages as $lang) {

			$title = escape($_POST['title_'.$lang['id']]);
			$meta_keywords = escape($_POST['meta_keywords_'.$lang['id']]);
			$meta_description = escape($_POST['meta_description_'.$lang['id']]);
			if($page_name!="listings")
				$noindex = checkbox_value("noindex");
			else 	
				$noindex = escape($_POST["noindex"]);
			$db->query("update ".TABLE_SEO_PAGES." set `title`='$title', `meta_keywords`='$meta_keywords', `meta_description`='$meta_description', `noindex`='$noindex' where `page`='$page_name' and `lang_id`='{$lang['id']}'");

		}

		$this->clearSeoPagesCache();

		return ;

	}


	function check_form_settings () {

		global $lng;
		$this->error='';
		$this->tmp=array();

		global $config_demo;
		if($config_demo==1) $this->addError($lng['general']['errors']['demo'].'<br />');

		if(!$_POST['admin_username']) $this->addError($lng['settings']['errors']['admin_username_missing'].'<br />');
		else {
			global $config_abs_path;
			require_once $config_abs_path."/classes/users.php"; 
			$usr = new users();
			if($usr->user_exists(escape($_POST['admin_username'])))
				$this->addError($lng['users']['errors']['username_exists'].'<br />');
		}
		if(!$_POST['admin_email']) $this->addError($lng['settings']['errors']['admin_email_missing'].'<br />');
		else { 
			if(!validator::valid_email($_POST['admin_email'])) $this->addError($lng['settings']['errors']['invalid_admin_email'].'<br />');
		}

		if($_POST['contact_email'] && !validator::valid_email($_POST['contact_email'])) $this->addError($lng['settings']['errors']['invalid_contact_email'].'<br />');

		if(!empty($_POST['enable_recaptcha']) && $_POST['enable_recaptcha'] && (empty($_POST['recaptcha_public_key']) || empty($_POST['recaptcha_private_key']))) $this->addError($lng['settings']['errors']['recaptcha_enter_keys'].'<br />');

		
		if(isset($_POST['nologin_activate_via_sms']) && $_POST['nologin_activate_via_sms']=='on') {

			global $config_abs_path;
			require_once $config_abs_path."/classes/users.php";
			$usr = new users();
			$found = $usr->getRequiredIntlPhoneField(-1);
			
			if(!$found) $this->addError($lng['nologin']['errors']['no_required_intl_phone'].'<br />'); 
			
			// check if a SMS gateway is default and configured
			$sg = new sms_gateways();
			$default = $sg->getDefault();
			$no_sg = 0;
			if($default) {
			
				$class_name = sms_gateways::getSMSGatewayClass($default);
				global $config_abs_path;
				require_once($config_abs_path.'/classes/sms_verification/'.$class_name.'.php');
				
				$gcl = new $class_name;
				if(!$gcl->correctSettings()) $no_sg=1;

			} else {
			
				$no_sg = 1;
			
			}
			
			if($no_sg) $this->addError($lng['groups']['errors']['no_default_sms_or_not_configured'].'<br />'); 
			
 		
		}

		if($this->getError()!='')
		{
 
			// fields with default 0 value
			$array_inputs_zero = array('days_del_expired', 'days_notify', 'delete_login_older_than', 'google_maps_default_height', 'session_expires');

			foreach ($array_inputs_zero as $field) {

				if(isset($_POST[$field])) $this->tmp[$field]=$_POST[$field]; else $this->tmp[$field]=0;

			}
			// fields with default null value
			$array_inputs_null = array('admin_name', 'admin_username', 'admin_email', 'contact_email', 'site_name', 'google_maps_default_long', 'google_maps_default_lat', 'delete_expired', 'subdomain_field', 'recaptcha_public_key', 'recaptcha_private_key', 'google_maps_api_key');
			foreach ($array_inputs_null as $field) {

				if(isset($_POST[$field])) $this->tmp[$field]=cleanStr($_POST[$field]); else $this->tmp[$field]='';

			}
			global $languages;
			$array_inputs_lang_null = array('admin_name', 'site_name');
			foreach ($array_inputs_lang_null as $field) {

				foreach ($languages as $lang) {

					$lang_id = $lang['id'];
					$f = $field."_".$lang_id;
					if(isset($_POST[$f])) {
						$this->tmp[$field]["$lang_id"]=cleanStr($_POST[$f]);
						} 
					else $this->tmp[$field]["$lang_id"]='';
				}

			}

			// checkbox fields
			$array_checkboxes = array('users_delete_ads', 'users_feature_ads', 'register_captcha', 'contact_captcha', 'cron_simulator', 'send_mail_to_admin_when_pending', 'send_mail_to_admin_when_registeres', 'send_mail_to_admin_when_new_ad', 'send_mail_to_user_when_posting_ads', 'send_mail_to_user_when_expired', 'send_mail_to_user_before_expires', 'nologin_enabled', 'nologin_pending_listing', 'nologin_allow_edit', 'nologin_allow_delete', 'nologin_extra_options', 'nologin_image_verification', 'internal_messaging', 'enable_locations', 'enable_subdomains', 'enable_recaptcha', 'users_can_ask_account_removal', 'contact_messages_pending', 'enable_affiliates', 'nologin_activate_via_email', 'nologin_activate_via_sms');

			foreach ($array_checkboxes as $field) {

				if(isset($_POST[$field]) && $_POST[$field]=="on") $this->tmp[$field]=1; else $this->tmp[$field]=0;

			}

			//location_fields
			if(isset($_POST['location_fields'])) {
				$this->tmp['location_fields'] = cleanStr($_POST['location_fields']);
			}

		}

		return 1;
	}

	function editSettings() {

		global $db;
		$this->clean=array();
		$this->check_form_settings();
		if($this->getError()!='') return 0;

		global $languages;
		$str_update = '';

		// checkbox fields
		$array_checkboxes = array('users_delete_ads', 'users_feature_ads', 'register_captcha', 'login_captcha', 'contact_captcha', 'cron_simulator', 'send_mail_to_admin_when_pending', 'send_mail_to_admin_when_registeres', 'send_mail_to_admin_when_new_ad', 'send_mail_to_user_when_posting_ads', 'send_mail_to_user_when_expired', 'send_mail_to_user_before_expires', 'nologin_enabled', 'nologin_pending_listing', 'nologin_extra_options', 'nologin_allow_edit', 'nologin_allow_delete', 'nologin_image_verification', 'internal_messaging', 'enable_locations', 'enable_subdomains', 'enable_recaptcha', 'users_can_ask_account_removal', 'contact_messages_pending', 'enable_affiliates', 'nologin_activate_via_email', 'nologin_activate_via_sms');
	
		foreach ($array_checkboxes as $field) {

			$this->clean[$field] = checkbox_value($field);
			$str_update.=" `$field` = '".$this->clean[$field]."', ";

		}

		// fields with default 0 value
		$array_inputs_zero = array('days_notify', 'delete_login_older_than', 'session_expires');

		foreach ($array_inputs_zero as $field) {

			if(isset($_POST[$field]) && $_POST[$field]) $this->clean[$field]=escape($_POST[$field]); else $this->clean[$field]=0;
			$str_update.=" `$field` = '".$this->clean[$field]."',";

		}

		// fields with default null value
		$array_inputs_null = array('admin_username', 'admin_email', 'contact_email', 'subdomain_field', 'recaptcha_public_key', 'recaptcha_private_key', 'google_maps_api_key');
		$no = count($array_inputs_null);
		$i=1;
		foreach ($array_inputs_null as $field) {

			$this->clean[$field] = escape($_POST[$field]);
			$str_update.=" `$field` = '".$this->clean[$field]."'";
			$str_update.=", ";
			//$i++;
		}

		$del_expired = escape($_POST['delete_expired']);
		if($del_expired=="never") { 
			$this->clean['delete_expired'] = 0;
			$this->clean['days_del_expired'] = escape($_POST['days_del_expired']);
		}
		else {
			$this->clean['delete_expired'] = 1;
			if($del_expired=="immediately") $this->clean['days_del_expired'] = 0;
			else $this->clean['days_del_expired'] = escape($_POST['days_del_expired']);
		}
		$str_update.=" `delete_expired` = '".$this->clean['delete_expired']."',";
		$str_update.=" `days_del_expired` = '".$this->clean['days_del_expired']."'";

		//location_fields
		if(isset($_POST['location_fields'])) {
			$this->clean['location_fields'] = clean($_POST['location_fields']);
			$str_update.=", `location_fields` = '".$this->clean['location_fields']."'";
		}

		$array_maps = array('google_maps_default_long', 'google_maps_default_lat', 'google_maps_default_height');
		$no=count($array_maps);
		foreach ($array_maps as $field) {

			$this->clean[$field] = escape($_POST[$field]);

		}

		if(!$this->clean['google_maps_default_long']) $this->clean['google_maps_default_long'] = '-83.022206';
		if(!$this->clean['google_maps_default_lat']) $this->clean['google_maps_default_lat'] = '39.998264';
		if(!$this->clean['google_maps_default_height']) $this->clean['google_maps_default_height'] = '6';
		$i=0;
		foreach ($array_maps as $field) {
			$str_update.=",";
			$str_update.=" `$field` = '".$this->clean[$field]."'";
			$i++;

		}

		$res=$db->query("update ".TABLE_SETTINGS." set ".$str_update);

		global $settings;

		if((isset($_POST['enable_locations']) && $_POST['enable_locations'] && !$settings['enable_locations']) || (isset($_POST['enable_locations']) && $_POST['enable_locations'] && escape($_POST['location_fields']) != $settings['location_fields'])) {

			$settings=settings::getSettings();
			global $config_abs_path;
			require_once $config_abs_path."/classes/listings.php";
			$l = new listings;
			$l->countLocationAds();
		}

		// lang fields
		$array_inputs_null_lang = array('admin_name', 'site_name');
		foreach ($array_inputs_null_lang as $field) {

			foreach ($languages as $lang) {
				$lang_id = $lang['id'];
				$f = $field."_".$lang_id;
				if(isset($_POST[$f])) $this->clean[$field][$lang_id] = escape($_POST[$f]); 
				else $this->clean[$field][$lang_id] = '';
			}
		}

		foreach ($languages as $lang) {

			$lang_id = $lang['id'];
			$res=$db->query("update ".TABLE_SETTINGS."_lang set `admin_name` = '".$this->clean["admin_name"][$lang_id]."' , `site_name` = '".$this->clean["site_name"][$lang_id]."' where `lang_id` = '$lang_id'");
		}

		self::clearSettingsCache();

		//$db->query($sql_gr);

		// set default value for last affiliates payment
		if(!$settings['enable_affiliates'] && checkbox_value('enable_affiliates')) {

			// enable affiliates custom page
			global $crt_lang;
			$page_id = $db->fetchRow("select id from ".TABLE_CUSTOM_PAGES."_lang where `title` like 'Affiliates' and `lang_id` like '$crt_lang'");
			if($page_id) $db->query("update ".TABLE_CUSTOM_PAGES." set `active`=1 where id=$page_id");
			global $config_abs_path;
			require_once $config_abs_path."/classes/config/custom_pages_config.php";
			$cp = new custom_pages_config();
			$cp->clearNavbarLinksCache();

		}

		require_once "../classes/config/groups_config.php";
		$gr = new groups_config;
		$gr->clearGroupsCache();

		return 1;

	}


	function check_form_mobile_settings() {

		global $lng;
		$this->error='';
		$this->tmp=array();

		global $config_demo;
		if($config_demo==1) $this->addError($lng['general']['errors']['demo'].'<br />');

		if(!$_POST['mobile_thmb_width'] || !$_POST['mobile_thmb_height'] || !$_POST['mobile_big_thmb_width'] || !$_POST['mobile_big_thmb_height']) $this->addError($lng['settings']['errors']['incorrect_thumb_dimmensions'].'<br />');

		if($_FILES['mobile_nopic']['name']) {
			$this->nopic=new image('mobile_nopic','../images','nopic');
			if(!$this->nopic->verify()) $this->addError($lng['settings']['errors']['nopic_image'].$this->nopic->getError());
		}

		if($_FILES['mobile_big_nopic']['name']) {
			$this->big_nopic=new image('mobile_big_nopic','../images','big_nopic');
			if(!$this->big_nopic->verify()) $this->addError($lng['settings']['errors']['big_nopic_image'].$this->big_nopic->getError());
		}

		if($_FILES['mobile_header_pic']['name']) {
			$this->header_pic=new image('mobile_header_pic','../images','header_pic');
			$allowedtypes=array("jpeg", "jpg", "png", "gif");
			$this->header_pic->setTypes($allowedtypes);
			if(!$this->header_pic->verify()) $this->addError($lng['settings']['errors']['header_pic_image'].$this->header_pic->getError());
		}

		if($this->getError()!='')
		{

			// fields with default null value
			$array_inputs_null = array('mobile_template', 'mobile_thmb_height', 'mobile_big_thmb_width', 'mobile_thmb_width', 'mobile_thmb_height', 'mobile_header_pic_link', 'mobile_ads_per_page');
			foreach ($array_inputs_null as $field) {

				if(isset($_POST[$field])) $this->tmp[$field]=cleanStr($_POST[$field]); else $this->tmp[$field]='';

			}

			// checkbox fields
			$array_checkboxes = array('enable_mobile_templates', 'enable_mobile_subdomain', 'mobile_show_header');

			foreach ($array_checkboxes as $field) {

				if(isset($_POST[$field]) && $_POST[$field]=="on") $this->tmp[$field]=1; else $this->tmp[$field]=0;

			}

		}

		return 1;
	}

	function editMobileSettings() {

		global $db;
		global $mobile_settings;
		$this->clean=array();
		$this->check_form_mobile_settings();
		if($this->getError()!='') return 0;

		$str_update ='';


		// fields with default null value
		$array_inputs_null = array('mobile_template', 'mobile_thmb_height', 'mobile_big_thmb_width', 'mobile_thmb_width', 'mobile_thmb_height', 'mobile_header_pic_link', 'mobile_ads_per_page');
		foreach ($array_inputs_null as $field) {

			$this->clean[$field] = escape($_POST[$field]);
			$str_update.=" `$field` = '".$this->clean[$field]."',";

		}

		// checkbox fields
		$array_checkboxes = array('enable_mobile_templates', 'enable_mobile_subdomain', 'mobile_show_header');
		$no = count($array_checkboxes); $i=0;
		foreach ($array_checkboxes as $field) {

			$this->clean[$field] = checkbox_value($field);
			$str_update.=" `$field` = '".$this->clean[$field]."'";
			if($i<$no-1) $str_update.=", ";
			$i++;

		}

		if(isset($_FILES['mobile_nopic']['name']) && $_FILES['mobile_nopic']['name']) {
			if($this->nopic->upload()) { 
				$this->clean['mobile_nopic']=$this->nopic->getUploadedFile();
				$res=$db->query("update ".TABLE_MOBILE_SETTINGS." set mobile_nopic='".$this->clean['mobile_nopic']."'");
			}
			else $this->addError($this->nopic->getError());
		}

		if(isset($_FILES['mobile_big_nopic']['name']) && $_FILES['mobile_big_nopic']['name']) {
			if($this->big_nopic->upload()) { 
				$this->clean['mobile_big_nopic']=$this->big_nopic->getUploadedFile();
				$res=$db->query("update ".TABLE_MOBILE_SETTINGS." set mobile_big_nopic='".$this->clean['mobile_big_nopic']."'");
			}
			else  $this->addError($this->big_nopic->getError());
		}

		if(isset($_FILES['mobile_header_pic']['name']) && $_FILES['mobile_header_pic']['name']) {
			if($this->header_pic->upload()) { 
				$this->clean['mobile_header_pic']=$this->header_pic->getUploadedFile();
				$res=$db->query("update ".TABLE_MOBILE_SETTINGS." set mobile_header_pic='".$this->clean['mobile_header_pic']."'");
			}
			else  $this->addError($this->header_pic->getError());
		}

		$res=$db->query("update ".TABLE_MOBILE_SETTINGS." set ".$str_update);

		$this->clearMobileSettingsCache();

		return 1;

	}

	function editSecuritySettings() {

		global $db;
		$this->clean=array();

		// checkbox fields
		$array_checkboxes = array('block_admin_attempts', 'block_user_attempts');

		foreach ($array_checkboxes as $field) {

			if(isset($_POST[$field]) && $_POST[$field]=="on") $this->clean[$field]=1; else $this->clean[$field]=0;

		}

		if(isset($_POST["allowed_admin_attempts"]) && is_numeric($_POST["allowed_admin_attempts"])) $this->clean["allowed_admin_attempts"]=$_POST["allowed_admin_attempts"]; else $this->clean["allowed_admin_attempts"]=5;
		
		if(isset($_POST["block_admin_attempts_for"]) && is_numeric($_POST["block_admin_attempts_for"])) $this->clean["block_admin_attempts_for"]=$_POST["block_admin_attempts_for"]; else $this->clean["block_admin_attempts_for"]=1;
		
		if(isset($_POST["allowed_user_attempts"]) && is_numeric($_POST["allowed_user_attempts"])) $this->clean["allowed_user_attempts"]=$_POST["allowed_user_attempts"]; else $this->clean["allowed_user_attempts"]=5;

		if(isset($_POST["block_user_attempts_for"]) && is_numeric($_POST["block_user_attempts_for"])) $this->clean["block_user_attempts_for"]=$_POST["block_user_attempts_for"]; else $this->clean["block_user_attempts_for"]=1;

		$res=$db->query('update '.TABLE_SECURITY_SETTINGS.' set `block_admin_attempts` = "'.$this->clean['block_admin_attempts'].'", `allowed_admin_attempts` = "'.$this->clean['allowed_admin_attempts'].'", `block_admin_attempts_for` = "'.$this->clean['block_admin_attempts_for'].'", `block_user_attempts` = "'.$this->clean['block_user_attempts'].'", `allowed_user_attempts` = "'.$this->clean['allowed_user_attempts'].'", `block_user_attempts_for` = "'.$this->clean['block_user_attempts_for'].'";');

		// clear and write cache
		$lc_cache = new cache();
		$lc_cache->writeCache("security_settings", $this->clean, '', 1);

		return 1;

	}
	
		function check_form_invoice_settings() {

		global $lng;
		$this->error='';
		$this->tmp=array();

		global $config_demo;
		if($config_demo==1) $this->addError($lng['general']['errors']['demo'].'<br />');

		if($_FILES['invoice_logo']['name']) {
			$this->invoice_logo=new image('invoice_logo','../images','invoice_logo');
			if(!$this->invoice_logo->verify()) $this->addError($lng['settings']['errors']['invoice_logo'].$this->invoice_logo->getError());
		}

		if($this->getError()!='')
		{

			// fields with default null value
			$array_inputs_null = array('seller_details', 'user_fields', 'custom_text', 'filename', 'vat_percent');
			foreach ($array_inputs_null as $field) {

				if(isset($_POST[$field])) $this->tmp[$field]=cleanStr($_POST[$field]); else $this->tmp[$field]='';

			}

			// checkbox fields
			$array_checkboxes = array('enable_invoices', 'show_vat');

			foreach ($array_checkboxes as $field) {

				if(isset($_POST[$field]) && $_POST[$field]=="on") $this->tmp[$field]=1; else $this->tmp[$field]=0;

			}

		}

		return 1;
	}

	function editInvoiceSettings() {

		global $db;
		global $ads_settings;
		$this->clean=array();
		$this->check_form_invoice_settings();
		if($this->getError()!='') return 0;

		$str_update ='';

		// fields with default null value
		$array_inputs_null = array('seller_details', 'user_fields', 'custom_text', 'filename', 'vat_percent');
		foreach ($array_inputs_null as $field) {

			if($field=="user_fields") 
				$this->clean[$field] = escape($_POST['user_fields_val']);
			else
				$this->clean[$field] = escape($_POST[$field]);
			$str_update.=" `$field` = '".$this->clean[$field]."',";

		}

		// checkbox fields
		$array_checkboxes = array('enable_invoices', 'show_vat');

		foreach ($array_checkboxes as $field) {

			$this->clean[$field] = checkbox_value($field);
			$str_update.=" `$field` = '".$this->clean[$field]."',";

		}

		if(isset($_FILES['invoice_logo']['name']) && $_FILES['invoice_logo']['name']) {
			if($this->invoice_logo->upload()) { 
				$this->clean['invoice_logo']=$this->invoice_logo->getUploadedFile();
				$res=$db->query("update ".TABLE_INVOICE_SETTINGS." set `invoice_logo`='".$this->clean['invoice_logo']."'");
			}
			else  $this->addError($this->invoice_logo->getError());
		}

		$str_update = rtrim($str_update, ',');
		
		$res=$db->query("update ".TABLE_INVOICE_SETTINGS." set ".$str_update);

		$this->clearInvoiceSettingsCache();

		return 1;

	}
	
	function check_change_password() {

		global $lng;
		$this->error='';

		global $config_demo;
		if($config_demo==1) $this->addError($lng['general']['errors']['demo'].'<br />');

		if(!$_POST['password'] || !$_POST['password1']) $this->addError($lng['users']['errors']['password_missing']);
		else if(strcmp($_POST['password'], $_POST['password1'])) $this->addError($lng['users']['errors']['passwords_dont_match']);

		return 1;

	}

	function change_password() {

		global $db;
		$this->check_change_password();
		if($this->getError()!='') return 0;
		
		global $config_abs_path;
		require_once $config_abs_path."/classes/settings.php";
		
		$res=$db->query("update ".TABLE_SETTINGS." set admin_password='".settings::encode($_POST['password'])."'");

		self::clearSettingsCache();

		return 1;

	}

	function install_change_settings() {

		global $db, $config_live_site;
		if(isset($_POST['password']) && $_POST['password']!='' && $_POST['password']!='admin') 
			$res=$db->query("update ".TABLE_SETTINGS." set admin_password='".users::encode($_POST['password'])."'");
		$res=$db->query("update ".TABLE_SETTINGS."_lang set site_name='".escape($_POST['site_name'])."'");
		if(isset($_POST['admin_email']) && $_POST['admin_email']!='')
			$res=$db->query("update ".TABLE_SETTINGS." set admin_email='".escape($_POST['admin_email'])."',  contact_email='".$_POST['admin_email']."'");
		if(isset($_POST['admin_username']) && $_POST['admin_username']!='' && $_POST['admin_username']!='admin')
			$res=$db->query("update ".TABLE_SETTINGS." set admin_username='".escape($_POST['admin_username'])."'");

		// change top logo link to be main site link
		$res=$db->query("update ".TABLE_SETTINGS." set `header_pic_link`='".$config_live_site."'");

		self::clearSettingsCache();

		return 1;

	}

	function changeCharset($charset) {

		global $db;
		if($charset) {
			$res=$db->query("update ".TABLE_APPEARANCE."_lang set `charset`='".escape($charset)."'");
			$this->clearAppearanceCache();
		}
		return;
	}

	static function disableTranslateTitleDescription() {

		global $db, $ads_settings;
		$res=$db->query("update ".TABLE_ADS_SETTINGS." set translate_title_description=0");
		$sc = new settings_config;
		$search_in = $sc->modSearchInFields(0, $ads_settings['search_in_fields']);
		$res=$db->query("update ".TABLE_ADS_SETTINGS." set search_in_fields='$search_in'");
		return;

	}

	function modSearchInFields($translate, $arr) {

		if($translate) {
			$search_f = explode(",", $arr);
			$found = 0;
			global $crt_lang;
			$new_search_fields = '';
			$i=0;
			foreach ($search_f as $f) {
				if($i) $new_search_fields.=",";
				if($f=="title") { $found=1; $f = "title_$crt_lang"; } 
				if($f=="description") { $found=1; $f = "description_$crt_lang"; } 
				$new_search_fields.=$f;
				$i++;
			}
			if($found) $arr = $new_search_fields;
		}
		else {

			$search_f = explode(",", $arr);
			$found_title = 0;
			$found_desc = 0;

			$new_search_fields = '';
			$i=0;
			foreach ($search_f as $f) {
				if(strstr($f,"title_")) { 
					if(!$found_title) { $f="title"; $found_title=1; } 
					else continue; 
				}
				if(strstr($f,"description_")) { 
					if(!$found_desc) { $f="description"; $found_desc=1; } 
					else continue; 
				}
				if($i) $new_search_fields.=",";
				$new_search_fields.=$f;
				$i++;
			}
			if($found_title || $found_desc) $arr = $new_search_fields;
		}

		return $arr;

	}

	function clearAdsSettingsCache() {

		// clear cache
		$lc_cache = new cache();
		//$lc_cache->clearCache("base_settings");
		$lc_cache->clearAllCacheFiles();

	}

	function clearAppearanceCache() {

		// clear cache
		$lc_cache = new cache();
		//$lc_cache->clearCache("base_settings");
		// in case categories settings are changed
		//$lc_cache->clearCache("base_categories");
		$lc_cache->clearAllCacheFiles();
		

	}

	static function clearSettingsCache() {

		// clear cache
		$lc_cache = new cache();
		//$lc_cache->clearCache("base_settings");
		$lc_cache->clearAllCacheFiles();

	}

	function clearSeoSettingsCache() {

		// clear cache
		$lc_cache = new cache();
		$lc_cache->clearCache("base_settings");

	}

	function clearSeoPagesCache() {

		// clear cache
		$lc_cache = new cache();
		$lc_cache->clearCache("base_meta_info");

	}

	function clearMailSettingsCache() {

		// clear cache
		$lc_cache = new cache();
		$lc_cache->clearCache("base_settings");

	}

	function clearMobileSettingsCache() {

		// clear cache
		$lc_cache = new cache();
		$lc_cache->clearCache("base_settings");

	}

	function clearInvoiceSettingsCache() {

		// clear cache
		$lc_cache = new cache();
		$lc_cache->clearCache("base_settings");

	}

	public static function emptyCompiled($adm=0) {
	
		global $config_abs_path;
		$adm_folder = '';
		if($adm) $adm_folder="/admin";
		$tmp_dir = $config_abs_path.$adm_folder."/temp";
		$files = dir($tmp_dir);
		while ($file = $files->read()) {
			if($file && $file!='.' && $file!='..' && $file!=".htaccess" && $file!="index.html") {
				$fis = $tmp_dir.'/'.$file;
				@unlink($fis);
			}
		} closedir($files->handle);
	}
/*
	function minifyCSS()
	{
		global $appearance_settings;
		$appearance_settings = settings::getAppearance();

		// minify css
		$min = new minifier();
		$min->minifyCSS();
		$min->minifyCSS('rtl');

	}
*/

	function setMaintenance($val) {
	
		global $db;
		$db->query("update ".TABLE_APPEARANCE." set `maintenance_mode` ='$val'");
		$this->clearAppearanceCache();
	
	}

	function addMaintenanceIPs($str) {
	
		global $db;
		$db->query("update ".TABLE_APPEARANCE." set `maintenance_ips` ='$str'");
		$this->clearAppearanceCache();
	
	}

}
?>
