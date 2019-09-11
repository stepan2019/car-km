<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class blocked_phones {

	public function __construct()
	{
	
	}

	function delete($id) {

		global $db;
		global $config_demo;
		if($config_demo==1) return;

		if(!$id) return;
		$res_del=$db->query('delete from '.TABLE_BLOCKED_PHONES.' where `id` = "'.$id.'"');
		return 1;

	}

	function deletePhone($phone) {

		global $db;
		global $config_demo;
		if($config_demo==1) return;

		$res_del=$db->query('delete from '.TABLE_BLOCKED_PHONES.' where `phone` = "'.$phone.'"');
		return 1;

	}

	function deleteFromWhitelist($id){

		global $db;
		global $config_demo;
		if($config_demo==1) return;

		if(!$id) return;
		$res_del=$db->query('delete from '.TABLE_WHITELIST_PHONES.' where `id`="'.$id.'"');
		return 1;

	}

	function searchPhones($post_array, $start_from, $no_per_page) {

		global $db;
		global $appearance_settings;
		$date_format=$appearance_settings["date_format_long"];

		$where='';
		if(isset($post_array['phone']) && $post_array['phone']) $where=" where `phone` like '{$post_array['phone']}'";
		if(isset($post_array['info']) && $post_array['info']) { 
			if($where=='')
				$where=" where `info` like '%{$post_array['info']}%'";
			else $where.=" and `info` like '%{$post_array['info']}%'";
			
		}
		
		$array_phone=$db->fetchAssocList("select * from ".TABLE_BLOCKED_PHONES.$where." order by `id` desc limit ".$start_from.", ".$no_per_page);
		$i = 0;
		foreach($array_phone as $row) {
			$array_phone[$i]['info'] = cleanStr($row['info']);
			$i++;
		}
		
		return $array_phone;

	}

	function getNo($post_array) {

		global $db;

		$where='';
		if(isset($post_array['phone']) && $post_array['phone']) $where=" where `phone` like '{$post_array['phone']}'";
		if(isset($post_array['info']) && $post_array['info']) { 
			if($where=='')
				$where=" where `info` like '%{$post_array['info']}%'";
			else $where.=" and `info` like '%{$post_array['info']}%'";
			
		}

		$no=$db->fetchRow('select count(*) from '.TABLE_BLOCKED_PHONES.$where);
		return $no;

	}

	function searchWhitelistPhones($post_array, $start_from, $no_per_page) {

		global $db;

		$where='';
		if(isset($post_array['phone']) && $post_array['phone']) $where=" where `phone` like '{$post_array['phone']}'";
		if(isset($post_array['info']) && $post_array['info']) { 
			if($where=='')
				$where=" where `info` like '%{$post_array['info']}%'";
			else $where.=" and `info` like '%{$post_array['info']}%'";
			
		}
		
		$array_phone=$db->fetchAssocList("select * from ".TABLE_WHITELIST_PHONES.$where." order by `id` desc limit ".$start_from.", ".$no_per_page);
		$i = 0;
		foreach($array_phone as $row) {
			$array_phone[$i]['info'] = cleanStr($row['info']);
			$i++;
		}
		
		return $array_phone;

	}

	function getNoWhitelistPhones() {

		global $db;

		$where='';
		if(isset($post_array['phone']) && $post_array['phone']) $where=" where `phone` like '{$post_array['phone']}'";
		if(isset($post_array['info']) && $post_array['info']) { 
			if($where=='')
				$where=" where `info` like '%{$post_array['info']}%'";
			else $where.=" and `info` like '%{$post_array['info']}%'";
			
		}

		$no=$db->fetchRow('select count(*) from '.TABLE_WHITELIST_PHONES.$where);
		return $no;

	}
	
	function add($str, $info) {

		global $db;
		global $config_demo;
		if($config_demo==1) return;

		$phone=escape($str);
		$info=escape($info);

		if($this->checkIfWhitelisted($phone)) return 0;
		if( blocked_phones::isBlocked($phone) ) return 0;

		$res=$db->query("insert into ".TABLE_BLOCKED_PHONES." set `phone`= '$phone', `info`='$info'");
		return 1;

	}

	function addBulk($str, $info) {

		global $db;
		global $config_demo;
		if($config_demo==1) return;

		$word_array = explode('|', $str);
		$count_word = count($word_array);
		for($i=0;$i<$count_word;$i++)
		{
			$phone=trim($word_array[$i]);
			if($phone=='') continue;
			$this->add($phone, $info);
		}
	}

	function addToWhitelist($str, $info='') {

		global $db;
		global $config_demo;
		if($config_demo==1) return;
	
		$phone=escape($str);
		$info = escape($info);
		$db->query("insert into ".TABLE_WHITELIST_PHONES." set `phone`= '$phone', `info`='$info'");
		$db->query("delete from ".TABLE_BLOCKED_PHONES." where `phone` like '$phone'");
		return 1;

	}

	function addBulkToWhitelist($str, $info) {

		global $db;
		global $config_demo;
		if($config_demo==1) return;

		$word_array = explode('|', $str);
		$count_word = count($word_array);
		for($i=0;$i<$count_word;$i++)
		{
			$phone=trim($word_array[$i]);
			if($phone=='') continue;
			if($this->checkIfWhitelisted($phone)) continue;

			$this->addToWhitelist($phone, $info);
		}
	}

	function checkIfWhitelisted($phone){
	
		global $db;
		$res=$db->query("select * from ".TABLE_WHITELIST_PHONES." where `phone` like '$phone'");
		return $db->numRows($res);
	    
	
	}
	
	static function isBlocked($phone) {
		
		global $db;

		$res=$db->query("select * from ".TABLE_BLOCKED_PHONES." where `phone` like '$phone'");
		return $db->numRows($res);
		
	}}
?>
