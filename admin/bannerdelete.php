<?php 
 	$id = $_GET['id'];
	$del_done = $config->deleteBanner($id);
	if($del_done == true) {
		echo "<script>window.location = 'home.php?query=bannerlist';</script>";
	} else {
		echo "<script>window.location = 'home.php?query=bannerlist&&res=fail';</script>";
	}
?>