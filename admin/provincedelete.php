<?php 
 	$id = $_GET['id'];
	$del_done = $config->deleteProvince($id);
	if($del_done == true) {
		echo "<script>window.location='home.php?query=province';</script>";
	}
?>
