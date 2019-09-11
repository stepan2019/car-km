<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class groups {

	public function __construct()
	{
	
	}

	function getId() {
		return $this->id;
	}

	
	static function getName($id) { 

		if(!$id || !is_numeric($id)) return;

		if($id==1000) { 
			global $lng;
			return clean($lng['packages']['not_logged_in']);
		}

		global $db;
		global $crt_lang;
		$uname=$db->fetchRow("select `name` from ".TABLE_USER_GROUPS."_lang where id=$id and `lang_id`='$crt_lang'");
		return $uname; 
	}


	function getPackagePending($id) { 

		global $db;
		if(!$id || !is_numeric($id)) return 0;
		$ar=$db->fetchRow('select `package_pending` from '.TABLE_USER_GROUPS.' where id="'.$id.'"');
		return $ar; 
	}

	function getListingPending($id='') { 

		global $db;
		if(!$id) { 
			global $settings;
			return $settings['nologin_pending_listing'];
		}
		$ar=$db->fetchRow('select `listing_pending` from '.TABLE_USER_GROUPS.' where id="'.$id.'"');
		return $ar; 
	}

	function getActivateAccount($id) { 

		global $db;
		if(!$id || !is_numeric($id)) return 0;
		$act=$db->fetchRow('select activate_account from '.TABLE_USER_GROUPS.' where id="'.$id.'"');
		return $act; 
	}

	static function getAdminVerification($id) { 

		global $db;
		if(!$id || !is_numeric($id)) return 0;
		$act=$db->fetchRow('select admin_verification from '.TABLE_USER_GROUPS.' where id="'.$id.'"');
		return $act; 
	}

	function getStore($id) { 

		global $db;
		if(!$id || !is_numeric($id)) return 0;
		$ar=$db->fetchRow('select `store` from '.TABLE_USER_GROUPS.' where id="'.$id.'"');
		return $ar; 
	}

	function getGroup($id) {
		
		global $db;
		global $crt_lang;
		if(!$id || !is_numeric($id)) return;
		$result=$db->fetchAssoc("select * from ".TABLE_USER_GROUPS." LEFT JOIN ".TABLE_USER_GROUPS."_lang on ".TABLE_USER_GROUPS.".`id` = ".TABLE_USER_GROUPS."_lang.`id` where ".TABLE_USER_GROUPS.".id='$id' and `lang_id` = '$crt_lang'");
		return $result;

	}


	static function getNoActive() {
	
		global $db;
		$no=$db->fetchRow('select count(*) from '.TABLE_USER_GROUPS.' where `active`=1');
		return $no;

	}

	function noDefActiveGroup() {

		global $db;
		$group=$db->fetchRow('select id from '.TABLE_USER_GROUPS.' where active=1');
		return $group;
	}

	function getAll() {

		global $db;
		global $crt_lang, $settings;

		$str = "";
		$array=$db->fetchAssocList("select ".TABLE_USER_GROUPS.".*, ".TABLE_USER_GROUPS."_lang.* from ".TABLE_USER_GROUPS." LEFT JOIN ".TABLE_USER_GROUPS."_lang on ".TABLE_USER_GROUPS.".`id` = ".TABLE_USER_GROUPS."_lang.`id` where `lang_id` = '$crt_lang' ".$str." order by `order_no`");

		$i=0;
		$usr = new users();
		foreach ($array as $result) {

			$array[$i]['name'] = cleanStr($result['name']);	
			$array[$i]['description'] = cleanStr($result['description']);	
			$array[$i]['last'] = 0;	
			$i++;

		}

		if($i) $array[$i-1]['last'] = 1;
		return $array;

	}

	// get groups short version
	function getShortGroups() {

		global $db;
		global $crt_lang;

		$array=$db->fetchAssocList("select ".TABLE_USER_GROUPS.".*, ".TABLE_USER_GROUPS."_lang.* from ".TABLE_USER_GROUPS." LEFT JOIN ".TABLE_USER_GROUPS."_lang on ".TABLE_USER_GROUPS.".`id` = ".TABLE_USER_GROUPS."_lang.`id` where `lang_id` = '$crt_lang' order by `order_no`");

		$i=0;
		foreach ($array as $result) {
			$array[$i]['name']=cleanStr($result['name']);
			$array[$i]['description']=cleanStr($result['description']);
			$i++;
		}
		return $array;

	}

	function getAllRegister() {

		global $db;
		global $crt_lang, $settings;

		$str = "";

		$array=$db->fetchAssocList("select * from ".TABLE_USER_GROUPS." LEFT JOIN ".TABLE_USER_GROUPS."_lang on ".TABLE_USER_GROUPS.".`id` = ".TABLE_USER_GROUPS."_lang.`id` where auto_register=1 and `active`=1 and `lang_id` = '$crt_lang' ".$str." order by `order_no`");

		$i=0;
		foreach ($array as $result) {
			$array[$i]['name']=cleanStr($result['name']);
			$array[$i]['description']=cleanStr($result['description']);
			$i++;
		}

		return $array;

	}
	
	function getAllPostAds() {

		global $db;
		global $crt_lang, $settings;

		$str = "";

		$array=$db->fetchAssocList("select * from ".TABLE_USER_GROUPS." LEFT JOIN ".TABLE_USER_GROUPS."_lang on ".TABLE_USER_GROUPS.".`id` = ".TABLE_USER_GROUPS."_lang.`id` where `post_ads`=1 and `active`=1 and `lang_id` = '$crt_lang' ".$str." order by `order_no`");

		$i=0;
		foreach ($array as $result) {
			$array[$i]['name']=cleanStr($result['name']);
			$array[$i]['description']=cleanStr($result['description']);
			$i++;
		}

		return $array;

	}

	static function exists($id) {

		global $db;
		$res=$db->query("select * from ".TABLE_USER_GROUPS." where id='$id'");
		if($db->numRows($res)) return 1;
		return 0;
	}

	static function noDefRegisterGroup() {

		global $db;
		$group=$db->fetchRow('select id from '.TABLE_USER_GROUPS.' where auto_register=1 and active=1');
		return $group;
	}

	function noDefGroup() {

		global $db;
		$group=$db->fetchRow('select id from '.TABLE_USER_GROUPS.' where active=1');
		return $group;
	}


}

?>