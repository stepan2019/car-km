<?php
    include "../setting/config.php";
    
    $id = $_POST['activeItem'];
    $isChecked = $_POST['checkItem'];

	$result = $config->dealerActive($id, $isChecked);

	echo $result;
?>
