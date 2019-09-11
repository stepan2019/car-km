<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/


	// youtube video accepted patterns
	global $pattern_youtube_embed;
	$pattern_youtube_embed = '/^<object( width="\d+")?( height="\d+")?><param name="movie" value="http:\/\/(www\.)?youtube(-nocookie)?.com\/(.*)<\/object>$/i';

	global $pattern_youtube_iframe_embed;
	$pattern_youtube_iframe_embed = '/^<iframe(.*)( src="http:\/\/(www\.)?youtube(-nocookie)?.com\/(.*)")(.*)><\/iframe>$/i';

	global $pattern_youtube_url;
	$pattern_youtube_url = "/^http(s)?:\/\/(?:www\.)?youtube(-nocookie)?.com\/watch\?(?=.*v=\w+)(?:\S+)?$/i";

	global $pattern_short_youtube_url;
	$pattern_short_youtube_url = "/^http(s)?:\/\/(?:www\.)?youtu.be\/(.*)$/i";

	global $pattern_youtube_default_embed_code;
	$pattern_youtube_default_embed_code = '/^<iframe( width="\d+")?( height="\d+")? src="(http:)?(https:)?\/\/www.youtube.com\/embed\/(\S+)"( frameborder="0")?( allowfullscreen)?><\/iframe>$/i';

	global $youtube_default_embed_code;
	$youtube_default_embed_code = '<iframe width="%width" height="%height" src="http://www.youtube.com/embed/%video" frameborder="0" allowfullscreen></iframe>';

	// username regexp pattern
	global $pattern_username;
	$pattern_username = "@^[\p{L}\w-_.]+$@u";
	$pattern_username2 = "/^[A-Z0-9]+[A-Z0-9._-]+[A-Z0-9]$/i";
	// username minimum width
	global $min_username_width;
	$min_username_width = 2;

	function formatVideo($str) {

		global $pattern_youtube_url, $pattern_short_youtube_url, $youtube_default_embed_code;
		global $ads_settings, $mobile_settings;
		global $is_mobile;
		
		if(!$is_mobile || !$mobile_settings['enable_mobile_templates']) {
			$w = $ads_settings['big_thmb_width'];
			$h = $ads_settings['big_thmb_height'];
		} else {
			$w = $mobile_settings['mobile_big_thmb_width'];
			$h = $mobile_settings['mobile_big_thmb_height'];
		}
		// if the string is url format
		if(preg_match($pattern_youtube_url, $str)) {

			parse_str( parse_url( $str, PHP_URL_QUERY ) );
			$arr = array("%width" => $w, "%height" =>$h, "%video" => $v, "http://" => "https://");
			$str = str_replace(array_keys($arr), array_values($arr), $youtube_default_embed_code);
			
			return $str;

		}

		// if the string is short url format
		if(preg_match($pattern_short_youtube_url, $str)) {

			$pos = stripos($str, "youtu.be/");
			$v = substr($str, $pos+strlen("youtu.be/"));
			$arr = array("%width" => $w, "%height" =>$h, "%video" => $v, "http://" => "https://");
			$str = str_replace(array_keys($arr), array_values($arr), $youtube_default_embed_code);

			return $str;

		}

		return $str;

	}

?>