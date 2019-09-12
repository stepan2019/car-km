<?php
include "include/include.php";
$root = realpath($_SERVER["DOCUMENT_ROOT"]);
global $crt_lang_code;
global $lng;

include $root . "/setting/config.php";
// include "setting/config.php";
session_start();
// session_start();
 $result = $config->getContentByCode($crt_lang_code);
 $content = $result->fetch_assoc();

 $banners = $config->loadAdsBanner("header");
 $banners_footer = $config->loadAdsBanner("footer");

 $header = $config->loadBannerByCode("header", $crt_lang_code);
 $footer = $config->loadBannerByCode("footer", $crt_lang_code);
?>
<!DOCTYPE html>
<html lang="<?php echo $crt_lang_code; ?>">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=yes">
    <meta name="description"
          content="Registration of km, mileage registration,kilometer, km Greece cars, greece vehicle km, Car Km, Car mileage,Register your Vehicle,mileage register,Greece,Greece, mileage register, Register your vehicle,Greece cars, Greece car km, carpass km register, carpass.gr">
    <meta name="author" content="https://pegasuswings.gr/">
    <link rel="icon" href=" ">
    <title>Carpass Greece national KM Registration</title>

    <link href="css/bootstrap.min.css" rel="stylesheet">

    <link href="css/loaders.css" rel="stylesheet">
    <link href="css/swiper.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/animate.min.css">
    <link rel="stylesheet" href="css/nivo-lightbox.css">
    <link rel="stylesheet" href="css/nivo_themes/default/default.css">

    <link rel="stylesheet" href="/css/all.css">
    <style>
        @media (max-width: 1023px) {
            .ads_img1 {
                width: 100% !important;
                height: 120px;
            }

            .ads_img2 {
                width: 100% !important;
                height: 120px;
                margin-top: 300px;
            }

            .ads-title2 {
                margin-top: 80px;
            }

            .ads-title3 {
                margin-top: 155px !important;
            }

            .btn-custom {
                margin-top: 105px !important;
                margin-left: 0% !important;
            }

            footer {
                margin-top: 40px;
            }
        }
    </style>
    <script>
        exdate = new Date();
        exdate.setDate(exdate.getDate() + 365);
    </script>
</head>
<body>
<div id="fb-root"></div>
<!--<script async defer crossorigin="anonymous"-->
<!--        src="https://connect.facebook.net/el_GR/sdk.js#xfbml=1&version=v3.3&appId=352556005433627&autoLogAppEvents=1"></script>-->
<!--<div class="loader loader-bg">-->
<!--    <div class="loader-inner ball-clip-rotate-pulse">-->
<!--        <div></div>-->
<!--        <div></div>-->
<!--    </div>-->
<!--</div>-->

<?php
include "template/header.php";
?>

<?php
$header_one = $header->fetch_assoc();
?>
<div class="dashboard-panel parallax-section"
     style="background:url('img/<?php echo $header_one["file_name"]; ?>'); background-size: cover; background-repeat: no-repeat; min-height: 700px; height: 1200px;">
    <div class="dashboard-ab">
        <div style="position: relative;">
            <div class="container text-center">
                <?php
                if ($banners->num_rows > 0) {
                    $cnt = $banners->num_rows;
                    $showNumber = rand(0, $cnt - 1);

                    $arr = [];
                    while ($banner = $banners->fetch_assoc()) {
                        array_push($arr, $banner);
                    }
                    $banner = $arr[$showNumber];
                    ?>
                    <a target="_blank" href="<?php echo $banner["link"]; ?>" style="position: relative;"><span
                                class="ads-title"><?php echo $banner["bannerText"]; ?></span><img class="ads_img1"
                                                                                                  style="width: 900px; height: 120px;border: 1px solid #cbcbcb;border-radius: 10px 10px 10px 10px;"
                                                                                                  src="img/<?php echo $banner["fileName"]; ?>"
                                                                                                  alt="ads"></a>
                    <?php
                }
                ?>

                <h1 class="mb-5 dashboard-title"
                    style="margin-top: 5rem !important;"><?php echo $header_one["title"] ?></h1>
                <h3 class="mt-1 mb-3 dashboard-text"><?php echo $content['content1']; ?></h3>
                <h3 class="mt-1 mb-3 dashboard-text"><?php echo $content['content2']; ?></h3>
                <a href="
                        <?php
                if (@$_SESSION['user']) {
                    echo '/vehicle/add.php';
                } else {
                    echo '/user/login.php?query=goVehicle';
                }
                ?>" class="btn btn-primary submit-fs mt-5 ml-5 btn-custom"><?php echo $lng['navbar']['add_vehicle_km'];?></a>
            </div>
        </div>
    </div>
</div>

<br><br>

<div align="center">


    <?php
    if ($banners_footer->num_rows > 0) {
        $cnt = $banners_footer->num_rows;
        $showNumber = rand(0, $cnt - 1);

        $arr = [];
        while ($banner = $banners_footer->fetch_assoc()) {
            array_push($arr, $banner);
        }
        $banner = $arr[$showNumber];
        ?>
        <a target="_blank" href="<?php echo $banner["link"]; ?>" style="position: relative; "><span
                    class="ads-title ads-title3"><?php echo $banner["bannerText"]; ?></span><img class="ads_img2"
                                                                                                 style="width: 900px; height: 120px;background-color: #f1edd4;border: 1px solid #cbcbcb;border-radius: 10px 10px 10px 10px;margin-bottom: 10px;"
                                                                                                 src="img/<?php echo $banner["fileName"]; ?>"
                                                                                                 alt="ads"></a>
        <?php
    }
    ?>

</div>
</div>
</div>
</div>
</section>

<br>

<?php
include "template/footer.php";
?>

<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/scrollPosStyler.js"></script>
<script src="js/swiper.min.js"></script>
<script src="js/isotope.min.js"></script>
<script src="js/nivo-lightbox.min.js"></script>
<script src="js/wow.min.js"></script>
<script src="js/core.js"></script>

</body>
</html>
