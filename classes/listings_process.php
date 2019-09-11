<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class listings_process {

	var $edit;

	public function __construct()
	{
		$this->edit = 0;
		$this->error = array();
	}

	function setEdit($val) {

		$this->edit = $val;

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

	function count_words($string) {
	
		$word_count=0;
		$cleaned_str= preg_replace("/[^A-Z0-9]/i"," ",$string);
		$string = preg_replace("/ +/", " ", $string);
		$string = explode(" ", $string);

		while (list(, $word) = each ($string)) {
			if (preg_match("/[0-9A-ZA`-Ö?-y]/i", $word)) $word_count++;
		}

		return($word_count);

	}

	function check_form ($id='') {

		global $db, $settings, $ads_settings, $lng;
		global $is_admin, $logged_in, $is_mod, $crt_usr;

		if( !$logged_in && !$is_admin && !$is_mod && !$settings['nologin_enabled'] ) { 

			$this->addError("category", "Please login to be able to post listings!");
			exit(0); 

		}

		$nologin = 0;
		if(!$logged_in && !$is_admin && !$is_mod) $nologin = 1;

		$this->tmp=array();

		$package_id = 0;
		$no_words=0; $no_pictures=0; $featured=0; $amount=0;

		if(!$id) { // if not in edit mode

			if(!$_POST['category']) $this->addError("category", $lng['listings']['errors']['category_missing']);

			else if(!is_numeric($_POST['category']))  $this->addError("category", $lng['listings']['errors']['invalid_category']);

			else $category = escape($_POST['category']);

			if(!$_POST['package']) $this->addError("package", $lng['listings']['errors']['package_missing']);
			else if(!is_numeric($_POST['package'])) $this->addError("package", $lng['listings']['errors']['invalid_package']);
			else $package_id = escape($_POST['package']);

			//if(($is_admin || $is_mod) && empty($_POST['user_id']) && empty($_POST['username']) && empty($_POST['email'])) $this->addError("user_id", $lng['listings']['errors']['user_missing']);
			if(($is_admin || $is_mod) && empty($_POST['user_id'])) $this->addError("user_id", $lng['listings']['errors']['user_missing']);

		}// end if not in edit mode

		else { // edit mode

			$category=listings::getCategory($id);
			$package_id=listings::getPackage($id);
		}


		if($package_id) {
			$no_words=packages::getNoWords($package_id);
			$photo_mandatory = packages::getPhotoMandatory($package_id);
		}

		// if photo mandatory
		// check if at least a photo is uploaded
		if($photo_mandatory) {
			if(!$id && !pictures::getNoSessionPictures()) {
				$this->addError("file-uploader", $lng['listings']['error']['photo_mandatory']);
			}
		}
		
		$def_lang='';
		$mlang_fields = 0;
		if($ads_settings['translate_title_description']) { 
			global $languages;
			if(empty($languages)) $languages = common::getCachedObject("base_languages");
			if(count($languages)>1) { 
				$def_lang = "_".languages::getDefault(); 
				$mlang_fields = 1;
			}
		}

		$description_array=array("description");
		$title_array=array("title");
		// for the case when we need different descriptions for each language

		if($mlang_fields) {
			$description_array=array();
			$title_array=array();
			foreach ($languages as $l) { 	
				array_push($description_array, "description_".$l['id']);
				array_push($title_array, "title_".$l['id']);
			}
		}

		// check if title exists
		$title_exists = 0;
		foreach ($title_array as $t) {

			if($_POST[$t]) { $title_exists = 1; break; }

		}
		if(!$title_exists) $this->addError("title".$def_lang, $lng['listings']['errors']['title_missing']);

		// check if description exists
		$desc_exists = 0;
		foreach ($description_array as $desc) {

			if($_POST[$desc]) { $desc_exists = 1; break; }

		}

		if(!$desc_exists) $this->addError("description".$def_lang, $lng['listings']['errors']['content_missing']);

		// check number of words
		if($no_words>0) {
		foreach ($description_array as $desc) {
			if(!$_POST[$desc]) continue;
			$wc=$this->count_words($_POST[$desc]);
			if($wc>$no_words) { 
				$err=preg_replace ('/::MAX::/', "$no_words", $lng['listings']['errors']['words_quota_exceeded']);
				$this->addError("description", $err);
				break;
			}
			
		} //end foreach

		} // end if($no_words>0)
		
		// check for forbidden words
		if(!$is_admin && !$is_mod) {
		$bd = 0;
		foreach ($description_array as $desc) {
			if($ads_settings['badwords_check']==1 && $_POST[$desc]) {
				$badword=new badwords();
				if($ads_settings['badwords_check_type']==1 && $badword->check($_POST[$desc])) {
					$badwords_error = str_replace('::FORBIDDEN_WORDS_LIST::', $badword->getWordsList(), $lng['listings']['errors']['badwords']);
					$this->addError("description", $badwords_error);
					$bd = 1;
				}
			}

		} // end foreach

		foreach ($title_array as $t) {
			if($ads_settings['badwords_check']==1 && $_POST[$t]) {
				$badword=new badwords();
				if($ads_settings['badwords_check_type']==1 && $badword->check($_POST[$t])) {
					$badwords_error = str_replace('::FORBIDDEN_WORDS_LIST::', $badword->getWordsList(), $lng['listings']['errors']['badwords']);
					if(!$bd) $this->addError("title", $badwords_error);
				}
			}

		} // end foreach
		} // end if not admin check for forbidden words
	
		// check if video code valid - steps version
 		//if(isset($_POST['video']) && $_POST['video']!="on" && !validator::valid_youtube(cleanStr($_POST['video']))) $this->addError("video", $lng['listings']['errors']['invalid_youtube_video']); 

		// check if video code valid - one step version
		if(!$id) {
 			if(isset($_POST['video']) && $_POST['video']=="on" && !empty($_POST['video_code']) && !validator::valid_youtube(cleanStr($_POST['video_code']))) $this->addError("video_code", $lng['listings']['errors']['invalid_youtube_video']); 
		}
		else {
		if(isset($_POST['video_code']) && !empty($_POST['video_code']) && !validator::valid_youtube(cleanStr($_POST['video_code']))) $this->addError("video_code", $lng['listings']['errors']['invalid_youtube_video']); 
		}
		if(!$id) {
 			if(isset($_POST['url']) && $_POST['url']=="on" && !empty($_POST['site_url']) && !validator::valid_url(cleanStr($_POST['site_url']))) $this->addError("site_url", $lng['listings']['errors']['invalid_url']); 
		}
		else {
		if(isset($_POST['site_url']) && !empty($_POST['site_url']) && !validator::valid_url(cleanStr($_POST['site_url']))) $this->addError("site_url", $lng['listings']['errors']['invalid_url']); 
		}

		$fields=new fields_process('cf');
		$fields->setEdit($this->edit);
		// check custom fields
		if( (!$id && isset($_POST['category']) && $_POST['category']!='') || $id) {

			$fieldset = categories::getFieldset($category);
			$fields->check_form_fields($fieldset);
			if($fields->getError()) { 

				$this->error = array_merge($this->error, $fields->getError());

			}

		}

		if($nologin && $settings['nologin_image_verification']) {

			global $config_abs_path;
			require_once $config_abs_path."/include/captcha.php";
			$err=checkCaptcha();
			if($err) { 
				if($settings['enable_recaptcha'])
					$this->addError("recaptcha_div", $err);
				else 
					$this->addError("number", $err);
			}

		}

		if($nologin) {

			$nl_fields = new fields_process('uf');
			$nl_fields->setEdit($this->edit);
			$nl_fields->check_form_fields(-1);
			if($nl_fields->getError()) {
				$this->error = array_merge($this->error, $nl_fields->getError());
			}

		}

		// auction check
		if($ads_settings['enable_auctions'] && isset($_POST['enable_auction']) && $_POST['enable_auction']=="on") {
		
			if(!isset($_POST['starting_price']) || !$_POST['starting_price'])
					$this->addError("starting_price", $lng['useraccount']['error']['auction_start_price']);
	
		}

		// post_ad_verification hook
		do_action("post_ad_verification", array());

		if($this->getError())
		{

			if($id) $this->tmp['id']=$id;

			foreach ($description_array as $f) {
			
				if(isset($_POST[$f])) $this->tmp[$f]=cleanStr($_POST[$f]); else $this->tmp[$f]='';

			}

			foreach ($title_array as $f) {
			
				if(isset($_POST[$f])) $this->tmp[$f]=cleanStr($_POST[$f]); else $this->tmp[$f]='';

			}


			foreach(array('currency') as $f) {

				if(isset($_POST[$f])) $this->tmp[$f]=$_POST[$f]; else $this->tmp[$f]='';

			}

			if($is_admin || $is_mod) {
				if(isset($_POST['user_id'])) $this->tmp['user_id']=$_POST['user_id']; else $this->tmp['user_id']='';
				if(isset($_POST['username'])) $this->tmp['username']=$_POST['username']; else $this->tmp['username']='';
				if(isset($_POST['email'])) $this->tmp['email']=$_POST['email']; else $this->tmp['email']='';
			}

			// video field 
			if(isset($_POST['video'])) $this->tmp['video']=cleanStr($_POST['video']); else $this->tmp['video']='';

			if($id) $this->tmp['enable_video'] = listings::videoEnabled($id);

			// url field 
			if(isset($_POST['url'])) $this->tmp['url']=cleanStr($_POST['url']); else $this->tmp['url']='';

			if($id) $this->tmp['enable_url'] = listings::urlEnabled($id);

			$this->tmp['tmp_fields']=$fields->getTmp();
			foreach ($this->tmp['tmp_fields'] as $key=>$value) {
				$this->tmp[$key] = $value;
			}

			if($nologin) {
				$this->tmp['tmp_nologin_fields']=$nl_fields->getTmp();
				foreach ($this->tmp['tmp_nologin_fields'] as $key=>$value) {
					$this->tmp[$key] = $value;
				}
			}

			// meta info
			if($ads_settings['add_meta_with_listings']) {
				if(isset($_POST['meta_keywords'])) $this->tmp['meta_keywords']=cleanStr($_POST['meta_keywords']); else $this->tmp['meta_keywords']='';

				if(isset($_POST['meta_description'])) $this->tmp['meta_description']=cleanStr($_POST['meta_description']); else $this->tmp['meta_description']='';
			}

		}
		return 1;
	}

	function add() {

		do_action("start_post_ad", array(&$spa_errors));
		
	    if($spa_errors && is_array($spa_errors)) {
			foreach($spa_errors as $key => $value)
				$this->addError($key, $value);
		}
	
		global $db, $ads_settings, $settings, $crt_lang;
		global $is_admin, $is_mod, $logged_in, $crt_usr;

		$clean=array();
		$this->check_form();
		if($this->getError()) return 0;

		$clean['user_id'] = 0;
		if($crt_usr) $clean['user_id'] = $crt_usr;
		else if($is_admin || $is_mod) { 
			if(isset($_POST['user_id']) && $_POST['user_id']) $clean['user_id'] = escape($_POST['user_id']); 
		}

		$clean['category_id'] = escape($_POST['category']);
		$fieldset = categories::getFieldset($clean['category_id']);
		// get ad available options for the current package

		$clean['package_id']=escape($_POST['package']);

		if(!$is_admin && !$is_mod) { 
			if(!empty($_POST['subscription']) && $_POST['subscription']) $clean['usr_pkg']=escape($_POST['subscription']); 
			else { 

				// if ad posting without ajax there will not be a usr_pkg defined
				$up = new users_packages(); 
				$clean['usr_pkg'] = $up->add($clean['package_id'], $crt_usr); 
				//echo "adding".$clean['package_id']." to ".$crt_usr." resulting ".$clean['usr_pkg'];
				//exit(0);

			}
		}
		else $clean['usr_pkg'] = 0;

		$packages=new packages();
		$package=$packages->getPackage($clean['package_id']);
		$clean['featured']=$package['featured'];
		$clean['highlited']=$package['highlited'];
		$clean['urgent']=$package['urgent'];
		$clean['priority'] = 0;
		if($package['priority']) $clean['priority']=priorities::getOrderNo($package['priority']); // order no for priority, not id
		$clean['enable_video']=$package['video'];
		$clean['enable_url']=$package['url'];
		$no_days = $package['no_days'];

		if($is_admin || $is_mod) { 

			if(!$clean['featured'] && isset($_POST['featured']) && $_POST['featured']) $clean['featured'] = escape($_POST["featured"]);
			if(!$clean['highlited'] && isset($_POST['highlited']) && $_POST['highlited']) $clean['highlited'] = checkbox_value("highlited");
			if(!$clean['urgent'] && isset($_POST['urgent']) && $_POST['urgent']) $clean['urgent'] = checkbox_value("urgent");
			if(!$clean['priority'] && isset($_POST['priority']) && $_POST['priority']) $clean['priority'] = escape($_POST['priority']);
			if(!$clean['enable_video'] && isset($_POST['video']) && $_POST['video']) $clean['enable_video'] = checkbox_value("video");
			if(!$clean['enable_url'] && isset($_POST['url']) && $_POST['url']) $clean['enable_url'] = checkbox_value("url");

		}

		// ------------------ Default Fields ---------------------
		$description_array=array("description");
		$title_array=array("title");
		$mlang = 0;
		// for the case when we need different descriptions for each language

		if($ads_settings['translate_title_description']) {
			global $languages;
			if(empty($languages)) $languages = common::getCachedObject("base_languages");
			if(count($languages)>1) {
				$description_array=array();
				$title_array=array();
				foreach ($languages as $l) { 	
					array_push($description_array, "description_".$l['id']);
					array_push($title_array, "title_".$l['id']);
				}
				$mlang = 1;
			}
		}
		$tags_list = tags_list($ads_settings['allowed_html']);

		$no_desc_fields = 0;
		// check for forbidden words
		foreach ($description_array as $desc) {
			// replace badwords with ***** if the case
			$clean[$desc]=escape($_POST[$desc]);
			if(!$is_admin && !$is_mod && $clean[$desc] && $ads_settings['badwords_check'] && $ads_settings['badwords_check_type']==0) {
				$badword=new badwords();
				if($badword->check($clean[$desc])) $clean[$desc]=$badword->replace($clean[$desc]); 
			}
			// remove html tags that are not allowed
			if($clean[$desc]) $clean[$desc] = strip_tags($clean[$desc], $tags_list);
			else $no_desc_fields = 1;
			
			$clean[$desc]= str_replace('\x', '--x',$clean[$desc]);
			
		} // end foreach ($description_array as $desc) 

		$no_title_fields = 0;
		foreach ($title_array as $t) {

			$clean[$t] = escape($_POST[$t]);

			// replace badwords with ***** if the case
			if(!$is_admin && !$is_mod && $clean[$t] && $ads_settings['badwords_check'] && $ads_settings['badwords_check_type']==0) {
				$badword=new badwords();
				if($badword->check($clean[$t])) $clean[$t]=$badword->replace($clean[$t]); 
			}

			// remove html tags that are not allowed
			if($clean[$t]) $clean[$t] = strip_tags($clean[$t], $tags_list);
			else $no_title_fields = 1;
		}

		// check if some language fields are not set and set them with default lang or another default value
		if($mlang) {

			if($no_title_fields) {
				if($clean['title_'.$crt_lang]) $default_title = $clean['title_'.$crt_lang];
				else {
					foreach ($languages as $l) if($clean['title_'.$l['id']]) $default_title = $clean['title_'.$l['id']];
				}
				foreach ($languages as $l) if(!$clean['title_'.$l['id']]) $clean['title_'.$l['id']] = $default_title;
			}

			if($no_desc_fields) {
				if($clean['description_'.$crt_lang]) $default_description = $clean['description_'.$crt_lang];
				else {
					foreach ($languages as $l) if($clean['description_'.$l['id']]) $default_description = $clean['description_'.$l['id']];
				}
				foreach ($languages as $l) if(!$clean['description_'.$l['id']]) $clean['description_'.$l['id']] = $default_description;
			}

		}

		//$clean['description'] = str_replace("\n", "\n<br />", $clean['description']);

		// check if price allowed for this category first!
		$fields=new fields('cf');
		if($fields->fieldsetHasPrice($fieldset)) 
			if(isset($_POST['currency'])) $clean['currency']=$db->my_mysql_real_escape_string($_POST['currency']); else $clean['currency']='';
		else $clean['currency']='';

		// meta info
		if($ads_settings['add_meta_with_listings']) {
			if(isset($_POST['meta_keywords'])) $clean['meta_keywords']=escape($_POST['meta_keywords']); else $clean['meta_keywords']='';

			if(isset($_POST['meta_description'])) $clean['meta_description']=escape($_POST['meta_description']); else $clean['meta_description']='';
		} else {
			$clean['meta_keywords']='';
			$clean['meta_description']='';
		}

		$clean['date_added']=date('Y-m-d H:i:s');

		$insert_array=array("user_id", "package_id", "usr_pkg", "category_id", "currency", "date_added", "active", "pending", "featured", "highlited", "urgent", "priority", "meta_keywords", "meta_description", "language");

		if(!$mlang) {
			array_push($insert_array, "description");
			array_push($insert_array, "title");
		} else {
			foreach ($description_array as $desc) array_push($insert_array, $desc);
			foreach ($title_array as $t) array_push($insert_array, $t);
		}

		global $crt_lang;
		$clean['language'] = $crt_lang;

		// if video enabled or video option just chosen
		$clean['video']='';
		if( (isset($_POST['video']) && $_POST['video']=="on") || ( $clean['package_id'] && packages::getVideo($clean['package_id']))) { 

			// add wmode="transparent" to <embed tag>
			if(isset($_POST['video'])) { 

				// one step posting
				if($_POST['video']=="on" && !empty($_POST['video_code']))  {
						$clean['video']=escape($_POST['video_code']); 
					//else // multistep posting
					//	$clean['video']=escape($_POST['video']); 
					if(!strstr($clean['video'], " wmode=\"transparent\"")) $clean['video'] = str_replace("></embed>", " wmode=\"transparent\"></embed>", $clean['video']);
				}
			}
			$insert_array[count($insert_array)] = "video";
		}

		// if url enabled or url option just chosen
		$clean['url']='';
		if( (isset($_POST['url']) && $_POST['url']=="on") || ( $clean['package_id'] && packages::getUrl($clean['package_id']))) { 

			// add wmode="transparent" to <embed tag>
			if(isset($_POST['url'])) { 

				// one step posting
				if($_POST['url']=="on" && !empty($_POST['site_url']))  {
						$clean['site_url']=escape($_POST['site_url']); 
				}
			}
			$insert_array[count($insert_array)] = "site_url";
		}

		if($is_admin || $is_mod) $clean['active']=1; else $clean['active']=0;
		$clean['pending']=0; 

		// build insert query string
		$sql="insert into ".TABLE_ADS." SET ";
		$i=0;
		foreach ($insert_array as $f) {
			if($i) $sql.=", ";
			$sql.="`$f` = '".$clean[$f]."'";
			$i++;
		}

		if($no_days!=0) $sql.=", `date_expires` = date_add('".$clean['date_added']."', interval '$no_days' day)";

		// add custom fields to query
		$f = new fields_process('cf');
		$sql.=$f->add_fields($fieldset);

		// add to database
		$res=$db->query($sql);

		$id=$db->insertId();
		$this->last=$id;

		// if there is a query error add it to the errors
		global $config_debug;
		if(!$id) $this->addError("", $db->getError());

		// add the photos to the current listing
		pictures::addSessionsToDB($this->last);

		// if nologin
		if($settings['nologin_enabled'] && !$clean['user_id']) {

			$nl_f = new fields_process('uf');
			$sql_nologin=$nl_f->add_fields(-1);
			$this->addOwnerInfo($id, $sql_nologin);

		}

		$lis = new listings;
		if($clean['enable_video']) {

			$noexp=0;
			if($package['video']) $noexp=1;
			$lis->addOption($id, 'video', $noexp);

		}
		if($clean['enable_url']) {

			$noexp=0;
			if($package['url']) $noexp=1;
			$lis->addOption($id, 'url', $noexp);

		}

		if($is_admin || $is_mod) { 

			$lis->userApprove($id);
			$lis->incCat($id, 1, $clean['category_id']);
			// check for mail alerts
			global $ads_settings;
			if($ads_settings['alerts_enabled']) {
				global $config_abs_path;
				require_once $config_abs_path."/classes/alerts.php";
				$alert = new alerts;
				$alert->checkImmediate($id, $clean);
			}
		}

		// add auction
		if($id && $ads_settings['enable_auctions'] && isset($_POST['enable_auction']) && $_POST['enable_auction']=="on") {
		
			global $config_abs_path;
			require_once $config_abs_path."/classes/auctions.php";
			$starting_price = escape($_POST['starting_price']);
			$auction_currency = escape($_POST['auction_currency']);
			$act = new auctions();
			$act->add($id, $starting_price, $auction_currency);
	
		}
		
		$slug = new slugs();
		$title_field = "title";
		if($ads_settings['translate_title_description']) {
			$default_lang = languages::getDefault();
			$title_field .= "_".$default_lang;
		}
		$slug->addListing($id, $clean[$title_field]);
		
		// end_post_ad hook
		do_action("end_post_ad", array($id));

		return $id;

	}


	function edit($id) {

		global $db, $crt_lang;
		global $ads_settings, $settings;
		global $is_admin, $is_mod, $crt_usr;

		if($settings['enable_locations'] && $settings['location_fields']) {
			$fields = $settings['location_fields'];
			$fields = str_replace(" ", "", $fields);
			$fields = str_replace(",", "`,`", $fields);
			$old = $db->fetchAssoc("select `$fields`, `active` from ".TABLE_ADS." where id=$id");
		}

		$category=listings::getCategory($id);
		$package=listings::getPackage($id);

		$fieldset = categories::getFieldset($category);

		if(!$crt_usr && !$is_admin && !$is_mod && !$settings['nologin_enabled']) { header("Location: not_authorized.php"); exit(0); }

		$this->clean=array();
		$this->check_form($id);
		if($this->getError()) return 0;

		do_action("start_edit_ad", array($id));

		$description_array=array("description");
		$title_array=array("title");
		$mlang = 0;
		// for the case when we need different descriptions for each language

		if($ads_settings['translate_title_description']) {
			global $languages;
			if(empty($languages)) $languages = common::getCachedObject("base_languages");
			if(count($languages)>1) {
				$description_array=array();
				$title_array=array();
				foreach ($languages as $l) { 	
					array_push($description_array, "description_".$l['id']);
					array_push($title_array, "title_".$l['id']);
				}
				$mlang = 1;
			}
		}

		$tags_list = tags_list($ads_settings['allowed_html']);

		$no_desc_fields = 0;
		foreach ($description_array as $desc) {
			// replace badwords with ***** if the case
			$clean[$desc]=escape($_POST[$desc]);

			// check for forbidden words
			if(!$is_admin && !$is_mod && $clean[$desc] && $ads_settings['badwords_check'] && $ads_settings['badwords_check_type']==0) {
				$badword=new badwords();
				if($badword->check($clean[$desc])) $clean[$desc]=$badword->replace($clean[$desc]);
			}
			// remove html tags that are not allowed
			if($clean[$desc]) $clean[$desc] = strip_tags($clean[$desc], $tags_list);
			else $no_desc_fields=1;

		} // end foreach ($description_array as $desc) 

		$no_title_fields = 0;
		foreach ($title_array as $t) {

			$clean[$t] = escape($_POST[$t]);

			// remove html tags that are not allowed
			if($clean[$t]) $clean[$t] = strip_tags($clean[$t], $tags_list);
			else $no_title_fields = 1;
		}

		// check if some language fields are not set and set them with default lang or another default value
		if($mlang) {

			if($no_title_fields) {
				if($clean['title_'.$crt_lang]) $default_title = $clean['title_'.$crt_lang];
				else {
					foreach ($languages as $l) if($clean['title_'.$l['id']]) $default_title = $clean['title_'.$l['id']];
				}
				foreach ($languages as $l) if(!$clean['title_'.$l['id']]) $clean['title_'.$l['id']] = $default_title;
			}

			if($no_desc_fields) {
				if($clean['description_'.$crt_lang]) $default_description = $clean['description_'.$crt_lang];
				else {
					foreach ($languages as $l) if($clean['description_'.$l['id']]) $default_description = $clean['description_'.$l['id']];
				}
				foreach ($languages as $l) if(!$clean['description_'.$l['id']]) $clean['description_'.$l['id']] = $default_description;
			}

		}

		global $crt_lang;
		$clean['language'] = $crt_lang;

		$fields=new fields('cf');
		if($fields->fieldsetHasPrice($fieldset)) 
			if(isset($_POST['currency'])) $clean['currency']=$db->my_mysql_real_escape_string($_POST['currency']); else $clean['currency']='';
		else $clean['currency']='';

		// meta info
		if($ads_settings['add_meta_with_listings']) {
			if(isset($_POST['meta_keywords'])) $clean['meta_keywords']=escape($_POST['meta_keywords']); else $clean['meta_keywords']='';

			if(isset($_POST['meta_description'])) $clean['meta_description']=escape($_POST['meta_description']); else $clean['meta_description']='';
		} else {
			$clean['meta_keywords']='';
			$clean['meta_description']='';
		}

		$insert_array=array("currency", "meta_keywords", "meta_description", "language");
		if(!$mlang) {
			array_push($insert_array, "description");
			array_push($insert_array, "title");
		} else {
			foreach ($description_array as $desc) array_push($insert_array, $desc);
			foreach ($title_array as $t) array_push($insert_array, $t);
		}

		$enable_video = listings::videoEnabled($id);

		// if video enabled or video option just chosen
		if($enable_video || packages::getVideo($package)) { 
			if(isset($_POST['video_code'])) { 
				$clean['video']=escape($_POST['video_code']); 
				if(!strstr($clean['video'], " wmode=\"transparent\"")) $clean['video'] = str_replace("></embed>", " wmode=\"transparent\"></embed>", $clean['video']);
			}
			else $clean['video']='';
			$insert_array[count($insert_array)] = "video";
		}

		$enable_url = listings::urlEnabled($id);

		// if url enabled or url option just chosen
		if($enable_url || packages::getUrl($package)) { 
			if(isset($_POST['site_url'])) { 
				$clean['site_url']=escape($_POST['site_url']); 
			}
			else $clean['site_url']='';
			$insert_array[count($insert_array)] = "site_url";
		}

		$pending_edited = 0; 
		if(!($is_admin || $is_mod) && $ads_settings['pending_edited']  && listings::wasListingPostedAsPending($id)) $pending_edited=1;
	
		if(!$pending_edited) {

			// build query string
			$sql="update ".TABLE_ADS." SET ";
			$i=0;
			foreach ($insert_array as $f) {
				if($i) $sql.=", ";
				$sql.="`$f` = '".$clean[$f]."'";
				$i++;
			}

			// add custom fields to query
			$f = new fields_process('cf');
			$f->setEdit($this->edit); // set fields edit mode
			$sql.=$f->add_fields($fieldset);
		
			$sql.=" where id=".$id;

			// add to database
			$res=$db->query($sql);
//echo $sql;
		}
		else {
		// -------------- PENDING EDITED ---------------

			global $config_abs_path, $config_live_site;

			// get custom fields values as array
			$f = new fields_process('cf');
			$f->setEdit($this->edit); // set fields edit mode
			$field_values=$f->add_fields($fieldset, 1);

			$larr = array();
			foreach ($insert_array as $f) {
				$larr[$f] = $clean[$f];
			}

			$larr = array_merge($larr, $field_values);
			
			require_once $config_abs_path."/libs/JSON.php";
			
			if (version_compare(phpversion(), '5.4', 'ge'))
			
				$encoded = json_encode($larr, JSON_UNESCAPED_UNICODE);
			
			else 
			
				$encoded = raw_json_encode($larr);
			
			$this->addAsPendingEdited($id, $encoded);

			// ad_id, ad_title, review link
			// contact_name

			$this->notifyAdminPendingEdited($id);

		}


		// if nologin
		if($settings['nologin_enabled'] && !$crt_usr && !$is_admin & !$is_mod) {
			//$this->editOwnerInfo($id);

			$nl_f = new fields_process('uf');
			$nl_f->setEdit($this->edit);
			$sql_nologin=$nl_f->add_fields(-1);
			$this->editOwnerInfo($id, $sql_nologin);

		}

		// add auction
		if($ads_settings['enable_auctions']) {
		
			global $config_abs_path;
			require_once $config_abs_path."/classes/auctions.php";
			if(isset($_POST['enable_auction']) && $_POST['enable_auction']=="on") {
		
				$starting_price = escape($_POST['starting_price']);
				$auction_currency = escape($_POST['auction_currency']);
				$act = new auctions();
				// check if already exists
				if($act_id = $act->adHasAuction($id))
					$act->update($act_id, $starting_price, $auction_currency);
				else
					$act->add($id, $starting_price, $auction_currency);
	
			} else { // check if it wasn't enabled and should be disabled
		
				$act = new auctions();
				if($act_id = $act->adHasAuction($id))
					$act->delete($act_id);
		
			}
		
		} // end if enable_auctions

		$slug = new slugs();
		$title_field = "title";
		if($ads_settings['translate_title_description']) {
			$default_lang = languages::getDefault();
			$title_field .= "_".$default_lang;
		}
		$slug->editListing($id, $clean[$title_field]);

		
		// end_post_ad hook
		do_action("end_edit_ad", array($id));

		if($pending_edited) return 1;
// !!!!!! do this when accepting the ad

		// check if location fields have changed
		if($settings['enable_locations'] && $settings['location_fields'] && $old['active']) {
			global $config_abs_path;
			require_once $config_abs_path."/classes/locations.php";
			$loc_fields = explode(",", $settings['location_fields']);
			$lis = new listings;
			global $languages;
			foreach($loc_fields as $f) {
				$loc_val = "";
				if(isset($_POST[$f])) $loc_val = $_POST[$f]; 
				if($old[$f]!=$loc_val) {

					if($old[$f]) {
						// decrement for old location
						if($old[$f]) $lis->decForField($id, $f, $old[$f]);
						$lis->decCategoryField($id, $category, $f, $old[$f]);

						// if multilanguage, decrement for the translated location name
						if(count($languages>1)) {
							$l = new locations();
							$translated_locations = $l->translateLocation ($f, $old[$f]);
							foreach($translated_locations as $tr)
								$lis->decCategoryField($id, $category, $f, $tr);
						}
					}

					if($_POST[$f]) {
						// increment for new location
						if($_POST[$f]) $lis->incForField($id, $f, $_POST[$f]);
						$lis->incCategoryField($id, $category, $f, $_POST[$f]);

						// if multilanguage, increment for the translated location name
						if(count($languages>1)) {
							$l = new locations();
							$translated_locations = $l->translateLocation ($f, $_POST[$f]);
							foreach($translated_locations as $tr)
								$lis->incCategoryField($id, $category, $f, $tr);
						}
					}

				}
			}
		}

		return 1;

	}

	function addOwnerInfo($id, $sql) {

		global $settings;
		global $db;
		$ip = getRemoteIp();
		$activation='';
		if($settings['nologin_activate_via_sms']==1)
			$activation=substr(generate_random(),0,6);
		else $activation=generate_random();	 // activation is used even if no activation is required to access the listing
			
		$res = $db->query("insert into ".TABLE_ADS_EXTENSION." set `id` = '$id', `ip`='$ip', `activation`='$activation'".$sql);
		
		do_action("add_contact", array($id));
		
		return 1;
	
	}

	function editOwnerInfo($id, $sql) {

		global $db;
		if($sql) {
			$sql = substr($sql, 2);
			$res = $db->query("update ".TABLE_ADS_EXTENSION." set $sql where `id` = '$id'");
		}
		return 1;
	
	}


	function getLast() {

		return $this->last;
	
	}

	function addAsPendingEdited($id, $encoded) {

		global $db;
		$timestamp = date("Y-m-d H:i:s");

		$exists = $db->fetchAssoc("select * from ".TABLE_PENDING_EDITED." where `ad_id`='$id'");

		if(!$exists)
			$db->query("insert into ".TABLE_PENDING_EDITED." set `ad_id`='$id', `date` = '$timestamp', `edited` = '".escape($encoded)."'");
		else 
			$db->query("update ".TABLE_PENDING_EDITED." set `edited`='$encoded' where `ad_id`='$id'");

		return 1;

	}

	function denyPendingEdited($id) {

		global $db;
		$db->query("delete from ".TABLE_PENDING_EDITED." where `ad_id`='$id'");

		$user_details = listings::getUserDetails($id);
		$details_link = listings::makeDetailsLink($id, $user_details['key']);
		$ad_title = cleanStr(listings::getTitle($id));
		
		// send mail to user
		global $config_abs_path;
		require_once $config_abs_path."/classes/mails.php";
		require_once $config_abs_path."/classes/mail_templates.php";

		$mail2send=new mails();
		$mail2send->init($user_details['email'], $user_details['name']);

		global $lng;
		$array_subject = array("ad_id" => $id, "ad_title" => $ad_title, "action" =>$lng['listings']['denied']);
		$array_message = array("ad_id" => $id, "ad_title" => $ad_title, "details_link" => $details_link, "contact_name" => $user_details['name'], "action" =>$lng['listings']['denied']);

		$mail2send->composeAndSend("pending_edited", $array_message, $array_subject);


	}

	function acceptPendingEdited($id) {

		global $config_abs_path, $db;
		require_once $config_abs_path."/libs/JSON.php";

		$edited = $db->fetchRow("select `edited` from ".TABLE_PENDING_EDITED." where `ad_id`='$id'");

		if($edited) {

			$array_fields = json_decode($edited);

			$sql = "update ".TABLE_ADS." set ";
			$first = 1;

			// get a list of listings table fields
			$existing_fields = $db->getTableFields(TABLE_ADS);	

			foreach($array_fields as $key=>$value) {

				if(!in_array($key, $existing_fields)) continue;
				if(!$first) $sql.=", ";
				$sql.="`$key` = '$value'";
				$first = 0;

			}

			$sql.=" where `id`='$id'";
			$db->query($sql);
		}

		$pictures_edited = $db->fetchRow("select `pictures_edited` from ".TABLE_PENDING_EDITED." where `ad_id`='$id'");
		if($pictures_edited) {
			$array_pictures = json_decode($pictures_edited, true);

			$idarray = array();

			foreach ($array_pictures as $p) {
    				$idarray[] = $p['id'];
			}

			// delete old pictures which aren't in the new array anymore
			$pic = new pictures;
			$array_old_pictures = $pic->getPicturesArray($id);

			foreach ($array_old_pictures as $pic_id) {
				if(!in_array($pic_id, $idarray)) $pic->delete($pic_id);
			}

			// add picture only if not added already
			foreach($array_pictures as $p) {
				if(!in_array($p['id'], $array_old_pictures)) $pic->addToDB($id, $p['picture'], $p['folder']);
			}
			$pic->reOrder($id);
		}

		// update pictures if the case

		$db->query("delete from ".TABLE_PENDING_EDITED." where `ad_id`='$id'");

		$user_details = listings::getUserDetails($id);
		$details_link = listings::makeDetailsLink($id, $user_details['key']);
		$ad_title = cleanStr(listings::getTitle($id));
		
		// send mail to user
		global $config_abs_path;
		require_once $config_abs_path."/classes/mails.php";
		require_once $config_abs_path."/classes/mail_templates.php";

		$mail2send=new mails();
		$mail2send->init($user_details['email'], $user_details['name']);

		global $lng;
		$array_subject = array("ad_id" => $id, "ad_title" => $ad_title, "action" =>$lng['listings']['accepted']);
		$array_message = array("ad_id" => $id, "ad_title" => $ad_title, "details_link" => $details_link, "contact_name" => $user_details['name'], "action" =>$lng['listings']['accepted']);

		$mail2send->composeAndSend("pending_edited", $array_message, $array_subject);

		return 1;

	}

	function notifyAdminPendingEdited($id) {

		global $db, $config_live_site, $config_abs_path;
		require_once $config_abs_path."/classes/users.php";
		require_once $config_abs_path."/classes/mails.php";
		require_once $config_abs_path."/classes/mail_templates.php";

		$already_sent = $db->fetchRow("select `notification_sent` from ".TABLE_PENDING_EDITED." where `ad_id`='$id'");
		if($already_sent) return;

		$user_details = listings::getUserDetails($id);
		$details_link = listings::makeDetailsLink($id, $user_details['key']);
		$ad_title = cleanStr(listings::getTitle($id));

		$review_link = $config_live_site.'/admin/review_pending_edited.php?id='.$id;
		if($html_mails) $review_link = '<a href="'.$review_link.'">'.$review_link.'</a>';

		$mail2send=new mails();
		$mail2send->init();

		$array_subject = array("ad_id" => $id, "ad_title" => $ad_title);
		$array_message = array("ad_id" => $id, "ad_title" => $ad_title, "details_link" => $details_link, "review_link" => $review_link);

		$mail2send->composeAndSend("admin_pending_edited", $array_message, $array_subject);

		$db->query("update ".TABLE_PENDING_EDITED." set `notification_sent` = 1 where `ad_id`='$id'");

	}

}
?>