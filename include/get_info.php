<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

require_once "../config.php";
global $config_abs_path;
require_once $config_abs_path."/include/include_min.php";
require_once $config_abs_path."/classes/packages.php";

global $appearance_settings;
header('Content-type: text/html; charset='.$appearance_settings['charset']);

$arr_action=array("banner", "depending", "discount", "sub_price", "reload_depending", "browse_location", "browse_make", "quick-search", "subcat", "listing", "geocoding", "processor", "dep_value");

if(isset($_GET['type']) && in_array($_GET['type'], $arr_action)) $type=$_GET['type']; 

else 
// check for post
{
	if(isset($_POST['type']) && in_array($_POST['type'], $arr_action)) $type=$_POST['type'];
}
if(!$type) exit(0);

switch ($type) {
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

		$val='';
		if($dep!=-1 && $dep!="all" && $dep) {
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
//***
		echo $top_str[$field_no].':'.$val.':'.$next_caption1.':'.$next_caption2.':'.$next_top_str1.':'.$next_top_str2;
		for($i=0; $i<$count;$i++) echo ':'.$values[$i]['id'].','.$values[$i]['name'];

	break;

	case 'reload_depending':

		require_once $config_abs_path."/classes/categories.php";
		require_once $config_abs_path."/classes/fields.php";
		require_once $config_abs_path."/classes/depending_fields.php";

		if ( !isset($_GET['str'])) exit(0); else $str = escape($_GET['str']);
		if ( isset($_GET['cat']) && is_numeric($_GET['cat'])) $cat = $_GET['cat']; 
		else if($_GET['cat']) {
			$arr_cat = explode(",", $_GET['cat']);
			if(is_numeric($arr_cat[0])) $cat = $arr_cat[0]; else $cat = 0;
		}
		else $cat=0;

		// get fieldset for category
		if($cat) {
			$fieldset = categories::getFieldset($cat);
		}
		else $fieldset=0;
		$arr = explode(",", $str);
		$i=0;
		$str = '';
		$df = new depending_fields();
		$cf = new fields('cf');

		foreach ($arr as $dep_id) {

			if($cat) {
			// check if the fieldset for the category is included in the field's fieldsets or if its fieldset is 0
			$included = $cf->fieldsetIncluded($fieldset, $dep_id);
			if(!$included) continue;
			}
			
			$result = $df->getInput($dep_id);
			if($i) $str.="^";
			$str.=$result['caption1'].",".$result['top_str1'].",".$result['caption2'].",".$result['top_str2'];

			$vals = $df->getTable($result['table1'], $fieldset);
			foreach($vals as $val) $str.=','.$val['name'];
			$i++;
		}
		echo $str;
		// return result: field1_caption,top_str1,val1,val2,val3^field2_caption,top_str2,val1,val2,val3

	break;

	case 'dep_value':

	require_once $config_abs_path."/classes/depending_fields.php";

	    if(isset($_GET['table'])) $table = escape($_GET['table']);
	    if(isset($_GET['field'])) $field = escape($_GET['field']);
	    if(isset($_GET['val']) && is_numeric($_GET['val'])) $val = escape($_GET['val']);
	    if( !$table || !$val || !$field ) return;
	    $f = new depending_fields();
	    global $crt_lang;
	    $values=$f->getIdNameNo($field, $table, $crt_lang, $val);
	    $i = 0;
	    foreach($values as $val) { 
		if($i) echo "|";
		echo $val['id']."^".$val['name']."^".$val['no'];
		$i++;
	    }	    
	break;

	case 'sub_price':
		$id = get_numeric_only("id");
		$packages=new packages();
		$price=packages::getAmount($id);
		echo $price;
	break;

	case 'banner':
		require_once $config_abs_path."/classes/banners.php";
		$id = get_numeric_only("id");
		$banner=new banners();
		$banner->addHit($id);
	break;
	case 'discount':
		require_once $config_abs_path."/classes/coupons.php";
		require_once $config_abs_path."/classes/auth.php";

		my_session_start();

		if(isset($_GET['id'])) $code = escape($_GET['id']);
		if(isset($_GET['dtype']) && ($_GET['dtype']=="ads" || $_GET['dtype']=="store")) $type = $_GET['dtype']; else { echo '0'; exit(0); }
		if(isset($_GET['amount']) && is_numeric($_GET['amount'])) $amount = $_GET['amount']; else { echo '-1'; exit(0); }

		$auth = new auth();
		$user_id = $auth->crtUserId();
		$user = new users;
		$group = $user->getGroup($user_id);

		$result = coupons::getDiscountByCode ( $code, $user_id, $group, $type );
		if(!$result) { echo '-1'; exit(0); }

		if($result['type'] == "fixed") {

			$amount_final = $amount-$result['discount'];
			if($amount_final<0) $amount_final = 0;

		} else { // percent
			$amount_final = $amount - ($result['discount']*$amount)/100;
		}

		echo format_price($amount_final);

	break;
	case 'browse_location':
		global $crt_lang;
		$id = get_numeric_only("id");
		if ( !isset($_GET['table'])) exit(0); else $table = escape($_GET['table']);
		global $db;
		global $config_table_prefix;
		$arr = $db->fetchRowList("select `id` from ".$config_table_prefix.$table." where lang_id like '$crt_lang'");
		$string = implode(",", $arr);
		echo trim($string);
	break;
	case 'browse_make':
		global $crt_lang;
		$id = get_numeric_only("id");
		if ( !isset($_GET['table'])) exit(0); else $table = escape($_GET['table']);
		global $db;
		global $config_table_prefix;
		$arr = $db->fetchRowList("select `set_id` from ".$config_table_prefix.$table." where `set_id`!=0 and `lang_id` = '$crt_lang' group by `set_id`");
		$string = implode(",", $arr);
		echo trim($string);
	break;

	case 'quick-search':

		require_once $config_abs_path."/classes/fields.php";
		require_once $config_abs_path."/classes/depending_fields.php";
		require_once $config_abs_path."/classes/categories.php";
		$id = get_numeric_only("id");
		$fields_str='';
		$fields=common::getCachedObject("base_listing_fields");
		$f = new fields("cf");
		$arr=$f->getSearchForCategory($id, "quick");
		$no_fields=count($fields);
		$k=0;

		for($i=0; $i<$no_fields;$i++) {
			if(!$fields[$i]['quick_search']) continue;

			$field_id=$fields[$i]['id'];
			$fieldset = $fields[$i]['fieldset'];
			
			$search_type = $fields[$i]['search_type'];

			// check depending fields
			if($fields[$i]['type']=="depending") {
				
			$caption = array();
			$caption[1] = $fields[$i]['depending']['caption1'];
			$caption[2] = $fields[$i]['depending']['caption2'];
			$caption[3] = $fields[$i]['depending']['caption3'];
			$caption[4] = $fields[$i]['depending']['caption4'];
				
			for($j=1; $j<=4;$j++) {
				if($j>$fields[$i]['depending']['no']) break;
				if($k) $fields_str.=',';
				$fields_str.='li_'.$caption[$j].'=';
				if(in_array($field_id,$arr) || $fieldset==0)	$fields_str.="1";
				else $fields_str.="0";
				$k++;
			}

			} else { // not depending

				$caption=$fields[$i]['caption'];

				if($search_type==2 && $fields[$i]['type']!="date") // interval 
				{

					if($k) $fields_str.=',';
					$fields_str.='li_'.$caption.'_low=';
					if(in_array($field_id,$arr) || $fieldset==0)	$fields_str.="1";
					else $fields_str.="0";

					$fields_str.=',';
					$fields_str.='li_'.$caption.'_high=';
					if(in_array($field_id,$arr) || $fieldset==0)	$fields_str.="1";
					else $fields_str.="0";
			
				} else {

					if($k) $fields_str.=',';
					$fields_str.='li_'.$caption.'=';
					if(in_array($field_id,$arr) || $fieldset==0)	$fields_str.="1";
					else $fields_str.="0";

				} // end not interval

				}
			$k++;
		}

		if($fields_str=='') $fields_str='0';
		echo $fields_str;

	break;

	case 'subcat':
                $id = get_numeric_only("id");
                $group = get_numeric("group");
		$arr=common::getCachedObject("base_short_categories", array("group" => $group, "parent" => $id));

                if(!$arr) { echo ("0"); exit(0); }
                $i=0;
                foreach($arr as $row) {
                        if($i) echo "^";
                        echo $row['id']."@".cleanHtml($row['name']);
                        $i++;
                }
        break;
	// listing short version for gmaps popup
	case 'listing':
		$id = get_numeric_only("id");

		require_once $config_abs_path."/include/include.php";
		
		require_once $config_abs_path."/classes/listings.php";
		require_once $config_abs_path."/classes/pictures.php";
		require_once $config_abs_path."/classes/users.php";
		require_once $config_abs_path."/classes/categories.php";
		global $db, $ads_settings, $seo_settings, $config_live_site;
		$l = new listings;
		$listing = $l->getListing($id);
		$listing['description'] = str_replace("\n", "<br>",get_start_string($listing['description'], 200));
		
		$smarty_info = new Smarty;
		$smarty_info = smartyShowDBVal($smarty_info);

		$smarty_info->assign("lng", $lng);
		$smarty_info->assign("listing", $listing);
		$smarty_info->assign("ads_settings", $ads_settings);
		$smarty_info->assign("seo_settings", $seo_settings);
		$smarty_info->assign("live_site", $config_live_site);
		
		global $seo;
		$seo = new seo();
		$seo->init($smarty_info);
		$smarty_info->registerObject('seo', $seo, null, false);

		$listing_details = $smarty_info->fetch("gmaps_listing.html");

		echo $listing_details;

	break;

	case 'geocoding':
		require_once $config_abs_path."/libs/JSON.php";
		if(isset($_POST['address'])) $address=base64_decode($_POST['address']); else exit(0);
		//$address = base64_decode("IEZyYW5jZSDDjmxlLWRlLUZyYW5jZSBQYXJpcw==");
		//echo $address;
		/*$url = "https://maps.google.com/maps/api/geocode/json?address=".urlencode($address);
		if(_isCurl()) 
			$geocode = getSSLPage($url);
		else*/ 
		$url = "http://maps.google.com/maps/api/geocode/json?address=".urlencode($address);
		if(_isCurl())
			$geocode = curl_get_contents($url);
		else 
			$geocode=file_get_contents($url);
			
		$output= json_decode($geocode);
		if(!$output->results) { echo "|"; break; }
		$latitude = $output->results[0]->geometry->location->lat;
		$longitude = $output->results[0]->geometry->location->lng;
		echo $latitude."|".$longitude;
	break;


	case 'processor':
		require_once $config_abs_path."/classes/payment_processors.php";
		if(isset($_POST['processor'])) $processor=escape($_POST['processor']); else exit(0);
		if(isset($_POST['total'])) $total=escape($_POST['total']); else exit(0);

		if($total<=0) echo "||";

		$pp = new payment_processors();
		$tax = $pp->calculateTax($processor, $total);
		$full_total = $total + $tax;

		$tax_info = ''; $total_str = ''; $tax_str = '';
		if($tax>0) $tax_str = add_currency(format_price($tax));
		if($full_total>0) $total_str = add_currency(format_price($full_total));

		$tax_info = $pp->getFormattedTax($processor);

		echo $tax_str."|".$total_str."|".$tax_info;

	break;

}

function getSSLPage($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSLVERSION,3); 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

function curl_get_contents($url)
{
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $url);

    $data = curl_exec($ch);
    curl_close($ch);

    return $data;
}
?>
