<?php
error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);
//error_reporting(E_PARSE);
$configFile="include/config.php";
if(file_exists("../$configFile"))	$configFile = "../$configFile";
require_once($configFile);

startTimer();

header("Content-Type: text/plain");
session_start();

debug();


$string = "deleteChars(string, start, end=null)";
debug("deleteChars", $string);
debug($string, deleteChars($string, 4));
debug($string, deleteChars($string, 6, 10));
debug($string, deleteChars($string, 4, 10));


//debug("arrayGet", arrayGet($config,"TYPES.VIDEO"));

debug();
debug("publish");
$publish = getConfig("_publish");
debugVar("publish");
debug();
debug("publish.site");
$site = getParam("site", $publish["default"]);
$publish = getConfig("_publish.$site");
debugVar("publish");

$byType = getParamBoolean("bytype");

debug();
debug("maxSize");
$maxSize = arrayGet($publish, "image.size");
debugVar("maxSize");
$maxSize = getConfig("_publish.$site.image.size");
debugVar("maxSize");
//should be null
$notfound = getConfig("_publish.$site.ima.size");
debug("notfound", is_null($notfound));

//splitChunks("../2013", "197642876.mp4", 2000000, $chunks);
//debugVar("chunks");
//joinChunks("../2013", "197642876.mp4", false);

debug("USER");
debug("Logged in", is_loggedin());
debug("User", current_user());
debug("Upload ", is_uploader());
debug("Admin ",is_admin());
debug("Local", isLocal());

debug();
debug("CLIENT");
debug("User agent", $_SERVER['HTTP_USER_AGENT']);
debug("iPad", isIpad());
debug("Android", isAndroid());
debug("Kindle", isKindle());
debug("Mobile", isMobile());
debug("Firefox", isFirefox());
debug("IE", isIE());
debug("Chrome", isChrome());
debug("allowJqueryFX", allowJqueryFX());
debug("is FFMPEG enabled", isFfmpegEnabled());

debug();
debug("PATH");
debug("PHP_SELF", $_SERVER['PHP_SELF']);
debug("REQUEST_URI", $_SERVER['REQUEST_URI']);
debug("Current Script Name", currentScriptName());
debug("Current Script Path", currentScriptPath());
debug("FILE",__FILE__);
debug("dirname",dirname(__FILE__));
debug("basename",basename(__FILE__));

$dir = $path;
debug("dirname $dir",dirname($dir));
$dir = dirname($dir);
debug("dirname $dir",dirname($dir));
$dir = dirname($dir);
debug("dirname $dir",dirname($dir));

$path=getPath();
debugVar("path");
$relPath=getRelPath($path);
debugVar("relPath");
$relPath=getDiskPath($path);
debugVar("relPath");
$absPath=diskPathToUrl($path);
debugVar("absPath");
debug("currentDir",realpath(""));
debug("relPath $relPath",realpath($relPath));

debug("App root", pathToAppRoot());
debug("App dir", getAppRootDir());
debug("Data root", pathToDataRoot());

debug("APP_ROOT",  getAppRoot() . " / " . getAbsoluteAppRoot() );
debug("DATA_ROOT", getDataRoot(). " / " . getAbsoluteDataRoot() );
debug("AbsoluteUrl", getAbsoluteUrl($path));
debug("DOCUMENT_ROOT",$_SERVER["DOCUMENT_ROOT"]);

debug();
debug("SEARCH"); //file filters
$search =  array();
$search["type"]=getParam("type");
$search["name"]=getParam("name");
$search["depth"]=getParam("depth",0);
$search["maxCount"]=getParam("count",0);
if(!is_numeric($search["depth"]))
	$search["depth"]=getParamBoolean("depth");
$search["tnDir"]=getParam("tndir");
debugVar("search");

debug("publish", getConfig("_publish.free"));

debug("arrayJoinRecursive", arrayJoinRecursive($config["TYPES"]));
debug("FlattenArray", FlattenArray($config["TYPES"]["VIDEO"]));

debug("arraySearchRecursive:", arraySearchRecursive($config,"mpg"));
debug("arraySearchRecursive CSS,css:", arraySearchRecursive("CSS","css"));

debug("arrayGet", arrayGet($config,"TYPES.VIDEO"));

$tp="IMAGE|AUDIO";
debug("Extensions for types($tp)", getExtensionsForTypes($tp));

debug("isEmptyValue");
$value=array();
$empty=isEmptyValue($value);
debug(jsValue($value), $empty);

$value="";
$empty=isEmptyValue($value);
debug(jsValue($value), $empty);

$value=0;
$empty=isEmptyValue($value);
debug(jsValue($value), $empty);

$value=false;
$empty=isEmptyValue($value);
debug(jsValue($value), $empty);

$value=null;
$empty=isEmptyValue($value);
debug(jsValue($value), $empty);

$bg=findInParent($relPath,".bg.jpg");
debug("Background", $bg);
$stylesheet=findInParent($path,"night.css",true);
debug("Stylesheet", $stylesheet);
//addStylesheet($relPath);
debug();

// $cmdOutput=shell_exec("dir ..\\$path");
// debug("command", $cmdOutput)";
startTimer();

$tagData = loadTagFiles($relPath);
debugVar("tagData", true);

$files = listFiles($relPath, $search);
debug("listFiles Time elapsed", getTimer());
debugVar("files", true);

startTimer();
$dirs=selectDirs($relPath,$files);
debug("selectDirs Time elapsed", getTimer());
debugVar("dirs", true);

startTimer();
$groupedFiles = groupByName($files, $byType);
debug("groupByName Time elapsed", getTimer());

startTimer();
debugVar("groupedFiles", true);

saveMetadataIndex($relPath, $groupedFiles);

$metadataIndex = loadMetadataIndex($relPath);
debugVar("metadataIndex", true);

//$indexFiles=selectFilesByType($files,"DIR|VIDEO|IMAGE");
//debugVar("indexFiles",true);

//$dateIndex=getRefreshedDateIndex($relPath,$indexFiles);	
//debug("Files from $relPath date index", count($dateIndex));
//debugVar("dateIndex",true);
//debug("Constants: ", get_defined_constants(true), true);
$tn=getParam("tn");
if($tn && $dirs)
{
	debug("thumbnails in $tn");
	foreach($dirs as $dir)
	{
		debug("$relPath/$dir", subdirThumbs("$relPath/$dir",$tn));
	}
}

$t1 = getTimer();
debug("Time elapsed", getTimer());
startTimer();

$appRootDir=pathToAppRoot();
$csvFilename=combine($appRootDir,"config","events.csv");
$csvData = readCsvTableFile($csvFilename);
debug("readCsvTableFile $csvFilename rows", count($csvData));
debugVar("csvData",true);

debug("Time elapsed", getTimer());
?> 
