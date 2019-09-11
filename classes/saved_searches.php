<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class saved_searches {

	public function __construct()
	{
	
	}

	function delete($id) {

		global $db;
		$db->query("delete from ".TABLE_SAVED_SEARCHES." where id='$id'");
		return 1;
	}

	static function deleteUser($id) {

		global $db;
		$db->query("delete from ".TABLE_SAVED_SEARCHES." where user_id='$id'");
		return 1;
	}

	function saveSearch($post_array) {

		global $db, $crt_usr;
	
		// check if the search is not empty
		$array_ignore = array("area", "page", "x", "y", "Search", "show");
		$i=0;
		foreach ($post_array as $key=>$value) {
			if(in_array($key, $array_ignore) || !$value) continue;
			$i++;
		}
		if(!$i) {
			global $lng;
			$this->addError($lng['search']['error']['cannot_save_empty_search']);
			return 0;
		}

		$clean['search'] = escape($this->makeSearch($post_array));
		$clean['user_id'] = $crt_usr;

		$clean['ip'] = getRemoteIp();

		$clean['browser'] = escape($_SERVER["HTTP_USER_AGENT"]);
		
		$fields = array("user_id", "ip", "search", "browser");
		
		$timestamp = date("Y-m-d H:i:s");
		$sql = "insert into ".TABLE_SAVED_SEARCHES." set `date` = '$timestamp' ";
		foreach($fields as $f) {

			$sql.=", $f = '".$clean[$f]."'";
		}
		$res = $db->query($sql);
		$id=$db->insertId();
		return $id;
	
	}

	function makeSearch($post_array) {

		$i=0;
		$search_str = "";
		$array_ignore=array("x","y","Search","page", "show");
		foreach($post_array as $key => $val) {
			if(!$val || in_array($key, $array_ignore)) continue;
			if($i) $search_str.="&";
			$search_str.="$key=$val";
			$i++;
		}
		return $search_str;

	}

	function getNoSavedSearches($user_id) {

		global $db;
		$no = $db->fetchRow("select count(*) from ".TABLE_SAVED_SEARCHES." where `user_id` = '$user_id'");
		return $no;
	}

	function getSavedSearches($user_id='') {

		global $db;
		if($user_id) $str_user = " where `user_id` = '$user_id'";
		global $appearance_settings;
		$date_format=$appearance_settings["date_format"];
		global $seo_settings;

		$array = $db->fetchAssocList("select *, date_format(`date`,'$date_format') as `date_nice` from ".TABLE_SAVED_SEARCHES.$str_user." order by `date` desc");
		$result = array();
		$i=0;
		$al = new alerts();
		foreach($array as $row) {

			$result[$i] = $row;
			if($seo_settings['enable_mod_rewrite']) $result[$i]['search_sef'] = $this->makeQueryString($row['search']);
			$search_array = $al->searchToArray($row['search']);
			$search=$al->makeSearchString($search_array);
			$result[$i]['search_str'] = $search;
			$i++;
		}
		return $result;
	}

	function search($post_array, $page,$no_per_page,$order,$order_way) {

		$start=($page-1)*$no_per_page;

		$found = 0; $where = '';
		$join = '';

		foreach($post_array as $key=>$value) {
			if( !$key || $value=='' ) continue;
			
			switch($key) {
				case "id": 
				case "ip": 
					if($found) $where.=" and "; else $where = " where ";
					$where .= " ".TABLE_SAVED_SEARCHES.".`$key` = '$value' ";
					$found = 1;
				break;
				case "username": 
					if($found) $where.=" and "; else $where = " where ";
					//$join = " left join ".TABLE_USERS." on ".TABLE_USERS.".id=".TABLE_SAVED_SEARCHES.".user_id ";
					$where .= " ".TABLE_USERS.".`username` like '$value' ";
					$found = 1;
				break;
				case "date_from": 
					if($found) $where.=" and "; else $where = " where ";
					$where .= " ".TABLE_SAVED_SEARCHES.".`date` >= '$value' ";
					$found = 1;
				break;

				case "date_to": 
					if($found) $where.=" and "; else $where = " where ";
					$where .= " ".TABLE_SAVED_SEARCHES.".`date` <= '$value' ";
					$found = 1;
				break;
			}
		}

		global $appearance_settings, $settings;
		$date_format=$appearance_settings["date_format"];
		global $seo_settings;

		if($settings['enable_username']) $str1 = ", ".TABLE_USERS.".username";
		else	 $str1 = ", ".TABLE_USERS.".`email`, ".TABLE_USERS.".`".$settings['contact_name_field']."`";

		global $db;
		$array = $db->fetchAssocList("select ".TABLE_SAVED_SEARCHES.".*
		$str1
		, date_format(".TABLE_SAVED_SEARCHES.".`date`,'$date_format') as `date_nice` 
		from ".TABLE_SAVED_SEARCHES." 
		left join ".TABLE_USERS." on ".TABLE_USERS.".id=".TABLE_SAVED_SEARCHES.".user_id ".$where." order by ".TABLE_SAVED_SEARCHES.".`$order` $order_way limit ".$start.", ".$no_per_page);

		$result = array();
		$i=0;
		$al = new alerts();
		foreach($array as $row) {

			$result[$i] = $row;
			if($seo_settings['enable_mod_rewrite']) $result[$i]['search_sef'] = $this->makeQueryString($row['search']);
			$search_array = $al->searchToArray($row['search']);
			$search=$al->makeSearchString($search_array);
			$result[$i]['search_str'] = $search;
			$i++;
		}

		$join_users = "";
		if(strstr($where, TABLE_USERS)) 
			$join_users = "left join ".TABLE_USERS." on ".TABLE_USERS.".id=".TABLE_SAVED_SEARCHES.".user_id ";
		
		$no_searches = $db->fetchRow("select count(*) from ".TABLE_SAVED_SEARCHES." $join_users ".$where);
		$this->setNoSearches($no_searches);

		return $result;

	}

	function getNoSearches() {

		return $this->no_searches;

	}

	function setNoSearches($no_searches) {

		$this->no_searches = $no_searches;

	}

	function belongsToUser($id, $user_id) {

		global $db;
		$belongs = $db->fetchRow("select count(*) from ".TABLE_SAVED_SEARCHES." where `id` = $id and `user_id` = $user_id");
		return $belongs;
	}

	function getError() {
	
		return $this->error;

	}

	function addError($str) {

		$this->error.=	$str;

	}

	function makeQueryString($str) {

		global $config_live_site;

		$array = explode("&", $str);
		$post_array = array();
		foreach($array as $row) {
			$pa = explode("=", $row);
			$post_array[$pa[0]] = $pa[1];
		}
		
		$seo = new seo();
		$url_str = $seo->makeSearchLink($post_array);

		return $url_str;

	}

}

?>
