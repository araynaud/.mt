<?php
require_once("include/config.php");

$path=getPath();
$relPath=getRelPath($path);
$file=getParam("file");
$contentType=getParam("type");
$download=getParamBoolean("download",empty($contentType));

if(!file_exists("$relPath/$file"))
{
	header("HTTP/1.0 404 Not Found");
	return;
}

if(fileHasType($file,"IMAGE"))
{
	$src_info = getimagesize("$relPath/$file");
	$contentType=$src_info["mime"];
}
else if(fileHasType($file,"VIDEO"))
{
	$ext=getFilenameExtension($file);
	$contentType = "video/$ext";
}

sendFileToResponse($relPath,$file,$contentType,$download);
?>