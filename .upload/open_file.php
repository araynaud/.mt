<?php
require_once("../include/config.php");

$path=getPath();
$relPath=getDiskPath($path);
$file=getParam("file");
$debug=getParamBoolean("debug");
$program = getParam("program");

startTimer();

setContentType("text", "plain");
$output = openFile($program, $relPath, $file);

//for AJAX: output image file Url when image ready
$jsonResponse=array();
$jsonResponse["file"]=$file;
$jsonResponse["output"]=$output;
$jsonResponse["time"]=getTimer();
echo jsValue($jsonResponse);
?>
