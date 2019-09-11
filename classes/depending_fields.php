<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class depending_fields {

	public function __construct()
	{
	
	}

	function getId() {
		
		return $this->id;

	}

	function getRegId($name, $table, $categ=0, $dep_id=0) {
	
		global $db;
		global $config_table_prefix;
		global $crt_lang;
		if($categ) {
			global $config_abs_path;
			require_once $config_abs_path."/classes/categories.php";
			$fieldset = categories::getFieldset($categ);
			if($fieldset) $str_set = " and (`set_id`='$fieldset' or `set_id`=0)";
//***
		} else 
		if($dep_id) {
			$str_set = " and `dep`='$dep_id'";
		} else $str_set = "";
		$row=$db->fetchRow("select `id` from $config_table_prefix$table where name like '$name' and `lang_id`='$crt_lang' ".$str_set);
		if(!$row) return 0;
		return $row;

	}

	function getDependingField($id) {

		global $db;
		global $crt_lang;

		$result=$db->fetchAssoc("select * from `".TABLE_DEPENDING_FIELDS."` left join ".TABLE_DEPENDING_FIELDS."_lang on ".TABLE_DEPENDING_FIELDS.".`id` = ".TABLE_DEPENDING_FIELDS."_lang.`id` where `lang_id` = '$crt_lang' and ".TABLE_DEPENDING_FIELDS.".id='$id'");
		return $result;

	}


	function getTableN($n, $id='') {
		
		global $db;
		if(!$id) $id=$this->id;
		$result=$db->fetchRow("select `table$n` from ".TABLE_DEPENDING_FIELDS." where id='".$id."'");
		return $result;

	}

	function getTable($table, $set='') {
		
		global $db;
		global $config_table_prefix;
		global $crt_lang;
		if(!$set) { 
			$set = 0;
			$where_str = " where `set_id` = 0 and `lang_id` = '$crt_lang'";
		} else {
			$where_str = " where ( `set_id` REGEXP '\[\[:<:\]\]$set\[\[:>:\]\]'  or `set_id` = 0 ) and `lang_id` = '$crt_lang' ";
		}
		global $order_dep_fields;
		if($order_dep_fields) $order_by="name";
		else $order_by="id";
		$result=$db->fetchAssocList("select * from ".$config_table_prefix.$table.$where_str." order by `$order_by`");
		return $result;

	}

	function getTableStrict($table, $set='') {
		
		global $db;
		global $config_table_prefix;
		if(!$set) $set=0;
		global $crt_lang;
		$where_str = " where `set_id` = $set and `lang_id` = '$crt_lang'";
		global $order_dep_fields;
		if($order_dep_fields) $order_by="name";
		else $order_by="id";
		$result=$db->fetchAssocList("select * from ".$config_table_prefix.$table.$where_str." order by `$order_by`");
		return $result;

	}

	function getElements($table, $lang_id, $set='') {

		global $db;
		global $config_table_prefix;

		if(!$set) { 
			$where_str = " where `lang_id` = '".escape($lang_id)."'";
		} else {
			$where_str = " where ( `dep` REGEXP '\[\[:<:\]\]$set\[\[:>:\]\]'  or `dep` = 0 ) and `lang_id` = '".escape($lang_id)."' ";
		}
		global $order_dep_fields;
		if($order_dep_fields) $order_by="name";
		else $order_by="id";
		$result=$db->fetchRowList("select name from ".$config_table_prefix.$table.$where_str." order by `$order_by`");

		$str = '';
		foreach($result as $res) { if($str) $str.="|"; $str.=$res; }
		return $str;

	}

	function getSecondTable($table, $dep='') {
		
		global $db;
		global $config_table_prefix;
		global $crt_lang;
		if($dep) $dep_str=" where dep=$dep and `lang_id` = '$crt_lang'"; 
		else $dep_str=" where `lang_id` = '$crt_lang'";
		global $order_dep_fields;
		if($order_dep_fields) $order_by="name";
		else $order_by="id";
		$result=$db->fetchAssocList("select * from ".$config_table_prefix.$table.$dep_str." order by `$order_by`");
		return $result;

	}

	function getTableLang($table, $set='') {
		
		global $db;
		global $config_table_prefix;
		global $crt_lang;

		if(!$set) {
			$set = 0;
			$where_str = " where `set_id` = 0 and `lang_id` != '$crt_lang'";
		} else {
			$where_str = " where ( `set_id` REGEXP '\[\[:<:\]\]$set\[\[:>:\]\]'  or `set_id` = 0 ) and `lang_id` != '$crt_lang' ";
		}
		$arr=$db->fetchAssocList("select ".$config_table_prefix.$table.".*, ".TABLE_LANGUAGES.".`name` as `language_name`, ".TABLE_LANGUAGES.".`image` from ".$config_table_prefix.$table." left join ".TABLE_LANGUAGES." on ".$config_table_prefix.$table.".`lang_id`=".TABLE_LANGUAGES.".`id` ".$where_str." order by ".$config_table_prefix.$table.".`id`, ".$config_table_prefix.$table.".`lang_id`");

		$result = array();
		$i = 0;
		foreach($arr as $row) {

			$id = $row['id'];
			$default = $db->fetchRow("select `name` from ".$config_table_prefix.$table." where id='$id' and `lang_id`='$crt_lang'");
			$row['default_val'] = $default;
			$result[$i] = $row;
			$i++;

		}

		return $result;

	}

	function getTableStrictLang($table, $set='') {
		
		global $db;
		global $config_table_prefix;
		global $crt_lang;
		if(!$set) $set=0;

		$where_str = " where `set_id` = $set and `lang_id` != '$crt_lang' and ".TABLE_LANGUAGES.".enabled=1 ";
		$arr=$db->fetchAssocList("select ".$config_table_prefix.$table.".*, ".TABLE_LANGUAGES.".`name` as `language_name`, ".TABLE_LANGUAGES.".`image` from ".$config_table_prefix.$table." left join ".TABLE_LANGUAGES." on ".$config_table_prefix.$table.".`lang_id`=".TABLE_LANGUAGES.".`id` ".$where_str." order by ".$config_table_prefix.$table.".`id`, ".$config_table_prefix.$table.".`lang_id`");

		$result = array();
		$i = 0;
		foreach($arr as $row) {

			$id = $row['id'];
			$default = $db->fetchRow("select `name` from ".$config_table_prefix.$table." where id='$id' and `lang_id`='$crt_lang'");
			$row['default_val'] = $default;
			$result[$i] = $row;
			$i++;

		}

		return $result;
	}

	function getSecondTableLang($table, $dep='') {

		global $db;
		global $config_table_prefix;
		global $crt_lang;

		if($dep) $where_str = " where `dep` = $dep and `lang_id` != '$crt_lang' and ".TABLE_LANGUAGES.".enabled=1 ";
		else  $where_str = " where `lang_id` != '$crt_lang'";
		$arr=$db->fetchAssocList("select ".$config_table_prefix.$table.".*, ".TABLE_LANGUAGES.".`name` as `language_name`, ".TABLE_LANGUAGES.".`image` from ".$config_table_prefix.$table." left join ".TABLE_LANGUAGES." on ".$config_table_prefix.$table.".`lang_id`=".TABLE_LANGUAGES.".`id` ".$where_str." order by ".$config_table_prefix.$table.".`id`, ".$config_table_prefix.$table.".`lang_id`");

		$result = array();
		$i = 0;
		foreach($arr as $row) {

			$id = $row['id'];
			$default = $db->fetchRow("select `name` from ".$config_table_prefix.$table." where id='$id' and `lang_id`='$crt_lang'");
			$row['default_val'] = $default;
			$result[$i] = $row;
			$i++;

		}

		return $result;

	}


	function getInput($id) {

		global $db;
		global $crt_lang;
		$result = $db->fetchAssoc("select `caption1`, `caption2`, `caption3`, `caption4`, `caption5`, `table1`, `table2`, `table3`, `table4`, `table5`, `top_str1`, `top_str2`, `top_str3`, `top_str4`, `top_str5` from ".TABLE_DEPENDING_FIELDS." left join ".TABLE_DEPENDING_FIELDS."_lang on ".TABLE_DEPENDING_FIELDS."_lang.`id` = ".TABLE_DEPENDING_FIELDS.".`id` where lang_id = '$crt_lang' and ".TABLE_DEPENDING_FIELDS.".`id` = $id");

		return $result;

	}

	function getIdByName($table, $name, $where=''){

		global $db;
		global $config_table_prefix;
		global $crt_lang;

		$id = $db->fetchRow("select `id` from $config_table_prefix$table where `name` like '$name' and `lang_id` = '$crt_lang'".$where);
		return $id;

	}

	function getIdByNameLang($table, $name, $lang){

		global $db;
		global $config_table_prefix;
		if(!$lang) {
			global $crt_lang;
			$lang = $crt_lang;
		}
		$id = $db->fetchRow("select `id` from $config_table_prefix$table where `name` like '$name' and `lang_id` = '$lang'");
		return $id;

	}

	function getElement($table, $id, $lang =''){

		global $db;
		global $config_table_prefix;
		if(!$lang) {
			global $crt_lang;
			$lang = $crt_lang;
		}
		$value = $db->fetchRow("select `name` from $config_table_prefix$table where `id` = '$id' and `lang_id` = '$lang'");
		return $value;

	}


	function translateField( $id, $caption, $lang_from, $lang_to, $value ) {

		global $db;

		$result=$db->fetchAssoc("select * from `".TABLE_DEPENDING_FIELDS."` where id='$id'");
		for($i=1; $i<=5; $i++) {
			if($caption==$result['caption'.$i]) { $table = $result['table'.$i]; break; }
		}
		$elem_id = $this->getIdByNameLang($table, escape($value), $lang_from);
		$element = $this->getElement($table, $elem_id, $lang_to);
		if(!$element) return $value;
		return $element;
	}

	function getIdNameNo($field, $table, $lang_id, $set='') {

		global $db;
		global $config_table_prefix;

		if(!$set) { 
			$where_str = " where `lang_id` = '".escape($lang_id)."'";
		} else {
			$where_str = " where ( `dep` REGEXP '\[\[:<:\]\]$set\[\[:>:\]\]'  or `dep` = 0 ) and `lang_id` = '".escape($lang_id)."' ";
		}
		global $order_dep_fields;
		if($order_dep_fields) $order_by="name";
		else $order_by="id";

		$result=$db->fetchAssocList("select `id`, `name`, `no` from ".$config_table_prefix.$table." left join ".$config_table_prefix."location_no_ads on (".$config_table_prefix.$table.".`name` = ".$config_table_prefix."location_no_ads.`val` and `field`='$field') ".$where_str." order by `$order_by`");

		/*$str = '';
		foreach($result as $res) { if($str) $str.="|"; $str.=$res['id']."^".$res['name']."^".$res['no']; }
		return $str;*/
		return $result;

	}


}
?>
