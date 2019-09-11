<?php
$response = "";

$result = $config->getEmailSetting();
$currentData = $result->fetch_assoc();
require_once "include/include.php";
require_once '../classes/validator.php';
require_once '../classes/config/settings_config.php';
require_once '../classes/mails.php';
require_once '../classes/settings.php';

global $db;
global $lng;

$errors_str='';
$successful = 0;
if (isset($_POST['Update'])) {
    $html_email = (isset($_POST['html_email'])) ? $_POST['html_email'] : 0;
    $smtp_auth = $_POST['smtp_auth'];
    $smtp_server = $_POST['smtp_server'];
    $encryption = $_POST['encryption'];
    $port = $_POST['port'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $bcc_email = (isset($_POST['bcc_email'])) ? $_POST['bcc_email'] : 0;
    $admin_email = (isset($_POST['admin_email'])) ? $_POST['admin_email'] : 0;

    $result = $config->update_email_option($html_email, $smtp_auth, $smtp_server, $encryption, $port, $username, $password, $bcc_email, $admin_email);
    if ($result) {
        $result = $config->getEmailSetting();
        $currentData = $result->fetch_assoc();
//        header("location:/admin/home.php?query=mail");
    } else {
        $response = "Sorry, is failed to update";
    }
    $extra_info="";
    $mail = new mails();
    $mail->init();
    $mail->setSubject($lng['settings']['test_mail']);
    $mail->setMessage($lng['settings']['test_mail']);
    $sent = $mail->send();
    if($sent) $info = $lng['mailto']['message_sent'];
    else $info = $lng['mailto']['sending_message_failed'];

    if(!$sent)	$extra_info = $mail->getSendError()."<br/>".$mail->getDebugMessage();
}
?>

<div class="register-div">
    <form method="post">
        <div class="row text-left mt-4">
            <div class="col-md-12">
                <label class="control-label">Enable HTML emails </label>
                <!--<i class="fas fa-signature"></i>-->
                <input class="ml-3" type="checkbox" value="1" class="form-check-inline" id="html_email"
                       name="html_email"
                    <?php if ($currentData['html_email']) echo "checked"; ?>/>
            </div>
        </div>
        <div class="row text-left mt-4">
            <div class="col-md-12">
                <label class="control-label">Use SMTP authentication </label>
                <!--<i class="fas fa-signature"></i>-->
                <input class="ml-3" type="checkbox" value="1" class="form-check-inline" id="smtp_auth" name="smtp_auth"
                    <?php if ($currentData['smtp_auth']) echo "checked"; ?>/>
            </div>
        </div>
        <div class="row col-md-12">
            <div class="col-md-12 text-left mt-4">
                <label class="control-label">SMTP Server</label>
                <div class="agileits-main">
                    <!--<i class="fas fa-map-marker-alt"></i>-->
                    <input type="text" placeholder="" required="" name="smtp_server"
                           value="<?php echo $currentData['smtp_server'] ?>">
                </div>
            </div>
        </div>
        <div class="row col-md-12">
            <div class="col-md-12 text-left mt-4">
                <label class="control-label">encryption</label>
                <div class="agileits-main">
                    <select name="encryption" required="" id="encryption" style="width:114%"
                            value="<?php echo $currentData['encryption'] ?>">
                        <!--<option disabled selected>Status</option>-->
                        <option value="SSL" <?php if ($currentData['encryption'] == "SSL") echo "selected"; ?>>SSL
                        </option>
                        <option value="TLS" <?php if ($currentData['encryption'] == "TLS") echo "selected"; ?>>TLS
                        </option>
                    </select>
                </div>
            </div>
        </div>
        <div class="row col-md-12">
            <div class="col-md-12 text-left mt-4">
                <label class="control-label">Port</label>
                <div class="agileits-main">
                    <!--<i class="fas fa-map-marker-alt"></i>-->
                    <input type="number" placeholder="" required="" name="port"
                           value="<?php echo $currentData['port'] ?>">
                </div>
            </div>
        </div>
        <div class="row col-md-12">
            <div class="col-md-12 text-left mt-4">
                <label class="control-label">Username</label>
                <div class="agileits-main">
                    <!--<i class="fas fa-map-marker-alt"></i>-->
                    <input type="email" placeholder="" required="" name="username"
                           value="<?php echo $currentData['username'] ?>">
                </div>
            </div>
        </div>
        <div class="row col-md-12">
            <div class="col-md-12 text-left mt-4">
                <label class="control-label">Password</label>
                <div class="agileits-main">
                    <!--<i class="fas fa-map-marker-alt"></i>-->
                    <input type="text" placeholder="" required="" name="password"
                           value="<?php echo $currentData['password'] ?>">
                </div>
            </div>
        </div>
        <div class="row col-md-12">
            <div class="col-md-12 text-left mt-4">
                <label class="control-label">Send BCC to </label>
                <div class="agileits-main">
                    <!--<i class="fas fa-map-marker-alt"></i>-->
                    <input type="email" placeholder="" required="" name="bcc_email"
                           value="<?php echo $currentData['bcc_email'] ?>">
                </div>
            </div>
        </div>
        <div class="row text-left mt-4">
            <div class="col-md-12">
                <label class="control-label">Send emails with administrator email as sender</label>
                <!--<i class="fas fa-signature"></i>-->
                <input class="ml-3" type="checkbox" value="1" class="form-check-inline" id="bcc" name="admin_email"
                    <?php if ($currentData['admin_email']) echo "checked"; ?>/>
            </div>
        </div>
        <div class="row col-md-12">
            <div class="col-md-12 text-left mt-4">
                <div class="submit">
                    <input type="submit" class="btn btn-primary submit-fs btn-custom" value="Update" name="Update">
                </div>
            </div>
        </div>
        <div class="clearfix"></div>
    </form>
</div>
