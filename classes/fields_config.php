<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class fields_config {

	var $error;
	var $tmp;
	var $type;
	var $not_logged_in;

	public function __construct($type)
	{

		global $db;
		$this->type = $type;
		if($type=='uf') { $this->table = TABLE_USER_FIELDS; $this->type='uf'; } else { $this->table=TABLE_FIELDS; $this->type='cf'; }
		$this->edit = 0;
		$this->not_logged_in = 0;
	}

	function delete($id='') {

		global $db;
		global $config_demo;

		if($config_demo==1) return;

		if(!$id) $id=$this->id;
		$this->remakeOrder($id);
		$select_fields = '`type`, `caption`, `read_only`, `dep_id`';
		if($this->type=='cf') $select_fields.=', `type`, `fieldset`';
		if($this->type=='uf') $select_fields.=', `groups`';
		$row=$db->fetchAssoc('select '.$select_fields.' from `'.$this->table.'` where id="'.$id.'"');

		// do not delete read only fields 
		if($row['read_only'] == 1) return;

		$type=$row['type'];

		if($type=="depending") {

			$dep = new depending_fields_config();
			$dep_id=$row['dep_id'];
			$dep->delete($dep_id, $this->type);

		} elseif ($type=="image" || $type=="logo" || $type=="file" || $type=="video" || $type=="audio") {

			// !!!!!!!!!!!!! remove all from the folder

		}

		// request custom type module function
		global $default_fields_types;

		if($type!="depending" && $type!="terms" && in_array($type, $default_fields_types)) {

			$caption=$row['caption'];
			if($this->type=="cf") 
				$db->query('alter table '.TABLE_ADS.' drop `'.$caption.'`');
			else {
				$alt_table=TABLE_USERS;
				if($row['groups']==-1) $alt_table=TABLE_ADS_EXTENSION;

				$db->query('alter table '.$alt_table.' drop `'.$caption.'`');
			}

		} // end if not depending

		if(!in_array($type, $default_fields_types)) {

			global $modules_array;
			if(in_array($type, $modules_array)) {
				$custom_obj = new $type;
				$custom_obj->delete($this->type, $row['caption']);
			}
		}

		$res_del=$db->query('delete from `'.$this->table.'` where id="'.$id.'";');
		$res_del_lang=$db->query('delete from `'.$this->table.'_lang` where id="'.$id.'";');

		if($this->type!='cf') return;

		// check location_fields, search_in_fields, search_location_fields
		// remove this field if found in one of these fields
		if($type!="depending"){
			fields_config::clean_ads_settings_field('location_fields', $caption);
			fields_config::clean_ads_settings_field('search_in_fields', $caption);
			fields_config::clean_ads_settings_field('search_location_fields', $caption);
		}

		$this->clearFieldsCache();

	}

	function remakeOrder($id) {
	
		global $db;
		$order=$db->fetchRow("select `order_no` from `".$this->table."` where id='$id'");
		if(!$order) return;
		$res=$db->query("update `".$this->table."` set `order_no`=`order_no`-1 where `order_no`>='$order'");

		$this->clearFieldsCache();

	}

	function Enable($id) {
	
		global $config_demo;
		if($config_demo==1) return;

		global $db;
		$db->query("update `".$this->table."` set `active` = 1 where id=$id");
		$type = $db->fetchRow("select `type` from `".$this->table."` where id=$id");
		if($type=="price") {

			global $config_abs_path;
			require_once $config_abs_path."/classes/config/settings_config.php";
			settings_config::setEnablePrice(1);

		}
		elseif ($type=="username") {

			global $config_abs_path;
			require_once $config_abs_path."/classes/config/settings_config.php";
			settings_config::setEnableUsername(1);

		}

		$this->clearFieldsCache();
		return 1;
		
	}
	
	function Disable($id) {
	
		global $config_demo;
		if($config_demo==1) return;

		global $db;
		$db->query("update `".$this->table."` set `active` = 0 where id=$id");
		$type = $db->fetchRow("select `type` from `".$this->table."` where id=$id");
		if($type=="price") {

			global $config_abs_path;
			require_once $config_abs_path."/classes/config/settings_config.php";
			settings_config::setEnablePrice(0);

		}
		elseif ($type=="username") {

			global $config_abs_path;
			require_once $config_abs_path."/classes/config/settings_config.php";
			settings_config::setEnableUsername(0);

		}
		$this->clearFieldsCache();
		return 1;
		
	}

	function getFieldLang($id='') {

		global $db;
		if(!$id) $id=$this->id;

		$row=$db->fetchAssoc("select * from `".$this->table."` where id=".$id."");
		$array_lang=$db->fetchAssocList("select * from `".$this->table."_lang` where id=".$id."");

		foreach($array_lang as $f_lang) {

			$lang_id = $f_lang['lang_id'];
			foreach ($f_lang as $key=>$value) {
				if($key=='id' || $key=='lang_id') continue;
				$row[$key][$lang_id] = cleanStr($value);

			}

			if(trim($row['elements'][$lang_id])) {
				$row['elements'][$lang_id] = trim($row['elements'][$lang_id]);
				$row['elements'][$lang_id] = str_replace('|', "\n",$row['elements'][$lang_id]);
			}

			if(isset($row['search_elements']) && trim($row['search_elements'][$lang_id])) {
				$row['search_elements'][$lang_id] = trim($row['search_elements'][$lang_id]);
				$row['search_elements'][$lang_id] = str_replace('|', "\n",$row['search_elements'][$lang_id]);
			}

		}

		// numeric format
		if($row['is_numeric'] && $row['extensions']) { 
			$arr_ext=explode("|",$row['extensions']);
			$row['format_decimals']=$arr_ext[0];
			$row['format_point']=$arr_ext[1];
			$row['format_separator']=$arr_ext[2];
		}

		// depending
		if($row['type']=='depending') {
			$dep = new depending_fields_config();
			$depending = $dep->getDependingFieldLang($row['dep_id']);
			$row['depending'] = $depending;
		}

		if($row['type']=="google_maps") {
			$row['extensions_array'] = explode(",", $row['extensions']);
		}

		return $row;

	}

	function getReadOnly($id='') {
		
		global $db;
		if(!$id) $id=$this->id;
		$type=$db->fetchRow('select read_only from `'.$this->table.'` where id="'.$id.'"');
		return $type;

	}

	// uf only, admin side
	function getSpecificFields() {
		
		global $db;
		if(!$id) $id=$this->id;

		$array=$db->fetchAssocList('select * from `'.$this->table.'` LEFT JOIN `'.$this->table.'_lang` on `'.$this->table.'`.`id` = `'.$this->table.'_lang`.`id` where lang_id = "'.$crt_lang.'" and groups!="0" order by `order_no`');



		$i=0;
		foreach($array as $row) {	
			// clean slashes
			foreach($row as $key=>$value) $arr[$i][$key] = cleanStr($row[$key]);

			$arr[$i]['elements_array']=explode("|",trim($row['elements']));
			for($a=0; $a<count($arr[$i]['elements_array']);$a++) {
				$arr[$i]['elements_array'][$a]=trim($arr[$i]['elements_array'][$a]);
			}

			$i++;
		}
		return $arr;
	}


	// uf only
	function getGroups($id='') {

		global $db;
		if(!$id) $id=$this->id;
		$groups=$db->fetchRow("select groups from `".$this->table."` where id='$id'");
		if(!$groups) return 0;
		if($groups==-1) {
			global $lng;
			$array_groups[0] = $lng['custom_fields']['not_logged_in'];
			return $array_groups;
		}
		$arr=explode(",",trim($groups));
		$no=count($arr);

		$array_groups=array();
		for($i=0;$i<$no;$i++) {
			$group_name=cleanStr(groups::getName($arr[$i]));
			$array_groups[$i]=$group_name;
		}
		return $array_groups;
	}

	function count() {
		
		global $db;
		$result=$db->fetchRow('select count(*) from `'.$this->table.'`');
		return $result;

	}

	function getNo() {
		
		global $db;
		$result=$db->fetchRow('select count(*) from `'.$this->table.'`');
		return $result;

	}
	

	function caption_exists($str, $tab='') {

		global $db;

		$array_reserved = array("name", "type", "amount", "groups", "show", "order", "order_way", "category", "category_id", "credits", "active", "moderator", "sections", "affiliate", "affiliate_id", "location");
		if(in_array($str, $array_reserved)) return 1;

		if($tab) $table = $tab; else { 
			if($this->type=="cf") $table = TABLE_ADS;
			else { 
				$table = TABLE_USERS;
			}
		}

		$tb_fields = $db->getTableFields($table);
		if(in_array($str, $tb_fields)) return 1;

		if($this->not_logged_in) {

			$table = TABLE_ADS_EXTENSION;
			$tb_fields = $db->getTableFields($table);
			if(in_array($str, $tb_fields)) return 1;

			// user details are on the same form as listing details
			$table = TABLE_ADS;
			$tb_fields = $db->getTableFields($table);
			if(in_array($str, $tb_fields)) return 1;

		}

		return 0;
	}

	function make_caption($name, $type, $exclude = array()) {


		$caption = characters_map($name, 1);
		$caption = strtolower($caption);                              // Convert to lowercase
		$caption = preg_replace('/[^a-zA-Z0-9\s]/', '', $caption);  // Remove all punctuation
		$caption = preg_replace("/ +/", " ", $caption);               // Remove multiple spaces
		$caption = str_replace (" ","_", $caption);
		$old_caption=$caption;
		$i=1;
		while($this->caption_exists(strtolower($caption)) || in_array($caption, $exclude)) {
			$caption=$old_caption.$i;
			$i++;
		}

		// if image or file, check the other fields table
		// the folder name must not be the same!
		if($type=="image" || $type=="logo" || $type=="file" || $type=="video" || $type=="audio") {

			if($this->table==TABLE_USER_FIELDS) $table = TABLE_FIELDS; 
			else $table = TABLE_USER_FIELDS;
			while($this->caption_exists(strtolower($caption), $table)) {
				$caption=$old_caption.$i;
				$i++;
			}
		}
	
		if(!$caption) { 
			for($i=0;$i<5;$i++) $caption .= chr(rand(97, 122));
		}

		return strtolower($caption);
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
	
		return $this->tmp;

	}

	function check_form ($id='', $type='') {

		global $lng;
		$this->error='';
		$this->tmp=array();

		if(!$id) $type=escape($_POST['type']);

		global $languages;
		global $config_demo;

		if($config_demo==1) $this->addError($lng['general']['errors']['demo'].'<br />');

		if(!$id) {

			global $extra_fields_types;
			global $default_fields_types;
			if(!in_array($_POST['type'],$default_fields_types) && !in_array($_POST['type'],$extra_fields_types)) $this->addError($lng['custom_fields']['errors']['invalid_type'].'<br />');

		}

		foreach ($languages as $lang) {

			$lang_id = $lang['id'];
			if(!$_POST['name_'.$lang_id]) { $this->addError($lng['custom_fields']['errors']['name_missing'].'<br />');
				break;
			}
		}

		if($this->type=='uf' && isset($_POST['choose_groups']) && $_POST['choose_groups']=="choose")
		{
			$groups_list='';
			$i=0;
			while (isset($_POST['groups'][$i]) && $goup=$_POST['groups'][$i]){
				if($i) $groups_list.=',';
				$groups_list.=$group;
				$i++;
			}
			if($groups_list=='') $this->addError($lng['custom_fields']['errors']['groups_required'].'<br />');
		}

		if( $type=='depending' ) {
			$dep_fields = new depending_fields_config();
			$dep_fields->check_form ($id);
			if($dep_fields->getError()!='') { 
				$this->addError($dep_fields->getError());
				
			}
			$this->tmp['depending'] = $dep_fields->getTmp();
		} else {

		if(isset($_POST['min']) && $_POST['min'] && !is_numeric($_POST['min']))
				$this->addError($lng['custom_fields']['errors']['invalid_min'].'<br />');
		if(isset($_POST['max']) && $_POST['max'] && !is_numeric($_POST['max']))
				$this->addError($lng['custom_fields']['errors']['invalid_max'].'<br />');

		$array_for_size = array("textbox", "textarea", "htmlarea", "url", "email", "youtube", "date", "multiselect", "phone", "whatsapp", "twitter");

		if(in_array($type, $array_for_size)) { 

			if($_POST['size'] && $type!='textarea' && $type!='htmlarea' && $type!='youtube' && !is_numeric($_POST['size']))  
				$this->addError($lng['custom_fields']['errors']['textbox_invalid_length'].'<br />');
			if($_POST['size'] && ($type=='textarea' || $type=='htmlarea' || $type=='youtube') && !validator::valid_length($_POST['size']))
				$this->addError($lng['custom_fields']['errors']['textarea_invalid_length'].'<br />');
		}

		if($type=='menu' || $type=='radio' || $type=='radio_group' || $type=='checkbox_group' || $type=="multiselect") {
			foreach ($languages as $lang) {
				$lang_id = $lang['id'];
				if(!$_POST['elements_'.$lang_id]) { $this->addError($lng['custom_fields']['errors']['elements_required'].'<br />'); break; }
			}
		}

		if(( $type=='menu' || $type=='radio' || $type=='radio_group' ) && ( isset($_POST['default_textbox']) && $_POST['default_textbox']!='')) {
	
			// for all languages
			foreach ($languages as $lang) {
				$lang_id = $lang['id'];

				$elements=str_replace("\n", "|", $_POST['elements_'.$lang_id]);
				$elements=str_replace("\r", "", $elements);
				$array_el=explode("|",$elements);
				$found=0;
				for($i=0; $i<count($array_el); $i++) {
					if(!strcmp(trim($array_el[$i]),trim($_POST['default_textbox_'.$lang_id]))) { $found=1; break; }
				}
				if(!$found) { $this->addError($lng['custom_fields']['errors']['default_str_in_elements'].'<br />'); break; }
			} // end foreach
		}

		global $settings;
		if($type=="google_maps" && (!isset($settings['google_maps_api_key']) || !$settings['google_maps_api_key']))
			$this->addError($lng['settings']['errors']['google_maps_api_key_missing'].'<br />'); 
		
		$array_validation = array ( "url" , "email" , "file" , "image", "logo" , "date", "youtube", "twitter", "video", "audio" );

		if(((isset($_POST['required']) && $_POST['required']=='on' && $type!="username" && $type!="password" && $type!="user_email") || ( isset($_POST['validation_type']) && $_POST['validation_type']) || (isset($_POST['min']) && $_POST['min'] ) || ( isset($_POST['max']) && $_POST['max'] ) || in_array($type, $array_validation) || checkbox_value("is_numeric")) ) {
			// for all languages
			foreach ($languages as $lang) {

				$lang_id = $lang['id'];
 				if($_POST['error_message_'.$lang_id]=='') { $this->addError($lng['custom_fields']['errors']['error_message_required'].'<br />');
				break;
				}

			} // end foreach
		} // end if
		
		if(isset($_POST['unique']) && $_POST['unique']=='on') {
			// for all languages
			foreach ($languages as $lang) {

				$lang_id = $lang['id'];
 				if($_POST['error_message2_'.$lang_id]=='') { $this->addError($lng['custom_fields']['errors']['unique_error_message_required'].'<br />');
				break;
				}

			} // end foreach
		
		}
		

		if($type=='file' && ( $_POST['extensions']!='' && !validator::valid_comma_separated($_POST['extensions']))) {
			$this->addError($lng['custom_fields']['errors']['invalid_extensions_list'].'<br />');
		}
		
		if(($type=='image' || $type=='logo' || $type=='file' || $type=='video' || $type=='audio') && !$_POST['max_uploaded_size']) {
			$this->addError($lng['custom_fields']['errors']['max_uploaded_size_missing'].'<br />');
		}

		if($type=='date') {
			// for all languages
			foreach ($languages as $lang) {

				$lang_id = $lang['id'];
				if(!isset($_POST['date_format_'.$lang_id])) $this->addError($lng['custom_fields']['errors']['date_format_required'].'<br />');

			} // end foreach
		}

		if(isset($_POST['image_resize']) && $_POST['image_resize'] && ($type=='image' || $type=='logo') && !validator::valid_length($_POST['image_resize']))
		$this->addError($lng['custom_fields']['errors']['invalid_image_resize'].'<br />');

		if(isset($_POST['type']) && $_POST['type']=='date' && isset($_POST['default_textbox']) && !validator::valid_date($_POST['default_textbox'])) {
			$this->addError($lng['custom_fields']['errors']['invalid_default_date'].'<br />');
		}
		} // end if not depending

		if($this->getError()!='') {

			if($id!='') { 
				$this->tmp['id']=$id;
				$this->tmp['type']=$type;
			}
			else { 

				if(isset($_POST['type'])) $this->tmp['type']=$_POST['type']; else $this->tmp['type']='';

			} // end else !$id

			// for all languages
			foreach ($languages as $lang) {
				$lang_id = $lang['id'];
				if(isset($_POST['name_'.$lang_id])) $this->tmp['name'][$lang_id]=cleanStr($_POST['name_'.$lang_id]); else $this->tmp['name'][$lang_id]='';
			}

			if($this->type=='cf') {

				if(isset($_POST['all_fieldsets'])) $this->tmp['all_fieldsets']=$_POST['all_fieldsets'];
				if(isset($_POST['all_fieldsets']) && $_POST['all_fieldsets']=="0")
				{
					$this->tmp['fieldset']='';
					$i=0;
					while (isset($_POST['fieldset'][$i]) && $f=escape($_POST['fieldset'][$i])){
						if($i) $this->tmp['fieldset'].=',';
						$this->tmp['fieldset'].=$f;
						$i++;
					}
				} else $this->tmp['fieldset']='0';

			}

			$f_array = array("default_checkbox", "validation_type", "min", "max", "size", "max_uploaded_size", "extensions", "image_resize");

			foreach ($f_array as $f) {
				if(isset($_POST[$f])) $this->tmp[$f]=cleanStr($_POST[$f]); else $this->tmp[$f]='';
			}

			$f_array_lang = array("error_message", "error_message2", "info_message", "default_textarea", "default_textbox", "elements", "prefix", "postfix", "date_format", "top_str");

			foreach ($f_array_lang as $f) {
				// for all languages
				foreach ($languages as $lang) {
					$lang_id = $lang['id'];
					$fname = $f."_".$lang_id;
					if(isset($_POST[$fname])) $this->tmp[$f][$lang_id]=cleanStr($_POST[$fname]); else $this->tmp[$f][$lang_id]='';
				}
			}

			foreach ($languages as $lang) {

				$lang_id = $lang['id'];
				if($type=="textarea") $this->tmp['default_val'][$lang_id] = $_POST['default_textarea'.$lang_id];
				elseif($type!="checkbox")  $this->tmp['default_val'][$lang_id] = $_POST['default_textbox_'.$lang_id];
				elseif($type!="terms") $this->tmp['default_val'][$lang_id] = $_POST['terms'.$lang_id];

			}

			$c_array = array("is_numeric", "other_val", "all_val", "required", "editable", "active", "unique", "ext1");
			foreach ($c_array as $f) {
				if(isset($_POST[$f]) && $_POST[$f]=='on') $this->tmp[$f]=1; else $this->tmp[$f]=0;
			}

			// public
			if($this->type=='uf') {
				$array_public = array("0", "1", "2");
				if(in_array($_POST['public'], $array_public)) $this->tmp['public']=$_POST['public']; else $this->tmp['public']=1;

			}

			$k = 0;
			while(isset($_POST['auto_locate_fields'][$k]) && $f = $_POST['auto_locate_fields'][$k]) {
				$this->tmp['extensions_array'][$k]=$f;
				$k++;
			}

			// uf only
			if($this->type=='uf'){ 
				if(isset($_POST['choose_group'])) $this->tmp['choose_group']=$_POST['choose_group'];
				if(isset($_POST['choose_group']) && $_POST['choose_group']=="choose")
				{
					$this->tmp['groups']='';
					$i=0;
					while (isset($_POST['groups'][$i]) && $group=escape($_POST['groups'][$i])){
						if($i) $this->tmp['groups'].=',';
						$this->tmp['groups'].=$group;
						$i++;
					}
				} else $this->tmp['groups']='0';
			}
			
			// cf only
			if($this->type=='cf') {
				if(isset($_POST['advanced_search'])  && $_POST['advanced_search']=='on') $this->tmp['advanced_search']=1; else $this->tmp['advanced_search']=0;
				if(isset($_POST['search_type'])) $this->tmp['search_type']=$_POST['search_type']; else $this->tmp['search_type']='';
				if(isset($_POST['quick_search'])  && $_POST['quick_search']=='on') $this->tmp['quick_search']=1; else $this->tmp['quick_search']=0;

				foreach ($languages as $lang) {

					$lang_id = $lang['id'];
					$fname = "search_elements_".$lang_id;
					if(isset($_POST[$fname])) 
						$this->tmp[$fname][$lang_id]=cleanStr($_POST[$fname]); 
					else $this->tmp[$fname][$lang_id]='';

				}
				
			}

			// phone field extra fields
			if($type=="phone" || $type=="whatsapp") {
				
				// use "ext1" field for "international_format"
				$this->tmp['ext1']=checkbox_value("international_format");
				// use "extensions" field for "only_countries"
				if(isset($_POST['only_countries'])) $this->tmp['extensions']=$_POST['only_countries']; else $this->tmp['extensions']='';
				
				if(isset($_POST['only_countries'])) $this->tmp['extensions']=$_POST['only_countries']; else $this->tmp['extensions']='';
				
				$this->tmp['other_val']=checkbox_value("country_autodetect");
				
			}

			if(isset($_POST["format_decimals"]) && is_numeric($_POST["format_decimals"])) $this->tmp['format_decimals'] = $_POST["format_decimals"]; 
			if(isset($_POST["format_point"])) $this->tmp['format_point'] = $_POST["format_point"]; 
			if(isset($_POST["format_separator"])) $this->tmp['format_separator'] = $_POST["format_separator"]; 

		}

		// request custom type module function
		global $default_fields_types;

		if(!in_array($type, $default_fields_types)) {

			global $modules_array;
			if(in_array($type, $modules_array)) {
				$custom_obj = new $type;
				if(!$id) $id=0;
				if(!$custom_obj->check_form($id)) $this->addError($custom_obj->getError());
			}
		}

		return 1;
	}

	function add() {
	
		global $db;
		global $languages;
		// request custom type module function
		global $default_fields_types;

		$this->clean=array();
		$this->check_form();
		if($this->getError()!='') return 0;

		$this->clean['type'] = escape($_POST['type']);
		if($this->type=='cf') { 

			if(isset($_POST['all_fieldsets']) && $_POST['all_fieldsets']=="0")
			{
				$this->clean['fieldset']='';
				$i=0;
				while (isset($_POST['fieldset'][$i]) && $f=escape($_POST['fieldset'][$i])){
					if($i) $this->clean['fieldset'].=',';
					$this->clean['fieldset'].=$f;
					$i++;
				}
			} else $this->clean['fieldset']='0';

		}

		// for all languages
		foreach ($languages as $lang) {

			$lang_id = $lang['id'];
			$this->clean['name'][$lang_id] = escape($_POST['name_'.$lang_id]);
		}

		$this->clean['required'] = '';
		$this->clean['error_message'] = array();
		$this->clean['error_message2'] = array();
		$this->clean['info_message'] = array();
		$this->clean['default_val'] = array();
		$this->clean['elements'] = array();
		$this->clean['top_str'] = array();
		$this->clean['postfix'] = array();
		$this->clean['prefix'] = array();
		$this->clean['date_format'] = array();
		$this->clean['unique'] = 0;
		$this->clean['ext1'] = 0;

		global $crt_lang;

		// check if not logged in field 
		$this->not_logged_in = checkbox_value("not_logged_in");

		$this->clean['caption']=$this->make_caption($this->clean['name'][$crt_lang], $this->clean['type']);

		if($this->clean['type']!='checkbox' && $this->clean['type']!='depending') { // all but checkbox and depending
	
			// for all languages
			foreach ($languages as $lang) {
				$lang_id = $lang['id'];
				$this->clean['error_message'][$lang_id]=escape($_POST['error_message_'.$lang_id]);
			}
			
			if($this->clean['type']=="terms") $this->clean['required']=1;
			else $this->clean['required']=checkbox_value('required');

		}

		if($this->clean['type']=='checkbox') { // checkbox only
	
			// for all languages
			foreach ($languages as $lang) {
				$lang_id = $lang['id'];
				$this->clean['error_message'][$lang_id]='';
			}
			$this->clean['required']='';
			$this->clean['default_val']=escape($_POST['default_checkbox']);
		}

		$this->clean['is_numeric']=checkbox_value('is_numeric');
		$this->clean['editable']=checkbox_value('editable');
		$this->clean['ext1']=checkbox_value('international_format');

		if(in_array($this->clean['type'], array("textbox", "phone", "whatsapp", "email", "url"))) { // all but checkbox and depending

			$this->clean['unique']=checkbox_value('unique');
			if($this->clean['unique']==1) {
			
			// for all languages
			foreach ($languages as $lang) {
				$lang_id = $lang['id'];
				$this->clean['error_message2'][$lang_id]=escape($_POST['error_message2_'.$lang_id]);
			}
			
			}

		}

		// public
		if($this->type=='uf') {
			$array_public = array("0", "1", "2");
			if(in_array($_POST['public'], $array_public)) $this->clean['public']=$_POST['public']; else $this->clean['public']=1;

		}


		if(isset($_POST['validation_type'])) $this->clean['validation_type']=escape($_POST['validation_type']); else $this->clean['validation_type']='';
		if(isset($_POST['min'])) $this->clean['min']=escape($_POST['min']); else $this->clean['min']='0';
		if(isset($_POST['max'])) $this->clean['max']=escape($_POST['max']); else $this->clean['max']='0';
		if(isset($_POST['size'])) $this->clean['size']=escape($_POST['size']); else $this->clean['size']='20';

		if( ($this->clean['type']=="file" || $this->clean['type']=="image" || $this->clean['type']=="video" || $this->clean['type']=="audio") && isset($_POST['max_uploaded_size'])) $this->clean['max_uploaded_size']=escape($_POST['max_uploaded_size']); else $this->clean['max_uploaded_size']='0';

		if($this->clean['type']=="file" && isset($_POST['extensions'])) $this->clean['extensions']=escape($_POST['extensions']); else $this->clean['extensions']='';

		if(($this->clean['type']=="image" || $this->clean['type']=="logo") && isset($_POST['image_resize'])) $this->clean['image_resize']=escape($_POST['image_resize']); else $this->clean['image_resize']='';

		$array_default_textbox = array("textbox", "menu", "radio", "radio_group", "url", "email", "date", "price");

		// for all languages
		foreach ($languages as $lang) {

			$lang_id = $lang['id'];
			if(isset($_POST['elements_'.$lang_id])) {

				$this->clean['elements'][$lang_id]=trim($_POST['elements_'.$lang_id]);
				if($this->clean['elements'][$lang_id]) $this->clean['elements'][$lang_id]=str_replace("\n", "|", $this->clean['elements'][$lang_id]);
				if($this->clean['elements'][$lang_id]) { 
					$this->clean['elements'][$lang_id]=str_replace("\r", "", $this->clean['elements'][$lang_id]);
					$this->clean['elements'][$lang_id] = clean($this->clean['elements'][$lang_id]);
				}
				// strip multiple signs
				$this->clean['elements'][$lang_id] = preg_replace("/(\|){2,}/",'$1',$this->clean['elements'][$lang_id]);
				$this->clean['elements'][$lang_id] = escape($this->clean['elements'][$lang_id]);

			} else $this->clean['elements'][$lang_id]='';

			if(isset($_POST['top_str_'.$lang_id])) $this->clean['top_str'][$lang_id]=escape($_POST['top_str_'.$lang_id]);
			else $this->clean['top_str'][$lang_id]='';

			if(isset($_POST['info_message_'.$lang_id])) $this->clean['info_message'][$lang_id]=escape($_POST['info_message_'.$lang_id]);
			else $this->clean['info_message'][$lang_id]='';

			if(in_array($this->clean['type'], $array_default_textbox)) {

				if(isset($_POST['default_textbox_'.$lang['id']])) $this->clean['default_val'][$lang_id]=escape($_POST['default_textbox_'.$lang_id]); 
				else $this->clean['default_val'][$lang_id]='';
			
			} else if($this->clean['type']=='textarea' || $this->clean['type']=='htmlarea') {
	
				if(isset($_POST['default_textarea_'.$lang['id']])) $this->clean['default_val'][$lang_id]=escape($_POST['default_textarea_'.$lang_id]); else $this->clean['default_val'][$lang_id]='';
	
			} else if($this->clean['type']=='google_maps') {
				$this->clean['default_val'][$lang_id]='';
			} else if($this->clean['type']=='terms') {
				if(isset($_POST['terms_'.$lang['id']])) $this->clean['default_val'][$lang_id]=escape($_POST['terms_'.$lang_id]); else $this->clean['default_val'][$lang_id]='';
			}

			if(isset($_POST['prefix_'.$lang_id])) $this->clean['prefix'][$lang_id]=escape($_POST['prefix_'.$lang_id]); else $this->clean['prefix'][$lang_id]='';
			if(isset($_POST['postfix_'.$lang_id])) $this->clean['postfix'][$lang_id]=escape($_POST['postfix_'.$lang_id]); else $this->clean['postfix'][$lang_id]='';

			if($this->clean['type']=='date' && isset($_POST['date_format_'.$lang_id])) $this->clean['date_format'][$lang_id]=escape($_POST['date_format_'.$lang_id]); else $this->clean['date_format'][$lang_id]='';

		} //end for all languages 

		if($this->type=='cf') {

			$this->clean['advanced_search']=0;
			$this->clean['quick_search']=0;
			$this->clean['search_type']=0;

			$array_search = array("textbox", "textarea", "htmlarea", "menu", "radio", "radio_group", "url", "email", "depending", "price", "date", "checkbox", "phone", "whatsapp");

			if(in_array($this->clean['type'], $array_search) || !in_array($this->clean['type'], $default_fields_types)) {

				$this->clean['advanced_search']=checkbox_value('advanced_search');
				$this->clean['quick_search']=checkbox_value('quick_search');

				if($_POST['search_type']==1 || $_POST['search_type']==2 || $_POST['search_type']==3 ) {
	
					$this->clean['search_type']=$_POST['search_type'];
					if(!$this->clean['is_numeric'] && $this->clean['search_type']==2 && $this->clean['type']!="date") $this->clean['search_type'] = 1;

				} else $this->clean['search_type']=0;

				// keywords search only for htmlarea or textarea
				if($this->clean['advanced_search']==1 && ( $this->clean['type']=='textarea' || $this->clean['type']=='htmlarea' )) {

					$this->clean['search_type']=3;

				}

			} // end if(in_array($this->clean['type'], $array_search) || !in_array($this->clean['type'], $default_fields_types))

			foreach($languages as $lang) {
			$lang_id = $lang['id'];
			if(isset($_POST['search_elements_'.$lang_id])) {

				$this->clean['search_elements'][$lang_id] = trim($_POST['search_elements_'.$lang_id]);
				if($this->clean['search_elements'][$lang_id]) {
					$this->clean['search_elements'][$lang_id]=str_replace("\n", "|", $this->clean['search_elements'][$lang_id]);
					$this->clean['search_elements'][$lang_id]=str_replace("\r", "", $this->clean['search_elements'][$lang_id]);
				}
				// strip multiple signs
				$this->clean['search_elements'][$lang_id] = preg_replace("/(\|){2,}/",'$1',$this->clean['search_elements'][$lang_id]);

				$this->clean['search_elements'][$lang_id] = escape( $this->clean['search_elements'][$lang_id]);

			} else $this->clean['search_elements'][$lang_id]='';
			}

		}

		if($this->type=='uf') {

			if(isset($_POST['choose_group']) && $_POST['choose_group']=="choose")
			{
				$this->clean['groups']='';
				$i=0;
				while (isset($_POST['groups'][$i]) && $group=escape($_POST['groups'][$i])){
					if($i) $this->clean['groups'].=',';
					$this->clean['groups'].=$group;
					$i++;
				}
			} else $this->clean['groups']='0';

			// field for not logged in users
			if($this->not_logged_in) $this->clean['groups']='-1';

		} // end if uf

		$this->clean['order_no']=$this->getOrder();
		$fieldset = new fieldsets();

		if($this->clean['type']=="depending") {

			$dep_fields = new depending_fields_config();
			$dep_fields->setNologin($this->not_logged_in);
			$this->clean['dep_id'] = $dep_fields->add($this->type);
			
		} else $this->clean['dep_id']=0;

		// other_val
		if($this->clean['type']=="depending" || $this->clean['type']=="menu") {
			$this->clean['other_val']=checkbox_value('other_val');
			$this->clean['all_val']=checkbox_value('all_val');
		}
		else { 
			$this->clean['other_val'] = 0;
			$this->clean['all_val'] = 0;
		}

		// normal custom fields
		if(in_array($this->clean['type'], $default_fields_types)) {

		if($this->type=='cf') { 
			$fields_array = array("fieldset", "caption", "type", "order_no", "is_numeric", "validation_type", "size", "min", "max", "required", "editable", "advanced_search", "quick_search", "search_type", "max_uploaded_size", "extensions", "image_resize", "dep_id", "other_val", "all_val", "unique", "ext1");
			$fields_array_lang = array("name", "error_message", "error_message2", "info_message", "default_val", "prefix", "postfix", "elements", "search_elements", "date_format", "top_str");

		}
		else { 
			$fields_array = array("caption", "type", "order_no", "is_numeric", "validation_type", "size", "min", "max", "required", "editable", "max_uploaded_size", "extensions", "image_resize", "dep_id", "groups", "other_val", "all_val", "public", "unique", "ext1");
			$fields_array_lang = array("name", "error_message", "error_message2", "info_message", "default_val", "prefix", "postfix", "elements", "date_format", "top_str");
		}

		//auto_locate_fields
		if($this->clean['type']=="google_maps") {

			$k=0;
			$this->clean['extensions'] = '';
			while(isset($_POST['auto_locate_fields'][$k]) && $f = $_POST['auto_locate_fields'][$k]) {
				if($k) $this->clean['extensions'].=',';
				$this->clean['extensions'].=$f;
				$k++;
			}
		}

		// numeric format
		if($this->clean['is_numeric']) {

			if(isset($_POST["format_decimals"]) && is_numeric($_POST["format_decimals"]))
				$decimals = $_POST["format_decimals"]; 
			else $decimals = 0;
			$point = escape($_POST["format_point"]);
			$separator = escape($_POST["format_separator"]);
			
			$this->clean['extensions']=$decimals."|".$point."|".$separator;

		}

		// phone field extra fields
		if($this->clean['type']=="phone" || $this->clean['type']=="whatsapp") {
				
			// use "ext1" field for "international_format"
			$this->clean['ext1']=checkbox_value("international_format");
					
			// use "extensions" field for "only_countries"
			if(isset($_POST['only_countries'])) $this->clean['extensions'] = escape($_POST['only_countries']); else $this->clean['extensions']='';
			
			$this->clean['other_val']=checkbox_value("country_autodetect");
				
		}
		
		$sql = "insert into `".$this->table."` SET ";
		$i=0;
		foreach ($fields_array as $f) {
			if(!$this->clean["$f"] && ($f=="min" || $f=="max" || $f=="max_uploaded_size" || $f=="dep_id" || $f=="required" || $f=="is_numeric")) continue;
			if($i) $sql.=", ";
			$sql.=" `$f` = '".$this->clean["$f"]."'";
			$i++;
		}
		$sql .= ";";

		$res=$db->query($sql);
		$id=$db->insertId();

		// for all languages
		foreach ( $languages as $lang) {

			$lang_id = $lang['id'];
			$sql_lang = "insert into `".$this->table."_lang` SET `id` = $id, `lang_id`='$lang_id' ";
			foreach ($fields_array_lang as $f) {

				if(isset($this->clean[$f][$lang_id])) $sql_lang.=", `$f` = '".$this->clean[$f][$lang_id]."'";

			}
			$sql_lang .= ";";
			$res=$db->query($sql_lang);
		}

		if($this->clean['type']!="depending" && $this->clean['type']!="terms" ) {

			if($this->type=='cf') $alt_table = TABLE_ADS;
			else { 
				$alt_table=TABLE_USERS;
				if($this->clean['groups']==-1) $alt_table=TABLE_ADS_EXTENSION;
			}

			$datatype = $this->generateDbFieldType($this->clean['type'], $this->clean['is_numeric']);

			$res_alter=$db->query("alter table `$alt_table` add `".$this->clean['caption']."` ".$datatype);
			
			// add index
			/*if($this->type=="cf") {
				$array_noindex = array('textarea', 'htmlarea', 'depending', 'youtube', 'google_maps', 'terms');
				if(!in_array($this->clean['type'], $array_noindex)) {
					global $config_table_prefix;
					$db->query("ALTER TABLE `".$config_table_prefix."ads` ADD INDEX ( `{$this->clean['caption']}` )");
				}
			}*/

			// if user field and public is selected as user choice
			if($this->type=='uf' && $this->clean['public']==2) {

				// check if does not exist first
				$field_name = "pb_".$this->clean['caption'];
				$tb_fields = $db->getTableFields($alt_table);

				if(!in_array($field_name, $tb_fields)) 
	 				$res_alter2=$db->query("alter table `$alt_table` add `".$field_name."` tinyint(1) default 1");

			}

		
		}

		if($this->clean['type']=="image" || $this->clean['type']=="logo" || $this->clean['type']=="file" || $this->clean['type']=="video" || $this->clean['type']=="audio") {

			global $config_abs_path;
			$path = $config_abs_path."/uploads/".$this->clean['caption'];

			$cgi = isCGI();
			if($cgi) $mode = 0755;
			else $mode = 0777;

			$res = @mkdir($path, $mode);
			@chmod($path, $mode);
			// check if the folder is still not writeable and display notice !!!

		}

		} else {

			$custom_obj = new $this->clean['type'];
			$custom_obj->add($this->type, $this->table, $this->clean);

		}

		$this->clearFieldsCache();

		return 1;

	}

	function edit($id) {
	
		global $db;
		global $languages;

		$this->clean=array();
		$f = new fields($this->type);
		$type=$f->getType($id);
		$ro=$this->getReadOnly($id);

		$this->check_form($id, $type);
		if($this->getError()!='') return 0;
		$this->clean['validation_type']='';
		if($type!="depending") $this->clean['dep_id']='0';

		$old_data = $db->fetchAssoc("select * from `".$this->table."` where id=$id");
		$this->not_logged_in = 0;
		if($this->type=="uf") {
			if($old_data['groups']==-1) $this->not_logged_in = 1;
		}

		foreach ($languages as $lang) {

			$lang_id = $lang['id'];
			$this->clean['prefix'][$lang_id]='';
			$this->clean['postfix'][$lang_id]='';
			$this->clean['elements'][$lang_id]='';
			$this->clean['name'][$lang_id]=escape($_POST['name_'.$lang_id]);

			if($type!='checkbox' && $type!='depending') { // all but checkbox and depending
	
				if(isset($_POST['error_message_'.$lang_id])) $this->clean['error_message'][$lang_id]=escape($_POST['error_message_'.$lang_id]); else $this->clean['error_message'][$lang_id]='';	

			} else if($type=='checkbox') { // checkbox only

				$this->clean['error_message'][$lang_id]='';

			}

			if(isset($_POST['info_message_'.$lang_id])) $this->clean['info_message'][$lang_id]=escape($_POST['info_message_'.$lang_id]); else $this->clean['info_message'][$lang_id]='';

			if($type=='menu' || $type=='radio' || $type=='radio_group' || $type=='checkbox_group' || $type=='multiselect') {

				$this->clean['elements'][$lang_id]=trim($_POST['elements_'.$lang_id]); // do not escape
				if($this->clean['elements'][$lang_id]) {
					$this->clean['elements'][$lang_id] = str_replace("\n", "|", $this->clean['elements'][$lang_id]);
					$this->clean['elements'][$lang_id] = str_replace("\r", "", $this->clean['elements'][$lang_id]);
					$this->clean['elements'][$lang_id] = escape($this->clean['elements'][$lang_id]);
				}
				// strip multiple commas
				$this->clean['elements'][$lang_id] = preg_replace("/(\|){2,}/",'$1',$this->clean['elements'][$lang_id]);

			}

			if(isset($_POST['prefix_'.$lang_id])) $this->clean['prefix'][$lang_id]=escape($_POST['prefix_'.$lang_id]); else $this->clean['prefix'][$lang_id]='';

			if(isset($_POST['postfix_'.$lang_id])) $this->clean['postfix'][$lang_id]=escape($_POST['postfix_'.$lang_id]); else $this->clean['postfix'][$lang_id]='';

			if(isset($_POST['top_str_'.$lang_id])) $this->clean['top_str'][$lang_id]=escape($_POST['top_str_'.$lang_id]); else $this->clean['top_str'][$lang_id]='';

			$this->clean['date_format'][$lang_id]='';
			if($type=='date') {

				if(isset($_POST['date_format_'.$lang_id])) $this->clean['date_format'][$lang_id]=escape($_POST['date_format_'.$lang_id]);

			}

			$array_default_textbox = array("textbox", "menu", "radio", "radio_group", "url", "email", "date", "price");
			if(in_array($type, $array_default_textbox)) {

				if(isset($_POST['default_textbox_'.$lang_id])) $this->clean['default_val'][$lang_id]=escape($_POST['default_textbox_'.$lang_id]); else $this->clean['default_val'][$lang_id]='';

			} else if($type=='textarea' || $type=='htmlarea') {
	
				if(isset($_POST['default_textarea_'.$lang_id])) $this->clean['default_val'][$lang_id]=escape($_POST['default_textarea_'.$lang_id]); else $this->clean['default_val'][$lang_id]='';
	
			} else if($type=='terms') {
				if(isset($_POST['terms_'.$lang['id']])) $this->clean['default_val'][$lang_id]=escape($_POST['terms_'.$lang_id]); else $this->clean['default_val'][$lang_id]='';
			}
		}

		if($this->type=='cf') {

			if(isset($_POST['all_fieldsets']) && $_POST['all_fieldsets']=="0")
			{
				$this->clean['fieldset']='';
				$i=0;
				while (isset($_POST['fieldset'][$i]) && $fs=escape($_POST['fieldset'][$i])){
					if($i) $this->clean['fieldset'].=',';
					$this->clean['fieldset'].=$fs;
					$i++;
				}
			} else $this->clean['fieldset']='0';
		}

		if($type!='checkbox' && $type!='depending') { // all but checkbox and depending
	
			if($type=="terms") $this->clean['required']=1;
			else $this->clean['required']=checkbox_value('required');


		}  else if($type=='checkbox') { // checkbox only
	
			$this->clean['required']='';
			if(isset($_POST['default_checkbox'])) $this->clean['default_val']=escape($_POST['default_checkbox']); else $this->clean['default_val']='';
		}

		if($type=='textbox' || $type=='phone' || $type=="whatsapp") {

			if(isset($_POST['validation_type'])) $this->clean['validation_type']=escape($_POST['validation_type']);

		}

		if($type=="price") $this->clean['validation_type']="price";

		if($type=='textbox' || $type=='phone' || $type=="whatsapp" || $type=='textarea' || $type=='htmlarea' || $type=="multiselect" || $type=="checkbox_group") { // ????????
			if(isset($_POST['min'])) $this->clean['min']=escape($_POST['min']);
			if(isset($_POST['max'])) $this->clean['max']=escape($_POST['max']);
		} else {
			$this->clean['min']='0';
			$this->clean['max']='0';
		}

		//$array_for_size = array("textbox", "textarea", "htmlarea", "url", "email", "youtube", "date");
	
		if(isset($_POST['size'])) $this->clean['size']=escape($_POST['size']);
		else $this->clean['size']='20';

		$this->clean['is_numeric']=checkbox_value('is_numeric');
		$this->clean['editable']=checkbox_value('editable');
		$this->clean['ext1']=checkbox_value('international_format');
		
		if(in_array($type, array("textbox", "phone", "whatsapp", "email", "url"))) { // all but checkbox and depending

			$this->clean['unique']=checkbox_value('unique');
			if($this->clean['unique']==1) {
			
			// for all languages
			foreach ($languages as $lang) {
				$lang_id = $lang['id'];
				$this->clean['error_message2'][$lang_id]=escape($_POST['error_message2_'.$lang_id]);
			}
			
			}

		}

		$this->clean['active']=checkbox_value('active');

		if($this->type=='uf' && isset($_POST['public'])) {

			$array_public = array("0", "1", "2");
			if(in_array($_POST['public'], $array_public)) $this->clean['public']=$_POST['public']; else $this->clean['public']=1;

		}

		if($this->type=='cf') {

			$this->clean['advanced_search']=checkbox_value('advanced_search');
			$this->clean['quick_search']=checkbox_value('quick_search');

			if($_POST['search_type']==1 || $_POST['search_type']==2 || $_POST['search_type']==3 ) {

				$this->clean['search_type']=$_POST['search_type'];
				if(!$this->clean['is_numeric'] && $this->clean['search_type']==2 && $type!="date") $this->clean['search_type'] = 1;

			} else $this->clean['search_type']=0;

			// keywords search only for htmlarea or textarea
			if($this->clean['advanced_search']==1 && ( $type=='textarea' || $type=='htmlarea' )) {

				$this->clean['search_type']=3;

			}

			foreach($languages as $lang) {
			$lang_id = $lang['id'];
			if(isset($_POST['search_elements_'.$lang_id])) {
				$this->clean['search_elements'][$lang_id]=trim($_POST['search_elements_'.$lang_id]); // do not escape
				if($this->clean['search_elements'][$lang_id]) $this->clean['search_elements'][$lang_id]=str_replace("\n", "|", $this->clean['search_elements'][$lang_id]);
				if($this->clean['search_elements'][$lang_id]) $this->clean['search_elements'][$lang_id]=str_replace("\r", "", $this->clean['search_elements'][$lang_id]);
				// strip multiple commas
				$this->clean['search_elements'][$lang_id] = preg_replace("/(\|){2,}/",'$1',$this->clean['search_elements'][$lang_id]);

				$this->clean['search_elements'][$lang_id] = escape($this->clean['search_elements'][$lang_id]);

			} else $this->clean['search_elements'][$lang_id] = '';

			}
		
		}

		if($type=='depending') {
			$dep = new depending_fields_config();
			$dep_id = $f->getDepId($id);
			$dep->setNologin($this->not_logged_in);
			$dep->edit($dep_id, $this->type);
		}

		$this->clean['max_uploaded_size']='0';
		if($type=="file" || $type=="image" || $type=="logo" || $type=="video" || $type=="audio") { 
			if(isset($_POST['max_uploaded_size'])) $this->clean['max_uploaded_size']=escape($_POST['max_uploaded_size']); else $this->clean['max_uploaded_size']='0';
		}
		if( $type=="file" && isset($_POST['extensions'])) $this->clean['extensions']=escape($_POST['extensions']); else $this->clean['extensions']='';

		if( ($type=="image" || $type=="logo") && isset($_POST['image_resize'])) $this->clean['image_resize']=escape($_POST['image_resize']); else $this->clean['image_resize']='';

		if($this->type=="uf") {

			//if($old_data['groups']>0) {

			if(isset($_POST['choose_group']) && $_POST['choose_group']=="choose")
			{	
				$this->clean['groups']='';
				$i=0;
				while (isset($_POST['groups'][$i])){
					$group=escape($_POST['groups'][$i]);
					if($i) $this->clean['groups'].=',';
					$this->clean['groups'].=$group;
					$i++;
				}
			} else $this->clean['groups']='0';
		
			//} 
			// if not logged in users field, you cannot change the groups
			//else
			if($old_data['groups']==-1) $this->clean['groups']='-1';
		}

		// other_val
		if($type=="depending" || $type=="menu") {
			$this->clean['other_val']=checkbox_value('other_val');
			$this->clean['all_val']=checkbox_value('all_val');
		}
		else { 
			$this->clean['other_val'] = 0;
			$this->clean['all_val'] = 0;
		}

		//auto_locate_fields
		if($type=="google_maps") {
			$k=0;
			$this->clean['extensions'] = '';
			while(isset($_POST['auto_locate_fields'][$k]) && $f = $_POST['auto_locate_fields'][$k]) {
				if($k) $this->clean['extensions'].=',';
				$this->clean['extensions'].=$f;
				$k++;
			}
		}

		// numeric format
		if($this->clean['is_numeric']) {

			if(isset($_POST["format_decimals"]) && is_numeric($_POST["format_decimals"]))
				$decimals = $_POST["format_decimals"]; 
			else $decimals = 0;
			$point = escape($_POST["format_point"]);
			$separator = escape($_POST["format_separator"]);
			$this->clean['extensions']=$decimals."|".$point."|".$separator;

		}

		// phone field extra fields
		if( $type=="phone" || $type=="whatsapp") {
				
			// use "ext1" field for "international_format"
			$this->clean['ext1']=checkbox_value("international_format");
					
			// use "extensions" field for "only_countries"
			if(isset($_POST['only_countries'])) $this->clean['extensions'] = escape($_POST['only_countries']); else $this->clean['extensions']='';
				
			$this->clean['other_val']=checkbox_value("country_autodetect");
		}

		// email or password double verification
		if( $type=="password" || $type=="user_email") {
				
			// use "ext1" field for "international_format"
			$this->clean['ext1']=checkbox_value("double_verification");
				
		}

		if($this->type=='cf') { 

			$fields_array = array("fieldset", "validation_type", "size", "min", "max", "required", "editable", "advanced_search", "quick_search", "search_type", "max_uploaded_size", "extensions", "image_resize", "dep_id", "other_val", "all_val", "is_numeric", "active", "unique", "ext1");

			$fields_array_lang = array("name", "error_message", "error_message2", "info_message", "default_val", "prefix", "postfix", "elements", "date_format", "top_str", "search_elements");

		}
		else { 
			$fields_array = array("validation_type", "size", "min", "max", "required", "editable", "public" , "max_uploaded_size", "extensions", "image_resize", "dep_id", "groups", "other_val", "all_val", "is_numeric", "active", "unique", "ext1");

			$fields_array_lang = array("name", "error_message", "error_message2", "info_message", "default_val", "prefix", "postfix", "elements", "date_format", "top_str");
		}

		$sql = "update `".$this->table."` SET ";
		$i=0;
		foreach ($fields_array as $f) {
			if( (!isset($this->clean["$f"]) || !$this->clean["$f"]) && ($f=="min" || $f=="max" || $f=="max_uploaded_size" || $f=="dep_id")) continue;
			if(isset($this->clean[$f])) {
				if($i) $sql.=", ";
				$sql.=" `$f` = '".$this->clean[$f]."'";
				$i++;
			}
		}
		$sql .= " where `id`='$id';";
//echo $sql;
		$res=$db->query($sql);

		// for each language
		foreach ($languages as $lang) {

			$lang_id = $lang['id'];
			$sql_lang = "update `".$this->table."_lang` SET ";
			$i=0;
			foreach ($fields_array_lang as $f) {
				if(isset($this->clean[$f][$lang_id])) {
					if($i) $sql_lang.=", ";
					$sql_lang.=" `$f` = '".$this->clean[$f][$lang_id]."'";
					$i++;
				}
			}
			$sql_lang .= " where `id`='$id' and `lang_id`='$lang_id';";

			$res_lang=$db->query($sql_lang);

		} // end foreach language

		// check if must change from numeric or to numeric
		if(($old_data['is_numeric'] && !$this->clean['is_numeric']) || (!$old_data['is_numeric'] && $this->clean['is_numeric'])) {

			if($this->type=='cf') $alt_table = TABLE_ADS;
			else { 
				$alt_table=TABLE_USERS;
				if($this->clean['groups']=="-1") $alt_table=TABLE_ADS_EXTENSION;
			}

			$datatype = $this->generateDbFieldType($old_data['type'], $this->clean['is_numeric']);

			$res_alter=$db->query("alter table `$alt_table` change `{$old_data['caption']}` `".$old_data['caption']."` ".$datatype);

		}

		// if user field and public is selected as user choice
		if($this->type=='uf' && $this->clean['public']==2 && $old_data['public']!=2) {

			if($this->type=='cf') $alt_table = TABLE_ADS;
			else { 
				$alt_table=TABLE_USERS;
				if($this->clean['groups']=="-1") $alt_table=TABLE_ADS_EXTENSION;
			}

			// check if does not exist first
			$field_name = "pb_".$old_data['caption'];
			$tb_fields = $db->getTableFields($alt_table);

			if(!in_array($field_name, $tb_fields)) 
 				$res_alter2=$db->query("alter table `$alt_table` add `".$field_name."` tinyint(1) default 1");

		}

		$this->clearFieldsCache();

		return 1;

	}


	function generateDbFieldType($type, $is_numeric) {

		if($type=='checkbox') return "int(1) default null";

		if (($type=="textbox" || $type=="menu") && $is_numeric) return "float default null";

		if($type=='textarea' || $type=='htmlarea' || $type=='checkbox_group' || $type=='youtube' || $type=='multiselect') return "text null";

		if($type=='radio' || $type=='radio_group' || $type=='menu' || $type=='file' || $type=='image' || $type=='logo') return "varchar(128) default null";

		if($type=='google_maps') return "varchar(64) default null";

		if($type=='date') return "date default null";

		return "varchar(255) default null";

	}

	function getOrder() {

		global $db;
		$res_order=$db->query('select order_no from `'.$this->table.'` order by order_no desc limit 1');
		if($db->numRows($res_order)>0) $order_no=($db->fetchRow()+1);
		else $order_no=1;
		return $order_no;
	}

	function moveUp($order_no, $fset = '') {

		global $config_demo;
		if($config_demo==1) return;
		
		global $db;
		$id=$db->fetchRow("select `id` from `".$this->table."` where `order_no`='$order_no'");
		if(!$id) return 0;

		if(!$fset) {
			$goto=$order_no-1;
			if($goto<1) return 0;
		} else {

			$goto = $db->fetchRow("select `order_no` from `".$this->table."` where ( fieldset REGEXP '\[\[:<:\]\]$fset\[\[:>:\]\]'  or fieldset = 0 ) and `order_no` < $order_no order by order_no desc limit 1");
			if(!$goto) return 0;

		}

		$dest=$db->fetchAssoc("select `id`, `order_no` from `".$this->table."` where `order_no`='$goto'");
		if(!$dest) {
			$res3=$db->query("update `".$this->table."` set `order_no`='$goto' where `id`='$id'");
			return $goto*(-1); // returneaza - (minus) goto
		}

		$res2=$db->query("update `".$this->table."` set `order_no`='$order_no' where `id`=".$dest['id']);
		$res3=$db->query("update `".$this->table."` set `order_no`='$goto' where `id`='$id'");

		$this->clearFieldsCache();

		return $goto;
	}

	function moveDown($order_no, $fset = '') {
		
		global $config_demo;
		if($config_demo==1) return;

		global $db;
		$id=$db->fetchRow("select `id` from `".$this->table."` where `order_no`='$order_no'");
		if(!$id) return 0;

		if(!$fset) {
			$goto=$order_no+1;
		} else {

			$goto = $db->fetchRow("select `order_no` from `".$this->table."` where ( fieldset REGEXP '\[\[:<:\]\]$fset\[\[:>:\]\]'  or fieldset = 0 ) and `order_no` > $order_no order by order_no asc limit 1");
			if(!$goto) return 0;

		}

		$dest=$db->fetchAssoc("select `id`, `order_no` from `".$this->table."` where `order_no`='$goto'");
		if(!$dest) {
			$res3=$db->query("update `".$this->table."` set `order_no`='$goto' where `id`='$id'");
			return $goto*(-1);
		}

		$res2=$db->query("update `".$this->table."` set `order_no`='$order_no' where `id`=".$dest['id']);
		$res3=$db->query("update `".$this->table."` set `order_no`='$goto' where `id`='$id'");

		$this->clearFieldsCache();

		return $goto;
	}

	function getAllForm() {

		global $db;
		global $crt_lang;
		$arr=array();
		$arr=$db->fetchAssocList("select `".$this->table."_lang`.`id`, `name` from `".$this->table."` LEFT JOIN `".$this->table."_lang` on `".$this->table."`.`id` = `".$this->table."_lang`.`id` where `lang_id`='$crt_lang' order by `order_no`");

		$i = 0;
		foreach ($arr as $row) {
			$arr[$i]['name'] = cleanHtml($arr[$i]['name']);
			$i++;
		}

		return $arr;
	}




	function reArrange() {

		global $db;
		$arr = $db->fetchRowList("select id from `".$this->table."` order by `order_no`");
		$i=1;
		foreach($arr as $id) {
			$db->query("update `".$this->table."` set `order_no` = '$i' where id=$id");
			$i++;
		}
		$this->clearFieldsCache();
	}

		function getFields($fset = '') {

		global $db;
		global $crt_lang;
		global $settings;

		if($this->type=="cf") $f = "fieldset"; else $f = "groups";
		if($fset) { 
			if($fset>0)
				$where_str = " and (`$f` REGEXP '\[\[:<:\]\]$fset\[\[:>:\]\]' or `$f` = 0)"; 
			elseif($fset==-1) $where_str = " and `$f` = -1 ";
		}
		else $where_str="";
		if(!$fset && $this->type=="uf" && $settings['nologin_enabled']==0) $where_str = " and `$f` != -1 ";

		$array=$db->fetchAssocList("select * from `".$this->table."` LEFT JOIN `".$this->table."_lang` on `".$this->table."`.`id` = `".$this->table."_lang`.`id` where `lang_id` = '$crt_lang'".$where_str." order by `order_no`");

		$i=0;
		global $lng;
		$array_fields=array();
		$dep = new depending_fields();
		foreach($array as $result) {

			// clean slashes
			foreach($result as $key=>$value) $array_fields[$i][$key] = cleanStr($result[$key]);
	
			// make the description
			$desc="<div class=\"overlay-top-bg\">".$array_fields[$i]['name']."</div><table class=\"datatable\" style=\"width: 600px;\">";

			// type
			$desc.="<tr><td class=\"raligned\">".$lng['custom_fields']['type']."</td><td class=\"laligned\">".$lng['custom_fields'][$array_fields[$i]['type']]."</td></tr>";

			// required
			if($array_fields[$i]['type']!='checkbox' && $array_fields[$i]['type']!='depending' && $array_fields[$i]['type']!='password') {
				$desc.="<tr><td class=\"raligned\">".$lng['custom_fields']['required']."</td><td class=\"laligned\">";	
				if($array_fields[$i]['required']==1) 
					$desc.="<span class=\"sc2\">".$lng['general']['yes']."</span>"; 
				else 
					$desc.="<span class=\"sc1\">".$lng['general']['no']."</span>"; 
				$desc.="</td></tr>";
			}

			// editable
			if($array_fields[$i]['type']!='password') {
				$desc.="<tr><td class=\"raligned\">".$lng['custom_fields']['editable']."</td><td class=\"laligned\">";	
				if($array_fields[$i]['editable']==1) 
					$desc.="<span class=\"sc1\">".$lng['general']['yes']."</span>"; 
				else 
					$desc.="<span class=\"sc2\">".$lng['general']['no']."</span>"; 
				$desc.="</td></tr>";
			}

			// read only
			$desc.="<tr><td class=\"raligned\">".$lng['custom_fields']['read_only']."</td><td class=\"laligned\">";	
			if($array_fields[$i]['read_only']==1) 
				$desc.="<span class=\"sc2\">".$lng['general']['yes']."</span>"; 
			else 
				$desc.="<span class=\"sc1\">".$lng['general']['no']."</span>"; 
			$desc.="</td></tr>";

			// unique
			if(in_array($array_fields[$i]['type'], array("textbox", "email", "phone", "whatsapp", "url"))) {
				$desc.="<tr><td class=\"raligned\">".$lng['custom_fields']['unique']."</td><td class=\"laligned\">";	
				if($array_fields[$i]['unique']==1) 
					$desc.="<span class=\"sc2\">".$lng['general']['yes']."</span>"; 
				else 
					$desc.="<span class=\"sc1\">".$lng['general']['no']."</span>"; 
				$desc.="</td></tr>";
			}

			// active
			$desc.="<tr><td class=\"raligned\">".$lng['general']['active']."</td><td class=\"laligned\">";	
			if($array_fields[$i]['active']==1) 
				$desc.="<span class=\"sc1\">".$lng['general']['yes']."</span>"; 
			else 
				$desc.="<span class=\"sc2\">".$lng['general']['no']."</span>"; 
			$desc.="</td></tr>";

			// public
			if($this->type=="uf") {
				$desc.="<tr><td class=\"raligned\">".$lng['custom_fields']['public']."</td><td class=\"laligned\">";	
				if($array_fields[$i]['public']==1) 
					$desc.="<span class=\"sc1\">".$lng['general']['yes']."</span>"; 
				else if($array_fields[$i]['public']==0) 
					$desc.="<span class=\"sc2\">".$lng['general']['no']."</span>"; 
				else $desc.="<span class=\"sc1\">".$lng['custom_fields']['user_choice']."</span>"; 
				$desc.="</td></tr>";

				// groups
				if($array_fields[$i]['groups']==0) {
					$desc.="<tr><td class=\"raligned\">".$lng['custom_fields']['groups_list']."</td><td class=\"laligned\">".$lng['custom_fields']['all_groups']."</td></tr>";
				} else {
					$gr_array = $this->getGroups($array_fields[$i]['id']);
					$gr_str = implode(", ", $gr_array);
					$desc.="<tr><td class=\"raligned\">".$lng['custom_fields']['groups_list']."</td><td class=\"laligned\">$gr_str</td></tr>";
				}
			}

			// groups
			if($this->type=="uf" && $array_fields[$i]['groups']==-1) {
//!!!!!!!!!
				//$desc.=" <b>".$lng['packages']['not_logged_in']."</b> ";
			}

			// validation type
			if($array_fields[$i]['type']=='textbox' || $array_fields[$i]['type']=='phone' || $array_fields[$i]['type']=="whatsapp") { 
				$desc.="<tr><td class=\"raligned\">".$lng['custom_fields']['validation_type']."</td><td class=\"laligned\">";	
				if($array_fields[$i]['validation_type']=='') 
					$desc.=$lng['general']['none']; 
				else 
					$desc.=$array_fields[$i]['validation_type']; 
				$desc.="</td></tr>";
			}

			// size
			$array_for_size = array("textbox", "textarea", "htmlarea", "url", "email", "youtube", "date", "phone", "whatsapp");
			if(in_array($array_fields[$i]['type'], $array_for_size) && $array_fields[$i]['size']) { 
				$desc.="<tr><td class=\"raligned\">".$lng['custom_fields']['length']."</td><td class=\"laligned\">".$array_fields[$i]['size']."</td></tr>";	
			}

			// min - max values
			$array_for_min = array("textbox", "phone", "whatsapp", "textarea", "htmlarea");
			if(in_array($array_fields[$i]['type'], $array_for_min)) { 

				if($array_fields[$i]['min']) $desc.="<tr><td class=\"raligned\">".$lng['custom_fields']['minimum']."</td><td class=\"laligned\">".$array_fields[$i]['min']."</td></tr>";	

				if($array_fields[$i]['max']) $desc.="<tr><td class=\"raligned\">".$lng['custom_fields']['maximum']."</td><td class=\"laligned\">".$array_fields[$i]['max']."</td></tr>";	

			}

			// default value
			if($array_fields[$i]['type']!='checkbox' && $array_fields[$i]['type']!='terms' && $array_fields[$i]['default_val'])
				$desc.="<tr><td class=\"raligned\">".$lng['custom_fields']['default_val']."</td><td class=\"laligned\">".$array_fields[$i]['default_val']."</td></tr>";	

			if($array_fields[$i]['type']=='checkbox' && $array_fields[$i]['default_val']==0) 
				$desc.="<tr><td class=\"raligned\">".$lng['custom_fields']['default_val']."</td><td class=\"laligned\">".$lng['general']['checked']."</td></tr>";	
			else if($array_fields[$i]['type']=='checkbox' && $array_fields[$i]['default_val']==1) 
				$desc.="<tr><td class=\"raligned\">".$lng['custom_fields']['default_val']."</td><td class=\"laligned\">".$lng['general']['unchecked']."</td></tr>";	

			// prefix, postfix
			$array_for_prefix = array("textbox", "menu", "radio", "url", "email", "date", "phone", "whatsapp");

			if(in_array( $array_fields[$i]['type'], $array_for_prefix)) {

				if($array_fields[$i]['prefix']) $desc.="<tr><td class=\"raligned\">".$lng['custom_fields']['prefix']."</td><td class=\"laligned\">".$array_fields[$i]['prefix']."</td></tr>";	

				if($array_fields[$i]['postfix']) $desc.="<tr><td class=\"raligned\">".$lng['custom_fields']['postfix']."</td><td class=\"laligned\">".$array_fields[$i]['postfix']."</td></tr>";	

			}

			// search
			if($this->type=='cf') {
				$desc.="<tr><td class=\"raligned\">".$lng['custom_fields']['advanced_search']."</td><td class=\"laligned\">";	
				if($array_fields[$i]['advanced_search']==1) 
					$desc.="<span class=\"sc1\">".$lng['general']['yes']."</span>"; 
				else 
					$desc.="<span class=\"sc2\">".$lng['general']['no']."</span>"; 
				$desc.="</td></tr>";

				$desc.="<tr><td class=\"raligned\">".$lng['custom_fields']['quick_search']."</td><td class=\"laligned\">";	
				if($array_fields[$i]['quick_search']==1) 
					$desc.="<span class=\"sc1\">".$lng['general']['yes']."</span>"; 
				else 
					$desc.="<span class=\"sc2\">".$lng['general']['no']."</span>"; 
				$desc.="</td></tr>";

				$desc.="<tr><td class=\"raligned\">".$lng['custom_fields']['search_type']."</td><td class=\"laligned\">";	
				if($array_fields[$i]['search_type']==2) 
					$desc.=$lng['custom_fields']['interval']; 
				else if($array_fields[$i]['search_type']==3)
					$desc.=$lng['custom_fields']['search_keyword'];
				else $desc.=$lng['custom_fields']['exact_match'];
				$desc.="</td></tr>";

			}

			// error
			if($array_fields[$i]['error_message']) 
				$desc.="<tr><td class=\"raligned\">".$lng['custom_fields']['error_message']."</td><td class=\"err laligned\">".$array_fields[$i]['error_message']."</td></tr>";	

			// error2
			if($array_fields[$i]['unique']==1 && $array_fields[$i]['error_message2']) 
				$desc.="<tr><td class=\"raligned\">".$lng['custom_fields']['unique_error_message']."</td><td class=\"err laligned\">".$array_fields[$i]['error_message2']."</td></tr>";	
	
			// info
			if($array_fields[$i]['info_message']) 
				$desc.="<tr><td class=\"raligned\">".$lng['custom_fields']['info_message']."</td><td class=\"inf laligned\">".$array_fields[$i]['info_message']."</td></tr>";	

			// elements
			if($array_fields[$i]['elements']) { 
				if(strlen($array_fields[$i]['elements'])>500) $elements = substr($array_fields[$i]['elements'], 0, 500).' ... ';
				else $elements = $array_fields[$i]['elements'];
    	    			$elements=str_replace("|", ", ", $elements);

				$desc.="<tr><td class=\"raligned\">".$lng['custom_fields']['elements']."</td><td class=\"laligned\">".$elements."</td></tr>";	
			}

			// max uploaded size
			$array_files = array("file", "image", "logo", "video", "audio");
			if(in_array($array_fields[$i]['type'], $array_files) && $array_fields[$i]['max_uploaded_size']) {
				$desc.="<tr><td class=\"raligned\">".$lng['custom_fields']['max_uploaded_size']."</td><td class=\"laligned\">".$array_fields[$i]['max_uploaded_size']."MB</td></tr>";	
			}

			// extensions
			if($array_fields[$i]['type']=="file" && $array_fields[$i]['extensions']) {
				$desc.="<tr><td class=\"raligned\">".$lng['custom_fields']['extensions']."</td><td class=\"laligned\">".$array_fields[$i]['extensions']."</td></tr>";	
			}

			// resize image
			if(($array_fields[$i]['type']=="image" || $array_fields[$i]['type']=="logo") && $array_fields[$i]['image_resize']) {
				$desc.="<tr><td class=\"raligned\">".$lng['custom_fields']['image_resize']."</td><td class=\"laligned\">".$array_fields[$i]['image_resize']."</td></tr>";	
			}

			// date_format
			if($array_fields[$i]['type']=="date" && $array_fields[$i]['date_format']) {
				$desc.="<tr><td class=\"raligned\">".$lng['custom_fields']['date_format']."</td><td class=\"laligned\">".$array_fields[$i]['date_format']."</td></tr>";	
			}

			// phone
			if($array_fields[$i]['type']=="phone" || $array_fields[$i]['type']=="whatsapp") {
				
				$desc.="<tr><td class=\"raligned\">".$lng['custom_fields']['international_format']."</td><td class=\"laligned\">";	
				if($array_fields[$i]['ext1']==1) 
					$desc.="<span class=\"sc1\">".$lng['general']['yes']."</span>"; 
				else
					$desc.="<span class=\"sc2\">".$lng['general']['no']."</span>"; 
				$desc.="</td></tr>";

			}

			$desc.="</table><br/>";
			$array_fields[$i]['description']=$desc;
			
			if($this->type=='cf' && $array_fields[$i]['fieldset']) {
				$fieldsets_arr = explode(",",$array_fields[$i]['fieldset']);
				$j=0;
				$array_fields[$i]['fieldset_name'] = '';
				foreach($fieldsets_arr as $fset) {
					if(!$fset) continue;
					if($j) $array_fields[$i]['fieldset_name'].=', ';
					$array_fields[$i]['fieldset_name'].=fieldsets::getName($fset);
					$j++;
				}
			}
			$array_fields[$i]['last'] = 0;
			
			if($array_fields[$i]['type']=="depending") $array_fields[$i]['depending'] = $dep->getDependingField($array_fields[$i]['dep_id']);
			$i++;
		}

		if($i) $array_fields[$i-1]['last'] = 1;

		return $array_fields;
	}

	static function clean_ads_settings_field($ads_settings_field_name, $caption){

		global $ads_settings, $db;
		$l_array = explode(",", $ads_settings[$ads_settings_field_name]);
		if(in_array($caption, $l_array)) {

			// remove the field from the array
			$l_new = "";
			$i = 0;
			foreach ($l_array as $l) {
				$l = trim($l);
				if($l==$caption) continue;
				if($i) $l_new.=",";
				$l_new.=$l;
				$i++;
			}
			// save the new array
			$db->query("update ".TABLE_ADS_SETTINGS." set `$ads_settings_field_name` = '$l_new'");

			// clear cache
			$lc_cache = new cache();
			$lc_cache->clearCache("base_settings");

			$ads_settings = settings::getAdsSettings();
		}
	}

	function setNologin($n) {

		$this->not_logged_in = $n;

	}

	function getNologin($n) {

		return $this->not_logged_in;

	}

	function clearFieldsCache() {

		// clear cache
		$lc_cache = new cache();
		$lc_cache->clearCache("base_interface");
		$lc_cache->clearCache("base_refine_fields");
		$lc_cache->clearCache("base_refine_fields_list");
		$lc_cache->clearCache("base_search_fields");
		$lc_cache->clearCache("base_listing_fields");
		$lc_cache->clearCache("base_user_fields");
		$lc_cache->clearCache("gmap_fields");
		$lc_cache->clearCache("base_short_listing_fields");
		$lc_cache->clearCache("base_short_user_fields");
		$lc_cache->clearCache("special_user_fields");
		$lc_cache->clearCache("special_listing_fields");
		

	}

}
?>
