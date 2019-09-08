<?php 
    $id = $_GET['id'];
    $result = $config->getDealerById($id);
    $user = $result->fetch_assoc();
    
    if(isset($_POST['update'])) {
        $name = $_POST['name'];
        $address = $_POST['address'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $company = $_POST['company'];
        $website = $_POST['website'];

        $update_done = $config->updateDealer($id, $name, $address, $email, $phone, $company, $website);
        if($update_done == true) {
            echo "<script>window.location='home.php?query=dealerlist';</script>";
        } else {
            echo "<script>alert('Cant update Information');</script>";
        }
    }
?>

<div class="outter-wp">
    <div class="graph-visual tables-main">
        <span class="inner-tittle">
            Edit Dealer
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
                    <div class="form-group"> <label for="address" class="col-sm-4 control-label">Company</label>
                        <div class="col-sm-8">
                            <div class="agileits-main">
                                <i class="far fa-building"></i>
                                <input type="text" name="company"
                                    value="<?php echo $user['company']; ?>" required="">
                            </div>
                        </div>
                    </div>
                    <div class="form-group"> <label for="address" class="col-sm-4 control-label">Website</label>
                        <div class="col-sm-8">
                            <div class="agileits-main">
                                <i class="fas fa-network-wired"></i>
                                <input type="text" name="website"
                                    value="<?php echo $user['website']; ?>" required="">
                            </div>
                        </div>
                    </div>
                    <div class="text-center" style="margin-top:30px;">
                        <button type="submit" class="btn btn-primary submit-fs btn-custom" name="update" style="font-size:1.2em;">Update</button>
                        <a href="home.php?query=dealerlist" class="btn btn-primary submit-fs btn-custom" style="font-size:1.2em;">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>