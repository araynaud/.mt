<?php
require_once("include/config.php");

startTimer();

$path=getPath();
$relPath=getDiskPath($path);
$file=getParam("file");
$target=getParam("target");
$size=getParam("size");
$debug=getParamBoolean("debug");
$format=getParam("format");

debugVar("target");
if($target!=="" && !$size)
{
	if(is_numeric($target))
		$target = getSubdirForTnIndex($target);
	$size = getSizeForTarget($target);
}

$image=makeVideoThumbnail($relPath, $file, $size, ".$target");
$imgType=getImageTypeFromExt($image);
if($debug)
{
	debug($image,$imgType);
	debug("REQUEST_URI", $_SERVER['REQUEST_URI']);
	if(file_exists($image))
		debug('Content-Length: ', filesize($image));
	echo "<img src='$image' alt='$image'/>";
	return;
}

//for AJAX: output image file Url when image ready
if($format=="ajax" && file_exists($image))
{
	$jsonResponse=array();
	$jsonResponse["file"]=$file;
	$jsonResponse["output"]=$image;
	$jsonResponse["time"]=getTimer();
	echo jsValue($jsonResponse);
	return;
}

if(!file_exists($image))
	$image="icons/media-play.png";
$imgType=getImageTypeFromExt($image);

setContentType("image", $imgType);
header('Content-Length: ' . filesize($image));

$fp = fopen($image, 'rb'); //stream the image directly from the generated file
fpassthru($fp);
fclose($fp);	
?>
