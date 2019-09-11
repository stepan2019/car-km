<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class custom_pages {

	var $id;
	var $title;

	public function __construct($id='')
	{
	
		if($id) $this->id=$id;

	}

	function getId() {
		return $this->id;
	}


	function getTitle($id='') {
		global $db;
		global $crt_lang;
		if(!$id) $id=$this->id;
		$title=$db->fetchRow("select ".TABLE_CUSTOM_PAGES."_lang.title from ".TABLE_CUSTOM_PAGES."_lang where ".TABLE_CUSTOM_PAGES."_lang.id='$id' and `lang_id`='$crt_lang'");
		return $title;
	}


	function getContent($id, $lang_id='') {

		global $db;
		global $crt_lang;
		if($lang_id) $lang = $lang_id; else $lang = $crt_lang;

		if(!$id) $id=$this->id;
		$content=$db->fetchRow("select ".TABLE_CUSTOM_PAGES."_lang.content from ".TABLE_CUSTOM_PAGES."_lang where ".TABLE_CUSTOM_PAGES."_lang.id='$id' and `lang_id`='$lang'");

		return $content;

	}

	function getContentByCode($code, $lang_id='') {

		global $db;
		global $crt_lang;
		if($lang_id) $lang = $lang_id; else $lang = $crt_lang;

		$id = $db->fetchRow("select `id` from ".TABLE_CUSTOM_PAGES." where `code` like '$code'");

		$content=$db->fetchRow("select ".TABLE_CUSTOM_PAGES."_lang.content from ".TABLE_CUSTOM_PAGES."_lang where ".TABLE_CUSTOM_PAGES."_lang.id='$id' and `lang_id`='$lang'");

		return $content;

	}

	function getNo() {
		global $db;
		$no=$db->fetchRow('select count(*) from '.TABLE_CUSTOM_PAGES);
		return $no;
	}

	function getCustomPage($id='') {

		global $db;
		global $crt_lang;
		if(!$id) $id=$this->id;

		$row=$db->fetchAssoc("select * from ".TABLE_CUSTOM_PAGES." LEFT JOIN ".TABLE_CUSTOM_PAGES."_lang on ".TABLE_CUSTOM_PAGES.".`id` = ".TABLE_CUSTOM_PAGES."_lang.`id` where ".TABLE_CUSTOM_PAGES.".id=".$id." and `lang_id`='$crt_lang'");

		$arr = array("meta_keywords", "meta_description"); // allow any content for title and description
		foreach ($arr as $key) $row[$key] = cleanStr($row[$key]);

		return $row;

	}


	function getNavLinks() {

		global $db;
		global $crt_lang;

		$arr=$db->fetchAssocList("select ".TABLE_CUSTOM_PAGES.".id, `type`, `title`, `hreflink`, `blank` from ".TABLE_CUSTOM_PAGES." LEFT JOIN ".TABLE_CUSTOM_PAGES."_lang on ".TABLE_CUSTOM_PAGES.".`id` = ".TABLE_CUSTOM_PAGES."_lang.`id` where `lang_id`='$crt_lang' and active=1 and navlink=1 order by `order_no`");

		$i = 0;
		foreach ($arr as $res) {

			$arr[$i] = $res;
			$arr[$i]['title'] = escape($res['title']);
			$arr[$i]['url_title'] = _urlencode($arr[$i]['title']);
			$i++;
		}

		return $arr;

	}

	function firstPageActive() {

		global $db;
		$active = $db->fetchRow("select `active` from ".TABLE_CUSTOM_PAGES." where id=1");
		return $active;
	}

	function getFirstPageContent() {

		global $db;
		global $crt_lang;

		$content=$db->fetchRow("select content from ".TABLE_CUSTOM_PAGES."_lang LEFT JOIN ".TABLE_CUSTOM_PAGES." on ".TABLE_CUSTOM_PAGES.".`id` = ".TABLE_CUSTOM_PAGES."_lang.`id` where `lang_id`='$crt_lang' and ".TABLE_CUSTOM_PAGES.".`id`=1 and `active`=1");
		return $content;
	}

	function getIdByCode($code) {

		global $db;
		$id=$db->fetchRow("select `id` from ".TABLE_CUSTOM_PAGES." where `code` like '$code'");
		return $id;
	}

}
?>
