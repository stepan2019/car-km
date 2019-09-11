<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class pictures {

	var $error;
	var $array;

	public function __construct($id=0)
	{
	
		global $db;
		if($id) {
			$this->id=$id;
			$this->array=$db->fetchAssoc("select * from ".TABLE_ADS_PICTURES." where `id`=".$id." order by `order_no` asc");
		}
	}

	function getId() {
		return $this->id;
	}

	function delete($id=0) {

		global $db;
		global $config_abs_path;
		if(!$id) $id=$this->id;
		$res=$db->query("select `ad_id`, `picture`, `folder` from ".TABLE_ADS_PICTURES." where `id`='$id'");
		if($db->numRows($res)) { 
			$row=$db->fetchAssoc(); 
			$ad_id = $row['ad_id'];
			$folder = $row['folder'];
			$this->deleteFile($row['picture'], $folder);
		}
		$res_del=$db->query("delete from ".TABLE_ADS_PICTURES." where `id`='$id'");

		$this->reOrder($ad_id);
		return 1;
	}

	function reOrder($ad_id) {

		global $db;
		if(!$ad_id) return;
		$array = $db->fetchRowList("select id from ".TABLE_ADS_PICTURES." where `ad_id` = $ad_id order by `order_no` asc");
		$i = 1;
		foreach ($array as $row) {

			$db->query("update ".TABLE_ADS_PICTURES." set `order_no` = $i where `id` = $row");
			$i++;

		}

	}

	function deleteFile($str, $folder='') {

		global $config_abs_path;
		$target_folder=$config_abs_path."/images/listings";
		if($folder) $target_folder.="/".$folder;
		@unlink($target_folder.'/'.$str);
		@unlink($target_folder.'/thmb/'.$str);
		@unlink($target_folder.'/medThmb/'.$str);
		@unlink($target_folder.'/bigThmb/'.$str);
		@unlink($target_folder.'/mobile_thmb/'.$str);
		@unlink($target_folder.'/mobile_bigThmb/'.$str);

	}

	function deletePicFile($filename, $pending_edited=0) {

		global $db;
		$pic_arr=$db->fetchAssoc("select `id`, `ad_id` from ".TABLE_ADS_PICTURES." where `picture` like '$filename'");
		$pic_id = $pic_arr['id'];
		$ad_id = $pic_arr['ad_id'];

		if($pending_edited || !$pic_id) { 
			// might be stored in session
			// or we are in pending edited mode 
			$i = 0;
			foreach($_SESSION['pictures'] as $p) {
				if($p['picture']==$filename) { 
					array_splice($_SESSION['pictures'], $i, 1);

					if($pending_edited)
						$this->saveAsPendingEdited($ad_id);
					else 
						$this->deleteFile($filename, $p['folder']);

					break;
				}
				$i++;
			}
			return 1;
		}
		$this->delete($pic_id);
		return 1;

	}

	function getAd($id) {
		
		global $db;
		if(!$id) $id = $this->id;
		$id=$db->fetchRow("select ad_id from ".TABLE_ADS_PICTURES." where id = '$id'");
		if(!$id) return 0;
		return $id;

	}

	function getNoPictures($ad_id, $pending_edited=0) {

		global $db;
		$no_pics = 0;
		if($ad_id && !$pending_edited)
			$no_pics=$db->fetchRow("select count(*) from ".TABLE_ADS_PICTURES." where ad_id=".$ad_id);
		else  {
			if(isset($_SESSION['pictures']))
				$no_pics = count($_SESSION['pictures']);
		}
		return $no_pics;
	}

	function getPictures($ad_id) {
	
		global $db;
		global $config_abs_path;

		global $is_mobile;
		$mobile_str="";
		if($is_mobile) $mobile_str="mobile_";
		$ss=array();
		$array=$db->fetchAssocList("select * from ".TABLE_ADS_PICTURES." where `ad_id`='$ad_id' order by `order_no` asc");
		if(!empty($array)) {
			$i=0;
			foreach ($array as $row) {
	
				$folder_str = "";
				if($row['folder']) $folder_str = $row['folder']."/";

				$target_folder=$config_abs_path."/images/listings/".$folder_str;
				$thmb_target_folder=$config_abs_path."/images/listings/".$folder_str.$mobile_str."bigThmb/";

				$ss[$i]=$row;
				$size=@getimagesize($target_folder.$row['picture']);
				$ss[$i]['width']=$size[0];
				$ss[$i]['height']=$size[1];
				$size_thmb=@getimagesize($thmb_target_folder.$row['picture']);
				$ss[$i]['thmb_width']=$size_thmb[0];
				$ss[$i]['thmb_height']=$size_thmb[1];
				$i++;
			}
		}
		return $ss;
	}

	function getPicturesArray($ad_id) {
	
		global $db;
		$array=$db->fetchRowList("select `id` from ".TABLE_ADS_PICTURES." where `ad_id`='$ad_id'");
		return $array;

	}

	function getCSVPictures($ad_id) {
	
		global $db;
		global $config_live_site;
		$res_ss=$db->query("select picture, folder from ".TABLE_ADS_PICTURES." where `ad_id`='$ad_id' order by `order_no` asc");
		$str = '';
		if($db->numRows($res_ss)) {
			$array = $db->fetchAssocList();
			$i=0;
			foreach ($array as $row) {
				if($i) $str.=',';
				$folder_str = "";
				if($row['folder']) $folder_str = $row['folder']."/";
				$str.=$config_live_site.'/images/listings/'.$folder_str.$row['picture'];
				$i++;
			}
		}
		return $str;
	}


	function getPicturesFormatted($ad_id, $pending_edited=0) {
	
		global $db;
		global $config_abs_path, $config_live_site;

		require_once $config_abs_path."/libs/JSON.php";

		$str=""; 
		$array = array(); 
		$i=0;
		$target_folder=$config_abs_path."/images/listings/";

		if($ad_id && !$pending_edited) {
			$res_ss=$db->query("select * from ".TABLE_ADS_PICTURES." where `ad_id`='$ad_id' order by `order_no`");
			if($db->numRows($res_ss)) $array = $db->fetchAssocList();
		}
		else $array = $_SESSION['pictures'];

		foreach ($array as $row) {
			if($row['folder']) $folder_str = "{$row['folder']}/"; else $folder_str = "";
			$src = $target_folder.$folder_str."thmb/" . $row['picture'];
			$url = $config_live_site."/images/listings/".$folder_str."thmb/" . $row['picture'];
			$size = @getimagesize($src);
			$pst = array ("img" => array("src" => $url, "name" => $row['picture'],  "rename" => $row['picture'], "width" => $size[0], "height" => $size[1]));
			$str.="var a = ".json_encode($pst).";";
			$i++;
		}

		return $str;
	}

	function getPicture($id) {
	
		global $db;
		global $config_abs_path;
		$ss=array();
		$row=$db->fetchAssoc("select * from ".TABLE_ADS_PICTURES." where `id`='$id'");
		$folder_str = "";
		if($row['folder']) $folder_str = $row['folder']."/";
		$target_folder=$config_abs_path."/images/listings/".$folder_str;
		$ss['picture']=$target_folder.$row['picture'];
		$size=@getimagesize($target_folder.$row['picture']);
		$ss['width']=$size[0];
		$ss['height']=$size[1];
		return $ss;
	}

	function getPictureURL($id) {
	
		global $db, $config_live_site;

		$row=$db->fetchAssoc("select * from ".TABLE_ADS_PICTURES." where `ad_id`='$id' order by order_no asc limit 1");

		if(!$row) return '';

		$folder_str = "";
		if($row['folder']) $folder_str = $row['folder']."/";

		$url=$config_live_site."/images/listings/".$folder_str.$row['picture'];

		return $url;
	}

	function getPictureThmbURL($id) {
	
		global $db, $config_live_site;

		$row=$db->fetchAssoc("select * from ".TABLE_ADS_PICTURES." where `ad_id`='$id' order by order_no asc limit 1");

		if(!$row) return '';

		$folder_str = "";
		if($row['folder']) $folder_str = $row['folder']."/";

		$url=$config_live_site."/images/listings/".$folder_str."thmb/".$row['picture'];

		return $url;
	}


	function getThumb($id) {
	
		global $db;
		global $config_abs_path;
		$ss=array();
		$row=$db->fetchAssoc("select * from ".TABLE_ADS_PICTURES." where `id`='$id'");
		$folder_str = "";
		if($row['folder']) $folder_str = $row['folder']."/";
		$target_folder=$config_abs_path."/images/listings/".$folder_str."thmb/";
		$ss['picture']=$target_folder.$row['picture'];
		$size=@getimagesize($target_folder.$row['picture']);
		$ss['width']=$size[0];
		$ss['height']=$size[1];
		return $ss;
	}

	function deletePictures($ad_id) {
	
		global $db;
		$ss=$db->fetchRowList("select `picture` from ".TABLE_ADS_PICTURES." where `ad_id`='$ad_id'");
		foreach($ss as $row) { 
			$this->deleteFile($row);
		}
		$res_del=$db->query("delete from ".TABLE_ADS_PICTURES." where `ad_id`='$ad_id'");
	}

	function addToDB($ad_id, $file, $folder='', $pending_edited = 0) {

		global $db;
		// get last order no
		$last_order = $this->getMaxOrder($ad_id, $pending_edited);

 		if($ad_id && !$pending_edited) {

			if(!$last_order) $order_no = 1; else $order_no = $last_order+1;
			$res1=$db->query("insert into ".TABLE_ADS_PICTURES." (`ad_id`, `picture`, `order_no`, `folder`) values ('$ad_id', '$file', '$order_no', '$folder')");
			//echo "insert into ".TABLE_ADS_PICTURES." (`ad_id`, `picture`, `order_no`, `folder`) values ('$ad_id', '$file', '$order_no', '$folder')"
		}
		else { 
			if(!isset($_SESSION['pictures'])) $_SESSION['pictures'] = array();
			array_push($_SESSION['pictures'], array( "picture" => $file, "folder" => $folder ));
			if($pending_edited)
				$this->saveAsPendingEdited($ad_id);
		}

		return 1;
	}

	static function addSessionsToDB($ad_id) {

		global $db;

		$order_no = 1;
		foreach ($_SESSION['pictures'] as $p) {
			$res=$db->query("insert into ".TABLE_ADS_PICTURES." (`ad_id`, `picture`, `order_no`, `folder`) values ('$ad_id', '{$p['picture']}', '$order_no', '{$p['folder']}')");
			$order_no++;
		}

		return 1;
	}

	static function getNoSessionPictures() {

		return count($_SESSION['pictures']);
	}

	function createFolder($subdir_path) {

		// check if cgi or cli
		$cgi = isCGI();
		if($cgi) $mode = 0755;
		else $mode = 0777;

		// make main images folder
		@mkdir($subdir_path, $mode);
		@chmod($subdir_path, $mode);

		// make small thumbs folder
		@mkdir($subdir_path."/thmb", $mode);
		@chmod($subdir_path."/thmb", $mode);

		// make medium thumbs folder
		@mkdir($subdir_path."/medThmb", $mode);
		@chmod($subdir_path."/medThmb", $mode);

		// make big thumbs folder
		@mkdir($subdir_path."/bigThmb", $mode);
		@chmod($subdir_path."/bigThmb", $mode);

		// make mobile small thumbs folder
		@mkdir($subdir_path."/mobile_thmb", $mode);
		@chmod($subdir_path."/mobile_thmb", $mode);

		// make mobile big thumbs folder
		@mkdir($subdir_path."/mobile_bigThmb", $mode);
		@chmod($subdir_path."/mobile_bigThmb", $mode);
		
	
	}

	function add($ad_id, $form_name, $source="input", $pending_edited=0) {

		global $config_abs_path;
		$target_folder=$config_abs_path."/images/listings";
		$url_title="";

		// subfolder for images
		$subdir = date("Y-m");
		$subdir_path = $config_abs_path."/images/listings/".$subdir;
		$subdir_thmb = $config_abs_path."/images/listings/".$subdir."/thmb";
		if(!file_exists($subdir_path)) {

			$this->createFolder($subdir_path);

		}

		if(!file_exists($subdir_path) || !file_exists($subdir_thmb)) { $subdir_path = $target_folder; $subdir = ""; }
		$this->setSubdir($subdir);

		if( ($source=="input" && isset($_FILES[$form_name]['name']) && $_FILES[$form_name]['name']!='') || ( $source=="xhr" && isset($_GET[$form_name]) && $_GET[$form_name]!='')) {

			$image=new image($form_name, $subdir_path,'listing', $source);

			if(isset($_GET['title']) && $_GET['title']) 

				$url_title = start_words(_urlencode(strip_tags(escape($_GET['title']))), 40);

			else if(isset($_POST['title']) && $_POST['title']) 

				$url_title = start_words(_urlencode(strip_tags(escape($_POST['title']))), 40);

			else if($ad_id)	$url_title = start_words(listings::getUrlTitle($ad_id), 40);

			$image->setTitle($url_title);

			if(!$image->verify()) { $this->addError($image->getError()); return 0; }

			if($image->upload()) { 
				$clean['image']=$image->getUploadedFile();
			}

			else { $this->addError($image->getError()); return 0; }

		} else { $clean['image']=''; return 0; }

		if($clean['image']) $this->addToDB($ad_id,$clean['image'], $subdir, $pending_edited);

		return $clean['image'];
	}

	function setSubdir($subdir) {

		$this->subdir = $subdir;

	}

	function getSubdir() {

		return $this->subdir;

	}

	function addUrl($ad_id, $url) {

		global $config_abs_path;
		$target_folder=$config_abs_path."/images/listings";

		// subfolder for images
		$subdir = date("Y-m");
		$subdir_path = $config_abs_path."/images/listings/".$subdir;
		if(!file_exists($subdir_path)) {

			$this->createFolder($subdir_path);

		}
		if(!file_exists($subdir_path)) { $subdir_path = $target_folder; $subdir = ""; }
		$this->setSubdir($subdir);

		$image=new image($url, $subdir_path, 'listing', 'url');
		if($image->uploadUrl()) { 

			$clean['image']=$image->getUploadedFile();
			
		}
		else { $this->addError($image->getError()); return 0; }
		if($clean['image']) $this->addToDB($ad_id, $clean['image'], $subdir);
		return $clean['image'];
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

	function orderUp($file, $pending_edited=0) {
		
		global $db;

		$ord=$db->fetchAssoc("select `id`, `order_no`, `ad_id` from ".TABLE_ADS_PICTURES." where `picture` like '$file'");

		$id = $ord['id'];
		$order_no = $ord['order_no'];
		$ad_id = $ord['ad_id'];

		if($pending_edited || !$ord) { 
			// image stored in sessions
			// or we are in pending edited mode
			$i = 0;
			foreach($_SESSION['pictures'] as $p) {
				if($p['picture']==$file) {
					// first image, do not move
					if($i==0) return 0;
					// switch pictures
					$tmp = $_SESSION['pictures'][$i-1];
					$_SESSION['pictures'][$i-1] = $_SESSION['pictures'][$i];
					$_SESSION['pictures'][$i] = $tmp;

					if($pending_edited)
						$this->saveAsPendingEdited($ad_id);

					return 0;
				}
				$i++;
			}

			return 0;
		}
		if($order_no<=1) return -1;

		// get id of the prev order
		$ord = $db->fetchAssoc("select `id`, `order_no` from ".TABLE_ADS_PICTURES." where `ad_id` = $ad_id and `order_no`<$order_no order by `order_no` desc limit 1");

		if(!$ord) return -1;
		$goto = $ord['order_no'];
		$secondid = $ord['id'];

		$res1=$db->query("update ".TABLE_ADS_PICTURES." set `order_no`='$goto' where `id`='$id'");
		$res2=$db->query("update ".TABLE_ADS_PICTURES." set `order_no`='$order_no' where `id`='$secondid'");

		return $ad_id;
	}

	function orderDown($file, $pending_edited=0) {

		global $db;
		$ord=$db->fetchAssoc("select `id`, `order_no`, `ad_id` from ".TABLE_ADS_PICTURES." where `picture` like '$file'");

		$id = $ord['id'];
		$order_no = $ord['order_no'];
		$ad_id = $ord['ad_id'];

		if($pending_edited || !$ord) { 
			// image stored in sessions
			// or we are in pending edited mode	
			$i = 0;
			$no_pics = count($_SESSION['pictures']);
			foreach($_SESSION['pictures'] as $p) {
				if($p['picture']==$file) {
					// first image, do not move
					if($i==$no_pics-1) return 0;
					// switch pictures
					$tmp = $_SESSION['pictures'][$i+1];
					$_SESSION['pictures'][$i+1] = $_SESSION['pictures'][$i];
					$_SESSION['pictures'][$i] = $tmp;

					if($pending_edited)
						$this->saveAsPendingEdited($ad_id);

					return 0;
				}
				$i++;
			}
			return 0;
		}

		// get id of the prev order
		$ord = $db->fetchAssoc("select `id`, `order_no` from ".TABLE_ADS_PICTURES." where `ad_id` = $ad_id and `order_no`>$order_no order by `order_no` asc limit 1");
		if(!$ord) return -1;
		$goto = $ord['order_no'];
		$secondid = $ord['id'];

		$res1=$db->query("update ".TABLE_ADS_PICTURES." set `order_no`='$goto' where `id`='$id'");
		$res2=$db->query("update ".TABLE_ADS_PICTURES." set `order_no`='$order_no' where `id`='$secondid'");

		return $ad_id;
	}

	function getMaxOrder($ad_id, $pending_edited=0) {

		global $db;
		if($ad_id && !$pending_edited) 
			$last_order = $db->fetchRow("select `order_no` from ".TABLE_ADS_PICTURES." where `ad_id`=$ad_id order by `order_no` desc limit 1");
		else {
			if(!isset($_SESSION['pictures'])) return 0;
			return count($_SESSION['pictures']);
		}

		if(!$last_order) return 0;
		return $last_order;

	}

	function prepareRegenerate() {

		global $db;
		// first remove all thumbnails
		global $config_abs_path;

		// get main picture folders, exclude "thmb", "medThmb" and "bigThmb"
		$pictures_path = $config_abs_path."/images/listings";
		$exclude_folders = array(".", "..", "thmb", "medThmb", "bigThmb", "mobile_thmb", "mobile_bigThmb", ".svn");
		$exclude_files = array("index.html");

		$array_image_folders = array("");
		$folders_h = dir($pictures_path);
		while ($f = $folders_h->read()) {
			// do not use files
			if(!is_dir($pictures_path."/".$f)) continue;
			// exclude thumbnail folders 
			if(in_array($f, $exclude_folders)) continue;
			array_push($array_image_folders, $f);
		}
		closedir($folders_h->handle);

		// foreach of $array_image_folders
		foreach($array_image_folders as $imgfolder) {

			$folder_str = "";
			if($imgfolder) $folder_str = $imgfolder."/";
			
			$thmb_folders = array($config_abs_path."/images/listings/".$folder_str."thmb", $config_abs_path."/images/listings/".$folder_str."bigThmb", $config_abs_path."/images/listings/".$folder_str."medThmb", $config_abs_path."/images/listings/".$folder_str."mobile_thmb", $config_abs_path."/images/listings/".$folder_str."mobile_bigThmb");

			foreach($thmb_folders as $tf) {

				$folders_h = @dir($tf);
				if($folders_h) {
					while ($f = $folders_h->read()) {
						if(is_dir($tf."/".$f)) continue;
						if(!in_array($f, $exclude_files)) { 
							@unlink($tf.'/'.$f);
						}
					} 
					closedir($folders_h->handle);
				}
			}
		}

		$picture = $db->fetchRow("select `id` from ".TABLE_ADS_PICTURES." order by id desc limit 1");
		return $picture;

	}

	function regeneratePicture($id, $watermark) {

		global $db;
		$pic = $db->fetchAssoc("select `picture`, `folder` from ".TABLE_ADS_PICTURES." where id=$id");
		if(!$pic) return;

		$folder_str = "";
		if($pic['folder']) $folder_str = $pic['folder']."/";

		global $config_abs_path;
		if(!file_exists($config_abs_path."/images/listings/".$folder_str.$pic['picture'])) return;

		$img = new image;
		global $ads_settings;
		$img->setFolder($pic['folder']);
		$img->resize($pic['picture'], 'thmb', $ads_settings['thmb_width'], $ads_settings['thmb_height'], 0);
		$img->resize($pic['picture'], 'medThmb', $ads_settings['med_thmb_width'], $ads_settings['med_thmb_height'], 0);
		$img->resize($pic['picture'], 'bigThmb', $ads_settings['big_thmb_width'], $ads_settings['big_thmb_height'], $watermark);

 		$mobile_settings = $db->fetchAssoc("select * from ".TABLE_MOBILE_SETTINGS);

		if($mobile_settings['enable_mobile_templates']) {
			$img->resize($pic['picture'], 'mobile_thmb', $mobile_settings['mobile_thmb_width'], $mobile_settings['mobile_thmb_height'], 0);
			$img->resize($pic['picture'], 'mobile_bigThmb', $mobile_settings['mobile_big_thmb_width'], $mobile_settings['mobile_big_thmb_height'], 0);
		}

	}

	function RemoveUnused() {

		global $db;
		global $config_abs_path;

		// get main picture folders, exclude "thmb" and "bigThmb"
		$pictures_path = $config_abs_path."/images/listings";
		$exclude_folders = array(".", "..", "thmb", "medThmb", "bigThmb", "mobile_thmb", "mobile_bigThmb", ".svn");
		$exclude_files = array("index.html");

		$array_image_folders = array("");
		$folders_h = dir($pictures_path);
		while ($f = $folders_h->read()) {
			// do not use files
			if(!is_dir($pictures_path."/".$f)) continue;
			// exclude thumbnail folders 
			if(in_array($f, $exclude_folders)) continue;
			array_push($array_image_folders, $f);
		}
		closedir($folders_h->handle);

		// foreach of $array_image_folders
		foreach($array_image_folders as $imgfolder) {

			$folder_str = "";
			if($imgfolder) $folder_str = $imgfolder."/";
			$image_folder = $config_abs_path."/images/listings/".$folder_str;
			
			$folders_h = dir($image_folder);
			while ($f = $folders_h->read()) {
				if(is_dir($image_folder.$f)) continue;
				if(!in_array($f, $exclude_files)) { 
					$exists = 0;
					$exists = $db->fetchRow("select count(*) from ".TABLE_ADS_PICTURES." where `picture` like '$f' and folder like '$imgfolder'");
					if(!$exists && $f) { 
						@unlink($image_folder.$f);
						@unlink($image_folder.'thmb/'.$f);
						@unlink($image_folder.'medThmb/'.$f);
						@unlink($image_folder.'bigThmb/'.$f);
						@unlink($image_folder.'mobile_thmb/'.$f);
						@unlink($image_folder.'mobile_bigThmb/'.$f);
					}
				}

			} 
			closedir($folders_h->handle);
		}

	}

	function saveAsPendingEdited($ad_id) {

		global $config_abs_path, $db;
		require_once $config_abs_path."/libs/JSON.php";
		require_once $config_abs_path."/classes/listings_process.php";

		$encoded = json_encode($_SESSION['pictures']);

		// check if already exists
		$exists = $db->fetchAssoc("select * from ".TABLE_PENDING_EDITED." where `ad_id`='$ad_id'");

		if(!$exists)
			$db->query("insert into ".TABLE_PENDING_EDITED." set `ad_id`='$ad_id', `date` = '$timestamp', `pictures_edited` = '".escape($encoded)."'");
		else 
			$db->query("update ".TABLE_PENDING_EDITED." set `pictures_edited`='$encoded' where `ad_id`='$ad_id'");

		$lp = new listings_process();
		$lp->notifyAdminPendingEdited($ad_id);

		return 1;

	}

	function getPendingEdited($ad_id) {

		global $db, $config_abs_path;
		require_once $config_abs_path."/libs/JSON.php";

		$new_pictures = $db->fetchRow("select `pictures_edited` from ".TABLE_PENDING_EDITED." where `ad_id` = '$ad_id'");
		$pending_pictures = json_decode($new_pictures, true);

		return $pending_pictures;

	}

}

?>
