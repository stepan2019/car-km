<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class featured_plans_config {

	var $error;
	var $tmp;

	public function __construct()
	{
	
		$this->error=''; $this->tmp='';

	}

	function getNo() {

		global $db;
		$no=$db->fetchRow('select count(*) from '.TABLE_FEATURED_PLANS);
		return $no;
	}

	function orderExists($order) {

		global $db;
		$res=$db->query("select * from ".TABLE_FEATURED_PLANS." where order_no='$order'");
		if($db->numRows($res)) return 1;
		return 0;

	}

	function delete($id) {

		global $db;
		global $config_demo;
		if($config_demo==1) return;

		// remake order
		$this->remakeOrder($id);

		$res_del=$db->query("delete from ".TABLE_FEATURED_PLANS." where id='$id'");

		$this->clearFeaturedPlansCache();
	}

	function remakeOrder($id) {
	
		global $db;
		$order=$db->fetchRow("select `order_no` from ".TABLE_FEATURED_PLANS." where id='$id'");
		if(!$order) return;
		$res=$db->query("update ".TABLE_FEATURED_PLANS." set `order_no`=`order_no`-1 where `order_no`>='$order'");

		$this->clearFeaturedPlansCache();
	}

	function moveDown($id) {
		
		global $db;
		if(!$id) $id=$this->id;

		global $config_demo;
		if($config_demo==1) return;

		$order_no=$db->fetchRow("select `order_no` from ".TABLE_FEATURED_PLANS." where `id`='$id'");
		$goto=$order_no-1;
		$res1=$db->query("update ".TABLE_FEATURED_PLANS." set `order_no`='1000' where `id`='$id'");
		$res2=$db->query("update ".TABLE_FEATURED_PLANS." set `order_no`='$order_no' where `order_no`='$goto'");
		$res3=$db->query("update ".TABLE_FEATURED_PLANS." set `order_no`='$goto' where `id`='$id'");

		$this->clearFeaturedPlansCache();

		return 1;
	}

	function moveUp($id) {
		
		global $db;
		if(!$id) $id=$this->id;

		global $config_demo;
		if($config_demo==1) return;

		$order_no=$db->fetchRow("select `order_no` from ".TABLE_FEATURED_PLANS." where `id`='$id'");
		$goto=$order_no+1;
		$res1=$db->query("update ".TABLE_FEATURED_PLANS." set `order_no`='1000' where `id`='$id'");
		$res2=$db->query("update ".TABLE_FEATURED_PLANS." set `order_no`='$order_no' where `order_no`='$goto'");
		$res3=$db->query("update ".TABLE_FEATURED_PLANS." set `order_no`='$goto' where `id`='$id'");

		$this->clearFeaturedPlansCache();

		return 1;
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

	function check_form ($id='') {

		global $lng;
		$this->error='';
		$this->tmp=array();

		global $config_demo;
		if($config_demo==1) $this->addError($lng['general']['errors']['demo'].'<br />');

		if(!$_POST['fp_amount'] ) $this->addError($lng['priorities']['errors']['price_missing'].'<br />');
		else if (!validator::valid_price(escape($_POST['fp_amount']))) $this->addError($lng['priorities']['errors']['invalid_price'].'<br />');

		if($this->getError()!='')
		{
			if($id!='') $this->tmp['id']=$id;
			if(isset($_POST['fp_amount'])) $this->tmp['amount']=$_POST['fp_amount']; else $this->tmp['amount']='';
			if(isset($_POST['fp_no_days'])) $this->tmp['no_days']=$_POST['fp_no_days']; else $this->tmp['no_days']='';

		}
		return 1;
	}

	function add() {

		global $db;

		$this->clean=array();
		$this->check_form();
		if($this->getError()!='') return 0;

		global $appearance;
		$decimals = $appearance['number_format_decimals'];
		$point = $appearance['number_format_point'];
		$separator = $appearance['number_format_separator'];
		$amount = escape($_POST['fp_amount']);
		//remove number format separator
		$amount = str_replace($separator,"",$amount);
		//replace number_format_point with "."
		$amount = str_replace($point,".",$amount);

		$format='%f';
		$arr = sscanf($amount, $format, $this->clean['amount']);
		$order_no=$this->getOrder();

		$no_days = escape($_POST['fp_no_days']);

		$res=$db->query('insert into '.TABLE_FEATURED_PLANS.' (`amount`, `no_days`, `order_no`) values ("'.$this->clean['amount'].'", "'.$no_days.'", "'.$order_no.'");');

		$this->clearFeaturedPlansCache();

		return 1;

	}

	function edit($id) {

		global $db;
		global $crt_lang;
		global $lng;
		$this->clean=array();

		$this->check_form($id);
		if($this->getError()!='') return 0;

		$this->clean['amount'] = escape($_POST['fp_amount']);
		$this->clean['no_days'] = escape($_POST['fp_no_days']);

		$res=$db->query('update '.TABLE_FEATURED_PLANS.' set `amount` = "'.$this->clean['amount'].'", `no_days` = "'.$this->clean['no_days'].'" where id='.$id.';');
		$this->clearFeaturedPlansCache();

		return 1;

	}

	function getOrder() {

		global $db;
		$order_no=$db->fetchRow('select `order_no` from '.TABLE_FEATURED_PLANS.' order by `order_no` desc limit 1');
		if($order_no) $order_no=$order_no+1;
		else $order_no=1;
		return $order_no;
	}

	function clearFeaturedPlansCache() {

		// clear cache
		$lc_cache = new cache();
		$lc_cache->clearCache("base_featured_plans");

	}

}
?>
