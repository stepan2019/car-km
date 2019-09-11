<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class currencies {

	var $name;

	public function __construct($name='')
	{
		$this->name=$name;
	}

	function getNo() {

		global $db;
		$no=$db->fetchRow("select count(*) from ".TABLE_CURRENCIES);
		return $no;

	}

	function add() {
	
		global $config_demo;
		if($config_demo==1) return;
		$this->clean=array();
		$this->clean['name'] = escape($_POST['name']);
		$order_no=$this->getOrder();

		global $db;
		$res=$db->query('insert into '.TABLE_CURRENCIES.' (`currency`, `order_no`) values ("'.$this->clean['name'].'", '.$order_no.');');
		
		$this->clearCurrenciesCache();

		return 1;

	}

	function orderExists($order) {

		global $db;
		$res=$db->query("select * from ".TABLE_CURRENCIES." where `order_no`='$order'");
		if($db->numRows($res)) return 1;
		return 0;

	}

	function delete($id) {

		global $config_demo;
		if($config_demo==1) return;

		// remake order
		$this->remakeOrder($curr);

		//delete subcategories
		global $db;
		$res=$db->query("delete from ".TABLE_CURRENCIES." where `id`='$id'");

		$this->clearCurrenciesCache();

	}

	function remakeOrder($id) {
	
		global $db;
		$order=$db->fetchRow("select `order_no` from ".TABLE_CURRENCIES." where id='$id'");
		if(!$order) return;
		$res=$db->query("update ".TABLE_CURRENCIES." set `order_no`=`order_no`-1 where `order_no`>='$order'");

		$this->clearCurrenciesCache();

	}

	function moveUp($id) {
		
		global $db;
		$order_no=$db->fetchRow("select `order_no` from ".TABLE_CURRENCIES." where id='$id'");
		$goto=$order_no-1;
		$res1=$db->query("update ".TABLE_CURRENCIES." set `order_no`='1000' where id='$id'");
		$res2=$db->query("update ".TABLE_CURRENCIES." set `order_no`='$order_no' where `order_no`='$goto'");
		$res3=$db->query("update ".TABLE_CURRENCIES." set `order_no`='$goto' where id='$id'");

		$this->clearCurrenciesCache();
		return 1;
	}

	function moveDown($id) {
		
		global $db;
		$order_no=$db->fetchRow("select `order_no` from ".TABLE_CURRENCIES." where id='$id'");
		$goto=$order_no+1;
		$res1=$db->query("update ".TABLE_CURRENCIES." set `order_no`='1000' where id='$id'");
		$res2=$db->query("update ".TABLE_CURRENCIES." set `order_no`='$order_no' where `order_no`='$goto'");
		$res3=$db->query("update ".TABLE_CURRENCIES." set `order_no`='$goto' where id='$id'");

		$this->clearCurrenciesCache();
		return 1;
	}

	function getOrder() {

		global $db;
		$order_no = $db->fetchRow('select `order_no` from '.TABLE_CURRENCIES.' order by `order_no` desc limit 1');
		if($order_no) $order_no=$order_no+1;
		else $order_no=1;
		return $order_no;
	}

	function clearCurrenciesCache() {

		// clear cache
		$lc_cache = new cache();
		$lc_cache->clearCache("base_interface");

	}

}
?>
