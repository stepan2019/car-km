<form method="post">
    <div class="row col-md-12">
        <div class="col-md-5 text-left">
            <div class="agileits-main">
                <label class="control-label">Plate Number or VIN </label>
                <i class="fas fa-signature" style="left: 39%;"></i>
                <select name="plate" required="" id="select2_make">
                    <option disabled selected>Plate Number or VIN</option>
                <?php 
                    $getVehicleList = $config->getVehicleList();
                    while($getVehicleList_fetch = $getVehicleList->fetch_assoc()) {
                ?>
                        <option value="<?php echo $getVehicleList_fetch['plate']; ?>" <?php if( $getVehicleList_fetch['plate'] == $_POST['plate']) echo "selected"; ?>>
                            <?php echo $getVehicleList_fetch['plate']; ?>
                        </option>
                <?php
                    }
                ?>
                <?php 
                    $getVehicleList = $config->getVehicleList();
                    while($getVehicleList_fetch = $getVehicleList->fetch_assoc()) {
                ?>
                        <option value="<?php echo $getVehicleList_fetch['vin']; ?>">
                            <?php echo $getVehicleList_fetch['vin']; ?>
                        </option>
                <?php
                    }
                ?>
                </select>
            </div>
        </div>
        <div class="col-md-7">
            <button type="submit" class="btn btn-primary submit-fs ml-5 btn-custom" name="view_history" id="view_btn">View</button>
            
            <button type="button" class="btn btn-primary submit-fs ml-5 btn-custom" name="" id="generate_report_btn">Generate Report</button>
            <!--<button type="submit" class="btn btn-primary submit-fs ml-5 btn-custom" name="gen_report">Generate</button>-->
            <!--<a href="pdf.php?query=<?php echo $_POST['plate']; ?>" class="btn btn-primary submit-fs btn-custom" target="_blank"">Generate Report</a>-->
			 
                         
        </div>
    </div>
</form>

<?php
    if(isset($_POST['view_history'])) {
        if(isset($_POST['plate'])) {
            $plate = $_POST['plate'];

            $resultByPlate = $config->get_vehicle_by_plate($plate);
            $countByPlate = $resultByPlate->num_rows;

            $resultByVIN = $config->get_vehicle_by_vin($plate);
            $countByVIN = $resultByVIN->num_rows;

            if($countByPlate > 0 || $countByVIN > 0) {
                if($countByPlate > 0)
                    $vehicle_info = $resultByPlate->fetch_assoc();
                else
                    $vehicle_info = $resultByVIN->fetch_assoc();
                $car_id = $vehicle_info['id'];
                $user_id = $vehicle_info['user_id'];
                $user_type = $vehicle_info['type'];

                if($user_type == "user")
                    $result = $config->getUserById($user_id);
                else
                    $result = $config->getDealerById($user_id);

                $user_info = $result->fetch_assoc();
    ?>
                <div id="html-2-pdfwrapper" class="row col-md-12 text-left mt-4">
            <?php
                if($user_type == "user") {
            ?>
                    <div class="col-md-3">
                        <p><u>Fullname</u></p>
                        <p class="w-f-18"><?php echo $user_info['name']; ?></p>
                    </div>
            <?php } else { ?>
                    <div class="col-md-3">
                        <p><u>Company Name</u></p>
                        <p class="w-f-18"><?php echo $user_info['company']; ?></p>
                    </div>
            <?php } ?>
                    <div class="col-md-3">
                        <p><u>Address</u></p>
                        <p class="w-f-18"><?php echo $user_info['address']; ?></p>
                    </div>
                    <div class="col-md-6">
                        <h2 style="color: #f68;">Tellerrapport</h2>
                        <p><?php echo date('d-m-Y'); ?></p>
                    </div>

                    <?php
                        $result = $config->get_last_vehicle_km_by_car_id($car_id);
                        $km_last_info = $result->fetch_assoc();

                        $result = $config->getCrashedByCarId($car_id);
                        $crash_info = $result->fetch_assoc();
                    ?>
                    
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th class="text-center">License Plate</th>
                                <th class="text-center">VIN</th>
                                <th class="text-center">Make + Model</th>
                                <th class="text-center">Year Build</th>
                                <th class="text-center">Current km reading</th>
                                <th class="text-center">Car Crashed ?</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-center"><?php echo $vehicle_info['plate']; ?></td>
                                <td class="text-center"><?php echo $vehicle_info['vin']; ?></td>
                                <td class="text-center"><?php echo $vehicle_info['make']; ?> + <?php echo $vehicle_info['model']; ?></td>
                                <td class="text-center"><?php echo $vehicle_info['year']; ?></td>
                                <td class="text-center"><?php echo $km_last_info['km']; ?> km</td>
                                <td class="text-center">
                                    <?php echo $crash_info['crash']; ?>
                                    <?php
                                        if($crash_info['crash'] == "yes") {
                                            echo '(';
                                            if($crash_info['front'] != "")
                                                echo "front ";
                                            if($crash_info['back'] != "")
                                                echo "back ";
                                            if($crash_info['lefty'] != "")
                                                echo "left ";
                                            if($crash_info['righty'] != "")
                                                echo "right ";
                                            if($crash_info['total'] != "")
                                                echo "total ";
                                            echo ')';
                                        }
                                    ?>    
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="col-md-12">
                        <p>
                            <?php
                                if($km_last_info['logic'] == "true") {
                            ?>
                                    <span class="mr-4">Logical</span>
                                    <img src="/img/Logical.png" alt="record">
                            <?php
                                } else if($km_last_info['logic'] == "false") {
                            ?>
                                    <span class="mr-4">Not Logical</span>
                                    <img src="/img/Not-Logical.png" alt="record">
                            <?php
                                } else {
                            ?>
                                    <span class="mr-4">No Judgement</span>
                                    <img src="/img/No-Jurgment.png" alt="record">
                            <?php
                                }
                            ?>
                        </p>
                    </div>

                    <p class="col-md-12" style="font-size: 24px;">Km Record</p>
            
                <?php
                    $result = $config->get_vehicle_km_by_car_id($car_id);
                    $totalRows = $result->num_rows;
                    $minRows = ceil($totalRows / 3);
                    $minRows = $minRows > 10 ? $minRows : 10;
                ?>
                    <table class="table" style="width: 33%;">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 16%;">km</th>
                                <th class="text-center" style="width: 16%;">add_date</th>
                            </tr>
                        </thead>
                        <tbody>
                    <?php
                        $cnt = 0;
                        while($km_info = $result->fetch_assoc()) {
                            $cnt++;
                    ?>
                            <tr>
                                <td class="text-center" style="border:none;"><?php echo $km_info['km']; ?></td>
                                <td class="text-center" style="border:none;"><?php echo $km_info['add_date']; ?></td>
                            </tr>
                    <?php
                            if($cnt == $minRows) break;
                        }
                    ?>
                        </tbody>
                    </table>
                    <table class="table" style="width: 33%;">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 16%;">km</th>
                                <th class="text-center" style="width: 16%;">add_date</th>
                            </tr>
                        </thead>
                        <tbody>
                    <?php
                        $cnt = 0;
                        while($km_info = $result->fetch_assoc()) {
                            $cnt++;
                    ?>
                            <tr>
                                <td class="text-center" style="border:none;"><?php echo $km_info['km']; ?></td>
                                <td class="text-center" style="border:none;"><?php echo $km_info['add_date']; ?></td>
                            </tr>
                    <?php
                            if($cnt == $minRows) break;
                        }
                    ?>
                        </tbody>
                    </table>
                    <table class="table" style="width: 33%;">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 16%;">km</th>
                                <th class="text-center" style="width: 16%;">add_date</th>
                            </tr>
                        </thead>
                        <tbody>
                    <?php
                        while($km_info = $result->fetch_assoc()) {
                    ?>
                            <tr>
                                <td class="text-center" style="border:none;"><?php echo $km_info['km']; ?></td>
                                <td class="text-center" style="border:none;"><?php echo $km_info['add_date']; ?></td>
                            </tr>
                    <?php
                        }
                    ?>
                        </tbody>
                    </table>
                    <div class="col-md-12">
                        <p style="font-size: 24px;"><u>Explanation</u></p>
                        <p>Registration of the car KM in Greece is From 2019, we cannot JUDGEMENT about the Km before this year.<br>
                            You see in the report Logical, Not Logical, No JUDGEMENT that means:<br>
                            1 - Logical that means the KM is Logical as we have Registered by Carpass.<br>
                            2 - Not Logical, That means the km is change after registration by Carpass.<br>
                            3 - No Judgment, that mean we have no any data of this vehicle, that is first Registration by Carpass.
                        </p>
                    </div>
                </div>
        
            <!-- <button class="btn btn-primary mb-5 btn-custom" onclick="generate()">Generate PDF</button> -->
    <?php
            } else {
                echo 'The data does not exist.';
            }
        } else {
            echo 'Please select Plate Number or VIN.';
        }
    }
    
    // if(isset($_POST['gen_report'])) {
    //     if(isset($_POST['plate'])) {
    //         $plate = $_POST['plate'];
    //         //echo $plate;exit;
    //         //$link = "<script>window.open('https://www.car-km.com/admin/pdf.php?query=', '_blank')</script>";
    //         //cho $link;
    //         echo '<a href="pdf.php?query='.$plate.'" class="btn btn-primary submit-fs btn-custom" target="_blank"></a>';
    //     } else {
    //         echo 'Please select Plate Number or VIN.';
    //     }
    // }
?>


<script src="https://code.jquery.com/jquery-1.11.3.js" type="text/javascript"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.3.3/jspdf.min.js" type="text/javascript"></script>
	<script src="https://html2canvas.hertzen.com/dist/html2canvas.js" type="text/javascript"></script>
	
<script>	
$(document).ready(function(){
    $('#generate_report_btn').click(function(){
        var plate = $('#select2_make').val();
        if(!plate){
            alert("please select plate number");
            return ;
        }
        // var link = "https://www.car-km.com/admin/pdf.php?query=" + plate;
        // window.open(link, '_blank');
        var link = "https://www.car-km.com/admin/pdf.php";
        window.open(link,"tttt", {'query': plate});
        
       
    });
    // $("#view_btn").click(function(e) {
    //     console.log("submited")
    //     e.preventDefault();
    // });
});
</script>

 