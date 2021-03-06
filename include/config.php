<?php
require_once("http_functions.php");
require_once("text_functions.php");
require_once("array_functions.php");
require_once("path_functions.php");
require_once("json_xml_functions.php");
require_once("debug_functions.php");
require_once("file_functions.php");
require_once("config_functions.php");
require_once("dir_functions.php");
require_once("login_functions.php");
require_once("image_functions.php");
require_once("exif_functions.php");
require_once("command_functions.php");
require_once("ffmpeg_functions.php");
require_once("dateindex_functions.php");
require_once("ui_functions.php");

require_once("classes/BaseObject.php");
require_once("classes/User.php");
require_once("classes/MediaFile.php");
require_once("classes/Album.php");

$startTime = startTimer();
$path = reqPath();
LoadConfiguration($path, $config);
if(isDebugMode())
	header("Content-Type: text/plain");
//after loading mappings
$params = requestFilters(false);
if($path != $params["path"])
{
	$path = $params["path"];
	LoadConfiguration($path, $config);
}
debugVar("params");

if($timezone = getConfig("DEFAULT_TIMEZONE"))
	date_default_timezone_set($timezone);
debug();
?>