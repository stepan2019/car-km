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


	function valid_name($str) {

		if(preg_match("/^[A-Z][[:space:]A-Z0-9._-]*[A-Z0-9]$/i", $str))	return 1;
		else return 0;

	}

	function valid_url($str)
	{

		$pattern = "/^((http|https)+(:\/\/))?([A-Za-z0-9_\-]+\.?)+((\/)([A-Za-z0-9_\-~!=.\$\*()\&)]+\.?)+(\/)?)*(\?[A-Za-z0-9_\-]+=[A-Za-z0-9_\-]+)*(\&[A-Za-z0-9_\-]+=[A-Za-z0-9_\-~!=.:\$\*()\&]+)*(\/)*$/i";
		if(preg_match($pattern, $str)) return 1;
		return 0;
	}
	function strict_url($str)
	{

		$pattern = "/^(http|https)+(:\/\/)([A-Za-z0-9_\-]+\.?)+((\/)([A-Za-z0-9_\-]+\.?)+(\/)?)*(\?[A-Za-z0-9_\-]+=[A-Za-z0-9_\-]+)*(\&[A-Za-z0-9_\-]+=[A-Za-z0-9_\-]+)*$/i";
		if(preg_match($pattern, $str)) return 1;
		return 0;
	}

	function valid_alpha($str)
	{
		if(preg_match("/^[[:alpha:]]*$/i", $str))	return 1;
		return 0;
	}

	function valid_alphanum($str)
	{
		if(preg_match("/^[[:alnum:]]*$/i", $str)) return 1;
		return 0;
	}

	function valid_digit($str)
	{
		if(preg_match("/^[[:digit:]]$/", $str)) return 1;
		return 0;
	}

	function valid_numeric($str)
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

	function valid_price($str)
	{

		global $appearance_settings;
		$point = $appearance_settings['price_format_point'];
		$separator = $appearance_settings['price_format_separator'];
		$ereg_str = "/^([0-9]+)?(".$separator.")?([0-9]+)?(".$point.")?([0-9]+)?$/";

		$ereg_str = str_replace(".", "\.", $ereg_str);
		if(preg_match($ereg_str, $str)) return 1;

		//if(is_numeric($str)) return 1;

		return 0;

	}

	function valid_pcre($str,$pcre)
	{
		if(preg_match($pcre, $str)) return 1;
		return 0;
	}

	function valid_email($str)
	{
		if(preg_match("/^[A-Z0-9._%-]+@[A-Z0-9._%-]+\.[A-Z]{2,6}$/i", $str)) return 1;
		return 0;
	}

	function valid_date($str)
	{
		// !!!!!!!
		return 1;
	}

	function valid_comma_separated($str)
	{
		if(preg_match ('/^([A-Z0-9]+,?)([A-Z0-9]+,?)+$/i', $str)) return 1;
		return 0;
	}

	function valid_length($str) {
		if(preg_match("/^[0-9]+X+[0-9]+$/i", $str)) return 1;
		return 0;
	}

	function valid_youtube($str) {

	$pattern = '/^<object( width="\d+")?( height="\d+")?><param name="movie" value="http:\/\/(www\.)?youtube(-nocookie)?.com\/(.*)<\/object>$/i';

	$pattern_iframe = '/^<iframe(.*)( src="http:\/\/(www\.)?youtube(-nocookie)?.com\/(.*)")(.*)><\/iframe>$/i';

	if(preg_match($pattern, $str) || preg_match($pattern_iframe, $str)) return 1;

	return 0;

	}

	function valid_gmaps($str) {

		return 1;

	}

	// must be at least 2 characters long, no spaces
	function valid_username($str) {

		if(strlen($str)<2) return 0;
		//if(preg_match("/^[A-Z0-9]+[A-Z0-9._-]+[A-Z0-9]$/i", $str)) return 1;
		if(preg_match("/^[A-Z0-9]+[A-Z0-9àáèéìíòóùúãõâêôç._-]+[A-Z0-9]$/i", $str)) return 1;
		return 0;
	}

	function valid_alias($str) {

		if(preg_match("/^[A-Z0-9]+[A-Z0-9._-]*[A-Z0-9]$/i", $str)) return 1;
		return 0;
	}

	function isImage($str) {

		$array_ext = array ("png", "jpg", "jpeg", "gif");
		$ext = getExtension($str);
		if(in_array($ext, $array_ext)) return 1;
		return 0;

	}
}
?>
