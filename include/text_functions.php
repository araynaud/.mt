<?php
function equals($str,$sub,$caseSenstive=false)
{
	if(!$caseSenstive)
	{
		if(is_string($str)) $str=strtolower($str);
		if(is_string($sub)) $sub=strtolower($sub);
	}
	return $str == $sub;
}

function contains($str,$sub,$caseSenstive=false)
{
	if(!$sub) return false;
	if(!$caseSenstive)
	{
		$str=strtolower($str);
		$sub=strtolower($sub);
	}
	return strpos($str, $sub)!==false;
}

function startsWith($str,$sub, $caseSenstive=false)
{
	if(!$caseSenstive)
	{
		$str=strtolower($str);
		$sub=strtolower($sub);
	}
	return strpos($str, $sub)===0;
}

function endsWith($str, $sub, $caseSenstive=false)
{
	if(!$caseSenstive)
	{
		$str=strtolower($str);
		$sub=strtolower($sub);
	}
    $strlen = strlen($str);
    $testlen = strlen($sub);
    if ($testlen > $strlen)
		return false;
    return substr_compare($str, $sub, -$testlen) === 0;
}

//remove or add dot
function toggleDot($str)
{
	if($str[0]=='.') //remove initial dot if present
		return substr($str,1);
	else //add dot if absent
		return "." . $str;
}

function substringBefore($string,$char,$stringOrEmpty=true,$include=false)
{	
	if(empty($char)) 
		return $string;
	$pos=strpos($string,$char);
	if ($pos===false ) 
		return $stringOrEmpty ? $string : "";		
	if($include)
		$pos+=strlen($char);

	return substr($string, 0, $pos);
}

function substringBeforeLast($string,$char,$stringOrEmpty=true,$include=false)
{	
	if(empty($char)) 
		return $string;
	$pos=strrpos($string,$char);
	if ($pos===false) 
		return $stringOrEmpty ? $string : "";		
	if($include)
		$pos+=strlen($char);

	return substr($string, 0, $pos);
}

function substringAfter($string,$char,$stringOrEmpty=false,$include=false)
{	
	if(empty($char)) 
		return $string;
	$pos=strpos($string,$char);
	if ($pos===false ) 
		return $stringOrEmpty ? $string : "";		
	if(!$include)
		$pos+=strlen($char);
	return substr($string,$pos);
}

function substringAfterLast($string,$char,$stringOrEmpty=false,$include=false)
{	
	if(empty($char)) 
		return $string;
	$pos=strrpos($string,$char);
	if ($pos===false ) 
		return $stringOrEmpty ? $string : "";		
	if(!$include)
		$pos+=strlen($char);
	return substr($string,$pos);
}

function trimChar($str,$ch,$left=true,$right=true)
{	
	if(empty($str) || empty($ch)) 
		return $str;

	// trim left
	while($left && $str[0]==$ch)
		$str=substr($str, 1);  
	// trim right
	if($right) 
	{
		$len=strlen($str);
		while($len > 0 && $str[--$len]==$ch)
			$str=substr($str, 0, -1);  
	}
	return $str;
}

function makePathTitle($path, $depth=1)
{
	global $config;
	if(empty($path))
		return isset($config["TITLE"]) ? $config["TITLE"] : "";

	if($depth==1)
		return makeTitle(substringAfterLast($path,"/",true));
		
	$pathArray = explode('/',$path);
	$nb=count($pathArray);
	if( empty($depth) || $depth > $nb)
		$depth =  $nb;

	$title ="";
	$sep="";
	for ($i = $nb - $depth; $i < $nb; $i++)
	{
		if(empty($pathArray [$i]))
			continue;
		$title .= $sep . makeTitle($pathArray [$i]);
		$sep=" ";
	}
	return $title;
}

function sortMinMax(&$min,&$max)
{
	if($min<=$max)return false;
	swap($min,$max);
	return true;
}

function deleteChars($string, $start, $end=null)
{
	if($end===null) $end = $start+1;
	sortMinMax($start, $end);
	$before = substr($string, 0, $start);
	$after = substr($string, $end);
	return "$before$after";
}

// filename to title
// transform _ into spaces
// insert spaces between a and A or digit

function escapeAmp($str)
{
	$str = str_replace("&", "&amp;", $str);
	$str = str_replace("<", "&lt;", $str);
	$str = str_replace(">", "&gt;", $str);
	return str_replace("\"", "&quot;", $str);
}

//remove nono printable control chars
function removeControlChars($str)
{
	//remove non printable control chars
	$str = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x80-\x9F]/u', '', $str);
//	$str = preg_replace( '/[^[:print:]]/', ':p', $str);
//	$str = preg_replace("/[[:cntrl:]]/", ':c', $str);
	return $str;
}

function escapeNewLine($str, $escapeQuotes=true)
{
	//remove non printable control chars
	$str =  removeControlChars($str);

	$str=str_replace("\\", "\\\\", $str);
	$str=str_replace("\r", "", $str);
	$str=str_replace("\n", "\\n", $str);
	$str=str_replace("\t", "\\t", $str);

	if($escapeQuotes)
		$str=str_replace('"', '\\"', $str);
	return $str;
}

function makeTitle($filename)
{
	$filename= str_replace("_", " ", $filename);
	//$filename= str_replace("/", " ", $filename);
	$filename= str_replace("-", " - ", $filename);
	$filename= str_replace(".", " ", $filename);
	$filename= str_replace("  ", " ", $filename);
	//$filename=escapeAmp($filename);
	
	if(ctype_lower($filename) || ctype_upper($filename) || ctype_digit($filename))
		return $filename;
		
	//search for lowercaseUppercase sequences in string, insert spaces between
	$nbWords=0;
	$wordPos=0;
	$formLabel="";
	while($wordPos < strlen($filename)) //and nbWords < 3
	{
		$nextWordPos = findNextWord($filename,$wordPos);
		if (!empty($formLabel))
			$formLabel = $formLabel . " ";
		$formLabel = $formLabel . substr($filename,$wordPos,$nextWordPos-$wordPos+1);
		$wordPos=$nextWordPos+1;
		$nbWords++;
	}
	return $formLabel;
}

function findNextWord($str,$startPos)
{
	$j=$startPos;
	$count=0;
	$endPos=strlen($str)-1;
	//default result, if no next word found
	$nextWord=strlen($str);
	$found=false;
	while($j < $endPos && !$found)
	{
		$currentChar=substr($str,$j,1);
		$nextChar=substr($str,$j+1,1);
		if( different_ctype($currentChar,$nextChar))
		{
			$nextWord = $j;
			$found=true;
			$count++;
		}
		$j++;
	}
	return $nextWord;
}

//test if different character types to insert a space
function different_ctype($currentChar,$nextChar)
{
	return	ctype_lower($currentChar) && (ctype_upper($nextChar) || ctype_digit($nextChar))  // lU or l3
	|| 	  	ctype_digit($currentChar) && (ctype_lower($nextChar) || ctype_upper($nextChar))
	|| 		ctype_upper($currentChar) && ctype_digit($nextChar); // A0
}

function cleanupFilename($filename)
{
	$filename= str_replace(" ", "_", $filename);
	$filename= str_replace("&", "",  $filename);
	$filename= str_replace("à", "a", $filename);
	$filename= str_replace("é", "e", $filename);
	$filename= str_replace("è", "e", $filename);
	$filename= str_replace("ê", "e", $filename);
	$filename= str_replace("ë", "e", $filename);
	$filename= str_replace("ù", "u", $filename);
	$filename= str_replace("ç", "c", $filename);
	return $filename;
}

//encode to UTF8 only if not already encoded
function encodeUtf8($string)
{
	if(mb_detect_encoding($string, 'UTF-8', true) === FALSE)
		$string = utf8_encode($string);
	return $string;
}

//encode from UTF8 only if encoded
function decodeUtf8($string)
{
	if (mb_detect_encoding($string, 'UTF-8', true) === TRUE) 
		$string = utf8_decode($string);
	return $string;
}


//copy $src to $dst if $dst is empty
function setIfEmpty(&$dst,$src)
{
	if(empty($dst))	$dst=$src;
	return $dst;
}

function setIfNull(&$dst,$src)
{
	if($dst===null) $dst=$src;
	return $dst;
}

function setIfSet(&$dst,$src)
{
	if(isset($src)) $dst=$src;
	return $dst;
}

//convert if numeric or boolean
function parseValue($var)
{
	$var=trim($var);
	if(is_numeric($var))
		return 0+$var;
	if(!strcasecmp($var,"true") || !strcasecmp($var,"false"))
		return parseBoolean($var);
	return parseConstant($var);
}

//convert to defined constant value if it exists
function parseConstant($name)
{
	$constants=get_defined_constants();
	if(isset($constants[$name]))
		return $constants[$name];
	return $name;
}


// ARRAY functions

function toArray($str)
{
	if(is_string($str))
		return preg_split("/[,; \|]/", $str);
//	if(is_array($str))
	return $str;
}


//flatten array to string
// ! does it modify array ?
function arrayJoinRecursive($array, $sep="|")
{
	if(!is_array($array))
		return $array;
		
	foreach($array as $key=>$value)
		if(is_array($value))
			$array[$key]=arrayJoinRecursive($value, $sep);

	return implode($sep,$array);
}

function flattenArray($array)
{
	if(!is_array($array))
		return (array) $array;

	$result=array();
	foreach($array as $key=>$value)
		$result = array_merge($result,flattenArray($value));

	return $result;
}

//array_search 
function arraySearchRecursive($array,$search)
{
	if(is_string($array) && strcasecmp($array,$search)===0)
		return true;

	if(!is_array($array))
		$array = toArray($array);
		
	foreach($array as $key=>$value)
	{
		if($value===$search ||  (is_string($value) && strcasecmp($value,$search)===0))	
			return $key;

		if(!is_array($value)) continue;

		$sub=arraySearchRecursive($value,$search);
		if($sub) 
		{
			debug("arraySearchRecursive", "$key.$sub");
			return "$key.$sub";
		}
	}
	return false;
}

//arrayGet from nested array
function arrayGet($array, $keys)
{
	if(!is_array($keys))
		$keys=explode(".", $keys);
	if(empty($keys))
		return $array;
	//if more keys, look into sub array
	$key=array_shift($keys);
	if(!isset($array[$key])) 
		return null;
	if(is_array($array[$key]))
		return arrayGet($array[$key], $keys);
	return $array[$key];
}

function getConfig($key)
{
	global $config;
	return arrayGet($config, $key);
}

//format string with args
//for each argument: quote if necessary
function formatString()
{
	$args = func_get_args();
	$str = array_shift($args);
	foreach($args as $n => $param)
		$cmd = str_replace("[$n]", $param, $cmd);

	return $cmd;
}

//return 1st non null argument
function coalesce()
{
	$args = func_get_args();
	foreach($args as $arg)
		if($arg!==null)
			return $arg;
	return null;
}

//return 1st found argument
function arrayGetCoalesce()
{
	$args = func_get_args();
	$array = array_shift($args);
	foreach($args as $keys)
	{
		$val = arrayGet($array, $keys);
		if($val!==null)
			return $val;
	}
	return null;
}

//filter array by keys, not values.
function arrayFilterKeys($a, $funct)
{
	$f = array_filter(array_keys($a), $funct); 
	$b = array_intersect_key($a, array_flip($f));
	return $b;
}
?>