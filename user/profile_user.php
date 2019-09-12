<?php 
    $response = "";
    $response2 = "";

    $id = $user_info['id'];
    global $lng;

    if(isset($_POST['update'])) {
        $name = $_POST['name'];
        $address = $_POST['address'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];

        $result = $config->updateUser($id, $name, $address, $email, $phone);
        if($result)	{
            header("location:/index.php");
        } else {
            $response = "Sorry, is failed to update.";
        }
    }

    if(isset($_POST['change'])) {
        $pass = $user_info['password'];
        $current_password = md5($_POST['current_password']);
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if($pass != $current_password) {
            $response2 = "Current password is incorrect.";
        } else if($new_password != $confirm_password) {
            $response2 = "New password is incorrect.";
        } else {
            $change_done = $config->changePassword($id, $type, md5($new_password));
            if($change_done) {
                header("location:/index.php");
            } else {
                $response2 = "Sorry, is failed to update.";
            }
        }
    }
?>

<div class="register-div">
    <form method="post">
        <div class="row col-md-12">
            <div class="col-md-3 text-left mt-4">
                <label class="control-label"><?php echo $lng['useraccount']['Fullname'];?></label>
                <div class="agileits-main">
                    <i class="fas fa-signature"></i>
                    <input type="text" value="<?php echo $user_info['name']; ?>" required="" name="name">
                </div>
            </div>
            <div class="col-md-3 text-left mt-4">
                <label class="control-label"><?php echo $lng['useraccount']['Address'];?></label>
                <div class="agileits-main">
                    <i class="fas fa-map-marker-alt"></i>
                    <input type="text" value="<?php echo $user_info['address']; ?>" required="" name="address">
                </div>
            </div>
            <div class="col-md-3 text-left mt-4">
                <label class="control-label"><?php echo $lng['useraccount']['email'];?></label>
                <div class="agileits-main">
                    <i class="far fa-envelope"></i>
                    <input type="email" value="<?php echo $user_info['email']; ?>" required="" name="email">
                </div>
            </div>
            <div class="col-md-3 text-left mt-4">
                <label class="control-label"><?php echo $lng['useraccount']['Phone_Number'];?></label>
                <div class="agileits-main">
                    <i class="fas fa-phone"></i>
                    <input type="tel" value="<?php echo $user_info['phone']; ?>" required="" name="phone">
                </div>
            </div>
        </div>
        <div class="text-center submit mt-5">
            <input type="submit" class="btn btn-primary submit-fs btn-custom" value="<?php echo $lng['useraccount']['Update'];?>" name="update">
            <?php if($response != "") { ?>
                <p><label class="control-label mt-3"><?php echo $response; ?></label></p>
            <?php } ?>
            <a href="/index.php" class="btn btn-primary submit-fs btn-custom"><?php echo $lng['useraccount']['Cancel'];?></a>
        </div>
        <div class="clearfix"></div>
    </form>
    <form method="post">
        <div class="row col-md-12">
            <div class="col-md-3 text-left mt-4">
                <label class="control-label"><?php echo $lng['useraccount']['Current_Password'];?></label>
                <div class="agileits-main">
                    <i class="fas fa-unlock-alt"></i>
                    <input type="password" required="" name="current_password">
                </div>
            </div>
            <div class="col-md-3 text-left mt-4">
                <label class="control-label"><?php echo $lng['useraccount']['New_password'];?></label>
                <div class="agileits-main">
                    <i class="fas fa-unlock-alt"></i>
                    <input type="password" required="" name="new_password">
                </div>
            </div>
            <div class="col-md-3 text-left mt-4">
                <label class="control-label"><?php echo $lng['useraccount']['Confirm_New_Password'];?></label>
                <div class="agileits-main">
                    <i class="fas fa-unlock-alt"></i>
                    <input type="password" required="" name="confirm_password">
                </div>
            </div>
        </div>
        <div class="text-center submit mt-5">
            <input type="submit" class="btn btn-primary submit-fs btn-custom" value="<?php echo $lng['useraccount']['change_password'];?>" name="change">
            <?php if($response2 != "") { ?>
                <p><label class="control-label mt-3"><?php echo $response2; ?></label></p>
            <?php } ?>
        </div>
    </form>
</div>
