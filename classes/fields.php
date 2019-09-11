<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class fields {

	var $id;
	var $type;
	var $array;
	var $tmp;
	var $table;
	var $edit;
	var $ro;
	var $multi_depending='';

	public function __construct($type)
	{
	
		global $db;
		$this->type = $type;
		if($type=='uf') { $this->table = TABLE_USER_FIELDS; $this->type='uf'; } else { $this->table=TABLE_FIELDS; $this->type='cf'; }
		$this->edit = 0;
	}

	function getId() {
		
		return $this->id;

	}

	function getName($id='') {

		global $db;
		global $crt_lang;
		if(!$id) $id=$this->id;
		$name=$db->fetchRow('select `name` from `'.$this->table.'_lang` where id="'.$id.'" and lang_id="'.$crt_lang.'"');
 		return $name;

	}

	function getNameByCaption($str) {

		global $db;
		global $crt_lang;

		$name=$db->fetchRow("SELECT `name` FROM `".$this->table."` left join ".$this->table."_lang on `".$this->table."`.id = ".$this->table."_lang.id where `caption` like '$str' and lang_id='$crt_lang'");

		if($name) return $name;

		// the field might be a depending field
		//first field
		for($i=1; $i<=4; $i++) {

			$name=$db->fetchRow("SELECT `name$i` FROM `".TABLE_DEPENDING_FIELDS."` left join ".TABLE_DEPENDING_FIELDS."_lang on ".TABLE_DEPENDING_FIELDS.".id = ".TABLE_DEPENDING_FIELDS."_lang.id where `caption$i` like '$str' and lang_id='$crt_lang'");

			if($name) return $name;

		}
	}

	// cf only
	function getDepId($id) {

		global $db;
		$dep=$db->fetchRow('select dep_id from `'.$this->table.'` where id="'.$id.'"');
		return $dep;

	}

	function getType($id='') {
		
		global $db;
		if(!$id) $id=$this->id;
		$type=$db->fetchRow('select type from `'.$this->table.'` where id="'.$id.'"');
		return $type;

	}

	function getTypeByCaption($caption) {
		
		global $db;
		$type=$db->fetchRow('select `type` from `'.$this->table.'` where caption="'.$caption.'"');
		return $type;

	}

	function getDependingByCaption($caption) {
		
		global $db;
		$dep_array = $db->fetchAssoc("select * from ".TABLE_DEPENDING_FIELDS." where `caption1` like '$caption' or `caption2` like '$caption' or `caption3` like '$caption' or `caption4` like '$caption' or `caption5` like '$caption'");
		if($dep_array) {

			if($dep_array['caption1']==$caption) $table = $dep_array['table1'];
			if($dep_array['caption2']==$caption) $table = $dep_array['table2'];
			if($dep_array['caption3']==$caption) $table = $dep_array['table3'];
			if($dep_array['caption4']==$caption) $table = $dep_array['table4'];
			if($dep_array['caption5']==$caption) $table = $dep_array['table5'];
			$dep = new depending_fields();
			global $crt_lang;

			$elements = $dep->getElements($table, $crt_lang);
			$elements_array = explode("|", $elements);
			return $elements_array;
		}
		return array();
	}

	function getIdByCaption($caption) {
		
		global $db;
		$type=$db->fetchRow('select `id` from `'.$this->table.'` where caption="'.$caption.'"');
		return $type;

	}

	function getCaption($id='') {

		global $db;
		if(!$id) $id=$this->id;
		$caption=$db->fetchRow('select `caption` from `'.$this->table.'` where id="'.$id.'"');
		return $caption;

	}

	function getElementsArray($id='') {

		global $db;
		global $crt_lang;
		if(!$id) $id=$this->id;
		$elements=$db->fetchRow("select `elements` from ".$this->table."_lang where id='$id' and `lang_id`='$crt_lang'");
		$elements_array = explode("|", $elements);
		return $elements_array;

	}

	function getSearch($type, $set='') {

		global $db;
		global $crt_lang;
		if($type=="quick") $where_str=" and `quick_search` = 1";
		else  $where_str=" and `advanced_search` = 1";

		if($set)  $where_str .= " and ( `fieldset` REGEXP '\[\[:<:\]\]$set\[\[:>:\]\]'  or `fieldset` = 0 ) ";
		else if($type=="advanced") $where_str .= " and `fieldset` = 0 ";
		$array=$db->fetchAssocList("select * from `".$this->table."` LEFT JOIN `".$this->table."_lang` on `".$this->table."`.`id` = `".$this->table."_lang`.`id` where `lang_id` = '$crt_lang' and `active` = 1".$where_str." order by `order_no`");

		$i=0;
		$arr=array();
		$this->multi_depending = '';
		//$arr['multi_depending'] = '';
		$clean_fields = array("name", "top_str", "error_message", "info_message", "prefix", "postfix", "elements", "search_elements", "default_val", "extensions");
		foreach($array as $row) {

			// clean strings
			foreach($clean_fields as $key) $row[$key] = cleanHtml($row[$key]);

			$arr[$i]=$row;
			$arr[$i]['size'] = str_replace("x", "X", $arr[$i]['size']);
			if(strstr($arr[$i]['size'],"X")) { 
				$arr_size=explode("X",$arr[$i]['size']);
				$arr[$i]['cols']=$arr_size[0];
				$arr[$i]['rows']=$arr_size[1];
			}

			if(trim($row['elements'])) {
			$arr[$i]['elements_array']=explode("|",trim($row['elements']));
			for($a=0; $a<count($arr[$i]['elements_array']);$a++) {
				$arr[$i]['elements_array'][$a]=trim($arr[$i]['elements_array'][$a]);
			}
			} // end if $row['elements']
			
			if( trim($row['search_elements'])) {
			$arr[$i]['search_elements_array']=explode("|",trim($row['search_elements']));
			for($a=0; $a<count($arr[$i]['search_elements_array']);$a++) {
				$arr[$i]['search_elements_array'][$a]=trim($arr[$i]['search_elements_array'][$a]);
			}
			} // end if $row['search_elements']

			if($arr[$i]['type']=='depending') {

				$dep = new depending_fields();
				$depending = $dep->getDependingField($arr[$i]['dep_id']);
				$arr[$i]['depending'] = $depending;
				$arr[$i]['depending']['elements'] = $dep->getTable($arr[$i]['depending']['table1'], $set);
				if(strstr($arr[$i]['fieldset'], ',') || (is_numeric($arr[$i]['fieldset']) && $arr[$i]['fieldset']!=0)) { 
					if($this->multi_depending) $this->multi_depending.=',';
					$this->multi_depending .= $arr[$i]['dep_id'];
				}
			}

			if($arr[$i]['type']=="date") {
				$arr[$i]['date_format_standard'] = convertDateFormat($arr[$i]['date_format']);
			}
			$i++;
		}
		return $arr;
	}

	function getSearchDepValues($arr, $post_arr) {

		$i=0;
		$dep = new depending_fields();
		foreach($arr as $row) {
			if($arr[$i]['type']=='depending') {
				// get second depending field values for the field
				if(isset($post_arr[$arr[$i]['depending']['caption1']]) && $post_arr[$arr[$i]['depending']['caption1']]) {
					$fieldset=0;
					if($post_arr['category']) $fieldset = categories::getFieldset($post_arr['category']);

					$dep_id1 = $dep->getIdByName($arr[$i]['depending']['table1'], escape($post_arr[$arr[$i]['depending']['caption1']]), " and ( `set_id` REGEXP '\[\[:<:\]\]$fieldset\[\[:>:\]\]'  or `set_id` = 0 )");
					$arr[$i]['depending']['elements2'] = $dep->getSecondTable($arr[$i]['depending']['table2'], $dep_id1);

				}

				// get third depending field values for the field
				if($arr[$i]['depending']['no']>2 && isset($post_arr[$arr[$i]['depending']['caption2']]) && $post_arr[$arr[$i]['depending']['caption2']]) {

					$dep_id2 = $dep->getIdByName($arr[$i]['depending']['table2'], escape($post_arr[$arr[$i]['depending']['caption2']]), " and `dep`='$dep_id1'");
					$arr[$i]['depending']['elements3'] = $dep->getSecondTable($arr[$i]['depending']['table3'], $dep_id2);

				}

				// get forth depending field values for the field
				if($arr[$i]['depending']['no']>3 && isset($post_arr[$arr[$i]['depending']['caption3']]) && $post_arr[$arr[$i]['depending']['caption3']]) {

					$dep_id3 = $dep->getIdByName($arr[$i]['depending']['table3'], escape($post_arr[$arr[$i]['depending']['caption3']]), " and `dep`='$dep_id2'");
					$arr[$i]['depending']['elements4'] = $dep->getSecondTable($arr[$i]['depending']['table4'], $dep_id3);

				}

				// get fifth depending field values for the field
				if($arr[$i]['depending']['no']>4 && isset($post_arr[$arr[$i]['depending']['caption4']]) && $post_arr[$arr[$i]['depending']['caption4']]) {

					$dep_id4 = $dep->getIdByName($arr[$i]['depending']['table4'], escape($post_arr[$arr[$i]['depending']['caption4']]), " and `dep`='$dep_id3'");
					$arr[$i]['depending']['elements5'] = $dep->getSecondTable($arr[$i]['depending']['table5'], $dep_id4);

				}
			}// end if depending
			$i++;
		}//end foreach
		return $arr;
	}

	function getSearchForCategory($categ, $type) {

		global $db;

		if($type=="quick") $str = "`quick_search`=1";
		else $str = "`advanced_search`=1";

		$array = $db->fetchAssocList("SELECT `".TABLE_FIELDS."`.`id`, `fieldset` FROM `".TABLE_FIELDS."` where ".$str);
		$c = new categories;
		$categ_fieldset = $c->getFieldset($categ);
		$result = array();
		$i=0;
		foreach($array as $row) {
			$id = $row['id'];
			if($row['fieldset']==0) {
				$result[$i] = $id;
				$i++;
				continue;
			}
			$fieldset_array = explode(",",$row['fieldset']);
			if(in_array($categ_fieldset, $fieldset_array)) {
				$result[$i] = $id;
				$i++;
				continue;
			}
		}
		

		return $result;

	
	}

	function getSearchFields() {

		global $db;
		global $crt_lang;

		$i=0;
		$arr=array();

		global $languages_array;
		foreach($languages_array as $l) $arr[$l] = array();

		$array=$db->fetchAssocList("select * from `".$this->table."` LEFT JOIN `".$this->table."_lang` on `".$this->table."`.`id` = `".$this->table."_lang`.`id` where `active` = 1 order by `".$this->table."`.`id`");

		foreach($array as $row) {
			if(!in_array($row['lang_id'], $languages_array)) continue;

			if($row['type']!='depending') { 
				$arr[$row['lang_id']][$row['caption']]['is_numeric'] = $row['is_numeric'];
				$arr[$row['lang_id']][$row['caption']]['dep_id'] = 0;
				$arr[$row['lang_id']][$row['caption']]['search_type'] = 'default';
				$arr[$row['lang_id']][$row['caption']]['elements'] = $row['elements'];
				$arr[$row['lang_id']][$row['caption']]['type'] = $row['type'];
				$arr[$row['lang_id']][$row['caption']]['all_val'] = $row['all_val'];
			}

			if($row['search_type']==2) {

				$arr[$row['lang_id']][$row['caption']."_low"]['search_type'] = 'interval';
				$arr[$row['lang_id']][$row['caption']."_high"]['search_type'] = 'interval';
				$arr[$row['lang_id']][$row['caption']."_low"]['elements'] = '';
				$arr[$row['lang_id']][$row['caption']."_high"]['elements'] = '';
				$arr[$row['lang_id']][$row['caption']."_low"]['type'] = $row['type'];
				$arr[$row['lang_id']][$row['caption']."_high"]['type'] = $row['type'];

			} 
			else if($row['search_type']==3) {

				$arr[$row['lang_id']][$row['caption']]['search_type'] = 'keyword';
				$arr[$row['lang_id']][$row['caption']]['elements'] = $row['elements'];
				$arr[$row['lang_id']][$row['caption']]['type'] = $row['type'];

			}

			//else 
			if($row['type']=="depending") {

				$dep = new depending_fields();
				$depending = $dep->getDependingField($row['dep_id']);

				$arr[$row['lang_id']][$depending['caption1']]['dep_id'] = $row['dep_id'];
				$arr[$row['lang_id']][$depending['caption2']]['dep_id'] = $row['dep_id'];

				$arr[$row['lang_id']][$depending['caption1']]['search_type'] = 'default';
				$arr[$row['lang_id']][$depending['caption2']]['search_type'] = 'default';
				if($depending['no']>=3) $arr[$row['lang_id']][$depending['caption3']]['search_type'] = 'default';
				if($depending['no']>=4) $arr[$row['lang_id']][$depending['caption4']]['search_type'] = 'default';

				$arr[$row['lang_id']][$depending['caption1']]['elements'] = $dep->getElements($depending['table1'], $row['lang_id']);

				$arr[$row['lang_id']][$depending['caption2']]['elements'] = '';
				if($depending['no']>=3) { 
					$arr[$row['lang_id']][$depending['caption3']]['dep_id'] = $row['dep_id'];
					$arr[$row['lang_id']][$depending['caption3']]['elements'] = '';
				}
				if($depending['no']>=4) { 
					$arr[$row['lang_id']][$depending['caption4']]['dep_id'] = $row['dep_id'];
					$arr[$row['lang_id']][$depending['caption4']]['elements'] = '';
				}

				$arr[$row['lang_id']][$depending['caption1']]['type'] = 'depending';
				$arr[$row['lang_id']][$depending['caption2']]['type'] = 'depending';
				if($depending['no']>=3) $arr[$row['lang_id']][$depending['caption3']]['type'] = 'depending';
				if($depending['no']>=4) $arr[$row['lang_id']][$depending['caption4']]['type'] = 'depending';

				$arr[$row['lang_id']][$depending['caption1']]['is_numeric'] = 0;
				$arr[$row['lang_id']][$depending['caption2']]['is_numeric'] = 0;
				if($depending['no']>=3) $arr[$row['lang_id']][$depending['caption3']]['is_numeric'] = 0;
				if($depending['no']>=4) $arr[$row['lang_id']][$depending['caption4']]['is_numeric'] = 0;

				$arr[$row['lang_id']][$depending['caption1']]['all_val'] = $row['all_val'];
				$arr[$row['lang_id']][$depending['caption2']]['all_val'] = $row['all_val'];
				if($depending['no']>=3) $arr[$row['lang_id']][$depending['caption3']]['all_val'] = $row['all_val'];
				if($depending['no']>=4) $arr[$row['lang_id']][$depending['caption4']]['all_val'] = $row['all_val'];

			}
		}
		return $arr;
	}

	function getSearchFieldsDepValues($arr, $post_arr) {

		global $config_abs_path;
		require_once($config_abs_path.'/classes/fields.php');
		require_once($config_abs_path.'/classes/depending_fields.php');
		foreach($arr as $key=>$value) {
			$lang_id = $key;
			foreach($value as $row) {

			if($row['type']!="depending") continue;

			$dep = new depending_fields();
			$depending = $dep->getDependingField($row['dep_id']);

			if(isset($post_arr[$depending['caption1']]) || isset($_POST[$depending['caption1']])) {
				$fieldset=0;
				if($post_arr['category']) $fieldset = categories::getFieldset($post_arr['category']);
				if(isset($post_arr[$depending['caption1']])) $val1 = escape(rawurldecode($post_arr[$depending['caption1']]));
				if(isset($_POST[$depending['caption1']])) $val1 = escape(rawurldecode($_POST[$depending['caption1']]));
				$val1 = $dep->getIdByName($depending['table1'], $val1, " and `set_id`='$fieldset'");
				if($val1) $arr[$lang_id][$depending['caption2']]['elements'] = $dep->getElements($depending['table2'], $lang_id, $val1);
			}
			if($depending['no']>=3) { 
				if(isset($post_arr[$depending['caption2']]) || isset($_POST[$depending['caption2']])) {
					if(isset($post_arr[$depending['caption2']])) $val2 = 	escape(rawurldecode($post_arr[$depending['caption2']]));
					if(isset($_POST[$depending['caption2']])) $val2 = escape(rawurldecode($_POST[$depending['caption2']]));
					$val2 = $dep->getIdByName($depending['table2'], $val2, " and `dep`='$val1'");
					if($val2) $arr[$lang_id][$depending['caption3']]['elements'] = $dep->getElements($depending['table3'], $lang_id, $val2);
				}
			}
			if($depending['no']>=4) { 
				if(isset($post_arr[$depending['caption3']]) || isset($_POST[$depending['caption3']])) {
					if(isset($post_arr[$depending['caption3']])) $val3 = 	escape(rawurldecode($post_arr[$depending['caption3']]));
					if(isset($_POST[$depending['caption3']])) $val3 = escape(rawurldecode($_POST[$depending['caption3']]));
					$val3 = $dep->getIdByName($depending['table3'], $val3, " and `dep`='$val1'");
					if($val3) $arr[$lang_id][$depending['caption4']]['elements'] = $dep->getElements($depending['table4'], $lang_id, $val3);
				}
			}
			} // end foreach $value
		}
		return $arr;
	}


	function getAll($set='') {

		global $db;
		global $crt_lang, $is_admin;

		$where_str="";
		if($set && $this->type=="cf") $where_str .= " and ( fieldset REGEXP '\[\[:<:\]\]$set\[\[:>:\]\]'  or fieldset = 0 ) ";
		if($set==-1&& $this->type=="uf") $where_str .= " and `groups` = $set";
		if($set && $set!=-1 && $this->type=="uf") $where_str .= " and ( (`groups` REGEXP '\[\[:<:\]\]$set\[\[:>:\]\]' and `groups`!=-1)  or `groups` = 0 )";

		if(!$is_admin && $this->edit) $where_str .= " and `editable` = 1";

		$array=$db->fetchAssocList("select * from `".$this->table."` LEFT JOIN `".$this->table."_lang` on `".$this->table."`.`id` = `".$this->table."_lang`.`id` where `lang_id` = '$crt_lang'".$where_str." and `active` = 1 order by `order_no`");

		$i=0;
		$arr=array();
		$clean_fields = array("name", "top_str", "error_message", "info_message", "prefix", "postfix", "elements", "search_elements", "default_val", "extensions");

		foreach($array as $row) {

			// clean strings
			foreach($clean_fields as $key) if(isset($row[$key])) $arr[$i][$key] = cleanStr($row[$key]);

			if($this->type=='uf') $arr_gr=explode(",",trim($row['groups']));
			// if cf of uf and in the correct group
			if($this->type=='cf' || ($this->type=='uf' && (!$set || $row['groups']==0 || in_array($set, $arr_gr)))) {

				$arr[$i]=$row;
				$arr[$i]['size'] = str_replace("x", "X", $arr[$i]['size']);
				if(strstr($arr[$i]['size'],"X")) { 
					$arr_size=explode("X",$arr[$i]['size']);
					$arr[$i]['cols']=$arr_size[0];
					$arr[$i]['rows']=$arr_size[1];
				}
				if(trim($row['elements'])) {
				$arr[$i]['elements_array']=explode("|",trim($row['elements']));
				for($a=0; $a<count($arr[$i]['elements_array']);$a++) {
					$arr[$i]['elements_array'][$a]=trim($arr[$i]['elements_array'][$a]);
					if($row['type']=="checkbox_group" || $row['type']=="multiselect") {
					// used for checkboxes array elements
					$arr[$i]['extra_elements_array'][$a]['name']=$arr[$i]['elements_array'][$a];
					//$arr[$i]['extra_elements_array'][$a]['input_name']=$arr[$i]['caption']."_".fields::clean_element(characters_map($arr[$i]['elements_array'][$a]));
					$arr[$i]['extra_elements_array'][$a]['input_name']=$arr[$i]['caption']."_".$a;
					} // end if checkbox group

				}
				} // end if $row['elements']

				// numeric format
				if($arr[$i]['is_numeric'] && $arr[$i]['extensions']) { 
					$arr_ext=explode("|",$arr[$i]['extensions']);
					$arr[$i]['format_decimals']=$arr_ext[0];
					$arr[$i]['format_point']=$arr_ext[1];
					$arr[$i]['format_separator']=$arr_ext[2];
				}

				if($this->type=="cf" && trim($row['search_elements'])) {
				$arr[$i]['search_elements_array']=explode("|",trim($row['search_elements']));
				for($a=0; $a<count($arr[$i]['search_elements_array']);$a++) {
					$arr[$i]['search_elements_array'][$a]=trim($arr[$i]['search_elements_array'][$a]);
				}
				} // end if $row['search_elements']

				if($arr[$i]['type']=='depending') {

					$dep = new depending_fields();
					$depending = $dep->getDependingField($arr[$i]['dep_id']);
					$arr[$i]['depending'] = $depending;
					$arr[$i]['depending']['elements'] = $dep->getTable($arr[$i]['depending']['table1'], $set);

				}
				else $arr[$i]['depending'] = 0;

				if($this->type=='uf') {
					$arr[$i]['groups_array']=explode(",",trim($row['groups']));
					for($a=0; $a<count($arr[$i]['groups_array']);$a++) {
						$arr[$i]['groups_array'][$a]=trim($arr[$i]['groups_array'][$a]);
					}
				} // if uf
				
				if(($arr[$i]['type']=='phone' || $arr[$i]['type']=='whatsapp') && $arr[$i]['extensions']!='') {
				
					$arr[$i]['extensions'] = '"'.str_replace(',', '","',$arr[$i]['extensions']).'"';
				
				}

				$i++;
			}

		}

		return $arr;
	}

	function getFieldsArray($set, $public=1) {

		global $db;
		global $crt_lang;
		$arr=array();
		$extra="";
		$where_str='';
		if($this->type=='cf') { $where_str=" and (fieldset REGEXP '\[\[:<:\]\]$set\[\[:>:\]\]' or `fieldset` = 0)"; $extra = ", `dep_id`"; }

		else { 
			if($public) $where_str=" and `public`>=1"; else $where_str="";
			if($set!=-1)
				$where_str.=" and ( (`groups` REGEXP '\[\[:<:\]\]$set\[\[:>:\]\]' and `groups` != -1 ) or `groups` = '0')";
			else $where_str.=" and `groups` = -1 ";
		}

		$arr=$db->fetchAssocList("select `".$this->table."`.*, `".$this->table."_lang`.* from `".$this->table."` LEFT JOIN `".$this->table."_lang` on `".$this->table."`.`id` = `".$this->table."_lang`.`id` where `lang_id` = '$crt_lang' and `active` = 1 ".$where_str." order by `order_no`");

		$i=0;
		$result = array();
		$clean_fields = array("name", "elements", "search_elements");

		foreach ($arr as $f) {
			if($f['type'] == "depending") {
				$dep = new depending_fields();
				$d = $dep->getDependingField($f['dep_id']);
				for($k=1; $k<=$d['no'];$k++) {
					if($k>1) $i++;
					$result[$i]['id'] = $f['id'];
					$result[$i]['name'] = cleanHtml($d['name'.$k]);
					$result[$i]['type'] = 'depending';
					if(isset($f['public'])) $result[$i]['public'] = $f['public'];
					$result[$i]['caption'] = $d['caption'.$k];
				}

			} else { 
				$result[$i] = $f;

				// clean strings
				foreach($clean_fields as $key) if(isset($f[$key])) $result[$i][$key] = cleanHtml($f[$key]);

				if($result[$i]['elements']) $result[$i]['elements_array'] = explode("|",$result[$i]['elements']);
				if($this->type=="cf" && $result[$i]['search_elements']) $result[$i]['search_elements_array'] = explode("|",$result[$i]['search_elements']);
			}
			$i++; 
		}
		return $result;
	}


	function gMapsFields($set) {

		global $db;
		if($this->type=="cf") { 
			$sql = "select caption, extensions from `".$this->table."` where (fieldset REGEXP '\[\[:<:\]\]$set\[\[:>:\]\]' or `fieldset`= 0) and active=1 and `type` like 'google_maps'";

			$q = $db->query($sql);
			$exists = $db->numRows($q);
			if(!$exists) return 0;
			$arr = $db->fetchAssocList();
			return $arr;
		}

		// uf only
		$array = $db->fetchAssocList("select caption, extensions, groups, public from `".$this->table."` where active=1 and `type` like 'google_maps'");
		if(!count($array)) return 0;
		
		//if(!$set) return 1;
		$array_g = array();
		$k=0;
		foreach ($array as $row) {
			if($set!=-1 && trim($row['groups'])==0) { $array_g[$k] = $row; $k++; continue; }

			$arr_gr=explode(",",trim($row['groups']));
			if(in_array($set, $arr_gr))  { $array_g[$k] = $row; $k++; continue; }
		}
		if($k==0) return 0;
		return $array_g;

	}

	function getGmapsField($set) {

		global $db;
		$sql = "select caption from `".$this->table."` where (fieldset REGEXP '\[\[:<:\]\]$set\[\[:>:\]\]' or `fieldset`= 0) and active=1 and `type` like 'google_maps'";
		$q = $db->query($sql);
		$exists = $db->numRows($q);
		if(!$exists) return 0;
		$gmaps_field = $db->fetchRow();
		return $gmaps_field;
	}

	function HTMLAreaFieldExists($set) {

		global $db;
		if($this->type=="cf") { 
			$sql = "select id from `".$this->table."` where (fieldset REGEXP '\[\[:<:\]\]$set\[\[:>:\]\]' or `fieldset`= 0) and active=1 and `type` like 'htmlarea'";
			$q = $db->query($sql);
			$exists = $db->numRows($q);
			return $exists;
		}

		// uf only
		$array = $db->fetchRowList("select groups from `".$this->table."` where active=1 and `type` like 'htmlarea'");
		if(!count($array)) return 0;
		if(!$set) return 1;

		foreach ($array as $row) {
			if(trim($row)==0) return 1;
			$arr_gr=explode(",",trim($row));
			if(in_array($set, $arr_gr)) return 1;
		}
		return 0;
	}

	static function clean_element($name) {

		$el= preg_replace("/[^A-Za-z0-9]/","_",$name);
		return strtolower($el);
	}


	function fileExists($table, $caption, $file) {

		global $db;
		$exists = $db->fetchRow("select `id` from $table where `$caption` like '$file'");
		if($exists) return 1;
		return 0;

	}

	function fieldsetIncluded($fieldset, $dep_id) {

		global $db;
		$fset = $db->fetchRow("select `fieldset` from `".$this->table."` where `dep_id` = $dep_id");
		if($fset==0) return 1;
		$arr = explode(",",$fset);
		if(in_array($fieldset, $arr)) return 1;
		return 0;

	}

	function fieldsetHasPrice($fset) {

		global $db;
		$result = $db->fetchRow("select `fieldset` from `".$this->table."` where caption like 'price'");
		if($result==0) return 1;
		$arr = explode(",",$result);
		if(in_array($fset,$arr)) return 1;
		return 0;

	}

	function getMultiDepending() {

		return $this->multi_depending;

	}

	function getFieldsOfTypeShort($type, $where='') {

		global $db;
		global $crt_lang;
		$type = str_replace(",","','",$type);
		$arr = $db->fetchAssocList("select `".$this->table."`.`id`, `name`, `caption`, `dep_id`, `type` from `".$this->table."` left join `".$this->table."_lang` on `".$this->table."`.id = `".$this->table."_lang`.id where `type` in ('$type') and lang_id='$crt_lang' and `active`=1 ".$where);

		return $arr;
		
	}
	
	function getFieldsOfType($type, $exclude_type='', $additional_fields='', $where='') {

		global $db;
		global $crt_lang;
		if($type) {
			$type = str_replace(",","','",$type);
			$arr = $db->fetchAssocList("select `".$this->table."`.`id`, `name`, `caption`, `dep_id`, `type` from `".$this->table."` left join `".$this->table."_lang` on `".$this->table."`.id = `".$this->table."_lang`.id where `type` in ('$type') and lang_id='$crt_lang' and `active`=1".$where);

		} else {
			$exclude_type = str_replace(",","','",$exclude_type);
			$arr = $db->fetchAssocList("select `".$this->table."`.`id`, `name`, `caption`, `dep_id`, `type` from `".$this->table."` left join `".$this->table."_lang` on `".$this->table."`.id = `".$this->table."_lang`.id where `type` not in ('$exclude_type') and lang_id='$crt_lang' and `active`=1".$where);
		}
		$fields_array = array();		
		
		// get captions for depending fields
		$i = 0;
		if($additional_fields) {
			global $lng;
			$af = explode(",", $additional_fields);
			foreach($af as $f) {
				$fields_array[$i]['caption'] = $f; 
				$fields_array[$i]['name'] = $lng['fields'][$f];
				$i++;
			}	
		}
		foreach($arr as $a) {
				
				if($a['type']=="depending") {
					$darr = $db->fetchAssoc("select * from ".TABLE_DEPENDING_FIELDS." left join ".TABLE_DEPENDING_FIELDS."_lang on ".TABLE_DEPENDING_FIELDS.".id = ".TABLE_DEPENDING_FIELDS."_lang.id where `lang_id` = '$crt_lang' and ".TABLE_DEPENDING_FIELDS.".id='{$a['dep_id']}'");
					$aid = $a['dep_id']; 
					for($j=1; $j<=4; $j++) {

						$fields_array[$i]['name'] = $darr['name'.$j];
						$fields_array[$i]['caption'] = $darr['caption'.$j];
						$fields_array[$i]['type'] = 'depending';
						$fields_array[$i++]['id'] = $aid;
						if($darr['no']<=$j) break;

					}

				}
				else {
					$fields_array[$i++] = $a;					
				}
		} 

		return $fields_array;
	}

	function getDependingFieldsCaptions() {

		global $db;
		$arr = $db->fetchAssocList("select `".$this->table."`.`id`, `dep_id` from `".$this->table."` where `type` = 'depending' and `active`=1");
		$array = array(); $i = 0;
		foreach($arr as $d) {
			$darr = $db->fetchAssoc("select * from ".TABLE_DEPENDING_FIELDS." where id='{$d['dep_id']}'");
			$array[$i++] = $darr['caption1'];
			$array[$i++] = $darr['caption2'];
			if($darr['no']>=3 && $darr['caption3']) $array[$i++] = $darr['caption3'];
			if($darr['no']==4 && $darr['caption4']) $array[$i++] = $darr['caption4'];
		}

		return $array;
	}

	function getFieldsLang() {

		global $db;

		$array=$db->fetchAssocList("SELECT * FROM `".$this->table."` left join `".$this->table."_lang` on `".$this->table."`.id = `".$this->table."_lang`.id where active=1 order by `".$this->table."`.`id`");
		$result = array();

		$i=0;
		$last = 0;
		foreach($array as $row) {

			if($row['id'] != $last) $result[$row['id']] = array();
			$result[$row['id']][$row['lang_id']] = $row;

			$last = $row['id'];

		}

		return $result;
	}

	function getIndividualFields() {

		global $db;
		global $crt_lang;
		$fields = $db->fetchAssocList("select ".TABLE_FIELDS.".id, caption, name, type from ".TABLE_FIELDS." left join ".TABLE_FIELDS."_lang on ".TABLE_FIELDS.".id = ".TABLE_FIELDS."_lang.id where active = 1 and `type` != 'depending' and `lang_id` = '$crt_lang' order by order_no");
		$no = count($fields);

		$dep_array = $db->fetchRowList("select dep_id from ".TABLE_FIELDS." where active = 1 and `type` = 'depending' order by order_no");

		foreach($dep_array as $dep) {
			$dfields = $db->fetchAssoc("select * from ".TABLE_DEPENDING_FIELDS." left join ".TABLE_DEPENDING_FIELDS."_lang on ".TABLE_DEPENDING_FIELDS.".id = ".TABLE_DEPENDING_FIELDS."_lang.id where ".TABLE_DEPENDING_FIELDS.".id = $dep and `lang_id` = '$crt_lang'");

			$fields[$no]['type'] = "depending";
			$fields[$no]['caption'] = $dfields['caption1'];
			$fields[$no]['name'] = $dfields['name1'];
			$no++;
			$fields[$no]['type'] = "depending";
			$fields[$no]['caption'] = $dfields['caption2'];
			$fields[$no]['name'] = $dfields['name2'];
			$no++;
			if($dfields['no']>=3) {
				$no++;
				$fields[$no]['type'] = "depending";
				$fields[$no]['caption'] = $dfields['caption3'];
				$fields[$no]['name'] = $dfields['name3'];
			}
			if($dfields['no']>=4) {
				$no++;
				$fields[$no]['type'] = "depending";
				$fields[$no]['caption'] = $dfields['caption4'];
				$fields[$no]['name'] = $dfields['name4'];
			}
		}
		return $fields;
	}


	function getDepFieldsets($id) {

		global $db;
		$str = $db->fetchRow("select `fieldset` from `".$this->table."` where dep_id = $id");
		if($str==0) return 0;
		$fsets = array();
		if($str!='') {
			$fsets_arr = explode(",", $str);
			$i = 0;
			foreach($fsets_arr as $fset) {
				$fsets[$i]['id'] = $fset;
				if($fset) $fsets[$i]['name'] = fieldsets::getName($fset);
				$i++;
			}
		}
		return $fsets;

	}

	function getDependingFieldName($dep_id) {

		global $db;
		$result = $db->fetchRow("select `name` from `".$this->table."_lang` left join `".$this->table."` on `".$this->table."`.id = `".$this->table."_lang`.id where `".$this->table."`.`dep_id` = '$dep_id'");
		return $result;

	}

	function getSearchCaptions($type, $set='') {

		global $db;
		global $crt_lang;
		if($type=="quick") $where_str=" and `quick_search` = 1";
		else  $where_str=" and `advanced_search` = 1";

		if($set)  $where_str .= " and ( `fieldset` REGEXP '\[\[:<:\]\]$set\[\[:>:\]\]'  or `fieldset` = 0 ) ";
		else if($type=="advanced") $where_str .= " and `fieldset` = 0 ";

		$array=$db->fetchAssocList("select `type`, `caption`, `dep_id` from `".$this->table."` where `active` = 1".$where_str." order by `order_no`");
		$i=0;
		$arr=array();
		foreach($array as $row) {

			if($row['type']=='depending') {

				$dep = new depending_fields();
				$depending = $dep->getDependingField($row['dep_id']);
				$arr[$i]=$depending['caption1']; $i++;
				$arr[$i]=$depending['caption2']; $i++;
				if($depending['caption3']) { $arr[$i]=$depending['caption3']; $i++; }
				if($depending['caption4']) { $arr[$i]=$depending['caption4']; $i++; }
				if($depending['caption5']) { $arr[$i]=$depending['caption5']; $i++; }

			}
			else {
				$arr[$i]=$row['caption'];
				$i++;
			}
		}
		return $arr;
	}

	function getField($caption, $type='cf') {

		global $db;
		global $crt_lang;

		$arr=$db->fetchAssoc ("select * from `".$this->table."` LEFT JOIN `".$this->table."_lang` on `".$this->table."`.`id` = `".$this->table."_lang`.`id` where `lang_id` = '$crt_lang' and `caption` like '$caption'");
	
		$clean_fields = array("name", "top_str", "error_message", "info_message", "prefix", "postfix", "elements", "default_val", "extensions");

		// clean strings
		foreach($clean_fields as $key) { 
			if(isset($arr[$key]))
				$arr[$key] = cleanHtml($arr[$key]);
		}

		$arr['size'] = str_replace("x", "X", $arr['size']);
		if(strstr($arr['size'],"X")) { 
			$arr_size=explode("X",$arr['size']);
			$arr['cols']=$arr_size[0];
			$arr['rows']=$arr_size[1];
		}

		if(isset($arr['elements']) && trim($arr['elements'])) {
			$arr['elements_array']=explode("|",trim($arr['elements']));
			for($a=0; $a<count($arr['elements_array']);$a++) {
				$arr['elements_array'][$a]=trim($arr['elements_array'][$a]);
			}
		} // end if $row['elements']

		// numeric format
		if($arr['is_numeric'] && $arr['extensions']) { 
			$arr_ext=explode("|",$arr['extensions']);
			$arr['format_decimals']=$arr_ext[0];
			$arr['format_point']=$arr_ext[1];
			$arr['format_separator']=$arr_ext[2];
		}
			
		if( $type=="cf" && trim($arr['search_elements'])) {
			$arr['search_elements_array']=explode("|",trim($arr['search_elements']));
			for($a=0; $a<count($arr['search_elements_array']);$a++) {
				$arr['search_elements_array'][$a]=trim($arr['search_elements_array'][$a]);
			}
		} // end if $row['search_elements']

		if($arr['type']=='depending') {
		
			$dep = new depending_fields();
			$depending = $dep->getDependingField($arr['dep_id']);
			$arr['depending'] = $depending;
			$arr['depending']['elements'] = $dep->getTable($arr['depending']['table1']);

		}

		if($arr['type']=="date") {
			$arr['date_format_standard'] = convertDateFormat($arr['date_format']);
		}

		return $arr;
	}


	function getFieldsOfType2($type, $exclude_type='', $additional_fields='', $where='') {

		global $db;
		global $crt_lang;
		if($type) {
			$type = str_replace(",","','",$type);
			$arr = $db->fetchAssocList("select `".$this->table."`.`id`, `name`, `caption`, `dep_id`, `type` from `".$this->table."` left join `".$this->table."_lang` on `".$this->table."`.id = `".$this->table."_lang`.id where `type` in ('$type') and lang_id='$crt_lang' and `active`=1".$where);

		} else {
			$exclude_type = str_replace(",","','",$exclude_type);
			$arr = $db->fetchAssocList("select `".$this->table."`.`id`, `name`, `caption`, `dep_id`, `type` from `".$this->table."` left join `".$this->table."_lang` on `".$this->table."`.id = `".$this->table."_lang`.id where `type` not in ('$exclude_type') and lang_id='$crt_lang' and `active`=1".$where);
		}
		$fields_array = array();

		// get captions for depending fields
		$i = 0;
		if($additional_fields) {
			global $lng;
			$af = explode(",", $additional_fields);
			foreach($af as $f) {
				$fields_array[$i]['caption'] = $f; 
				$fields_array[$i]['name'] = $lng['fields'][$f];
				$i++;
			}	
		}
		foreach($arr as $a) {

			$fields_array[$i++] = $a;

		} 

		return $fields_array;
	}

	function getUserSpecialCF($group) {

		global $settings;
		$special_cf = array("contact_name" => $settings['contact_name_field'], "phone" =>array(), "whatsapp" => array(), "url" => array(), "logo" => array(), "youtube" => "", "google_maps" => "", "twitter" => array());
		$phone_idx = 0; $whatsapp_idx=0; $twitter_idx=0; $url_idx=0; $email_idx=0;
		if(!$group) $group=-1;
		$array_fields=$this->getFieldsArray($group);

		foreach($array_fields as $field) {
		
			if($field['public']==0) continue;
			
			// email
			if($field['type']=="user_email")
				$special_cf['email'][$email_idx++] = array ("caption" => $field['caption'], "public" => $field['public']);

			// phone
			if($field['type']=="phone")
				$special_cf['phone'][$phone_idx++] = array ("caption" => $field['caption'], "public" => $field['public']);
			
			// whatsapp
			if($field['type']=="whatsapp")
				$special_cf['whatsapp'][$whatsapp_idx++] = array ("caption" => $field['caption'], "public" => $field['public']);

			// twitter
			if($field['type']=="twitter")
				$special_cf['twitter'][$twitter_idx++] = array ("caption" => $field['caption'], "public" => $field['public']);
			
			// url
			if($field['type']=="url")
				$special_cf['url'][$url_idx++] = array ("caption" => $field['caption'], "public" => $field['public']);

			// logo
			if($field['type']=="logo" && !$special_cf['logo']) {
				$special_cf['logo'] = $field['caption'];
			}
			
			// youtube
			if($field['type']=="youtube" && !$special_cf['youtube']) {
				$special_cf['youtube'] = $field['caption'];
			}

			// map
			if($field['type']=="google_maps" && !$special_cf['google_maps']) {
				$special_cf['google_maps'] = $field['caption'];
			}

		}

		return $special_cf;
		
	}

	function getListingSpecialCF($fset) {

		global $settings;
		$special_cf = array("phone" =>array(), "whatsapp" => array(), "twitter" => array(), "logo" => array());
		$phone_idx = 0; $whatsapp_idx=0; $twitter_idx=0; $logo_idx=0;
		if(!$fset) $fset=0;
		$array_fields=$this->getFieldsArray($fset);
		
		foreach($array_fields as $field) {
		
			// phone
			if($field['type']=="phone")
				$special_cf['phone'][$phone_idx++] = array ("caption" => $field['caption']);
			
			// whatsapp
			if($field['type']=="whatsapp")
				$special_cf['whatsapp'][$whatsapp_idx++] = array ("caption" => $field['caption']);

			// twitter
			if($field['type']=="twitter")
				$special_cf['twitter'][$twitter_idx++] = array ("caption" => $field['caption']);

			// logo
			if($field['type']=="logo")
				$special_cf['logo'][$logo_idx++] = array ("caption" => $field['caption']);

		}
	
		return $special_cf;
		
	}
	
}
?>
