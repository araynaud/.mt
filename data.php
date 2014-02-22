<?php session_start(); 
require_once("include/config.php");

function getFileData(&$getData, $path, $file)
{
	global $config;

	$relPath=getDiskPath($path);
	$filePath=combine($relPath, $file);
	$getData = strtolower($getData);
	switch ($getData)
	{
		case "groupedfiles":
		case "files":
			$search = getSearchParameters();
			$files = listFiles($relPath, $search); 
			if($getData == "groupedfiles")
				$files = groupByName($files, true);
			return $files;
		case "config":
			return $config;
		case "size":
			return getImageSize($filePath);
		case "info":
			return getImageInfo($filePath);
		case "metadata":
		case "exif":
			return getMediaFileInfo($filePath);
		case "album":
			$getData="Album";
			return new Album($path, true);
		case "mediafile":
		default:
			$getData="MediaFile";
			$mf = MediaFile::getMediaFile();
			if($mf && is_object($mf))
			{
				$mf->files = $mf->getFilenames();
				$mf->paths = $mf->getFilePaths(true);
				$mf->urls  = $mf->getFilePaths(true, true);
			}
			return $mf;
	}
}

startTimer();

$getData = reqParam("data", "MediaFile");
//output options
$format=reqParam("format", "json");
$indent=reqParam("indent", 1);
$includeEmpty=reqParamBoolean("empty");
$attributes=getParamBoolean("attributes", true);

$path=getPath();
$relPath=getDiskPath($path);
$file=getParam("file"); // file or folder description file
$filePath=combine($relPath, $file);
$data=getFileData($getData, $path, $file);
$data=objToArray($data, true, true);

$save=getParamBoolean("save");
if($save)
	saveImageInfo($relPath, $file, $data);
if(isAssociativeArray($data))
{
	$data["count"] = count($data);
	$data["time"] = getTimer();
}

switch (strtolower($format))
{
	case "xml":
		setContentType("text", $format);
		echo xmlValue($getData, $data, $indent, $includeEmpty, true, $attributes);
		break;
	case "csv":
		setContentType("text", "plain");
		echo csvValue($data, $includeEmpty);
		break;
	default:
		setContentType("text", "plain");
		echo jsValue($data, $indent, $includeEmpty);
}

?>
