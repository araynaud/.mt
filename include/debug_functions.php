<?php
//Debugging functions
function isDebugMode()
{
	global $config;
	return $config["DEBUG"]["OUTPUT"] || contains(currentScriptName(),"test") || getParam("debug")==="true";
}

function startTimer()
{
	global $startTime;
	$startTime = microtime(true);
	return $startTime;
}

function getTimer()
{
	global $startTime, $endTime;
	$endTime = microtime(true);
	return $endTime  - $startTime;
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
	else if(!isset($value) || $value===null)
		echo "$text:\n";
	else if (is_scalar($value))
		echo "$text: $value\n";
	else
		echo "$text: " . jsValue($value, $indent) . "\n";
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