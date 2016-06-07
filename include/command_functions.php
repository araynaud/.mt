<?php
//functions to call external programs via command line
function quoteFilename($filename)
{
	if(contains($filename," "))
		$filename = escapeshellarg($filename);
	if(isWindows())
		$filename = str_replace("/", "\\", $filename);

	//TODO if windows, replace / by \\ in filenames
	return $filename;
}

//pass cmd and its args
//for each argument: quote if necessary
function makeCommand()
{
	$args = func_get_args();
	$cmd = array_shift($args);
	foreach($args as $n => $param)
	{
		$cmd = str_replace("[$n]", quoteFilename($param), $cmd);
	}
	//TODO: quote filenames only if [f$n]
	//replace unused [$n] arguments with empty
	return $cmd;
}

function execCommand($cmd, $background=false, $toString=true, $redirectError=false)
{
	if(!$background && $redirectError)
		$cmd .= " 2>&1";
	if($background && isWindows())
		$cmd="start \"proc_title\" $cmd";
	else if($background && isUnix())
		$cmd .= " &";
	debugText("execCommand", $cmd);

	exec($cmd, $output, $cmdReturn);

	if($toString)
		$output = implode("\n", $output);
	debugText("Output", $output, true);
	debug("Return", $cmdReturn);
	debug();
	return $output;
}

function execBackground($cmd)
{
	if(isWindows())
		$cmd="start \"proc_title\" $cmd"; 
	else if(isUnix())
		$cmd .= " &";
	debugText("execBackground", $cmd);

	$pid = popen($cmd, "r");
	return $pid;
}

function execPhp($script, $args, $background=false, $toString=true, $redirectError=false)
{
	$script = getFilename($script, "php");
	$cmd = "php -f $script";

	if($args)
		foreach ($args as $key => $value)
			$cmd .= " $key=" . quoteFilename($value);

	if($background)
		return execBackground($cmd);
	else
		return execCommand($cmd, $background, $toString, $redirectError);
}

function getNamedArgs(&$args=array())
{
	global $argv;
	if(!isset($argv)) return $args;
	
	foreach ($argv as $key => $value) 
	{
		if($key == 0) continue;

		$arg = splitBeforeAfter($value, "=");
		if(count($arg) == 2)
			$args[$arg[0]] = $arg[1];
	}
	return $args;
}
?>