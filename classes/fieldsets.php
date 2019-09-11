<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class fieldsets {

	var $id;

	public function __construct()
	{
	
	}

	function getId() {
		
		return $this->id;

	}

	static function getFieldset($id='') {
		
		global $db;
		if(!$id) $id=$this->id;
		$result=$db->fetchAssoc("select * from ".TABLE_FIELDSETS." where id=$id");
		return $result;

	}

	static function getName($id) {
		
		if(!$id || !is_numeric($id)) return '';
		global $db;
		$name=$db->fetchRow("select `name` from ".TABLE_FIELDSETS." where id=$id");
		return $name;

	}

	static function getFieldsets() {

		global $db;
		$arr=$db->fetchAssocList("select * from ".TABLE_FIELDSETS." order by `name`");
		return $arr;
	}
}
?>
