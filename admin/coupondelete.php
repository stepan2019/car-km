<?php 
 	$id = $_GET['id'];
	$del_done = $config->deleteCoupon($id);
	//if($del_done == true) {
		echo "<script>window.location = 'home.php?query=coupons_list';</script>";
	//}
?>