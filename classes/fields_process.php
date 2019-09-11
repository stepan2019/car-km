<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class fields_process {

	var $edit;
	var $error;
	var $info;
	var $tmp;
	var $obj_id;

	public function __construct($type='cf')
	{
	
		$this->type = $type;
		if($type=='uf') { $this->table = TABLE_USER_FIELDS; $this->type='uf'; } else { $this->table=TABLE_FIELDS; $this->type='cf'; }
		$this->edit = 0;
		$this->tmp = array();
		$this->error = array();
		$this->obj_id = 0;

	}

	function setEdit($val) {

		$this->edit = $val;

	}

	function setId($val) {

		$this->obj_id = $val;

	}

	function getError() {
	
		return $this->error;

	}

	function addError($err_field, $err_text) {

		array_push($this->error, array("field"=> $err_field, "error" => $err_text));

	}

	function getTmp() {
	
		return $this->tmp;

	}

	function check_form_fields($set) {

		// request custom type module function
		global $default_fields_types;

		$this->tmp=array();
//		$f = new fields($this->type);
		if($this->type=="cf") { $cn = "base_listing_fields"; $extra="fieldset"; }
		else { $cn = "base_user_fields"; $extra="group"; }
		$fields=common::getCachedObject($cn, array("$extra" => $set));

		$arr_users_nocheck = array();

		global $is_admin, $is_mod;

		// do not check terms and conditions field if administrator or in edit mode
		// terms are only shown at registration
		if($is_admin || $this->edit) { array_push($arr_users_nocheck, "terms"); array_push($arr_users_nocheck, "password"); }

		$fields_arr = array();
	
		$no_fields=count($fields);
		for ($i=0; $i<$no_fields; $i++)	{

			if(in_array($fields[$i]['type'], $default_fields_types)) {

			if( $this->type=="cf" ) array_push($fields_arr, $fields[$i]['caption']);
	
			if($this->edit && !$fields[$i]['editable'] && !$is_admin && !$is_mod) continue;

			if( ($this->type=="cf") || ($this->type=="uf" && !in_array($fields[$i]['type'], $arr_users_nocheck))) $this->validate_field($fields[$i], $set);
			}
			else { // module type

				global $modules_array;
				if(in_array($fields[$i]['type'], $modules_array)) {
				$custom_obj = new $fields[$i]['type'];

				$ok = $custom_obj->check_form_fields($fields[$i], $this->tmp);
				if(!$ok) $this->addError("$fields[$i]['caption']", $fields[$i]['error_message']);
				}
			}
		}

		// fields rules
		if($this->type=="cf") {
		    global $config_abs_path;
		    require_once $config_abs_path."/classes/rules.php";
		    $rs = new rules();
		    $rs->verifyRules($fields_arr);
		    $err_arr = $rs->getFieldError();
		    if($err_arr) $this->addError($err_arr[0]['field'], $err_arr[0]['error']);
		}
		return 1;
	}

	function validate_field($field, $fieldset) {

		global $db;
		global $is_admin, $is_mod;
		$caption=$field['caption'];
		$type=$field['type'];
		$error=$field['error_message'];
		$error2=$field['error_message2'];
		$validation_type=$field['validation_type'];
		$min=$field['min'];
		$max=$field['max'];
		$required=$field['required'];
		$unique=$field['unique'];
		$editable=$field['editable'];
		$extensions = $field['extensions'];
		$max_uploaded_size = $field['max_uploaded_size']*1000;
		$image_resize = $field['image_resize'];
		$date_format = $field['date_format'];

		if($field['is_numeric'] && !$validation_type) $validation_type = "numeric";

		$set = 0; $val='';
		$double_validation=0;
		
		switch ($type) {

			case "depending":

					$no_fields = $field['depending']['no'];
					for($nf=1; $nf<=$no_fields;$nf++) {

						$caption=$field['depending']['caption'.$nf];
						$required=$field['depending']['required'.$nf];
						if($required==1 && (!isset($_POST[$caption]) || !$_POST[$caption] ) ) { $err=1; 	$this->addError($caption, $field['depending']['error_message'.$nf]);}

						if(isset($_POST[$caption])) { 

							if($field['other_val'] && $_POST[$caption]=="-1")
								$this->tmp[$caption] = cleanStr($_POST[$caption."_other_val"]); 
							else $this->tmp[$caption] = cleanStr($_POST[$caption]);

						}

					}
					$this->tmp['dep_id'] = $field['depending']['id'];
					$set = 1;

					return; // don't run the rest of the code
					break;

			case "checkbox":
					$val = checkbox_value($caption);
					$set = 1;
					break;

			case "checkbox_group":

					// keep tmp values
					$elements = explode("|", trim($field['elements']));
					$val = array();
					$k = 0; $n=0;
					foreach ($elements as $el) {
						$el = trim($el);
						$check_caption = $caption."_".$n;
						if(isset($_POST[$check_caption]) && $_POST[$check_caption]=='on') { $val[$k]=$el; $k++; }
						$n++;
					}
					$set = 1;
					break;

			case "multiselect":
					// keep tmp values
					$val = array();
					$k = 0;
					while(isset($_POST[$caption][$k]) && $f = $_POST[$caption][$k]) {
						$val[$k]=$f;
						$k++;
					}
					$set = 1;
					break;

			case "file":
					if(isset($_FILES[$caption]['name'])) $val = $_FILES[$caption]['name']; else $val='';
					$validation_type = 'file';
					$set = 1;
					break;

			case "image":
			case "logo":
					if(isset($_FILES[$caption]['name'])) $val = $_FILES[$caption]['name']; else $val='';
					$validation_type = 'image';
					$set = 1;
					break;

			case "video":
					if(isset($_FILES[$caption]['name'])) $val = $_FILES[$caption]['name']; else $val='';
					$validation_type = 'video';
					$set = 1;
					break;

			case "audio":
					if(isset($_FILES[$caption]['name'])) $val = $_FILES[$caption]['name']; else $val='';
					$validation_type = 'audio';
					$set = 1;
					break;
					
			case "youtube":
					$validation_type = 'youtube';
					if(isset($_POST[$caption])) $val=cleanStr($_POST[$caption]); else $val='';
					$set = 1;
					break;

			case "twitter":
					$validation_type = 'twitter';
					if(isset($_POST[$caption])) $val=cleanStr($_POST[$caption]); else $val='';
					$set = 1;
					break;

			case "google_maps":
					$validation_type = 'google_maps';
					if(isset($_POST[$caption])) $val=cleanStr($_POST[$caption]); else $val='';
					$set = 1;
					break;

			case "menu":
					if($field['other_val']) {

						if(isset($_POST[$caption])) {
							if($_POST[$caption]=="-1") $val=cleanStr($_POST[$caption."_other_val"]); 
							else $val=cleanStr($_POST[$caption]);
						} else $val='';
					} else $val=cleanStr($_POST[$caption]);
					$set = 1;
					break;

			case "date":
					if(isset($_POST[$caption."_vis"])) $this->tmp['vis'][$caption] = $_POST[$caption."_vis"]; else $this->tmp['vis'][$caption] = '';
					$set = 1;
					break;
			case "password":
			case "user_email":
					if(isset($_POST[$caption])) $val=cleanStr($_POST[$caption]); else $val='';
					if($field['ext1'] && !$is_admin && !$is_mod && !$this->edit) {
						if(isset($_POST[$caption."_repeat"])) $val_repeat=cleanStr($_POST[$caption."_repeat"]); else $val_repeat='';
						$double_validation=1;
					}
					$set = 1;
					break;

			default:

				break;

		} // end switch


		if($type=='email' || $type=='user_email') $validation_type = 'email';
		if($type=='url') $validation_type = 'url';
		if($type=='price') $validation_type = 'price';

		if(!$set) {
			if(isset($_POST[$caption])) $val=cleanStr($_POST[$caption]); else $val='';
		}

		// remember tmp value even if there is no error, it might be a listing error
		$this->tmp[$caption]=$val;

		$err=0;
		if(!$val) {

			if($required==1 && $type!="file" && $type!="image" && $type!="logo" && $type!="video" && $type!="audio") {
				$err=1;
				
				global $ads_settings;
				if( $type=="price" && $ads_settings['enable_auctions'] && isset($_POST['enable_auction']) && $_POST['enable_auction']=="on") {  $required = 0; $err = 0; }
				
				if($type=="price" && $required) {
					do_action("check_price_field", array($field, $fieldset, &$err));
				}
				
			}

			if($required==1 && ($type=="file" || $type=="image" || $type=="logo" || $type=="video" || $type=="audio")) {

				if(!$this->edit) $err = 1;
				else {
				// check if a value exists for that field !!!

				}

			}

		} else {

			$set = 0;
			if($validation_type!='' && $val) {
				switch ($validation_type) {

				case "alpha":  
					if(!validator::valid_alpha($val)) 	$err=1;
				break;

				case "alphanum": 
					if(!validator::valid_alphanum($val)) $err=1;
				break;

				case "digit": 
					if(!validator::valid_digit($val)) $err=1;
				break;

				case "numeric": 
					if(!validator::valid_numeric($val)) $err=1;
				break;

				case "price": 
					if(!validator::valid_price($val)) $err=1;
				break;

				case "email":  
					if(!validator::valid_email($val)) $err=1;
					if($caption=="mgm_email") {
						global $config_abs_path;
						require_once $config_abs_path."/classes/blocked_emails.php";
						if(blocked_emails::isBlocked(escape($val))) {

							global $lng;
							$this->addError($caption, $lng['users']['errors']['email_not_permitted']);

						}
					}
				break;

				case "url": 
					if(!validator::valid_url($val)) $err=1;
				break;

				case "file": 

					if(!$this->valid_file($caption, $extensions, $max_uploaded_size)) $err=1;
				break;

				case "image": 
				case "logo": 

					global $config_abs_path;
					require_once $config_abs_path."/classes/images.php";
					$dir = $config_abs_path."/uploads/".$caption;
					$img = new image($caption, $dir, "fields");
					$img->setMaxSize($max_uploaded_size);
					
					if(!$img->verify()) { $err=1; $error.=" : ".$img->getError(); }// !!! warn when folder not writeable!

				break;
				
				case "video": 

					if(!$this->valid_video($caption, $max_uploaded_size)) $err=1;
				break;

				case "audio": 

					if(!$this->valid_audio($caption, $max_uploaded_size)) $err=1;
				break;

				case "youtube": 
					if(!validator::valid_youtube($val)) $err=1;
				break;

				case "twitter": 
					if(!validator::valid_twitter($val)) $err=1;
				break;

				case "google_maps": 
					if(!validator::valid_gmaps($val)) $err=1;
				break;

				default:

					if($validation_type!="" && !validator::valid_pcre($val, $validation_type)) $err=1;

				break;

				} // end switch

			}// end if validation_type && $val

			if($type=='date') { // de completat
				//check if valid format $date_format
			}	

			if($min>0 && $type!="multiselect" && $type!="checkbox_group") {
				if(strlen($val)<$min) $err=1; 
			}
			if($max>0) { 
				// if multiselect or checkbox_group, check if at most max elements are selected
				if($type=="multiselect" || $type=="checkbox_group") {
					if(count($val)>$max) $err = 1;
				}
				else 
					if(strlen($val)>$max) $err=1; 
			}
			
		} // if val not null

		if($min>0 && ($type=="multiselect" || $type=="checkbox_group")) {

			if(count($val)<$min) $err = 1;
			
		}

		if($err && $error) $this->addError($caption, $error);

		// get hidden international number
		if(($type=="phone" || $type=="whatsapp") && $field['ext1']==1) { $val=escape($_POST[$caption."_hidden"]);}
					
		// check for blocked phones
		if($type=="phone") {
		
		global $config_abs_path;
		require_once $config_abs_path."/classes/blocked_phones.php";
		
		if(blocked_phones::isBlocked($val)) {

			global $lng;
			$this->addError($caption, $lng['users']['errors']['phone_not_permitted']);

		}
		} // end check for blocked phones

					
		// add unique errors if the case
		if($unique && !$this->validateUniqueField($caption, $val)) {

			$this->addError($caption, $error2);

		}

		if($double_validation) {
			if(!isset($_POST[$caption."_repeat"]) || !$_POST[$caption."_repeat"] || $_POST[$caption."_repeat"]!=$val) {
				global $lng;
				if($type=="password")
					$this->addError($caption."_repeat", $lng['users']['errors']['passwords_dont_match']);	
				else
					$this->addError($caption."_repeat", $lng['users']['errors']['emails_dont_match']);
			}
		}

	}

	function valid_file($caption, $extensions, $max_size) {

		global $config_abs_path;
		global $lng;
		$dir = $config_abs_path."/uploads/".$caption;

		// if no file uploaded
		if(!isset($_FILES[$caption])) return 0;

		// problems with the folder

		if(!is_dir($dir) || !is_writeable($dir)) { $this->addError($caption, $lng['images']['errors']['folder_not_writeable']); return 0; }

		$extension = getExtension($_FILES[$caption]['name']);

		// system blocked extensions
		$blocked_extensions = array("php", "js", "php4", "php5", "pl", "cgi", "rb");
		if(in_array($extension, $blocked_extensions)) return 0;

		// check if allowed extension
		if($extensions) {
			$extensions = trim($extensions,",");
			$extensions_array = explode(",", $extensions);

			if(!in_array($extension,$extensions_array)) return 0;

		}
		// check if file size allowed
		$size=filesize($_FILES[$caption]['tmp_name']);

		if($size>($max_size*1000)) return 0;

		return 1;

	}

	function add_fields($set, $array_format=0) {

		// $array_format = 1 -> pending edited

		global $db;
		global $default_fields_types;
		
		//$this->check_form_fields($set);
		//if($this->getError()) return 0;

		if(!$array_format) $sql="";
		else $return_array =  array();

		//$f = new fields($this->type);
		if($this->type=="cf") { $cn = "base_listing_fields"; $extra="fieldset"; }
		else { $cn = "base_user_fields"; $extra="group"; }
		$fields=common::getCachedObject($cn, array("$extra" => $set));
		$no_fields=count($fields);

		global $is_admin, $is_mod;

		$public_email = 0;
		for ($i=0; $i<$no_fields; $i++)	{

			// test here fieldset belonging because of the price
			if($this->type=="cf") {
			$fieldset = $fields[$i]['fieldset'];
			if($fieldset!=0 && $fields[$i]['type']!="price") {
				$fset_array = explode(",", $fieldset);
				if(!in_array($set, $fset_array)) continue;
			}	
			}


			$field_val="";
			$field_type=$fields[$i]['type'];

			if($field_type=="terms" || $field_type=="password") continue;

			$validation = $fields[$i]['validation_type'];
			if($this->type=="cf" && $field_type=='price') $validation="price";
			if($this->type=="uf" && ( $field_type=='username')) continue;

			if($this->type=="uf" && $field_type=='user_email' && $set!=-1) { 
				$public_email = $fields[$i]["public"];
				continue;
			}
			
			
			// modules types
			if(!in_array($fields[$i]['type'], $default_fields_types)) {

				global $modules_array;
				if(in_array($field_type, $modules_array)) {
					$custom_obj = new $field_type;
					$sql_mod = $custom_obj->add_fields();
					$sql.=$sql_mod;
					continue;
				}
			}

			// if edit mode, check if editable
			if( !$is_admin && !$is_mod && $this->edit==1 && $fields[$i]['editable']==0) continue;

			if($field_type=="depending") {

				$no_fs = $fields[$i]['depending']['no'];
				for($nf=1; $nf<=$no_fs; $nf++) {

					$caption = $fields[$i]['depending']['caption'.$nf];
					if(isset($_POST[$caption]) &&  $_POST[$caption]) { 
					
						if($fields[$i]['other_val']==1 && $_POST[$caption]=="-1") {
							$field_val = escape($_POST[$caption."_other_val"]);
						} else $field_val = escape($_POST[$caption]);

					}
					else $field_val='';

					if(!$array_format)  {

						if($field_val) $sql.=", `$caption` = '".$field_val."'";
						else $sql.=", `$caption` = null";

					}
					else {

						$return_array[$caption] = $field_val;

					}
				}

			} else {
			
			$caption=$fields[$i]['caption'];

			if($field_type=="checkbox") { 

				if(isset($_POST[$caption]) && $_POST[$caption]=='on') $field_val=1; else $field_val=0;

			} elseif($field_type=="checkbox_group"){

				$field_val='';

				// add checkboxes names separated by "|" 
				$j = 0;
				foreach ( $fields[$i]['extra_elements_array'] as $element) {
					if(isset($_POST[$element['input_name']]) && $_POST[$element['input_name']]=="on") {
						if($j) $field_val.="|";
						$field_val.=$element['name'];
						$j++;
					}
				}
				$field_val = escape($field_val);

			} elseif($field_type=="multiselect"){

				// add elements separated by "|" 
				$field_val='';
				$k = 0;
				while(isset($_POST[$caption][$k]) && $fk = $_POST[$caption][$k]) {
					if($k) $field_val.="|";
					$field_val.=$fk;
					$k++;
				}
				$field_val = escape($field_val);
			}
			elseif($field_type=="file" || $field_type=="video" || $field_type=="audio") {

				if(isset($_FILES[$caption]['name']) && $_FILES[$caption]['name']) { 

					global $config_abs_path;
					$dir = $config_abs_path."/uploads/".$caption."/";

					$extension = getExtension($_FILES[$caption]['name']);
					// generate new file name
					$newname = $caption.'-'.time().".".$extension;
					move_uploaded_file($_FILES[$caption]['tmp_name'], $dir.$newname);
					$field_val = $newname;
				} else $field_val='';
			}
			elseif($field_type=="image" || $field_type=="logo") { 

				if(isset($_FILES[$caption]['name']) && $_FILES[$caption]['name']) {

					global $config_abs_path;
					$dir = $config_abs_path."/uploads/".$caption;
					$img = new image($caption, $dir, "fields");
					$img->setMaxSize($fields[$i]['max_uploaded_size']);

					$img->setGenerate($caption);
					$img->verify();

					if(!$fields[$i]['image_resize']) {
						if($img->upload()){
							$field_val=$img->getUploadedFile();
						}
					} // if not image resize
					else {
						$fields[$i]['image_resize'] = str_replace("x", "X", $fields[$i]['image_resize']);
						$arr_size = explode("X",$fields[$i]['image_resize']);
						$img->setThmbWidth($arr_size[0]);
						$img->setThmbHeight($arr_size[1]);
						if($img->makeThumb($dir)){
							$field_val=$img->getUploadedFile();
						}
					}
				} else $field_val = '';

			}
			elseif($field_type=="google_maps" && $this->type=="cf"){ // for distance search

				$field_val='';
				if(isset($_POST[$caption]) && $_POST['caption']) {
					$field_val=escape($_POST[$caption]);
					$arr_coord = explode(",", $field_val);
					$lat = $arr_coord[0];
					$long = $arr_coord[1];
					
					$sql.=", `lat`='$lat', `lng`='$long' ";
				}
				
			}
			else { 

				if(isset($_POST[$caption])) {

					$field_val=escape($_POST[$caption]);

					if($field_type=="menu" && $fields[$i]['other_val']==1) {
						if($field_val=="-1") $field_val=escape($_POST[$caption."_other_val"]);
					}
					if($field_type=="youtube") {
						// add wmode="transparent" to <embed tag>
						if(!strstr($field_val, ' wmode=\"transparent\"')) $field_val = str_replace("></embed>", ' wmode=\"transparent\"></embed>', $field_val);
					}
					if($field_type=="textarea" || $field_type=="htmlarea") $field_val = str_replace("\n", "\n<br />", escapeHtml($field_val));
					if($field_type=="textbox") $field_val = escapeHtml($field_val);
					if($field_type=="phone" || $field_type=="whatsapp") { 
					
						// get hidden international number
						if($fields[$i]['ext1']==1) { 
							if(!isset($_POST[$caption]) || !$_POST[$caption])
								$field_val='';
							else
								$field_val=escape($_POST[$caption."_hidden"]);
						}
						
						$field_val = escapeHtml($field_val);
						
					}

				} else $field_val='';
				
			}

			if($validation=="url" || $field_type=="url") { // correct if http:// is missing

				$field_val = correct_href($field_val);

			}

			if($validation=="numeric") { // correct numeric value

				$field_val = correct_numeric($field_val);

			}

			if($validation=="price" && $field_type=="price") { // correct price value
				$f = new fields($this->type);
				if($f->fieldsetHasPrice($set)) {
					if($field_val) {
					if($field_val<0) $field_val=0;
					
					/*$arr_ext=explode("|",$fields[$i]['extensions']);
					$format_point=$arr_ext[1];
					$format_separator=$arr_ext[2];
					
					$field_val = correct_price($field_val, $format_separator, $format_point);*/
					} else $field_val= "-1";
				} else $field_val= "-1";
			}

			if(!$this->edit || $field_val || ($this->edit && $field_type!="image" && $field_type!="logo" && $field_type!="file" && $field_type!="video" && $field_type!="audio")) {

				if(!$array_format)  {

					if($field_val || ($field_type=="checkbox" && $field_val==0)) $sql.=", `$caption` = '".$field_val."'";
					else if($fields[$i]['default_val']) $sql.=", `$caption` = '".$fields[$i]['default_val']."'";
					else $sql.=", `$caption` = null";
				}
				else {

					if($field_val || ($field_type=="checkbox" && $field_val==0)) $return_array[$caption] = $field_val;
					else if($fields[$i]['default_val']) $return_array[$caption] = $fields[$i]['default_val'];
					else $return_array[$caption] = null;

					
				}
	
			}
			} // end if not depending

		// public user choice
		if($this->type=="uf" && $fields[$i]["public"]==2) {
		  $array_uc = array("textbox","textarea","htmlarea","menu","url","email","user_email","phone","whatsapp","date");
		  if( in_array($field_type, $array_uc)) {

		    $uc_field_val = checkbox_value("pb_".$caption);
		    $sql.=", `pb_".$caption."` = '".$uc_field_val."'";
		  }
		
		}

		}

		// public email user choice
		// emails are not editable, so they will skip the previous part
		if($this->type=="uf" && !($is_admin || $is_mod) && $public_email==2) {

			$uc_field_val = checkbox_value("pb_email");
		    $sql.=", `pb_email` = '".$uc_field_val."'";

		}

		
//echo $sql; exit(0);	
		if(!$array_format) 
			return $sql;
		return $return_array;
	}

	function usernameEditable() {

		global $db;
		$editable = $db->fetchRow("select `editable` from ".$this->table." where `caption` like 'username'");
		return $editable;

	}

	function emailEditable() {

		global $db;
		$editable = $db->fetchRow("select `editable` from ".$this->table." where `caption` like 'email'");
		return $editable;

	}

	function validateUniqueField($caption, $val) {

		global $db;

		if($this->type=='cf') $tbl=TABLE_ADS; else $tbl=TABLE_USERS;
		
		$edit_str = '';
		if($this->edit) $edit_str=" and `id` != '{$this->obj_id}' ";
		
		$no = $db->fetchRow("select count(*) from ".$tbl." where `$caption` like '$val'".$edit_str);
		if($no) return 0;
		
		return 1;
	
	}
	
	function valid_video($caption, $max_size) {

		global $config_abs_path;
		global $lng;
		$dir = $config_abs_path."/uploads/".$caption;

		// if no file uploaded
		if(!isset($_FILES[$caption])) return 0;

		// problems with the folder

		if(!is_dir($dir) || !is_writeable($dir)) { $this->addError($caption, $lng['images']['errors']['folder_not_writeable']); return 0; }

		$extension = getExtension($_FILES[$caption]['name']);

		// check if allowed extension
		$extensions_array = array("mp4");

		if(!in_array($extension,$extensions_array)) return 0;

		// check if file size allowed
		$size=filesize($_FILES[$caption]['tmp_name']);

		if($size>($max_size*1000)) return 0;

		return 1;

	}
	
	function valid_audio($caption, $max_size) {

		global $config_abs_path;
		global $lng;
		$dir = $config_abs_path."/uploads/".$caption;

		// if no file uploaded
		if(!isset($_FILES[$caption])) return 0;

		// problems with the folder

		if(!is_dir($dir) || !is_writeable($dir)) { $this->addError($caption, $lng['images']['errors']['folder_not_writeable']); return 0; }

		$extension = getExtension($_FILES[$caption]['name']);

		// check if allowed extension
		$extensions_array = array("mp3");

		if(!in_array($extension,$extensions_array)) return 0;

		// check if file size allowed
		$size=filesize($_FILES[$caption]['tmp_name']);

		if($size>($max_size*1000)) return 0;

		return 1;

	}	
}

?>
