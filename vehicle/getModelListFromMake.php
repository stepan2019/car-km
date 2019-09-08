<?php
    include "../setting/config.php";
    
    $selectedMake = $_POST['selectedMake'];

    $result = $config->getModelListFromMake($selectedMake);

    $dbdata = array();

    while ( $row = $result->fetch_assoc())  {
        $dbdata[] = $row;
    }

    //Print array in JSON format
    echo json_encode($dbdata);
?>