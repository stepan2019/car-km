<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/


class categories_config {

	var $error;
	var $tmp;

	public function __construct()
	{
	
		$this->error=''; $this->tmp='';

	}

	function deletePicture($id='') {

		global $db;
		global $config_demo;
		if($config_demo==1) return;

		if(!$id) $id=$this->id;
		$picture=$this->getPicture($id);
		global $config_abs_path;
		$file_path=$config_abs_path."/images/categories/".$picture;
		if(file_exists($file_path)) @unlink($file_path);
		$res_del=$db->query("update ".TABLE_CATEGORIES." set picture='' where id='$id'");

		$this->clearCategoriesCache();

		return 1;
	}

	function deleteIcon($id='') {

		global $db;
		global $config_demo;
		if($config_demo==1) return;

		if(!$id) $id=$this->id;
		$icon=$this->getIcon($id);
		global $config_abs_path;
		$file_path=$config_abs_path."/images/categories/".$icon;
		if(file_exists($file_path)) @unlink($file_path);
		$res_del=$db->query("update ".TABLE_CATEGORIES." set icon='' where id='$id'");

		$this->clearCategoriesCache();

		return 1;
	}

	function delete($id) {

		global $db;
		global $config_demo, $config_abs_path;
		if($config_demo==1) return;

		// remake order
		$this->remakeOrderDelete($id);

		$this->deletePicture($id);
		$this->deleteIcon($id);
		$res_del=$db->query("delete from ".TABLE_CATEGORIES." where id='$id'");
		$res_del=$db->query("delete from ".TABLE_CATEGORIES."_lang where id='$id'");

		//delete listings
		require_once $config_abs_path."/classes/listings.php";
		require_once $config_abs_path."/classes/pictures.php";
		$listings=new listings();
		$listings->deleteCateg($id);

		//delete subcategories
		$array=$db->fetchRowList("select id from ".TABLE_CATEGORIES." where parent_id='$id'");
		foreach($array as $row) {
			$subcat=$row;
			$this->delete($subcat);
		}
		$slug = new slugs();
		$slug->deleteCategory($id);
		$this->clearCategoriesCache();
	}

	function getCategoryLang($id='') {

		global $db;
		if(!$id) $id=$this->id;

		$row=$db->fetchAssoc("select * from ".TABLE_CATEGORIES." where id=".$id."");
		$array_lang=$db->fetchAssocList("select * from ".TABLE_CATEGORIES."_lang where id=".$id."");

		foreach($array_lang as $cat_lang) {

			$lang_id = $cat_lang['lang_id'];
			foreach ($cat_lang as $key=>$value) {
				if($key=='id' || $key=='lang_id') continue;
				$row[$key][$lang_id] = cleanStr($value);
			}
		}
		return $row;

	}

	function moveAds($from,$to) {

		global $db;
		global $config_demo, $config_abs_path;
		if($config_demo==1) return;

		require_once $config_abs_path."/classes/listings.php";
		$listings = new listings;
		$listings->moveAds($from, $to, 'categ');

		//move from subcategories
		$array=$db->fetchRowList("select id from ".TABLE_CATEGORIES." where parent_id='$from'");
		foreach($array as $row) {
			$subcat=$row;
			$this->moveAds($subcat,$to);
		}
		$this->clearCategoriesCache();
	}

	function remakeOrderDelete($id) {
	
		global $db;
		$order=$db->fetchRow("select `order_no` from ".TABLE_CATEGORIES." where id='$id'");
		if(!$order) return;
		$res=$db->query("update ".TABLE_CATEGORIES." set `order_no`=`order_no`-1 where `order_no`>='$order'");

		$this->clearCategoriesCache();

	}

	function remakeOrderAdd($id, $parent_id) {
	
		// if no parent id leave default order
		if(!$parent_id) return;

		global $db;
		// get last order with the same parent
		$last_order = $db->fetchRow("select `order_no` from ".TABLE_CATEGORIES." where `parent_id`='$parent_id' and id!='$id' order by `order_no` desc limit 1");
		
		if(!$last_order) $last_order = $db->fetchRow("select `order_no` from ".TABLE_CATEGORIES." where `id`='$parent_id'");

		if(!$last_order) return;

		$new_order = $last_order+1;
		$db->query("update ".TABLE_CATEGORIES." set `order_no` ='$new_order' where id='$id'");
		$res=$db->query("update ".TABLE_CATEGORIES." set `order_no`=`order_no`+1 where `order_no`>='$new_order' and id!='$id'");

		$this->clearCategoriesCache();

	}

	function getIdByOrder($order_no) {

		global $db;
		$id=$db->fetchRow("select id from ".TABLE_CATEGORIES." where `order_no` = $order_no");
		return $id;
	}

	function move($from, $to) {

		global $config_demo;
		if($config_demo==1) return;
		
		$s = "";
		$from_list = $this->subcatList($this->getIdByOrder($from), $s);
		$from_array = explode(",", $from_list);

		$to_list = $this->subcatList($this->getIdByOrder($to), $s);
		$to_array = explode(",", $to_list);

		$move_from = count($to_array);
		$move_to = count($from_array);

		if($from>$to) { 
			$move_from = $move_from*-1;
			$move_to = $move_to*-1;
		}

		global $db;
		foreach($from_array as $cat) $db->query("update ".TABLE_CATEGORIES." set `order_no` = `order_no`+($move_from) where id='$cat'");

		foreach($to_array as $cat) $db->query("update ".TABLE_CATEGORIES." set `order_no` = `order_no`-($move_to) where id='$cat'");

		$this->clearCategoriesCache();

		return $to;

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

	function check_form ($id='') {

		global $lng;
		$this->error='';
		$this->tmp=array();

		global $config_demo;
		if($config_demo==1) $this->addError($lng['general']['errors']['demo'].'<br />');

		global $languages;
		foreach ($languages as $lang) {
			if(!$_POST['name_'.$lang['id']]) { $this->addError($lng['categories']['errors']['name_missing'].'<br />');
				break;
			}
		}

		if(!$_POST['fieldset']) $this->addError($lng['categories']['errors']['set_missing'].'<br />');
		global $config_abs_path;
		if(isset($_FILES['picture']['name']) && $_FILES['picture']['name']!='') {
			$image=new image('picture', $config_abs_path.'/images/categories','categ'); 
			if(!$image->verify()) $this->addError($image->getError());
		}

		if(isset($_FILES['icon']['name']) && $_FILES['icon']['name']!='') {
			$image=new image('icon', $config_abs_path.'/images/categories','categ'); 
			if(!$image->verify()) $this->addError($image->getError());
		}

		if(isset($_POST['choose_group']) && $_POST['choose_group']=="choose")
		{

			$groups_list='';
			$i=0;
			while (isset($_POST['groups'][$i]) && $usr=$_POST['groups'][$i]){
				if($i) $groups_list.=',';
				$groups_list.=$usr;
				$i++;
			}
			if($groups_list=='') $this->addError($lng['categories']['errors']['groups_required'].'<br />'); 
		}

		if($this->getError()!='')
		{
			if($id!='') $this->tmp['id']=$id;
			if(isset($groups_list)) $this->tmp['groups']=$groups_list; else $this->tmp['groups']='';

			// fields with default null value
			$array_inputs_null = array('choose_groups', 'fieldset');
			foreach ($array_inputs_null as $field) {

				if(isset($_POST[$field])) $this->tmp[$field]=cleanStr($_POST[$field]); else $this->tmp[$field]='';

			}

			// lang fields with default null value
			$array_inputs_null_lang = array('name', 'description', 'page_title', 'meta_keywords', 'meta_description');
			foreach ($array_inputs_null_lang as $field) {
				foreach ($languages as $lang) {

					$lang_id = $lang['id'];
					$f = $field."_".$lang_id;
					if(isset($_POST[$f])) 
						$this->tmp[$field][$lang_id]=cleanStr($_POST[$f]); 
					else $this->tmp[$field][$lang_id]='';
				}
			}

			if(isset($_POST['parent_id'])) $this->tmp['parent_id']=$_POST['parent_id']; else $this->tmp['parent_id']=0;

		}

		return 1;
	}

	function add() {

		global $db;
		$this->clean=array();
		$this->check_form();
		if($this->getError()!='') return 0;

		global $languages;
		// fields with default null value
		$array_inputs_null = array('choose_group', 'fieldset');
		foreach ($array_inputs_null as $field) {

			if(isset($_POST[$field])) $this->clean[$field] = escape($_POST[$field]);

		}

		$array_inputs_null_lang = array('name', 'description', 'page_title', 'meta_keywords', 'meta_description');
		foreach ($array_inputs_null_lang as $field) {
			foreach ($languages as $lang) {
				$lang_id = $lang['id'];
				$f = $field."_".$lang_id;
				if(isset($_POST[$f])) $this->clean[$field][$lang_id] = escape($_POST[$f]); 
				else $this->clean[$field][$lang_id] = '';
			}
		}

		$this->clean['parent_id'] = escape($_POST['parent_id']);
		global $config_abs_path;
		if(isset($_FILES['picture']['name']) && $_FILES['picture']['name']!='') {
			$image=new image('picture', $config_abs_path."/images/categories",'categ');
			$image->verify();
			if($image->upload()) $this->clean['picture']=$image->getUploadedFile();
			else $this->addError($image->getError());
		} else $this->clean['picture']='';

		if(isset($_FILES['icon']['name']) && $_FILES['icon']['name']!='') {
			$image=new image('icon', $config_abs_path."/images/categories",'categ');
			$image->verify();
			if($image->upload()) $this->clean['icon']=$image->getUploadedFile();
			else $this->addError($image->getError());
		} else $this->clean['icon']='';

		if(isset($_POST['choose_group']) && $_POST['choose_group']=="choose")
		{
			$this->clean['groups']='';
			$i=0;
			while (isset($_POST['groups'][$i]) && $usr=escape($_POST['groups'][$i])){
				if($i) $this->clean['groups'].=',';
				$this->clean['groups'].=$usr;
				$i++;
			}
		} else $this->clean['groups']='0';

		$order_no=$this->getOrder();

		$res=$db->query('insert into '.TABLE_CATEGORIES.' (`picture`, `icon`, `parent_id`, `fieldset`, `order_no`, `groups`) values ("'.$this->clean['picture'].'", "'.$this->clean['icon'].'", "'.$this->clean['parent_id'].'", "'.$this->clean['fieldset'].'", "'.$order_no.'", "'.$this->clean['groups'].'");');

		$id=$db->insertId();

		foreach ($languages as $lang) {

			$lang_id = $lang['id'];

			$res=$db->query("insert into ".TABLE_CATEGORIES."_lang set `id` = '$id', `lang_id` = '$lang_id', `name` = '".$this->clean['name'][$lang_id]."', `description` = '".$this->clean['description'][$lang_id]."', `page_title` = '".$this->clean['page_title'][$lang_id]."', `meta_keywords` = '".$this->clean['meta_keywords'][$lang_id]."', `meta_description` = '".$this->clean['meta_description'][$lang_id]."';");

		}

		// set level
		$level = $this->getLevel($id);
		$db->query("update ".TABLE_CATEGORIES." set `level` = '$level' where id='$id'");

		$this->remakeOrderAdd($id, $this->clean['parent_id']);

		$slug = new slugs();
		if(isset($_POST['slug']) && $_POST['slug']) {
			$s = escape($_POST['slug']);
			$slug->addCategory($id, $s);
		}
		else {
			$default_lang = languages::getDefault();
			$slug->addCategory($id, $this->clean['name'][$default_lang]);
		}
		
		$this->clearCategoriesCache();

		return 1;

	}

	function edit($id) {

		global $db;
		$this->clean=array();
		$this->check_form($id);
		if($this->getError()!='') return 0;

		global $languages;
		$this->clean['fieldset'] = escape($_POST['fieldset']);
		$this->clean['parent_id'] = escape($_POST['parent_id']);

		$array_inputs_null_lang = array('name', 'description', 'page_title', 'meta_keywords', 'meta_description');
		foreach ($array_inputs_null_lang as $field) {
			foreach ($languages as $lang) {
				$lang_id = $lang['id'];
				$f = $field."_".$lang_id;
				if(isset($_POST[$f])) $this->clean[$field][$lang_id] = escape($_POST[$f]); 
				else $this->clean[$field][$lang_id] = '';
			}
		}
		if(isset($_FILES['picture']['name']) && $_FILES['picture']['name']!='') {
			global $config_abs_path;
			$image=new image('picture', $config_abs_path."/images/categories",'categ');
			$image->verify();
			if($image->upload()) $this->clean['picture']=$image->getUploadedFile();
			else $this->addError($image->getError());
			// delete old picture
			if($this->clean['picture']!=$this->getPicture($id)) $this->deletePicture($id);
			$res=$db->query('update '.TABLE_CATEGORIES.' set `picture` = "'.$this->clean['picture'].'" where id='.$id.';');
		}

		if(isset($_FILES['icon']['name']) && $_FILES['icon']['name']!='') {
			global $config_abs_path;
			$image=new image('icon', $config_abs_path."/images/categories",'categ');
			$image->verify();
			if($image->upload()) $this->clean['icon']=$image->getUploadedFile();
			else $this->addError($image->getError());
			// delete old icon
			if($this->clean['icon']!=$this->getIcon($id)) $this->deleteIcon($id);
			$res=$db->query('update '.TABLE_CATEGORIES.' set `icon` = "'.$this->clean['icon'].'" where id='.$id.';');
		}

		if(isset($_POST['choose_group']) && $_POST['choose_group']=="choose")
		{
			$this->clean['groups']='';
			$i=0;
			while (isset($_POST['groups'][$i])){
				$usr=escape($_POST['groups'][$i]);
				if($i) $this->clean['groups'].=',';
				$this->clean['groups'].=$usr;
				$i++;
			}
		} else $this->clean['groups']='0';

		$old_parent = $db->fetchRow("select `parent_id` from ".TABLE_CATEGORIES." where id='$id'");

		$res=$db->query('update '.TABLE_CATEGORIES.' set `fieldset` = "'.$this->clean['fieldset'].'", `parent_id` = "'.$this->clean['parent_id'].'", `groups` = "'.$this->clean['groups'].'" where id='.$id.';');

		foreach ($languages as $lang) {

			$lang_id = $lang['id'];

			$res=$db->query("update ".TABLE_CATEGORIES."_lang set `name` = '".$this->clean['name'][$lang_id]."', `description` = '".$this->clean['description'][$lang_id]."', `page_title` = '".$this->clean['page_title'][$lang_id]."', `meta_keywords` = '".$this->clean['meta_keywords'][$lang_id]."', `meta_description` = '".$this->clean['meta_description'][$lang_id]."' where id=$id and `lang_id`='$lang_id';");

		}

		// if parent changed, redo the ordering
		if($old_parent!=$this->clean['parent_id']) {
			$this->remakeOrderDelete($id);
			$this->remakeOrderAdd($id, $this->clean['parent_id']);
			// remake level
			$level = $this->getLevel($id);
			$db->query("update ".TABLE_CATEGORIES." set `level` = '$level' where id='$id'");
		}

		$slug = new slugs();
		if(isset($_POST['slug']) && $_POST['slug']) {
			$s = escape($_POST['slug']);
			$slug->editCategory($id, $s);
		}
		else
		{
			$default_lang = languages::getDefault();
			$slug->editCategory($id, $this->clean['name'][$default_lang]);
		}
		
		$this->clearCategoriesCache();

		return 1;

	}

	function getOrder() {

		global $db;
		$order_no=$db->fetchRow('select `order_no` from '.TABLE_CATEGORIES.' order by `order_no` desc limit 1');
		if($order_no) $order_no=$order_no+1;
		else $order_no=1;
		return $order_no;
	}

	function changeFieldset($from, $to) {

		global $db;
		$db->query("update ".TABLE_CATEGORIES." set `fieldset`=$to where `fieldset`=$from");
		$this->clearCategoriesCache();
		return 1;
	}

	function Recount() {

		global $db;
		$db->query("update ".TABLE_CATEGORIES." set `ads`='0'");
		
		$categories = $db->fetchRowList("select `id` from ".TABLE_CATEGORIES);
		$cats = new categories;
		foreach($categories as $cat)
		{
    			$no_ads = $db->fetchRow("select count(*) from ".TABLE_ADS." where category_id='$cat' and `active`=1");
    			if($no_ads) $cats->addToCat($cat, $no_ads);

		}

		global $settings;
		if($settings['enable_locations'] && $settings['location_fields']) {
			global $config_abs_path;
			require_once $config_abs_path."/classes/locations.php";
			require_once $config_abs_path."/classes/listings.php";
			$l = new listings;
			$l->countLocationAds();
		}

		$this->clearCategoriesCache();

	}

	function getPicture($id='') {

		global $db;
		if(!$id) $id = $this->id;
		$picture=$db->fetchRow('select picture from '.TABLE_CATEGORIES.' where id="'.$id.'"');
		return $picture;
	}

	function getIcon($id='') {

		global $db;
		if(!$id) $id = $this->id;
		$picture=$db->fetchRow('select `icon` from '.TABLE_CATEGORIES.' where id="'.$id.'"');
		return $picture;
	}

	function getCategory($id='') {

		global $db;
		global $crt_lang;
		if(!$id) $id=$this->id;
		$row=$db->fetchAssoc("select * from ".TABLE_CATEGORIES." LEFT JOIN ".TABLE_CATEGORIES."_lang on ".TABLE_CATEGORIES.".`id` = ".TABLE_CATEGORIES."_lang.`id` where ".TABLE_CATEGORIES.".id=".$id." and `lang_id`='$crt_lang'");

		$arr = array("name", "description", "page_title", "meta_keywords", "meta_description");
		foreach ($arr as $key) $row[$key] = cleanStr($row[$key]);

		return $row;

	}

	function getAll($parent='') {

		global $db;
		global $crt_lang;
		if(isset($parent) && $parent) $where_str=" where parent_id='$parent' and `lang_id` = '$crt_lang'"; else $where_str=" where `lang_id` = '$crt_lang'";

		$array=$db->fetchAssocList("select ".TABLE_CATEGORIES.".*, ".TABLE_CATEGORIES."_lang.*, ".TABLE_FIELDSETS.".`name` as `set` from ".TABLE_CATEGORIES." 
		LEFT JOIN ".TABLE_CATEGORIES."_lang on ".TABLE_CATEGORIES.".`id` = ".TABLE_CATEGORIES."_lang.`id` 
		LEFT JOIN ".TABLE_FIELDSETS." on ".TABLE_CATEGORIES.".`fieldset`=".TABLE_FIELDSETS.".`id` 
		".$where_str." order by `order_no`");
		//".$where_str." group by ".TABLE_CATEGORIES.".`id` order by `order_no`");

		$i=0;
		$arr=array();
		foreach($array as $result) {

			$arr[$i] = $result;
			$arr[$i]['name'] = cleanStr($arr[$i]['name']);
			$arr[$i]['subcats']=categories::getSubcatsNo($result['id']);
			if($result['parent_id']!=0) $arr[$i]['parent']=cleanStr(categories::getName($result['parent_id'])); else $arr[$i]['parent']='-';
			$arr[$i]['order_up'] = $this->canOrderUp($arr[$i]['id'], $arr[$i]['parent_id'], $arr[$i]['order_no']);
			$arr[$i]['order_down'] = $this->canOrderDown($arr[$i]['id'], $arr[$i]['parent_id'], $arr[$i]['order_no']);
			$i++;

		}

		return $arr;
	}
	
	function getLevel($categ, $no=0) {

		if(!$categ) return $no;
		global $db;
		$parent=$db->fetchRow("select `parent_id` from ".TABLE_CATEGORIES." where `id`=".$categ);
		$no++;
		$no = $this->getLevel($parent, $no);
		return $no;
	}

	function updateLevels() {

		global $db;
		$categories=$db->fetchRowList("select `id` from ".TABLE_CATEGORIES);
		foreach ($categories as $cat) {
			$level = $this->getLevel($cat);
			$db->query("update ".TABLE_CATEGORIES." set `level` = '$level' where id = '$cat'");
		}
		$this->clearCategoriesCache();
	}

	function reArrange() {

		global $db;
		$this->updateLevels();

		$result=$db->fetchAssocList("select ".TABLE_CATEGORIES.".`id`, `parent_id`, `order_no`, `level` from ".TABLE_CATEGORIES." order by `order_no`");	

		$no = count($result);
		for($i=0; $i<$no; $i++) { 
			$result[$i]['moved'] = 0; 
			$result[$i]['parent'] = 0; 
		}

		$found=1;
		while($found) {
		$found=0;
		for($i=0; $i<$no; $i++) {
			
			if($result[$i]['parent_id'] && !$result[$i]['moved']) {
				$found=1;
				$element = $result[$i];
				// delete from that position
				array_splice($result, $i, 1);
				$found=0;
				// search for father position, set parent flag=1
				// there are only no-1 elements now
				for($j=0; $j<$no-1 && !$found;$j++) { 
					if($result[$j]['id'] == $element['parent_id'] ) {
						$pos_parent = $j;
						$pos = $j+1;

						while($pos<$no-1 && ( ($result[$pos]['parent_id']==$element['parent_id']  && $result[$pos]['order_no']<$element['order_no']) || ($result[$pos]['level']>$element['level'] ))) {
							$pos++;
						}
						$result[$pos_parent]['parent'] = 1;
						$found=1;
					}
				}
				// insert after father position
				array_splice($result, $pos, 0, array($element));
	
				$s = "";
				$subcats_list = $this->subcatList($element['id'], $s);
				$subcats = explode(",", $subcats_list);
				for($j=0; $j<$no; $j++) { 
					if(in_array($result[$j]['id'], $subcats)) $result[$j]['moved'] = 0;
				}
				$result[$pos]['moved'] = 1;
			}

		} //end for
		} //while found

		$i = 1;
		foreach ($result as $res) {
			$db->query("update ".TABLE_CATEGORIES." set `order_no` = '$i' where id='{$res['id']}'");
			$i++;
		}
		$this->clearCategoriesCache();

	}

	function subcatList($cat, &$str) {

		global $db;

		$result=$db->fetchAssocList("select `id` from ".TABLE_CATEGORIES." where `parent_id`=".$cat."");

		foreach($result as $row) {
			$subcat = $row['id'];
			$this->subcatList($subcat, $str);
		}

		if($str) $str.=",";
		$str.=$cat;
		return $str;

	}

	function canOrderUp($id, $parent, $order_no) {

		global $db;
		$exists = $db->fetchRow("select `order_no` from ".TABLE_CATEGORIES." where `parent_id` = $parent and `order_no`<$order_no order by order_no desc limit 1");
		return $exists;

	}

	function canOrderDown($id, $parent, $order_no) {

		global $db;
		$exists = $db->fetchRow("select `order_no` from ".TABLE_CATEGORIES." where `parent_id` = $parent and `order_no`>$order_no order by order_no asc limit 1");
		return $exists;

	}

	function clearCategoriesCache() {

		// clear cache
		$lc_cache = new cache();
		$lc_cache->clearCache("base_categories");
		$lc_cache->clearCache("base_short_categories");
		$lc_cache->clearCache("base_interface");
		$lc_cache->clearCache("base_short_categories_fset");

	}

	function Enable($id) {
	
		global $config_demo;
		if($config_demo==1) return;

		global $db;
		$db->query("update `".TABLE_CATEGORIES."` set `active` = 1 where id=$id");

		$this->clearCategoriesCache();
		return 1;
		
	}
	
	function Disable($id) {
	
		global $config_demo;
		if($config_demo==1) return;

		global $db;
		$db->query("update `".TABLE_CATEGORIES."` set `active` = 0 where id=$id");

		$this->clearCategoriesCache();
		return 1;
		
	}

	
		// !!!!!!!! not for multilanguage 
		function add_multi() {

		global $db;
		$this->clean=array();
		$this->check_form();
		if($this->getError()!='') return 0;

		global $languages;
		// fields with default null value
		$array_inputs_null = array('choose_group', 'fieldset');
		foreach ($array_inputs_null as $field) {

			if(isset($_POST[$field])) $this->clean[$field] = escape($_POST[$field]);

		}

		$array_inputs_null_lang = array('name', 'description', 'page_title', 'meta_keywords', 'meta_description');
		foreach ($array_inputs_null_lang as $field) {
			foreach ($languages as $lang) {
				$lang_id = $lang['id'];
				$f = $field."_".$lang_id;
				if(isset($_POST[$f])) $this->clean[$field][$lang_id] = escape($_POST[$f]); 
				else $this->clean[$field][$lang_id] = '';
			}
		}

		$this->clean['parent_id'] = escape($_POST['parent_id']);
		global $config_abs_path;
		if(isset($_FILES['picture']['name']) && $_FILES['picture']['name']!='') {
			$image=new image('picture', $config_abs_path."/images/categories",'categ');
			$image->verify();
			if($image->upload()) $this->clean['picture']=$image->getUploadedFile();
			else $this->addError($image->getError());
		} else $this->clean['picture']='';

		if(isset($_FILES['icon']['name']) && $_FILES['icon']['name']!='') {
			$image=new image('icon', $config_abs_path."/images/categories",'categ');
			$image->verify();
			if($image->upload()) $this->clean['icon']=$image->getUploadedFile();
			else $this->addError($image->getError());
		} else $this->clean['icon']='';

		if(isset($_POST['choose_group']) && $_POST['choose_group']=="choose")
		{
			$this->clean['groups']='';
			$i=0;
			while (isset($_POST['groups'][$i]) && $usr=escape($_POST['groups'][$i])){
				if($i) $this->clean['groups'].=',';
				$this->clean['groups'].=$usr;
				$i++;
			}
		} else $this->clean['groups']='0';

		$lang_id = $languages[0]['id'];
		
		$order_no=$this->getOrder();

		$name_array = explode(",",$this->clean['name'][$lang_id]);
		foreach ($name_array as $name) {
		
		$name = trim($name);
		$res=$db->query('insert into '.TABLE_CATEGORIES.' (`picture`, `icon`, `parent_id`, `fieldset`, `order_no`, `groups`) values ("'.$this->clean['picture'].'", "'.$this->clean['icon'].'", "'.$this->clean['parent_id'].'", "'.$this->clean['fieldset'].'", "'.$order_no.'", "'.$this->clean['groups'].'");');

		$id=$db->insertId();

		//foreach ($languages as $lang) {

		//$lang_id = $lang['id'];

		$res=$db->query("insert into ".TABLE_CATEGORIES."_lang set `id` = '$id', `lang_id` = '$lang_id', `name` = '".$name."', `description` = '".$this->clean['description'][$lang_id]."', `page_title` = '".$this->clean['page_title'][$lang_id]."', `meta_keywords` = '".$this->clean['meta_keywords'][$lang_id]."', `meta_description` = '".$this->clean['meta_description'][$lang_id]."';");

		// set level
		$level = $this->getLevel($id);
		$db->query("update ".TABLE_CATEGORIES." set `level` = '$level' where id='$id'");

		$this->remakeOrderAdd($id, $this->clean['parent_id']);

		$order_no++;
		//}
		}

		$this->clearCategoriesCache();

		return 1;

	}
}
?>