<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class rss_config {

	var $error;
	var $tmp;

	public function __construct()
	{
	
		$this->error=''; $this->tmp='';

	}

	function getAll() {

		global $db;
		global $crt_lang;

		$res = $db->query("select * from ".TABLE_RSS." LEFT JOIN ".TABLE_RSS."_lang on ".TABLE_RSS.".`id` = ".TABLE_RSS."_lang.`id` where `lang_id`='$crt_lang'");

		$no = $db->numRows($res);

		$rss_array=$db->fetchAssocList();

		$result = array();
		$i=0;
		global $config_live_site;
		foreach ($rss_array as $row) {
			$result[$i] = $row;
			$result[$i]['rss_link'] = $config_live_site."/feed.php";
			if($no>1) $result[$i]['rss_link'].="?id=".$result[$i]['id'];

			$i++;
		}
		return $result;

	}


	function check_form($id='') {

		global $lng;
		$this->tmp=array();

		global $config_demo;
		if($config_demo==1) $this->addError($lng['general']['errors']['demo'].'<br>');

		global $languages;
		$array_required_lang = array ("short_title", "title");
		foreach ($array_required_lang as $field) {
			foreach ($languages as $lang) {

				$lang_id = $lang['id']; 
				if(!isset($_POST[$field."_".$lang_id]) || !$_POST[$field."_".$lang_id]) { 
					$this->addError($lng['rss']['errors'][$field.'_required'].'<br>'); 
					break;
				}

			}
		}

		$array_required = array ("link");
		foreach ($array_required as $field) {
			if(!isset($_POST["$field"]) || !$_POST["$field"]) $this->addError($lng['rss']['errors'][$field.'_required'].'<br>');
		}

		if(isset($_POST["no_items"]) && !is_numeric($_POST["no_items"]))
			$this->addError($lng['rss']['errors']['invalid_no_items'].'<br>');

		$array_checkboxes = array("enabled");
		$array_all = array("link", "language", "feedburner", "parameters", "no_items");
		$array_all_lang = array("short_title", "title", "description");

		if($this->getError()!='')
		{
			if($id!='') $this->tmp['id']=$id;
			foreach($array_all as $field) {
				if(isset($_POST["$field"])) $this->tmp["$field"] = cleanStr($_POST["$field"]); else $this->tmp["$field"] = "";
			}

			foreach($array_all_lang as $field) {
				foreach ($languages as $lang) {
					$lang_id = $lang['id'];
					if(isset($_POST[$field."_".$lang_id])) $this->tmp[$field][$lang_id] = cleanStr($_POST[$field."_".$lang_id]); else $this->tmp[$field][$lang_id] = "";
				}
			}

			foreach($array_checkboxes as $field) {
				$this->tmp["$field"] = checkbox_value("$field");
			}

			$this->tmp['categories']='0';
			$i=0;
			while (isset($_POST['category'][$i]) && $categ=escape($_POST['category'][$i])){
				if($i) $this->tmp['categories'].=',';
				$this->tmp['categories'].=$categ;
				$i++;
			}

			$this->tmp['users']='0';
			$i=0;
			while (isset($_POST['user'][$i]) && $usr=escape($_POST['user'][$i])){
				if($i) $this->tmp['users'].=',';
				$this->tmp['users'].=$user;
				$i++;
			}

			$this->tmp['packages']='0';
			$i=0;
			while (isset($_POST['package'][$i]) && $pkg=escape($_POST['package'][$i])){
				if($i) $this->tmp['packages'].=',';
				$this->tmp['packages'].=$pkg;
				$i++;
			}
			
			if(isset($_POST['show_fields'])) {

				$this->tmp['show_fields'] = cleanStr($_POST['show_fields']);

			}

			if(isset($_POST['logo_field'])) {
			
				$this->tmp['logo_field'] = cleanStr($_POST['logo_field']);

			}

		}

	}

	function add() {

		global $db;
		$this->clean=array();
		$this->check_form();
		if($this->getError()!='') return 0;
		global $languages;

		$array_checkboxes = array("enabled");
		$array_fields = array("link", "language", "no_items", "feedburner", "type", "logo_field");
		$array_fields_lang = array("short_title", "title", "description");

		foreach($array_fields as $field) {
			if(isset($_POST["$field"])) $clean["$field"] = escape($_POST["$field"]); else $clean["$field"] = "";
		}
		if(!$clean["no_items"]) $clean["no_items"]=0;

		foreach($array_fields_lang as $field) {
			foreach($languages as $lang) {
				$lang_id = $lang['id'];
				if(isset($_POST[$field."_".$lang_id])) $clean[$field][$lang_id] = escape($_POST[$field."_".$lang_id]); else $clean[$field][$lang_id] = "";
			}
		}

		foreach($array_checkboxes as $field) {
			$clean[$field] = checkbox_value($field);
		}

		if($clean['type']==1) { // listings type
		
		// make parameters string
		$categories='';
		$i=0;
		while (isset($_POST['category'][$i]) && $categ=escape($_POST['category'][$i])){
			if(!$categ) continue;
			if($i) $categories.=',';
			$categories.=$categ;
			$i++;
		}

		$users='';
		$i=0;
		while (isset($_POST['user'][$i]) && $usr=escape($_POST['user'][$i])){
			if(!$usr) continue;
			if($i) $users.=',';
			$users.=$usr;
			$i++;
		}

		$packages='';
		$i=0;
		while (isset($_POST['package'][$i]) && $pkg=escape($_POST['package'][$i])){
			if(!$pkg) continue;
			if($i) $packages.=',';
			$packages.=$pkg;
			$i++;
		}

		$clean['parameters']='';
		$sign="";
		if($categories) $clean['parameters'].=$sign."category_id=".$categories;
		if($clean['parameters']) $sign="&";

		if($packages) $clean['parameters'].=$sign."package_id=".$packages;
		if($clean['parameters']) $sign="&";

		if($users) $clean['parameters'].=$sign."user_id=".$users;
		// end make parameters string
		
		}
		else { // users type

		// make parameters string
		$groups='';
		$show_dealers = 0;
		$i=0;
		while (isset($_POST['group'][$i]) && $gr=escape($_POST['group'][$i])){
			if(!$gr) continue;
			if($gr==-1) { $show_dealers = 1; $i++; continue; }
			if($i) $groups.=',';
			$groups.=$gr;
			$i++;
		}

		$order_by = escape($_POST['order_by']);
		$order_way = escape($_POST['order_way']);

		$clean['parameters']='';
		$sign="";
		if($groups) $clean['parameters'].=$sign."group=".$groups;
		if($clean['parameters']) $sign="&";
		
		if($show_dealers) $clean['parameters'].=$sign."store=1";
		if($clean['parameters']) $sign="&";

		$clean['parameters'].=$sign."order_by=".$order_by."&order_way=".$order_way;
		// end make parameters string

		if(isset($_POST['show_fields'])) {

			$clean['show_fields'] = cleanStr($_POST['show_fields']);

		}

		} // end if users type

		$array_all = array("enabled", "link", "language", "feedburner", "parameters", "no_items", "type");

		if($clean['type']==2) { 
			array_push($array_all, "show_fields");
			array_push($array_all, "logo_field");
		}
		
		$sql = "insert into ".TABLE_RSS." set ";
		$i=0;
		foreach($array_all as $key) {
			if($i) $sql.= ", ";
			$sql.="`$key` = '".$clean[$key]."'";
			$i++;
		}

		$res = $db->query($sql);

		$id=$db->insertId();

		foreach ($languages as $lang) {

			$lang_id = $lang['id'];
			$sql_lang = "insert into ".TABLE_RSS."_lang set `id`=$id, `lang_id` = '$lang_id'";
			foreach($array_fields_lang as $key) {
				$sql_lang.=", `$key` = '".$clean[$key][$lang_id]."'";
			}

			$res_lang = $db->query($sql_lang);
		}

		$this->clearRssCache();

		return 1;
	}

	function edit($id) {

		global $db;
		$this->clean=array();
		$this->check_form($id);
		if($this->getError()!='') return 0;
		global $languages;

		$array_checkboxes = array("enabled");
		$array_fields = array("link", "language", "no_items", "feedburner", "type", "logo_field");
		$array_fields_lang = array("short_title", "title", "description");

		foreach($array_fields as $field) {
			if(isset($_POST["$field"])) $clean["$field"] = escape($_POST["$field"]); else $clean["$field"] = "";
		}
		if(!$clean["no_items"]) $clean["no_items"]=0;

		foreach($array_fields_lang as $field) {
			foreach($languages as $lang) {
				$lang_id = $lang['id'];
				if(isset($_POST[$field."_".$lang_id])) $clean[$field][$lang_id] = escape($_POST[$field."_".$lang_id]); else $clean[$field][$lang_id] = "";
			}
		}

		foreach($array_checkboxes as $field) {
			$clean[$field] = checkbox_value($field);
		}

		if($clean['type']==1) { // listings type
		
		// make parameters string
		$categories='';
		$i=0;
		while (isset($_POST['category'][$i]) && $categ=escape($_POST['category'][$i])){
			if(!$categ) continue;
			if($i) $categories.=',';
			$categories.=$categ;
			$i++;
		}

		$users='';
		$i=0;
		while (isset($_POST['user'][$i]) && $usr=escape($_POST['user'][$i])){
			if(!$usr) continue;
			if($i) $users.=',';
			$users.=$usr;
			$i++;
		}

		$packages='';
		$i=0;
		while (isset($_POST['package'][$i]) && $pkg=escape($_POST['package'][$i])){
			if(!$pkg) continue;
			if($i) $packages.=',';
			$packages.=$pkg;
			$i++;
		}

		$clean['parameters']='';
		$sign="";
		if($categories) $clean['parameters'].=$sign."category_id=".$categories;
		if($clean['parameters']) $sign="&";

		if($packages) $clean['parameters'].=$sign."package_id=".$packages;
		if($clean['parameters']) $sign="&";

		if($users) $clean['parameters'].=$sign."user_id=".$users;
		// end make parameters string

		} else { // users type

		// make parameters string
		$groups='';
		$show_dealers = 0;
		$i=0;
		while (isset($_POST['group'][$i]) && $gr=escape($_POST['group'][$i])){
			if(!$gr) continue;
			if($gr==-1) { $show_dealers = 1; $i++; continue; }
			if($i) $groups.=',';
			$groups.=$gr;
			$i++;
		}

		$order_by = escape($_POST['order_by']);
		$order_way = escape($_POST['order_way']);

		$clean['parameters']='';
		$sign="";
		if($groups) $clean['parameters'].=$sign."group=".$groups;
		if($clean['parameters']) $sign="&";
		
		if($show_dealers) $clean['parameters'].=$sign."store=1";
		if($clean['parameters']) $sign="&";

		$clean['parameters'].=$sign."order_by=".$order_by."&order_way=".$order_way;
		// end make parameters string

		if(isset($_POST['show_fields'])) {

			$clean['show_fields'] = cleanStr($_POST['show_fields']);

		}
		} // end if users type
		
		$array_all = array("enabled", "link", "language", "feedburner", "parameters", "no_items", "type");
		if($clean['type']==2) { 
			array_push($array_all, "show_fields");
			array_push($array_all, "logo_field");
		}

		$sql = "update ".TABLE_RSS." set ";
		$i=0;
		foreach($array_all as $key) {
			if($i) $sql.= ", ";
			$sql.="`$key` = '".$clean[$key]."'";
			$i++;
		}
		$sql.=" where id=$id";

		$res = $db->query($sql);

		foreach ($languages as $lang) {

			$lang_id = $lang['id'];
			$sql_lang = "update ".TABLE_RSS."_lang set ";
			$i=0;
			foreach($array_fields_lang as $key) {
				if($i) $sql_lang.=", ";
				$sql_lang.="`$key` = '".$clean[$key][$lang_id]."'";
				$i++;
			}
			$sql_lang.=" where `id`=$id and `lang_id` = '$lang_id'";
			$res_lang = $db->query($sql_lang);
		}

		$this->clearRssCache();

		return 1;

	}

	function getError() {
	
		return $this->error;

	}

	function addError($str) {

		$this->error.=	$str;

	}

	function setError($str) {

		$this->error=$str;

	}

	function Enable($id) {

		global $config_demo;
		if($config_demo==1) return;

		global $db;
		$db->query("update ".TABLE_RSS." set enabled=1 where id=$id");

		$this->clearRssCache();
 
	}

	function Disable($id) {
 
		global $config_demo;
		if($config_demo==1) return;

		global $db;
		$db->query("update ".TABLE_RSS." set enabled=0 where id=$id");

		$this->clearRssCache();

	}

	function delete($id) {

		global $config_demo;
		if($config_demo==1) return;

		global $db;
		$db->query("delete from ".TABLE_RSS." where id=$id");
		$db->query("delete from ".TABLE_RSS."_lang where id=$id");

		$this->clearRssCache();

	}

	function getTmp() {
	
		$this->tmp['array_show_fields'] = array();
		if(!empty( $this->tmp['show_fields'])) $this->tmp['array_show_fields'] = explode(",", $this->tmp['show_fields']);

		return $this->tmp;

	}

	function clearRssCache() {

		// clear cache
		$lc_cache = new cache();
		$lc_cache->clearCache("base_interface");

	}

}
?>
