<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class import_export {

var $template = '';
var $newline = "\r\n";
var $showFieldNames;
var $data; // ad or user
var $type; // csv or xml
var $error = '';
var $f;
var $xml_tags;
var $row_array = array();
var $top_tag;
var $bottom_tag;
var $start_tag;
var $end_tag;
var $required_fields;
var $default = array();
var $def_fields = array();
var $table;
var $parser;
var $curr_element;
var $succeeded = 0;
var $failed = 0;
var $added_elements=array();
var $ad_ignore_tags = array( "date_formatted" , "date_expires_formatted", "expired", "category", "plan", "plan_amount", "username", "pending_package", "invoice", "price_formatted", "stock");
var $user_ignore_tags = array();
var $purpose = "import";
var $cl_pics = null;
var $file_name = null;
var $last_id = 0;

// the "id" field represents a unique id based on which ads can be modified or even deleted
// used only for ad import type
var $uid = 0;

// if value is 1 this import is a scheduled import
// scheduled imports can behave differently from bulk upload imports
var $scheduled = 0;

// if $no_duplicates == 1, items which already exist based on the unique id are no longer imported
var $no_duplicates = 0;

public function __construct()
{
	
}

function setUid($var) {

	$this->uid = $var;

}

function setScheduled($var) {

	$this->scheduled = $var;

}

function setNoDuplicates($var) {

	$this->no_duplicates = $var;

}

function setTemplate($template) {

	$this->template = $template;

}

function setData($data) {

	$this->data = $data;
	if($this->data=="ad") $this->table = TABLE_ADS;
	if($this->data=="user") $this->table = TABLE_USERS;

}

function setType($type) {

	$this->type = $type;

}

function setDefault($arr) {

	$this->default = $arr;

}

function setPurpose($purpose) {

	$this->purpose = $purpose;

}

function getTemplate() {

	return $this->template;

}

function getAddedElements() {

	return $this->added_elements;

}

function getError() {
	
	return $this->error;

}

function addError($str) {

	$this->error.=	$str;

}

function setError($str) {

	$this->error =	$str;

}

function getTemplates() {

	global $db;
	$array = $db->fetchAssocList("select * from ".TABLE_IE_TEMPLATES);
	$result = array();
	$i = 0;
	foreach ($array as $res) {
		$result[$i] = $res;
		$result[$i]['fields'] = $this->getTemplateCSVFields($res['id']);
		$i++;
	}
	return $result;

}

function getTemplateDetails($id) {

	global $db;
	$result = $db->fetchAssoc("select * from ".TABLE_IE_TEMPLATES." where `id`=$id");
	return $result;

}

function getAdTemplates() {

	global $db;
	$extra = "";
	if($this->purpose=="export") $extra = " and `purpose` like 'export'";
	else if($this->purpose=="import") $extra = " and `purpose` like 'import'";
	$array = $db->fetchAssocList("select * from ".TABLE_IE_TEMPLATES." where type = 'ad'".$extra);
	$result=array();
	$i = 0;
	foreach ($array as $res) {
		$result[$i] = $res;
		$result[$i]['fields'] = $this->getTemplateCSVFields($res['id']);
		$i++;
	}
	return $result;

}

function getUserTemplates() {

	global $db;
	$array = $db->fetchAssocList("select * from ".TABLE_IE_TEMPLATES." where type = 'user'");
	$i = 0;
	foreach ($array as $res) {
		$result[$i] = $res;
		$result[$i]['fields'] = $this->getTemplateCSVFields($res['id']);
		$i++;
	}
	return $result;

}

function getAdTemplatesStr() {

	global $db;
	$array = $db->fetchAssocList("select * from ".TABLE_IE_TEMPLATES." where type = 'ad'");
	$i = 0;
	$str = '';
	foreach ($array as $res) {
		if($i) $str.=",";
		$str.=$res['id'].":".$res['name'];
		$i++;
	}
	return $str;

}


function getUserTemplatesStr() {

	global $db;
	$array = $db->fetchAssocList("select * from ".TABLE_IE_TEMPLATES." where type = 'user'");
	$i = 0;
	$str = '';
	foreach ($array as $res) {
		if($i) $str.=",";
		$str.=$res['id'].":".$res['name'];
		$i++;
	}
	return $str;

}

function getTemplateFields($template='') {

	global $db;

	if($template) {

		// csv - always use field names
		if($this->type=="csv") {

			$result = $db->fetchRowList("select `field` from ".TABLE_IE_TEMPLATES_FIELDS." where `template_id` = ".$template." order by `id`");
			return $result;
		}

		// xml - use aliases
		$array = array();
		$result = $db->fetchAssocList("select `field`, `alias`, `cdata` from ".TABLE_IE_TEMPLATES_FIELDS." where `template_id` = ".$template." order by `id`");
		$i=0;
		foreach ($result as $row) {
			$array[$i] = $row;
			if(!$row['alias']) $array[$i]['alias'] = $row['field'];
			$i++;
		}
		return $array;
	}

	if($this->purpose=="import")
		$fields = $this->getImportCSVFields();
	else 
		$fields = $this->getExportCSVFields();

	$array = explode(",",$fields);

	// simple array for csv
	if($this->type=="csv") return $array;

	// field and alias for xml
	$result = array();
	$i=0;
	foreach ($array as $row) {
		$result[$i]['field'] = $row;
		$result[$i]['alias'] = $row;
		$result[$i]['cdata'] = '0';
		$i++;
	}	
	return $result;

}


function getTemplateCSVFields($template) {

	global $db;
	$array = $db->fetchAssocList("select * from ".TABLE_IE_TEMPLATES_FIELDS." where `template_id` = ".$template." order by `id`");
	$str = '';
	$i = 0;
	foreach($array as $field) {
		if($i) $str.=',';
		$str.=$field['field'];
		$i++;
	}
	return $str;

}

function deleteTemplate($id) {

	global $config_demo;
	if($config_demo==1) return;

	global $db;
	$db->query("delete from ".TABLE_IE_TEMPLATES." where id=$id");
	$db->query("delete from ".TABLE_IE_TEMPLATES_FIELDS." where template_id=$id order by `id`");
	return 1;
}

function addTemplate() {

	global $lng;
	$array_types = array ('ad', 'user');
	$array_purposes = array ('import', 'export');

	$clean['type'] = $_POST['type'];

	if(!in_array($clean['type'], $array_types)) { 
		$this->addError($lng['ie']['errors']['choose_type']); 
		return 0;
	}

	$clean['purpose'] = $_POST['purpose'];

	if(!in_array($clean['purpose'], $array_purposes)) { 
		$this->addError($lng['ie']['errors']['choose_purpose']); 
		return 0;
	}

	$clean['name'] = escape($_POST['name']);
	if(!$clean['name']) { 
		$this->addError($lng['ie']['errors']['enter_template_name']); 
		return 0;
	}

	$clean['fields'] = escape($_POST['fields']);

	if(!$clean['fields']) { 
		$this->addError($lng['ie']['errors']['enter_template_fields']); 
		return 0;
	}
		
	$fields_array = explode( ',', $clean['fields']);
	if(!count($fields_array)) { 
		$this->addError($lng['ie']['errors']['enter_template_fields']); 
		return 0;
	}

	global $db;
	$db->query("insert into ".TABLE_IE_TEMPLATES." set `name` = '".$clean['name']."', `type` = '".$clean['type']."', `purpose` = '".$clean['purpose']."'");
	$id=$db->insertId();

	foreach ($fields_array as $field) {

		if($field) $db->query("insert into ".TABLE_IE_TEMPLATES_FIELDS." set `template_id` = '$id', `field` = '$field'");

	}
	return 1;

}

function getFields() {

	if($this->data == "ad") {

		$listings = new listings;
		$array = $listings->getTableFields();
		$result = array_merge($array, $this->ad_ignore_tags);
		return $result;
	}

	$users = new users;
	$array = $users->getTableFields();
	$result = array_merge($array, $this->user_ignore_tags);
	return $result;

}

function getImportFields() {

	global $db;

	if($this->data == "ad") $result = $db->getTableFields(TABLE_ADS);
	else $result = $db->getTableFields(TABLE_USERS);

	return $result;

}


function getImportCSVFields($d='') {

	if($d) $data = $d;
	else $data = $this->data;

	if($data == "ad") {

		$listings = new listings;
		$fields = $listings->getTableCSVFields(0);
		return $fields;
	}

	$users = new users;
	$fields = $users->getTableCSVFields(0);
	return $fields;
	

}

function getExportCSVFields($d='') {

	if($d) $data = $d;
	else $data = $this->data;

	if($data == "ad") {

		$listings = new listings;
		$fields = $listings->getTableCSVFields();
		$farray = explode(",", $fields);
/*		$ext_fields = implode($this->ad_ignore_tags,',');

		if($fields) $fields.=',';
		$fields.=$ext_fields;
*/
		if(!in_array("url", $farray)) $fields .= ",url"; // add url to export fields

		return $fields;
	}

	$users = new users;
	$fields = $users->getTableCSVFields();
	$ext_fields = implode($this->user_ignore_tags,',');
	if($fields) $fields.=',';
	$fields.=$ext_fields;
	return $fields;
	

}


function getTemplateName($template) {

	global $db;
	$name = $db->fetchRow("select `name` from ".TABLE_IE_TEMPLATES." where id=$template");
	return $name;

}

function Export() {

	global $config_abs_path;
	// fname including template name
	if($this->template) $tname = str_replace(" ", "-",$this->getTemplateName($this->template).'-'); else $tname='';
	$fname = $this->data."-".$tname.date("d-m-Y").".".$this->type;
	if (!($this->f = fopen($config_abs_path.'/temp/'.$fname, 'w'))) {
		$this->addError($lng['ie']['errors']['cant_create_output_file']."<br>");
		return 0;
	}

	if($this->type=="csv") $this->CSVExport();
	else $this->XMLExport();

	fclose($this->f);

	header("Pragma: public");
	header("Expires: 0");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Content-Type: application/force-download");
	header("Content-Disposition: attachment; filename=".basename($fname));
	header("Content-Description: File Transfer");
	@readfile($config_abs_path.'/temp/'.$fname);
	exit;


}

function CSVExport() {

	$sett = $this->getSettings();
	$column_separator = $sett['csv_column_separator'];

	$this->purpose = "export";
	$template = $this->getTemplateFields($this->template);

	if($this->data == "ad") {
		$listings = new listings();
		$sql = $this->exportListings("csv");
	} else {
		$usr = new users();
		$sql = $usr->exportUsers("csv");
	}

	if($column_separator=='"') $str_sep = '"';
	else if($column_separator=="'") $str_sep = "'";
	else $str_sep = '"';

	switch ($column_separator) {
		case '\t':
			$column_separator = "\t";
			break;
		case '\r':
			$column_separator = "\r";
			break;
		case '\n':
			$column_separator = "\n";
			break;
	}

	$item = '';

	if($this->showFieldNames) {
	$i = 0;
	foreach ($template as $field) {
		if($i) $item.="$column_separator";
		$item .= $field;
		$i++;
	}
	$item.=$this->newline;
	fwrite($this->f, $item);
	} // end show field names

	global $db;
	$arr = $db->query($sql);
	$i=0;
	$result=array();
	$pictures=new pictures();
	global $seo_settings, $config_live_site;

	while ($row = $db->fetchAssocRes($arr)) {

		// clean slashes
		foreach($row as $key=>$value) $row[$key] = cleanStr($row[$key]);

		if($this->data == "ad") {

			$row['price_formatted'] = '';
			if($row['price']>0) $row['price_formatted'] = add_currency(format_price_field($row['price']), $row['currency']);

			// comma separated pictures
			$row['pictures'] = $pictures->getCSVPictures($row['id']);

			// make options field comma separated values !!!!!!!!!!!!!!!!!
			// format the other fields and add a _formatted to the field name !!!

			$row['stock']=sprintf("%04d", $row['id']);

			if($seo_settings['enable_mod_rewrite']) {
				$s = new seo;
				$row['url'] = $s->makeDetailsLink($row['id']);
			}
			else 
				$row['url'] = $config_live_site.'/details.php?id='.$row['id'];

		} else {

			if($row['blocked']) $row['blocked']=1;
			else $row['blocked']=0;

		}

		$item = '';
		$i = 0;
		foreach ($template as $field) {
			if($i) $item.="$column_separator";
			if(!isset($row[$field])) $row[$field]='';
			$item .= $str_sep.$row[$field].$str_sep;
			$i++;
		}
		$item.=$this->newline;
		fwrite($this->f, "$item");

	}

	return 1;

}

function XMLExport() {

	$this->purpose = "export";

	$template = $this->getTemplateFields($this->template);
	$top_xml = "<?xml version='1.0' ?\>";

	if($this->data == "ad") {
		$listings = new listings();
		$sql = $this->exportListings("xml");
		$top_tag = "<listings>";
		$bottom_tag = "</listings>";
		$start_tag = "<listing>";
		$end_tag = "</listing>";
	} else {
		$usr = new users();
		$sql = $usr->exportUsers("xml");
		$top_tag = "<users>";
		$bottom_tag = "</users>";
		$start_tag = "<user>";
		$end_tag = "</user>";
	}

	fwrite($this->f, stripslashes($top_xml).$this->newline);
	fwrite($this->f, $top_tag.$this->newline);

	global $db;
	$arr = $db->query($sql);
	$result=array();
	$pictures=new pictures();
	global $seo_settings, $config_live_site;

	while ($row = $db->fetchAssocRes($arr)) {

		// clean slashes
		foreach($row as $key=>$value) $row[$key] = cleanStr($row[$key]);

		if($this->data == "ad") {

			$row['price_formatted'] = add_currency(format_price_field($row['price']), $row['currency']);

			// comma separated pictures
			$row['pictures'] = $pictures->getCSVPictures($row['id']);

			// first picture only
			$row['picture'] = $pictures->getPictureURL($row['id']);

			// make options field comma separated values !!!!!!!!!!!!!!!!!
			// format the other fields and add a _formatted to the field name !!!

			$row['stock']=sprintf("%04d", $row['id']);

			if($seo_settings['enable_mod_rewrite']) {
				$s = new seo;
				$row['url'] = $s->makeDetailsLink($row['id']);
			}
			else 
				$row['url'] = $config_live_site.'/details.php?id='.$row['id'];


		} else {

			if($row['blocked']) $row['blocked']=1;
			else $row['blocked']=0;

		}

		$item = "\t".$start_tag;
		$item.=$this->newline;

		foreach ($template as $t) {
			if(!$t['field']) continue;
			if(!isset($row[$t['field']])) $row[$t['field']]='';
			if($t['cdata'] && $row[$t['field']]) { $cdata_start="<![CDATA["; $cdata_end = "]]>"; }
			else  { $cdata_start=""; $cdata_end = ""; }
			$item .= "\t\t<".$t['alias'].">".$cdata_start.$row[$t['field']].$cdata_end."</".$t['alias'].">";
			$item.=$this->newline;
		}
		$item .= "\t".$end_tag;
		$item.=$this->newline;
		fwrite($this->f, $item);

	}

	fwrite($this->f, $bottom_tag.$this->newline);

	return 1;

}


function editAliases($template) {

	global $db;
	global $lng;

	global $config_demo;
	if($config_demo==1) $this->addError($lng['general']['errors']['demo'].'<br />');

	$fields = $this->getTemplateFields($template);
	foreach($fields as $field) {
		$fname = $field['field'];
		if(isset($_POST[$fname]) && $_POST[$fname]!='') {
			$val = escape($_POST[$fname]);
			$cdata = checkbox_value($fname.'_cdata');
			if(!validator::valid_alias($val)) $this->setError($lng['ie']['errors']['invalid_alias']);
			else {
				$db->query("update ".TABLE_IE_TEMPLATES_FIELDS." set `alias` = '$val', `cdata` = $cdata where `field` like '$fname' and `template_id`='$template'");
			}
		}
	}
	return 1;

}

function getSettings() {

	global $db;
	$array = $db->fetchAssoc("select * from ".TABLE_IE_SETTINGS);

	switch ($array['csv_column_separator']) {
		case "\t":
			$array['csv_column_separator'] = "\\t";
			break;
		case "\r":
			$array['csv_column_separator'] = "\\r";
			break;
		case "\n":
			$array['csv_column_separator'] = "\\n";
			break;
	}
	return $array;

}

function saveBulkSettings() {

	global $db;
	global $lng;

	global $config_demo;
	if($config_demo==1) $this->addError($lng['general']['errors']['demo'].'<br />');

	if(isset($_POST['bulk_type']) && ($_POST['bulk_type']=="xml" || $_POST['bulk_type']=="csv")) $clean['bulk_type'] = $_POST['bulk_type']; else $clean['bulk_type']="xml";
/*
	if(isset($_POST['bulk_type']) && ($_POST['bulk_type']=="csv") && !$_POST['bulk_template'] ) { 
		$this->addError($lng['ie']['errors']['csv_bulk_uploads_require_template']);
		return 0;
	}
*/
	if(isset($_POST['bulk_template']) && $_POST['bulk_template'] && is_numeric($_POST['bulk_template'])) $clean['bulk_template'] = $_POST['bulk_template']; else $clean['bulk_template'] = '';

	if(isset($_POST['bulk_plan']) && is_numeric($_POST['bulk_plan'])) $clean['bulk_plan'] = $_POST['bulk_plan'];

	$db->query("update ".TABLE_IE_SETTINGS." set `bulk_type` = '".$clean['bulk_type']."', `bulk_template` = '".$clean['bulk_template']."', `bulk_plan` = '".$clean['bulk_plan']."'");
	return 1;
}

function saveCSVSettings() {

	global $lng;
	global $db;

	global $config_demo;
	if($config_demo==1) $this->addError($lng['general']['errors']['demo'].'<br />');

	if(isset($_POST['csv_column_separator'])) $clean['csv_column_separator'] = $_POST['csv_column_separator'];
	else { 
		$this->addError($lng['ie']['errors']['csv_column_separator']);
		return 0;
	}

	switch (stripslashes($clean['csv_column_separator'])) {
		case '\t':
			$clean['csv_column_separator'] = "\t";
			break;
		case '\r':
			$clean['csv_column_separator'] = "\r";
			break;
		case '\n':
			$clean['csv_column_separator'] = "\n";
			break;
	}

	if(isset($_POST['csv_field_separator'])) $clean['csv_field_separator'] = escape($_POST['csv_field_separator']); 
	else $clean['csv_field_separator'] = '';

	$db->query("update ".TABLE_IE_SETTINGS." set `csv_column_separator` = '".$clean['csv_column_separator']."', `csv_field_separator` = '".$clean['csv_field_separator']."'");
	return 1;

}

function FormCSVExport() {

	$this->setType("csv");

	if(isset($_POST['csv_export_type']) && ($_POST['csv_export_type']=="ad" || $_POST['csv_export_type']=="user"))
		$this->setData($_POST['csv_export_type']);
	else return 0;

	if(isset($_POST['csv_template']) && is_numeric($_POST['csv_template']))
		$this->setTemplate($_POST['csv_template']);

	if(isset($_POST['show_field_names']) && $_POST['show_field_names']=="on")
		$this->showFieldNames = 1;

	$this->Export();

}


function FormXMLExport() {

	$this->setType("xml");

	if(isset($_POST['xml_export_type']) && ($_POST['xml_export_type']=="ad" || $_POST['xml_export_type']=="user"))
		$this->setData($_POST['xml_export_type']);
	else return 0;

	if(isset($_POST['xml_template']) && is_numeric($_POST['xml_template']))
		$this->setTemplate($_POST['xml_template']);

	$this->Export();

}

	function xml_parse_file($file_name, $pos=0, $encoding="UTF-8")
	{
		//Initialize Parser
		$this->parser=xml_parser_create($encoding);

		xml_set_object($this->parser, $this);
		xml_set_default_handler($this->parser,"_default"); 

		//Specify Handlers to start and ending tag
		xml_set_element_handler($this->parser, "start_element", "end_element");
		//Data Handler
		xml_set_character_data_handler($this->parser, "character_data");
		//match the exact case
		//xml_parser_set_option($this->parser, XML_OPTION_TARGET_ENCODING, $encoding);
		xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, 0);
		xml_parser_set_option($this->parser, XML_OPTION_SKIP_WHITE, 0);
		//Open the Data File 

		$fp= @fopen($file_name, "r") ;

		if(!$fp) {
			$this->addError($lng['ie']['errors']['could_not_open_file']);
			return 0;
		}

		if(!$this->scheduled) {
		
			fseek($fp, $pos+1);
			
		}
		$this->continue=1;
		$this->eof=0;
		
		//read Data
		while ($this->continue && $data=$this->readXML($fp))
		{
			//echo "--".$data."--";
			if(!xml_parse($this->parser, $data, feof($fp))) $this->addError(sprintf('XML error %d:"%s" at line %d column %d byte %d',
            xml_get_error_code($this->parser),
            xml_error_string(xml_get_error_code($this->parser)),
            xml_get_current_line_number($this->parser),
            xml_get_current_column_number($this->parser),
            xml_get_current_byte_index($this->parser)));
		}
		xml_parser_free($this->parser);

		if(!$this->scheduled) {
			if(feof($fp)) $new_pos = 0;
			else $new_pos = ftell($fp);
		}
		
		@fclose($fp);

		if(!$this->scheduled)
			return $new_pos;
		
	}

	function readXML($f) {
	
		if($this->scheduled)
			$data = fread($f, 4096);
		else $data=fgets($f);
		return $data;
	}

	function whitespace_strip(&$str)
	{
		$str=preg_replace ('/ +/', ' ', trim($str));
		$str=preg_replace("/[\r\t\n]/","",$str);
	}

	function start_element($parser, $element_name, $element_attrs)
	{
	//echo $this->start_tag."!!!";
		// if top tag break
		if($element_name == $this->top_tag) return;

		// if this enabled, tags that don't exist are added to the content
		/*if($element_name != $this->start_tag && $element_name != $this->end_tag && $element_name != $this->top_tag && $element_name != $this->bottom_tag ) {
		$found = 0;
		for($i=0; $i<count($this->xml_tags);$i++)
		{
			if($this->xml_tags[$i]['field'] == $element_name || $this->xml_tags[$i]['alias'] == $element_name)
			{

				$found = 1;
				break;
			}
		}
		if( !$found ) { 
			$this->character_data($parser,"<$element_name>");
			return; 
		}
		}*/

		// if start tag, initialize a new array
		if($element_name == $this->start_tag) {
//echo "start tag!!!";
			//initialize $row_array
			$this->row_array = array();
			return;
		}

		$found=0;
		for($i=0; $i<count($this->xml_tags);$i++)
		{
			if($this->xml_tags[$i]['alias'] == $element_name || $this->xml_tags[$i]['field'] == $element_name)
			{
				$this->curr_element = $element_name;
				$this->row_array[$this->xml_tags[$i]['field']] = ''; // initialize data string
				$found=1;
				break;
			}
		}
		if(!$found) $this->curr_element = '';

	}

	function end_element($parser, $element_name)
	{

		// if top bottom break
		if($element_name == $this->top_tag) { $this->eof=1; return; }

		// if this enabled, tags that don't exist are added to the content
		/*if($element_name != $this->start_tag && $element_name != $this->end_tag && $element_name != $this->top_tag && $element_name != $this->bottom_tag ) {
		$found = 0;
		for($i=0; $i<count($this->xml_tags);$i++)
		{
			if($this->xml_tags[$i]['field'] == $element_name || $this->xml_tags[$i]['alias'] == $element_name)
			{

				$found = 1;
				break;
			}
		}
		if( !$found ) { $this->character_data($parser,"</$element_name>"); return; }
		}*/


		// if end tag, import the current registration
		if($element_name == $this->start_tag) {

			$this->addItem($this->row_array);
			$this->continue = 0;

		}

	}

	function character_data($parser,$data)
	{

		if(!$this->curr_element) return;

		$this->whitespace_strip($data);

		for($i=0; $i<count($this->xml_tags);$i++)
		{
			if($data != "")
			{

				if($this->xml_tags[$i]['field'] == $this->curr_element || $this->xml_tags[$i]['alias'] == $this->curr_element)
				{

					if(isset($this->row_array[$this->xml_tags[$i]['field']])) $this->row_array[$this->xml_tags[$i]['field']] .= $data;
					else $this->row_array[$this->xml_tags[$i]['field']] = $data;
					break;
				}
			}
			else
			{
				if($this->xml_tags[$i]['field'] == $this->curr_element || $this->xml_tags[$i]['alias'] == $this->curr_element)
				{
				if(!isset($this->row_array[$this->xml_tags[$i]['field']]))
					$this->row_array[$this->xml_tags[$i]['field']] = "";
				}
			break;
			}
		}

	}

	function _default($parser, $handler)
	{

	}

	function setShowFieldNames($val) {

		$this->showFieldNames = $val;

	}

	function Import() {

		global $lng;
		global $config_abs_path;

		global $config_demo;
		if($config_demo==1) $this->addError($lng['general']['errors']['demo'].'<br />');

		if(!isset($_POST[$this->type.'_import_type']) || $_POST[$this->type.'_import_type']=='' || ( $_POST[$this->type.'_import_type']!= "ad" && $_POST[$this->type.'_import_type'] != "user" )) return;
		$this->data = $_POST[$this->type.'_import_type'];

		if($this->data == "ad") $this->table = TABLE_ADS; else $this->table = TABLE_USERS;


		if(isset($_POST['show_field_names']) && $_POST['show_field_names']=="on")
		$this->showFieldNames = 1;

		// upload file
		if(isset($_FILES[$this->type.'_import_file']['name']) && $_FILES[$this->type.'_import_file']['name']) $file_name = $_FILES[$this->type.'_import_file']['name']; 
		else { $this->addError($lng['ie']['errors']['upload_import_file']); return 0;}

		// check if extension is the type
		if(getExtension($file_name) != $this->type) { $this->addError($lng['ie']['errors']['incorrect_file_type'].$this->type); return 0;}

		$dir = $config_abs_path."/temp/";
		if(!move_uploaded_file($_FILES[$this->type.'_import_file']['tmp_name'], $dir.$file_name)) {
			$this->addError($lng['ie']['errors']['could_not_upload_file']); 
			return 0; 
		}

		// get template post
		if(isset($_POST[$this->type.'_template']) && $_POST[$this->type.'_template']!='') $this->template = escape($_POST[$this->type.'_template']);

		$this->default['package_id'] = ''; $this->default['category_id'] = ''; $this->default['user_id'] = ''; $this->default['group'] = '';
		$this->default['date_added'] = ''; $this->default['registration_date'] = '';

		// get data default fields: plan, category, user, date_added - for listings    group for users
		if($this->data == "ad") {

			$sett = $this->getSettings();

			if(isset($_POST[$this->type.'_plan']) && $_POST[$this->type.'_plan']!='') $this->default['package_id'] = escape($_POST[$this->type.'_plan']);
			else $this->default['package_id'] = $sett['bulk_plan']; // set the default bulk uploading package

			if(isset($_POST[$this->type.'_category']) && $_POST[$this->type.'_category']!='') $this->default['category_id'] = escape($_POST[$this->type.'_category']); 

			if(isset($_POST[$this->type.'_user']) && $_POST[$this->type.'_user']!='') {

				global $settings;
				if($settings['enable_username'])
					$this->default['user_id'] = users::getUserId(escape($_POST[$this->type.'_user']));
				else 
					$this->default['user_id'] = users::getIdByEmail(escape($_POST[$this->type.'_user']));
				
			}

			if(isset($_POST[$this->type.'_date_added']) && $_POST[$this->type.'_date_added']!='') $this->default['date_added'] = escape($_POST[$this->type.'_date_added']);

			if(isset($_POST[$this->type.'_language']) && $_POST[$this->type.'_language']!='') $this->default['language'] = escape($_POST[$this->type.'_language']);

		} else {

			if(isset($_POST[$this->type.'_group']) && $_POST[$this->type.'_group']!='') $this->default['group'] = escape($_POST[$this->type.'_group']);

			if(isset($_POST[$this->type.'_date_added']) && $_POST[$this->type.'_date_added']!='') $this->default['registration_date'] = escape($_POST[$this->type.'_date_added']);

		}

		if($this->type=="csv") $result = $this->CSVImport($dir.$file_name);
		else if($this->type=="xml") $result = $this->XMLImport($dir.$file_name);

		return $result;
	}

	function XMLImport($file_name) {

		global $lng;
		global $db;
		$this->file_name = $file_name;
		// make xml tags list

		if($this->data == "ad") { 
			$this->required_fields = array("user_id", "category_id", "package_id", "title", "description");
		}
		else $this->required_fields = array ("group", "username", "email", "password");

		$auth = new auth();
		$is_admin = $auth->adminLoggedIn();
		//if($is_admin) array_push($this->required_fields, "date_added");

		$this->purpose = "import";

		if($this->template) { 
			$this->xml_tags = $this->getTemplateFields($this->template);
			foreach ($this->required_fields as $f) {
				$found=0;
				foreach ( $this->xml_tags as $x ) {
					if($x['field'] == $f) { $found=1; break; }
				}
				// add to tags array if it does not exist
				// check first if not "category_id" field and "category" already exists
				if(!$found && ($f!="category_id" || !in_array("category",$this->xml_tags))) array_push($this->xml_tags, array("field" => $f,"alias" => $f));

				// search for mgm_email
				global $settings;
				if( $f=="user_id" && !$found && $settings['nologin_enabled']) {
					foreach ( $this->xml_tags as $x ) {
						if($x['field'] == "mgm_email") { $found=1; break; }
					}
				}

			}
		}
		else {
			$arr_fields = $this->getFields();//$db->getTableFields($this->table); // include extra ignore fields

			$i=0;
			foreach ($arr_fields as $row) {
				$this->xml_tags[$i]['field'] = $row;
				$this->xml_tags[$i]['alias'] = $row;
				$i++;
			}
		}

		// the tags on one dimmensional array
		if($this->template) {
			$tags = array();
			$i = 0;
			foreach ($this->xml_tags as $tag) {
				$tags[$i] = $tag['field'];
				$i++;
			}
		}

		if($this->data == "ad") {

			//$this->required_fields = array("user_id", "category_id", "package_id", "title", "description");
			$this->def_fields = array("user_id" => $this->default['user_id'], "category_id" => $this->default['category_id'], "package_id" => $this->default['package_id'], "date_added" => $this->default['date_added'], "title" => "", "description" => "");

			$this->top_tag = "listings";
			$this->start_tag = "listing";
			$this->end_tag = "listing";
			$this->bottom_tag = "listings";
			
		} else {

			//$this->required_fields = array ("group", "username", "email", "password");
			$this->def_fields = array("group" => $this->default['group'], "username" => "", "email" => "", "password" => "");

			$this->top_tag = "users";
			$this->start_tag = "user";
			$this->end_tag = "user";
			$this->bottom_tag = "users";
		}	

		// check if the template has the required fields or they are included in the default fields
		// only if a template is chosen
		if($this->template) {
			$str_not_present = '';
			$no_missing = 0;
			foreach ($this->required_fields as $req) {

				if(!in_array($req, $tags) && !$this->def_fields[$req]) {

					//check if category is not added as category name ("category") in the template
					if($req=="category_id" && in_array("category",$this->xml_tags)) continue;

					if($no_missing) $str_not_present.=", ";
					$str_not_present .= '"'.$req.'"';
					$no_missing++;
				}
			}
			if($no_missing) { $this->addError($lng['ie']['errors']['following_fields_missing'].$str_not_present); return 0; }
		}

		global $appearance_settings;
		$enc = "UTF-8";
		if($appearance_settings['charset']=="iso-8859-1") $enc = "ISO-8859-1";
		

		if($this->scheduled) {
		
			$this->xml_parse_file($file_name, 0, $enc);
			return 1;
		}

		$this->default_lang = languages::getDefault();

		$data = json_encode($this);
		global $config_table_prefix;
		$db->query("insert into `".$config_table_prefix."temp_import` set `json`='".escape($data)."'");
		$import_id = $db->insertId();
		return $import_id;
	}

	function CSVImport($file_name) {

		global $db, $config_abs_path;
		global $lng;
		require_once $config_abs_path."/classes/categories.php";
		$this->file_name = $file_name;

		$this->purpose = "import";

		if($this->data == "ad") {

			$this->required_fields = array("user_id", "category_id", "package_id", "title", "description");
			$this->def_fields = array("user_id" => $this->default['user_id'], "category_id" => $this->default['category_id'], "package_id" => $this->default['package_id'], "date_added" => $this->default['date_added'], "title" => "", "description" => "");

		} else {

			$this->required_fields = array ("group", "username", "email", "password");
			$this->def_fields = array("group" => $this->default['group'], "username" => "", "email" => "", "password" => "");

		}	

		if($this->template) $this->xml_tags = $this->getTemplateFields($this->template);
		else $this->xml_tags = $db->getTableFields($this->table);

		// check if the template has the required fields or they are included in the default fields
		// only if a template is chosen
		if($this->template) {

			$str_not_present = '';
			$no_missing = 0;
			foreach ($this->required_fields as $req) {

				if(!in_array($req, $this->xml_tags) && !$this->def_fields[$req]) {

					//check if category is not added as category name ("category") in the template
					if($req=="category_id" && in_array("category",$this->xml_tags)) continue;

					// search for mgm_email
					global $settings;
					if( $req=="user_id" && !$found && $settings['nologin_enabled'] && in_array("mgm_email",$this->xml_tags)) continue;

					if($no_missing) $str_not_present.=", ";
					$str_not_present .= '"'.$req.'"';
					$no_missing++;
				}
			}
			if($no_missing) { $this->addError($lng['ie']['errors']['following_fields_missing'].$str_not_present); return 0; }
		}

		$fp= @fopen($file_name, "r") ;
		if(!$fp) {
			$this->addError($lng['ie']['errors']['could_not_open_file']);
			return 0;
		}

		$ie_settings = $this->getSettings();
		$this->column_separator = $ie_settings['csv_column_separator'];

		switch ($this->column_separator) {
		case '\t':
			$this->column_separator = "\t";
			break;
		case '\r':
			$this->column_separator = "\r";
			break;
		case '\n':
			$this->column_separator = "\n";
			break;
		}

		$this->field_separator = $ie_settings['csv_field_separator'];

		// if fist one contains the fields read one here
		if($this->showFieldNames) {

			$this->xml_tags = fgetcsv($fp, 4096, $this->column_separator);
			$num = count($this->xml_tags);
			// trim fields separator from the end if any
			if(!$this->xml_tags[$num-1]) array_pop($this->xml_tags);

			// remove spaces
			$i=0;
			foreach($this->xml_tags as $x){ $this->xml_tags[$i] = trim($x); $i++;} 

			// check if the fields are valid and all present
			$str_not_present = '';
			$no_missing = 0;
			foreach ($this->required_fields as $req) {

				if(!in_array($req, $this->xml_tags) && !$this->def_fields[$req]) {

					// search for mgm_email
					global $settings;
					if( $req=="user_id" && $settings['nologin_enabled'] && in_array("mgm_email",$this->xml_tags)) continue;
					if($no_missing) $str_not_present.=", ";
					$str_not_present .= '"'.$req.'"';
					$no_missing++;
				}

			}

			if($no_missing) { $this->addError($lng['ie']['errors']['following_fields_missing'].$str_not_present); return 0; }
		}

		if(!$this->scheduled) {
			$this->no_fields = count($this->xml_tags);
			@fclose($fp);

			$this->default_lang = languages::getDefault();
		
			$data = json_encode($this);
			global $config_table_prefix;
			$db->query("insert into `".$config_table_prefix."temp_import` set `json`='".escape($data)."'");
			$import_id = $db->insertId();
			return $import_id;
		}

	}
/*
	function parse_csv($data) {

		$this->row_array = array();
		// get ie settings
		$settings = $this->getSettings();
		$column_separator = $settings['csv_column_separator'];
		$field_separator = $settings['csv_field_separator'];

		if(function_exist(str_getcsv)) {
			$this->row_array = str_getcsv($data, $column_separator, $field_separator);
		}

		$start_field = $column_separator.$field_separator;
		$end_field = $field_separator.$column_separator;

		//Search and replace all instances of ," with some unique string, say **sep**.
		str_replace($start_field, "**sep**", $data);

		//Search and replace (you will need regular expressions) all instances of " at the beginning of a line with the same unique string.

		//Search and replace all instances of ", with the same unique string.
		str_replace($end_field, "**sep**", $data);

		//Search and replace (you will need regular expressions) all instances of " at the end of a line with the same unique string.

		//Search and replace all remaining " with a different unique string **quote**.
		str_replace($field_separator, "**quote**", $data);

		//Search and replace your first unique string with ".

		//Import the file into Excel.
		//In Excel, search and replace the second unique string with ".

		return 1;

	}
*/

	function CSVImportLine($import_id, $line) {
//echo "line=".$line;
		global $db, $config_abs_path, $config_table_prefix;
		global $lng;
		$dline = $line;
		$this->import_id = $import_id;

		$json = $db->fetchRow("select `json` from `".$config_table_prefix."temp_import` where id='$import_id'");
		$ie_array = json_decode($json, true);

		$this->xml_tags = $ie_array['xml_tags'];
		$this->def_fields = $ie_array['def_fields'];
		$this->uid = $ie_array['uid'];
		$this->no_duplicates = $ie_array['no_duplicates'];
		$this->table =  $ie_array['table'];
		$this->data = $ie_array['data'];
		$this->default = $ie_array['default'];
		$this->required_fields = $ie_array['required_fields'];

		$fp= @fopen($ie_array['file_name'], "r");
		
		if($ie_array['showFieldNames']) { fgets($fp); }
		
		$i=0;
		while(!feof($fp) && $i<($line-1))
		{
			$data = fgets($fp);  
			$i++;
		}
		$data = fgetcsv($fp, 4096, $ie_array['column_separator'], $ie_array['field_separator']);
		if($data==FALSE) { 
			if(feof($fp)) return 0;
			else return $dline+1;
		}
	//	_print_r($this->xml_tags);
		$num = count($data);
		if($num!=$ie_array['no_fields']) { $this->addError($lng['ie']['errors']['incorrect_data_count'].$line); return $dline+1; }

		$this->row_array = array();
		$cat_exists = 0;
		for ($c=0; $c < $num; $c++) {

			if($this->xml_tags[$c]=="category_id") $cat_exists = 1;
//echo "cat_exists=".$cat_exists;
			if($this->xml_tags[$c]=="category") { 
//echo "categggg";
				if(!$cat_exists) {
					$this->row_array["category_id"] = categories::getIdByName($data[$c]);
					$this->xml_tags[$c] = "category_id";
					$cat_exists = 1;
				}
				else { $c++; continue; }
					
			}
			else { 
				//if($this->xml_tags[$c]=="category_id" && $cat_exists) { $c--; continue;}
				$this->row_array[$this->xml_tags[$c]] = $data[$c];
			}

		}

		// add default fields if not present
		foreach ($this->def_fields as $df =>$dvalue) {

			if(!in_array($df, $this->xml_tags)&& ($df!="category_id" || !in_array("category",$this->xml_tags))) { $this->row_array[$df] = $dvalue; array_push($this->xml_tags, $df); }
		}

		$this->addItem($this->row_array);

		@fclose($fp);
		
		return $dline+1;
	}

	function XMLImportLine($import_id, $pos) {
	
		global $db, $config_abs_path, $config_table_prefix;
		global $lng;
		$this->import_id = $import_id;

		$json = $db->fetchRow("select `json` from `".$config_table_prefix."temp_import` where id='$import_id'");
		$ie_array = json_decode($json, true);

		$this->xml_tags = $ie_array['xml_tags'];
		$this->def_fields = $ie_array['def_fields'];
		$this->uid = $ie_array['uid'];
		$this->no_duplicates = $ie_array['no_duplicates'];
		$this->table =  $ie_array['table'];
		$this->data = $ie_array['data'];
		$this->default = $ie_array['default'];
		$this->required_fields = $ie_array['required_fields'];
		
		$this->start_tag = $ie_array['start_tag'];
		$this->top_tag = $ie_array['top_tag'];
		$this->end_tag = $ie_array['end_tag'];
		$this->bottom_tag = $ie_array['bottom_tag'];

		global $appearance_settings;
		$enc = "UTF-8";
		if($appearance_settings['charset']=="iso-8859-1") $enc = "ISO-8859-1";

		$new_pos = $this->xml_parse_file($ie_array['file_name'], $pos, $enc);
		
		return $new_pos;
	
	}
	

	function addItem($parsed_array) {
//_print_r($this->default);
//_print_r($this->def_fields);
//_print_r($parsed_array);
		global $lng;
		global $db;
		$sql_str = ''; $where='';
		$uid_exists = 0;

		// if unique id field should be used
		if($this->uid && $parsed_array['id']) {

			$uid_exists = $db->fetchRow("select `id` from ".TABLE_ADS." where `unique_id` = '{$parsed_array['id']}'");
			if($uid_exists) { 

				// if only_download_inexisting
				if($this->no_duplicates) return;

				$sql_str = "update ".$this->table." set ";
				$where = " where `unique_id` = '{$parsed_array['id']}'";
			}

		}

		if(!$sql_str) $sql_str = "insert into ".$this->table." set ";
		$i = 0;

		global $config_abs_path;
		if($this->data=="ad") {
			require_once $config_abs_path."/classes/listings.php";
			require_once $config_abs_path."/classes/pictures.php";
			$cl_pics = new pictures;
		}

		$date_added = $this->default['date_added'];
		$date_expires = 0;
		$user_approved = 0;
		$category_id = 0;

		$fields_array = $this->getImportFields();
		// add "pictures" to fields list
		if($this->data=="ad") {
			array_push($fields_array, "pictures");
			array_push($fields_array, "mgm_email");
		}

		$category_id_exists=0;
		$xml_tags = $this->xml_tags;
		foreach($this->xml_tags as $tag) {
		
			if($tag=="category_id") $category_id_exists++;
		
		}
		if($category_id_exists>1) { 
			$x = 0;
			foreach($xml_tags as $tag) {
				if($tag=="category_id") { array_splice($xml_tags, $x, 1); break;}
				$x++;
			}
		}
//_print_r($xml_tags);
		foreach ($xml_tags as $tag) {

			$val = '';
			if($this->type=="csv") { 
				$field_name = $tag;
				$alias = "";
			}
			else { 
				$field_name = $tag['field'];
				$alias = $tag['alias']; 
			}

			// if not in fields array except "pictures" exit 

			if(!in_array($field_name, $fields_array)) continue;
			//if($this->data == "ad" && in_array($field_name, $this->ad_ignore_tags)) continue;
			//if($this->data == "user" && in_array($field_name, $this->user_ignore_tags)) continue;

			// category_id and date_added should be allowed to be rewritten by scheduled imports
			// any default field should be allowed to be rewritten by admin
			global $is_admin;
			if(!$is_admin && !($this->scheduled && $field_name=="category_id") && !($this->scheduled && $field_name=="date_added")) {

			// if default value add this not the one parsed
			if(isset($this->default[$field_name]) && $this->default[$field_name]) {
				if($i) $sql_str.=", ";
				$sql_str.="`$field_name` = '".$this->default[$field_name]."'";
				$i++;
				continue;
			}
			} // end if not admin or scheduled

			// check if $this->required_fields are all present or default valules 
			if( in_array($field_name, $this->required_fields) && 
				!((isset($parsed_array[$field_name]) && $parsed_array[$field_name]) || (isset($parsed_array[$alias]) && $parsed_array[$alias]))	
				) 
			{

			// use the selected default value if present
				// this will be used as last resort, if a tag does not exist for that field
				if($this->def_fields[$field_name]) $val = $this->def_fields[$field_name];
				else  { 
					// category special case
					// check if both category_id and category, add only one
					if($field_name=="category_id") {
						if(isset($parsed_array["category"])) $val = categories::getIdByName($parsed_array["category"]);

						// use alias tag if present
						if(!$val && isset($parsed_array[$alias])) $val = categories::getIdByName($parsed_array[$alias]);

						if($val) { $category=1; continue;}
					}

					if($field_name=="user_id") continue;

					// could not added because a requied field is missing
					$this->failed++;

					$this->addError($lng['ie']['errors']['required_field_missing'].$field_name.'<br>');
					
					return 0;
				}
				
			} else { // not required fields
				if($field_name=="category") {
					$field_name = "category_id";
					if(isset($parsed_array[$field_name])) $val = categories::getIdByName($parsed_array[$field_name]);
					if(!$val && isset($parsed_array[$alias])) $val = categories::getIdByName($parsed_array[$alias]);
					$category_id = $val;
				} else {		
					if(isset($parsed_array[$field_name])) $val = $parsed_array[$field_name];
					// use alias tag if present
					if(!$val && isset($parsed_array[$alias])) $val = $parsed_array[$alias];
				}

			}

			if(!$val) continue;
			if($field_name=="date_expires") $date_expires=1;
			if($field_name=="date_added") $date_added=$val;
			if($field_name=="registration_date") $registration_date=$val;
			if($field_name=="user_approved") $user_approved=1;

			// special fields

			if($this->data == "ad") {

				// un-format price
				if($field_name == "price") {

					$val = $this->unformat_price($val);

				}

				if($field_name == "pictures") {

					$pictures_str = $val;

				} 

				if($field_name == "mgm_email") {

					$mgm_email_str = $val;

				} 


			} else {

				if($field_name == "password") {// encrypt password

					$val = users::encode($val);

				}

			}

			// if id, check if the id already exists and if exists do not add it to query
			if($field_name == "id") {

				// unique id field
				if($this->uid) {

					if($i) $sql_str.=", ";
					$sql_str.="`unique_id` = '$val'";
					$i++;
					

				} else {

					if($this->data=="ad") $exists = listings::idExists($val);
					else $exists = users::exists($val);

					if(!$exists) { 
						if($i) $sql_str.=", ";
						$sql_str.="`$field_name` = '$val'";
						$i++;
					}
			} 
			}
			else 
			if($this->data != "ad" || ($this->data=="ad" && $field_name != "pictures" && $field_name != "mgm_email")) {
				if($i) $sql_str.=", ";
				$sql_str.="`$field_name` = '".escape($val)."'";
				$i++;
			}
		}

		if($this->data == "ad") {

		if(!isset($parsed_array['date_added']) && !isset($this->default['date_added'])) {
			if(!isset($date_added) || !$date_added) {
				$date_added = date("Y-m-d H:i:s");
			}
			$sql_str.=", date_added = '$date_added'";
		}
			$pkg = 0;
			if(isset($parsed_array['package_id']) || $this->default['package_id']) {
				$pkg = $this->default['package_id'];
				if(!$pkg && isset($parsed_array['package_id'])) $pkg = $parsed_array['package_id'];
				$p = new packages;
				$pkg_info = $p->getPackage($pkg);
			}
			// set date expires afterwards if having date_added and package
			if( !$date_expires && $pkg ) {
				$no_days = $pkg_info['no_days'];
				if($no_days) $sql_str.=", `date_expires` = date_add('$date_added', interval '$no_days' day)";
			}

			if(!isset($parsed_array['language']) && !isset($this->default['language'])) {
				global $crt_lang; 
				$sql_str.=", language = '$crt_lang'";
			}

			// set user approved
			if( !$user_approved ) {
				$sql_str.=", `user_approved` = 1";
			}

		}

		if($this->data=="user") {

			if(!isset($registration_date) || !$registration_date) { 
				$registration_date = date("Y-m-d H:i:s");
			}
			$sql_str.=", registration_date = '$registration_date'";
			
			// get group info
			$gr = 0;
			if(isset($parsed_array['group']) || $this->default['group']) {
				$gr = $this->default['group'];
				if(!$gr && isset($parsed_array['group'])) $gr = $parsed_array['group'];
				$groups = new groups();
				$group_info = $groups->getGroup($gr);
			}
			
		}
		
		$listing = new listings;
		// if update ad first decrement from old category, then increment to the correct category
		if($uid_exists)	$listing->decCat($uid_exists, 1);
//echo $sql_str.$where;
		$db->query($sql_str.$where);
		//echo $sql_str.$where."<br/>";
		if(!$uid_exists) $ad_id=$db->insertId();
		else $ad_id = $uid_exists;

		if($this->data=="ad") {
			$slug = new slugs();
			$title_field = "title";
			global $ads_settings;
			if($ads_settings['translate_title_description']) {
				$title_field .= "_".$this->default_lang;
			}
			if(!$slug->slugForObjectExists($ad_id, 'listing'))
				$slug->addListing($ad_id, $parsed_array[$title_field]);
		}
		
		// increment no for categories
		$listing->incCat($ad_id, 1, $category_id);

		global $config_table_prefix;
		
		if(!$this->scheduled) {
		
		if($ad_id) { 
			/*
			$this->succeeded++;
			$c = count($this->added_elements);
			$this->added_elements[$c] = $ad_id;*/
			$db->query("update `".$config_table_prefix."temp_import` set `succeeded`=`succeeded`+1 where `id`={$this->import_id}");
		}
		else {
			$db->query("update `".$config_table_prefix."temp_import` set `failed`=`failed`+1 where `id`={$this->import_id}");
			//$this->failed++;
		}
		} // end if not scheduled

		if($this->data=="ad" && $pkg) {

			$update = 0;
			// check if default plan has options: featured, highlited, video, priorities and set them for the ad
			$sql_update = "update ".TABLE_ADS." set ";
			if($pkg_info['featured']) {
				if($update) $sql_update.=",";
				$sql_update.="featured = '".$pkg_info['featured']."' ";
				$update = 1;
			}
			if($pkg_info['highlited']) {
				if($update) $sql_update.=",";
				$sql_update.="highlited = 1 ";
				$update = 1;
			}
			if($pkg_info['urgent']) {
				if($update) $sql_update.=",";
				$sql_update.="urgent = 1 ";
				$update = 1;
			}
			if($pkg_info['priority']) {
				if($update) $sql_update.=",";
				$sql_update.="priority = '".$pkg_info['priority']."' ";
				$update = 1;
			}

			$sql_update .="where `id` = $ad_id";

			if($update) $db->query($sql_update);

			if($pkg_info['video']) { 
				$listing->setVideo($ad_id,1);
				
			}
			if($pkg_info['url']) { 
				$listing->setUrl($ad_id,1);
				
			}

		}

		if($this->data=="user" && $gr) {

			$update = 0;
			// check if group has extra options: store, bulk uploads
			$sql_update = "update ".TABLE_USERS." set ";
			if($group_info['store']) {
				if($update) $sql_update.=",";
				$sql_update.="store = 1 ";
				$update = 1;
			}
			if($group_info['bulk_uploads']) {
				if($update) $sql_update.=",";
				$sql_update.="bulk_uploads = 1 ";
				$update = 1;
			}
			$sql_update .="where `id` = $ad_id";

			if($update) $db->query($sql_update);

		}

		if($this->data == "ad" && isset($pictures_str) && $pictures_str) {

			$array_pics = explode(",", $pictures_str);
			
			$cl_pics->deletePictures($ad_id);
			
			foreach ($array_pics as $pic) {
				if(!$pic) continue;

				// if url and image, download, save and then add to database
				if($pic && validator::isImage($pic))
					//$this->addTempPicture($ad_id, $pic);
					$cl_pics->addUrl($ad_id, $pic);

			}
		}

		if($this->data == "ad" && isset($mgm_email_str) && $mgm_email_str) {

			global $config_abs_path;
			require_once $config_abs_path."/classes/listings_process.php";
			listings_process::addOwnerInfo($ad_id, ", `mgm_email` = '$mgm_email_str'");

		}
		
		$this->last_id = $ad_id;
	}
	/*
	function addTempPicture($ad_id, $pic_url) {
	
		global $db;
		$db->query("insert into ".TABLE_TEMP_PICTURES." set `ad_id`='$ad_id', `picture_url`='$pic_url'");
	
	}*/

	function getCustomFieldsList() {

		global $db;
		$array_na = array("id", "user_id", "viewed", "featured", "highlited", "priority", "urgent"); // for users
		$array = $db->getTableFields(TABLE_ADS);
		$str = "";
		foreach ($array as $row) {
			if(!in_array($row, $array_na)) $str.=', "'.$row.'"';
		}
		return $str;
	}

	function unformat_price($str) {

		global $appearance_settings;
		$dec_sep = $appearance_settings['price_field_format_point'];
		$th_sep = $appearance_settings['price_field_format_separator'];
		$price = str_replace("$th_sep", "", $str);
		$price = str_replace("$dec_sep", ".", $price);
		return $price;

	}

	function bulkUploads() {

		global $db;
		global $lng;
		global $config_abs_path;

		$ie_settings = $this->getSettings();


		$this->type = $ie_settings['bulk_type'];
		$this->template = $ie_settings['bulk_template'];
		$this->data = "ad";
		$this->table = TABLE_ADS;

		$this->default['package_id'] = ''; $this->default['category_id'] = ''; $this->default['user_id'] = ''; $this->default['group'] = '';
		$this->default['date_added'] = ''; $this->default['registration_date'] = '';

		if(isset($_POST[$ie_settings['bulk_type'].'_category']) && $_POST[$ie_settings['bulk_type'].'_category']!='') $this->default['category_id'] = escape($_POST[$ie_settings['bulk_type'].'_category']); 
/*		else { // check if the template has a category, if not ask to choose one
			
			$fields = $this->getTemplateCSVFields($this->template);
			$fields_array = explode(",", $fields);
			if(!in_array("category_id", $fields_array) && !in_array("category", $fields_array)) {
				$this->addError($lng['ie']['errors']['choose_categ']);
				return 0;
			}
		}*/

		// check if a file uploaded
		if(isset($_FILES[$this->type.'_import_file']['name']) && $_FILES[$this->type.'_import_file']['name']) $file_name = $_FILES[$this->type.'_import_file']['name']; 
		else { $this->addError($lng['ie']['errors']['upload_import_file']); return 0;}

		// check if extension is the type
		if(getExtension($file_name) != $this->type) { $this->addError($lng['ie']['errors']['incorrect_file_type'].$this->type); return 0;}

		// upload file
		$dir = $config_abs_path."/temp/";
		if(!move_uploaded_file($_FILES[$this->type.'_import_file']['tmp_name'], $dir.$file_name)) {

			$this->addError($lng['ie']['errors']['could_not_upload_file']); 
			return 0; 
		}

		$sett = $this->getSettings();

		$this->default['package_id'] = $sett['bulk_plan']; // set the default bulk uploading package
		global $crt_usr;
		$this->default['user_id'] = $crt_usr; 

		if($this->type=="csv") { 
		//	$this->setShowFieldNames(1); 
			$result = $this->CSVImport($dir.$file_name); 
		}
		else if($this->type=="xml") $result = $this->XMLImport($dir.$file_name);

		return $result;

	}

	function getStats($show_ads=1) {

		global $lng;
		global $seo_settings;
		global $config_live_site;
		$mod_rewrite = $seo_settings['enable_mod_rewrite'];
		$info = $lng['ie']['bulk_upload_status'].' '.$this->succeeded.' '.$lng['ie']['successfully'].', '.$this->failed.' '.$lng['ie']['failed'].'<br>';
		if($this->succeeded && $show_ads) {
			$info.=$lng['ie']['uploaded_listings'].'<br>';
			//$listings = new listings();
			foreach ( $this->added_elements as $el) {

				$title = cleanStr(listings::getTitle($el));

				if($mod_rewrite) {
					$seo = new seo();
					$lnk=$seo->makeDetailsLink($el, $title);
				}
				else 
					$lnk=$config_live_site.'/details.php?id='.$el;

				$info .= '<a href="'.$lnk.'">'.$title.'</a><br>';

			}
		}

		return $info;
	}

	function exportListings($type){ 

		global $db;
		global $lng;
		global $appearance_settings;
		global $ads_settings;
		$date_format=$appearance_settings["date_format"];

		$where = ""; $where_start = " where";
		if(isset($_POST[$type.'_category']) && is_numeric($_POST[$type.'_category'])) { 
			$where .= $where_start." ".TABLE_ADS.".`category_id` = '".$_POST[$type.'_category']."'";
			$where_start = " and";
		}
		if(isset($_POST[$type.'_plan']) && is_numeric($_POST[$type.'_plan']))  { 
			$where .= $where_start." ".TABLE_ADS.".`package_id` = '".$_POST[$type.'_plan']."'";
			$where_start = " and";
		}
		if(isset($_POST[$type.'_user']))  { 
			if(isset($_POST[$type.'_user']) && $_POST[$type.'_user']) {

				global $settings;
				if($settings['enable_username']) $f = 'username'; else $f = 'email';

				$where .= $where_start." ".TABLE_USERS.".`$f` like '".$_POST[$type.'_user']."'";
				$where_start = " and";
			}
		}

		if(isset($_POST[$type.'_date_start']) && $_POST[$type.'_date_start']!='') { 
			$where .= $where_start." ".TABLE_ADS.".`date_added` > '".escape($_POST[$type.'_date_start'])."'";
			$where_start = " and";
		}

		if(isset($_POST[$type.'_date_end']) && $_POST[$type.'_date_end']!='') { 
			$where .= $where_start." ".TABLE_ADS.".`date_added` < '".escape($_POST[$type.'_date_end'])."'";
			$where_start = " and";
		}

		if(isset($_POST[$type.'_last'])) $last = escape($_POST[$type.'_last']);
		else $last = '';

		if(isset($_POST[$type.'_ad_order_by']) && $_POST[$type.'_ad_order_by']!='') $order_by = escape($_POST[$type.'_ad_order_by']);
		else $order_by = TABLE_ADS.'.date_added';

		if(isset($_POST[$type.'_ad_order_way']) && $_POST[$type.'_ad_order_way']!='') $order_way = escape($_POST[$type.'_ad_order_way']);
		else $order_way = 'desc';

		$timestamp = date("Y-m-d H:i:s");

		$sql = "select ".TABLE_ADS.".*, ".TABLE_ADS.".id as id, date_format(`date_added`,'$date_format') as date_formatted, date_format(`date_expires`,'$date_format') as date_expires_formatted, (date_expires<'$timestamp' and date_expires!='0000-00-00 00:00:00' and ".TABLE_ADS.".active=0) as expired, ".TABLE_CATEGORIES."_lang.name as category, ".TABLE_PACKAGES."_lang.name as plan, ".TABLE_PACKAGES.".amount as plan_amount, ".TABLE_USERS.".username as username, ".TABLE_USERS_PACKAGES.".pending as pending_package, ".TABLE_ACTIONS.".`invoice` from ".TABLE_ADS." 
		left join ".TABLE_CATEGORIES."_lang on ".TABLE_ADS.".category_id=".TABLE_CATEGORIES."_lang.id 
		left join ".TABLE_PACKAGES."_lang on ".TABLE_ADS.".package_id=".TABLE_PACKAGES."_lang.id 
		left join ".TABLE_PACKAGES." on ".TABLE_ADS.".package_id=".TABLE_PACKAGES.".id 
		left join ".TABLE_USERS_PACKAGES." on ".TABLE_ADS.".usr_pkg=".TABLE_USERS_PACKAGES.".id 
		left join ".TABLE_USERS." on ".TABLE_ADS.".user_id=".TABLE_USERS.".id 
		left join ".TABLE_ACTIONS." on ".TABLE_ADS.".id=".TABLE_ACTIONS.".object_id 
		".$where." and ( ".TABLE_ACTIONS.".`type` like 'newad' or ".TABLE_ACTIONS.".`type` like 'renewad' ) 
		group by ".TABLE_ADS.".id
		order by ".$order_by." ".$order_way." , ".TABLE_ACTIONS.".`date` desc";

		if($last>0) $sql .= " limit ".$last;

		return $sql;
	}

}


?>
