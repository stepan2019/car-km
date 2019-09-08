<?php 
    $response = "";
    $id = $_GET['id'];
    $result = $config->getCouponById($id);
    $coupon = $result->fetch_assoc();
    //var_dump($coupon);exit;
    if(isset($_POST['save_code'])) {
        $code = $_POST['code'];
        $allow_usage = $_POST['allow_usage'];
            
            $result = $config->edit_coupon($code, $allow_usage);
            //var_dump($result);exit;
            if($result) {
                // header("location:home.php");
                // $result = $config->getCoupon();
                // $couponData = $result->fetch_assoc();
                echo "<script>window.location = 'home.php?query=coupons_list';</script>";
            } else {
                echo "<script>alert('Sorry ...');</script>";
            }
    }
?>




<div>
    
    <form method="post" class="form-horizontal" role="form">
        
        <div class="row col-md-12">
            
            <label class="control-label" style="font-size:30px">Coupon Code</labe
            <div class="col-md-1"> </div>
            <div class="col-md-7 text-left mt-1">
                <div class="agileits-main">
                    <!--<i class="fas fa-list-ol"></i>-->
                    <input type="text" required="" name="code" id="code" style="width:100%" value="<?php echo $coupon["code"]; ?>">
                </div>
            </div>
        </div>
        
        <div class="row col-md-12">
            
            <label class="control-label" style="font-size:30px">Allow Usage</labe
            <div class="col-md-1"> </div>
            <div class="col-md-7 text-left mt-1">
                <div class="agileits-main">
                    <!--<i class="fas fa-list-ol"></i>-->
                    <input type="number" required="" name="allow_usage" id="allow_usage" style="width:100%" value="<?php echo $coupon["allow_usage"]; ?>">
                </div>
            </div>
        </div>
        
        
        <div class="text-center submit mt-5">
            <button type="submit" class="btn btn-primary submit-fs btn-custom" name="save_code" id="save_code">save</button>
        </div>
    </form>
</div>