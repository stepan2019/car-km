<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

require_once($config_abs_path."/classes/payment.php");
require_once($config_abs_path."/classes/payment_processors.php");
require_once($config_abs_path.'/classes/payment_actions.php');

$pp_list = payment_processors::getActivePPList();

require_once($config_abs_path.'/classes/payment/free.php');
require_once($config_abs_path.'/classes/payment/credits_payment.php');

foreach($pp_list as $pp) { 
	require_once($config_abs_path.'/classes/payment/'.$pp.'.php');
}
?>
