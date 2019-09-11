<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class slugs {


	var $id;
	var $reserved;

	public function __construct()
	{
	
		$this->reserved=array("search", "content");
		
	}
	
	function makeSlug($id, $type, $title='') {

		if(!$id) return;
		
		global $db, $seo_settings;
		if(!$title) $title = $this->getTitle($id, $type);
		$rand = 0;
		if(!$title) { 
			$title = substr(generate_random(),0,6);
			$rand = 1;
		}
		$title = cleanStr($title);
		$no_chars = $seo_settings['maximum_slug_width'];
		$slug = _urlencode($title, 1, $no_chars, 1);
		while($this->slugExists($slug)) {
			if($rand) $title = substr(generate_random(),0,6);
			$slug = $this->makeNewSlug($slug);
		}
		
		$db->query("insert into ".TABLE_SLUGS." set `object_id`='$id', `type`='$type', `slug`='".escape($slug)."'");

		return $slug;

	}
	
	function updateSlug($id, $type, $title) {

		global $db, $seo_settings;
		$no_chars=$seo_settings['maximum_slug_width'];
		$slug = _urlencode($title, 1, $no_chars, 1);
		
		$old_slug = $db->fetchRow("select `slug` from ".TABLE_SLUGS." where `type` like '$type' and `object_id`='$id'");
		if($slug==$old_slug) return;
		
		while($this->slugExists($slug, $id)) {
			$slug = $this->makeNewSlug($slug);
		}
		
		$db->query("update ".TABLE_SLUGS." set `slug`='".escape($slug)."' where `type`='$type' and `object_id`='$id'");

	}

	function slugExists($slug, $id='') {
	
		global $db;
		
		if(in_array($slug, $this->reserved)) return 1;
		
		$str_id="";
		if($id) $str_id=" and `object_id` != $id ";
		$exists = $db->fetchRow("select count(*) from ".TABLE_SLUGS." where `slug` like '$slug'".$str_id);
		return $exists;
	
	}

	function slugForObjectExists($object_id, $type) {
	
		global $db;
		
		$exists = $db->fetchRow("select count(*) from ".TABLE_SLUGS." where `object_id` = '$object_id' and `type` like '$type'");
		return $exists;
	
	}

	function makeNewSlug($slug) {
	
		// check if any number at the end 
		if (preg_match('#_(\d+)$#', $slug, $matches)) {
			$no = $matches[1];
			$no++;
			$slug = preg_replace('/_(\d+)$/', '', $slug);

		}
		else $no = 1;
		
		return $slug."_".$no;
	
	}
	
	function addListing($id, $title) {
	
		$this->makeSlug($id, "listing", $title);
	
	}

	function addCategory($id, $title) {
	
		$this->makeSlug($id, "category", $title);
	
	}
	
	function addUser($id, $title='') {
	
		if(!$title) $title = users::getContactName($id);
		$this->makeSlug($id, "user", $title);
	
	}

	function addContent($id, $title) {
	
		$this->makeSlug($id, "content", $title);
	
	}

	function editListing($id, $title) {
	
		$this->updateSlug($id, "listing", $title);
	
	}

	function editCategory($id, $title) {
	
		$this->updateSlug($id, "category", $title);
	
	}
	
	function editUser($id, $title='') {

		if(!$title) $title = users::getContactName($id);
		$this->updateSlug($id, "user", $title);
	
	}

	function editContent($id, $title) {
	
		$this->updateSlug($id, "content", $title);
	
	}

	function deleteListing($id) {
	
		global $db;
		$db->query("delete from ".TABLE_SLUGS." where `type` like 'listing' and `object_id` = '$id'");
	
	}

	function deleteCategory($id) {
	
		global $db;
		$db->query("delete from ".TABLE_SLUGS." where `type` like 'category' and `object_id` = '$id'");
	
	}
	
	function deleteUser($id) {
	
		global $db;
		$db->query("delete from ".TABLE_SLUGS." where `type` like 'user' and `object_id` = '$id'");
	
	}
	
	function deleteContent($id) {
	
		global $db;
		$db->query("delete from ".TABLE_SLUGS." where `type` like 'content' and `object_id` = '$id'");
	
	}

	function getSlug($object_id, $type) {
		
		global $db;
		$slug = $db->fetchRow("select `slug` from ".TABLE_SLUGS." where `type` like '$type' and `object_id`='$object_id'");
/*
		if(!$slug) {
			$s = new slugs();
			$slug = $s->makeSlug($object_id, $type);
		}*/
		if(!$slug) return 0;
		return $slug;
		
	}
	
	static function getCategorySlug($id) {
	
		$s = new slugs();
		return $s->getSlug($id, "category");
	
	}

	static function getCustomPageSlug($id) {
	
		$s = new slugs();
		return $s->getSlug($id, "content");
	
	}

	static function getListingSlug($id) {
	
		$s = new slugs();
		$slug = $s->getSlug($id, "listing");
		if(!$slug) return 0;
		return $slug;
	
	}
	
	static function getUserSlug($id) {
	
		$s = new slugs();
		return $s->getSlug($id, "user");
	
	}


	static function getCategoryId($slug) {
	
		global $db;
		$id = $db->fetchRow("select `object_id` from ".TABLE_SLUGS." where type='category' and `slug` like '$slug'");
		return $id;
	
	}
	static function getCustomPageId($slug) {
	
		global $db;
		$id = $db->fetchRow("select `object_id` from ".TABLE_SLUGS." where type='content' and `slug` like '$slug'");
		return $id;
	
	}

	static function getListingId($slug) {
	
		global $db;
		$id = $db->fetchRow("select `object_id` from ".TABLE_SLUGS." where type='listing' and `slug` like '$slug'");
		return $id;
	
	}

	static function getUserId($slug) {
	
		global $db;
		$id = $db->fetchRow("select `object_id` from ".TABLE_SLUGS." where type='user' and `slug` like '$slug'");
		return $id;
	
	}

	function getTitle($id, $type) {
	
		switch ($type) {
		case 'listing':
			return listings::getTitle($id);
        break;
		case 'category':
			return categories::getName($id);
        break;
		case 'user':
			return users::getContactName($id);
		case 'content':
			$cp = new custom_pages();
			return $cp->getTitle($id);
        break;
		}// end switch 
	
	}

	function prepareRegenerate($type) {
	
		global $db;
		$db->query("delete from ".TABLE_SLUGS." where `type`='$type'");
		
		if($type=="listing")
			$id = $db->fetchRow("select `id` from ".TABLE_ADS." order by id desc limit 1");
		else 	
			$id = $db->fetchRow("select `id` from ".TABLE_USERS." order by id desc limit 1");
			
		return $id;
		
	}
	
	function regenerate() {
	
		global $db;
		$db->query("delete from ".TABLE_SLUGS." where `type`='listing' or `type`='user'");
	
		// generate user slugs
		global $settings, $config_abs_path;
		$field = $settings['contact_name_field'];
		require_once $config_abs_path."/classes/users.php";
		$res = $db->query("select `id`, `$field` from ".TABLE_USERS);
		while($u = $db->fetchAssocRes($res) ) {
			$this->makeSlug($u['id'], "user", $u[$field]);
		}

		// generate listings slugs
		$title_str = "title";
		// if translate_title and description
		global $ads_settings; 
		if($ads_settings['translate_title_description'])
			$title_str.="_".$default_lang;
		$res = $db->query("select `id`, `$title_str` from ".TABLE_ADS);
		while($l = $db->fetchAssocRes($res) ) {
			$this->makeSlug($l['id'], "listing", $l[$title_str]);
		}
	
	}

	function generate() {
	
		global $db;
		$db->query("delete from ".TABLE_SLUGS);
	
		// generate category slugs
		$default_lang = languages::getDefault();
		$res = $db->query("select `id`, `name` from ".TABLE_CATEGORIES."_lang where `lang_id`='$default_lang'");
		while($c = $db->fetchAssocRes($res) ) {
			$this->makeSlug($c['id'], "category", $c['name']);
		}

		// generate custom pages slugs
		$res = $db->query("select `id`, `title` from ".TABLE_CUSTOM_PAGES."_lang where `lang_id`='$default_lang'");
		while($c = $db->fetchAssocRes($res) ) {
			$this->makeSlug($c['id'], "content", $c['title']);
		}

		// generate user slugs
		global $settings, $config_abs_path;
		$field = $settings['contact_name_field'];
		require_once $config_abs_path."/classes/users.php";
		$res = $db->query("select `id`, `$field` from ".TABLE_USERS);
		while($u = $db->fetchAssocRes($res) ) {
			$this->makeSlug($u['id'], "user", $u[$field]);
		}

		// generate listings slugs
		$title_str = "title";
		// if translate_title and description
		global $ads_settings; 
		if($ads_settings['translate_title_description'])
			$title_str.="_".$default_lang;
		$res = $db->query("select `id`, `$title_str` from ".TABLE_ADS);
		while($l = $db->fetchAssocRes($res) ) {
			$this->makeSlug($l['id'], "listing", $l[$title_str]);
		}
	
	}

	
	function generateCategorySlugs() {
	
		global $db;
		$db->query("delete from ".TABLE_SLUGS." where `type` like 'category'");
	
		$default_lang = languages::getDefault();
		$res = $db->query("select `id`, `name` from ".TABLE_CATEGORIES."_lang where `lang_id`='$default_lang'");
		while($c = $db->fetchAssocRes($res) ) {
			$this->makeSlug($c['id'], "category", $c['name']);
		}
		
		$cc = new categories_config;
		$cc->clearCategoriesCache();
		
	}
}
?>
