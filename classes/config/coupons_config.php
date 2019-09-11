<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class coupons_config {

	var $error;
	var $tmp;
	var $clean;

	public function __construct()
	{
	
		$this->error=''; $this->tmp='';

	}

	function delete($id=0) {

		global $config_demo;
		if($config_demo==1) return;

		global $db;
		$res_del=$db->query('delete from '.TABLE_COUPONS.' where `id`="'.$id.'"');
		return 1;
	}

	function getAll() {

		global $db;
		global $lng;
		$result=$db->fetchAssocList('select * from '.TABLE_COUPONS.' order by id desc');
		$i=0;

		$array=array();
		foreach($result as $row) {
			$array[$i]=$row;
			if($row['groups']==-1)
				$array[$i]['groups_list'] = $lng['coupons']['all_groups'];
			else {
				$array[$i]['groups_list'] = '';
				$arr_groups = explode(",", $row['groups']);
				$j=0;
				foreach($arr_groups as $gr) {
					if($j) $array[$i]['groups_list'] .=', ';
					if($gr==0) $array[$i]['groups_list'] .= $lng['coupons']['not_logged_in'];
					else $array[$i]['groups_list'] .= cleanStr(groups::getName($gr));
					$j++;
				}
			}
			$i++;
		}
		return $array;
	}

	function coupon_exists($str,$id='') {

		global $db;
		if($id!='') $str_edit=" and id!=".$id; else $str_edit='';
		$no=$db->numRows("select * from ".TABLE_COUPONS." where `code` like '$str'".$str_edit);
		return $no;
	}

	function count() {
	
		global $db;
		$no=$db->fetchRow('select count(*) from '.TABLE_COUPONS);
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

	function check_form ($id='') {

		global $lng;
		$this->error='';
		$this->tmp=array();

		global $config_demo;
		if($config_demo==1) $this->addError($lng['general']['errors']['demo'].'<br>');

		if(!$_POST['code']) $this->addError($lng['coupons']['errors']['code_missing'].'<br>');
		else { 
			if($id && $this->coupon_exists($_POST['code'], $id)) $this->addError($lng['coupons']['errors']['code_exists'].'<br>');
			if(!$id && $this->coupon_exists($_POST['code'])) $this->addError($lng['coupons']['errors']['code_exists'].'<br>');
		}

		if(!$_POST['discount']) $this->addError($lng['coupons']['errors']['discount_missing'].'<br>');
		else if( !is_numeric($_POST['discount'])) $this->addError($lng['coupons']['errors']['invalid_discount'].'<br>');
		else if ($_POST['type'] == "percent" && (  0>$_POST['discount'] || $_POST['discount']>100 ))  $this->addError($lng['coupons']['errors']['invalid_discount'].'<br>');

		if(isset($_POST['choose_groups']) && $_POST['choose_groups']=="choose")
		{
			$groups_list='';
			$i=0;
			while (isset($_POST['groups'][$i]) && $group=$_POST['groups'][$i]){
				if($i) $groups_list.=',';
				$groups_list.=$group;
				$i++;
			}
		}


		if($this->getError()!='')
		{
			if($id) $this->tmp['id']=$id;
			if(isset($_POST['code'])) $this->tmp['code']=$_POST['code']; else $this->tmp['code']='';
			if(isset($_POST['discount'])) $this->tmp['discount']=$_POST['discount']; else $this->tmp['discount']='';
			if(isset($_POST['type'])) $this->tmp['type']=$_POST['type']; else $this->tmp['type']=0;
			if(isset($_POST['allow'])) $this->tmp['allow']=$_POST['allow']; else $this->tmp['allow']='';

			$array_checkboxes = array("ads", "store");
			foreach ($array_checkboxes as $ck) {
				if(isset($_POST[$ck]) && $_POST[$ck]=="on") $this->tmp[$ck] = 1; else $this->tmp[$ck] = 0;
			}

			if(isset($_POST['choose_group'])) $this->tmp['choose_group']=$_POST['choose_group'];
			if(isset($_POST['choose_group']) && $_POST['choose_group']=="choose")
			{
				$this->tmp['groups']='';
				$i=0;
				while (isset($_POST['groups'][$i]) && $group=escape($_POST['groups'][$i])!=''){
					if($i) $this->tmp['groups'].=',';
					$this->tmp['groups'].=$group;
					$i++;
				}
			} else $this->tmp['groups']='-1';
		}

		return 1;
	}

	function add() {
	
		global $db;
		$this->clean=array();
		$this->check_form();
		if($this->getError()!='') return 0;
		$this->clean['code'] = escape($_POST['code']);
		$this->clean['discount'] = escape($_POST['discount']);
		$this->clean['type'] = escape($_POST['type']);

		$this->clean['allow'] = escape($_POST['allow']);
		if(!is_numeric($this->clean['allow']) || $this->clean['allow']<0) $this->clean['allow']=0;

		if(isset($_POST['choose_group']) && $_POST['choose_group']=="choose")
		{
			$this->clean['groups']='';
			$i=0;
			while (isset($_POST['groups'][$i])){
				$group=escape($_POST['groups'][$i]);
				if($i) $this->clean['groups'].=',';
				$this->clean['groups'].=$group;
				$i++;
			}
		} else $this->clean['groups']='-1';

		$array_checkboxes = array("ads", "store");
		foreach ($array_checkboxes as $ck) {
			$this->clean[$ck] = checkbox_value($ck);
		}

		$db->query('insert into '.TABLE_COUPONS.' set `code` = "'.$this->clean['code'].'", `type` = "'.$this->clean['type'].'",  `discount` =  "'.$this->clean['discount'].'", `ads` = "'.$this->clean['ads'].'", `store` = "'.$this->clean['store'].'", `allow` = "'.$this->clean['allow'].'", `groups` = "'.$this->clean['groups'].'";');
		
		return 1;

	}

	function edit($id) {
	
		global $db;
		$this->clean=array();
		$this->check_form($id);
		if($this->getError()!='') return 0;
		$this->clean['code'] = escape($_POST['code']);
		$this->clean['discount'] = escape($_POST['discount']);
		$this->clean['type'] = escape($_POST['type']);
		$this->clean['allow'] = escape($_POST['allow']);
		if(!is_numeric($this->clean['allow']) || $this->clean['allow']<0) $this->clean['allow']=0;

		$array_checkboxes = array("ads", "store");
		foreach ($array_checkboxes as $ck) {
			$this->clean[$ck] = checkbox_value($ck);
		}

		if(isset($_POST['choose_group']) && $_POST['choose_group']=="choose")
		{
			$this->clean['groups']='';
			$i=0;
			while (isset($_POST['groups'][$i])){
				$group=escape($_POST['groups'][$i]);
				if($i) $this->clean['groups'].=',';
				$this->clean['groups'].=$group;
				$i++;
			}
		} else $this->clean['groups']='-1';

		$res = $db->query("update ".TABLE_COUPONS." set `code` = '".$this->clean['code']."', `type` = '".$this->clean['type']."',  `discount` =  '".$this->clean['discount']."', `ads` = '".$this->clean['ads']."', `store` = '".$this->clean['store']."', `allow` = '".$this->clean['allow']."', `groups` = '".$this->clean['groups']."' where id=$id;");
		
		return 1;

	}

}
?>
