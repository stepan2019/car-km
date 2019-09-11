<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class rss {

	var $style='';
	
	public function __construct()
	{
	
		$this->rss_version = '2.0';
		$this->error='';
	}

	function noRss() {

		global $db;
		$no=$db->fetchRow("select count(*) from ".TABLE_RSS." where enabled=1");
		return $no;
	}

	function getFirst() {

		global $db;
		$id = $db->fetchRow("select id from ".TABLE_RSS." where enabled=1 limit 1");
		return $id;

	}


	function getRssFeed($id) {

		global $db;
		global $crt_lang;

		$result=$db->fetchAssoc("select * from ".TABLE_RSS." LEFT JOIN ".TABLE_RSS."_lang on ".TABLE_RSS.".`id` = ".TABLE_RSS."_lang.`id` where `lang_id`='$crt_lang' and ".TABLE_RSS.".id=$id");

		return $result;

	}

	function getRssFeedLang($id) {

		global $db;
		if(!$id) $id=$this->id;

		$row=$db->fetchAssoc("select * from ".TABLE_RSS." where id=".$id."");

		if($row['show_fields']) $row['array_show_fields'] = explode(",", $row['show_fields']);
		$row['param'] = array();
		if($row['parameters']) {
			$arr = explode("&", $row['parameters']);
			foreach($arr as $p) {
				$pexp = explode("=", $p);
				$pname=$pexp[0];
				$parray=explode(",",$pexp[1]);
				$j=0;
				$row['param'][$pname] = array();
				foreach ($parray as $pa) {
					if($pa)
					$row['param'][$pname][$j++] = $pa;
				}
			}
		}

		$array_lang=$db->fetchAssocList("select * from ".TABLE_RSS."_lang where id=".$id."");

		foreach($array_lang as $rss_lang) {

			$lang_id = $rss_lang['lang_id'];
			foreach ($rss_lang as $key=>$value) {
				if($key=='id' || $key=='lang_id') continue;
				$row[$key][$lang_id] = cleanStr($value);
			}
		}
		return $row;

	}

	function makeListingsCondition($str) {

		global $db;

		$condition = " where ".TABLE_ADS.".`active` = 1 and ".TABLE_ADS.".`user_approved` = 1 ";
		if(!$str) return $condition;
		$array_fields = array();
		$i=0;
		$res = $db->query("describe ".TABLE_ADS);
		while($row = $db->fetchRowRes($res)) { 
			$array_fields[$i] = $row;
			$i++;
		}
		$array = explode("&", $str);
		foreach ($array as $item) {
			
			$exp = explode ("=",$item);
			if(!in_array($exp[0], $array_fields)) continue;
			if(strstr($exp[1],',')) $condition .= " and `".$exp[0]."` in (".$exp[1].") ";
			else $condition .= " and `".$exp[0]."` like '".$exp[1]."' ";

		}
		return $condition;

	}

		function makeUsersCondition($str) {

		global $db;

		//group=14&store=1&order_by=contact_name&order_way=asc
		
		$group = '';
		$store = '';
		$condition='';
		
		$array = explode("&", $str);
		foreach ($array as $item) {
			
			$exp = explode ("=",$item);
			if($exp[0]=="group") $group = $exp[1];
			if($exp[0]=="store") $store = $exp[1];

		}
		
		if( $store && $group ) $condition = " where (`group` = '$group' or `store` = '$store') ";
		else {
			if($group) $condition = " where `group` = '$group' ";
			if($store) $condition = " where `store` = '$store' ";
		}
		
		return $condition;

	}

	function getOrderBy($param) {
	
		$array = explode("&", $param);
		$order_by = "registration_date";

		foreach ($array as $item) {
		
			$exp = explode ("=",$item);
			if($exp[0]=="order_by") $order_by = $exp[1];
		
		}
		
		return $order_by;
	
	}
	
	function getOrderWay($param) {
	
		$array = explode("&", $param);
		$order_way = "desc";
		
		foreach ($array as $item) {
		
			$exp = explode ("=",$item);
			if($exp[0]=="order_way") $order_way = $exp[1];
		
		}
		
		return $order_way;
	
	}

	function generateFeed($feed, $items) {

		header('Content-Type: application/xml');

		global $appearance_settings;
		global $settings;
		global $config_live_site;

		$charset=$appearance_settings['charset'];
		$site_name=$settings['site_name'];
		$built = date("r");//Sat, 17 Jan 2009 10:53:45 GMT
		$pub = date("r");//Sat, 17 Jan 2009 10:53:45 GMT

		$head = "<?xml version=\"1.0\" encoding=\"$charset\"?>\n";
		if($this->style) $head.=$this->style."\n";
		$head.="<rss version=\"2.0\" xmlns:atom=\"http://www.w3.org/2005/Atom\" xmlns:content=\"http://purl.org/rss/1.0/modules/content/\">\n
<channel>\n
	<atom:link href=\"".$config_live_site."/feed.php\" rel=\"self\" type=\"application/rss+xml\" />
	<title>".rss::well_formed($feed['title'])."</title>\n
	<link>".$feed['link']."</link>\n
	<description>".cleanStr($feed['description'])."</description>\n
	<language>".$feed['language']."</language>\n
	<generator>".rss::well_formed($site_name)."</generator>\n
	<docs>http://blogs.law.harvard.edu/tech/rss</docs>\n
	<lastBuildDate>$built</lastBuildDate>\n
	<pubDate>$pub</pubDate>\n";

		echo $head;

		foreach($items as $item) {

			$item['content'] = $this->cData($item['content']);

			$str = "		<item>\n
			<title>".$item['title']."</title>\n
			<guid>".$item['guid']."</guid>\n
			<link>".$item['link']."</link>\n
			<pubDate>".$item['date']."</pubDate>\n
			<description>".$item['description']."</description>\n
			<content:encoded>".$item['content']."</content:encoded>\n
		</item>\n";

			echo $str;

		}

		$tail = "</channel>\n
</rss>\n";

		echo $tail;
	}


	function cData($str)
	{
		return '<![CDATA[ ' . $str . ' ]]>';
	}

	function setStyle($str)
	{
		$this->style=$str;
	}

static function well_formed($str) {

	global $appearance_settings;
	$str = htmlspecialchars( strip_tags(html_entity_decode($str, ENT_NOQUOTES, $appearance_settings['charset'])), ENT_QUOTES, $appearance_settings['charset'] );
	// remove non ascii characters
	//$str = preg_replace('/[^(\x20-\x7E)]*/','', $str);
	return $str;

}

}

?>
