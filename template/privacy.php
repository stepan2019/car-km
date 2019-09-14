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
$result = $config->getPolicyContentByCode($lang_id);
$policy = $result->fetch_assoc();
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
    <script>
        exdate=new Date();
        exdate.setDate(exdate.getDate() + 365);
    </script>
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
                <?php echo $policy['content'];?>

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
