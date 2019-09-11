<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

function setGmaps($ftype, $fset, &$smarty, $gmaps_array='', $array = '') {


	$gmaps_array = addGmaps($ftype, $fset, $gmaps_array, $array);

	gmapsSmartyAssign($gmaps_array, $smarty);
	// end google maps
}

function addGmaps($ftype, $fset, &$gmaps_array='', $array='') {

	global $settings;

	if(!$gmaps_array) { 

		$gmaps_array = array();
		$gmaps_array['gmaps']=0;

		// enumerative array with unique fields that count to auto fill google maps fields
		$gmaps_array['gmaps_unique']=array();

		// key-value array with fields that will count to auto fill other google maps fields and the coresponding google maps field
		$gmaps_array['gmaps_fields']=array();

		$gmaps_array['value_exists']=0;

	}

	global $config_abs_path;
	require_once $config_abs_path."/classes/fields.php";
	$f=new fields($ftype);
	$gmaps_temp = $f->gMapsFields($fset);

	if($gmaps_temp) {

		$i = 0;
		$j = 0;
		foreach($gmaps_temp as $g) {

			if( $array && isset($array[$g['caption']]) && $array[$g['caption']]) $gmaps_array['value_exists']=1;

			$gmaps_f = explode(",", $g['extensions']);
			if(!count($gmaps_f)) continue;

			$gmaps_temp[$i]['address'] = '';
			foreach($gmaps_f as $gf) {

				if(!$gf) continue;

				if($array) {
					if(isset($array[$gf]) && $array[$gf]) {

						if($gmaps_temp[$i]['address']) $gmaps_temp[$i]['address'] .="<br/>";
						$gmaps_temp[$i]['address'] .= "<b>".$array[$gf]."</b>";
						
					}
				} // end if array

				// remove new lines because they break functionality
				$gmaps_temp[$i]['address'] = str_replace(array("\r\n", "\r", "\n"), "<br/>", $gmaps_temp[$i]['address']);
				
				$gmaps_array['gmaps_unique'][$j] = $gf;
				$gmaps_array['gmaps_fields'][$gf] = $g['caption'];
				$j++;	
			}
			$i++;
		}
	}
	else { $gmaps_temp = 0; }
	
	if($gmaps_temp) { 
		if(!$gmaps_array['gmaps']) 
			$gmaps_array['gmaps'] = $gmaps_temp;
		else {
			// add to existing array
			$n = count($gmaps_array['gmaps']);
			foreach($gmaps_temp as $g)
				$gmaps_array['gmaps'][$n++] = $g;
		}
	}
//_print_r($gmaps_array);
	return $gmaps_array;

}

function gmapsSmartyAssign($gmaps_array, &$smarty) {

	$smarty->assign("gmaps", $gmaps_array['gmaps']);
	$smarty->assign("gmaps_unique", $gmaps_array['gmaps_unique']);
	$smarty->assign("gmaps_fields", $gmaps_array['gmaps_fields']);
	$smarty->assign("gmaps_value_exists", $gmaps_array['value_exists']);

}

?>
