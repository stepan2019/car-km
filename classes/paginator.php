<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class paginator {

	var $items_per_page;
	var $total_items;
	var $current_page;
	var $no_pages;
	var $order_by;
	var $order_way;
	var $mid_range;
	var $querystring;
	var $seo_url_string;
	var $extra_fields_array;
	var $exclude_array;
	var $no_seo = 0;
	var $default_order;
	var $default_order_way;
	var $admin=0;
	var $addToPath = '';
	var $live_site;
	var $crt_script;
	var $prefix='';
	var $account_type='';

	public function __construct($no_per_page='')
	{
	
		global $appearance_settings;
		if($no_per_page) $this->items_per_page = $no_per_page; else $this->items_per_page = $appearance_settings['ads_per_page'];
		$this->current_page = 1;
		$this->mid_range = 5;
		$this->querystring = '';
		$this->seo_url_string = '';
		$this->extra_fields_array = array();
		$this->exclude_array = array();
		$this->default_order = 'date_added';
		$this->default_order_way = 'desc';
		$this->prefix = '';

		if(isset($_GET['page']) && is_numeric($_GET['page'])) {
			$this->current_page=$_GET['page']; 
			$this->current_page=checkPageNo($this->current_page, $this->items_per_page);
		}
		else $this->current_page=1;

		if(isset($_GET['order'])) 
			$this->order_by=escape($_GET['order']); 
		else $this->order_by = $this->default_order;

		$array_order_way= array("asc", "desc");
		if(isset($_GET['order_way']) && in_array($_GET['order_way'],$array_order_way)) 
			$this->order_way=$_GET['order_way']; 
		else $this->order_way = $this->default_order_way;

		if(isset($_GET['account_type']) && is_numeric($_GET['account_type']))
			$this->account_type=$_GET['account_type']; 
	}

	function setItemsNo($no) {

		if(!is_numeric($no) || $no<0) return;
		$this->total_items = $no;
		$this->no_pages = ceil($no/$this->items_per_page);
		if(!$this->no_pages) $this->no_pages = 1;

	}	

	function setExtraFieldsArray($var) {

		$this->extra_fields_array = $var;

	}	

	function setExcludeArray($var) {

		$this->exclude_array = $var;

	}

	function setNoSeo($var) {

		$this->no_seo = $var;

	}	

	function setAdmin($var) {

		$this->admin = $var;

	}	

	function setDefaultOrder($var) {

		$this->order_by = $var;

	}	

	function setDefaultOrderWay($var) {

		$this->order_way = $var;

	}	

	function setSeoUrlStr($var) {

		$this->seo_url_string = "/".$var;

	}	

	function setOrderBy($var) {

		$this->order_by = $var;

	}	

	function setOrderWay($var) {

		$this->order_way = $var;

	}	
	
	function addToPath($str) {

		$this->addToPath=$str;

	}

	// sets a prefix used to change the name of smarty variables
	// in case multiple paginators are used on a page
	function setPrefix($var) {

		$this->prefix = $var;

	}	

	function paginate(&$smarty) {

		global $config_live_site;
		global $seo_settings, $ads_settings;
	
		if($this->admin) $this->live_site = $config_live_site.'/admin'; else $this->live_site = $config_live_site;
		if($this->addToPath) $this->live_site.=$this->addToPath;
		$this->crt_script = getScriptNameNoExt();

		global $seo;
		$show_link = '';
		$acctype_link = '';
		$no_page = 0;
		// url string for listings search page
		if($this->crt_script=="listings" && $seo_settings['enable_mod_rewrite']) {

			$pag_array = $seo->makeSearchPagesLink();
			//_print_r($pag_array);
			$this->current_page = $pag_array['page'];
			$this->current_page = checkPageNo($this->current_page, $this->items_per_page);
			$this->order_by = $pag_array['order_by'];
			$this->order_way = $pag_array['order_way'];
			$pages_link = $pag_array['pages_link'];
			$order_by_link = $pag_array['order_by_link'];
			$order_way_link = $pag_array['order_way_link'];
			$order_link = $pag_array['order_link'];
			$acctype_link = $pag_array['acctype_link'];
			$this->querystring = $pag_array['crt_page_link'];
			$show_link = $pag_array['show_link'];

		} 
		else {

			// mod rewrite urls
			if($seo_settings['enable_mod_rewrite'] && $seo_settings['sef_legacy_mode'] && !$this->no_seo) { // mod rewrite url string

				$new_script = $seo->links[$this->crt_script];

				// extra_fields_array
				$key_str = "";
				foreach ($this->extra_fields_array as $key) {

					$key_str.="/".urlencode($_GET[$key]);
				}
				$crt_page_str = "/".$this->current_page;

				if($this->crt_script=="listings" && $this->current_page==1) $crt_page_str="";
				if($this->order_by == $this->default_order && $this->order_way == $this->default_order_way ) $no_page = 1;

				// add order to url or not 
				$order_str = '';
				if($this->order_by!=$this->default_order || $this->order_way!=$this->default_order_way)
					$order_str = "/".$this->order_by."/".$this->order_way;

				$this->querystring = $this->live_site.$this->seo_url_string.$key_str.$crt_page_str.$order_str."/".$new_script;

				$pages_link = $this->live_site.$this->seo_url_string.$key_str."/##page##".$order_str."/".$new_script;

				$order_by_link = $this->live_site.$this->seo_url_string.$key_str.$crt_page_str."/##order##/".$this->order_way."/".$new_script;

				$order_way_link = $this->live_site.$this->seo_url_string.$key_str.$crt_page_str."/".$this->order_by."/##order_way##/".$new_script;
				
				$order_link = $this->live_site.$this->seo_url_string.$key_str.$crt_page_str."/##order##/##order_way##/".$new_script;

				$acctype_link = $this->live_site.$this->seo_url_string.$key_str.$crt_page_str.$order_str."/##acctype##/".$new_script;

			} else {

				if($seo_settings['enable_mod_rewrite'] && isset($seo->links[$this->crt_script]) && !$this->no_seo)
					$script_name = "/".$seo->links[$this->crt_script]."/";
				else
					$script_name = "/".getScriptName();
				if(($this->crt_script=="store" || $this->crt_script=="user_listings") && isset($_GET['user_slug']))	
					$script_name.=$_GET['user_slug']."/";

				if($this->crt_script=="store" && isset($_GET['crt_dealer']) && $ads_settings['dealer_subdomain']){
					
					global $main_domain; 

					if(strstr($config_live_site, "https://")) $proto = "https://";
					else $proto = "http://";

					$this->live_site = $proto.$_GET['crt_dealer'].".".$main_domain;

				}
					
				// extra_fields_array
				$key_str = "";
				foreach ($this->extra_fields_array as $key) {
					if(isset($_GET[$key])) $key_str.="&amp;$key=".urlencode($_GET[$key]);
				}

				$this->querystring = $this->live_site.$script_name."?page=".$this->current_page;
				if($this->order_by!=$this->default_order) $this->querystring .="&amp;order=".$this->order_by;
				if($this->order_way!=$this->default_order_way) $this->querystring .="&amp;order_way=".$this->order_way;
				if($this->account_type) $this->querystring .="&amp;account_type=".$this->account_type;
				$this->querystring .=$key_str;

				$pages_link = $this->live_site.$script_name."?page=##page##";
				if($this->order_by!=$this->default_order) $pages_link .="&amp;order=".$this->order_by;
				if($this->order_way!=$this->default_order_way) $pages_link .="&amp;order_way=".$this->order_way;
				if($this->account_type) $pages_link .="&amp;account_type=".$this->account_type;
				$pages_link .=$key_str;

				$order_by_link = $this->live_site.$script_name."?page=".$this->current_page."&amp;order=##order##";
				if($this->order_way!=$this->default_order_way) $order_by_link .="&amp;order_way=".$this->order_way;
				if($this->account_type) $order_by_link .="&amp;account_type=".$this->account_type;
				$order_by_link .=$key_str;

				$order_way_link = $this->live_site.$script_name."?page=".$this->current_page."&amp;order_way=##order_way##";
				if($this->order_by!=$this->default_order) $order_way_link .="&amp;order=".$this->order_by;
				if($this->account_type) $order_way_link .="&amp;account_type=".$this->account_type;
				$order_way_link .=$key_str;

				$order_link = $this->live_site.$script_name."?page=".$this->current_page."&amp;order=##order##&amp;order_way=##order_way##";
				$order_link .=$key_str;

				$show_link = $this->live_site.$script_name."?page=".$this->current_page."&amp;show=##show##";
				if($this->order_way!=$this->default_order_way) $order_by_link .="&amp;order_way=".$this->order_way;
				if($this->order_by!=$this->default_order) $order_way_link .="&amp;order=".$this->order_by;
				if($this->account_type) $show_link .="&amp;account_type=".$this->account_type;
				$show_link .=$key_str;

				$acctype_link = $this->live_site.$script_name."?page=".$this->current_page;
				if($this->order_way!=$this->default_order_way) $acctype_link .="&amp;order_way=".$this->order_way;
				if($this->order_by!=$this->default_order) $acctype_link .="&amp;order=".$this->order_by;
				$acctype_link .="&amp;account_type=##acctype##";
				$acctype_link .=$key_str;
			}

			if($_GET) {
				$i=0;
				$args = explode("&",$_SERVER['QUERY_STRING']);
				foreach($args as $arg)
				{
					$keyval = explode("=",$arg);
					$key = $keyval[0];
					$val = $keyval[1];

					if($key != "page" && $key!="crt_city" && $key != "order" && $key != "order_way" && $key != "account_type" && $key !="user_slug" && $key!="crt_dealer" && (!in_array($key, $this->extra_fields_array) && !in_array($key ,$this->exclude_array) || !$seo_settings['enable_mod_rewrite']  )) {
						$sign="&amp;";
						$this->querystring .= $sign.$arg;
						$pages_link.=$sign.$arg;
						$order_by_link.=$sign.$arg;
						$order_way_link.=$sign.$arg;
						$acctype_link.=$sign.$arg;
						if($key!="show" && $key != "crt_city") $show_link.=$sign.$arg;
						$i++;
					}
				}
			} // end if GET

			if($_POST)
			{
				$i=0;
				foreach($_POST as $key=>$val)
				{
 					if($key != "page" && $key!="crt_city" && $key != "order" && $key != "order_way" && $key != "account_type" && !in_array($key ,$this->exclude_array) && ( !in_array($key, $this->extra_fields_array) || !$seo_settings['enable_mod_rewrite']  ) && $val) {
						$val = urlencode($val);

						if($key=="order" && $val==$this->default_order) continue;
						if($key=="order_way" && $val==$this->default_order_way) continue;

						if($i || strstr($this->querystring, "?")) $sign = "&amp;"; else $sign="?";
						$this->querystring .= "$sign$key=$val";
						$pages_link.="$sign$key=$val";
						$order_by_link.="$sign$key=$val";
						$order_way_link.="$sign$key=$val";
						$show_link.="$sign$key=$val";
						$acctype_link.="$sign$key=$val";
						$i++;
					}
				}
			}

		}

		$prev_page = $this->current_page-1;
		$next_page = $this->current_page+1;

		// assign to smarty
		$smarty->assign($this->prefix."page", $this->current_page);
		$smarty->assign($this->prefix."prev_page", $prev_page);
		$smarty->assign($this->prefix."next_page", $next_page);
		$smarty->assign($this->prefix."no_pages", $this->no_pages);
		$smarty->assign($this->prefix."total_items", $this->total_items);
		$smarty->assign($this->prefix."order", $this->order_by);
		$smarty->assign($this->prefix."order_way", $this->order_way);
		$smarty->assign($this->prefix."items_per_page", $this->items_per_page);
		$smarty->assign($this->prefix."mid_range", $this->mid_range);
		$smarty->assign($this->prefix."pages_link", $pages_link);
		$smarty->assign($this->prefix."order_by_link", $order_by_link);
		$smarty->assign($this->prefix."order_way_link", $order_way_link);
		$smarty->assign($this->prefix."order_link", $order_link);
		$smarty->assign($this->prefix."crt_page_link", $this->querystring);
		$smarty->assign($this->prefix."show_link", $show_link);
		$smarty->assign($this->prefix."acctype_link", $acctype_link);
		$smarty->assign($this->prefix."no_page", $no_page);
		global $max_results;
		$smarty->assign($this->prefix."max_pages", ceil($max_results/$this->items_per_page));

	}

}

?>
