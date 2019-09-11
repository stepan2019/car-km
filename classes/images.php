<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class image {

	var $allowedtypes=array("jpeg", "jpg", "png", "gif");
	var $gd_info=array("GD Version"=>"", "FreeType Support"=>false, "FreeType Linkage"=>false, "T1Lib Support"=>false, "GIF Read Support"=>false, "GIF Create Support"=>false, "JPG Support"=>false, "PNG Support"=>false, "WBMP Support"=>false, "XBM Support"=>false, "JIS-mapped Japanese Font Support"=>false,);
	var $width;
	var $height;
	var $resize;
	var $thmb_type;
	var $thmb_dir;
	var $watermark='';
	var $watermark_position='';
	var $source = "input";
	var $resize_width;
	var $resize_height;
	var $resize_type;

public function __construct($input='', $dir='', $type="listing", $source = "input")
{

	$this->source = $source;
	$this->input=$input;
	$this->dir=$dir;
	$this->error='';
	if($this->source=="input" && $input ) $this->extension=getExtension($_FILES[$this->input]['name']);
	else if($this->source=="xhr" && $input) $this->extension=getExtension(escape($_GET['qqfile']));
	else if($this->source=="url" && $input ) $this->extension=getExtension($this->input);
	$this->generate=0;
	$this->type=$type;
	$this->width=0;
	$this->height=0;
	$this->size=0;
	$this->extra_no='';
	$this->resize=0;
	$this->thmb_type='';

	if($this->type=="listing") {

		$this->generate=1;
		if($this->source!="url") $this->extra_no=substr($this->input,5); else $this->extra_no="";

		global $ads_settings, $mobile_settings;
		if(!$mobile_settings) $mobile_settings = settings::getMobileSettings();

		$this->ads_settings = $ads_settings;
		$this->mobile_settings = $mobile_settings;
		$this->max_size=$this->ads_settings['pic_max_size'];
		$this->max_width=$this->ads_settings['pic_max_width'];
		$this->max_height=$this->ads_settings['pic_max_height'];
		if($this->ads_settings['watermark']) { 

			$this->watermark=$this->ads_settings['watermark'];
			$this->watermark_position=$this->ads_settings['watermark_position'];
			$this->watermark_transparency = $this->ads_settings['watermark_transparency'];

		}

		if($this->ads_settings['resize_image']) { 
			$this->resize = 1; 
			$this->thmb_type = 'resize'; 
		}

		global $config_abs_path;
		$this->thmb_dir = $target_folder=$dir;//$config_abs_path."/images/listings/";
	}
	else if($type=="store") {

		global $ads_settings;
		$this->ads_settings = $ads_settings;
		if($this->ads_settings['resize_store_image']) {
			$this->resize = 1; 
			$this->thmb_width=$this->ads_settings['resize_store_width'];
			$this->thmb_height=$this->ads_settings['resize_store_height'];
			global $config_abs_path;
			$this->thmb_dir = $target_folder=$config_abs_path."/images/store/";
		}

		$this->allowedtypes=array("jpeg", "jpg", "png", "gif", "swf");

	}

	$this->memory_limit=$this->get_memory_limit();

	$this->gd_info = function_exists('gd_info') ? ($this->gd_info=gd_info()) : $this->_gd_info();
	preg_match("/\A[\D]*([\d+\.]*)[\D]*\Z/", $this->gd_info['GD Version'], $matches);
	list($gd_version_string, $gd_version_number) = $matches;
	$this->gd_version = substr($gd_version_number, 0, strpos($gd_version_number, '.'));

}

function setTypes($array) {

	$this->allowedtypes=$array;

}
	
function setDir($dir) { $this->dir=$dir; }

function setGenerate($gen) { $this->generate=$gen; }

function getError() { return $this->error; }

function getUploadedFile() { return $this->dest_file; }

function setTitle($title) { $this->title = $title; }

function getTitle() { if(isset($this->title) && $this->title) return $this->title; }

function setDestFile() {

	if(!$this->generate) {

		$this->dest_file=$_FILES[$this->input]['name'];
		$this->dest_file_body=substr($this->dest_file, 0, ((strlen($this->dest_file)-strlen($this->extension)))-1);
		$this->dest_file_body = str_replace(array(' ', '_'), array('-','-'), $this->dest_file_body) ;
		$this->dest_file_body = preg_replace("/-+/", "-", $this->dest_file_body);
                $this->dest_file_body = preg_replace('/[^A-Za-z0-9_]/', '', $this->dest_file_body);
		$this->dest_file=$this->dest_file_body.'.'.$this->extension;
	
	} elseif($this->generate==1) {

		global $settings;
		
		$image_name = "";
		if($this->type=="listing") {
			$image_name = $this->getTitle();
		} elseif($this->type=="store") {
			global $settings;
			$image_name = start_words(_urlencode($_POST[$settings['contact_name_field']]), 40);
		}
		if(!$image_name) { 
			$rand_string = md5(uniqid(rand(),1));
			$image_name = substr($rand_string,0,8);
		} 
		$image_name = preg_replace("/[^A-Z0-9]/i", "_", $image_name);
		// add a random no so the name would not be the same
		$this->dest_file_body=$image_name.'-'.time()."-".rand(0,1000);

		if($this->extra_no!='') $this->dest_file_body.='-'.$this->extra_no;
		$this->dest_file=$this->dest_file_body.'.'.$this->extension;

	} else {

		$this->dest_file_body=$this->generate.'-'.time();
		if($this->extra_no!='') $this->dest_file_body.='-'.$this->extra_no;
		$this->dest_file=$this->dest_file_body.'.'.$this->extension;

	}

}

function setMaxSize($size) {

	$this->max_size = $size;
	return 1;

}

function setResize($val) { $this->resize=$val; }

function setResizeWidth($w) { $this->resize_width=$w; $this->thmb_width=$w; }

function setResizeHeight($h) { $this->resize_height=$h;  $this->thmb_height=$h; }

function setResizeType($t) { $this->resize_type=$t; }

function setThmbFolder($f) { $this->thmb_dir=$f; }

function upload() {

	global $lng;

	//resize to a smaller version
	// only for listings

	if($this->type=="listing") {
		$this->makeThumb($this->thmb_dir."/thmb", "small");
		$this->makeThumb($this->thmb_dir."/medThmb", "med");
		$this->makeThumb($this->thmb_dir."/bigThmb", "big");
		
		global $db;
		$mobile_settings = $db->fetchAssoc("select * from ".TABLE_MOBILE_SETTINGS);

		if($mobile_settings['enable_mobile_templates']) {
			$this->makeThumb($this->thmb_dir."/mobile_thmb", "small_mobile");
			$this->makeThumb($this->thmb_dir."/mobile_bigThmb", "big_mobile");
		}
		do_action("upload_listing_image", array($this));
	}

	if($this->resize) {

		if(isset($this->resize_type) && $this->resize_type) $resize_type = $this->resize_type;
		else $resize_type="resize";
		$ret = $this->makeThumb($this->thmb_dir, $resize_type);
		return $ret;
	} 
//	else {

		if($this->watermark) { // add watermark to the not resized file

			if($this->source=="url") $img_name=$this->dir.'/'.$this->dest_file;
			else 
			if($this->source=="input")
				$img_name=$_FILES[$this->input]['tmp_name'];
			else 
				$img_name=escape($_GET[$this->input]);

			$src_img = $this->myImageCreate($img_name);
					
			$this->thmb_width = $this->width;
			$this->thmb_height = $this->height;

			
			if(!$src_img){ 
				$this->error.="No source image!<br/>";
				return 0;
			}

			$this->addWatermark($src_img);

			// ---------------------- copy to file -------------------
			if(!$this->copyToFile($src_img,$this->dir.'/'.$this->dest_file)) { 
				$this->error.="Could not copy to image!<br/>";
				return 0;
			}

			$this->imgDestroy($src_img);

			return 1;

		}

		if($this->source=="input") {
			$img_name=$_FILES[$this->input]['tmp_name'];
			$result = @move_uploaded_file($img_name, $this->file_path);
		}
		else {
			$src_img = $this->myImageCreate(escape($_GET[$this->input]));
			$result = $this->copyToFile($src_img,$this->file_path);
		}

		if(!$result){
		
			$this->error.=$lng['images']['errors']['file_not_uploaded'].'<br />';
			return 0;
		} 
		else { // chmod just in case it is saved as 600
			@chmod($this->file_path, 0644); // or 640 !!!!!
		}
		return 1;
//	}
//	return $ret;
}

function verify(){

	global $lng;

	if(($this->source=="input" && !isset($_FILES[$this->input])) || ($this->source=="xhr" && !isset($_GET[$this->input]))) { 
		$this->error.=$lng['images']['errors']['no_file'].'<br />'; 
		return 0;
	}

	//verify if folder exists

	if(!is_dir($this->dir)) 
	{ 
		$this->error.=$lng['images']['errors']['no_folder'].'<br />';
		return 0;
	}

	//verify if folder is writeable
	if(!is_writeable($this->dir)) 
	{ 
		$this->error.=$lng['images']['errors']['folder_not_writeable'].'<br />';
		return 0;
	}

	// check if in allowed types
	if(!in_array($this->extension,$this->allowedtypes)) { 
		$this->error.=$lng['images']['errors']['file_type_not_accepted'].'<br />'; 
		return 0; 
	}

	// check if there has been an error while uploading
	if($this->source=="input" && $_FILES[$this->input]['error']) {
		$error=$lng['images']['errors']['error'];
		$this->error.=preg_replace ('/::ERROR::/',$_FILES[$this->input]['error'],$error);
		return 0;
	}

	//$needed=getNeededMemoryForImageCreate($width, $height, $truecolor);
	// calculeaza mem necesara !!!!!!

	$this->setDestFile();

	$this->file_path=$this->dir.'/'.$this->dest_file;

	if(is_file($this->file_path) && !$this->_is_writable($this->file_path))
	{ 

		$this->error.=$lng['images']['errors']['file_exists_unwriteable'].'<br />';
		//return 0;

	}

	if(isset($this->max_size) && $this->max_size) {

		if($this->source=="input")
			$this->size=filesize($_FILES[$this->input]['tmp_name']);
		else if($this->source="xhr") 
			if (isset($_SERVER["CONTENT_LENGTH"]))	$this->size = (int)$_SERVER["CONTENT_LENGTH"];

		if($this->size>($this->max_size*1000000)) // MB
		{ 

			$error=$lng['images']['errors']['file_uploaded_too_big'].'<br />';
			$this->error.=preg_replace ('/::MAX_FILE_UPLOAD_SIZE::/',$this->max_size, $error);
			return 0;
		}

	}

	if($this->source=="input") {
		$size=@getimagesize($_FILES[$this->input]['tmp_name']);
		$this->width=$size[0];
		$this->height=$size[1];
	} // end if source = input

	else if ($this->source=="xhr") {

		$tmp_img = file_get_contents('php://input');
		$image = ImageCreateFromString($tmp_img);
		$this->width = ImageSX($image);
		$this->height = ImageSY($image);
		$size[0] = $this->width; $size[1] = $this->height;

	}

	//test the size of the file
	if( isset($this->max_width) && isset($this->max_height) && $this->max_width && $this->max_height ) {

		if($size[0]>$this->max_width || $size[1]>$this->max_height)
		{ 

			$error=$lng['images']['errors']['file_dimmensions_too_big'].'<br />';
			$error1=preg_replace ('/::MAX_FILE_WIDTH::/',$this->max_width,$error);
			$this->error.=preg_replace ('/::MAX_FILE_HEIGHT::/',$this->max_height,$error1);
			return 0;
		}

	}

	return 1;
}


function uploadUrl() {

	global $lng;

	if(!$this->verifyUrl()) return 0;

	if($this->type=="listing") {
		$this->makeThumb($this->thmb_dir."/thmb", "small");
		$this->makeThumb($this->thmb_dir."/medThmb", "med");
		$this->makeThumb($this->thmb_dir."/bigThmb", "big");
		$this->makeThumb($this->thmb_dir."/mobile_thmb", "small_mobile");
		$this->makeThumb($this->thmb_dir."/mobile_bigThmb", "big_mobile");
	}

	if($this->resize) {

		$this->makeThumb($this->thmb_dir, $this->thmb_type);

	} 
	else {

		if($this->watermark) {

			$img_name=$this->dir.'/'.$this->dest_file;

			$this->thmb_width = $this->width;
			$this->thmb_height = $this->height;

			$src_img = $this->myImageCreate($img_name);
			if(!$src_img) return 0;

			$this->addWatermark($src_img);

			// ---------------------- copy to file -------------------
			if(!$this->copyToFile($src_img,$this->dir.'/'.$this->dest_file)) return 0;

			$this->imgDestroy($src_img);

		}
		 else if(!@copy($this->input, $this->file_path))
		{ 
		
			if(!$this->copyemz($this->input, $this->file_path)) {

				$this->error.=$lng['images']['errors']['file_not_uploaded'].'<br />';
				return 0;

			}
		}
	}
	return 1;
}

function verifyUrl(){

	global $lng;
	//verify if folder exists

	if(!is_dir($this->dir)) 
	{ 
		$this->error.=$lng['images']['errors']['no_folder'].'<br />';
		return 0;
	}

	//verify if folder is writeable
	if(!is_writeable($this->dir)) 
	{ 
		$this->error.=$lng['images']['errors']['folder_not_writeable'].'<br />';
		return 0;
	}

	// check if in allowed types
	if(!in_array($this->extension,$this->allowedtypes)) { 
		$this->error.=$lng['images']['errors']['file_type_not_accepted'].'<br />'; 
		return 0; 
	}

	$this->setDestFile();
	$this->file_path=$this->dir.'/'.$this->dest_file;

	// check if there has been an error while copying file
	if(!@copy($this->input, $this->file_path)) { 
		if(!$this->copyemz($this->input, $this->file_path)) {
			$this->error.=$lng['ie']['errors']['could_not_save_file'].$this->input;
			return 0; 
		}
	}

	if(is_file($this->file_path) && !$this->_is_writable($this->file_path))
	{ 

		$this->error.=$lng['images']['errors']['file_exists_unwriteable'].'<br />';
		return 0;

	}

	if(isset($this->max_size) && $this->max_size) {

		$this->size=filesize($this->file_path);

		if($this->size>($this->max_size*1000000)) // MB
		{ 

			$error=$lng['images']['errors']['file_uploaded_too_big'].'<br />';
			$this->error.=preg_replace ('/::MAX_FILE_UPLOAD_SIZE::/',$this->max_size,$error);
			return 0;
		}

	}

	$size=@getimagesize($this->file_path);
	$this->width=$size[0];
	$this->height=$size[1];

	//test the size of the file
	if( isset($this->max_width) && isset($this->max_height) && $this->max_width && $this->max_height ) {

		if($size[0]>$this->max_width || $size[1]>$this->max_height)
		{ 
			$error=$lng['images']['errors']['file_dimmensions_too_big'].'<br />';
			$error1=preg_replace ('/::MAX_FILE_WIDTH::/',$this->max_width,$error);
			$this->error.=preg_replace ('/::MAX_FILE_HEIGHT::/',$this->max_height,$error1);
			return 0;
		}

	}

	return 1;
}

function setMaxWidth($w) {

	$this->max_width = $w;
	return 1;

}

function setMaxHeight($w) {

	$this->max_height = $w;
	return 1;

}

function setThmbWidth($w) {

	$this->thmb_width = $w;
	return 1;

}

function setThmbHeight($h) {

	$this->thmb_height = $h;
	return 1;

}

function makeThumb($thmb_dir, $thmb_type='')
{

	global $lng;
	//verify if folder exists
	if(!is_dir($thmb_dir)) 
	{ 
		$this->error.=$lng['images']['errors']['no_thmb_folder'].'<br />';
		return 0;
	}

	//verify if folder is writeable
	if(!is_writeable($thmb_dir)) 
	{ 
		$this->error.=$lng['images']['errors']['thmb_folder_not_writeable'].'<br />';
		return 0;
	}
		
	if($this->source=="url") $img_name=$this->dir.'/'.$this->dest_file;
	else if($this->source=="input") $img_name=$_FILES[$this->input]['tmp_name'];
	else $img_name = escape($_GET[$this->input]);

	if($this->type=='listing' && $thmb_type!='') {

		if($thmb_type=='small') {

			$this->thmb_width=$this->ads_settings['thmb_width'];
			$this->thmb_height=$this->ads_settings['thmb_height'];

		} else 
		if($thmb_type=='med') {

			$this->thmb_width=$this->ads_settings['med_thmb_width'];
			$this->thmb_height=$this->ads_settings['med_thmb_height'];

		} else 
		if($thmb_type=='big') {

			$this->thmb_width=$this->ads_settings['big_thmb_width'];
			$this->thmb_height=$this->ads_settings['big_thmb_height'];

		} else if($thmb_type=='small_mobile') {

			$this->thmb_width=$this->mobile_settings['mobile_thmb_width'];
			$this->thmb_height=$this->mobile_settings['mobile_thmb_height'];

		} else 
		if($thmb_type=='big_mobile') {

			$this->thmb_width=$this->mobile_settings['mobile_big_thmb_width'];
			$this->thmb_height=$this->mobile_settings['mobile_big_thmb_height'];


		} else if($thmb_type=='resize') {
			$this->thmb_width=$this->ads_settings['resize_width'];
			$this->thmb_height=$this->ads_settings['resize_height'];
		}
		
		else if($thmb_type=='resize_custom') {
			$this->thmb_width=$this->resize_width;
			$this->thmb_height=$this->resize_height;
		}
		$thmb_name=$thmb_dir.'/'.$this->dest_file;

	} else { // only resize the image from the temp 

		$thmb_name=$this->dir.'/'.$this->dest_file;

	}
	
	$src_img = $this->myImageCreate($img_name);
	if(!$src_img) { 
		$this->error.="Could not create image<br/>";
		return 0;
	}

	if( $this->width < $this->thmb_width && $this->height < $this->thmb_height ) {
		// do not resize smaller images
		$this->thmb_height=$this->height;
		$this->thmb_width=$this->width;

	}
	else {
		$ratio1=$this->width/$this->thmb_width; 
		$ratio2=$this->height/$this->thmb_height;

		if($ratio1>$ratio2) $this->thmb_height=$this->height/$ratio1;
		else $this->thmb_width=$this->width/$ratio2;
	}

	if($this->gd_version>=2)
		$dst_img=ImageCreateTrueColor($this->thmb_width,$this->thmb_height);
	else 
		$dst_img=ImageCreate($this->thmb_width,$this->thmb_height);

if($this->extension=="png" || $this->extension=="gif") {
	
		imagealphablending($dst_img, false);
		if ( $this->extension=="png" ) imagesavealpha($dst_img,true);
		$transparent = imagecolorallocatealpha($dst_img, 255, 255, 255, 127);
		imagefilledrectangle($dst_img, 0, 0, $this->thmb_width,$this->thmb_height, $transparent);
	
}

	if($this->gd_version>=2)
		imagecopyresampled($dst_img,$src_img,0,0,0,0,$this->thmb_width,$this->thmb_height,$this->width,$this->height);
	else 
		imagecopyresized($dst_img,$src_img,0,0,0,0,$this->thmb_width,$this->thmb_height,$this->width,$this->height);

	// -------------------- watermark -----------------------
	if($this->watermark && $thmb_type != 'small' && $thmb_type != 'med' && $thmb_type != 'small_mobile' && $thmb_type != 'big_mobile') $this->addWatermark($dst_img);

	// ---------------------- copy to file -------------------
	if(!$this->copyToFile($dst_img,$thmb_name)) { 
		$this->error.="Could not copy to file!<br/>";
		return 0;
	}

	$this->imgDestroy($dst_img);
	$this->imgDestroy($src_img);

	// if resized reset the image dimmensions
	if($thmb_type=='resize') {
		$this->width=$this->thmb_width;
		$this->height=$this->thmb_height;
	}

	return 1;

}

function resize($img_name, $thmb_dir, $thmb_width, $thmb_height, $watermark)
{

	global $config_abs_path;
	$image_folder = $config_abs_path.'/images/listings/';
	
	if($this->getFolder()) $image_folder.=$this->getFolder()."/";

	$thmb_dir = $image_folder.$thmb_dir;

	// get folder out of thmb_dir

	$this->extension=getExtension($img_name);
	$size=@getimagesize($image_folder.$img_name);
	$this->width=$size[0];
	$this->height=$size[1];

	global $lng;
	if(!is_dir($thmb_dir)) 
	{ 
		$this->error.=$lng['images']['errors']['no_thmb_folder'].'<br />';
		return 0;
	}

	//verify if folder is writeable
	if(!is_writeable($thmb_dir)) 
	{ 
		$this->error.=$lng['images']['errors']['thmb_folder_not_writeable'].'<br />';
		return 0;
	}

	$this->thmb_width=$thmb_width;
	$this->thmb_height=$thmb_height;
	$thmb_name=$thmb_dir.'/'.$img_name;
	
	$src_img = $this->myImageCreate($image_folder.$img_name);
	if(!$src_img) { 
		$this->error.="Resize: Could not create image!<br/>";
		return 0;
	}

	$ratio1=$this->width/$this->thmb_width; 
	$ratio2=$this->height/$this->thmb_height;

	if($ratio1>$ratio2) $this->thmb_height=$this->height/$ratio1;
	else $this->thmb_width=$this->width/$ratio2;

	if($this->gd_version>=2)
		$dst_img=ImageCreateTrueColor($this->thmb_width,$this->thmb_height);
	else 
		$dst_img=ImageCreate($this->thmb_width,$this->thmb_height);

	if($this->gd_version>=2)
		imagecopyresampled($dst_img,$src_img,0,0,0,0,$this->thmb_width,$this->thmb_height,$this->width,$this->height);
	else 
		imagecopyresized($dst_img,$src_img,0,0,0,0,$this->thmb_width,$this->thmb_height,$this->width,$this->height);

	// -------------------- watermark -----------------------
	if($this->watermark && $watermark) $this->addWatermark($dst_img);

	// ---------------------- copy to file -------------------
	if(!$this->copyToFile($dst_img,$thmb_name)) { 
		$this->error.="Resize: Could not copy to file!<br/>";
		return 0;
	}

	$this->imgDestroy($dst_img);
	$this->imgDestroy($src_img);

	return 1;

}

function myImageCreate($img_name, $ext = '') {

	$wm=0; if($ext) $wm=1;
	if(!$ext) $ext = $this->extension;
	// when source is xhr and not watermark creation case
	if($this->source=="xhr" && !$wm) {

		$tmp_img = file_get_contents('php://input');
		$src_img = ImageCreateFromString($tmp_img);
		return $src_img;

	}

	global $lng;
	if($ext=="jpg" || $ext=="jpeg") {
		if (!function_exists('imagecreatefromjpeg')) { 
			$this->error.=$lng['images']['errors']['no_jpg_support'].'<br />';
			return 0;
		}
		$src_img=@imagecreatefromjpeg($img_name);
		if(!$src_img) { 
			$this->error.=$lng['images']['errors']['jpg_create_error'].'<br />'; 
			return 0;
		}
	}

	if($ext=="png") {
		if (!function_exists('imagecreatefrompng')) { 
			$this->error.=$lng['images']['errors']['no_png_support'].'<br />';
			return 0;
		}

		$src_img=@imagecreatefrompng($img_name);

		if(!$src_img) { 
			$this->error.=$lng['images']['errors']['png_create_error'].'<br />'; 
			return 0;
		}
	}

	if($ext=="gif") {
		if (!function_exists('imagecreatefromgif')) { 
			$this->error.=$lng['images']['errors']['no_gif_support'].'<br />';
			return 0;
		}
		$src_img=@imagecreatefromgif($img_name);
		if(!$src_img) { 
			$this->error.=$lng['images']['errors']['gif_create_error'].'<br />';
			return 0;
		}
	}
	return $src_img;
}

function copyToFile($dst_img,$thmb_name) {

	if($this->extension=="png") {
		
		$result=@imagepng($dst_img,$thmb_name);
		if(!$result) { 
			$this->error.=$lng['images']['errors']['no_png_support'].'<br />';
			return 0;
		}
	}

	if($this->extension=="gif")	{
		
		$result=@imagegif($dst_img,$thmb_name);
		if(!$result) { 
			$this->error.=$lng['images']['errors']['no_gif_support'].'<br />';
			return 0;
		}
	}

	if($this->extension=="jpg" || $this->extension=="jpeg")	{
		global $jpeg_compression;
		if($jpeg_compression)
			$result=@imagejpeg($dst_img,$thmb_name,$jpeg_compression);
		else
			$result=@imagejpeg($dst_img,$thmb_name);
		if(!$result) { 
			global $lng;
			$this->error.=$lng['images']['errors']['no_jpg_support'].'<br />';
			return 0;
		}
	}
	return 1;

}

function imgDestroy($img) {

	if (is_resource($img)) imagedestroy($img);

}

function addWatermark($dst_img) {

	global $config_abs_path;
	$watermark_file=$config_abs_path.'/images/'.$this->watermark;

	if(!file_exists($watermark_file)) return;

	$wext=getExtension($watermark_file);

	$watermark = $this->myImageCreate($watermark_file, $wext);

	if(!$watermark) {
		$this->error.="Could not create watermark file!<br/>";
		return 0;
	}

	$watermark_width = imagesx($watermark);
	$watermark_height = imagesy($watermark);

	switch ($this->watermark_position) {

		case 'tl':
			$dest_x = 5;
			$dest_y = 5;
			break;
		case 'tr':
			$dest_x = $this->thmb_width - $watermark_width - 5;
			$dest_y = 5;
			break;
		case 'bl':
			$dest_x = 5;
			$dest_y = $this->thmb_height - $watermark_height - 5;
			break;
		case 'br':
			$dest_x = $this->thmb_width - $watermark_width - 5;
			$dest_y = $this->thmb_height - $watermark_height - 5;
			break;
		case 'c':
			$dest_x = (($this->thmb_width - $watermark_width)/2);
			$dest_y = (($this->thmb_height - $watermark_height)/2); 
			break;

	}

	$this->imagecopymerge_alpha($dst_img, $watermark, $dest_x, $dest_y, 0,0, $watermark_width, $watermark_height, (100-$this->watermark_transparency));

}

function imagecopymerge_alpha($dst_im, $src_im, $dest_x, $dest_y, $src_x, $src_y, $src_w, $src_h, $pct){

        // creating a cut resource
        $cut = imagecreatetruecolor($src_w, $src_h);

        // copying relevant section from background to the cut resource
        imagecopy($cut, $dst_im, 0, 0, $dest_x, $dest_y, $src_w, $src_h);

        // copying relevant section from watermark to the cut resource
        imagecopy($cut, $src_im, 0, 0, $src_x, $src_y, $src_w, $src_h);

        // insert cut resource to destination image
        imagecopymerge($dst_im, $cut, $dest_x, $dest_y, 0, 0, $src_w, $src_h, $pct);

}


function _gd_info() {

	$gif_support = 0;
	ob_start();
	eval("phpinfo();");
	$info = ob_get_contents();
	ob_end_clean();

	foreach(explode("\n", $info) as $line) {
		if(strpos($line, "GD Version") !== false)
			$this->gd_info["GD Version"] = trim(str_replace("GD Version", "", strip_tags($line)));
		if(strpos($line, "FreeType Support") !== false)
			$this->gd_info["FreeType Support"] = trim(str_replace("FreeType Support", "", strip_tags($line)));
		if(strpos($line, "FreeType Linkage") !== false)
			$this->gd_info["FreeType Linkage"] = trim(str_replace("FreeType Linkage", "", strip_tags($line)));
		if(strpos($line, "T1Lib Support") !== false)
			$this->gd_info["T1Lib Support"] = trim(str_replace("T1Lib Support", "", strip_tags($line)));
		if(strpos($line, "GIF Read Support") !== false)
			$this->gd_info["GIF Read Support"] = trim(str_replace("GIF Read Support", "", strip_tags($line)));
		if(strpos($line, "GIF Create Support") !== false)
			$this->gd_info["GIF Create Support"] = trim(str_replace("GIF Create Support", "", strip_tags($line)));
		if(strpos($line, "GIF Support") !== false)
			$gif_support = trim(str_replace("GIF Support", "", strip_tags($line)));
		if(strpos($line, "JPG Support") !== false)
			$this->gd_info["JPG Support"] = trim(str_replace("JPG Support", "", strip_tags($line)));
		if(strpos($line, "PNG Support") !== false)
			$this->gd_info["PNG Support"] = trim(str_replace("PNG Support", "", strip_tags($line)));
		if(strpos($line, "WBMP Support") !== false)
			$this->gd_info["WBMP Support"] = trim(str_replace("WBMP Support", "", strip_tags($line)));
		if(strpos($line, "XBM Support") !== false)
			$this->gd_info["XBM Support"] = trim(str_replace("XBM Support", "", strip_tags($line)));
	}

	if($gif_support === "enabled") {
		$this->gd_info["GIF Read Support"] = true;
		$this->gd_info["GIF Create Support"] = true;
	}

	if($this->gd_info["FreeType Support"] === "enabled") {
		$this->gd_info["FreeType Support"] = true;
	}
 
	if($this->gd_info["T1Lib Support"] === "enabled") {
		$this->gd_info["T1Lib Support"] = true;
	}

	if($this->gd_info["GIF Read Support"] === "enabled") {
		$this->gd_info["GIF Read Support"] = true;
	}
 
	if($this->gd_info["GIF Create Support"] === "enabled") {
		$this->gd_info["GIF Create Support"] = true;
	}

	if($this->gd_info["JPG Support"] === "enabled") {
		$this->gd_info["JPG Support"] = true;
	}

	if($this->gd_info["PNG Support"] === "enabled") {
		$this->gd_info["PNG Support"] = true;
	}

	if($this->gd_info["WBMP Support"] === "enabled") {
		$this->gd_info["WBMP Support"] = true;
	}

	if($this->gd_info["XBM Support"] === "enabled") {
		$this->gd_info["XBM Support"] = true;
	}

	//return $array;
}

function get_memory_limit() {

	$val = trim(ini_get('memory_limit'));
	$last = strtolower($val{strlen($val)-1});
	$val = substr($val, 0, strlen($val)-1);

	switch($last) {
		case 'g':
		$val *= 1024;
		case 'm':
		$val *= 1024;
		case 'k':
		$val *= 1024;
	}
	return $val;

}

function getNeededMemoryForImageCreate($width, $height, $truecolor) {
  return $width*$height*(2.2+($truecolor*3));
}


function _is_writable($path) {

    if ($path{strlen($path)-1}=='/') // recursively return a temporary file path
        return is__writable($path.uniqid(mt_rand()).'.tmp');
    else if (is_dir($path))
        return is__writable($path.'/'.uniqid(mt_rand()).'.tmp');
    // check tmp file for read/write capabilities
    $rm = file_exists($path);
    $f = @fopen($path, 'a');
    if ($f===false)
        return false;
    fclose($f);
    if (!$rm)
        unlink($path);
    return true;
}

function copyemz($file1,$file2){
	$contentx =@file_get_contents($file1);
	$openedfile = fopen($file2, "w");
	fwrite($openedfile, $contentx);
	fclose($openedfile);
	if ($contentx === FALSE) {
		$status=false;
	}else $status=true;
return $status;
} 

function getFolder() {

	return $this->folder;

}

function setFolder($folder) {

	$this->folder = $folder;

}

}
?>
