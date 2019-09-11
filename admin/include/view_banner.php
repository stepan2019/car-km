<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

require_once "../../classes/banners.php";
require_once "include.php";
$id = get_numeric_only("id");

$b = new banners();
$banner = $b->getBanner($id);
$extension = $banner['extension'];
$filename = $banner['filename'];
if($filename) $size=@getimagesize('../../images/baners/'.$filename);
?>
<html>
<head>
</head>
<body>
<?php
if($banner['code']!='') {

	echo $banner['code'];

} else 
if($extension=='swf') {
?>
	<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000"
	codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,79,0"
	id="<?=$filename?>" width="<?=$size[0]?>" height="<?=$size[1]?>">
	<param name="movie" value="../../images/baners/<?=$filename?>">
	<param name="bgcolor" value="#FFFFFF">
	<param name="quality" value="high">
	<param name="allowscriptaccess" value="samedomain">
	<embed type="application/x-shockwave-flash"
          pluginspage="http://www.macromedia.com/go/getflashplayer"
          name="<?=$filename?>"
          src="../../images/baners/<?=$filename?>"
	  width="<?=$size[0]?>" height="<?=$size[1]?>"
          quality="high"
          swliveconnect="true"
          allowscriptaccess="samedomain">
          <noembed>
          </noembed>
	</embed>
	</object>
<?php
}
?>
</body>
</html>
