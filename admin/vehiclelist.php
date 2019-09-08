<div>
    <?php
        $vehicleList = $config->getVehicleList();
        $count = $vehicleList->num_rows;
    ?>
    <div class="mb-4">
        <span class="dashboard-txt">Total Vehicles : <?php echo $count; ?></span>
        <a href="home.php?query=vehiclehistory" class="btn btn-primary submit-fs btn-custom ml-5">Search Vehicle KM</a>
        <a href="home.php?query=newfields" class="btn btn-primary submit-fs btn-custom ml-5">New Makes & Models</a>
    </div>
	<table class="table table-bordered" id="wang-dataTable">
        <thead>
            <tr>
                <th class="text-center">#</th>
                <th>Plate</th>
                <th>VIN</th>
                <th>Make</th>
                <th>Model</th>
                <th>Year</th>
                <th>KM now</th>
                <th>Add Date</th>
                <th>Car Crashed?</th>
                <th class="text-center">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php 
				$s_sn = 1;
				if($count > 0) {
					while($vehicle = $vehicleList->fetch_assoc()) { 
			?>
            <tr>
                <td class="text-center"><?php echo $s_sn; ?></td>
                <td><?php echo $vehicle['plate']; ?></td>
                <td><?php echo $vehicle['vin']; ?></td>
                <td><?php echo $vehicle['make']; ?></td>
                <td><?php echo $vehicle['model']; ?></td>
                <td><?php echo $vehicle['year']; ?></td>
                <?php
                    $result = $config->get_last_vehicle_km_by_car_id($vehicle['id']);
                    $km_last_info = $result->fetch_assoc();
                ?>
                <td><?php echo $km_last_info['km']; ?></td>
                <td><?php echo $km_last_info['add_date']; ?></td>

                <?php
                    $result = $config->getCrashedByCarId($vehicle['id']);
                    $crash_info = $result->fetch_assoc();
                ?>
                <td><?php echo $crash_info['crash']; ?></td>

                <td class="text-center">
                    <a href="home.php?query=vehicleedit&id=<?php echo $vehicle['id']; ?>">
                    	<i class="far fa-edit" style="float:left;"></i>
                    </a>
                    <a href="home.php?query=vehicledelete&id=<?php echo $vehicle['id']; ?>">
                    	<i class="far fa-trash-alt" style="float:right;"></i>
                    </a>
                </td>
            </tr>
            <?php $s_sn++; }} else {
			?>
            <td colspan="12">No any vehicle information found
            </td>
            <?php 
		        }
            ?>
        </tbody>
    </table>
</div>