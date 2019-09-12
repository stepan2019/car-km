<?php
    include "../setting/config.php";
    
    $id = $_POST['activeItem'];
    $isChecked = $_POST['checkItem'];

	$result = $config->userActive($id, $isChecked);

	echo $result;
?>
