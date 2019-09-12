<?php

$response = "";
require_once "../include/include.php";
require_once '../classes/validator.php';
require_once '../classes/mails.php';
require_once '../classes/settings.php';
require_once $config_abs_path . "/classes/mail_templates.php";
global $lng;
global $mail_setting;
$setting = new settings();
$mail_setting = $setting->getMailSettings();
global $lng;
if (isset($_POST['register'])) {

    $name = $_POST['name'];
    $address = $_POST['address'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $company = $_POST['company'];
    $website = $_POST['website'];
    $password = md5($_POST['password']);

    $settingResult = $config->getEmailSetting();

    $setting = $settingResult->fetch_assoc();
    $is_useemail = $config->dealerEmailCheck($email);
    if($is_useemail){
        $response = 'Your email address already exists';
    }else{

        $result = $config->register_dealer($name, $address, $email, $phone, $company, $website, $password);

        global $config_live_site;
        // add activation code to db record
        $activation_code = generate_random();
        $res_act = $db->query("update dealer set activation='$activation_code' where `email` = '$email'");
        $type = "dealder";
        $account = urlencode($_POST['email']);
        if (!$mail_setting['html_mails'])
            $act_link = $config_live_site . '/activate_account.php?account=' . $account . '&activation=' . $activation_code . '&type=' . $type;
        else {
            $lnk = $config_live_site . '/activate_account.php?account=' . $account . '&activation=' . $activation_code . '&type=' . $type;
            $act_link = '<a href="' . $lnk . '">' . $lnk . '</a>';
        }
        //send Active mail to dealer
        $mail2send = new mails();
        $mail2send->init($_POST['email'], $_POST['name']);
        $mail2send->to = $email;
        $mail2send->to_name = $name;
        $mail2send->setSubject(cleanStr('Thank you for registration on carpass'));
        $msg = nl2br(cleanStr('<div><p>After activation you can ADD your vehicle,</p><p> please click on this link to activate your account. </p><p>Then link to activate</p>
                    <p>' . $act_link . '</p></div>')) . '';
        $mail2send->setMessage($msg);
        $is_sendMail = $mail2send->send();
        $mail2admin = new mails();
        $mail2admin->init($mail_setting['username'], $_POST['name']);
        $mail2admin->to = $mail_setting['username'];
        $mail2admin->to_name = 'Carpass Admin';
        $mail2admin->setSubject(cleanStr('Dealer Registration'));
        $msg = nl2br(cleanStr('<div><p>Dealer email address : '.$email.',</p><p> Dealer name : '.$name.'. </p><p>Please check this user</p>
                    </div>')) . '';
        $mail2admin->setMessage($msg);
        $is_sendMail = $mail2admin->send();
        if ($is_sendMail) {
            $response = 'You are registered. you got mail. you have to activate your account.';
        } else {
            $response = $mail2send->send_error;
        }
    }
}
?>
<style>
    @media (max-width: 1023px) {
        .swiper-container {
            height: 170vh !important;
        }
    }
</style>

<div class="register-div container">
    <?php if ($response != "") { ?>
        <p><label class="control-label mt-3"><?php echo $response; ?></label></p>
    <?php } ?>
    <form method="post">
        <div class="row col-md-12 justify-content-center">
            <div class="col-md-3 text-left mt-4">
                <label class="control-label"><?php echo $lng['users']['Username']; ?></label>
                <div class="agileits-main">
                    <i class="fas fa-signature"></i>
                    <input type="text" placeholder="Aram Sardar" required="" name="name">
                </div>
            </div>
            <div class="col-md-3 text-left mt-4">
                <label class="control-label"><?php echo $lng['users']['password']; ?></label>
                <div class="agileits-main">
                    <i class="fas fa-unlock-alt"></i>
                    <input type="password" placeholder="ex:t7G*4lz" required="" name="password">
                </div>
            </div>
            <div class="col-md-3 text-left mt-4">
                <label class="control-label"><?php echo $lng['users']['Address']; ?></label>
                <div class="agileits-main">
                    <i class="fas fa-map-marker-alt"></i>
                    <input type="text" placeholder="Bagdada/Iraq" required="" name="address">
                </div>
            </div>
        </div>
        <div class="row col-md-12 justify-content-center">
            <div class="col-md-3 text-left mt-4">
                <label class="control-label"><?php echo $lng['users']['Email']; ?></label>
                <div class="agileits-main">
                    <i class="far fa-envelope"></i>
                    <input type="email" placeholder="Aram@gmail.om" required="" name="email">
                </div>
            </div>
            <div class="col-md-3 text-left mt-4">
                <label class="control-label"><?php echo $lng['users']['Phone_number']; ?></label>
                <div class="agileits-main">
                    <i class="fas fa-phone"></i>
                    <input type="text" placeholder="07702247788" required="" name="phone">
                </div>
            </div>
            <div class="col-md-3 text-left mt-4">
                <label class="control-label"><?php echo $lng['users']['Company']; ?></label>
                <div class="agileits-main">
                    <i class="far fa-building"></i>
                    <input type="text" placeholder="Car Sell" required="" name="company">
                </div>
            </div>
        </div>
        <div class="row col-md-12 justify-content-center">
            <div class="col-md-3 text-left mt-4">
                <label class="control-label"><?php echo $lng['users']['Website']; ?></label>
                <div class="agileits-main">
                    <i class="fas fa-network-wired"></i>
                    <input type="text" placeholder="https://seyare.net" required="" name="website">
                </div>
            </div>
        </div>
        <div class="row submit col-md-12">
            <div class="col-md-1"></div>
            <div class="col-md-3 text-left mt-4">
                <input type="submit" class="btn btn-primary submit-fs btn-custom"
                       value="<?php echo $lng['users']['register']; ?>" name="register">

            </div>
            <div class="col-md-8"
        </div>
        <div class="clearfix"></div>
    </form>
</div>
