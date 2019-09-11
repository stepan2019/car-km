<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class invoices {

	var $id;
	var $error='';
	var $info='';
	var $clean;
	var $array;
	var $tmp;
	var $no_invoices;

	public function __construct()
	{
	
	}

	function getId() {

		return $this->id;

	}

	function delete($id=0) {

		global $db;
		$res_del=$db->query('delete from '.TABLE_INVOICES.' where `id`="'.$id.'"');
		return 1;
	}

	function getInvoice($id='') {
		
		if(!$id) $id=$this->id;
		global $db;
		$result=$db->fetchAssoc('select * from '.TABLE_INVOICES.' where `id`="'.$id.'"');
		$result['subtotal'] = $result['amount'] - $result['tax'];
		$result['subtotal_nice'] = add_currency($result['subtotal'], $result['currency']);
		$result['amount_nice'] = add_currency($result['amount'], $result['currency']);
		if($result['tax']) $result['tax_nice'] = add_currency($result['tax'], $result['currency']);
		else $result['tax_nice'] = 0;
		
		global $invoice_settings;
		if($invoice_settings['show_vat']) {
			$result['vat'] = format_price(($invoice_settings['vat_percent'] * $result['amount'])/100);
			$result['vat_nice'] = add_currency($result['vat'], $result['currency']);
		}
		
		$result['payment_details'] = unserialize($result['payment_details']);
		
		return $result;

	}

	function getInvoiceForPA($id) {

		global $db;
		$result=$db->fetchRow('select `id` from '.TABLE_INVOICES.' where `payment_action`="'.$id.'"');
		if(!$result) return 0;
		return $result;

	}
	
	function getUserInvoices($user_id) {
		
		if(!$id) $id=$this->id;
		global $db;
		$result=$db->fetchAssoc('select * from '.TABLE_INVOICES.' where `user_id`="'.$user_id.'"');
		return $result;

	}

	function count() {
	
		global $db;
		$no=$db->fetchRow('select count(*) from '.TABLE_INVOICES);
		return $no;

	}

	function getError() {
	
		return $this->error;

	}

	function addError($str) {

		$this->error.=	$str;

	}

	function setError($str) {

		$this->error=$str;

	}

	function getTmp() {
	
		return $this->tmp;

	}

	function setInfo($str){

		$this->info = $str;

	}

	function getInfo() {

		return $this->info;

	}

	function add($payment_action) {

		global $db;
		global $lng;
		global $invoice_settings, $appearance_settings;

		// prevent creating the invoice multiple times
		$exists = $db->fetchRow("select `id` from ".TABLE_INVOICES." where `payment_action`='$payment_action'");
		if($exists) return;
		
		$this->error='';
		$this->tmp=array();
		$this->clean=array();

		$pa = new payment_actions();
		$pa_details = $pa->getPaymentAction($payment_action);

		// if amount == 0 exit
		if($pa_details['amount']==0) return;
		// don't generate for credits
		if($pa_details['processor']=='credits') return;
		
		
		$user_id = $pa_details['user_id'];
		
		if($user_id) {
			global $config_abs_path;
			require_once $config_abs_path."/classes/users.php";
			$user_details = users::getUserInfo($user_id);
		}
		else {
			// get listing id
			$listing_id = $pa->getAttachedListingId($payment_action);
			$user_details = listings::getOwnerInfo($listing_id);
		}
		
		$user_details_str = '';
		$user_fields_array = explode(",", $invoice_settings['user_fields']);
		foreach($user_fields_array as $field) {
		
			if(!$field) continue;
			if(isset($user_details[$field]) && $user_details[$field]) 
				$user_details_str.=$user_details[$field]."<br/>";
		
		}
		
		$payment_details = $this->makePaymentDetails(unserialize($pa_details['action']));
		
		$db->query("insert into ".TABLE_INVOICES." set `date` = '{$pa_details['date']}', `payment_action` = '".$payment_action."', `user_id`='$user_id', `currency` ='{$appearance_settings['default_currency']}', `amount` = '{$pa_details['amount']}',  `tax` =  '{$pa_details['tax']}', `seller_details` = '{$invoice_settings['seller_details']}', `invoice_logo` = '{$invoice_settings['invoice_logo']}', `user_details` = '".$user_details_str."', `custom_text` = '{$invoice_settings['custom_text']}', `payment_details`='".serialize($payment_details)."', `processor`='".$pa_details['processor']."';");
		
		$last_id=$db->insertId();

		return 1;

	}

	function searchInvoices($post_array, $page, $no_per_page,$order,$order_way) {

		global $db;
		global $lng;
		global $crt_lang;
		global $appearance_settings, $settings;
		$date_format=$appearance_settings["date_format_long"];
		$currency_pos=$appearance_settings["currency_pos"];
		$default_currency=$appearance_settings["default_currency"];

		$start=($page-1)*$no_per_page;

		$where = ''; $found = 0;
		$join=" left join ".TABLE_USERS." on ".TABLE_INVOICES.".`user_id` = ".TABLE_USERS.".`id` 
		left join ".TABLE_PAYMENT_PROCESSORS." on ".TABLE_INVOICES.".`processor` = ".TABLE_PAYMENT_PROCESSORS.".`processor_code` ";

		foreach($post_array as $key=>$value) {
			if( !$key || $value=='' ) continue;
			
			switch($key) {
				case "id": 
				case "user_id": 
				case "processor": 
					if($found) $where.=" and "; else $where = " where ";
					$where .= " ".TABLE_INVOICES.".`$key` = '$value' ";
					$found = 1;
				break;
				case "username": 
					if($found) $where.=" and "; else $where = " where ";
					$where .= " ".TABLE_USERS.".`$key` like '$value' ";
					$found = 1;
				break;
				case "email": 
					if($found) $where.=" and "; else $where = " where ";
					$where .= " ".TABLE_USERS.".`$key` like '$value' ";
					$found = 1;
				break;
				case "amount_from": 
					if($found) $where.=" and "; else $where = " where ";
					$where .= " ".TABLE_INVOICES.".`amount` >= '$value' ";
					$found = 1;
				break;
				case "amount_to": 
					if($found) $where.=" and "; else $where = " where ";
					$where .= " ".TABLE_INVOICES.".`amount` <= '$value' ";
					$found = 1;
				break;
				case "date_from": 
					if($found) $where.=" and "; else $where = " where ";
					$where .= " ".TABLE_INVOICES.".`date` >= '$value' ";
					$found = 1;
				break;
				case "date_to": 
					if($found) $where.=" and "; else $where = " where ";
					$where .= " ".TABLE_INVOICES.".`date` <= '$value' ";
					$found = 1;
				break;
			}
		}

		$no_invoices = $this->getNoSearchInvoices($where, $join);
		$this->setNoInvoices($no_invoices);
	
	$array=$db->fetchAssocList("select *, ".TABLE_INVOICES.".id as invoice_id, ".TABLE_INVOICES.".amount as invoice_amount, date_format(".TABLE_INVOICES.".`date`,'$date_format') as date_nice, ".TABLE_PAYMENT_PROCESSORS.".processor_name 
	from ".TABLE_INVOICES." 
	$join 
	".$where." order by ".TABLE_INVOICES.".`$order` $order_way limit $start, $no_per_page");
	$i=0;
	
	$result=array();
	foreach( $array as $row){

		$result[$i]=$row;

		$result[$i]['amount_nice'] = add_currency($result[$i]['invoice_amount']);

		if($result[$i]['tax']) $result[$i]['tax_nice'] = add_currency($result[$i]['tax']);

		$fields = array();
		$result[$i]['user_details'] = cleanHtml($result[$i]['user_details']);
		
		$i++;
	}

	return $result;

}

	function getNoSearchInvoices($where='', $join='') {

		global $db;
		$total=$db->fetchRow('select count(*) from '.TABLE_INVOICES.$join.$where);
		return $total;
		
	}

	function setNoInvoices($no) {

		$this->no_invoices = $no;

	}

	function noInvoices() {

		return $this->no_invoices;

	}

	function generateInexisting() {
	
	}

	function makePaymentDetails($action) {
	
		global $lng;
		$payment_details = array();
		$i = 0;

		// either new/renew listing or new/renew subscription with or without listing
		if(($action['newpkg']['value']==1 || $action['renewpkg']['value']==1) && ($action['newpkg']['price']>0 || $action['renewpkg']['price']>0) && $action['pkg_id']>0) {
		
			// check if the plan is a subscription or not
			global $config_abs_path;
			require_once $config_abs_path."/classes/packages.php";
			require_once $config_abs_path."/classes/users_packages.php";
			$up = new users_packages();
			$pkg = new packages();
			$pkg_id = $up->getPackageId($action['pkg_id']);
			$pkg_type = $pkg->getType($pkg_id);
			
			if($action['newpkg']['value']==1) $price = $action['newpkg']['price'];
			else $price = $action['renewpkg']['price'];
			
			// subscription
			if($pkg_type=="sub") {
		
				if($action['newpkg']['value']==1) {
					$payment_details[$i]['description'] = $lng['invoice']['new_subscription'].": #".$action['pkg_id'];
				}
				else {
					$payment_details[$i]['description'] = $lng['invoice']['renew_subscription'].": #".$action['pkg_id'];
				}
				$payment_details[$i]['amount'] = $price;
				$payment_details[$i]['amount_nice'] = add_currency($payment_details[$i]['amount']);
				$i++;
			
			}
			else {
			
				// ad type plan
				if($action['newad']['value']==1 && $action['ad_id']) {
					$payment_details[$i]['description'] = $lng['invoice']['new_listing'].": #".$action['ad_id'];
					$payment_details[$i]['amount'] = $price;
					$payment_details[$i]['amount_nice'] = add_currency($payment_details[$i]['amount']);
					$i++;
				}
				if($action['renewad']['value']==1 && $action['ad_id']) {
					$payment_details[$i]['description'] = $lng['invoice']['renew_listing'].": #".$action['ad_id'];
					$payment_details[$i]['amount'] = $price;
					$payment_details[$i]['amount_nice'] = add_currency($payment_details[$i]['amount']);
					$i++;
				}
			
			} // end if pkg type
		
		} // end if newpkg
		
		if($action['featured']['value']>=1 && $action['featured']['price']>0 && $action['ad_id']) {
			global $config_abs_path;
			require_once $config_abs_path."/classes/featured_plans.php";
			$no_days = featured_plans::getDays($arr['extra']);
			$payment_details[$i]['description'] = $lng['invoice']['featured_eo']." ".$no_days." ".$lng['days']." : #".$action['ad_id'];
			$payment_details[$i]['amount'] = $action['featured']['price'];
			$payment_details[$i]['amount_nice'] = add_currency($payment_details[$i]['amount']);
			$i++;
		}
		if($action['highlited']['value']==1 && $action['highlited']['price']>0 && $action['ad_id']) {
			$payment_details[$i]['description'] = $lng['invoice']['highlited_eo'].": #".$action['ad_id'];
			$payment_details[$i]['amount'] = $action['highlited']['price'];
			$payment_details[$i]['amount_nice'] = add_currency($payment_details[$i]['amount']);
			$i++;
		}
		if($action['priority']['value']>=1 && $action['priority']['price']>0 && $action['ad_id']) {
			$payment_details[$i]['description'] = $lng['invoice']['priority_eo'].": #".$action['ad_id'];
			$payment_details[$i]['amount'] = $action['priority']['price'];
			$payment_details[$i]['amount_nice'] = add_currency($payment_details[$i]['amount']);
			$i++;
		}
		if($action['video']['value']==1 && $action['video']['price']>0 && $action['ad_id']) {
			$payment_details[$i]['description'] = $lng['invoice']['video_eo'].": #".$action['ad_id'];
			$payment_details[$i]['amount'] = $action['video']['price'];
			$payment_details[$i]['amount_nice'] = add_currency($payment_details[$i]['amount']);
			$i++;
		}

		if($action['new_creditspkg']['value']==1 && $action['new_creditspkg']['price']>0 && $action['credits_pkg_id']) {
			$payment_details[$i]['description'] = $lng['invoice']['new_credits_pkg'].": #".$action['credits_pkg_id'];
			$payment_details[$i]['amount'] = $action['new_creditspkg']['price'];
			$payment_details[$i]['amount_nice'] = add_currency($payment_details[$i]['amount']);
			$i++;
		}

		if($action['store']['value']==1 && $action['store']['price']>0) {
			$payment_details[$i]['description'] = $lng['invoice']['store'];
			$payment_details[$i]['amount'] = $action['store']['price'];
			$payment_details[$i]['amount_nice'] = add_currency($payment_details[$i]['amount']);
			$i++;
		}

		do_action("make_invoice_payment_details", array($action, &$payment_details));
		
		return $payment_details;
	}
	
}
?>
