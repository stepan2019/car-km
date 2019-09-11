<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class auth {

	var $expire = 3600;
	var $username = null;
	var $passhash = null;
	var $error;
	var $remember;
	var $post_index;
	var $session_index;
	var $admin_post_index;
	var $admin_session_index;
	var $login_error;

	public function __construct()
	{
	
		$this->remember = 0;
		global $settings;
		if($settings['enable_username']) {
			$this->post_index = array('auth_name' => 'username' , 'pass' => 'password');
			$this->admin_post_index = array('auth_name' => 'username' , 'pass' => 'password');
		} else {
			$this->post_index = array('auth_name' => 'email' , 'pass' => 'password');
			$this->admin_post_index = array('auth_name' => 'email' , 'pass' => 'password');
		}

		$this->session_index = array('auth_name' => 'oxcl_auth_name' , 'pass' => 'oxcl_pass', 'moderator' => 'oxcl_moderator', 'affiliate' => 'oxcl_affiliate');
		$this->admin_session_index = array('auth_name' => 'oxcl_super_user' , 'pass' => 'oxcl_super_pass');

	}

	function haslogin() {

		$this->auth_name = escape($_POST[$this->post_index['auth_name']]);
		if($_POST[$this->post_index['pass']]) $this->passhash = users::encode(@$_POST[$this->post_index['pass']]);
		$this->remember = checkbox_value("remember");
		return $this->checklogin($this->auth_name , $this->passhash);
	}

	function checklogin($aname=null,$passhash=null) {

		if ($aname === null) $aname = $this->auth_name;
		if ($passhash === null) $passhash = $this->passhash;
		global $config_abs_path;
		require_once $config_abs_path."/classes/users.php";
		$usr=new users();
		$valid = $usr->validAccount($aname,$passhash);
		if(!$valid) $this->setLoginError($usr->getLoginError());
		return $valid;
	}

	function checkIdentity($aname=null, $identity=null) {

		if ($aname === null) $aname = $this->auth_name;
		global $config_abs_path;
		require_once $config_abs_path."/classes/users.php";
		$usr=new users();
		return $usr->checkIdentity($aname,$identity);
	}

	function savelogin($ip, $uid=null, $provider=null) {

		global $db;
		$_SESSION[$this->session_index['auth_name']] = $this->auth_name;
		$_SESSION[$this->session_index['pass']] = $this->passhash;
		$_SESSION[$this->session_index['moderator']] = users::isModerator($this->auth_name);
		$_SESSION[$this->session_index['affiliate']] = users::isAffiliate($this->auth_name);

		if($uid) $_SESSION[$this->session_index['auth_name']] = $uid;

		if($provider) { 
		    $this->auth_name = $uid;
		    $_SESSION[$this->session_index['auth_name']] = $uid;
		    $_SESSION[$this->session_index['pass']] = users::encode($provider);
		    $_SESSION['provider'] = $provider;
		} else {
		    $_SESSION['provider'] = null;
		    $_SESSION['identity'] = null;
		}

		$timestamp = date("Y-m-d H:i:s");
		$res=$db->query("insert into ".TABLE_LOGIN_HISTORY." set auth_name='$this->auth_name', date_login = '$timestamp', ip = '$ip', succeeded = 1");
		// log out admin for safety
		$this->admin_clearlogin();

		// remember login
		$this->rememberLoginDetails();	

	}

	function saveIdentityLogin ($ip, $aname, $auth_provider, $identity) {

		global $db, $settings;

		// get username for identity and auth provider
		// username can be changed from account name
		$arr = users::getUserPassWithIdentity($identity, $auth_provider);
		
		if($settings['enable_username'])
			$this->auth_name = $arr['username'];
		else $this->auth_name = $arr['email'];
		$_SESSION[$this->session_index['auth_name']] = $this->auth_name;
		
		$_SESSION[$this->session_index['pass']] = $arr['password'];
		$_SESSION[$this->session_index['moderator']] = users::isModerator($this->auth_name);
		$_SESSION[$this->session_index['affiliate']] = users::isAffiliate($this->auth_name);

		$_SESSION['provider'] = $auth_provider;
		$_SESSION['identity'] = $identity;

		$timestamp = date("Y-m-d H:i:s");
		$res=$db->query("insert into ".TABLE_LOGIN_HISTORY." set auth_name='$this->auth_name', date_login = '$timestamp', ip = '$ip', succeeded = 1");
		// log out admin for safety
		$this->admin_clearlogin();

	}

	function rememberLoginDetails() {

		if($this->remember) { 

			global $main_domain;

			$expire = time() + 60*60*24*365;

			setcookie("remember", 1, $expire, "/", ".".$main_domain);
			setcookie($this->session_index['auth_name'], $this->auth_name, $expire, "/", ".".$main_domain);
			setcookie($this->session_index['pass'], $this->passhash, $expire, "/", ".".$main_domain);
			setcookie($this->session_index['moderator'], $_SESSION[$this->session_index['moderator']], $expire, "/", ".".$main_domain);
			setcookie($this->session_index['affiliate'], $_SESSION[$this->session_index['affiliate']], $expire, "/", ".".$main_domain);

		}

	}

	function saveFailedLogin($ip) {
	
		global $db;

		$timestamp = date("Y-m-d H:i:s");

		$res=$db->query("insert into ".TABLE_LOGIN_HISTORY." set auth_name = '$this->auth_name', date_login = '$timestamp', `ip` = '$ip', succeeded = 0");
		$login_id = $db->insertId();

		$security_settings  = common::getCachedObject("base_security");
		if(!$security_settings['block_admin_attempts'] && !$security_settings['block_user_attempts']) return;

		// check if admin or regular user and log failed attempts
		global $settings;
		$admin_attempt = 0;
		if( $this->auth_name == $settings["admin_username"] || $this->auth_name == $settings["admin_email"]) $admin_attempt = 1;

		$ret = 0;
		
		// if admin
		if( $admin_attempt ) {
		
		  if($security_settings['block_admin_attempts']) {
		  
		      $this->addFailedAttempt(1, $ip);
		      $ret = $this->checkFailedAttempts($security_settings, 1, $ip, $login_id);
		  
		  }
		
		} // end admin attempt
		else { // user attempt

		  if($security_settings['block_user_attempts']) {
		  
		      $this->addFailedAttempt(0, $ip);
		      $ret = $this->checkFailedAttempts($security_settings, 0, $ip, $login_id);
		  
		  }
		
		} // end user attempt
		
		return $ret;
		
	}


	function autologin($user_id) {

		$user = users::getUserInfo($user_id);
		global $settings;
		if($settings['enable_username'])
			$_SESSION[$this->session_index['auth_name']] = $user['username'];
		else 
			$_SESSION[$this->session_index['auth_name']] = $user['email'];
		$_SESSION[$this->session_index['pass']] = $user['password'];
		$_SESSION[$this->session_index['moderator']] = $user['moderator'];
		$_SESSION[$this->session_index['affiliate']] = $user['affiliate'];

		if($user['auth_provider'] && $user['identity']) { 

		    $_SESSION['provider'] = $user['auth_provider'];
		    $_SESSION['identity'] = $user['identity'];

		} else {
		    $_SESSION['provider'] = null;
		    $_SESSION['identity'] = null;
		}
	}

	function expire($time)
	{
		$this->expire = $time;
		session_cache_limiter('private');
		session_cache_expire($time / 60);
	}
	
	
	function clearlogin() {

		if(!empty($_SESSION)) {
		unset($_SESSION[$this->session_index['auth_name']]);
		unset($_SESSION[$this->session_index['pass']]);
		unset($_SESSION[$this->session_index['moderator']]);
		unset($_SESSION[$this->session_index['affiliate']]);
		unset($_COOKIE[$this->session_index['auth_name']]);
		unset($_COOKIE[$this->session_index['pass']]);
		unset($_COOKIE[$this->session_index['moderator']]);
		unset($_COOKIE[$this->session_index['affiliate']]);
		unset($_COOKIE['remember']);
		}
		global $main_domain;
		setcookie ($this->session_index['auth_name'], "", time() - 3600, "/", ".".$main_domain);
		setcookie ($this->session_index['pass'], "", time() - 3600, "/", ".".$main_domain);
		setcookie ('remember', "", time() - 3600, "/", ".".$main_domain);

	}

	function _stripslashes($text) {
		if (_get_magic_quotes_gpc()) $text = stripslashes($text);
		return $text;
	}

	function loggedIn() {

		if(!isset($_SESSION[$this->session_index['auth_name']]) || !isset($_SESSION[$this->session_index['pass']])) return 0;
		if(!$this->checklogin($_SESSION[$this->session_index['auth_name']], $_SESSION[$this->session_index['pass']])) return 0;
		return $_SESSION[$this->session_index['auth_name']];

	}	

	function modLoggedIn() {

		$crt_usr = $this->loggedIn();
		if(!$crt_usr) return 0;
		if(users::isModerator($crt_usr)) return $crt_usr;
		return 0;

	}

	function crtUser() {

		return $_SESSION[$this->session_index['auth_name']];

	}	

	function crtUserId() {

		global $config_abs_path, $settings;
		require_once $config_abs_path."/classes/users.php";
		if(!isset($_SESSION[$this->session_index['auth_name']])) return 0;
		$aname=$_SESSION[$this->session_index['auth_name']];
		$identity=$_SESSION['identity'];
		$usr=new users();

		if($settings['enable_username'])
			$user_id=users::getUserId($aname, $identity);
		else 
			$user_id=users::getIdByEmail($aname, $identity);
		return $user_id;

	}	

	function admin_haslogin() {

		global $config_abs_path;
		require_once $config_abs_path."/classes/settings.php";
		$this->auth_name = escape($_POST[$this->admin_post_index['auth_name']]);
		$this->passhash = settings::encode(@$_POST[$this->admin_post_index['pass']]);

		return $this->admin_checklogin($this->auth_name , $this->passhash);
	}

	function admin_checklogin($aname=null,$passhash=null) {

		if ($aname === null) $aname = $this->auth_name;
		if ($passhash === null) $passhash = $this->passhash;
		if(!$aname || !$passhash) return 0;

		global $settings;
		$admin_password=$settings["admin_password"];
		if( ($settings["admin_username"]==$aname || $settings["admin_email"]==$aname) && $admin_password==$passhash) return 1;
		return 0;
	}

	function admin_savelogin($ip) {
		global $db;
		$_SESSION[$this->admin_session_index['auth_name']] = $this->auth_name;
		$_SESSION[$this->admin_session_index['pass']] = $this->passhash;
		$_SESSION['moderator'] = null;
		$_SESSION['affiliate'] = null;
		$_SESSION['provider'] = null;
		$_SESSION['identity'] = null;

		$timestamp = date("Y-m-d H:i:s");
		$res=$db->query("insert into ".TABLE_LOGIN_HISTORY." set auth_name = '$this->auth_name', date_login = '$timestamp', `ip` = '$ip', succeeded = 1");
		// log out user for safety
		$this->clearlogin();
	}

	function adminLoggedIn() {

		if(!isset($_SESSION[$this->admin_session_index['auth_name']]) || !isset($_SESSION[$this->admin_session_index['pass']])) return 0;
		if(!$this->admin_checklogin($_SESSION[$this->admin_session_index['auth_name']], $_SESSION[$this->admin_session_index['pass']])) return 0;
		return 1;

	}	

	function admin_clearlogin() {

		if(isset($_SESSION[$this->admin_session_index['auth_name']])) unset($_SESSION[$this->admin_session_index['auth_name']]);
		if(isset($_SESSION[$this->admin_session_index['pass']])) unset($_SESSION[$this->admin_session_index['pass']]);

	}
	
	function getLastLogin($auth_name){

		global $db;
		global $appearance_settings;
		$date_format=$appearance_settings["date_format_long"];
		$arr=array();
		$arr=$db->fetchAssoc("select date_format(date_login, '$date_format') as date_login_nice, ip from ".TABLE_LOGIN_HISTORY." where auth_name like '".escape($auth_name)."' and succeeded = 1 order by date_login desc limit 1");
		return $arr;

	}

	function getLastAdminLogin(){

		global $db;
		global $appearance_settings, $settings;

		$date_format=$appearance_settings["date_format_long"];
		$arr=array();
		$arr=$db->fetchAssoc("select date_format(date_login, '$date_format') as date_login_nice, ip from ".TABLE_LOGIN_HISTORY." where `auth_name` like '".escape($settings["admin_username"])."' or `auth_name` like '".escape($settings["admin_email"])."' and succeeded = 1 order by date_login desc limit 1");
		return $arr;

	}

	function getLoginBefore($auth_name){

		global $db;
		global $appearance_settings;
		$date_format=$appearance_settings["date_format_long"];
		$arr=array();
		$arr=$db->fetchAssoc("select date_format(date_login, '$date_format') as date_login_nice, ip from ".TABLE_LOGIN_HISTORY." where auth_name like '$auth_name' and succeeded = 1 order by date_login desc limit 1,1 ");
		return $arr;

	}

	function authCount($aname) {
	
		global $db, $settings;
		if($aname == $settings['admin_username'] || $aname==$settings['admin_email'])
			$count=$db->fetchRow("select count(*) from ".TABLE_LOGIN_HISTORY." where auth_name like '{$settings['admin_username']}' or auth_name like '{$settings['admin_email']}'");
		else
			$count=$db->fetchRow("select count(*) from ".TABLE_LOGIN_HISTORY." where auth_name like '$aname'");
		return $count;

	}

	function getNoAuthPages($aname, $no_per_page) {
	
		$total=$this->authCount($aname);
		if($total==0) return 1;
		return ceil($total/$no_per_page);

	}

	function getLoginHistory($aname,$page,$no_per_page) {

		$start=($page-1)*$no_per_page;
		global $appearance_settings, $settings;
		global $db;
		$date_format=$appearance_settings["date_format_long"];
		
		if($aname == $settings['admin_username'] || $aname==$settings['admin_email'])
			$arr=$db->fetchAssocList("select *, date_format(date_login, '$date_format') as date_login_nice from ".TABLE_LOGIN_HISTORY." where auth_name like '{$settings['admin_username']}' or auth_name like '{$settings['admin_email']}' order by date_login desc limit $start, $no_per_page");
		else
			$arr=$db->fetchAssocList("select *, date_format(date_login, '$date_format') as date_login_nice from ".TABLE_LOGIN_HISTORY." where auth_name like '$aname' order by date_login desc limit $start, $no_per_page");
			
		return $arr;

	}

	static function deleteLoginHistory($aname) {

		global $db;
		$db->query("delete from ".TABLE_LOGIN_HISTORY." where auth_name like '$aname'");
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

	function checkCookieLogin() {

		if(isset($_COOKIE['remember']) && $_COOKIE['remember']) {
			if($this->checklogin($_COOKIE[$this->session_index['auth_name']], $_COOKIE[$this->session_index['pass']]))
			{
				$_SESSION[$this->session_index['auth_name']] = $_COOKIE[$this->session_index['auth_name']];
				$_SESSION[$this->session_index['pass']] = $_COOKIE[$this->session_index['pass']];
				return $_SESSION[$this->session_index['auth_name']];
			}
		}
		return 0;
 	}

	function savePassword($newpass) {

	    $_SESSION[$this->session_index['pass']] = users::encode($newpass);

	}

	function saveAdminPassword($newpass) {

	    $_SESSION[$this->admin_session_index['pass']] = settings::encode($newpass);

	}

	function isMod() {

		if(empty($_SESSION[$this->session_index['moderator']])) return null;
		return $_SESSION[$this->session_index['moderator']];
	
	}

	function isAff() {

		return $_SESSION[$this->session_index['affiliate']];
	
	}

	function addFailedAttempt($is_admin, $ip) {
	
	      global $db;
	      $timestamp = date("Y-m-d H:i:s");
	      $db->query("insert into ".TABLE_FAILED_ATTEMPTS." set `is_admin`='$is_admin', `date`='$timestamp', `ip`='$ip'");
	
	}
	
	function checkFailedAttempts($security_settings, $is_admin, $ip, $login_id) {

	    global $db;
	    $timestamp = date("Y-m-d H:i:s");
	    $no = $db->fetchRow("select count(*) from ".TABLE_FAILED_ATTEMPTS." where `is_admin`='$is_admin' and `ip`='$ip' and 
	    date_add(`date`, interval '1' hour ) >= '$timestamp'");
	    if($is_admin) $str = "admin"; else $str="user";
	    if( $no>=$security_settings['allowed_'.$str.'_attempts']) {
	    
			// block ip for X hours
			global $config_abs_path;
			require_once $config_abs_path."/classes/blocked_ips.php";
			$bi = new blocked_ips();
			$hours = $security_settings['block_'.$str.'_attempts_for'];
			global $lng;
			$added = $bi->addTemporary($ip, $hours, $lng['security']['failed_login_attempts']);
		
			if($added) { 
				$db->query("update ".TABLE_LOGIN_HISTORY." set `blocked`=1 where `id`='$login_id'");
				// if blocked already, start with no login attempts next time
				$this->deleteIPFailedAttempts($ip);
				// if blocked will return -1
				return -1;
			}
	    
	    }
	    return 0;
	
	}
	
	function deleteIPFailedAttempts($ip) {
	
		global $db;
		$db->query("delete from ".TABLE_FAILED_ATTEMPTS." where `ip` like '$ip'");
	
	}

	function setLoginError($err) {
	
		$this->login_error = $err;
	
	}

	function getLoginError() {
	
		return $this->login_error;
	
	}

}

?>
