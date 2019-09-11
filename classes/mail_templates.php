<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class mail_templates {

	public function __construct()
	{
	
	}

	static function getVal($val) {

		global $db;
		global $crt_lang;

		$result=$db->fetchAssoc("select * from ".TABLE_MAILS." where ".TABLE_MAILS.".`code` like '$val' and `lang_id` = '$crt_lang'");
		global $mail_settings;
		$html_mails=$mail_settings["html_mails"];

		// clean slashes
//		foreach($result as $key=>$value) {
//			$result[$key] = cleanStr($result[$key]);
			//if($html_mails) $result[$key] = str_replace("\n", "",$result[$key]);
			//if($html_mails) $result[$key] = str_replace("\r", "",$result[$key]);
//		}

		return $result;
	}

	function getReg($val) {

		global $db;
		global $crt_lang;
		$result=$db->fetchAssoc("select * from ".TABLE_MAILS." where `code` like '$val' and `lang_id` = '$crt_lang'");
		// clean slashes
		foreach($result as $key=>$value) $result[$key] = cleanStr($result[$key]);

		return $result;

	}

	function getRegLang($val) {

		global $db;
		//global $crt_lang;
		$result=$db->fetchAssocList("select lang_id, ".TABLE_MAILS.".code, subject, content, info from ".TABLE_MAILS." left join ".TABLE_LANGUAGES." on ".TABLE_MAILS.".lang_id=".TABLE_LANGUAGES.".id where ".TABLE_MAILS.".`code` like '$val' and ".TABLE_LANGUAGES.".enabled=1");
		$array = array();
		foreach ($result as $res) {
			
			// clean slashes
			$fields = array("code", "subject", "content", "info");
			foreach($fields as $key) $res[$key] = cleanStr($res[$key]);
			$array[$res['lang_id']] = $res;
		}

		return $array;

	}


	function getAll() {

		global $db;
		global $crt_lang;
		$array=$db->fetchAssocList("select * from ".TABLE_MAILS." where `lang_id` = '$crt_lang' order by `code`");

		// clean slashes
		$fields = array("code", "subject", "content", "info");
		$i=0;
		foreach ($array as $result) {

			foreach($fields as $key) $array[$i][$key] = cleanStr($array[$i][$key]);
			$i++;

		}

		return $array;
	}

	function getFirst() {

		global $db;
		$result=$db->fetchRow("select `code` from ".TABLE_MAILS." order by `code` limit 1");
		return $result;
	}

	function edit($code) {

		global $db;
		global $config_demo;
		if($config_demo==1) return;
		global $languages;
		
		foreach ($languages as $lang) {

			$lang_id = $lang['id'];
			$this->clean['content']["$lang_id"] = escape($_POST['content_'.$lang_id]);
			$this->clean['subject']["$lang_id"] = escape($_POST['subject_'.$lang_id]);

			$res=$db->query("update ".TABLE_MAILS." set content='".$this->clean['content']["$lang_id"]."', subject='".$this->clean['subject']["$lang_id"]."' where `code` like '$code' and `lang_id`='$lang_id'");

		}

		return 1;
	}

	function update($lang_id, $code, $subject, $content){

		global $db;
		global $config_demo;
		if($config_demo==1) return;

		$res=$db->query("update ".TABLE_MAILS." set content='$content', `subject`='$subject' where `code` like '$code' and `lang_id`='$lang_id'");

		return 1;

	}


}
?>
