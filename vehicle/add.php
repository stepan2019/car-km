<?php

include "../setting/config.php";

include "../include/include.php";
require_once '../classes/validator.php';
require_once '../classes/mails.php';
require_once '../classes/settings.php';
require_once $config_abs_path . "/classes/mail_templates.php";

global $mail_setting;
global $crt_lang_code;
global $lng;
global $text_direction;
$setting = new settings();
$mail_setting = $setting->getMailSettings();
$paypal_result = $config->getPaypalSetting();
$paypal_setting = $paypal_result->fetch_assoc();


session_start();

if (!@$_SESSION['user']) {
    header("location:/user/login.php");
}
$result = $config->getVehicleList();
$dbdata = array();

while ($row = $result->fetch_assoc()) {
    $dbdata[] = $row;
}
$result = $config->getProvinList();
$provdata = array();

while ($row = $result->fetch_assoc()) {
    $provdata[] = $row;
}
$email = @$_SESSION['user'];
$type = @$_SESSION['type'];
$result = $config->getAddress($email, $type);
$user_info = $result->fetch_assoc();
$user_id = $user_info['id'];

$result = $config->getInformationContent();
$information = $result->fetch_assoc();

$response = "";
$response2 = "";
$response3 = "";

$resMake = "";
$resModel = "";
$resBuild = "";
$resCrash = "";

if (isset($_POST['add_car'])) {
    $after_fix = $_POST['after_fix'];
    $plate = $_POST['plate'] . '/' . $after_fix;
    $pre_fix = $_POST['plate'];
    $vin = $_POST['vin'];

    if (isset($_POST['make']) && isset($_POST['model']) && isset($_POST['year']) && isset($_POST['crash'])) {
        $make = $_POST['make'];
        $model = $_POST['model'];
        $year = $_POST['year'];
        $km = $_POST['km'];
        $date = $_POST['date'];
        $crash = $_POST['crash'];

        $front = "";
        $back = "";
        $lefty = "";
        $righty = "";
        $total = "";

        if (isset($_POST['front']))
            $front = $_POST['front'];
        if (isset($_POST['back']))
            $back = $_POST['back'];
        if (isset($_POST['lefty']))
            $lefty = $_POST['lefty'];
        if (isset($_POST['righty']))
            $righty = $_POST['righty'];
        if (isset($_POST['total']))
            $total = $_POST['total'];

        if ($crash == "no") {
            $front = "";
            $back = "";
            $lefty = "";
            $righty = "";
            $total = "";
        }

        if ($crash == "yes" && $front == "" && $back == "" && $lefty == "" && $righty == "" && $total == "") {
            $resCrash = $lng['general']['select_crashed_car'];
        } else {
            $result = $config->add_vehicle($user_id, $type, $plate, $pre_fix, $after_fix, $vin, $make, $model, $year, $km, $date, $crash, $front, $back, $lefty, $righty, $total);
            if ($result) {
                // $response = "Successfully to add";
                global $config_live_site;
                global $mail_setting;
                // add activation code to db record
                $mail2send = new mails();
                $mail2send->init($email, 'Carpass');
                $mail2send->to = $mail_setting['username'];
                $mail2send->to_name = 'Car register';
                $mail2send->setSubject(cleanStr('Hello admin there is vehicle ADD to carpass database'));
                $msg = nl2br(cleanStr('<div>
                                        <p> with plate number' . $plate . ' and vin number ' . $vin . '</p>
                                        <p> Please check the vehicle . </p>
                                    </div><div>User Email : ' . $email . '</div>')) . '';
                $mail2send->setMessage($msg);
                $is_sendMail = $mail2send->send();
                if (!$is_sendMail) {
                    $response = $mail2send->send_error;
                }
                ?>
                <script src="../js/jquery.min.js"></script>
                <script>
                    $(function () {
                        $('#pdf_form > input').val("<?php echo $vin; ?>");
                        $("#historyPDF").modal();
                        $('#paySubBtn').click(function (e) {
                            e.preventDefault();
                            document.forms[0].submit();
                            // document.forms[2].submit();
                        });
                    });
                </script>

                <div class="modal fade" id="historyPDF" tabindex="-1" role="dialog" aria-labelledby="modalTitle"
                     aria-hidden="true">
                    <div class="modal-dialog" role="document" style="max-width: 800px;margin: 200px auto;">
                        <div class="modal-content" style="background-color: #1d4964; border-radius: 4px;">
                            <div class="modal-body text-center">
                                <p class="dashboard-txt"><?php echo $lng['Payment']['Thank_you']; ?></p>
                                <p class="dashboard-txt"><?php echo $lng['Payment']['please_click']; ?></p>
                                <?php
                                $result = $config->getPrice();
                                $currentPrice = $result->fetch_assoc();
                                $val = $currentPrice['price'];

                                if ($val == 0) {
                                    ?>
                                    <button class="btn btn-primary submit-fs btn-custom"
                                            onclick="modalClose()"><?php echo $lng['Payment']['generate_report']; ?>
                                    </button>
                                    <?php
                                } else {
                                    ?>
                                    <form class="paypal" action="payments.php" method="post" id="paypal_form"
                                          target="_self">
                                        <input type="hidden" name="vin" id="vin" value="<?php echo $vin; ?>"/>
                                        <input type="hidden" name="plate" id="plate" value="<?php echo $plate; ?>"/>
                                        <input type="hidden" name="payer_email" id="payer_email"
                                               value="<?php echo $user_info['email'] ?>"/>
                                        <input type="hidden" name="itemAmount" value="<?php echo $val; ?>"/>

                                        <input type="hidden" name="cmd" value="_xclick"/>
                                        <input type="hidden" name="lc" value="UK"/>
                                        <input type="hidden" name="bn"
                                               value="PP-BuyNowBF:btn_buynow_LG.gif:NonHostedGuest"/>
                                        <input type="hidden" name="first_name" value="Customer's First Name"/>
                                        <input type="hidden" name="last_name" value="Customer's Last Name"/>
                                        <input type="hidden" name="item_number" value="123456" / >
                                        <?php
                                        if ($paypal_setting['allow_paypal'] == 'yes') {
                                            ?>
                                            <input type="button" name="paySubBtn" value="PayPal" id="paySubBtn"
                                                   class="btn btn-primary submit-fs btn-custom"/>
                                        <?php } ?>
                                        <input type="button" name="coupon" value="<?php echo $lng['Payment']['coupon'];?>" id="coupon_btn"
                                               class="btn btn-primary submit-fs btn-custom"/>
                                    </form>

                                <?php } ?>
                                <script type="text/javascript">
                                    function modalClose() {
                                        $("#historyPDF").modal('hide');

                                        document.forms[1].submit();
                                        return true;
                                    }

                                </script>
                                <p class="dashboard-txt"><?php echo $lng['Payment']['after']; ?></p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-primary btn-custom"
                                        data-dismiss="modal"><?php echo $lng['Payment']['Close']; ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
            } else {
                $response = "Sorry, is failed to add";
            }
        }
    } else if (!isset($_POST['make'])) {
        $resMake = "You must select the Car Make";
    } else if (!isset($_POST['model'])) {
        $resModel = "You must select the Car Model";
    } else if (!isset($_POST['year'])) {
        $resBuild = "You must select the Car Build Year";
    } else if (!isset($_POST['crash'])) {
        $resCrash = "You must select the Car Crash Status";
    }
}

?>

<!DOCTYPE html>
<html lang="<?php echo $crt_lang_code; ?>">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" href=" ">
    <title>Car Registration - Add Vehicle Km</title>

    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <?php
    if ($text_direction == 'rtl') {
        ?>
        <link href="/css/custom_rtl.css" rel="stylesheet">
        <?php
    } else {
        ?>
        <link href="/css/custom.css" rel="stylesheet">
    <?php } ?>

    <link href="../css/loaders.css" rel="stylesheet">
    <link href="../css/swiper.min.css" rel="stylesheet">
    <link href="../css/animate.min.css" rel="stylesheet">
    <link href="../css/nivo-lightbox.css" rel="stylesheet">
    <link href="../css/nivo_themes/default/default.css" rel="stylesheet">


    <link rel="stylesheet" href="/css/all.css" crossorigin="anonymous">
    <style type="text/css">
        .modal-dialog {
            max-width: 1450px;
        }

        #car_plate {
            text-align: center;
            font-size: 24px;
            font-weight: bolder;
            padding: 0 0 0 0;
        }
    </style>
    <script>
        exdate = new Date();
        exdate.setDate(exdate.getDate() + 365);

    </script>
</head>
<body>

<?php
include "../template/header.php";
?>

<div class="contact-sec info-panel parallax-section" style="background-size: cover; background-repeat: no-repeat;">
    <div class="container mt-3">
        <form method="post">
            <div class="row col-md-12">
                <div class="col-md-1"></div>
                <div class="col-md-4 mt-1">
                    <label class="control-label"><?php echo $lng['general']['Plate_Number']; ?></label>
                    <div class="only-plate row">
                        <!--<i class="fas fa-list-ol"></i>-->
                        <input type="text" required="" name="plate" id="car_plate"
                               style="text-transform: uppercase;border:solid 1px black;height: 45px;"
                               onkeyup="this.value = this.value.toUpperCase();" class="col-md-10">
                    </div>
                    <div class="row">
                        <select name="after_fix" required="" id="add_provin" class="col-md-6 col-sm-6"
                                style="border:solid 1px black;font-weight:bolder;font-size:25px;height:45px;">
                            <option disabled selected></option>

                            <?php
                            $getProvinList = $config->getProvinList();
                            while ($getProvinList_fetch = $getProvinList->fetch_assoc()) {
                                ?>
                                <option value="<?php echo $getProvinList_fetch[$crt_lang_code]; ?>"
                                        style="font-size:25px;">
                                    <?php echo $getProvinList_fetch[$crt_lang_code]; ?>
                                </option>
                                <?php
                            }
                            ?>
                        </select>
                        <div style="font-size:25px;border:solid 1px black;background:white;text-align: center;font-weight:bolder;"
                             class="col-md-4 col-sm-4">العراق
                        </div>
                    </div>
                </div>
                <div class="col-md-1"></div>
                <div class="col-md-5 mt-1">
                    <label class="control-label"><?php echo $lng['general']['Vin']; ?></label>
                    <div class="agileits-main">
                        <i class="fas fa-barcode"></i>
                        <input type="text" required="" name="vin" id="car_vin"
                               onkeyup="this.value = this.value.toUpperCase();">
                        <a href="#vin_help" data-toggle="modal"><img src="../img/vin-help.png" alt="vin help"></a>
                    </div>
                </div>
                <div class="col-md-1"></div>
            </div>
            <div class="row col-md-12">
                <div class="col-md-1"></div>
                <div class="col-md-3 mt-4">
                    <label class="control-label"><?php echo $lng['general']['Make']; ?></label>
                    <div class="agileits-main">
                        <i class="far fa-building"></i>
                        <select name="make" required="" id="add_car_make">
                            <option disabled selected><?php echo $lng['general']['Make']; ?></option>

                            <?php
                            $getMakeList = $config->getMakeList();
                            while ($getMakeList_fetch = $getMakeList->fetch_assoc()) {
                                ?>
                                <option value="<?php echo $getMakeList_fetch['name']; ?>">
                                    <?php echo $getMakeList_fetch['name']; ?>
                                </option>
                                <?php
                            }
                            ?>
                        </select>
                    </div>
                    <?php if ($resMake != "") { ?>
                        <p><label class="control-label mt-3"><?php echo $resMake; ?></label></p>
                    <?php } ?>
                </div>
                <div class="col-md-4 mt-4">
                    <label class="control-label"><?php echo $lng['general']['Model']; ?></label>
                    <div class="agileits-main">
                        <i class="fas fa-cogs"></i>
                        <select name="model" required="" id="add_car_model">
                            <option disabled selected><?php echo $lng['general']['Model']; ?></option>
                        </select>
                    </div>
                    <?php if ($resModel != "") { ?>
                        <p><label class="control-label mt-3"><?php echo $resModel; ?></label></p>
                    <?php } ?>
                </div>
                <div class="col-md-3 mt-4">
                    <label class="control-label"><?php echo $lng['general']['Build_Year']; ?></label>
                    <div class="agileits-main">
                        <i class="far fa-clock"></i>
                        <select name="year" required="" id="car_year">
                            <option disabled selected><?php echo $lng['general']['Build_Year']; ?></option>
                            <?php
                            $start_year = 1950;
                            foreach (range(date('Y'), $start_year) as $x) {
                                echo '<option value="' . $x . '">' . $x . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <?php if ($resBuild != "") { ?>
                        <p><label class="control-label mt-3"><?php echo $resBuild; ?></label></p>
                    <?php } ?>
                </div>
                <div class="col-md-1"></div>
                <div class="col-md-1"></div>
                <div class="col-md-5 mt-4">
                    <label class="control-label"><?php echo $lng['general']['KM']; ?></label>
                    <div class="agileits-main">
                        <i class="fas fa-running"></i>
                        <input type="text" name="km" step="any" required="" id="car_km" min="1" max="1000"
                               maxlength="7">
                    </div>
                </div>
                <div class="col-md-5 mt-4">
                    <label class="control-label"><?php echo $lng['general']['date']; ?></label>
                    <div class="agileits-main">
                        <i class="far fa-calendar-alt"></i>
                        <input type="date" value="<?php echo date('Y-m-d'); ?>" disabled>
                        <input type="hidden" name="date" value="<?php echo date('Y-m-d'); ?>" id="car_date">
                    </div>
                </div>
                <div class="col-md-1"></div>
            </div>
            <div class="row col-md-12">
                <div class="col-md-1"></div>
                <div class="col-md-3 mt-4">
                    <label class="control-label"><?php echo $lng['general']['Crashed ?']; ?></label>
                    <div class="agileits-main">
                        <i class="fas fa-car-crash"></i>
                        <select name="crash" required="" id="car_crash">
                            <option disabled selected><?php echo $lng['general']['Status']; ?></option>
                            <option value="yes"><?php echo $lng['general']['Yes']; ?></option>
                            <option value="no"><?php echo $lng['general']['No']; ?></option>
                        </select>
                    </div>
                    <?php if ($resCrash != "") { ?>
                        <p><label class="control-label mt-3"><?php echo $resCrash; ?></label></p>
                    <?php } ?>
                </div>
                <div class="col-md-1"></div>
            </div>
            <div class="row col-md-12 car_status">
                <div class="col-md-1"></div>
                <div class="col-md-1 check-border">
                    <input type="checkbox" name="front" value="yes"> <?php echo $lng['general']['Front']; ?>
                </div>
                <div class="col-md-1 check-border">
                    <input type="checkbox" name="back" value="yes"> <?php echo $lng['general']['Back']; ?>
                </div>
                <div class="col-md-1 check-border">
                    <input type="checkbox" name="lefty" value="yes"> <?php echo $lng['general']['Left']; ?>
                </div>
                <div class="col-md-1 check-border">
                    <input type="checkbox" name="righty" value="yes"> <?php echo $lng['general']['Right']; ?>
                </div>
                <div class="col-md-2 check-border">
                    <input type="checkbox" name="total" value="yes"> <?php echo $lng['general']['Total_Loss']; ?>
                </div>
                <div class="col-md-1"></div>
            </div>

            <div class="text-center submit mt-5">
                <button type="submit" class="btn btn-primary submit-fs btn-custom"
                        name="add_car"><?php echo $lng['general']['submit']; ?></button>
                <button type="reset"
                        class="btn btn-primary submit-fs btn-custom"><?php echo $lng['general']['Reset']; ?></button>
                <?php if ($response != "") { ?>
                    <p><label class="control-label mt-3"><?php echo $response; ?></label></p>
                <?php } ?>
            </div>
        </form>
        <form action="pdf.php" method="post" id="pdf_form" target="_self" name="pdf_form">
            <input type="hidden" name="vin" value="<?php echo $vin; ?>"/>
            <input type="hidden" name="plate" value="<?php echo $plate; ?>"/>
            <input type="hidden" name="email" value="<?php echo $user_info['email']; ?>"/>
        </form>
        <form action="invoice.php" method="post" id="invoice_form" target="_blank" name="invoice_form">
            <input type="hidden" name="vin" id="cupon_vin" value="<?php echo $vin; ?>"/>
            <input type="hidden" name="plate" value="<?php echo $plate; ?>"/>
            <input type="hidden" name="email" value="<?php echo $user_info['email']; ?>"/>
        </form>
        <form action="get-coupon.php" method="post" id="coupon_form" target="_self" name="coupon_form">
            <input type="hidden" name="vin" id="cupon_vin" value="<?php echo $vin; ?>"/>
            <input type="hidden" name="plate" value="<?php echo $plate; ?>"/>
            <input type="hidden" name="email" value="<?php echo $user_info['email']; ?>"/>
        </form>
        <hr>

        <div class="extra-field">
            <div class="text-center text-black mb-4"><?php echo $lng['general']['Request_make_model']; ?>
            </div>
            <div class="text-center">
                <a href="../template/contact.php"
                   class="btn btn-primary submit-fs btn-custom"><?php echo $lng['general']['Request_New']; ?></a>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="vin_help" tabindex="-1" role="dialog" aria-labelledby="modalTitle" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header text-center">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <img src="../img/vin.png" alt="vin help" style="width: 100%;">
            </div>
        </div>
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
<script src="../js/scrollPosStyler.js"></script>
<script type="text/javascript" src="../js/jquery.mask.js"></script>
<script type="text/javascript" src="../js/jquery.masknumber.js"></script>

<script type="text/javascript">

    $(document).ready(function () {
        $('#car_plate').mask('AYYYYYY', {
            'translation': {
                A: {pattern: /[A-Za-z0-9]/},
                Y: {pattern: /[0-9]/}
            }
        });
        $('#car_km').maskNumber({integer: true});
        // $('#car_km').mask("#.##0,00", {reverse: true});

        var dbArrayPlateDate = [];

        var dbJsonData = '<?php echo json_encode($dbdata); ?>';
        obj = JSON.parse(dbJsonData);

        var provJsonData = '<?php echo json_encode($provdata); ?>';
        provObj = JSON.parse(provJsonData);

        for (var i = 0; i < obj.length; i++) {
            dbArrayPlateDate.push(obj[i].pre_fix);
        }
        $("#car_plate").on("input", function () {

            if ($('#add_provin').val() == '' || $('#add_provin').val() == null)
                return;

            var currentInputPlate = $(this).val();
            $("#add_car_make").find('option:eq(0)').prop('selected', true);

            $('option', $('#add_car_model')).remove();
            $('#add_car_model').append("<option disabled selected>Select Car Model</option>");

            $("#car_year").find('option:eq(0)').prop('selected', true);
            isInArray = dbArrayPlateDate.includes(currentInputPlate);
            if (isInArray) {
                var indexMatched = dbArrayPlateDate.indexOf(currentInputPlate);
                var provinSel = obj[indexMatched].after_fix;
                for (var i in provObj) {
                    if (provObj[i]['<?php echo $crt_lang_code;?>'] == $('#add_provin').val()) {
                        if (provObj[i]['en'] != provinSel && provObj[i]['ar'] != provinSel && provObj[i]['el'] != provinSel && provObj[i]['Ku'] != provinSel) {
                            return;
                        }
                    }
                }
                var idInputPlate = obj[indexMatched].id;
                var plateInputPlate = obj[indexMatched].plate;

                var vinInputPlate = obj[indexMatched].vin;
                var makeInputPlate = obj[indexMatched].make;
                var modelInputPlate = obj[indexMatched].model;
                var yearInputPlate = obj[indexMatched].year;

                $("#car_vin").val(vinInputPlate);
                $("#add_car_make").val(makeInputPlate);

                var makeData = {
                    'selectedMake': makeInputPlate
                };

                $.ajax({
                    type: 'POST',
                    url: 'getModelListFromMake.php',
                    data: makeData,
                    dataType: 'json',
                    encode: true
                }).done(function (list) {
                    $('option', $('#add_car_model')).remove();
                    if (list.length > 0) {
                        for (var i = 0; i < list.length; i++) {
                            var makeElement = list[i]['name'];
                            $('#add_car_model').append("<option value='" + makeElement + "'>" + makeElement + "</option>");
                        }
                        $("#add_car_model").val(modelInputPlate);
                    } else {
                        $('#add_car_model').append("<option disabled selected>No data</option>");
                    }
                });

                $("#car_year").val(yearInputPlate);
            }
        });
        $("#add_provin").change("select", function () {

            if ($('#car_plate').val() == '' || $('#car_plate').val() == null) {
                return;
            }
            var currentInputPlate = $('#car_plate').val();
            $("#add_car_make").find('option:eq(0)').prop('selected', true);

            $('option', $('#add_car_model')).remove();
            $('#add_car_model').append("<option disabled selected>Select Car Model</option>");

            $("#car_year").find('option:eq(0)').prop('selected', true);
            isInArray = dbArrayPlateDate.includes(currentInputPlate);
            if (isInArray) {
                var indexMatched = dbArrayPlateDate.indexOf(currentInputPlate);
                var provinSel = obj[indexMatched].after_fix;
                for (var i in provObj) {
                    if (provObj[i]['<?php echo $crt_lang_code;?>'] == $('#add_provin').val()) {
                        if (provObj[i]['en'] != provinSel && provObj[i]['ar'] != provinSel && provObj[i]['el'] != provinSel && provObj[i]['Ku'] != provinSel) {
                            return;
                        }
                    }
                }
                var idInputPlate = obj[indexMatched].id;
                var plateInputPlate = obj[indexMatched].plate;

                var vinInputPlate = obj[indexMatched].vin;
                var makeInputPlate = obj[indexMatched].make;
                var modelInputPlate = obj[indexMatched].model;
                var yearInputPlate = obj[indexMatched].year;

                $("#car_vin").val(vinInputPlate);
                $("#add_car_make").val(makeInputPlate);

                var makeData = {
                    'selectedMake': makeInputPlate
                };

                $.ajax({
                    type: 'POST',
                    url: 'getModelListFromMake.php',
                    data: makeData,
                    dataType: 'json',
                    encode: true
                }).done(function (list) {
                    $('option', $('#add_car_model')).remove();
                    if (list.length > 0) {
                        for (var i = 0; i < list.length; i++) {
                            var makeElement = list[i]['name'];
                            $('#add_car_model').append("<option value='" + makeElement + "'>" + makeElement + "</option>");
                        }
                        $("#add_car_model").val(modelInputPlate);
                    } else {
                        $('#add_car_model').append("<option disabled selected>No data</option>");
                    }
                });

                $("#car_year").val(yearInputPlate);
            }
        });

        $("#car_crash").on("change", function (e) {
            var isShow = $(e.target).val();
            if (isShow == "yes") {
                $(".car_status").css("display", "flex");
            } else {
                $(".car_status").css("display", "none");
            }
        });

        $("select#add_car_make").change(function () {
            var selectedMake = this.value;
            var makeData = {
                'selectedMake': selectedMake
            };

            $.ajax({
                type: 'POST',
                url: 'getModelListFromMake.php',
                data: makeData,
                dataType: 'json',
                encode: true
            }).done(function (list) {
                $('option', $('#add_car_model')).remove();
                if (list.length > 0) {
                    for (var i = 0; i < list.length; i++) {
                        var makeElement = list[i]['name'];
                        $('#add_car_model').append("<option value='" + makeElement + "'>" + makeElement + "</option>");
                    }
                } else {
                    $('#add_car_model').append("<option disabled selected>No data</option>");
                }
            });
        });

        $('#coupon_btn').click(function () {
            document.forms[4].submit();
        })
    });
</script>

</body>

</html>
