<?php 
 	$id = $_GET['id'];
	$del_done = $config->deleteInvoiceHistory($id);
	//if($del_done == true) {
		echo "<script>window.location = 'home.php?query=invoices';</script>";
	//}
?>
