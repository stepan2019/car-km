<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

function checkValidationImage() {

	if(!isset($_POST['number']) || empty($_SESSION['image_value']) || md5($_POST['number']) != $_SESSION['image_value']) return 0;
	return 1;
	
}

function checkReCaptcha() {

		global $config_abs_path, $settings;
		$privatekey = $settings['recaptcha_private_key'];

		if(isset($_POST['g-recaptcha-response'])){
			$captcha=$_POST['g-recaptcha-response'];
        }
        if(!$captcha) return 0;

		$ip = getRemoteIp();
		$url = "https://www.google.com/recaptcha/api/siteverify?secret=".$privatekey."&response=".$captcha."&remoteip=".$ip;
		
	if ( function_exists('curl_version') )
	{

	    $ch = curl_init();
	    
	    curl_setopt( $ch, CURLOPT_URL, $url );
	    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		
	    $response = trim(curl_exec($ch));

	    curl_close($ch);
	}
	else 
		$response=file_get_contents($url);
		

        $responseKeys = json_decode($response,true);
        if(intval($responseKeys["success"]) !== 1)
          return 0;

		return 1;
}

function checkCaptcha() {

		global $settings, $lng;
		$err="";
		if(!$settings['enable_recaptcha']) {
			if(!checkValidationImage()) 
				$err = $lng['contact']['error']['invalid_validation_number'];
		} else {

			$ret = checkReCaptcha();
			if($ret!=1) { 
				if($ret=="incorrect-captcha-sol")
					$err=$lng['contact']['error']['invalid_validation_number'];
				else $err=$ret.'<br />';
			}
		}
		return $err;
}

?>