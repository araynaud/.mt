<?php

/*

image_collage.php?
size=
border =
margin = 
rows=img1,img2,img3;img6;img4,img5
columns=img1,img2,img3;img6;img4,img5

ratio = 
default: keep each image ratio compute rows/columns automatically
ratio = 1
force ratio; crop each image to fit in box (1=square)
ratio = 1;1.78;.75 force ratio for images in each row/column

use .ss images

rows: 
img1  img2  img3
----- img6 -----
-img4--  --img5-

columns:
img1    |   img4
img2  img6   |
img3    |   img5

*/

require_once("include/config.php");

function getImages($album, $files)
{
	if(!$files) return;

	$mediaFiles = $album->getFilesByType("IMAGE");
	$files = explode(";", $files);
	foreach ($files as $key => $value)
		$files[$key] = explode(",", $files[$key]);

	debug("files", $files);

	foreach ($files as $key => $row)
		foreach ($row as $key2 => $file)
		{
			if(!array_key_exists($file, $mediaFiles))
				continue;

			$mediaFiles[$file]->getRatio();
			$mf[$key][$key2] = $mediaFiles[$file];
		}

	debug("files", $mf, true);
	return $mf;
}

//compute rows or column dimensions 
//TODO: add margin, border size as parameter
function computeDimensions($mediaFiles, $iscolumn=false)
{
	$dimensions = array();
	foreach ($mediaFiles as $key => $row)
	{
		$dimensions[$key] = 0;
		$ratios = array();
		foreach ($row as $key2 => $mf)
			$ratios[] = $mf->getRatio();

		$dimensions[$key] = totalRatio($ratios, $iscolumn);
	}
	return $dimensions;
}

//compute total ratio
//$is column
function totalRatio($dimensions, $iscolumn)
{
	if(!$iscolumn)
		return array_sum($dimensions);

	$total = 0;
	foreach ($dimensions as $ratio)
		if($ratio)
			$total += 1/$ratio;

	if($total && $iscolumn) 
		return 1 / $total;
	return $total;
}


function copyImages($img, $mediaFiles, $dimensions, $iscolumn=false, $margin = 0)
{
	$x = $margin;
	$y = $margin;
	$maxSize = max(imageSY($img), imageSX($img));
	$totalWidth  = imageSX($img) - (1+count($dimensions)) * $margin;
	$totalHeight = imageSY($img) - (1+count($dimensions)) * $margin;

//debug("image size", "$totalWidth x $totalHeight");
	foreach ($mediaFiles as $key => $group)
	{
		if($iscolumn) // get column width
		{
			$totalHeight = imageSY($img) - (1+count($group)) * $margin;
			$y = $margin;
			$width = round($totalHeight * $dimensions[$key]);
			debug("column $key", $width);
		}
		else // get row height
		{
			$totalWidth = imageSX($img) - (1+count($group)) * $margin;
			$x = $margin;
			$height = round($totalWidth / $dimensions[$key]);
			debug("row $key", $height);
		}

	debug("copyImages ", "$margin / $x,$y");

		foreach ($group as $ind => $mf)
		{
			if($iscolumn)
				$height = intval($width / $mf->getRatio());
			else
				$width = intval($height * $mf->getRatio());

			$mfimg = $mf->loadImage($maxSize);
			copyResizedImage($img, $mfimg, $x, $y, $width, $height, false);
			//imagerectangle ($img , $x, $y, $x + $width - 1, $y + $height - 1, WHITE);
			//imagestring($img, 5, $x+5, $y+5, $mf->getTitle() , PINK);

			$mf->unloadImage();
			if($iscolumn)
				$y += $height + $margin;
			else
				$x += $width + $margin;
		}

		if($iscolumn)
			$x += $width + $margin;
		else
			$y += $height + $margin;
	}
}

session_start(); 
error_reporting(E_ERROR | E_PARSE | E_WARNING | E_NOTICE);

startTimer();

$path = reqPath();
$relPath = getDiskPath($path);
//input file
$size    = getParam("size", 1000);				//new size (max dimension)
$target  = getParam("target");
$margin  = getParam("margin",0);
$format  = getParam("format");
$angle   = getParam("angle");			//rotation angle
$filter  = getParam("filter");			//image filters
$text    = getParam("text");
$info    = getParamBoolean("info");		//display debug info
$iscolumn = getParamBoolean("columns");		//images as rows or as columns
$transpose = getParamBoolean("transpose");		//images as rows or as columns

if($iscolumn)
	$nb = getParam("columns");
else
	$nb = getParam("rows");

$saveFile = getParam("save");
$saveDir = getParam("to");
debugVar("target");

$files = getParam("files");
$tag = getParam("tag");

$album = new Album($path, true);
if($files)
	$mediaFiles = getImages($album, $files);
else if($tag)
{
	$tagFiles = $album->getFilesByTag($tag);
	if(!$nb)
		$nb = round(sqrt(count($tagFiles)));
debug("tagFiles " . count($tagFiles), $nb);
	$mediaFiles = arrayDivide($tagFiles, $nb, $transpose);
}

$bgcolor = getParam("bg", "WHITE");
debugVar($bgcolor);
$bgcolor = parseColor($bgcolor);
debugVar($bgcolor);

$dimensions = computeDimensions($mediaFiles, $iscolumn);
debug($iscolumn ? "column widths" : "row heights", $dimensions);
$ratioSum = totalRatio($dimensions, !$iscolumn);
debugVar("ratioSum");

$totalMargin = $margin * (1 + count($dimensions));
$size -= $totalMargin;
$totalWidth = $totalHeight = $size;
if($ratioSum > 1)
	$totalHeight = round($size / $ratioSum);
else
	$totalWidth = round($size * $ratioSum);

$totalHeight += $totalMargin;
$totalWidth  += $totalMargin;

debug("dimensions", "$totalWidth x $totalHeight");

//for each image, load it and copy it
$img = createImage($totalWidth, $totalHeight, $bgcolor);
debug("imageCreate", $img);

//copy images;
copyImages($img, $mediaFiles, $dimensions, $iscolumn, $margin);

debug();
if($text)
{
//	imageWriteText($img, $text);
	$box = imageWriteTextCentered($img, $text, 100, 2);
	debug("imagettfbbox", $box);
}

//output file
if($target!=="")
{
	if(is_numeric($target))		$target = getSubdirForTnIndex($target);
	if(!$size)		$size = getSizeForTarget($target);
	if(!$saveDir)	$saveDir = ".$target";
}

//create output folder if necessary
createDir($relPath, $saveDir);
$outputDir = combine($relPath, $saveDir);

preventCaching();


// ----------  IMAGE TRANSFORMATIONS ----
//apply filter if specified
if(is_callable("gd_$filter"))
	call_user_func("gd_$filter", $img);
		
// Write the string at the top left
if($info)
{
	$textcolor = PINK;
	$time=getTimer();
	imagestring($img, 5, 10, 2, "$totalWidth * $totalHeight done in $time", $textcolor);
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
setContentType("image", "jpeg");

debugVar("outputFile");
$format=getImageTypeFromExt($outputFile);
debugVar("format");

if($outputFile || !isDebugMode())
	outputImage($img, $outputFile, $format);

if($outputFile && !isDebugMode()) //to response only
	sendFileToResponse($outputFile,"","",false);
?>