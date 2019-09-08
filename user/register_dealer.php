<?php 
    $response = "";

    if(isset($_POST['register'])) {
        $name = $_POST['name'];
        $address = $_POST['address'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $company = $_POST['company'];
        $website = $_POST['website'];
        $password = md5($_POST['password']);

        $result = $config->register_dealer($name, $address, $email, $phone, $company, $website, $password);
        if($result) {
            header("location:/user/login.php?type=login_dealer");
        } else {
            $response = "Sorry, is failed to register";
        }
    }
?>
<style>
    @media(max-width:1023px){
        .swiper-container{
            height:170vh !important;
        }
    }
</style>

<div class="register-div">
    <form method="post">
        <div class="row col-md-12">
            <div class="col-md-3 text-left mt-4">
                <label class="control-label">Username</label>
                <div class="agileits-main">
                    <i class="fas fa-signature"></i>
                    <input type="text" placeholder="Aram Sardar" required="" name="name">
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
                    <input type="text" placeholder="Bagdada/Iraq" required="" name="address">
                </div>
            </div>
            <div class="col-md-3 text-left mt-4">
                <label class="control-label">Email</label>
                <div class="agileits-main">
                    <i class="far fa-envelope"></i>
                    <input type="email" placeholder="Aram@gmail.om" required="" name="email">
                </div>
            </div>
            <div class="col-md-3 text-left mt-4">
                <label class="control-label">Phone Number</label>
                <div class="agileits-main">
                    <i class="fas fa-phone"></i>
                    <input type="tel" placeholder="07702247788" required="" name="phone">
                </div>
            </div>
            <div class="col-md-3 text-left mt-4">
                <label class="control-label">Company</label>
                <div class="agileits-main">
                    <i class="far fa-building"></i>
                    <input type="text" placeholder="Car Sell" required="" name="company">
                </div>
            </div>
            <div class="col-md-3 text-left mt-4">
                <label class="control-label">Website</label>
                <div class="agileits-main">
                    <i class="fas fa-network-wired"></i>
                    <input type="url" placeholder="https://seyare.net" required="" name="website">
                </div>
            </div>
        </div>
        <div class="submit">
            <input type="submit" class="btn btn-primary submit-fs btn-custom" value="Register" name="register">
            <?php if($response != "") { ?>
                <p><label class="control-label mt-3"><?php echo $response; ?></label></p>
            <?php } ?>
        </div>
        <div class="clearfix"></div>
    </form>
</div>
