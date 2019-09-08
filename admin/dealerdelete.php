<?php 
 	$id = $_GET['id'];
	$del_done = $config->deleteDealer($id);
	if($del_done == true) {
		echo "<script>window.location = 'home.php?query=dealerlist';</script>";
	} else {
		echo "<script>window.location='home.php?query=dealerlist'; alert('cant delete');</script>";
	}
?>