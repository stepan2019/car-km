<?php
    include "../setting/config.php";

    session_start();

    if(@$_SESSION['user']) {
        $email = @$_SESSION['user'];
    }
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

    $response = "";
    
    if(isset($_POST['contactSubmit'])) {
        $fullname = $_POST['fullname'];
        $phonenumber = $_POST['phonenumber'];
        $email = $_POST['email'];
        $message = $_POST['message'];

        $adminEmail = $config->getAdminEmail();
        $obj = $adminEmail->fetch_assoc();
        $mailTofirma = $obj['email'];
        $mailFrom = '"Carpass Registration" <carpass.gr@gmail.com>';
        $subject    = 'Carpass Contact Us';

        $headers = "From: " . strip_tags($email) . "\r\n";
        $headers .= "Reply-To: ". strip_tags($mailTofirma) . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";

        $result = @mail($mailTofirma, $subject, $message, $headers);

        if($result)
            $response = "Mail has been sent successfully.";
        else
            $response = "Failed.";
    }
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
    <title>Car Registration - Contact</title>

    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/custom.css" rel="stylesheet">

    <link href="../css/loaders.css" rel="stylesheet">
    <link href="../css/swiper.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/animate.min.css">
    <link rel="stylesheet" href="../css/nivo-lightbox.css">
    <link rel="stylesheet" href="../css/nivo_themes/default/default.css">

    <link rel="stylesheet" href="/css/all.css" crossorigin="anonymous">
    <style>
        @media (max-width:1023px){
            footer {
                margin-top:220px;
            }
        	
        }
    </style>
    <script>
        exdate = new Date();
        exdate.setDate(exdate.getDate() + 365);
    </script>
</head>
<body>
    <?php
        include "header.php";
    ?>

    <div class="contact-sec dashboard-panel parallax-section" style="background-size: cover; background-repeat: no-repeat; min-height: 700px;">
        <form id="contactForm" method="post" name="contactForm">
            <div class="container mt-5">
                <h2><?php echo $lng['contact']['Contact_us'];?><small><?php echo $lng['contact']['fill_all_field'];?></small> </h2>
                <div class="row pt-5">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="fullname"><?php echo $lng['contact']['name'];?></label>
                            <input type="text" name="fullname" class="form-control" id="fullname" required="" aria-describedby="emailHelp">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="examplePhone"><?php echo $lng['contact']['Phone Number'];?></label>
                            <input type="tel" name="phonenumber" class="form-control" id="examplePhone" required="" aria-describedby="emailHelp">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="exampleInputEmail1"><?php echo $lng['contact']['your_mail_address'];?></label>
                            <input type="email" name="email" class="form-control" id="exampleInputEmail1" required="" value="<?php if(isset($email)) echo $email; ?>" aria-describedby="emailHelp">
                            <small id="emailHelp" class="form-text text-muted"><?php echo $lng['contact']['never_share_mail'];?></small>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <label for="exampleTextarea"><?php echo $lng['contact']['Enter_Your_Massage'];?></label>
                        <textarea class="form-control" name="message" id="exampleTextarea" required="" rows="3"></textarea>
                    </div>
                    <div class="col-md-12 text-center text-xs-center action-block">
                        <button type="submit" name="contactSubmit" id="contactSubmit" class="btn btn-capsul btn-aqua submit-fs btn-custom"><?php echo $lng['contact']['Submit'];?></button>
                    </div>
                    <?php if($response != "") { ?>
                        <div class="col-md-12 text-center">
                            <p><label class="control-label mt-3"><?php echo $response; ?></label></p>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </form>
    </div>

    <?php
        include "information.php";
    ?>

    <?php
        include "footer.php";
    ?>

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
