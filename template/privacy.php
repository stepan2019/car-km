<?php
include "../setting/config.php";
include "../include/include.php";

global $lng;
global $crt_lang_code;
if (isset($_GET['lang_id'])) {
    $lang_id = $_GET['lang_id'];
} else {
    $lang_id = $crt_lang_code;
}
$result = $config->getInformationContent();
$information = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" href=" ">
    <title>Car Registration - Privacy Policy</title>

    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/custom.css" rel="stylesheet">

    <link href="../css/loaders.css" rel="stylesheet">
    <link href="../css/swiper.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/animate.min.css">
    <link rel="stylesheet" href="../css/nivo-lightbox.css">
    <link rel="stylesheet" href="../css/nivo_themes/default/default.css">

    <link rel="stylesheet" href="/css/all.css" crossorigin="anonymous">
</head>
<body>
<?php
include "header.php";
?>

<div class="contact-sec dashboard-panel parallax-section">
    <div class="container privacy mt-5">
        <h2>Privacy Policy of Carpass</h2>
        <div class="row pt-2">
            <div class="col-md-2"></div>
            <div class="col-md-8 privacy-box">
                <p>All information will be Stord in database to collect as Mach as possible KM of the Vehicle in
                    Greece.</p>
                <p>The information is accessible for everyone who want buy or sell Vehicle.</p>
                <p>The car dealers can also access this data base to control vehicle Km.</p>
                <p>We don’t use your information for any thing eels then checking Km of the vehicle.</p>
                <p>We will sometimes sent newsletter mail to own registered user , this way you will be up to date with
                    all information, according use of owe database.</p>
                <p>If you have any question you can contact us.</p>
                <p><a href="mailto:info@example.com"><i class="fa fa-envelope"></i> carpass.gr@gmail.com</a></p>

            </div>
            <div class="col-md-2"></div>
        </div>
    </div>
</div>

<?php
include "information.php";
?>

<?php
include "footer.php";
?>

<script src="../js/jquery.min.js"></script>
<script src="../js/bootstrap.min.js"></script>
<script src="../js/scrollPosStyler.js"></script>
<script src="../js/swiper.min.js"></script>
<script src="../js/isotope.min.js"></script>
<script src="../js/nivo-lightbox.min.js"></script>
<script src="../js/wow.min.js"></script>
<script src="../js/core.js"></script>

</body>
</html>
