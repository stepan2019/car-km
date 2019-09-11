<?php
    include "setting/config.php";

    session_start();

    $result = $config->getAboutContent();
    $about = $result->fetch_assoc();
    
    $result = $config->getInformationContent();
    $information = $result->fetch_assoc();
    
    $banners = $config->loadAdsBanner("header");
    $banners_footer = $config->loadAdsBanner("footer");

    $header = $config->loadBanner("header");
    $footer = $config->loadBanner("footer");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=yes">
    <meta name="description" content="Registration of km, mileage registration,kilometer, km greece cars, greece vehicle km, Car Km, Car mileage,Register your Vehicle,mileage register,Greece,Greece, mileage register, Register your vehicle,Greece cars, Greece car km, carpass km register, carpass.gr">
    <meta name="author" content="https://www.pegasuswings.gr">
    <link rel="icon" href=" ">
    <title>Car-KM  KM Registration Greece</title>

    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/custom.css" rel="stylesheet">

    <link href="css/loaders.css" rel="stylesheet">
    <link href="css/swiper.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/animate.min.css">
    <link rel="stylesheet" href="css/nivo-lightbox.css">
    <link rel="stylesheet" href="css/nivo_themes/default/default.css">

    <link rel="stylesheet" href="/css/all.css" crossorigin="anonymous">
</head>
<body>
    <div id="fb-root"></div>
<!--    <script async defer crossorigin="anonymous" src="https://connect.facebook.net/el_GR/sdk.js#xfbml=1&version=v3.3&appId=352556005433627&autoLogAppEvents=1"></script>-->
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
	<div class="dashboard-panel parallax-section" style="background:url('img/<?php echo $header_one["file_name"]; ?>'); background-size: cover; background-repeat: no-repeat; min-height: 700px;">
        <div class="dashboard-ab">
            <div style="position: relative;">
                <div class="container text-center">
            <?php
                if($banners->num_rows > 0) {
                    $cnt = $banners->num_rows;
                    $showNumber = rand(0, $cnt - 1);

                    $arr = [];
                    while($banner = $banners->fetch_assoc()) {
                        array_push($arr, $banner);
                    }
                    $banner = $arr[$showNumber];
            ?>
                    <a target="_blank" href="<?php echo $banner["link"]; ?>" style="position: relative;"><span class="ads-title"><?php echo $banner["bannerText"]; ?></span><img style="width: 900px; height: 120px;border: 1px solid #cbcbcb;border-radius: 10px 10px 10px 10px;" src="img/<?php echo $banner["fileName"]; ?>" alt="ads"></a>
            <?php
                }
            ?>
                    <h1 class="mb-5 dashboard-title" style="margin-top: 5rem !important;"><?php echo $header_one["title"] ?></h1>
                    <h2 class="mt-5 mb-5 dashboard-text">You can Add Vehicle KM and Get KM Report for free</h2>
                    <a href="
                        <?php
                            if(@$_SESSION['user']) {
                                echo '/vehicle/add.php';
                            } else {
                                echo '/user/login.php?query=goVehicle';
                            }
                        ?>" class="btn btn-primary submit-fs mt-5 ml-5 btn-custom">Add Vehicle KM</a>
                </div>
            </div>
        </div>  
    </div>
	</div>
    <div align="center">

	
	
            <?php
                if($banners_footer->num_rows > 0) {
                    $cnt = $banners_footer->num_rows;
                    $showNumber = rand(0, $cnt - 1);

                    $arr = [];
                    while($banner = $banners_footer->fetch_assoc()) {
                        array_push($arr, $banner);
                    }
                    $banner = $arr[$showNumber];
            ?>
                    <a target="_blank" href="<?php echo $banner["link"]; ?>" style="position: relative; "><span class="ads-title"><?php echo $banner["bannerText"]; ?></span><img style="width: 900px; height: 120px;background-color: #f1edd4;border: 1px solid #cbcbcb;border-radius: 10px 10px 10px 10px;margin-bottom: 10px;" src="img/<?php echo $banner["fileName"]; ?>" alt="ads"></a>
            <?php
                }
            ?>
			
			</div>
                </div>
            </div>
        </div>
    </section>
    
    <?php
        include "template/information.php";
    ?>

    <?php
        include "template/footer.php";
    ?>

    <script src="js/jquery.min.js" ></script> 
    <script src="js/bootstrap.min.js"></script> 
    <script src="js/scrollPosStyler.js"></script> 
    <script src="js/swiper.min.js"></script> 
    <script src="js/isotope.min.js"></script> 
    <script src="js/nivo-lightbox.min.js"></script> 
    <script src="js/wow.min.js"></script> 
    <script src="js/core.js"></script> 

</body>
</html>
