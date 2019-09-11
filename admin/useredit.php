<?php 
    $id = $_GET['id'];
    $result = $config->getUserById($id);
    $user = $result->fetch_assoc();
    
    if(isset($_POST['update'])) {
        $name = $_POST['name'];
        $password = md5($_POST['password']);
        $address = $_POST['address'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];

        $update_done = $config->updateUser($id, $name, $address, $email, $phone, $password);
        if($update_done == true) {
            echo "<script>window.location='home.php?query=userlist';</script>";
        } else {
            echo "<script>alert('Cant update Information');</script>";
        }
    }
?>

<div class="outter-wp">
    <div class="graph-visual tables-main">
        <span class="inner-tittle">
            Edit User
        </span>
        <hr>
        <div class="form-body">
            <form class="form-horizontal" method="post">
                <div class="col-md-5">
                    <div class="form-group"> <label for="inputEmail3" class="col-sm-4 control-label">Name</label>
                        <div class="col-sm-8">
                            <div class="agileits-main">
                                <i class="fas fa-signature"></i>
                                <input type="text" id="inputEmail3" name="name"
                                    value="<?php echo $user['name']; ?>" required="">
                            </div>
                        </div>
                    </div>
                    <div class="form-group"> <label for="inputEmail3" class="col-sm-4 control-label">Password</label>
                        <div class="col-sm-8">
                            <div class="agileits-main">
                                <i class="fas fa-signature"></i>
                                <input type="password" id="inputEmail3" name="password"
                                       value="<?php echo $user['password']; ?>" required="">
                            </div>
                        </div>
                    </div>
                    <div class="form-group"> <label for="address" class="col-sm-4 control-label">Address</label>
                        <div class="col-sm-8">
                            <div class="agileits-main">
                                <i class="fas fa-map-marker-alt"></i>
                                <input type="text" name="address"
                                    value="<?php echo $user['address']; ?>" required="">
                            </div>
                        </div>
                    </div>
                    <div class="form-group"> <label for="address" class="col-sm-4 control-label">Email</label>
                        <div class="col-sm-8">
                            <div class="agileits-main">
                                <i class="fas fa-envelope"></i>
                                <input type="text" name="email"
                                    value="<?php echo $user['email']; ?>" required="">
                            </div>
                        </div>
                    </div>
                    <div class="form-group"> <label for="address" class="col-sm-4 control-label">Phone</label>
                        <div class="col-sm-8">
                            <div class="agileits-main">
                                <i class="fas fa-phone"></i>
                                <input type="text" name="phone"
                                    value="<?php echo $user['phone']; ?>" required="">
                            </div>
                        </div>
                    </div>
                    <div class="text-center" style="margin-top:30px;">
                        <button type="submit" class="btn btn-primary submit-fs btn-custom" name="update" style="font-size:1.2em;">Update</button>
                        <a href="home.php?query=userlist" class="btn btn-primary submit-fs btn-custom" style="font-size:1.2em;">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
