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
    <title>Car Registration - Term and Services</title>

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

    <div class="container privacy mt-3">
        <h2>Term and Services</h2>
        <div class="row pt-2">
            <div class="col-md-2"></div>
            <div class="col-md-8 privacy-box">
                <p>Welcome to Carpass, as user you most register to access the database.</p>
                <p>We have two type of users:</p>
                <p> - Normal user<br> - Dealer user</p>
                <p>Please register as dealer if you have car dealer business.</p>
                <p>When you register you are accord with owe term and service.</p>
                <p>If you want check Km of Vehicle, you most first add the number plate and the KM at the moment you
                    register the vehicle.</p>
                <p>After registration you can generate report, the report will show all registered data of the
                    vehicle.</p>
                <p>If you think some one add wrong data of your vehicle, please contact us.</p>
                <p class="text-center">Carpass</p>
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
