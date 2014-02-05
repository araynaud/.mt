<?php
require_once("../include/config.php");

startTimer();

$path=getPath();
$relPath=getDiskPath($path);
$file=getParam("file");
$chunk = getParam("chunk",0);
$nbChunks = getParam("nbChunks",1);

$tndir = getParam("tn");

$thumbnails = findThumbnails($relPath, $file);
debugVar("thumbnails");

//publish.url, .username, .password, path, upload_max_filesize from config file 
$publish = getConfig("_publish");
$uploadScript = getConfig("_publish.script");
$site = getParam("site", $publish["default"]);
$publish = getConfig("_publish.$site");
debugVar("publish");
$destPath = combine(@$publish["path"], getParam("target"));

$serviceBaseUrl = combine($publish["url"], $uploadScript);

$filePath = combine($relPath, $file);
debugVar("filePath");
$descFilePath = combine($relPath, getFilename($file,"txt"));
debugVar("descFilePath");

$response = array();
if(!file_exists($filePath))
{	
	$response["message"] = "file $path/$file does not exist.";
	echo jsValue($response);
	return;	
}

//if file is image, check if width or height > max dimension
debugVar("publish");
$maxSize = arrayGet($publish, "image.size");
debugVar("maxSize");
$maxUpload = isset($publish["upload_max_filesize"]) ? $publish["upload_max_filesize"] : 0;
$useResizedImage = false;
if(fileIsImage($filePath))
{
	//find the right size. largest < max size
	$imageInfo = getImageInfo($filePath);
	$tnIndex = getTnIndexForSize($imageInfo, $maxSize);
	$tndir =  getSubdirForTnIndex($tnIndex);
	debugVar("tnIndex");
	$useResizedImage = $imageInfo && $maxSize && ($imageInfo["width"] > $maxSize || $imageInfo["height"] > $maxSize);
}
if($tndir)
	$tndir=".$tndir";
debugVar("tndir");

debugVar("useResizedImage");

//if image dimensions larger than max size: create resized image
if($useResizedImage)
{
	debugVar("imageInfo");
	$tnPath=combine($relPath, $tndir, $file);
	debugVar("tnPath");
	//if tnpath does not exists, create dir and resized image
	if(!file_exists($tnPath))
	{
		createThumbnail($relPath, $file, $tndir, $maxSize);
//		createResizedImage($relPath, $file, $tnPath, $maxSize, $maxSize);
		debug("created $tnPath");
	}
	$filePath = $tnPath;
	$relPath=combine($relPath, $tndir);
	debugVar("filePath");
}

$metadataFilename = getMetadataFilename($relPath, $file);
if(!file_exists($metadataFilename))
{
	$data = getMediaFileInfo($filePath);
	if($data)
		saveImageInfo($relPath, $file, $data);
}

$response["filesize"] = filesize($filePath);	
addVarToArray($response,"tnIndex");
addVarToArray($response,"tndir");

//1st time: split file if necessary
if(!$chunk)
{
	$nbChunks = splitChunks($relPath, $file, $maxUpload, $chunks);
//	$response["chunks"] = $chunks;
}

if($nbChunks>1)
{
	$chunk++;
	$chunkName = getChunkName($file, $chunk, $nbChunks);
	$filePath = combine($relPath, $chunkName);
}

addVarToArray($response,"chunk"); // == $nbChunks ? 0 : $chunk;
addVarToArray($response,"nbChunks");

$response["file"] = uploadFile($publish, $filePath, $destPath);

if($nbChunks>1) // delete after upload
	deleteFile($filePath);

//upload thumbnails if they exist. only with 1st chunk
if($chunk <=1)
{
	if(file_exists($descFilePath))
		$response["description"] = uploadFile($publish, $descFilePath, $destPath);

	if($thumbnails)
		foreach ($thumbnails as $dir => $tnPath)
			if($tnPath && ".$dir" != $tndir)
				$response[$dir] = uploadFile($publish, $tnPath, "$destPath/.$dir");

	//save metadata if not using original image
	$response["metadata"] = uploadFile($publish, $metadataFilename, $destPath, ".tn");
}

$response["time"] = getTimer();
echo jsValue($response);

//response:
//no file: { "path": "demo", "getcwd": "/mnt/111/sdb/d/b/minorart/.mp/.upload", "freeSpace": 10000000, "target": "../../demo" }
//file uploaded: { "path": "demo", "getcwd": "/mnt/111/sdb/d/b/minorart/.mp/.upload", "freeSpace": 10000000, 
//"tmpFile": "/mnt/111/sdb/d/b/minorart/phpQPlW8Z", "fileType": "image/jpeg", "target": "../../demo/original_upload.jpg", "moved": true }
//echo "[" . implode(",", $response) . "\n]";
?>