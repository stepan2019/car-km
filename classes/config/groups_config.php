<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class groups_config {

	var $error;
	var $tmp;
	var $last;

	public function __construct()
	{
	
		$this->error=''; $this->tmp='';

	}

	function delete($id=0) {

		global $db;
		global $config_demo;
		if($config_demo==1) return;

		if(!$id) $id=$this->id;

		// remake order
		$this->remakeOrder($id);

		$res_del=$db->query('delete from '.TABLE_USER_GROUPS.' where id="'.$id.'"');
		$res_del_lang=$db->query('delete from '.TABLE_USER_GROUPS.'_lang where id="'.$id.'"');

		$usr=new users();
		$usr->deleteUsers($id);

		$this->clearGroupsCache();

	}

	function Enable($id=0) {

		global $db;
		global $config_demo;
		if($config_demo==1) return;

		if(!$id) $id=$this->id;
		$res=$db->query('update '.TABLE_USER_GROUPS.' set active=1 where id="'.$id.'"');

		$this->clearGroupsCache();

	}

	function Disable($id=0) {

		global $db;
		global $config_demo;
		if($config_demo==1) return;

		if(!$id) $id=$this->id;
		$res=$db->query('update '.TABLE_USER_GROUPS.' set active=0 where id="'.$id.'"');

		$this->clearGroupsCache();

	}

	function getGroupLang($id='') {
		
		global $db;
		if(!$id) $id=$this->id;
		$row=$db->fetchAssoc("select * from ".TABLE_USER_GROUPS." where id=".$id."");
		$array_lang=$db->fetchAssocList("select * from ".TABLE_USER_GROUPS."_lang where id=".$id."");

		foreach($array_lang as $gr_lang) {

			$lang_id = $gr_lang['lang_id'];
			foreach ($gr_lang as $key=>$value) {
				if($key=='id' || $key=='lang_id') continue;
				$row[$key][$lang_id] = cleanStr($value);
			}
		}
		return $row;

	}

	function count() {
	
		global $db;
		$no=$db->fetchRow('select count(*) from '.TABLE_USER_GROUPS);
		return $no;

	}

	function getNo() {
	
		global $db;
		$no=$db->fetchRow('select count(*) from '.TABLE_USER_GROUPS);
		return $no;

	}

	function getAll() {

		global $db;
		global $crt_lang, $settings;

		$array=$db->fetchAssocList("select ".TABLE_USER_GROUPS.".*, ".TABLE_USER_GROUPS."_lang.* from ".TABLE_USER_GROUPS." LEFT JOIN ".TABLE_USER_GROUPS."_lang on ".TABLE_USER_GROUPS.".`id` = ".TABLE_USER_GROUPS."_lang.`id` where `lang_id` = '$crt_lang' order by `order_no`");

		$i=0;
		$u = new users();
		$array_groups=array();
		foreach ($array as $result) {
			$array_groups[$i]=$result;
			$array_groups[$i]['users'] = $u->count($result['id']);
			$array_groups[$i]['last'] = 0;	
			$i++;
		}
		if($i) $array_groups[$i-1]['last'] = 1;
		return $array_groups;

	}

	function group_exists($str, $lang_id, $id='') {

		global $db;
		$res=$db->query("select * from ".TABLE_USER_GROUPS."_lang where `name` like '$str' and `lang_id`='$lang_id' and `id`!=$id");
		if($db->numRows($res)) return 1;
		return 0;
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

	function getInfo() {
	
		return $this->info;

	}

	function setInfo($str) {

		$this->info=$str;

	}

	function getTmp() {
	
		return $this->tmp;

	}

	function check_form ($id='') {

		global $db;
		$auth=new auth();
		$is_admin=0;
		if($auth->adminLoggedIn()) $is_admin=1;
		if(!$is_admin) return 0;

		global $lng;
		$this->error='';
		$this->tmp=array();
		global $languages;

		global $config_demo;
		if($config_demo==1) $this->addError($lng['general']['errors']['demo'].'<br />');

		foreach ($languages as $lang) {

			$lang_id = $lang['id'];
			if(!$_POST['name_'.$lang_id]) { $this->addError($lng['groups']['errors']['groupname_missing'].'<br />'); break;
			} else if($this->group_exists($_POST['name_'.$lang_id], $lang_id, $id)) { $this->addError($lng['groups']['errors']['groupname_exists'].'<br />'); break; }

		}
		
		if(isset($_POST['activate_via_sms']) && $_POST['activate_via_sms']=='on') {

			global $config_abs_path;
			require_once $config_abs_path."/classes/users.php";
			$usr = new users();
			$found = $usr->getRequiredIntlPhoneField($id);
			
			if(!$found) $this->addError($lng['groups']['errors']['no_required_intl_phone'].'<br />'); 
			
			// check if a SMS gateway is default and configured
			$sg = new sms_gateways();
			$default = $sg->getDefault();
			$no_sg = 0;
			if($default) {
			
				$class_name = sms_gateways::getSMSGatewayClass($default);
				global $config_abs_path;
				require_once($config_abs_path.'/classes/sms_verification/'.$class_name.'.php');
				
				$gcl = new $class_name;
				if(!$gcl->correctSettings()) $no_sg=1;

			} else {
			
				$no_sg = 1;
			
			}
			
			if($no_sg) $this->addError($lng['groups']['errors']['no_default_sms_or_not_configured'].'<br />'); 
			
 		
		}

		if($this->getError()!='')
		{

			// lang fields with default null value
			$array_inputs_null_lang = array('name', 'description');
			foreach ($array_inputs_null_lang as $field) {
				foreach ($languages as $lang) {

					$lang_id = $lang['id'];
					$f = $field."_".$lang_id;
					if(isset($_POST[$f])) 
						$this->tmp[$field][$lang_id]=cleanStr($_POST[$f]); 
					else $this->tmp[$field][$lang_id]='';
				}
			}

			if(isset($_POST['auto_register']) && $_POST['auto_register']=="on") $this->tmp['auto_register']=1; else $this->tmp['auto_register']=0;
			if(isset($_POST['activate_via_email']) && $_POST['activate_via_email']=="on") $this->tmp['activate_via_email']=1; else $this->tmp['activate_via_email']=0;
			if(isset($_POST['activate_via_sms']) && $_POST['activate_via_sms']=="on") $this->tmp['activate_via_sms']=1; else $this->tmp['activate_via_sms']=0;
			if(isset($_POST['store'])) $this->tmp['store']=escape($_POST['store']); else $this->tmp['store']=0;
			if(isset($_POST['bulk_uploads']) && $_POST['bulk_uploads']=="on") $this->tmp['bulk_uploads']=1; else $this->tmp['bulk_uploads']=0;
			if(isset($_POST['listing_pending']) && $_POST['listing_pending']=="on") $this->tmp['listing_pending']=1; else $this->tmp['listing_pending']=0;
			if(isset($_POST['package_pending']) && $_POST['package_pending']=="on") $this->tmp['package_pending']=1; else $this->tmp['package_pending']=0;
			if(isset($_POST['admin_verification']) && $_POST['admin_verification']=="on") $this->tmp['admin_verification']=1; else $this->tmp['admin_verification']=0;

			// fields with default 0 value
			$array_inputs_zero = array('affiliates_cookie_availability', 'affiliates_percentage', 'affiliates_payment_cycle');

			foreach ($array_inputs_zero as $field) {

				if(isset($_POST[$field])) $this->tmp[$field]=$_POST[$field]; else $this->tmp[$field]=0;
	
			}
		}

		return 1;
	}

	function add() {

		global $db;
		global $lng;
		$auth=new auth();
		$is_admin=$auth->adminLoggedIn();

		$this->clean=array();
		$this->check_form();
		if($this->getError()!='') return 0;

		global $languages;
		$array_lang = array('name', 'description');
		foreach ($array_lang as $field) {
			foreach ($languages as $lang) {
				$lang_id = $lang['id'];
				$f = $field."_".$lang_id;
				if(isset($_POST[$f])) $this->clean[$field][$lang_id] = escape($_POST[$f]); 
				else $this->clean[$field][$lang_id] = '';
			}
		}

		$this->clean['store'] = escape($_POST['store']);
		
		$this->clean['activate_via_email'] = checkbox_value('activate_via_email');
		$this->clean['activate_via_sms'] = checkbox_value('activate_via_sms');
		
		if(isset($_POST['default_credits']) && is_numeric($_POST['default_credits'])) $this->clean['default_credits'] = escape($_POST['default_credits']);
		else $this->clean['default_credits'] = 0;

		$checkboxes = array ('auto_register', 'post_ads', 'affiliates', 'bulk_uploads', 'listing_pending', 'package_pending', 'admin_verification');
		foreach ($checkboxes as $chk) {

			$this->clean[$chk] = checkbox_value($chk);

		}

		// fields with default 0 value
		$array_inputs_zero = array('affiliates_cookie_availability', 'affiliates_percentage', 'affiliates_payment_cycle');

		foreach ($array_inputs_zero as $field) {

			if(isset($_POST[$field])) $this->clean[$field]=$_POST[$field]; else $this->clean[$field]=0;

		}
			
		$order_no=$this->getOrder();

		$res=$db->query('insert into '.TABLE_USER_GROUPS.' (auto_register, post_ads, affiliates, activate_via_email, activate_via_sms, admin_verification, store, bulk_uploads, order_no, listing_pending, package_pending, default_credits, affiliates_cookie_availability, affiliates_percentage, affiliates_payment_cycle) values ("'.$this->clean['auto_register'].'", "'.$this->clean['post_ads'].'", "'.$this->clean['affiliates'].'", "'.$this->clean['activate_via_email'].'", "'.$this->clean['activate_via_sms'].'", "'.$this->clean['admin_verification'].'", "'.$this->clean['store'].'", "'.$this->clean['bulk_uploads'].'", '.$order_no.', "'.$this->clean['listing_pending'].'", "'.$this->clean['package_pending'].'", "'.$this->clean['default_credits'].'", "'.$this->clean['affiliates_cookie_availability'].'", "'.$this->clean['affiliates_percentage'].'", "'.$this->clean['affiliates_payment_cycle'].'");');

		$last_id=$db->insertId();

		foreach ($languages as $lang) {

			$lang_id = $lang['id'];
			$res_lang=$db->query("insert into ".TABLE_USER_GROUPS."_lang (`id`, `lang_id`, `name`, `description`) values ('$last_id', '$lang_id', '".$this->clean['name'][$lang_id]."', '".$this->clean['description'][$lang_id]."');");

		}

		// add affiliates periodic entry
		if($this->clean['affiliates'])
			$db->query("insert into ".TABLE_PERIODIC." set `type`='affiliates_payment', `val`='$last_id', `date` = null");
		
		do_action("add_user_group", array(&$last_id));
		
		$this->clearGroupsCache();
		return 1;
	}

	function edit($id) {

		global $db;
		global $lng;
		$this->clean=array();
		$this->check_form($id);
		if($this->getError()!='') return 0;

		global $languages;
		$array_lang = array('name', 'description');
		foreach ($array_lang as $field) {
			foreach ($languages as $lang) {
				$lang_id = $lang['id'];
				$f = $field."_".$lang_id;
				if(isset($_POST[$f])) $this->clean[$field][$lang_id] = escape($_POST[$f]); 
				else $this->clean[$field][$lang_id] = '';
			}
		}

		$this->clean['store'] = escape($_POST['store']);
		$this->clean['activate_via_email'] = checkbox_value('activate_via_email');
		$this->clean['activate_via_sms'] = checkbox_value('activate_via_sms');
		if(isset($_POST['default_credits']) && is_numeric($_POST['default_credits'])) $this->clean['default_credits'] = escape($_POST['default_credits']);
		else $this->clean['default_credits'] = 0;

		$checkboxes = array ('auto_register', 'post_ads', 'affiliates', 'bulk_uploads', 'listing_pending', 'package_pending', 'admin_verification');
		foreach ($checkboxes as $chk) {

			$this->clean[$chk] = checkbox_value($chk);

		}

		// fields with default 0 value
		$array_inputs_zero = array('affiliates_cookie_availability', 'affiliates_percentage', 'affiliates_payment_cycle');

		foreach ($array_inputs_zero as $field) {

			if(isset($_POST[$field])) $this->clean[$field]=$_POST[$field]; else $this->clean[$field]=0;

		}

		$old_affiliates_val = $db->fetchRow("select `affiliates` from ".TABLE_USER_GROUPS." where id='$id'");
		
		$res=$db->query('update '.TABLE_USER_GROUPS.' set `auto_register` = "'.$this->clean['auto_register'].'", `post_ads` = "'.$this->clean['post_ads'].'", `affiliates` = "'.$this->clean['affiliates'].'", `activate_via_email` = "'.$this->clean['activate_via_email'].'", `activate_via_sms` = "'.$this->clean['activate_via_sms'].'", `store` = "'.$this->clean['store'].'", `bulk_uploads` = "'.$this->clean['bulk_uploads'].'", `admin_verification` = "'.$this->clean['admin_verification'].'", `listing_pending` = "'.$this->clean['listing_pending'].'", `package_pending` = "'.$this->clean['package_pending'].'", `default_credits` = "'.$this->clean['default_credits'].'" , `affiliates_cookie_availability` = "'.$this->clean['affiliates_cookie_availability'].'", `affiliates_percentage` = "'.$this->clean['affiliates_percentage'].'", `affiliates_payment_cycle` = "'.$this->clean['affiliates_payment_cycle'].'" where id='.$id.';');

		foreach ($languages as $lang) {

			$lang_id = $lang['id'];
		
			$res_lang=$db->query('update '.TABLE_USER_GROUPS.'_lang set `name` = "'.$this->clean['name'][$lang_id].'", `description` = "'.$this->clean['description'][$lang_id].'" where id='.$id.' and `lang_id`="'.$lang_id.'";');

		}
		
		if($old_affiliates_val != $this->clean['affiliates']) {
			$db->query("update ".TABLE_USERS." set `affiliate`='{$this->clean['affiliates']}' where `group`='$id'");
			if(!$this->clean['affiliates']) $db->query("delete from ".TABLE_PERIODIC." where `type` like 'affiliates_payment' and `val`='$id'");
		}
		
		do_action("edit_user_group", array(&$id));

		$this->clearGroupsCache();
		return 1;

	}

	function orderExists($order) {

		global $db;
		$res=$db->query("select * from ".TABLE_USER_GROUPS." where order_no='$order'");
		if($db->numRows($res)) return 1;
		return 0;

	}

	function remakeOrder($id) {
	
		global $db;
		$order=$db->fetchRow("select `order_no` from ".TABLE_USER_GROUPS." where id='$id'");
		if(!$order) return;
		$res=$db->query("update ".TABLE_USER_GROUPS." set `order_no`=`order_no`-1 where `order_no`>='$order'");

		$this->clearGroupsCache();

	}

	function moveUp($id=0) {
		
		global $db;
		if(!$id) $id=$this->id;
		$order_no=$db->fetchRow("select `order_no` from ".TABLE_USER_GROUPS." where `id`='$id'");
		$goto=$order_no-1;
		$res1=$db->query("update ".TABLE_USER_GROUPS." set `order_no`='1000' where `id`='$id'");
		$res2=$db->query("update ".TABLE_USER_GROUPS." set `order_no`='$order_no' where `order_no`='$goto'");
		$res3=$db->query("update ".TABLE_USER_GROUPS." set `order_no`='$goto' where `id`='$id'");

		$this->clearGroupsCache();

		return 1;
	}

	function moveDown($id=0) {
		
		global $db;
		if(!$id) $id=$this->id;
		$order_no=$db->fetchRow("select `order_no` from ".TABLE_USER_GROUPS." where `id`='$id'");
		$goto=$order_no+1;
		$res1=$db->query("update ".TABLE_USER_GROUPS." set `order_no`='1000' where `id`='$id'");
		$res2=$db->query("update ".TABLE_USER_GROUPS." set `order_no`='$order_no' where `order_no`='$goto'");
		$res3=$db->query("update ".TABLE_USER_GROUPS." set `order_no`='$goto' where `id`='$id'");

		$this->clearGroupsCache();

		return 1;
	}

	function getOrder() {

		global $db;
		$order_no=$db->fetchRow('select `order_no` from '.TABLE_USER_GROUPS.' order by `order_no` desc limit 1');
		if($order_no) $order_no=$order_no+1;
		else $order_no=1;
		return $order_no;
	}

	function clearGroupsCache() {

		// clear cache
		$lc_cache = new cache();
		$lc_cache->clearCache("base_interface");
		$lc_cache->clearCache("base_groups");
		$lc_cache->clearCache("base_short_groups");
		$lc_cache->clearCache("base_autoregister_groups");

	}

}
?>
