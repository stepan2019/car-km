<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

require_once "include.php";
require_once $config_abs_path."/libs/JSON.php";

$post = get_numeric("post", 0);

if(!$post) {
$smarty = new Smarty;
$smarty->caching = 0;
$smarty = common($smarty);
$smarty->assign("lng",$lng);
}
else my_session_start();

$post_str = '';
if(isset($_POST['post_str'])) $post_str = cleanStr($_POST['post_str']);
if(isset($_GET['post_str'])) $post_str = cleanStr($_GET['post_str']);
$post_array = array();
if($post_str) $post_array = (array)json_decode($post_str, true);
foreach($post_array as $key=>$value) $post_array[$key] = base64_decode($value);

if($post) {

	$ret = array("response" => 0, "error" => null, "info" => null);

	if(!trim($post_str)) { 
		
		$ret['error'] = $lng['alerts']['no_terms_searched'];
		
		global $appearance_settings;
		if(strtolower($appearance_settings['charset'])!="utf-8") $ret = utf8_encode_all($ret);

		echo htmlspecialchars(json_encode($ret), ENT_NOQUOTES);
		exit(0); 

	} 

	require_once "../classes/alerts.php";
	require_once "../classes/mails.php";
	require_once "../classes/mail_templates.php";
	require_once "../classes/validator.php";
	require_once "../classes/fields.php";
	require_once "../classes/depending_fields.php";
	require_once "../classes/categories.php";

	if(isset($_POST['alert_email'])) $email = escape($_POST['alert_email']); else $email='';
	if(isset($_POST['alert_frequency'])) $frequency = escape($_POST['alert_frequency']); else $frequency='';

	$al = new alerts;
	$al->add($email, $frequency, $post_array);

	$ret['error'] = $al->getError();
	$ret['info'] = $al->getInfo();
	if(!$ret['error']) $ret['response'] = 1;

	global $appearance_settings;
	if(strtolower($appearance_settings['charset'])!="utf-8") $ret = utf8_encode_all($ret);

	echo json_encode($ret);

}
else
{

	require_once "../classes/alerts.php";
	require_once "../classes/fields.php";
	require_once "../classes/depending_fields.php";
	require_once "../classes/categories.php";

	$str_search = "";
	$alerts = new alerts();
	$str_search = $alerts->makeSearchString($post_array);
	$smarty->assign("str_search",$str_search);

	$smarty->assign("post_str", $post_str);
	$smarty->display('email_alert_box.html');
	close();
}


?>
