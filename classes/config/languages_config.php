<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class languages_config
{

    var $error;
    var $clean;
    var $tmp;

    public function __construct()
    {

        global $config_abs_path;
        $this->dir = $config_abs_path . "/lang";
        $this->error = '';

    }


    function setLanguage($lang)
    {

        $this->lang = $lang;
        $this->lang_dir = $this->dir;
        $this->lang_file = $this->dir . '/' . $lang . '.php';

    }

    function setModule($mod)
    {

        $this->lang_dir = "../modules/$mod/lang";
        $this->lang_file = $this->lang_dir . '/' . $this->lang . '.php';

    }

    function getLanguagesFiles()
    {

        global $db;
        $array = $db->fetchAssocList("select id, name from " . TABLE_LANGUAGES . " order by `order_no`");
        $i = 0;
        foreach ($array as $arr) {
            $languages[$i] = $arr;
            $languages[$i]['file'] = $arr['id'] . ".php";
            $i++;
        }
        return $languages;
    }

    function readLanguageFile()
    {

        global $lng;

        if (!file_exists($this->lang_file)) {

            $this->addError($lng['languages']['errors']['file_does_not_exist']);
            return 0;

        }

        if ($fp = fopen($this->lang_file, 'r')) {

            $content = fread($fp, filesize($this->lang_file));
            $content = htmlspecialchars($content);
            $this->setContent($content);
            fclose($fp);
            return 1;
        } else {

            $this->addError($lng['languages']['errors']['cannot_open_file_for_reading']);
            return 0;
        }

    }

    function setContent($content)
    {

        $this->content = $content;

    }

    function getContent()
    {

        //$clean_str = (! _get_magic_quotes_gpc ()) ? stripslashes ($this->content) : $this->content;
        return $this->content;

    }

    function isWriteable()
    {
        global $lng;
        if (is_writable($this->lang_file) == true) return '';
        $cgi = isCGI();
        if ($cgi) $file_perm = "644";
        else $file_perm = "666";

        $error = str_replace("::WRITE_PERMISSION::", $file_perm, $lng['languages']['errors']['warning_file_not_writeable']);
        return $error;

    }


    function saveLanguage()
    {

        global $lng;

        global $config_demo;
        if ($config_demo == 1) {
            $this->addError($lng['general']['errors']['demo'] . '<br />');
            return 0;
        }

        $cgi = isCGI();

        if (is_writable($this->lang_file) == false) {
            if ($cgi) $result = @chmod($this->lang_file, 0644);
            else  $result = @chmod($this->lang_file, 0666);
            if (!$result) {

                $this->addError($lng['languages']['errors']['cannot_write_file'] . ' ' . $this->lang_file);
                return 0;
            }
        }

        clearstatcache();

        // check for malicious code
        global $limited_permissions;
        if ($limited_permissions) $this->secureContent();

        if ($fp = fopen($this->lang_file, 'w')) {

            if (_get_magic_quotes_gpc())
                $this->content = stripslashes($this->content);

            fputs($fp, $this->content, strlen($this->content));
            fclose($fp);
            @chmod($this->lang_file, 0666);

        } else {

            $this->addError($lng['languages']['errors']['failed_open_file_for_writing']);
            return 0;

        }
        return 1;
    }

    function secureContent()
    {

        $tmp_content = $this->content;
        $this->content = '';

        $separator = "\r\n";
        $line = strtok($tmp_content, $separator);

        $pattern = '/(^$|^<\?php$|^\?>$|^\/\/[\p{L}\p{N}\p{Pd}\p{Po}\p{Zs}]+$|^\/\*[\p{L}\p{N}\p{Pd}\p{Zs}\.]+\*\/|^\$lng(_[\p{L}\p{N}}]+)?(\[\'[\p{L}\p{N}_]+\'\])+[\p{Zs}]?=[\p{Zs}]?\'([^;]|(&laquo;)|(&raquo;))+\';(\s*\/\/)?(.)*$)/i';
        /*
        (?:(?!\'\s*;).)+
        (?x)    # enable regex comment mode
        ^       # match start of line/string
        (?:     # begin non-capturing group
          (?!   # begin negative lookahead
            \'\s*;  # quote followed or not by space and followed by ;
          )     # end negative lookahead
          .     # any single character
        )       # end non-capturing group
        +       # repeat previous match one or more times
        $       # match end of line/string
        */

        $safe = 0;
        while ($line !== false) {

            // check if the line is safe
            $safe = 0;
            if (preg_match($pattern, stripslashes($line))) $safe = 1;

            if ($safe) $this->content .= $line . $separator;

            else echo "discard: " . $line . "<br/>";
            $line = strtok($separator);

        }

    }

    function getError()
    {

        return $this->error;

    }

    function addError($str)
    {

        $this->error .= $str;

    }

    function setError($str)
    {

        $this->error = $str;

    }

    function getMod()
    {
        $val = 0;
        $perms = fileperms($this->file_path);
        //echo '==='.$perms.'==';
        // Owner; User
        $val += (($perms & 0x0100) ? 0x0100 : 0x0000); //Read
        $val += (($perms & 0x0080) ? 0x0080 : 0x0000); //Write
        $val += (($perms & 0x0040) ? 0x0040 : 0x0000); //Execute

        // Group
        $val += (($perms & 0x0020) ? 0x0020 : 0x0000); //Read
        $val += (($perms & 0x0010) ? 0x0010 : 0x0000); //Write
        $val += (($perms & 0x0008) ? 0x0008 : 0x0000); //Execute

        // Global; World
        $val += (($perms & 0x0004) ? 0x0004 : 0x0000); //Read
        $val += (($perms & 0x0002) ? 0x0002 : 0x0000); //Write
        $val += (($perms & 0x0001) ? 0x0001 : 0x0000); //Execute

        // Misc
        $val += (($perms & 0x40000) ? 0x40000 : 0x0000); //temporary file (01000000)
        $val += (($perms & 0x80000) ? 0x80000 : 0x0000); //compressed file (02000000)
        $val += (($perms & 0x100000) ? 0x100000 : 0x0000); //sparse file (04000000)
        $val += (($perms & 0x0800) ? 0x0800 : 0x0000); //Hidden file (setuid bit) (04000)
        $val += (($perms & 0x0400) ? 0x0400 : 0x0000); //System file (setgid bit) (02000)
        $val += (($perms & 0x0200) ? 0x0200 : 0x0000); //Archive bit (sticky bit) (01000)

        return $val;

    }

    function addInstall($array)
    {

        global $db;
        $array_fields = array("id", "name", "image", "default", "enabled", "order_no");
        $sql = "insert into " . TABLE_LANGUAGES . " set ";
        $i = 0;
        foreach ($array_fields as $row) {
            if ($i) $sql .= ", ";
            $sql .= "`$row` = '" . $array[$row] . "'";
            $i++;
        }
        $db->query($sql);
        return 1;
    }

    function add()
    {

        global $db;
        $this->clean = array();
        $this->check_form();
        if ($this->getError() != '') return 0;
        $this->clean['id'] = escape($_POST['id']);
        $this->clean['name'] = escape($_POST['name']);
        $this->clean['code'] = escape($_POST['code']);
        $this->clean['direction'] = escape($_POST['direction']);
        global $config_abs_path;
        $this->clean['default'] = checkbox_value("default");
        $this->clean['enabled'] = checkbox_value("enabled");

        if (isset($_FILES['flag_image']['name']) && $_FILES['flag_image']['name'] != '') {

            $image = new image('flag_image', $config_abs_path . '/images/languages', 'language');
            $image->verify();
            if ($image->upload()) $this->clean['image'] = $image->getUploadedFile();
            else $this->addError($image->getError());
        } else $this->clean['image'] = '';

        $this->clean['characters_map'] = '';
        $i = 0;
        while (isset($_POST['characters_map'][$i]) && $map = $_POST['characters_map'][$i]) {
            if ($i) $this->clean['characters_map'] .= ',';
            $this->clean['characters_map'] .= $map;
            $i++;
        }

        $this->clean['order_no'] = $this->getOrder();

        $array_fields = array("id", "name", "code", "image", "default", "enabled", "order_no", "characters_map", "direction");
        $sql = "insert into " . TABLE_LANGUAGES . " set ";
        $i = 0;
        foreach ($array_fields as $row) {
            if ($i) $sql .= ", ";
            $sql .= "`$row` = '" . $this->clean[$row] . "'";
            $i++;
        }
        $db->query($sql);
        //$last=$db->insertId();

        // copy default language file with the new name
        $old_default = languages::getDefault();
        $path = $config_abs_path . "/lang/";
        $filename = $this->clean['id'] . ".php";
        $filepath = $path . $filename;

        if (!file_exists($filepath)) {

            $default_file = $old_default . ".php";
            $default_langfile = $path . $default_file;

            if (!@copy($default_langfile, $filepath)) {
                global $lng;
                $error_str = str_replace("::DEFAULT::", $default_file, $lng['languages']['errors']['could_not_copy_lang_file']);

                $error_str = str_replace("::NEWLANG::", $filename, $error_str);

                $this->addError($error_str);
            }

        }

        if ($this->clean['default']) $this->changeDefault($this->clean['id']);

        if ($this->clean['enabled']) $this->addLanguage($this->clean['id'], $old_default);

        // if fields for title and description for this language must be added
        global $ads_settings;
        if ($ads_settings['translate_title_description']) {
            $listing = new listings;
            $listing->checkLanguageFields();
        }

        global $languages;
        $languages = languages::getActiveLanguages();

        $this->clearLanguagesCache();

        do_action("add_language", array($this->clean['id']));


        return 1;

    }

    function check_form($id = '')
    {

        global $db;
        global $lng;
        $this->error = '';
        $this->tmp = array();

        global $config_demo;
        if ($config_demo == 1) $this->addError($lng['general']['errors']['demo'] . '<br />');

        global $config_abs_path;
        if (!$id) {
            if (!$_POST['id']) $this->addError($lng['languages']['errors']['id_missing'] . '<br />');
            // check if language id already exists
            else if ($this->languageExists($_POST['id'])) $this->addError($lng['languages']['errors']['id_exists'] . '<br />');
        }
        if (!$_POST['name']) $this->addError($lng['languages']['errors']['name_missing'] . '<br />');

        if (isset($_FILES['flag_image']['name']) && $_FILES['flag_image']['name'] != '') {
            $image = new image('flag_image', $config_abs_path . '/images/languages', 'language');
            if (!$image->verify()) $this->addError($image->getError());
        }

        if ($this->getError() != '') {
            if (!$id) $this->tmp['id'] = $_POST['id'];
            if (isset($_POST['name'])) $this->tmp['name'] = cleanStr($_POST['name']); else $this->tmp['name'] = '';
            if (isset($_POST['code'])) $this->tmp['code'] = cleanStr($_POST['code']); else $this->tmp['code'] = '';
            $this->tmp['direction'] = cleanStr($_POST['direction']);
            $this->tmp['default'] = checkbox_value("default");
            $this->tmp['enabled'] = checkbox_value("enabled");
            if (isset($_POST['characters_map']) && $_POST['characters_map']) $this->tmp['characters_map_array'] = $_POST['characters_map'];

        }
        return 1;
    }

    function edit($id)
    {

        global $db;
        $this->clean = array();
        $this->check_form($id);
        if ($this->getError() != '') return 0;

        $old_enabled_val = $db->fetchRow("select `enabled` from " . TABLE_LANGUAGES . " where id='$id'");

        $this->clean['name'] = escape($_POST['name']);
        $this->clean['code'] = escape($_POST['code']);
        $this->clean['direction'] = escape($_POST['direction']);
        global $config_abs_path;
        $this->clean['default'] = checkbox_value("default");
        $this->clean['enabled'] = checkbox_value("enabled");
        if ($this->clean['enabled'] == 0) $this->clean['default'] = 0;

        if (isset($_FILES['flag_image']['name']) && $_FILES['flag_image']['name'] != '') {

            $image = new image('flag_image', $config_abs_path . '/images/languages', 'language');
            $image->verify();
            if ($image->upload()) $this->clean['image'] = $image->getUploadedFile();
            else $this->addError($image->getError());
        } else $this->clean['image'] = '';

        $this->clean['characters_map'] = '';
        $i = 0;
        while (isset($_POST['characters_map'][$i]) && $map = $_POST['characters_map'][$i]) {
            if ($i) $this->clean['characters_map'] .= ',';
            $this->clean['characters_map'] .= $map;
            $i++;
        }

        $array_fields = array("name", "default", "code", "enabled", "characters_map", "direction");
        $sql = "update " . TABLE_LANGUAGES . " set ";
        $i = 0;
        foreach ($array_fields as $row) {
            if ($i) $sql .= ", ";
            $sql .= "`$row` = '" . $this->clean[$row] . "'";
            $i++;
        }
        $sql .= " where id='$id'";
        $db->query($sql);

        if ($this->clean['image']) $db->query("update " . TABLE_LANGUAGES . " set `image` = '" . $this->clean['image'] . "' where id='$id'");

        if ($this->clean['default']) {
            $this->changeDefault($id);
            if (!$this->clean['enabled']) {
                // make it enabled
                $this->clean['enabled'] = 1;
                $db->query("update " . TABLE_LANGUAGES . " set `enabled`=1 where id='$id'");
            }
        } else {
            // if no other enabled language, set it enabled and default
            $exists = $db->fetchRow("select count(*) from " . TABLE_LANGUAGES . " where `enabled` = 1 and id!='$id' order by `order_no`");
            if (!$exists) {
                $this->clean['enabled'] = 1;
                $db->query("update " . TABLE_LANGUAGES . " set `default` = 1, `enabled` = 1 where id='$id'");
            } else {
                // check if no other default language
                $exists = $db->fetchRow("select count(*) from " . TABLE_LANGUAGES . " where `enabled` = 1 and `default`=1 and id!='$id' order by `order_no`");
                if (!$exists) {

                    $this->clean['enabled'] = 1;
                    $db->query("update " . TABLE_LANGUAGES . " set `default` = 1, `enabled` = 1 where id='$id'");
                }
            }

        }

        global $ads_settings;
        //if(!$ads_settings['translate_title_description']) return 1;
        $new_enabled_val = $this->clean['enabled'];
        if ($old_enabled_val == 0 && $new_enabled_val == 1) {

            if ($ads_settings['translate_title_description']) {
                global $config_abs_path;
                require_once $config_abs_path . "/classes/listings.php";
                $listing = new listings;
                $listing->checkLanguageFields();
            }
            global $crt_lang;
            $this->addLanguage($id, $crt_lang);
        }

        if ($old_enabled_val == 1 && $new_enabled_val == 0 && $ads_settings['translate_title_description']) {

            $listing->deleteLanguageFields($id);

        }

        $this->clearLanguagesCache();

        return 1;

    }

    function getTmp()
    {

        return $this->tmp;

    }

    function orderExists($order)
    {

        global $db;
        $res = $db->query("select * from " . TABLE_LANGUAGES . " where order_no='$order'");
        if ($db->numRows($res)) return 1;
        return 0;

    }

    function languageExists($lang)
    {

        global $db;
        $no = $db->fetchRow("select id from " . TABLE_LANGUAGES . " where id='$lang'");
        if ($no) return 1;
        return 0;

    }

    function changeDefault($id)
    {

        global $db;

        global $config_demo;
        if ($config_demo == 1) return;

        $db->query("update " . TABLE_LANGUAGES . " set `default` = 0");
        $db->query("update " . TABLE_LANGUAGES . " set `default` = 1 where id='$id'");

        $this->clearLanguagesCache();

        return 1;
    }

    function Enable($id)
    {

        global $db;

        global $config_demo;
        if ($config_demo == 1) return;

        $db->query("update " . TABLE_LANGUAGES . " set `enabled` = 1 where id='$id'");

        // add missing elements, copy from the default language
        global $crt_lang;
        $this->addLanguage($id, $crt_lang);

        global $ads_settings;
        if ($ads_settings['translate_title_description']) {
            $listing = new listings;
            $listing->checkLanguageFields();
        }

        global $languages;
        $languages = languages::getActiveLanguages();

        do_action("add_language", array($id));

        $this->clearLanguagesCache();

        return 1;

    }

    function Disable($id)
    {

        global $db;

        global $config_demo;
        if ($config_demo == 1) return;

        $db->query("update " . TABLE_LANGUAGES . " set `enabled` = 0 where id='$id'");

        global $ads_settings;
        if ($ads_settings['translate_title_description']) {
            $listing = new listings;
            $listing->deleteLanguageFields($id);
        }

        $this->clearLanguagesCache();

        // get new languages array
        global $languages;
        $languages = languages::getActiveLanguages();

        // check if description and title fields need to be modified
        $l = new listings();
        $l->checkLanguageFields();

        return 1;

    }

    function delete($id)
    {

        global $db;

        global $config_demo;
        if ($config_demo == 1) return;

        global $config_abs_path;
        $im = $db->fetchRow("select `image` from " . TABLE_LANGUAGES . " where id='$id'");
        if ($im) @unlink($config_abs_path . "/images/languages/" . $im);

        $db->query("delete from " . TABLE_LANGUAGES . " where id='$id'");

        $array_tables = array(TABLE_APPEARANCE . "_lang", TABLE_SETTINGS . "_lang", TABLE_CATEGORIES . "_lang", TABLE_CUSTOM_PAGES . "_lang", TABLE_DEPENDING_FIELDS . "_lang", TABLE_FIELDS . "_lang", TABLE_USER_FIELDS . "_lang", TABLE_INFO, TABLE_MAILS, TABLE_PACKAGES . "_lang", TABLE_PRIORITIES . "_lang", TABLE_RSS . "_lang", TABLE_USER_GROUPS . "_lang", TABLE_ADS_SETTINGS . "_lang");

        foreach ($array_tables as $table) {

            $db->query("delete from $table where lang_id='$id'");

        }

        global $ads_settings;
        if (!$ads_settings['translate_title_description']) return 1;
        $listing = new listings;
        $listing->deleteLanguageFields($id);

        do_action("delete_language", array($id));

        $this->clearLanguagesCache();

        // get new languages array
        global $languages;
        $languages = languages::getActiveLanguages();

        // check if description and title fields need to be modified
        listings::checkLanguageFields();

        return 1;

    }

    function setDefault($id)
    {

        global $config_demo;
        if ($config_demo == 1) return;

        global $db;
        $db->query("update " . TABLE_LANGUAGES . " set `default` = 0 where id not like '$id'");
        $db->query("update " . TABLE_LANGUAGES . " set `default` = 1 where id='$id'");

        $this->clearLanguagesCache();
        return 1;
    }

    function getOrder()
    {

        global $db;
        $order_no = $db->fetchRow('select `order_no` from ' . TABLE_LANGUAGES . ' order by `order_no` desc limit 1');
        if ($order_no) $order_no = $order_no + 1;
        else $order_no = 1;
        return $order_no;
    }

    function moveUp($id)
    {

        global $db;
        if (!$id) $id = $this->id;

        global $config_demo;
        if ($config_demo == 1) return;

        $order_no = $db->fetchRow("select `order_no` from " . TABLE_LANGUAGES . " where `id`='$id'");
        $goto = $order_no - 1;
        $res1 = $db->query("update " . TABLE_LANGUAGES . " set `order_no`='1000' where `id`='$id'");
        $res2 = $db->query("update " . TABLE_LANGUAGES . " set `order_no`='$order_no' where `order_no`='$goto'");
        $res3 = $db->query("update " . TABLE_LANGUAGES . " set `order_no`='$goto' where `id`='$id'");

        $this->clearLanguagesCache();

        return 1;
    }

    function moveDown($id)
    {

        global $db;
        if (!$id) $id = $this->id;

        global $config_demo;
        if ($config_demo == 1) return;

        $order_no = $db->fetchRow("select `order_no` from " . TABLE_LANGUAGES . " where `id`='$id'");
        $goto = $order_no + 1;
        $res1 = $db->query("update " . TABLE_LANGUAGES . " set `order_no`='1000' where `id`='$id'");
        $res2 = $db->query("update " . TABLE_LANGUAGES . " set `order_no`='$order_no' where `order_no`='$goto'");
        $res3 = $db->query("update " . TABLE_LANGUAGES . " set `order_no`='$goto' where `id`='$id'");

        $this->clearLanguagesCache();

        return 1;
    }

    function addLanguage($lang_id, $default)
    {

        $dc = new depending_fields_config();
        $dc->addLanguage($lang_id, $default);
        global $db;
        $array_tables = array(TABLE_APPEARANCE, TABLE_SETTINGS);

        foreach ($array_tables as $tab) {

            // if already exists a row for this language exit
            $exists = $db->fetchRow("select count(*) from " . $tab . "_lang where `lang_id` = '$lang_id'");
            if ($exists) continue;

            $array_cpy = $db->fetchAssoc("select * from " . $tab . "_lang where `lang_id` = '$default'");

            $sql = "insert into " . $tab . "_lang set `lang_id` = '$lang_id' ";
            foreach ($array_cpy as $key => $value) {
                if ($key != 'lang_id') $sql .= ", `$key` = '" . addslashes($value) . "'";
            }
            $res = $db->query($sql);

        } // end for each table


        $array_tables = array(TABLE_CATEGORIES, TABLE_CUSTOM_PAGES, TABLE_PACKAGES, TABLE_PRIORITIES, TABLE_RSS, TABLE_USER_GROUPS, TABLE_FIELDS, TABLE_USER_FIELDS, TABLE_ADS_SETTINGS);

        foreach ($array_tables as $tab) {
            $array_cpy = $db->fetchAssocList("select * from " . $tab . "_lang where `lang_id` = '$default'");

            foreach ($array_cpy as $res) {
//                print_r($res);exit;

                // if already exists a row for this language exit
                $exists = $db->fetchRow("select count(*) from " . $tab . "_lang where `lang_id` = '$lang_id' and `id` = " . $res['id']);
                if ($exists) continue;
                $sql = "insert into " . $tab . "_lang set `lang_id` = '$lang_id' ";

                foreach ($res as $key => $value) {

                    if ($key != 'lang_id') $sql .= ", `$key` = '" . addslashes($value) . "'";

                }

                $res = $db->query($sql);
            }
        } // end for each table


        $array_tables = array(TABLE_INFO, TABLE_MAILS, TABLE_SEO_PAGES);
        foreach ($array_tables as $tab) {

            // if already exists a row for this language exit
            $exists = $db->fetchRow("select count(*) from " . $tab . " where `lang_id` = '$lang_id'");
            if ($exists) continue;

            $array_cpy = $db->fetchAssocList("select * from " . $tab . " where `lang_id` = '$default'");

            foreach ($array_cpy as $result) {

                $sql = "insert into " . $tab . " set `lang_id` = '$lang_id' ";
                foreach ($result as $key => $value) {

                    if ($key != 'lang_id') $sql .= ", `$key` = '" . addslashes($value) . "'";

                }

                $res = $db->query($sql);

            }
        } // end for each table

        $this->clearLanguagesCache();

        return 1;
    }


    function deleteImage($id)
    {

        global $config_demo;
        if ($config_demo == 1) return;

        global $db;
        global $config_abs_path;
        $pic = $db->fetchRow("select `image` from " . TABLE_LANGUAGES . " where id='$id'");
        if ($pic) @unlink($config_abs_path . "/images/languages/" . $pic);
        $db->query("update " . TABLE_LANGUAGES . " set `image` ='' where id='$id'");

        $this->clearLanguagesCache();

        return 1;

    }

    function clearLanguagesCache()
    {

        // clear cache
        $lc_cache = new cache();
        $lc_cache->clearAllCacheFiles();
        /*$lc_cache->clearCache("base_languages");
        $lc_cache->clearCache("base_languages_list");
        $lc_cache->clearCache("base_interface");*/

    }

}

?>
