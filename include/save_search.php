<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

require_once "include.php";
require_once "../classes/saved_searches.php";

$post = get_numeric("post", 0);

if(!$post) {
$smarty = new Smarty;
$smarty->caching = 0;
$smarty = common($smarty);
$smarty->assign("lng",$lng);
}
else my_session_start();

checkAuth();
global $crt_usr;
if(!$crt_usr) exit(0);

require_once $config_abs_path."/libs/JSON.php";

if(isset($_POST['post_str'])) $post_str = cleanStr($_POST['post_str']); else $post_str='';
$post_array = array();
if($post_str) $post_array = (array)json_decode($post_str, true);
foreach($post_array as $key=>$value) $post_array[$key] = base64_decode($value); 

$ss = new saved_searches;
$result = $ss->saveSearch($post_array);

$error=''; $info='';

if(!$result) $error = $ss->getError();
else $info = $lng['search']['search_saved'];

if(!$post) {

	$smarty->assign("error",$error);
	$smarty->assign("info",$info);
	$smarty->display('save_search_box.html');
	close();
} 
else {
	if($error) echo $error;
	else echo "0";
}
?>
