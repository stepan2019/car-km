<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class searches {

    public function __construct()
    {
	
    }

    function addSearch() {
    
		global $db;
		$now = @date('Y-m-d H:i:s');;
		$sql="insert into ".TABLE_SEARCHES." set `date`='$now'";
		
		global $ads_settings;
		if($ads_settings['enable_distance_search'] && isset($_POST['qs_lat']) && isset($_POST['qs_long'])) {
		
			$lat = escape($_POST['qs_lat']);
			$long = escape($_POST['qs_long']);
			$sql.=", `lat`='$lat', `long`='$long'";
			
		} // end if enable_distance_search
		
		if($ads_settings['enable_location_autosuggest'] && count($ads_settings['address_components'])) {	

			$location_fields="";
			foreach($ads_settings['address_components'] as $f) {
						
					if(isset($_POST["qs_".$f['field']]) && $_POST["qs_".$f['field']])
						$location_fields.=$f['field']."=".escape($_POST["qs_".$f['field']])."|";
			}
			$location_fields = rtrim($location_fields, "|");
				
			// change charset if needed
			global $config_db_charset;
			if($config_db_charset=="latin1" && function_exists(iconv)) {
			
			    $location_fields = iconv( 'UTF-8', 'ISO-8859-1//TRANSLIT', $location_fields );
			}
				
			do_action("add_search_location_fields", array(&$location_fields));
			
			if($location_fields) $sql.=", `location_fields`='$location_fields'";
			
		} // end if enable_location_autosuggest
		
		$res=$db->query($sql);

		$search_id=$db->insertId();
		
		return $search_id;
    
    }
   
   function updateSearchURL($id, $url) {
   
		global $db;
		$db->query("update ".TABLE_SEARCHES." set `search_url` = '$url' where id='$id'");
		return 1;
   
   }

   function getSearch($id) {
   
		global $db, $ads_settings;
		$array = $db->fetchAssoc("select * from ".TABLE_SEARCHES." where id='$id'");
		
		$search['lat'] = $array['lat'];
		$search['long'] = $array['long'];
		
		if($ads_settings['enable_location_autosuggest']) {
			$lf = explode("|", $array['location_fields']);
			foreach($lf as $l) {
				$k = explode("=", $l);
				if(in_array( $k[0], $ads_settings['address_components_fields']) && $k[1]) $search[$k[0]] = $k[1];
			}
		}
		
		return $search;
   
   }
   
   function makeSearchURL() {
   
   }

}
?>
