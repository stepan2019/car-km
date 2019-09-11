<?php

$vin = $_POST['vin'];
$plate = $_POST['plate'];
$payer_email = $_POST['email'];
include "../include/include.php";
global $crt_lang_code;
global $text_direction;
global $lng;
//echo $vin;exit;


include "../setting/config.php";

$result = $config->getCoupon();
$couponData = $result->fetch_All();
//var_dump($couponData);exit;
if (isset($_POST['save_code'])) {
    $code = $_POST['code'];
    //$amount = $_POST['amount'];
    $amount = 1;
    $allow_usage = 0;
    foreach ($couponData as $coupon) {
        if ($coupon[1] == $code) {
            $allow_usage = $coupon[2];
        }
    }
    if ($allow_usage < $amount) {
        echo "<script>alert('Your copuncode is expaire');</script>";
    } else {
        $result1 = $config->update_coupon($code, $amount);
        $result = $config->add_coupon_history($code, $amount, $vin, $payer_email);
        if ($result) {

            //  $link = "https://www.car-km.com/admin/pdf.php?query=".$vin;
            //  echo "<script>window.open('.$link.', '_blank')</script>";
            // $result = $config->getCoupon();
            // $couponData = $result->fetch_All();
        } else {
            echo "<script>alert('Sorry ...');</script>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $crt_lang_code;?>">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" href=" ">
    <title>Car Registration - Add Vehicle Km</title>

    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/custom.css" rel="stylesheet">

    <link href="../css/loaders.css" rel="stylesheet">
    <link href="../css/swiper.min.css" rel="stylesheet">
    <link href="../css/animate.min.css" rel="stylesheet">
    <link href="../css/nivo-lightbox.css" rel="stylesheet">
    <link href="../css/nivo_themes/default/default.css" rel="stylesheet">

    <link href="../css/dataTables.1.9.4.css" rel="stylesheet">

    <link rel="stylesheet" href="/css/all.css" crossorigin="anonymous">
    <?php
    if ($text_direction == 'rtl') {
        ?>
        <link href="/template/css/style-rtl.css" rel="stylesheet">
        <?php
    } else {
        ?>
        <link href="/template/css/style.css" rel="stylesheet">
    <?php } ?>
    <style type="text/css">
        .modal-dialog {
            max-width: 1450px;
        }

        .main_container {
            height: -webkit-fill-available;
        }

        .info-panel {
            padding-top: 0px !important;
        }

        .submit-fs {
            margin-top: 0.5rem !important;
        }
    </style>
    <script>
        exdate=new Date();
        exdate.setDate(exdate.getDate() + 365);

    </script>
</head>
<body style="background-color: #ece4b7;" <?php if (isset($_POST['save_code'])) { ?> onload="document.forms['pdf_form'].submit();" <?php } ?>>

<?php
include "../template/header.php";
?>
<div class="main_container">
    <div class="get_coupon" style="margin-top:150px;">

        <form method="post" class="form-horizontal" role="form">
            <div class="row col-md-12">
                <div class="col-md-3"></div>
                <div class="col-md-2">
                    <label class="control-label" style="font-size:20px"><?php echo $lng['Payment']['Copun_code'];?></label>
                </div>
                <div class="col-md-3 text-left mt-1">
                    <div class="agileits-main">
                        <!--<i class="fas fa-list-ol"></i>-->
                        <input type="text" required="" name="code" id="code" style="width:100%" value="">
                    </div>
                </div>


                <!--<div class="col-md-2"> -->
                <!--    <label class="control-label" style="font-size:20px">Amount</label>-->
                <!--</div>-->
                <!--<div class="col-md-3 text-left mt-1">-->
                <!--    <div class="agileits-main">-->
                <!--<i class="fas fa-list-ol"></i>-->
                <!--        <input type="number" required="" name="amount" id="amount" style="width:100%" value="">-->
                <!--    </div>-->
                <!--</div>-->

                <div class="col-md-2">
                    <!--<div class="text-center submit mt-5">-->
                    <button type="submit" class="btn btn-primary submit-fs btn-custom" name="save_code" id="save_code">
                        <?php echo $lng['Payment']['Submit'];?>
                    </button>
                    <!--</div>-->
                </div>
            </div>
            <input type="hidden" name="vin" value="<?php echo $_POST['vin']; ?>"/>
            <input type="hidden" name="plate" value="<?php echo $_POST['plate']; ?>"/>
            <input type="hidden" name="email" value="<?php echo $_POST['email']; ?>"/>
        </form>
        <form action="pdf.php" method="post" id="pdf_form" target="_self" name="pdf_form">
            <input type="hidden" name="vin" value="<?php echo $_POST['vin']; ?>"/>
            <input type="hidden" name="plate" value="<?php echo $_POST['plate']; ?>"/>
            <input type="hidden" name="email" value="<?php echo $_POST['email']; ?>"/>
        </form>
    </div>


</div>
<?php
include "../template/information.php";
?>

<?php
include "../template/footer.php";
?>

<script src="../js/jquery.min.js"></script>
<script src="../js/bootstrap.min.js"></script>
<script src="../js/dataTables.1.9.4.js"></script>
<script src='../js/select2/select2.min.js' type='text/javascript'></script>
<script>
    $('#wang-dataTable').dataTable();
</script>
