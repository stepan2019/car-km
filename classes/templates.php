<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class templates {

	var $id;
	var $error;
	var $clean;
	var $array;
	var $tmp;

	public function __construct($mobile=0)
	{
	
		if($mobile)
			$this->templates_dir="../templates_mobile";
		else 
			$this->templates_dir="../templates";
		$this->error='';
		$this->folder='';

	}

	function setTemplate($tpl) {

		$this->template_dir=$this->templates_dir.'/'.$tpl;

	}

	function getFiles($ext) {

		$tpl = dir($this->template_dir);

		$templates=array();
		$i=0;
		while ($file = $tpl->read()) {

			if ($ext==getExtension($file)) { $templates[$i]= $file; $i++; }

		} 
		closedir($tpl->handle);

		sort($templates);

		return $templates;
	}

	function getCSSFiles($ext) {

		$tpl = dir($this->template_dir."/css");
		$templates=array();
		$i=0;
		while ($file = $tpl->read()) {

			if ($ext==getExtension($file)) { $templates[$i]= $file; $i++; }

		} 
		closedir($tpl->handle);

		sort($templates);

		return $templates;
	}

	function getTemplates() {

		$tpl = dir($this->templates_dir);

		$templates=array();
		$i=0;
		while ($file = $tpl->read()) {

			if($file!='.' && $file!='..' && is_dir($this->templates_dir.'/'.$file)) { $templates[$i]= $file; $i++; }

		} 
		closedir($tpl->handle);

		sort($templates);

		return $templates;
	}

	function readTemplateFile($file) {

		global $lng;
		$this->file=$file;
		$this->file_path=$this->template_dir.'/'.$file;

		if(!file_exists($this->file_path)) {

			$this->addError($lng['templates']['errors']['file_does_not_exist']);
			return 0;

		}

		if ( $fp = fopen( $this->file_path, 'r' ) ) {

			$content = fread( $fp, filesize( $this->file_path ) );
			$content = htmlspecialchars( $content, ENT_QUOTES );
			$this->setContent($content);
			fclose($fp);
			return 1;
		} else {

			$this->addError($lng['templates']['errors']['cannot_open_file_for_reading']);
			return 0;

		}

	}

	function readCssFile() {

		global $lng;
		$this->file_path=$this->template_dir.'/css/style.css';
		if(!file_exists($this->file_path)) {

			$this->addError($lng['templates']['errors']['file_does_not_exist']);
			return 0;

		}

		if ( $fp = fopen( $this->file_path, 'r' ) ) {

			$content = fread( $fp, filesize( $this->file_path ) );
			$content = htmlspecialchars( $content, ENT_QUOTES );
			$this->setContent($content);
			fclose($fp);
			return 1;
		} else {

			$this->addError($lng['templates']['errors']['cannot_open_file_for_reading']);
			return 0;

		}

	}

	function setContent($content) {

		$this->content=$content;

	}

	function setFolder($str) {

		$this->folder=$str."/";

	}

	function setFile($file) {
	
		$this->file=$file;
		$this->file_path=$this->template_dir.'/'.$this->folder.$file;

	}

	function setCssFile($tpl) {
	
		$this->file="style.css";
		$this->file_path=$this->template_dir.'/css/'.$this->file;

	}

	function getContent() {
	
		//$clean_str = $this->content;//(! _get_magic_quotes_gpc ()) ? stripslashes ($this->content) : $this->content;
		return $this->content;

	}

	function isWriteable() {

		global $lng;
		if(is_writable($this->file_path)==1) return '';
		$cgi = isCGI();
		if($cgi) $file_perm = "644";
		else $file_perm = "666"; 

		$error = str_replace("::WRITE_PERMISSION::", $file_perm, $lng['templates']['errors']['warning_file_not_writeable']);
		return $error;

	}


	function saveTemplate () {

		global $lng;

		global $config_demo;
		if($config_demo==1) { $this->addError($lng['general']['errors']['demo'].'<br />'); return 0; }

		$cgi = isCGI();

		if (!is_writable($this->file_path)) {
			if($cgi) $result = @chmod($this->file_path, 0644);
			else  $result = @chmod($this->file_path, 0666);
			if(!$result) {

				$this->addError($lng['templates']['errors']['cannot_write_file'].' '.$this->file_path);
				return 0;
			}
		}

		clearstatcache();

		if ( $fp = fopen ($this->file_path, 'w' ) ) {

			$content = ( _get_magic_quotes_gpc ()) ? stripslashes ($this->content) : $this->content;
			fputs( $fp, $content, strlen( $content ));
			fclose( $fp );
			//@chmod($this->file_path, 0444);

		} else {

			$this->addError($lng['templates']['errors']['failed_open_file_for_writing']);
			return 0;

		}
		return 1;
	}

	function getError() { return $this->error; }

	function addError($str) { $this->error.= $str; }

	function setError($str) { $this->error=$str; }

function getMod() {

	$val = 0;
	$val = substr(base_convert(fileperms($file_path), 10, 8), 3);
	return $val;

}

}
