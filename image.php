<?php
require_once("include/config.php");
session_start(); 
error_reporting(E_ERROR | E_PARSE | E_WARNING | E_NOTICE);

startTimer();

$path=getPath();
$relPath=getDiskPath($path);

//input file
$url=getParam("url");
$file=getParam("file");
$inputFile=combine($relPath,$file);

//click coordinates
$x=getParam("x",0);
$y=getParam("y",0);
//selection coordinates
$x1=getParam("x1",0);
$y1=getParam("y1",0);
$x2=getParam("x2",0);
$y2=getParam("y2",0);

//edit/transform parameters
$tool=getParam("tool");
$edit=!empty($tool);
$undo=($tool=="undo");

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

$angle=getParam("rotate");			//rotation angle
$filter=getParam("filter");			//image filters
$info=getParamBoolean("info");		//display debug info
$tolerance=getParam("tolerance", DEFAULT_TOLERANCE);	//color tolerance for fill/replace

//JPG lossless transform before other operations. if rotate multiple of 90 or flip
$transform = getParam("transform");

//create output folder if necessary
createDir($relPath, $saveDir);
$outputDir = combine($relPath, $saveDir);

if($format=="thumbnail")
{
	$tnImage = getExifThumbnail($inputFile);
	if($saveDir)
	{
		createDir($relPath, $saveDir);
		$outputFile = combine($outputDir, $file);
		writeBinaryFile($outputFile, $tnImage);
	}
	if($tnImage)
		header("Content-Type: image/jpeg");
	echo $tnImage;
	return;
}

preventCaching();

//make configurable: if irfan view is enabled.
if($target && $size)
{
	$status = jpegResize($relPath, $file, $saveDir, $size);
	if($status!==false) 
	{
		$imgType=getImageTypeFromExt($file);
		if($format == "ajax")
		{
			$jsonResponse=array();
			$jsonResponse["file"]=$file;
			$outputFile = combine($outputDir, $file);
			$jsonResponse["output"] = diskPathToUrl($outputFile);
			$jsonResponse["filesize"] = filesize($outputFile);
			$jsonResponse["time"]=getTimer();
			echo jsValue($jsonResponse);
			return;
		}
		if(!isDebugMode())
			sendFileToResponse($outputDir, $file, "image/$imgType");
		return;
	}
}

if($transform) //only for JPEG, angle multiple of 90
{
	jpegLosslessRotate($relPath, $file, $transform);
	$tnPath=findThumbnail($relPath, $file, ".tn");
	if($tnPath)		deleteFile($tnPath);

	$tnPath=findThumbnail($relPath, $file, ".ss");
	if($tnPath)		deleteFile($tnPath);
	resetMedadata($relPath, $file);

	if($format == "ajax")
	{
		$jsonResponse=array();
		$jsonResponse["file"]=$file;
		$jsonResponse["output"]=$inputFile;
		$jsonResponse["time"]=getTimer();
		echo jsValue($jsonResponse);
		return;
	}
	if(!isDebugMode())
	{
		$imgType=getImageTypeFromExt($file);
		sendFileToResponse($outputDir, $file, "image/$imgType");
//		setContentType("image",$imgType);
//		$fp = fopen(combine($relPath, $file), 'rb'); //stream the image directly from the generated file
//		fpassthru($fp);
//		fclose($fp);	
	}
	return;
}

$imageInfo = getImageInfo($inputFile);
debug("getImageInfo 1", getTimer());
debugVar("imageInfo");
$img=false;
if($edit)
	$img=loadTempImage($undo);
if(!$img && $url)
{
	$img=loadImage($url,$imageInfo);
	$file=basename($url);
}
if(!$img)
	$img=loadImageForResize($relPath, $file, $size, $imageInfo);

if(!$img)
	$img = exif_thumbnail($inputFile);
else
{
	debug("loaded img", getTimer());
	getImageInfo($inputFile, $img, $imageInfo);
	debug("getImageInfo 2", getTimer());
	debugVar("imageInfo");

	// ----------  IMAGE TRANSFORMATIONS ----
	$pixFilled=0;
	//Crop
	if($tool=="crop")
	{
		$img = cropImage($img, $imageInfo, $x1,$y1,$x2,$y2);
		debug("crop", getTimer());
	}

	//Resize
	if(($tool=="resize" || empty($tool)) && $size)
	{
		$img = resizeImage($img, $imageInfo, $size, $size);
		debug("resize", getTimer());
	}

	// Rotate
	if(($tool=="rotate" || empty($tool)) && $angle)
	{
		$img = rotateImage($img, $imageInfo, $angle);
		debug("rotate", getTimer());
	}

	// Make the background transparent
	// USE $imageInfo["transparent"] -1 for GIF or PNG with single transparent color
	// or any color with alpha = 127 for alpha PNG
	if($tool=="clear")
		$pixFilled=clearBackground($img, $imageInfo, $x, $y, $tolerance, TRANSPARENT);

	if($tool=="replace")
		$pixFilled=replacePixelColor($img, $imageInfo, $x, $y, $tolerance, TRANSPARENT);

	//apply filter if specified
	if(is_callable("gd_$filter"))
		call_user_func("gd_$filter",$img);

	makeImageTransparent($img, @$imageInfo["transparent"], @$imageInfo["alpha"]);

	if($edit)
		saveTempImage($img);
			
	// Write the string at the top left
	if($info)
	{
		$textcolor = PINK;
		$time=getTimer();
		imagestring($img, 5, 10, 2, "X:$x Y:$y Done in $time " . $imageInfo["format"], $textcolor);
		if($imageInfo["animated"]) 
			imagestring($img, 5, 10, 14, "An:" . countAnimatedGifFrames($inputFile), PINK);
		imagestring($img, 5, 10, 26, "T:" . $imageInfo["transparent"] . " A:" . $imageInfo["alpha"], PINK);
		imagestring($img, 5, 10, 38, $imageInfo["width"] . " * " . $imageInfo["height"] . " = " . $imageInfo["width"] * $imageInfo["height"], $textcolor);
		imagestring($img, 5, 10, 50, "Selection: $x1,$y1 to $x2,$y2", $textcolor);
	}
}

//if target dir specified, write file or output directly to response
$outputFile = NULL;
if($saveFile && $tool=="save")
	$outputFile = combine($relPath, $saveDir, $saveFile);
else if($saveDir)
	$outputFile = combine($relPath, $saveDir, $file);

//for AJAX: output image file Url when image ready
if($format=="ajax" && $outputFile)
{
	debugVar("url");
	debugVar("img");
	debug("img", gettype($img));
	outputImage($img, $outputFile, $imageInfo["format"]);

	$jsonResponse=array();
	$jsonResponse["file"]=$file;
	$jsonResponse["info"]=$imageInfo;
	$jsonResponse["output"] = diskPathToUrl($outputFile);
	$jsonResponse["filesize"] = filesize($outputFile);
	$jsonResponse["time"]=getTimer();
	echo jsValue($jsonResponse);
	return;
}

//othwerwise, output image to response and to file 
setContentType("image", $imageInfo["format"]);

debugVar("outputFile");
outputImage($img, $outputFile, $imageInfo["format"]);
if($outputFile===NULL) //to response only
	return;

header('Content-Length: ' . filesize($outputFile));
$fp = fopen($outputFile, 'rb'); //stream the image directly from the generated file
fpassthru($fp);
fclose($fp);	
?>