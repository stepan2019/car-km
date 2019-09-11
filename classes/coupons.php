<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class coupons {


	var $id;
	var $array;

	public function __construct()
	{
	
	}

	function getId() {

		return $this->id;

	}

	static function deleteListing($id) {

		global $db;
		$db->query("delete from ".TABLE_DISCOUNTS." where object_id = $id and ( type like 'newad' or type like 'renewad' or type like 'upgrade' )");
		return 1; 

	}

	static function deleteUser($id) {

		global $db;
		$db->query("delete from ".TABLE_DISCOUNTS." where object_id = $id and type like 'store'");
		return 1; 

	}

	static function deleteSubscription($id) {

		global $db;
		$db->query("delete from ".TABLE_DISCOUNTS." where object_id = $id and type like 'newpkg'");
		return 1; 

	}

	function getCoupon($id='') {
		
		if(!$id) $id=$this->id;
		global $db;
		$result=$db->fetchAssoc('select * from '.TABLE_COUPONS.' where `id`="'.$id.'"');

		if($result['groups']) {
			$result['groups_array']=explode(",",$result['groups']);
			for($a=0; $a<count($result['groups_array']);$a++) {
				$result['groups_array'][$a]=trim($result['groups_array'][$a]);
			}
		}

		return $result;

	}

	function getDiscount($id='') {
		
		if(!$id) $id=$this->id;
		global $db;
		$result=$db->fetchRow('select discount from '.TABLE_COUPONS.' where `id`="'.$id.'"');
		return $result;

	}

	static function getDiscountByCode($code, $user_id='', $group='', $type='') {
		
		global $db;
		if($type && ($type=="ads" || $type=="store")) $str_type = " and `$type` = $type"; else $str_type='';
		if($group) $str_group = " and ( `groups` REGEXP '\[\[:<:\]\]$group\[\[:>:\]\]'  or `groups` = -1 )";
		else $str_group = '';
		$arr=$db->fetchAssoc("select count(*) as `no`, ".TABLE_COUPONS.".* from ".TABLE_COUPONS." where `code` like '$code'".$str_type.$str_group." group by `code`");

		if(!$arr['no']) return 0;

		// if guest or checking not needed, don't check for allow times
		if($user_id==0) return $arr;

		$allow = $arr['allow'];

		// allow unlimited times
		if($allow==0) return $arr;

		// check if number of times allowed is not passed
		$no = $db->fetchRow("select count(*) from ".TABLE_DISCOUNTS." where code like '$code' and `user_id` = '$user_id'");
		if($no>=$allow) return 0;

		return $arr;

	}

	function getCode($id='') {
		
		if(!$id) $id=$this->id;
		global $db;
		$res=$db->fetchRow('select `code` from '.TABLE_COUPONS.' where `id`="'.$id.'"');
		return $result;

	}


	static function typeExists($type) {

		if($type!='ads' && $type!='store') return 0;

		global $db;
		$exists = $db->fetchRow("select count(*) from ".TABLE_COUPONS." where `$type` = 1");
		return $exists;

	}

	function addDiscount($object_id, $type, $code, $user_id) {

		global $db;

		//$del = $db->query("delete from ".TABLE_DISCOUNTS." where `object_id` = $object_id and `type` like '$type'");

		$res = $db->query("insert into ".TABLE_DISCOUNTS." (`object_id`, `type`, `code`, `user_id`) values ($object_id, '$type', '$code', '$user_id')");

		return 1;

	}

	static function codeValid($code, $type, $user_id, $group) {
		
		if(!$code || !$type) return 0;
		if($type!="ads" && $type!="store") return 0;
		
		global $db;
		$arr = $db->fetchAssoc("select count(*) as no, ".TABLE_COUPONS.".* from ".TABLE_COUPONS." where `code` like '$code' and `$type` = 1 and ( `groups` REGEXP '\[\[:<:\]\]$group\[\[:>:\]\]'  or `groups` = -1 ) group by `code`");

		if(!$arr['no']) return 0;

		// if guest, don't check for allow times
		if($user_id==0) return $arr;

		$allow = $arr['allow'];

		// allow unlimited times
		if($allow==0) return $arr;

		// check if number of times allowed is not passed
		$no = $db->fetchRow("select count(*) from ".TABLE_DISCOUNTS." where code like '$code' and `user_id` = '$user_id'");
		if($no>=$allow) return 0;

		return $arr;

	}

}
?>
