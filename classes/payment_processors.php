<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class  payment_processors{

var $error='';

public function __construct()
{
	
}

function getNo() {

	global $db;
	$no=$db->fetchRow("select count(*) from ".TABLE_PAYMENT_PROCESSORS);
	return $no;

}

function getNoActive() {

	global $db;
	$no=$db->fetchRow("select count(*) from ".TABLE_PAYMENT_PROCESSORS." where `enabled`=1 and `free` =0");
	return $no;

}

function getPaymentProcessors() {

	global $db;
	$row=$db->fetchAssocList("select * from ".TABLE_PAYMENT_PROCESSORS." where `free`=0");
	return $row;

}

function getActivePaymentProcessors() {

	global $db;
	$row=$db->fetchAssocList("select * from ".TABLE_PAYMENT_PROCESSORS." where `enabled`=1 and `free`=0");
	return $row;

}

static function getActivePPList() {

	global $db;
	$arr=$db->fetchRowList("select `processor_class` from ".TABLE_PAYMENT_PROCESSORS." where `enabled`=1 and `free`=0");
	return $arr;

}

static function getAllPPList() {

	global $db;
	$arr=$db->fetchRowList("select `processor_class` from ".TABLE_PAYMENT_PROCESSORS);
	return $arr;

}

static function getAll() {

	global $db;
	$row=$db->fetchAssocList("select * from ".TABLE_PAYMENT_PROCESSORS);

	$i = 0;
	global $appearance_settings;

	foreach($row as $r) {
		$row[$i]['tax_str'] = '';
		if($row[$i]['percent_tax']) $row[$i]['tax_str'].=$row[$i]['percent_tax']."%";
		if($row[$i]['percent_tax'] && $row[$i]['fixed_tax']) $row[$i]['tax_str'].=" + ";
		if($row[$i]['fixed_tax']) $row[$i]['tax_str'].=$row[$i]['fixed_tax'].$appearance_settings['default_currency'];
		$i++;
	}

	return $row;

}

function getAllActive() {

	global $db;
	$row=$db->fetchAssocList("select * from ".TABLE_PAYMENT_PROCESSORS." where `enabled`=1");
	return $row;

}

static function getPaymentProcessorClass($code) {

	global $db;
	$result=$db->fetchRow("select `processor_class` from ".TABLE_PAYMENT_PROCESSORS." where `processor_code`like '$code'");
	return $result;

}

function getManual($code) {

	global $db;
	$result=$db->fetchRow("select `manual` from ".TABLE_PAYMENT_PROCESSORS." where `processor_code`like '$code'");
	return $result;

}

function isFree($code) {

	global $db;
	$result=$db->fetchRow("select `free` from ".TABLE_PAYMENT_PROCESSORS." where `processor_code`like '$code'");
	return $result;

}

function isPending($code) {

	global $db;
	$result=$db->fetchRow("select `pending` from ".TABLE_PAYMENT_PROCESSORS." where `processor_code`like '$code'");
	return $result;

}

function getPaymentProcessor($code) {

	global $db;
	$result=$db->fetchAssoc("select * from ".TABLE_PAYMENT_PROCESSORS." where `processor_code`like '$code'");
	return $result;

}

	function getSettings($code) {

		global $config_table_prefix;
		$class_name = payment_processors::getPaymentProcessorClass($code);
		$processor = new $class_name;

		//$processor->setTable($config_table_prefix.$table);
		$settings = $processor->getSettings();

		foreach ($settings as $key=>$value) {
			if(is_array($value)) continue;
			$settings[$key] = cleanStr($value);
		}
		return $settings;
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

	function saveSettings($code) {

		global $lng;
		$this->error='';
		$this->tmp=array();

		global $config_demo;
		if($config_demo==1) { $this->addError($lng['general']['errors']['demo'].'<br />'); return 0; }
		global $config_table_prefix;

		$class_name = payment_processors::getPaymentProcessorClass($code);
		$processor = new $class_name;
		//$processor->setTable($config_table_prefix.$table);

		if(!$processor->saveSettings()) { 
			$this->setError($processor->getError());
			$this->tmp = $processor->getTmp();
			return 0;
		}
		return 1;
	}

	function Enable($code) {

		global $db;
		$class_name = payment_processors::getPaymentProcessorClass($code);
		$processor = new $class_name;
		if($processor->correctSettings()) { 
			$db->query("update ".TABLE_PAYMENT_PROCESSORS." set enabled=1 where `processor_code`like '$code'");
			$this->clearPPSettingsCache();
			return 1;
		}
		$this->setError($processor->getError());
		return 0;
	}

	function Disable($code) {

		global $db;
		$db->query("update ".TABLE_PAYMENT_PROCESSORS." set enabled=0 where `processor_code`like '$code'");
		$this->clearPPSettingsCache();
		return 1;
	}

	function setPending($code) {

		global $db;
		$db->query("update ".TABLE_PAYMENT_PROCESSORS." set pending=1 where `processor_code`like '$code'");
		return 1;
	}

	function setNotPending($code) {

		global $db;
		$db->query("update ".TABLE_PAYMENT_PROCESSORS." set pending=0 where `processor_code`like '$code'");
		return 1;
	}
	
/*	function editTitle($id, $title) {

		global $db;

		if(!$title) return 0;

		$this->clean['title'] = escape($title);

		$res=$db->query('update '.TABLE_PAYMENT_PROCESSORS.' set `processor_title` = "'.$this->clean['title'].'" where `processor_code` like "'.$id.'";');

		return 1;

	}
*/
	function editRecurring($id, $recurring) {

		global $db;

		//if(!$recurring) return 0;

		$this->clean['recurring'] = escape($recurring);

		$res=$db->query('update '.TABLE_PAYMENT_PROCESSORS.' set `recurring` = "'.$this->clean['recurring'].'" where `processor_code` like "'.$id.'";');

		return 1;

	}

	function editProcessor($id) {

		global $db;
		$array = array("processor_title", "recurring", "percent_tax", "fixed_tax");
		$sign=""; $query_str = '';
		foreach($array as $a) {
			$this->clean[$a] = escape($_POST[$a]);
			if($a=="recurring" && (!isset($_POST[$a]) || $_POST[$a]=='')) continue;
			$query_str .= $sign." `$a` = '{$this->clean[$a]}' ";
			$sign=",";
		}

		$res=$db->query("update ".TABLE_PAYMENT_PROCESSORS." set ".$query_str." where `processor_code` like '$id';");

	}

	static function getRecurring($processor) {

		global $db;
		$recurring = $db->fetchRow("select `recurring` from ".TABLE_PAYMENT_PROCESSORS." where `processor_code` like '$processor'");
		return $recurring;

	}

	function calculateTax($processor, $total) {

		global $db;
		$taxes = $db->fetchAssoc("select `percent_tax`, `fixed_tax` from ".TABLE_PAYMENT_PROCESSORS." where `processor_code` like '$processor'");
		$percent_tax = 0;

		if($taxes['percent_tax']) $percent_tax = ($taxes['percent_tax']*$total)/100;
		return $percent_tax+$taxes['fixed_tax'];

	}

	function getFormattedTax($processor) {

		global $db;
		$taxes = $db->fetchAssoc("select `percent_tax`, `fixed_tax` from ".TABLE_PAYMENT_PROCESSORS." where `processor_code` like '$processor'");

		$tax_str = '';
		if($taxes['percent_tax']) $tax_str.=$taxes['percent_tax']."%";
		if($taxes['percent_tax'] && $taxes['fixed_tax']) $tax_str.=" + ";
		if($taxes['fixed_tax']) $tax_str.=add_currency($taxes['fixed_tax']);

		return $tax_str;

	}

	function amountWithTax($processor, $total) {

		return $total + $this->calculateTax($processor, $total);

	}

	function clearPPSettingsCache() {

		// clear cache
		$lc_cache = new cache();
		$lc_cache->clearCache("base_interface");

	}

	static function getName($processor_code) {

		global $db;
		$name = $db->fetchRow("select `processor_name` from ".TABLE_PAYMENT_PROCESSORS." where `processor_code` like '$processor_code'");
		return $name;

	}

}
?>
