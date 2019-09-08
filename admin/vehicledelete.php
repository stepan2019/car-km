<?php 
 	$id = $_GET['id'];
	$del_done = $config->deleteVehicle($id);
	if($del_done == true) {
		$del_done = $config->deleteCrashByCarId($id);
		if($del_done == true) {
			$del_done = $config->deleteKmByCarId($id);
			if($del_done == true)
				echo "<script>window.location = 'home.php?query=vehiclelist';</script>";
			else
				echo "<script>window.location='home.php?query=vehiclelist'; alert('cant delete');</script>";
		} else {
			echo "<script>window.location='home.php?query=vehiclelist'; alert('cant delete');</script>";
		}
	} else {
		echo "<script>window.location='home.php?query=vehiclelist'; alert('cant delete');</script>";
	}
?>