<?php 
    include "../setting/config.php";

    session_start();

    if(!@$_SESSION['user']) {
         header("location:/user/login.php");
    }
    
    $result = $config->getInformationContent();
    $information = $result->fetch_assoc();

    $email = @$_SESSION['user'];
    $type = @$_SESSION['type'];
    $result = $config->getAddress($email, $type);
    $user_info = $result->fetch_assoc();
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
    <title>Car Registration - Profile</title>

    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/custom.css" rel="stylesheet">

    <link href="../css/loaders.css" rel="stylesheet">
    <link href="../css/swiper.min.css" rel="stylesheet">
    <link href="../css/animate.min.css" rel="stylesheet">
    <link href="../css/nivo-lightbox.css" rel="stylesheet">
    <link href="../css/nivo_themes/default/default.css" rel="stylesheet">

    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.8.2/css/all.css"
        integrity="sha384-oS3vJWv+0UjzBfQzYUhtDYW+Pj2yciDJxpsK1OYPAYjqT085Qq/1cq5FLXAZQ7Ay" crossorigin="anonymous">
</head>
<body>

    <?php
        include "../template/header.php";
    ?>

    <div class="swiper-container main-slider" id="myCarousel">
        <div class="swiper-wrapper">
            <div class="swiper-slide slider-bg-position" style="background:url('../img/banner1.jpg'); display: block;" data-hash="slide1">
            <?php 
                if($type == "user") {
                    include "profile_user.php";
                } else {
                    include "profile_dealer.php";
                }
            ?>
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