<?php

class RgbColor
{
    const R = 0;
    const G = 1;
    const B = 2;
    const A = 3;
	private $_color;
	public function __construct($value)
	{
		$color=$value;
	}
	
	public function value()
	{
		return $color;
	}
	
	public function get($comp)
	{
		return getColorComp($this->color,$comp);	
	}
	
	public function diff($rgb)
	{
		$rgb=is_object($rgb) ? $rgb->$color : $rgb;
		return colorDiff($this->color,$rgb2);
	}
}

//First two are alpha, then triplet
define("TRANSPARENT", 0X0040FFFF); 
define("DEFAULT_TOLERANCE", 10); 
define("PINK", 0x00E020B0); 
define("RED", 0x00FF0000); 
define("GREEN", 0x0000FF00); 
define("BLUE", 0x000000FF); 
define("CYAN", (BLUE + GREEN) / 2); 
define("WHITE", 0x00FFFFFF); 
define("YELLOW", 0x00FFFF00); 
define("GREY", 0x003E3E3E); 
define("TRANSPARENT_TYPES", "gif,png"); 
$TRANSPARENT_TYPES=explode(",", TRANSPARENT_TYPES);
//image functions using GD
function createThumbnails($dir,$subfolder,$size,$force=false)
{
	$thumbsDir=combine($dir,$subfolder);
	$search=array("type"=>"IMAGE");
	$pics=listFiles($dir, $search);
	if(count($pics) == 0)
		return false;

	//create thumbs folder if necessary
	if (!file_exists($thumbsDir))
		mkdir ($thumbsDir, 0700);

	//create missing image thumbs if necessary
	foreach ($pics as $p)
	{	
		if($force || !findThumbnail($dir, $p, $subfolder) )
		{
			echo "$p ";
			createResizedImage($dir, $p, combine($thumbsDir,$p), $size,$size);
		}
	}
}

function findThumbnails($dir, $file, $appendPath=true)
{
	$sizes = getConfig("thumbnails.sizes");
	if(!$sizes) return false;
	$thumbnails = array();
	foreach($sizes as $tnDir => $size)
		$thumbnails[$tnDir] = findThumbnail($dir, $file, ".$tnDir", $appendPath);

	return $thumbnails;
}

function findThumbnail($dir, $file, $tnDir, $appendPath=true)
{
//for image, get .tn/image
	$thumb=combine($dir,$tnDir,$file);
	if(file_exists($thumb))
		return $appendPath ? $thumb : $file;
//for video, get .tn/name.jpg
	$file=getFilename($file,"jpg");
	$thumb=combine($dir,$tnDir,$file);
	if(file_exists($thumb))	
		return $appendPath ? $thumb : $file;
	return false;
}

function createThumbnail($relPath, $file, $tndir, $size)
{
	$tnPath=combine($relPath, $tndir, $file);
	return createResizedImage($relPath, $file, $tnPath, $size, $size);
}

/*
	Create Resized Image($name,$filename,$dstW,$dstH)
	creates a resized image
	variables:
	$name		Original filename
	$filename	Filename of the resized image
	$dstW		width of resized image
	$dstH		height of resized image
*/	

function createResizedImage($dir, $srcFilename, $dstFilename, $dstW, $dstH)
{
	$size=max($dstW, $dstH);
	$imageInfo=null;
	$img = loadImageForResize($dir, $srcFilename, $size, $imageInfo);
	$img = resizeImage($img, $imageInfo, $dstW,$dstH, true);
	if(!contains($dstFilename,"/"))
		$dstFilename=combine($dir,$dstFilename);
	outputImage($img,$dstFilename,NULL);
	return $dstFilename;
}

// use original file or largest thumbnail smaller than maxsize ?
function getSubdirForSize($imageInfo, $maxSize)
{
	$tn = getTnIndexForSize($imageInfo, $maxSize);
	return getSubdirForTnIndex($tn);
}

function getSizeForTarget($target)
{
	if(is_numeric($target))
		$target = getSubdirForTnIndex($target);
	return getConfig("thumbnails.sizes.$target");
}

function getTnIndexForSize($imageInfo, $maxSize)
{
	$sizes = getConfig("thumbnails.sizes");
	//use original file
	if(!$sizes || !$maxSize || ($maxSize > $imageInfo["width"] && $maxSize > $imageInfo["height"])) return false;

	$tnIndex = 0;
	foreach($sizes as $dir => $size)
	{
		debug("getTnIndexForSize $tnIndex $dir", $size);
		if($size >= $maxSize) return $tnIndex;
		$tnIndex++;
	}
	return false;
}

function getSubdirForTnIndex($tn)
{
	if($tn===false) return false;
	$dirs=getConfig("thumbnails.dirs");
	if(array_key_exists($tn, $dirs))
		return $dirs[$tn];
	return false;
}

function setBackgroundImage($relPath,$file,$size=0)
{
	if(!$size) 
		$size=getConfig("background.size");
	createResizedImage($relPath, $file, ".bg.jpg", $size, $size);
}

//Load image using GD function depending on type
function loadImage($file, &$imageInfo = array())
{  
    if(!$imageInfo)  $imageInfo = getImageInfo($file);
 	debug("getimagesize $file", getimagesize($file));
    if(!$imageInfo)	return false;

	$imgType = is_array($imageInfo) ? @$imageInfo["format"] : $imageInfo;
	if(!$imgType) $imgType = getImageTypeFromExt($file);

 	$funct = "imagecreatefrom" . $imgType;
 debug("loadImage", $funct);
	if(!$funct || !function_exists($funct))	return false;
	return $funct($file);
}

//load original image or .ss depending on desired size
//if info["size"] > $size, load smaller image
//TODO: extend to more sizes. read sizes from config
function loadImageForResize($dir, $file, $size, &$imageInfo=array())
{
//resize by using .ss image for source if it exists and if target size smaller
	$inputFile=false;
	if($size<=1000)
		$inputFile=findThumbnail($dir, $file, ".ss");
//otherwise, load original image
	if(!$inputFile)
		$inputFile=combine($dir,$file);	
	return loadImage($inputFile, $imageInfo);
}

//Save image to file or output to response if dstFilename empty
function outputImage($img,$dstFilename=null,$imgType="", $destroy=true)
{
debug("outputImage $dstFilename", $img);
	if(empty($dstFilename))
		$dstFilename=null;
	if(empty($imgType))
		$imgType=strtolower(getFilenameExtension($dstFilename));

	if(is_string($img) && $outputFile)
	 	writeBinaryFile($outputFile, $img);
	else if(is_string($img))
		echo $img;
	else if ($imgType=="gd2")
		imagegd2($img, $dstFilename);
	else if ($imgType=="png")
		imagepng($img, $dstFilename, 9, PNG_ALL_FILTERS); 
	else if ($imgType=="gif")
		imagegif($img, $dstFilename); 
	else
		imagejpeg($img, $dstFilename); 

	if($destroy)
		imagedestroy($img);	
}

//GD temp image
function getTempImageName()
{
	global $config;
	return getDiskPath(combine(@$config["TEMP_DIR"], session_id() . ".gd2"));
}

function getUndoImageName()
{
	global $config;
	return getDiskPath(combine(@$config["TEMP_DIR"], session_id() . "_0.gd2"));
}

function loadTempImage($undo=false)
{
	$tmpFilename=getUndoImageName();
	if(!$undo || !file_exists($tmpFilename))
		$tmpFilename=getTempImageName();
	if(!file_exists($tmpFilename)) 
		return false;
	return loadImage($tmpFilename);
}

function deleteTempImage()
{
	$tmpFilename=getUndoImageName();
	if(file_exists($tmpFilename)) 
		unlink(getUndoImageName());
	$tmpFilename=getTempImageName();
	if(file_exists($tmpFilename)) 
		return unlink($tmpFilename);
	return false;
}

function saveTempImage($img)
{
	global $config;
	createDir($config["TEMP_DIR"]);
	$undoFilename=getUndoImageName();
	$tmpFilename=getTempImageName();
	if(file_exists($undoFilename))
		unlink($undoFilename);
	if(file_exists($tmpFilename))
		rename($tmpFilename,$undoFilename);
	//imagegd2($img, $tmpFilename);
	outputImage($img,$tmpFilename,"",false);
}

//create image with alpha channel or 1 transparent color
function imageCreateTransparent($width, $height, $transparentColor=0)
{
	$img = imagecreatetruecolor($width, $height);
	makeImageTransparent($img, $transparentColor);
	if($transparentColor) 
		imagefill($img, 0, 0, $transparentColor-1);
	return $img;
}

function makeImageTransparent($img,$transparentColor=0, $alpha=true)
{
	if($transparentColor) //set specified color as transparent color, and fill background
		imagecolortransparent($img,$transparentColor-1);
	else if($alpha) //if no color specified: save alpha channel
	{
		imagesavealpha($img, true);
		imagealphablending($img, false); 
	}
	return $img;
}

//resize image, keep aspect ratio, fit in dimensions passed
//TODO: 
function resizeImage($srcImg,$imageInfo,$dstW,$dstH,$destroy=true)
{
	$srcW=imageSX($srcImg);
	$srcH=imageSY($srcImg);
	if ($srcW > $srcH) //landscape image: fit width
	{
		$dstW=$dstW;
		$dstH=intval($dstH * $srcH / $srcW);
	}
	else if ($srcW < $srcH) //portrait image: fit height
	{
		$dstW=intval($dstW * $srcW / $srcH);
		$dstH=$dstH;
	}
	else // square image
	{
		$dstW=$dstW;
		$dstH=$dstH;
	}

//do not enlarge small images
	if ($srcW < $dstW && $srcH < $dstH)
		return $srcImg;

	$dstImg=imageCreateTransparent($dstW, $dstH, @$imageInfo["transparent"]);
	if(@$imageInfo["transparent"])
		imagecopyresized($dstImg,$srcImg,0,0,0,0,$dstW,$dstH,$srcW,$srcH); 
	else
		imagecopyresampled($dstImg,$srcImg,0,0,0,0,$dstW,$dstH,$srcW,$srcH); 
//	imagestring($dstImg, 5, 90, 5, "$srcW x $srcH - $dstW x $dstH", PINK);
	if($destroy)	imagedestroy($srcImg); 
	return $dstImg;
}

function convertImageToTrueColor($srcImg, $imageInfo, $destroy=true)
{
	$width=isset($imageInfo["width"]) ? $imageInfo["width"] : imageSX($srcImg);
	$height=isset($imageInfo["height"]) ? $imageInfo["height"] : imageSY($srcImg);
	$dstImg=imageCreateTransparent($width, $height, @$imageInfo["transparent"]);
	imagecopy($dstImg,$srcImg, 0, 0, 0, 0,$width, $height); 

	if($destroy)	imagedestroy($srcImg); 
	return $dstImg;
}

function cropImage($srcImg, $imageInfo, &$x1,&$y1,&$x2,&$y2,$destroy=true)
{
	if($x1==$x2 || $y1==$y2)// || !isInImage($srcImg,$x1,$y1) || !isInImage($srcImg,$x2,$y2))
		return $srcImg;

	$maxX = $imageInfo["width"] - 1;
	$maxY = $imageInfo["height"] - 1; 

	sortMinMax($x1,$x2);
	sortMinMax($y1,$y2);
	setBetween($x1,0,$maxX);
	setBetween($x2,0,$maxX);
	setBetween($y1,0,$maxY);
	setBetween($y2,0,$maxY);
	$width=$x2-$x1;
	$height=$y2-$y1;
	$dstImg=imageCreateTransparent($width,$height, @$imageInfo["transparent"]);
	imagecopy($dstImg,$srcImg,0,0,$x1,$y1,$width,$height); 

	if($destroy)	imagedestroy($srcImg); 
	return $dstImg;
}

function rotateImage($srcImg, $imageInfo, $rotateAngle, $destroy=true)
{
//	TODO: if multiple of 90 and IrfanView enabled, use lossless transform before other functions 
//	If srcImg is GIF = not truecolor => convert first.
	if(!imageistruecolor($srcImg))
	{
		$srcImg = convertImageToTrueColor($srcImg, $imageInfo);
		debug("rotateImage convertImageToTrueColor", $srcImg);
	}

	$dstImg = imagerotate($srcImg, $rotateAngle, $imageInfo["transparent"]-1);
	makeImageTransparent($dstImg,$imageInfo["transparent"]);
debug("rotateImage $srcImg", $dstImg);
	if($destroy)	imagedestroy($srcImg); 
	return $dstImg;
}

function swap(&$min,&$max)
{
	$tmp=$max;
	$max=$min;
	$min=$tmp;
}

function setBetween(&$val,$min,$max)
{	
	sortMinMax($min,$max);
	$val=max($val,$min);
	$val=min($val,$max);
	return $val;
}

function getColor($img, $r,$g,$b,$a=0)
{
	if($a>0)	return imagecolorallocatealpha($img, $r, $g, $b,  $a);
	return imagecolorallocate($img, $r, $g, $b);
}

function getImageTypeFromExt($file)
{
	$ext = strtolower(getFilenameExtension($file));
	return ($ext==="jpg") ? "jpeg" : $ext;
}

function getImageMimeType($file)
{
	$imageInfo = getimagesize($file);
	return $imageInfo["mime"];
}


function getImageSizeInfo($file, &$imageInfo=array())
{
	$is = @getimagesize($file);
debug("getImageSize", $is);
    if(!$is)	return false;
	$imageInfo["format"] = substringAfter($is["mime"],"/");
	$imageInfo["width"] = $is[0];
	$imageInfo["height"] = $is[1];
	if($imageInfo["height"])
		$imageInfo["ratio"] =  $imageInfo["width"] / $imageInfo["height"];
	return $imageInfo;
}

function getImageInfo($file, $img=null, &$imageInfo=array())
{
	if(!file_exists($file)) return array();

	getImageSizeInfo($file, $imageInfo);
	if(isTransparentType($imageInfo))
		loadImageInfo($file, "", $imageInfo);
debug("loadImageInfo", $imageInfo);

//do not return if GIF or PNG
	if($imageInfo && isset($imageInfo["width"]) && !isTransparentType($imageInfo))
	 	return $imageInfo;

	getImageSizeInfo($file, $imageInfo);
debug("getImageSizeInfo", $imageInfo);
	if(!$imageInfo || !isTransparentType($imageInfo)) 
		return $imageInfo;

	getImageAnimInfo($file, $imageInfo);

//$img true: force load
debug("img", $img);
	getImageTransparencyInfo($file, $img, $imageInfo);
debug("saveImageInfo",$imageInfo);
	saveImageInfo($file, "", $imageInfo);

debug("getImageInfo return", $imageInfo);
	return $imageInfo;
}


function getImageAnimInfo($file, &$imageInfo=array())
{
	//Animated GIF
	if(isset($imageInfo["animated"])) return $imageInfo;
	$imageInfo["animated"] = isAnimatedGif($file, $imageInfo);
	if($imageInfo["animated"])
		$imageInfo["frames"] = countAnimatedGifFrames($file,$imageInfo);
	debug("getImageAnimInfo", $imageInfo);
	return $imageInfo;
}

function getImageTransparencyInfo($file, $loadImg, &$imageInfo=array())
{
	//Transparent GIF or PNG
	if(!$loadImg || !isTransparentType($imageInfo))
		return $imageInfo;

	//$img true: force load, then destroy at end of function
	if($loadImg===true)
		$img = loadImage($file, $imageInfo);
	else
		$img = $loadImg;
	if(!isset($imageInfo["transparent"]))
	{
		$imageInfo["transparent"] = hasTransparentColor($img, $imageInfo);
debug("transparentColor", $imageInfo["transparent"]);
		if($imageInfo["transparent"])
			$imageInfo["transparentPixels"] = hasColorPixels($img, $imageInfo["transparent"]-1 , $imageInfo);
debug("transparentPixels", @$imageInfo["transparentPixels"]);
	}

	//PNG with alpha channnel
	if(!isset($imageInfo["alpha"]))
	{
		$imageInfo["alpha"] = hasAlphaPixels($img, $imageInfo);
debug("alpha", @$imageInfo["alpha"]);
	}
	if($loadImg===true)
		imagedestroy($img); 

	return $imageInfo;
}

function loadImageInfo($relPath, $file="", &$imageInfo=array())
{
	$csvFilename = getMetadataFilename($relPath, $file);
	readCsvFile($csvFilename, 0, ";", ".", $imageInfo);
	if(isset($imageInfo["type"]))
		$imageInfo = resetMedadata($relPath, $file);
	return $imageInfo;
}

function saveImageInfo($relPath, $filename, $imageInfo)
{
	$csvFilename = getMetadataFilename($relPath, $filename, true);
	if($csvFilename)
		writeCsvFile($csvFilename, $imageInfo, true);
}

function resetMedadata($relPath, $file)
{
	saveImageInfo($relPath, $file, null);
	return array();
}

function getMetadataFilename($relPath, $filename="", $createDir=false)
{
	debug("getMetadataFilename", "($relPath, $filename)");
	if(!$filename)
	{
		splitFilePath($relPath, $relPath, $filename);
		debug("splitFilePath", "($relPath, $filename)");
	}
	if(!file_exists($relPath) && !$createDir) return false;
	createDir($relPath,".tn");
	$filename = getFilename($filename, "csv");
	return combine($relPath, ".tn", $filename);
}

//GD image Filter functions

//to black and white
function gd_grayscale($img)
{
  imagefilter($img,IMG_FILTER_GRAYSCALE);
}

//to negative
function gd_negative($img)
{
  imagefilter($img,IMG_FILTER_NEGATE);
}

//to black and white, then sepia
function gd_sepia($img)
{
  imagefilter($img,IMG_FILTER_GRAYSCALE);
  imagefilter($img,IMG_FILTER_COLORIZE,100,50,0);
}

//fill ajacent pixels with similar color with transparent color
function clearBackground($img, $imageInfo, $x, $y, $tolerance, $bgColor)
{
	global $startColor, $iterations;
	$iterations=0;
debug("clearBackground",$imageInfo);
	if(!isInImage($img,$x,$y)) return 0;

	$pixelColor = imagecolorat($img, $x, $y);
	$startColor=$pixelColor;
	
	$pixFilled=floodFill($img, $imageInfo, $x, $y, 1, 1, $tolerance, $bgColor,$pixelColor);
	$pixFilled=floodFill($img, $imageInfo, $x-1, $y-1, -1, -1, $tolerance, $bgColor,$pixelColor);
	$pixFilled=floodFill($img, $imageInfo, $x-1, $y, -1, 1, $tolerance, $bgColor,$pixelColor);
	$pixFilled=floodFill($img, $imageInfo, $x, $y-1, 1, -1, $tolerance, $bgColor,$pixelColor);
	return $pixFilled;
}

//fill ajacent pixels with similar color with transparent color
function floodFill($img, $imageInfo, $x, $y, $dx, $dy, $tolerance, $newColor, $prevColor=null)
{
	global $iterations;
	$iterations++;
debug("floodFill",$imageInfo);
	//if($iterations> 4 * $imageInfo["width"] * $imageInfo["height"]) return 0;
	
	if(!isInImage($img, $x, $y)) return 0;
	
	//go 1 more after edge found
	$pixelColor = imagecolorat($img, $x, $y);
	$result=imagesetpixel($img, $x, $y, $newColor);

	if(!colorMatches($pixelColor, $newColor, $prevColor, $tolerance))	return 0;
	
	$result+=floodFill ($img, $x+$dx, $y, $dx, $dy, $tolerance, $newColor, $pixelColor); //bottom
	$result+=floodFill ($img, $x, $y+$dy, $dx, $dy, $tolerance, $newColor, $pixelColor); //right
	return $result;
}

function pixelMatches($img, $x, $y, $newColor, $prevColor, $tolerance)
{
	if(!isInImage($img,$x,$y)) return false;
	$pixelColor = imagecolorat($img, $x, $y);
	return colorMatches($pixelColor, $newColor, $prevColor, $tolerance);
}

function colorMatches($pixelColor, $newColor, $prevColor, $tolerance)
{
	global $startColor;
	$t2=$tolerance*$tolerance;
	//if($pixelColor == $newColor) return false;
	if(colorDiff($pixelColor,$startColor) <= $t2) return true;
	return colorDiff($pixelColor,$prevColor) <= $t2;
	return false;
}

function replaceColor($img, $imageInfo, $startColor, $tolerance, $newColor)
{
	global $iterations, $startColor;
	$result=0;
	$prevColor=$startColor;
	for($y=0; $y < $imageInfo["height"]; $y++)
		for($x=0; $x < $imageInfo["width"]; $x++)
		{
			$iterations++;
			$pixelColor = imagecolorat($img, $x, $y);
			if(colorMatches($pixelColor, $newColor, $prevColor, $tolerance))
				$result+=imagesetpixel($img, $x, $y, $newColor);
		}
	return $result;
}

function replacePixelColor($img, $imageInfo, $x, $y, $tolerance, $newColor)
{
	global $imgWidth, $imgHeight, $iterations, $startColor;
	$startColor = imagecolorat($img, $x, $y);
	return replaceColor($img, $imageInfo, $startColor, $tolerance, $newColor);
}

function isInImage($img,$x,$y)
{
	$maxX = ImageSX($img);
	$maxY = ImageSY($img); 
	return $x>=0 && $x<$maxX && $y>=0 && $y<$maxY;
}

function isTransparentType($imageInfo, $types=TRANSPARENT_TYPES)
{
	$imgType = is_array($imageInfo) ? @$imageInfo["format"] : $imageInfo;
	if(!$imgType) return true;
	return contains($types, $imgType);
}

//returns if the GIF or PNG image has transparent background color AND Transparent pixels OR alpha pixels
function isImageTransparent($img, $imageInfo)
{
	if(!isTransparentType($imageInfo))	return false;
	if(is_string($img))		$img=loadImage($img);

	$tr = @$imageInfo["transparent"];
	if(!$tr) $tr = hasTransparentColor($img, $imageInfo);
	$tp = $tr && hasColorPixels($img, $tr-1 , $imageInfo);
	return $tp || hasAlphaPixels($img, $imageInfo);
}

function hasTransparentColor($img, $imageInfo)
{
	if(!isTransparentType($imageInfo))	return 0;
	$transparency=imagecolortransparent($img);
	return $transparency + 1;
}

//check if any pixel of given color
//TODO check 1st and last rows and columns first
function hasColorPixels($img, $color)
{
    $width = imagesx($img); // Get the width of the image
    $height = imagesy($img); // Get the height of the image
    // We run the image pixel by pixel and as soon as we find a transparent pixel we stop and return true.
    //TODO look in corners and edges first.
    for($j = 0; $j < $height; $j++)
	    for($i = 0; $i < $width; $i++)
        {
            $rgba = imagecolorat($img, $i, $j);
            if($rgba == $color)
                return $j * $width + $i + 1;
        }
    // If we dont find any pixel the function will return false.
    return 0;
}

//check if any alpha pixel. PNG only
function hasAlphaPixels($img, $imageInfo)
{
	if(!isTransparentType($imageInfo, "png")) return false;
    $width = imagesx($img); // Get the width of the image
    $height = imagesy($img); // Get the height of the image
    // We run the image pixel by pixel and as soon as we find a transparent pixel we stop and return true.
    for($j = 0; $j < $height; $j++)
	    for($i = 0; $i < $width; $i++)
        {
            $rgba = imagecolorat($img, $i, $j);
            $alpha = ($rgba & 0x7F000000) >> 24;            
            if($alpha == 0x7F)
                return $j * $width + $i + 1;
        }
    // If we dont find any pixel the function will return false.
    return 0;
}

//count transparent pixels and fill them with color
function fillPixels($img,$color=TRANSPARENT)
{
    $width = imagesx($img); // Get the width of the image
    $height = imagesy($img); // Get the height of the image
    $nb=0;
    // We run the image pixel by pixel and as soon as we find a transparent pixel we stop and return true.
    for($j = 0; $j < $height; $j++)
	    for($i = 0; $i < $width; $i++)
        {
            $rgba = imagecolorat($img, $i, $j);
            $alpha = ($rgba & 0x7F000000) >> 24;            
            if($alpha == 0x7F)
            {
            	$color = imagecolorallocate($img, 0, $alpha, $alpha);
            	//set color according to Alpha value
            	imagesetpixel($img, $i, $j, $color);
                $nb++;
            }
        }
    // If we dont find any pixel the function will return false.
    return $nb;
}

//detect animated gif
function isAnimatedGif($filename, $imageInfo)
{
    if(!file_exists($filename))	return false;
	if(!isTransparentType($imageInfo, "gif")) return false;
	// '#(\x00\x21\xF9\x04.{4}\x00\x2C.*){2,}#s'
    return preg_match('#(\x00\x21\xF9\x04.{4}\x00.*){2,}#s', file_get_contents($filename));
}

//detect animated gif + count frames!
//an animated gif contains multiple "frames", with each frame having a
//header made up of:
// * a static 4-byte sequence (\x00\x21\xF9\x04)
// * 4 variable bytes
// * a static 2-byte sequence (\x00\x2C)
//'#\x00\x21\xF9\x04.{4}\x00\x2C#s'

function countAnimatedGifFrames($filename, $imageInfo)
{
    if(!file_exists($filename))	return false;
	if(!isTransparentType($imageInfo, "gif")) return false;
	$chunk=file_get_contents($filename);
	$count = preg_match_all('#\x00\x21\xF9\x04.{4}\x00#s', $chunk, $matches);
    return $count;
}

function colorDiff($rgb1,$rgb2)
{
	if($rgb1==$rgb2) return 0;

	$dr = getColorComp($rgb1,RgbColor::R) - getColorComp($rgb2,RgbColor::R);
	$dg = getColorComp($rgb1,RgbColor::G) - getColorComp($rgb2,RgbColor::G);
	$db = getColorComp($rgb1,RgbColor::B) - getColorComp($rgb2,RgbColor::B);

	return $dr * $dr + $dg * $dg + $db * $db;
//    return abs($dr) + abs($dg) + abs($db);
}

function getColorComp($rgb,$comp)
{
	return ($rgb >> (8*$comp)) & 0xFF;
}

// Draw a border
function drawBorder($img, $color, $thickness = 1)
{
    $x1 = 0;
    $y1 = 0;
    $x2 = ImageSX($img) - 1;
    $y2 = ImageSY($img) - 1;

    for($i = 0; $i < $thickness; $i++)
        ImageRectangle($img, $x1++, $y1++, $x2--, $y2--, $color);
} 
?>
