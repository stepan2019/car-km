<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class messages {

	public function __construct()
	{
	
		$this->starting =0;

	}

	function delete($id, $user_id='') {

		global $db;
		global $config_demo;
		if($config_demo==1) return;

		global $db;
		if($user_id) $where_user = " and `to` = '$user_id'";
		$db->query("delete from ".TABLE_MESSAGES." where id='$id' ".$where_user);
		return;

	}

	function report($id, $user_id) {


		global $db;
		$ok = $db->fetchRow("select `to` from ".TABLE_MESSAGES." where id='$id'");
		if($ok != $user_id) return 0;
		$db->query("update ".TABLE_MESSAGES." set `report` = 1 where `id`='$id'");

		// send email
		$mail2send=new mails();
		$mail2send->init();

		$array_subject = array("id"=>$id);

		$array_message = array("id"=>$id);

		$mail2send->composeAndSend("report_message", $array_message, $array_subject);
		return 1;
	}

	function Block($id) {

		global $db;
		global $config_demo;
		if($config_demo==1) return;

		$ip=$this->getIp($id);

		global $config_abs_path;
		require_once $config_abs_path."/classes/blocked_ips.php";
		$bi = new blocked_ips();
		$bi->add($ip, "Spam message");

	}

	function Unblock($id) {

		global $db;
		global $config_demo;
		if($config_demo==1) return;

		$ip=$this->getIp($id);
		$res=$db->query('delete from '.TABLE_BLOCKED_IPS.' where ip like "'.$ip.'"');

	}

	function getIp ($id) {

		global $db;
		$ip = $db->fetchRow("select `ip` from ".TABLE_MESSAGES." where id='$id'");
		return $ip;
	}
	
	function getMessages($post_array, $page,$no_per_page,$order,$order_way, $hide_pending=0) {

		if($page) $start=($page-1)*$no_per_page;

		global $settings;
		$found = 0; $where = '';
		if($hide_pending) { $where=" where ".TABLE_MESSAGES.".`pending`=0 "; $found=1; }

		foreach($post_array as $key=>$value) {

			if( !$key || $value=='' ) continue;
			
			switch($key) {
				case "id": 
				case "ip": 
					if($found) $where.=" and "; else $where = " where ";
					$where .= " ".TABLE_MESSAGES.".`$key` = '$value' ";
					$found = 1;
				break;
				case "keyword": 
					if($found) $where.=" and "; else $where = " where ";
					$where .= " ".TABLE_MESSAGES.".`message` like '%$value%' ";
					$found = 1;
				break;
				case "email": 
					if($found) $where.=" and "; else $where = " where ";
					$where .= " ( ".TABLE_USERS.".`$key` = '$value' or ".TABLE_MESSAGES.".from_email = '$value' ) ";
					$found = 1;
				break;
				case "username": 
					if($found) $where.=" and "; else $where = " where ";
					$where .= " ".TABLE_USERS.".`$key` = '$value' ";
					$found = 1;
				break;
				case "name": 
					if($found) $where.=" and "; else $where = " where ";
					$where .= " ".TABLE_USERS.".`".$settings['contact_name_field']."` like '%$value%' ";
					$found = 1;
				break;
				case "user_id": 
					if($found) $where.=" and "; else $where = " where ";
					$where .= " (".TABLE_MESSAGES.".`from` = '$value' or ".TABLE_MESSAGES.".`to` = '$value') ";
					$found = 1;
				break;
				case "date_from": 
					if($found) $where.=" and "; else $where = " where ";
					$where .= " ".TABLE_MESSAGES.".`date` >= '$value' ";
					$found = 1;
				break;

				case "date_to": 
					if($found) $where.=" and "; else $where = " where ";
					$where .= " ".TABLE_MESSAGES.".`date` <= '$value' ";
					$found = 1;
				break;
				case "history": 
					if($found) $where.=" and "; else $where = " where ";
					$where .= " ( ".TABLE_MESSAGES.".`id` = '$value' or ".TABLE_MESSAGES.".`starting` = '$value' ) ";
					$found = 1;
				break;
				case "pending": 
					if($found) $where.=" and "; else $where = " where ";
					$where .= " ".TABLE_MESSAGES.".`pending` = 1 ";
					$found = 1;
				break;
				case "type": 
					global $crt_usr;
					if($found) $where.=" and "; else $where = " where ";
					if($value=="sent") 
						$where .= " ".TABLE_MESSAGES.".`from` = $crt_usr ";
					if($value=="received") 
						$where .= " ".TABLE_MESSAGES.".`to` = $crt_usr ";
					$found = 1;
				break;
			}
		}

		global $appearance_settings;
		$date_format=$appearance_settings["date_format_long"];

		global $ads_settings;
		$title_var ="`title`";
		if($ads_settings['translate_title_description']) {
			global $languages;
			global $crt_lang;
			if(empty($languages)) $languages = common::getCachedObject("base_languages");
			if(count($languages)>1) {
				$title_var = "`title_$crt_lang`";
			}
		}

		global $db;

		$limit_str = "";
		if($page) $limit_str = " limit ".$start.", ".$no_per_page;

		$messages_array=$db->fetchAssocList("select *, ".TABLE_MESSAGES.".id as `msg_id`, ".TABLE_MESSAGES.".pending as `pending`, date_format(".TABLE_MESSAGES.".`date`,'$date_format') as date_nice, ".TABLE_ADS.".$title_var as `title`, ".TABLE_MESSAGES.".ip as ip from ".TABLE_MESSAGES." left join ".TABLE_ADS." on ".TABLE_ADS.".id = ".TABLE_MESSAGES.".ad_id left join ".TABLE_USERS." on ".TABLE_USERS.".id = ".TABLE_MESSAGES.".`from` $where"." order by ".TABLE_MESSAGES.".`".$order."` ".$order_way.$limit_str);

		$i = 0;
		foreach($messages_array as $msg) {

			$messages_array[$i]['message'] = cleanStr($messages_array[$i]['message']);
			$messages_array[$i]['title'] = cleanStr($messages_array[$i]['title']);
			$messages_array[$i]['from_contact_name'] = $msg[$settings['contact_name_field']];

			if($messages_array[$i]['to']) {

				$contact_str="";
				if($settings['contact_name_field']!='email' && $settings['contact_name_field']!='username') $contact_str=", `{$settings['contact_name_field']}`";


				$user_info = $db->fetchAssoc("select  email, username".$contact_str." from ".TABLE_USERS." where id='{$messages_array[$i]['to']}'");

				$messages_array[$i]['to_contact_name'] = $user_info[$settings['contact_name_field']];
				$messages_array[$i]['to_email'] = $user_info['email'];
				$messages_array[$i]['to_username'] = $user_info['username'];
			}
			$messages_array[$i]['blocked'] = $db->fetchRow("select count(*) from ".TABLE_BLOCKED_IPS." where ip like '{$messages_array[$i]['ip']}'");
			$i++;
		}

		$no_messages = $this->noSearchMessages($where);
		$this->no_messages = $no_messages;
//_print_r($messages_array);
		return $messages_array;
	}

	function getMessage($id) {

		global $db;

		global $appearance_settings, $settings;
		$date_format=$appearance_settings["date_format_long"];

		global $ads_settings;
		$title_var ="`title`";
		if($ads_settings['translate_title_description']) {
			global $languages;
			global $crt_lang;
			if(empty($languages)) $languages = common::getCachedObject("base_languages");
			if(count($languages)>1) {
				$title_var = "`title_$crt_lang`";
			}
		}

		$array = $db->fetchAssoc("select *, ".TABLE_MESSAGES.".id as `msg_id`, ".TABLE_MESSAGES.".pending as `msg_pending`, date_format(".TABLE_MESSAGES.".`date`,'$date_format') as date_nice, ".TABLE_ADS.".$title_var as `title`, ".TABLE_MESSAGES.".ip as ip from ".TABLE_MESSAGES." left join ".TABLE_ADS." on ".TABLE_ADS.".id = ".TABLE_MESSAGES.".ad_id left join ".TABLE_USERS." on ".TABLE_USERS.".id = ".TABLE_MESSAGES.".`from` where ".TABLE_MESSAGES.".`id`='$id'");

		$array['message'] = cleanStr($array['message']);
		$array['title'] = cleanStr($array['title']);

		if($array['to']) {

			$contact_str="";
			if($settings['contact_name_field']!='email' && $settings['contact_name_field']!='username') $contact_str=", `{$settings['contact_name_field']}`";

			$user_info = $db->fetchAssoc("select email, username".$contact_str." from ".TABLE_USERS." where id='{$array['to']}'");
			$array['to_contact_name'] = $user_info[$settings['contact_name_field']];
			$array['to_email'] = $user_info['email'];
			$array['to_username'] = $user_info['username'];
		} else {
			$array['to_contact_name'] = '';
			$array['to_username'] = '';
		}

		if($array['from']) {

			$contact_str="";
			if($settings['contact_name_field']!='email' && $settings['contact_name_field']!='username') $contact_str=", `{$settings['contact_name_field']}`";

			$user_info2 = $db->fetchAssoc("select email, username".$contact_str." from ".TABLE_USERS." where id='{$array['from']}'");
			$array['from_contact_name'] = $user_info2[$settings['contact_name_field']];
			$array['from_email'] = $user_info2['email'];
			$array['from_username'] = $user_info2['username'];
		} else {
			$array['from_contact_name'] = '';
			$array['from_username'] = '';
		}

		return $array;
	}

	function add($to, $to_email, $from, $from_email, $from_phone, $ad_id, $message, $reply_to='', $pending = 0) {

		$timestamp = date("Y-m-d H:i:s");
		$ip = getRemoteIp();

		global $db;
		$reply_str = ""; $starting_str="";
		if($reply_to) $reply_str = ", `reply_to` ='$reply_to'";
		if($this->starting) $starting_str = ", `starting` ='{$this->starting}'";
		$db->query("insert into ".TABLE_MESSAGES." set `from` = '$from', `from_email`='$from_email', `from_phone`='$from_phone', `to` = '$to', `to_email` = '$to_email', `date` = '$timestamp', `message` = '".escape($message)."', `ad_id` = '$ad_id', `ip`='$ip', `pending`='$pending'".$reply_str.$starting_str);

		return 1;

	}

	function setStarting($starting) {

		$this->starting = $starting;
	
	}

	function noSearchMessages($where) {

		global $db;
		$no=$db->fetchRow("select count(*) from ".TABLE_MESSAGES." left join ".TABLE_ADS." on ".TABLE_ADS.".id = ".TABLE_MESSAGES.".ad_id left join ".TABLE_USERS." on ".TABLE_USERS.".id = ".TABLE_MESSAGES.".`from` ".$where);
		return $no;
	}

	function getNoMessages() {

		return $this->no_messages;

	}

	function setNoMessages($no_messages) {

		$this->no_messages = $no_messages;

	}

	static function belongsTo($msg_id, $user_id) {
		
		global $db;
		$details = $db->fetchAssoc("select `from`, `to` from ".TABLE_MESSAGES." where id='$msg_id'");
		if( $details['from']==$user_id || $details['to']==$user_id) return 1;
		return 0;

	}

	function send($id) {

		global $config_abs_path;
		require_once $config_abs_path."/classes/listings.php";
		require_once $config_abs_path."/classes/mails.php";
		require_once $config_abs_path."/classes/mail_templates.php";
		$msg = $this->getMessage($id);

		$details_link = listings::makeDetailsLink($msg['ad_id']);

		// get listing details
		$listings = new listings;
		$details = $listings->getBriefListing($msg['ad_id']);

		$ad_title = $details['title'];

		// send email
		$mail2send=new mails();
		$mail2send->init($msg['to_email'], $msg['to_contact_name'], $msg['from_email'], $msg['from_contact_name']);

		global $mail_settings;

		if($mail_settings["html_mails"])
			$message = nl2br($msg['message']);  else $message = $msg['message'];

		$sender_name = $msg['from_contact_name'];
		if(!$sender_name) $sender_name = $msg['from_email'];

		$array_subject = array("ad_id"=>$msg['ad_id'], "username"=>$msg['to_username'], "sender_name"=>$sender_name, "title"=>$ad_title);

		$array_message = array("ad_id"=>$msg['ad_id'], "username"=>$msg['to_username'], "contact_name"=>$msg['to_contact_name'], "sender_name"=>$sender_name, "sender_email"=> $msg['from_email'], "ad_link"=>$details_link, "title"=>$ad_title, "ip"=>$msg['ip'], "message" => $message);

		if(!$msg['starting']) $mtemplate = "mailto"; else $mtemplate="reply";
		
		$sent = $mail2send->composeAndSend($mtemplate, $array_message, $array_subject);

		// set message not pending
		if($sent) { 
			global $db;
			$db->query("update ".TABLE_MESSAGES." set `pending`=0 where id='$id'");
		}

		return $sent;

	}

	function isPending ($id) {

		global $db;
		$pending = $db->fetchRow("select `pending` from ".TABLE_MESSAGES." where id='$id'");
		return $pending;
	}

	function getNoPendingMessages() {

		global $db;
		$no = $db->fetchRow("select count(*) from ".TABLE_MESSAGES." where pending=1");
		return $no;

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

	function check_form ($id='') {

		global $lng;
		$this->error='';
		$this->tmp=array();

		if(!$_POST['message']) $this->addError($lng['messages']['error']['add_message'].'<br />');
		
		return;

	}
	
	function edit($id) {


		global $db;
		
		global $db;
		$this->clean=array();
		$this->check_form($id);
		if($this->getError()!='') return 0;
		
		$this->clean['message'] = $_POST['message'];

		global $mail_settings;

		if($mail_settings["html_mails"])
			$this->clean['message'] = nl2br($this->clean['message']);

		$approve = 0;
		if(isset($_POST['approve']) && $_POST['approve']=='on') $approve = 1;
		
		$res=$db->query('update '.TABLE_MESSAGES.' set `message` = "'.escape($this->clean['message']).'" where id='.$id.';');
		
		if($approve && $this->isPending($id)) $this->send($id);
		
		return 1;

		
	}

	
}


?>
