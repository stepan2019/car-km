<?php
include "../setting/config.php";

$result = $config->getInformationContent();
$information = $result->fetch_assoc();
include "../include/include.php";

global $lng;
global $crt_lang_code;
global $text_direction;
?>

<!DOCTYPE html>
<html lang="<?php echo $crt_lang_code; ?>">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" href=" ">
    <title>Car Registration - Register</title>

    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/custom.css" rel="stylesheet">

    <link href="../css/loaders.css" rel="stylesheet">
    <link href="../css/swiper.min.css" rel="stylesheet">
    <link href="../css/animate.min.css" rel="stylesheet">
    <link href="../css/nivo-lightbox.css" rel="stylesheet">
    <link href="../css/nivo_themes/default/default.css" rel="stylesheet">
    <?php
    if ($text_direction == 'rtl') {
        ?>
        <link href="/css/custom_rtl.css" rel="stylesheet">
        <?php
    } else {
        ?>
        <link href="/css/custom.css" rel="stylesheet">
    <?php } ?>
    <link rel="stylesheet" href="/css/all.css" crossorigin="anonymous">
    <script>
        exdate=new Date();
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
            <p class="type-title"><?php echo $lng['users']['Select_type_to_register'];?></p><br>
            <div class="type-user">
                <div class="type-user1">
                    <a href="register.php?type=register_user" class="link-size typing-glow mr-5"><i
                                class="far fa-user"></i>&nbsp;&nbsp;<?php echo $lng['users']['User'];?></a>
                </div>
                <div class="type-user2">
                    <a href="register.php?type=register_dealer" class="link-size typing-glow ml-5"><i
                                class="fas fa-car"></i>&nbsp;&nbsp;<?php echo $lng['users']['Delaer'];?></a>
                </div>
            </div>
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

<script src="../js/jquery.min.js"></script>
<script src="../js/bootstrap.min.js"></script>
<script src="../js/scrollPosStyler.js"></script>
</body>

</html>
