<?php 
    include "../setting/config.php";

    session_start();

    if(!@$_SESSION['user']) {
         header("location:/user/login.php");
    }
    include "../include/include.php";

    global $crt_lang_code;


    $result = $config->getInformationContent();
    $information = $result->fetch_assoc();

    $email = @$_SESSION['user'];
    $type = @$_SESSION['type'];
    $result = $config->getAddress($email, $type);
    $user_info = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="<?php echo $crt_lang_code;?>">
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
            <div class="swiper-slide slider-bg-position" data-hash="slide1">
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
