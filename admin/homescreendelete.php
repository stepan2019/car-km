<?php 
 	$id = $_GET['id'];
	$del_done = $config->deleteHomescreen($id);
	if($del_done == true) {
		echo "<script>window.location = 'home.php?query=homescreenlist';</script>";
	} else {
		echo "<script>window.location = 'home.php?query=homescreenlist&&res=fail';</script>";
	}
?>