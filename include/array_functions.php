<?php

// ARRAY functions

function splitLines($str)
{
	if(is_array($str))	return $str;
	return preg_split("/\\n|\\r\\n/", $str);
}

function toArray($str)
{
	if(is_array($str))	return $str;
	if(empty($str))		return array();
	return preg_split("/[,; \|]/", $str);
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

function flattenArray($data)
{
	$result=array();
	if(!$data) return $result;
	
	if(is_array($data))
		foreach($data as $key=>$value)
			$result = array_merge($result, flattenArray($value));
	else
		$result[] = $data;
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

function arrayToMap($arr)
{
	if(!$arr) 	return array();
	if(!is_array($arr))
		$arr=toArray($arr);
	return array_combine($arr, $arr);
}

//arrayGet from nested array
function arrayGet($array, $keys, $default=null)
{
	if(empty($keys))
		return $array;
	if(!is_array($keys))
		$keys=explode(".", $keys);
	//if more keys, look into sub array
	$key=array_shift($keys);
	if(!isset($array[$key])) 
		return $default;
	if(is_array($array[$key]))
		return arrayGet($array[$key], $keys);
	return $array[$key];
}

//create array values within CSV data
// TYPES.VIDEO;flv;mp4	=> $csv["TYPES"]["VIDEO"] = ["flv", "mp4"]
// keep previous key: .STREAM;flv;mp4	=> $csv["TYPES"]["STREAM"] = ["flv", "mp4"]
function setNestedArrayValue(&$csvRows, $key, $value)
{
	if(!is_array($key))
	{
		$csvRows[$key]=$value;
		return $value;
	}
	
	//test if each level of key exists
	//create array(s) if necessary
	$var=&$csvRows;
	$c=count($key)-1;
	for($i=0;$i<$c;$i++)
	{
		$name=$key[$i];
		//or create nested array nodes
		if(!isset($var[$name]))
			$var[$name]=array();
		$var=&$var[$name]; //change reference
	}
	//last node: assign value
	$name=$key[$c];
	$var[$name] = $value;

	return $var[$name];
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

//get or copy multiple properties from array
function arrayCopyMultiple($from, $keys, $to=array())
{
	if(!$from || !$keys) return $to;

	if(is_string($keys))
		$keys = explode(",", $keys);

	foreach ($keys as $key)
		if(array_key_exists($key, $from))
			$to[$key] =	$from[$key];

	return $to;
}

//filter array by keys, not values.
function arrayFilterKeys($a, $funct)
{
	$f = array_filter(array_keys($a), $funct); 
	$b = array_intersect_key($a, array_flip($f));
	return $b;
}


//array intersect by keys
function arrayIntersection()
{
	$args = func_get_args();
	//if only 1 arg, use arrays in this arg 
	if(count($args) == 1)
		$args = reset($args);

	$result = array_shift($args);
	foreach($args as $ar)
		$result = array_intersect_key($result, $ar);

	return $result;
}

//array union by keys
//array_replace not available in PHP < 5.3.0
function arrayUnion()
{
	$args = func_get_args();
	//if only 1 arg, use arrays in this arg 
	if(count($args) == 1)
		$args = reset($args);

	$result = array();
	foreach($args as $ar)
		foreach ($ar as $key => $value)
			$result[$key] = $value;

	return $result;
}

//divide array into N slices
//take every Nth element
function arrayDivide($array, $nb = 1, $transpose = false)
{
	//divide in 1 or more than length = same array
	$nb=round($nb);
	if($nb <=1) //|| nb > this.length)
		return array($array);
	$len = count($array);
	$nb = min($nb, $len);
//	if($nb > count($array))	$nb = count($array);

	$result = array();
	$i=0;
	if($transpose)
	{
		$remainingElements=$array;
		for($i=0; $i < $nb; $i++)
		{
			$perCol = round(count($remainingElements) / ($nb - $i));
			$result[$i] = array_slice($remainingElements, 0, $perCol);
			$remainingElements = array_slice($remainingElements, $perCol);
		}
	}
	else
	{
		for($i = 0; $i < $nb; $i++)
			$result[] = array();
		for($i = 0; $i< $len; $i++)
			$result[$i % $nb][] = $array[$i];
	}
	return $result;
};

function arrayDistinct($data, $field)
{
	$distinct=array();
	foreach ($data as $el)
	{
		if(!isset($el[$field])) continue;
		$val = $el[$field];
		if(!isset($distinct[$val]))
			$distinct[$val] = $val;
	}
	return $distinct;
}

function arrayCountBy($data, $field)
{
	$distinct=array();
	foreach ($data as $el)
	{
		if(!isset($el[$field])) continue;
		$val = @$el[$field];
		if(!isset($distinct[$val]))
			$distinct[$val] = 1;
		else 
			$distinct[$val]++;
	}
	return $distinct;
}

function arrayGroupBy($data, $field)
{
	$distinct=array();
	foreach ($data as $el)
	{
		if(!isset($el[$field])) continue;
		$val = @$el[$field];
		if(!isset($distinct[$val]))
			$distinct[$val] = array();
		$distinct[$val][] = $el;
	}
	return $distinct;
}

?>