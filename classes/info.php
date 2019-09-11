<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class info {

	public function __construct()
	{
	
	}

	static function getVal($val) {

		global $db;
		global $crt_lang;
		$content=$db->fetchRow("select `content` from ".TABLE_INFO." where `code` like '$val' and `lang_id` = '$crt_lang'");

		return cleanStr($content);

	}

	function getReg($val) {

		global $db;
		global $crt_lang;
		$result=$db->fetchAssoc("select * from ".TABLE_INFO." where `code` like '$val' and `lang_id`='$crt_lang'");

		// clean slashes
		$fields = array("content", "info");
		foreach($fields as $key) $result[$key] = cleanStr($result[$key]);

		return $result;

	}

	function getRegLang($val) {

		global $db;
		$result=$db->fetchAssocList("select * from ".TABLE_INFO." where `code` like '$val'");
		$array = array();
		foreach ($result as $res) {
			
			// clean slashes
			$fields = array("content", "info");
			foreach($fields as $key) $res[$key] = cleanStr($res[$key]);
			$array[$res['lang_id']] = $res;
		}
		return $array;

	}

	function getAll() {

		global $db;
		global $crt_lang;
		$array=$db->fetchAssocList("select * from ".TABLE_INFO." where `lang_id`='$crt_lang' order by `code`");

		// clean slashes
		$fields = array("content", "info");
		$i=0;
		foreach ($array as $result) {

			foreach($fields as $key) $array[$i][$key] = cleanStr($array[$i][$key]);
			$i++;

		}

		return $array;
	}

	function getFirst() {

		global $db;
		$result=$db->fetchRow("select `code` from ".TABLE_INFO." order by `code` limit 1");
		return $result;
	}
	
	function edit($code){

		global $db;
		global $config_demo;
		if($config_demo==1) return;
		global $languages;
		
		foreach ($languages as $lang) {

			$lang_id = $lang['id'];
			$this->clean['content']["$lang_id"] = escape($_POST['content_'.$lang_id]);

			$res=$db->query("update ".TABLE_INFO." set content='".$this->clean['content']["$lang_id"]."' where `code` like '$code' and `lang_id`='$lang_id'");
		}
		return 1;

	}

	function update($lang_id, $code, $content){

		global $db;
		global $config_demo;
		if($config_demo==1) return;

		$res=$db->query("update ".TABLE_INFO." set content='".$content."' where `code` like '$code' and `lang_id`='$lang_id'");

		return 1;

	}

}
?>
