<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class banners {

	var $id;
	var $error;
	var $clean;
	var $category;
	var $array;
	var $tmp;
	var $path;

	public function __construct($id=0)
	{

		global $db;
		$this->path="../images/baners/";
		if($id) {
			$this->id=$id;
			$this->array=array();
			$this->array=$db->fetchAssoc('select * from '.TABLE_BANNERS.' where `id`='.$id);
		}
	}

	function getId() {
		return $this->id;
	}


	function addHit($id=0) {

		global $db;
		if(!$id) $id=$this->id;
		$res_del=$db->query('update '.TABLE_BANNERS.' set `clicks` = `clicks`+1 where `id`="'.$id.'"');
		return 1;
	}

	function getImage($id='') {

		global $db;
		if(!$id) $id=$this->id;
		$res=$db->query("select `filename` from ".TABLE_BANNERS." where `id` = '$id'");
		if($db->numRows($res)) $file = $db->fetchRow(); else $file=0;
		return cleanStr($file);

	}

	function getCode($id='') {

		global $db;
		if(!$id) $id=$this->id;
		$res=$db->query("select `code` from ".TABLE_BANNERS." where `id` = '$id'");
		if($db->numRows($res)) $code = $db->fetchRow(); else $code=0;
		return $code;

	}

	function getType($id='') {

		global $db;
		if(!$id) $id=$this->id;
		$row=$db->fetchAssoc("select `code`, `filename` from ".TABLE_BANNERS." where `id` = '$id'");
		if($row['filename']) return 'image';
		return 'code';

	}

	function getBanner($id='') {
		
		global $db;
		if(!$id) $id=$this->id;
		global $appearance_settings;
		$date_format=$appearance_settings["date_format"];
		
		$result=$db->fetchAssoc('select *, date_format(`date_added`,"'.$date_format.'") as date_nice from '.TABLE_BANNERS.' where `id`="'.$id.'"');
		if($result['filename']!='') $result['type']='image'; else $result['type']='code';
		if($result['filename']!='') {
			$result['extension'] = getExtension($result['filename']);
		} else $result['extension'] = '';
		return $result;

	}

	function getCategories($id='') {

		global $db;
		if(!$id) $id=$this->id;
		$categories=$db->fetchRow("select categories from ".TABLE_BANNERS." where id='$id'");
		if(!$categories) return '';
		if($categories==0) return 0;
		$arr=explode(",",$categories);
		$no=count($arr);
		$array_categories=array();
		for($i=0;$i<$no;$i++) {
			$categ_name=cleanStr(categories::getName($arr[$i]));
			$array_categories[$i]=$categ_name;
		}
		return $array_categories;
	}

	function getSections($id='') {

		global $db;
		if(!$id) $id=$this->id;
		$sections=$db->fetchRow("select `sections` from ".TABLE_BANNERS." where id='$id'");
		if(!$sections) return '';
		if($sections=='') return 0;
		$arr=explode(",",$sections);
		return $arr;
	}


	function getTemplateBanners($pos, $section, $categ, $bloc='') {

		global $db;
		$db = new db_mysql();
		$pos = cleanStr($pos);
		$no_banners = $this->getNoBanners($pos);

		// ------------ banners location module ---------------
		$bloc_cond = "";
		global $modules_array;
		global $self_noext, $settings, $appearance_settings;
		if(in_array("banners_location", $modules_array) && isset($bloc) && $bloc && (($self_noext=="listings" || $self_noext=="details") || ($settings['enable_locations'] ))) {
			$bloc = escape($bloc);
			$bloc_cond = " and `location` = '$bloc' ";

		}
		// ------------ end banners location module ---------------

		$cat_cond = "";
		$cat_regexp = "";
		$section_cond = "";

		global $cat;
		if($self_noext=="listings" || $self_noext=="details" || $cat) {
		if($categ && is_numeric($categ)) $cat_regexp = " or categories REGEXP '\[\[:<:\]\]$categ\[\[:>:\]\]'";
		$cat_cond = "and ( categories like '0' $cat_regexp )";
		}
		if($section) { 
			$section = cleanStr($section);
			$section_cond = "and ( sections like '0' or sections REGEXP '\[\[:<:\]\]$section\[\[:>:\]\]' )";
		}

		$timestamp = date("Y-m-d H:i:s");

		$str_impressions = '';
		
		if($appearance_settings['enable_impressions_count']) $str_impressions = " and ( `max_impressions` = 0 or `impressions` < `max_impressions` )";
		
		$sql = "select * from ".TABLE_BANNERS." where `position`='$pos'".$str_impressions." and ( `max_clicks` = 0 or `clicks` < `max_clicks` ) and ( date_start like '0000-00-00 00:00:00' or date_start <= '$timestamp' or date_start is null) and ( date_end >='$timestamp' or date_end like '0000-00-00 00:00:00' or date_end is null) $cat_cond $bloc_cond $section_cond order by rand() limit $no_banners";

		$array=$db->fetchAssocList($sql);

		// ------------ banners location module ---------------
		// add banners for no location
		if(in_array("banners_location", $modules_array) && ($self_noext=="listings" || $self_noext=="details")) {

			$no_found = count($array);
			$diff = $no_banners - $no_found;

			global $bloc_settings;
			if($diff>0 && $bloc_settings['display_any']) {

				// make the string with found banners
				$j=0;
				$str_found = "";
				foreach($array as $fa) {
					if($j) $str_found .= ",".$fa['id'];
					else $str_found .= $fa['id'];
					$j++;
				}
				if($str_found) $str_found = " and id not in ( $str_found ) ";

				$bloc_cond = " and (`location` is null or `location` = '') ";

				$sql = "select * from ".TABLE_BANNERS." where `position`='$pos'".$str_impressions." and ( `max_clicks` = 0 or `clicks` < `max_clicks` ) and ( date_start like '0000-00-00 00:00:00' or date_start <= '$timestamp' or date_start is null) and ( date_end >='$timestamp' or date_end like '0000-00-00 00:00:00' or date_end is null) $cat_cond $bloc_cond $section_cond $str_found order by rand() limit $diff";

				$array2=$db->fetchAssocList($sql);
				foreach($array2 as $a) {
					$array[$no_found++] = $a;
				}
				
			}
		}
		// ------------ end banners location module ---------------

		$i=0;
		foreach($array as $result) {
			if($result['filename']!='') {
				$array[$i]['extension'] = getExtension($result['filename']);
				$array[$i]['size']=@getimagesize('images/baners/'.$result['filename']);
			} else $array[$i]['extension'] = '';
			$i++;

			// update impressions number
			if($appearance_settings['enable_impressions_count']) 
				$db->query("update ".TABLE_BANNERS." set `impressions` = `impressions` + 1 where id = ".$result['id']);
		}

		return $array;
	}

	static function getBannerPositions() {

		global $db;
		$array=$db->fetchRowList("select `name` from ".TABLE_BANNERS_POSITIONS." where `active`=1");
		return $array;

	}

	function getNoBanners($pos) {

		global $db;
		$no = $db->fetchRow("select `no_banners` from ".TABLE_BANNERS_POSITIONS." where `name` like '$pos' and active=1");
		if(!$no) return 0;
		return $no;
	}

}
?>
