<?php

require_once "include/include.php";
require_once '../classes/database.php';
require_once $config_abs_path.'/admin/include/util.php';

global $lng;
$smarty = new Smarty;
$smarty = common($smarty);
$smarty->assign("tab", "tools");
$smarty->assign("lng", $lng);

$info = '';
$error = '';

$database = new database();
$backups_array = $database->readBackups();
$smarty->assign("backups_array", $backups_array);
$smarty->assign("no_backups", count($backups_array));

if (isset($_GET['download'])) {
    $download = escape($_GET['download']);
    $database->downloadFile($download);
}
global $config_live_site;
if (isset($_GET['restore'])) {
    $restore = escape($_GET['restore']);
    $path = $config_abs_path . '/db_backup/';
    if ($database->restoreDB($path . $restore)) {
        $info = $lng['database']['backup_restored'];
        ?>
        <script type="text/javascript">
            alert("<?php echo $info ?>");
            window.location = "<?php echo $config_live_site ?>/admin/db_tools.php"
        </script>
        <?php
    } else $error = $lng['database']['could_not_restore_backup'];
}

if (isset($_POST['Backup'])) {

    if (isset($_POST['compress']) && $_POST['compress'] == 1) $compress = 1; else $compress = 0;
    $database->setCompress($compress);

    global $limited_permissions;
    if ($config_demo == 1 || $limited_permissions) {
        $error = "Function disabled in the demo!";
    } else {
        $database->saveToFile();
        ?>
        <script type="text/javascript">
            window.location = "/admin/home.php?query=db_tools";
        </script>
        <?php

    }
}


if (isset($_POST['Restore'])) {

    global $limited_permissions;
    if ($config_demo == 1 || $limited_permissions) {
        $error = "Function disabled in the demo!";
    } else {

        global $config_abs_path;
        $path = $config_abs_path . '/temp';
        if ($_FILES['upload_restore']['name']) $extension = getExtension($_FILES['upload_restore']['name']);
        $filename = $_FILES['upload_restore']['name'];
        $path .= "/" . $filename;
        if ($extension != 'sql' && $extension != 'gz') $error = $lng['database']['invalid_backup_file'];
        else if (!move_uploaded_file($_FILES['upload_restore']['tmp_name'], $path))
            $error .= $lng['images']['errors']['file_not_uploaded'] . '<br>';

        if (!$error) {
            if ($database->restoreDB($path)) {
                $info = $lng['database']['backup_restored'];
                ?>
                <script type="text/javascript">
                    alert("<?php echo $info ?>");
                    window.location = "/admin/home.php?query=db_tools";
                </script>
                <?php
            } else $error = $lng['database']['could_not_restore_backup'];
        }
    } // end if demo
}
$schedule = $database->getSchedule();

if (isset($_POST['Save_schedule'])) {

    global $limited_permissions;
    if ($config_demo == 1 || $limited_permissions) {
        $error = "Function disabled in the demo!";
    } else {
        if (!$database->saveSchedule()) {
            $error = $database->getError();
            $schedule = $database->getTmp();
        } else {
            ?>
                <script type="text/javascript">
                    window.location = "/admin/home.php?query=db_tools";
                </script>
            <?php
        }
    }
}
$smarty->assign("schedule", $schedule);

$smarty->assign("info", $info);
$smarty->assign("error", $error);

$smarty->display('db_tools.html');
close();
?>
