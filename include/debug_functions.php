<?php
//Debugging functions

function addFunctionCall($name)
{
	global $functionStats;
	if(!$functionStats) 
		$functionStats=array();
	if(!isset($functionStats[$name]))
		$functionStats[$name]=1;
	else
		$functionStats[$name]++;
}

function getFunctionStats($name="")
{
	global $functionStats;
	return $name ? @$functionStats[$name] : $functionStats;
}

function isDebugMode()
{
	$isTest = is_callable("currentScriptName") && contains(currentScriptName(),"test");
	return $isTest || getConfig("debug.output")  || parseBoolean(reqParam("debug"));
}

function setDebugMode($mode=true)
{
	$_REQUEST["debug"] = $mode;
}

function startTimer()
{
	global $startTime;
	$startTime = microtime(true);
	return $startTime;
}

function getTimer($ms=false)
{
	global $startTime, $endTime;
	$endTime = microtime(true);
	$time = $endTime - $startTime;
	$result = $ms ? formatMs($time) : $time;  
debug("Timer", $result);
	return $result;
}

function getTimerSinceLast($ms=false)
{
	global $endTime;
	$time = microtime(true);
	$result = $time - $endTime;
	$endTime = $time;
	$result = $ms ? formatMs($result) : $result;  
debug("Timer Since Last", $result);
	return $result;
}

function formatMs($time, $digits=3)
{
	return round($time * 1000, $digits) . "ms";
}

function testFunctionResult()
{
	$args = func_get_args();
	$funct = array_shift($args);
	if(!isDebugMode())
		return call_user_func_array($funct, $args);

	$args2 = func_get_args();
	$funct = array_shift($args2);
	$time = getTimer();
	foreach ($args2 as $key => $arg)
		if(is_array($arg))
			$args2[$key] = shortenArray($arg);

	debug("Test $funct args", $args2);
	$result = call_user_func_array($funct, $args);
	$time = formatMs(getTimer() - $time);
	$nb = $result;
	if(is_array($nb))
		$nb = shortenArray($result);	
	debug("Test $funct result", $nb, true);
	debug("Test $funct time", $time);
	debug();
	return $result;
}

function shortenArray($arr, $maxlength=6)
{
	$count = count($arr);
	if(!is_array($arr) || $count <= $maxlength)
		return $arr;

	$arr = array_slice($arr, 0, $maxlength);
	$arr[] = "... Array($count) ...";
	return $arr;
}

function debugVar($name, $indent=0)
{
	debug($name, @$GLOBALS[$name], $indent);
}

function debug($text="", $value=null, $indent=0)
{
	if(!isDebugMode()) return;

	if(!$value && !$text)
		echo "\n";
	else if($indent==="print_r")
	{
		echo "$text: ";
		print_r($value);
	}
	else if(!isset($value) || $value===null)
		echo "$text: null\n";
	else if (is_bool($value))
		echo "$text: " . BtoS($value) . "\n";
	else if (is_scalar($value))
		echo "$text: $value\n";
	else
		echo "$text: " . jsValue($value, $indent, true) . "\n";
}

function debugText($text="", $value=null)
{
	if(!isDebugMode()) return;
	if(is_array($value))
		$value=implode("\n", $value);

	if(!$value && !$text)
		echo "\n";
	else if(!isset($value) || $value===null)
		echo "$text\n";
	else if(contains($value,"\n"))
		echo "$text:\n$value\n\n";
	else
		echo "$text: $value\n";
}


function debugStack($levels=1)
{
	if(!isDebugMode()) return;
	$stack=debug_backtrace();
	if($levels!=1)	debug("\nSTACK");
	
	for($i=1; $i<count($stack); $i++)
	{
		$file="";
		if(isset($stack[$i]["file"])) $file= basename($stack[$i]["file"], ".php");
//		if(isset($stack[$i]["line"])) $line= $stack[$i]["line"];
		$funk=combine($file, $stack[$i-1]["line"], $stack[$i]["function"]);
		debug($funk, $stack[$i]["args"]);
		if($i==$levels) break;
	}
}
?>