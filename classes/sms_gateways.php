<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class sms_gateways{

var $error='';

public function __construct()
{
	
}

function getNo() {

	global $db;
	$no=$db->fetchRow("select count(*) from ".TABLE_SMS_GATEWAYS);
	return $no;

}

function getDefault() {

	global $db;
	$default=$db->fetchRow("select `gateway_code` from ".TABLE_SMS_GATEWAYS." where `default`=1");
	return $default;

}

function getSMSGateways() {

	global $db;
	$row=$db->fetchAssocList("select * from ".TABLE_SMS_GATEWAYS);
	return $row;

}

static function getAll() {

	global $db;
	$row=$db->fetchAssocList("select * from ".TABLE_SMS_GATEWAYS);
	return $row;

}

static function getAllSGList() {

	global $db;
	$arr=$db->fetchRowList("select `gateway_class` from ".TABLE_SMS_GATEWAYS);
	return $arr;

}

static function getSMSGatewayClass($code) {

	global $db;
	$result=$db->fetchRow("select `gateway_class` from ".TABLE_SMS_GATEWAYS." where `gateway_code`like '$code'");
	return $result;

}

function getSMSGateway($code) {

	global $db;
	$result=$db->fetchAssoc("select * from ".TABLE_SMS_GATEWAYS." where `gateway_code`like '$code'");
	return $result;

}

	function getSettings($code) {

		global $config_table_prefix;
		$class_name = sms_gateways::getSMSGatewayClass($code);
		$processor = new $class_name;

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

		$class_name = sms_gateways::getSMSGatewayClass($code);
		$processor = new $class_name;

		if(!$processor->saveSettings()) { 
			$this->setError($processor->getError());
			$this->tmp = $processor->getTmp();
			return 0;
		}
		return 1;
	}

	function setDefault($code) {

		global $db;
		$class_name = sms_gateways::getSMSGatewayClass($code);
		$processor = new $class_name;
		if($processor->correctSettings()) { 
			$db->query("update ".TABLE_SMS_GATEWAYS." set `default`=0");
			$db->query("update ".TABLE_SMS_GATEWAYS." set `default`=1 where `gateway_code`like '$code'");
			$this->clearPPSettingsCache();
			return 1;
		}
		$this->setError($processor->getError());
		return 0;
	}


	function editProcessor($id) {

		global $db;
		$array = array("gateway_title");
		$sign=""; $query_str = '';
		foreach($array as $a) {
			$this->clean[$a] = escape($_POST[$a]);
			$query_str .= $sign." `$a` = '{$this->clean[$a]}' ";
			$sign=",";
		}

		$res=$db->query("update ".TABLE_SMS_GATEWAYS." set ".$query_str." where `gateway_code` like '$id';");

	}


	function clearPPSettingsCache() {

		// clear cache
		$lc_cache = new cache();
		$lc_cache->clearCache("base_interface");//!!!!!!!!!!!

	}

	static function getName($processor_code) {

		global $db;
		$name = $db->fetchRow("select `gateway_name` from ".TABLE_SMS_GATEWAYS." where `gateway_code` like '$processor_code'");
		return $name;

	}

	function setTitle($gateway, $title) {

		global $db;
		$db->fetchRow("update ".TABLE_SMS_GATEWAYS." set `gateway_title`='$title' where `gateway_code`='$gateway'");
		return;

	}

}
?>
