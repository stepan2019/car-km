<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class seo {

	var $links;

	public function __construct()
	{
	
	global $sef_links, $sef_links_aliases, $sef_fields_aliases, $keyword_name;
	global $seo_settings;
	if(!$seo_settings['sef_legacy_mode'])
		$this->links = $seo_settings['sef_links'];
	else 	
		$this->links = $sef_links;

	$this->keyword = $keyword_name;
	
	}

	function init(&$smarty) {

 		do_action("init_seo", array(&$this->links));
		$smarty->assign("sef_links", $this->links);
		$smarty->assign("sef_keyword", $this->keyword);

	}

	// make a SEF search category page
	// used for: first page category display links, categories path links
	function makeSearchCategoryLink ($id, $name='', $slug='') {

		global $config_live_site, $seo_settings;
		
		if(!$seo_settings['sef_legacy_mode']) {
			if(!$slug)
				$slug = slugs::getCategorySlug($id);
			$search_folder="/".$this->links["listings"];
			$search_link = $config_live_site.$search_folder."/".$slug;
			return $search_link;
		}	
		
		// legacy mode
		global $config_abs_path;
		require_once $config_abs_path."/classes/categories.php";
		if(!$name) $name = cleanHtml(categories::getName($id));
		$search_link = $config_live_site."/".$id."-"._urlencode($name)."/".$this->links["listings"];
		
		return $search_link;
		
	}

	// make a SEF index category page
	// used on first page when browse categories is enabled
	function makeBrowseCategoryLink ($id, $name='', $slug='') {

		global $config_live_site, $seo_settings;
		
		if(!$seo_settings['sef_legacy_mode']) {
			if(!$slug)
				$slug = slugs::getCategorySlug($id);
			$link = $config_live_site."/".$slug;
			return $link;
		}	
		
		// legacy mode
		global $config_abs_path;
		require_once $config_abs_path."/classes/categories.php";
		if(!$name) $name = cleanHtml(categories::getName($id));
		$link = $config_live_site."/".$id."-"._urlencode($name)."/".$this->links["index"];
		
		return $link;
		
	}

	// make a SEF search link for 1 or 2 key - value pairs
	// used for: tag cloud, browse by location and browse by make modules links
	function makeKeyValueLink ($key, $value, $key2='', $value2='') {

		global $config_live_site, $settings, $seo_settings;
		$kv1 = $kv2 = '';
		
		if(!$seo_settings['sef_legacy_mode']) {
			$search_folder="/".$this->links["listings"];
			
			if($settings['enable_subdomains'] && ($key==$settings['subdomain_field'] || $key2==$settings['subdomain_field'])) {
				
				global $config_abs_path;
				require_once $config_abs_path."/classes/locations.php";
			
				$loc = new locations;
				global $main_domain;
				if($key==$settings['subdomain_field']) {
					$loc_val = $value;
					if($key2 && $value2) $kv2 = "?".$key2."=".rawurlencode(trim($value2));
				}
				else {
					$loc_val = $value2;
					if($key && $value) $kv2 = "?".$key."=".rawurlencode(trim($value));
		    
				}
				if(strstr($config_live_site, "https://")) $proto = "https://";
				else $proto = "http://";
		
				$search_link = $proto.$loc->buildLocationSubdomain($loc_val).".".$main_domain.$search_folder."/".$kv2;
				return $search_link;

			}
			
			$kv1 = "?".$key."=".rawurlencode(trim($value));
			if($key2 && $value2) $kv2 = "&".$key2."=".rawurlencode(trim($value2));
			$search_link = $config_live_site.$search_folder."/".$kv1.$kv2;
			
			return $search_link;
			
		}	

		// legacy mode
		if($settings['enable_subdomains'] && ($key==$settings['subdomain_field'] || $key2==$settings['subdomain_field'])) {
			global $config_abs_path;
			require_once $config_abs_path."/classes/locations.php";
		    $loc = new locations;
		    global $main_domain;
		    if($key==$settings['subdomain_field']) {
			$loc_val = $value;
			if($key2 && $value2) $kv2 = "/".$key2."-".rawurlencode(trim($value2));
		    }
		    else {
			$loc_val = $value2;
			if($key && $value) $kv2 = "/".$key."-".rawurlencode(trim($value));
		    
		    }
			if(strstr($config_live_site, "https://")) $proto = "https://";
			else $proto = "http://";
		    $search_link = $proto.$loc->buildLocationSubdomain($loc_val).".".$main_domain.$kv2."/".$this->links["listings"];
		    return $search_link;
		}

		$kv1 = "/".$key."-".rawurlencode(trim($value));
		if($key2 && $value2) $kv2 = "/".$key2."-".rawurlencode(trim($value2));
		$search_link = $config_live_site.$kv1.$kv2."/".$this->links["listings"];
		return $search_link;

	}

	// make a SEF search page link using the search array values
	function makeSearchLink($search_array, $page=1, $exclude_str = '', $current = '', $category_name='') {

		// $search array
		// $exclude_str (page|category|xxx)
		// $page
		// $current ##$current## - for delete links

		global $config_live_site, $seo_settings;
		$current_str = "";
		$sign="?";
		
		if($current) $current_str .= "##$current##/";

		$exclude_array = array(); 
		if($exclude_str) $exclude_array = explode("|", $exclude_str);

		$search_folder=$this->links["listings"]."/";
		
		// start assembling the search string
		$refine_search_link = $config_live_site."/";
		if(!$seo_settings['sef_legacy_mode']) $refine_search_link.=$search_folder;
		//$refine_search_link.=$current_str;
		//echo $refine_search_link;

		if(isset($search_array) && $search_array) {

			// if category
			if(isset($search_array['category']) && $search_array['category'] && $current != "cat") { 
				if(!$seo_settings['sef_legacy_mode']) {
					$slug = slugs::getCategorySlug($search_array['category']);
					$refine_search_link.=$slug."/";
				}
				else {
					// legacy mode
					global $config_abs_path;
					require_once $config_abs_path."/classes/categories.php";
					if(!$category_name) $category_name = cleanHtml(categories::getName($search_array['category']));
					$refine_search_link.=$search_array['category']."-"._urlencode($category_name)."/";
				}
			}

			global $settings, $ads_settings;
			$array_loc = explode(",", $ads_settings['search_location_fields']);

			foreach($search_array as $key => $value) {

				if( in_array($key,array("category", "cat", "show", "lat", "long", "search_id"))) continue;
				//if($value!='' && $key==$current) { $sign="&"; continue; }
				if($value=='' || $value=='|' || in_array($key, $exclude_array)) continue;
				if(($key=="order" && $value=="date_added") || ($key=="order_way" && $value=="desc")) continue;
				if($value=='' && $key!="area") continue;
				if($settings['enable_locations'] && in_array($key, $array_loc)) continue;
				if($settings['enable_locations'] && $key=="crt_city") continue;
				//if($ads_settings['enable_location_autosuggest'] && in_array( $key, $ads_settings['address_components_fields']))  continue;
				if($key=="location") $value=str_replace(", ", "|", $value);
				$value = trim($value, '|');

				if(!$seo_settings['sef_legacy_mode']) {
					if(strstr($refine_search_link, "?")) $sign="&";
					$refine_search_link .= $sign.$key."="._urlencode( str_replace("/", "_", $value), 0);
				}
				else
					// legacy mode
					$refine_search_link .= $key."-"._urlencode( str_replace("/", "_", $value), 0)."/";

			}
	
		} // end if $search_array

		// if delete link
		if(!$seo_settings['sef_legacy_mode']) {
			if($current) { 
				$refine_search_link .= "##$current##";
				$sign="&";
			}	
			if($page>1) $refine_search_link .= $sign."page=".$page;
			if( isset($search_array['search_id']) && $search_array['search_id']) $refine_search_link.=$sign."search_id=".$search_array['search_id'];
		}
		else {
			// legacy mode
			if($current) $refine_search_link .= $current_str;
			// add page 
			if($page>1) $refine_search_link .= $page."/";
			$refine_search_link .= $this->links["listings"];
			// if search_id
			if($search_array['search_id']) $refine_search_link.="?search_id=".$search_array['search_id'];
		}
		return $refine_search_link;

	}

	// SEF search pages links
	// used on paginator
	function makeSearchPagesLink() {

		global $config_live_site, $settings, $seo_settings;

		if($seo_settings['sef_legacy_mode']) return $this->makeLegacySearchPagesLink();
		
		$current_page = 1;
		$pag_array = array("pages_link" => "", "order_by_link" =>"", "order_way_link" => "", "order_link"=>"", "crt_page_link" => "", "show_link" => "", "acctype_link" => "", "order_by" => "", "order_way" => "", "page" => "1");
		$search_array = array();

		foreach($_GET as $key=>$value) {
		
			if($key=="show") continue;
			if($key=="page" && is_numeric($_GET['page'])) { $pag_array['page'] = $_GET['page']; continue; }
			$search_array[$key] = rawurlencode($value);

		}

		$search_folder="/".$this->links["listings"]."/";
		
		$pag_array['crt_page_link'] =  $pag_array['pages_link'] = $pag_array['order_by_link'] = $pag_array['order_way_link'] = $pag_array['order_link'] = $pag_array['show_link'] = $pag_array['acctype_link'] = $config_live_site.$search_folder;

		// add category to url
		if( (isset($search_array['category']) && $search_array['category']) || (isset($search_array['category_slug']) && $search_array['category_slug'])) { 

			if(isset($search_array['category_slug']) && $search_array['category_slug'])
				$slug = $search_array['category_slug'];
			else 	
				$slug = slugs::getCategorySlug($search_array['category']);

			$str = $slug."/";
			$pag_array['crt_page_link'].=$str;
			$pag_array['pages_link'].=$str;
			$pag_array['order_by_link'].=$str;
			$pag_array['order_way_link'].=$str;
			$pag_array['order_link'].=$str;
			$pag_array['show_link'].=$str;
			$pag_array['acctype_link'].=$str;
		}

		$sign="?";

		$pag_array['order_by_link'].=$sign."order=##order##";
		$pag_array['order_way_link'].=$sign."order_way=##order_way##";
		$pag_array['order_link'].=$sign."order_way=##order_way##&order=##order##";
		$pag_array['show_link'].=$sign."show=##show##";
		$pag_array['acctype_link'].=$sign."account_type=##acctype##";
		$pag_array['pages_link'].=$sign."page=##page##";

		$sign="&";
		// add the rest of search values
		foreach($search_array as $key=>$value) {

			if($key=="category" || $key=="category_name" || $key=="category_slug" || ($key=="page" && $value==1)) continue;
			$pag_array['pages_link'].=$sign."$key=$value";
			$pag_array['crt_page_link'].= $sign."$key=$value";
			$pag_array['show_link'].=$sign."$key=$value";
			if($key!="account_type") $pag_array['acctype_link'].=$sign."$key=$value";
			if($key!="order") $pag_array['order_by_link'].=$sign."$key=$value";
			if($key!="order_way") $pag_array['order_way_link'].=$sign."$key=$value";
			if($key!="order" && $key!="order_way") $pag_array['order_link'].=$sign."$key=$value";

 			if($key=="order_way") $pag_array['order_way'] = $value;
			if($key=="order") $pag_array['order_by'] = $value;
			$sign="&";
			
		}

		// add search_id if available
		$search_id_str = "";
		$search_id = get_numeric('search_id', 0);
		if($search_id)
			$search_id_str.=$sign."search_id=".$search_id;

		return $pag_array;

	}

	function makeLegacySearchPagesLink() {

		global $config_live_site, $settings;
		$current_page = 1;
		$pag_array = array("pages_link" => "", "order_by_link" =>"", "order_way_link" => "", "order_link"=>"", "crt_page_link" => "", "show_link" => "", "acctype_link" => "", "order_by" => "", "order_way" => "", "page" => "1");
		$search_array = array();

		// make search array
		if($_SERVER['QUERY_STRING'])
		{
			$i=0;

			$query_string = $_SERVER['QUERY_STRING'];
			$args_arr = explode("&", $_SERVER['QUERY_STRING']);
			foreach ($args_arr as $a) {
				if(strstr($a, "listings.html"))  {$query_string = $a; break; }
			}
		
			$args = explode("/", $query_string);


			$get_array = array();
			foreach($args as $arg)
			{
				// page will stand out as a numeric value separately written
				if(is_numeric($arg)) { 
					$pag_array["page"] = $arg; 
					$i++; continue; 
				}

				// if the first argument, check if category is set
				if($i==0) {
					$keyval = explode("-",$arg,2);
					if(is_numeric($keyval[0])) { 
						$search_array['category'] = $keyval[0]; 
						$search_array['category_name'] = $keyval[1];
						$i++; 
						continue; 
					}
				}

				$keyval = explode("-",$arg,2);
		
				if(count($keyval)>1) {
					$key = $keyval[0];
					if($key=="show") continue;
					$value = rawurlencode($keyval[1]); // 
					$search_array[$key] = $value;
					$i++;
				}
			}
		}

		$pag_array['crt_page_link'] =  $pag_array['pages_link'] = $pag_array['order_by_link'] = $pag_array['order_way_link'] = $pag_array['order_link'] = $pag_array['show_link'] = $pag_array['acctype_link'] = $config_live_site."/";

		// add category to url
		if( isset($search_array['category']) && $search_array['category']) { 
			$str = $search_array['category']."-".$search_array['category_name']."/";
			$pag_array['crt_page_link'].=$str;
			$pag_array['pages_link'].=$str;
			$pag_array['order_by_link'].=$str;
			$pag_array['order_way_link'].=$str;
			$pag_array['order_link'].=$str;
			$pag_array['show_link'].=$str;
			$pag_array['acctype_link'].=$str;
		}

		// add the rest of search values
		foreach($search_array as $key=>$value) {

			if($key=="category" || $key=="category_name" || $key=="page") continue;
			$pag_array['pages_link'].="$key-$value/";
			$pag_array['crt_page_link'].= "$key-$value/";
			$pag_array['show_link'].="$key-$value/";
			if($key!="account_type") $pag_array['acctype_link'].="$key-$value/";
			if($key!="order") $pag_array['order_by_link'].="$key-$value/";
			if($key!="order_way") $pag_array['order_way_link'].="$key-$value/";
			if($key!="order" && $key!="order_way") $pag_array['order_link'].="$key-$value/";

			if($key=="order_way") $pag_array['order_way'] = $value;
			if($key=="order") $pag_array['order_by'] = $value;

		}

		$pag_array['order_by_link'].="order-##order##/";
		$pag_array['order_way_link'].="order_way-##order_way##/";
		$pag_array['order_link'].="order_way-##order_way##/order-##order##/";
		$pag_array['show_link'].="show-##show##/";
		$pag_array['acctype_link'].="account_type-##acctype##/";

		// add page to url
		if($current_page!=1) {
			$pag_array['crt_page_link'].=$pag_array["page"]."/";
			$pag_array['order_by_link'].=$pag_array["page"]."/";
			$pag_array['order_way_link'].=$current_page."/";
			$pag_array['order_link'].=$current_page."/";
			$pag_array['show_link'].=$current_page."/";
			$pag_array['acctype_link'].=$current_page."/";
		}
		$pag_array['pages_link'].="##page##/";

		// add search_id if available
		$search_id_str = "";
		$search_id = get_numeric('search_id', 0);
		if($search_id)
			$search_id_str.="?search_id=".$search_id;

		// add html page name
		$pag_array['crt_page_link'].=$this->links["listings"].$search_id_str;
		$pag_array['pages_link'].=$this->links["listings"].$search_id_str;
		$pag_array['order_by_link'].=$this->links["listings"].$search_id_str;
		$pag_array['order_way_link'].=$this->links["listings"].$search_id_str;
		$pag_array['order_link'].=$this->links["listings"].$search_id_str;
		$pag_array['show_link'].=$this->links["listings"].$search_id_str;
		$pag_array['acctype_link'].=$this->links["listings"].$search_id_str;

		
		return $pag_array;

	}
	// SEF details page link 
	function makeDetailsLink($id, $title='', $search_id=0, $category_id=0, $slug='') {

		global $seo_settings, $config_live_site;
		if(!$seo_settings['sef_legacy_mode']) {
			if(!isset($slug) || !$slug) $slug = slugs::getListingSlug($id);
			if(!isset($category_id) || !$category_id) $category_id = listings::getCategory($id);
			$category_folder=slugs::getCategorySlug($category_id);
			//!!!!!!!!!!!!!!!!!!
			if(!$category_folder) $category_folder="c";
			$details_link = $config_live_site."/".$category_folder."/".$slug;
			return $details_link;
		}	

		// legacy mode
		if(isset($title) && $title) $url_title = _urlencode($title);
		else { 
			global $config_abs_path;
			require_once $config_abs_path."/classes/listings.php";
			$url_title = listings::getUrlTitle($id);
		}

		global $config_live_site;
		$details_link=$config_live_site."/".$id."-".$url_title."/".$this->links["details"];
		
		//if(isset($search_id) && $search_id) $details_link.="?search_id=".$search_id;

		return $details_link;
	}


	// SEF subdomain details page link 
	function makeSubdomainDetailsLink($id, $title='', $subdomain='', $cannonical=0, $category_id=0) {

		global $settings, $config_live_site;
		$cloc = new locations();
		if($subdomain) $url_subdomain = $cloc->buildLocationSubdomain($subdomain);
		else {
			global $db; 
			$url_subdomain = $db->fetchRow("select `{$settings['location_subdomain']}` from ".TABLE_ADS." where id='$id'");
		}

		// check if not added already
		if(stristr($config_live_site, $url_subdomain)) {
			// if subdomain is included cannonical url is not needed
			if($cannonical)  return '';
			
			$subdomain_link = $config_live_site;
		}
		else {
		
			if(strstr($config_live_site, "https://")) $proto = "https://";
			else $proto = "http://";

			global $main_domain;
			if(stristr($config_live_site, "www"))
				$subdomain_link = str_replace($proto."www.", $proto.$url_subdomain.".", $proto.$main_domain);
			else 
				$subdomain_link = str_replace($proto, $proto.$url_subdomain.".", $proto.$main_domain);
		}

		global $seo_settings, $config_live_site;
		if(!$seo_settings['sef_legacy_mode']) {
		
			$slug = slugs::getListingSlug($id);
			if(!$category_id) $category_id = listings::getCategory($id);
			$category_folder=slugs::getCategorySlug($category_id);
			$details_link = $subdomain_link."/".$category_folder."/".$slug;
			
			return $details_link;
			
		}	

		// legacy mode
		if($title) $url_title = _urlencode($title);
		else $url_title = listings::getUrlTitle($id);
		
		$details_link=$subdomain_link."/".$id."-".$url_title."/".$this->links["details"];

		return $details_link;
	}

	function makeSubdomainSearchCategoryLink($id, $name='', $subdomain='') {

		global $settings, $config_live_site;
		$cloc = new locations();
		$url_subdomain = $cloc->buildLocationSubdomain($subdomain);

		// check if not added already
		if(stristr($config_live_site, $url_subdomain)) {
			// if subdomain is included cannonical url is not needed
			if($cannonical)  return '';
			
			$subdomain_link = $config_live_site;
		}
		else {
		
			if(strstr($config_live_site, "https://")) $proto = "https://";
			else $proto = "http://";

			global $main_domain;
			if(stristr($config_live_site, "www"))
				$subdomain_link = str_replace($proto."www.", $proto.$url_subdomain.".", $proto.$main_domain);
			else 
				$subdomain_link = str_replace($proto, $proto.$url_subdomain.".", $proto.$main_domain);
		}

		global $seo_settings, $config_live_site;
		if(!$seo_settings['sef_legacy_mode']) {
		
			$slug = slugs::getCategorySlug($id);
			$search_folder="/".$this->links["listings"];
			$search_link = $subdomain_link."/".$search_folder."/".$slug;
			return $search_link;
			
		}	

		// legacy mode
		
		if(!$name) {
			require_once $config_abs_path."/classes/categories.php"; 
			$name = cleanHtml(categories::getName($id));
		}
		$search_link = $config_live_site."/".$id."-"._urlencode($name)."/".$this->links["listings"];

		return $search_link;
	}
	// SEF dealer page link
	function makeDealerLink($id, $dealer_name='', $page='', $canonical=0) {

		// subdomain dealer link
		global $ads_settings;
		$usr = new users();
		if($ads_settings['dealer_subdomain'] && $usr->allowStoreBanner($id)) { 
			$subdomain = $usr->getDealerSubdomain($id);
			
			global $main_domain, $config_live_site; 

			if(strstr($config_live_site, "https://")) $proto = "https://";
			else $proto = "http://";

			$site_url = $proto.$subdomain.".".$main_domain;

			$page_str="";
			if($page) $page_str=$page."/";
			$dealer_link = $site_url."/".$page_str;
			
			return $dealer_link;
		}
		
		// non subdomain dealer link
		if($dealer_name) $url_dealer = _urlencode($dealer_name);
		else $url_dealer = _urlencode(users::getContactName($id));

		global $config_live_site;
		$site_url = $config_live_site;

		if(strstr($config_live_site, "https://")) $proto = "https://";
		else $proto = "http://";

		if($canonical) { 
			global $main_domain; 
			$site_url = $proto.$main_domain; 
		}

		global $seo_settings;
		
		if(!$seo_settings['sef_legacy_mode']) {
			$slug = slugs::getUserSlug($id);
			$users_folder="/".$this->links["store"];
			$dealer_link = $config_live_site.$users_folder."/".$slug;
			if($page) $dealer_link.="?page=".$page;
			return $dealer_link;
		}	

		$page_str="";
		if($page) $page_str=$page."/";
		$dealer_link=$site_url."/".$id."-".$url_dealer."/".$page_str.$this->links["store"];

		return $dealer_link;

	}

	// SEF dealer page link
	function makeUserListingsLink($id, $dealer_name='', $page='', $canonical=0) {

		global $config_live_site, $seo_settings;
		
		if(!$seo_settings['sef_legacy_mode']) {
			$slug = slugs::getUserSlug($id);
			$users_folder="/".$this->links["user_listings"];
			$dealer_link = $config_live_site.$users_folder."/".$slug;
			if($page) $dealer_link.="?page=".$page;
			return $dealer_link;
		}	

		// legacy mode
		if($dealer_name) $url_dealer = _urlencode($dealer_name);
		else $url_dealer = _urlencode(users::getContactName($id));

		global $config_live_site;
		$site_url = $config_live_site;
		
		if(strstr($config_live_site, "https://")) $proto = "https://";
		else $proto = "http://";

		if($canonical) { global $main_domain; $site_url = $proto.$main_domain; }
		$page_str="";
		if($page) $page_str=$page."/";
		$dealer_link=$site_url."/".$id."-".$url_dealer."/".$page_str.$this->links["user_listings"];

		return $dealer_link;

	}

	// SEF contact details page link
	function makeUserDetailLink($id, $contact_name='', $canonical=0) {

		global $config_live_site, $seo_settings;

		if(strstr($config_live_site, "https://")) $proto = "https://";
		else $proto = "http://";
		
		$site_url = $config_live_site;
		if($canonical) { global $main_domain; $site_url = $proto.$main_domain; }
		
		if(!$seo_settings['sef_legacy_mode']) {
		
			$slug = slugs::getUserSlug($id);
			$users_folder="/".$this->links['contact_details'];
			$contact_link = $site_url.$users_folder."/".$slug;
			
			return $contact_link;
			
		}	

		// legacy mode
		if($contact_name) $url_contact = _urlencode($contact_name);
		else $url_contact = _urlencode(users::getContactName($id));
		$contact_link=$site_url."/".$id."-".$url_contact."/".$this->links["contact_details"];

		return $contact_link;

	}

	// SEF affiliate link
	function makeAffiliateLink($user_id, $affiliate_id='') {

		if(!$affiliate_id) $affiliate_id = users::getAffiliateId($user_id);
		global $config_live_site, $seo_settings;
		$link = $config_live_site."/";
		if($seo_settings['enable_mod_rewrite']) {
			if(!$seo_settings['sef_legacy_mode']) {
				$affiliate_folder=$this->links['affiliate']."/";
				$slug = slugs::getUserSlug($user_id);
				$link.=$affiliate_folder.$slug;
			}
			else 
				$link.="aff-".$affiliate_id;
		}
		else $link.="?aff=".$affiliate_id;
		
		return $link;
	}

	function makeCustomPageLink ($id, $title='', $slug='') {

		global $config_live_site, $seo_settings;
		
		if(!$seo_settings['sef_legacy_mode']) {
			if(!isset($slug) || !$slug)	$slug = slugs::getCustomPageSlug($id);
			$content_folder="";
			if($this->links['content'])
				$content_folder="/".$this->links['content'];
			$content_link = $config_live_site.$content_folder."/".$slug;
			return $content_link;
		}	
		
		// legacy mode
		global $config_abs_path;
		require_once $config_abs_path."/classes/custom_pages.php";
		if(!$title) {
			$cp = new custom_pages();
			$title = $cp->getTitle($id);
		}
		$content_link = $config_live_site."/".$id."-"._urlencode($title)."/".$this->links["content"];
		
		return $content_link;
		
	}


	// set meta info 
	function setMetaInfo($crt_page, $smarty) {

		global $page_info, $seo_settings, $settings, $cat, $meta_format;

		// get meta information pattern
		// will be used to get the page meta information, based on the current page type
		$meta_formats = common::getCachedObject("base_meta_info");

		// only content pages don't need processing
		if( !isset($meta_formats[$crt_page]) && $crt_page!="content") {

			$page_info=array( 'title' => '', 'meta_keywords' => '', 'meta_description' => '', 'canonical' => '', 'noindex' => '', 'canonical' => '' );
			$smarty->assign("page_info",$page_info);
			
			do_action("set_meta_info", array(&$page_info, $smarty));

			return;
		}

		if($crt_page!="content") {

			$meta_format = $meta_formats[$crt_page];
			$smarty->assign("seo_settings",$seo_settings);

			$page_info = $meta_format;

		}

		if(!isset($page_info['title'])) $page_info['title']=$settings['site_name'];
		if(!isset($page_info['meta_keywords'])) $page_info['meta_keywords']='';
		if(!isset($page_info['meta_description'])) $page_info['meta_description']='';
		if(!isset($page_info['noindex'])) $page_info['noindex'] = '';
		if(!isset($page_info['canonical'])) $page_info['canonical'] = ''; 

		global $no_tags, $md_no_tags,$mk_no_tags;
		$no_tags = 0; $md_no_tags=0; $mk_no_tags=0;
		// set location values first
		$page_info = $this->replaceLocationMetaInfo($page_info);

		switch($crt_page) {
			case "details":
				if(isset($_GET['listing_slug']) && $_GET['listing_slug']) {
					$id = slugs::getListingId(escape($_GET['listing_slug']));
				}
				else 
					$id = get_numeric("id", 0);
				if(!$id) break;	
				$page_info = $this->getListingMetaInfo(escape($id));
				break;

			case "listings":
				$page_info= $this->getSearchMetaInfo($smarty);
				break;

			case "content":
				if(isset($_GET['cp_slug']) && $_GET['cp_slug']) {
					$id = slugs::getCustomPageId(escape($_GET['cp_slug']));
				}
				else 
				$id = get_numeric("id"); 
				
				if(!$id) break;	

				// get page content meta info
				$page_info = $this->getCustomPageMetaInfo(escape($id));
				break;

			case "index":

				if($cat) {
					$page_info = $this->getCategoryMetaInfo($cat);
				}

				break;

			case "recent_ads":
				$page_info = $this->getRecentAdsMetaInfo();
				break;

			case "contact_details":
			case "store":
			case "user_listings":

				if(isset($_GET['user_slug']) && $_GET['user_slug']) {
					$id = slugs::getUserId(escape($_GET['user_slug']));
				}
				else $id = get_numeric("id");

				if(!$id) break;	
				$page_info = $this->getUserPageMetaInfo(escape($id));
				break;

			case "login":
			case "register":
			case "pre-register":
			case "pre-submit":
			case "refine":
			case "notfound":
			case "contact":
			case "favorites":

				// no replacements needed, use the default meta info

				// canonical
				$canonical_subdomains = 0;
				if($settings['enable_locations'] && $settings['enable_subdomains'] && isset($_GET['crt_city']) && $_GET['crt_city']) $canonical_subdomains = 1;
				if($canonical_subdomains) { 

					global $main_domain, $sef_links; 
					
					if(strstr($config_live_site, "https://")) $proto = "https://";
					else $proto = "http://";

					$site_url = $proto.$main_domain; 

					if($seo_settings['enable_mod_rewrite']) {
						$page_info['canonical'] = $site_url."/".$sef_links[$crt_page];

					} else 

						$page_info['canonical'] = $site_url."/".$crt_page.".php";

				} // end canonical subdomains

				break;

			default:
				break;

		}
//_print_r($page_info);
		// interpret smarty tags
		$smarty->error_reporting = E_ALL & ~E_NOTICE;
		//$smarty->debugging = true;
		//if($crt_page=="listings" || $crt_page=="index") {
		
			$page_info['title'] = $smarty->fetch('string:'.$page_info['title']);
			
			str_replace("\$notag", "\$md_notag", $page_info['meta_description']);
			
			$page_info['meta_description'] = $smarty->fetch('string:'.$page_info['meta_description']);
			
			str_replace("\$notag", "\$mk_notag", $page_info['meta_keywords']);
			
			$page_info['meta_keywords'] = $smarty->fetch('string:'.$page_info['meta_keywords']);
			
		//}
		
		global $title_length, $meta_description_length, $meta_keywords_length;
		//$page_info['title'] = start_words($page_info['title'], $title_length);

		$page_info['meta_keywords'] = start_words($page_info['meta_keywords'], $meta_keywords_length);

		$page_info['meta_description'] = start_words($page_info['meta_description'], $meta_description_length);

		if($crt_page!="content") {
			// clean invalid %key tags
			$page_info = $this->cleanMetaInfo();
		}

		do_action("set_meta_info_end", array(&$page_info, $smarty));
		
		$smarty->assign("page_info",$page_info);

	}

	// set meta info for details page
	function getListingMetaInfo($id) {
		
		global $db;
		global $ads_settings, $seo_settings, $settings, $page_info;
		global $crt_lang;
		$arr = array( 'title', 'meta_keywords', 'meta_description', 'canonical', 'noindex' );
		foreach($arr as $f)
			if(!isset($page_info[$f])) $page_info[$f]='';

		// if translate title & description
		$title_field ="`title`";
		$description_field ="`description`";
		if($ads_settings['translate_title_description']) {
			global $languages;
			if(empty($languages)) $languages = common::getCachedObject("base_languages");
			if(count($languages)>1) {
				$title_field = "`title_$crt_lang` as `title`";
				$description_field = "`description_$crt_lang` as `description`";
			}
		}

// LOCATION SUBDOMAINS
$location_str='';
if($settings['enable_locations'] && $settings['enable_subdomains']) {
	$location_str=", {$settings['subdomain_field']}";
}

		// get all fields from title format
		$matches = array();
		preg_match_all("/%[a-z_0-9]*/i", $page_info['title'], $matches);
		$title_fields_array = $matches[0];

		// get all fields from meta keywords format
		$matches = array();
		preg_match_all("/%[a-z_0-9]*/i", $page_info['meta_keywords'], $matches);
		$meta_keywords_fields_array = $matches[0];

		// get all fields from meta description format
		$matches = array();
		preg_match_all("/%[a-z_0-9]*/i", $page_info['meta_description'], $matches);
		$meta_description_fields_array = $matches[0];

		// possible fields
		$possible_fields = $db->getTableFields(TABLE_ADS);
		if(!in_array("title", $possible_fields)) array_push($possible_fields, "title");
		array_push($possible_fields, "category_name");
		array_push($possible_fields, "category_title");
		array_push($possible_fields, "category_path");
		array_push($possible_fields, "category_meta_description");
		array_push($possible_fields, "category_meta_keywords");

		// get listing data
		$listing_info = $db->fetchAssoc("select $title_field, ".TABLE_ADS.".$description_field, ".TABLE_ADS.".*,  ".TABLE_ADS.".`meta_description` as `meta_description`, ".TABLE_ADS.".`meta_keywords` as `meta_keywords`, ".TABLE_CATEGORIES.".`parent_id`, ".TABLE_CATEGORIES."_lang.`name` as `category_name`, ".TABLE_CATEGORIES."_lang.`meta_description` as `category_meta_description`, ".TABLE_CATEGORIES."_lang.`meta_keywords` as `category_meta_keywords`, ".TABLE_CATEGORIES."_lang.`page_title` as `category_title` $location_str from ".TABLE_ADS." left join ".TABLE_CATEGORIES."_lang on ".TABLE_ADS.".category_id=".TABLE_CATEGORIES."_lang.id left join ".TABLE_CATEGORIES." on ".TABLE_ADS.".category_id=".TABLE_CATEGORIES.".id where ".TABLE_ADS.".id='".$id."' and `lang_id`='$crt_lang';");

		foreach ($listing_info as $key=>$value) {
		
			$listing_info[$key] = preg_replace("/[{}]/","",$listing_info[$key]);
		
		}

		if(!$listing_info) return $page_info;
		
		$listing_info['category_path'] = $listing_info['category_name'];
		if($listing_info['parent_id']) {
			$categ = new categories();
			$listing_info['category_path'] = $categ->catPath($listing_info['parent_id'], '').$listing_info['category_path'];
		}
		
		
		// if the listing has meta info
		if($ads_settings['add_meta_with_listings']) {
			$page_info['meta_keywords'] = cleanHtml($listing_info['meta_keywords']);
			$page_info['meta_description'] = cleanHtml($listing_info['meta_description']);
		}

		// build title
		$title_values = array();
		foreach($title_fields_array as $f) {
			if(!in_array(substr($f, 1), $possible_fields)) continue;
			$title_values[$f] = cleanHtml($listing_info[substr($f, 1)]); 
		}

		$page_info['title'] = trim(str_replace(array_keys($title_values), array_values($title_values), $page_info['title']));

		if(!in_array("description", $possible_fields)) array_push($possible_fields, "description");

		if(!$page_info['meta_keywords'] || !$ads_settings['add_meta_with_listings']){

			// build meta keywords
			$meta_keywords_values = array();

			foreach($meta_keywords_fields_array as $f) {

				if(!in_array(substr($f, 1), $possible_fields)) continue;
				if($f=="%title" || $f=="%description") { 
					$meta_keywords_values[$f] = shuffle_keys(cleanHtml($listing_info[substr($f, 1)]));
					continue;
				}
				$meta_keywords_values[$f] = cleanHtml($listing_info[substr($f, 1)]); 
			}

			$page_info['meta_keywords'] = trim(str_replace(array_keys($meta_keywords_values), array_values($meta_keywords_values), $page_info['meta_keywords']));

		}

		if(!$page_info['meta_description'] || !$ads_settings['add_meta_with_listings']){

			// build meta description
			$meta_description_values = array();
			foreach($meta_description_fields_array as $f) {

				if(!in_array(substr($f, 1), $possible_fields)) continue;
				$meta_description_values[$f] = cleanHtml($listing_info[substr($f, 1)]); 
			}

			$page_info['meta_description'] = trim(str_replace(array_keys($meta_description_values), array_values($meta_description_values), $page_info['meta_description']));

		}

		// cannonical url
		if($settings['enable_locations'] && $settings['enable_subdomains'] && $listing_info[$settings['subdomain_field']]) {
				$page_info['canonical'] = $this->makeSubdomainDetailsLink($id, $listing_info['title'], $listing_info[$settings['subdomain_field']], 1);

		}

		return $page_info;

	}


	// set meta info for category page
	function getCategoryMetaInfo($id) {

		global $page_info;
		if(!$id) return$page_info;

		$arr = array( 'title', 'meta_keywords', 'meta_description', 'canonical', 'noindex' );
		foreach($arr as $f)
			if(!isset($page_info[$f])) $page_info[$f]='';

		global $db;
		global $crt_lang, $config_abs_path;
		$row = $db->fetchAssoc("select `name`, `page_title`, `meta_keywords`, `meta_description` from ".TABLE_CATEGORIES."_lang where `lang_id`='$crt_lang' and id=$id");

		if(!$row) return array();

		require_once $config_abs_path."/classes/categories.php";

		$category_path = cleanHtml($row['name']);
		$categ = new categories();
		$parent_id = $categ->getParent($id);
		if($parent_id){
			
			$category_path = $categ->catPath($parent_id, '').$category_path;
			
		}
		
		$category_info = array(
			"%category_name" => cleanHtml($row['name']), 
			"%category_path" => cleanHtml($row['name'])." ".categories::getParentName($id), 
			"%category_title" => cleanHtml($row['page_title']),
			"%category_meta_keywords" => cleanHtml($row['meta_keywords']), 
			"%category_meta_description" => cleanHtml($row['meta_description'])
			);
		// default meta keywords and description

		$page_info['title'] = trim(str_replace(array_keys($category_info), array_values($category_info), $page_info['title']));

		$page_info['meta_keywords'] = trim(str_replace(array_keys($category_info), array_values($category_info), $page_info['meta_keywords']));

		$page_info['meta_description'] = trim(str_replace(array_keys($category_info), array_values($category_info), $page_info['meta_description']));

		return $page_info;

	}

	function cleanMetaInfo() {

		global $page_info;

		$find = array("/%[a-z_0-9]*/i", "/^[(\s)?,-]+/i", "/[,(\s)?]+$/i", "/,(\s*,)+/i", "/(\s-)+/i", "/(\s,)+/i", "/[\s]+/", "/\"/i");
		$replace = array("", "", "", ",", " -", " ,", " ", "");

		$page_info['title'] = preg_replace($find,$replace,$page_info['title']);
		$page_info['meta_keywords'] = preg_replace($find,$replace,$page_info['meta_keywords']);
		$page_info['meta_description'] = preg_replace($find,$replace,$page_info['meta_description']);
		
		return $page_info;

	}

	// set meta info for custom page
	function getCustomPageMetaInfo($id) {

		global $db;
		global $crt_lang, $settings, $config_live_site;

		$result=$db->fetchAssoc("select `page_title` as `title`, `meta_description`, `meta_keywords` from ".TABLE_CUSTOM_PAGES."_lang where `lang_id`='$crt_lang' and id=$id");

		$result['noindex'] = $db->fetchRow("select `noindex` from ".TABLE_CUSTOM_PAGES." where id=$id");

		foreach ($result as $key=>$value)
			$result[$key] = cleanHtml($value);

		// cannonical url
		$result['canonical'] = '';
		if($settings['enable_locations'] && $settings['enable_subdomains'] && isset($_GET['crt_city']) && $_GET['crt_city']) {

			// build canonical without subdomain 
			global $main_domain, $seo_settings, $sef_links;

			if(strstr($config_live_site, "https://")) $proto = "https://";
			else $proto = "http://";

			if($seo_settings['enable_mod_rewrite']) {
				$link = $this->makeCustomPageLink ($id, $result['title']);
				$result['canonical'] = str_replace($config_live_site, $proto.$main_domain, $link);
			}
			else
				$result['canonical'] = $proto.$main_domain."/content.php?id=".$id;

		}

		return $result;

	}

	// set meta info for recent ads page
	function getRecentAdsMetaInfo() {

		global $seo_settings, $page_info;

		// non canonical links
		if( (!empty($_GET['order']) && $_GET['order'] != "date_added") || ( !empty($_GET['order_way']) && $_GET['order_way'] != "desc") ) {
			global $config_live_site;
			$page = get_numeric("page", 1);
			if($seo_settings['enable_mod_rewrite']) {
				$page_str="";
				if($page>1) $page_str = "/".$page;
				if($seo_settings['sef_legacy_mode'])
					$page_info['canonical'] = $config_live_site.$page_str."/".$this->sef_links['recent_ads'].".html";
				else 	
					$page_info['canonical'] = $config_live_site."/".$this->sef_links['recent_ads']."?page=".$page;
			} else {
				$page_info['canonical'] = $config_live_site."/recent_ads.php";
				if($page>1) $page_info['canonical'].="?page=".$page;
			}

		}

		return $page_info;

	}

	// set meta info for user page
	function getUserPageMetaInfo($id) {

		global $db;
		global $crt_lang, $seo_settings, $settings, $page_info;

		// get all fields from title format
		$matches = array();
		preg_match_all("/%[a-z_0-9]*/i", $page_info['title'], $matches);
		$title_fields_array = $matches[0];

		// get all fields from meta keywords format
		$matches = array();
		preg_match_all("/%[a-z_0-9]*/i", $page_info['meta_keywords'], $matches);
		$meta_keywords_fields_array = $matches[0];

		// get all fields from meta description format
		$matches = array();
		preg_match_all("/%[a-z_0-9]*/i", $page_info['meta_description'], $matches);
		$meta_description_fields_array = $matches[0];

		// possible fields
		$possible_fields = $db->getTableFields(TABLE_USERS);

		// get user data
		$user_info = users::getUserInfo($id);

		// build title
		$title_values = array();
		foreach($title_fields_array as $f) {
			if(!in_array(substr($f, 1), $possible_fields)) continue;
			$title_values[$f] = cleanHtml($user_info[substr($f, 1)]); 
		}

		$page_info['title'] = trim(str_replace(array_keys($title_values), array_values($title_values), $page_info['title']));


		// build meta keywords
		$meta_keywords_values = array();

		foreach($meta_keywords_fields_array as $f) {

			if(!in_array(substr($f, 1), $possible_fields)) continue;
			$meta_keywords_values[$f] = cleanHtml($user_info[substr($f, 1)]); 
		}

		$page_info['meta_keywords'] = trim(str_replace(array_keys($meta_keywords_values), array_values($meta_keywords_values), $page_info['meta_keywords']));


		// build meta description
		$meta_description_values = array();
		foreach($meta_description_fields_array as $f) {

			if(!in_array(substr($f, 1), $possible_fields)) continue;
			$meta_description_values[$f] = cleanHtml($user_info[substr($f, 1)]); 
		}

		$page_info['meta_description'] = trim(str_replace(array_keys($meta_description_values), array_values($meta_description_values), $page_info['meta_description']));

		$canonical_subdomains = 0;
		if($settings['enable_locations'] && $settings['enable_subdomains'] && isset($_GET['crt_city']) && $_GET['crt_city']) $canonical_subdomains = 1;

		global $config_live_site;
		$site_url = $config_live_site;

		if(strstr($config_live_site, "https://")) $proto = "https://";
		else $proto = "http://";

		if($canonical_subdomains) { global $main_domain; $site_url = $proto.$main_domain; }

		$page = get_numeric("page", 1);

		// canonical links
		if( (!empty($_GET['order']) && $_GET['order'] != "date_added") || ( !empty($_GET['order_way']) && $_GET['order_way'] != "desc") || $canonical_subdomains ) {

			global $self_noext;
			if($seo_settings['enable_mod_rewrite']) {
				if($self_noext=="user_listings") $page_info['canonical'] = $this->makeUserListingsLink($id, '', $page, $canonical_subdomains );
				else if($self_noext=="store") $page_info['canonical'] = $this->makeDealerLink($id, '', $page, $canonical_subdomains );
				else $page_info['canonical'] = $this->makeUserDetailLink($id, '', $canonical_subdomains );
			} else {
				global $config_live_site;
				$page_info['canonical'] = $site_url."/".$self_noext.".php?id=".$id;
				if($page>1) $page_info['canonical'].="&page=".$page;
			}

		}

		return $page_info;

	}

	// set meta info for search page
	function getSearchMetaInfo($smarty) {

		global $post_array, $location_array, $page_info, $lng, $page;
		$smarty->assign("post_array", $post_array);
		
		// get category and parent category name
		$category_name = "";
		if($post_array['category']) { 

			global $config_abs_path;
			require_once($config_abs_path.'/classes/categories.php');
			$categ_info = categories::getForSeo($post_array['category']);
			
			$categ_info = $this->replaceLocationMetaInfo($categ_info);
			
			
			$category_path = $categ_info['name'];
			$categ = new categories();
			$parent_id = $categ->getParent($post_array['category']);
			
			if($parent_id)	{
			
				$category_path = $categ->catPath($parent_id, '').$category_path;
			
			}

			$category_name = $categ_info['name'];
			$category_title = $categ_info['page_title'];
			$category_meta_description = $categ_info['meta_description'];
			$category_meta_keywords = $categ_info['meta_keywords'];
 
		}

		// get ad title format
		global $seo_settings;

		// get all fields from title format
		$matches = array();
		preg_match_all("/%(\([-.*:,]\))?[a-z_0-9]*(\([-.*:,]\))?/i", $page_info['title'], $matches);
		$title_fields_array = $matches[0];

		$prefixes = array();
		$postfixes = array();
		$complete_tags = array(); // tags with prefix and postfix

		// get prefixes and postfixes
		// remove them from $title_fields_array
		$i = 0;
		foreach($title_fields_array as $tag) {

			$complete_tags[$i] = $tag;
			$prefix=''; $postfix='';
			if(preg_match("/%(\([-.*:,]\))[a-z_0-9]*(\([-.*:,]\))?/i", $tag)) {  
				$prefix = substr($tag, 1, 3);
				$prefixes[$i] = substr($prefix, 1,1)." ";
			} else $prefixes[$i]='';
			
			if(substr($tag, strlen($tag)-1, 1)==')') {  
				$postfix = substr($tag, strlen($tag)-3, 3);
				$postfixes[$i] = " ".substr($postfix, 1,1);
			} else $postfixes[$i]='';
			
			if($prefix!='') $title_fields_array[$i] = str_replace($prefix, '', $tag);
			if($postfix!='') $title_fields_array[$i] = str_replace($postfix, '', $title_fields_array[$i]);
			$i++;
	
		}
		// get all fields from meta keywords format
		$matches = array();
		preg_match_all("/%[a-z_0-9]*/i", $page_info['meta_keywords'], $matches);
		$meta_keywords_fields_array = $matches[0];

		$mk_prefixes = array();
		$mk_postfixes = array();
		$mk_complete_tags = array(); // tags with prefix and postfix

		// get prefixes and postfixes
		// remove them from $meta_keywords_fields_array
		$i = 0;
		foreach($meta_keywords_fields_array as $tag) {

			$mk_complete_tags[$i] = $tag;
			$prefix=''; $postfix='';
			if(preg_match("/%(\([-.*:,]\))[a-z_0-9]*(\([-.*:,]\))?/i", $tag)) {  
				$prefix = substr($tag, 1, 3);
				$mk_prefixes[$i] = substr($prefix, 1,1)." ";
			} else $mk_prefixes[$i]='';
			
			if(substr($tag, strlen($tag)-1, 1)==')') {  
				$postfix = substr($tag, strlen($tag)-3, 3);
				$mk_postfixes[$i] = " ".substr($postfix, 1,1);
			} else $mk_postfixes[$i]='';
			
			if($prefix!='') $meta_keywords_fields_array[$i] = str_replace($prefix, '', $tag);
			if($postfix!='') $meta_keywords_fields_array[$i] = str_replace($postfix, '', $meta_keywords_fields_array[$i]);
			$i++;
	
		}

		// get all fields from meta description format
		$matches = array();
		preg_match_all("/%[a-z_0-9]*/i", $page_info['meta_description'], $matches);
		$meta_description_fields_array = $matches[0];

		$md_prefixes = array();
		$md_postfixes = array();
		$md_complete_tags = array(); // tags with prefix and postfix

		// get prefixes and postfixes
		// remove them from $meta_description_fields_array
		$i = 0;
		foreach($meta_description_fields_array as $tag) {

			$md_complete_tags[$i] = $tag;
			$prefix=''; $postfix='';
			if(preg_match("/%(\([-.*:,]\))[a-z_0-9]*(\([-.*:,]\))?/i", $tag)) {  
				$prefix = substr($tag, 1, 3);
				$md_prefixes[$i] = substr($prefix, 1,1)." ";
			} else $md_prefixes[$i]='';
			
			if(substr($tag, strlen($tag)-1, 1)==')') {  
				$postfix = substr($tag, strlen($tag)-3, 3);
				$md_postfixes[$i] = " ".substr($postfix, 1,1);
			} else $md_postfixes[$i]='';
			
			if($prefix!='') $meta_description_fields_array[$i] = str_replace($prefix, '', $tag);
			if($postfix!='') $meta_description_fields_array[$i] = str_replace($postfix, '', $meta_description_fields_array[$i]);
			$i++;
	
		}

		// possible fields
		global $db;
		$possible_fields = $db->getTableFields(TABLE_ADS);
		if(!in_array("title", $possible_fields)) array_push($possible_fields, "title");
		array_push($possible_fields, "category_name");
		array_push($possible_fields, "category_path");
		array_push($possible_fields, "category_title");
		array_push($possible_fields, "category_meta_description");
		array_push($possible_fields, "category_meta_keywords");
		array_push($possible_fields, "page");
		array_push($possible_fields, $this->keyword);

		// build title
		$title_values = array();
		global $no_tags;
		$i = -1;
		
		foreach($title_fields_array as $f) {

			$i++;
			$f1 = $complete_tags[$i];

			if(!in_array(substr($f, 1), $possible_fields)) continue;
			
			if($f=="%category_title" ) { if($post_array['category']) { $title_values[$f1] = $prefixes[$i].$category_title.$postfixes[$i]; $no_tags++; } else $title_values[$f1] =''; continue; }
			if($f=="%category_name") { if($post_array['category']) { $title_values[$f1] = $prefixes[$i].$category_name.$postfixes[$i]; $no_tags++;} else $title_values[$f1] = ''; continue; }
			if($f=="%category_path") { if($post_array['category']) { $title_values[$f1] = $prefixes[$i].$category_path.$postfixes[$i]; $no_tags++; } else $title_values[$f1] = ''; continue; }
			if($f=="%page" && $page>1) { $title_values[$f1] = $prefixes[$i].$lng['general']['page']." ".$page.$postfixes[$i]; $no_tags++; continue; }
			if(isset($post_array[substr($f, 1)]) && $post_array[substr($f, 1)]) { $title_values[$f1] = $prefixes[$i].ucfirst(cleanHtml($post_array[substr($f, 1)])).$postfixes[$i]; $no_tags++; }
			//else if(isset($location_array[substr($f, 1)])) $title_values[$f] = cleanHtml($location_array[substr($f, 1)]); 
			else $title_values[$f1] = "";
		}

		$notags = 1;
		if($no_tags) $notags = 0;

		$page_info['title'] = trim(str_replace(array_keys($title_values), array_values($title_values), $page_info['title']));

		$smarty->assign("notags", $notags);
		
		// build meta keywords
		$meta_keywords_values = array();

		global $mk_no_tags;
		$i = -1;

		foreach($meta_keywords_fields_array as $f) {

			$i++;
			$f1 = $mk_complete_tags[$i];
			
			if(!in_array(substr($f, 1), $possible_fields)) continue;
			if($f=="%category_title" ) { if($post_array['category']) { $meta_keywords_values[$f1] = $mk_prefixes[$i].$category_title.$mk_postfixes[$i]; $mk_no_tags++; } else $meta_keywords_values[$f1] =''; continue; }
			if($f=="%category_name") { if($post_array['category']) { $meta_keywords_values[$f1] = $mk_prefixes[$i].$category_name.$mk_postfixes[$i]; $mk_no_tags++; } else $meta_keywords_values[$f1] = ''; continue; }
			if($f=="%category_path") { if($post_array['category']) { $meta_keywords_values[$f1] = $mk_prefixes[$i].$category_path; $mk_no_tags++;} else $meta_keywords_values[$f1] = ''; continue; }
			if($f=="%category_title") { if($post_array['category']) { $meta_keywords_values[$f1] = $mk_prefixes[$i].$category_title; $mk_no_tags++;} else $meta_keywords_values[$f1] = ''; continue; }
			if($f=="%category_meta_keywords") { if($post_array['category']) { $meta_keywords_values[$f1] = $mk_prefixes[$i].$category_meta_keywords.$mk_postfixes[$i]; $mk_no_tags++;} else $meta_keywords_values[$f1] = ''; continue; }
			if($f=="%page" && $page>1) { $meta_keywords_values[$f1] = $mk_prefixes[$i].$lng['general']['page']." ".$page.$mk_postfixes[$i]; $mk_no_tags++; continue; }
			if(isset($post_array[substr($f, 1)]) && $post_array[substr($f, 1)]) { $meta_keywords_values[$f1] = $mk_prefixes[$i].ucfirst(cleanHtml($post_array[substr($f, 1)])).$mk_postfixes[$i]; $mk_no_tags++; }
			//else if(isset($location_array[substr($f, 1)])) $meta_keywords_values[$f] = cleanHtml($location_array[substr($f, 1)]); 
			else $meta_keywords_values[$f] = "";

		}

		$mk_notag = 1;
		if($mk_no_tags) $mk_notag = 0;

		$page_info['meta_keywords'] = trim(str_replace(array_keys($meta_keywords_values), array_values($meta_keywords_values), $page_info['meta_keywords']));

		$smarty->assign("mk_notag", $mk_notag);
		
		// build meta description
		$meta_description_values = array();
		global $md_no_tags;
		$i = -1;

		foreach($meta_description_fields_array as $f) {

			$i++;
			$f1 = $md_complete_tags[$i];
			
			if(!in_array(substr($f, 1), $possible_fields)) continue;
			if($f=="%category_title" ) { if($post_array['category']) { $meta_description_values[$f1] = $md_prefixes[$i].$category_title.$md_postfixes[$i]; $md_no_tags++; } else $meta_description_values[$f1] =''; continue; }
			if($f=="%category_name") { if($post_array['category']) { $meta_description_values[$f1] = $md_prefixes[$i].$category_name.$md_postfixes[$i]; $md_no_tags++; } else $meta_description_values[$f1] = ''; continue; }
			if($f=="%category_path") { if($post_array['category']) { $meta_description_values[$f1] = $md_prefixes[$i].$category_path; $md_no_tags++;} else $meta_description_values[$f1] = ''; continue; }
			if($f=="%category_title") { if($post_array['category']) { $meta_description_values[$f1] = $md_prefixes[$i].$category_title; $md_no_tags++;} else $meta_description_values[$f1] = ''; continue; }
			if($f=="%category_meta_description") { if($post_array['category']) { $meta_description_values[$f1] = $md_prefixes[$i].$category_meta_description.$md_postfixes[$i]; $md_no_tags++;} else $meta_description_values[$f1] = ''; continue; }
			if($f=="%page" && $page>1) { $meta_description_values[$f1] = $md_prefixes[$i].$lng['general']['page']." ".$page.$md_postfixes[$i]; $md_no_tags++; continue; }
			if(isset($post_array[substr($f, 1)]) && $post_array[substr($f, 1)]) { $meta_description_values[$f1] = $md_prefixes[$i].ucfirst(cleanHtml($post_array[substr($f, 1)])).$md_postfixes[$i]; $md_no_tags++; }
			//else if(isset($location_array[substr($f, 1)])) $meta_description_values[$f] = cleanHtml($location_array[substr($f, 1)]); 
			else $meta_description_values[$f] = "";
		}

		$md_notag = 1;
		if($md_no_tags) $md_notag = 0;

		$page_info['meta_description'] = trim(str_replace(array_keys($meta_description_values), array_values($meta_description_values), $page_info['meta_description']));

		$smarty->assign("md_notag", $md_notag);

		// non canonical links
		if( (!empty($post_array['order']) && $post_array['order'] != "date_added") || ( !empty($post_array['order_way']) && $post_array['order_way'] != "desc") ) {

			if($seo_settings['enable_mod_rewrite'])
				$page_info['canonical'] = $this->makeSearchLink($post_array, $page, "order|order_way");
			else {
				global $config_live_site;
				$page_info['canonical'] = preg_replace("/\&order(_way)?=[a-z]+/i" ,"", $config_live_site."/listings.php?".$_SERVER['QUERY_STRING']);
			}
		}

		return $page_info;

	}

	function replaceLocationMetaInfo($pg_info) {

		global $settings;
		if(!$settings['enable_locations']) return $pg_info;

		global $location_array, $self_noext;

		$location_values = array();
		global $no_tags, $md_no_tags;
		foreach($location_array as $key=>$value) {
			if(!$value) continue;
			$location_values['%'.$key] = ucfirst($value);
			if($self_noext=="listings") {
				if(isset($pg_info['title']) && strstr($pg_info['title'], '%'.$key)) $no_tags++;
				if(strstr($pg_info['meta_description'], '%'.$key)) $md_no_tags++;
			}
		}

		if(isset($pg_info['title'])) $pg_info['title'] = str_replace(array_keys($location_values), array_values($location_values), $pg_info['title']);
		if(isset($pg_info['page_title'])) $pg_info['page_title'] = str_replace(array_keys($location_values), array_values($location_values), $pg_info['page_title']);
		$pg_info['meta_keywords'] = str_replace(array_keys($location_values), array_values($location_values), $pg_info['meta_keywords']);
		$pg_info['meta_description'] = str_replace(array_keys($location_values), array_values($location_values), $pg_info['meta_description']);

		return $pg_info;

	}

	function legacySearchParseParam(&$post_array) {
	
		global $seo_settings, $keyword_name, $settings;

		$has_search_fields = 0;
		
		if(strstr ($_SERVER['QUERY_STRING'], "category_slug")) return $has_search_fields;

if($_SERVER['QUERY_STRING']) {

	if($seo_settings['enable_mod_rewrite']) {
		$query_string = $_SERVER['QUERY_STRING'];
		$args_arr = explode("&", $_SERVER['QUERY_STRING']);
		foreach ($args_arr as $a) {
			if(strstr($a, "listings.html"))  {$query_string = $a; break; }
		}
		
		$args = explode("/", $query_string);
	} else {
		$page = get_numeric("page");
		$args = explode("&",$_SERVER['QUERY_STRING']);
	}

	$i = 0;
	foreach($args as $arg)
	{

		if($seo_settings['enable_mod_rewrite']) {
			// check for page string
			if(is_numeric($arg)) { $post_array['page'] = $arg; $i++; continue; }

			// if the first argument, check if category is set
			if($i==0) {

				$keyval = explode("-",$arg);
				if(is_numeric($keyval[0])) { $post_array['category'] = $keyval[0]; $i++;  $has_search_fields = 1; continue; }
				if(preg_match("/^crt_city=(.*)+$/", $keyval[0]) ) continue;
	
			}

			$keyval = explode("-",$arg,2);

		} 
		else {

			$keyval = explode("=",$arg);

		}

		if(!$seo_settings['enable_mod_rewrite'] || count($keyval)>1) {

		// get slashes back 
		$keyval[1] = str_replace("__", "/", $keyval[1]);

		// get $ sign back
		$keyval[1] = str_replace("~~", "$", $keyval[1]);

		// get & sign back
		$keyval[1] = str_replace("||", "&", $keyval[1]);

		switch($keyval[0]) {

			case "search":
			case "Search":
			case "x":
			case "y":
				break;
			// quick search
			case "qs_".$keyword_name:
				if($keyval[1]) $post_array[$keyword_name] = cleanSearchString($keyval[1]);
  				$has_search_fields = 1;
				break;
			case "qs_category":
			case "qs_user_id":
			case "qs_price_low":
			case "qs_price_high":
				$keyval[0] = substr($keyval[0], 3);
				if($keyval[1]) $post_array[$keyval[0]] = cleanSearchString($keyval[1]);
  				$has_search_fields = 1;
				break;
			case "qs_currency":
				$keyval[0] = 'currency';
				$currency_val = cleanSearchString($keyval[1]);
				if($keyval[1]) $post_array[$keyval[0]] = $currency_val;
  				$has_search_fields = 1;

				global $modules_array;
  				if(in_array("multicurrency", $modules_array)) {
					$mc = new multicurrency();
					$mc->setMulticurrencyCookie($keyval[1]);
  				}

  				break;
			// refine search
			case $keyword_name:
				if($keyval[1]) $post_array[$keyword_name] = cleanSearchString($keyval[1]);
  				$has_search_fields = 1;
				break;
			case "category":// only when mod rewrite is not on
			case "user_id":
			case "price_low":
			case "price_high":
				if($keyval[1] && is_numeric($keyval[1])) $post_array[$keyval[0]] = $keyval[1];
  				$has_search_fields = 1;
				break;
			case "currency":
				$currency_val = cleanSearchString($keyval[1]);
				if($keyval[1]) $post_array[$keyval[0]] = $currency_val;
  				$has_search_fields = 1;
  				
  				global $modules_array;
  				if(in_array("multicurrency", $modules_array)) {
					$mc = new multicurrency();
					$mc->setMulticurrencyCookie($keyval[1]);
  				}
  				
				break;
			case "order":
			$order1_array = array("date_added", "price","title");
				if($keyval[1] &&  in_array($keyval[1],$order1_array)) $post_array['order'] = $keyval[1];
  				$has_search_fields = 1;
				break;
			case "order_way":
				$order2_array = array("desc", "asc");
				if($keyval[1] &&  in_array($keyval[1],$order2_array)) $post_array['order_way'] = $keyval[1];
  				$has_search_fields = 1;
				break;
			case "with_pic":
				if($keyval[1]==0 || $keyval[1]==1) $post_array['with_pic'] = $keyval[1];
  				$has_search_fields = 1;
				break;
			case "with_auction":
				if($keyval[1]==0 || $keyval[1]==1) $post_array['with_auction'] = $keyval[1];
  				$has_search_fields = 1;
				break;
			case "show":
				$show_array = array("list", "gallery");
				if($keyval[1] &&  in_array($keyval[1],$show_array)) $post_array['show'] = $keyval[1]; else break;
				global $main_domain;
				$expire = time() + 60*60*24*365;
				setcookie('show', $post_array['show'], $expire, '/', ".".$main_domain);
  				$has_search_fields = 1;
				break;
			case "location":
				$post_array["location"] = str_replace("|", ", ", rawurldecode($keyval[1]));
				break;
			default: 

  				$has_search_fields = 1;
				//$post_array[$keyval[0]] = cleanSearchString($keyval[1]);
				$post_array[$keyval[0]] = ucfirst(cleanSearchString($keyval[1]));

				// can only be set with refine search, only one at once
				if($settings['enable_locations'] && in_array($keyval[0], explode(",", $settings['location_fields']))) { 

					$lclass->setLocation($keyval[0], cleanSearchString($keyval[1]));

				}

				break;

		} // end switch

		} // end if(!$seo_settings['enable_mod_rewrite'] || count($keyval)>1)
	}

}


		return $has_search_fields;
	}

}
?>
