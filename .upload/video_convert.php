<?php
require_once("../include/config.php");

$path=getPath();
$relPath=getDiskPath($path);
$file=getParam("file");
$tnDir=getParam("target");
$size=getParam("size",0);
$debug=getParamBoolean("debug");
$format=getParam("format", "ajax");
$convertTo = getParam("to", "stream");
$mode = getParam("mode");
$start = getParam("start");
$end = getParam("end");
//$length = getParam("length");
//if(!$end)	$end=$start+$length;

//if metadata / stream[0] is mp4 format 
//if (fileHasType($file,"mts"))
//	$outputFile=remuxVideo($relPath, $file, "mp4");
//else

startTimer();
$outputFile="";
$progress="";
if($mode=="progress")
	$progress=convertVideoProgress($relPath, $file, $convertTo);
else
	$outputFile=convertVideo($relPath, $file, $convertTo, $size, $start, $end);

$imgType=getImageTypeFromExt($outputFile);

if($debug)
{
	debug($outputFile,$imgType);
	debug("REQUEST_URI", $_SERVER['REQUEST_URI']);
	if(file_exists($outputFile))
		debug('Content-Length: ', filesize($outputFile));
}

//for AJAX: output image file Url when image ready
if($format=="ajax")
{
	setContentType("text", "plain");
	$jsonResponse=array();
	$jsonResponse["file"]=$file;
	if(file_exists($outputFile))
	{
		$jsonResponse["output"]=$outputFile;
		$jsonResponse["outputSize"]=filesize($outputFile);
	}
	$jsonResponse["progress"] =	$progress;
	$jsonResponse["time"]=getTimer();
	echo jsValue($jsonResponse);
	return;
}

if(!file_exists($outputFile))
	return;
	
//download video as attachment?
setContentType("video", $imgType);
header('Content-Length: ' . filesize($outputFile));

$fp = fopen($outputFile, 'rb'); //stream the file directly from the generated file
fpassthru($fp);
fclose($fp);	
?>
