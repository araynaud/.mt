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

function execCommand($cmd, $background=false, $toString=true, $redirectError=true)
{
	if($redirectError)
		$cmd .= " 2>&1";
	if($background && isWindows())
		$cmd="start \"proc title\" $cmd";
	else if($background && isUnix())
		$cmd .= " &";
	debugText("execCommand", $cmd);
	exec($cmd, $output, $cmdReturn);
	if($output && $toString)
		$output=implode("\n", $output);
	debugText("Output", $output, true);
	debug("Return", $cmdReturn);
	debug();
	return $output;
}

?>