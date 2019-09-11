<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class cache {

	var $cache_path = "";

	public function __construct()
    {
		global $config_abs_path;
		$this->cache_path = $config_abs_path."/temp";
    }

	function cache() {

	}

	function readCache($cache_name, $lang_name='', $param=null) {

		global $cached_vars;
		$extra_param = '';
		if($param)
			foreach($param as $key => $value) $extra_param.=$value;
		$cache_file_name = base64_encode( $cache_name ).$extra_param.$lang_name;
		if(isset($cached_vars[$cache_file_name])) return $cached_vars[$cache_file_name];
		if ( ! is_dir( $this->cache_path ) ) {

			echo "Error, invalid cache directory!";
			return null;	
		}

		$cache_file = $this->cache_path."/".$cache_file_name.".CCH";
		if(file_exists($cache_file)) {

			$raw_data = file_get_contents( $cache_file );
			$data = unserialize( base64_decode( $raw_data ) );
			$cached_vars[$cache_file_name] = $data;
			return $data;

		}
		return null;
	
	}

	function writeCache($cache_name, $data, $lang_name='', $clear_first = 0, $param = null) {

		if($clear_first)  $this->clearCache($cache_name);

		if ( ! is_dir( $this->cache_path ) ) {

			echo "Error, invalid cache directory!";
			return 0;
		}

		$extra_param = '';
		if($param)
			foreach($param as $key => $value) $extra_param.=$value;

		$cache_file = $this->cache_path."/".base64_encode( $cache_name ).$extra_param. $lang_name .".CCH";

		$enc_data = base64_encode( serialize( $data ) );
		if($f = fopen($cache_file, "w")) {
			fwrite($f, $enc_data);
			fclose($f);
			$this->chmodCache($cache_file);
			return 1;
		}

	}

	function chmodCache($cache_file) {

		$cgi = isCGI();
		if($cgi) $result = @chmod($cache_file, 0644);
		else $result = @chmod($cache_file, 0666);
		//if(!$result) echo "Could not chmod cache file !!!";

	}	
	
	function clearCache($cache_name){

		// delete for all languages
		$folders_h = dir($this->cache_path);
		while ($f = $folders_h->read()) {
			// only cache files

			if(getExtension($f) != "cch") continue;
			if(!preg_match("/".base64_encode($cache_name)."(.*)\.CCH/i", $f) ) continue;

			$cache_file = $this->cache_path."/".$f;

			if(file_exists($cache_file)) 
				@unlink($cache_file);
			
		}
		closedir($folders_h->handle);

	}

	function clearAllCacheFiles(){

		$folders_h = dir($this->cache_path);
		while ($f = $folders_h->read()) {
			// only cache files
			$ext = getExtension($f);
			if( $ext != "cch" && $ext != "php") continue;

			$cache_file = $this->cache_path."/".$f;

			if(file_exists($cache_file)) 
				@unlink($cache_file);
			
		}
		closedir($folders_h->handle);

	}


	function rebuildCache() {

	}

}
