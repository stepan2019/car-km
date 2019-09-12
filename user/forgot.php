<?php
include "../setting/config.php";

session_start();

$response = "";
$type = "";
include "../include/include.php";
require_once '../classes/validator.php';
require_once '../classes/mails.php';
require_once '../classes/settings.php';
require_once $config_abs_path . "/classes/mail_templates.php";
global $lng;
global $mail_setting;
$setting = new settings();
$mail_setting = $setting->getMailSettings();
if(isset($_GET["type"])) {
    $type = $_GET["type"];
}

if(isset($_POST["email"]) && (!empty($_POST["email"]))) {
    $email = $_POST["email"];
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    if (!$email) {
        $response .="<p>Invalid email address please type a valid email address!</p>";
    } else {
        $result = $config->getUserFromEmail($email, $type);
        if($result != 1)
            $response .= "<p>No user is registered with this email address!</p>";
    }
    if($response!="") {
        // $response = $response."<br><a href='javascript:history.go(-1)'>Go Back</a>";
    } else {
        $expFormat = mktime(
            date("H"), date("i"), date("s"), date("m"), date("d") + 1, date("Y")
        );
        $expDate = date("Y-m-d H:i:s", $expFormat);
        $key = md5(2418 * 2 . $email);
        $addKey = substr(md5(uniqid(rand(),1)),3,10);
        $key = $key . $addKey;

        $result = $config->setForgotPassword($email, $key, $expDate);

        if($result) {
            $output='<p>Dear user,</p>';
            $output.='<p>Please click on the following link to reset your password.</p>';
            $output.='<p>-------------------------------------------------------------</p>';
            $output.='<p><a href="https://car-km.com/user/reset-password.php?key='.$key.'&email='.$email.'&type='.$type.'&action=reset" target="_blank">
               https://car-km.com/user/reset-password.php?key='.$key.'&email='.$email.'&type='.$type.'&action=reset</a></p>';
            $output.='<p>-------------------------------------------------------------</p>';
            $output.='<p>Please be sure to copy the entire link into your browser.
               The link will expire after 1 day for security reason.</p>';
            $output.='<p>If you did not request this forgotten password email, no action is needed, your password will not be reset. However, you may want to log into your account and change your security password as someone may have guessed it.</p>';
            $output.='<p>Thanks,</p>';
            $body = $output;
            $subject = "Password Recovery";

            $mail2send = new mails();
            $mail2send->init($mail_setting['username'], 'Car-KM');
            $mail2send->to = $email;
            $mail2send->to_name = 'You';
            $mail2send->setSubject(cleanStr($subject));
            $msg = nl2br(cleanStr($body)) . '';
            $mail2send->setMessage($msg);
            $is_sendMail = $mail2send->send();

            if($is_sendMail)
                $response = "An email has been sent to you with instructions on how to reset your password.";
            else
                $response = "Failed to send.";
        } else {
            $response = "Failed to add to database.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" href=" ">
    <title>Car Registration - Forgot Password</title>

    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/custom.css" rel="stylesheet">

    <link href="../css/loaders.css" rel="stylesheet">
    <link href="../css/swiper.min.css" rel="stylesheet">
    <link href="../css/animate.min.css" rel="stylesheet">
    <link href="../css/nivo-lightbox.css" rel="stylesheet">
    <link href="../css/nivo_themes/default/default.css" rel="stylesheet">

    <link rel="stylesheet" href="/css/all.css" crossorigin="anonymous">
    <script>
        exdate = new Date();
        exdate.setDate(exdate.getDate() + 365);

    </script>
</head>
<body>

<?php
include "../template/header.php";
?>

<div class="swiper-container main-slider" id="myCarousel">
    <div class="swiper-wrapper">
        <div class="swiper-slide slider-bg-position" style="background:url('../img/banner2.jpg'); display: block;" data-hash="slide1">
            <div class="forgot-div">
                <form method="post">
                    <div class="text-center">
                        <label class="control-label text-left"><?php echo $lng['password_recovery']['email'];?></label>
                        <div class="agileits-main">
                            <i class="far fa-envelope"></i>
                            <input type="email" required="" name="email">
                        </div>
                    </div>
                    <div class="text-center submit mt-5">
                        <input type="submit" class="btn btn-primary submit-fs btn-custom" value="<?php echo $lng['password_recovery']['Reset_password'];?>" name="reset">
                    </div>
                    <div class="clearfix"></div>
                </form>
            </div>
            <?php if($response != "") { ?>
                <p class="forgot-res"><label class="control-label mt-3"><?php echo $response; ?></label></p>
            <?php } ?>
        </div>
    </div>
</div>

<?php
include "../template/information.php";
?>

<?php
include "../template/footer.php";
?>

<script src="../js/jquery.min.js" ></script>
<script src="../js/bootstrap.min.js"></script>
<script src="../js/scrollPosStyler.js"></script>
</body>

</html>
