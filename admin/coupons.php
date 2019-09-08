<?php

    $result = $config->getCoupon();
    $couponData = $result->fetch_All();
    if(isset($_POST['save_code'])) {
        $code = $_POST['code'];
        $allow_usage = $_POST['allow_usage'];
        $temp=0;
        
        foreach($couponData as $coupon){
            if($coupon[1] == $code){
                $temp = 1;
                break;
            }
        }
        
        if($temp == 1){
            echo "<script>alert('Sorry! Same coupon exist ...');</script>";
        }else{
    
            
            $result = $config->add_coupon($code, $allow_usage);
            if($result) {
                // header("location:home.php");
                $result = $config->getCoupon();
                $couponData = $result->fetch_assoc();
            } else {
                echo "<script>alert('Sorry ...');</script>";
            }
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
                    <input type="text" required="" name="code" id="code" style="width:100%" value="">
                </div>
            </div>
        </div>
        
        <div class="row col-md-12">
            
            <label class="control-label" style="font-size:30px">Allow Usage</labe
            <div class="col-md-1"> </div>
            <div class="col-md-7 text-left mt-1">
                <div class="agileits-main">
                    <!--<i class="fas fa-list-ol"></i>-->
                    <input type="number" required="" name="allow_usage" id="allow_usage" style="width:100%" value="">
                </div>
            </div>
        </div>
        
        
        <div class="text-center submit mt-5">
            <button type="submit" class="btn btn-primary submit-fs btn-custom" name="save_code" id="save_code">save</button>
        </div>
    </form>
</div>
