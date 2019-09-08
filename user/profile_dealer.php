<?php 
    $response = "";
    $response2 = "";

    $id = $user_info['id'];

    if(isset($_POST['update'])) {
        $name = $_POST['name'];
        $address = $_POST['address'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $company = $_POST['company'];
        $website = $_POST['website'];

        $result = $config->updateDealer($id, $name, $address, $email, $phone, $company, $website);
        if($result) {
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
                <label class="control-label">FullName</label>
                <div class="agileits-main">
                    <i class="fas fa-signature"></i>
                    <input type="text" value="<?php echo $user_info['name']; ?>" required="" name="name">
                </div>
            </div>
            <div class="col-md-3 text-left mt-4">
                <label class="control-label">Address</label>
                <div class="agileits-main">
                    <i class="fas fa-map-marker-alt"></i>
                    <input type="text" value="<?php echo $user_info['address']; ?>" required="" name="address">
                </div>
            </div>
            <div class="col-md-3 text-left mt-4">
                <label class="control-label">Email</label>
                <div class="agileits-main">
                    <i class="far fa-envelope"></i>
                    <input type="email" value="<?php echo $user_info['email']; ?>" required="" name="email">
                </div>
            </div>
            <div class="col-md-3 text-left mt-4">
                <label class="control-label">Phone Number</label>
                <div class="agileits-main">
                    <i class="fas fa-phone"></i>
                    <input type="tel" value="<?php echo $user_info['phone']; ?>" required="" name="phone">
                </div>
            </div>
            <div class="col-md-3 text-left mt-4">
                <label class="control-label">Company</label>
                <div class="agileits-main">
                    <i class="far fa-building"></i>
                    <input type="text" value="<?php echo $user_info['company']; ?>" required="" name="company">
                </div>
            </div>
            <div class="col-md-3 text-left mt-4">
                <label class="control-label">Website</label>
                <div class="agileits-main">
                    <i class="fas fa-network-wired"></i>
                    <input type="url" value="<?php echo $user_info['website']; ?>" required="" name="website">
                </div>
            </div>
        </div>
        <div class="text-center submit mt-5">
            <input type="submit" class="btn btn-primary submit-fs btn-custom" value="Update" name="update">
            <?php if($response != "") { ?>
                <p><label class="control-label mt-3"><?php echo $response; ?></label></p>
            <?php } ?>
            <a href="/index.php" class="btn btn-primary submit-fs btn-custom">Cancel</a>
        </div>
        <div class="clearfix"></div>
    </form>
    <form method="post">
        <div class="row col-md-12">
            <div class="col-md-3 text-left mt-4">
                <label class="control-label">Current Password</label>
                <div class="agileits-main">
                    <i class="fas fa-unlock-alt"></i>
                    <input type="password" required="" name="current_password">
                </div>
            </div>
            <div class="col-md-3 text-left mt-4">
                <label class="control-label">New Password</label>
                <div class="agileits-main">
                    <i class="fas fa-unlock-alt"></i>
                    <input type="password" required="" name="new_password">
                </div>
            </div>
            <div class="col-md-3 text-left mt-4">
                <label class="control-label">Confirm New Password</label>
                <div class="agileits-main">
                    <i class="fas fa-unlock-alt"></i>
                    <input type="password" required="" name="confirm_password">
                </div>
            </div>
        </div>
        <div class="text-center submit mt-5">
            <input type="submit" class="btn btn-primary submit-fs btn-custom" value="Change" name="change">
            <?php if($response2 != "") { ?>
                <p><label class="control-label mt-3"><?php echo $response2; ?></label></p>
            <?php } ?>
        </div>
    </form>
</div>