<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class blocked_ips {

	public function __construct()
	{
	
	}

	function delete($id) {

		global $db;
		global $config_demo;
		if($config_demo==1) return;

		if(!$id) return;
		$res_del=$db->query('delete from '.TABLE_BLOCKED_IPS.' where `id`="'.$id.'"');
		return 1;

	}

	function deleteIP($ip) {

		global $db;
		global $config_demo;
		if($config_demo==1) return;

		$res_del=$db->query('delete from '.TABLE_BLOCKED_IPS.' where `ip`="'.$ip.'"');
		return 1;

	}

	function deleteFromWhitelist($id){

		global $db;
		global $config_demo;
		if($config_demo==1) return;

		if(!$id) return;
		$res_del=$db->query('delete from '.TABLE_WHITELIST_IPS.' where `id`="'.$id.'"');
		return 1;

	}

	function searchIPs($post_array, $start_from, $no_per_page) {

		global $db;
		global $appearance_settings;
		$date_format=$appearance_settings["date_format_long"];

		$where='';
		if(isset($post_array['ip']) && $post_array['ip']) $where=" where `ip` like '{$post_array['ip']}'";
		if(isset($post_array['info']) && $post_array['info']) { 
			if($where=='')
				$where=" where `info` like '%{$post_array['info']}%'";
			else $where.=" and `info` like '%{$post_array['info']}%'";
			
		}
		
		$array_ip=$db->fetchAssocList("select *, date_format(`date_expires`,'$date_format') as date_expires_nice from ".TABLE_BLOCKED_IPS.$where." order by `id` desc limit ".$start_from.", ".$no_per_page);
		$i = 0;
		foreach($array_ip as $row) {
			$array_ip[$i]['info'] = cleanStr($row['info']);
			$i++;
		}
		
		return $array_ip;

	}

	function getNo($post_array) {

		global $db;

		$where='';
		if(isset($post_array['ip']) && $post_array['ip']) $where=" where `ip` like '{$post_array['ip']}'";
		if(isset($post_array['info']) && $post_array['info']) { 
			if($where=='')
				$where=" where `info` like '%{$post_array['info']}%'";
			else $where.=" and `info` like '%{$post_array['info']}%'";
			
		}

		$no=$db->fetchRow('select count(*) from '.TABLE_BLOCKED_IPS.$where);
		return $no;

	}

	function searchWhitelistIPs($post_array, $start_from, $no_per_page) {

		global $db;

		$where='';
		if(isset($post_array['ip']) && $post_array['ip']) $where=" where `ip` like '{$post_array['ip']}'";
		if(isset($post_array['info']) && $post_array['info']) { 
			if($where=='')
				$where=" where `info` like '%{$post_array['info']}%'";
			else $where.=" and `info` like '%{$post_array['info']}%'";
			
		}
		
		$array_ip=$db->fetchAssocList("select * from ".TABLE_WHITELIST_IPS.$where." order by `id` desc limit ".$start_from.", ".$no_per_page);
		$i = 0;
		foreach($array_ip as $row) {
			$array_ip[$i]['info'] = cleanStr($row['info']);
			$i++;
		}
		
		return $array_ip;

	}

	function getNoWhitelistIPs() {

		global $db;

		$where='';
		if(isset($post_array['ip']) && $post_array['ip']) $where=" where `ip` like '{$post_array['ip']}'";
		if(isset($post_array['info']) && $post_array['info']) { 
			if($where=='')
				$where=" where `info` like '%{$post_array['info']}%'";
			else $where.=" and `info` like '%{$post_array['info']}%'";
			
		}

		$no=$db->fetchRow('select count(*) from '.TABLE_WHITELIST_IPS.$where);
		return $no;

	}

	function add($str, $info) {

		global $db;
		global $config_demo;
		if($config_demo==1) return;

		$ip=escape($str);
		$info=escape($info);

		if($this->checkIfWhitelisted($ip)) return 0;
		if( blocked_ips::isBlocked($ip) ) return 1;

		$res=$db->query("insert into ".TABLE_BLOCKED_IPS." set `ip`= '$ip', `type`=1, `info`='$info'");
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
			$ip=trim($word_array[$i]);
			if($ip=='') continue;
			if(!validator::valid_ip($ip)) continue;
			$this->add($ip, $info);
		}
	}

	function addToWhitelist($str, $info='') {

		global $db;
		global $config_demo;
		if($config_demo==1) return;
	
		$ip=escape($str);
		$info = escape($info);
		$db->query("insert into ".TABLE_WHITELIST_IPS." set `ip`= '$ip', `info`='$info'");
		$db->query("delete from ".TABLE_BLOCKED_IPS." where `ip` like '$ip'");
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
			$ip=trim($word_array[$i]);
			if($ip=='') continue;
			if($this->checkIfWhitelisted($ip)) continue;
			if(!validator::valid_ip($ip)) continue;
			$this->addToWhitelist($ip, $info);
		}
	}

	function checkIfWhitelisted($ip){
	
		global $db;
		$res=$db->query("select * from ".TABLE_WHITELIST_IPS." where `ip` like '$ip'");
		return $db->numRows($res);
	    
	
	}
	
	static function isBlocked($ip) {
		
		global $db;

		$res=$db->query("select * from ".TABLE_BLOCKED_IPS." where `ip` like '$ip'");
		return $db->numRows($res);
		
	}
	
	function addTemporary($ip, $hours, $info) {
	
		global $db, $lng;
		if($this->checkIfWhitelisted($ip)) return 0;
	      
		$timestamp = date("Y-m-d H:i:s");
	      
		if( blocked_ips::isBlocked($ip) ) return 1;
		
		$res=$db->query("insert into ".TABLE_BLOCKED_IPS." set `ip`='$ip', `blocked_for`='$hours', `date_expires` = date_add('$timestamp', interval $hours hour), `info`='$info', `type`='2'");

		return 1;
	
	}

	
	
}
?>
