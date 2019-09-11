<?php
    $response = "";
    $goTo = "";
    
    if(!empty($_GET['go'])) {
        $goTo = $_GET['go'];
    }
    global $lng;

    if(isset($_POST['login'])) {
        $email = $_POST['email'];
        $password = md5($_POST['password']);

        $result = $config->login_user($email, $password);
        $isBlock = $config->blockCheck($email, "user");
        if($result == 1 && !$isBlock)	{
            $_SESSION['user'] = $email;
            $_SESSION['type'] = "user";
            if(isset($goTo) && $goTo == "goVehicle"){
                $URL="/vehicle/add.php";
                echo '<META HTTP-EQUIV="refresh" content="0;URL=' . $URL . '">';
            }else if(isset($goTo) && $goTo == "goRequest"){
                $URL="/vehicle/history.php";
                echo '<META HTTP-EQUIV="refresh" content="0;URL=' . $URL . '">';
            }else{
               $URL="/vehicle/add.php";
                echo '<META HTTP-EQUIV="refresh" content="0;URL=' . $URL . '">';
            }
        } else {
            $response = "Sorry, is failed to login";
        }
    }
?>

<div class="login-div">
    <form method="post">
        <div class="row col-md-12">
            <div class="col-md-5 text-left mt-4 mr-4">
                <label class="control-label"><?php echo $lng['login']['Email'];?></label>
                <div class="agileits-main">
                    <i class="far fa-envelope"></i>
                    <input type="email" required="" name="email">
                </div>
            </div>
            <div class="col-md-5 text-left mt-4">
                <label class="control-label"><?php echo $lng['login']['password'];?></label>
                <div class="agileits-main">
                    <i class="fas fa-unlock-alt"></i>
                    <input type="password" required="" name="password">
                </div>
            </div>
        </div>
        <div class="text-center submit mt-5">
            <a href="forgot.php?type=user" style="color: black;"><i class="fas fa-key"></i>&nbsp;&nbsp;&nbsp;<?php echo $lng['login']['forgot_pass'];?></a>
        </div>
        <div class="text-center submit mt-5">
            <input type="submit" class="btn btn-primary submit-fs btn-custom" value="<?php echo $lng['login']['login'];?>" name="login">
            <?php if($response != "") { ?>
                <p><label class="control-label mt-3"><?php echo $response; ?></label></p>
            <?php } ?>
        </div>
        <div class="clearfix"></div>
    </form>
</div>
