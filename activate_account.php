<?php

ini_set('display_errors', '1');
require_once "include/include.php";
require_once "setting/config.php";
global $lng;
if (isset($_GET['account']))
    $account = escape($_GET['account']);

if (isset($_GET['activation'])) $activation = escape($_GET['activation']);
$type = $_GET['type'];
$account = rawurldecode($account);
$post = get_numeric("post", 0);

my_session_start();
$comResult = $config->compareActivationCode($account, $_GET['activation'], $type);
if ($comResult->num_rows) {
    $set_active = $config->setActivation($account, $_GET['activation'], $type);
    if($set_active){
        $info = 'Successfully Activation';
    }else{
        $info = "Failed Activation";
    }
}else{
    $info = "Invalid Activation Code";
}
echo $info;


