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
function computeRatios($mediaFiles, $iscolumn=false)
{
	$ratios = array();
	foreach ($mediaFiles as $key => $row)
	{
		$mfratios = array();
		foreach ($row as $key2 => $mf)
			$mfratios[] = $mf->getRatio();

		$ratios[$key] = totalRatio($mfratios, $iscolumn);
	}
	return $ratios;
}

function computeImageSize($mediaFiles, $size, $margin, $iscolumn=false)
{
	$ratios = computeRatios($mediaFiles, $iscolumn);
	$ratioSum = totalRatio($ratios, !$iscolumn);
	debug("computeImageSize overall ratio:", $ratioSum);
	debug("computeImageSize ratios", $ratios);

	//total number of spaces
	$fixedSize = array();
	$variableSize = array();
	$dim = array();

	foreach ($mediaFiles as $key => $group)
	{
		$fixedSize[$key] = $margin * (1 + count($group));
		$variableSize[$key] = $size - $fixedSize[$key];
		$dim[$key] = $iscolumn ? round($variableSize[$key] * $ratios[$key])
			: round($variableSize[$key] / $ratios[$key]);
	}

	debug("fixed", $fixedSize);
	debug("variable", $variableSize);
	debug($iscolumn ? "column widths" : "row heights", $dim);
//total Height
	$computedSize = array_sum($dim);
	$computedSize += $margin * (1 + count($ratios));

	$dimensions = $iscolumn ? array($computedSize, $size) : array($size, $computedSize);
	debug("computeImageSize: dimensions", $dimensions);
	return $dimensions;
}

//compute total ratio
//$is column
function totalRatio($ratios, $iscolumn)
{
	if(!$iscolumn)
		return array_sum($ratios);

	$total = 0;
	foreach ($ratios as $ratio)
		if($ratio)
			$total += 1/$ratio;

	if($total && $iscolumn) 
		return 1 / $total;
	return $total;
}

//todo use ratio + count for fixed margins
//last col/row: fill rest of image
function copyImages($img, $mediaFiles, $dimensions, $iscolumn=false, $margin = 0, $caption = false)
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
				$height = round($width / $mf->getRatio());
			else
				$width = round($height * $mf->getRatio());

			$mfimg = $mf->loadImage($maxSize);
			if(!$mfimg) continue;
			copyResizedImage($img, $mfimg, $x, $y, $width, $height, false);
			$mf->unloadImage();
			if($caption)
				imageWriteText($img, $mf->getDescription(), 20, $x+2, $y+5, 2);

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
$margin  = getParam("margin", 0);
$format  = getParam("format");
$angle   = getParam("angle");			//rotation angle
$filter  = getParam("filter");			//image filters
$text    = getParam("text");
$textTop    = getParam("top");
$textBottom  = getParam("bottom");
$page   = getParam("page");
$nb      = getParam("groups");
$maxfiles = getParam("maxfiles", 0);
$info    = getParamBoolean("info");		//display debug info
$caption = getParamBoolean("caption");
$sort = getParam("sort");
$nb = getParam("columns", 0);
$iscolumn = getParamBoolean("columns");
if(!$nb)
	$nb = getParam("rows", 0);
else if($nb=="true")
	$nb = 0;
$transpose = getParamBoolean("transpose");		//images as rows or as columns

$saveFile = getParam("save");
$saveDir = getParam("to");
debugVar("target");

$files = getParam("files");
$tag = getParam("tag");

$album = new Album($path, true);
if($files)
	$mediaFiles = getImages($album, $files);
else
{
	if($tag)
		$tagFiles = $album->getFilesByTag($tag);
	else
		$tagFiles = $album->getMediaFiles("IMAGE|VIDEO");

	$tagFiles = array_values($tagFiles);
	if($sort=="random")
		shuffle($tagFiles);

	if($maxfiles > 0 && $maxfiles < count($tagFiles))
	{
		$start = $page ? ($page-1)*$maxfiles : 0;
		$tagFiles = array_slice($tagFiles, $start, $maxfiles);
	}
	if(!$nb)
		$nb = round(sqrt(count($tagFiles)));
debug("tagFiles " . count($tagFiles), $nb);
debug("tagFiles", $tagFiles, true);
	$mediaFiles = arrayDivide($tagFiles, $nb, $transpose);
}

debug();
$bgcolor = getParam("bg", "WHITE");
debugVar("bgcolor");
$bgcolor = parseColor($bgcolor);
debugVar("bgcolor");

$ratios = computeRatios($mediaFiles, $iscolumn);
debug($iscolumn ? "column widths" : "row heights", $ratios);
$ratioSum = totalRatio($ratios, !$iscolumn);
debugVar("ratioSum");

debugVar("size");
$dim = computeImageSize($mediaFiles, $size, $margin, $iscolumn);
debugVar("dim");
$img = createImage($dim[0], $dim[1], $bgcolor);

/*$totalMargin = $margin * (1 + count($ratios));
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
debug("createImage", $img);
*/

//copy images;
copyImages($img, $mediaFiles, $ratios, $iscolumn, $margin, $caption);

debug();
if($text)
	$box = imageWriteTextCentered($img, $text, 100, 2, $margin);
if($textTop)
	$box = imageWriteTextCentered($img, $textTop, 100, 2, $margin, "top");
if($textBottom)
	$box = imageWriteTextCentered($img, $textBottom, 100, 2, $margin, "bottom");

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
{
	$saveFile = getFilename($saveFile, "jpg");
	$outputFile = combine($relPath, $saveDir, $saveFile);
}
//else if($saveDir)
//	$outputFile = combine($relPath, $saveDir, $file);
//if($outputFile)


//for AJAX: output image file Url when image ready
if($outputFile && ($format=="ajax" || $format=="json"))
{
	setContentType("text", "plain");
	debugVar("url");
	debugVar("img");
	debug("img", gettype($img));
	outputImage($img, $outputFile);

	$jsonResponse=array();
	$jsonResponse["file"]=$file;
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
	outputImage($img, $outputFile);

if($outputFile && !isDebugMode()) //to response only
	sendFileToResponse($outputFile,"","",false);
?>