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

function contains($str, $sub, $caseSenstive=false)
{
	if(!$sub) return false;
	if(is_array($str))
		return in_array($sub, $str);
	if(!$caseSenstive)
	{
		$str=strtolower($str);
		$sub=strtolower($sub);
	}
	return strpos($str, $sub)!==false;
}

function startsWith($str, $sub, $caseSenstive=false)
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

function splitBeforeAfter($string, $char, $last=false)
{	
	if(empty($char)) 
		$pos = false;
	else
		$pos = $last ? strrpos($string, $char) : strpos($string, $char);

	if ($pos===false)
	{
		$before = $string;
		$after = "";
	}
	else
	{
		$before = substr($string, 0, $pos);
		$pos += strlen($char);
		$after = substr($string, $pos);
	}

	return array($before, $after);
}

function substringBefore($string, $char, $stringOrEmpty=true, $include=false, $last=false)
{	
	if(empty($char)) 
		return $string;
	$pos = $last ? strrpos($string, $char) : strpos($string, $char);
	if ($pos===false) 
		return $stringOrEmpty ? $string : "";		
	if($include)
		$pos+=strlen($char);

	return substr($string, 0, $pos);
}

function substringBeforeLast($string, $char, $stringOrEmpty=true, $include=false)
{	
	return substringBefore($string, $char, $stringOrEmpty, $include, true);
}

function substringAfter($string, $char, $stringOrEmpty=false, $include=false, $last=false)
{	
	if(empty($char)) 
		return $string;
	$pos = $last ? strrpos($string, $char) : strpos($string, $char);
	if ($pos===false) 
		return $stringOrEmpty ? $string : "";		
	if(!$include)
		$pos+=strlen($char);
	return substr($string, $pos);
}

function substringAfterLast($string, $char, $stringOrEmpty=false, $include=false)
{	
	return substringAfter($string, $char, $stringOrEmpty, $include, true);
}

function trimChar($str, $ch, $left=true, $right=true)
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

function cutAfterSentence($text, $maxLength)
{
	$before = substr($text, 0, $maxLength);
	return substringBeforeLast($before, ".", true, true);
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

//remove non printable control chars
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

function cleanupFilenameCmd($filename)
{
	$filename = str_replace("  ", " ", $filename);
	$filename = str_replace(":", "-", $filename);
	$filename = str_replace("/", "-", $filename);

	$filename = str_replace(";", "", $filename);
	$filename = str_replace(",", "", $filename);
	$filename = str_replace(".", "", $filename);
	$filename = str_replace("?", "", $filename);
	return trim($filename);
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
function parseValue($var, $separator="")
{
	$var=trim($var);
	if(is_numeric($var))
		return 0+$var;
	if(!strcasecmp($var,"true") || !strcasecmp($var,"false"))
		return parseBoolean($var);
	if($separator && contains($var,$separator))
		return explode($separator, $var);
	return parseConstant($var);
}

//convert to defined constant value if it exists
function parseConstant($name)
{
	if(defined($name))
		return constant($name);
	return $name;
}

function parseColor($color)
{
	if(defined($color))
		return constant($color);
	if(defined(strtoupper($color)))
		return constant(strtoupper($color));

	$color = hexdec($color);
	debug("parseColor int color", $color); 
	debug("parseColor hex color", dechex($color)); 
	return $color;
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

function formatDate($mtime,$xml=false)
{
	if(!$mtime) return "";
	$date = date("Y-m-d H:i:s", $mtime);
	if($xml)
		$date = str_replace(" ", "T", $date);
	return $date;
}

//hh:mm:ss => number of seconds
function parseTime($time)
{
	if(is_numeric($time)) return $time;
	$arr = explode(":", $time);
	$days = $hours = 0;
	if(count($arr) == 4)
		$days = array_shift($arr);
	if(count($arr) == 3)
		$hours = array_shift($arr);
	$minutes = array_shift($arr);
	$seconds = array_shift($arr);

	return $seconds + 60*$minutes + 60*60*$hours + 60*60*24*$days;
}

function formatTime($seconds)
{
	$seconds = round($seconds);
	$minutes = floor($seconds / 60);
	$hours = floor($minutes / 60);
	$seconds = $seconds % 60;
	$minutes = $minutes % 60;
	if($minutes < 10 && $hours > 0)
		$minutes = "0$minutes";
	if($seconds < 10)
		$seconds = "0$seconds";
	$timeString="$minutes:$seconds";
	if($hours > 0)
	 	$timeString="$hours:$timeString";
	return $timeString;
}

?>