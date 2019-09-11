<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class security_periodic {

	public function __construct()
	{
	
	}


function blockAdminFailedAttempts() {

	global $db;
	$security_settings = common::getCachedObject("security_settings");
	$no_failed_attempts = $security_settings['block_admin_attempts'];
	
	$delete_expired=$settings['delete_expired'];
	$days_del_expired=$settings['days_del_expired'];

	if(!$delete_expired) return;

	// delete immediatelly
	$timestamp = date("Y-m-d H:i:s");

	if(!$days_del_expired) $sql="select id from ".TABLE_ADS." where `active`=0 and `date_expires` <= '$timestamp' and date_expires!='0000-00-00 00:00:00'";
	// delete after a time
	else $sql = "select id from ".TABLE_ADS." where active=0 and date_expires <= '$timestamp' and date_expires!='0000-00-00 00:00:00' and date_expires is not null and date_add(`date_expires`, interval '$days_del_expired' day) <= '$timestamp'";

	$listing = new listings;
	$res = $db->query($sql);
	while($l = $db->fetchRowRes($res) ) {
		$listing->delete($l);
	}

	return 1;

}

}
?>
