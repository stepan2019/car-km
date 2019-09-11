<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class featured_plans {

	var $id;
	var $name;
	var $error;

	public function __construct($id='',$name='')
	{
	
		$this->id=$id;
		
	}


	function getId() {
		return $this->id;
	}

	static function getDaysByOrder($order_no) {
		
		global $db;
		global $crt_lang;
		$name=$db->fetchRow("select `no_days` from ".TABLE_FEATURED_PLANS." where `order_no`=$order_no");
		return $name;
	}

	
	static function getPrice($id='') {
		
		global $db;
		global $crt_lang;
		$name=$db->fetchRow("select `amount` from ".TABLE_FEATURED_PLANS." where `id`=$id");
		return $name;
	}

	static function getDays($id='') {
		
		global $db;
		global $crt_lang;
		$name=$db->fetchRow("select `no_days` from ".TABLE_FEATURED_PLANS." where `id`=$id");
		return $name;
	}

	static function getOrderNo($id) {
		
		global $db;
		$ord=$db->fetchRow("select `order_no` from ".TABLE_FEATURED_PLANS." where `id`=$id");
		return $ord;
	}

	function getFeaturedPlans() {
		
		global $db;
		global $crt_lang;

		$array=$db->fetchAssocList("select * from ".TABLE_FEATURED_PLANS." order by `order_no` desc");
		$i=0;

		$arr=array();
		foreach($array as $result) {	
			$arr[$i]=$result;
			$arr[$i]['amount_formatted'] = format_price($result['amount']);	
			$i++;

		}
		return $arr;
	}

	function getFeaturedPlan($id) {

		global $db;
		global $crt_lang;

		$array=$db->fetchAssoc("select * from ".TABLE_FEATURED_PLANS." where `id`='$id'");

		return $array;
	}

}
?>
