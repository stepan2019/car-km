<?php
    include "../setting/config.php";
    
    session_start();

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
    <title>CNational CarPass Information</title>

    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/custom.css" rel="stylesheet">

    <link href="../css/loaders.css" rel="stylesheet">
    <link href="../css/swiper.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/animate.min.css">
    <link rel="stylesheet" href="../css/nivo-lightbox.css">
    <link rel="stylesheet" href="../css/nivo_themes/default/default.css">

    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.8.2/css/all.css"
        integrity="sha384-oS3vJWv+0UjzBfQzYUhtDYW+Pj2yciDJxpsK1OYPAYjqT085Qq/1cq5FLXAZQ7Ay" crossorigin="anonymous">
</head>
<body>
    <?php
        include "header.php";
    ?>

    <div class="contact-sec info-panel parallax-section" style="background-size: cover; background-repeat: no-repeat;">
        <div class="container privacy mt-3">
            <h2>National CarPass Information</h2>
            <div class="row pt-2">
                <div class="col-md-2"></div>
                <div class="col-md-8 privacy-box">
             <p><?php echo $information['content']; ?></p>
                </div>
                <div class="col-md-2"></div>
            </div>
        </div>
    </div>

 

  </div>
    
        <?php
        include('footer.php')
        ?>
    </div>

    <script src="../js/jquery.min.js" ></script> 
    <script src="../js/bootstrap.min.js"></script> 
    <script src="../js/scrollPosStyler.js"></script> 
    <script src="../js/swiper.min.js"></script> 
    <script src="../js/isotope.min.js"></script> 
    <script src="../js/nivo-lightbox.min.js"></script> 
    <script src="../js/wow.min.js"></script> 
    <script src="../js/core.js"></script> 

</body>
</html>
