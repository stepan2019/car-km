<?php

include "../include/include.php";
global $lng;
global $crt_lang_code;
if (isset($_GET['lang_id'])) {
    $lang_id = $_GET['lang_id'];
} else {
    $lang_id = $crt_lang_code;
}
$languages = $config->getEnableLanguages();

if (isset($_POST["submit"])) {
    $statusMsg = '';

    $targetDir = "../img/";
    $fileName = basename($_FILES["file"]["name"]);
    $targetFilePath = $targetDir . $fileName;
    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);

    if (isset($_POST["submit"]) /*&& !empty($_FILES["file"]["name"])*/) {
        $bannerText = $_POST['bannerText'];
        $position = $_POST['position'];
        $langId = $_POST['language'];
        $info = @getimagesize($_FILES["file"]["tmp_name"]);
        $width = $info[0];
        $height = $info[1];

        $allowTypes = array('jpg', 'png', 'jpeg', 'gif', 'swf');
        if (in_array($fileType, $allowTypes)) {
            if (move_uploaded_file($_FILES["file"]["tmp_name"], $targetFilePath)) {
                $insert = $config->imageUpload($fileName, $bannerText, $position, $width, $height, $langId);
                if ($insert) {
                    $statusMsg = "The file " . $fileName . " has been uploaded successfully.";
                } else {
                    $statusMsg = "File upload failed, please try again.";
                }
            } else {
                $statusMsg = "Sorry, there was an error uploading your file.";
            }
        } else {
            $statusMsg = 'Sorry, only JPG, JPEG, PNG, GIF, & SWF files are allowed to upload.';
        }
    } else {
        $statusMsg = 'Please select a file to upload.';
    }

    echo $statusMsg;
}
?>

<form method="post" enctype="multipart/form-data">
    <div class="col-md-12 row">
        <div class="col-md-12 mt-4">Language :</div>
        <div class="col-md-12">
            <div class="agileits-main">
                <select name="language" required="" class="banner-txt">
                    <?php
                    while ($row = $languages->fetch_assoc()) {
                        ?>
                        <option value="<?php echo $row['code']; ?>"><?php echo $row['name']; ?></option>
                    <?php } ?>
                </select>
            </div>
        </div>
    </div>
    <div class="col-md-4">Title :</div>
    <div class="col-md-8">
        <div class="agileits-main">
            <input type="text" name="bannerText" value="" required="" style="width: 300px; padding: 0.9em 1em;">
        </div>
    </div>

    <div class="col-md-4 mt-4">Select Image File to Upload :</div>
    <div class="col-md-8"><input type="file" name="file"></div>

    <div class="col-md-4 mt-4">Template position :</div>
    <div class="col-md-8">
        <div class="agileits-main">
            <select name="position" required="" style="padding: 0.9em 1em;">
                <option value="header">Header</option>
                <option value="footer">Footer</option>
            </select>
        </div>
    </div>
    <div class="col-md-12 mt-5"><input type="submit" name="submit" value="Add New Home Screen"
                                       class="btn btn-primary btn-custom"></div>
</form>
