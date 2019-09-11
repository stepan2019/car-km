<?php
$fname = urldecode($_GET['fname']);
$filepath = "../db_backup/" . $fname;
if(file_exists($filepath)) {
    header("Pragma: public");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Content-Type: application/force-download");
    header("Content-Disposition: attachment; filename=" . basename($filepath));
    header("Content-Description: File Transfer");
    @readfile($filepath);
}else{
    exit('file is not exist');
}
exit;
