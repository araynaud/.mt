<?php
require_once("../include/config.php");
setContentType("text", "plain");

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
	if(!$stream)
	{
		$response["time"] = getTimer();
		$response["message"] = "$file is not a streamable video. convert file first.";
		echo jsValue($response);
		return;
	}
	$stream=$stream[0];
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
$postData["metadata"] = csvValue($mf->getMetadata());	
$postData["tags"] = csvValue($mf->getTags());	
$response["file"] = uploadFile($publish, $filePath, $destPath, $postData);

if($nbChunks>1) // delete after upload
	deleteFile($filePath);


//upload thumbnails if they exist and if config enables it.
// only with 1st chunk. or last?
if($chunk <=1)
{
	$descPath = $mf->getDescriptionFilename(true);
	$postData["version"] = "description";
	$response["desc"] = uploadFile($publish, $descPath, $destPath);

	$filePaths = $mf->getFilePaths(true, false, false, true, false);
	foreach ($filePaths as $key => $value) 
		if($filePath!=$value)
			$response[$key] = uploadFile($publish, $value, "$destPath/.$key");
}


//send metadata row as data, upload.php appends it to index file
$response["time"] = getTimer();
echo jsValue($response);

?>