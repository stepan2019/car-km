<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class packages {

	public function __construct()
	{
	
	}

	function getId() {
		return $this->id;
	}


	function getPackage($id='') {

		global $db;
		global $crt_lang;

		if(!$id) $id=$this->id;
		$result=$db->fetchAssoc("select * from ".TABLE_PACKAGES." LEFT JOIN ".TABLE_PACKAGES."_lang on ".TABLE_PACKAGES.".`id` = ".TABLE_PACKAGES."_lang.`id` where ".TABLE_PACKAGES.".id=$id and `lang_id`='$crt_lang'");

		$array_fields = array("name", "description");
		foreach($array_fields as $key) if($result[$key]) $result[$key] = cleanStr($result[$key]);

		if($result['groups']) {
			$result['groups_array']=explode(",",$result['groups']);
			for($a=0; $a<count($result['groups_array']);$a++) {
				$result['groups_array'][$a]=trim($result['groups_array'][$a]);
			}
		}

		if($result['categories']) {
			$result['categories_array']=explode(",",$result['categories']);
			for($a=0; $a<count($result['categories_array']);$a++) {
				$result['categories_array'][$a]=trim($result['categories_array'][$a]);
			}
		}

		$result['price_curr'] = add_currency(format_price($result['amount']));

		return $result;

	}

	static function getName($id) {

		global $db;
		global $crt_lang;
		$name=$db->fetchRow("select `name` from ".TABLE_PACKAGES."_lang where `id`=$id and `lang_id`='$crt_lang'");
		return cleanStr($name);

	}

	static function getNoDays($id) {
		
		global $db;
		$no=$db->fetchRow('select `no_days` from '.TABLE_PACKAGES.' where `id`="'.$id.'"');
		return $no;

	}

	function getSubscriptionTime($id) {
		
		global $db;
		$no=$db->fetchRow('select `subscription_time` from '.TABLE_PACKAGES.' where `id`="'.$id.'"');
		return $no;

	}

	static function getNoPictures($id) {
		
		global $db;
		$pic=$db->fetchRow('select no_pictures from '.TABLE_PACKAGES.' where `id`="'.$id.'"');
		return $pic;

	}

	function getType($id) {
		
		global $db;
		$type=$db->fetchRow('select type from '.TABLE_PACKAGES.' where `id`="'.$id.'"');
		return $type;

	}

	static function getNoWords($id) {
		
		global $db;
		$no=$db->fetchRow('select `no_words` from '.TABLE_PACKAGES.' where `id`="'.$id.'"');
		return $no;

	}

	static function getPhotoMandatory($id) {
		
		global $db;
		$no=$db->fetchRow('select `photo_mandatory` from '.TABLE_PACKAGES.' where `id`="'.$id.'"');
		return $no;

	}

	static function getVideo($id) {

		global $db;
		$video=$db->fetchRow("select `video` from ".TABLE_PACKAGES." where `id`=$id");
		return $video;

	}

	static function getUrl($id) {

		global $db;
		$video=$db->fetchRow("select `url` from ".TABLE_PACKAGES." where `id`=$id");
		return $video;

	}

	function getAmount($id) {
		
		global $db;
		$amount=$db->fetchRow('select amount from '.TABLE_PACKAGES.' where `id`="'.$id.'"');
		return $amount;

	}


	function getAll($group='') {

		global $db;
		global $lng;
		global $crt_lang;
		$array=$db->fetchAssocList("select * from ".TABLE_PACKAGES." LEFT JOIN ".TABLE_PACKAGES."_lang on ".TABLE_PACKAGES.".`id` = ".TABLE_PACKAGES."_lang.`id` where `lang_id`='$crt_lang' order by `order_no`");

		$i=0;
		$array_pkg=array();
		global $appearance_settings;
		$currency_pos=$appearance_settings['currency_pos'];
		$default_currency = $appearance_settings['default_currency'];
		$array_fields = array("name", "description");

		foreach($array as $result) {
			$arr_gr=explode(",",$result['groups']);
			if(!$group || $result['groups']==0 || in_array($group, $arr_gr)) {

			$array_pkg[$i]=$result;
			
			foreach($array_fields as $key) $array_pkg[$i][$key] = cleanStr($array_pkg[$i][$key]);
			
			$array_pkg[$i]['price_curr']=add_currency(format_price($array_pkg[$i]['amount']));

			$array_pkg[$i]['total_ads']=$this->getAdsNo($result['id']);

			if($array_pkg[$i]['priority']) $array_pkg[$i]['priority_name'] = priorities::getName($array_pkg[$i]['priority']); else $array_pkg[$i]['priority_name']='';

			if($result['groups']==0) $array_pkg[$i]['groups_formatted'] = $lng['general']['all'];
			else $array_pkg[$i]['groups_formatted'] = '';

			$array_pkg[$i]['groups_array']=explode(",",$result['groups']);
			for($a=0; $a<count($array_pkg[$i]['groups_array']);$a++) {
				$array_pkg[$i]['groups_array'][$a]=trim($array_pkg[$i]['groups_array'][$a]);
				if($array_pkg[$i]['groups_array'][$a]) $array_pkg[$i]['groups_formatted'].=cleanStr(groups::getName($array_pkg[$i]['groups_array'][$a])).'<br>';
			}

			if($result['categories']==0) $array_pkg[$i]['categories_formatted'] = $lng['general']['all'];
			else $array_pkg[$i]['categories_formatted'] = '';

			$array_pkg[$i]['categories_array']=explode(",",$result['categories']);
			for($a=0; $a<count($array_pkg[$i]['categories_array']);$a++) {
				$array_pkg[$i]['categories_array'][$a]=trim($array_pkg[$i]['categories_array'][$a]);
				if($array_pkg[$i]['categories_array'][$a]) $array_pkg[$i]['categories_formatted'].=cleanStr(categories::getName($array_pkg[$i]['categories_array'][$a])).'<br>';
			}

			$array_pkg[$i]['last'] = 0;	
			$i++;

			} //end if group
		}
		if($i) $array_pkg[$i-1]['last'] = 1;

		return $array_pkg;
	}

	function getAdsNo($id) {

		global $db;
		$no=$db->fetchRow('select count(*) from '.TABLE_ADS.' where package_id='.$id);
		return $no;

	}


	static function getAllForm($group='') {

		global $db;
		global $crt_lang;

		$where_str = "";
		if($group!=0)  $where_str .= " and ( ".TABLE_PACKAGES.".`groups` REGEXP '\[\[:<:\]\]$group\[\[:>:\]\]'  or `groups` = 0 ) ";

		$arr=$db->fetchAssocList("select ".TABLE_PACKAGES.".`id`, `name` from ".TABLE_PACKAGES." LEFT JOIN ".TABLE_PACKAGES."_lang on ".TABLE_PACKAGES.".`id` = ".TABLE_PACKAGES."_lang.`id` where `lang_id`='$crt_lang' and `active`=1 $where_str order by `order_no`");

		$i = 0;
		foreach ($arr as $row) {
			$arr[$i]['name'] = cleanHtml($arr[$i]['name']);
			$i++;
		}
		return $arr;
	}

	function getAllSubscriptions($group='', $user_id='') {

		global $db;
		global $crt_lang;

		$array=$db->fetchAssocList("select * from ".TABLE_PACKAGES." LEFT JOIN ".TABLE_PACKAGES."_lang on ".TABLE_PACKAGES.".`id` = ".TABLE_PACKAGES."_lang.`id` where `lang_id`='$crt_lang' and `active`=1 and no_ads!=1 order by `order_no`");


		$i=0;
		$array_pkg=array();
		$array_fields = array("name", "description");
		foreach($array as $result) {

			// check if number of allowed packages use used
			if($user_id && $result['allow']>0) {
				$res = $db->query("select * from ".TABLE_USERS_PACKAGES." where `user_id`='$user_id' and `package_id`='".$result['id']."'");
				$no_used = $db->numRows($res);
				if($no_used>=$result['allow']) continue;
			}

			$groups = explode(',',$result['groups']);
			if(!$group || $result['groups']==0 || in_array($group,$groups)) {

			$array_pkg[$i]=$result;
			foreach($array_fields as $key) $array_pkg[$i][$key] = cleanStr($array_pkg[$i][$key]);

			if($array_pkg[$i]['priority']) $array_pkg[$i]['priority_name'] = priorities::getName($array_pkg[$i]['priority']); else $array_pkg[$i]['priority_name']='';

			$array_pkg[$i]['price_curr']=add_currency(format_price($array_pkg[$i]['amount']));

			$i++;

			} // if in group
		}
		return $array_pkg;
	}

	function getAllPlans($group='', $category='', $user_id = '') {

		global $db;
		global $crt_lang;

		$where_str = '';
		if($group=='1000') $where_str .= " and `type` like 'ad'";
		if($category)  $where_str .= " and ( ".TABLE_PACKAGES.".`categories` REGEXP '\[\[:<:\]\]$category\[\[:>:\]\]'  or `categories` = 0 ) ";
		if($group!=0)  $where_str .= " and ( ".TABLE_PACKAGES.".`groups` REGEXP '\[\[:<:\]\]$group\[\[:>:\]\]'  or `groups` = 0 ) ";


		$array=$db->fetchAssocList("select * from ".TABLE_PACKAGES." LEFT JOIN ".TABLE_PACKAGES."_lang on ".TABLE_PACKAGES.".`id` = ".TABLE_PACKAGES."_lang.`id` where `lang_id`='$crt_lang' and `active`=1$where_str order by `order_no`");

		$i=0;
		$array_pkg=array();
		$array_fields = array("name", "description");
		foreach($array as $result) {

			// check if number of allowed packages use used
			if($user_id && $result['allow']>0) {

				$and_str="";

				$res=$db->query("select * from ".TABLE_USERS_PACKAGES." where `user_id`='$user_id' and `package_id`='".$result['id']."'".$and_str);

				$no_used = $db->numRows($res); 
				if($no_used>=$result['allow']) continue;
			}

			if(!$user_id && $result['allow']>0) {
				$ip = getRemoteIp();
				$res=$db->query("select * from ".TABLE_USERS_PACKAGES." where `user_id`='0' and `package_id`='".$result['id']."' and ip like '$ip'");
				$no_used = $db->numRows($res);
				if($no_used>=$result['allow']) continue;
			}

			$array_pkg[$i]=$result;
			foreach($array_fields as $key) $array_pkg[$i][$key] = cleanStr($array_pkg[$i][$key]);

			if($array_pkg[$i]['priority']) $array_pkg[$i]['priority_name'] = priorities::getName($array_pkg[$i]['priority']); else $array_pkg[$i]['priority_name']='';

			$array_pkg[$i]['price_curr']=add_currency(format_price($array_pkg[$i]['amount']));

			$i++;

		}
		return $array_pkg;
	}

	function subscriptionExists($group='') {

		global $db;
		$subscr=$db->fetchAssocList("select `id`, `groups` from ".TABLE_PACKAGES." where no_ads!=1");
		if(!count($subscr)) return 0;

		foreach($subscr as $sub) {
	
			if ($sub['groups']==0) return 1;
			$arr = explode(",", $sub['groups']);
			if(in_array($group, $arr)) return 1;
			
		}

		return 0;
	}

	function getAdBasedPlans() {

		global $db;
		global $crt_lang;

		$array=$db->fetchAssocList("select * from ".TABLE_PACKAGES." LEFT JOIN ".TABLE_PACKAGES."_lang on ".TABLE_PACKAGES.".`id` = ".TABLE_PACKAGES."_lang.`id` where `lang_id`='$crt_lang' and no_ads=1 order by `order_no`");

		return $array;

	}

	function getSubscriptionBasedPlans() {

		global $db;
		global $crt_lang;

		$array=$db->fetchAssocList("select * from ".TABLE_PACKAGES." LEFT JOIN ".TABLE_PACKAGES."_lang on ".TABLE_PACKAGES.".`id` = ".TABLE_PACKAGES."_lang.`id` where `lang_id`='$crt_lang' and no_ads!=1 and `active`=1 order by `order_no`");

		return $array;

	}

	function subscriptionAllowed($pkg_id, $user_id) {

		global $db;
		$allow = $db->fetchRow("select allow from ".TABLE_PACKAGES." where id='$pkg_id'");
		if($allow==0) return 1;
		$res = $db->query("select * from ".TABLE_USERS_PACKAGES." where `user_id`='$user_id' and `package_id`='".$pkg_id."'");
		$no_used = $db->numRows($res);
		if($no_used>=$allow) return 0;
		return 1;

	}

	function allowedFor($pkg_details, $group_id, $user_id) {

		global $db;
		if($pkg_details['groups']!=0 && !in_array( $group_id, $pkg_details['groups_array'])) return 0;
		
		if($pkg_details['allow']) {

			$res = $db->query("select * from ".TABLE_USERS_PACKAGES." where `user_id`='$user_id' and `package_id`='".$pkg_details['id']."'");
			$no_used = $db->numRows($res);
			if($no_used>=$pkg_details['allow']) return 0;

		}
		return 1;

	}

	static function isPending($id) {

		global $db;
		$pending = $db->fetchRow("select `pending` from ".TABLE_PACKAGES." where `id` = '$id'");
		return $pending;

	}

}

?>
