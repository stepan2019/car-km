<?php 
 	$id = $_GET['id'];
	$del_done = $config->deleteUser($id);
	if($del_done == true) {
		echo "<script>window.location = 'home.php?query=userlist';</script>";
	} else {
		echo "<script>window.location='home.php?query=userlist'; alert('cant delete');</script>";
	}
?>