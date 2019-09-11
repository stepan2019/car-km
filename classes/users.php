<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class users {

	var $id;
	var $error;
	var $login_error;
	var $clean;
	var $array;
	var $tmp;
	var $last;

	public function __construct($id=0)
	{
	
		global $db;
		if($id) {
			$this->id=$id;
			$this->array=array();
			$this->array=$db->fetchAssoc('select * from '.TABLE_USERS.' where id='.$id);

			foreach ($this->array as $key=>$value) $this->array[$key] = cleanStr($value);

		}
		$this->error = array();
	}

	function getId() {
		return $this->id;
	}

	function delete($id) {

		global $db,  $config_abs_path, $config_demo, $settings;
		if($config_demo==1) return;

		if(!$id || !is_numeric($id)) return;
		$res_del=$db->query('delete from '.TABLE_USERS.' where id="'.$id.'"');
		// delete in case the user is an affiliate
		$res_del=$db->query('delete from '.TABLE_AFFILIATES.' where id="'.$id.'"');

		// delete login history
		if($settings['enable_username'])
			$aname=users::getUsername($id);
		else 
			$aname = users::getEmail($id);
		auth::deleteLoginHistory(escape($aname));

		// delete user listings
		require_once $config_abs_path."/classes/listings.php";
		require_once $config_abs_path."/classes/pictures.php";
		$listings=new listings();
		$listings->deleteUser($id);


		//delete users packages
		users_packages::deleteUser($id);

		global $ads_settings;
		if($ads_settings['saved_searches_enabled']) {
			require_once $config_abs_path."/classes/saved_searches.php";
			saved_searches::deleteUser($id);
		}

		// action 
		require_once $config_abs_path."/classes/actions.php";
		actions::deleteUser($id);

		// delete discounts 
		require_once $config_abs_path."/classes/coupons.php";
		coupons::deleteUser($id);

		// delete from TABLE_OPTIONS
		$res_del=$db->query("delete from ".TABLE_OPTIONS." where `object_id`='$id' and `option`='store'");

		// !!! delete files or images from custom fields

		$slug = new slugs();
		$slug->deleteUser($id);
		
		// add hook
		do_action("delete_user", array($id));

	}

	function deleteUsers($group) {

		global $db;
		$arr = $db->fetchRowList("select id from ".TABLE_USERS." where `group`=$group");
		foreach ($arr as $row) $this->delete($row);
		return 1;

	}

	function Enable($id) {

		global $db;
		global $config_demo;
		if($config_demo==1) return;

		$array = $db->fetchAssoc("select ".TABLE_USERS.".*, (".TABLE_USERS.".active=0 && ".TABLE_USER_GROUPS.".admin_verification=1 ) as pending from ".TABLE_USERS." left join ".TABLE_USER_GROUPS." on ".TABLE_USERS.".`group` = ".TABLE_USER_GROUPS.".`id` where ".TABLE_USERS.".`id` =$id");

		$res=$db->query('update '.TABLE_USERS.' set active=1 where id="'.$id.'"');

		// SEND EMAIL IF THE USER REQUIRES ADMIN VERIFICATION
		if($array['pending']) {

			global $config_abs_path;
			require_once $config_abs_path."/classes/mails.php";
			require_once $config_abs_path."/classes/mail_templates.php";

			$mail2send=new mails();
			global $settings;
			$mail2send->init($array['email'], $array[$settings['contact_name_field']]);

			$array_subject = array();

			$array_message = array("user"=>$array, "username"=>$array['username'], "contact_name"=>$array[$settings['contact_name_field']],  "email"=>$array['email'], "enable_username" => $settings['enable_username']);

			$email_template = "registration";
			
			if($array['affiliate']) {

				// get affiliate information
				$affiliate_info = $db->fetchAssoc("select * from ".TABLE_AFFILIATES." where id=$id");

				// generate affiliate link
				$seo = new seo();
				$affiliate_link = $seo->makeAffiliateLink($id, $affiliate_info['affiliate_id']);

				$array_subject["affiliate_id"] = $affiliate_info['affiliate_id'];
				$array_subject[ "affiliate_link"] = $affiliate_link;

				$array_message["affiliate_id"] = $affiliate_info['affiliate_id'];
				$array_message[ "affiliate_link"] = $affiliate_link;

			} 

			$mail2send->composeAndSend($email_template, $array_message, $array_subject);

		}
	}

	function enableStore($id) {

		global $db;
		$db->query("update ".TABLE_USERS." set `store` = 1 where `id`='$id'");

		global $ads_settings;
		$days_expires = $ads_settings['store_availability'];
		$timestamp = date("Y-m-d H:i:s");

		if($days_expires)	
				$str_expires = " `date_expires` = date_add('$timestamp', interval '$days_expires' day)";
		else 
				$str_expires = " `date_expires` = ''";

		$db->query("delete from ".TABLE_OPTIONS." where `object_id` = $id and `option` like 'store'");

		$db->query("insert into ".TABLE_OPTIONS." set `object_id` = '$id', `option` = 'store', `date_added` = '$timestamp', $str_expires ");

		$this->generateDealerSubdomain($id);
		
		return 1;
	}

	function disableStore($id) {

		global $db;
		$db->query("update ".TABLE_USERS." set `store` = 0 where `id`='$id'");
		return 1;
	}

	function enablePendingStore($id) {

		global $db;
		global $lng;
		global $ads_settings, $settings;
		$days_expires = $ads_settings['store_availability'];
		$timestamp = date("Y-m-d H:i:s");
		if($days_expires) $str_expires = "`date_expires` = date_add('$timestamp', interval '$days_expires' day)";
		else $str_expires = "`date_expires` = ''";

		$db->query("update ".TABLE_USERS." set `store` = 1 where `id`='$id'");

		$db->query("delete from ".TABLE_OPTIONS." where `object_id` = $id and `option` like 'store'");

		$db->query("insert into ".TABLE_OPTIONS." set `object_id` = '$id', `option` = 'store', `date_added` = '$timestamp', $str_expires ");

		$db->query("update ".TABLE_ACTIONS." set pending=0 where type='store' and `user_id` = $id");

		$this->generateDealerSubdomain($id);
		
		global $config_abs_path;
		require_once $config_abs_path."/classes/mails.php";
		require_once $config_abs_path."/classes/mail_templates.php";

		// get user details
		$user = new users();
		$user_details = $user->getUser($id);
		$username = $user_details['username'];
		$user_email = $user_details['email'];

		$user_contact = $user_details[$settings['contact_name_field']];
		if(!$user_contact) $user_contact = $username;

		// send email
		$mail2send=new mails();
		$mail2send->init($user_email, $user_contact);

		$array_subject = array();

		$array_message = array("id" => $id, "username"=>$username, "contact_name" => $user_contact, "days" => $ads_settings['store_availability'], "admin_activated"=> 1, "status" => $lng['general']['active']);

		$mail2send->composeAndSend("buy_store_status", $array_message, $array_subject);

		return 1;
	}

	function enableBulkUploads($id) {

		global $db;
		$db->query("update ".TABLE_USERS." set `bulk_uploads` = 1 where `id`='$id'");
		return 1;
	}

	function disableBulkUploads($id) {

		global $db;
		$db->query("update ".TABLE_USERS." set `bulk_uploads` = 0 where `id`='$id'");
		return 1;
	}

	function Disable($id) {

		global $db;
		global $config_demo;
		if($config_demo==1) return;

		$res=$db->query('update '.TABLE_USERS.' set active=0, `activation`="" where id="'.$id.'"');

	}
	
	function Block($id) {

		global $db;
		global $config_demo;
		if($config_demo==1) return;

		$ip=users::getIp($id);
		
		$bi = new blocked_ips();
		$bi->add($ip, "Blocked user");
		

	}

	function Unblock($id) {

		global $db;
		global $config_demo;
		if($config_demo==1) return;

		$ip=users::getIp($id);
		$res=$db->query('delete from '.TABLE_BLOCKED_IPS.' where ip like "'.$ip.'"');

	}

	function activate_link($aname, $activation) {

		global $db, $lng, $settings;
		global $modules_array;

		if($settings['enable_username']) $field="username"; else $field = "email";

		if(in_array("suspend_user", $modules_array)) $str_extra=", `suspended`, `date_unsuspend`";
		$res=$db->query("select `id`, `active`, `group`".$str_extra." from ".TABLE_USERS." where $field like '$aname'");

		if(!$db->numRows($res)) { $this->addError('', $lng['users']['errors']['invalid_account_or_activation']); return 0; }

		$result = $db->fetchAssoc();

		if($result['active']==1) { $this->addError('', $lng['users']['errors']['account_already_active']); return 0; }

		if(in_array("suspend_user", $modules_array) && $result['suspended']==1) {
			// if banned
			if(!$result['date_unsuspend'])
				{ 
					$this->addError('', $lng['login']['errors']['banned_account']); 
					return 0; 
				}
			// if suspeneded
			else {
				global $appearance_settings;
				$date_format=$appearance_settings["date_format"];
				$suspended_until=$db->fetchRow("select date_format(date_unsuspend,'".$date_format."') from ".TABLE_USERS." where `$field` like '$aname'");
				$this->addError('', $lng['login']['errors']['suspended_account'].$suspended_until); 
				return 0;
			}
		}
		
		$res=$db->query("select id from ".TABLE_USERS." where $field like '$aname' and activation like '$activation'");

		if(!$db->numRows($res)) { $this->addError('', $lng['users']['errors']['invalid_account_or_activation']); return 0; }

		require_once "classes/groups.php";
		// check first if administrator verification is on
		if (groups::getAdminVerification($result['group'])) {

			// send mail to admin
			$status = $lng['users']['waiting_admin_activation'];

			$mail2send=new mails();
			$mail2send->init();

			$user = users::getUserInfo($result['id']);

			$array_subject = array("user" => $user);

			$array_message = array("user" => $user, "email"=>$user['email'], "contact_name"=>$user[$settings['contact_name_field']], "admin_verification" => 1, "status" => $status, "group" => groups::getName($result['group']), "enable_username" =>$settings['enable_username']);

			if($settings['enable_username']) { 
				$array_subject["username"] = $user["username"];
				$array_message["username"] = $user["username"];
			}


			$mail2send->composeAndSend("admin_new_account", $array_message, $array_subject);

			$res=$db->query("update ".TABLE_USERS." set `activation`='' where $field like '$aname'");
			return -1;

		}

		$res=$db->query("update ".TABLE_USERS." set active=1, `activation`='' where $field like '$aname'");

		return 1;

	}

	static function getUsername($id='') { 

		global $db;
		$uname=$db->fetchRow('select `username` from '.TABLE_USERS.' where id="'.$id.'"');
		return $uname; 
	}

	function getPassHash($id='') { 

		global $db;
		if(!$id) $id=$this->id;
		$pass=$db->fetchRow('select password from '.TABLE_USERS.' where id="'.$id.'"');
		return $pass; 
	}

	static function getUserId($username, $identity='') {
		
		global $db;
		$str = "";
		if($identity) $str = " and `identity`='$identity'";
		$id=$db->fetchRow('select id from '.TABLE_USERS.' where `username` like "'.$username.'"'.$str);
		if(!$id) 
		$id=$db->fetchRow('select id from '.TABLE_USERS.' where `email` like "'.$username.'"'.$str);
		if(!$id) return 0;
		return $id;

	}

	function getGroup($id) {
		
		global $db;
		$id=$db->fetchRow("select `group` from ".TABLE_USERS." where id=$id");
		if(!$id) return 0;
		return $id;

	}

	static function getIp($id) {
		
		global $db;
		$id=$db->fetchRow("select `ip` from ".TABLE_USERS." where id=$id");
		if(!$id) return 0;
		return $id;

	}

	function getStoreBanner($id){

		global $db;
		$banner=$db->fetchRow("select `store_banner` from ".TABLE_USERS." where id=$id");
		return $banner;

	}

	function allowStoreBanner($id){

		global $db;
		$allow=$db->fetchRow("select `store` from ".TABLE_USERS." where id=$id");
		return $allow;

	}

	function getGroupName($id) {
		
		global $db;
		global $crt_lang;

		$group_name=$db->fetchRow("select ".TABLE_USER_GROUPS."_lang.`name` from ".TABLE_USERS." left join ".TABLE_USER_GROUPS."_lang on ".TABLE_USER_GROUPS."_lang.`id`=".TABLE_USERS.".`group` where ".TABLE_USERS.".id=$id and lang_id='$crt_lang'");
		if(!$group_name) return 0;
		return $group_name;

	}

	static function getEmail($id='') { 

		global $db;
		if(!$id) return;
		$email=$db->fetchRow('select email from '.TABLE_USERS.' where id="'.$id.'"');
		return $email; 
	}

	static function getContactName($id) { 

		global $db, $settings;
		if(!$id) return;

		$name=$db->fetchRow("select `{$settings['contact_name_field']}` from ".TABLE_USERS." where id='".$id."'");
		return cleanStr($name); 
	}

	function getContactData($id) { 

		global $db, $settings;
		if(!$id) $id=$this->id;

		$contact_str = "";
		if($settings['contact_name_field'] !="username" && $settings['contact_name_field'] !="email") $contact_str=", `{$settings['contact_name_field']}`";

		$result=$db->fetchAssoc("select `username`, `email`".$contact_str.", `language` from ".TABLE_USERS." where id='".$id."'");
		if(!$result) return 0;
		foreach ($result as $key=>$value) $result[$key] = cleanStr($result[$key]);
		return $result; 
	}

	function getUserByUsername($username) {

		global $db;
		$id=$db->fetchRow('select `id` from '.TABLE_USERS.' where username like "'.$username.'"');
		if(!$id) return 0;
		$result = $this->getUser($id);
		return $result; 

	}

	static function getUserInfo($id) {
		
		global $db;
		$result=$db->fetchAssoc("select * from ".TABLE_USERS." where id='$id'");
//???!!!!!!!!cleanStr???
		return $result;
	}

	function getUser($id, $not_formatted=0) {
		
		global $db;
		global $appearance_settings, $settings;
		$date_format=$appearance_settings["date_format"];

		$result=$db->fetchAssoc("select *, ".TABLE_USERS.".`ip` as register_ip, ".TABLE_USERS.".`id` as id, date_format(`registration_date`,'$date_format') as date_nice, ( ".TABLE_BLOCKED_IPS.".ip is not null ) as blocked from ".TABLE_USERS." left join ".TABLE_BLOCKED_IPS." on ".TABLE_USERS.".ip = ".TABLE_BLOCKED_IPS.".ip where ".TABLE_USERS.".id='$id'");

		if(!$result) return 0;

		foreach ($result as $key=>$value) { 
			$result[$key] = cleanStr($result[$key]);
			if($key=="sections") $result[$key] = unserialize($result[$key]);
		}

		if($result[$settings['contact_name_field']]) $result['url_name']=_urlencode($result[$settings['contact_name_field']]);
		else $result['url_name']=_urlencode($result['username']);

		if($result['store']) { 
			$result['store_expires_nice'] = $db->fetchRow("select date_format(`date_expires`,'$date_format') from ".TABLE_OPTIONS." where `object_id` = $id and `option` = 'store'");
		} else {
			$act = $db->fetchAssoc("select * from ".TABLE_ACTIONS." where `type` like 'store' and `user_id` = '$id' order by `date` desc limit 1" );
			if($act) $result['store_pending'] = $act['pending'];

		}

		if(isset($result['user_info']) && $result['user_info']) $result['user_info_formatted']= str_replace("\n", "<br>", $result['user_info']);

		// if affiliate
		if($result['affiliate']) {
			$affiliate_info = users::getAffiliateInfo($id);
			$result['affiliate_id'] = $affiliate_info['affiliate_id'];
			$result['affiliate_paypal_email'] = $affiliate_info['affiliate_paypal_email'];
			$seo = new seo();
			$result['affiliate_link'] = $seo->makeAffiliateLink($id, $affiliate_info['affiliate_id']);
		}

		// special fields
		global $config_abs_path;
		require_once $config_abs_path."/classes/fields.php";	
		require_once $config_abs_path."/classes/depending_fields.php";	

		$f = new fields("uf");
		$fields = $f->getFieldsArray($result['group'], 0);
		$this->setFields($fields);

		foreach ($fields as $field) {

		$fname = $field['caption'];

		if($field['type'] == "checkbox_group" || $field['type'] == "multiselect") { 

			$result[$fname] = explode("|",$result[$fname]);

		}
		else if($field['type'] == "date") {

			if($result[$fname] && $result[$fname]!='0000-00-00') {
				$result['vis'][$fname] = format_date_str($result[$fname], $field['date_format']);
			} else $result[$fname] = '';

		} // end if date
		else if( $field['type']=="youtube" && $result[$fname]) {

			global $config_abs_path;
			require_once $config_abs_path."/include/patterns.php";
			$result["not_formatted"][$fname] = $result[$fname];
			$result[$fname] = formatVideo($result[$fname]);

		}

		if(!$not_formatted && isset($result[$fname]) && $result[$fname]) {

		// format numeric fields
		if( $field['type'] != "depending" && $field['validation_type'] == "numeric"){

			$result[$fname] = format_numeric($result[$fname]);

		}
		else if( isset($field['is_numeric']) && $field['is_numeric']){

				$result[$fname] = format_numeric_field($fname, $result[$fname], $field['extensions']);

		} else if ($field['type']=="textarea") {

			$result[$fname] = str_replace("\n", "<br/>", $result[$fname]);

		} 
		
		} // end if not formatted

		} // foreach 

		global $crt_lang;
		if($result['language']!=$crt_lang) {
			$language = new languages;
			$result = $language->translateFieldsElements($result, "uf");
		}

		do_action("get_user", array(&$result));
		
		return $result;

	}

	static function getNoListings($id) {
		
		global $db;
		$no=$db->fetchRow('select count(*) from '.TABLE_ADS.' where user_id="'.$id.'"');
		return $no;

	}

	function count($group='') {
	
		global $db;
		$where = "";
		if($group) $where = " where `group` = $group";
		$no=$db->fetchRow('select count(*) from '.TABLE_USERS.$where);
		return $no;

	}


	function getNo($search='') {
	
		global $db;
		$where="";
		if($search!="") { 
			$where .= " where username like '%$search%'";
		}
		$no=$db->fetchRow('select count(*) from '.TABLE_USERS.$where);
		return $no;

	}

	function getNoModerators() {

		global $db;
		$no=$db->fetchRow('select count(*) from '.TABLE_USERS." where `moderator` = 1");
		return $no;

	}


	function getNoAffiliates() {

		global $db;
		$no=$db->fetchRow('select count(*) from '.TABLE_USERS." where `affiliate` = 1");
		return $no;

	}

	function getNoActive() {
	
		global $db;
		$no=$db->fetchRow('select count(*) from '.TABLE_USERS.' where active=1');
		return $no;

	}

	function getUsersWithAds() {
	
		global $db;

		$res=$db->query("SELECT DISTINCT(`user_id`) FROM `".TABLE_ADS."` WHERE `user_id` >0;");
		return $db->numRows($res);

	}

	function getUsersWithStore() {
	
		global $db;
		$no=$db->fetchRow("select count(*) from ".TABLE_USERS." where `store` = 1;");
		return $no;

	}

	function getUsersWithBulkUploads() {
	
		global $db;
		$no=$db->fetchRow("select count(*) from ".TABLE_USERS." where `bulk_uploads` = 1;");
		return $no;

	}

	function getNoInactive() {
	
		global $db;
		$no=$db->fetchRow('select count(*) from '.TABLE_USERS.' where active=0');
		return $no;

	}

	function getLatestUsers($no) {

		global $db;
		global $appearance_settings;
		$date_format=$appearance_settings["date_format"];

		$sql='select username, email, id, date_format(registration_date,"'.$date_format.'") as `date` from '.TABLE_USERS.' order by registration_date desc limit '.$no;

		$array_users=$db->fetchAssocList($sql);
		$i = 0;
		foreach($array_users as $row) {
			$array_users[$i]['listings'] = listings::getNoListings($row['id']);
			$i++;
		}
		return $array_users;
	}

	function getNoPages($no_per_page) {
	
		$total=$this->count();
		if($total==0) return 1;
		return ceil($total/$no_per_page);

	}

	// for users only
	// for drop down boxes to select the user to post ads for
	function getAll($group='') {

		global $db, $settings;
		$where="";
		if($group) $where=" where `group` = $group";

		if($settings['enable_username']) { $usr_field="`username`"; $field="username"; } else { $usr_field="`email` as `username`"; $field="email"; }

		$array=$db->fetchAssocList("select `id`, $usr_field,  `{$settings['contact_name_field']}` from ".TABLE_USERS." $where order by $field");
		return $array;

	}

	function getLoginHistory($page,$no_per_page, $search='') {

		global $db, $settings;
		$start=($page-1)*$no_per_page;

		if($settings['enable_username']) $field="username"; else $field="email";

		if($search!='') $where=" where `$field` like '%$search%'"; else $where="";
		$sql = "select $field from ".TABLE_USERS." $where order by $field asc limit $start, $no_per_page";

		$array=$db->fetchRowList($sql);

		$i=0;
		$array_users=array();
		$auth=new auth();

		// get admin user
		if($page==1 && !$search) {
			global $settings;
			$admin_username=$settings["admin_".$field];
			$array_users[$i][$field]=$admin_username;
			$login_info=$auth->getLastAdminLogin();
			$array_users[$i]['login_info']=$login_info;
			$i++;
		}
	
		foreach ($array as $result) {
			$array_users[$i][$field]=$result;
			$login_info=$auth->getLastLogin($result);
			$array_users[$i]['login_info']=$login_info;
			$i++;
		}
		return $array_users;

	}

	function searchUsers($post_array, $page,$no_per_page,$order,$order_way) {

		global $db;
		global $lng;
		global $crt_lang;
		global $appearance_settings;
		$date_format=$appearance_settings["date_format"];

		$start=($page-1)*$no_per_page;

		//$where = " where ".TABLE_USERS.".`affiliate` = 0 ";

		global $db;
		$user_fields = $db->getTableFields(TABLE_USERS);

		$where='';
		foreach($post_array as $key=>$value) {
			if( !$key || empty($value) ) continue;
			
			switch($key) {
				case "id": 
				case "group":
				case "active": 
					$where.=" where ".TABLE_USERS.".`$key` = '$value' ";

				break;

				case "username": 
				case "contact_name": 
				case "email": 
				case "ip": 

					$where .= " where ".TABLE_USERS.".`$key` like '$value' ";

				break;
				case "inactive": 
					$where.=" where ".TABLE_USERS.".`active` = '0' ";

				break;
				case "date_from": 

					$where .= " where ".TABLE_USERS.".`registration_date` >= '$value' ";

				break;

				case "date_to": 

					$where .= " where ".TABLE_USERS.".`registration_date` <= '$value' ";

				break;

				case "only_moderators": 

					$where .= " where ".TABLE_USERS.".`moderator` = 1 ";

				break;

				case "only_stores": 

					$where .= " where ".TABLE_USERS.".`store` = 1 ";

				break;

				default: 

					if( in_array($key, $user_fields) ) {
 						$where .= " where ".TABLE_USERS.".`$key` = '$value' ";
					}
				break;
			}
		}

		$no_users = $this->getNoSearchUsers($where);
		$this->setNoUsers($no_users);

		$group = ''; $join_ads = ''; $no_ads = '';
		if($order=="listings") {
			$group = "group by ".TABLE_USERS.".id";
			$join_ads = "left join ".TABLE_ADS." on ".TABLE_USERS.".id=".TABLE_ADS.".user_id ";
			$no_ads = " count(".TABLE_ADS.".user_id) as listings,";
		}

		$sql="select ".TABLE_USERS.".*, date_format(registration_date,'".$date_format."') as date, $no_ads (".TABLE_USER_GROUPS.".admin_verification=1 and ".TABLE_USERS.".active=0) as pending, ".TABLE_ACTIONS.".`invoice` from ".TABLE_USERS." 
$join_ads
left join ".TABLE_USER_GROUPS." on ".TABLE_USERS.".`group`=".TABLE_USER_GROUPS.".`id` 
left join ".TABLE_ACTIONS." on ".TABLE_USERS.".id=".TABLE_ACTIONS.".`user_id` and ( ".TABLE_ACTIONS.".`type` = 'store' )
$where 
$group order by `".$order."` ".$order_way." limit ".$start.", ".$no_per_page;
//echo $sql;	

		$array=$db->fetchAssocList($sql);

		global $config_abs_path;
		require_once $config_abs_path."/classes/blocked_ips.php";
		require_once $config_abs_path."/classes/blocked_emails.php";
		require_once $config_abs_path."/classes/blocked_phones.php";
		require_once $config_abs_path."/classes/fields.php";
		
		$i=0;
		$array_users=array();
		$f = new fields("uf");
		foreach ($array as $result) {
			
			foreach ($result as $key=>$value) $array_users[$i][$key] = cleanStr($result[$key]);

	    		if(isset($array_users[$i]['user_info']) && $array_users[$i]['user_info']) $array_users[$i]['user_info_formatted']= str_replace("\n", "<br>", $array_users[$i]['user_info']);

	    	if($result['no_credits']>0) $array_users[$i]['no_credits'] = format_numeric($result['no_credits'], 2);

			if($order!="listings") $array_users[$i]['listings'] = listings::getNoListings($result['id']);

			// blocked users
			$array_users[$i]['ip_blocked'] = blocked_ips::isBlocked($array_users[$i]['ip']);
			$array_users[$i]['email_blocked'] = blocked_emails::isBlocked($array_users[$i]['email']);

			// ---------- blocked phones --------------
			$where_str='';
			if($result['group']) $where_str .= " and ( (`groups` REGEXP '\[\[:<:\]\]{$result['group']}\[\[:>:\]\]' and `groups`!=-1)  or `groups` = 0 )";

			$f = new fields("uf");
			$phone_fields = $f->getFieldsOfTypeShort("phone", $where_str);
			
			$array_phones = array();
			$j=0;

			foreach($phone_fields as $p) {

				$phone = $this->getFieldValue($result['id'],$p['caption']);
				if($phone) {
					$array_phones[$j] = $phone;
					$j++;
				}
			}
			
			$array_users[$i]['blocked_phones'] = array();
			$j=0;
			foreach($array_phones as $p) {
				if(blocked_phones::isBlocked($p)) { $array_users[$i]['blocked_phones'] = $p ; $j++; }
			}
			
			// ---------------- end blocked phones -----------------

			if($array_users[$i]['ip_blocked'] || $array_users[$i]['email_blocked'] || count($array_users[$i]['blocked_phones'])) $array_users[$i]['blocked'] = 1;
			else $array_users[$i]['blocked'] = 0;
			
			// get group name
			$array_users[$i]['group_name'] = $db->fetchRow("select `name` from ".TABLE_USER_GROUPS."_lang where `lang_id`= '$crt_lang' and id='{$result['group']}'");

			// pending actions
			$array_users[$i]['pending_actions'] = array();
			$array_users[$i]['pending_info'] = '';
			if($result['invoice']) { 
				$array_users[$i]['pending_actions'] = $db->fetchAssocList("select * from ".TABLE_ACTIONS." where `invoice` = ".$result['invoice']." and pending = 1");
				foreach($array_users[$i]['pending_actions'] as $action)
					if($action['type'] == "store") $array_users[$i]['pending_info'].=$lng['users']['pending_store'].'<br />';
			}

			do_action("get_users", array(&$array_users, $i));
			
			$i++;
		}

		return $array_users;

	}

	function getUsers($where, $page, $no_per_page,$order,$order_way) {

		global $db;
		global $lng;
		global $crt_lang;
		global $appearance_settings;
		$date_format=$appearance_settings["date_format"];

		$limit_str='';
		if($no_per_page>0) {
		
			$start=($page-1)*$no_per_page;
			$limit_str=" limit ".$start.", ".$no_per_page;
			
		}

		$sql="select *, date_format(registration_date,'".$date_format."') as `date` from ".TABLE_USERS." 
 $where order by `".$order."` ".$order_way.$limit_str;
//echo $sql;	

		$array=$db->fetchAssocList($sql);

		$i=0;
		$array_users=array();
		foreach ($array as $result) {
			
			foreach ($result as $key=>$value) $array_users[$i][$key] = cleanStr($result[$key]);

			$i++;
		}

		return $array_users;

	}
	
	function searchAffiliates($post_array, $page,$no_per_page,$order,$order_way) {

		global $db;
		global $lng;
		global $crt_lang;
		global $appearance_settings;
		$date_format=$appearance_settings["date_format"];

		$start=($page-1)*$no_per_page;

		$where = " where ".TABLE_USERS.".`affiliate` = 1 ";

		global $db;
		$user_fields = $db->getTableFields(TABLE_USERS);

		foreach($post_array as $key=>$value) {
			if( !$key || empty($value) ) continue;
			
			switch($key) {
				case "id": 
					$where.=" and ".TABLE_USERS.".`$key` = '$value' ";

				break;

				case "username": 
				case "contact_name": 
				case "email": 
				case "ip": 

					$where .= " and ".TABLE_USERS.".`$key` like '$value' ";

				break;
				case "date_from": 

					$where .= " and ".TABLE_USERS.".`registration_date` >= '$value' ";

				break;

				case "date_to": 

					$where .= " and ".TABLE_USERS.".`registration_date` <= '$value' ";

				break;

				default: 

					if( in_array($key, $user_fields) ) {
 						$where .= " and ".TABLE_USERS.".`$key` = '$value' ";
					}
				break;
			}
		}

		$no_users = $this->getNoSearchUsers($where);
		$this->setNoUsers($no_users);

		$sql="select ".TABLE_USERS.".*, date_format(registration_date,'".$date_format."') as date, ".TABLE_BLOCKED_IPS.".`ip` as blocked,  (".TABLE_USER_GROUPS.".admin_verification=1 and ".TABLE_USERS.".active=0) as pending, ".TABLE_ACTIONS.".`invoice` from ".TABLE_USERS." 
left join ".TABLE_BLOCKED_IPS." on ".TABLE_USERS.".`ip`=".TABLE_BLOCKED_IPS.".ip 
left join ".TABLE_USER_GROUPS." on ".TABLE_USERS.".`group`=".TABLE_USER_GROUPS.".`id` 
left join ".TABLE_ACTIONS." on ".TABLE_USERS.".id=".TABLE_ACTIONS.".`user_id` and ( ".TABLE_ACTIONS.".`type` = 'store' )
$where 
order by `".$order."` ".$order_way." limit ".$start.", ".$no_per_page;
//echo $sql;	

		$array=$db->fetchAssocList($sql);

		$i=0;
		$array_users=array();
		foreach ($array as $result) {
			
			foreach ($result as $key=>$value) $array_users[$i][$key] = cleanStr($result[$key]);

			if($array_users[$i]['blocked']) $array_users[$i]['blocked']=1;
			else $array_users[$i]['blocked']=0;

			$i++;
		}

		return $array_users;

	}

	function getNoSearchUsers($where) {

		global $db;

		$total=$db->fetchRow('select count(*) from '.TABLE_USERS.$where);

		return $total;
		
	}

	function getNoUsers() {

		return $this->no_users;

	}

	function setNoUsers($no) {

		$this->no_users = $no;

	}

	function user_exists($str, $id='') {

		global $db;
		if($id) $str_id = " and id!=".$id; else $str_id="";
		$res=$db->query("select * from ".TABLE_USERS." where username like '$str'".$str_id);
		if($db->numRows($res)) return 1;
		return 0;
	}

	function exists($id) {

		global $db;
		$res=$db->query("select * from ".TABLE_USERS." where id='$id'");
		if($db->numRows($res)) return 1;
		return 0;
	}

	function email_exists($str, $id='') {

		global $db;
		if($id) $str_id = " and id!=".$id; else $str_id="";
		$res=$db->query("select * from ".TABLE_USERS." where email like '$str'".$str_id);
		if($db->numRows($res)) return 1;
		return 0;
	}

	function getError() {
	
		return $this->error;

	}

	function addError($err_field, $err_text) {

		array_push($this->error, array("field"=> $err_field, "error" => $err_text));

	}

	function getInfo() {

		return $this->info;

	}

	function setInfo($str) {

		$this->info=$str;

	}

	function getTmp() {
	
		return $this->tmp;

	}

	function check_edit_info ($id, $mod) {

		global $lng, $is_admin, $is_mod;
		$this->tmp=array();

		if($is_admin || $is_mod) {
			global $config_demo;
			if($config_demo==1) $this->addError('', $lng['general']['errors']['demo']);
		}

		$cf = new fields_process("uf");
		$email_editable = $cf->emailEditable();
		$username_editable = $cf->usernameEditable();

		if($email_editable) {
		if(!$_POST['email']) 
			$this->addError("email", $lng['users']['errors']['email_missing']);

		else { 
			if(!validator::valid_email($_POST['email'])) 
				$this->addError("email", $lng['users']['errors']['invalid_email']);

			else if($this->email_exists(escape($_POST['email']), $id)) 
				$this->addError("email", $lng['users']['errors']['email_exists']);

			else if(!$is_admin && !$is_mod) {
			if(blocked_emails::isBlocked(escape($_POST['email'])))
				$this->addError("email", $lng['users']['errors']['email_not_permitted']);
			}
		}
		} // end if email editable

		global $settings;
		if($settings['enable_username'] && ($is_admin || $is_mod || $username_editable)) {

			if(!$_POST['username']) 
				$this->addError("username", $lng['users']['errors']['username_missing']);

			else 
			if($this->user_exists(escape($_POST['username']), $id)) 
				$this->addError("username", $lng['users']['errors']['username_exists']);

			else 
			if(escape($_POST['username'])== $settings["admin_username"])
				 $this->addError("username", $lng['users']['errors']['username_exists']);

			else 
			if(!validator::valid_username($_POST['username'])) 
				$this->addError("username", $lng['users']['errors']['invalid_username']);

			$this->tmp['username'] = cleanStr($_POST['username']);

		}

		if( isset($_POST['group']) && $_POST['group']!='') {
			$group=escape($_POST['group']);
		} 
		else 
			$group = $this->getGroup($id);

		// credits no
		if(($is_admin || $is_mod) && isset($_POST['credits']) && !is_numeric($_POST['credits'])) {
			$this->addError("credits", $lng['users']['errors']['invalid_no_credits']);
		}

		$fields = new fields_process("uf");
		$fields->setEdit(1);
		$fields->setId($id);
		$fields->check_form_fields($group);
		if($fields->getError()) 
			$this->error = array_merge($this->error, $fields->getError());

		$old_data = $this->getUser($id);
		$store = $old_data['store'];
		$store_banner = $old_data['store_banner'];

		// check for store_banner
		if($store && isset($_FILES['store_banner']['name']) && $_FILES['store_banner']['name']) {

			global $config_abs_path;
			$dir = $config_abs_path."/images/store";
			$img = new image('store_banner', $dir, "store");

			if(!$img->verify()) 
				$this->error = array_merge($this->error, $img->getError());

		}

		// is affiliate
		// check for affiliate_paypal_email
		if($old_data['affiliate'])  {

			if(empty($_POST['affiliate_paypal_email']))
				$this->addError("affiliate_paypal_email", $lng['users']['errors']['affiliate_paypal_email']);
			else if(!validator::valid_email($_POST['affiliate_paypal_email']))
				$this->addError("affiliate_paypal_email", $lng['users']['errors']['invalid_email']);

		}


		if($this->getError())
		{
			$this->tmp['id']=$id;
			if($email_editable) {
				if(isset($_POST['email'])) $this->tmp['email']=$_POST['email']; else $this->tmp['email']='';
			} else $this->tmp['email']=$old_data['email'];

			if(($is_admin || $is_mod) && isset($_POST['no_credits']) && $_POST['no_credits']) $this->tmp['no_credits']=$_POST['no_credits'];

			$this->tmp['store']=$store;
			$this->tmp['store_banner']=$store_banner;
			$this->tmp['tmp_fields']=$fields->getTmp();
		
			foreach ($this->tmp['tmp_fields'] as $key=>$value) {
				$this->tmp[$key] = $value;
			}

		}

		return 1;
	}

	function check_form ($group='') {

		global $db, $lng, $is_admin, $is_mod;
		$this->tmp=array();

		if( !$_POST['group'] || !is_numeric($_POST['group'])) $this->addError("group", $lng['users']['errors']['group_missing']);

		// credits no
		if( ($is_admin || $is_mod) && isset($_POST['credits']) && !is_numeric($_POST['credits'])) {
			$this->addError("credits", $lng['users']['errors']['invalid_no_credits']);
		}

		// read_only fields
		global $settings;
		if($settings['enable_username']) {
			if(!$_POST['username']) 
				$this->addError("username", $lng['users']['errors']['username_missing']);

			else 
			if($this->user_exists(escape($_POST['username'])))  
				$this->addError("username", $lng['users']['errors']['username_exists']);

			else 
			if(escape($_POST['username'])== $settings["admin_username"]) 
				$this->addError("username", $lng['users']['errors']['username_exists']);

			else 
			if (!validator::valid_username($_POST['username'])) 
				$this->addError("username", $lng['users']['errors']['invalid_username']);
		} // end if enable_username

		if(!$_POST['email']) 
			$this->addError("email", $lng['users']['errors']['email_missing']);

		else { 

			if(!validator::valid_email($_POST['email'])) 
				$this->addError("email", $lng['users']['errors']['invalid_email']);

			else 
			if($this->email_exists(escape($_POST['email']))) 
				$this->addError("email", $lng['users']['errors']['email_exists']);

			else 
			if(!$is_admin && !$is_mod && blocked_emails::isBlocked(escape($_POST['email'])))
				$this->addError("email", $lng['users']['errors']['email_not_permitted']);

			else 
			if(escape($_POST['email'])== $settings["admin_email"]) 
				$this->addError("email", $lng['users']['errors']['email_exists']);
		}

//		if(!$_POST['password'] || !$_POST['password1']) $this->addError("password", $lng['users']['errors']['password_missing']);
//		else if(strcmp($_POST['password'], $_POST['password1'])) $this->addError("password1", $lng['users']['errors']['passwords_dont_match']);

		global $settings;
		$captcha=$settings["register_captcha"];

		// check captcha if enabled and not administrator
		if(!$is_admin && !$is_mod && $captcha) {

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

		$fields=new fields_process('uf');
		// check custom fields
		if(!$group) {
		if( isset($_POST['group']) && $_POST['group']!='') {
			$group=escape($_POST['group']);
		} else $group = '';
		}

		if($group) {

			$fields->check_form_fields($group);
			if($fields->getError()) $this->error = array_merge($this->error, $fields->getError());

			$gr = new groups;
			$group_settings = $gr->getGroup($group);
			$store = $group_settings['store'];

			// if store >0 for the group check for store_banner
			if($store >0 && isset($_FILES['store_banner']['name']) && $_FILES['store_banner']['name'] ) {
				global $config_abs_path;
				$dir = $config_abs_path."/images/store";
				$img = new image('store_banner', $dir, "store");
				if(!$img->verify()) $this->error = array_merge($this->error, $img->getError());
			}

			// is affiliate
			// check for affiliate_paypal_email
			if($group_settings['affiliates']) 
			
				if(empty($_POST['affiliate_paypal_email']))
					$this->addError("affiliate_paypal_email", $lng['users']['errors']['affiliate_paypal_email']);
				else if(!validator::valid_email($_POST['affiliate_paypal_email']))
					$this->addError("affiliate_paypal_email", $lng['users']['errors']['invalid_email']);

			} 	 

		if($this->getError())
		{
			$array_fields = array("email");
			if($settings['enable_username']) array_push($array_fields, "username");
			if(($is_admin || $is_mod) && isset($_POST['group'])) $this->tmp['group']=$_POST['group']; else $this->tmp['group']='';
			if(($is_admin || $is_mod) && isset($_POST['no_credits'])) $this->tmp['no_credits']=$_POST['no_credits'];

			foreach($array_fields as $f)
			{
				if(isset($_POST[$f])) $this->tmp[$f]=cleanStr($_POST[$f]); else $this->tmp[$f]='';
			}

			$this->tmp['tmp_fields']=$fields->getTmp();
		
			foreach ($this->tmp['tmp_fields'] as $key=>$value) {
				$this->tmp[$key] = $value;
			}

		}

		return 1;

	}

	function check_change_password () {

		global $lng;
		global $config_demo;

		if($config_demo==1) $this->addError('', $lng['general']['errors']['demo']);
//_print_r($_POST);
		if(!$_POST['password'] || !$_POST['password1']) { 
			if(!$_POST['password']) $this->addError('password', $lng['users']['errors']['password_missing']);
			if(!$_POST['password1']) $this->addError('password1', $lng['users']['errors']['password_missing']);
		}
		else if(strcmp($_POST['password'], $_POST['password1'])) $this->addError('password', $lng['users']['errors']['passwords_dont_match']);

		return 1;
	}

	function addCreditsForGroup($user_id, $group_id) {
	
		global $db, $config_abs_path;
		require_once $config_abs_path."/classes/settings.php";
		require_once $config_abs_path."/classes/groups.php";
		$credits_enabled = settings::getCreditsEnabled();
		if(!$credits_enabled) return;

		$gr = new groups();
		$group_settings = $gr->getGroup($clean['group']);
		$no_credits = $group_settings['default_credits'];
		$db->query("update ".TABLE_USERS." set `no_credits`='$no_credits' where id='$user_id'");
		
	}
	
	function add($group='') {

		global $db, $lng, $is_admin, $is_mod, $config_abs_path;
		global $settings;

		$this->clean=array();
		if(!$group) { 
			if(isset($_POST['group']) && is_numeric($_POST['group'])) $group = $_POST['group']; 
		}
		
		if($group) $this->check_form($group); else $this->check_form();
		if($this->getError()) return 0;

		if($is_admin || $is_mod) $clean['group'] = escape($_POST['group']); 
		else $clean['group'] = $group;

		$gr = new groups();
		$group_settings = $gr->getGroup($clean['group']);
		
		require_once $config_abs_path."/classes/settings.php";
		$credits_enabled = settings::getCreditsEnabled();
		$clean['no_credits'] = 0;
		
		if($credits_enabled) {
			if(($is_admin || $is_mod) && isset($_POST['no_credits'])) $clean['no_credits'] = escape($_POST['no_credits']);
			else $clean['no_credits'] = $group_settings['default_credits'];
		}

		$array_fields = array("email");
		if($settings['enable_username']) array_push($array_fields, "username");
		foreach($array_fields as $f) {
			$clean[$f] = escape($_POST[$f]);
		}

		$password = $db->my_mysql_real_escape_string($_POST['password']);
		$clean['password'] = users::encode($password);
		$clean['registration_date']=date('Y-m-d H:i:s');
		$clean['ip']=getRemoteIp();

		$activate_via_email = $group_settings['activate_via_email'];
		$activate_via_sms = $group_settings['activate_via_sms'];
		$admin_verification = $group_settings['admin_verification'];
		if($group_settings['store']==2) $clean['store'] = 1; else $clean['store'] = 0;
		if($group_settings['bulk_uploads']==1) $clean['bulk_uploads'] = 1; else $clean['bulk_uploads'] = 0;
		$clean['store_banner'] = '';

		if($clean['store']) {
			// check for store_banner
			if(isset($_FILES['store_banner']['name']) && $_FILES['store_banner']['name']) {

				global $config_abs_path;
				$dir = $config_abs_path."/images/store";
				$img = new image('store_banner', $dir, "store");
				$img->setGenerate(1);
				$img->verify();
				if($img->upload()){
					$clean['store_banner']=$img->getUploadedFile();
				} else $clean['store_banner'] = '';
			}

		}
		
		if($is_admin || $is_mod) $clean['active']=1;
		else {
			if($activate_via_email || $activate_via_sms || $admin_verification) $clean['active']=0;
			else $clean['active']=1;
		}

		// moderator
		$clean['moderator'] = 0;
		if($is_admin) $clean['moderator'] = checkbox_value("moderator");

		// affiliate
		if($group_settings['affiliates']==1) $clean['affiliate'] = 1; else { $clean['affiliate'] = 0; $clean['affiliate_id'] = '';}


		$insert_array = array("group", "email", "password", "ip", "registration_date", "active", "store", "store_banner", "bulk_uploads", "language", "affiliate");
		if($settings['enable_username']) array_push($insert_array, "username");
		if($is_admin) array_push($insert_array, "moderator");
		if($credits_enabled) array_push($insert_array, "no_credits");

		if($group_settings['affiliates']) 
			$clean['affiliate_paypal_email'] = escape($_POST['affiliate_paypal_email']);

		global $crt_lang;
		$clean['language'] = $crt_lang;

		// build insert query string
		$sql="insert into ".TABLE_USERS." SET ";
		$i=0;
		foreach ($insert_array as $f) {
			if($i) $sql.=", ";
			$sql.="`$f` = '".$clean[$f]."'";
			$i++;
		}

		//if(($is_admin || $is_mod) && isset($clean['no_credits']) && $clean['no_credits']) $sql.=", `no_credits` = '".$clean['no_credits']."'";

		// add custom fields to query
		$fields=new fields_process('uf');
		$sql.=$fields->add_fields($clean['group']);

		if($clean['moderator']) {

			// get sections for moderators
			$sections = array("listings", "users", "subscriptions", "searches", "alerts", "messages", "orders", "security", "banners", "custom_pages", "bulk_emails", "import_export", "news", "multicurrency");
			$actions = array("view", "add", "edit", "delete");
			foreach($sections as $section) {
				foreach ($actions as $action) {
					$sections_array[$section][$action] = checkbox_value($section."_".$action);
				}
			}
			$clean['sections'] = serialize($sections_array);
			$sql.=", `sections` = '".$clean['sections']."'";

		}
//echo $sql;
		// add to database
		$res=$db->query($sql);

		$last_id=$db->insertId();
		$this->last=$last_id;
//echo "last_id=".$last_id;
		// create the affiliate id
		if($clean['affiliate']) {

			$affiliate_id = users::generateAffiliateId();
			$db->query("insert into ".TABLE_AFFILIATES." set `id`= $last_id, `affiliate_id` = '$affiliate_id', `affiliate_paypal_email`='{$clean['affiliate_paypal_email']}'");

		}

		do_action("add_user", array($clean, $last_id));
		
		$slug = new slugs();
		$cname="";
		if(isset($clean[$settings['contact_name_field']])) $cname.=$clean[$settings['contact_name_field']];
		$slug->addUser($last_id, $cname);
		
		// if admin return
		if($is_admin || $is_mod) return 1;// ???????????? send email even if admin

		// activation via email or both email and sms
		if($activate_via_email==1) { // email verification
			$this->setInfo($lng['users']['info']['activate_account']);
		}
		elseif($activate_via_sms==1) { // SMS verification
			$this->setInfo($lng['users']['info']['sms_verification']);
		}
		else if ($admin_verification) 
			$this->setInfo($lng['users']['info']['awaiting_admin_verification']);
		else 
			$this->setInfo($lng['users']['info']['welcome_user']);

		// !!! - if also verify account, do not activate the account after activation

		// get user information for emails
		$user = users::getUserInfo($last_id);

		
		require_once $config_abs_path."/classes/mails.php";
		require_once $config_abs_path."/classes/mail_templates.php";

		$contact_name = users::getContactName($last_id); 

		// make mail content
		if($activate_via_email || $activate_via_sms || $clean['active']) { //  send mail to the user only if activation is needed or the account is active
								// do not send when admin verification

			global $mail_settings;
			$html_mails=$mail_settings["html_mails"];

			// if activation via sms or both send only sms activation code
			// email verification is done because the url is sent only via email
			if($activate_via_sms==1) { 
			
				$activate_account = 2;
				global $config_live_site;
				// add activation code to db record
				$activation_code=substr(generate_random(),0,6);

				$res_act=$db->query("update ".TABLE_USERS." set activation='$activation_code' where id = '$last_id'");

				if($settings['enable_username']) $account = urlencode($clean['username']);
				else $account = urlencode($clean['email']);

				if(!$html_mails)
					$act_link=$config_live_site.'/activate_account.php?account='.$account.'&type=sms';
				else {
					$lnk=$config_live_site.'/activate_account.php?account='.$account.'&amp;type=sms';
					$act_link='<a href="'.$lnk.'">'.$lnk.'</a>';
				}

				// send SMS
				$phone_no = $user[$this->getRequiredIntlPhoneField($clean['group'])];
				if($phone_no) {
				
					global $config_abs_path;
					require_once($config_abs_path.'/classes/sms_gateways.php');

					// get default SMS gateway
					$sg = new sms_gateways();
					$default = $sg->getDefault();
					if($default) {
			
						$class_name = sms_gateways::getSMSGatewayClass($default);
						require_once($config_abs_path.'/classes/sms_verification/'.$class_name.'.php');
				
						$gcl = new $class_name;
						$sent = $gcl->send($phone_no, $activation_code, $last_id);
						// !!!!!!!!!!!!!

					}  // end if default
				
				}// end if phone no
			
			}// end if activate_account=2
			elseif($activate_via_email==1) { 

				$activate_account = 1;
				global $config_live_site;
				// add activation code to db record
				$activation_code=generate_random();

				$res_act=$db->query("update ".TABLE_USERS." set activation='$activation_code' where id = '$last_id'");

				if($settings['enable_username']) $account = urlencode($clean['username']);
				else $account = urlencode($clean['email']);

				if(!$html_mails)
					$act_link=$config_live_site.'/activate_account.php?account='.$account.'&activation='.$activation_code;
				else {
					$lnk=$config_live_site.'/activate_account.php?account='.$account.'&amp;activation='.$activation_code;
					$act_link='<a href="'.$lnk.'">'.$lnk.'</a>';
				}
			} 
			else $act_link='';

			$mail2send=new mails();
			$mail2send->init($clean['email'], $contact_name);

			// regular user or moderator
			$mail_template = "registration";

			$array_subject = array("user" => $user, "contact_name"=>$contact_name);

			$array_message = array("user" => $user, "email"=>$clean['email'], "contact_name"=>$contact_name, "password" => $password, "link" => $act_link, "activation"=> $activate_account, "admin_verification" => $admin_verification, "enable_username" =>$settings['enable_username']);

			if($activate_via_sms==1) $array_message['phone'] = $phone_no;
			
			if($settings['enable_username']) { 
				$array_subject["username"] = $clean["username"];
				$array_message["username"] = $clean["username"];
			}

			if($clean['affiliate']) {

				$seo = new seo();
				$affiliate_link = $seo->makeAffiliateLink($last_id, $affiliate_id);

				$array_subject["affiliate_id"] = $affiliate_id;
				$array_subject[ "affiliate_link"] = $affiliate_link;

				$array_message["affiliate_id"] = $affiliate_id;
				$array_message[ "affiliate_link"] = $affiliate_link;

			} 

			$mail2send->composeAndSend($mail_template, $array_message, $array_subject);

		}

		//  ---------- send email to admin --------------
		global $settings;
		if($settings['send_mail_to_admin_when_registeres'] || $admin_verification) {

			if($clean['active']) $status = $lng['general']['active'];
			else if($activate_account==2) $status = $lng['users']['waiting_sms_activation'];
			else if($activate_account==1) $status = $lng['users']['waiting_mail_activation'];
			else if($admin_verification) $status = $lng['users']['waiting_admin_activation'];
			else $status = $lng['general']['inactive'];

			$mail2send=new mails();
			$mail2send->init();

			$array_subject = array("user" => $user);

			$array_message = array("user" => $user, "email"=>$clean['email'], "contact_name"=>$contact_name, "email"=>$clean['email'], "admin_verification" => $admin_verification, "status" => $status, "group" => $group_settings['name'], "enable_username" =>$settings['enable_username']);

			if($settings['enable_username']) { 
				$array_subject["username"] = $clean["username"];
				$array_message["username"] = $clean["username"];
			}


			$mail2send->composeAndSend("admin_new_account", $array_message, $array_subject);

		}

		return 1;

	}

	function edit_info($id) {
	
		global $db, $lng;
		global $is_admin, $is_mod, $crt_usr;
		global $settings;
		$this->clean=array();

		// check if admin or logged as current user

		if($crt_usr && $crt_usr!=$id) {header("Location: not_authorized.php"); exit(0); }
		else if(!$is_admin && !$is_mod && !$crt_usr) { header("Location: not_authorized.php"); exit(0); }

		$fields=new fields_process('uf');

		if(!$is_admin && !$is_mod) {
			$email_editable = $fields->emailEditable();
			$username_editable = $fields->usernameEditable();
		}

		if($is_admin || $is_mod) $clean['group'] = escape($_POST['group']);
		else $clean['group'] = $this->getGroup($id);

		$clean['moderator'] = 0;
		if($is_admin) $clean['moderator'] = checkbox_value("moderator");


		$this->check_edit_info($id, $clean['moderator']);
		if($this->getError()) return 0;

		if($is_admin || $is_mod || $email_editable) 
			$clean['email'] = escape($_POST['email']);

		// if admin get username
		if($settings['enable_username'] && ($is_admin || $is_mod || $username_editable)) {
			$clean['username'] = escape($_POST['username']);
		}

		$clean['no_credits'] = 0;
		if(($is_admin || $is_mod) && isset($_POST['no_credits'])) $clean['no_credits'] = escape($_POST['no_credits']);

		$gr = new groups();
		$group_settings = $gr->getGroup($clean['group']);
		$store = $this->allowStoreBanner($id);
		$clean['store_banner'] = '';
		if($store) {
			// check for store_banner
			if(isset($_FILES['store_banner']['name']) && $_FILES['store_banner']['name']) {
				global $config_abs_path;
				$dir = $config_abs_path."/images/store";
				$img = new image('store_banner', $dir, "store");
				$img->setGenerate(1);
				$img->verify();
				if($img->upload()){
					$clean['store_banner']=$img->getUploadedFile();
				} else $clean['store_banner'] = '';
			}

		}

		if($is_admin || $is_mod) {
			$update_array = array("group", "email", "language", "affiliate");
			if($settings['enable_username']) array_push($update_array, "username");
		}
		else { 
			$update_array = array("group", "language", "affiliate");
			if($settings['enable_username'] && $username_editable) array_push($update_array, "username");
			if($email_editable) array_push($update_array, "email"); 
		}

		if($is_admin)	array_push($update_array, "moderator");

		// affiliate
		if($group_settings['affiliates']==1) { 
			$clean['affiliate'] = 1; 
			$clean["affiliate_paypal_email"] = escape($_POST["affiliate_paypal_email"]);
		}
		else { 
			$clean['affiliate'] = 0;
		}

		global $crt_lang;
		$clean['language'] = $crt_lang;
		
		// build insert query string
		$sql="update ".TABLE_USERS." SET ";
		$i=0;
		foreach ($update_array as $f) {
			if($i) $sql.=", ";
			$sql.="`$f` = '".$clean[$f]."'";
			$i++;
		}

		// update affiliate
		if($group_settings['affiliates']==1) {
			$exists = $db->fetchRow("select `id` from ".TABLE_AFFILIATES." where id='$id'");
			if(!$exists) {
				$affiliate_id = users::generateAffiliateId();
				$db->query("insert into ".TABLE_AFFILIATES." set `id`= $id, `affiliate_id` = '$affiliate_id', `affiliate_paypal_email`='{$clean['affiliate_paypal_email']}'");
			}
			else 
				$db->query("update ".TABLE_AFFILIATES." set `affiliate_paypal_email`='{$clean['affiliate_paypal_email']}' where id='$id'");
		}

		if($clean['moderator']) {

			$sections = array("listings", "users", "subscriptions", "searches", "alerts", "messages", "orders", "security", "banners", "custom_pages", "bulk_emails", "import_export", "news", "multicurrency");
			$actions = array("view", "add", "edit", "delete");
			foreach($sections as $section) {
				foreach ($actions as $action) {
					$sections_array[$section][$action] = checkbox_value($section."_".$action);
				}
			}
			$clean['sections'] = serialize($sections_array);
			$sql.=", `sections` = '".$clean['sections']."'";

		} 

		if(($is_admin || $is_mod) && !empty($clean['no_credits'])) $sql.=", `no_credits` = '".$clean['no_credits']."'";
		if($clean['store_banner']) $sql.=", `store_banner` = '".$clean['store_banner']."'";

		// update custom fields
		$fields->setEdit(1);
		$sql.=$fields->add_fields($clean['group']);

		$sql.=" where `id` = $id";

		$res=$db->query($sql);

		$this->setInfo($lng['users']['info']['account_info_updated']);
		
		$slug = new slugs();
		$slug->editUser($id);
		
		return 1;

	}

	function change_password($id) {
	
		global $db;
		global $lng;
		if(!$id) return 0;
		$this->clean=array();
		$this->check_change_password();

		if($this->getError()) return 0;
		$clean['password'] = users::encode(escape($_POST['password']));

		$res=$db->query('update '.TABLE_USERS.' set `password` = "'.$clean['password'].'" where id='.$id.';');
		$res=$db->query("delete from ".TABLE_USER_KEYS." where user_id='$id' and `type`=1");
		$this->setInfo($lng['users']['info']['password_changed']);
		return 1;

	}



	function validAccount($aname,$passhash) {

		global $db, $config_abs_path, $settings;
		require_once $config_abs_path."/classes/validator.php";
		global $lng;

		//if(!(isset($_SESSION['identity']) && $_SESSION['identity']) && !validator::valid_username($user)) return 0;

		if($settings['enable_username']) 

			$res=$db->query("select * from ".TABLE_USERS." where (`username` like '$aname' or `email` like '$aname') and `password` like '$passhash'");

		else 
		
			$res=$db->query("select * from ".TABLE_USERS." where `email` like '$aname' and `password` like '$passhash'");

			
		if(!$db->numRows($res)) { 
		
			if($settings['enable_username'])
				$err=$lng['login']['errors']['invalid_username_pass'];
			else 
				$err=$lng['login']['errors']['invalid_email_pass'];
		
			$this->setLoginError($err);
			return 0;
		}	
		
		$user_array = $db->fetchAssocRes($res);
		if($user_array['active']==0) {
			
			global $modules_array;
			if(in_array("suspend_user", $modules_array) && $user_array['suspended']==1) {

				// if banned
				if(!$user_array['date_unsuspend'])
					$this->setLoginError($lng['login']['errors']['banned_account']);
				// if suspeneded
				else {
					global $appearance_settings;
					$date_format=$appearance_settings["date_format"];
					$suspended_until=$db->fetchRow("select date_format(date_unsuspend,'".$date_format."') from ".TABLE_USERS." where `username` like '$aname' or `email` like '$aname'");
					$this->setLoginError($lng['login']['errors']['suspended_account'].$suspended_until);
				}

			}
			else {
				// account not activated
				$this->setLoginError($lng['login']['errors']['account_not_activated']);
			}
			return 0;
		}

		return 1;

	}

	function checkIdentity($user,$identity) {

		global $db;
//		$res=$db->query("select * from ".TABLE_USERS." where `username` like '$user' and `identity` like '$identity' and `active`=1");
		// check only identity, name can be changed!
		$res=$db->query("select * from ".TABLE_USERS." where `identity` like '$identity' and `active`=1");
		if($db->numRows($res)) return 1;
		return 0;

	}

	static function getUserPassWithIdentity($identity, $auth_provider) {

		global $db, $settings;
		if($settings['enable_username']) $field="username"; else $field="email";
		$arr=$db->fetchAssoc("select `$field`, `password` from ".TABLE_USERS." where `identity` like '$identity' and `auth_provider` like '$auth_provider' and `active`=1");
		return $arr;

	}

	function generateRecoveryKey($id, $type=1) {

		// $type=1 - password recovery
		// $type=2 - account removal
		global $db;
		$timestamp = date("Y-m-d H:i:s");
		$activation_code=generate_random();
		$res=$db->query("delete from ".TABLE_USER_KEYS." where user_id='$id' and `type`='$type'");
		$res=$db->query("insert into ".TABLE_USER_KEYS." values ('$id', '$activation_code', '$timestamp', '$type')");
		return $activation_code;

	}

	function getKeyUser($key, $type=1) {

		global $db;
		$res=$db->query("select user_id from ".TABLE_USER_KEYS." where activation like '$key' and `type`='$type'");
		if(!$db->numRows($res)) return -1;
		return $db->fetchRow();

	}

	static function getIdByEmail($email, $identity='') {

		global $db;
		$str = "";
		if($identity) $str = " and `identity`='$identity'";
		$res=$db->query("select id from ".TABLE_USERS." where email like '$email'".$str);
		if(!$db->numRows($res)) return 0;
		return $db->fetchRow();

	}


	function getTableFields() {

		global $db;

		$extra_fields = array("registration_date_formatted", "listings", "blocked", "group_name", "pending", "invoice");

		$fields = $db->getTableFields(TABLE_USERS);

		$fields = array_merge($fields, $extra_fields);

		return $fields;

	}

	function getTableCSVFields($extra = 1) {

		global $db;
		$extra_fields = array("registration_date_formatted", "listings", "blocked", "group_name", "pending", "invoice");

		$fields = $db->getTableCSVFields(TABLE_USERS);

		if(!$extra) return $fields;
		foreach ($extra_fields as $f) $fields.=",".$f;
		return $fields;

	}


	function exportUsers($type){ 

		global $db;
		global $lng;
		global $appearance_settings;
		global $ads_settings;
		$date_format=$appearance_settings["date_format"];

		$where = " where 1 = 1 ";
		if(isset($_POST[$type.'_group']) && is_numeric($_POST[$type.'_group'])) 
			$where .= " and `group` = '".$_POST[$type.'_group']."'";

		if(isset($_POST[$type.'_date_start']) && $_POST[$type.'_date_start']!='') 
			$where .= " and `registration_date` > '".escape($_POST[$type.'_date_start'])."'";

		if(isset($_POST[$type.'_date_end']) && $_POST[$type.'_date_end']!='')
			$where .= " and `registration_date` < '".escape($_POST[$type.'_date_end'])."'";

		if(isset($_POST[$type.'_last'])) $last = escape($_POST[$type.'_last']);
		else $last = '';

		if(isset($_POST[$type.'_user_order_by']) && $_POST[$type.'_user_order_by']!='') $order_by = escape($_POST[$type.'_user_order_by']);
		else $order_by = 'registration_date';

		if(isset($_POST[$type.'_user_order_way']) && $_POST[$type.'_user_order_way']!='') $order_way = escape($_POST[$type.'_user_order_way']);
		else $order_way = 'desc';

		global $crt_lang;

		$sql="select ".TABLE_USERS.".*, date_format(registration_date,'".$date_format."') as `registration_date_formatted`, count(".TABLE_ADS.".user_id) as listings, ".TABLE_BLOCKED_IPS.".`ip` as blocked, ".TABLE_USER_GROUPS."_lang.`name` as `group_name`, (".TABLE_USER_GROUPS.".admin_verification=1 and ".TABLE_USERS.".active=0) as pending, ".TABLE_ACTIONS.".`invoice` from ".TABLE_USERS." 
		left join ".TABLE_ADS." on ".TABLE_USERS.".id=".TABLE_ADS.".user_id 
		left join ".TABLE_BLOCKED_IPS." on ".TABLE_USERS.".`ip`=".TABLE_BLOCKED_IPS.".ip 
		left join ".TABLE_USER_GROUPS."_lang on ".TABLE_USERS.".`group`=".TABLE_USER_GROUPS."_lang.`id` 
		left join ".TABLE_USER_GROUPS." on ".TABLE_USERS.".`group`=".TABLE_USER_GROUPS.".`id` 
		left join ".TABLE_ACTIONS." on ".TABLE_USERS.".id=".TABLE_ACTIONS.".`user_id` and ( ".TABLE_ACTIONS.".`type` like 'store' )
		where ".TABLE_USER_GROUPS."_lang.`lang_id` = '".$crt_lang."'  
		group by ".TABLE_USERS.".id 
		order by `".$order_by."` ".$order_way;
		if($last>0) $sql .= " limit ".$last;

		return $sql;

	}


	function moveUsers($from_group, $to_group) {

		global $db;
		if(!$from_group) return;
		if(!$to_group) return;

		$db->query("update ".TABLE_USERS." set `group` = $to_group where `group` = $from_group");
		return 1;

	}

	function emptyField($crt_usr, $delete) {

		global $db;
		$db->query("update ".TABLE_USERS." set `$delete` = '' where `id`=$crt_usr");
		return 1;
	}

	function setFields($fields) {

		$this->fields = $fields;

	}

	function getFields() {

		return $this->fields;

	}

	static function getAutocomplete($term) {

		global $db, $settings;
		if($settings['enable_username']) $field="username"; else $field="email";
		$result = $db->fetchRowList("select `$field` from ".TABLE_USERS." where `$field` like '$term%' limit 10");
		return $result;
	}

	static function encode($str) {

		return md5($str);

	}

	static function getNoCredits($id) {

		global $db;
		$no_credits = $db->fetchRow("select `no_credits` from ".TABLE_USERS." where `id`='$id'");
		if(!$no_credits) return 0;
		return format_numeric($no_credits);

	}

	function removalRequest($id) {

		$activation_code = $this->generateRecoveryKey($id, 2);
		$user = $this->getUser($id);

		global $mail_settings, $settings;
		$html_mails=$mail_settings["html_mails"];

		$mail2send=new mails();
		$mail2send->init($user['email'], $user[$settings['contact_name_field']]);

		global $config_live_site;
		if(!$html_mails)
			$rem_link=$config_live_site.'/include/remove_account.php?key='.$activation_code;
		else {
			$lnk=$config_live_site.'/include/remove_account.php?key='.$activation_code;
			$rem_link='<a href="'.$lnk.'">'.$lnk.'</a>';
		}

		$array_subject = array();
		$array_message = array("removal_link"=>$rem_link, "user"=>$user, "id" =>$id);

		$mail2send->composeAndSend("account_removal", $array_message, $array_subject);

		//$info=info::getVal("password_recovery_mail_sent");

		//return $info;

	}

	function getSections($id='') { 

		global $db;
		if(!$id) $id=$this->id;
		$uname=$db->fetchRow('select `sections` from '.TABLE_USERS.' where id="'.$id.'"');
		return unserialize($uname); 
	}

	function getSectionsArray($arr) {

		$sections = array();
		$i = 0;

		foreach($arr as $key => $value) 
			if( $value['view']==1 || $value['edit']==1 || $value['add']==1 || $value['delete']==1)
				$sections[$i++] = $key;
		return $sections;

	}

	static function isModerator($aname) { 

		global $db, $settings;
		if($settings['enable_username']) $field="username"; else $field="email";
		$mod=$db->fetchRow("select `moderator` from ".TABLE_USERS." where `$field` like '".$aname."'");
		return $mod; 
	}

	static function isAffiliate($aname) { 

		global $db, $settings;
		if($settings['enable_username']) $field="username"; else $field="email";
		$aff=$db->fetchRow("select `affiliate` from ".TABLE_USERS." where `$field` like '".$aname."'");
		return $aff; 
	}

	static function generateAffiliateId() {

		global $db;

		$aff_id = rand(10000000, 99999999);
		if(users::affiliateExists($aff_id)) return users::generateAffiliateId();

		return $aff_id;

	}

	static function affiliateExists($aff_id) {

		global $db;
		$exists = $db->fetchRow("select `id` from ".TABLE_AFFILIATES." where `id` = '$aff_id'");
		return $exists;

	}

	static function getAffiliateId($id) {

		global $db;
		$aff_id = $db->fetchRow("select `affiliate_id` from ".TABLE_AFFILIATES." where id='$id'");
		return $aff_id;

	}

	static function getAffiliateInfo($id) {

		global $db;
		$aff_info = $db->fetchAssoc("select * from ".TABLE_AFFILIATES." where id='$id'");
		return $aff_info;

	}

	function getLast() {

		return $this->last;
	
	}

	function updateUserInfo($id) {

		if(!$id) return 0;
		$user_info = escape($_POST['user_info']);
		global $db;
		$db->query("update ".TABLE_USERS." set `user_info`='$user_info' where id=$id");
		return 1;

	}

	// get required international formatted phone field
	function getRequiredIntlPhoneField($group=0) {

		global $db;
		// check if a required Phone field exists 
		$phone_fields = $db->fetchAssocList("select `id`, `caption`, `groups` from ".TABLE_USER_FIELDS." where `type` like 'phone' and `required`=1 and `ext1`=1");
		$found=0;

		foreach($phone_fields as $p) {
			
			if($group!=-1 && $p['groups']=='0') { $found=$p['caption']; break; }
			if(!$group) { $found=0; continue; }
				
			$group_array = explode(",", $p['groups']);
			if(in_array($group, $group_array)) { $found=$p['caption']; break; }
			
		}

		return $found;
	
	}
	
	function getFieldValue($id,$field) {
	
		global $db;
		$val = $db->fetchRow("select `$field` from ".TABLE_USERS." where `id`='$id'");
		return $val;
	
	}
	
	function BlockEmail($id) {

		global $db;
		global $config_demo;
		if($config_demo==1) return;

		$email=users::getEmail($id);
		
		$be = new blocked_emails();
		$be->add($email, "Blocked user");
		

	}

	function UnblockEmail($id) {

		global $db;
		global $config_demo;
		if($config_demo==1) return;

		$email=users::getEmail($id);
		$res=$db->query('delete from '.TABLE_BLOCKED_EMAILS.' where `email` like "'.$email.'"');

	}

	function BlockPhone($id, $caption) {

		global $db;
		global $config_demo;
		if($config_demo==1) return;

		$phone = $db->fetchRow("select `$caption` from ".TABLE_USERS." where id='$id'");
		if(!$phone) return;

		$bp = new blocked_phones();
		$bp->add($phone, "Blocked user");
		

	}

	function UnblockPhone($id, $caption) {

		global $db;
		global $config_demo;
		if($config_demo==1) return;

		$phone = $db->fetchRow("select `$caption` from ".TABLE_USERS." where id='$id'");
		if(!$phone) return;

		$res=$db->query('delete from '.TABLE_BLOCKED_PHONES.' where `phone` like "'.$phone.'"');

	}

	static function isBlocked($id) {
	
		global $config_abs_path;
		require_once $config_abs_path."/classes/blocked_ips.php";
		require_once $config_abs_path."/classes/blocked_emails.php";
		require_once $config_abs_path."/classes/blocked_phones.php";
		require_once $config_abs_path."/classes/fields.php";
		$result = users::getUserInfo($id);
		$ip_blocked = blocked_ips::isBlocked($result['ip']);
		$email_blocked = blocked_emails::isBlocked($result['email']);
			
		// ---------- blocked phones --------------
		$where_str='';
		if($result['group']) $where_str .= " and ( (`groups` REGEXP '\[\[:<:\]\]{$result['group']}\[\[:>:\]\]' and `groups`!=-1)  or `groups` = 0 )";

		$f = new fields("uf");
		$phone_fields = $f->getFieldsOfTypeShort("phone", $where_str);
			
		$array_phones = array();
		$j=0;

		$phone_blocked=0;
		$usr = new users();
		foreach($phone_fields as $p) {

			$phone = $usr->getFieldValue($id,$p['caption']);
			if(blocked_phones::isBlocked($phone)) { $phone_blocked=1; break; }
		}
			
		if($ip_blocked || $email_blocked || $phone_blocked) return 1;
		
		return 0;

	}
	
	function hasDealerSubdomain($id) {

		global $db;
		$subdomain = $db->fetchRow("select `dealer_subdomain` from ".TABLE_USERS." where `id` = '$id'");
		return $subdomain;

	}
	
	function generateDealerSubdomain($id, $contact_name='') {
	
		global $db;
		
		if($subdomain = trim($this->hasDealerSubdomain($id))) return $subdomain;
		if(!$contact_name) $contact_name = users::getContactName($id);
		
		$subdomain = slug($contact_name);
		//echo "--".$contact_name."->".$subdomain."--<br/>";
		$exists = $db->fetchRow("select count(*) from ".TABLE_USERS." where `dealer_subdomain` like '$subdomain'");
		$i=0;
		while($exists) {
		
			$new_subdomain = $subdomain.$i;
			$i++;
			$exists = $db->fetchRow("select count(*) from ".TABLE_USERS." where `dealer_subdomain` like '$new_subdomain'");
			if(!$exists) $subdomain = $new_subdomain;
		}
	
		$db->query("update ".TABLE_USERS." set `dealer_subdomain`='$subdomain' where id='$id'");

		return $subdomain;
		
	}
	
	function getDealerId($dealer_subdomain) {
	
		global $db;
		$id = $db->fetchRow("select `id` from ".TABLE_USERS." where `dealer_subdomain` like '$dealer_subdomain'");
		if($id) return $id;
		return 0;
		
	}
	
	function getDealerSubdomain($id) {
	
		global $db;
		$subdomain = $db->fetchRow("select `dealer_subdomain` from ".TABLE_USERS." where `id` = '$id'");
		//echo "select `dealer_subdomain` from ".TABLE_USERS." where `id` = '$id'";
		return $subdomain;
		
	}

	function setLoginError($err) {
	
		$this->login_error = $err;
	
	}

	function getLoginError() {
	
		return $this->login_error;
	
	}

}
?>