<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class badwords {

    var $name;
    var $words_list;

    public function __construct($name='')
    {
	
	$this->name=$name;
	$this->words_list='';

    }

    function getNo() {

	global $db;
	$no=$db->fetchRow('select count(*) from '.TABLE_BADWORDS);
	return $no;
    }

    function getAll() {

	global $db;
	$arr=$db->fetchRowList('select word from '.TABLE_BADWORDS.' order by word');
	$i = 0;
	foreach($arr as $row)
		$arr[$i++] = cleanStr($row);
	return $arr;
    }

    function addToWordsList($word) {
    
		if($this->words_list)
			$this->words_list.=", ";
		$this->words_list.=$word;
    }
    
    function getWordsList() {
		return $this->words_list;
    }
    
    function deleteAll() {

	global $db;
	global $config_demo;
	if($config_demo==1) return;

	$res=$db->query('delete from '.TABLE_BADWORDS);
    }

    function delete($str) {

	global $db;
	global $config_demo;
	if($config_demo==1) return;

	$res=$db->query('delete from '.TABLE_BADWORDS.' where word like "'.$str.'"');
    }

function addBulk($str) {

	global $db;
	global $config_demo;
	if($config_demo==1) return;
		
	$word_array = explode(',', $str);
	foreach($word_array as $w) {
		$w=trim(escape($w));
		if(!$w) continue;
		$res=$db->query('select * from '.TABLE_BADWORDS.' where word like "'.$w.'";');
		$exists=$db->numRows($res);
		if(!$exists) $res1=$db->query('insert into '.TABLE_BADWORDS.' values ("'.$w.'");');
	}
}

function check($str) {

	$str = " ".$str;
	$bad=$this->getAll();
	if(!$bad) return 0;
	
	$pattern = '/\b(';
	$div = '';
	$find=0;
	
	/*
	foreach ($bad as $word) {
		$pattern .= $div . preg_quote($word, "/");
		$div = '|';
	}
	
	$pattern .= ')\b/i';
	if (preg_match($pattern, $str)) {
	    $find++;
	    //$this->addToWordsList($word);
	}*/
	
	foreach ($bad as $word) {
		if(strlen($word)>1)
			$pattern = '/\b('.preg_quote($word, "/").')\b/i';
		else 
			$pattern = '/['.$word.']/i';
		if (preg_match($pattern, $str)) {
			$find++;
			$this->addToWordsList($word);
		}
	}

	return $find;
}

function replace($str) {

	$str = " ".$str." ";
	$bad=$this->getAll();
	if(!$bad) return 0;
	
	$pattern = '/\b(';
	$div = '';
	$find=0;
	foreach ($bad as $word) {
		$pattern .= $div . preg_quote($word, "/");
		$div = '|';
	}
	$pattern .= ')\b/i';
	return preg_replace($pattern, "****", $str);

}


}
?>
