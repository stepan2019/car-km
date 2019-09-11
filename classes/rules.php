<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class rules {

	var $id;
	var $error;

	public function __construct()
	{
	
		$this->error = '';
		$this->fieldError = array();
	}

	function getOrderNo($id) {
		
		global $db;
		$ord=$db->fetchRow("select `order_no` from ".TABLE_RULES." where `id`=$id");
		return $ord;
	}

	function getByOrder($order_no) {
		
		global $db;
		$arr=$db->fetchRow("select * from ".TABLE_RULES." where `order_no`=$order_no");
		return $name;
	}

	function getNo() {

		global $db;
		$no=$db->fetchRow('select count(*) from '.TABLE_RULES);
		return $no;
	}

	function getAll() {

		global $db;
		global $lng;
		$array=$db->fetchAssocList("select * from ".TABLE_RULES." order by `order_no`");
		$i=0;

		$arr=array();
		foreach($array as $result) {	
			$arr[$i]=$result;
			$arr[$i]['last'] = 0;	

			$arr[$i]['categories'] = '';
			if($arr[$i]['category']==0) $arr[$i]['categories'] = $lng['banners']['all_categories'];
			$arr_categ = explode(",", $arr[$i]['category']);
			foreach($arr_categ as $cat) {
				if($cat==0) continue;
				if($arr[$i]['categories']) $arr[$i]['categories'].=", ";
				$arr[$i]['categories'] .= cleanStr(categories::getName($cat));
			}

			$arr[$i]['details'] = '';
			$f = new fields('cf');
			$field_name = cleanStr($f->getNameByCaption($arr[$i]['field']));
			if($arr[$i]['type']=='allowed') {
				$second_field_name = cleanStr($f->getNameByCaption($arr[$i]['second_field']));
				$arr[$i]['details'] .= $lng['rules']['for_field'].": ".$field_name." ".$lng['rules']['values'].": ". str_replace("|", ",", $arr[$i]['selected_values'])." <br/> ".$lng['rules']['allow_on'].": ".$second_field_name." ".$lng['rules']['only_values'].": ".str_replace("|", ",", $arr[$i]['allowed_values']);
			}
			if($arr[$i]['type']=='required') {
				$required_field_name = cleanStr($f->getNameByCaption($arr[$i]['required_field']));
				$arr[$i]['details'] .= $lng['rules']['for_field'].": ".$field_name." ".$lng['rules']['values'].": ". str_replace("|", ",", $arr[$i]['selected_values'])." <br/> ".$lng['rules']['require_value_for'].": ".$required_field_name;
			}

			if($arr[$i]['type']=='required_gr') {
				$required_group_name = cleanStr(groups::getName($arr[$i]['required_group']));
				$arr[$i]['details'] .= $lng['rules']['for_field'].": ".$field_name." ".$lng['rules']['values'].": ". str_replace("|", ",", $arr[$i]['selected_values'])." <br/> ".$lng['rules']['required_group'].": ".$required_group_name;
			}

			$i++;
		}
		if($i) $arr[$i-1]['last'] = 1;
		return $arr;
	}

	function getRules($categ) {

		global $db;
		$str_categ = "";
		if($categ) $str_categ = " where ( `category` like '0' or `category` REGEXP '\[\[:<:\]\]$categ\[\[:>:\]\]' )";
		$array=$db->fetchAssocList("select * from ".TABLE_RULES.$str_categ." order by `order_no`");
		return $array;
	}

	function getRule($id) {

		global $db;
		$array=$db->fetchAssoc("select * from ".TABLE_RULES." where `id`='$id'");
		return $array;
	}

	function orderExists($order) {

		global $db;
		$res=$db->query("select * from ".TABLE_RULES." where order_no='$order'");
		if($db->numRows($res)) return 1;
		return 0;

	}

	function delete($id) {

		global $db;
		global $config_demo;
		if($config_demo==1) return;

		// remake order
		$this->remakeOrder($id);

		$res_del=$db->query("delete from ".TABLE_RULES." where id='$id'");

	}

	function remakeOrder($id) {
	
		global $db;
		$order=$db->fetchRow("select `order_no` from ".TABLE_RULES." where id='$id'");
		if(!$order) return;
		$res=$db->query("update ".TABLE_RULES." set `order_no`=`order_no`-1 where `order_no`>='$order'");
	}

	function getIdByOrder($order) {

		global $db;
		$id=$db->fetchRow('select id from '.TABLE_RULES.' limit '.$order.',1');
		return $id;
	}

	function moveUp($id) {
		
		global $db;
		if(!$id) $id=$this->id;

		global $config_demo;
		if($config_demo==1) return;

		$order_no=$db->fetchRow("select `order_no` from ".TABLE_RULES." where `id`='$id'");
		$goto=$order_no-1;
		$res1=$db->query("update ".TABLE_RULES." set `order_no`='1000' where `id`='$id'");
		$res2=$db->query("update ".TABLE_RULES." set `order_no`='$order_no' where `order_no`='$goto'");
		$res3=$db->query("update ".TABLE_RULES." set `order_no`='$goto' where `id`='$id'");

		return 1;
	}

	function moveDown($id) {
		
		global $db;
		if(!$id) $id=$this->id;

		global $config_demo;
		if($config_demo==1) return;

		$order_no=$db->fetchRow("select `order_no` from ".TABLE_RULES." where `id`='$id'");
		$goto=$order_no+1;
		$res1=$db->query("update ".TABLE_RULES." set `order_no`='1000' where `id`='$id'");
		$res2=$db->query("update ".TABLE_RULES." set `order_no`='$order_no' where `order_no`='$goto'");
		$res3=$db->query("update ".TABLE_RULES." set `order_no`='$goto' where `id`='$id'");

		return 1;
	}

	function getError() {
	
		return $this->error;

	}

	function getFieldError() {
	
		return $this->fieldError;

	}

	function addError($str) {

		$this->error.=	$str;

	}

	function addFieldError($err_field, $err_text) {

		array_push($this->fieldError, array("field"=> $err_field, "error" => $err_text));

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

		$type = $_POST['type'];
		if( !$_POST['selected_values']) $this->addError($lng['rules']['errors']['enter_selected_values'].'<br />');

		if($type=="allowed" && !$_POST['allowed_values']) $this->addError($lng['rules']['errors']['enter_allowed_values'].'<br />');

		if(!$_POST['error_message'])  $this->addError($lng['rules']['errors']['enter_error_message'].'<br />');

		if($this->getError()!='')
		{
			$array_fields = array("type", "choose_categ", "categories", "field", "selected_values", "second_field", "allowed_values", "required_field", "required_group", "error_message", "order_no");

			foreach($array_fields as $f) {
				if(isset($_POST[$f])) $this->tmp[$f]=cleanStr($_POST[$f]); else $this->tmp[$f]='';
			}
			
		}
		return 1;

	}

	function add() {

		global $db;
		$this->clean=array();
		$this->check_form();
		if($this->getError()!='') return 0;

		$categories_list='';
		if(isset($_POST['choose_categ']) && $_POST['choose_categ']=="choose")
		{
			$categories_list='';
			$i=0;
			while (isset($_POST['categories'][$i]) && $cat=$_POST['categories'][$i]){
				if($i) $categories_list.=',';
				$categories_list.=$cat;
				$i++;
			}
		}
		if(!$categories_list) $categories_list = 0;

		$order_no=$this->getOrder();

		$array_fields = array("type", "field", "selected_values", "second_field", "allowed_values", "required_field", "required_group", "error_message");
		$sql = "";
		foreach($array_fields as $f) {
			if(isset($_POST[$f]) && $_POST[$f]) { 
				if($sql) $sql.=", ";
				$clean[$f] = escape($_POST[$f]);
				$sql.=" `$f` =  '{$clean[$f]}' ";
				
			}
		}

		$res=$db->query("insert into ".TABLE_RULES." set `category` = '$categories_list', `order_no`= '".$order_no."', ".$sql);

		return 1;

	}

	function edit($id) {

		global $db;
		global $crt_lang;
		global $lng;
		$this->clean=array();

		$categories_list='';
		if(isset($_POST['choose_categ']) && $_POST['choose_categ']=="choose")
		{
			$categories_list='';
			$i=0;
			while (isset($_POST['categories'][$i]) && $cat=$_POST['categories'][$i]){
				if($i) $categories_list.=',';
				$categories_list.=$cat;
				$i++;
			}
		}
		if(!$categories_list) $categories_list = 0;

		$array_fields = array("type", "field", "selected_values", "second_field", "allowed_values", "required_field", "required_group", "error_message");
		$sql = "";
		foreach($array_fields as $f) {
			if(isset($_POST[$f]) && $_POST[$f]) { 
				if($sql) $sql.=", ";
				$clean[$f] = escape($_POST[$f]);
				$sql.=" `$f` =  '{$clean[$f]}' ";
				
			}
		}

		$res=$db->query("update ".TABLE_RULES." set `category` = '$categories_list', ".$sql." where id='$id'");

		return 1;

	}

	function getOrder() {

		global $db;
		$order_no=$db->fetchRow('select `order_no` from '.TABLE_RULES.' order by `order_no` desc limit 1');
		if($order_no) $order_no=$order_no+1;
		else $order_no=1;
		return $order_no;
	}

	function getList() {

		global $db;
		$array=$db->fetchAssocList("select id from ".TABLE_RULES);
		return $array;

	}

	function verifyRules($fields) {

		$categ = 0;
		if(isset($_POST['category'])) $categ = escape($_POST['category']);
		$rules = $this->getRules($categ);
		foreach($rules as $r) {
			if($r['type']=="allowed") $this->validateAllowed($r, $fields);
			if($r['type']=="required") $this->validateRequired($r, $fields);
			if($r['type']=="required_gr") $this->validateRequiredGroup($r, $fields);
		}
	}

	function validateAllowed($rule, $fields) {

		// for field 'field' selected values 'selected_values'
		// second_field must have allowed_values

		if( !in_array( $rule['field'], $fields)) return;
		if( !in_array( $rule['second_field'], $fields)) return;

		$array_values = explode("|", $rule['selected_values']);

		if(isset($_POST[$rule['field']]) && $this->in_arrayi($_POST[$rule['field']], $array_values)) {
			$allowed_values = explode("|", $rule['allowed_values']);
			if(!in_array($_POST[$rule['second_field']], $allowed_values)) $this->addFieldError($rule['second_field'], $rule['error_message']);
		}

	}

	function validateRequired($rule, $fields) {

		// for field 'field' selected values 'selected_values'
		// a value for 'second_field' must be provided
		
		if( !in_array( $rule['field'], $fields)) return;
		if( !in_array( $rule['required_field'], $fields)) return;

		// for field 'field' selected values 'selected_values'
		// a value is required for 'required_field'
		$array_values = explode("|", $rule['selected_values']);
		if(isset($_POST[$rule['field']]) && $this->in_arrayi($_POST[$rule['field']], $array_values)) {
			if(!$_POST[$rule['required_field']]) $this->addFieldError($rule['required_field'], $rule['error_message']);
		}

	}

	function validateRequiredGroup($rule, $fields) {

		if( !in_array( $rule['field'], $fields)) return;

		// for field 'field' selected values 'selected_values'
		// group 'required_group' is required
		
		$array_values = explode("|", $rule['selected_values']);
		if(isset($_POST[$rule['field']]) && $this->in_arrayi($_POST[$rule['field']], $array_values)) {
			checkAuth();
			global $usr_sett;
			if($usr_sett['group'] != $rule['required_group']) $this->addFieldError($rule['field'], $rule['error_message']);
		}

	}


    function in_arrayi($needle, $haystack) {
        return in_array(strtolower($needle), array_map('strtolower', $haystack));
    }

}
?>
