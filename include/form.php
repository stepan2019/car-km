<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

function my_session_start() {

	global $settings;
//	$session_name = session_name("oxss");
	if(($settings['enable_locations'] && $settings['enable_subdomains'])) { 

		global $main_domain;
		session_set_cookie_params(0, '/', '.'.$main_domain);
	}
//	session_start();

}

function _get_magic_quotes_gpc() {

	global $force_magic_quotes;
	$magic_quotes = 0;
	if(get_magic_quotes_gpc () || $force_magic_quotes) $magic_quotes=1;
	return $magic_quotes;

}

// sanitizes strings about to be used in a sql statement
function escape($str, $no_trim=0) {

	global $force_magic_quotes;
	$magic_quotes = 0;
	if(get_magic_quotes_gpc () || $force_magic_quotes) $magic_quotes=1;

	if(!$no_trim)
	    $clean_str = ( $magic_quotes ) ? _mysql_real_escape_string(stripslashes(trim($str))) : _mysql_real_escape_string(trim($str));
	else 
	    $clean_str = ( $magic_quotes ) ? _mysql_real_escape_string(stripslashes($str)) : _mysql_real_escape_string($str);

	return $clean_str;

}

function escapeHTML($str, $allowed_only = 0, $tags_list='') {

	if($allowed_only) {
		global $ads_settings;
		$tags_list = tags_list($ads_settings['allowed_html']);
		$clean_str = strip_tags($str, $tags_list);
	} 
	else 
		$clean_str = strip_tags($str);

	return $clean_str;

}

// mysql_real_escape_string replacement
function _mysql_real_escape_string ($str) {

	return strtr($str, array( "\x00" => '\x00', "\n" => '\n', "\r" => '\r', '\\' => '\\\\', "'" => "\'", '"' => '\"', "\x1a" => '\x1a' ));
}

function _addslashes($str) {

	$clean_str = ( _get_magic_quotes_gpc ()) ? $str : addslashes($str);
	return $clean_str;

}

// cleans slashes for a string pulled out from the db 
function cleanStr($str) {

	$clean_str = stripslashes (trim($str));
	return $clean_str;
}

// cleans a string for HTML output, tags removed
function cleanHtml($str, $allowed_only = 0, $tags_list='') {

	if($allowed_only) {
		global $ads_settings;
		$tags_list = tags_list($ads_settings['allowed_html']);
		$clean_str = stripslashes ( strip_tags($str, $tags_list));
	} 
	else 
		$clean_str = stripslashes ( strip_tags($str));

// fix for js issue???!!!

	return $clean_str;
}

// cleans a string with html elements encoded
function clean($str) {

	$str = strip_tags($str);
	global $appearance_settings;
	$charset = $appearance_settings['charset'];

	$supported = array("ISO-8859-1", "ISO-8859-5", "ISO-8859-15", "UTF-8", "cp866", "cp1251", "cp1252", "KOI8-R", "BIG5", "GB2312", "BIG5-HKSCS", "Shift_JIS", "EUC-JP", "MacRoman");
	if(!in_array($appearance_settings['charset'], $supported)) { 
		$str = mb_convert_encoding($str, 'UTF-8', mb_detect_encoding($str));
		$charset = "UTF-8";
	}    
	return htmlspecialchars($str, ENT_QUOTES, $charset, false);	
 	
}

function cleanSearchString($str) {

	return trim(strip_tags(rawurldecode($str)), " \t\n\r\0\x0B|");
	
}

function checkbox_value($str) {

	if(isset($_POST[$str]) && $_POST[$str]=="on") return 1;
	return 0;

}

function generate_random() {
	$str = md5(uniqid(rand(),1));
	return $str;
}

function html_link($str) {

	$str=correct_href($str);
	$ret='<a href="'.$str.'">'.$str.'</a>';
	return $ret;

}

function correct_href($str) {

	if(!trim($str)) return;
	if(strcmp(substr($str,0,7),"http://") && strcmp(substr($str,0,8),"https://")) $str="http://".$str;
	return $str;

}

function correct_number_format($str) {

	global $appearance_settings;
	$decimals = $appearance_settings['number_format_decimals'];
	$point = $appearance_settings['number_format_point'];
	$separator = $appearance_settings['number_format_separator'];
	$ereg_str = "/^[0-9]*".$point."*[0-9]+".$separator."*[0-9]*$/";

	$ereg_str = str_replace(".", "\.", $ereg_str);
	if(preg_match($ereg_str, $str)) return 1;
	return 0;

}

function correct_numeric($str) {

	global $appearance_settings;
	$point = $appearance_settings['number_format_point'];
	$separator = $appearance_settings['number_format_separator'];

	// replace 
	$str = str_replace($point,"#",$str);
	$str = str_replace($separator,"",$str);
	$str = str_replace("#",".",$str);

	return $str;

}

function correct_price($str, $separator='', $point='') {

	global $appearance_settings;
	if(empty($point)) $point = $appearance_settings['price_format_point'];
	if(empty($_separator)) $separator = $appearance_settings['price_format_separator'];

	// replace 
	$str = str_replace($point,"#",$str);
	$str = str_replace($separator,"",$str);
	$str = str_replace("#",".",$str);

	return $str;

}

function getExtension($str) {

	$ext = preg_replace('/^.*\.([^.]+)$/D', '$1', $str);
	return strtolower($ext);

}

function stripExtension($str) {

	$noext = preg_replace("/\\.[^.\\s]{3,4}$/", "", $str);
	return $noext;

}

function _print_r($arr) {

	echo "<pre>";
	print_r($arr);
	echo "</pre>";

}


function smartyShowDBVal($smarty) {

	global $config_abs_path;
	global $config_live_site;
	global $appearance_settings;
	global $settings;
	$smarty->compile_dir=$config_abs_path.'/temp/';
	$smarty->assign("site_url",$config_live_site);
	$smarty->assign("administrator",$settings['admin_name']);
	$smarty->assign("site_name",$settings['site_name']);

	$smarty->assign("settings",$settings);
	$smarty->assign("appearance",$appearance_settings);

	$smarty->debugging = false;
	$smarty->template_dir = $config_abs_path."/templates/".$appearance_settings['template'];
	$smarty->assign("template_path",$config_live_site."/templates/".$appearance_settings['template']."/");
	return $smarty;

}

function getScriptName() {

	return basename($_SERVER["SCRIPT_NAME"]);

}

function getScriptNameNoExt() {

	return basename($_SERVER["SCRIPT_NAME"] , ".php");

}

function getPath($url='') {

	if(!$url) $url = $_SERVER["SCRIPT_NAME"];
	return dirname($url);

}

function _urlencode($str, $replace_signs = 1, $no_chars = 0, $all_maps=0) {

	$str = strip_tags($str);
	$str = characters_map($str, $all_maps);
	$str = strtolower($str);   // Convert to lowercase
	if($no_chars) { 
		$diff_chars = _strlen($str)-$no_chars;
		if($diff_chars>0) {
			$pos = _strpos($str, ' ', (-1)*$diff_chars);
			if($pos) $str = _substr($str, $pos);
		}
	}
	//$str = start_words(trim($str), $no_chars);
	if($replace_signs) { 
		// reserved characters: "&", "$", "+", ",", "/", ":", ";", "=", "?", "@"
		// unsafe characters: " ", "?", "<>", "[]", "{}", "|", "\", "^", "~", "%", "#", "`"
		// other signs: "!", ".", "(", ")", ", "'"	
		$str = str_replace (" ","-", $str);
		$str = preg_replace('/[&$+,\/:;=?@<>{}|\^~%#\[\]!.()"\']/', '', $str);
		$str = preg_replace("/(-){2,}/",'$1',$str); 
	}
//	$str = rawurlencode( $str );


/*	$str = strip_tags($str);
	$str = characters_map($str);
	$str = strtolower($str);                              // Convert to lowercase
	global $non_latin_url;
	if(!$non_latin_url) $str = preg_replace('/[^a-zA-Z0-9\s\/-]&/', '', $str);  // Remove all punctuation
	
// reserved characters: "&", "$", "+", ",", "/", ":", ";", "=", "?", "@"
// unsafe characters: " ", "?", "<>", "[]", "{}", "|", "\", "^", "~", "%", "#", "`"	
	
	$str = preg_replace("/ +/", " ", $str);               // Remove multiple spaces
	$str = str_replace ("/","--", $str);
	if($no_chars) $str = start_words(trim($str), $no_chars);
	$str = urlencode( str_replace (" ","-", $str));
	//$str = preg_replace("/(-){2,}/",'$1',$str); // replace multiple --- with a single one
*/

	return $str;

}

function start_words($str, $len)
{
	if($len < _strlen($str))
	{
		$ret=_substr($str,$len);
		return $ret;
	}
	else return $str;
}

function _substr($str, $len, $add="") {

	global $config_db_charset;
	if($config_db_charset=="utf8" && function_exists('mb_substr'))
	    $ret=mb_substr($str,0,$len,"utf8").$add;
	else
	    $ret=substr($str,0,$len).$add;
	return $ret;
}

function _strlen($str) {

	global $config_db_charset;
	if($config_db_charset=="utf8" && function_exists('mb_strlen'))
	    $no=mb_strlen($str,"utf8");
	else
	    $no=strlen($str);
	return $no;
}

function _strpos($str, $needle, $offset=0) {

	global $config_db_charset;
	if($config_db_charset=="utf8" && function_exists('mb_strpos'))
	    $ret=mb_strpos($str,$needle,$offset,"utf8");
	else
	    $ret=strpos($str,$needle,$offset);
	return $ret;
}

function format_numeric($str) {

	if(!is_numeric($str)) return;
	global $appearance_settings;
	$decimals = $appearance_settings['number_format_decimals'];
	$point = $appearance_settings['number_format_point'];
	$th_separator = $appearance_settings['number_format_separator'];
	$result = number_format($str, $decimals, $point, $th_separator);
	return $result;

}

function format_price($str, $decimals='', $th_separator='', $point='') {

	if(!is_numeric($str)) return;
	global $appearance_settings;
	if(empty($decimals)) $decimals = $appearance_settings['price_format_decimals'];
	if(empty($point)) $point = $appearance_settings['price_format_point'];
	if(empty($th_separator)) $th_separator = $appearance_settings['price_format_separator'];
	$result = number_format($str, $decimals, $point, $th_separator);
	return $result;

}

function format_price_field($str, $decimals='', $th_separator='', $point='') {

	if(!is_numeric($str)) return;
	global $appearance_settings;
	if(empty($decimals)) $decimals = $appearance_settings['price_field_format_decimals'];
	if(empty($decimals)) $decimals=0;
	if(empty($point)) $point = $appearance_settings['price_field_format_point'];
	if(empty($th_separator)) $th_separator = $appearance_settings['price_field_format_separator'];
	$result = number_format($str, $decimals, $point, $th_separator);
	return $result;

}


function format_int($str) {

	if(!is_numeric($str)) return;
	global $appearance_settings;
	$decimals = 0;
	$point = '';
	$th_separator = $appearance_settings['number_format_separator'];
	$result = number_format($str, $decimals, $point, $th_separator);
	return $result;

}

function add_currency($amount, $currency='') {

	global $appearance_settings;
	if(!$currency) $currency=$appearance_settings['default_currency'];
	if($amount!='') {
		global $add_space_after_currency;
		if($appearance_settings['currency_pos']==0) { 
		    if($add_space_after_currency)
			$amount_nice = $currency." ".$amount;
		    else 
			$amount_nice = $currency.$amount;
		}
		else { 
		    if($add_space_after_currency)
			$amount_nice = $amount." ".$currency;
		    else 	
			$amount_nice = $amount.$currency;
		}
		return $amount_nice;
	}
	global $lng;
	return $lng['listings']['NA'];

}

function format_date_str($date, $date_format) {

	$date_format_new = convertDateFormat($date_format);
	global $db;
	$result = $db->fetchRow('select date_format("'.$date.'", "'.$date_format_new.'")');
	$result = str_replace("'", "", $result);
	return $result;
}

function convertDateFormat($date_format) {

/*
	   The format can be combinations of the following:
	   d  - day of month (no leading zero) (1 -- 12)				--> %e
	   dd - day of month (two digit) (01 -- 12)					--> %d
	   //o  - day of year (no leading zeros) 	
	   oo - day of year (three digit) (001 -- 365)					--> %j
	   D  - day name short	(Sun -- Sat)						--> %a
	   DD - day name long (Sunday -- Saturday)					--> %W
	   m  - month of year (no leading zero)	 (1 -- 12)				--> %c
	   mm - month of year (two digit) (01 -- 12)					--> %m
	   M  - month name short (Jan -- Dec)						--> %b
	   MM - month name long	 (January -- December)					--> %M
	   y  - year (two digit) (00 -- 09)						--> %y
	   yy - year (four digit) (2000 -- 2009)					--> %Y
	   //@ - Unix timestamp (ms since 01/01/1970)
	   '...' - literal text
	   '' - single quote
*/
	$len = strlen($date_format);
	$start_quote=0;
	$date_format_new = '';
	for ($i=0; $i<$len;$i++) {

		// jump through quoted content
		while ($start_quote && $i<$len && $date_format[$i] != '\'') $date_format_new .= $date_format[$i++];
		if($date_format[$i] == '\'') $start_quote = ($start_quote+1)%2;

		if($i==$len-1) continue;

		switch ($date_format[$i]) {

			case 'd':
				if($date_format[$i+1] == 'd') {
					$date_format_new.="%d";
					$i++;
				} else 
					$date_format_new.= "%e";
				break;

			case 'D':
				if($date_format[$i+1] == 'D') {
					$date_format_new .= "%W";
					$i++;
				} else 
					$date_format_new .= "%a";
				break;

			case 'm':
				if($date_format[$i+1] == 'm') {
					$date_format_new .= "%m";
					$i++;
				} else 
					$date_format_new .= "%c";
				break;

			case 'M':
				if($date_format[$i+1] == 'M') {
					$date_format_new .= "%M";
					$i++;
				} else 
					$date_format_new .= "%b";
				break;

			case 'y':
				if($date_format[$i+1] == 'y') {
					$date_format_new .= "%Y";
					$i++;
				} else 
					$date_format_new .= "%y";
				break;

			case 'o':
				if($date_format[$i+1] == 'o') {
					$date_format_new .= "%j";
					$i++;
				}
				break;
			default: 
				$date_format_new .= $date_format[$i];
 				break;
		}

	}
	return $date_format_new;

}

function tags_list($str) {

	$result = "";
	$array = explode(",", $str);
	foreach ($array as $el) $result.="<".$el.">";
	return $result;

}

/* ---------------  start characters map ------------------ */
global $array_maps;
$array_maps = array("czech-slovak-slovenian", "danish_norwegian", "dutch", "finnish", "french", "gaelic", "german", "greek", "hungarian", "italian", "portuguese", "romanian", "russian", "spanish", "swedish", "turkish", "lithuanian", "polish");

global $source, $dest;
$source = array();
$dest = array();

// Czech, Slovak, and Slovenian
$source['czech-slovak-slovenian'] = array("Á", "á", "Ą", "ą", "Ä", "ä", "É", "é", "Ę", "ę", 
"Ě", "ě", "Í", "í", "Ó", "ó", "Ô", "ô", "Ú", "ú", 
"Ů", "ů", "Ý", "ý", "Č", "č", "Ć", "ć", "ď", "Đ", 
"đ", "ť", "Ĺ", "ĺ", "Ň", "ň", "Ŕ", "ŕ", "Ř", "ř", 
"Š", "š", "Ž", "ž");
$dest['czech-slovak-slovenian'] = array("A", "a", "A", "a", "A", "a", "E", "e", "E", "e", 
"E", "e", "I", "i", "O", "o", "O", "o", "U", "u", 
"U", "u", "Y", "y", "C", "c", "C", "c", "f", "D", 
"d", "y", "L", "I", "N", "n", "R", "r", "R", "r", 
"S", "s", "Z", "z");

// Danish and Norwegian
$source['danish-norwegian'] = array("æ", "å", "œ", "ø", "Æ", "Å", "Œ", "Ø");
$dest['danish-norwegian'] = array("a", "a", "oe", "o", "ae", "a", "oe", "o");

// Dutch
$source['dutch'] = array("à", "á", "â", "ä", "è", "é", "ê", "ë", "ì", "í",
"î", "ï", "ò", "ó", "ô", "ö", "ù", "ú", "û", "ü",
"À", "Á", "Â", "Ä", "È", "É", "Ê", "Ë", "Ì", "Í",
"Î", "Ï", "Ò", "Ó", "Ô", "Ö", "Ù", "Ú", "Û", "Ü" );
$dest['dutch'] = array("a", "a", "a", "a", "e", "e", "e", "e", "i", "i",
"i", "i", "o", "o", "o", "o", "u", "u", "u", "u",
"a", "a", "a", "a", "e", "e", "e", "e", "i", "i",
"i", "i", "o", "o", "o", "o", "u", "u", "u", "u");

//Finnish
$source['finnish'] = array("ä", "ö", "Ä", "Ö");
$dest['finnish'] = array("a", "o", "a", "o");

// French
$source['french'] = array("à", "â", "ä", "è", "é", "ê", "ë", "î", "ï", "ô", 
"œ", "ù", "û", "ü", "ÿ", "À", "Á", "Ä", "È", "É",
"Ê", "Ë", "Î", "Ï", "Ô", "Œ", "Ù", "Û", "Ü", "Ÿ",
"ç", "Ç");
$dest['french'] = array("a", "a", "a", "e", "e", "e", "e", "i", "i", "o", 
"oe", "u", "u", "u", "y", "a", "a", "a", "e", "e",
"e", "e", "i", "i", "o", "oe", "u", "u", "u", "y",
"c", "c");

// Gaelic (Irish and Scotish)
$source['gaelic'] = array("á", "é", "í", "ó", "ú", "Á", "É", "Í", "Ó", "Ú");
$dest['gaelic'] = array("a", "e", "i", "o", "u", "a", "e", "i", "o", "u");

// German
$source['german'] = array("ä", "ö", "ü", "Ä", "Ö", "Ü", "ß");
$dest['german'] = array("ae", "oe", "ue", "ae", "oe", "ue", "s");

// Greek
$source['greek'] = array("α", "β", "γ", "δ", "ε", "ζ", "η", "θ", "ι", "κ", 
"λ", "μ", "ν", "ξ", "ο", "π", "ρ", "σ", "τ", "υ", 
"φ", "χ", "ψ", "ω", "ά", "έ", "ί", "ή", "ό", "ύ", 
"ώ", "Ου", "Ού", "Α", "Β", "Γ", "Δ", "Ε", "Ζ", "Η", 
"Θ", "Ι", "Κ", "Λ", "Μ", "Ν", "Ξ", "Ο", "Π", "Ρ", 
"Σ", "Τ", "Υ", "Φ", "Χ", "Ψ", "Ω", "ς", "Ά", "Έ", 
"Ή", "Ί", "Ό", "Ύ", "Ώ", "ϊ", "ΐ");
$dest['greek'] = array("a", "b", "g", "d", "e", "z", "i", "th", "i", "k", 
"l", "m", "n", "ks", "o", "p", "r", "s", "t", "i", 
"f", "x", "ps", "o", "a", "e", "i", "i", "o", "i", 
"o", "ou", "ou", "a", "b", "g", "d", "e", "z", "i", 
"th", "i", "k", "l", "m", "n", "ks", "o", "p", "r", 
"s", "t", "i", "f", "x", "ps", "o", "s", "a", "e", 
"i", "i", "o", "i", "o", "i", "i");

// Hungarian
$source['hungarian'] = array("á", "Á", "é", "É", "í", "Í", "ö", "Ö", "ó", "Ó", 
"ő", "Ő", "ü", "Ü", "ú", "Ú", "ű", "Ű");
$dest['hungarian'] = array("a", "A", "e", "E", "i", "I", "o", "O", "o", "O", 
"o", "O", "u", "U", "u", "U", "u", "U");


// Italian
$source['italian'] = array("à", "è", "é", "ì", "ò", "ó", "ù", "À", "È", "É",
"Ì", "Ò", "Ó", "Ù");
$dest['italian'] = array("a", "e", "e", "i", "o", "o", "u", "a", "e", "e",
"i", "o", "o", "u");

// Portuguese
$source['portuguese'] = array("à", "á", "â", "ã", "é", "ê", "í", "ó", "ô", "õ", 
"ú", "ü", "À", "Á", "Â", "Ã", "É", "Ê", "Í", "Ó",
"Ô", "Õ", "Ú", "Ü", "ç", "Ç");
$dest['portuguese'] = array("a", "a", "a", "a", "e", "e", "i", "o", "o", "o", 
"u", "u", "a", "a", "a", "a", "e", "e", "i", "o",
"o", "o", "u", "u", "c", "c");

// Romanian
$source['romanian'] = array("ă", "Ă", "â", "Â", "î", "Î", "ș", "Ș", "ț", "Ț");
$dest['romanian'] = array("a", "a", "a", "a", "i", "i", "s", "s", "t", "t");

// Russian
$source['russian'] = array("й", "ц", "у", "к", "е", "н", "г", "ш", "щ", 
"з", "х", "ъ", "э", "ж", "д", "л", "о", "р", "п", 
"а", "в", "ы", "ф", "я", "ч", "с", "м", "и", "т", 
"ь", "б", "ю", "Й", "Ц", "У", "К", "Е", "Н", "Г", 
"Ш", "Щ", "З", "Х", "Ъ", "Ф", "Ы", "В", "А", "П", 
"Р", "О", "Л", "Д", "Я", "Ч", "С", "М", "И", "Т", 
"Ь", "Б", "Ю", "Э", "Ж");
$dest['russian'] = array("y", "ts", "u", "k", "e", "n", "g", "sh", "sch", 
"z", "h", "", "e", "j", "d", "l", "o", "r", "p", 
"a", "v", "yi", "f", "ya", "ch", "s", "m", "i", "t", 
"", "b", "yu", "Y", "TS", "U", "K", "E", "N", "G", 
"SH", "SCH", "Z", "H", "", "F", "Y", "V", "A", "P", 
"R", "O", "L", "D", "YA", "CH", "S", "M", "I", "T", 
"", "B", "YU", "E", "J",);

// Spanish
$source['spanish'] = array("á", "é", "í", "ó", "ú", "ü", "Á", "É", "Í", "Ó",
"Ú", "Ü", "ñ", "Ñ");
$dest['spanish'] = array("a", "e", "i", "o", "u", "u", "a", "e", "i", "o",
"u", "u", "n", "n");

// Swedish
$source['swedish'] = array("å", "ä", "ö", "Å", "Ä", "Ö");
$dest['swedish'] = array("a", "a", "o", "a", "a", "o");


// Turkish
$source['turkish'] = array("ı", "İ", "ö", "Ö", "ü", "Ü", "ç", "Ç", "ğ", "Ğ",
"ş", "Ş");
$dest['turkish'] = array("i", "i", "o", "o", "u", "u", "c", "c", "g", "g",
"s", "s");

// Lithuanian
$source['lithuanian'] = array("ą", "č", "ę", "ė", "į", "š", "ų", "ū", "ž");
$dest['lithuanian'] = array("a", "c", "e", "e", "i", "s", "u", "u", "z");

// Polish
$source['polish'] = array("ł", "ó", "ś", "ź", "ż", "ą", "ę", "ć", "ń", "Ł", 
"Ó", "Ź", "Ż", "Ą", "Ę", "Ć", "Ś", "Ń");
$dest['polish'] = array("l", "o", "s", "z", "z", "z", "e", "c", "n", "L",
"O", "Z", "Z", "A", "E", "C", "S", "N");


function characters_map($str, $all=0){

	global $source, $dest;
	if($all) {
		foreach($source as $key=>$value) {
				$str = str_replace($source[$key], $dest[$key], $str);
		}
		return $str;
	}

	global $maps;
	if(empty($maps)) $maps = common::getCharacterMaps();

	foreach($maps as $map) {
		if(isset($source[$map]) && isset($dest[$map])) {
				$str = str_replace($source[$map], $dest[$map], $str);
		}
	}
	return $str;
	
}

/* ---------------  end characters map ------------------ */

function isCGI() {

	$sapi = php_sapi_name();
	$cgi=0;
	if(stristr($sapi, "cgi")) $cgi = 1;
	return $cgi;

}


function breakpoint(){
    ob_flush();
    flush();
    sleep(.1);
    debugBreak();
}

function write_last($day) {
	global $config_abs_path;
	$file=fopen($config_abs_path."/last.php", "w");
	if($file != null) {
		$data='<?php
	define ("LAST_DAY","'.$day.'");
?>';
		fwrite($file, $data);
	}
	fclose($file);
}


function shuffle_keys($str) {

	//$clean_str=preg_replace("/[^[:alnum:]+]/"," ",$str);
	$keys='';
	$array_str=explode(" ",trim($str));
	$n=count($array_str);
	srand((double)microtime()*1000000); 
	$i=0;
	$used=array();
	$values=array();
	for($i=0; $i<$n; $i++) {$used[$i]='';$values[$i]=''; }
	$a=0;
	for($i=0; $i<$n; $i++)
	{
		$val = rand(0,$n-1); 
		$again=0;
		for($j=0; $j<count($used);$j++)
			if($used[$j]==$val) { $again=1; continue; }
		if(!$again) { $used[$a]=$val; $values[$i]=trim($array_str[$val]); $a++; }
	}

	$first=1;
	for($k=0;$k<$n;$k++) {
		if(_strlen(trim($array_str[$k]))>2) {
			if($k>0) $first=0;
			if(!$first) $keys.=', ';
			$keys.=$array_str[$k];
		}
	}
	return $keys;
}

function get_start_string($str, $len)
{
	if($len < _strlen($str)) {
		$ret = _substr($str, $len, "...");
		return $ret;
	}
	return $str;
}

function checkPageNo($page, $ads_per_page)
{
	
	// it too high page no, set the page a lower no
	global $max_results;
	if($page*$ads_per_page>$max_results) return ceil($max_results/$ads_per_page);

	return $page;
	
} 

function checkNoItems($no_items)
{
	
	// it too high number of items set to max_results
	global $max_results;
	if($no_items>$max_results) return $max_results;

	return $no_items;
	
} 

function _mb_strtolower($str) {
	if(function_exists("mb_strtolower"))
		return mb_strtolower($str, mb_detect_encoding($str));
	return strtolower($str);	
}

function _strtolower ($str) {

	global $config_db_charset;
	if($config_db_charset=="utf8")
		$str = _mb_strtolower($str);
	else 		
		$str = strtolower($str);
	return $str;
}

$extra_banner_positions = array();

$site_sections = array("firstpage", "details", "listings", "recent", "user_listings", "custom", "account", "login-register", "other");

$default_fields_types = array("textbox", "textarea", "htmlarea", "menu", "multiselect", "radio", "radio_group", "checkbox", "checkbox_group", "depending", "url", "email", "date", "file", "image", "logo", "youtube", "google_maps", "terms", "price", "username", "password", "user_email", "phone", "whatsapp", "twitter", "section", "video", "audio");
    
function get_numeric_only($f) {
	
	if(isset($_GET[$f]) && is_numeric($_GET[$f])) $val = $_GET[$f]; else exit(0);
	return $val;

}

function get_numeric($f, $default='') {
	
	if(isset($_GET[$f]) && is_numeric($_GET[$f])) $val = $_GET[$f]; 
	else $val = $default;
	return $val;

}

function setMainDomain() {

	global $config_live_site, $main_domain;
	
	$dstr = str_replace("http://www.", "", $config_live_site);
	$dstr = str_replace("http://", "", $dstr);
	$dstr = str_replace("https://www.", "", $dstr);
	$dstr = str_replace("https://", "", $dstr);

//	$dstr = str_replace("https://www.", "", $config_live_site);
//	$dstr = str_replace("https://", "", $dstr);

        // normal domain name domain.com
        $tld_pattern1 = "/^([A-Za-z0-9.-]+(\.))?[A-Za-z0-9.-]+(\.)[A-Za-z]{2,6}(.*)?$/";
        $composed_tlds = "co.uk|co.za|com.ua|com.au|com.tw|com.mx|com.br|co.sw|co.nz|com.de|com.ng|com.ph";
	// domain name with a composed tld
        $tld_pattern2 = "/^([A-Za-z0-9.-]+(\.))?[A-Za-z0-9.-]+(\.)(".$composed_tlds.")(.*)?$/";
	// server ip
        $ip_pattern= "/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}(.*)?$/";
	// localhost install
        $localhost_pattern = "/^localhost(.*)$/";

        if(preg_match($tld_pattern2, $dstr) )
    	    preg_match ("/([A-Za-z0-9.-]+(\.))?[A-Za-z0-9-]+(\.)(".$composed_tlds.")/", $dstr, $domain_only);
        else if(preg_match($tld_pattern1, $dstr) )
            preg_match ("/([A-Za-z0-9.-]+(\.))?([A-Za-z0-9-])+(\.)[A-Za-z]{2,6}/", $dstr, $domain_only);
	else if(preg_match($ip_pattern, $dstr) ) 
	    preg_match ("/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/", $dstr, $domain_only);
	else if(preg_match($localhost_pattern, $dstr) ) {
	    $domain_only = array(0=>"localhost");
	}

	$main_domain = $domain_only[0];
	return $main_domain;

}

function _isCurl(){
    return function_exists('curl_version');
}    

function url_exists($url) {

    if(_isCurl()) {
        $ch = @curl_init($url);
	@curl_setopt($ch, CURLOPT_HEADER, TRUE);
        @curl_setopt($ch, CURLOPT_NOBODY, TRUE);
	@curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE);
        @curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	$status = array();
        preg_match('/HTTP\/.* ([0-9]+) .*/', @curl_exec($ch) , $status);
    }
    else {
	$h = get_headers($url);
        $status = array();
        preg_match('/HTTP\/.* ([0-9]+) .*/', $h[0] , $status);
    }
    return ($status[1] == 200);
}

function createActionsArray() {

    return array( "ad_id" => 0, "pkg_id" => 0, "credits_pkg_id" => 0, "newad" => array( 'value' => 0 , 'price' => 0) , "renewad" => array( 'value' => 0 , 'price' => 0), "featured" => array( 'value' => 0 , 'price' => 0), "highlited" => array( 'value' => 0 , 'price' => 0), "priority" => array( 'value' => 0 , 'price' => 0), "urgent" => array( 'value' => 0 , 'price' => 0), "video" => array( 'value' => 0 , 'price' => 0), "url" => array( 'value' => 0 , 'price' => 0), "newpkg" => array( 'value' => 0 , 'price' => 0), "renewpkg" => array( 'value' => 0 , 'price' => 0), "new_creditspkg" => array( 'value' => 0 , 'price' => 0), "store" => array( 'value' => 0 , 'price' => 0), "discount_code" =>'' );

}

function setTimezone() {

	global $appearance_settings;
	// set default timezone
	if(!$appearance_settings['timezone'] || !function_exists('date_default_timezone_set')) return;

	date_default_timezone_set($appearance_settings['timezone']);
}

function isUnicodePropertiesFriendly() {

    if ( !@preg_match('/^.$/u', urldecode('%C3%B1'))) return 0;
    if ( !@preg_match('/^\pL$/u', urldecode('%C3%B1'))) return 0;
    return 1;

}

function my_hash_hmac($algo, $data, $key, $raw_output = false)
{
    $algo = strtolower($algo);
    $pack = 'H'.strlen($algo('test'));
    $size = 64;
    $opad = str_repeat(chr(0x5C), $size);
    $ipad = str_repeat(chr(0x36), $size);

    if (strlen($key) > $size) {
        $key = str_pad(pack($pack, $algo($key)), $size, chr(0x00));
    } else {
        $key = str_pad($key, $size, chr(0x00));
    }

    for ($i = 0; $i < strlen($key) - 1; $i++) {
        $opad[$i] = $opad[$i] ^ $key[$i];
        $ipad[$i] = $ipad[$i] ^ $key[$i];
    }

    $output = $algo($opad.pack($pack, $algo($ipad.$data)));

    return ($raw_output) ? pack($pack, $output) : $output;
}

function isBot() {

    if (isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/bot|crawl|slurp|spider/i', $_SERVER['HTTP_USER_AGENT']))
	return true;
    return false;

}

function format_numeric_field($fname, $str, $ext) {
	
	if(!is_numeric($str)) return $str;

	// get format
	$arr_ext=explode("|",$ext);
	$decimals=$arr_ext[0];
	if(!$decimals) $decimals=0;
	$point=$arr_ext[1];
	$th_separator=$arr_ext[2];
		
	$result = number_format($str, $decimals, $point, $th_separator);

	return $result;
}

function mysqli_installed() {

    if(function_exists('mysqli_connect'))
	return 1;
    return 0;
    
}

function raw_json_encode($input, $flags = 0) {
    $fails = implode('|', array_filter(array(
        '\\\\',
        $flags & JSON_HEX_TAG ? 'u003[CE]' : '',
        $flags & JSON_HEX_AMP ? 'u0026' : '',
        $flags & JSON_HEX_APOS ? 'u0027' : '',
        $flags & JSON_HEX_QUOT ? 'u0022' : '',
    )));
    //$pattern = "/\\(?:(?:$fails)(*SKIP)(*FAIL)|u([0-9a-fA-F]{4}))/";
    return preg_replace_callback($pattern, "js_hed", json_encode($input, $flags));
}

function js_hed($m) {
return html_entity_decode("&#x$m[1];", ENT_QUOTES, 'UTF-8');
}

function getRemoteIp() {

	if(isset($_SERVER['HTTP_X_REAL_IP']) && $_SERVER['HTTP_X_REAL_IP'])
		return $_SERVER['HTTP_X_REAL_IP'];
	
    return $_SERVER['REMOTE_ADDR'];

}

function slug($string)
{
    return strtolower(trim(preg_replace('~[^0-9a-z]+~i', '-', html_entity_decode(preg_replace('~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i', '$1', htmlentities($string, ENT_QUOTES, 'UTF-8')), ENT_QUOTES, 'UTF-8')), '-'));
}

function utf8_encode_all($dat) // -- It returns $dat encoded to UTF8
{
  if (is_string($dat)) return utf8_encode($dat);
  if (!is_array($dat)) return $dat;
  $ret = array();
  foreach($dat as $i=>$d) $ret[$i] = utf8_encode_all($d);
  return $ret;
} 

function is_robot() {

  return (
    isset($_SERVER['HTTP_USER_AGENT'])
    && preg_match('/bot|crawl|slurp|spider|mediapartners/i', $_SERVER['HTTP_USER_AGENT'])
  );
}
?>
