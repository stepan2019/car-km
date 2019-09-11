<?php

$response = "";

include "../include/include.php";

global $lng;
global $crt_lang_code;
if (isset($_GET['lang_id'])) {
    $lang_id = $_GET['lang_id'];
} else {
    $lang_id = $crt_lang_code;
}
$result = $config->getInformationContentByCode($lang_id);
$information = $result->fetch_assoc();

$languages = $config->getEnableLanguages();

if (isset($_POST['add_information'])) {
    $content = $_POST['content'];
    $lang_id = $_POST['lang_id'];

    $is_success = $config->add_information_content($content, $lang_id);
    if ($is_success) {
        $result = $config->getInformationContentByCode($lang_id);
        $information = $result->fetch_assoc();
        echo "<script>window.location = 'home.php?query=information&lang_id='" . $lang_id . ";</script>";
    } else {
        $response = "Sorry, is failed to add";
    }
}
?>
<div>
    <form method="post">
        <p>Content</p>
        <script src="../css/ckeditor/ckeditor.js"></script>

        <div class="right_div" align="right" style="margin: 8px 10px 0 0;">
            <input name="lang_id" type="hidden" value="<?php echo $lang_id; ?>">
            <select name="lang" onchange="doSel(this);">
                <?php
                while ($row = $languages->fetch_assoc()) {
                    ?>
                    <option value="location.href='home.php?query=information&lang_id=<?php echo $row['code']; ?>'"
                            <?php if ($lang_id == $row['code']){ ?>selected<?php } ?>><?php echo $row['name']; ?></option>
                <?php } ?>
            </select>
        </div>

        <textarea id="ckeditor" class="ckeditor" name="content"
                  style="min-height:500px;"><?php echo $information['content'] ?></textarea>

        <?php if ($response != "") { ?>
            <p><label class="control-label mt-3"><?php echo $response; ?></label></p>
        <?php } ?>

        <button type="submit" class="btn btn-primary btn-custom" name="add_information">OK</button>
        <a href="/admin" class="btn btn-primary btn-custom">Cancel</a>

    </form>
</div>
