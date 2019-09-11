<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class credits {

	public function __construct()
	{
	
	}

	function deletePackage($id) {

		global $db;
		$db->query("delete from ".TABLE_CREDITS_PACKAGES." where id='$id'");
		$db->query("delete from ".TABLE_CREDITS_PACKAGES."_lang where id='$id'");
		return 1;

	}

	function addForUser($user_id, $no_credits) {

		if(!$no_credits) return;
		global $db;
		$db->query("update ".TABLE_USERS." set `no_credits` = `no_credits`+$no_credits where id = $user_id");

		return 1; 

	}

	static function creditsForUser($user_id) {

		if(!$user_id) return 0;

		global $db;
		$credits = $db->fetchRow("select `no_credits` from ".TABLE_USERS." where `id`='$user_id'");
		if(!$credits) return 0;

		return $credits;

	}

	static function userHasCredits($user_id, $price) {

		if(!$user_id) return 0;

		global $db;
		global $credits_settings;

		$no_credits = ceil($price/$credits_settings['unit']);

		$has_credits = $db->fetchRow("select `no_credits` from ".TABLE_USERS." where `id`='$user_id'");
		if($has_credits<$no_credits) return 0;

		return $has_credits;

	}

	function getCreditPackage($id) {

		global $db;
		global $crt_lang;

		if(!$id) return;
		$result=$db->fetchAssoc("select * from ".TABLE_CREDITS_PACKAGES." LEFT JOIN ".TABLE_CREDITS_PACKAGES."_lang on ".TABLE_CREDITS_PACKAGES.".`id` = ".TABLE_CREDITS_PACKAGES."_lang.`id` where ".TABLE_CREDITS_PACKAGES.".id=$id and `lang_id`='$crt_lang'");

		$array_fields = array("name");
		foreach($array_fields as $key) if($result[$key]) $result[$key] = cleanStr($result[$key]);

		if($result['groups']) {

			$result['groups_array']=explode(",",$result['groups']);
			for($a=0; $a<count($result['groups_array']);$a++) {
				$result['groups_array'][$a]=trim($result['groups_array'][$a]);
			}
		}
		$result['price_curr'] = add_currency(format_price($result['price']));

		return $result;

	}

	function getNoCredits($id) {
		
		global $db;
		$no=$db->fetchRow('select `no_credits` from '.TABLE_CREDITS_PACKAGES.' where `id`="'.$id.'"');
		return $no;

	}

	function getPrice($id) {
		
		global $db;
		$amount=$db->fetchRow('select `price` from '.TABLE_CREDITS_PACKAGES.' where `id`="'.$id.'"');
		return $amount;

	}

	static function getPackageName($id) {
		
		global $db;
		global $crt_lang;
		$name=$db->fetchRow('select `name` from '.TABLE_CREDITS_PACKAGES.'_lang where `id`="'.$id.'" and `lang_id`="'.$crt_lang.'"');
		return $name;

	}

	function getCreditPackages($group='') {

		global $db;
		global $lng;
		global $crt_lang;
		$array=$db->fetchAssocList("select * from ".TABLE_CREDITS_PACKAGES." LEFT JOIN ".TABLE_CREDITS_PACKAGES."_lang on ".TABLE_CREDITS_PACKAGES.".`id` = ".TABLE_CREDITS_PACKAGES."_lang.`id` where `lang_id`='$crt_lang' order by `order_no`");

		$i=0;
		foreach($array as $result) {
			$arr_gr=explode(",",$result['groups']);
			if(!$group || $result['groups']==0 || in_array($group, $arr_gr)) {

			$array_pkg[$i]=$result;
			
			$array_pkg[$i]['name'] = cleanStr($array_pkg[$i]['name']);
			
			$array_pkg[$i]['price_curr']=add_currency(format_price($array_pkg[$i]['price']));

			$i++;

			} //end if group
		}
		return $array_pkg;

	}

	function getAll($group='') {

		global $db;
		global $lng;
		global $crt_lang;
		$array=$db->fetchAssocList("select * from ".TABLE_CREDITS_PACKAGES." LEFT JOIN ".TABLE_CREDITS_PACKAGES."_lang on ".TABLE_CREDITS_PACKAGES.".`id` = ".TABLE_CREDITS_PACKAGES."_lang.`id` where `lang_id`='$crt_lang' order by `order_no`");

		$i=0;
		$gr = new groups();
		$array_pkg = array();
		foreach($array as $result) {
			$arr_gr=explode(",",$result['groups']);
			if(!$group || $result['groups']==0 || in_array($group, $arr_gr)) {

			$array_pkg[$i]=$result;
			
			$array_pkg[$i]['name'] = cleanStr($array_pkg[$i]['name']);
			
			$array_pkg[$i]['price_curr']=add_currency(format_price($array_pkg[$i]['price']));

			if($result['groups']==0) $array_pkg[$i]['groups_formatted'] = $lng['general']['all'];
			else $array_pkg[$i]['groups_formatted'] = '';

			$array_pkg[$i]['groups_array']=explode(",",$result['groups']);
			for($a=0; $a<count($array_pkg[$i]['groups_array']);$a++) {
				$array_pkg[$i]['groups_array'][$a]=trim($array_pkg[$i]['groups_array'][$a]);
				if($array_pkg[$i]['groups_array'][$a]) $array_pkg[$i]['groups_formatted'].=$gr->getName($array_pkg[$i]['groups_array'][$a]).'<br>';
			}

			$array_pkg[$i]['last'] = 0;	
			$i++;

			} //end if group
		}
		if($i) $array_pkg[$i-1]['last'] = 1;

		return $array_pkg;

	}

	function ActivatePending($id, $pkg_id, $user_id) {

		global $db, $lng, $settings, $config_abs_path;

		require_once $config_abs_path."/classes/payment_processors.php";

		$no_credits = $this->getNoCredits($pkg_id);
		$this->addForUser($user_id, $no_credits);

		$db->query("update ".TABLE_ACTIONS." set pending=0 where id = $id");

		$processor_code = $db->fetchRow("select ".TABLE_PAYMENT_ACTIONS.".processor from ".TABLE_ACTIONS." left join ".TABLE_PAYMENT_ACTIONS." on ".TABLE_ACTIONS.".invoice=".TABLE_PAYMENT_ACTIONS.".id where ".TABLE_ACTIONS.".id = $id");

		global $config_abs_path;
		require_once $config_abs_path."/classes/mails.php";
		require_once $config_abs_path."/classes/mail_templates.php";

		// get user details
		$user = new users();
		$user_details = $user->getContactData($user_id);
		$username = cleanStr($user_details['username']);
		$user_email = cleanStr($user_details['email']);
		$user_contact = cleanStr($user_details[$settings['contact_name_field']]);
		//if(!$user_contact) $user_contact=$username;

		// get plan details
		$credits_plan = $this->getCreditPackage($pkg_id);

		// send email
		$mail2send=new mails();
		$mail2send->init($user_email, $user_contact);

		$array_subject = array();

		$array_message = array("username"=>$username, "contact_name"=>$user_contact, "processor"=>payment_processors::getName($processor_code), "credits_plan"=>$credits_plan, "status"=>$lng['general']['active']);

		$mail2send->composeAndSend("buy_credits_status", $array_message, $array_subject);

	}

	function getPackageLang($id='') {

		global $db;
		if(!$id) $id=$this->id;

		$row=$db->fetchAssoc("select * from ".TABLE_CREDITS_PACKAGES." where id=".$id."");
		$array_lang=$db->fetchAssocList("select * from ".TABLE_CREDITS_PACKAGES."_lang where id=".$id."");

		foreach($array_lang as $pkg_lang) {

			$lang_id = $pkg_lang['lang_id'];
			foreach ($pkg_lang as $key=>$value) {
				if($key=='id' || $key=='lang_id') continue;
				$row[$key][$lang_id] = cleanStr($value);
			}
		}

		return $row;

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

		global $db;
		global $lng;
		$this->error='';
		$this->tmp=array();
		global $languages;

		global $config_demo;
		if($config_demo==1) $this->addError($lng['general']['errors']['demo'].'<br />');

		foreach ($languages as $lang) {
			if(!$_POST['name_'.$lang['id']]) { $this->addError($lng['credits']['errors']['pkg_name_missing'].'<br />'); break;
			}
		}

		if( $_POST['price'] && !is_numeric($_POST['price'])) $this->addError($lng['credits']['errors']['invalid_price'].'<br />');

		if($_POST['no_credits'] && !is_numeric($_POST['no_credits']))  $this->addError($lng['credits']['errors']['invalid_no_credits'].'<br />');

		if(isset($_POST['choose_groups']) && $_POST['choose_groups']=="choose")
		{
			$groups_list='';
			$i=0;
			while (isset($_POST['groups'][$i]) && $group=$_POST['groups'][$i]){
				if($i) $groups_list.=',';
				$groups_list.=$group;
				$i++;
			}
			if($groups_list=='') $this->addError($lng['custom_fields']['errors']['groups_required'].'<br />');
		}

		if($this->getError()!='')
		{
			if($id) $this->tmp['id']=$id;

			// lang fields with default null value
			$array_inputs_null_lang = array('name');
			foreach ($array_inputs_null_lang as $field) {
				foreach ($languages as $lang) {

					$lang_id = $lang['id'];
					$f = $field."_".$lang_id;
					if(isset($_POST[$f])) 
						$this->tmp[$field][$lang_id]=cleanStr($_POST[$f]); 
					else $this->tmp[$field][$lang_id]='';
				}
			}

			if(isset($_POST['price'])) $this->tmp['price']=$_POST['price']; else $this->tmp['price']=0;
			if(isset($_POST['no_credits'])) $this->tmp['no_credits']=$_POST['no_credits']; else $this->tmp['no_credits']=0;

			if(isset($_POST['choose_group'])) $this->tmp['choose_group']=$_POST['choose_group'];
			if(isset($_POST['choose_group']) && $_POST['choose_group']=="choose")
			{
				$this->tmp['groups']='';
				$i=0;
				while (isset($_POST['groups'][$i])){
					$group=escape($_POST['groups'][$i]);
					if($i) $this->tmp['groups'].=',';
					$this->tmp['groups'].=$group;
					$i++;
				}
			} else $this->tmp['groups']='0';

		}

		return 1;
	}

	function addPackage() {
	
		global $db;
		$this->clean=array();
		$this->check_form();
		if($this->getError()!='') return 0;
		global $languages;
		$array_lang = array('name');
		foreach ($array_lang as $field) {
			foreach ($languages as $lang) {
				$lang_id = $lang['id'];
				$f = $field."_".$lang_id;
				if(isset($_POST[$f])) $this->clean[$field][$lang_id] = escape($_POST[$f]); 
				else $this->clean[$field][$lang_id] = '';
			}
		}

		if($_POST['no_credits']) $this->clean['no_credits'] = escape($_POST['no_credits']); else $this->clean['no_credits']=0;
		if($_POST['price']) $this->clean['price'] = escape($_POST['price']); else $this->clean['price']=0;

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
		} else $this->clean['groups']='0';

		$order_no=$this->getOrder();

		$res=$db->query('insert into '.TABLE_CREDITS_PACKAGES.' (`price`, `no_credits`, `groups`, `order_no`) values ("'.$this->clean['price'].'", "'.$this->clean['no_credits'].'", "'.$this->clean['groups'].'", "'.$order_no.'");');

		$id=$db->insertId();

		foreach ($languages as $lang) {

			$lang_id = $lang['id'];
			$res_lang=$db->query('insert into '.TABLE_CREDITS_PACKAGES.'_lang (`id`, `lang_id`, `name`) values ("'.$id.'", "'.$lang_id.'", "'.$this->clean['name'][$lang_id].'");');

		}

		return 1;

	}

	function editPackage($id) {
	
		global $db;
		$this->clean=array();
		$this->check_form($id);
		if($this->getError()!='') return 0;

		global $languages;
		$array_lang = array('name');
		foreach ($array_lang as $field) {
			foreach ($languages as $lang) {
				$lang_id = $lang['id'];
				$f = $field."_".$lang_id;
				if(isset($_POST[$f])) $this->clean[$field][$lang_id] = escape($_POST[$f]); 
				else $this->clean[$field][$lang_id] = '';
			}
		}

		$this->clean['no_credits'] = escape($_POST['no_credits']);
		if($_POST['price']) $this->clean['price'] = escape($_POST['price']); else $this->clean['price']=0;

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
		} else $this->clean['groups']='0';

		$res=$db->query("update ".TABLE_CREDITS_PACKAGES." set `price`='".$this->clean['price']."', `no_credits`='".$this->clean['no_credits']."', `groups`='".$this->clean['groups']."' where id='$id';");

		foreach ($languages as $lang) {

			$lang_id = $lang['id'];

			$res_lang=$db->query("update ".TABLE_CREDITS_PACKAGES."_lang set `name`='".$this->clean['name'][$lang_id]."' where id='$id' and `lang_id` = '$lang_id';");
		
		}

		return 1;

	}

	function getOrder() {

		global $db;
		$res_order=$db->query('select `order_no` from '.TABLE_CREDITS_PACKAGES.' order by `order_no` desc limit 1');
		if($db->numRows($res_order)>0) $order_no=($db->fetchRow()+1);
		else $order_no=1;
		return $order_no;
	}

	function orderExists($order) {

		global $db;
		$res=$db->query("select * from ".TABLE_CREDITS_PACKAGES." where order_no='$order'");
		if($db->numRows($res)) return 1;
		return 0;

	}

	function remakeOrder($id) {
	
		global $db;
		$order=$db->fetchRow("select `order_no` from ".TABLE_CREDITS_PACKAGES." where id='$id'");
		if(!$order) return;
		$res=$db->query("update ".TABLE_CREDITS_PACKAGES." set `order_no`=`order_no`-1 where `order_no`>='$order'");
	}

	function moveUp($id) {
		
		global $db;
		if(!$id) $id=$this->id;
		$order_no=$db->fetchRow("select `order_no` from ".TABLE_CREDITS_PACKAGES." where `id`='$id'");
		$goto=$order_no-1;
		$res2=$db->query("update ".TABLE_CREDITS_PACKAGES." set `order_no`='$order_no' where `order_no`='$goto'");
		$res3=$db->query("update ".TABLE_CREDITS_PACKAGES." set `order_no`='$goto' where `id`='$id'");

		return 1;
	}

	function moveDown($id) {
		
		global $db;
		if(!$id) $id=$this->id;
		$order_no=$db->fetchRow("select `order_no` from ".TABLE_CREDITS_PACKAGES." where `id`='$id'");
		$goto=$order_no+1;
		$res2=$db->query("update ".TABLE_CREDITS_PACKAGES." set `order_no`='$order_no' where `order_no`='$goto'");
		$res3=$db->query("update ".TABLE_CREDITS_PACKAGES." set `order_no`='$goto' where `id`='$id'");

		return 1;
	}

	function getSettings() {

		global $db;
		$result = $db->fetchAssoc("select * from ".TABLE_CREDITS_SETTINGS);
		$result['unit_curr'] = add_currency(format_price($result['unit']));
		$result['groups_array'] = explode(",", $result['groups']);
		
		return $result;

	}

	static function getPendingCredits($user_id){

		global $db;
		$pkg_array = $db->fetchRowList("select `object_id` from ".TABLE_ACTIONS." where `type`='new_creditspkg' and user_id='$user_id' and `pending`=1");
		$cr = new credits();
		$credits=0;
		foreach($pkg_array as $pkg) {
			$no = $cr->getNoCredits($pkg);
			$credits+=$no;
		}

		return $credits;
	}

	static function creditsAllowed($credits_settings) {
		
		global $usr_sett;
		$group = $usr_sett['group'];
		if($credits_settings['groups']!=0) {
			$groups_array = explode(",", $credits_settings['groups']);
			if(!in_array($group, $groups_array) ) return 0;
		}
		return 1;
		
	}


}
?>