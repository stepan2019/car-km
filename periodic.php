<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/
require_once "include/include.php";
require_once "classes/mail_templates.php";
require_once "classes/mails.php";
require_once 'classes/config/sitemap_config.php';
require_once "classes/database.php";
require_once "classes/users_packages.php";
require_once "classes/pictures.php";
require_once "classes/periodic.php";
require_once "classes/fields.php";
require_once "classes/settings.php";

global $db;
global $ads_settings, $settings, $appearance_settings, $seo_settings;
global $minimal;
if($minimal) {

	require_once($config_abs_path.'/include/util.php');
	require_once($config_abs_path.'/classes/listings.php');
	require_once($config_abs_path.'/classes/categories.php');
	require_once($config_abs_path.'/classes/fields.php');

}

//common::getBaseCachedObjects();

$periodic=new periodic();

// ------------------ LISTINGS --------------------

// make ads expired and notify ( delete if the case )
$periodic->markExpiredAds();

// notify ads that will expire
$send_mail=$settings['send_mail_to_user_before_expires'];
if($send_mail) $periodic->notifyExpiredAds();

// delete and notify expired ads ( after time to keep expired has passed )
if($settings['delete_expired']>0) 
$periodic->deleteExpiredAds();

// delete unapproved and not active listings after 2 days
$periodic->deleteUnapprovedAds();

// expire featured, highlited, priority, video, store and send mail to the user
$periodic->markExpiredOption();

// ------------------ SUBSCRIPTIONS -----------------

// delete registrations for deleted ads
$periodic->deleteUnexistingAdBased();

// make subscription expired and notify
$periodic->markExpiredUsrPkg();

// notify subscriptions that will expire
if($send_mail) $periodic->notifyExpiredUsrPkg();

// delete expired subscriptions or subscriptions with no left ads
// delete after 3 days
$periodic->deleteExpiredUsrPkg(3);

// delete unapproved and not active subscriptions after 2 days
$periodic->deleteUnapprovedUsrPkg(2);

// ----------------- USERS ---------------------------

// delete password recovery records older than a day
$periodic->cleanRecoveryKeys();

// delete old login history records
$periodic->cleanAuthHistory();

// sitemap
$periodic->periodicSitemap();

// db_backup
$database = new database();
$database->periodic();

// remove unused images and files for fields
$periodic->removeUnusedFieldsFiles('cf');
$periodic->removeUnusedFieldsFiles('uf');

// remove older than one day files from temp folder ( export files )
$periodic->deleteOldExports();
// remove old template compiled files
$periodic->deleteOldCompiledFiles();

// periodic alerts check
if($ads_settings['alerts_enabled']) {
	require_once $config_abs_path."/classes/alerts.php";
	$periodic->periodicAlerts();
	// delete alert notifications after 30 days
	$periodic->deleteExpiredAlerts(30);
}

// recalculate time offset
settings::updateTimeOffset();

// release affiliates revenue
if($settings['enable_affiliates']) {

	require_once $config_abs_path."/classes/affiliates.php";
	$aff = new affiliates;
	$affiliate_groups = $aff->getAffiliateGroups();
	foreach($affiliate_groups as $agroup) {
		if($aff->timeToRelease($agroup))
			$aff->releasePayments($agroup);
	}
}

// delete old failed attempts
$periodic->deleteOldFailedAttempts();

// delete old searches
$periodic->deleteOldSearches(3);

do_action("periodic", array());

?>
