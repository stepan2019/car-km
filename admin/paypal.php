<?php 
    $response = "";
    
     $result = $config->getPaypalSetting();
     $currentData = $result->fetch_assoc();

    if(isset($_POST['Update'])) {
        $allow_paypal = $_POST['allow_paypal'];
        $api_username = $_POST['api_username'];
        $api_password = $_POST['api_password'];
        $api_signature = $_POST['api_signature'];
        $paypal_sandbox = $_POST['paypal_sandbox'];
        $note = $_POST['note'];
        $api_password = md5($_POST['api_password']);

        $result = $config->update_paypal($allow_paypal, $api_username, $api_password, $api_signature, $paypal_sandbox, $note);
        if($result) {
            $result = $config->getPaypalSetting();
            $currentData = $result->fetch_assoc();
            header("location:/admin/home.php?query=paypal");
        } else {
            $response = "Sorry, is failed to update";
        }
    }
?>

<div class="register-div">
    <form method="post">
        <div class="row col-md-12">
            <div class="col-md-12 text-left mt-4">
                <label class="control-label">Allow Paypal</label>
                <div class="agileits-main">
                    <!--<i class="fas fa-signature"></i>-->
                    <select name="allow_paypal" required="" id="allow_paypal" style="width:114%" value="">
                                <!--<option disabled selected>Status</option>-->
                                <option value="yes" <?php if( $currentData['allow_paypal'] == "yes") echo "selected";?>>Yes</option>
                                <option value="no" <?php if( $currentData['allow_paypal'] == "no") echo "selected";?>>No</option>
                            </select>
                </div>
            </div>
        </div>
        <div class="row col-md-12">
            <div class="col-md-12 text-left mt-4">
                <label class="control-label">PayPal Api Username</label>
                <div class="agileits-main">
                    <!--<i class="fas fa-map-marker-alt"></i>-->
                    <input type="text" placeholder="info_api1.ovreuropa.com" required="" name="api_username" value="<?php echo $currentData['api_username']?>">
                </div>
            </div>
        </div>
        <div class="row col-md-12">
            <div class="col-md-12 text-left mt-4">
                <label class="control-label">PayPal Api Password</label>
                <div class="agileits-main">
                    <!--<i class="fas fa-unlock-alt"></i>-->
                    <input type="password" placeholder="" required="" name="api_password">
                </div>
            </div>
        </div>
        <div class="row col-md-12">
            <div class="col-md-12 text-left mt-4">
                <label class="control-label">PayPal Api Signature</label>
                <div class="agileits-main">
                    <!--<i class="far fa-envelope"></i>-->
                    <input type="text" placeholder="" required="" name="api_signature" value="<?php echo $currentData['api_signature']?>">
                </div>
            </div>
        </div>
        <div class="row col-md-12">
            <div class="col-md-12 text-left mt-4">
                <label class="control-label">Paypal Sandbox</label>
                <div class="agileits-main">
                    <select name="paypal_sandbox" required="" id="paypal_sandbox" style="width:114%" value="<?php echo $currentData['paypal_sandbox']?>">
                                <!--<option disabled selected>Status</option>-->
                                <option value="yes" <?php if( $currentData['paypal_sandbox'] == "yes") echo "selected";?>>Yes</option>
                                <option value="no" <?php if( $currentData['paypal_sandbox'] == "no") echo "selected";?>>No</option>
                            </select>
                </div>
            </div>
        </div>
        <div class="row col-md-12">
            <div class="col-md-12 text-left mt-4">
                <!--<label class="control-label">PayPal Sandbox</label>-->
                <div class="agileits-main">
                    <!--<i class="fas fa-phone"></i>-->
                    <textarea type="text" placeholder="" required="" name="note" style="width:114%;height:50%"> <?php echo $currentData['note']?>
                    </textarea>
                </div>
            </div>
        </div>
        <div class="row col-md-12">
            <div class="col-md-12 text-left mt-4">
                <div class="submit">
                    <input type="submit" class="btn btn-primary submit-fs btn-custom" value="Update" name="Update">
                </div>
            </div>
        </div>
        <div class="clearfix"></div>
    </form>
</div>