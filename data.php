<?php session_start(); 
require_once("include/config.php");

function getFileData(&$getData, $path, $file)
{
	global $config;

	$arrays=getParamBoolean("arrays");
	$relPath=getDiskPath($path);
	$filePath=combine($relPath, $file);
	switch (strtolower($getData))
	{
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
			if($mf && is_array($mf))
			{
				$mf["files"] = $mf[0]->getFilenames();
				$mf["paths"] = $mf[0]->getFilePaths(true);
				$mf["urls"]  = $mf[0]->getFilePaths(true, true);
			}
			else if(is_object($mf))
			{
				$mf->files = $mf->getFilenames();
				$mf->paths = $mf->getFilePaths(true);
				$mf->urls  = $mf->getFilePaths(true, true);
			}
			return $mf;
	}
}

startTimer();

$getData = getParam("data", "MediaFile");
//output options
$format=getParam("format", "json");
$indent=getParam("indent", 1);
$includeEmpty=getParamBoolean("empty");
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

$data["fileDate"] = getFileDate($filePath);
$data["time"] = getTimer();

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
