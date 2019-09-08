<?php 
    $response = "";
    $id = $_GET['id'];
    $result = $config->getVehicleById($id);
    $vehicle = $result->fetch_assoc();

    $result = $config->getCrashedByCarId($id);
    $vehicle_crash = $result->fetch_assoc();

    $status = $vehicle_crash['crash'];

    $result = $config->get_last_vehicle_km_by_car_id($id);
    $vehicle_km = $result->fetch_assoc();

    if(isset($_POST['update'])) {
        $plate = $_POST['plate'];
        $make = $_POST['make'];
        $model = $_POST['model'];
        $year = $_POST['year'];
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

        $km = $_POST['km'];
        $date = $_POST['date'];

        $update_vehicle = $config->updateVehicle($id, $plate, $make, $model, $year);
        $update_crash = $config->updateVehicleCrash($id, $crash, $front, $back, $lefty, $righty, $total);
        $update_km = $config->updateVehicleKm($id, $km, $date);

        if($update_vehicle && $update_crash && $update_km) {
            echo "<script>window.location='home.php?query=vehiclelist';</script>";
        } else {
            $response = "Can not update Information";
        }
    }
?>

<form method="post">
    <div class="text-center">
        <h3 class="dashboard-txt"><u>Vehicle Edit</u></h3>
    </div>
    <div class="row col-md-12">
        <div class="col-md-1"></div>
        <div class="col-md-5 text-left mt-4">
            <label class="control-label">Plate Number</label>
            <div class="agileits-main">
                <i class="fas fa-list-ol"></i>
                <input type="text" id="inputEmail3" name="plate" value="<?php echo $vehicle['plate']; ?>" required="">
            </div>
        </div>
        <div class="col-md-5 text-left mt-4">
            <label class="control-label">VIN</label>
            <div class="agileits-main">
                <i class="fas fa-barcode"></i>
                <input type="text" required="" disabled value="<?php echo $vehicle['vin']; ?>" id="car_vin">
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
                <select name="make" required="" id="car_make">
                <?php 
                    $getMakeList = $config->getMakeList();
                    while($getMakeList_fetch = $getMakeList->fetch_assoc()) {
                ?>
                        <option value="<?php echo $getMakeList_fetch['name']; ?>" 
                            <?php if($getMakeList_fetch['name'] == $vehicle['make']) echo "selected"; ?>>
                            <?php echo $getMakeList_fetch['name']; ?>
                        </option>
                <?php
                    }
                ?>
                </select>
            </div>
        </div>
        <div class="col-md-4 text-left mt-4">
            <label class="control-label">Model</label>
            <div class="agileits-main">
                <i class="fas fa-cogs"></i>
                <select name="model" required="" id="car_model">
                <?php 
                    $getModelList = $config->getModelListFromMake($vehicle['make']);
                    while($getModelList_fetch = $getModelList->fetch_assoc()) {
                ?>
                        <option value="<?php echo $getModelList_fetch['name']; ?>" 
                            <?php if($getModelList_fetch['name'] == $vehicle['model']) echo "selected"; ?>>
                            <?php echo $getModelList_fetch['name']; ?>
                        </option>
                <?php
                    }
                ?>
                </select>
            </div>
        </div>
        <div class="col-md-3 text-left mt-4">
            <label class="control-label">Build Year</label>
            <div class="agileits-main">
                <i class="far fa-clock"></i>
                <input type="text" name="year" value="<?php echo $vehicle['year']; ?>" required="">
            </div>
        </div>
        <div class="col-md-1"></div>
        <div class="col-md-1"></div>
        <div class="col-md-5 text-left mt-4">
            <label class="control-label">KM Now</label>
            <div class="agileits-main">
                <i class="fas fa-running"></i>
                <input type="number" name="km" step="any" required="" id="car_km" value="<?php echo $vehicle_km['km']; ?>">
            </div>
        </div>
        <div class="col-md-5 text-left mt-4">
            <label class="control-label">Date</label>
            <div class="agileits-main">
                <i class="far fa-calendar-alt"></i>
                <input type="date" name="date" value="<?php echo date('Y-m-d'); ?>" required="" id="car_date">
            </div>
        </div>
        <div class="col-md-1"></div>
    </div>
   
    <div class="row col-md-12">
        <div class="col-md-1"></div>
        <div class="col-md-3 text-left mt-4">
            <label class="control-label">Crashed ?</label>
            <div class="agileits-main">
                <i class="fas fa-car-crash" style="left: 1%;"></i>
                <select name="crash" required="" id="car_crash" class="mr-4">
                    <option value="yes" <?php if($vehicle_crash['crash'] == "yes") echo "selected"; ?>>Yes</option>
                    <option value="no" <?php if($vehicle_crash['crash'] == "no") echo "selected"; ?>>No</option>
                </select>
            </div>
        </div>
        <div class="col-md-1"></div>
    </div>


    <div class="row col-md-12">
        <div class="col-md-1"></div>
        <div class="form-group col-md-10">
            <div class="col-md-12 mt-3 car_status">
                <input type="checkbox" name="front" value="yes" 
                    <?php 
                        if($vehicle_crash['front'] == "yes") {
                            echo "checked";
                        }
                    ?>
                >Front &nbsp;&nbsp;&nbsp;
                <input type="checkbox" name="back" value="yes"
                    <?php 
                        if($vehicle_crash['back'] == "yes") {
                            echo "checked";
                        }
                    ?>
                >Back &nbsp;&nbsp;&nbsp;
                <input type="checkbox" name="lefty" value="yes"
                    <?php 
                        if($vehicle_crash['lefty'] == "yes") {
                            echo "checked";
                        }
                    ?>
                >Left &nbsp;&nbsp;&nbsp;
                <input type="checkbox" name="righty" value="yes"
                    <?php 
                        if($vehicle_crash['righty'] == "yes") {
                            echo "checked";
                        }
                    ?>
                >Right &nbsp;&nbsp;&nbsp;
                <input type="checkbox" name="total" value="yes"
                    <?php 
                        if($vehicle_crash['total'] == "yes") {
                            echo "checked";
                        }
                    ?>
                >Total Loss &nbsp;&nbsp;&nbsp;
            </div>
        </div>
        <div class="col-md-1"></div>
    </div>

    <div class="text-center" style="margin-top:30px;">
        <button type="submit" class="btn btn-primary submit-fs btn-custom" name="update" style="font-size:1.2em;">Update</button>
        <a href="home.php?query=vehiclelist" class="btn btn-primary submit-fs btn-custom" style="font-size:1.2em;">Cancel</a>
        <?php if($response != "") { ?>
            <p><label class="control-label mt-3"><?php echo $response; ?></label></p>
        <?php } ?>
    </div>
</form>
        
<script src="../js/jquery.min.js" ></script>
<script type="text/javascript">
    $(document).ready(function() {
        var isCrash = "<?php echo $status ?>";
        if(isCrash == "no") {
            $(".car_status").css("display", "none");
        } else {
            $(".car_status").css("display", "block");
        }

        $("#car_crash").on("change", function(e) {
            var isShow = $(e.target).val();
            if(isShow == "yes") {
                $(".car_status").css("display", "block");
            } else {
                $(".car_status").css("display", "none");
            }
        });
        
        $("select#car_make").change(function() {
            var selectedMake = this.value;
            var makeData = {
                'selectedMake': selectedMake
            };

            $.ajax({
                type     : 'POST',
                url      : '../vehicle/getModelListFromMake.php',
                data     : makeData,
                dataType : 'json',
                encode   : true
            }).done(function(list) {
                $('option', $('#car_model')).remove();
                for(var i=0; i<list.length; i++) {
                    var makeElement =  list[i]['name'];
                    $('#car_model').append("<option value='" + makeElement + "'>" + makeElement + "</option>");
                }
            });
        });
    });
</script>