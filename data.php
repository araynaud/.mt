<?php session_start(); 
require_once("include/config.php");

function getFileData(&$getData)
{
	global $config;
	$getData = strtolower($getData);
	$search = getSearchParameters();
	$details = reqParam("details", 3);
	$all = reqParamBoolean("all");
	$relPath = getDiskPath(@$search["path"]);
	$file = reqParam("file");
	$filePath=combine($relPath, $file);
	$key = reqParam("key", false);
	switch ($getData)
	{
		case "album":
			$getData="Album";
			return new Album($search, $details);
		case "scandir":
			return scandir($relPath); 
		case "groupedscandir":
			$files = scandir($relPath); 
			return groupByName($relPath, $files, true, $details);
		case "files":
			return listFilesRecursive($relPath, $search); 
		case "groupedfiles":
			$files = listFilesRecursive($relPath, $search); 
			return groupByName($relPath, $files, true, $details);
		case "thumbnails":
			return subdirThumbs($relPath, @$search["count"], $search["depth"]);
		case "tags":
			return array_keys(listTagFiles($relPath, $search["depth"], @$search["tag"], true));
		case "taglists":
			if(@$search["tag"])
				$tags = searchTagFiles($relPath, $search["depth"], @$search["tag"]);
			else
				$tags = loadTagFiles($relPath, $search["depth"]);
			return $tags;
		case "tablefile":
			return readCsvTableFile($filePath, $key, true);
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
		case "keyframes":
			return getKeyframes($filePath);
		case "streamformat":
			return getStreamFormat($filePath);
		case "format":
			$columns = $all ? null : array("filename", "nb_streams", "format_name", "format_long_name", "start_time", "duration", "size", "bit_rate");		
			return ffprobe($filePath, "format", $columns);
		case "stream":
		case "streams":
			$columns = $all ? null : array("codec_name", "codec_type", "duration", "bit_rate", "width", "height", "nb_frames", "sample_aspect_ratio", "display_aspect_ratio");
			return ffprobe($filePath, "streams", $columns);
		case "mediafile":
		default:
			$getData="MediaFile";
			$mf = MediaFile::getMediaFile();
			if($mf && is_object($mf) && $all)
			{
				$mf->files = $mf->getFilenames();
				$mf->paths = $mf->getFilePaths(true);
				$mf->urls  = $mf->getFilePaths(true, true);
			}
			return $mf;
	}
}

//startTimer();

$getData = reqParam("data", "MediaFile");
$countTime = reqParamBoolean("counttime");
//output options
$format=reqParam("format", "json");
$format=strtolower($format);

$indent=reqParam("indent", 1);
$includeEmpty=reqParamBoolean("empty");
$attributes=getParamBoolean("attributes", true);

$data=getFileData($getData);
$data=objToArray($data, true, true);

//XML: ensure single root element
$count=count($data);
if($format == "xml" && !isAssociativeArray($data))
{
	$data = array($getData."_item" => $data);
}

if($countTime)
{
	$ct = array("count"=>$count, "time"=>getTimer());
	if(!isAssociativeArray($data))
		array_push($data,$ct);
	else
		array_unshift($data, $ct);
}

switch ($format)
{
	case "xml":
		setContentType("text", $format);
		echo '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
		echo xmlValue($getData, $data, $indent, $includeEmpty, true, $attributes);
		break;
	case "csv":
	case "text":
		setContentType("text", "plain");
		echo csvValue($data, $includeEmpty);
		break;
	default:
		setContentType("text", "plain");
		echo jsValue($data, $indent, $includeEmpty);
}

?>
