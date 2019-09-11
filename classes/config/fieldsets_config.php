<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class fieldsets_config {

	var $error;
	var $tmp;
	var $last;

	public function __construct()
	{
	
		$this->error=''; $this->tmp='';

	}

	function delete($id='') {

		global $db;
		global $config_demo;
		if($config_demo==1) return;

		if(!$id) $id=$this->id;

		$this->deleteFields($id);
		$res_del=$db->query("delete from ".TABLE_FIELDSETS." where id=$id;");
	}

	function deleteFields($id) {
	
		global $config_demo;
		if($config_demo==1) return;

		// only if the only fieldset
		global $db;
		$arr = $db->fetchRowList("select id from ".TABLE_FIELDS." where fieldset like '$id'");
		$fields = new fields_config('cf');
		foreach ($arr as $row) {
			$fields->delete($row);
		}

	}

	function getAll() {

		global $db;
		$arr=$db->fetchAssocList("select * from ".TABLE_FIELDSETS." order by ".TABLE_FIELDSETS.".`id`");
		$i=0;
		foreach($arr as $f) {
			$arr[$i++]['categories'] = $db->fetchRow("select count(*) from ".TABLE_CATEGORIES." where `fieldset`='{$f['id']}'");
		}
		
		return $arr;
	}

	function count() {
		
		global $db;
		$no=$db->fetchRow("select count(*) from ".TABLE_FIELDSETS);
		return $no;

	}

	function getNo() {
		
		global $db;
		$no=$db->fetchRow("select count(*) from ".TABLE_FIELDSETS);
		return $no;

	}
	function field_exists($str) {

		global $db;
		$res=$db->query("select * from ".TABLE_FIELDSETS." where `name` like '$str'");
		if($db->numRows($res)) return 1;
		return 0;

	}
	
	function valid_name($str) {

		if(preg_match("/^[A-Z][[:space:]A-Z0-9._-]*[A-Z0-9]$/i", $str))	return 1;
		else return 0;

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
		global $languages;

		global $config_demo;
		if($config_demo==1) $this->addError($lng['general']['errors']['demo'].'<br />');

		if(!$id) {

			if(!$_POST['fieldset_name']) { $this->addError($lng['fieldsets']['errors']['name_missing'].'<br />'); return; }
			else if($this->field_exists($_POST['fieldset_name'])) { $this->addError($lng['fieldsets']['errors']['name_exists'].'<br />'); return; }
			/*else if(!$this->valid_name(escape($_POST['fieldset_name']))) { $this->addError($lng['fieldsets']['errors']['invalid_name'].'<br />'); return; }*/
		}
		
		if($this->getError()!='') {

			if(isset($_POST['fieldset_name'])) $this->tmp['fieldset_name']=escape($_POST['fieldset_name']); else $this->tmp['fieldset_name']='';

			if(isset($_POST['description'])) $this->tmp['description']=escape($_POST['description']); else $this->tmp['description']='';

		}
		return 1;
	}

	function add() {
	
		global $db;
		$this->clean=array();
		$this->check_form();
		if($this->getError()!='') return 0;

		$this->clean['name'] = escape($_POST['fieldset_name']);
		if(isset($_POST['description'])) $this->clean['description']=escape($_POST['description']); else $this->clean['description']='';

		$res=$db->query("insert into ".TABLE_FIELDSETS." set `name` = '".$this->clean['name']."', `description` = '".$this->clean['description']."';");

		return 1;

	}

	function edit($id) {
	
		global $db;
		$this->clean=array();
		$this->check_form($id);
		if($this->getError()!='') return 0;

		$this->clean['name'] = escape($_POST['fieldset_name']);
		if(isset($_POST['description'])) $this->clean['description']=escape($_POST['description']); else $this->clean['description']='';

		$res=$db->query("update ".TABLE_FIELDSETS." set `name`='".$this->clean['name']."', `description`='".$this->clean['description']."' where `id`=$id;");

		return 1;

	}

}
?>
