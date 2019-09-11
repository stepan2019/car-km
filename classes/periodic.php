<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class periodic {

	public function __construct()
	{
	
	}



function deleteExpiredAds() {

	global $db;
	global $settings;
	$delete_expired=$settings['delete_expired'];
	$days_del_expired=$settings['days_del_expired'];

	if(!$delete_expired) return;

	// delete immediatelly
	$timestamp = date("Y-m-d H:i:s");

	if(!$days_del_expired) $sql="select id from ".TABLE_ADS." where `active`=0 and `date_expires` <= '$timestamp' and date_expires!='0000-00-00 00:00:00'";
	// delete after a time
	else $sql = "select id from ".TABLE_ADS." where active=0 and date_expires <= '$timestamp' and date_expires!='0000-00-00 00:00:00' and date_expires is not null and date_add(`date_expires`, interval '$days_del_expired' day) <= '$timestamp'";

	$listing = new listings;
	$res = $db->query($sql);
	while($l = $db->fetchRowRes($res) ) {
		$listing->delete($l);
	}

	return 1;

}

function markExpiredAds() {

	global $db;
	global $config_live_site;
	global $ads_settings, $settings;
	$send_mail=$settings['send_mail_to_user_when_expired'];

	global $mail_settings;
	$html_mails=$mail_settings['html_mails'];
	if($html_mails) $amp = "&amp;"; else $amp = "&";

	if($send_mail) {
		$mail2send=new mails();
		$mail2send->setMailTemplate("listing_expired");
	}

	global $crt_lang;
	$title_var ="`title`";
	if($ads_settings['translate_title_description']) {
		global $languages;
		if(empty($languages)) $languages = common::getCachedObject("base_languages");
		if(count($languages)>1) {
			$title_var = "`title_$crt_lang` as `title`";
		}
	}

	$timestamp = date("Y-m-d H:i:s");

	$listing = new listings;
	$result=$db->query("select ".TABLE_ADS.".id, $title_var, user_id, ".TABLE_ADS.".category_id, ".TABLE_USERS.".`username`, ".TABLE_USERS.".`email`, ".TABLE_USERS.".`{$settings['contact_name_field']}` from ".TABLE_ADS." 
	left join ".TABLE_USERS." on ".TABLE_ADS.".user_id=".TABLE_USERS.".id 
	left join ".TABLE_PACKAGES." on ".TABLE_ADS.".package_id=".TABLE_PACKAGES.".id 
	where ".TABLE_ADS.".active=1 and date_expires <= '$timestamp' and date_expires!='0000-00-00 00:00:00' and date_expires is not null;");

	while($row = $db->fetchAssocRes($result)) {

		$id = $row['id'];
		$listing->decCat($id, 1, $row['category_id']);
		$res_del=$db->query("update ".TABLE_ADS." set active=0, pending=0 where `id`='$id'");

		// remove ad options (featured, highlited priority, video) from TABLE_OPTIONS
		$this->deleteOptions($id);
		$db->query("update ".TABLE_ADS." set `featured`=0, `highlited` = 0, `urgent`=0, `priority` = 0, `sold`=0, `rented`=0 where id='$id'");

		// send email to announce expired ad
		if($send_mail==1) { 
			global $config_abs_path;
			require_once $config_abs_path."/classes/users.php";
			$user_details = listings::getUserDetails($id);
			$mail2send->init($user_details['email'], $user_details['name']);

			if(!$user_details) continue;

			$details_link = listings::makeDetailsLink($id, $user_details['key']);
			$title = cleanStr(listings::getTitle($id));

			$renew_link=$config_live_site.'/renew_listing.php?id='.$id;
			if(!$row['user_id']) $renew_link.=$amp."key=".$user_details['key'];
			if($html_mails) $renew_link='<a href="'.$renew_link.'">'.$renew_link.'</a>';

			$array_subject = array("id"=>$id, "ad_id"=>$id, "title" => $title);

			$array_message = array("id"=>$id, "ad_id"=>$id, "username"=>$user_details['username'], "contact_name"=>$user_details['name'], "nologin"=> $user_details['nologin'], "details_link"=>$details_link, "renew_link"=>$renew_link, "title" => $title);

			$mail2send->composeAndSend("", $array_message, $array_subject);

		}
	}
	return 1;
}

function notifyExpiredAds() {

	global $db;
	global $config_live_site;
	global $ads_settings, $settings, $seo_settings;

	$days_notify=$settings['days_notify'];
	if($days_notify==0) return;
	global $mail_settings;
	$html_mails=$mail_settings['html_mails'];
	if($html_mails) $amp = "&amp;"; else $amp = "&";

	global $crt_lang;
	$title_var ="`title`";
	if($ads_settings['translate_title_description']) {
		global $languages;
		if(empty($languages)) $languages = common::getCachedObject("base_languages");
		if(count($languages)>1) {
			$title_var = "`title_$crt_lang` as `title`";
		}
	}

	$mail2send=new mails();
	$mail2send->setMailTemplate("listing_will_expire");

	// send notification every day
	//$result=$db->fetchAssocList("select ".TABLE_ADS.".id, user_id from ".TABLE_ADS." left join ".TABLE_PACKAGES." on ".TABLE_ADS.".package_id=".TABLE_PACKAGES.".id where active=1 and date_add(date_added, interval no_days-$days_notify day)<'$today' and date_added!=date_add(date_added, interval no_days day);");

	// send notification only once
	$current_date = date("Y-m-d");

	$dn1 = $days_notify+1;
	$date_x_days_from_now_start = $db->fetchRow("select date_add('$current_date', INTERVAL $days_notify DAY)");
	$date_x_days_from_now_end = $db->fetchRow("select date_add('$current_date', INTERVAL $dn1 DAY)");

	$result=$db->query("select ".TABLE_ADS.".id, $title_var, user_id, ".TABLE_USERS.".`username`, ".TABLE_USERS.".`email`, ".TABLE_USERS.".`{$settings['contact_name_field']}` from ".TABLE_ADS." 
	left join ".TABLE_USERS." on ".TABLE_ADS.".user_id=".TABLE_USERS.".id 
	left join ".TABLE_PACKAGES." on ".TABLE_ADS.".package_id=".TABLE_PACKAGES.".id 
	where ".TABLE_ADS.".active=1 and date_expires between '$date_x_days_from_now_start' and '$date_x_days_from_now_end';");
// and date_added!=date_add(date_added, interval no_days day)

	while($row = $db->fetchAssocRes($result)) {

		$id = $row['id'];
		global $config_abs_path;
		require_once $config_abs_path."/classes/users.php";
		$user_details = listings::getUserDetails($id);
		$mail2send->init($user_details['email'], $user_details['name']);

		if(!$user_details) continue;

		$details_link = listings::makeDetailsLink($id, $user_details['key']);
		$title = cleanStr(listings::getTitle($id));

		$renew_link=$config_live_site.'/renew_listing.php?id='.$id;
		if(!$row['user_id']) $renew_link.=$amp."key=".$user_details['key'];
		if($html_mails) $renew_link='<a href="'.$renew_link.'">'.$renew_link.'</a>';

		$array_subject = array("id"=>$id, "ad_id"=>$id, "title" => $title);

		$array_message = array("id"=>$id, "ad_id"=>$id, "username"=>$user_details['username'], "contact_name"=>$user_details['name'], "nologin"=> $user_details['nologin'], "details_link"=>$details_link, "renew_link"=>$renew_link, "days_expire" => $days_notify, "title" => $title);

		$mail2send->composeAndSend("", $array_message, $array_subject);

	}
	return 1;
}

	function deleteUnapprovedAds() {

		global $db;

		$current_date = date("Y-m-d");

		$date_x_days_before_start = $db->fetchRow("select date_sub('$current_date', INTERVAL 11 DAY)");
		$date_x_days_before_end = $db->fetchRow("select date_sub('$current_date', INTERVAL 10 DAY)");

		$result = $db->query("select id from ".TABLE_ADS." where `active`=0 and `user_approved`=0 and `date_added` between '$date_x_days_before_start' and '$date_x_days_before_end'");

		$listing = new listings;
		while($row = $db->fetchRowRes($result)) {
			$listing->delete($row);
		}

		return 1;

	}

	function deleteOptions($id) {

		global $db;
		$db->query("delete from ".TABLE_OPTIONS." where `object_id` = '$id' and ( `option` like 'video' or `option` like 'highlited' or `option` like 'priority' or `option` like 'featured' or `option` like 'urgent' or `option` like 'url')");
		return 1;

	}

	function markExpiredOption() {

	global $db;
	global $settings;
	$send_mail=$settings['send_mail_to_user_when_expired'];
	if(!$send_mail) return 1;

	$this->markExpiredStore();
	$this->markExpiredExtraOption();

	// delete expired options from TABLE_OPTIONS
	$timestamp = date("Y-m-d H:i:s");

	$result_del=$db->query("delete from ".TABLE_OPTIONS." where date_expires <= '$timestamp' and `date_expires`!='0000-00-00 00:00:00' and `date_expires` is not null;");

	return 1;

	}

	function markExpiredStore() {

	global $db;
	global $ads_settings, $settings;

	$send_mail=$settings['send_mail_to_user_when_expired'];
	if(!$send_mail) return 1;

	$store_expires = $ads_settings['store_availability'];

	$mail2send=new mails();
	$mail2send->setMailTemplate("store_expired");

	$timestamp = date("Y-m-d H:i:s");

	$result=$db->query("select * from ".TABLE_OPTIONS." where `option` like 'store' and date_expires <= '$timestamp' and `date_expires`!='0000-00-00 00:00:00' and `date_expires` is not null order by `object_id`;");

	global $config_abs_path;
	while($row = $db->fetchAssocRes($result)) {

		require_once $config_abs_path."/classes/users.php";

		$user_id = $row['object_id'];
		$user_details = users::getUserInfo($user_id);

		$mail2send->init($user_details['email'], $user_details[$settings['contact_name_field']]);

		$array_subject = array();

		$array_message = array("id"=>$row['object_id'], "username"=>$user_details['username'], "contact_name"=>$user_details[$settings['contact_name_field']]);

		$mail2send->composeAndSend("", $array_message, $array_subject);

		// disable store
		$usr = new users;
		$usr->disableStore($user_id);

	}

	return 1;

	}

	function markExpiredExtraOption() {

	global $db;
	global $lng;

	$mail2send=new mails();
	$mail2send->setMailTemplate("ad_options_expired");

	$timestamp = date("Y-m-d H:i:s");

	$result=$db->query("select * from ".TABLE_OPTIONS." where `option` not like 'store' and date_expires <= '$timestamp' and `date_expires`!='0000-00-00 00:00:00' and `date_expires` is not null order by `object_id`;");

	$no_res = $db->numRows($result);

	$i = 0;
	while($row = $db->fetchAssocRes($result)) {
		$array_ad = array("featured", "highlited", "video", "priority", "urgent", "url");
		if(!in_array($row['option'], $array_ad)) continue;
		
		$id = $row['object_id'];

		global $config_abs_path;
		require_once $config_abs_path."/classes/users.php";
		$user_details = listings::getUserDetails($id);

		$mail2send->init($user_details['email'], $user_details['name']);

		$details_link = listings::makeDetailsLink($id, $user_details['key']);

		$title = cleanStr(listings::getTitle($id));

		$j = $i+1;
		$options_list = $lng['listings'][$row['option']];
		$options_array = array();
		$options_array[0] = $row['option'];

		// options from the same ad 
		while($j<$no_res && isset($row[$j]['object_id']) && $row[$j]['object_id']==$id) {

			$i=$j; $j++;
			$row = $result[$i];
			$options_list .= ', '.$lng['listings'][$row['option']];
			$options_array[count($options_array)] = $row['option'];
		}
		$this->removeOptions($options_array, $id);

		$array_subject = array("id"=>$row['object_id'], "title" => $title);

		$array_message = array("id"=>$row['object_id'], "username"=>$user_details['username'], "contact_name"=>$user_details['name'],  "details_link" => $details_link, "expired_options" => $options_list, "title" => $title);

		$mail2send->composeAndSend("", $array_message, $array_subject);
		$i++;

	}

	return 1;

	}

	function removeOptions($array, $id){

		global $db;
		if(!count($array)) return ;
		$str = "";
		foreach ($array as $row) {

			switch ($row) {
				case "featured":
					if($str) $str.=", ";
					$str.="`featured` = 0";
					break;
				case "priority":
					if($str) $str.=", ";
					$str.="`priority` = 0";
					break;
				case "highlited":
					if($str) $str.=", ";
					$str.="`highlited` = 0";
					break;
				case "urgent":
					if($str) $str.=", ";
					$str.="`urgent` = 0";
					break;
				case "video":
					//$str.="`video` = ''";
					break;
				case "url":
					//$str.="`site_url` = ''";
					break;
			}

		}
		
		if($str) $db->query("update ".TABLE_ADS." set $str where `id`=$id");
		return 1;
	}



function deleteUnexistingAdBased() {

	global $db;
	$result = $db->query("SELECT ".TABLE_USERS_PACKAGES.".id FROM `".TABLE_USERS_PACKAGES."` 
LEFT JOIN ".TABLE_ADS." ON ".TABLE_ADS.".usr_pkg = ".TABLE_USERS_PACKAGES.".id
LEFT JOIN ".TABLE_PACKAGES." ON ".TABLE_PACKAGES.".id = ".TABLE_USERS_PACKAGES.".package_id 
where ".TABLE_PACKAGES.".type='ad' and ".TABLE_ADS.".id is null");

	while($row = $db->fetchRowRes($result)) {
		users_packages::delete($row);
	}

}


function deleteExpiredUsrPkg($days) {

	global $db;

	$timestamp = date("Y-m-d H:i:s");

	if(!$days) $sql="select id from ".TABLE_USERS_PACKAGES." where `active`=0 and fixed=0 and `date_end` <= '$timestamp' and date_end!='0000-00-00 00:00:00'";

	else $sql = "select id from ".TABLE_USERS_PACKAGES." where active=0 and fixed=0 and date_end <= '$timestamp' and date_end!='0000-00-00 00:00:00' and date_add(`date_end`, interval '$days' day) <= '$timestamp'";

	$result = $db->query($sql);
	$up = new users_packages();
	while($row = $db->fetchRowRes($result)) {
		$up->delete($row);
	}

	return 1;
}

function markExpiredUsrPkg() {

	global $db;
	global $settings;
	$send_mail=$settings['send_mail_to_user_when_expired'];

	if($send_mail) {
		$mail2send=new mails();
		$mail2send->setMailTemplate("subscription_expired");
	}

	$timestamp = date("Y-m-d H:i:s");

	$result=$db->query("select ".TABLE_USERS_PACKAGES.".id, user_id, ads_left, (date_end!='0000-00-00 00:00:00' and date_end is not null) as expired, ".TABLE_USERS.".`username`, ".TABLE_USERS.".`email`, ".TABLE_USERS.".`{$settings['contact_name_field']}`,".TABLE_PACKAGES.".`type` from ".TABLE_USERS_PACKAGES." 
	left join ".TABLE_USERS." on ".TABLE_USERS_PACKAGES.".user_id=".TABLE_USERS.".id 
	left join ".TABLE_PACKAGES." on ".TABLE_USERS_PACKAGES.".package_id=".TABLE_PACKAGES.".id 
	where ".TABLE_PACKAGES.".`type` like 'sub' and ".TABLE_USERS_PACKAGES.".active=1 and (( date_end <= '$timestamp' and date_end!='0000-00-00 00:00:00' and date_end is not null) or `ads_left` = 0 ) ;");
	while($row = $db->fetchAssocRes($result)) {
		$id = $row['id'];
		$user_id = $row['user_id'];

		users_packages::expired($id);
		if($send_mail==1 && $user_id ) {

			if($row['expired']) $time_expired = 1;
			else $time_expired=0;

			$user_contact = $row[$settings['contact_name_field']];
			//if(!$user_contact) $user_contact=$username;

			$mail2send->init($row['email'], $user_contact);

			$array_subject = array("id" => $id);

			$array_message = array("id"=>$id, "username"=>$row['username'], "contact_name"=>$user_contact, "time_expired" => $time_expired, "subscription_id" => $id);

			$mail2send->composeAndSend("", $array_message, $array_subject);

		}
	}
	return 1;
}

function notifyExpiredUsrPkg() {

	global $db;
	global $settings;
	$days_notify=$settings['days_notify'];
	if($days_notify==0) return;

	$send_mail=$settings['send_mail_to_user_when_expired'];
	if(!$send_mail) return;

	$mail2send=new mails();
	$mail2send->setMailTemplate("subscription_will_expire");

	$current_date = date("Y-m-d");
	$dn1 = $days_notify + 1;

	$date_x_days_from_now_start = $db->fetchRow("select date_add('$current_date', INTERVAL $days_notify DAY)");
	$date_x_days_from_now_end = $db->fetchRow("select date_add('$current_date', INTERVAL $dn1 DAY)");

	// send notification
	$result=$db->query("select ".TABLE_USERS_PACKAGES.".id, user_id, ".TABLE_USERS.".`username`, ".TABLE_USERS.".`email`, ".TABLE_USERS.".`{$settings['contact_name_field']}` from ".TABLE_USERS_PACKAGES." 
	left join ".TABLE_USERS." on ".TABLE_USERS_PACKAGES.".user_id=".TABLE_USERS.".id 
	left join ".TABLE_PACKAGES." on ".TABLE_USERS_PACKAGES.".package_id=".TABLE_PACKAGES.".id 
	where ".TABLE_USERS_PACKAGES.".active=1 and date_end!='0000-00-00 00:00:00' and date_end is not null and date_end between '$date_x_days_from_now_start' and '$date_x_days_from_now_end';");

	while($row = $db->fetchAssocRes($result)) {

		$id = $row['id'];
		$user_id = $row['user_id'];

		if($user_id) {

			$user_contact = $row[$settings['contact_name_field']];
			//if(!$user_contact) $user_contact=$username;

			$mail2send->init($row['email'], $user_contact);

			$array_subject = array( "id" => $id );

			$array_message = array("id"=>$id, "username"=>$row['username'], "contact_name"=>$user_contact, "days_expire" => $days_notify, "subscription_id" => $id);

			$mail2send->composeAndSend("", $array_message, $array_subject);

		}

	}
	return 1;
}

	function deleteUnapprovedUsrPkg($days) {

		global $db;

		$current_date = date("Y-m-d");

		$dn1 = $days+1;
		$date_x_days_before_start = $db->fetchRow("select date_sub('$current_date', INTERVAL $dn1 DAY)");
		$date_x_days_before_end = $db->fetchRow("select date_sub('$current_date', INTERVAL $days DAY)");

		$result = $db->query("select id from ".TABLE_USERS_PACKAGES." where `active`=0 and `user_approved`=0 and `date_start` between '$date_x_days_before_start' and '$date_x_days_before_end'");

		while($row=$db->fetchRowRes($result)) {
			users_packages::delete($row);
		}

		return 1;

	}

	function cleanRecoveryKeys() {

		global $db;

		$current_date = date("Y-m-d");

		$two_days_ago = $db->fetchRow("select date_sub('$current_date', INTERVAL 2 DAY)");
		$one_day_ago = $db->fetchRow("select date_sub('$current_date', INTERVAL 1 DAY)");

		$res=$db->query("delete from ".TABLE_USER_KEYS." where `date` between '$two_days_ago' and '$one_day_ago'");
		return 1;

	}

	function cleanAuthHistory() {
	
		global $db;
		global $settings;
		$current_date = date("Y-m-d");

		$days=$settings['delete_login_older_than'];
		$dn1 = $days+1;

		$date_x_days_ago_start = $db->fetchRow("select date_sub('$current_date', INTERVAL $dn1 DAY)");
		$date_x_days_ago_end = $db->fetchRow("select date_sub('$current_date', INTERVAL $days DAY)");


		$db->query("delete from ".TABLE_LOGIN_HISTORY." where date_login between '$date_x_days_ago_start' and '$date_x_days_ago_end'");
		return 1;

	}


	function periodicSitemap() {

		global $db;
		global $config_abs_path;
		require_once $config_abs_path."/classes/config/sitemap_config.php";
		require_once $config_abs_path."/classes/categories.php";
		$sitemap = new sitemap();
		$sett = $sitemap->getAll();
		if(!$sett['enabled']) return;
		if($sett['auto_write_freq']=='daily') $days=1;
		else if($sett['auto_write_freq']=='weekly') $days=7;
		else $days=30;
		$val = $db->fetchAssoc("select (generated_last like '0000-00-00 00:00:00' or date_add(generated_last, interval $days day)<CURDATE()) as go from ".TABLE_SITEMAP);
		if($val['go']) { 
			$sitemap->writeSitemap(); 
			$this->updateSitemapDate();
		}
		return;
	}

	function updateSitemapDate() {

		global $db;
		$timestamp = date("Y-m-d H:i:s");
		$db->query("update ".TABLE_SITEMAP." set generated_last = '$timestamp'");

	}

	function removeUnusedFieldsFiles($type) {

		if($type=='uf') $table = TABLE_USER_FIELDS; else $table=TABLE_FIELDS;

		global $db;
		$result = $db->query("select id, caption from `".$table."` where `type` like 'file' or `type` like 'image' ");

		if($type=="cf") $t = TABLE_ADS; else $t = TABLE_USERS;

		$fields = new fields($type);

		// for all image or file field
		while($row = $db->fetchAssocRes($result)) {

			global $config_abs_path;
			if(!$row['caption']) continue;
			$folder_name = $config_abs_path."/uploads/".$row['caption'];

			if(!file_exists($folder_name)) continue;

			// browse the folder
			$files = dir($folder_name);
			while ($file = $files->read()) {

				if($file && $file!='.' && $file!='..') {
					
					if(!$fields->fileExists($t, $row['caption'], $file)) { 
						$filepath = $folder_name.'/'.$file;
						if(!is_dir($filepath)) @unlink($filepath);
					}
				}

			} closedir($files->handle);
		}
	}

	function deleteOldExports() {

		$now = time();
		$twodays = 172800;
		global $config_abs_path;
		$tmp_dir = $config_abs_path."/temp";

		$files = dir($tmp_dir);
		while ($file = $files->read()) {

			if($file && $file!='.' && $file!='..') {
				$ext = getExtension($file);
				if($ext!='php' && $ext!='html' && $ext!='CCH' && $file!=".htaccess") {
					$date = @filectime($tmp_dir.'/'.$file);
					$fis = $tmp_dir.'/'.$file;
					if($now-$date>$twodays && !is_dir($fis)) @unlink($fis);
				}
			}

		} closedir($files->handle);

	}

	function deleteOldCompiledFiles() {
	
		global $config_abs_path;
		$tmp_dir = $config_abs_path."/temp";
		
		$files = glob($tmp_dir.'/*.php');
		foreach($files as $file){
			if(is_file($file))
			unlink($file);
		}
	}
	
	
	function periodicAlerts() {

		global $db;
		global $ads_settings;
		global $config_live_site;
		global $modules_array;
		global $config_table_prefix;

		global $mail_settings;
		$html_mails=$mail_settings["html_mails"];

		$fields = explode(",",$ads_settings['search_in_fields']);
		$ads_fields = $db->getTableFields(TABLE_ADS);

		// delete alerts older than X days
		$days=$ads_settings['alerts_days_delete'];

		$current_date = date("Y-m-d");

		$alr = new alerts();

		if($days) {

			$dn1 = $days+1;
			$date_x_days_before_start = $db->fetchRow("select date_sub('$current_date', INTERVAL $dn1 DAY)");
			$date_x_days_before_end = $db->fetchRow("select date_sub('$current_date', INTERVAL $days DAY)");

			$alerts_query = $db->query("select id from ".TABLE_EMAIL_ALERTS." where `date` between '$date_x_days_before_start' and '$date_x_days_before_end'");
			$al = new alerts;
			while($del = $db->fetchRowRes($alerts_query))  {

				$alr->delete($del);

			}

			//foreach($delete_arr as $del) alerts::delete($del);
		}

		// check all alerts and send alert mails
		$mail2send=new mails();
		$mail2send->setMailTemplate("email_alert");

		$location_fields = explode(",", $ads_settings['location_fields']);

		$date_one_day_ago_start = $db->fetchRow("select date_sub('$current_date', INTERVAL 2 DAY)");
		$date_one_day_ago_end = $db->fetchRow("select date_sub('$current_date', INTERVAL 1 DAY)");

		$date_one_week_ago_start = $db->fetchRow("select date_sub('$current_date', INTERVAL 8 DAY)");
		$date_one_week_ago_end = $db->fetchRow("select date_sub('$current_date', INTERVAL 7 DAY)");

		$alerts_res = $db->query("select * from ".TABLE_EMAIL_ALERTS." where active=1 and (`frequency`=1 and `last_check` between '$date_one_day_ago_start' and '$date_one_day_ago_end') or (`frequency`=2 and `last_check` between '$date_one_week_ago_start' and '$date_one_week_ago_end')");
		

		while($al = $db->fetchAssocRes($alerts_res)) {

			$search_array = $alr->searchToArray($al['search']);
			$last_check = $al['last_check'];

			$with_pic = 0;
			$join = "";
			$sql = "";
			$search_by_area = 0;

			foreach ($search_array as $key=>$value) {

				if(preg_match ('/^([^\/])+(_low)$/', $key)) { 
					$key = str_replace("_low", "", $key);
					$sql .= "and `$key` >= '$value' ";
					continue;
				}

				if(preg_match ('/^([^\/])+(_high)$/', $key)) { 
					$key = str_replace("_high", "", $key);
					$sql .= "and `$key` <= '$value' ";
					continue;
				}

				if($key=="with_pic") {

					$sql.=" and ".TABLE_ADS_PICTURES.".`id` is not null ";
					$join .= " LEFT JOIN ".TABLE_ADS_PICTURES." on ".TABLE_ADS.".id=".TABLE_ADS_PICTURES.".ad_id";
					continue;
				}

				if($key == "category_id") {

					// if parent category, get subcategories list
					$categ = new categories();
					$s = "";
					$value = $categ->subcatList($value, $s);

					$sql.=' and '.TABLE_ADS.'.category_id in ('.$value.')';
					continue;

				}
				
				if($key == "location") {
					if(!count($location_fields)) continue;
					$a = 0;
					$where.=' and ( ';
					foreach($location_fields as $loc) {
						if($a) $where.=' or ';
						$where.=' '.TABLE_ADS.'.'.$loc.' like "'.$val.'"';
						$a++;
						}
						$where.=' )';
					continue;
				}

				// areasearch
				if($key=="zip") {

					global $modules_array;
					// if areasearch active and area set
					if(in_array("areasearch", $modules_array) && isset($search_array['area']) && $search_array['area'] && $search_array['area']>0) {
						$search_by_area = 1;
						$zip_loc = new areasearch();
						$zip_settings = $zip_loc->getSettings();
						if($zip_settings['um']=="miles") $radius = $zip_loc->miles_to_km($search_array['area']);
						else $radius = $search_array['area'];
						$coord = $zip_loc->getCoord($value);// $value = zip code
						if($coord!=0) {
							$sql.=' and (POW((69.1*('.$config_table_prefix.'zipcodes.lon-'.$coord['lon'].')*cos('.$coord['lat'].'/57.3)),"2")+POW((69.1*('.$config_table_prefix.'zipcodes.lat-'.$coord['lat'].')),"2"))<('.$radius.'*'.$radius.')';

							$join .= ' left join '.$config_table_prefix.'zipcodes on '.$config_table_prefix.'zipcodes.zipcode='.TABLE_ADS.'.zip';
						}
						continue;
					}
				}
				
				global $keyword_name;
				if($key==$keyword_name) {
				if(!count($fields)) continue;

				$key_array = explode('%20', $value);
				$no_words = count($key_array);
				$sql.=" and (";
				$k=0;
				for($i=$no_words; $i>0;$i--)
				{
					$w=trim($key_array[$i-1]);
					if($w!='') {
						$j=0;
						foreach($fields as $f) {
							if($k) $sql.=" or";
							$sql.=" ".TABLE_ADS.".$f like '%$w%'";
							$j++;
							$k++;
						}
					}
				}
				$sql.=" )";
				continue;
				}

				// the field can be a special module field 
				if(!in_array($key, $ads_fields)) {
					if(in_array($key, $modules_array)) {
						$custom_obj = new $key;
						$sql .= " and ".$custom_obj->getAdvSearch(TABLE_ADS, $value)." ";
					}
					continue;
				}

				$sql.="and `$key` = '$value' ";

			}

			$sql_final = "select ".TABLE_ADS.".`id` from ".TABLE_ADS."$join where active = 1 and `date_added` > '$last_check' ".$sql." order by `date_added` desc";

			$array_new = $db->fetchRowList($sql_final);
			$no = count($array_new);
			if($no) {

				$implode = implode(",", $array_new);
				$alert_id = $al['id'];
				$timestamp = date("Y-m-d H:i:s");
				$db->query("insert into ".TABLE_NEW_ALERTS." set `alert_id` = '$alert_id', `date` = '$timestamp', `listings` = '$implode'");
				$last_id=$db->insertId();

				// send alert email

				$mail2send->init($al['email'], $al['email']);

				$search=$alr->makeSearchString($search_array);

				if($html_mails) $amp = "&amp;"; else $amp = "&";
				$link = $config_live_site."/alerts.php?id=".$last_id.$amp."action=view";
				if($html_mails) $link ='<a href="'.$link.'">'.$link.'</a>';

				$unsubscribe_link = $config_live_site."/alerts.php?action=unsubscribe".$amp."id={$al['id']}".$amp."key=".$al['key'];
				if($html_mails) $unsubscribe_link ='<a href="'.$unsubscribe_link.'">'.$unsubscribe_link.'</a>';

				$array_subject = array("no"=>$no, "search"=>$search);

				$array_message = array("no"=>$no, "search"=>$search, "link"=>$link, "unsubscribe_link" => $unsubscribe_link);

				$mail2send->composeAndSend("", $array_message, $array_subject);

			}
		}

		// update last check
		$timestamp = date("Y-m-d H:i:s");
		$db->query("update ".TABLE_EMAIL_ALERTS." set last_check = '$timestamp' where active=1 and (`frequency`=1 and `last_check` between '$date_one_day_ago_start' and '$date_one_day_ago_end') or (`frequency`=2 and `last_check` between '$date_one_week_ago_start' and '$date_one_week_ago_end')");

		return 1;

	}


	function deleteExpiredAlerts($days) {

		if(!$days) return;
		global $db;

		$current_date = date("Y-m-d");

		$dn1 = $days+1;
		$date_x_days_before_start = $db->fetchRow("select date_sub('$current_date', INTERVAL $dn1 DAY)");
		$date_x_days_before_end = $db->fetchRow("select date_sub('$current_date', INTERVAL $days DAY)");

		$db->query("delete from ".TABLE_NEW_ALERTS." where `date` between '$date_x_days_before_start' and '$date_x_days_before_end'");

		return 1;
	}

	function deleteOldFailedAttempts() {

	  global $db;
	  $timestamp = date("Y-m-d H:i:s");
	  $db->query("delete from ".TABLE_FAILED_ATTEMPTS." where date_add(`date`, interval 1 hour) < '$timestamp'");

	}

	function unblockFailedAttempts() {
	
	      global $db;
	      $timestamp = date("Y-m-d H:i:s");
	      $db->query("delete from ".TABLE_BLOCKED_IPS." where `type`=2 and `date_expires`< '$timestamp'");
	
	}

	function deleteOldSearches($days) {

		if(!$days) return;
		global $db;

		$current_date = date("Y-m-d");

		$dn1 = $days+1;
		$date_x_days_before = $db->fetchRow("select date_sub('$current_date', INTERVAL $dn1 DAY)");

		$db->query("delete from ".TABLE_SEARCHES." where `date` < '$date_x_days_before'");

		return 1;
	}

}
?>
