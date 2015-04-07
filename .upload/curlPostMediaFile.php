<?php
require_once("../include/config.php");
setContentType("text", "plain");

startTimer();

$mf = MediaFile::getMediaFile();
$file = $mf->getFilename();

$response = array();
if(!$mf)
{	
	$response["message"] = "file $file does not exist.";
	echo jsValue($response);
	return;	
}

$relPath = $mf->getRelPath();
$fileType = strtolower($mf->getFileType());

$publish = getConfig("_publish");
if(!$publish)
{	
	$response["message"] = "publishing from that site is disabled.";
	echo jsValue($response);
	return;	
}

$chunk = reqParam("chunk",0);
$nbChunks = reqParam("nbChunks",1);

//publish.url, .username, .password, path, upload_max_filesize from config file 
$site = reqParam("site", $publish["default"]);
$publish = getConfig("_publish.$site");
debugVar("publish");
$destPath = combine(@$publish["path"], reqParam("target"));
$filePath = $mf->getFilePath();
debugVar("filePath");

//if file is image, check if width or height > max dimension
debugVar("publish");
$maxSize = arrayGet($publish, "image.size");
debugVar("maxSize");
$maxUpload = isset($publish["upload_max_filesize"]) ? $publish["upload_max_filesize"] : 0;
$tndir = false;

//find the right size. largest < max size
if($mf->isImage())
{
	$tndir = $mf->selectThumbnail($maxSize);
	if($tndir)
	{
		if($mf->getThumbnailFilesize($tndir) < 0)
		{
			$tnPath = $mf->createThumbnail($tndir);
			debug("created thumbnail $tnPath");
		}
		$filePath = $mf->getThumbnailFilePath($tndir);
		$relPath = dirname($filePath);
		debug("using thumbnail $tndir", $filePath);
	}
}
else if($mf->isVideo())
{	
	$stream=$mf->isVideoStream();
debug("isVideoStream", $stream, "print_r");
	if(!$stream)
	{
		$response["time"] = getTimer();
		$response["message"] = "$file is not a streamable video. convert file first.";
		echo jsValue($response);
		return;
	}	
	if(is_array($stream))
		$stream=reset($stream);
	$file = $mf->getFilename($stream);
	$filePath = $mf->getFilePath($stream);
	debug("using $stream $file", $filePath);
}

//1st time: split file if necessary
if(!$chunk)
	$nbChunks = splitChunks($relPath, $file, $maxUpload, $chunks);

if($nbChunks>1)
{
	$chunk++;
	$chunkName = getChunkName($file, $chunk, $nbChunks);
	$filePath = combine($relPath, $chunkName);
}

addVarToArray($response,"mf");
addVarToArray($response,"chunk");
addVarToArray($response,"nbChunks");

$postData = array();
$postData["type"] = $mf->getFileType();
$postData["fileDate"] = $mf->getTakenDate();
if($mf->isVideo())
	$postData["metadata"] = csvValue($mf->getMetadata());	
$postData["tags"] = csvValue($mf->getTags());	
$response["file"] = uploadFile($publish, $filePath, $destPath, $postData);

if($nbChunks>1) // delete after upload
	deleteFile($filePath);


//upload thumbnails if they exist and if config enables it.
// only with 1st chunk. or last?
if($chunk <=1)
{
	//upload description file
	$descPath = $mf->getDescriptionFilename(true);
	$postData["version"] = "description";
	$response["desc"] = uploadFile($publish, $descPath, $destPath);
	
	//upload subtitle file if it exists
	$descPath = $mf->getSubtitlesFilename(true);
	if($descPath)
	{
		$postData["version"] = "subtitles";
		$response["sub"] = uploadFile($publish, $descPath, $destPath);
	}
}

$uploadThumbs = arrayGet($publish, "$fileType.thumbnails");
if($chunk <=1 && $uploadThumbs)
{
	$filePaths = $mf->getFilePaths(true, false, false, true, false);
	foreach ($filePaths as $key => $value) 
		if($filePath!=$value)
			$response[$key] = uploadFile($publish, $value, "$destPath/.$key");
}

//send metadata row as data, upload.php appends it to index file
$response["time"] = getTimer();
echo jsValue($response);

?>