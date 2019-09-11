<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class packages_config {

	var $error;
	var $tmp;

	public function __construct()
	{
	
		$this->error=''; $this->tmp='';

	}
	
	function Activate($id='') {

		global $config_demo;
		if($config_demo==1) return;

		global $db;
		if(!$id) $id=$this->id;
		$res=$db->query('update '.TABLE_PACKAGES.' set active=1 where id="'.$id.'"');
		$timestamp = date("Y-m-d H:i:s");
		$res=$db->query('update '.TABLE_ADS.' set active=1 where `user_approved`=1 and `package_id`="'.$id.'" and (`date_expires`>"'.$timestamp.'" or `date_expires` is null or `date_expires` like "0000-00-00 00:00:00")');

		$this->clearPackagesCache();

		return 1;
	}

	function Deactivate($id='') {

		global $config_demo;
		if($config_demo==1) return;

		global $db;
		if(!$id) $id=$this->id;
		$res=$db->query('update '.TABLE_PACKAGES.' set active=0 where id="'.$id.'"');
		$res=$db->query('update '.TABLE_ADS.' set active=0 where `package_id`="'.$id.'"');
		
		$this->clearPackagesCache();

		return 1;
	}


	function delete($id=0) {

		global $db;
		global $config_demo;
		if($config_demo==1) return;

		if(!$id) $id=$this->id;
		$this->remakeOrder($id);
		$res_del=$db->query('delete from '.TABLE_PACKAGES.' where `id`="'.$id.'"');
		$res_del_lang=$db->query('delete from '.TABLE_PACKAGES.'_lang where `id`="'.$id.'"');
		$listings=new listings();
		$listings->deletePackage($id);

		$this->clearPackagesCache();

	}

	function getPackageLang($id='') {

		global $db;
		if(!$id) $id=$this->id;

		$row=$db->fetchAssoc("select * from ".TABLE_PACKAGES." where id=".$id."");
		$array_lang=$db->fetchAssocList("select * from ".TABLE_PACKAGES."_lang where id=".$id."");

		foreach($array_lang as $pkg_lang) {

			$lang_id = $pkg_lang['lang_id'];
			foreach ($pkg_lang as $key=>$value) {
				if($key=='id' || $key=='lang_id') continue;
				$row[$key][$lang_id] = cleanStr($value);
			}
		}

		return $row;

	}

	function count() {
	
		global $db;
		$no=$db->fetchRow('select count(*) from '.TABLE_PACKAGES);
		return $no;

	}

	function package_exists($str, $lang_id, $id='') {

		global $db;
		if($id!='') $str_edit=" and id!=".$id; else $str_edit='';
		$res=$db->query("select * from ".TABLE_PACKAGES."_lang where `name` like '$str' and `lang_id` like '$lang_id'".$str_edit);
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
			if(!$_POST['name_'.$lang['id']]) { $this->addError($lng['packages']['errors']['name_missing'].'<br />'); break;
			} else { 
			if($id && $this->package_exists($_POST['name_'.$lang['id']], $lang['id'], $id)) { $this->addError($lng['packages']['errors']['name_exists'].'<br />'); break; }
			if(!$id && $this->package_exists($_POST['name_'.$lang['id']], $lang['id'])) { $this->addError($lng['packages']['errors']['name_exists'].'<br />'); break; }
			}

		}

		if( $_POST['type'] && ( $_POST['type']=='ad' || $_POST['type']=='sub') ) $type = $_POST['type']; else $type = 'ad';

		if( $_POST['amount'] && !is_numeric($_POST['amount'])) $this->addError($lng['packages']['errors']['invalid_amount'].'<br />');

		if($_POST['no_days'] && !is_numeric($_POST['no_days']))  $this->addError($lng['packages']['errors']['invalid_no_days'].'<br />');

		if($_POST['no_words'] && !is_numeric($_POST['no_words']))  $this->addError($lng['packages']['errors']['invalid_no_words'].'<br />');

//		if(!$_POST['no_pictures']) $this->addError($lng['packages']['errors']['no_pictures_missing'].'<br />');
//		else if(!is_numeric($_POST['no_pictures']))  $this->addError($lng['packages']['errors']['invalid_no_pictures'].'<br />');
// allow 0 images
		if($_POST['no_pictures'] && !is_numeric($_POST['no_pictures']))  $this->addError($lng['packages']['errors']['invalid_no_pictures'].'<br />');


		if($type=="sub" && isset($_POST['no_ads']) && !is_numeric($_POST['no_ads']))  $this->addError($lng['packages']['errors']['invalid_no_ads'].'<br />');

		if(isset($_POST['subscription_time']) && !is_numeric($_POST['subscription_time']))  $this->addError($lng['packages']['errors']['invalid_subscription_time'].'<br />');

		// no_ads for subscriptions cannot be 1
		if($_POST['type']=="sub" && $_POST['no_ads']==1) $this->addError($lng['packages']['errors']['subscriptions_more_than_one_ad'].'<br />');

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

		if(isset($_POST['choose_categ']) && $_POST['choose_categ']=="choose")
		{
			$categories_list='';
			$i=0;
			while (isset($_POST['categories'][$i]) && $category=$_POST['categories'][$i]){
				if($i) $categories_list.=',';
				$categories_list.=$category;
				$i++;
			}
			if($categories_list=='') $this->addError($lng['packages']['errors']['categories_required'].'<br />');
		}

		if($this->getError()!='')
		{
			if($id) $this->tmp['id']=$id;

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

			if(isset($_POST['type'])) $this->tmp['type']=$_POST['type']; else $this->tmp['type']=0;
			if(isset($_POST['amount'])) $this->tmp['amount']=$_POST['amount']; else $this->tmp['amount']=0;
			if(isset($_POST['no_days'])) $this->tmp['no_days']=$_POST['no_days']; else $this->tmp['no_days']=0;
			if(isset($_POST['no_words'])) $this->tmp['no_words']=$_POST['no_words']; else $this->tmp['no_words']=0;
			//if(isset($_POST['no_pictures'])) $this->tmp['no_pictures']=$_POST['no_pictures']; else $this->tmp['no_pictures']='';
			if(isset($_POST['no_pictures'])) $this->tmp['no_pictures']=$_POST['no_pictures']; else $this->tmp['no_pictures']=0;
			if(isset($_POST['photo_mandatory']) && $_POST['photo_mandatory']=='on') $this->tmp['photo_mandatory']=1; else $this->tmp['photo_mandatory']=0;
			
			if(isset($_POST['featured']) && $_POST['featured']=='on') $this->tmp['featured']=1; else $this->tmp['featured']=0;
			if(isset($_POST['highlited']) && $_POST['highlited']=='on') $this->tmp['highlited']=1; else $this->tmp['highlited']=0;
			if(isset($_POST['priority'])) $this->tmp['priority']=$_POST['priority']; else $this->tmp['priority']=0;
			if(isset($_POST['video']) && $_POST['video']=='on') $this->tmp['video']=1; else $this->tmp['video']=0;

			if(isset($_POST['urgent']) && $_POST['urgent']=='on') $this->tmp['urgent']=1; else $this->tmp['urgent']=0;
			if(isset($_POST['url']) && $_POST['url']=='on') $this->tmp['url']=1; else $this->tmp['url']=0;

			if(isset($_POST['no_ads'])) $this->tmp['no_ads']=$_POST['no_ads']; else $this->tmp['no_ads']=0;
			//if(isset($_POST['no_videos'])) $this->tmp['no_videos']=$_POST['no_videos']; else $this->tmp['no_videos']=1;
			if(isset($_POST['subscription_time'])) $this->tmp['subscription_time']=$_POST['subscription_time']; else $this->tmp['subscription_time']=0;

			if(isset($_POST['allow']) && is_numeric($_POST['allow'])) $this->tmp['allow']=$_POST['allow']; else $this->tmp['allow']=0;


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

			if(isset($_POST['choose_categ'])) $this->tmp['choose_categ']=$_POST['choose_categ'];
			if(isset($_POST['choose_categ']) && $_POST['choose_categ']=="choose")
			{
				$this->tmp['categories']='';
				$i=0;
				while (isset($_POST['categories'][$i])){
					$category=escape($_POST['categories'][$i]);
					if($i) $this->tmp['categories'].=',';
					$this->tmp['categories'].=$category;
					$i++;
				}
			} else $this->tmp['categories']='0';

		}

		return 1;
	}

	function add() {
	
		global $db;
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

		$this->clean['type'] = escape($_POST['type']);
		//$this->clean['no_pictures'] = escape($_POST['no_pictures']);
		if($_POST['no_pictures']) $this->clean['no_pictures'] = escape($_POST['no_pictures']); else $this->clean['no_pictures']=0;
		//if($_POST['no_videos']) $this->clean['no_videos'] = escape($_POST['no_videos']); else $this->clean['no_videos']=1;
		if($_POST['amount']) $this->clean['amount'] = escape($_POST['amount']); else $this->clean['amount']=0;
		if($_POST['no_days']) $this->clean['no_days'] = escape($_POST['no_days']); else $this->clean['no_days']=0;
		if($_POST['no_words']) $this->clean['no_words'] = escape($_POST['no_words']); else $this->clean['no_words']=0;

		if($this->clean['type']=="ad") $this->clean['no_ads']=1;
		else if(isset($_POST['no_ads'])) $this->clean['no_ads'] = escape($_POST['no_ads']); else $this->clean['no_ads']=0;
		if(isset($_POST['subscription_time'])) $this->clean['subscription_time'] = escape($_POST['subscription_time']); else $this->clean['subscription_time']=0;
		
		if(!$_POST['amount']) { $this->clean['no_ads']=1; $this->clean['subscription_time']=0; }
		
		$this->clean['photo_mandatory']=checkbox_value("photo_mandatory");
		
		global $ads_settings;
		if($ads_settings['enable_featured']) $this->clean['featured']=checkbox_value("featured"); else $this->clean['featured']=0;
		if($ads_settings['enable_highlited']) $this->clean['highlited']=checkbox_value("highlited"); else $this->clean['highlited']=0;
		if($ads_settings['enable_video']) $this->clean['video']=checkbox_value("video"); else $this->clean['video']=0;
		if($ads_settings['enable_priorities'] && $_POST['priority']) $this->clean['priority'] = escape($_POST['priority']); else $this->clean['priority']=0;
		if($ads_settings['enable_urgent']) $this->clean['urgent']=checkbox_value("urgent"); else $this->clean['urgent']=0;
		if($ads_settings['enable_url']) $this->clean['url']=checkbox_value("url"); else $this->clean['url']=0;

		if(isset($_POST['allow']) && is_numeric($_POST['allow'])) $this->clean['allow']=$_POST['allow']; else $this->clean['allow']=0;

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

		if(isset($_POST['choose_categ']) && $_POST['choose_categ']=="choose")
		{
			$this->clean['categories']='';
			$i=0;
			while (isset($_POST['categories'][$i]) ){
				$category=escape($_POST['categories'][$i]);
				if($i) $this->clean['categories'].=',';
				$this->clean['categories'].=$category;
				$i++;
			}
		} else $this->clean['categories']='0';


		$order_no=$this->getOrder();

		$res=$db->query('insert into '.TABLE_PACKAGES.' (`type`, `amount`, `no_words`, `no_days`, `no_pictures`, `photo_mandatory`, `no_ads`, `subscription_time`, `groups`, `categories`, `order_no`, `featured`, `highlited`, `priority`, `video`, `urgent`, `url`, `allow`) values ("'.$this->clean['type'].'", "'.$this->clean['amount'].'", "'.$this->clean['no_words'].'", "'.$this->clean['no_days'].'", "'.$this->clean['no_pictures'].'", "'.$this->clean['photo_mandatory'].'", "'.$this->clean['no_ads'].'", "'.$this->clean['subscription_time'].'", "'.$this->clean['groups'].'", "'.$this->clean['categories'].'", "'.$order_no.'", "'.$this->clean['featured'].'", "'.$this->clean['highlited'].'", "'.$this->clean['priority'].'", "'.$this->clean['video'].'", "'.$this->clean['urgent'].'", "'.$this->clean['url'].'", "'.$this->clean['allow'].'");');

		$id=$db->insertId();

		foreach ($languages as $lang) {

			$lang_id = $lang['id'];
			$res_lang=$db->query('insert into '.TABLE_PACKAGES.'_lang (`id`, `lang_id`, `name`, `description`) values ("'.$id.'", "'.$lang_id.'", "'.$this->clean['name'][$lang_id].'", "'.$this->clean['description'][$lang_id].'");');

		}

		$this->clearPackagesCache();

		return 1;

	}

	function edit($id) {
	
		global $db;
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

		$this->clean['type'] = escape($_POST['type']);
		$this->clean['no_pictures'] = escape($_POST['no_pictures']);
		//if($_POST['no_videos']) $this->clean['no_videos'] = escape($_POST['no_videos']); else $this->clean['no_videos']=1;
		$this->clean['photo_mandatory']=checkbox_value("photo_mandatory");
		if($_POST['amount']) $this->clean['amount'] = escape($_POST['amount']); else $this->clean['amount']=0;
		if($_POST['no_days']) $this->clean['no_days'] = escape($_POST['no_days']); else $this->clean['no_days']=0;
		if($_POST['no_words']) $this->clean['no_words'] = escape($_POST['no_words']); else $this->clean['no_words']=0;

		if($this->clean['type']=="ad") $this->clean['no_ads']=1;
		else if(isset($_POST['no_ads']) && $_POST['no_ads']) $this->clean['no_ads'] = escape($_POST['no_ads']); else $this->clean['no_ads']=0;

		if(isset($_POST['subscription_time']) && $_POST['subscription_time']) $this->clean['subscription_time'] = escape($_POST['subscription_time']); else $this->clean['subscription_time']=0;
		
		$this->clean['featured']=checkbox_value("featured");
		$this->clean['highlited']=checkbox_value("highlited");
		$this->clean['video']=checkbox_value("video");
		$this->clean['urgent']=checkbox_value("urgent");
		$this->clean['url']=checkbox_value("url");
		if($_POST['priority']) $this->clean['priority'] = escape($_POST['priority']); else $this->clean['priority']=0;

		if(isset($_POST['allow']) && is_numeric($_POST['allow'])) $this->clean['allow']=$_POST['allow']; else $this->clean['allow']=0;

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

		if(isset($_POST['choose_categ']) && $_POST['choose_categ']=="choose")
		{
			$this->clean['categories']='';
			$i=0;
			while (isset($_POST['categories'][$i])){
				$category=escape($_POST['categories'][$i]);
				if($i) $this->clean['categories'].=',';
				$this->clean['categories'].=$category;
				$i++;
			}
		} else $this->clean['categories']='0';

		$res=$db->query("update ".TABLE_PACKAGES." set `type`='".$this->clean['type']."', `amount`='".$this->clean['amount']."', `no_words`='".$this->clean['no_words']."', `no_days`='".$this->clean['no_days']."', `no_pictures`='".$this->clean['no_pictures']."', `photo_mandatory`='".$this->clean['photo_mandatory']."', `no_ads`='".$this->clean['no_ads']."', `subscription_time`='".$this->clean['subscription_time']."', `featured`='".$this->clean['featured']."', `highlited`='".$this->clean['highlited']."', `priority`='".$this->clean['priority']."', `video`='".$this->clean['video']."', `urgent`='".$this->clean['urgent']."', `url`='".$this->clean['url']."', `groups`='".$this->clean['groups']."', `categories`='".$this->clean['categories']."', `allow`='".$this->clean['allow']."' where id='$id';");

		foreach ($languages as $lang) {

			$lang_id = $lang['id'];

			$res_lang=$db->query("update ".TABLE_PACKAGES."_lang set `name`='".$this->clean['name'][$lang_id]."', `description`='".$this->clean['description'][$lang_id]."' where id='$id' and `lang_id` = '$lang_id';");
		
		}

		$this->clearPackagesCache();

		return 1;

	}

	function getOrder() {

		global $db;
		$res_order=$db->query('select `order_no` from '.TABLE_PACKAGES.' order by `order_no` desc limit 1');
		if($db->numRows($res_order)>0) $order_no=($db->fetchRow()+1);
		else $order_no=1;
		return $order_no;
	}

	function orderExists($order) {

		global $db;
		$res=$db->query("select * from ".TABLE_PACKAGES." where order_no='$order'");
		if($db->numRows($res)) return 1;
		return 0;

	}

	function remakeOrder($id) {
	
		global $db;
		$order=$db->fetchRow("select `order_no` from ".TABLE_PACKAGES." where id='$id'");
		if(!$order) return;
		$res=$db->query("update ".TABLE_PACKAGES." set `order_no`=`order_no`-1 where `order_no`>='$order'");

		$this->clearPackagesCache();

	}

	function moveUp($id) {
		
		global $db;
		if(!$id) $id=$this->id;
		$order_no=$db->fetchRow("select `order_no` from ".TABLE_PACKAGES." where `id`='$id'");
		$goto=$order_no-1;
		$res2=$db->query("update ".TABLE_PACKAGES." set `order_no`='$order_no' where `order_no`='$goto'");
		$res3=$db->query("update ".TABLE_PACKAGES." set `order_no`='$goto' where `id`='$id'");

		$this->clearPackagesCache();

		return 1;
	}

	function moveDown($id) {
		
		global $db;
		if(!$id) $id=$this->id;
		$order_no=$db->fetchRow("select `order_no` from ".TABLE_PACKAGES." where `id`='$id'");
		$goto=$order_no+1;
		$res2=$db->query("update ".TABLE_PACKAGES." set `order_no`='$order_no' where `order_no`='$goto'");
		$res3=$db->query("update ".TABLE_PACKAGES." set `order_no`='$goto' where `id`='$id'");

		$this->clearPackagesCache();

		return 1;
	}

	function clearPackagesCache() {

		// clear cache
		$lc_cache = new cache();
		$lc_cache->clearCache("base_short_plans");
		$lc_cache->clearCache("base_plans");

	}

}
?>
