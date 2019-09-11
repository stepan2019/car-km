<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class priorities_config {

	var $error;
	var $tmp;

	public function __construct()
	{
	
		$this->error=''; $this->tmp='';

	}

	function setName($name) {

		global $db;
		global $crt_lang;
		$this->name=$name;
		$db->query("update ".TABLE_PRIORITIES." set name='$name' where id='".$this->id."' and `lang_id` = '$crt_lang'");
		$this->clearPrioritiesCache();

	}

	function getNo() {

		global $db;
		$no=$db->fetchRow('select count(*) from '.TABLE_PRIORITIES);
		return $no;
	}

	function orderExists($order) {

		global $db;
		$res=$db->query("select * from ".TABLE_PRIORITIES." where order_no='$order'");
		if($db->numRows($res)) return 1;
		return 0;

	}

	function delete($id) {

		global $db;
		global $config_demo;
		if($config_demo==1) return;

		// remake order
		$this->remakeOrder($id);

		$res_del=$db->query("delete from ".TABLE_PRIORITIES." where id='$id'");
		$res_del_lang=$db->query("delete from ".TABLE_PRIORITIES."_lang where id='$id'");

		$this->clearPrioritiesCache();
	}

	function remakeOrder($id) {
	
		global $db;
		$order=$db->fetchRow("select `order_no` from ".TABLE_PRIORITIES." where id='$id'");
		if(!$order) return;
		$res=$db->query("update ".TABLE_PRIORITIES." set `order_no`=`order_no`-1 where `order_no`>='$order'");

		$this->clearPrioritiesCache();
	}

	function moveDown($id) {
		
		global $db;
		if(!$id) $id=$this->id;

		global $config_demo;
		if($config_demo==1) return;

		$order_no=$db->fetchRow("select `order_no` from ".TABLE_PRIORITIES." where `id`='$id'");
		$goto=$order_no-1;
		$res1=$db->query("update ".TABLE_PRIORITIES." set `order_no`='1000' where `id`='$id'");
		$res2=$db->query("update ".TABLE_PRIORITIES." set `order_no`='$order_no' where `order_no`='$goto'");
		$res3=$db->query("update ".TABLE_PRIORITIES." set `order_no`='$goto' where `id`='$id'");

		$this->clearPrioritiesCache();

		return 1;
	}

	function moveUp($id) {
		
		global $db;
		if(!$id) $id=$this->id;

		global $config_demo;
		if($config_demo==1) return;

		$order_no=$db->fetchRow("select `order_no` from ".TABLE_PRIORITIES." where `id`='$id'");
		$goto=$order_no+1;
		$res1=$db->query("update ".TABLE_PRIORITIES." set `order_no`='1000' where `id`='$id'");
		$res2=$db->query("update ".TABLE_PRIORITIES." set `order_no`='$order_no' where `order_no`='$goto'");
		$res3=$db->query("update ".TABLE_PRIORITIES." set `order_no`='$goto' where `id`='$id'");

		$this->clearPrioritiesCache();

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

		global $languages;

		foreach ($languages as $lang) {

			if(!$_POST['name_'.$lang['id']]) { $this->addError($lng['priorities']['errors']['name_missing'].'<br />');
				break;
			}
		}

		if(!$_POST['price'] ) $this->addError($lng['priorities']['errors']['price_missing'].'<br />');
		else if (!validator::valid_price(escape($_POST['price']))) $this->addError($lng['priorities']['errors']['invalid_price'].'<br />');

		if($this->getError()!='')
		{
			if($id!='') $this->tmp['id']=$id;
			if(isset($_POST['price'])) $this->tmp['price']=$_POST['price']; else $this->tmp['price']='';

			foreach ($languages as $lang) {

				$lang_id = $lang['id'];
				if(isset($_POST["name_".$lang_id])) 
					$this->tmp["name"][$lang_id]=cleanStr($_POST["name_".$lang_id]);
				else $this->tmp["name"][$lang_id]='';
			}
		
		}
		return 1;
	}

	function add() {

		global $db;

		$this->clean=array();
		$this->check_form();
		if($this->getError()!='') return 0;

		global $languages;
		foreach ($languages as $lang) {
			$lang_id = $lang['id'];
			$f = "name_".$lang_id;
			if(isset($_POST[$f])) $this->clean["name"][$lang_id] = escape($_POST[$f]); 
			else $this->clean["name"][$lang_id] = '';
		}

		global $appearance;
		$decimals = $appearance['number_format_decimals'];
		$point = $appearance['number_format_point'];
		$separator = $appearance['number_format_separator'];
		$price = escape($_POST['price']);
		//remove number format separator
		$price = str_replace($separator,"",$price);
		//replace number_format_point with "."
		$price = str_replace($point,".",$price);

		$format='%f';
		$arr = sscanf($price, $format, $this->clean['price']);
		$order_no=$this->getOrder();

		$res=$db->query('insert into '.TABLE_PRIORITIES.' (`price`, `order_no`) values ("'.$this->clean['price'].'", "'.$order_no.'");');

		$id=$db->insertId();

		foreach ($languages as $lang) {
			$lang_id = $lang['id'];
			$res=$db->query("insert into ".TABLE_PRIORITIES."_lang (`id`, `lang_id`, `name`) values ($id, '$lang_id', '".$this->clean['name'][$lang_id]."');");
		}

		$this->clearPrioritiesCache();

		return 1;

	}

	function edit($id) {

		global $db;
		global $crt_lang;
		global $lng;
		$this->clean=array();

		$this->check_form($id);
		if($this->getError()!='') return 0;


		global $languages;
		foreach ($languages as $lang) {
			$lang_id = $lang['id'];
			$f = "name_".$lang_id;
			if(isset($_POST[$f])) $this->clean["name"][$lang_id] = escape($_POST[$f]); 
			else $this->clean["name"][$lang_id] = '';
		}

		$this->clean['price'] = escape($_POST['price']);

		$res=$db->query('update '.TABLE_PRIORITIES.' set `price` = "'.$this->clean['price'].'" where id='.$id.';');

		foreach ($languages as $lang) {

			$lang_id = $lang['id'];

			$res=$db->query("update ".TABLE_PRIORITIES."_lang set `name` = '".$this->clean['name'][$lang_id]."' where id=$id and `lang_id`='$lang_id';");

		}

		$this->clearPrioritiesCache();

		return 1;

	}

	function getOrder() {

		global $db;
		$order_no=$db->fetchRow('select `order_no` from '.TABLE_PRIORITIES.' order by `order_no` desc limit 1');
		if($order_no) $order_no=$order_no+1;
		else $order_no=1;
		return $order_no;
	}

	function getPriorityLang($id) {

		global $db;

		$row=$db->fetchAssoc("select * from ".TABLE_PRIORITIES." where id=".$id."");
		$array_lang=$db->fetchAssocList("select * from ".TABLE_PRIORITIES."_lang where id=".$id."");

		foreach($array_lang as $pri_lang) {

			$lang_id = $pri_lang['lang_id'];
			foreach ($pri_lang as $key=>$value) {
				if($key=='id' || $key=='lang_id') continue;
				$row[$key][$lang_id] = cleanStr($value);
			}
		}
		return $row;

	}

	function clearPrioritiesCache() {

		// clear cache
		$lc_cache = new cache();
		$lc_cache->clearCache("base_priorities");

	}

}
?>
