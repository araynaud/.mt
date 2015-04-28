<?php
error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);
session_start();
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
debug();
$user = new User();
debugVar("user", true);
debug("\t$path admin", $user->hasAccess("admin"));
debug("\t$path edit", $user->hasAccess("edit"));
debug("\t$path read", $user->hasAccess());
debug("\t$path level", $user->getAccessLevel());
debug();

$groups=getConfig("groups");
debugVar("groups", true);

$dirAccess = getConfig("access");
debugVar("dirAccess", true);

//$files = testFunctionResult("listFilesDir", $relPath, $search);
$files = testFunctionResult("listFilesRecursive", $relPath, $search);
//$configFiles = testFunctionResult("findFile", $relPath, ".config.csv", 1);
//debugVar("configFiles", true);
$dirs = testFunctionResult("selectDirs", $relPath, $files);
debugVar("dirs", true);

foreach ($dirs as $subdir)
{
	debug("\t$subdir", $user->getAccessLevelTo($relPath,$subdir));
//	debug("\t$subdir read", $user->hasAccessTo($relPath,$subdir));
//	debug("\t$subdir edit", $user->hasAccessTo($relPath,$subdir, "edit"));
//	debug("\t$subdir admin", $user->hasAccessTo($relPath,$subdir,"admin"));
}

//$groupedFiles = testFunctionResult("groupByName", $relPath, $files, false);


//$mf = MediaFile::getMediaFiles();
//debugVar("mf", true);

//$mf = MediaFile::getMediaFile();
//debugVar("mf", true);

debugVar("functionStats");

debug("Time elapsed", getTimer(true));
?> 
