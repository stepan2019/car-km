<?php
/*
	*
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

require_once "include/include.php";
require_once "../classes/images.php";
require_once "../classes/languages.php";
require_once "../classes/config/languages_config.php";
require_once "../classes/config/depending_fields_config.php";
require_once $config_abs_path.'/admin/include/util.php';
global $db;
global $lng;
$smarty = new Smarty;
$smarty = common($smarty);
$smarty->assign("tab","settings");
$smarty->assign("lng",$lng);

global $array_maps;
$smarty->assign("array_maps",$array_maps);
$error='';
if(isset($_POST['Submit'])) {

    $lang = new languages_config();
    if(!$lang->add()) {
        $tmp = $lang->getTmp();
        $smarty->assign("language",$tmp);
        $error = $lang->getError();
    } else {
        $error = $lang->getError();
        if(!$error) {
            header("Location: home.php?query=languages");
            exit(0);
        }
    }

}
$smarty->assign("error",$error);

if($db->error!='') { $db_error = $db->getError(); $smarty->assign('db_error',$db_error); }
$smarty->display('add_language.html');

$db->close();
close();
?>
