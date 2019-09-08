<?php
    $userList = $config->getUserList();
    $userCount = $userList->num_rows;

    $dealerList = $config->getDealerList();
    $dealerCount = $dealerList->num_rows;
    
    $userBlockList = $config->getBlockList("user");
    $userBlockCount = $userBlockList->num_rows;

    $dealerBlockList = $config->getBlockList("dealer");
    $dealerBlockCount = $dealerBlockList->num_rows;
    
    $reportCount = $config->getReportCount();

    $vehicleList = $config->getVehicleList();
    $vehicleCount = $vehicleList->num_rows;

    $paymentList = $config->getPayments();
    $paidCount = $paymentList->num_rows;

    $sum = 0;
    while($one = $paymentList->fetch_assoc()) {
        $sum += $one['itemAmount'];
    }

    $logical = 0;
    $notLogical = 0;
    $noJudgement = 0;

    while($vehicle = $vehicleList->fetch_assoc()) {
    	$car_id = $vehicle['id'];
    	$result = $config->get_last_vehicle_km_by_car_id($car_id);
    	$obj = $result->fetch_assoc();
    	$status = $obj['logic'];
    	if($status == "true")
    		$logical++;
    	else if($status == "false")
    		$notLogical++;
    	else
    		$noJudgement++;
    }

?>

<div class="row col-md-12 text-center">
    <div class="col-md-1"></div>
    <div class="col-md-4 mb-4 pt-3 pb-3" style="background-color: #e86f14;">
        <p class="analysis-txt"><?php echo $userCount; ?></p>
        <p class="analysis-txt">Total Users</p>
    </div>
    <div class="col-md-2"></div>
    <div class="col-md-4 mb-4 pt-3 pb-3" style="background-color: #971e2f;">
        <p class="analysis-txt"><?php echo $dealerCount; ?></p>
        <p class="analysis-txt">Total Dealers</p>
    </div>
    <div class="col-md-1"></div>
</div>

<div class="row col-md-12 text-center">
    <div class="col-md-1"></div>
    <div class="col-md-4 mb-4 pt-3 pb-3" style="background-color: #131b86;">
        <p class="analysis-txt"><?php echo $userBlockCount + $dealerBlockCount; ?><span style="font-size: 18px;"> ( user-<?php echo $userBlockCount; ?>, dealer-<?php echo $dealerBlockCount; ?> )</span></p>
        <p class="analysis-txt">Total Block Users</p>
    </div>
    <div class="col-md-2"></div>
    <div class="col-md-4 mb-4 pt-3 pb-3" style="background-color: #55bb55;">
        <p class="analysis-txt"><?php echo $reportCount; ?></p>
        <p class="analysis-txt">Total Generate Report</p>
    </div>
    <div class="col-md-1"></div>
</div>

<hr>

<div class="row col-md-12 text-center">
    <div class="col-md-1"></div>
    <div class="col-md-4 mb-4 pt-3 pb-3" style="background-color: #ed2844;">
        <p class="analysis-txt"><?php echo $vehicleCount; ?></p>
        <p class="analysis-txt">Total Vehicle</p>
    </div>
    <div class="col-md-2"></div>
    <div class="col-md-4 mb-4 pt-3 pb-3" style="background-color: #08293c;">
        <p class="analysis-txt"><?php echo $logical; ?></p>
        <p class="analysis-txt">Logical</p>
    </div>
    <div class="col-md-1"></div>
</div>

<div class="row col-md-12 text-center">
    <div class="col-md-1"></div>
    <div class="col-md-4 mb-4 pt-3 pb-3" style="background-color: #0c3f5e;">
        <p class="analysis-txt"><?php echo $notLogical; ?></p>
        <p class="analysis-txt">Not logical</p>
    </div>
    <div class="col-md-2"></div>
    <div class="col-md-4 mb-4 pt-3 pb-3" style="background-color: #a14f13;">
        <p class="analysis-txt"><?php echo $noJudgement; ?></p>
        <p class="analysis-txt">No judgement</p>
    </div>
    <div class="col-md-1"></div>
</div>

<hr>

<div class="row col-md-12 text-center">
    <div class="col-md-1"></div>
    <div class="col-md-4 mb-4 pt-3 pb-3" style="background-color: #971e2f;">
        <p class="analysis-txt"><?php echo $paidCount; ?></p>
        <p class="analysis-txt">Total Payment</p>
    </div>
    <div class="col-md-2"></div>
    <div class="col-md-4 mb-4 pt-3 pb-3" style="background-color: #131b86;">
        <p class="analysis-txt">$ <?php echo $sum; ?></p>
        <p class="analysis-txt">Sum</p>
    </div>
    <div class="col-md-1"></div>
</div>