<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class validator {

	public function __construct()
	{
	
	}

	static function valid_name($str) {

		if(preg_match("/^[A-Z][[:space:]A-Z0-9._-]*[A-Z0-9]$/i", $str))	return 1;
		else return 0;

	}

	static function valid_url($str)
	{

		//$pattern = "/^((http|https)+(:\/\/))?([A-Za-z0-9_\-]+\.?)+((\/)([A-Za-z0-9_\-~!=.\$\*()\&)]+\.?)+(\/)?)*(\?[A-Za-z0-9_\-]+=[A-Za-z0-9_\-]+)*(\&[A-Za-z0-9_\-]+=[A-Za-z0-9_\-~!=.:\$\*()\&]+)*(\/)*$/i";

		//$pattern = "/^((http|https)+(:\/\/))?([A-Za-z0-9_-]+\.[A-Za-z0-9_-]+)(.)*$/i";

		//if(preg_match($pattern, $str)) return 1;
		
		if(filter_var($str, FILTER_VALIDATE_URL) == true ) return 1;
		
		return 0;
	}

	static function strict_url($str)
	{

		$pattern = "/^(http|https)+(:\/\/)([A-Za-z0-9_\-]+\.?)+((\/)([A-Za-z0-9_\-]+\.?)+(\/)?)*(\?[A-Za-z0-9_\-]+=[A-Za-z0-9_\-]+)*(\&[A-Za-z0-9_\-]+=[A-Za-z0-9_\-]+)*$/i";
		if(preg_match($pattern, $str)) return 1;
		return 0;
	}

	static function valid_alpha($str)
	{
		if(preg_match("/^[[:alpha:]]*$/i", $str))	return 1;
		return 0;
	}

	static function valid_alphanum($str)
	{
		if(preg_match("/^[[:alnum:]]*$/i", $str)) return 1;
		return 0;
	}

	static function valid_digit($str)
	{
		if(preg_match("/^[[:digit:]]$/", $str)) return 1;
		return 0;
	}

	static function valid_numeric($str)
	{

		global $appearance_settings;
		$point = $appearance_settings['number_format_point'];
		$separator = $appearance_settings['number_format_separator'];
		$ereg_str = "/^([0-9]+)?(".$separator.")?([0-9]+)?(".$point.")?([0-9]+)?$/";

		$ereg_str = str_replace(".", "\.", $ereg_str);
		if(preg_match($ereg_str, $str)) return 1;

		//if(is_numeric($str)) return 1;

		return 0;

	}

	static function valid_price($str)
	{

		global $appearance_settings;
		$point = $appearance_settings['price_format_point'];
		$separator = $appearance_settings['price_format_separator'];
// 		$ereg_str = "/^([0-9]+)?(".$separator.")?([0-9]+)?(".$point.")?([0-9]+)?$/";
		$ereg_str = "/^(([0-9]+)?(".$separator.")?)+?([0-9]+)?(".$point.")?([0-9]+)?$/";

		$ereg_str = str_replace(".", "\.", $ereg_str);
		if(preg_match($ereg_str, $str)) return 1;

		//if(is_numeric($str)) return 1;

		return 0;

	}

	static function valid_pcre($str,$pcre)
	{
		if(preg_match($pcre, $str)) return 1;
		return 0;
	}

	static function valid_email($str)
	{
		//if(preg_match("/^[A-Z0-9._%-]+@[A-Z0-9._%-]+\.[A-Z]{2,6}$/i", $str)) return 1;
		if(filter_var($str, FILTER_VALIDATE_EMAIL) == true ) return 1;
		return 0;
	}

	static function valid_date($str)
	{
		// !!!!!!!
		return 1;
	}

	static function valid_comma_separated($str)
	{
		if(preg_match ('/^([A-Z0-9]+,?)([A-Z0-9]+,?)+$/i', $str)) return 1;
		return 0;
	}

	static function valid_length($str) {
		if(preg_match("/^[0-9]+X+[0-9]+$/i", $str)) return 1;
		return 0;
	}

	static function valid_youtube($str) {

		global $config_abs_path;
		require_once $config_abs_path."/include/patterns.php";

		global $pattern_youtube_embed, $pattern_youtube_iframe_embed, $pattern_youtube_url, $pattern_short_youtube_url, $pattern_youtube_default_embed_code;

		if(preg_match($pattern_youtube_embed, $str) || preg_match($pattern_youtube_iframe_embed, $str) || preg_match($pattern_youtube_url, $str) || preg_match($pattern_short_youtube_url, $str) || preg_match($pattern_youtube_default_embed_code, $str)) return 1;

		return 0;

	}

	static function valid_gmaps($str) {

		return 1;

	}

	// must be at least $min_username_width characters long, no spaces
	static function valid_username($str) {

		global $config_abs_path;
		require_once $config_abs_path."/include/patterns.php";
		global $pattern_username, $min_username_width;
		if(strlen($str)<$min_username_width) return 0;
		if(@preg_match($pattern_username, $str)) { 
			return 1;
		}
		elseif(@preg_match($pattern_username2, $str)) { 
			return 1;
		}
		return 0;
	}

	static function valid_alias($str) {

		if(preg_match("/^[A-Z0-9]+[A-Z0-9._-]*[A-Z0-9]$/i", $str)) return 1;
		return 0;
	}

	static function isImage($str) {

		$array_ext = array ("png", "jpg", "jpeg", "gif");
		$ext = getExtension($str);
		if(in_array(strtolower($ext), $array_ext)) return 1;
		return 0;

	}

	static function valid_ip($str) {
	
		if(filter_var($str, FILTER_VALIDATE_IP) == true ) return 1;
		return 0;
		
	}

	static function valid_twitter($str) {
	
		return preg_match('/^(@)?[A-Za-z0-9_]{1,15}$/', $str);
		
	}

}
?>
