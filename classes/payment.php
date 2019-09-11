<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class payment {

	var $processor; //paypal, 2co, mb, manual, free
	var $amount;
	var $actions_array; // array( "ad_id" => 0, "pkg_id" => 0, "newad" => array( 'value' => 0 , 'price' => 0) , "renewad" => array( 'value' => 0 , 'price' => 0), "featured" => array( 'value' => 0 , 'price' => 0), "highlited" => array( 'value' => 0 , 'price' => 0), "priority" => array( 'value' => 0 , 'price' => 0), "video" => array( 'value' => 0 , 'price' => 0), "newpkg" => array( 'value' => 0 , 'price' => 0), "renewpkg" => array( 'value' => 0 , 'price' => 0) );

	var $user_id;
	var $pay;
	var $debug;
	var $user_key;
	var $payment_processor;
	var $pending;
	var $tax;
	var $affiliate;
	var $error;

	public function __construct($processor, $key='')
	{

		$this->processor = $processor;
		global $config_debug;
		$this->debug=$config_debug;
		$this->nolog = 0;

		$pp = new payment_processors();
		$this->payment_processor = $pp->getPaymentProcessor($processor);
		$this->pending = $this->payment_processor['pending'];
		$class_name = $this->payment_processor['processor_class'];

		global $config_abs_path;
		require_once $config_abs_path."/classes/payment/".$class_name.".php";

		$this->pay = new $class_name;
		global $config_table_prefix;
		//$this->pay->setTable($config_table_prefix.$this->payment_processor['processor_table']);
		if($key) 
			$this->pay->init($key); 
		else 
			$this->pay->init();
		
		$this->pay->setDebug($this->debug);
		$this->user_key = $this->pay->getUserKey();
		$this->affiliate = "";

	}

	function getManual() {

		return $this->payment_processor['manual'];

	}

	function isPending() {

		return $this->payment_processor['pending'];

	}

	function setDebug($val) {

		$this->debug = $val;
		$this->pay->setDebug($val);

	}

	function setNolog($val) {

		$this->nolog = $val;

	}

	function setFormTitle($val) {

		$this->pay->setFormTitle($val);

	}

	function setAmount($str) {

		$this->amount = $str;
		$this->pay->setAmount($str);

	}

	function setSubscription($total, $days) {

		$this->amount = $total;
		$this->pay->setSubscription($total, $days);

	}

	function setFirstSubscription($total, $days) {

		$this->amount = $total;
		$this->pay->setFirstSubscription($total, $days);

	}

	function setActionsArray($array) {

		$this->actions_array = $array;

	}

	function setItemName($str) {

		$this->pay->setItemName($str);

	}

	function setUserId($id) {

		$this->user_id = $id;

	}

	function setRecipient($str) {

		$this->pay->setRecipient($str);

	}

	function disableReturn() {

		$this->pay->disableReturn();

	}

	function setInfo($str) {

		$this->info = $str;

	}

	function getInfo() {

		return $this->info;

	}

	function getError() {
	
		return $this->error;

	}

	function getPPError() {
	
		return $this->pay->getError();
		
	}
	
	function addError($str) {

		$this->error.=	$str;

	}

	function setError($str) {

		$this->error=$str;

	}

	
	function writeForm($invoice='')
	{

		global $db, $settings;

		$post = $this->pay->getPost();

		// add to tables only if not in a complete payment process
		if(!$invoice && !$this->nolog) {

			$timestamp = date("Y-m-d H:i:s");

			$aff_str = "";

			if($settings['enable_affiliates'] && !empty($_COOKIE["affiliate"])) $aff_str = ", `affiliate_id` = '{$_COOKIE["affiliate"]}'";

			$res = $db->query("INSERT INTO ".TABLE_PAYMENT_ACTIONS." SET `processor`='".$this->processor."', user_id='".$this->user_id."', ukey='".$this->user_key."', `action` = '".addslashes(serialize($this->actions_array))."', amount = '".$this->amount."', post='".addslashes(serialize($post))."', date='$timestamp', `tax` = '{$this->tax}'".$aff_str);

			$invoice=$db->insertId();
			$this->addActions($invoice);

		}

		$this->pay->setInvoiceNo($invoice);

		$form = $this->pay->getForm();
		
		$err = $this->pay->getError();
		if($err) $this->setError($err);

		return $form;

	}

	// add to actions table
	function addActions($invoice_no) {

		//$actions_array = array( "ad_id" => 0, "pkg_id" => 0, "newad" => array( 'value' => 0 , 'price' => 0) , "renewad" => array( 'value' => 0 , 'price' => 0), "featured" => array( 'value' => 0 , 'price' => 0), "highlited" => array( 'value' => 0 , 'price' => 0), "priority" => array( 'value' => 0 , 'price' => 0), "video" => array( 'value' => 0 , 'price' => 0), "newpkg" => array( 'value' => 0 , 'price' => 0), "renewpkg" => array( 'value' => 0 , 'price' => 0), "store" => array( 'value' => 0 , 'price' => 0), "discount_code" =>'' );
		global $config_abs_path;
		require_once $config_abs_path."/classes/actions.php";
		require_once $config_abs_path."/classes/groups.php";

		$action = new actions();
		if($this->user_id) {
			$user = new users();
			$user_group = $user->getGroup($this->user_id);
		} else $user_group = 0;
		$group = new groups();

		$object_id = 0;
		$action_pending=0;
		if(!empty($this->actions_array['ad_id']) && $this->actions_array['ad_id']!=0) {

			if($group->getListingPending($user_group)) $ad_pending=1; 
			else $ad_pending = 0;

			if($ad_pending || $this->pending) $pending = 1;
			else $pending = 0;
			
			$extra_options_pending = $this->pending;

			$object_id = $this->actions_array['ad_id'];

			if( ( isset($this->actions_array['newad']['value']) && $this->actions_array['newad']['value']!=0) ||  ( isset($this->actions_array['renewad']['value']) && $this->actions_array['renewad']['value']!=0)) {

				if($this->actions_array['newad']['value']!=0) $type='newad'; else $type='renewad';
				$action->add($type, $object_id, $this->user_id, $invoice_no, $pending);

			}

			// check if option is included or not in the plan!
			require_once $config_abs_path."/classes/packages.php";
			$pkg_id = listings::getPackage($this->actions_array['ad_id']);
			$p = new packages();
			$pkg_details = $p->getPackage($pkg_id);
			
			if(isset($this->actions_array['featured']['value']) && $this->actions_array['featured']['value'] && (!$pkg_details['featured'] || $pkg_details['featured']!=$this->actions_array['featured']['value'])) {

				$type = "featured";
				$action->add($type, $object_id, $this->user_id, $invoice_no, $extra_options_pending, $this->actions_array['featured']['value']);

			}
			if(isset($this->actions_array['highlited']['value']) && $this->actions_array['highlited']['value'] && !$pkg_details['highlited']) {
	
				$type="highlited";
				$action->add($type, $object_id, $this->user_id, $invoice_no, $extra_options_pending);
			}

			if(isset($this->actions_array['priority']['value']) && $this->actions_array['priority']['value'] && (!$pkg_details['priority'] || $pkg_details['priority']!=$this->actions_array['priority']['value'])) {

				$type = "priority"; 
				$action->add($type, $object_id, $this->user_id, $invoice_no, $extra_options_pending, $this->actions_array['priority']['value']);
			}
			
			if(isset($this->actions_array['urgent']['value']) && $this->actions_array['urgent']['value'] && !$pkg_details['urgent']) {

				$type = "urgent";
				$action->add($type, $object_id, $this->user_id, $invoice_no, $extra_options_pending);

			}

			if(isset($this->actions_array['video']['value']) && $this->actions_array['video']['value'] && !$pkg_details['video']) {
	
				$type="video";
				$action->add($type, $object_id, $this->user_id, $invoice_no, $extra_options_pending);
			}
			
			if(isset($this->actions_array['url']['value']) && $this->actions_array['url']['value'] && !$pkg_details['url']) {
	
				$type="url";
				$action->add($type, $object_id, $this->user_id, $invoice_no, $extra_options_pending);
			}

		}

		if( isset($this->actions_array['pkg_id']) && $this->actions_array['pkg_id']!=0) {

			if($group->getPackagePending($user_group)) $plan_pending=1;
			else $plan_pending = 0;

			if($plan_pending || $this->pending) $pending = 1;
			else $pending = 0;

			$type='';
			if($this->actions_array['newpkg']['value']!=0) $type='newpkg'; 
			else if($this->actions_array['renewpkg']['value']!=0) $type='renewpkg';
			if($type) {
				$object_id = $this->actions_array['pkg_id'];
				$action->add($type, $object_id, $this->user_id, $invoice_no, $pending);
			}
		}

		if(isset($this->actions_array['store']) && $this->actions_array['store']['value']!=0) {

			$type = "store"; 
			$object_id = $this->user_id;
			$action->add($type, $object_id, $this->user_id, $invoice_no, $this->pending);
		}

		if( isset($this->actions_array['credits_pkg_id']) && $this->actions_array['credits_pkg_id']!=0) {

			if($group->getPackagePending($user_group)) $plan_pending=1;
			else $plan_pending = 0;

			if($plan_pending || $this->pending) $pending = 1;
			else $pending = 0;

			$type='';
			if(isset($this->actions_array['new_creditspkg']) && $this->actions_array['new_creditspkg']['value']!=0) $type='new_creditspkg'; 
			if($type) {
				$object_id = $this->actions_array['credits_pkg_id'];
				$action->add($type, $object_id, $this->user_id, $invoice_no, $pending);
			}
		}

		do_action("payment_add_action", array($this->actions_array, &$type, $object_id, $this->user_id, $invoice_no, 0));

	}

	function process()
	{

		global $db;
		global $lng;

		// check first if the action has not been processed before
		// if not auto renew
		if($this->payment_processor['recurring'] <=0 || !$this->pay->isAutoRenew()) {

		$completed = $db->fetchRow("select completed from ".TABLE_PAYMENT_ACTIONS." where ukey = '".$this->user_key."'");
		if($completed) { $this->setInfo($lng['payments']['transaction_already_processed']); return; }
		else if($this->getManual()) {
			$exists = $db->fetchRow("select count(*) from ".$this->pay->getRetTable()." where ukey = '".$this->user_key."'");
			if($exists) { $this->setInfo($lng['payments']['transaction_already_processed']); return; }
		}

		} // end if not auto renew
		else {
			// delete user key
			$this->user_key = "";

		}

		$success = $this->pay->process();

		if($success) { 

			// get user key again for payments which set user key after receiving it from the server
			$this->user_key = $this->pay->getUserKey();

			if(!$this->pending) $this->pending = $this->pay->getPending();
			$this->paymentDone();

		} //end if success

		else $this->setInfo($lng[$this->processor]['invalid_transaction']);

	}
	// usage: - info only - (0)
	// 	  - info and perform needed actions (1)

	function paymentDone($act_type = 1)
	{

		// check if subscr_payment - in this case don't check for ukey, only for subscr_id !!!!!

		global $config_live_site, $config_abs_path;
		global $lng;
		global $db;
		global $settings;
		global $ads_settings;

		// for normal payments (not subscriptions auto renew) 
		if( $this->payment_processor['recurring'] <=0 || !$this->pay->subscrAutoRenew()) {

			$res=$db->query("select * from ".TABLE_PAYMENT_ACTIONS." where ukey = '".$this->user_key."'");
			
			if(!$db->numRows($res)) { 
				$this->setInfo($lng[$this->processor]['invalid_transaction']);
				return 0;
			}
			$row = $db->fetchAssoc();
			$this->affiliate = $row["affiliate_id"];

			// log the user back in - not for paypal ipn
			$user_id = $row['user_id'];
			$auth=new auth();
			if($user_id) $auth->autologin($user_id);

			$amount = $row['amount'];
			$tax = $row['tax'];
			$processor = $row['processor'];
			$this->actions_array = unserialize(stripslashes($row['action']));

			$invoice_no = $row['id'];

			// get subscription values
			$subscr_id = $row['subscr_id'];
			$subscr_signup = $row['subscr_signup'];
			$subscr_payment = 0;

			// set subscription id to user package
			if($subscr_signup && $subscr_id) {

				users_packages::setSubscriptionId( $subscr_id, $this->actions_array['pkg_id'] );

			}

		} //end if(!$this->pay->subscrAutoRenew())

		// if recurring payment
		else {

			$subscr_id = $this->pay->getSubscriptionId();

			// get package, amount and user_id
			$up = new users_packages;
			$sub_array = $up->getRecurringSubscription( $subscr_id );

			// if no subscription exists, exit
			if(!$sub_array) return;

			// log the user back in - not for paypal ipn!
			$user_id = $sub_array['user_id'];

			$amount = packages::getAmount($sub_array['package_id']);
			$processor = $this->pay->name;
			$this->actions_array = 	array( "ad_id" => 0, "pkg_id" => $sub_array['id'], "newad" => array( 'value' => 0 , 'price' => 0) , "renewad" => array( 'value' => 0 , 'price' => 0), "featured" => array( 'value' => 0 , 'price' => 0), "highlited" => array( 'value' => 0 , 'price' => 0), "urgent" => array( 'value' => 0 , 'price' => 0), "priority" => array( 'value' => 0 , 'price' => 0), "video" => array( 'value' => 0 , 'price' => 0), "url" => array( 'value' => 0 , 'price' => 0), "newpkg" => array( 'value' => 1 , 'price' => $amount), "renewpkg" => array( 'value' => 0 , 'price' => 0), "store" => array( 'value' => 0 , 'price' => 0), "discount_code" =>'' );

			$subscr_payment = 1;

			// add to payment actions table
			$timestamp = date("Y-m-d H:i:s");

			$res = $db->query("INSERT INTO ".TABLE_PAYMENT_ACTIONS." SET `processor`='".$processor."', user_id='".$user_id."', ukey='".$this->user_key."', `action` = '".addslashes(serialize($this->actions_array))."', amount = '".$amount."', date='$timestamp', `subscr_payment` = 1, subscr_id = '$subscr_id', `completed` = 1, `tax` = '{$this->tax}'");

			$invoice_no = $db->insertId();

		} // end else if recurring payment


		$action = new actions();
		$user = new users();
		$ad_pending = 0;
		$plan_pending = 0;
		$upgrades_pending = 0;
		$store_pending = 0;
		$credits_pending = 0;

		$paid_ad = 0;
		$paid_upgrade = 0;
		$paid_plan = 0;
		$paid_store = 0;
		$paid_credits = 0;

		// check which of the elements are paid in the invoice: ad, plan, upgrades
		// if $amount - the price can be not 0 but the total amount can be 0 if you use full discounts
		if( $amount && $this->actions_array['newad']['price'] + $this->actions_array['renewad']['price']) $paid_ad = 1;

		if( $amount && $this->actions_array['featured']['price'] + $this->actions_array['highlited']['price'] + $this->actions_array['priority']['price'] + $this->actions_array['video']['price'] + $this->actions_array['urgent']['price'] + $this->actions_array['url']['price']) $paid_upgrade = 1;

		do_action("payment_identify_paid_upgrade", array(&$paid_upgrade, $amount, $this->actions_array));
		
		if( $amount && $this->actions_array['newpkg']['price'] + $this->actions_array['renewpkg']['price']) { $paid_plan = 1; $paid_ad = 1; }

		if( $amount && isset($this->actions_array['store']) && $this->actions_array['store']['price'] ) $paid_store = 1;

		if( $amount && isset($this->actions_array['new_creditspkg']) && $this->actions_array['new_creditspkg']['price']) $paid_credits = 1;

		// payment processor settings
		$proc = new payment_processors();
		$proc_details = $proc->getPaymentProcessor($processor);
		$manual = $proc_details['manual'];//$proc->getManual($processor);
		$free = $proc_details['free'];//$proc->isFree($processor);
		$processor_name = $proc_details['processor_title'];

		// by default pending is if the payment is pending
		// this happens only for elements that are paid or if processor is free 
		if($paid_ad || $free) $ad_pending = $this->pending; // if amount for posting ad
		if($paid_plan || $free) $plan_pending = $this->pending; // if amount for the subscription
		if($paid_upgrade || $free) $upgrades_pending = $this->pending; // if amount for subscription
		if($paid_store || $free) $store_pending = $this->pending; // if amount for store
		if($paid_credits || $free) $credits_pending = $this->pending; // if amount for credits

		// ----------------------------------------------------------------

		// affiliates revenue
		if( $amount && !$this->pending && $this->affiliate) {

			require_once $config_abs_path."/classes/affiliates.php";
			affiliates::setAffiliateRevenue($amount, $this->affiliate, $invoice_no);

		}
		// end affiliates revenue

		$mail_template='';
		$info='';
		// nologin
		if($settings['nologin_enabled'] && !$user_id) $nologin = 1; else $nologin = 0;

		// if ad_id then use the "ad_publish_status" template
		if( ( isset($this->actions_array['newad']['value']) && $this->actions_array['newad']['value']!=0) ||  ( isset($this->actions_array['renewad']['value']) && $this->actions_array['renewad']['value']!=0)) {

			$info = info::getVal("ad_publish_status");
			if($settings['send_mail_to_user_when_posting_ads']) 
				$mail_template = "ad_publish_status";

		} 
		// an ad plan has been purchased, use "subscription_status" template
		elseif( ( isset($this->actions_array['newpkg']['value']) && $this->actions_array['newpkg']['value']!=0) ||  ( isset($this->actions_array['renewpkg']['value']) && $this->actions_array['renewpkg']['value']!=0)) {

			$info = info::getVal("subscription_status");
			$mail_template = "subscription_status";

		}
		// else use "ad_options_upgrade_status" template
		elseif ($this->actions_array['featured']['value']!=0 || $this->actions_array['highlited']['value']!=0 || $this->actions_array['priority']['value']!=0 || $this->actions_array['video']['value']!=0 || $this->actions_array['urgent']['value']!=0 || $this->actions_array['url']['value']!=0) {

			$info = info::getVal("ad_options_upgrade_status");
			$mail_template = "ad_options_upgrade_status";
		}

		// a store has been purchased, use "buy_store_status" template
		elseif ( isset($this->actions_array['store']['value']) && $this->actions_array['store']['value']!=0) {

			$info = info::getVal("buy_store_status");
			$mail_template = "buy_store_status";

		}
		// a credits package has been purchased, use "buy_credits_status" template
		elseif( isset($this->actions_array['new_creditspkg']) && $this->actions_array['new_creditspkg']['value']!=0) {

			$info = info::getVal("buy_credits_status");
			$mail_template = "buy_credits_status";

		}

		do_action("payment_set_info_str", array($this->actions_array, $this->pending, &$info, &$mail_template));
		
		// if listing pending for the users group -> ad_pending
		// if subscription pending for the users group -> plan_pending
		//if(!$this->pending) {

		if($user_id) {

			global $config_abs_path;
			require_once $config_abs_path."/classes/groups.php";
			$user_group = $user->getGroup($user_id);
			$group = new groups();
			if($group->getListingPending($user_group)) { 
				$ad_pending=1; 
				//$upgrades_pending=1;
			}
			if($group->getPackagePending($user_group)) $plan_pending=1;
		}

		if($nologin) {

			// if listing needs activation will be made pending after activation
			//if( $settings['nologin_activate_listing'] ) 
			//	$ad_pending = 0;
			//else 
			if(!($settings['nologin_activate_via_email'] || $settings['nologin_activate_via_sms']) && $settings['nologin_pending_listing']) $ad_pending = 1;
		}
		//}

		$pkg_type = '';
		if($this->actions_array['ad_id']) {

			$listings = new listings();
 			if(!($nologin && ($settings['nologin_activate_via_email'] || $settings['nologin_activate_via_sms']))) $listings->userApprove($this->actions_array['ad_id']);
// ?????????
			$usr_pkg = $listings->getUserPackage($this->actions_array['ad_id']);
			if($usr_pkg) {

				$up = new users_packages();
				$pkg_type = $up->getPackageType($usr_pkg);
				// if package is pending and it is a subscription
				if(!$ad_pending && $up->isPending($usr_pkg) && $pkg_type == "sub" ) $ad_pending=1;

				if($act_type>0) { // if not info only

					// substract from the number of ads left in user package
					if($this->actions_array['newad']['value']!=0 || $this->actions_array['renewad']['value']!=0) $up->substractNoAds($usr_pkg);

					// if the package is ad based there is no need to keep it pending, but make it inactive
					if($pkg_type != "sub") $up->activateAdPackage($usr_pkg);

				}
			}

			// get listing details
			global $config_abs_path;
			require_once $config_abs_path."/classes/listings.php";
			require_once $config_abs_path."/classes/pictures.php";
			require_once $config_abs_path."/classes/categories.php";
			$listing_details = $listings->getListing($this->actions_array['ad_id']);

		} // end if if($this->actions_array['ad_id'])

		// if publishing ad
		if($act_type>0 && ($this->actions_array['newad']['value']!=0 || $this->actions_array['renewad']['value']!=0)) {

			$listings = new listings();

			if($ad_pending) $listings->makePending($this->actions_array['ad_id']);

			else {

				// if not inactive nologin ads
				if(!($nologin && ($settings['nologin_activate_via_email'] || $settings['nologin_activate_via_sms']))) {
					$listings->activateListing($this->actions_array['ad_id']);
					if(!$upgrades_pending) {
						$pa = new payment_actions();
						$pa->ActivateInvoice($invoice_no);
					}
				}

			} // end else if ad pending

		} // end newad || renewad

		// if subscription
		if($act_type>0 && !$nologin && ($this->actions_array['newpkg']['value']!=0 || $this->actions_array['renewpkg']['value']!=0)) {

			$up = new users_packages();

			// approve the package!
			$up->userApprove($this->actions_array['pkg_id']);

			// leave one ad packages inactive
			$pkg_type = $up->getPackageType($this->actions_array['pkg_id']);

			// if recurring set today date_start
			if($subscr_payment || $this->actions_array['renewpkg']['value']) {

				$up->renewSubscriptionDate( $this->actions_array['pkg_id'] );

			}
			else {

				if($pkg_type=="sub" && $plan_pending) $up->makePending($this->actions_array['pkg_id']);

				else {

					if($pkg_type=="sub") $up->activatePackage($this->actions_array['pkg_id']);
					if(!$ad_pending && !$upgrades_pending) {
						$pa = new payment_actions();
						$pa->ActivateInvoice($invoice_no);
					} // end if !$ad_pending

				} // else if plan pending

			}// end if recurring set today date_start

		} // end newpkg || renewpkg

		if ($act_type>0 && ($this->actions_array['featured']['value']!=0 || $this->actions_array['highlited']['value']!=0 || $this->actions_array['priority']['value']!=0 || $this->actions_array['video']['value']!=0 || $this->actions_array['urgent']['value']!=0 || $this->actions_array['url']['value']!=0)) {

			$listings = new listings();
			if(!$upgrades_pending){

				if($this->actions_array['featured']['value']!=0) $listings->makeFeatured($this->actions_array['ad_id'], $this->actions_array['featured']['value']);

				if($this->actions_array['highlited']['value']!=0) $listings->makeHighlited($this->actions_array['ad_id']);

				if($this->actions_array['priority']['value']!=0) $listings->enablePriority($this->actions_array['ad_id'], $this->actions_array['priority']['value']);

				if($this->actions_array['urgent']['value']!=0) $listings->makeUrgent($this->actions_array['ad_id']);

				if($this->actions_array['video']['value']!=0) $listings->enableVideo($this->actions_array['ad_id']);

				if($this->actions_array['url']['value']!=0) $listings->enableUrl($this->actions_array['ad_id']);
				
				if(!$upgrades_pending && !$plan_pending) {
					$pa = new payment_actions();
					$pa->ActivateInvoice($invoice_no);
				}

			} // end else if upgrades pending

			if($nologin) {

				// if listing needs activation will be made pending after activation
				// check first if the upgrade is made same time with a listing
				if( ($settings['nologin_activate_via_email'] || $settings['nologin_activate_via_sms']) && ($this->actions_array['newad']['value']!=0 || $this->actions_array['renewad']['value']!=0) ) 
					$upgrades_pending = 0;
				else 
					if($settings['nologin_pending_listing']) $upgrades_pending = 1;
			}	

		}
		
		do_action("payment_enable_extra_feature", array($act_type, $this->actions_array, $this->pending, $invoice_no));

		// enable store if not pending
		if ($act_type>0 && (isset($this->actions_array['store']) && $this->actions_array['store']['value']!=0)) {

			if(!$store_pending) $user->enableStore($user_id);

		}

		// if buy credits
		if($act_type>0 && !$nologin && isset($this->actions_array['new_creditspkg']) && $this->actions_array['new_creditspkg']['value']!=0) {

			require_once $config_abs_path."/classes/credits.php";
			if(!$credits_pending) {
				$cr = new credits();
				$no_credits = $cr->getNoCredits($this->actions_array['credits_pkg_id']);
				$cr->addForUser($user_id, $no_credits);
			}

		} // end new_creditspkg

		global $mail_settings;
		$html_mails=$mail_settings["html_mails"];
		if($html_mails) $amp = "&amp;"; else $amp = "&";

		// ------------ user details ---------------
		if($user_id) {

			$user_details = $user->getUser($user_id);	
			$username = $user_details['username'];
			$user_email = $user_details['email'];
			$user_contact = $user_details[$settings['contact_name_field']];
			//if(!$user_contact) $user_contact = $username;

		} else {

			$user_details = listings::getOwnerInfo($this->actions_array['ad_id']);
			$user_email = $user_details['mgm_email'];
			$user_contact = $user_details['mgm_name'];
			$username = '';
		}
		// -------- create info template -----------

		$smarty_info = new Smarty;
		$smarty_info = smartyShowDBVal($smarty_info);
		$smarty_info->assign("value", $info);
//%%%%%%%%%%%%%%%%%%%
		// post ads no login

		if($nologin && ($settings['nologin_activate_via_email'] || $settings['nologin_activate_via_sms']) && ($this->actions_array['newad']['value']!=0 || $this->actions_array['renewad']['value']!=0)) {

			if($settings['nologin_activate_via_sms']==1) {
				// SMS activation
				$activation_link = $config_live_site.'/activate_listing.php?id='.$this->actions_array['ad_id'];
				$sms_activation_link = $activation_link;
				
				// send SMS
				$listings->sendSMSVerification($this->actions_array['ad_id']);
			}
			else  {
				// email activation
				$activation_link = $config_live_site.'/details.php?id='.$this->actions_array['ad_id'].$amp.'action=activation'.$amp.'key='.$user_details['activation'];
			}
			if($html_mails) $activation_link ='<a href="'.$activation_link.'">'.$activation_link.'</a>';

		} 
		else $activation_link='';

		$activation = 0;
		if(!$user_id) { 
			if($settings['nologin_activate_via_sms']) $activation=2;
			elseif($settings['nologin_activate_via_email']) $activation=1;
		}
		
		$array_message = array("nologin" => $nologin, "activation" => $activation, "activation_link" => $activation_link, "amount" => $amount, "manual" => $manual, "amount_formatted" => add_currency(format_numeric($amount)), "processor" => $processor_name, "invoice_no" => $invoice_no, "ad_pending" => $ad_pending, "plan_pending"=> $plan_pending, "store_pending"=> $store_pending, "actions"=> $this->actions_array, "user_id"=> $user_id, "ad_id"=> $this->actions_array['ad_id'], "featured"=>$this->actions_array['featured']['value'], "featured_price"=>add_currency($this->actions_array['featured']['price']), "highlited"=>$this->actions_array['highlited']['value'], "highlited_price"=>add_currency($this->actions_array['highlited']['price']), "priority"=>$this->actions_array['priority']['value'], "priority_price"=>add_currency($this->actions_array['priority']['price']), "urgent"=>$this->actions_array['urgent']['value'], "urgent_price"=>add_currency($this->actions_array['urgent']['price']), "video"=>$this->actions_array['video']['value'], "video_price"=>add_currency($this->actions_array['video']['price']), "url"=>$this->actions_array['url']['value'], "url_price"=>add_currency($this->actions_array['url']['price']), "unlimited"=> $lng['general']['unlimited'], "username"=> $username, "email" => $user_email, "contact_name"=> $user_contact, "tax" =>$tax, "tax_formatted" => add_currency(format_numeric($tax)), "enable_username" =>$settings['enable_username']);

		do_action("payment_add_to_message", array(&$array_message));
		
		// end post ads no login

		if(isset($this->actions_array['ad_id']) && $this->actions_array['ad_id']) {

			// ----------------- details link -------------------
			$ad_title = cleanStr(listings::getTitle($this->actions_array['ad_id']));

			global $seo_settings;
			if($seo_settings['enable_mod_rewrite'] && !$nologin) {
				$seo = new seo();
				$details_link=$seo->makeDetailsLink($this->actions_array['ad_id'], $ad_title);
			} else { 

				$details_link=$config_live_site.'/details.php?id='.$this->actions_array['ad_id'];
				if($nologin) $details_link.=$amp.'key='.$user_details['activation'];

			}
			$array_message["details_link"] = $details_link;
			$array_message["title"] = $ad_title;
			$array_message["listing_details"] = $listing_details;

		}

		if($this->actions_array['newad']['value']==1 || $this->actions_array['renewad']['value']==1) $ad_posted = 1; else $ad_posted = 0;
		if($this->actions_array['newpkg']['value']==1 || $this->actions_array['renewpkg']['value']==1) $pkg_posted = 1; else $pkg_posted = 0;
		if($this->actions_array['featured']['value']>0 || $this->actions_array['highlited']['value']==1 || $this->actions_array['priority']['value']>0 || $this->actions_array['video']['value']==1 || $this->actions_array['urgent']['value']==1 || $this->actions_array['url']['value']==1) $upgrade_posted = 1; else $upgrade_posted = 0;

		// status for upgrades
		if(!$ad_posted && $upgrade_posted && $upgrades_pending) {
			$status=$lng['listings']['waiting_admin_approval'];
			$active = 0;
		}
		else { 
			$status=$lng['listings']['active'];
			$active = 1;
		}

		// status for ads or packages
		if($ad_posted || $pkg_posted) {

		if( $ad_pending==1 || ( $plan_pending==1 && $pkg_type=="sub" )) { 

			$status=$lng['listings']['waiting_admin_approval'];
			$active = 0;
		}
		else if($nologin && ($settings['nologin_activate_via_email'] || $settings['nologin_activate_via_sms'])) { 
			$status=$lng['listings']['waiting_activation'];
			$active = 0;
		}
		else { 
			$status=$lng['listings']['active'];
			$active = 1;
		}
		}// end status for ads or packages

		// status for store
		if(isset($this->actions_array['store']) && $this->actions_array['store']['value']!=0) {

			if($store_pending) { 
				$status=$lng['listings']['pending'];
				$active = 0;
			}
			else { 
				$status=$lng['listings']['active'];
				$active = 1;
			}

			$array_message["days"] = $ads_settings['store_availability'];

		}

		// status for credits
		if(isset($this->actions_array['new_creditspkg']) && $this->actions_array['new_creditspkg']['value']!=0) {

			if($credits_pending) { 
				$status=$lng['listings']['pending'];
				$active = 0;
			}
			else { 
				$status=$lng['listings']['active'];
				$active = 1;
			}
			require_once $config_abs_path."/classes/credits.php";
			$cr = new credits();
			$no_credits = $cr->getNoCredits($this->actions_array['credits_pkg_id']);
			$array_message["credits"] = $no_credits;
			$array_message["credits_pending"] = $credits_pending;

		}

		$array_message["status"] = $status;
		$array_message["active"] = $active;
		$array_message["new_ad"] = $ad_posted;
		$array_message["new_subscription"] = $pkg_posted;

		if(isset($this->actions_array['featured']) && $this->actions_array['featured']['value']) {

			global $config_abs_path;
			require_once $config_abs_path."/classes/featured_plans.php";
			if($this->actions_array['featured']['value']!=100) $array_message["featured_no_days"] = featured_plans::getDays($this->actions_array['featured']['value']);
		}

		if(isset($this->actions_array['priority']) && $this->actions_array['priority']['value']) {

			global $config_abs_path;
			require_once $config_abs_path."/classes/priorities.php";

			//$pri = new priorities;
			//$priorities = $pri->getPriorities();
			$priorities = common::getCachedObject("base_priorities");
			foreach ($priorities as $pri) {
				if($pri['order_no'] == $this->actions_array['priority']['value']) $priority_name = $pri['name'];
			}
			$array_message["priority_name"] = $priority_name;
		}


		if(isset($this->actions_array['pkg_id']) && $this->actions_array['pkg_id']) {

			$up = new users_packages();
			$plan = $up->getPackage($this->actions_array['pkg_id']);
			$plan_name = $plan['name'];

			$array_message["plan_name"] = $plan_name;
			$array_message["plan"] = $plan;
			$array_message["plan_price"] = add_currency($plan['amount']);

		}  elseif(isset($this->actions_array['ad_id']) && $this->actions_array['ad_id']) {

			if($plan_name = listings::getPackageName($this->actions_array['ad_id'])) 
			$array_message["plan_name"] = $plan_name;

		}  elseif(isset($this->actions_array['credits_pkg_id']) && $this->actions_array['credits_pkg_id']) {

			$cr = new credits();
			$array_message["credits_plan"] = $cr->getCreditPackage($this->actions_array['credits_pkg_id']);

		} 

		if (isset($this->actions_array['store']) && $this->actions_array['store']['value']!=0) {

			$array_message["store"] = 1;

		}
		if ($this->actions_array['featured']['value']!=0 || $this->actions_array['highlited']['value']!=0 || $this->actions_array['priority']['value']!=0 || $this->actions_array['video']['value']!=0 || $this->actions_array['urgent']['value']!=0 || $this->actions_array['url']['value']!=0) {

			$array_message["upgrade"] = 1;

		}
		else {
			$array_message["upgrade"] = 0;
		}

		// check if any discount
		if(isset($this->actions_array['discount_code']) && $this->actions_array['discount_code']) {

			global $config_abs_path;
			require_once $config_abs_path."/classes/coupons.php";
			$coupon = coupons::getDiscountByCode($this->actions_array['discount_code']);
			if($coupon['type'] == "fixed") $discount = add_currency($coupon['discount']);
			else $discount = $coupon['discount'].'%';

			$array_message["coupon"] = $coupon;
			$array_message["discount"] = $discount;

		}
		else {
			$array_message["coupon"] = '';
			$array_message["discount"] = 0;
		}
		$smarty_info->assign($array_message);

		$info_str = $smarty_info->fetch("db_template.html");

		// ------------ end create info template -----------

		$this->setInfo($info_str);

		if(!$act_type) return 1;
		
		if(!$mail_template) return 1;

		// --------------------- if $act_type ==1 ----------------------
		// -------------- send mails and perform actions ---------------

		if(isset($details_link) && $html_mails) $array_message['details_link'] = '<a href="'.$details_link.'">'.$details_link.'</a>';

		$mail2send=new mails();
		$mail2send->init($user_email, $user_contact);

		$array_subject = array("amount" => $amount, "amount_formatted" => add_currency(format_numeric($amount)), "processor" => $processor_name, "invoice_no" => $invoice_no, "user_id"=> $user_id, "ad_id"=> $this->actions_array['ad_id'], "username"=> $username, "email" => $user_email, "contact_name"=> $user_contact, "admin_activated" => 0);

		$mail2send->composeAndSend($mail_template, $array_message, $array_subject);
		$send_to_admin = 0;

		// send mail to admin if pending
		if( ( $ad_pending || ($plan_pending && $pkg_type=="sub") || $upgrades_pending || $store_pending || $credits_pending) && $settings['send_mail_to_admin_when_pending']) {

			$send_to_admin = 1;
			$mail_template = "admin_announce_pending";

		} // end sending mail to admin if pending
		// send mail to admin if new listing
		elseif( $settings['send_mail_to_admin_when_new_ad'] && isset($this->actions_array['ad_id']) && $this->actions_array['ad_id']  && ( isset($this->actions_array['newad']['value']) && $this->actions_array['newad']['value']!=0) ||  ( isset($this->actions_array['renewad']['value']) && $this->actions_array['renewad']['value']!=0) ) {

			$send_to_admin = 1;
			$mail_template = "admin_new_ad";

		}

		if($send_to_admin) {

			$mail2send=new mails();
			$mail2send->init("", "");

			$array_subject = array("amount" => $amount, "amount_formatted" => add_currency(format_numeric($amount)), "processor" => $processor_name, "invoice_no" => $invoice_no, "user_id"=> $user_id, "ad_id"=> $this->actions_array['ad_id'], "username"=> $username, "contact_name"=> $user_contact);
			$array_message["type"] = $username;
			$mail2send->composeAndSend($mail_template, $array_message, $array_subject);

		}

		// redirect to listing activation if SMS activation is required
		if($nologin && ($settings['nologin_activate_via_sms']==1 && $settings['nologin_activate_via_email']==0) && ($this->actions_array['newad']['value']!=0 || $this->actions_array['renewad']['value']!=0)) {
		
			header("Location: ".$sms_activation_link);
			exit(0);
			
		}

	}

	function cancel()
	{
		global $db;
		global $lng;
		// do not delete it, allow to finish the payment later
		//$res = $db->query("delete from ".TABLE_PAYMENT_ACTIONS." where ukey='".$this->user_key."' and completed='0'");
		$this->setInfo($lng[$this->processor]['transaction_canceled']);
		
	}


	function log($msg)
	{
		if(!$this->debug) return;

		$content = "-----------------------------------\n".date("r")."\n";
		$content .= $msg."\n";
		$content .= "-----------------------------------\n";

		global $config_abs_path;
		$file = $config_abs_path."/log/payment_debug";

		$handle = fopen($file, "a");
		fwrite($handle, $content);
		fclose($handle);
	}

	function setTax($amount) {

		$this->tax = $amount;

	}

	function getConfirmation() {

		$result = '';
		try { 
			$result = $this->pay->getConfirmation(); 
		} 
		catch (Exception $e) {
			// getConfirmation function does not exist
		}
		return $result;
	}

	
}
?>
