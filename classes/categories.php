<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class categories {


	var $id;
	var $name;

	public function __construct($id='',$name='')
	{
	
		$this->id=$id;
		$this->name=$name;
	}

	function getId() {
		return $this->id;
	}

	static function getName($id) {

		global $db;
		if(!is_numeric($id)) return;
		global $crt_lang;
		$name=$db->fetchRow("select `name` from ".TABLE_CATEGORIES."_lang where id='$id' and `lang_id` = '$crt_lang'");
 		return $name;
	}

	function getPageTitle($id='') {
		global $db;
		if(!is_numeric($id)) return;
		global $crt_lang;
		$title=$db->fetchRow("select `page_title` from ".TABLE_CATEGORIES."_lang where id='$id' and `lang_id` = '$crt_lang'");
		return cleanHtml($title);
	}

	static function getForSeo($id) {

		global $db;
		if(!is_numeric($id)) return;
		global $crt_lang;
		$arr=$db->fetchAssoc("select `name`, `page_title`, `meta_description`, `meta_keywords` from ".TABLE_CATEGORIES."_lang where id='$id' and `lang_id` = '$crt_lang'");

		if(!$arr) return;
		foreach($arr as $key=>$value) $arr[$key] = cleanHtml($value);
		return $arr;

	}

	static function getFieldset($id='') {

		global $db;
		if(!is_numeric($id)) return;
		$fieldset=$db->fetchRow('select fieldset from '.TABLE_CATEGORIES.' where id="'.$id.'"');
		return $fieldset;

	}

	function isParent($id='') {

		global $db;
		if(!$id) $id = $this->id;
		if(!is_numeric($id)) return;
		$res = $db->query("select id from ".TABLE_CATEGORIES." where parent_id = '$id' limit 1");
		$no = $db->numRows($res);
		if($no>0) return 1;
		return 0;
	}

	function getParent($id='') {

		global $db;
		if(!$id) $id = $this->id;
		if(!is_numeric($id)) return;
		$parent = $db->fetchRow('select parent_id from '.TABLE_CATEGORIES.' where id="'.$id.'"');
		return $parent;
	}

	static function getParentName($id) {

		if(!is_numeric($id)) return;
		global $db;
		global $crt_lang;
		$parent = $db->fetchRow("SELECT `name` FROM `".TABLE_CATEGORIES."_lang` left join ".TABLE_CATEGORIES." on ".TABLE_CATEGORIES.".`parent_id` = ".TABLE_CATEGORIES."_lang.`id` where ".TABLE_CATEGORIES.".id=$id and lang_id='$crt_lang'");
		return $parent;
	}

	static function getSubcatsNo($id='') {

		global $db;
		if(!$id) $id = $this->id;
		if(!is_numeric($id)) return;
		$no=$db->fetchRow("select count(*) from ".TABLE_CATEGORIES." where parent_id = '$id' and `active`=1");
		return $no;
	}

	function getNo() {

		global $db;
		$no=$db->fetchRow('select count(*) from '.TABLE_CATEGORIES." and `active`=1");
		return $no;
	}

	function getAdsNo($id, $no=0) {

		if(!is_numeric($id)) return;
		global $db;
		$n=$db->fetchRow('select count(*) from '.TABLE_ADS.' where active=1 and category_id='.$id);
		$no+=$n;
		if($this->isParent($id)) {
			$arr = $db->fetchRowList("select id from ".TABLE_CATEGORIES." where parent_id='$id' and `active`=1");
			foreach($arr as $catid) $no+=$this->getAdsNo($catid, 0);
		}
		return $no;

	}

	function getIdByName($name) {

		global $db;
		$id=$db->fetchRow('select id from '.TABLE_CATEGORIES.'_lang where `name` like "'.$name.'"');
		return $id;
	}

	function getSubcats($parent, $str_start, $array_subcats, $set=0) {

		if(!is_numeric($parent)) return;
		global $db;
		global $crt_lang;

		if($set) $str_fset = " and fieldset='$set'"; else $str_fset='';
		$result=$db->fetchAssocList("select * from ".TABLE_CATEGORIES." LEFT JOIN ".TABLE_CATEGORIES."_lang on ".TABLE_CATEGORIES.".`id` = ".TABLE_CATEGORIES."_lang.`id` where ".TABLE_CATEGORIES.".parent_id='$parent' and `lang_id` = '$crt_lang' and `active`=1 ".$str_fset." order by `order_no`");
		foreach($result as $array) {

			$no=count($array_subcats);
			$array_subcats[$no]=array();
			$array_subcats[$no]['id']=$array['id'];
			$array_subcats[$no]['name']=cleanStr($array['name']);
			$array_subcats[$no]['str_start']=$str_start;
			$array_subcats[$no]['parent']=$this->isParent($array['id']);
			$array_subcats=$this->getSubcats($array['id'], $str_start.'&nbsp;&nbsp;&nbsp;', $array_subcats, $set);
			
		}
		return $array_subcats;
	}

	// array of categories mainly for drop down menu usage
	function getAllOptions ($crt_group=0, $parent_id=0) {

		global $db;
		global $crt_lang;

		$group_regexp = '';
		if($crt_group) { 
			if($crt_group==-1) // nologin user
				$group_regexp = " and `groups`=0 ";
			else 
				$group_regexp = " and (`groups` REGEXP '\[\[:<:\]\]$crt_group\[\[:>:\]\]' or `groups`=0 )";
		}

		$parent_str = '';
		if($parent_id) 
			$parent_str = " and `parent_id` = '$parent_id' ";

		$result=$db->fetchAssocList("select ".TABLE_CATEGORIES.".`id`, `name`, description, `parent_id`, `groups`, `order_no`, `level`, `slug` 
		from ".TABLE_CATEGORIES." 
		LEFT JOIN ".TABLE_CATEGORIES."_lang on ".TABLE_CATEGORIES.".`id` = ".TABLE_CATEGORIES."_lang.`id`
		LEFT JOIN ".TABLE_SLUGS." on ".TABLE_CATEGORIES.".`id` = ".TABLE_SLUGS.".`object_id` 
		where `lang_id`='$crt_lang' and `active`=1 and ".TABLE_SLUGS.".`type`='category' $group_regexp $parent_str order by `order_no`");
		$no = count($result);
		$parents_array = array();
		for($i=0; $i<$no; $i++) { 

			$result[$i]['name']=cleanHtml($result[$i]['name']);
			$result[$i]['url_title'] = _urlencode($result[$i]['name']);
			if(!$parent_id) {
				$result[$i]['str'] = '';
				for($j=0; $j<$result[$i]['level']-1;$j++) $result[$i]['str'] .="&nbsp;&nbsp;&nbsp;";
				// add to parents list
				if(!in_array($result[$i]['parent_id'], $parents_array)) array_push($parents_array, $result[$i]['parent_id']);
			}

		}

		if(!$parent_id) {
			// set parents
			for($i=0; $i<$no; $i++) { 
				$result[$i]['parent'] = 0;
				if(in_array($result[$i]['id'], $parents_array)) $result[$i]['parent'] = 1;
			}
		}

		return $result;
	}


	function getCategories ($parent='') {

		global $db;
		global $crt_lang;
		global $appearance_settings;

		global $is_mobile;
		if($is_mobile) $appearance_settings['first_page_type'] = 1;
		
		if(isset($parent) && is_numeric($parent) && $appearance_settings['first_page_type']==1) $where_parent=" and parent_id='$parent'"; else $where_parent='';

		global $settings;
		$ads_str = "";
		$join_str = "";
		$where_str = "";
		// if locations enabled and a location is set
		if($settings['enable_locations']) {
			global $location_array;
			$no_locs = count($location_array);
			if($no_locs) {
				$ak = array_keys($location_array);
				$crt_location_field=""; $crt_location="";
				// get current location
				foreach($location_array as $key=>$value) {
					if($key!="zip" && $key && $value) { $crt_location_field = $key; $crt_location = $value; }
				}

				if( $crt_location_field && $crt_location ) {
					$ads_str = ", ".TABLE_CATEGORIES_NO_ADS.".`no` as `ads` ";
					$join_str = " left outer join ".TABLE_CATEGORIES_NO_ADS." on (".TABLE_CATEGORIES.".id = ".TABLE_CATEGORIES_NO_ADS.".`category_id`  and `field` like '$crt_location_field' and `val` like '".escape($crt_location)."')";
				}
			}
		}

		$result=$db->fetchAssocList("select ".TABLE_CATEGORIES.".*, ".TABLE_CATEGORIES."_lang.*, 0 as `last`, `slug` $ads_str 
		from ".TABLE_CATEGORIES." 
		LEFT JOIN ".TABLE_CATEGORIES."_lang on ".TABLE_CATEGORIES.".`id` = ".TABLE_CATEGORIES."_lang.`id` 
		LEFT JOIN ".TABLE_SLUGS." on ".TABLE_CATEGORIES.".`id` = ".TABLE_SLUGS.".`object_id` 
		$join_str where `lang_id`='$crt_lang' and `active`=1 and ".TABLE_SLUGS.".`type`='category' $where_parent order by `order_no`");

		$arr_fields = array("name", "description");
		$no_cats_per_row=$appearance_settings["max_cat_per_row"];
		$no = count($result);


		// number of categories on each column
		if($appearance_settings['first_page_type']==2) { 
			$first_level_categories = $db->fetchRow("select count(*) from ".TABLE_CATEGORIES." where `parent_id`=0");
			$array_first_parents = array();
			$div = floor($first_level_categories/$no_cats_per_row);

			$mod = $first_level_categories%$no_cats_per_row;
			for($i=0; $i<$no_cats_per_row;$i++) array_push($array_first_parents, $div);
			for($i=0; $i<$mod;$i++) $array_first_parents[$i]++;

			// set in the table the order of first parent in each row, starting with second
			$sum = 1;
			for($i=0; $i<$no_cats_per_row;$i++) {
				$sum = $array_first_parents[$i] + $sum;
				$array_first_parents[$i] = $sum;
			}

			$result[$no-1]['last'] = 1;
			$crt_row = 0;
			$crt_parent = 0;
		}

		for($i=0; $i<$no; $i++) { 

			$result[$i]['url_title'] = _urlencode($result[$i]['name']);
			$result[$i]['name'] = cleanHtml($result[$i]['name']);

			if($appearance_settings['first_page_type']==1) { 
				$result[$i]['description'] = cleanHtml($result[$i]['description']);
				$result[$i]['subcats']=categories::getSubcatsNo($result[$i]['id']);
				$result[$i]['crt_row']=ceil(($i+1) / $no_cats_per_row);
				$result[$i]['last_on_row'] = ($i+1)%$no_cats_per_row==0?1:0;
				$result[$i]['alt'] = trim($this->catAlt($result[$i]['id'], ''));
			} // end if($appearance_settings['first_page_type']==1)

			if($appearance_settings['first_page_type']!=1) { 
				$result[$i]['subcats']=categories::getSubcatsNo($result[$i]['id']);
			} // end if($appearance_settings['first_page_type']!=1)

			if(isset($result[$i]['ads']) && $result[$i]['ads']) $result[$i]['ads'] = format_int($result[$i]['ads']);

			if($appearance_settings['first_page_type']==2) { 
				if(!$result[$i]['parent_id']) { 
					$crt_parent++;
					if(in_array($crt_parent, $array_first_parents) && $i>0) $result[$i-1]['last'] = 1;
				}
			}

		}

		return $result;

	}



	function catPathArray($parent, $arr) {

		if(!$parent) return $arr;

		global $db;
		global $crt_lang;

		$result=$db->fetchAssoc("select ".TABLE_CATEGORIES.".`id`, `name`, `parent_id` from ".TABLE_CATEGORIES." LEFT JOIN ".TABLE_CATEGORIES."_lang on ".TABLE_CATEGORIES.".`id` = ".TABLE_CATEGORIES."_lang.`id` where ".TABLE_CATEGORIES.".`id`=".$parent." and `lang_id`='$crt_lang'");

		$result['name'] = cleanHtml($result['name']);
		$result['url_category'] = _urlencode($result['name']);
		$no = count($arr);
		$arr[$no] = $result;

		//$str = $result['name']." : ".$str;
		$arr=$this->catPathArray($result['parent_id'], $arr);

		return $arr;

	}

	function catPath($parent, $str) {

		if(!$parent) return $str;

		global $db;
		global $crt_lang;

		$result=$db->fetchAssoc("select ".TABLE_CATEGORIES.".`id`, `name`, `parent_id` from ".TABLE_CATEGORIES." LEFT JOIN ".TABLE_CATEGORIES."_lang on ".TABLE_CATEGORIES.".`id` = ".TABLE_CATEGORIES."_lang.`id` where ".TABLE_CATEGORIES.".`id`=".$parent." and `lang_id`='$crt_lang'");

		$result['name'] = cleanHtml($result['name']);
		$str = $result['name']." : ".$str;
		$str=$this->catPath($result['parent_id'], $str);

		return $str;

	}

	function catAlt($parent, $str) {

		if(!$parent) return $str;

		global $db;
		global $crt_lang;

		$result=$db->fetchAssoc("select ".TABLE_CATEGORIES.".`id`, `name`, `parent_id` from ".TABLE_CATEGORIES." LEFT JOIN ".TABLE_CATEGORIES."_lang on ".TABLE_CATEGORIES.".`id` = ".TABLE_CATEGORIES."_lang.`id` where ".TABLE_CATEGORIES.".`id`=".$parent." and `lang_id`='$crt_lang'");

		$result['name'] = cleanHtml($result['name']);
		$str = $result['name']." ".$str;
		$str=$this->catAlt($result['parent_id'], $str);

		return $str;

	}

	function makeCategoryPath($id) {

		if(!is_numeric($id)) return;
		global $db;
		global $crt_lang;

		$result = $db->fetchAssoc("select ".TABLE_CATEGORIES.".`id`, `name`, `parent_id` from ".TABLE_CATEGORIES." LEFT JOIN ".TABLE_CATEGORIES."_lang on ".TABLE_CATEGORIES.".`id` = ".TABLE_CATEGORIES."_lang.`id` where ".TABLE_CATEGORIES.".`id`=".$id." and `lang_id`='$crt_lang'");

		if(empty($result)) return "";

		$result['name'] = cleanHtml($result['name']);

		$str = $this->catPath($result['parent_id'], $result['name']);
		return $str;
	}


	function makeFooterLinks() {

		global $crt_lang;
		global $db;
		global $config_live_site;

		$result=$db->fetchAssocList("select ".TABLE_CATEGORIES.".`id`, `name` from ".TABLE_CATEGORIES." LEFT JOIN ".TABLE_CATEGORIES."_lang on ".TABLE_CATEGORIES.".`id` = ".TABLE_CATEGORIES."_lang.`id` where  `lang_id`='$crt_lang' and `active`=1 order by `order_no`");

	        $str='';
        	$str_no_href='';
		global $config_footer_categ_len;
		global $seo_settings;
		foreach($result as $row) {
			$cat_name=cleanHtml($row['name']);
			//$url_title= _urlencode($cat_name);
			$cat_id=$row['id'];
			if($seo_settings['enable_mod_rewrite']) {
				global $seo;
				$cat_link = $seo->makeSearchCategoryLink($cat_id, $cat_name);
			}
			else $cat_link = $config_live_site."/listings.php?category=".$cat_id;
			if(strlen($str_no_href)+strlen($cat_name) < $config_footer_categ_len)
			{
				if($str)
				{
					$str.=' | ';
					$str_no_href.=' | ';
				}
				$str.= '<a href="'.$cat_link.'">'.$cat_name.'</a>';
				$str_no_href.=$cat_name;
			}
			else
			{
				$str.='<br />';
				$str.='<a href="'.$cat_link.'">'.$cat_name.'</a>';
				$str_no_href=$cat_name;
			}
		}

		return $str;
	}

	function getGroups($id='') {

		global $db;
		if(!$id) $id=$this->id;
		$groups=$db->fetchRow("select `groups` from ".TABLE_CATEGORIES." where id='$id'");
		if(!$groups) return 0;
		$arr=explode(",",$groups);
		$no=count($arr);
		$array_groups=array();
		for($i=0;$i<$no;$i++) {
			$array_users[$i]=groups::getName($arr[$i]);
		}
		return $array_groups;
	}


	function getAllSet($set) {

		global $db;
		global $crt_lang;
		$sql_set = "";
		if(!$set) { 
			$set = 0;
		}
		else $sql_set = " `fieldset`=".$set." and ";

		$array=$db->fetchAssocList("select ".TABLE_CATEGORIES.".`id`, `name`, `parent_id` from ".TABLE_CATEGORIES." LEFT JOIN ".TABLE_CATEGORIES."_lang on ".TABLE_CATEGORIES.".`id` = ".TABLE_CATEGORIES."_lang.`id` where $sql_set `lang_id`='$crt_lang' and `active`=1 order by `order_no`");

		//$result = array();
		$i=0;
		foreach ($array as $row) {
			//$result[$i] = $row;
			$array[$i]['name'] = cleanHtml($row['name']);
			$array[$i]['url_title'] = _urlencode($row['name']);
			if($array[$i]['parent_id']!=0) $array[$i]['parent']=1; else $array[$i]['parent']=0;
			$i++;
		}

		return $array;
	}

	function subcatList($categ, &$str) {

		if(!is_numeric($categ)) return;
		global $db;

		$result=$db->fetchAssocList("select `id` from ".TABLE_CATEGORIES." where `parent_id`=".$categ." and `active`=1");

		foreach($result as $row) {
			$subcat = $row['id'];
			$this->subcatList($subcat, $str);
		}

		if($str) $str.=",";
		$str.=$categ;
		return $str;

	}

	function addToCat($cat_id, $no) {

		if(!is_numeric($cat_id) || !is_numeric($no)) return;
		global $db;
		$cats = new categories;
		$arr = $cats->catPathArray($cat_id, array());
		foreach($arr as $parent) {
			$res=$db->query("update ".TABLE_CATEGORIES." set `ads`=`ads`+$no where `id`='".$parent['id']."'");
		}

	}

	function getParentCategory($id) {
		
		global $db;
		global $crt_lang;
		$parent_id = $db->fetchRow("select `parent_id` from ".TABLE_CATEGORIES." where `id`='$id'");
		if(!$parent_id) return null;

		$parent_details = $db->fetchAssoc("select `id`, `name` from ".TABLE_CATEGORIES."_lang where `id` = '$parent_id' and `lang_id`='$crt_lang'");
		$parent_details['name'] = escape($parent_details['name']);
		$parent_details['url_title'] = _urlencode($parent_details['name']);
		return $parent_details;

	}

}
?>
