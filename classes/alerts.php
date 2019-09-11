<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class alerts {

	var $id;
	var $error='';
	var $info='';
	var $clean;
	var $array;
	var $tmp;

	public function __construct()
	{
	
	}

	function getId() {

		return $this->id;

	}

	function delete($id=0) {

		global $db;
		$res_del=$db->query('delete from '.TABLE_EMAIL_ALERTS.' where `id`="'.$id.'"');
		$db->query("delete from ".TABLE_NEW_ALERTS." where `alert_id` = '$id'");
		return 1;
	}

	function Enable($id) {

		global $db;
		$res_del=$db->query('update '.TABLE_EMAIL_ALERTS.' set `active` = 1 where `id`="'.$id.'"');
		return 1;
	}

	function Disable($id) {

		global $db;
		$res_del=$db->query('update '.TABLE_EMAIL_ALERTS.' set `active` = 0 where `id`="'.$id.'"');
		return 1;
	}

	function getAlert($id='') {
		
		if(!$id) $id=$this->id;
		global $db;
		$result=$db->fetchAssoc('select * from '.TABLE_EMAIL_ALERTS.' where `id`="'.$id.'"');
		return $result;

	}

	function getUserId($id='') {
		
		if(!$id) $id=$this->id;
		global $db;
		$result=$db->fetchRow('select `user_id` from '.TABLE_EMAIL_ALERTS.' where `id`="'.$id.'"');
		return $result;

	}

	function getEmail($id='') {
		
		if(!$id) $id=$this->id;
		global $db;
		$result=$db->fetchRow('select `email` from '.TABLE_EMAIL_ALERTS.' where `id`="'.$id.'"');
		return $result;

	}

	function getSearch($id='') {
		
		if(!$id) $id=$this->id;
		global $db;
		$result=$db->fetchRow('select `search` from '.TABLE_EMAIL_ALERTS.' where `id`="'.$id.'"');
		return $result;

	}

	function getFreq($id='') {
		
		if(!$id) $id=$this->id;
		global $db;
		$result=$db->fetchRow('select `frequency` from '.TABLE_EMAIL_ALERTS.' where `id`="'.$id.'"');
		return $result;

	}

	function count() {
	
		global $db;
		$no=$db->fetchRow('select count(*) from '.TABLE_EMAIL_ALERTS);
		return $no;

	}

	function confirm($id, $key) {

		global $db;

		$exists = $db->fetchRow("select count(*) from ".TABLE_EMAIL_ALERTS." where `id`=$id and `key` like '$key'");
		if(!$exists) return 0;

		$db->query("update ".TABLE_EMAIL_ALERTS." set `active`=1 where `id`=$id and `key` like '$key'");
		return 1;

	}

	function unsubscribe($id, $key) {

		global $db;

		$exists = $db->fetchRow("select count(*) from ".TABLE_EMAIL_ALERTS." where `id`=$id and `key` like '$key'");
		if(!$exists) return 0;

		$db->query("delete from ".TABLE_EMAIL_ALERTS." where `id`=$id and `key` like '$key'");
		return 1;

	}

	function getAll() {

		global $db;
		$result=$db->fetchAssocList('select * from '.TABLE_EMAIL_ALERTS.' order by id desc');
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
	
		return $this->tmp;

	}

	function setInfo($str){

		$this->info = $str;

	}

	function getInfo() {

		return $this->info;

	}

	function add($email,$freq, $post_array) {
	
		global $db;
		global $lng;
		global $ads_settings;
		$this->error='';
		$this->tmp=array();
		$this->clean=array();
		if(!$ads_settings['alerts_enabled']) { $this->addError("You are not allowed to add an alert!"); return 0; }

		if($ads_settings['alerts_require_login']) {

			$auth = new auth;

			if(!$auth->crtUserId()) { 
				global $config_live_site;
				$search=$this->makeSearch($post_array);

				$login_link = $config_live_site."/login.php?loc=listings.php?".$search;
				$err = str_replace("::LINK::", $login_link, $lng['alerts']['error']['login_required']);
				$this->addError($err);
				return 0;
			}

		}

		// check if other key than category is added
		if($ads_settings['alerts_ask_adv_key']) {

			$array_ignore = array("category", "area", "user_id", "page", "x", "y", "Search", "show");
			$inc = 0;
			foreach($post_array as $key => $val) if($val!='' && !in_array($key, $array_ignore)) $inc++;
			if(!$inc) { $this->addError($lng['alerts']['error']['ask_adv_key']); return 0; }
		}


		if(!$email) $this->addError($lng['alerts']['error']['email_required']);
		else if(!validator::valid_email($email)) $this->addError($lng['alerts']['error']['invalid_email']);
		if(!is_numeric($freq)) $this->addError($lng['alerts']['error']['invalid_frequency']);

		if($this->getError()!='') return 0;

		// get current user id
		$user_id = 0;
		$auth = new auth;
		$user_id = $auth->crtUserId();

		$this->clean['search'] = escape($this->makeSearch($post_array));

		$this->clean['user_id'] = $user_id;

		$this->clean['email'] = escape($email);

		$this->clean['frequency'] = escape($freq);

		$this->clean['key'] = generate_random();

		if($ads_settings['alerts_activation']==1 || ($ads_settings['alerts_activation']==2 && !$user_id)) $confirmation = 1; else $confirmation = 0;

		$active = 1;
		if($confirmation) $active = 0;

		$timestamp = date("Y-m-d H:i:s");

		$db->query("insert into ".TABLE_EMAIL_ALERTS." set `user_id` = '".$this->clean['user_id']."', `email` = '".$this->clean['email']."',  `ip` =  '".getRemoteIp()."', `search` = '".$this->clean['search']."', `frequency` = '".$this->clean['frequency']."', `key` = '".$this->clean['key']."', `date` = '$timestamp', `last_check` = '$timestamp', `active` = $active;");
		$last_id=$db->insertId();


		// send email for alert confirmation
		$mail2send=new mails();
		$mail2send->init($this->clean['email'], $this->clean['email']);

		$search=$this->makeSearchString($post_array);
		global $config_live_site;
		global $mail_settings;
		$html_mails=$mail_settings["html_mails"];

		if($html_mails) $amp = "&amp;"; else $amp = "&";
		if($confirmation) {
			$confirmation_link = $config_live_site."/alerts.php?action=confirm".$amp."id=$last_id".$amp."key=".$this->clean['key'];
			if($html_mails) $confirmation_link ='<a href="'.$confirmation_link.'">'.$confirmation_link.'</a>';
		}
		else $confirmation_link = '';

		$unsubscribe_link = $config_live_site."/alerts.php?action=unsubscribe".$amp."id=$last_id".$amp."key=".$this->clean['key'];
		if($html_mails) $unsubscribe_link ='<a href="'.$unsubscribe_link.'">'.$unsubscribe_link.'</a>';

		$array_subject = array("search"=>$search);

		$array_message = array("search"=>$search, "confirmation"=>$confirmation, "confirmation_link"=>$confirmation_link, "unsubscribe_link"=>$unsubscribe_link);

		$mail2send->composeAndSend("email_alert_confirmation", $array_message, $array_subject);

		if( !$confirmation ) {
			
			$this->setInfo($lng['alerts']['alert_added']);
		}
		else { 
			$this->setInfo(str_replace("::EMAIL::", $this->clean['email'], $lng['alerts']['alert_added_activate']));
		}
		
		return 1;

	}

	function makeSearch($post_array) {

		$i=0;
		$search_str = "";
		$array_ignore=array("x","y","Search","page", "show");
		foreach($post_array as $key => $val) {
			if($val=='' || in_array($key, $array_ignore)) continue;
			if($i) $search_str.="&";
			$search_str.="$key=$val";
			$i++;
		}
		return $search_str;
	}

	function makeSearchString($post_array) {

		global $lng;
		$search_str = "";
		$categ_string = "";
		$array_ignore=array("x","y","Search","page", "show");
		
		global $config_abs_path;
		require_once $config_abs_path."/classes/fields.php";
		$f = new fields("cf");

		foreach($post_array as $key=>$value) {

			//$value = rawurldecode($value);

			$separator=" ";

			// comparative search
			if(preg_match ('/^([^\/])+(_low)$/', $key)) {
				$separator = " ".$lng['search']['more_than']." ";
				$key = str_replace("_low", "", $key);
			}

			if(preg_match ('/^([^\/])+(_high)$/', $key)) {
				$separator = " ".$lng['search']['less_than']." ";
				$key = str_replace("_high", "", $key);
			}

			if($value=='' || in_array($key, $array_ignore)) continue;

			if($key=="category" || $key=="category_id") {
				$categ = new categories();
				$categ_string = $lng['general']['search']." ".$categ->makeCategoryPath($value).$lng['alerts']['category'];
				continue;
			}

			global $keyword_name;
			if($key==$keyword_name) $field_name = $lng['alerts']['keyword'];
			else if($key=="with_pic") { $field_name=$lng['advanced_search']['only_ads_with_pic']; $value = ""; }
			else if($key=="location") { $field_name=$lng['listings']['location']; }
			else $field_name = cleanStr($f->getNameByCaption($key));

			// areasearch 
			if($key=="zip" && $value) {

				global $modules_array;
				// if areasearch active and area set
				if(in_array("areasearch", $modules_array) && isset($post_array['area']) && $post_array['area']) {
					global $lng_areasearch;
					$as = new areasearch;
					$area_settings = $as->getSettings();
					$area = $post_array['area']." ".$area_settings['um'];
					if($search_str) $search_str = $search_str.", ";
					$search_str.=$area." ".$lng_areasearch['around']." $field_name \"$value\"";
					continue;
				}
			}

			if($field_name) { 
				if($search_str) $search_str = $search_str.", ";
				$search_str.="$field_name";
				if($value!='') $search_str.=$separator."\"$value\"";
			}

		}

		$str = $categ_string;
		if($str && $search_str) $str.=", ";//.$lng['general']['for']." ";
		else if ($search_str) $str.=$lng['general']['search']." ";//$lng['search']['search_for']." ";
		$str.=$search_str;

		return $str;

	}

	function checkImmediate($id, $clean) {

		global $db;
		global $modules_array;
		global $ads_settings, $seo_settings;
		global $config_live_site;

		$fields = explode(",",$ads_settings['search_in_fields']);

		$ads_fields = $db->getTableFields(TABLE_ADS);
// where email != current email
		$search_in = $db->fetchAssocList("select `id`, `email`, `search`, `key` from ".TABLE_EMAIL_ALERTS." where frequency=0 and active=1");

		global $config_abs_path;
		require_once $config_abs_path."/classes/mails.php";
		require_once $config_abs_path."/classes/mail_templates.php";

		$mail2send=new mails();
		$mail2send->setMailTemplate("email_alert");

		global $mail_settings;
		$html_mails=$mail_settings["html_mails"];

		if($html_mails) $amp = "&amp;"; else $amp = "&";

		$location_fields = explode(",", $ads_settings['location_fields']);

		foreach($search_in as $alert) {

			$match = 1;
			$search = $this->searchToArray($alert['search']);

			foreach ($search as $key=>$value) {

				if($key=="with_pic") {
					if(isset($clean['images']) && count($clean['images'])==0) { $match = 0; break; }
					continue;
				}

				if($key == "category_id") {
					// if parent category, get subcategories list
					$categ = new categories();
					$s = "";
					$value = $categ->subcatList($value, $s);
					$arr_cats = explode(",", $value);

					if(!in_array($clean['category_id'], $arr_cats)) { $match = 0; break; }
					continue;

				}

				if($key == "location") {
					$found=0;
					foreach ( $location_fields as $loc ) {
						if($clean[$loc]==$value) $found=1;
					}
					if(!$found) { $match = 0; break; }
					continue;

				}

				if(preg_match ('/^([^\/])+(_low)$/', $key)) { 
					$key = str_replace("_low", "", $key);
					if(!isset($clean[$key]) || $clean[$key] < $value) { $match = 0; break; }
					continue;
				}

				if(preg_match ('/^([^\/])+(_high)$/', $key)) { 
					$key = str_replace("_high", "", $key);
					if(!isset($clean[$key]) || $clean[$key] > $value) { $match = 0; break; }
					continue;
				}

				// areasearch
				if($key=="zip") {
					global $modules_array;

					// if areasearch active and area set
					if($value && in_array("areasearch", $modules_array) && isset($search['area']) && $search['area'] && $search['area']>0) {
						$areas = new areasearch;
						if(isset($clean['zip']) && $areas->distance($value,$clean['zip'])>$search['area']) { $match = 0; break; }
						continue;
					}
				}

				global $keyword_name;
				if($key==$keyword_name) {
				if(!count($fields)) continue;

				$search_array = explode(' ', $value);
//_print_r($search_array);				
				$no_words = count($search_array);
				$k=0;
				for($i=$no_words; $i>0;$i--)
				{
					$found = 0;
					$w=trim($search_array[$i-1]);
					if($w!='') {
						$j=0;
						foreach($fields as $f) {
							if(isset($clean[$f]) && strstr($clean[$f], $w )) $found = 1;
							$j++;
							$k++;
						}
					}
					//echo $found." ".$w."<br/>";
					if(!$found) { $match=0; break; }
				}
				continue;
				}
//echo $match."on".$key;
				// the field can be a special module field 
				if(!in_array($key, $ads_fields)) {
					if(in_array($key, $modules_array)) {
						$custom_obj = new $key;
						$array_fields = $custom_obj->getFieldsArray();
						$search_type = $custom_obj->getSearchType(); // 1 = exact match, 2 = partial match
	
						$found = 0;
						foreach($array_fields as $f) {
							if($search_type==1) {
								if( $clean[$f] == $value ) $found=1;
							} else {
								if(strstr($clean[$f], $value)) $found = 1;
							}
						}
						if(!$found) { $match = 0; break; }
					}
					continue;
				}

				if(!isset($clean[$key]) || $clean[$key] != $value) { $match = 0; break; }

			}

			if($match) {
//echo "sending alert";
				// send alert email
				$mail2send->init($alert['email'], $alert['email']);

				if($seo_settings['enable_mod_rewrite']) {
					$seo = new seo();
					$link=$seo->makeDetailsLink($id, $clean['title']);
				}
				else $link=$config_live_site.'/details.php?id='.$id;

				if($html_mails) $link ='<a href="'.$link.'">'.$link.'</a>';

				$unsubscribe_link = $config_live_site."/alerts.php?action=unsubscribe".$amp."id={$alerts['id']}".$amp."key=".$alert['key'];
				if($html_mails) $unsubscribe_link ='<a href="'.$unsubscribe_link.'">'.$unsubscribe_link.'</a>';


				$search_string = $this->makeSearchString($search);

				$array_subject = array("search"=>$search_string, "no"=>1);

				$array_message = array("search"=>$search_string, "no"=>1, "link"=>$link, "unsubscribe_link" => $unsubscribe_link);

				$mail2send->composeAndSend("", $array_message, $array_subject);

				$this->setLastCheck($alert['id']);

			}

		}

	}

	function setLastCheck($id) {

		global $db;
		$timestamp = date("Y-m-d H:i:s");
		$db->query("update ".TABLE_EMAIL_ALERTS." set last_check = '$timestamp' where id=$id");
		return 1;

	}

	function searchToArray($str) {

		$result = array();
		if(!$str) return $result;
		$arr = explode("&",$str);

		foreach($arr as $each) {

			if(!$each) continue;
			$vars = explode("=",$each);
			if($vars[0]=="category") $vars[0] = "category_id";
			$result[$vars[0]] = $vars[1];

		}

		return $result;
	}


	function getNewAlerts($id, $page, $no_per_page) {

		global $db;
		$listings = $db->fetchRow("select `listings` from ".TABLE_NEW_ALERTS." where `id`=$id");
		$arr = explode(",", $listings);
		$no_listings = count($arr);
		$start = ($page-1)*$no_per_page;

		$max = $no_listings%$no_per_page;

		$return_array = array();
		for($i=0; $i<$no_per_page && $i<$max;$i++) {

			$return_array[$i] = $arr[$start+$i];

		}

		return implode(",",$return_array);
	}

	function getNoListings($id) {

		global $db;
		$listings = $db->fetchRow("select `listings` from ".TABLE_NEW_ALERTS." where `id`=$id");
		$arr = explode(",", $listings);
		$no_listings = count($arr);
		return $no_listings;

	}

	function getAlertId($id) {

		global $db;
		$id = $db->fetchRow("select `alert_id` from ".TABLE_NEW_ALERTS." where `id`=$id");
		return $id;

	}

	function getNoAlerts($user_id) {

		global $db;
		$no = $db->fetchRow("select count(*) from ".TABLE_EMAIL_ALERTS." where `user_id` = $user_id ");
		return $no;

	}

	function getAlerts($user_id=0) {

		global $db;
		if($user_id) $str_user = " where `user_id` = '$user_id'";
		global $appearance_settings;
		$date_format=$appearance_settings["date_format"];

		$array = $db->fetchAssocList("select *, date_format(`date`,'$date_format') as `date_nice` from ".TABLE_EMAIL_ALERTS.$str_user." order by date desc");

		$result = array();
		$i=0;
		foreach($array as $row) {

			$result[$i] = $row;
			$search_array = $this->searchToArray($row['search']);
			$search=$this->makeSearchString($search_array);
			$result[$i]['search_str'] = $search;
			$i++;
		}

		return $result;
	}

	function belongsToUser($id, $user_id) {

		global $db;
		$belongs = $db->fetchRow("select count(*) from ".TABLE_EMAIL_ALERTS." where `id` = $id and `user_id` = $user_id");
		return $belongs;
	}


	function search($post_array, $page,$no_per_page,$order,$order_way) {

		$start=($page-1)*$no_per_page;

		$found = 0; $where = '';
		$join = '';

		foreach($post_array as $key=>$value) {
			if( !$key || $value=='' ) continue;
			
			switch($key) {
				case "id": 
				case "ip": 
				case "email": 
				case "frequency": 
					if($found) $where.=" and "; else $where = " where ";
					// faster query for large number
					if($value==1) $where .= " ".TABLE_EMAIL_ALERTS.".`$key` = $value ";
					else $where .= " ".TABLE_EMAIL_ALERTS.".`$key` = '$value' ";
					$found = 1;
				break;
				case "username": 
					if($found) $where.=" and "; else $where = " where ";
					$where .= " ".TABLE_USERS.".`username` like '$value' ";
					$found = 1;
				break;
				case "active": 
					if($found) $where.=" and "; else $where = " where ";
					$where .= " ".TABLE_EMAIL_ALERTS.".`active` = 1 ";
					$found = 1;
				break;
				case "inactive": 
					if($found) $where.=" and "; else $where = " where ";
					$where .= " ".TABLE_EMAIL_ALERTS.".`active` = 0 ";
					$found = 1;
				break;
				case "date_from": 
					if($found) $where.=" and "; else $where = " where ";
					$where .= " ".TABLE_EMAIL_ALERTS.".`date` >= '$value' ";
					$found = 1;
				break;

				case "date_to": 
					if($found) $where.=" and "; else $where = " where ";
					$where .= " ".TABLE_EMAIL_ALERTS.".`date` <= '$value' ";
					$found = 1;
				break;
			}
		}

		global $appearance_settings;
		$date_format=$appearance_settings["date_format"];
		global $seo_settings;

		global $db;
		$array = $db->fetchAssocList("select ".TABLE_EMAIL_ALERTS.".*, ".TABLE_USERS.".username, date_format(".TABLE_EMAIL_ALERTS.".`date`,'$date_format') as `date_nice` from ".TABLE_EMAIL_ALERTS." left join ".TABLE_USERS." on ".TABLE_USERS.".id=".TABLE_EMAIL_ALERTS.".user_id ".$where." order by ".TABLE_EMAIL_ALERTS.".`$order` $order_way limit ".$start.", ".$no_per_page);

		$result = array();
		$i=0;
		$al = new alerts();
		foreach($array as $row) {

			$result[$i] = $row;
			$search_array = $al->searchToArray($row['search']);
			$search=$al->makeSearchString($search_array);
			$result[$i]['search_str'] = $search;
			$i++;

		}

		$join_users = "";
		if(strstr($where, TABLE_USERS)) 
			$join_users = "left join ".TABLE_USERS." on ".TABLE_USERS.".id=".TABLE_EMAIL_ALERTS.".user_id ";

		$no_alerts = $db->fetchRow("select count(*) from ".TABLE_EMAIL_ALERTS." $join_users ".$where);
		$this->setNoAlerts($no_alerts);

		return $result;

	}

	function noAlerts() {

		return $this->no_alerts;

	}

	function setNoAlerts($no_alerts) {

		$this->no_alerts = $no_alerts;

	}

}
?>
