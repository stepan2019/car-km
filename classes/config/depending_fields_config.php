<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class depending_fields_config {

	var $error;
	var $tmp;
	var $last;
	var $not_logged_in;

	public function __construct()
	{
	
		$this->error=''; $this->tmp='';
		$this->not_logged_in = 0;

	}
	function delete($id, $type) {

		global $db;
		global $config_demo;
		if($config_demo==1) return;
		global $config_table_prefix;

		$row=$db->fetchAssoc('select * from '.TABLE_DEPENDING_FIELDS.' where id="'.$id.'"');
		$table1=$config_table_prefix.$row['table1'];
		$table2=$config_table_prefix.$row['table2'];
		$table3=$config_table_prefix.$row['table3'];
		$table4=$config_table_prefix.$row['table4'];
		$table5=$config_table_prefix.$row['table5'];
		$caption1=$row['caption1'];
		$caption2=$row['caption2'];
		$caption3=$row['caption3'];
		$caption4=$row['caption4'];
		$caption5=$row['caption5'];

		if($type=='cf') $tab = TABLE_ADS; else $tab = TABLE_USERS;
		$db->query('alter table '.$tab.' drop `'.$caption1.'`');
		if($row['no']>=2 && $caption2) $db->query('alter table '.$tab.' drop `'.$caption2.'`');
		if($row['no']>=3 && $caption3) $db->query('alter table '.$tab.' drop `'.$caption3.'`');
		if($row['no']>=4 && $caption4) $db->query('alter table '.$tab.' drop `'.$caption4.'`');
		if($row['no']>=5 && $caption5) $db->query('alter table '.$tab.' drop `'.$caption5.'`');

		$db->query('drop table '.$table1);
		if($row['no']>=2 && $table2) $db->query('drop table '.$table2);
		if($row['no']>=3 && $table3) $db->query('drop table '.$table3);
		if($row['no']>=4 && $table4) $db->query('drop table '.$table4);
		if($row['no']>=5 && $table5) $db->query('drop table '.$table5);

		$res_del=$db->query('delete from '.TABLE_DEPENDING_FIELDS.' where id="'.$id.'";');
		$res_del=$db->query('delete from '.TABLE_DEPENDING_FIELDS.'_lang where id="'.$id.'";');

		if($type!="cf") return 1;

		// check location_fields, search_in_fields, search_location_fields
		// remove this field if found in one of these fields
		fields_config::clean_ads_settings_field('location_fields', $caption1);
		fields_config::clean_ads_settings_field('location_fields', $caption2);

		fields_config::clean_ads_settings_field('search_in_fields', $caption1);
		fields_config::clean_ads_settings_field('search_in_fields', $caption2);

		fields_config::clean_ads_settings_field('search_location_fields', $caption1);
		fields_config::clean_ads_settings_field('search_location_fields', $caption2);

		if($row['no']>=3 && $caption3) {
			fields_config::clean_ads_settings_field('location_fields', $caption3);
			fields_config::clean_ads_settings_field('search_in_fields', $caption3);
			fields_config::clean_ads_settings_field('search_location_fields', $caption3);
		}

		if($row['no']>=4 && $caption4) {
			fields_config::clean_ads_settings_field('location_fields', $caption4);
			fields_config::clean_ads_settings_field('search_in_fields', $caption4);
			fields_config::clean_ads_settings_field('search_location_fields', $caption4);
		}

		if($row['no']>=5 && $caption5) {
			fields_config::clean_ads_settings_field('location_fields', $caption5);
			fields_config::clean_ads_settings_field('search_in_fields', $caption5);
			fields_config::clean_ads_settings_field('search_location_fields', $caption5);
		}
		
		$this->clearDepFieldsCache();
		return 1;
		
	}

	function deleteDepValue($id, $val, $no) {

		global $db;
		global $config_demo;
		global $config_table_prefix;
		if($config_demo==1) return;

		$array = $db->fetchAssoc("select * from ".TABLE_DEPENDING_FIELDS." where id=$id");
		for($i=$no; $i<=5; $i++) {

			if($array['no']<$i) continue;
			$res_del=$db->query('delete from '.$config_table_prefix.$array['table'.$i].' where id="'.$val.'"');

			if($array['no']<($i+1)) continue;

			if($i<4 && $array['no']>($i+3)) {
			$rec = $db->fetchRowList('select id from '.$config_table_prefix.$array['table'.($i+1)].' where dep="'.$val.'"');

			foreach ($rec as $v) {

				$this->deleteDepValue($id, $v, $array['no']+1);

			} 

			} // end if $i<3

			$res_del=$db->query('delete from '.$config_table_prefix.$array['table'.($i+1)].' where dep="'.$val.'"');

		}
		$this->clearDepFieldsCache();
		return 1;
	}

	function getDependingFieldLang($id='') {

		global $db;
		if(!$id) $id=$this->id;

		$row=$db->fetchAssoc("select * from ".TABLE_DEPENDING_FIELDS." where id=".$id."");
		$row['dep_required1'] = $row['required1'];
		$row['dep_required2'] = $row['required2'];
		$row['dep_required3'] = $row['required3'];
		$row['dep_required4'] = $row['required4'];
		$row['dep_required5'] = $row['required5'];

		$array_lang=$db->fetchAssocList("select * from ".TABLE_DEPENDING_FIELDS."_lang where id=".$id."");
		foreach($array_lang as $f_lang) {

			$lang_id = $f_lang['lang_id'];
			foreach ($f_lang as $key=>$value) {
				if($key=='id' || $key=='lang_id') continue;
				$row["dep_".$key][$lang_id] = cleanStr($value);
			}
		}
		return $row;

	}

	function make_caption($name, $type, $exclude = array()) {

		$f = new fields_config($type);
		$f->setNologin($this->not_logged_in);
		$caption = $f->make_caption($name, "depending", $exclude);
		return $caption;

	}

	function valid_name($str) {

		if(preg_match("/^[A-Z][[:space:]A-Z0-9._-]*[A-Z0-9]$/i", $str))	return 1;
		else return 0;

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
		$array_fields = array("dep_name1", "dep_name2", "dep_name3", "dep_name4", "dep_name5");

		$depending_no = escape($_POST['depending_no']);

		$i = 1;
		foreach ($array_fields as $field) {
			if($i>$depending_no) continue;
			foreach ($languages as $lang) {

				$lang_id = $lang['id'];
				if(!$_POST[$field."_".$lang_id]) { $this->addError($lng['custom_fields']['errors'][$field.'_name_missing'].'<br />');
				break;
				}
			} // foreach languages
			$i++;
		}// foreach fields

		foreach ($languages as $lang) {

			$lang_id = $lang['id'];
			if( isset($_POST['dep_required1']) && $_POST['dep_required1']=="on" && !$_POST['dep_error_message1_'.$lang_id]) { $this->addError($lng['custom_fields']['errors']['dep_field1_error_missing'].'<br />');
			break;
			}
		}

		if($depending_no>=2) {

		foreach ($languages as $lang) {
			$lang_id = $lang['id'];
			if(isset($_POST['dep_required2']) && $_POST['dep_required2']=="on" && !$_POST['dep_error_message2_'.$lang_id]) { $this->addError($lng['custom_fields']['errors']['dep_field2_error_missing'].'<br />');
			break;
			}

		}

		} // end >=2

		if($depending_no>=3) {

		foreach ($languages as $lang) {
			$lang_id = $lang['id'];
			if(isset($_POST['dep_required3']) && $_POST['dep_required3']=="on" && !$_POST['dep_error_message3_'.$lang_id]) { $this->addError($lng['custom_fields']['errors']['dep_field3_error_missing'].'<br />');
			break;
			}

		}
		} // end >=3

		if($depending_no>=4) {

		foreach ($languages as $lang) {
			$lang_id = $lang['id'];
			if(isset($_POST['dep_required4']) && $_POST['dep_required4']=="on" && !$_POST['dep_error_message4_'.$lang_id]) { $this->addError($lng['custom_fields']['errors']['dep_field4_error_missing'].'<br />');
			break;
			}

		}
		} // end >=4

		if($depending_no>=5) {

		foreach ($languages as $lang) {
			$lang_id = $lang['id'];
			if(isset($_POST['dep_required5']) && $_POST['dep_required5']=="on" && !$_POST['dep_error_message5_'.$lang_id]) { $this->addError($lng['custom_fields']['errors']['dep_field5_error_missing'].'<br />');
			break;
			}

		}
		} // end >=5

		// keep tmp values even if no error occures
		$array_fields = array("dep_name1", "dep_name2", "dep_name3", "dep_name4", "dep_name5", "dep_top_str1", "dep_top_str2", "dep_top_str3", "dep_top_str4", "dep_top_str5", "dep_error_message1", "dep_error_message2", "dep_error_message3", "dep_error_message4", "dep_error_message5");
		foreach($array_fields as $field) {

			foreach ($languages as $lang) {
				$lang_id = $lang['id'];
				if(isset($_POST[$field.'_'.$lang_id])) $this->tmp[$field][$lang_id]=escape($_POST[$field.'_'.$lang_id]);
			}

		}

		$array_checkboxes = array("dep_required1", "dep_required2", "dep_required3", "dep_required4", "dep_required5");
		foreach($array_checkboxes as $field) {

			if(isset($_POST[$field]) && $_POST[$field]=='on') $this->tmp[$field] = 1; else $this->tmp[$field] = 0;

		}

		$this->tmp['no'] = $depending_no;

		return 1;
	}

	function add($type) {
	
		global $db;
		$this->clean=array();
		$this->check_form();
		if($this->getError()!='') return 0;
		global $config_table_prefix;
		global $languages;
		global $crt_lang;

		$depending_no = escape($_POST['depending_no']);

		$array_fields = array ("dep_name1", "dep_name2", "dep_name3", "dep_name4", "dep_name5", "dep_top_str1", "dep_top_str2", "dep_top_str3", "dep_top_str4", "dep_top_str5", "dep_error_message1", "dep_error_message2", "dep_error_message3", "dep_error_message4", "dep_error_message5" );

		// for all languages
		foreach ($languages as $lang) {
			$lang_id = $lang['id'];

			foreach($array_fields as $field) {
				if(isset($_POST[$field."_".$lang_id])) $this->clean[$field][$lang_id] = escape($_POST[$field."_".$lang_id]); else $this->clean[$field][$lang_id] = '';
			}

		}

		// make caption1		
		$this->clean['caption1'] = $this->make_caption($this->clean['dep_name1'][$crt_lang], $type);

		// exclude array for caption2	
		$exclude_array = array($this->clean['caption1']);

		// make caption2
		$this->clean['caption2'] = $this->make_caption($this->clean['dep_name2'][$crt_lang], $type, $exclude_array);

		if($depending_no>=3) { 

			// exclude array for caption3	
			$exclude_array = array($this->clean['caption1'], $this->clean['caption2']);

			// make caption3
			$this->clean['caption3'] = $this->make_caption($this->clean['dep_name3'][$crt_lang], $type, $exclude_array);
		}
		else $this->clean['caption3'] = '';

		if($depending_no>=4) { 
			// exclude array for caption4	
			$exclude_array = array($this->clean['caption1'], $this->clean['caption2'], $this->clean['caption3']);

			// make caption4
			$this->clean['caption4'] = $this->make_caption($this->clean['dep_name4'][$crt_lang], $type, $exclude_array);
		}
		else $this->clean['caption4']='';

		if($depending_no>=5) { 
			// exclude array for caption5	
			$exclude_array = array($this->clean['caption1'], $this->clean['caption2'], $this->clean['caption3'], $this->clean['caption4']);

			// make caption5
			$this->clean['caption5'] = $this->make_caption($this->clean['dep_name5'][$crt_lang], $type, $exclude_array);
		}
		else $this->clean['caption5']='';

		$this->clean['table1'] = $this->clean['caption1'];
		if($depending_no>=2) $this->clean['table2'] = $this->clean['caption2'];
		if($depending_no>=3) $this->clean['table3'] = $this->clean['caption3']; else $this->clean['table3']='';
		if($depending_no>=4) $this->clean['table4'] = $this->clean['caption4']; else $this->clean['table4']='';
		if($depending_no>=5) $this->clean['table5'] = $this->clean['caption5']; else $this->clean['table5']='';

		$this->clean['dep_required1']=checkbox_value('dep_required1');
		if($depending_no>=2) $this->clean['dep_required2']=checkbox_value('dep_required2');
		if($depending_no>=3) $this->clean['dep_required3']=checkbox_value('dep_required3'); else $this->clean['dep_required3']=0;
		if($depending_no>=4) $this->clean['dep_required4']=checkbox_value('dep_required4'); else $this->clean['dep_required4']=0;
		if($depending_no>=5) $this->clean['dep_required5']=checkbox_value('dep_required5'); else $this->clean['dep_required5']=0;

		$res=$db->query('insert into '.TABLE_DEPENDING_FIELDS.' (`no`, `caption1`, `caption2`, `caption3`, `caption4`, `caption5`, `table1`, `table2`, `table3`, `table4`, `table5`, `required1`, `required2`, `required3`, `required4`, `required5`) values ("'.$depending_no.'", "'.$this->clean['caption1'].'", "'.$this->clean['caption2'].'", "'.$this->clean['caption3'].'", "'.$this->clean['caption4'].'", "'.$this->clean['caption5'].'", "'.$this->clean['table1'].'", "'.$this->clean['table2'].'", "'.$this->clean['table3'].'", "'.$this->clean['table4'].'", "'.$this->clean['table5'].'", "'.$this->clean['dep_required1'].'", "'.$this->clean['dep_required2'].'", "'.$this->clean['dep_required3'].'", "'.$this->clean['dep_required4'].'", "'.$this->clean['dep_required5'].'");');

		$last=$db->insertId();

		foreach ($languages as $lang) {
			$lang_id = $lang['id'];
			$res=$db->query('insert into '.TABLE_DEPENDING_FIELDS.'_lang values ("'.$last.'", "'.$lang_id.'",  "'.$this->clean['dep_name1'][$lang_id].'", "'.$this->clean['dep_name2'][$lang_id].'",
			"'.$this->clean['dep_name3'][$lang_id].'", "'.$this->clean['dep_name4'][$lang_id].'", "'.$this->clean['dep_name5'][$lang_id].'", 
 			"'.$this->clean['dep_top_str1'][$lang_id].'", "'.$this->clean['dep_top_str2'][$lang_id].'",
			"'.$this->clean['dep_top_str3'][$lang_id].'", "'.$this->clean['dep_top_str4'][$lang_id].'", "'.$this->clean['dep_top_str5'][$lang_id].'",
 			"'.$this->clean['dep_error_message1'][$lang_id].'", "'.$this->clean['dep_error_message2'][$lang_id].'", 
			"'.$this->clean['dep_error_message3'][$lang_id].'", "'.$this->clean['dep_error_message4'][$lang_id].'", "'.$this->clean['dep_error_message5'][$lang_id].'");');
		}

		global $config_table_prefix;
		$res1=$db->query("create table ".$config_table_prefix.$this->clean['table1']." (`id` int(5) NOT NULL auto_increment, `name` varchar(64), `lang_id` varchar(20) default 'eng', `set_id` int(3) default 0, KEY `id` (`id`), KEY `idx_lang_id` (`lang_id`), KEY `idx_set_id` (`set_id`) )");

		if($depending_no>=2) $res_alter=$db->query("create table ".$config_table_prefix.$this->clean['table2']." (`id` int(5) NOT NULL auto_increment, `dep` int(2), `name` varchar(64), `lang_id` varchar(20) default 'eng', KEY `id` (`id`), KEY `idx_lang_id` (`lang_id`), KEY `idx_dep` (`dep`) )");

		if($depending_no>=3) $res_alter=$db->query("create table ".$config_table_prefix.$this->clean['table3']." (`id` int(5) NOT NULL auto_increment, `dep` int(2), `name` varchar(64), `lang_id` varchar(20) default 'eng', KEY `id` (`id`), KEY `idx_lang_id` (`lang_id`), KEY `idx_dep` (`dep`) )");

		if($depending_no>=4) $res_alter=$db->query("create table ".$config_table_prefix.$this->clean['table4']." (`id` int(5) NOT NULL auto_increment, `dep` int(2), `name` varchar(64), `lang_id` varchar(20) default 'eng', KEY `id` (`id`), KEY `idx_lang_id` (`lang_id`), KEY `idx_dep` (`dep`) )");

		if($depending_no>=5) $res_alter=$db->query("create table ".$config_table_prefix.$this->clean['table5']." (`id` int(5) NOT NULL auto_increment, `dep` int(2), `name` varchar(64), `lang_id` varchar(20) default 'eng', KEY `id` (`id`), KEY `idx_lang_id` (`lang_id`), KEY `idx_dep` (`dep`) )");

		if($type=='cf') 
			$tab = TABLE_ADS; 
		else { 
			if($this->not_logged_in) 
				$tab = TABLE_ADS_EXTENSIONS;
			else 
				$tab = TABLE_USERS;
		}
		$res_alter1=$db->query("alter table ".$tab." add `".$this->clean['caption1']."` varchar(100) NULL");
		if($type=="cf") $db->query("ALTER TABLE `".$config_table_prefix."ads` ADD INDEX ( `".$this->clean['caption1']."` )");

		if($depending_no>=2) { 
			$res_alter2=$db->query("alter table ".$tab." add `".$this->clean['caption2']."` varchar(100) NULL");
			if($type=="cf") $db->query("ALTER TABLE `".$config_table_prefix."ads` ADD INDEX ( `".$this->clean['caption2']."` )");
		}
		if($depending_no>=3) { 
			$res_alter1=$db->query("alter table ".$tab." add `".$this->clean['caption3']."` varchar(100) NULL");
			if($type=="cf") $db->query("ALTER TABLE `".$config_table_prefix."ads` ADD INDEX ( `".$this->clean['caption3']."` )");
		}
		if($depending_no>=4) { 
			$res_alter2=$db->query("alter table ".$tab." add `".$this->clean['caption4']."` varchar(100) NULL");
			if($type=="cf") $db->query("ALTER TABLE `".$config_table_prefix."ads` ADD INDEX ( `".$this->clean['caption4']."` )");
		}

		if($depending_no>=5) { 
			$res_alter2=$db->query("alter table ".$tab." add `".$this->clean['caption5']."` varchar(100) NULL");
			if($type=="cf") $db->query("ALTER TABLE `".$config_table_prefix."ads` ADD INDEX ( `".$this->clean['caption5']."` )");
		}

		$this->clearDepFieldsCache();
		
		return $last;

	}

	function edit($id, $type) {
	
		global $db;
		$this->clean=array();
		$this->check_form($id);
		if($this->getError()!='') return 0;
		global $languages;
		global $crt_lang;
		global $config_table_prefix;

		$old_data = $db->fetchAssoc("select * from ".TABLE_DEPENDING_FIELDS." where id=$id");
		$depending_no = escape($_POST['depending_no']);

		$array_fields = array ("dep_name1", "dep_name2", "dep_name3", "dep_name4", "dep_name5", "dep_top_str1", "dep_top_str2", "dep_top_str3", "dep_top_str4", "dep_top_str5", "dep_error_message1", "dep_error_message2", "dep_error_message3", "dep_error_message4", "dep_error_message5" );
		foreach($languages as $lang) {
			$lang_id = $lang['id'];
			foreach($array_fields as $field) {
				if(isset($_POST[$field.'_'.$lang_id])) $this->clean[$field][$lang_id] = escape($_POST[$field.'_'.$lang_id]);
			}
		}
	
		$this->clean['dep_required1']=checkbox_value('dep_required1');
		$this->clean['dep_required2']=checkbox_value('dep_required2');
		$this->clean['dep_required3']=checkbox_value('dep_required3');
		$this->clean['dep_required4']=checkbox_value('dep_required4');
		$this->clean['dep_required5']=checkbox_value('dep_required5');

		foreach ($languages as $lang) {

			$lang_id = $lang['id'];
			$res_lang=$db->query("update ".TABLE_DEPENDING_FIELDS."_lang set `name1` = '".$this->clean['dep_name1'][$lang_id]."', `name2` = '".$this->clean['dep_name2'][$lang_id]."', `name3` = '".$this->clean['dep_name3'][$lang_id]."', `name4` = '".$this->clean['dep_name4'][$lang_id]."', `name5` = '".$this->clean['dep_name5'][$lang_id]."', `top_str1` = '".$this->clean['dep_top_str1'][$lang_id]."', `top_str2` = '".$this->clean['dep_top_str2'][$lang_id]."', `top_str3` = '".$this->clean['dep_top_str3'][$lang_id]."', `top_str4` = '".$this->clean['dep_top_str4'][$lang_id]."', `top_str5` = '".$this->clean['dep_top_str5'][$lang_id]."', `error_message1`='".$this->clean['dep_error_message1'][$lang_id]."', `error_message2`='".$this->clean['dep_error_message2'][$lang_id]."', `error_message3`='".$this->clean['dep_error_message3'][$lang_id]."', `error_message4`='".$this->clean['dep_error_message4'][$lang_id]."', `error_message5`='".$this->clean['dep_error_message5'][$lang_id]."' where `id`='$id' and lang_id='$lang_id';");

		}

		$res=$db->query('update '.TABLE_DEPENDING_FIELDS.' set `no` = "'.$depending_no.'", `required1`="'.$this->clean['dep_required1'].'", `required2`="'.$this->clean['dep_required2'].'", `required3`="'.$this->clean['dep_required3'].'", `required4`="'.$this->clean['dep_required4'].'", `required5`="'.$this->clean['dep_required5'].'" where `id`="'.$id.'";');

		// if the number is changed add more tables
		if($old_data['no']!=$depending_no) {

			if($type=='cf') 
				$tab = TABLE_ADS; 
			else {
				if($this->not_logged_in) 
					$tab = TABLE_ADS_EXTENSION;
				else 
					$tab = TABLE_USERS;
			}
			// less tables
			if($old_data['no']<$depending_no) {
				for($i=$old_data['no']+1; $i<=$depending_no; $i++) {

					$caption = $this->make_caption($this->clean['dep_name'.$i][$crt_lang], $type);

					$db->query("update ".TABLE_DEPENDING_FIELDS." set `caption$i` = '$caption', `table$i` = '$caption' where `id` = $id");

					$db->query("create table ".$config_table_prefix.$caption." (`id` int(5) NOT NULL auto_increment, `dep` int(2), `name` varchar(64), `lang_id` varchar(20) default 'eng', KEY `id` (`id`) )");

					$db->query("alter table ".$tab." add `".$caption."` varchar(100) NULL");

				}
			} else { // need to remove tables and fields

				for($i=$depending_no+1; $i<=$depending_no; $i++) {

					$caption = $old_data['caption'.$i];

					$db->query("drop table ".$config_table_prefix.$caption);

					$db->query("alter table ".$tab." drop `".$caption."`");

				}

			}

		}

		$this->clearDepFieldsCache();

		return 1;

	}

	function addBulkDep1($str, $table, $set) {

		global $db;
		global $config_demo;
		if($config_demo==1) return;
		global $config_table_prefix;
		global $languages;
		global $crt_lang;

		$word_array = explode('|', $str);
		$count_word = count($word_array);
		for($i=0;$i<$count_word;$i++)
		{
			$w=trim($word_array[$i]);
			if($w!='')
			{

				$res=$db->query("select * from ".$config_table_prefix.$table." where name like '$w' and `set_id` = $set;");
				$exists=$db->numRows($res);
				if(!$exists) { 
					$res1=$db->query("insert into ".$config_table_prefix.$table." set `name` = '$w', `set_id` = $set, `lang_id`='$crt_lang';");
					$id=$db->insertId();
					foreach($languages as $lang) {
						$lang_id = $lang['id'];
						if($lang_id==$crt_lang) continue;
						$db->query("insert into ".$config_table_prefix.$table." set `id`='$id', `name` = '$w', `set_id` = '$set', `lang_id` = '$lang_id';");
					}
				}
			}
		}

		$this->clearDepFieldsCache();

	}

	function addBulkDep2($str, $table, $dep) {

		global $db;
		global $config_demo;
		if($config_demo==1) return;
		global $config_table_prefix;
		global $languages;
		global $crt_lang;

		$word_array = explode('|', $str);
		$count_word = count($word_array);
		for($i=0;$i<$count_word;$i++)
		{
			$w=trim($word_array[$i]);
			if($w!='')
			{
				$res=$db->query("select * from ".$config_table_prefix.$table." where name like '$w' and dep=$dep;");
				$exists=$db->numRows($res);
				if(!$exists) { 
					$res1=$db->query("insert into ".$config_table_prefix.$table." set `name` = '$w', `dep` = $dep, `lang_id`='$crt_lang';");
					$id=$db->insertId();
					foreach($languages as $lang) {
						$lang_id = $lang['id'];
						if($lang_id==$crt_lang) continue;
						$db->query("insert into ".$config_table_prefix.$table." set `id`='$id', `name` = '$w', `dep` = '$dep', `lang_id` = '$lang_id';");
					}
				}
			}
		}

		$this->clearDepFieldsCache();

	}

	function updateDependingLang($id, $lang_id, $table, $val) {

		global $db;
		global $config_table_prefix;
		if(!$id || !$lang_id) return;
		$db->query("update ".$config_table_prefix.$table." set `name`='$val' where `id` = $id and `lang_id`='$lang_id'");

		$this->clearDepFieldsCache();

		return 1;

	}

	function addLanguage($lang_id, $default) {

		global $db;
		global $config_table_prefix;
		$array_cpy = $db->fetchAssocList("select * from ".TABLE_DEPENDING_FIELDS."_lang where `lang_id` = '$default'");

		foreach ($array_cpy as $res) {

			// if already exists a row for this language exit
			$exists = $db->fetchRow("select count(*) from ".TABLE_DEPENDING_FIELDS."_lang where `lang_id` = '$lang_id' and `id` = ".$res['id']);
			if($exists) continue;

			$sql = "insert into ".TABLE_DEPENDING_FIELDS."_lang set `lang_id` = '$lang_id' ";

			foreach ($res as $key=>$value) {

				if($key!='lang_id') $sql.=", `$key` = '".addslashes($value)."'";

			}
			$res = $db->query($sql);

		}

		$array = $db->fetchAssocList("select * from ".TABLE_DEPENDING_FIELDS);
		foreach($array as $arr) {

			for($i=1; $i<=5;$i++) {

				$table = $arr['table'.$i];
				if($table) {
					$array_tab = $db->fetchAssocList("select * from ".$config_table_prefix.$table." where `lang_id` = '$default'");
					foreach($array_tab as $at) {
						$exists = $db->fetchRow("select count(*) from ".$config_table_prefix."$table where id = '".$at['id']."' and `lang_id`='$lang_id'");
						if($exists) continue;

						$sql = "insert into ".$config_table_prefix.$table." set `lang_id` = '$lang_id'";
						foreach($at as $key=>$value) {
							if($key!='lang_id') $sql.=", `$key` = '".addslashes($value)."'";
						}
						$res = $db->query($sql);
					}
				}
			}
		}

		$this->clearDepFieldsCache();

		return 1;

	}

	function setNologin($n) {

		$this->not_logged_in = $n;

	}

	function getNologin($n) {

		return $this->not_logged_in;

	}

	function clearDepFieldsCache() {

		// clear cache
		$lc_cache = new cache();
		$lc_cache->clearCache("base_interface");
		$lc_cache->clearCache("base_refine_fields");
		$lc_cache->clearCache("base_refine_fields_list");
		$lc_cache->clearCache("base_search_fields");
		$lc_cache->clearCache("base_listing_fields");
		$lc_cache->clearCache("base_user_fields");
		

	}

}

?>
