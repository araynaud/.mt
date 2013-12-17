<?php
require_once("../include/config.php");

$path=getPath();
$relPath=getRelPath($path);
$file=getParam("file");
$tnDir=getParam("target",".tn");
$size=getParam("size", 960);
$debug=getParamBoolean("debug");
$format=getParam("format");
//if metadata / stream[0] is mp4 format 
//if (fileHasType($file,"mts"))
//	$outputFile=remuxVideo($relPath, $file, "mp4");
//else

startTimer();

$outputFile=convertVideo($relPath, $file, "mp4", $size);

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
	$jsonResponse=array();
	$jsonResponse["file"]=$file;
	if(file_exists($outputFile))
	{
		$jsonResponse["output"]=$outputFile;
		$jsonResponse["outputSize"]=filesize($outputFile);
	}
	$jsonResponse["time"]=getTimer();
	echo jsValue($jsonResponse);
	return;
}

if(!file_exists($outputFile))
	return;
	
//download video as attachment?

setContentType("video", "$imgType");
header('Content-Length: ' . filesize($outputFile));

$fp = fopen($outputFile, 'rb'); //stream the image directly from the generated file
fpassthru($fp);
fclose($fp);	
?>
