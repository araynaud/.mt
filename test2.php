<?php
error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);
ini_set('display_errors', '1');
//error_reporting(E_PARSE);
$configFile="include/config.php";
if(file_exists("../$configFile"))	$configFile = "../$configFile";
require_once($configFile);
debug("Time elapsed", getTimer(true));
debug();
debug();
startTimer();

$search = getSearchParameters();
debugVar("search");

$path=arrayGet($search, "path");
$relPath=getRelPath($path);
debugVar("relPath");
debug("is_dir $relPath", is_dir($relPath));
$relPath=getDiskPath($path);
debugVar("relPath");
debug("is_dir $relPath", is_dir($relPath));

$absPath=diskPathToUrl($path);
debugVar("absPath");
debug("currentDir",realpath(""));
debug("relPath $relPath",realpath($relPath));

$tp=@$search["exts"];
debug("Extensions", $tp, "print_r");

/*
$indexFiles = scandir($relPath);
$indexFiles = testFunctionResult("selectFilesByType", $indexFiles, $search["type"]);
debugVar("indexFiles", true);
*/

//$files = testFunctionResult("listFilesDir", $relPath, $search);
//$files = testFunctionResult("listFilesRecursive", $relPath, $search);
$files = listFilesRecursive($relPath, $search);
debugVar("files", true);

//$dirs = testFunctionResult("selectDirs", $relPath, $files);
//debugVar("dirs", true);

//$groupedFiles = testFunctionResult("groupByName", $relPath, $files, false);


//$mf = MediaFile::getMediaFiles();
//debugVar("mf", true);

//$mf = MediaFile::getMediaFile();
//debugVar("mf", true);

debugVar("functionStats");

debug("Time elapsed", getTimer(true));
?> 
