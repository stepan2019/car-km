<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

$session_name = session_name("oxss");
session_start();
$no = 5;

if(isset($_GET['encoded']) && $_GET['encoded']==1) $encoded=1; else $encoded=0;

function random_string($len=5, $str='')
{
	for($i=1; $i<=$len; $i++)
	{
		$ord=rand(49, 104);
		if((($ord >= 49) && ($ord <= 57)) || (($ord >= 97) && ($ord <= 104)))
			$str.=chr($ord);
		else
			$str.=random_string(1);	
	}
	return $str;
}
$rand_str=random_string($no);
$_SESSION['image_value'] = md5($rand_str);

$image =imagecreatefrompng("../images/noise.png");

$size = 24;
if(@function_exists(imagettftext)) {

	$colors = array ( array(0, 0, 0), array(155,28,28), array(17,45,123), array(0, 0, 0), array(9,102,28), array(5,82,142), array(39,39,39), array(0,0,0), array(145,0,27));
	for($i=0; $i<$no; $i++) { 
		$letter=substr($rand_str,$i,1);
		$angle = rand(-20, 20);
		$font = "../include/fonts/".rand(1, 7).".ttf";
		$color=rand(0, 8);
		$textColor = imagecolorallocate ($image, $colors[$color][0],$colors[$color][1], $colors[$color][2]);
		imagettftext($image, $size, $angle, 10+(25*$i), $size+15, $textColor, $font, $letter);
	}

} else imagestring($image, 100, 50, 15, $rand_str, 3);

header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header('Content-type: image/jpeg');

if($encoded==1) { 
	ob_start();
	imagejpeg($image);
	$data = ob_get_contents();
	ob_end_clean();
	$base64 = 'data:image/jpg;base64,' . base64_encode($data);
	echo $base64; 
	exit(0); 
}

imagejpeg($image);
imagedestroy($image);

?>
