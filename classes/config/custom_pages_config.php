<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class custom_pages_config {

	var $error;
	var $tmp;
	var $last;

	public function __construct()
	{
	
		$this->error=''; $this->tmp='';

	}

	function Activate($id='') {

		global $config_demo;
		if($config_demo==1) return;

		global $db;
		if(!$id) $id=$this->id;
		$res=$db->query('update '.TABLE_CUSTOM_PAGES.' set active=1 where id="'.$id.'"');

		$this->clearNavbarLinksCache();

		return 1;
	}

	function Deactivate($id='') {

		global $config_demo;
		if($config_demo==1) return;

		global $db;
		if(!$id) $id=$this->id;
		$res=$db->query('update '.TABLE_CUSTOM_PAGES.' set active=0 where id="'.$id.'"');
		
		$this->clearNavbarLinksCache();

		return 1;
	}

	function getType($id='') {
		global $db;
		if(!$id) $id=$this->id;
		$title=$db->fetchRow('select type from '.TABLE_CUSTOM_PAGES.' where id="'.$id.'"');
		return $title;
	}

	function isParent($id='') {
		global $db;
		if(!$id) $id=$this->id;
		$parent=$db->fetchRow('select count(*) from '.TABLE_CUSTOM_PAGES.' where `parent_id`="'.$id.'"');
		return $parent;
	}

	function getReadOnly($id='') {

		global $db;
		if(!$id) $id=$this->id;
		$ro=$db->fetchRow('select read_only from '.TABLE_CUSTOM_PAGES.' where id="'.$id.'"');
		return $ro;

	}

	function getCustomPageLang($id='') {

		global $db;
		if(!$id) $id=$this->id;

		$row=$db->fetchAssoc("select * from ".TABLE_CUSTOM_PAGES." where id=".$id."");
		$array_lang=$db->fetchAssocList("select * from ".TABLE_CUSTOM_PAGES."_lang where id=".$id."");

		foreach($array_lang as $cp_lang) {

			$lang_id = $cp_lang['lang_id'];
			foreach ($cp_lang as $key=>$value) {
				if($key=='id' || $key=='lang_id') continue;
				$row[$key][$lang_id] = cleanStr($value);
			}
		}

		return $row;

	}

	function getAll() {

		global $db;
		global $crt_lang;
		$array=$db->fetchAssocList("select * from ".TABLE_CUSTOM_PAGES." LEFT JOIN ".TABLE_CUSTOM_PAGES."_lang on ".TABLE_CUSTOM_PAGES.".`id` = ".TABLE_CUSTOM_PAGES."_lang.`id` where `lang_id`='$crt_lang' order by `order_no`");

		$i=1;

		$arr=array();
		foreach($array as $result) {	

			$arr[$i]=$result;

			$fields = array("title", "content", "meta_keywords", "meta_description");
			foreach ($fields as $key) $arr[$i][$key] = cleanStr($arr[$i][$key]);

			$arr[$i]['last'] = 0;	
			$arr[$i]['parent'] = $this->isParent($result['id']);
			$i++;
		}

		if($i) $arr[$i-1]['last'] = 1;
		return $arr;
	}


	function search($word) {

		global $db;
		global $crt_lang;

		$array=$db->fetchAssocList("select * from ".TABLE_CUSTOM_PAGES." LEFT JOIN ".TABLE_CUSTOM_PAGES."_lang on ".TABLE_CUSTOM_PAGES.".`id` = ".TABLE_CUSTOM_PAGES."_lang.`id` where `lang_id`='$crt_lang' and `content` like '%".$word."%' order by `order_no`");

		$i=0;

		$arr=array();
		foreach($array as $result) {	

			$arr[$i]=$result;
			$a = array("title", "content", "meta_keywords", "meta_description");
			foreach ($a as $key) $arr[$i][$key] = cleanStr($arr[$i][$key]);

			$arr[$i]['last'] = 0;	
			$i++;
		}

		if($i) $arr[$i-1]['last'] = 1;
		return $arr;
	}

	function orderExists($order) {

		global $db;
		$res=$db->query("select * from ".TABLE_CUSTOM_PAGES." where order_no='$order'");
		if($db->numRows($res)) return 1;
		return 0;

	}

	function delete($id) {

		global $db;
		global $config_demo;
		if($config_demo==1) return;

		// remake order
		$this->remakeOrder($id);
		$ro = $this->getReadOnly($id);
		$parent = $db->fetchRow("select `parent_id` from ".TABLE_CUSTOM_PAGES." where id=$id");
		if(!$ro) {

			$res=$db->query("delete from ".TABLE_CUSTOM_PAGES." where id=$id");
			$res=$db->query("delete from ".TABLE_CUSTOM_PAGES."_lang where id=$id");

		}

		// change parent id
		$db->query("update ".TABLE_CUSTOM_PAGES." set `parent_id` = '$parent' where `parent_id` = $id");

		$slug = new slugs();
		$slug->deleteContent($id);

		$this->clearNavbarLinksCache();

		return 1;

	}

	function remakeOrder($id) {
	
		global $db;
		$order=$db->fetchRow("select `order_no` from ".TABLE_CUSTOM_PAGES." where id='$id'");
		if(!$order) return;
		$res=$db->query("update ".TABLE_CUSTOM_PAGES." set `order_no`=`order_no`-1 where `order_no`>='$order'");

		$this->clearNavbarLinksCache();

	}

	function moveUp($id) {
		
		global $config_demo;
		if($config_demo==1) return;

		global $db;
		if(!$id) $id=$this->id;
		$order_no=$db->fetchRow("select `order_no` from ".TABLE_CUSTOM_PAGES." where `id`='$id'");
		$goto=$order_no-1;
		$res1=$db->query("update ".TABLE_CUSTOM_PAGES." set `order_no`='1000' where `id`='$id'");
		$res2=$db->query("update ".TABLE_CUSTOM_PAGES." set `order_no`='$order_no' where `order_no`='$goto'");
		$res3=$db->query("update ".TABLE_CUSTOM_PAGES." set `order_no`='$goto' where `id`='$id'");

		$this->clearNavbarLinksCache();

		return 1;
	}

	function moveDown($id) {
		
		global $config_demo;
		if($config_demo==1) return;

		global $db;
		if(!$id) $id=$this->id;
		$order_no=$db->fetchRow("select `order_no` from ".TABLE_CUSTOM_PAGES." where `id`='$id'");
		$goto=$order_no+1;
		$res1=$db->query("update ".TABLE_CUSTOM_PAGES." set `order_no`='1000' where `id`='$id'");
		$res2=$db->query("update ".TABLE_CUSTOM_PAGES." set `order_no`='$order_no' where `order_no`='$goto'");
		$res3=$db->query("update ".TABLE_CUSTOM_PAGES." set `order_no`='$goto' where `id`='$id'");

		$this->clearNavbarLinksCache();

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

	function getTmp() {
	
		return $this->tmp;

	}

	function getLast() {

		return $this->last;
	
	}

	function getOrder() {

		global $db;
		$res_order=$db->query('select `order_no` from '.TABLE_CUSTOM_PAGES.' order by `order_no` desc limit 1');
		if($db->numRows($res_order)>0) $order_no=($db->fetchRow()+1);
		else $order_no=1;
		return $order_no;
	}

	function check_form ($id='') {

		global $lng;
		$this->error='';
		$this->tmp=array();
		global $languages;

		global $config_demo;
		if($config_demo==1) $this->addError($lng['general']['errors']['demo'].'<br />');

		if(!$id) {
			if(!$_POST['type'] || !is_numeric($_POST['type']) || ($_POST['type']!=1 && $_POST['type']!=2)) $this->addError($lng['custom_pages']['errors']['invalid_type'].'<br />');
			else $type = $_POST['type'];
		} else $type = $this->getType($id);


		foreach ($languages as $lang) {
			if(!$_POST['title_'.$lang['id']]) { $this->addError($lng['custom_pages']['errors']['title_missing'].'<br />');
				break;
			}
		}

		if($type && $type==2) {
			if(!$_POST['hreflink']) $this->addError($lng['custom_pages']['errors']['external_link_missing'].'<br />');
			else if(!validator::valid_url($_POST['hreflink'])) $this->addError($lng['custom_pages']['errors']['invalid_external_link'].'<br />');
		}

		if(isset($_POST['choose_groups']) && $_POST['choose_groups']=="choose")
		{
			$groups_list='';
			$i=0;
			while (isset($_POST['groups'][$i]) && $group=$_POST['groups'][$i]){
				if($i) $groups_list.=',';
				$groups_list.=$group;
				$i++;
			}
			if($groups_list=='') $this->addError($lng['custom_fields']['errors']['groups_required'].'<br />');
		}

		if($this->getError()!='')
		{
			if($id!='') $this->tmp['id']=$id;
			if(isset($_POST['navlink']) && $_POST['navlink']=='on') $tmp['navlink']=1; else $this->tmp['navlink']=0;
			if(isset($_POST['blank']) && $_POST['blank']=='on') $tmp['blank']=1; else $this->tmp['blank']=0;
			if(!$id && isset($_POST['type'])) $this->tmp['type']=$_POST['type']; else $this->tmp['type']='';
			if(isset($_POST['parent_id'])) $this->tmp['parent_id']=$_POST['parent_id']; else $this->tmp['parent_id']='';
			if(isset($_POST['noindex']) && $_POST['noindex']=='on') $tmp['noindex']=1; else $this->tmp['noindex']=0;
	
			$array = array("title", "page_title", "meta_keywords", "meta_description");

			foreach ($array as $field) {
				foreach ($languages as $lang) {

					$lang_id = $lang['id'];
					$f = $field."_".$lang_id;
					if(isset($_POST[$f])) {
						$this->tmp[$field][$lang_id]=cleanStr($_POST[$f]); 
					}
					else $this->tmp[$field][$lang_id]='';
				}
			}
			
			if(isset($_POST['choose_group'])) $this->tmp['choose_group']=$_POST['choose_group'];
			if(isset($_POST['choose_group']) && $_POST['choose_group']=="choose")
			{
				$this->tmp['groups']='';
				$i=0;
				while (isset($_POST['groups'][$i])){
					$group=escape($_POST['groups'][$i]);
					if($i) $this->tmp['groups'].=',';
					$this->tmp['groups'].=$group;
					$i++;
				}
			} else $this->tmp['groups']='0';


		}
		return 1;
	}

	function add() {

		global $db;
		$this->clean=array();
		$this->check_form();
		if($this->getError()!='') return 0;
		global $languages;

		$this->clean['type'] = escape($_POST['type']);
		$this->clean['navlink'] = escape($_POST['navlink']);
		$this->clean['blank'] = checkbox_value('blank');
		$this->clean['noindex'] = checkbox_value('noindex');
		if($_POST['parent_id']) $this->clean['parent_id'] = escape($_POST['parent_id']);
		else $this->clean['parent_id'] = 0;

		if(isset($_POST['hreflink']) && $_POST['hreflink']) { 
			$this->clean['hreflink'] = escape($_POST['hreflink']); 
			if(strcmp(substr($this->clean['hreflink'],0,7),"http://") && strcmp(substr($this->clean['hreflink'],0,8),"https://")) $this->clean['hreflink']="http://".$this->clean['hreflink'];
		} else $this->clean['hreflink']='';

		$array_inputs_null_lang = array('title', 'page_title', 'meta_keywords', 'meta_description');
		foreach ($array_inputs_null_lang as $field) {
			foreach ($languages as $lang) {
				$lang_id = $lang['id'];
				$f = $field."_".$lang_id;
				if(isset($_POST[$f])) $this->clean[$field][$lang_id] = escape($_POST[$f]); 
				else $this->clean[$field][$lang_id] = '';
			}
		}

		if(isset($_POST['choose_group']) && $_POST['choose_group']=="choose")
		{
			$this->clean['groups']='';
			$i=0;
			while (isset($_POST['groups'][$i])){
				$group=escape($_POST['groups'][$i]);
				if($i) $this->clean['groups'].=',';
				$this->clean['groups'].=$group;
				$i++;
			}
		} else $this->clean['groups']='0';

		$order_no=$this->getOrder();

		$res=$db->query('insert into '.TABLE_CUSTOM_PAGES.' (`type`, `hreflink`, `navlink`, `parent_id`, `blank`, `order_no`, `groups`, `noindex`) values ("'.$this->clean['type'].'", "'.$this->clean['hreflink'].'", "'.$this->clean['navlink'].'", "'.$this->clean['parent_id'].'", "'.$this->clean['blank'].'", "'.$order_no.'", "'.$this->clean['groups'].'", "'.$this->clean['noindex'].'");');

		$this->last=$db->insertId();

		foreach ($languages as $lang) {

			$lang_id = $lang['id'];

			$res=$db->query("insert into ".TABLE_CUSTOM_PAGES."_lang set `id` = '".$this->last."', `lang_id` = '$lang_id', `title` = '".$this->clean['title'][$lang_id]."', `page_title` = '".$this->clean['page_title'][$lang_id]."', `meta_keywords` = '".$this->clean['meta_keywords'][$lang_id]."', `meta_description` = '".$this->clean['meta_description'][$lang_id]."';");

		}

		$slug = new slugs();
		if(isset($_POST['slug']) && $_POST['slug']) {
			$s = escape($_POST['slug']);
			$slug->addContent($this->last, $s);
		}
		else
		{
			$default_lang = languages::getDefault();
			$slug->addContent($this->last, $this->clean['title'][$default_lang]);
		}
		
		$this->clearNavbarLinksCache();

		return 1;

	}

	function edit($id) {

		global $db;
		$this->clean=array();
		$this->check_form($id);
		global $languages;

		if($this->getError()!='') return 0;
		//$this->clean['title'] = escape($_POST['title']);
		$this->clean['navlink'] = escape($_POST['navlink']);
		$this->clean['blank'] = checkbox_value('blank');
		$this->clean['noindex'] = checkbox_value('noindex');
		if($_POST['parent_id']) $this->clean['parent_id'] = escape($_POST['parent_id']);
		else $this->clean['parent_id'] = 0;
		if(isset($_POST['hreflink']) && $_POST['hreflink']) { 
			$this->clean['hreflink'] = escape($_POST['hreflink']); 
			if(strcmp(substr($this->clean['hreflink'],0,7),"http://") && strcmp(substr($this->clean['hreflink'],0,8),"https://")) $this->clean['hreflink']="http://".$this->clean['hreflink'];
		} else $this->clean['hreflink']='';

		$this->clean['type'] = escape($_POST['type']);

		$array_inputs_null_lang = array('title', 'page_title', 'meta_keywords', 'meta_description');
		foreach ($array_inputs_null_lang as $field) {
			foreach ($languages as $lang) {
				$lang_id = $lang['id'];
				$f = $field."_".$lang_id;
				if(isset($_POST[$f])) $this->clean[$field][$lang_id] = escape($_POST[$f]); 
				else $this->clean[$field][$lang_id] = '';
			}
		}

		if(isset($_POST['choose_group']) && $_POST['choose_group']=="choose")
		{
			$this->clean['groups']='';
			$i=0;
			while (isset($_POST['groups'][$i])){
				$group=escape($_POST['groups'][$i]);
				if($i) $this->clean['groups'].=',';
				$this->clean['groups'].=$group;
				$i++;
			}
		} else $this->clean['groups']='0';

		$str_type = '';
		if($id!=1) $str_type = '`type` = "'.$this->clean['type'].'", ';

		$res=$db->query('update '.TABLE_CUSTOM_PAGES.' set '.$str_type.' `hreflink` = "'.$this->clean['hreflink'].'", `navlink` = "'.$this->clean['navlink'].'", `parent_id` = "'.$this->clean['parent_id'].'", `blank` = "'.$this->clean['blank'].'", `groups`="'.$this->clean['groups'].'", `noindex`="'.$this->clean['noindex'].'" where id='.$id.';');

		foreach ($languages as $lang) {

			$lang_id = $lang['id'];

			$res=$db->query("update ".TABLE_CUSTOM_PAGES."_lang set `title` = '".$this->clean['title'][$lang_id]."', `page_title` = '".$this->clean['page_title'][$lang_id]."', `meta_keywords` = '".$this->clean['meta_keywords'][$lang_id]."', `meta_description` = '".$this->clean['meta_description'][$lang_id]."' where `id` = $id and `lang_id`='$lang_id';");

		}

		$slug = new slugs();
		if(isset($_POST['slug']) && $_POST['slug']) {
			$s = escape($_POST['slug']);
			$slug->editContent($id, $s);
		}
		else
		{
			$default_lang = languages::getDefault();
			$slug->editContent($id, $this->clean['title'][$default_lang]);
		}

		$this->clearNavbarLinksCache();

		return 1;

	}

	function edit_content($id) {

		global $db;
		global $config_demo;
		if($config_demo==1) return;

		$postedValue = $_POST['content'];
	
		if (!_get_magic_quotes_gpc())
			$content=addslashes($postedValue);
		else
			$content=$postedValue;
	
		$lang_id = escape($_POST['lang_id']);
		$res=$db->query("update ".TABLE_CUSTOM_PAGES."_lang set content='$content' where id='$id' and `lang_id` = '$lang_id'");

		$this->clearNavbarLinksCache();

		return 1;

	}


	function clearNavbarLinksCache() {

		// clear cache
		$lc_cache = new cache();
 		$lc_cache->clearCache("base_cp");

	}

}
?>
