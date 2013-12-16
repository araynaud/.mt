<?php
require_once("include/config.php");

$path=getPath();
$relPath=getRelPath($path);

$file=getParam("file","readme.txt");
splitFilename($file,$name,$ext);
$description=readTextFile("$name.txt");
//echo "$relPath/$name.txt";
if(empty($description))
	header("HTTP/1.0 404 Not Found");
else
	echo utf8_encode($description);
?>