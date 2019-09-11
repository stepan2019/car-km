<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

require_once "../../config.php";
global $config_abs_path;
require_once $config_abs_path."/include/include.php";

global $appearance_settings;
header('Content-type: text/html; charset='.$appearance_settings['charset']);

$arr_action= array("package", "categ", "banners", "listing", "pkg", "cf", "uf", "dep", "group", "depending", "positions", "fieldset", "rule", "user_ac", "tpl");
$arr_id= array("package", "fieldset", "dep", "group");

if(isset($_GET['type']))  {
	if(!in_array($_GET['type'], $arr_action)) exit(0);
	$type=$_GET['type'];
	
}
else exit(0);

if(isset($_GET['id'])) { $id=escape($_GET['id']); if(!is_numeric($id)) exit(0); }
else if(in_array($_GET['type'], $arr_id)) exit(0);

switch ($type) {

	case 'categ':

		require_once $config_abs_path."/classes/categories.php";
		$s_array = common::getCachedObject("base_short_categories_fset");
		for($i=0;$i<count($s_array);$i++) {
			if ($i) echo ',';
			echo $s_array[$i]['id'];
		}

		break;

	case 'banners':

		require_once $config_abs_path."/classes/config/banners_config.php";

		$bc=new banners_config();
		$s_array=$bc->getList();
		for($i=0;$i<count($s_array);$i++) {
			if ($i) echo ',';
			echo $s_array[$i]['id'];
		}

		break;

	case 'positions':

		require_once $config_abs_path."/classes/config/banners_config.php";

		if(isset($_GET['pos'])) $pos = escape($_GET['pos']); else exit(0);
		$bc=new banners_config();
		$ss=$bc->getSpecificSection($pos);
		echo $ss;

		break;

	case 'pkg':

		require_once $config_abs_path."/classes/packages.php";
		$s_array=common::getCachedObject("base_short_plans");
		for($i=0;$i<count($s_array);$i++) {
			if ($i>0) echo ',';
			echo $s_array[$i]['id'];
		}

		break;

	case 'cf':

		require_once $config_abs_path."/classes/fields.php";
		require_once $config_abs_path."/classes/depending_fields.php";
		$s_array=common::getCachedObject("base_short_listing_fields");
		for($i=0;$i<count($s_array);$i++) {
			if ($i>0) echo ',';
			echo $s_array[$i]['id'];
		}

		break;

	case 'uf':

		require_once $config_abs_path."/classes/fields.php";
		require_once $config_abs_path."/classes/depending_fields.php";

		$s_array=common::getCachedObject("base_short_user_fields");
		for($i=0;$i<count($s_array);$i++) {
			if ($i>0) echo ',';
			echo $s_array[$i]['id'];
		}

		break;

	case 'fieldset':

		require_once $config_abs_path."/classes/fieldsets.php";

		$s_array=fieldsets::getFieldsets();
		for($i=0;$i<count($s_array);$i++) {
			if ($i>0) echo ',';
			echo $s_array[$i]['id'];
		}

		break;

	case 'dep':

		// id = selected value from table1
		// dep_id = id of the depending field

		$dep_id = get_numeric_only("dep_id");
		$n = get_numeric_only("table");

		global $config_abs_path;
		require_once $config_abs_path."/classes/depending_fields.php";
		$depending=new depending_fields();
		$tableN = $depending->getTableN($n, $dep_id);

		$dep_arr = $depending->getSecondTable($tableN, $id);
		$count=count($dep_arr);
		for($i=0; $i<$count;$i++) { 
			if($i) echo ':';
			echo $dep_arr[$i]['id'].','.$dep_arr[$i]['name'];
		}

		break; 

	case 'group':

		// div_str
		$div_str='';
		$fields=new fields('uf');
		$fields=$fields->getSpecificFields();
		$no_fields=count($fields);
		for($i=0; $i<$no_fields;$i++) {
			$groups_list=$fields[$i]['groups'];
			$caption=$fields[$i]['caption'];
			$div_name='div_'.$caption;
			$arr=explode(',',$groups_list);
			if($i) $div_str.=',';
			$div_str.=$div_name.'=';
			if(in_array($id,$arr))	$div_str.="1";
			else $div_str.="0";
		}

		if($div_str=='') $div_str='0';
		echo $div_str;

		break;

	case 'depending':

		require_once $config_abs_path."/classes/depending_fields.php";
		$field = get_numeric_only("field");
		$field_no = get_numeric_only("field_no");
		if ( !isset($_GET['dep'])) exit(0); 

		if($appearance_settings['charset']!="UTF-8")
			$dep = escape(utf8_decode(rawurldecode($_GET['dep'])));
		else $dep = escape(rawurldecode($_GET['dep']));

		$cat = get_numeric("cat", 0);
//***
		$prev_id = get_numeric("prev_id", 0);

		// $field = depending field id
		// $dep = first depending field selected value
		$f=new depending_fields();
		$df = $f->getDependingField($field);
		$top_str =array();
		$table = array();
		$caption = array();
		for($fn=0; $fn<5; $fn++) {
			$in = $fn+1;
			$top_str[$fn] = $df['top_str'.$in];
			$table[$fn] = $df['table'.$in];
			$caption[$fn] = $df['caption'.$in];
		}

		$table1 = $table[$field_no-1];
		$table2 = $table[$field_no];

		if($dep!=-1 && $dep) {
//***
			$val = $f->getRegId($dep, $table1, $cat, $prev_id);
			$values=$f->getSecondTable($table2, $val);

		} else {
			$values=array();
		}

		$count=count($values);
		if($field_no+1<5) { 
			$next_caption1 = $caption[$field_no+1];
			$next_top_str1 = $top_str[$field_no+1];
		} else {
 			$next_caption1='';
			$next_top_str1='';
		}

		if($field_no+2<5) { 
			$next_caption2 = $caption[$field_no+2];
			$next_top_str2 = $top_str[$field_no+2];
		} else {
			$next_caption2='';
			$next_top_str2='';
		}
/*
		if($field_no+3<5) { 
			$next_caption3 = $caption[$field_no+3];
			$next_top_str3 = $top_str[$field_no+3];
		} else {
			$next_caption3='';
			$next_top_str3='';
		}
		*/
//***
		echo $top_str[$field_no].':'.$val.':'.$next_caption1.':'.$next_caption2.':'.$next_top_str1.':'.$next_top_str2;
		for($i=0; $i<$count;$i++) echo ':'.$values[$i]['id'].','.$values[$i]['name'];

		break;

	case 'rule':

		require_once $config_abs_path."/classes/rules.php";

		$rules=new rules();
		$rules_array=$rules->getList();
		for($i=0;$i<count($rules_array);$i++) {
			if ($i) echo ',';
			echo $rules_array[$i]['id'];
		}

		break;

	case 'user_ac':

		require_once $config_abs_path."/classes/users.php";
		if(isset($_GET['term'])) {
		        $term = escape($_GET['term']);
        		$result = users::getAutocomplete($term);

			require_once $config_abs_path."/libs/JSON.php";

	        	echo json_encode($result);
		}
		break;

	case 'tpl':

		$tpl = escape($_GET['tpl']);
		global $db, $config_table_prefix;
		$arr = $db->fetchRowList("select `colorscheme` from ".$config_table_prefix."tpl_colorschemes where `tpl`='$tpl'");
		if(!$arr) echo "0"; 
		else echo implode("|",$arr);

		break;

} // end switch type


?>