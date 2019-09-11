<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class scheduled_imports {

	var $error;
	var $tmp;

	public function __construct()
	{
	
	}

	function delete($id=0) {

		global $db;
		global $config_demo;
		if($config_demo==1) return;

		$res_del=$db->query('delete from '.TABLE_SCHEDULED_IMPORTS.' where `id`="'.$id.'"');

	}

	function Enable($id) {

		global $db;
		global $config_demo;
		if($config_demo==1) return;

		$res=$db->query('update '.TABLE_SCHEDULED_IMPORTS.' set `active` = 1 where `id`="'.$id.'"');
		return 1;

	}

	function Disable($id) {

		global $db;
		global $config_demo;
		if($config_demo==1) return;

		$res=$db->query('update '.TABLE_SCHEDULED_IMPORTS.' set `active` = 0 where `id`="'.$id.'"');
		return 1;

	}

	function getScheduledImport($id) {

		global $db, $settings;
		$row=$db->fetchAssoc("select * from ".TABLE_SCHEDULED_IMPORTS." where id=".$id."");

		if($settings['enable_username'])
			$row['username'] = users::getUsername($row['user_id']);
		else 
			$row['username'] = users::getEmail($row['user_id']);
		return $row;

	}

	function getAll() {

		global $db, $settings;
		$array=$db->fetchAssocList("select * from ".TABLE_SCHEDULED_IMPORTS);

		$i = 0;
		foreach($array as $a) {
			if($settings['enable_username'])
				$array[$i]['username'] = users::getUsername($array[$i]['user_id']);
			else 
				$array[$i]['username'] = users::getEmail($array[$i]['user_id']);
			$i++;
		}
		return $array;

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

	function correctKey($id, $key) {

		global $db;
		$exists = $db->fetchRow("select `id` from ".TABLE_SCHEDULED_IMPORTS." where `id`='$id' and `key` like '$key' and `active`=1");
		return $exists;

	}

	function check_form ($id='') {

		global $db;
		global $lng;
		$this->error='';
		$this->tmp=array();
		global $languages;

		global $config_demo;
		if($config_demo==1) $this->addError($lng['general']['errors']['demo'].'<br />');

		if(!$_POST['name']) $this->addError($lng['ie']['name_missing'].'<br />');

		if($_POST['access_type']=='url' && !$_POST['url']) $this->addError($lng['ie']['url_missing'].'<br />');

		if($_POST['access_type']=='ftp' && (!$_POST['ftp_server'] || !$_POST['ftp_login'] || !$_POST['ftp_password'] || !$_POST['ftp_filename'])) $this->addError($lng['ie']['ftp_info_missing'].'<br />');

		if(!$_POST['user_id'] && !$_POST['username']) $this->addError($lng['ie']['user_missing'].'<br />');

		if($this->getError()!='')
		{
			if($id) $this->tmp['id']=$id;

			$array = array("name", "url", "user_id", "username", "type", "template", "category_id", "package_id", "access_type", "ftp_server", "ftp_login", "ftp_password", "ftp_filename");
			foreach($array as $a) {
				if(isset($_POST[$a])) $this->tmp[$a]=$_POST[$a]; else $this->tmp[$a]='';
			}

			$checkbox_fields_array = array("use_id_as_unique_field", "delete_inexisting", "only_download_inexisting", "active");
			foreach($checkbox_fields_array as $field)
				$this->tmp[$field] = checkbox_value($field);

		}

		return 1;
	}

	function add() {

		global $db;
		$this->clean=array();
		$this->check_form();
		if($this->getError()!='') return 0;

		$fields_array = array('name', 'url', 'type', 'template', 'category_id', 'package_id', "access_type", "ftp_server", "ftp_login", "ftp_password", "ftp_filename");
		$str="";
		foreach ($fields_array as $field) {
			if(isset($_POST[$field])) $this->clean[$field] = escape($_POST[$field]);
			else $this->clean[$field] = '';
			$str.=", `$field`='{$this->clean[$field]}'";
		}

		$checkbox_fields_array = array("use_id_as_unique_field", "delete_inexisting", "only_download_inexisting", "active");

		foreach($checkbox_fields_array as $field) {
			$this->clean[$field] = checkbox_value($field);
			$str.=", `$field`='{$this->clean[$field]}'";
		}

		if(isset($_POST['user_id']) && $_POST['user_id']) $this->clean['user_id'] = escape($_POST['user_id']);
		else { 
			global $settings;
			if($settings['enable_username'])
				$this->clean['user_id'] = users::getUserId($_POST['username']);
			else 
			$this->clean['user_id'] = users::getIdByEmail($_POST['username']);
		}

		$key=generate_random();

		$res=$db->query("insert into ".TABLE_SCHEDULED_IMPORTS." set `user_id`='{$this->clean['user_id']}' ".$str.", `key` = '$key'");

		return 1;

	}

	function edit($id) {
	
		global $db;
		$this->clean=array();
		$this->check_form($id);
		if($this->getError()!='') return 0;

		$fields_array = array('name', 'url', 'type', 'template', 'category_id', 'package_id', "access_type", "ftp_server", "ftp_login", "ftp_password", "ftp_filename");
		$str="";
		foreach ($fields_array as $field) {
			if(isset($_POST[$field])) $this->clean[$field] = escape($_POST[$field]);
			else $this->clean[$field] = '';
			$str.=", `$field`='{$this->clean[$field]}'";
		}

		$checkbox_fields_array = array("use_id_as_unique_field", "delete_inexisting", "only_download_inexisting", "active");

		foreach($checkbox_fields_array as $field) {
			$this->clean[$field] = checkbox_value($field);
			$str.=", `$field`='{$this->clean[$field]}'";
		}

		if(isset($_POST['user_id']) && $_POST['user_id']) $this->clean['user_id'] = escape($_POST['user_id']);
		else $this->clean['user_id'] = users::getUserId($_POST['username']);

		$res=$db->query("update ".TABLE_SCHEDULED_IMPORTS." set `user_id`='{$this->clean['user_id']}' ".$str." where id='$id'");

		return 1;

	}
	
	function autoImport($id) {

		global $db;
		global $lng;
		global $config_abs_path;

		$task = $this->getScheduledImport($id);

		if(!$task['user_id']) {
			// write to log !!!
			echo "No user id to import to!";
			exit(0);
		}

		$ie = new import_export();
		$ie->setScheduled(1);
		$ie->setUid($task['use_id_as_unique_field']);
		if($task['use_id_as_unique_field']) $ie->setNoDuplicates($task['only_download_inexisting']);

		$ie->setData('ad');
		$ie->setType($task['type']);
		if($task['template']) $ie->setTemplate($task['template']);// ??????? 

		$default['package_id'] = $task['package_id'];
		$default['category_id'] = $task['category_id'];
		$default['user_id'] = $task['user_id'];
		$default['date_added'] = date("Y-m-d H:i:s");

		$ie->setDefault($default);

		if($task['access_type']=="url") {

	// check if url !!!!!!
	if(!url_exists($task['url'])) { echo "Incorrect or inaccessible url!"; exit(0); }
			$path = explode("/", $task['url']);
			$file_name = $path[count($path)-1];
			if(!$file_name) $file_name = generate_random().".".$task['type'];
			$dest_file = $config_abs_path."/temp/".$file_name;

			// save file locally
			if(_isCurl()) {
 
				$fp = fopen($dest_file, 'w');
 // !!!!! check if cannot connect
				$ch = curl_init($task['url']);
				curl_setopt($ch, CURLOPT_FILE, $fp);
 
				$data = curl_exec($ch);
 
				curl_close($ch);
				fclose($fp);

			} else 
			if(!copy($task['url'], $dest_file)) 
			{

				$raw = file_get_contents($task['url']);
				file_put_contents($dest_file, $raw);

			}
		} // end if access_type = url
		else {
		// access type = ftp

			// set up a connection
			$conn_id = ftp_connect($task['ftp_server']) or die("Couldn't connect to ".$task['ftp_server']); 

			// !!!!!! if(!$conn_id) log_it... 

			// login with username and password
			if($result = ftp_login($conn_id, $task['ftp_login'], $task['ftp_password'])) {

				$path = explode("/", $task['ftp_filename']);
				$file_name = $path[count($path)-1];
				if(!$file_name) $file_name = generate_random().".".$task['type'];
				$dest_file = $config_abs_path."/temp/".$file_name;

				// try to download the file and save it locally
				if (ftp_get($conn_id, $dest_file, $task['ftp_filename'], FTP_BINARY)) {
    					//echo "Successfully written to $local_file\n";

				} else {
    					// !!!!!1 log 
				}

			} else {
				// log it !!!!!!!
				echo "Could not login with the provided username and password!";
			}

			// close the connection
			ftp_close($conn_id);
		}

		if($task['type']=="csv") $result = $ie->CSVImport($dest_file);
		else if($task['type']=="xml") $result = $ie->XMLImport($dest_file);

		$this->setError($ie->getError());
		echo $this->error;// !!!!!!!

		if($task['use_id_as_unique_field'] && $task['delete_inexisting']) { 
			$imported = $ie->getAddedElements();
			if(count($imported)) {
				$imported_str = "("; 
				$sign="";
				foreach($imported as $im) { $imported_str.=$sign.$im; $sign=","; }
				$imported_str .= ")";

				$this->deleteAllInexisting($task['user_id'], $imported_str);
			}
		}

		return $result;

	}

	function deleteAllInexisting($user_id, $imported_array) {

		if(!$imported_array) return;
		global $db, $config_abs_path;
		require_once $config_abs_path."/classes/pictures.php";
		$listing = new listings();
		$larr = $db->fetchRowList("select `id` from ".TABLE_ADS." where `user_id`='$user_id' and `unique_id` is not NULL and `id` not in $imported_array");

		foreach ($larr as $l) $listing->delete($l);

		return;

	}


}

?>