<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class database
{

    var $file_format = 'd_m_y__H_i_s';
    var $tables = array();
    var $drop_tables = true;
    var $struct_only = false;
    var $comments = true;
    var $newline = "\r\n";
    var $compress = false;
    var $write = true;
    var $last_id;
    var $install;
    var $error;

    public function __construct()
    {

        $this->install = 0;
        $this->f = null;
        global $config_abs_path;
        $this->backup_dir = $config_abs_path . "/db_backup";
    }

    function setWrite($write)
    {

        $this->write = $write;

    }

    function delete($file)
    {

        global $lng;

        if (@unlink($this->backup_dir . "/" . $file)) return $lng['database']['backup_deleted'];

        else return $lng['database']['could_not_delete_backup'];

    }


    function downloadFile($fname)
    {
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Content-Type: application/force-download");
        header("Content-Disposition: attachment; filename=" . basename($fname));
        header("Content-Description: File Transfer");
        @readfile($this->backup_dir . '/' . $fname);
        exit;
    }

    function writeHeader()
    {

        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Content-Type: application/force-download");
//		header("Content-Disposition: attachment; filename=".basename($fname));
        header("Content-Description: File Transfer");

    }

    function openFileToWrite($fname)
    {

        global $lng;

        if ($this->compress) {
            if (!($this->f = gzopen($this->backup_dir . '/' . $fname, 'w9'))) {
                $this->addError($lng['database']['cant_create_output_file'] . "<br>");
                return 0;
            }
            return 1;
        }

        // without compression
        if (!($this->f = fopen($this->backup_dir . '/' . $fname, 'w'))) {
            $this->addError($lng['database']['cant_create_output_file'] . "<br>");
            return 0;
        }
        return 1;
    }

    function openFileToRead($fname)
    {

        global $lng;

        if ($this->compress) {
            if (!($this->f = gzopen($fname, 'r'))) {
                $this->addError($lng['database']['cant_read_from_file'] . "<br>");
                return 0;
            }
            return 1;
        }

        // without compression
        if (!($this->f = fopen($fname, 'r'))) {
            $this->addError($lng['database']['cant_read_from_file'] . "<br>");
            return 0;
        }
        return 1;
    }

    function setDir($dir)
    {

        $this->backup_dir = $dir;

    }

    function getBackupDir()
    {

        return $this->backup_dir;

    }


    function closeFile()
    {

        if ($this->f) {
            if ($this->compress) { //gzflush($this->f);
                gzclose($this->f);
            } else {
                fflush($this->f);
                fclose($this->f);
            }
        } else return 0;
        return 1;
    }

    function _eof()
    {

        if ($this->compress) {
            if (gzeof($this->f)) return 1;
        } elseif (feof($this->f)) return 1;
        return 0;
    }

    function _gets()
    {

        if ($this->compress) return gzgets($this->f);
        else return fgets($this->f);

    }

    function saveToFile($fname = '')
    {

        if (!$fname) $fname = $this->generateFname();
        else $fname = basename($fname);
        if ($this->compress) $fname .= ".sql.gz"; else $fname .= ".sql";

        if (!$this->openFileToWrite($fname)) return 0;

        $this->backupDB();

        $this->closeFile();

        return $fname;
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

    function setCompress($val)
    {

        $this->compress = $val;

    }

    function getTables()
    {

        global $db;
        if (!($result = $db->fetchRowList('SHOW TABLES'))) return 0;
        return $result;

    }

    function writeTableStructure($table)
    {

        global $db;

        $str = "";
        if ($this->comments) {
            $str .= '#' . $this->newline;
            $str .= '# Table structure for table `' . $table . '`' . $this->newline;
            $str .= '#' . $this->newline . $this->newline;
        }

        if ($this->drop_tables)
            $str .= 'DROP TABLE IF EXISTS `' . $table . '`;' . $this->newline;

        if (!($result = $db->fetchAssoc('SHOW CREATE TABLE ' . $table)))
            return 0;

        $str .= $result['Create Table'] . ' ;' . $this->newline . $this->newline;

        $this->writeSQL($str);

        if (!$this->struct_only) {

            $this->writeInserts($table);
        }

        return 1;

    }

    function writeInserts($table)
    {

        global $db;

        $str = "";
        // write comments
        if ($this->comments) {

            $str .= '#' . $this->newline;
            $str .= '# Dumping data for table `' . $table . '`' . $this->newline;
            $str .= '#' . $this->newline . $this->newline;

        }

        // get each row and write to file once on 1000 registrations or at the end
        $i = 0;
        if (function_exists('mysqli_connect')) {
            if (!$res = mysqli_query($db->link, 'SELECT * FROM ' . $table)) return 0;
        } else {
            if (!$res = mysql_query('SELECT * FROM ' . $table)) return 0;
        }

        if (!$res) return 0;

        if (function_exists('mysqli_connect')) {
            while ($row = @mysqli_fetch_assoc($res)) {

                $values = "";
                foreach ($row as $val) {
                    $values .= '\'' . $this->myAddslashes($val) . '\', ';
                }
                $values = substr($values, 0, -2);
                $str .= 'INSERT INTO ' . $table . ' VALUES (' . $values . ');' . $this->newline;
                if ($i == 1000) {
                    $this->writeSQL($str);
                    unset($str);
                    $str = "";
                    $i = 0;
                }
                $i++;

            }
        } else {

            while ($row = @mysql_fetch_assoc($res)) {

                $values = "";
                foreach ($row as $val) {
                    $values .= '\'' . $this->myAddslashes($val) . '\', ';
                }
                $values = substr($values, 0, -2);
                $str .= 'INSERT INTO ' . $table . ' VALUES (' . $values . ');' . $this->newline;
                if ($i == 1000) {
                    $this->writeSQL($str);
                    unset($str);
                    $str = "";
                    $i = 0;
                }
                $i++;

            }

        }

        $str .= $this->newline . $this->newline;
        $this->writeSQL($str);

        return 1;
    }

    function myAddslashes($str)
    {

        $str = addslashes($str);
        $str = str_replace("\n", '\n', $str);
        $str = str_replace("\r", '\r', $str);
        $str = str_replace("\t", '\t', $str);
        return $str;
    }

    function backupDB()
    {

        $tables = $this->getTables();
        global $config_table_prefix;
        foreach ($tables as $tab) {
            if (preg_match("/^$config_table_prefix/", $tab))
                $this->writeTableStructure($tab);
        }
        return 1;

    }

    function generateFname()
    {

        $name = date($this->file_format);
        return $name;
    }

    function writeSQL($str)
    {

        if ($this->write == false) {
            echo $str;
            return;
        }
        if (!$this->f) return 0;
        if ($this->compress) gzwrite($this->f, $str);
        else fwrite($this->f, $str);
        return 1;

    }

    /* keep !!!!!!!!!!

        function restoreDB($fname, $replace='') {

            if(!$this->openFileToRead($fname)) return 0;
            $no_queries = 0;
            $max_queries = 1000;
            $query="";
            while (!$this->_eof()) {

                $line = $this->_gets();

                // check if empty line or comment
                $first= substr($line, 0,1);
                if(!empty($line) && $first!="-" && $first!="" && $first!="#" ){

                    if($replace) $line = str_replace("PREFIX", $replace, $line);

                    if($this->oneQueryStr($line)) {

                        $this->executeQuery($query);
                        $query="";
                        $query.=trim($line);

                    } else $query.=trim($line);

                    // check if it's the complete query
                    if(substr($query,-1,1)==";"){

                    if(substr($query , 0 , 6 )    == "INSERT") { // do multiple queries

                        $no_queries++;
                        if($no_queries>=$max_queries) { $this->executeQuery($query); $query = ""; }

                    } else {

                        // execute query
                        $this->executeQuery($query);
                        $query = "";
                    }
                    } // end if complete query
                }
            }
            $this->closeFile();
            return 1;

        }
    */
    function restoreDB($fname, $replace = '')
    {

        global $config_demo;
        if ($config_demo == 1) return;

        // checked if compressed or not
        if (!$fname) return 0;
        $ext = getExtension($fname);
        if ($ext == "gz") $this->compress = true; else $this->compress = false;

        if (!$this->openFileToRead($fname)) return 0;
        $query = "";
        $this->setInstall(1);

        while (!$this->_eof()) {

            $line = $this->_gets();

            // check if empty line or comment
            $first = substr($line, 0, 1);
            if (!empty($line) && $first != "-" && $first != "" && $first != "#") {

                if ($replace) $line = str_replace("PREFIX", $replace, $line);

                if ($this->oneQueryStr($line)) {

                    $this->executeQuery($query);
                    $query = "";
                    $query .= trim($line);

                } else $query .= trim($line);

                // check if it's the complete query
                if (substr($query, -1, 1) == ";") {
                    // execute query
                    $ans = $this->executeQuery($query);
                    $query = "";
                } // end if complete query
            }
        }
        $this->closeFile();
        return 1;

    }

    function installDB($fname, $replace, $default_lang = '', $charset = '', $collation = '')
    {

        // checked if compressed or not
        if (!$fname) return 0;
        $ext = getExtension($fname);
        if ($ext == "gz") $this->compress = true; else $this->compress = false;

        if (!$this->openFileToRead($fname)) return 0;
        $query = "";
        while (!$this->_eof()) {

            $line = $this->_gets();

            // check if empty line or comment
            $first = substr($line, 0, 1);
            if (!empty($line) && $first != "-" && $first != "" && $first != "#") {

                $line = str_replace("PREFIX", $replace, $line);
                if ($default_lang) $line = str_replace("DEF_LANG", $default_lang, $line);
                if ($charset) $line = str_replace("::CHARSET::", $charset, $line);
                if ($collation) $line = str_replace("::COLLATE::", $collation, $line);

                if ($this->oneQueryStr($line)) {

                    if ($query) {
                        $this->executeQuery($query);

                        $query = "";
                    }
                    $query .= trim($line);

                } else $query .= trim($line);

                // check if it's the complete query
                if (substr($query, -1, 1) == ";") {
                    // execute query
                    $ans = $this->executeQuery($query);

                    $query = "";
                } // end if complete query
            }
        }
        $this->closeFile();
        return 1;

    }

    function executeBulk($query_str)
    {

        global $config_demo;
        if ($config_demo == 1) return;
        global $config_table_prefix, $config_db_charset;

        $lines = explode("\n", $query_str);

        $query = "";
        $last_id = 0;
        foreach ($lines as $line) {

            $line = trim($line);
            // check if empty line or comment
            $first = substr($line, 0, 1);
            if (!empty($line) && $first != "-" && $first != "" && $first != "#") {

                $line = str_replace("PREFIX", $config_table_prefix, $line);
                $line = str_replace("##CHARSET##", $config_db_charset, $line);
                $line = str_replace("##COLLATION##", $config_db_collation, $line);

                if ($this->oneQueryStr($line)) {

                    $query = "";
                    $query .= trim($line);

                } else $query .= trim($line);

                // check if it's the complete query
                if (substr($query, -1, 1) == ";") {

                    // check if language replaceable
                    // for modules install
                    if (strstr($query, "##LANG##")) {

                        // replace last id for _lang tables
                        if (strstr($query, "##LAST_ID##"))
                            $query = str_replace("##LAST_ID##", $this->last_id, $query);

                        global $languages;
                        if (empty($languages)) $languages = common::getCachedObject("base_languages");
                        $final_query = '';
                        foreach ($languages as $lang) {
                            $lang_id = $lang['id'];
                            $final_query = trim(str_replace("##LANG##", $lang_id, $query));
                            $ans = $this->executeQuery($final_query);
                        }
                        $query = "";

                    } else {

                        // execute query
                        $ans = $this->executeQuery($query);
                        $query = "";
                    }

                } // end if complete query
            }
        }

        return 1;

    }

    function executeQuery($query)
    {

        global $db;

        $db->query($query);
        $db->setSql("");
        $this->last_id = $db->insertId();
        if ($db->sql_error($db->link) != "") $this->addError($db->getError());

        return 1;

    }

    function oneQueryStr($str)
    {

        $str = trim($str);
        if (substr($str, 0, 13) == "DROP DATABASE") return 1;
        if (substr($str, 0, 15) == "CREATE DATABASE") return 1;
        if (substr($str, 0, 10) == "DROP TABLE") return 1;
        if (substr($str, 0, 12) == "CREATE TABLE") return 1;
        if (substr($str, 0, 6) == "UPDATE") return 1;
        if (substr($str, 0, 3) == "USE") return 1;
        return 0;

    }

    function readBackups()
    {

        $arr = dir($this->backup_dir);
        $backups = array();
        $i = 0;
        while ($file = $arr->read()) {

            if ($file != '.' && $file != '..') {
                $ext = getExtension($file);
                if ($ext == "sql" || $ext == "gz") {
                    $backups[$i]['file'] = $file;
                    $backups[$i]['date'] = @filectime($this->backup_dir . '/' . $file);
                    $backups[$i]['size'] = @filesize($this->backup_dir . '/' . $file);
                    $backups[$i]['size_formatted'] = $this->formatSize($backups[$i]['size']);
                    $i++;
                }
            }

        }

        closedir($arr->handle);

        $backups = $this->dateSort($backups);

        return $backups;


    }

    function formatSize($str)
    {

        if (strlen($str) > 6) {
            $str = (int)$str / 1000000;
            $b = " MB";
        } elseif (strlen($str) > 3) {
            $str = (int)$str / 1000;
            $b = " KB";
        } else $b = " B";

        $str = number_format((double)$str, 1, ',', '.');

        return $str . $b;
    }

    function check_form()
    {

        global $lng;
        $this->error = '';
        $this->tmp = array();

        global $config_demo;
        if ($config_demo == 1) $this->addError($lng['general']['errors']['demo'] . '<br />');

        if ($this->getError() != '') {
            if (isset($_POST['enabled']) && $_POST['enabled'] == 'on') $this->tmp['enabled'] = 0; else $this->tmp['enabled'] = 1;

            if (isset($_POST["backup_freq"])) $this->tmp["backup_freq"] = cleanStr($_POST["backup_freq"]); else $this->tmp["backup_freq"] = '';

            if (isset($_POST['keep']) && is_numeric($_POST['keep'])) $this->tmp['keep'] = $_POST['keep']; else $this->tmp['keep'] = 0;

        }

    }

    function saveSchedule()
    {

        global $config_demo;
        if ($config_demo == 1) return;
        global $db;
        $this->clean = array();
        $this->check_form();
        if ($this->getError() != '') return 0;

        $this->clean = array();
        $this->clean['enabled'] = checkbox_value('enabled');

        if (isset($_POST["backup_compress"])) $this->clean["backup_compress"] = escape($_POST["backup_compress"]); else $this->clean["backup_compress"] = '0';

        if (isset($_POST["backup_freq"])) $this->clean["backup_freq"] = escape($_POST["backup_freq"]); else $this->clean["backup_freq"] = '';

        if (isset($_POST['keep']) && is_numeric($_POST['keep'])) $this->clean['keep'] = $_POST['keep']; else $this->clean['keep'] = 0;

        $res = $db->query("update " . TABLE_DB_BACKUP . " set enabled= " . $this->clean['enabled'] . ", backup_freq = '" . $this->clean['backup_freq'] . "' , `keep` = " . $this->clean['keep'] . ", `backup_compress` = " . $this->clean['backup_compress']);
        return 1;

    }

    function getSchedule()
    {

        global $db;
        $result = $db->fetchAssoc("select * from " . TABLE_DB_BACKUP);
        return $result;

    }

    function periodic()
    {
        global $db;
        $settings = $this->getSchedule();
        if (!$settings['enabled']) return;
        if ($settings['backup_freq'] == 'daily') $days = 1;
        else if ($settings['backup_freq'] == 'weekly') $days = 7;
        else $days = 30;
        $timestamp = date("Y-m-d H:i:s");
        $sql = "select (generated_last like '0000-00-00 00:00:00' or date_add(generated_last, interval " . $days . " day ) < date_add('" . $timestamp . "', interval 1 hour)) as go from " . TABLE_DB_BACKUP;
        $val = $db->fetchAssoc($sql);
        if ($val['go']) {
            $this->setCompress($settings['backup_compress']);
            $this->saveToFile();
            $this->updateDate();
        }

        // keep only X backups, delete older
        $no = $settings['keep'];
        $backups_array = $this->readBackups();
        $no_crt = count($backups_array);

        if ($no_crt > $no) {
            $diff = $no_crt - $no;
            for ($i = 0; $i < $diff; $i++) {
                $backup_name = $this->backup_dir . '/' . $backups_array[$i]['file'];
                @unlink($backup_name);
            }
        }

        return;
    }

    function updateDate()
    {

        global $db;
        $timestamp = date("Y-m-d H:i:s");
        $db->query("update " . TABLE_DB_BACKUP . " set generated_last = '$timestamp'");

    }

    function dateSort($a)
    {

        $no = count($a);
        for ($i = 0; $i < $no - 1; $i++) {
            for ($j = $i + 1; $j < $no; $j++) {
                // order ascending
                if ((int)$a[$i]['date'] > (int)$a[$j]['date']) {
                    $temp = $a[$i];
                    $a[$i] = $a[$j];
                    $a[$j] = $temp;
                }
            }
        }

        return $a;
    }

    function setInstall($val)
    {

        $this->install = $val;

    }

}

?>
