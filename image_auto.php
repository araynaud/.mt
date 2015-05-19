<?php

require_once("include/config.php");
session_start(); 
error_reporting(E_ERROR | E_PARSE | E_WARNING | E_NOTICE);

$mf = MediaFile::getMediaFile();
debugVar("mf", true);
if(!$mf) return;

$mfInfo = $mf->getImageInfo();
debugVar("mfInfo", true);

$mfiles = $mf->getFilenames(true,false,false);
$file = reset($mfiles);
debugVar("file");

$mfiles = $mf->getFilePaths();
debugVar("mfiles", true);

$relPath = $mf->getRelPath();
//input file
$url=getParam("url");
$inputFile=combine($relPath, $file);

//click coordinates
$x=getParam("x1",0);
$y=getParam("y1",0);
//selection coordinates
$x2=getParam("x2",0);
$y2=getParam("y2",0);

//edit/transform parameters
//output file
$size=getParam("size");				//new size (max dimension)
$target = getParam("target");
$format=getParam("format");

$saveFile = getParam("save");
$saveDir = getParam("to");
debugVar("target");
if($target!=="")
{
	if(is_numeric($target))		$target = getSubdirForTnIndex($target);
	if(!$size)		$size = getSizeForTarget($target);
	if(!$saveDir)	$saveDir = ".$target";
}

$angle=getParam("angle");			//rotation angle
$filter=getParam("filter");			//image filters
$info=getParamBoolean("info");		//display debug info
$tolerance=getParam("tolerance", DEFAULT_TOLERANCE);	//color tolerance for fill/replace

//create output folder if necessary
createDir($relPath, $saveDir);
$outputDir = combine($relPath, $saveDir);

preventCaching();

$imageInfo = getImageInfo($inputFile);
debug("getImageInfo 1", getTimer());
debugVar("imageInfo");

$img=loadImageForResize($relPath, $file, $size, $imageInfo);

debug("loaded img", getTimer());
getImageInfo($inputFile, $img, $imageInfo);
debug("getImageInfo 2", getTimer());
debugVar("imageInfo");

// ----------  IMAGE TRANSFORMATIONS ----
$pixFilled=0;
//Crop
if($x || $x2 || $y || $y2 )
{
	$img = cropImage($img, $imageInfo, $x, $y, $x2, $y2);
	debug("crop", getTimer());
}

//Resize
if($size)
{
	$img = resizeImage($img, $imageInfo, $size, $size);
	debug("resize", getTimer());
}

// Rotate
if($angle)
{
	$img = rotateImage($img, $imageInfo, $angle);
	debug("rotate", getTimer());
}

//apply filter if specified
if(is_callable("gd_$filter"))
	call_user_func("gd_$filter", $img);
		
// Write the string at the top left
if($info)
{
	$textcolor = PINK;
	$time=getTimer();
	imagestring($img, 5, 10, 2, "X:$x Y:$y Done in $time " . $imageInfo["format"], $textcolor);
	if(@$imageInfo["animated"]) 
		imagestring($img, 5, 10, 14, "An:" . countAnimatedGifFrames($inputFile), PINK);
	imagestring($img, 5, 10, 26, "T:" . @$imageInfo["transparent"] . " A:" . @$imageInfo["alpha"], PINK);
	imagestring($img, 5, 10, 38, $imageInfo["width"] . " * " . $imageInfo["height"] . " = " . $imageInfo["width"] * $imageInfo["height"], $textcolor);
	imagestring($img, 5, 10, 50, "Selection: $x,$y to $x2,$y2", $textcolor);
}

//if target dir specified, write file or output directly to response
$outputFile = NULL;
if($saveFile)
	$outputFile = combine($relPath, $saveDir, $saveFile);
else if($saveDir)
	$outputFile = combine($relPath, $saveDir, $file);

//for AJAX: output image file Url when image ready
if($outputFile && ($format=="ajax" || $format=="json"))
{
	setContentType("text", "plain");
	debugVar("url");
	debugVar("img");
	debug("img", gettype($img));
	outputImage($img, $outputFile, $imageInfo["format"]);

	$jsonResponse=array();
	$jsonResponse["file"]=$file;
	$jsonResponse["info"]=$imageInfo;
	$jsonResponse["output"] = diskPathToUrl($outputFile);
	$jsonResponse["filesize"] = filesize($outputFile);
	$jsonResponse["mediafile"]=$mf;
	$jsonResponse["time"]=getTimer();
	echo jsValue($jsonResponse, true);
	return;
}

//othwerwise, output image to response and to file 
setContentType("image", $imageInfo["format"]);

debugVar("outputFile");
$format=getImageTypeFromExt($outputFile);
debugVar("format");

if($outputFile || !isDebugMode())
	outputImage($img, $outputFile, $format);

if($outputFile && !isDebugMode()) //to response only
	sendFileToResponse($outputFile,"","",false);
?>