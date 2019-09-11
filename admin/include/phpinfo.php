<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

require_once "include.php";
global $config_demo;
if($config_demo) { echo "This action is not available on this demo."; exit(0); }
my_session_start();
$auth=new auth();
if(!$auth->adminLoggedIn()) exit(0);
phpinfo();
?>
