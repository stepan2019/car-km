<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

$path=dirname(__FILE__);
require_once($path.'/include.php');
if(isset($_GET['now'])) $now = $_GET['now']; else $now = 0;
$current_version = common::getCurrentVersion($now);
echo $current_version;
?>