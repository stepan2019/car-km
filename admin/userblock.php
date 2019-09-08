<?php
    include "../setting/config.php";
    
    $id = $_POST['blockItem'];
    $isChecked = $_POST['checkItem'];

    $isChecked = ($isChecked == "True") ? "False" : "True";

	$result = $config->userBlock($id, $isChecked);

	echo $result;
?>