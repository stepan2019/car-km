<?php
include "../setting/config.php";
session_start();
if (!empty($_GET['query'])) {
    $go = $_GET['query'];
}
include "../include/include.php";

global $lng;
global $crt_lang_code;
global $text_direction;
if (@$_SESSION['user']) {
    header("location:../vehicle/add.php");
    exit(0);

}

$result = $config->getInformationContent();
$information = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" href=" ">
    <title>Car Registration - Login</title>

    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/custom.css" rel="stylesheet">

    <link href="../css/loaders.css" rel="stylesheet">
    <link href="../css/all.css" rel="stylesheet">
    <link href="../css/swiper.min.css" rel="stylesheet">
    <link href="../css/animate.min.css" rel="stylesheet">
    <link href="../css/nivo-lightbox.css" rel="stylesheet">
    <link href="../css/nivo_themes/default/default.css" rel="stylesheet">

    <link rel="stylesheet" href="/css/all.css" crossorigin="anonymous">
    <?php
    if ($text_direction == 'rtl') {
        ?>
        <link href="/css/custom_rtl.css" rel="stylesheet">
        <?php
    } else {
        ?>
        <link href="/css/custom.css" rel="stylesheet">
    <?php } ?>
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
        <?php
        if (isset($_GET['type'])) {
            $page = $_GET['type'];
            include $page . ".php";
        } else {
            ?>
            <div class="type-title">
                <p><?php echo $lng['login']['Select_type_to_login']; ?></p><br>
            </div>
            <div>
                <div class="type-user1">
                    <a href="login.php?type=login_user<?php if (isset($go)) echo "&go=" . $go; ?>"
                       class="link-size typing-glow mr-5"><i
                                class="far fa-user"></i>&nbsp;&nbsp;<?php echo $lng['login']['User']; ?></a>
                </div>
                <div class="type-user2">
                    <a href="login.php?type=login_dealer<?php if (isset($go)) echo "&go=" . $go; ?>"
                       class="link-size typing-glow ml-5"><i
                                class="fas fa-car"></i>&nbsp;&nbsp;<?php echo $lng['login']['Delaer']; ?></a>
                </div>
            </div>
        <?php } ?>
    </div>
</div>
</div>

<?php
include "../template/footer.php";
?>

<script src="../js/jquery.min.js"></script>
<script src="../js/bootstrap.min.js"></script>
<script src="../js/scrollPosStyler.js"></script>
</body>

</html>
