<?php session_start(); 
require_once("include/config.php");

function getFileData(&$getData, $path, $file)
{
	global $config;
	$details=reqParamBoolean("details");
	$relPath=getDiskPath($path);
	$filePath=combine($relPath, $file);
	$getData = strtolower($getData);
	$search = getSearchParameters();
	switch ($getData)
	{
		case "groupedscandir":
		case "scandir":
			$files = scandir($relPath); 
			if($getData == "groupedscandir")
				$files = groupByName($relPath, $files, true, $details);
			return $files;

		case "groupedfiles":
		case "files":
			$files = listFiles($relPath, $search); 
			if($getData == "groupedfiles")
				$files = groupByName($relPath, $files, true, $details);
			return $files;
		case "tags":
			return array_keys(listTagFiles($relPath, $search["depth"], @$search["tag"], true));
		case "taglists":
			if(@$search["tag"])
				$tags = searchTagFiles($relPath, $search["depth"], @$search["tag"]);
			else
				$tags = loadTagFiles($relPath, $search["depth"]);
			return $tags;
		case "tablefile":
			return readCsvTableFile($filePath, 0, true);
		case "playlist":
			return readPlaylistFile($filePath); //, 0, true);
		case "datafile":
			return readConfigFile($filePath);
		case "config":
			return $config;
		case "size":
			return getImageSize($filePath);
		case "info":
			return getImageInfo($filePath);
		case "metadataindex":
			return getMetadataIndex($relPath, $search["type"], null, true);
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
$countTime = reqParamBoolean("counttime", true);
//output options
$format=reqParam("format", "json");
$format=strtolower($format);

$indent=reqParam("indent", 1);
$includeEmpty=reqParamBoolean("empty");
$attributes=getParamBoolean("attributes", true);

reqPathFile($path, $file, false);
$name = reqParam("name");
debugVar("name");
$relPath=getDiskPath($path);

$filePath=combine($relPath, $file);
$data=getFileData($getData, $path, $file);
$data=objToArray($data, true, true);

$save=getParamBoolean("save");
if($save)
	saveImageInfo($relPath, $file, $data);

//XML: ensure single root element
$count=count($data);
if($format == "xml" && !isAssociativeArray($data))
{
	$data = array($getData."_item" => $data);
}

if($countTime)
{
//	if(!isAssociativeArray($data))
		array_unshift($data, array("count"=>$count, "time"=>getTimer()));
/*	else
	{
		$data["count"] = $count;
		$data["time"] = getTimer();
	}
*/
}

switch ($format)
{
	case "xml":
		setContentType("text", $format);
		echo '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
		echo xmlValue($getData, $data, $indent, $includeEmpty, true, $attributes);
		break;
	case "csv":
		setContentType("text", "plain");
		echo csvValue($data, $includeEmpty);
		break;
	default:
		setContentType("text", "plain");
		$json = jsValue($data, $indent, $includeEmpty);
		//writeTextFile("$relPath/.album.json", $json);
		echo $json;
}

?>
