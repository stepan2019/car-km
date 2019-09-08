<?php
	$resProfile = "";
	$resPassword = "";
	
	$username = $_SESSION['admin'];
    $result = $config->getAdmin($username);
    $admin_info = $result->fetch_assoc();
    
    if(isset($_POST['set'])) {
	    $username = $_POST['username'];
	    $email = $_POST['email'];
	    
    	$update_done = $config->update_profile($username, $email);
        if($update_done) {
            $resProfile = "Profile has been updated successfully.";
        } else {
            $resProfile = "Cannot update profile.";
        }
	}

	if(isset($_POST['update'])) {
		$pass = $admin_info['password'];
	    $current_password = md5($_POST['current_password']);
	    $new_password = $_POST['new_password'];
	    $confirm_password = $_POST['confirm_password'];

	    if($pass != $current_password) {
	        $resPassword = "Current password is incorrect.";
	    } else if($new_password != $confirm_password) {
	        $resPassword = "New password is incorrect.";
	    } else {
	    	$update_done = $config->update_password($admin_info['username'], md5($new_password));
	        if($update_done) {
	            $resPassword = "Password has been updated successfully.";
	        } else {
	            $resPassword = "Cant update password.";
	        }
	    }
	}
?>
<div>
	<form method="post">
		<div class="form-group"> <label for="inputEmail3" class="col-sm-4 control-label">Username</label>
	        <div class="col-sm-8">
	            <div class="agileits-main">
	                <i class="far fa-user" style="left: 2%;"></i>
	                <input type="text" id="inputEmail3" name="username"
	                    value="<?php echo $admin_info['username']; ?>" required="">
	            </div>
	        </div>
	    </div>
	    <div class="form-group"> <label for="inputEmail3" class="col-sm-4 control-label">Email</label>
	        <div class="col-sm-8">
	            <div class="agileits-main">
	                <i class="far fa-envelope" style="left: 2%;"></i>
	                <input type="text" id="inputEmail3" name="email"
	                    value="<?php echo $admin_info['email']; ?>" required="">
	            </div>
	        </div>
	    </div>
	    <div class="col-md-12 mt-5">
	    	<button type="submit" class="btn btn-primary submit-fs btn-custom" name="set">Set</button>
	    	<?php if($resProfile != "") { ?>
                <p><label class="control-label mt-3"><?php echo $resProfile; ?></label></p>
            <?php } ?>
	    </div>
	</form>

	<hr>

	<form method="post">
	    <div class="row col-md-12">
	        <div class="col-md-4 form-group1 group-mail">
	            <label class="control-label">Current Password</label>
	            <div class="agileits-main">
	                <i class="fas fa-unlock-alt"></i>
	                <input type="password" required="" name="current_password">
	            </div>
	        </div>
	    </div>
	    <div class="row col-md-12">
	        <div class="col-md-4 form-group1 group-mail">
	            <label class="control-label">New Password</label>
	            <div class="agileits-main">
	                <i class="fas fa-unlock-alt"></i>
	                <input type="password" required="" name="new_password">
	            </div>
	        </div>
	        <div class="col-md-4 form-group1 group-mail">
	            <label class="control-label">Confirm New Password</label>
	            <div class="agileits-main">
	                <i class="fas fa-unlock-alt"></i>
	                <input type="password" required="" name="confirm_password">
	            </div>
	        </div>
	    </div>
	    <div class="col-md-12 mt-5">
	    	<button type="submit" class="btn btn-primary submit-fs btn-custom" name="update">Update</button>
	    	<?php if($resPassword != "") { ?>
                <p><label class="control-label mt-3"><?php echo $resPassword; ?></label></p>
            <?php } ?>
	    </div>
    </form>
</div>