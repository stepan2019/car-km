<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class modules {

	var $curr_element;
	var $top_tag;
	var $xml_array;
	var $error;

public function __construct()
{
	
}

function add() {

}

function delete() {

}

function Enable($id) {

	global $config_demo;
	if($config_demo==1) return;

	global $db;
	$db->query("update ".TABLE_MODULES." set enabled=1 where id like '$id'");
	$this->clearModulesCache();

}	

function Disable($id) {

	global $config_demo;
	if($config_demo==1) return;

	global $db;
	$db->query("update ".TABLE_MODULES." set enabled=0 where id like '$id'");
	$this->clearModulesCache();

}	

function Install($id) {

	global $config_demo;
	if($config_demo==1) return;

	// run the install script
	global $db;
	global $lng;
	$no = $db->numRows("select * from ".TABLE_MODULES." where id like $id");
	if($no) { $this->setError($lng['modules']['error']['already_installed']); return 0; }
	global $config_live_site;

	global $config_abs_path;
	$modules_folder = $config_abs_path."/admin/modules";
	$file_name = $modules_folder."/$id/install.php";
	$array = $this->xml_parse_file($file_name);

	$query = trim($array['query']);
	if($query) {
		// replace (( with < and  )) with >
		$query = str_replace("((","<", $query);
		$query = str_replace("))",">", $query);

		$dbase = new database;
		$dbase->executeBulk($query);
	} 

	$this->clearModulesCache();

	return 1;
}	

function Uninstall($id) {
	global $config_demo;
	if($config_demo==1) return;

	// run the uninstall script
	global $db;
	global $lng;
	$result = $this->getModule($id);
	global $config_abs_path;
	$modules_folder = $config_abs_path."/admin/modules";
	$file_name = $modules_folder."/$id/uninstall.php";
	$array = $this->xml_parse_file($file_name);

	$query = trim($array['query']);
	if($query) {
		$dbase = new database;
		$dbase->executeBulk($query);
	} 

	$this->clearModulesCache();

	return 1;
}	

function getError() {
	
	return $this->error;

}

function addError($str) {

	$this->error.=$str;

}

function setError($str) {

	$this->error=$str;

}

function getAll() {
	global $db;
	$arr = $db->fetchAssocList("select * from ".TABLE_MODULES);
	return $arr;
}

function getNo() {
	global $db;
	$arr = $db->fetchRow("select count(*) from ".TABLE_MODULES);
	return $arr;
}

function getModulesTable() {
	global $db;
	$result=array();
	$arr = $db->fetchAssocList("select * from ".TABLE_MODULES);
	foreach ($arr as $row) $result[$row['id']] = $row['enabled'];
	return $result;
}

function getName($id) {
	global $db;
	$result = $db->fetchRow("select `name` from ".TABLE_MODULES." where `id` like '$id'");
	return $result;
}

function getModule($id) {
	global $db;
	$result = $db->fetchAssoc("select * from ".TABLE_MODULES." where `id` like '$id'");
	return $result;
}

function xml_parse_file($file_name, $encoding="UTF-8")
{

	$this->top_tag = "module";
	$this->xml_array = array();
	//Initialize Parser
	$this->parser=xml_parser_create($encoding);

	xml_set_object($this->parser, $this);
	xml_set_default_handler($this->parser,"_default"); 

	//Specify Handlers to start and ending tag
	xml_set_element_handler($this->parser, "start_element", "end_element");
	//Data Handler
	xml_set_character_data_handler($this->parser, "character_data");
	//match the exact case
	xml_parser_set_option($this->parser, XML_OPTION_TARGET_ENCODING, "UTF-8");
	xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, 0);
	xml_parser_set_option($this->parser, XML_OPTION_SKIP_WHITE, 0);
	//Open the Data File 

	$fp= @fopen($file_name, "r") ;
	if(!$fp) {
		$this->addError($lng['ie']['errors']['could_not_open_file']);
		return 0;
	}

	//read Data
	while ($data=fread($fp, 4096))
	{
		if(!xml_parse($this->parser, $data, feof($fp))) $this->addError(sprintf('XML error %d:"%s" at line %d column %d byte %d',
            xml_get_error_code($this->parser),
            xml_error_string(xml_get_error_code($this->parser)),
            xml_get_current_line_number($this->parser),
            xml_get_current_column_number($this->parser),
            xml_get_current_byte_index($this->parser)));
	}
	xml_parser_free($this->parser);


	@fclose($fp);

	return $this->xml_array;

}

function start_element($parser, $element_name, $element_attrs)
{

	$this->curr_element = $element_name;

}

function end_element($parser, $element_name)
{

}

function _default($parser, $handler)
{

}

function character_data($parser,$data)
{

	if(!$this->curr_element || $this->curr_element == $this->top_tag) return;

	if($this->curr_element=="id") $this->whitespace_strip($data);

	if(!isset($this->xml_array[$this->curr_element])) $this->xml_array[$this->curr_element]='';
	$this->xml_array[$this->curr_element] .= $data;

}

function whitespace_strip(&$str)
{
	$str=preg_replace ('/ +/', ' ', trim($str));
	$str=preg_replace("/[\r\t\n]/","",$str);
}

function clearModulesCache() {

	// clear cache
	$lc_cache = new cache();
	$lc_cache->clearCache("base_settings");

}

}
?>
