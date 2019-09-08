<?php
   include "../setting/config.php";
   $error = "";
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
    <title>Car Registration - Reset Password</title>

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
            <div class="swiper-slide slider-bg-position" style="background:url('../img/banner2.jpg'); display: block;" data-hash="slide1">
            
               <?php
                  if (isset($_GET["key"]) && isset($_GET["email"]) && isset($_GET["action"]) && ($_GET["action"]=="reset") && !isset($_POST["action"])) {
                     $key = $_GET["key"];
                     $email = $_GET["email"];
                     $type = $_GET["type"];
                     $curDate = date("Y-m-d H:i:s");

                     $result = $config->getForgotPassword($email, $key);
                     if (!$result) {
                        $error .= '<h2>Invalid Link</h2>
                                 <p>The link is invalid/expired. Either you did not copy the correct link from the email, or you have already used the key in which case it is deactivated.</p>
                                 <p><a href="https://carpass.gr/user/forgot.php">Click here</a> to reset password.</p>';
                     } else {
                        $obj = $result->fetch_assoc();
                        $expDate = $obj['expDate'];
                        if ($expDate >= $curDate) {
               ?>
                           <div class="forgot-div">
                              <form method="post" action="" name="update">
                                 <input type="hidden" name="action" value="update" />
                                 <br /><br />

                                 <label style="color: white;"><strong>Enter New Password:</strong></label><br />
                                 <div class="agileits-main">
                                    <i class="far fa-envelope"></i>
                                    <input type="password" name="pass1" required />
                                 </div>
                                 <br /><br />

                                 <label style="color: white;"><strong>Re-Enter New Password:</strong></label><br />
                                 <div class="agileits-main">
                                    <i class="far fa-envelope"></i>
                                    <input type="password" name="pass2" required/>
                                 </div>
                                 <br /><br />

                                 <input type="hidden" name="email" value="<?php echo $email;?>"/>
                                 <input type="hidden" name="type" value="<?php echo $type;?>"/>
                                 <input type="submit" value="Reset Password" class="btn btn-primary submit-fs btn-custom" />
                              </form>
                           </div>
                        <?php
                        } else {
                           $error .= "<h2>Link Expired</h2>
                                       <p style='color: white;'>The link is expired. You are trying to use the expired link which as valid only 24 hours (1 days after request).<br /><br /></p>";
                        }
                     }
                     if($error!="") {
                        echo "<div class='error'>".$error."</div><br />";
                     }        
                  } // isset email key validate end
                  
                  
                  if(isset($_POST["email"]) && isset($_POST["action"]) && ($_POST["action"]=="update")) {
                     $error="";
                     $pass1 = $_POST["pass1"];
                     $pass2 = $_POST["pass2"];
                     $email = $_POST["email"];
                     $type = $_POST["type"];

                     $curDate = date("Y-m-d H:i:s");
                     
                     if ($pass1 != $pass2) {
                        $error.= "<p>Password do not match, both password should be same.<br /><br /></p>";
                     }
                     if($error!="") {
                        echo "<div class='error'>".$error."</div><br />";
                     } else {
                        $pass1 = md5($pass1);

                        $update_result = $config->updateForgotPassword($email, $pass1, $type);
                  
                        echo '<div class="error"><p>Congratulations! Your password has been updated successfully.</p>
                             <p><a href="https://carpass.gr/user/login.php">
                             Click here</a> to Login.</p></div><br />';
                     }     
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