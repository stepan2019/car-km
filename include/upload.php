<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

require_once "../classes/images.php";
require_once "../classes/pictures.php";
require_once "include.php";
my_session_start();

global $config_abs_path;
require_once $config_abs_path."/libs/JSON.php";

global $ads_settings;

if(isset($_GET['id']) && is_numeric($_GET['id'])) $ad_id = $_GET['id']; 
else $ad_id = 0;

$pending_edited = 0;
global $ads_settings;
if($ads_settings['pending_edited'])
	$pending_edited = get_numeric("pending_edited");

if (isset($_GET['qqfile'])) {
	$mode = "xhr";
} elseif (isset($_FILES['qqfile'])) {
	$mode = "input";
}
else if (isset($_POST['deleteFile']) && $_POST['deleteFile']) {
	$_file = rawurldecode($_POST['origName']);
	$picture = new pictures();
	$picture->deletePicFile($_file, $pending_edited);
	exit(0);
}
else if (isset($_POST['orderUp']) && $_POST['orderUp']) {
	$_file = rawurldecode($_POST['orderUp']);
	$picture = new pictures();
	$ad_id = $picture->orderUp($_file, $pending_edited);
	if($ad_id==-1) { echo "0"; exit(0); }
	$response = $picture->getPicturesFormatted($ad_id, $pending_edited);
	echo $response;
	exit(0);
}
else if (isset($_POST['orderDown']) && $_POST['orderDown']) {
	$_file = rawurldecode($_POST['orderDown']);
	$picture = new pictures();
	$ad_id = $picture->orderDown($_file, $pending_edited);
	if($ad_id==-1) { echo "0"; exit(0); }
	$response = $picture->getPicturesFormatted($ad_id, $pending_edited);
	echo $response;
	exit(0);
}

require_once "../classes/settings.php"; 
$picture = new pictures();
$max = get_numeric("max_photos");
$crt_no = $picture->getNoPictures($ad_id, $pending_edited);

// if no more photos allowed
if($crt_no>=$max) $ret = array ("error" => $lng['images']['errors']['max_photos'] );
else {

	$result = $picture->add($ad_id, 'qqfile', $mode, $pending_edited);
	if(!$result) { 
		$err = $picture->getError();
		$err = str_replace("<br />", "\n", $err);
		$ret = array ("error" => $err );
	}
	else {
		$folder = $picture->getSubdir();
		global $config_abs_path, $config_live_site;

		$target_folder=$config_abs_path."/images/listings/";
		if($folder) $target_folder.=$folder."/";
		$src = $target_folder."thmb/" . $result;
		$size = @getimagesize($src);

		// make image url
		$url = $config_live_site."/images/listings/";
		if($folder) $url.=$folder."/";
		$url.="thmb/" . $result;

		$ret = array ("success" => true, "pst" => array ("img" => array("src" => $url, "name" => $result,  "rename" => $result, "width" => $size[0], "height" => $size[1])) );
	}

}

echo htmlspecialchars(json_encode($ret), ENT_NOQUOTES);

?>
