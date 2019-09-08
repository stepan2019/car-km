<?php 
    include "../setting/config.php";

    session_start();

    if(!@$_SESSION['user']) {
         header("location:/user/login.php");
    }
    
    $result = $config->getVehicleList();
    $dbdata = array();

    while ( $row = $result->fetch_assoc())  {
        $dbdata[] = $row;
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

    if(isset($_POST['add_car'])) {
        $plate = $_POST['plate'];
        $vin = $_POST['vin'];
        if(isset($_POST['make']) && isset($_POST['model']) && isset($_POST['year']) && isset($_POST['crash'])) {
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
            
            if(isset($_POST['front']))
                $front = $_POST['front'];
            if(isset($_POST['back']))
                $back = $_POST['back'];
            if(isset($_POST['lefty']))
                $lefty = $_POST['lefty'];
            if(isset($_POST['righty']))
                $righty = $_POST['righty'];
            if(isset($_POST['total']))
                $total = $_POST['total'];

            if($crash == "no") {
                $front = "";
                $back = "";
                $lefty = "";
                $righty = "";
                $total = "";
            }

            if($crash == "yes" && $front == "" && $back == "" && $lefty == "" && $righty == "" && $total == "") {
                $resCrash = "You must select the Car Crashed Status if car has been crashed.";
            } else {
                $result = $config->add_vehicle($user_id, $type, $plate, $vin, $make, $model, $year, $km, $date, $crash, $front, $back, $lefty, $righty, $total);
                if($result) {
                    // $response = "Successfully to add";
?>
                    <script src="../js/jquery.min.js" ></script> 
                    <script>
                        $(function() {
                            $("#historyPDF").modal();
                        });
                    </script>

                    <div class="modal fade" id="historyPDF" tabindex="-1" role="dialog" aria-labelledby="modalTitle" aria-hidden="true">
                        <div class="modal-dialog" role="document" style="max-width: 800px;margin: 200px auto;">
                            <div class="modal-content" style="background-color: #1d4964; border-radius: 4px;">
                                <div class="modal-body text-center">
                                    <p class="dashboard-txt">Thanks for Registration of your Vehicle.</p>
                                    <p class="dashboard-txt">If you want to see all Report of this vehicle, please click on Generate Report.</p>
                            <?php
                                $result = $config->getPrice();
                                $currentPrice = $result->fetch_assoc();
                                $val = $currentPrice['price'];

                                if($val == 0) {
                            ?>
                                    <a href="pdf.php?query=<?php echo $vin; ?>" class="btn btn-primary submit-fs btn-custom" target="_blank" onclick="modalClose();">Generate Report</a>
                            <?php
                                } else {
                            ?>
                                    <form class="paypal" action="payments.php" method="post" id="paypal_form" target="_blank">
                                        <input type="hidden" name="vin" id="vin" value="<?php echo $vin; ?>" />
                                        <input type="hidden" name="plate" id="plate" value="<?php echo $plate; ?>" />
                                        <input type="hidden" name="payer_email" id="payer_email" value="<?php echo $user_info['email'] ?>" />
                                        <input type="hidden" name="itemAmount" value="<?php echo $val; ?>" />
                                        
                                        <input type="hidden" name="cmd" value="_xclick" />
                                        <input type="hidden" name="lc" value="UK" />
                                        <input type="hidden" name="bn" value="PP-BuyNowBF:btn_buynow_LG.gif:NonHostedGuest" />
                                        <input type="hidden" name="first_name" value="Customer's First Name" />
                                        <input type="hidden" name="last_name" value="Customer's Last Name" />
                                        <input type="hidden" name="item_number" value="123456" / >
                                        <input type="submit" name="submit" value="PayPal" class="btn btn-primary submit-fs btn-custom" onclick="modalClose();"/>
                                        <input type="button" name="coupon" value="Coupon" id="coupon_btn" class="btn btn-primary submit-fs btn-custom"/>
                                    </form>
                            <?php } ?>
                                    <script type="text/javascript">
                                        function modalClose() {
                                            $("#historyPDF").modal('hide');
                                            return true;
                                        }
                                    </script>
                                    <p class="dashboard-txt">After that you get Full Information about vehicle KM.</p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-primary btn-custom" data-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
<?php
                } else {
                    $response = "Sorry, is failed to add";
                }
            }
        } else if(!isset($_POST['make'])) {
            $resMake = "You must select the Car Make";
        } else if(!isset($_POST['model'])) {
            $resModel = "You must select the Car Model";
        } else if(!isset($_POST['year'])) {
            $resBuild = "You must select the Car Build Year";
        } else if(!isset($_POST['crash'])) {
            $resCrash = "You must select the Car Crash Status";
        }
    }
    
?>

<!DOCTYPE html>
<html>
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

    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.8.2/css/all.css"
        integrity="sha384-oS3vJWv+0UjzBfQzYUhtDYW+Pj2yciDJxpsK1OYPAYjqT085Qq/1cq5FLXAZQ7Ay" crossorigin="anonymous">
    
    <style type="text/css">
        .modal-dialog {
            max-width: 1450px;
        }
    </style>
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
                    <div class="col-md-5 text-left mt-1">
                        <label class="control-label">Plate Number</label>
                        <div class="agileits-main only-plate">
                            <!--<i class="fas fa-list-ol"></i>-->
                            <input type="text" required="" name="plate" id="car_plate" onkeyup="this.value = this.value.toUpperCase();">
                            <h3>Province</h3>
                        </div>
                    </div>
                    <div class="col-md-5 text-left mt-1">
                        <label class="control-label">VIN</label>
                        <div class="agileits-main">
                            <i class="fas fa-barcode"></i>
                            <input type="text" required="" name="vin" id="car_vin" onkeyup="this.value = this.value.toUpperCase();">
                            <a href="#vin_help" data-toggle="modal"><img src="../img/vin-help.png" alt="vin help"></a>
                        </div>
                    </div>
                    <div class="col-md-1"></div>
                </div>
                <div class="row col-md-12">
                    <div class="col-md-1"></div>
                    <div class="col-md-3 text-left mt-4">
                        <label class="control-label">Make</label>
                        <div class="agileits-main">
                            <i class="far fa-building"></i>
                            <select name="make" required="" id="add_car_make">
                                <option disabled selected>Select Car Make</option>
                                
                            <?php 
                                $getMakeList = $config->getMakeList();
                                while($getMakeList_fetch = $getMakeList->fetch_assoc()) {
                            ?>
                                    <option value="<?php echo $getMakeList_fetch['name']; ?>">
                                        <?php echo $getMakeList_fetch['name']; ?>
                                    </option>
                            <?php
                                }
                            ?>
                            </select>
                        </div>
                        <?php if($resMake != "") { ?>
                            <p><label class="control-label mt-3"><?php echo $resMake; ?></label></p>
                        <?php } ?>
                    </div>
                    <div class="col-md-4 text-left mt-4">
                        <label class="control-label">Model</label>
                        <div class="agileits-main">
                            <i class="fas fa-cogs"></i>
                            <select name="model" required="" id="add_car_model">
                                <option disabled selected>Select Car Model</option>
                            </select>
                        </div>
                        <?php if($resModel != "") { ?>
                            <p><label class="control-label mt-3"><?php echo $resModel; ?></label></p>
                        <?php } ?>
                    </div>
                    <div class="col-md-3 text-left mt-4">
                        <label class="control-label">Build Year</label>
                        <div class="agileits-main">
                            <i class="far fa-clock"></i>
                            <select name="year" required="" id="car_year">
                                <option disabled selected>Build Year</option>
                    <?php
                        $start_year = 1950;
                        foreach (range(date('Y'), $start_year) as $x) {
                            echo '<option value="'.$x.'">'.$x.'</option>';
                        }
                    ?>
                            </select>
                        </div>
                        <?php if($resBuild != "") { ?>
                            <p><label class="control-label mt-3"><?php echo $resBuild; ?></label></p>
                        <?php } ?>
                    </div>
                    <div class="col-md-1"></div>
                    <div class="col-md-1"></div>
                    <div class="col-md-5 text-left mt-4">
                        <label class="control-label">KM</label>
                        <div class="agileits-main">
                            <i class="fas fa-running"></i>
                            <input type="number" name="km" step="any" required="" id="car_km">
                        </div>
                    </div>
                    <div class="col-md-5 text-left mt-4">
                        <label class="control-label">Date</label>
                        <div class="agileits-main">
                            <i class="far fa-calendar-alt"></i>
                            <input type="date" value="<?php echo date('Y-m-d'); ?>" disabled >
                            <input type="hidden" name="date" value="<?php echo date('Y-m-d'); ?>" id="car_date">
                        </div>
                    </div>
                    <div class="col-md-1"></div>
                </div>
                <div class="row col-md-12">
                    <div class="col-md-1"></div>
                    <div class="col-md-3 text-left mt-4">
                        <label class="control-label">Crashed ?</label>
                        <div class="agileits-main">
                            <i class="fas fa-car-crash"></i>
                            <select name="crash" required="" id="car_crash">
                                <option disabled selected>Status</option>
                                <option value="yes">Yes</option>
                                <option value="no">No</option>
                            </select>
                        </div>
                        <?php if($resCrash != "") { ?>
                            <p><label class="control-label mt-3"><?php echo $resCrash; ?></label></p>
                        <?php } ?>
                    </div>
                    <div class="col-md-1"></div>
                </div>
                <div class="row col-md-12 car_status">
                    <div class="col-md-1"></div>
                    <div class="col-md-1 text-left check-border">
                        <input type="checkbox" name="front" value="yes"> Front
                    </div>
                    <div class="col-md-1 text-left check-border">
                        <input type="checkbox" name="back" value="yes"> Back
                    </div>
                    <div class="col-md-1 text-left check-border">
                        <input type="checkbox" name="lefty" value="yes"> Left
                    </div>
                    <div class="col-md-1 text-left check-border">
                        <input type="checkbox" name="righty" value="yes"> Right
                    </div>
                    <div class="col-md-2 text-left check-border">
                        <input type="checkbox" name="total" value="yes"> Total Loss
                    </div>
                    <div class="col-md-1"></div>
                </div>

                <div class="text-center submit mt-5">
                    <button type="submit" class="btn btn-primary submit-fs btn-custom" name="add_car">Submit</button>
                    <button type="reset" class="btn btn-primary submit-fs btn-custom">Reset</button>
                    <?php if($response != "") { ?>
                        <p><label class="control-label mt-3"><?php echo $response; ?></label></p>
                    <?php } ?>
                </div>
            </form>

            <hr>

            <div class="extra-field">
                <div class="text-center text-black mb-4">You can request to us the new make or model if there doesn't exist.</div>
                <div class="text-center">
                    <a href="../template/contact.php" class="btn btn-primary submit-fs btn-custom">Request New</a>
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

    <script src="../js/jquery.min.js" ></script> 
    <script src="../js/bootstrap.min.js"></script> 
    <script src="../js/scrollPosStyler.js"></script>
    <script type="text/javascript" src="../js/jquery.mask.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            var dbArrayPlateDate = [];

            var dbJsonData = '<?php echo json_encode($dbdata); ?>';
            obj = JSON.parse(dbJsonData);

            for(var i = 0; i < obj.length; i++) {
                dbArrayPlateDate.push(obj[i].plate);
            }
            $('#car_plate').mask('AYYYYYY', {'translation': {
                    A: {pattern: /[A-Z0-9]/},
                    Y: {pattern: /[0-9]/}
                }
            });

            $("#car_plate").on("input", function(){
                var currentInputPlate = $(this).val();
                $("#car_vin").val("");
                $("#add_car_make").find('option:eq(0)').prop('selected', true);
                
                $('option', $('#add_car_model')).remove();
                $('#add_car_model').append("<option disabled selected>Select Car Model</option>");
                
                $("#car_year").find('option:eq(0)').prop('selected', true);

                isInArray = dbArrayPlateDate.includes(currentInputPlate);
                if(isInArray) {
                    var indexMatched    = dbArrayPlateDate.indexOf(currentInputPlate);
                    var idInputPlate    = obj[indexMatched].id;
                    var plateInputPlate = obj[indexMatched].plate;
                    
                    var vinInputPlate   = obj[indexMatched].vin;
                    var makeInputPlate  = obj[indexMatched].make;
                    var modelInputPlate = obj[indexMatched].model;
                    var yearInputPlate  = obj[indexMatched].year;

                    $("#car_vin").val(vinInputPlate);
                    $("#add_car_make").val(makeInputPlate);

                    var makeData = {
                        'selectedMake': makeInputPlate
                    };

                    $.ajax({
                        type     : 'POST',
                        url      : 'getModelListFromMake.php',
                        data     : makeData,
                        dataType : 'json',
                        encode   : true
                    }).done(function(list) {
                        $('option', $('#add_car_model')).remove();
                        if(list.length > 0) {
                            for(var i=0; i<list.length; i++) {
                                var makeElement =  list[i]['name'];
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
            
            $("#car_crash").on("change", function(e) {
                var isShow = $(e.target).val();
                if(isShow == "yes") {
                    $(".car_status").css("display", "flex");
                } else {
                    $(".car_status").css("display", "none");
                }
            });
            
            $("select#add_car_make").change(function() {
                var selectedMake = this.value;
                var makeData = {
                    'selectedMake': selectedMake
                };

                $.ajax({
                    type     : 'POST',
                    url      : 'getModelListFromMake.php',
                    data     : makeData,
                    dataType : 'json',
                    encode   : true
                }).done(function(list) {
                    $("#car_vin").val("");
                    $('option', $('#add_car_model')).remove();
                    if(list.length > 0) {
                        for(var i=0; i<list.length; i++) {
                            var makeElement =  list[i]['name'];
                            $('#add_car_model').append("<option value='" + makeElement + "'>" + makeElement + "</option>");
                        }
                    } else {
                            $('#add_car_model').append("<option disabled selected>No data</option>");
                    }
                });
            });
            
            $('#coupon_btn').click(function(){
                var vin = $('#vin').val();
                var plate = $('#plate').val();
                var payer_email = $('#payer_email').val();
                
                var link = "http://car-km.com/vehicle/get-coupon.php?vin="+vin+"&payer_email="+payer_email+"&plate="+plate;
                //var encodedUrl = encodeURIComponent(link);
                window.location.replace(link);
                //alert(vin);
                //return;
            })
        });
    </script>
    
</body>

</html>
