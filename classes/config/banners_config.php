<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class banners_config {

	var $path;

	public function __construct()
	{
	
		global $config_abs_path;
		$this->path=$config_abs_path."/images/baners/";

	}

	function delete($id=0) {

		global $db;
		global $config_demo;
		if($config_demo==1) return;

		if(!$id) $id=$this->id;
		$filename=banners::getImage($id);
		if($filename) {
			if(file_exists($this->path.$filename)) @unlink($this->path.$filename);
		}
		$res_del=$db->query('delete from '.TABLE_BANNERS.' where `id`="'.$id.'"');

	}

	function deleteCateg($id) {

		global $db;
		$result = $db->fetchRowList("select id from ".TABLE_BANNERS." where position = $id");
		foreach ($result as $row) $this->delete($row);
		
	}

	function deletePosition($id=0) {

		global $db;
		global $config_demo;
		if($config_demo==1) return;

		if(!$id) $id=$this->id;
		$res_del=$db->query('delete from '.TABLE_BANNERS_POSITIONS.' where `name` like "'.$id.'"');
		// remove all banners from categ
		$banners = new banners();
		$banners->deleteCateg($id);

	}

	function clearHits($id=0) {

		global $db;
		if(!$id) $id=$this->id;
		$res_del=$db->query('update '.TABLE_BANNERS.' set `clicks` = 0, `impressions` = 0 where `id`="'.$id.'"');
		return 1;
	}

	function getHits($id='') {

		global $db;
		if(!$id) $id=$this->id;
		$res=$db->query("select `clicks` from ".TABLE_BANNERS." where `id` = '$id'");
		if($db->numRows($res)) $hits = $db->fetchRow(); else $hits=0;
		return $hits;

	}

	function getList() {

		global $db;
		$array=$db->fetchAssocList("select id from ".TABLE_BANNERS);
		return $array;

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

	function getNo() {

		global $db;
		$no=$db->fetchRow('select count(*) from '.TABLE_BANNERS);
		return $no;
	}

	function EnablePosition($id) {

		global $db;
		global $config_demo;
		if($config_demo==1) return;

		$res=$db->query('update '.TABLE_BANNERS_POSITIONS.' set `active`=1 where `id` = "'.$id.'"');

		$this->clearBannerPositionsCache();

	}

	function DisablePosition($id) {

		global $db;
		global $config_demo;
		if($config_demo==1) return;

		$res=$db->query('update '.TABLE_BANNERS_POSITIONS.' set `active`=0 where `id` = "'.$id.'"');

		$this->clearBannerPositionsCache();

	}

	function moveUp($id) {

		global $db;
		global $config_demo;
		if($config_demo==1) return;
		
		$res=$db->query("update ".TABLE_BANNERS_POSITIONS." set `no_banners`=`no_banners`+1 where `id` = '$id'");

		$this->clearBannerPositionsCache();

		return 1;
	}

	function moveDown($id) {

		global $db;
		global $config_demo;
		if($config_demo==1) return;
		
		if(!$id) $id=$this->id;
		$no=$db->fetchRow("select `no_banners` from ".TABLE_BANNERS_POSITIONS." where `id` = '$id'");
		if($no<=1) return;
		$goto=$no-1;
		$res=$db->query("update ".TABLE_BANNERS_POSITIONS." set `no_banners`='$goto' where `id` = '$id'");

		$this->clearBannerPositionsCache();

		return 1;
	}

	function getSpecificSection($pos) {

		global $db;
		$ss=$db->fetchRow("select `specific_section` from ".TABLE_BANNERS_POSITIONS." where `name` like '$pos'");
		return $ss;

	}

	function check_form ($id='') {

		global $lng;
		$this->error='';
		$this->tmp=array();

		global $config_demo;
		if($config_demo==1) $this->addError($lng['general']['errors']['demo'].'<br />');

		//if($id!='') $btype=$this->getType($id);
		//else {
			if(!$_POST['btype']) $this->addError($lng['banners']['errors']['type_missing'].'<br />');
			else $btype=$_POST['btype'];
		//}
		if($btype) { 
			if($btype=="image") {
				if(!$_FILES['image']['name'] && !$id) $this->addError($lng['banners']['errors']['image_missing'].'<br />');
				else if($_FILES['image']['name']) {
					$this->img=new image('image',$this->path,'banner');
					$allowedtypes=array("jpeg", "jpg", "png", "gif", "swf");
					$this->img->setTypes($allowedtypes);
					$this->img->setGenerate(0);
					if(!$this->img->verify()) $this->addError($this->img->getError());
				}
			}
			if($btype=="code" && !$_POST['code']) $this->addError($lng['banners']['errors']['code_missing'].'<br />');
		}
		if(!$_POST['position']) $this->addError($lng['banners']['errors']['position_missing'].'<br />');

		$categories_list='';
		$sections_list='';
		if(isset($_POST['choose_categ']) && $_POST['choose_categ']=="choose")
		{
			$categories_list='';
			$i=0;
			while (isset($_POST['categories'][$i]) && $cat=$_POST['categories'][$i]){
				if($i) $categories_list.=',';
				$categories_list.=$cat;
				$i++;
			}
			if($categories_list=='') $this->addError($lng['banners']['errors']['categories_required'].'<br />');
		}

		if(isset($_POST['choose_section']) && $_POST['choose_section']=="choose")
		{
			$sections_list='';
			$i=0;
			while (isset($_POST['sections'][$i]) && $cat=$_POST['sections'][$i]){
				if($i) $sections_list.=',';
				$sections_list.=$cat;
				$i++;
			}
			if($sections_list=='') $this->addError($lng['banners']['errors']['section_required'].'<br />');
		}


		if($this->getError()!='')
		{
			if(!$id) {
				if(isset($_POST['btype'])) $this->tmp['btype']=$_POST['btype']; else $this->tmp['btype']='';
			} else {
				$this->tmp['id']=$id;
				$this->tmp['btype']=$btype;
			}
			$this->tmp['categories']=$categories_list;
			$this->tmp['sections']=$sections_list;
			if(isset($_POST['title'])) $this->tmp['title']=$_POST['title']; else $this->tmp['title']='';
			if(isset($_POST['code'])) $this->tmp['code']=$_POST['code']; else $this->tmp['code']='';
			if(isset($_POST['position'])) $this->tmp['position']=$_POST['position']; else $this->tmp['position']='';
			if(isset($_POST['link'])) $this->tmp['link']=$_POST['link']; else $this->tmp['link']='';
			if(isset($_POST['choose_categ'])) $this->tmp['choose_categ']=$_POST['choose_categ'];
			if(isset($_POST['choose_section'])) $this->tmp['choose_section']=$_POST['choose_section'];

			if(isset($_POST['date_start'])) $this->tmp['date_start']=$_POST['date_start']; else $this->tmp['date_start']='';
			if(isset($_POST['date_end'])) $this->tmp['date_end']=$_POST['date_end']; else $this->tmp['date_end']='';
			if(isset($_POST['max_impressions'])) $this->tmp['max_impressions']=$_POST['max_impressions']; else $this->tmp['max_impressions']='';
			if(isset($_POST['max_clicks'])) $this->tmp['max_clicks']=$_POST['max_clicks']; else $this->tmp['max_clicks']='';

			global $modules_array;
			if(in_array("banners_location", $modules_array)) {
				$this->tmp['location'] = escape($_POST['location']);
			}

		}

		return 1;
	}

	function add() {
	
		global $db;
		$this->clean=array();
		$this->check_form();
		if($this->getError()!='') return 0;
		$this->clean['btype'] = escape($_POST['btype']);
		$this->clean['position'] = escape($_POST['position']);
		if($_POST['title']) $this->clean['title'] = clean($_POST['title']); else $this->clean['title']='';
		if($_POST['code']) $this->clean['code'] = _addslashes($_POST['code']); else $this->clean['code']='';
		if($_POST['link']) { 
			$this->clean['link'] = escape($_POST['link']); 
			if(strcmp(substr($this->clean['link'],0,7),"http://") && strcmp(substr($this->clean['link'],0,8),"https://")) $this->clean['link']="http://".$this->clean['link'];
		} else $this->clean['link']='';

		if($_FILES['image']['name']) {
			if($this->img->upload()) $this->clean['image'] = $this->img->getUploadedFile();
			else  $this->addError($this->img->getError());
		} else $this->clean['image']='';

		if(isset($_POST['choose_categ']) && $_POST['choose_categ']=="choose")
		{
			$this->clean['categories']='';
			$i=0;
			while (isset($_POST['categories'][$i]) && $cat=escape($_POST['categories'][$i])){
				if($i) $this->clean['categories'].=',';
				$this->clean['categories'].=$cat;
				$i++;
			}
		} else $this->clean['categories']='0';

		if(isset($_POST['choose_section']) && $_POST['choose_section']=="choose")
		{
			$this->clean['sections']='';
			$i=0;
			while (isset($_POST['sections'][$i]) && $cat=escape($_POST['sections'][$i])){
				if($i) $this->clean['sections'].=',';
				$this->clean['sections'].=$cat;
				$i++;
			}
		} else $this->clean['sections']='0';

		if($_POST['date_start']) $this->clean['date_start'] = escape($_POST['date_start']); else $this->clean['date_start']='';
		if($_POST['date_end']) $this->clean['date_end'] = escape($_POST['date_end']); else $this->clean['date_end']='';
		if($_POST['max_impressions']) $this->clean['max_impressions'] = escape($_POST['max_impressions']); else $this->clean['max_impressions']=0;
		if($_POST['max_clicks']) $this->clean['max_clicks'] = escape($_POST['max_clicks']); else $this->clean['max_clicks']=0;
		$this->clean['open_new'] = checkbox_value("open_new");

		$timestamp = date("Y-m-d H:i:s");
		$sql = 'insert into '.TABLE_BANNERS.' set `filename` = "'.$this->clean['image'].'", `code` = "'.$this->clean['code'].'", `position` = "'.$this->clean['position'].'", `link` = "'.$this->clean['link'].'", `date_added` = "'.$timestamp.'", `categories` = "'.$this->clean['categories'].'",';
		if($this->clean['date_start']) $sql.='`date_start` = "'.$this->clean['date_start'].'", ';
		if($this->clean['date_end']) $sql.='`date_end` = "'.$this->clean['date_end'].'", ';
		$sql.= '`max_impressions` = "'.$this->clean['max_impressions'].'", `max_clicks` = "'.$this->clean['max_clicks'].'", `sections` = "'.$this->clean['sections'].'", `title`= "'.$this->clean['title'].'", `open_new`= "'.$this->clean['open_new'].'"';

		global $modules_array;
		if(in_array("banners_location", $modules_array)) {
			$location = escape($_POST['location']);
			$sql.=", `location` =  '$location'";
		}

		$res=$db->query($sql);

		return 1;

	}

	function edit($id) {
	
		global $db;
		$this->clean=array();
		$this->check_form($id);
		if($this->getError()!='') return 0;
		//$this->clean['btype'] = $this->getType($id);
		$this->clean['btype'] = escape($_POST['btype']);
		$this->clean['position'] = escape($_POST['position']);
		if($_POST['title']) $this->clean['title'] = clean($_POST['title']); else $this->clean['title']='';
		if($_POST['code']) $this->clean['code'] = _addslashes($_POST['code']); else $this->clean['code']='';
		if($_POST['link']) { 
			$this->clean['link'] = escape($_POST['link']); 
			if(strcmp(substr($this->clean['link'],0,7),"http://") && strcmp(substr($this->clean['link'],0,8),"https://")) $this->clean['link']="http://".$this->clean['link'];
		} else $this->clean['link']='';

		if($_FILES['image']['name']) {
			if($this->img->upload()) $this->clean['image'] = $this->img->getUploadedFile();
			else  $this->addError($this->img->getError());
		} else $this->clean['image']='';

		if(isset($_POST['choose_categ']) && $_POST['choose_categ']=="choose")
		{
			$this->clean['categories']='';
			$i=0;
			while (isset($_POST['categories'][$i]) && $cat=escape($_POST['categories'][$i])){
				if($i) $this->clean['categories'].=',';
				$this->clean['categories'].=$cat;
				$i++;
			}
		} else $this->clean['categories']='0';


		if(isset($_POST['choose_section']) && $_POST['choose_section']=="choose")
		{
			$this->clean['sections']='';
			$i=0;
			while (isset($_POST['sections'][$i]) && $cat=escape($_POST['sections'][$i])){
				if($i) $this->clean['sections'].=',';
				$this->clean['sections'].=$cat;
				$i++;
			}
		} else $this->clean['sections']='0';

		if($_POST['date_start']) $this->clean['date_start'] = escape($_POST['date_start']); else $this->clean['date_start']='';
		if($_POST['date_end']) $this->clean['date_end'] = escape($_POST['date_end']); else $this->clean['date_end']='';
		if($_POST['max_impressions']) $this->clean['max_impressions'] = escape($_POST['max_impressions']); else $this->clean['max_impressions']=0;
		if($_POST['max_clicks']) $this->clean['max_clicks'] = escape($_POST['max_clicks']); else $this->clean['max_clicks']=0;
		$this->clean['open_new'] = checkbox_value("open_new");


		$res=$db->query('update '.TABLE_BANNERS.' set `code` = "'.$this->clean['code'].'", `position` = "'.$this->clean['position'].'", `link` = "'.$this->clean['link'].'", `categories` = "'.$this->clean['categories'].'", `max_impressions` = "'.$this->clean['max_impressions'].'", `max_clicks` = "'.$this->clean['max_clicks'].'", `sections` = "'.$this->clean['sections'].'", `title`= "'.$this->clean['title'].'" where id='.$id.';');

		if($this->clean['date_start']) $res=$db->query('update '.TABLE_BANNERS.' set `date_start` = "'.$this->clean['date_start'].'" where id='.$id.';');
		else  $res=$db->query('update '.TABLE_BANNERS.' set `date_start` = null where id='.$id.';');

		if($this->clean['date_end']) $res=$db->query('update '.TABLE_BANNERS.' set `date_end` = "'.$this->clean['date_end'].'" where id='.$id.';');
		else  $res=$db->query('update '.TABLE_BANNERS.' set `date_end` = null where id='.$id.';');

		if($this->clean['btype']=="image" && $_FILES['image']['name']) $res = $db->query("update ".TABLE_BANNERS." set `filename`='".$this->clean['image']."', `code`='' where id=$id");
		if($this->clean['btype']=="code") $res = $db->query("update ".TABLE_BANNERS." set `filename`='' where id=$id");

		$res = $db->query("update ".TABLE_BANNERS." set `open_new`='".$this->clean['open_new']."' where id=$id");
		
		global $modules_array;
		if(in_array("banners_location", $modules_array)) {
			$location = escape($_POST['location']);
			$res = $db->query("update ".TABLE_BANNERS." set `location` =  '$location' where id=$id");
		}
		
		return 1;

	}

	function getAll($category='') {

		global $db;
		global $lng;
		$cat_str='';
		global $appearance_settings;
		$date_format=$appearance_settings["date_format"];
		if($category) $cat_str=" where `category_id`='$category'";
		$array=$db->fetchAssocList("select *, date_format(`date_added`,'$date_format') as date_nice, date_format(`date_start`,'$date_format') as date_start_nice, date_format(`date_end`,'$date_format') as date_end_nice from ".TABLE_BANNERS.$cat_str);
		$i=0;
		foreach($array as $result) {
		
			if(trim($result['code'])) $array[$i]['type']=$lng['banners']['code'];
			else  $array[$i]['type']=$lng['banners']['image'];
			
			if($result['filename']!='') {
				$array[$i]['extension'] = getExtension($result['filename']);
			} else $array[$i]['extension'] = '';
			$i++;
		}
		return $array;

	}

	function getAllPositions() {

		global $db;
		$array=$db->fetchAssocList('select '.TABLE_BANNERS_POSITIONS.'.*, count('.TABLE_BANNERS.'.`id`) as total_banners from '.TABLE_BANNERS_POSITIONS.' left join '.TABLE_BANNERS.' on '.TABLE_BANNERS_POSITIONS.'.`name` = '.TABLE_BANNERS.'.`position` group by '.TABLE_BANNERS_POSITIONS.'.`name` order by `active` desc, '.TABLE_BANNERS_POSITIONS.'.id asc');

		$i=0;
		foreach ($array as $result) {
			if($result['no_banners']>1) $array[$i]['order_down']=1; else $array[$i]['order_down']=0;
			$i++;
		}
		return $array;

	}

	function getShortPositions() {

		global $db;
		$array=$db->fetchAssocList('select '.TABLE_BANNERS_POSITIONS.'.* from '.TABLE_BANNERS_POSITIONS.' order by `active` desc, id asc');

		return $array;

	}

	function clearBannerPositionsCache() {

		// clear cache
		$lc_cache = new cache();
		$lc_cache->clearCache("base_interface");

	}

	function disableImpressionsCount() {

		global $db;
		global $config_abs_path;
		$db->query("update ".TABLE_APPEARANCE." set `enable_impressions_count` ='0'");
		$sc = new settings_config();
		$sc->clearAppearanceCache();
		return 1;

	}

	function enableImpressionsCount() {

		global $db;
		global $config_abs_path;
		$db->query("update ".TABLE_APPEARANCE." set `enable_impressions_count` ='1'");
		$sc = new settings_config();
		$sc->clearAppearanceCache();
		return 1;

	}

	function disableUnusedPositions() {
	
		global $db;
		$array=$db->fetchRowList('select `name` from '.TABLE_BANNERS_POSITIONS.' where `active`=1');
		foreach($array as $pos) {
		
			$has_banners = $db->fetchRow("select count(*) from ".TABLE_BANNERS." where `position` like '$pos'");
			if(!$has_banners) $db->query("update ".TABLE_BANNERS_POSITIONS." set `active`=0 where `name` like '$pos'");
		
		}
	
		$this->clearBannerPositionsCache();
	}
	
}
?>
