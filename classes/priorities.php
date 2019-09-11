<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class priorities {

	var $id;
	var $name;
	var $error;

	public function __construct($id='',$name='')
	{
	
		$this->id=$id;
		$this->name=$name;
	}


	function getId() {
		return $this->id;
	}

	static function getName($id='') {
		
		global $db;
		global $crt_lang;
		$name=$db->fetchRow("select `name` from ".TABLE_PRIORITIES."_lang where `id`=$id and `lang_id`='$crt_lang'");
		return $name;
	}

	static function getOrderNo($id) {
		
		global $db;
		$ord=$db->fetchRow("select `order_no` from ".TABLE_PRIORITIES." where `id`=$id");
		return $ord;
	}

	static function getNameByOrder($order_no) {
		
		global $db;
		global $crt_lang;
		$name=$db->fetchRow("select `name` from ".TABLE_PRIORITIES."_lang LEFT JOIN ".TABLE_PRIORITIES." on ".TABLE_PRIORITIES.".`id` = ".TABLE_PRIORITIES."_lang.`id` where `order_no`=$order_no and `lang_id`='$crt_lang'");
		return $name;
	}

	function getPriorities() {
		
		global $db;
		global $crt_lang;

		$array=$db->fetchAssocList("select * from ".TABLE_PRIORITIES." LEFT JOIN ".TABLE_PRIORITIES."_lang on ".TABLE_PRIORITIES.".`id` = ".TABLE_PRIORITIES."_lang.`id` where `lang_id`='$crt_lang' order by `order_no` desc");
		$i=0;

		$arr=array();
		foreach($array as $result) {	
			$arr[$i]=$result;
			$arr[$i]['price_formatted'] = format_price($result['price']);	
			$i++;

		}
		return $arr;
	}

	function getPriority($id) {

		global $db;
		global $crt_lang;

		$array=$db->fetchAssocList("select * from ".TABLE_PRIORITIES." LEFT JOIN ".TABLE_PRIORITIES."_lang on ".TABLE_PRIORITIES.".`id` = ".TABLE_PRIORITIES."_lang.`id` where `lang_id`='$crt_lang' and `id`='$id'");

		return $array;
	}

	function getPri() {

		global $db;
		global $crt_lang;
		$array=$db->fetchAssocList("select * from ".TABLE_PRIORITIES." left join ".TABLE_PRIORITIES."_lang on ".TABLE_PRIORITIES.".id = ".TABLE_PRIORITIES."_lang.id where `lang_id`='$crt_lang' order by order_no desc");

		$arr=array();
		foreach($array as $result) {	
			$arr[$result['id']]=$result['name'];
		}
		return $arr;
	}

}
?>
