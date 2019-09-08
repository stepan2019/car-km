<?php 
    $response = "";

    if(isset($_POST['register'])) {
        $name = $_POST['name'];
        $address = $_POST['address'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $password = md5($_POST['password']);

        $result = $config->register_user($name, $address, $email, $phone, $password);
        if($result)	{
            header("location:/user/login.php?type=login_user");
        } else {
            $response = "Sorry, is failed to register";
        }
    }
?>

<style>
    @media(max-width:1023px){
        .swiper-container{
            height:150vh !important;
        }
        .btn_register{
            margin-left:-50px;
        }
    }
</style>

<div class="register-div">
    <form method="post">
        <div class="row col-md-12">
            <div class="col-md-3 text-left mt-4">
                <label class="control-label">Fullname</label>
                <div class="agileits-main">
                    <i class="fas fa-signature"></i>
                    <input type="text" placeholder="Mohammad Jamal" required="" name="name">
                </div>
            </div>
            <div class="col-md-3 text-left mt-4">
                <label class="control-label">Password</label>
                <div class="agileits-main">
                    <i class="fas fa-unlock-alt"></i>
                    <input type="password" placeholder="ex:t7G*4lz" required="" name="password">
                </div>
            </div>
            <div class="col-md-3 text-left mt-4">
                <label class="control-label">Address</label>
                <div class="agileits-main">
                    <i class="fas fa-map-marker-alt"></i>
                    <input type="text" placeholder="Sulaymanyah/Iraq" required="" name="address">
                </div>
            </div>
            <div class="col-md-3 text-left mt-4">
                <label class="control-label">Email</label>
                <div class="agileits-main">
                    <i class="far fa-envelope"></i>
                    <input type="email" placeholder="mohammad@gmail.com" required="" name="email">
                </div>
            </div>
            <div class="col-md-3 text-left mt-4">
                <label class="control-label">Phone Number</label>
                <div class="agileits-main">
                    <i class="fas fa-phone"></i>
                    <input type="tel" placeholder="07702247788" required="" name="phone">
                </div>
            </div>
        </div>
        <div class="text-center submit mt-5">
            <input type="submit" class="btn btn-primary submit-fs btn-custom btn_register" value="Register" name="register">
            <?php if($response != "") { ?>
                <p><label class="control-label mt-3"><?php echo $response; ?></label></p>
            <?php } ?>
        </div>
        <div class="clearfix"></div>
    </form>
</div>