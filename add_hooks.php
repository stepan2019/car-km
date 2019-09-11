<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/
// modules directory 
global $config_abs_path;
$modules_dir = $config_abs_path."/modules/";  

// get all modules files
$modules_files = glob($modules_dir."*.php");

// default hooks
global $hooks;
$hooks = array(   "include" => array(), 
				"init_template" => array(), 
				"search_keyword" => array(), 
				"periodic" => array(), 
				"first_page" => array(), 
				"details_page"=> array(), 
				"user_page" => array(), 
				"inc_cat" => array(), 
				"dec_cat" => array(), 
				"search_meta" => array(), 
				"authenticate" => array(), 
				"newad" => array(),
				"renewad" => array(),
				"add_language" => array(), 
				"delete_language" => array(), 
				"search1" => array(),
				"cached_object_before_read" => array(), 
				"cached_object_before_write" => array(), 
				"common1" => array(), 
				"search_listing_query" =>array(), // listings:: getShortListings()
				"delete_listing" =>array(), 
				"delete_user" =>array(), 
				"post_ad_verification" => array(), 
				"listings_page" =>array(), // pages where multiple listings are present, like search page, favorites, etc
				"listing_result" => array(), // listings::getListing() before returning the result array
				"short_listings_result" =>array(), // listings::getShortListings() before returning the result array
				"detailed_listings_result" =>array(), // listings::getListingsDetailed before returning the result array
				"start_post_ad" => array(), // listings_process::add() start
				"end_post_ad" => array(), // listings_process::add() at finish
				"end_edit_ad" => array(), //  listings_process::edit() at finish
				"search_listing_order" => array(), // listings:: getShortListings(),
				"details_not_owner" => array(), // details.php if not listing owner or admin
				"init_admin_template" => array(),
				"security_periodic" => array(),
				"mailto_post" => array(),
				"register_post" => array(),
				"nologin_ad_post" =>array(),
				"comments_post" => array(),
				"reviews_post" => array(),
				"price_drop_alert_post" => array(),
				"check_price_field" => array(),
 				"listing_picture"=> array(),
				"payment_add_action" => array(),
				"payment_identify_paid_upgrade"=>array(),
				"payment_enable_extra_feature"=>array(),
				"payment_set_info_str"=>array(),
				"payment_add_to_message"=>array(),
				"payment_actions_make_actions_str" =>array(),
				"make_invoice_payment_details" => array(),
				"newad_form" => array(), // new listing and edit listing forms
				"add_search_location_fields" => array(), // make changes to the location_fields string when adding a search
				"set_meta_info" => array(),
				"upload_listing_image"=>array(), // upload function in images class, listing image type
				"set_meta_info_end"=>array(),
 				"add_user" => array(),
 				"add_contact" => array(), // when creating contact on listings without an account
 				"init_seo" => array(),
 				"start_edit_ad" => array(),
				"activate_ad" => array(),
				"activate_ad2" => array(),
				"activate_pending_ad" => array(),
				"deactivate_ad" => array(),
				"renew_ad" => array(),
				"claim_listing_post" => array(),
				"search_init_template"=>array(),
				"add_user_group"=>array(),
				"edit_user_group"=>array(),
				"get_user"=>array(),
				"get_users"=>array(),
				"my_listings"=>array(),
				"userstats"=>array(),
				"users_list"=>array(),
				"users_list_search"=>array()

	);

// add_filter functionality
function add_filter($action, $function, $priority = 5) {

	global $hooks;
	$idx = count($hooks[$action]);
	array_push($hooks[$action], array("function" => $function));

}

function add_action($action, $function, $priority = 5) {

	add_filter($action, $function, $priority);

}

// include module files 
global $config_abs_path;
foreach($modules_files as $hook_file) {  
	require_once($hook_file);
}

function do_action($action, $args) {

	global $hooks;

	// order hooks by priority !!!!!! 

	foreach($hooks[$action] as $hook ) {

		call_user_func_array($hook["function"], $args);

	}

}

?>