<?php
require_once("../include/config.php");

startTimer();

$path=getPath();
$file=getParam("file");
$relPath=getDiskPath($path);
$mf = MediaFile::getMediaFile();

if(!$mf)
{	
	$response["message"] = "file $path/$file does not exist.";
	echo jsValue($response);
	return;	
}

$chunk = getParam("chunk",0);
$nbChunks = getParam("nbChunks",1);

//publish.url, .username, .password, path, upload_max_filesize from config file 
$publish = getConfig("_publish");
$site = getParam("site", $publish["default"]);
$publish = getConfig("_publish.$site");
debugVar("publish");
$destPath = combine(@$publish["path"], getParam("target"));

$filePath = combine($relPath, $file);
debugVar("filePath");

$response = array();

//if file is image, check if width or height > max dimension
debugVar("publish");
$maxSize = arrayGet($publish, "image.size");
debugVar("maxSize");
$maxUpload = isset($publish["upload_max_filesize"]) ? $publish["upload_max_filesize"] : 0;
$useResizedImage = false;

//1st time: split file if necessary
if(!$chunk)
	$nbChunks = splitChunks($relPath, $file, $maxUpload, $chunks);

if($nbChunks>1)
{
	$chunk++;
	$chunkName = getChunkName($file, $chunk, $nbChunks);
	$filePath = combine($relPath, $chunkName);
}

addVarToArray($response,"chunk");
addVarToArray($response,"nbChunks");

$response["file"] = uploadFile($publish, $filePath, $destPath);

if($nbChunks>1) // delete after upload
	deleteFile($filePath);

//upload thumbnails if they exist. only with 1st chunk
if($chunk <=1)
{
	$filePaths = $mf->getFilePaths(true);
	if($filePaths)
	foreach ($filePaths as $key => $value) 
	{
		$response[$key] = uploadFile($publish, $value, $destPath);
	}
}

$response["time"] = getTimer();
echo jsValue($response);

//response:
//no file: { "path": "demo", "getcwd": "/mnt/111/sdb/d/b/minorart/.mp/.upload", "freeSpace": 10000000, "target": "../../demo" }
//file uploaded: { "path": "demo", "getcwd": "/mnt/111/sdb/d/b/minorart/.mp/.upload", "freeSpace": 10000000, 
//"tmpFile": "/mnt/111/sdb/d/b/minorart/phpQPlW8Z", "fileType": "image/jpeg", "target": "../../demo/original_upload.jpg", "moved": true }
//echo "[" . implode(",", $response) . "\n]";
?>