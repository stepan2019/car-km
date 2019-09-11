<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/
$path = dirname(__FILE__);
require_once($path . '/include.php');

my_session_start();

if (!$_SERVER['HTTP_REFERER']) exit(0);
require_once $config_abs_path . "/classes/settings.php";
global $config_live_site;
$auth = new auth();
global $is_admin, $is_mod;
$is_admin = $auth->adminLoggedIn();
$is_mod = $auth->modLoggedIn();

//$is_admin=1;

//if(!$is_admin && !$is_mod) { header("Location: ".$config_live_site."/not_authorized.php"); exit(0); }

global $appearance_settings;
header('Content-type: text/html; charset=' . $appearance_settings['charset']);

$arr_action = array("enable", "disable", "block", "unblock", "delete", "move_up", "move_down", "clear_hits", "sold", "rented", "unsold", "unrented", "feature", "unfeature", "renew", "accept", "deny", "accept_featured", "install", "uninstall", "default", "pending", "not_pending", "edit", "save", "move", "delete_file", "send", "mark_paid", "whitelist", "resend", "make", "exists");
$arr_object = array("user", "group", "navbar", "cf", "categ", "banner_categ", "banners", "listing", "picture", "order", "custom_page", "pkg", "uf", "currency", "pri", "usr_pkg", "module", "rss", "language", "payment", "ie_template", "db", "store", "bulk_uploads", "info", "mail", "processor_title", "processor_recurring", "coupon", "fieldset", "search", "alert", "msg", "rule", "fortumo", "credits", "sch", "rev", "aff_payment", "pe", "blockedip", "whitelistedip", "blockedemail", "whitelistedemail", "smsg", "user_sms", "listing_sms", "invoice", "blockedphone", "whitelistedphone", "tpay", "mobilpay", "slug", "fp");

if (isset($_GET['action'])) {

    if (!in_array($_GET['action'], $arr_action)) exit(0);
    $action = $_GET['action'];

} else exit(0);

if (isset($_GET['object'])) {

    if (!in_array($_GET['object'], $arr_object)) exit(0);
    $object = $_GET['object'];

} else exit(0);

global $settings;

if ($is_mod) {
    global $settings;
    $usr = new users;

    if ($settings['enable_username'])
        $mod_id = users::getUserId($is_mod);
    else
        $mod_id = users::getIdByEmail($is_mod);

    $mod_sections = $usr->getSections($mod_id);
}
switch ($object) {

    case 'listing':

        $id = get_numeric_only("id");

        require_once $config_abs_path . "/classes/fields.php";
        require_once $config_abs_path . "/classes/depending_fields.php";
        require_once $config_abs_path . "/classes/categories.php";
        require_once $config_abs_path . "/classes/packages.php";
        require_once $config_abs_path . "/classes/listings.php";
        require_once $config_abs_path . "/classes/pictures.php";

        $listing = new listings();
        if ($action == "delete" && ($is_admin || $mod_sections['listings']['delete'])) $listing->delete($id);

        else if ($is_admin || $mod_sections['listings']['edit']) {

            if ($action == "enable") $listing->Activate($id);
            else if ($action == "disable") $listing->Deactivate($id);
            else if ($action == "sold") $listing->markSold($id);
            else if ($action == "unsold") $listing->markUnsold($id);
            else if ($action == "rented") $listing->markRented($id);
            else if ($action == "unrented") $listing->markUnrented($id);
            else if ($action == "renew") $listing->renew($id);
            else if ($action == "accept") {
                $listing->ActivatePending($id);

                // if expired renew
                if ($listing->isExpired($id)) {
                    require_once $config_abs_path . "/classes/priorities.php";
                    require_once $config_abs_path . "/classes/featured_plans.php";
                    $listing->renew($id);
                    $listing->renewOptions($id);
                }

            } else if ($action == "block") {
                require_once $config_abs_path . "/classes/users.php";
                require_once $config_abs_path . "/classes/blocked_ips.php";
                $listing->Block($id);
            } else if ($action == "unblock") {
                require_once $config_abs_path . "/classes/users.php";
                $listing->Unblock($id);
            } elseif ($action == "delete_file") {

                // delete a file or image from a listing
                $field_name = escape($_GET['field_name']);
                if (!$field_name) exit(0);
                global $db;
                $fname = $db->fetchRow("select `$field_name` from " . TABLE_ADS . " where id = '$id'");
                $db->query("update " . TABLE_ADS . " set `$field_name`='' where id = '$id'");
                $file_name = $config_abs_path . "/uploads/" . $field_name . "/" . $fname;
                if ($fname && file_exists($file_name)) @unlink($file_name);

            }
        }

        break;

    case 'user':
        require_once $config_abs_path . "/classes/users_packages.php";
        require_once $config_abs_path . "/classes/users.php";

        $usr = new users;
        $id = get_numeric_only("id");
        if ($is_admin || $mod_sections['users']['edit']) {
            if ($action == "enable") $usr->Enable($id);
            else if ($action == "disable") $usr->Disable($id);
            else if ($action == "block") {
                require_once $config_abs_path . "/classes/blocked_ips.php";
                $usr->Block($id);
            } else if ($action == "unblock") $usr->Unblock($id);
        }
        if ($action == "delete" && ($is_admin || $mod_sections['users']['delete'])) $usr->delete($id);

        elseif ($action == "delete_file") {

            // delete a file or image from a user info
            $field_name = escape($_GET['field_name']);
            if (!$field_name) exit(0);
            global $db;
            $fname = $db->fetchRow("select `$field_name` from " . TABLE_USERS . " where id = '$id'");
            $db->query("update " . TABLE_USERS . " set `$field_name`='' where id = '$id'");
            $file_name = $config_abs_path . "/uploads/" . $field_name . "/" . $fname;
            if ($fname && file_exists($file_name)) @unlink($file_name);

        }

        break;

    case 'group':

        if (!$is_admin) break;

        require_once $config_abs_path . "/classes/users.php";
        require_once $config_abs_path . "/classes/users_packages.php";
        require_once $config_abs_path . "/classes/config/groups_config.php";

        $id = get_numeric_only("id");
        $gr = new groups_config;

        if (!strcmp($action, "enable")) {
            require_once $config_abs_path . "/classes/groups.php";
            require_once $config_abs_path . "/classes/config/settings_config.php";
            $gr->Enable($id);
        } else if (!strcmp($action, "disable")) {
            require_once $config_abs_path . "/classes/groups.php";
            require_once $config_abs_path . "/classes/config/settings_config.php";
            $gr->Disable($id);
        } else if (!strcmp($action, "delete")) $gr->delete($id);
        else if (!strcmp($action, "move_up")) $gr->moveUp($id);
        else if (!strcmp($action, "move_down")) $gr->moveDown($id);

        break;

    case 'navbar':

        if (!$is_admin) break;

        $id = get_numeric_only("id");
        $navbar = new navbar();

        if (!strcmp($action, "enable")) $navbar->Enable($id);
        else if (!strcmp($action, "disable")) $navbar->Disable($id);
        else if (!strcmp($action, "delete")) $navbar->delete($id);
        else if (!strcmp($action, "move_up")) $navbar->moveUp($id);
        else if (!strcmp($action, "move_down")) $navbar->moveDown($id);

        break;

    case 'cf':

        if (!$is_admin) break;

        require_once $config_abs_path . "/classes/fields.php";
        require_once $config_abs_path . "/classes/config/fields_config.php";
        require_once $config_abs_path . "/classes/depending_fields.php";
        require_once $config_abs_path . "/classes/config/depending_fields_config.php";
        //require_once $config_abs_path."/admin/include/lists.php";

        $cf = new fields_config('cf');

        if (!strcmp($action, "delete")) {
            $id = get_numeric_only("id");
            $cf->delete($id);
        }
        if (!strcmp($action, "enable")) {
            $id = get_numeric_only("id");
            $cf->Enable($id);
        }
        if (!strcmp($action, "disable")) {
            $id = get_numeric_only("id");
            $cf->Disable($id);
        }
        if ($action == "move_up") {
            $order_no = get_numeric_only("order_no");
            $fieldset = get_numeric("fieldset", 0);
            $order_to = $cf->moveUp($order_no, $fieldset);
            echo $order_to;
        }
        if ($action == "move_down") {
            $order_no = get_numeric_only("order_no");
            $fieldset = get_numeric("fieldset", 0);
            $order_to = $cf->moveDown($order_no, $fieldset);
            echo $order_to;
        }
        break;

    /*case 'fset':

        if(!$is_admin) break;

        require_once $config_abs_path."/classes/config/fieldsets_config.php";
        require_once $config_abs_path."/classes/fields.php";
        require_once $config_abs_path."/classes/config/fields_config.php";
        require_once $config_abs_path."/classes/depending_fields.php";
        require_once $config_abs_path."/classes/config/depending_fields_config.php";
        //require_once $config_abs_path."/admin/include/lists.php";

        $id = get_numeric_only("id");
        $fset = new fieldsets_config();
        if(!strcmp($action,"delete")) $fset->delete($id);
    break;
*/
    case 'fieldset':

        if (!$is_admin) break;

        require_once $config_abs_path . "/classes/fieldsets.php";
        require_once $config_abs_path . "/classes/config/fieldsets_config.php";
        require_once $config_abs_path . "/classes/fields.php";
        require_once $config_abs_path . "/classes/config/fields_config.php";
        require_once $config_abs_path . "/classes/depending_fields.php";
        require_once $config_abs_path . "/classes/config/depending_fields_config.php";
        //require_once $config_abs_path."/admin/include/lists.php";

        $id = get_numeric_only("id");
        $fset = new fieldsets_config();
        if (!strcmp($action, "delete")) $fset->delete($id);
        break;

    case 'categ':

        if (!$is_admin) break;

        require_once $config_abs_path . "/classes/config/categories_config.php";
        require_once $config_abs_path . "/classes/categories.php";
        require_once $config_abs_path . "/classes/listings.php";

        $cat_config = new categories_config();

        if (!strcmp($action, "delete")) {
            $id = get_numeric_only("id");
            $cat_config->delete($id);
        } else if (!strcmp($action, "move")) {
            $order_no = get_numeric_only("order_no");
            $move_to = get_numeric_only("move_to");
            $order_to = $cat_config->move($order_no, $move_to);
            echo $order_to;
        } else if (!strcmp($action, "enable")) {
            $id = get_numeric_only("id");
            $cat_config->Enable($id);
        } else if (!strcmp($action, "disable")) {
            $id = get_numeric_only("id");
            $cat_config->Disable($id);
        }
        break;

    case 'banner_categ':

        if (!$is_admin) break;

        require_once $config_abs_path . "/classes/config/banners_config.php";

        $id = get_numeric_only("id");
        $bc = new banners_config();
        if (!strcmp($action, "move_up")) $bc->moveUp($id);
        else if (!strcmp($action, "move_down")) $bc->moveDown($id);
        else if (!strcmp($action, "enable")) $bc->EnablePosition($id);
        else if (!strcmp($action, "disable")) $bc->DisablePosition($id);
        break;

    case 'banners':
        require_once $config_abs_path . "/classes/config/banners_config.php";
        require_once $config_abs_path . "/classes/banners.php";

        $id = get_numeric_only("id");
        $bc = new banners_config();

        if ($action == "delete" && ($is_admin || $mod_sections['banners']['delete'])) $bc->delete($id);
        else if ($action == "clear_hits" && ($is_admin || $mod_sections['banners']['edit'])) $bc->clearHits($id);

        break;

    case 'order':
        require_once $config_abs_path . "/classes/payment_actions.php";

        $id = get_numeric_only("id");
        $order = new payment_actions();

        if ($action == "delete" && ($is_admin || $mod_sections['orders']['delete'])) $order->delete($id);
        else if ($is_admin || $mod_sections['orders']['edit']) {
            if (!strcmp($action, "enable")) $order->Activate($id);
            else if (!strcmp($action, "disable")) $order->Deactivate($id);
        }
        break;

    case 'picture':
        $id = get_numeric_only("id");
        if ($action == "delete" && ($is_admin || $mod_sections['listings']['edit'])) pictures::delete($id);
        break;

    case 'custom_page':

        require_once $config_abs_path . "/classes/config/custom_pages_config.php";

        $id = get_numeric_only("id");
        $cp = new custom_pages_config();

        if ($action == "delete" && ($is_admin || $mod_sections['custom_pages']['delete'])) $cp->delete($id);
        else if ($action == "enable" && ($is_admin || $mod_sections['custom_pages']['edit'])) $cp->Activate($id);
        else if ($action == "disable" && ($is_admin || $mod_sections['custom_pages']['edit'])) $cp->Deactivate($id);
        else if ($action == "move_up" && ($is_admin || $mod_sections['custom_pages']['edit'])) $cp->moveUp($id);
        else if ($action == "move_down" && ($is_admin || $mod_sections['custom_pages']['edit'])) $cp->moveDown($id);

        break;

    case 'pkg':

        if (!$is_admin) break;

        require_once $config_abs_path . "/classes/config/packages_config.php";
        $id = get_numeric_only("id");
        $pkg = new packages_config();

        if (!strcmp($action, "delete")) $pkg->delete($id);
        else if (!strcmp($action, "move_up")) $pkg->moveUp($id);
        else if (!strcmp($action, "move_down")) $pkg->moveDown($id);
        else if ($action == "enable") $pkg->Activate($id);
        else if ($action == "disable") $pkg->Deactivate($id);

        break;

    case 'uf':

        if (!$is_admin) break;

        require_once $config_abs_path . "/classes/fields.php";
        require_once $config_abs_path . "/classes/config/fields_config.php";
        require_once $config_abs_path . "/classes/depending_fields.php";
        require_once $config_abs_path . "/classes/config/depending_fields_config.php";
        require_once $config_abs_path . "/admin/include/lists.php";

        $uf = new fields_config('uf');

        if (!strcmp($action, "delete")) {
            $id = get_numeric_only("id");
            $uf->delete($id);
        }
        if (!strcmp($action, "enable")) {
            $id = get_numeric_only("id");
            $uf->Enable($id);
        }
        if (!strcmp($action, "disable")) {
            $id = get_numeric_only("id");
            $uf->Disable($id);
        }
        if ($action == "move_up") {

            $order_no = get_numeric_only("order_no");
            $order_to = $uf->moveUp($order_no);
            echo $order_to;
        }
        if ($action == "move_down") {
            $order_no = get_numeric_only("order_no");
            $order_to = $uf->moveDown($order_no);
            echo $order_to;
        }
        break;

    case 'pri':

        if (!$is_admin) break;

        require_once $config_abs_path . "/classes/priorities.php";
        require_once $config_abs_path . "/classes/config/priorities_config.php";

        $id = get_numeric_only("id");
        $pri = new priorities_config;
        if (!strcmp($action, "delete")) $pri->delete($id);
        else if (!strcmp($action, "move_up")) $pri->moveUp($id);
        else if (!strcmp($action, "move_down")) $pri->moveDown($id);
        else if ($action == "edit") {

            if (isset($_GET['name']) && isset($_GET['price'])) {
                $pri_name = escape($_GET['name']);
                $pri_price = escape($_GET['price']);
                if (!$pri->edit($id, $pri_name, $pri_price)) {
                    $err = $pri->getError();
                    echo $err;
                } else echo 1;
            }

        }
        break;

    case 'fp':

        if (!$is_admin) break;

        require_once $config_abs_path . "/classes/featured_plans.php";
        require_once $config_abs_path . "/classes/config/featured_plans_config.php";

        $id = get_numeric_only("id");
        $fp = new featured_plans_config();
        if (!strcmp($action, "delete")) $fp->delete($id);
        else if (!strcmp($action, "move_up")) $fp->moveUp($id);
        else if (!strcmp($action, "move_down")) $fp->moveDown($id);

        break;

    case 'processor_title':

        if (!$is_admin) break;

        require_once($config_abs_path . "/classes/payment.php");
        require_once($config_abs_path . "/classes/payment_processors.php");

        $pp_list = payment_processors::getAllPPList();
        foreach ($pp_list as $pp) {
            require_once($config_abs_path . '/classes/payment/' . $pp . '.php');
        }

        if ($action == "edit") {

            if (isset($_GET['id'])) $id = escape($_GET['id']); else exit(0);
            $pp = new payment_processors;
            if (isset($_GET['title'])) {
                $title = escape($_GET['title']);
                if (!$pp->editTitle($id, $title)) {
                    $err = $pp->getError();
                    echo $err;
                } else echo 1;
            }

        }
        break;

    case 'processor_recurring':

        if (!$is_admin) break;

        require_once($config_abs_path . "/classes/payment.php");
        require_once($config_abs_path . "/classes/payment_processors.php");

        $pp_list = payment_processors::getAllPPList();
        foreach ($pp_list as $pp) {
            require_once($config_abs_path . '/classes/payment/' . $pp . '.php');
        }

        if ($action == "edit") {

            if (isset($_GET['id'])) $id = escape($_GET['id']); else exit(0);
            $pp = new payment_processors;
            if (isset($_GET['recurring'])) {
                $recurring = escape($_GET['recurring']);
                if (!$pp->editRecurring($id, $recurring)) {
                    $err = $pp->getError();
                    echo $err;
                } else echo 1;
            }

        }
        break;

    case 'currency':

        if (!$is_admin) break;

        require_once $config_abs_path . "/classes/currencies.php";

        $id = get_numeric_only("id");
        $currency = new currencies();

        if (!strcmp($action, "delete")) $currency->delete($id);
        else if (!strcmp($action, "move_up")) $currency->moveUp($id);
        else if (!strcmp($action, "move_down")) $currency->moveDown($id);
        break;

    case 'usr_pkg':

        require_once $config_abs_path . "/classes/users_packages.php";
        require_once $config_abs_path . "/classes/packages.php";


        $id = get_numeric_only("id");
        $usr_pkg = new users_packages();

        if (($action == "enable" || $action == "disable") && ($is_admin || $mod_sections['subscriptions']['edit'])) {
            if ($action == "enable") $usr_pkg->Enable($id);
            else if ($action == "disable") $usr_pkg->Disable($id);
        } else if ($action == "delete" && ($is_admin || $mod_sections['subscriptions']['delete'])) $usr_pkg->delete($id);
        break;

    case 'module':

        if (!$is_admin) break;

        require_once $config_abs_path . "/classes/database.php";
        require_once $config_abs_path . "/classes/modules.php";

        if (isset($_GET['id'])) $id = escape($_GET['id']); else exit(0);
        $mod = new modules;
        if (!strcmp($action, "enable")) $mod->Enable($id);
        else if (!strcmp($action, "disable")) $mod->Disable($id);
        else if (!strcmp($action, "install")) $mod->Install($id);
        else if (!strcmp($action, "uninstall")) $mod->Uninstall($id);
        break;

    case 'rss':

        if (!$is_admin) break;

        require_once $config_abs_path . "/classes/config/rss_config.php";
        $id = get_numeric_only("id");
        $rss = new rss_config;
        if (!strcmp($action, "enable")) $rss->Enable($id);
        else if (!strcmp($action, "disable")) $rss->Disable($id);
        else if (!strcmp($action, "delete")) $rss->delete($id);
        break;

    case 'language':
//		if(!$is_admin) break;

        require_once $config_abs_path . "/classes/depending_fields.php";
        require_once $config_abs_path . "/classes/config/depending_fields_config.php";
        require_once $config_abs_path . "/classes/config/languages_config.php";
        require_once $config_abs_path . "/classes/config/settings_config.php";
        require_once $config_abs_path . "/classes/listings.php";

        if (isset($_GET['id'])) $id = escape($_GET['id']); else exit(0);
        $language = new languages_config;
        if ($action == "delete") $language->delete($id);
        if ($action == "enable") $language->Enable($id);
        if ($action == "disable") $language->Disable($id);
        if ($action == "default") $language->changeDefault($id);
        if ($action == "move_up") $language->moveUp($id);
        if ($action == "move_down") $language->moveDown($id);
        break;

    case 'payment':

        if (!$is_admin) break;

        require_once($config_abs_path . "/classes/payment.php");
        require_once($config_abs_path . "/classes/payment_processors.php");

        $pp_list = payment_processors::getAllPPList();
        foreach ($pp_list as $pp) {
            require_once($config_abs_path . '/classes/payment/' . $pp . '.php');
        }

        if (isset($_GET['id'])) $id = escape($_GET['id']); else exit(0);
        $pp = new payment_processors;
        if ($action == "enable") {
            $result = $pp->Enable($id);
            if (!$result) {
                $error = $pp->getError();
                echo $error;
            }
        }
        if ($action == "disable") $pp->Disable($id);
        if ($action == "pending") $pp->setPending($id);
        if ($action == "not_pending") $pp->setNotPending($id);
        break;

    case 'ie_template':

        if (!$is_admin) break;

        require_once $config_abs_path . "/classes/import_export.php";
        if (isset($_GET['id'])) $id = escape($_GET['id']); else exit(0);
        $ie = new import_export;
        if ($action == "delete") $ie->deleteTemplate($id);
        break;

    case 'db':

//        if (!$is_admin) break;

        require_once $config_abs_path . "/classes/database.php";
        if (isset($_GET['id'])) $id = escape($_GET['id']); else exit(0);
        $database = new database;
        if ($action == "delete") $result = $database->delete($id);
        break;

    case 'store':

        if (!$is_admin && !$mod_sections['users']['edit']) break;

        require_once $config_abs_path . "/classes/users.php";
        $usr = new users;
        $id = get_numeric_only("id");
        if (!strcmp($action, "enable")) $usr->enableStore($id);
        else if (!strcmp($action, "disable")) $usr->disableStore($id);

        break;

    case 'bulk_uploads':

        if (!$is_admin && !$mod_sections['users']['edit']) break;

        require_once $config_abs_path . "/classes/users.php";
        $usr = new users;
        $id = get_numeric_only("id");
        if (!strcmp($action, "enable")) $usr->enableBulkUploads($id);
        else if (!strcmp($action, "disable")) $usr->disableBulkUploads($id);

        break;

    case 'info':

        if (!$is_admin) break;

        require_once $config_abs_path . "/classes/info.php";

        $info_templates = new info();
        if (!strcmp($action, "save")) {

            if (isset($_GET['language'])) $language = escape($_GET['language']); else exit(0);
            if (isset($_GET['code'])) $code = escape($_GET['code']); else exit(0);
            if (isset($_GET['content'])) $content = escape($_GET['content']); else exit(0);
            $info_templates->update($language, $code, $content);

        }

        break;

    case 'mail':

        if (!$is_admin) break;

        $mail_templates = new mail_templates();
        if (!strcmp($action, "save")) {

            if (isset($_GET['language'])) $language = escape($_GET['language']); else exit(0);
            if (isset($_GET['code'])) $code = escape($_GET['code']); else exit(0);
            if (isset($_GET['subject'])) $subject = escape($_GET['subject']); else exit(0);
            if (isset($_GET['content'])) $content = escape($_GET['content']); else exit(0);
            $mail_templates->update($language, $code, $subject, $content);
        }

        break;

    case 'coupon':

        if (!$is_admin) break;

        require_once $config_abs_path . "/classes/config/coupons_config.php";

        $coupon = new coupons_config();
        if (!strcmp($action, "delete")) {

            if (isset($_GET['id']) && is_numeric($_GET['id'])) $id = escape($_GET['id']); else exit(0);
            $coupon->delete($id);
        }

        break;

    case 'search':

        if (!$is_admin && !$mod_sections['searches']['delete']) break;

        require_once $config_abs_path . "/classes/saved_searches.php";

        $id = get_numeric_only("id");
        $search = new saved_searches();

        if (!strcmp($action, "delete")) $search->delete($id);

        break;

    case 'alert':

        require_once $config_abs_path . "/classes/alerts.php";

        $id = get_numeric_only("id");
        $alert = new alerts();

        if ($action == "delete" && ($is_admin || $mod_sections['alerts']['delete'])) $alert->delete($id);
        if ($is_admin || $mod_sections['alerts']['edit']) {
            if (!strcmp($action, "enable")) $alert->Enable($id);
            if (!strcmp($action, "disable")) $alert->Disable($id);
        }

        break;

    case 'msg':

        $id = get_numeric_only("id");
        require_once $config_abs_path . "/classes/messages.php";
        $msg = new messages();

        if ($action == "delete" && ($is_admin || $mod_sections['messages']['delete'])) $msg->delete($id);
        else if ($is_admin || $mod_sections['alerts']['edit']) {

            if ($action == "block") $msg->Block($id);
            else if ($action == "unblock") $msg->Unblock($id);
            else if ($action == "send") {
                if ($msg->isPending($id)) $msg->send($id);
            }
        }

        break;

    case 'rule':

        if (!$is_admin) break;

        require_once $config_abs_path . "/classes/rules.php";
        $id = get_numeric_only("id");

        if ($action == "delete") {
            $r = new rules;
            $r->delete($id);
        }

        if ($action == "move_up") {
            $r = new rules;
            $r->moveUp($id);
        }

        if ($action == "move_down") {
            $r = new rules;
            $r->moveDown($id);
        }

        break;
    case 'fortumo':

        if (!$is_admin) break;

        require_once $config_abs_path . "/classes/payment/fortumo.php";
        $id = get_numeric_only("id");

        if ($action == "delete") {
            $f = new fortumo;
            $f->deleteProduct($id);
        }

        break;

    case 'credits':

        if (!$is_admin) break;

        require_once $config_abs_path . "/classes/credits.php";
        $id = get_numeric_only("id");
        $cr = new credits();

        if (!strcmp($action, "delete")) $cr->deletePackage($id);
        else if (!strcmp($action, "move_up")) $cr->moveUp($id);
        else if (!strcmp($action, "move_down")) $cr->moveDown($id);
        break;

    case 'sch':
        require_once $config_abs_path . "/classes/scheduled_imports.php";
        if (isset($_GET['id']) && is_numeric($_GET['id'])) $id = $_GET['id']; else exit(0);
        $si = new scheduled_imports();

        if (!strcmp($action, "delete")) $si->delete($id);
        else if (!strcmp($action, "enable")) $si->Enable($id);
        else if (!strcmp($action, "disable")) $si->Disable($id);

        break;

    case 'rev':
        require_once $config_abs_path . "/classes/affiliates.php";
        if (isset($_GET['id']) && is_numeric($_GET['id'])) $id = $_GET['id']; else exit(0);
        $aff = new affiliates();

        if (!strcmp($action, "delete")) $aff->deleteRev($id);

        break;

    case 'aff_payment':
        require_once $config_abs_path . "/classes/affiliates.php";
        if (isset($_GET['id']) && is_numeric($_GET['id'])) $id = $_GET['id']; else exit(0);
        $aff = new affiliates();

        if (!strcmp($action, "mark_paid")) $aff->markPaymentPaid($id, $_GET['paid']);

        break;

    case 'pe':

        if (isset($_GET['id']) && is_numeric($_GET['id'])) $id = $_GET['id']; else exit(0);

        global $config_abs_path;
        require_once $config_abs_path . "/classes/listings.php";
        require_once $config_abs_path . "/classes/listings_process.php";
        require_once $config_abs_path . "/classes/users.php";
        require_once $config_abs_path . "/classes/pictures.php";

        $lp = new listings_process();

        if (!strcmp($action, "accept")) $lp->acceptPendingEdited($id);
        if (!strcmp($action, "deny")) $lp->denyPendingEdited($id);

        break;

    case 'blockedip':


        global $config_abs_path;
        require_once $config_abs_path . "/classes/blocked_ips.php";
        require_once $config_abs_path . "/classes/validator.php";

        $bi = new blocked_ips();

        if (!strcmp($action, "delete") && ($is_admin || $mod_sections['security']['delete'])) {
            if (isset($_GET['id']) && is_numeric($_GET['id'])) $id = escape($_GET['id']); else exit(0);
            $bi->delete($id);
        }
        if (!strcmp($action, "whitelist") && ($is_admin || $mod_sections['security']['edit'])) {
            if (isset($_GET['ip'])) $ip = escape($_GET['ip']); else exit(0);
            $bi->addToWhitelist($ip);
        }

        break;

    case 'whitelistedip':

        if (isset($_GET['id']) && is_numeric($_GET['id'])) $id = escape($_GET['id']); else exit(0);

        global $config_abs_path;
        require_once $config_abs_path . "/classes/blocked_ips.php";

        $bi = new blocked_ips();

        if (!strcmp($action, "delete") && ($is_admin || $mod_sections['security']['delete'])) {
            $bi->deleteFromWhitelist($id);
        }

        break;

    case 'blockedemail':


        global $config_abs_path;
        require_once $config_abs_path . "/classes/blocked_emails.php";
        require_once $config_abs_path . "/classes/validator.php";

        $be = new blocked_emails();

        if (!strcmp($action, "delete") && ($is_admin || $mod_sections['security']['delete'])) {
            if (isset($_GET['id']) && is_numeric($_GET['id'])) $id = escape($_GET['id']); else exit(0);
            $be->delete($id);
        }
        if (!strcmp($action, "whitelist") && ($is_admin || $mod_sections['security']['edit'])) {
            if (isset($_GET['email'])) $email = escape($_GET['email']); else exit(0);
            $be->addToWhitelist($email);
        }

        break;

    case 'whitelistedemail':

        if (isset($_GET['id']) && is_numeric($_GET['id'])) $id = escape($_GET['id']); else exit(0);

        global $config_abs_path;
        require_once $config_abs_path . "/classes/blocked_emails.php";

        $be = new blocked_emails();

        if (!strcmp($action, "delete") && ($is_admin || $mod_sections['security']['delete'])) {
            $be->deleteFromWhitelist($id);
        }

        break;

    case 'smsg':

        if (isset($_GET['gateway'])) $gateway = escape($_GET['gateway']); else exit(0);

        global $config_abs_path;
        require_once $config_abs_path . "/classes/sms_gateways.php";

        $sg = new sms_gateways();

        if (!strcmp($action, "edit")) {
            if (isset($_GET['title'])) $title = escape($_GET['title']);
            if ($title) $sg->setTitle($gateway, $title);
        }

        if (!strcmp($action, "default")) {

            $class_name = sms_gateways::getSMSGatewayClass($gateway);
            require_once($config_abs_path . '/classes/sms_verification/' . $class_name . '.php');

            $response = $sg->setDefault($gateway);
            if ($response == 0) $response = $sg->getError();
            echo $response;
        }

        break;

    case 'user_sms':

        if (isset($_GET['id']) && is_numeric($_GET['id'])) $id = escape($_GET['id']); else exit(0);
        if ($action == "resend") {

            require_once $config_abs_path . "/classes/users.php";
            // send SMS
            $usr = new users();
            $user_details = $usr->getUser($id);
            $group = $user_details['group'];
            $phone_field = $usr->getRequiredIntlPhoneField($group);
            $phone_no = $user_details[$phone_field];
            if ($phone_no) {

                global $config_abs_path;
                require_once($config_abs_path . '/classes/sms_gateways.php');

                // get default SMS gateway
                $sg = new sms_gateways();
                $default = $sg->getDefault();
                if ($default) {

                    $class_name = sms_gateways::getSMSGatewayClass($default);
                    require_once($config_abs_path . '/classes/sms_verification/' . $class_name . '.php');

                    $gcl = new $class_name;
                    $sent = $gcl->send($phone_no, $user_details['activation'], $id);

                }  // end if default

            }// end if phone no

        }
        break;

    case 'invoice':
        require_once $config_abs_path . "/classes/invoices.php";

        $id = get_numeric_only("id");
        $inv = new invoices();

        if ($action == "delete" && ($is_admin || $mod_sections['orders']['delete'])) $inv->delete($id);
        break;


    case 'blockedphone':


        global $config_abs_path;
        require_once $config_abs_path . "/classes/blocked_phones.php";
        require_once $config_abs_path . "/classes/validator.php";

        $bp = new blocked_phones();

        if (!strcmp($action, "delete") && ($is_admin || $mod_sections['security']['delete'])) {
            if (isset($_GET['id']) && is_numeric($_GET['id'])) $id = escape($_GET['id']); else exit(0);
            $bp->delete($id);
        }
        if (!strcmp($action, "whitelist") && ($is_admin || $mod_sections['security']['edit'])) {
            if (isset($_GET['phone'])) $phone = escape($_GET['phone']); else exit(0);
            $bp->addToWhitelist($phone);
        }

        break;

    case 'whitelistedphone':

        if (isset($_GET['id']) && is_numeric($_GET['id'])) $id = escape($_GET['id']); else exit(0);

        global $config_abs_path;
        require_once $config_abs_path . "/classes/blocked_phones.php";

        $bp = new blocked_phones();

        if (!strcmp($action, "delete") && ($is_admin || $mod_sections['security']['delete'])) {
            $bp->deleteFromWhitelist($id);
        }

        break;

    case 'tpay':

        if (!$is_admin) break;

        require_once $config_abs_path . "/classes/payment/tpay.php";
        $id = get_numeric_only("id");

        if ($action == "delete") {
            $f = new tpay;
            $f->deleteProduct($id);
        }

        break;

    case 'mobilpay':

        if (!$is_admin) break;

        require_once $config_abs_path . "/classes/payment/mobilpay.php";
        $id = get_numeric_only("id");

        if ($action == "delete") {
            $f = new mobilpay;
            $f->deleteProduct($id);
        }

        break;

    case 'slug':

        if ($action == "make") {
            $str = $_GET['str'];
            $slug = _urlencode($str);
            echo $slug;
        }
        if ($action == "exists") {

            $slug = $_GET['slug'];
            $s = new slugs();
            if (isset($_GET['id'])) {
                $id = escape($_GET['id']);
                $exists = $s->slugExists($slug, $id);
            } else
                $exists = $s->slugExists($slug);
            echo $exists;
        }

        break;

        break;
} // end switch

?>
