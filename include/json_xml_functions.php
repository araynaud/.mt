<?php
//JSON serialization functions

//output PHP variable in query string parameter
function qsParameters($names, $first=true)
{
	if(is_string($names))
		$names=explode(",",$names);
	$result="";
	foreach ($names as $name)
	{
		$param=qsParameter($name, $first);
		if(empty($param)) continue;
		$result.=$param;
		$first=false;
	}
	return $result;
}

function qsParameter($name, $first=false)
{
	if(!isset($GLOBALS[$name]) || empty($GLOBALS[$name]))
		return "";
	$sep=$first ? "?" : "&";
	return "$sep$name=" . $GLOBALS[$name];
}

function addVarToArray(&$arr, $name, $empty=false)
{
	if(!$empty && (!isset($GLOBALS[$name]) || empty($GLOBALS[$name])))	return;
	$arr[$name] = @$GLOBALS[$name];
	return $arr;
}

//output boolean to string for JS
function BtoS($val)
{
	return $val ? "true" : "false";
}

//get class name of an object or type of a variable
function getVarType($var)
{
	if(is_object($var))	return get_class($var);
	return gettype($var);	
}

function indent($indentLevel,$char="\t")
{
	if($indentLevel==false || $indentLevel<=0) 
		return "";
	if($indentLevel===true || !is_numeric($indentLevel)) 
		$indentLevel=1;

	return "\n" . str_repeat ($char, $indentLevel-1);
}

function nextIndent($indentLevel,$char="\t")
{
	return $indentLevel > 0 ? $indentLevel+1 : $indentLevel;
}


//look for array with all numeric keys and containing no object or array
function isScalarOrArray($array)
{
	if(is_null($array))	return true;
	if(is_scalar($array))	return true;
	return isScalarArray($array);
}

function isScalarArray($array)
{
	if(!is_array($array))	return false;
	//look for key not numeric and not equal to value, or not scalar element
	foreach($array as $key=>$value)
		if(!is_null($value) && !is_scalar($value) || isAssociativePair($key,$value))
			return false;
	return true;
}


//look for array with key not numeric and not equal to value
function isAssociativeArray($array)
{
	if(!is_array($array))	return false;
	foreach($array as $key=>$value)
		if(isAssociativePair($key,$value))
			return true;
	return false;
}

function isAssociativePair($key,$value)
{
	if(is_string($value) && contains($value, $key))
		return false;
	return !is_int($key);
}

//determine if value should be output to JSON / XML
//skip empty objects, arrays, string, null
//output false bool and 0 numbers
function isEmptyValue($value)
{
	return empty($value) && !is_bool($value) && !is_numeric($value);
}

function isNotEmptyValue($value)
{
	return !isEmptyValue($value);
}

function objToArray($obj, $private=false, $includeEmpty=true, $recursive=false)
{
	if(is_scalar($obj)) return $obj;

	if(is_object($obj) && !$private)
	 	$vars = get_object_vars($obj);
	else
		$vars = (array) $obj;
	if(!$includeEmpty)
		$vars=array_filter($vars,"isNotEmptyValue");
//	if(is_array($obj)) return $vars;

//debug("objToArray",	getVarType($obj));
	$result=array();
	foreach ($vars as $key => $value)
	{
		$elementName=substringAfterLast($key, chr(0), true);
		if($elementName==="" || $elementName[0]==="_") continue;

		if($recursive && (is_object($value) || is_array($value)))		
			$result[$elementName] = objToArray($value, $private, $includeEmpty, $recursive);
		else		
			$result[$elementName] = $value;
	}

	return $result;
}

//output PHP variable in JS
function jsVar($name,$newvar=false,$indent=1, $includeEmpty=false, $private=true)
{
	$result=indent($indent);
	if($newvar) $result .= "var ";
	$result.="$name=" .	jsVarValue($name,$indent, $includeEmpty, $private) . ";\n";
	return $result;
}

//output variable value in javascript syntax
//TODO: recursive, outputs objects { key: value, etc: other }
function jsVarValue($name,$indent=1, $includeEmpty=false, $private=true)
{
	return jsValue($GLOBALS[$name],$indent, $includeEmpty, $private);
}

function jsValue($val, $indent=1, $includeEmpty=false, $private=true)
{
	if(!isset($val))	return "null";		//null
	if(is_bool($val)) 	return BtoS($val);	//boolean
	if(is_array($val))	return jsArray($val,  $indent, $includeEmpty, $private);	//JSON array []
	if(is_object($val)) return jsObject($val, $indent, $includeEmpty, $private);
	if(isJsonString($val)) return $val;
	if(is_string($val)) return '"' . escapeNewLine(encodeUtf8($val)) . '"';	//string between quotes. utf8_encode
	return $val;			//number, no quote
}

//TODO: if JSON, do not escape string jsValue(json) = json;
function isJsonString($val)
{
	if(!is_string($val)) return false;
	$val=trim($val);
	return startsWith($val, "{") && endsWith($val, "}") 
		|| startsWith($val, "[")  && endsWith($val, "]")
		|| startsWith($val, "'")  && endsWith($val, "'")
		|| startsWith($val, '"')  && endsWith($val, '"');
}

//write JSON array from PHP array
//recursive, outputs objects
function jsArray($array, $indent=1, $includeEmpty=false, $private=false)
{
	if(isAssociativeArray($array))
		return jsObject($array, $indent, $includeEmpty, $private);

	if(!$includeEmpty)
		$array=array_filter($array,"isNotEmptyValue");

	$separator = ",";
	if(isScalarArray($array)) 
	{
		$separator = ", ";
		$indent=0;
	}

	$tab=indent($indent);
	$indent=nextIndent($indent);		
	$sep="";
	$result="";
	foreach ($array as $value)
	{
//debugStack();		
		$jsValue=jsValue($value,$indent,$includeEmpty,$private);
		if(!$includeEmpty && isEmptyValue($jsValue)) continue;

		$result .= $sep;
		if(!is_array($value) && !is_object($value))
			$result .= indent($indent);
		$result .= $jsValue;
		$sep = $separator;
	}
	if(empty($result))
		return $includeEmpty ? "[]" : "";	
	return $tab . "[$result$tab]";
	
}

//write JSON object from PHP array or object
//recursive, outputs objects
function jsObject($obj, $indent=1, $includeEmpty=false, $private=false)
{
	$tab=indent($indent);
	$indent=nextIndent($indent);
	$vars=objToArray($obj, $private, $includeEmpty, true);
	//remove vars that start with _
	$sep="";
	$result = "";
	foreach($vars as $key=>$value)
	{
		$jsValue=jsValue($value,$indent,$includeEmpty,$private);
		if(!$includeEmpty && isEmptyValue($jsValue)) continue;

		$result .= $sep . indent($indent) . "\"$key\": ";
		$result .= $jsValue;
		$sep=",";
	}
	if(empty($result))
		return $includeEmpty ? "{}" : "";	
	return "$tab{" . $result . "$tab}";
}

//XML serialization functions

//write XML  from PHP array or object
//recursive

/*
<MediaFile key="100">
	<name>100</name>
	<versions>
		<MediaFileVersion/>
	</versions>
</MediaFile>
*/

function xmlValue($name, $value, $indent=1, $includeEmpty=true, $private=false, $outputAttributes=true)
{
//debug("outputAttributes", $outputAttributes);
	$tab=indent($indent);
	if(is_object($value))	return xmlObject($name, $value, $indent, $includeEmpty, $private, $outputAttributes);
	if(is_array($value))	return xmlArray($name, $value, $indent, $includeEmpty, $private, $outputAttributes);
	if(is_numeric($value)|| is_bool($value))		return "$tab<$name>$value</$name>";

	$strValue =  utf8_encode(escapeAmp(removeControlChars($value)));
	if(!isEmptyValue($strValue))		return "$tab<$name>$strValue</$name>";
	if($includeEmpty)		return "$tab<$name/>";
	return "";
}

function xmlArray($name, $array, $indent=1, $includeEmpty=true, $private=false, $outputAttributes=true)
{
//debug("outputAttributes", $outputAttributes);
	if(isAssociativeArray($array))
		return xmlObject($name, $array, $indent, $includeEmpty, $private, $outputAttributes);

	$tab=indent($indent);
	//$indent=nextIndent($indent);
	
	if(!$includeEmpty)
		$array=array_filter($array,"isNotEmptyValue");
	
	$result="";
	foreach($array as $key=>$value)
	{
		$elementName= isAssociativePair($key,$value) ? $key : $name; //getVarType($value);
		$result .= xmlValue($elementName, $value, $indent, $includeEmpty, $private, $outputAttributes);
	}

	return $result;
	
	if(empty($result))
		return $includeEmpty ? "$tab<$name/>" : "";

	return "$tab<$name>$result$tab</$name>";
}

//Serialize object to XML. output private variables?
function xmlObject($name, $obj, $indent=1, $includeEmpty=false, $private=true, $outputAttributes=true)
{
//debug("outputAttributes", $outputAttributes);
debug("xmlObject $name", $obj);
	$tab=indent($indent);
	$indent=nextIndent($indent);
	$vars=objToArray($obj, $private, $includeEmpty);
	if(!$name) $name=getVarType($obj);
debug("xmlObject vars", $vars);
			
	$result="";
	$attributes="";
	foreach($vars as $key=>$value)
	{
		if(is_numeric($key))
			$elementName=getVarType($value);
		else
			$elementName=$key;
debug($elementName, isAttribute($key, $value));
		if($outputAttributes && isAttribute($key, $value))
			$attributes .= xmlAttribute($elementName, $value);
		else
			$result .= xmlValue($elementName, $value, $indent, $includeEmpty, $private, $outputAttributes);
	}

	if(empty($result) && empty($attributes))
		return $includeEmpty ? "$tab<$name/>" : "";
	if(empty($result))
		return "$tab<$name$attributes/>";
	return "$tab<$name$attributes>$result$tab</$name>";
}

function xmlAttribute($name, $value)
{
	$value=escapeAmp(utf8_encode($value));
	return " $name=\"$value\"";
}

function isElement($var)
{
	return is_object($var) || is_array($var);
}

function isAttribute($key, $var)
{
	return isAssociativePair($key, $var) && (is_null($var) || is_scalar($var));
}

//-------------------- CSV serialization functions


//csvValue: write key and valye in 1 line
//if object or array of objects associative array: write rows recursively
function hasObjects($data)
{
	if(!is_object($data) && !isAssociativeArray($data))
		return false;
	if(is_object($data) || isAssociativeArray($data))
	{
		debug("hasObjects", $depth);
		return $depth;
	}
	foreach($data as $key => $val)
		if(hasObjects($val))
			return $true;
	return false;
}

function csvValue($val, $includeEmpty=false, $separator=";")
{
	if(!isset($val))	return "";		//null
	if(is_bool($val)) 	return BtoS($val);	//boolean
	if(is_string($val)) return escapeNewLine($val,false); //encodeUtf8(escapeNewLine($val,false));	
	if(is_scalar($val)) return "$val";			//number, no quote

	if(isScalarArray($val))
	{
		$result=array();
		foreach($val as $key => $el)
		{
			$v = csvValue($el, $includeEmpty, $separator);
			if($v || $includeEmpty)
				$result[]=$v;
		}
		return implode($separator, $result);
	}

	return arrayToCsv($val, $includeEmpty, $separator);
}

function csvKeyValue($key, $value, $includeEmpty=false, $separator=";")
{
	$line = csvValue($value, $includeEmpty, $separator);
	if(!$line && !$includeEmpty) return "";
	return "$key$separator$line\n";
}

function arrayToCsv($data, $includeEmpty=false, $separator=";", $prefix="")
{
	if(isScalarOrArray($data))
		return csvKeyValue($prefix, $data, $includeEmpty, $separator);

	if(is_object($data))
		$data=objToArray($data, true, true);
	
	$result="";
	foreach($data as $name => $value)
	{
		$key = $prefix ? "$prefix.$name" : $name;
debug($key, $value);
		$csv = arrayToCsv($data[$name], $includeEmpty, $separator, $key);
		$result .= $csv;
	}
	return $result;
}
?>