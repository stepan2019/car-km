<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class affiliates {

public function __construct()
{

}

function deleteRev($id) {

	global $db;
	$db->query("delete from ".TABLE_AFFILIATES_REVENUE." where `id` = '$id'");
	return;

}

static function setAffiliateRevenue ($amount=0, $affiliate_id="", $id='') {

	global $db;
	if($id && (!$amount && !$affiliate_id)) {

		$pa_info = $db->fetchAssoc("select `amount`, `affiliate_id` from ".TABLE_PAYMENT_ACTIONS." where `id`='$id'");

		$amount = $pa_info["amount"];
		$affiliate_id = $pa_info["affiliate_id"];

	}

	if(!$affiliate_id) return;

	// if the amount is 0 no need to go further
	if(!$amount) return;

	// if invalid affiliate id
	global $config_abs_path;
	require_once $config_abs_path."/classes/users.php";
	if(!users::affiliateExists($affiliate_id)) return;

	// check if it was already added
	$already_added = $db->fetchRow("select `order_id` from ".TABLE_AFFILIATES_REVENUE." where `order_id` = '$id'");
	if($already_added) return;

	// calculate the percent which goes to the affiliate
	$group_id = $db->fetchRow("SELECT `group` FROM `".TABLE_USERS."` WHERE id='$affiliate_id'");
	
	$a = new affiliates();
	$aff_settings = $a->getAffiliateSettings($group_id);
	$affiliate_revenue = ($aff_settings["affiliates_percentage"]*$amount ) /100;
	$crt_date = date('Y-m-d H:i:s');
	$db->query("insert into ".TABLE_AFFILIATES_REVENUE." set `date`='$crt_date', amount='$affiliate_revenue', affiliate_id='$affiliate_id', `order_id`='$id'");
	return 1;

}

function setPaid($id) {

	global $db;
	if(!$id) return;
	$db->query("update ".TABLE_AFFILIATES_REVENUE." set `paid` = 1 where id='$id'");

}

function unsetPaid($id) {

	global $db;
	if(!$id) return;
	$db->query("update ".TABLE_AFFILIATES_REVENUE." set `paid` = 0 where id='$id'");

}

function setReleased($id) {

	global $db;
	if(!$id) return;
	$db->query("update ".TABLE_AFFILIATES_REVENUE." set `released` = 1 where id='$id'");

}

function searchRevenue($post_array, $page,$no_per_page,$order,$order_way) {

		global $db;
		global $lng;
		global $appearance_settings;
		$date_format=$appearance_settings["date_format"];

		$start=($page-1)*$no_per_page;

		global $db;
		$first = 1; $where="";

		foreach($post_array as $key=>$value) {
			if( !$key || empty($value) ) continue;
			
			switch($key) {
				case "affiliate_id": 
				case "order_id": 
				case "payment_id": 
				case "id": 

					if($first) { $where.=" where "; $first = 0; } else $where.=" and ";
					$where.=TABLE_AFFILIATES_REVENUE.".`$key` = '$value' ";

				break;

				case "date_from": 

					if($first) { $where.=" where "; $first = 0; } else $where.=" and ";
					$where.=TABLE_AFFILIATES_REVENUE.".`date` >= '$value' ";

				break;

				case "date_to": 

					if($first) { $where.=" where "; $first = 0; } else $where.=" and ";
					$where.=TABLE_AFFILIATES_REVENUE.".`date` <= '$value' ";

				break;

				case "amount_from": 

					if($first) { $where.=" where "; $first = 0; } else $where.=" and ";
					$where.=TABLE_AFFILIATES_REVENUE.".`amount` >= '$value' ";

				break;

				case "amount_to": 

					if($first) { $where.=" where "; $first = 0; } else $where.=" and ";
					$where.=TABLE_AFFILIATES_REVENUE.".`amount` <= '$value' ";

				break;

				case "paid":
				case "released":

					if($first) { $where.=" where "; $first = 0; } else $where.=" and ";
					$where.=TABLE_AFFILIATES_REVENUE.".`$key` = '$value' ";

				break;

				case "username": 
				case "email": 

					if($first) { $where.=" where "; $first = 0; } else $where.=" and ";
					$where.=TABLE_USERS.".`$key` = '$value' ";

				break;

			}
		}

		$no_rev = $this->getNoSearchRev($where);
		$this->setNoRev($no_rev);

		$sql="select ".TABLE_AFFILIATES_REVENUE.".*, date_format(".TABLE_AFFILIATES_REVENUE.".`date`,'".$date_format."') as date_nice, ".TABLE_USERS.".username, ".TABLE_USERS.".email, ".TABLE_USERS.".id as user_id, ".TABLE_PAYMENT_ACTIONS.".action from ".TABLE_AFFILIATES_REVENUE." 
left join ".TABLE_USERS." on ".TABLE_AFFILIATES_REVENUE.".affiliate_id=".TABLE_USERS.".`id`
left join ".TABLE_PAYMENT_ACTIONS." on ".TABLE_AFFILIATES_REVENUE.".order_id=".TABLE_PAYMENT_ACTIONS.".`id`
$where 
order by `".$order."` ".$order_way." limit ".$start.", ".$no_per_page;
//echo $sql;	

		$array=$db->fetchAssocList($sql);
//_print_r($array);
		$i = 0;
		$pa = new payment_actions;
		foreach($array as $row) {
			$array[$i]['amount_nice'] = add_currency($row['amount']);
			$array[$i]['action_str']=$pa->actionsStr($row['action']);
			$i++;
		}
//_print_r($array);
		return $array;

}

function searchPaymentsHistory($post_array, $page,$no_per_page,$order,$order_way) {

		global $db;
		global $lng;
		global $appearance_settings;
		$date_format=$appearance_settings["date_format"];

		$start=($page-1)*$no_per_page;

		global $db;
		$first = 1; $where="";

		foreach($post_array as $key=>$value) {
			if( !$key || empty($value) ) continue;
			
			switch($key) {
				case "affiliate_id": 
				case "id": 
					if($first) { $where.=" where "; $first = 0; } else $where.=" and ";
					$where.=TABLE_AFFILIATES_PAYMENTS.".`$key` = '$value' ";

				break;

				case "date_from": 

					if($first) { $where.=" where "; $first = 0; } else $where.=" and ";
					$where.=TABLE_AFFILIATES_PAYMENTS.".`date` >= '$value' ";

				break;

				case "date_to": 

					if($first) { $where.=" where "; $first = 0; } else $where.=" and ";
					$where.=TABLE_AFFILIATES_PAYMENTS.".`date` <= '$value' ";

				break;

				case "amount_from": 

					if($first) { $where.=" where "; $first = 0; } else $where.=" and ";
					$where.=TABLE_AFFILIATES_PAYMENTS.".`amount` >= '$value' ";

				break;

				case "amount_to": 

					if($first) { $where.=" where "; $first = 0; } else $where.=" and ";
					$where.=TABLE_AFFILIATES_PAYMENTS.".`amount` <= '$value' ";

				break;

				case "completed":

					if($first) { $where.=" where "; $first = 0; } else $where.=" and ";
					$where.=TABLE_AFFILIATES_PAYMENTS.".`$key` = '$value' ";

				break;

				case "username": 
				case "email": 

					if($first) { $where.=" where "; $first = 0; } else $where.=" and ";
					$where.=TABLE_USERS.".`$key` = '$value' ";

				break;

			}
		}

		$no_rev = $this->getNoSearchPayments($where);
		$this->setNoPayments($no_rev);

		$sql="select ".TABLE_AFFILIATES_PAYMENTS.".*, date_format(".TABLE_AFFILIATES_PAYMENTS.".`date`,'".$date_format."') as date_nice, ".TABLE_USERS.".username, ".TABLE_USERS.".email, ".TABLE_AFFILIATES.".id as user_id, ".TABLE_AFFILIATES.".affiliate_paypal_email from ".TABLE_AFFILIATES_PAYMENTS." 
left join ".TABLE_AFFILIATES." on ".TABLE_AFFILIATES_PAYMENTS.".affiliate_id=".TABLE_AFFILIATES.".`affiliate_id`
left join ".TABLE_USERS." on ".TABLE_AFFILIATES.".id=".TABLE_USERS.".`id`
$where 
order by `".$order."` ".$order_way." limit ".$start.", ".$no_per_page;
//echo $sql;	

		$array=$db->fetchAssocList($sql);

		$i = 0;
		foreach($array as $row) {
			$array[$i]['amount_nice'] = add_currency($row['amount']);
			$i++;
		}

		return $array;

}

function releasePayments() {

	global $db;

	// get affiliates with active payments
	$array_affiliates = $db->fetchAssocList("select ".TABLE_AFFILIATES.".`affiliate_id`, `affiliate_paypal_email`, sum(`amount`) as `total` from ".TABLE_AFFILIATES." left join ".TABLE_AFFILIATES_REVENUE." on ".TABLE_AFFILIATES.".affiliate_id=".TABLE_AFFILIATES_REVENUE.".affiliate_id where ".TABLE_AFFILIATES_REVENUE.".released=0");


	// for each affiliate
	foreach($array_affiliates as $aff) {

		$payment_id = $this->addToPaymentHistory($aff['affiliate_id'], $aff['affiliate_paypal_email'], $aff['total']);

		$db->query("update ".TABLE_AFFILIATES_REVENUE." set `released` = 1, payment_id='$payment_id' where affiliate_id='{$aff['affiliate_id']}' and `released`=0");

	}
	$agroups = $this->getAffiliateGroups();
	foreach($agroups as $a)
		$this->setLastPaid($a);

}

function addToPaymentHistory($affiliate_id, $paypal_email, $total) {

	if(!$affiliate_id || !$paypal_email || !$total) return;

	global $db;
	$timestamp = date("Y-m-d H:i:s");
	$db->query("insert into ".TABLE_AFFILIATES_PAYMENTS." set `affiliate_id`='$affiliate_id', `amount`='$total', `date`='$timestamp', `processor`='paypal', `paid_to`='$paypal_email'");

	$id=$db->insertId();
	return $id;

}

function getLastPaid($agroup) {

	global $db;
	$result = $db->fetchRow("select `date` from ".TABLE_PERIODIC." where `type` = 'affiliates_payment' and `val` = '$agroup'");
	return $result;

}

function setLastPaid($agroup) {

	global $db;
	$timestamp = date("Y-m-d H:i:s");
	$db->query("update ".TABLE_PERIODIC." set `date` = '$timestamp' where `type` = 'affiliates_payment' and `val` = '$agroup'");
	return;

}

function timeToRelease($agroup) {

	global $db;
	$affiliate_settings = $this->getAffiliateSettings($agroup);

	$timestamp = date("Y-m-d H:i:s");
	$time2pay = $db->fetchRow("select date_add(`date`, interval '{$affiliate_settings['affiliates_payment_cycle']}' day) <= '$timestamp' from ".TABLE_PERIODIC." where `type` = 'affiliates_payment' and `val`='$agroup'");

	return $time2pay;

}

	function getNoSearchRev($where) {

		global $db;

		$total=$db->fetchRow('select count(*) from '.TABLE_AFFILIATES_REVENUE."
		left join ".TABLE_AFFILIATES." on ".TABLE_AFFILIATES_REVENUE.".affiliate_id=".TABLE_AFFILIATES.".`id`
		left join ".TABLE_USERS." on ".TABLE_AFFILIATES.".id=".TABLE_USERS.".`id`".$where);

		return $total;
		
	}

	function getNoRev() {

		return $this->no_rev;

	}

	function setNoRev($no) {

		$this->no_rev = $no;

	}

	function getNoSearchPayments($where) {

		global $db;

		$total=$db->fetchRow("select count(*) from ".TABLE_AFFILIATES_PAYMENTS."
		left join ".TABLE_AFFILIATES." on ".TABLE_AFFILIATES_PAYMENTS.".affiliate_id=".TABLE_AFFILIATES.".`id`
		left join ".TABLE_USERS." on ".TABLE_AFFILIATES.".id=".TABLE_USERS.".`id`".$where);

		return $total;
		
	}

	function getNoPayments() {

		return $this->no_rev;

	}

	function setNoPayments($no) {

		$this->no_rev = $no;

	}

	function getLastPayment($affiliate_id) {

		global $db, $appearance_settings;
		$date_format=$appearance_settings["date_format"];

		$payment = $db->fetchAssoc("select date_format(`date`,'$date_format') as `date`, `amount` from ".TABLE_AFFILIATES_PAYMENTS." where `completed`=1 and `affiliate_id`='$affiliate_id' order by `date` desc limit 1");

		if($payment) $payment['amount'] = add_currency($payment['amount']);

		return $payment;
	}

	function nextPaymentDate($user_id) {

		global $db;
		global $db, $appearance_settings;
		$date_format=$appearance_settings["date_format"];

		$agroup = $db->fetchRow("select `group` from ".TABLE_USERS." where id='$user_id'");		
		$affiliate_settings = $this->getAffiliateSettings($agroup);

		$time2pay = $db->fetchRow("select date_format(date_add(`date`, interval '{$affiliate_settings['affiliates_payment_cycle']}' day), '$date_format') from ".TABLE_PERIODIC." where `type`='affiliates_payment' and `val`='$agroup'");

		return $time2pay;

	}

	function getTotalDue($affiliate_id) {

		global $db;
		$sum = $db->fetchRow("select sum(`amount`) as `total` from ".TABLE_AFFILIATES." left join ".TABLE_AFFILIATES_REVENUE." on ".TABLE_AFFILIATES.".id=".TABLE_AFFILIATES_REVENUE.".affiliate_id where ".TABLE_AFFILIATES_REVENUE.".`affiliate_id`='$affiliate_id' and  ".TABLE_AFFILIATES_REVENUE.".released=0");
		if($sum) $sum = add_currency(format_price($sum));
		else $sum = 0;
		return $sum;

	}

	function getTotalPayments($affiliate_id) {

		global $db;
		$sum = $db->fetchRow("select sum(`amount`) as `total` from ".TABLE_AFFILIATES." left join ".TABLE_AFFILIATES_PAYMENTS." on ".TABLE_AFFILIATES.".affiliate_id=".TABLE_AFFILIATES_PAYMENTS.".affiliate_id where ".TABLE_AFFILIATES_PAYMENTS.".`affiliate_id`='$affiliate_id' and  ".TABLE_AFFILIATES_PAYMENTS.".completed=1");

		if($sum) $sum = add_currency(format_price($sum));
		else $sum = 0;
		return $sum;

	}

	function getPendingPayment($affiliate_id) {

		global $db;
		$sum = $db->fetchRow("select `amount` from ".TABLE_AFFILIATES_PAYMENTS." where `affiliate_id`='$affiliate_id' and completed=0 order by `date` desc limit 1");

		if($sum) $sum = add_currency(format_price($sum));

		return $sum;

	}

	static function getAffiliateId($user_id) {

		global $db;
		$affiliate_id = $db->fetchRow("select `affiliate_id` from ".TABLE_AFFILIATES." where id='$user_id'");
		return $affiliate_id;

	}

	function getPayment($id) {

		global $db;
		$payment_info = $db->fetchAssoc("select * from ".TABLE_AFFILIATES_PAYMENTS." where `id`='$id'");

		return $payment_info;

	}

	function markPaymentPaid($id, $val=1) {

		global $db;
		$db->query("update ".TABLE_AFFILIATES_PAYMENTS." set `completed`=$val where `id`='$id'");
		$db->query("update ".TABLE_AFFILIATES_REVENUE." set `paid`=$val where `payment_id`='$id'");

		return;

	}

	function getAffiliateSettings($group_id) {
	
		global $db;
		$aff_settings = $db->fetchAssoc("select `affiliates_cookie_availability`, `affiliates_percentage`, `affiliates_payment_cycle` from ".TABLE_USER_GROUPS." where id='$group_id'");
		return $aff_settings;
		
	}

	function getAffiliateGroups() {
	
		global $db;
		$affiliate_groups = $db->fetchRowList("select `id` from ".TABLE_USER_GROUPS." where `affiliates` = 1");
		return $affiliate_groups;
	
	}
	
}
?>
