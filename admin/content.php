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
$result = $config->getContentByCode($lang_id);
$content = $result->fetch_assoc();
$languages = $config->getEnableLanguages();

if (isset($_POST['add_content'])) {

    $content1 = $_POST['content1'];
    $content2 = $_POST['content2'];
    $lang_id = $_POST['lang_id'];

    $is_add = $config->add_content($content1, $content2, $lang_id);
    if ($is_add) {
        $result = $config->getContentByCode($lang_id);
        $content = $result->fetch_assoc();
        echo "<script>window.location = 'home.php?query=content&lang_id='" . $lang_id . ";</script>";
    } else {
        $response = "Sorry, is failed to add";
    }
}
?>
<div>
    <form method="post">
        <p>Content1</p>
        <script src="../css/ckeditor/ckeditor.js"></script>
        <div class="right_div" align="right" style="margin: 8px 10px 0 0;">
            <input name="lang_id" type="hidden" value="<?php echo $lang_id; ?>">
            <select name="lang" onchange="doSel(this);">
                <?php
                while ($row = $languages->fetch_assoc()) {
                    ?>
                    <option value="location.href='home.php?query=content&lang_id=<?php echo $row['code']; ?>'"
                            <?php if ($lang_id == $row['code']){ ?>selected<?php } ?>><?php echo $row['name']; ?></option>
                <?php } ?>
            </select>
        </div>
        <textarea id="ckeditor" class="ckeditor" name="content1"
                  style="min-height:200px;"><?php echo $content['content1'] ?></textarea>

        <p class="mt-5">Content2</p>
        <textarea id="ckeditor" class="ckeditor" name="content2"
                  style="min-height:200px;"><?php echo $content['content2'] ?></textarea>

        <button type="submit" class="btn btn-primary submit-fs btn-custom mt-5" name="add_content">OK</button>
        <a href="/admin" class="btn btn-primary submit-fs btn-custom mt-5">Cancel</a>

        <div>
            <?php if ($response != "") { ?>
                <p><label class="control-label mt-3"><?php echo $response; ?></label></p>
            <?php } ?>
        </div>

    </form>
</div>
