<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

require_once "../../classes/custom_pages.php";
require_once "include.php";

global $config_live_site, $appearance_settings;

$id = get_numeric_only("id");

$cp = new custom_pages();
$title=$cp->getTitle($id);
$content=$cp->getContent($id);

?>
<!DOCTYPE html>
<html>
<head>
<title><?=$title?></title>
</head>
<body>
<?=$content?>
</body>
</html>
