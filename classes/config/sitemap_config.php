<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class sitemap {

	var $error='';

	public function __construct()
	{
	
		$this->defaultPriority = "0.5";
		$this->defaultFrequency = "weekly";
		global $config_abs_path;
		global $config_live_site;
		$this->fname = $config_abs_path."/sitemap/sitemap.xml";
		$this->fname_http = htmlspecialchars($config_live_site, ENT_COMPAT, 'UTF-8')."/sitemap/sitemap.xml";

	}

	function getAll() {

		global $db;
		$this->array=$db->fetchAssoc("select * from ".TABLE_SITEMAP);
		return $this->array;

	}

	function checkPriority($p)
	{
		$f = floatval($p);
		if ($f >= 0.0 && $f <= 1.0) return(strval($f));
		if ($f < 0.0) return("0.0");
		if ($f > 1.0) return("1.0");
		return($this->defaultFrequency);
	}

	function getLink() {

		return $this->fname_http;

	}

	function checkFrequency($freq) {
		
		$array_freq = array("always", "hourly", "daily", "weekly", "monthly", "yearly", "never");
		if(in_array($freq, $array_freq)) return $freq;
		return $this->defaultFrequency;

	}

	function checkAutoFrequency($freq) {
		
		$array_auto_freq = array("daily", "weekly", "monthly");
		if(in_array($freq, $array_auto_freq)) return $freq;
		return $this->defaultFrequency;

	}

	function writeSitemap() {

		global $lng;
		global $seo_settings, $is_admin;
		global $sef_links, $settings, $config_abs_path, $config_live_site;
		$mod_rewrite = $seo_settings['enable_mod_rewrite'];

		if (!$this->f = @fopen($this->fname, 'w')) { 
			if($is_admin) $this->setError($lng['sitemap']['error']['could_not_write_file']);
			return 0;
		}

		$this->writeXML($this->createXMLHeader());

		$sett = $this->getAll();

		// write subdomains links		
		if($settings['enable_locations'] && $settings['enable_subdomains']) {

			global $sef_links;
			require_once $config_abs_path."/classes/locations.php";
			$cloc = new locations;
			$locations = $cloc->getLocations($settings['subdomain_field'], 1);
			$locations_array = explode("|", $locations);

			$categories = common::getCachedObject("base_short_categories");
			
			if(strstr($config_live_site, "https://")) $proto = "https://";
			else $proto = "http://";

			foreach($locations_array as $loc) {
			
				$city = $cloc->buildLocationSubdomain($loc);
				if(stristr($config_live_site, "www"))
					$subdomain_link = str_replace($proto."www.", $proto.$city.".", $config_live_site);
				else 
					$subdomain_link = str_replace($proto, $proto.$city.".", $config_live_site);
 				$this->writeLink($subdomain_link, $sett['priority'], $sett['changefreq']);
 				
				// write for every category
				if($sett['write_categories']) {	

					if(count($categories)) {
					foreach($categories as $cat) {

						if($mod_rewrite) { 

							$seo = new seo();
							$cname = categories::getName($cat['id']);
							$link = $seo->makeSubdomainSearchCategoryLink($id, $cname, $loc);
							
						}
						else $link = $subdomain_link."/listings.php?category=".$cat['id'];
						$this->writeLink($link, $sett['categories_priority'], $sett['categories_changefreq']);

					}
					} // end if categories

					 				
 				} // end write for every category
 				
			} // end foreach location
			
		}// end if($settings['enable_locations'] && $settings['enable_subdomains']) 


		// write categories links
		if($sett['write_categories']) {

			$categories = common::getCachedObject("base_short_categories_fset");
			if(count($categories)) {
			foreach($categories as $cat) {

				if($mod_rewrite) { 
					global $seo;
					if(!$seo) $seo = new seo();
					$link = $seo->makeSearchCategoryLink($cat['id'], $cat['name']);
				}
				else $link = $config_live_site."/listings.php?category=".$cat['id'];
				$this->writeLink($link, $sett['categories_priority'], $sett['categories_changefreq']);

			}
			} // end if categories
		}

		// write listings links	
		if($sett['write_listings']) {

			global $db;
			$listings = new listings();
			$sql = $listings->getAllSitemapQuery($sett['listings_no']);
			$result = $db->query($sql);
			while($ad = $db->fetchAssocRes($result)) {

				if($mod_rewrite) { 
					global $seo;
					if(!$seo) $seo = new seo();
	
					if($settings['enable_locations'] && $settings['enable_subdomains'] && $ad[$settings['subdomain_field']])
						$link = $seo->makeSubdomainDetailsLink($ad['id'], $ad['title'], $ad[$settings['subdomain_field']]);
					else
						$link = $seo->makeDetailsLink($ad['id'], $ad['title']);
				}
				else $link = $config_live_site."/details.php?id=".$ad['id'];
				$this->writeLink($link, $sett['listings_priority'], $sett['listings_changefreq']);

			}
		}
		// write custom pages links
		if($sett['write_custom_pages']) {

			global $config_abs_path;
			require_once $config_abs_path."/classes/custom_pages.php";
			$cp = new custom_pages();
			$pages = $cp->getNavLinks();
			if(count($pages)) {
			foreach($pages as $page) {

				if($mod_rewrite) $link = $seo->makeCustomPageLink ($page['id'], $page['title']);
				else $link = $config_live_site."/content.php?id=".$page['id'];
				$this->writeLink($link, $sett['cp_priority'], $sett['cp_changefreq']);

			}
			} // end if pages
		}

		// rest of the files - recent_ads.php, index.php, listings.php
		if($mod_rewrite) { 
			$array_files = array();
			$arr_files = array("index", "recent_ads", "contact", "register");
			foreach($arr_files as $f) {
				if($f=="index") array_push($array_files, "");
				else { 
					if($seo_settings['sef_legacy_mode'])
						array_push($array_files, $sef_links[$f]);
					else 
						array_push($array_files, $seo_settings['sef_links'][$f]);
				}
			}
		}
		else $array_files = array("index.php", "recent_ads.php", "contact.php", "register.php");
		foreach ($array_files as $file) {
			$link = $config_live_site."/".$file;
			$this->writeLink($link, $sett['priority'], $sett['changefreq']);
		}

		// add extra entries
		if(isset($sett['extra_entries']) && $sett['extra_entries']) {
			$this->writeXML($sett['extra_entries']);
		}
		
		$this->writeXML($this->createXMLFooter());

		//$this->updateDate();

		if ($this->f){
			fflush($this->f);
			fclose($this->f);
		}
		return 1;

	}

	function writeLink($link, $priority, $frequency, $lastmod='') {

		$str = "<url>\n";
		$str .= "<loc>".$link."</loc>\n";
		if($lastmod) $str .= "<lastmod>$lastmod</lastmod>\n";
		$str .= "<priority>".$priority."</priority>\n";
		$str .= "<changefreq>".$frequency."</changefreq>\n";
		$str .= "</url>";

		$this->writeXML($str);

	}

	function writeXML($str) {

		if ($this->f) fwrite($this->f, $str."\n");


	}

	function createXMLHeader() {

		$str = '<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
		return $str;

	}

	function createXMLFooter() {

		return "</urlset>";

	}

	function edit() {

		global $db;

		global $config_demo;
		if($config_demo==1) return;

		$clean = array();
		$array_checkboxes = array ("enabled", "write_categories", "write_listings", "write_custom_pages");
		foreach ( $array_checkboxes as $chk ) 
			$clean["$chk"] = checkbox_value("$chk");

		$array_priorities = array ("priority", "categories_priority", "listings_priority", "cp_priority");
		foreach ( $array_priorities as $pri ) 
			$clean["$pri"] = $this->checkPriority($_POST["$pri"]);

		$array_freq = array ("changefreq", "categories_changefreq", "listings_changefreq", "cp_changefreq");
		foreach ( $array_freq as $freq ) 
			$clean["$freq"] = $this->checkFrequency($_POST["$freq"]);

		$clean["auto_write_freq"] = $this->checkAutoFrequency($_POST["auto_write_freq"]);
		if(is_numeric($_POST['listings_no'])) $clean["listings_no"] = $_POST['listings_no']; else $clean["listings_no"] = 0;

		if(isset($_POST['extra_entries'])) $clean['extra_entries'] = escape($_POST['extra_entries']);
		
		$sql = "update ".TABLE_SITEMAP." set `extra_entries` = '{$clean['extra_entries']}'";
		$i=0;
		foreach ($clean as $key => $value) {
			$sql.=", `$key` = '$value'";
			$i++;
		}
		$res = $db->query($sql);
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


}
?>
