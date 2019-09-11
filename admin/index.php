<?php 
    session_start();
    if(@$_SESSION['admin']) {
        header("location:home.php");
    }
    include "../setting/config.php";
    
    $response = "";

    if(isset($_POST['admin_signin'])) {
        $username = $_POST['username'];
        $password = md5($_POST['password']);
        
        $result = $config->admin_check($username, $password);
        if($result == 1)	{
            $_SESSION['admin'] = $username;
            header("location:home.php");
        } else {
            $response = "Sorry, is failed to Login";
        }
    }
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
    <title>Car Registration - Admin</title>

    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/custom.css" rel="stylesheet">

    <link href="../css/loaders.css" rel="stylesheet">
    <link href="../css/swiper.min.css" rel="stylesheet">
    <link href="../css/animate.min.css" rel="stylesheet">
    <link href="../css/nivo-lightbox.css" rel="stylesheet">
    <link href="../css/nivo_themes/default/default.css" rel="stylesheet">

    <link rel="stylesheet" href="/css/all.css" crossorigin="anonymous">
</head>
<body>
    <div class="swiper-container main-slider" id="myCarousel">
        <div class="swiper-wrapper">
            <div class="swiper-slide slider-bg-position" style="background:url('../img/banner2.jpg')" data-hash="slide1">
                <form method="post" onsubmit="return checkform(this);">
                    <div class="col-md-3 text-left mt-4">
                        <div class="agileits-main">
                            <i class="far fa-user" style="left: 15%;"></i>
                            <input type="text" required="" class="text" name="username" placeholder="Username...">
                        </div>
                    </div>
                    <div class="col-md-3 text-left mt-4">
                        <div class="agileits-main">
                            <i class="fas fa-unlock-alt" style="left: 15%;"></i>
                            <input type="password" required="" name="password" placeholder="Password...">
                        </div>
                    </div>
                    
                    <div class="capbox">
                        <div id="CaptchaDiv"></div>
                        <div class="capbox-inner">
                            Type the above number:<br>
                            <input type="hidden" id="txtCaptcha">
                            <input type="text" name="CaptchaInput" id="CaptchaInput" size="15"><br>
                        </div>
                    </div>
                    
                    <div class="submit">
                        <input type="submit" class="btn btn-primary submit-fs mt-5 btn-custom" value="Login" name="admin_signin">
                        <?php if($response != "") { ?>
                            <p><label class="control-label mt-3"><?php echo $response; ?></label></p>
                        <?php } ?>
                    </div>
                    <div class="clearfix"></div>
                </form>
            </div>
        </div>
    </div>
    
    <script type="text/javascript">
        function captchaGenerate() {
            var a = Math.ceil(Math.random() * 9)+ '';
            var b = Math.ceil(Math.random() * 9)+ '';
            var c = Math.ceil(Math.random() * 9)+ '';
            var d = Math.ceil(Math.random() * 9)+ '';
            var e = Math.ceil(Math.random() * 9)+ '';
            var f = Math.ceil(Math.random() * 9)+ '';

            var code = a + b + c + d + e + f;
            document.getElementById("txtCaptcha").value = code;
            document.getElementById("CaptchaDiv").innerHTML = code; 
        }

        captchaGenerate();

        function checkform(theform) {
            var why = "";

            if(theform.CaptchaInput.value == "") {
                why += "- Please Enter CAPTCHA Code.\n";
            }
            if(theform.CaptchaInput.value != "") {
                if(ValidCaptcha(theform.CaptchaInput.value) == false) {
                    why += "- The CAPTCHA Code Does Not Match.\n";
                }
            }
            if(why != "") {
                captchaGenerate();
                return false;
            }
        }

        function ValidCaptcha() {
            var str1 = removeSpaces(document.getElementById('txtCaptcha').value);
            var str2 = removeSpaces(document.getElementById('CaptchaInput').value);
            if (str1 == str2) {
                return true;
            } else {
                return false;
            }
        }

        function removeSpaces(string) {
            return string.split(' ').join('');
        }
    </script>
</body>

</html>