<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class blocked_emails {

	public function __construct()
	{
	
	}

	function delete($id) {

		global $db;
		global $config_demo;
		if($config_demo==1) return;

		if(!$id) return;
		$res_del=$db->query('delete from '.TABLE_BLOCKED_EMAILS.' where `id` = "'.$id.'"');
		return 1;

	}

	function deleteEmail($email) {

		global $db;
		global $config_demo;
		if($config_demo==1) return;

		$res_del=$db->query('delete from '.TABLE_BLOCKED_EMAILS.' where `email` = "'.$email.'"');
		return 1;

	}

	function deleteFromWhitelist($id){

		global $db;
		global $config_demo;
		if($config_demo==1) return;

		if(!$id) return;
		$res_del=$db->query('delete from '.TABLE_WHITELIST_EMAILS.' where `id`="'.$id.'"');
		return 1;

	}

	function searchEmails($post_array, $start_from, $no_per_page) {

		global $db;
		global $appearance_settings;
		$date_format=$appearance_settings["date_format_long"];

		$where='';
		if(isset($post_array['email']) && $post_array['email']) $where=" where `email` like '{$post_array['email']}'";
		if(isset($post_array['info']) && $post_array['info']) { 
			if($where=='')
				$where=" where `info` like '%{$post_array['info']}%'";
			else $where.=" and `info` like '%{$post_array['info']}%'";
			
		}
		
		$array_email=$db->fetchAssocList("select * from ".TABLE_BLOCKED_EMAILS.$where." order by `id` desc limit ".$start_from.", ".$no_per_page);
		$i = 0;
		foreach($array_email as $row) {
			$array_email[$i]['info'] = cleanStr($row['info']);
			$i++;
		}
		
		return $array_email;

	}

	function getNo($post_array) {

		global $db;

		$where='';
		if(isset($post_array['email']) && $post_array['email']) $where=" where `email` like '{$post_array['email']}'";
		if(isset($post_array['info']) && $post_array['info']) { 
			if($where=='')
				$where=" where `info` like '%{$post_array['info']}%'";
			else $where.=" and `info` like '%{$post_array['info']}%'";
			
		}

		$no=$db->fetchRow('select count(*) from '.TABLE_BLOCKED_EMAILS.$where);
		return $no;

	}

	function searchWhitelistEmails($post_array, $start_from, $no_per_page) {

		global $db;

		$where='';
		if(isset($post_array['email']) && $post_array['email']) $where=" where `email` like '{$post_array['email']}'";
		if(isset($post_array['info']) && $post_array['info']) { 
			if($where=='')
				$where=" where `info` like '%{$post_array['info']}%'";
			else $where.=" and `info` like '%{$post_array['info']}%'";
			
		}
		
		$array_email=$db->fetchAssocList("select * from ".TABLE_WHITELIST_EMAILS.$where." order by `id` desc limit ".$start_from.", ".$no_per_page);
		$i = 0;
		foreach($array_email as $row) {
			$array_email[$i]['info'] = cleanStr($row['info']);
			$i++;
		}
		
		return $array_email;

	}

	function getNoWhitelistEmails() {

		global $db;

		$where='';
		if(isset($post_array['email']) && $post_array['email']) $where=" where `email` like '{$post_array['email']}'";
		if(isset($post_array['info']) && $post_array['info']) { 
			if($where=='')
				$where=" where `info` like '%{$post_array['info']}%'";
			else $where.=" and `info` like '%{$post_array['info']}%'";
			
		}

		$no=$db->fetchRow('select count(*) from '.TABLE_WHITELIST_EMAILS.$where);
		return $no;

	}
	
	function add($str, $info) {

		global $db;
		global $config_demo;
		if($config_demo==1) return;
	
		$email=escape($str);
		$info=escape($info);

		if($this->checkIfWhitelisted($email)) return 0;
		if( blocked_emails::isBlocked($email) ) return 1;
		
		$res=$db->query("insert into ".TABLE_BLOCKED_EMAILS." set `email`= '$email', `info`='$info'");
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
			$email=trim($word_array[$i]);
			if($email=='') continue;
			if(!validator::valid_email($email)) continue;
			$this->add($email, $info);
		}
	}

	function addToWhitelist($str, $info='') {

		global $db;
		global $config_demo;
		if($config_demo==1) return;
	
		$email=escape($str);
		$info = escape($info);
		$db->query("insert into ".TABLE_WHITELIST_EMAILS." set `email`= '$email', `info`='$info'");
		$db->query("delete from ".TABLE_BLOCKED_EMAILS." where `email` like '$email'");
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
			$email=trim($word_array[$i]);
			if($email=='') continue;
			if($this->checkIfWhitelisted($email)) continue;
			if(!validator::valid_email($email)) continue;
			$this->addToWhitelist($email, $info);
		}
	}

	function checkIfWhitelisted($email){
	
		global $db;
		$res=$db->query("select * from ".TABLE_WHITELIST_EMAILS." where `email` like '$email'");
		return $db->numRows($res);
	    
	
	}
	
	static function isBlocked($email) {
		
		global $db;

		$res=$db->query("select * from ".TABLE_BLOCKED_EMAILS." where `email` like '$email'");
		return $db->numRows($res);
		
	}}
?>
