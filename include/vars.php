<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

// search engine fiendly links as they should appear on site
// used only when Settings / Seo Settings "Enable search engine friendly URLs" option is enabled
// if these values are changed, the corresponding link names should also be changed in the .htaccess file
$sef_links = array ("contact" => "contact.html", 
		"content" => "content.html",
		"details" => "details.html",
		"favorites" => "favorites.html",
		"index" => "index.html",
		"listings" => "listings.html",
		"login" => "login.html",
		"logout" => "logout.html",
		"pre_register" => "pre-register.html",
		"pre_submit" => "pre-submit.html",
		"recent_ads" => "recent_ads.html",
		"register" => "register.html",
		"store" => "store.html",
		"user_listings" => "user_listings.html",
		"refine" => "refine.html", 
		"contact_details" => "contact_details.html" ,
		"featured_ads" => "featured_ads.html",
		"auctions" => "auctions.html"
);

// the word that specifies a keyword in the search process
// will show up in urls
$keyword_name = "keyword";

// lenght of a page title 
$title_length = "65";

// length for a page meta description
$meta_description_length = "250";

// length for a page meta keywords
$meta_keywords_length = "250";

// maximum results that will be shown on a search page
// default 20000, for high numbers the last pages on a search will load slow
$max_results = "20000";

// categories list in footer will have this number of letters on a line
$config_footer_categ_len = 140;

// change mysql locale
// see http://dev.mysql.com/doc/refman/5.0/en/locale-support.html
$mysql_locale = "";

//bare LFs
$emails_avoid_bare_lfs = 1;

// include user info in short version of the ad
$include_user_info = 0;

// multi byte characters search
$mbyte_characters_search = 0;

// non latin characters in the URL
$non_latin_url = 0;

//8.0
// set this to 1 if your host can only examine a limited number of joins
$mysql_big_selects = 0;

// order depending fields by name or not. If 0 is set, then the depending fields will be sorted by id, not by name.
$order_dep_fields = 1;

// set to 1 if you want searched words to be shown highlited 
$mark_search_words = 1;

// search words prefix and postfix
$search_words_prefix = '<font class="search">';
$search_words_postfix = '</font>';

// search pages listing description width
$search_pages_description_width = 200;

// value for jpeg compression for listings thumbnails 
// range from 0 to 100, 0 being the least qualitative image
// use 0 to disable this parameter
$jpeg_compression=0;


$set_charset = 1;

// for systems >= php 5.4 where magic quotes are deprecated, but are compiled with --enable-magic-quotes
$force_magic_quotes = 0;

$limited_permissions = 0;

// if this is 1 the prices will be written with a space after or before currency. 
$add_space_after_currency = 0;

?>