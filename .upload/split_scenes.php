<?php
require_once("../include/config.php");
setContentType("text", "plain");

$path = getPath();
$relPath = getDiskPath($path);
$file=getParam("file");
$debug=getParamBoolean("debug");

createDir($relPath, "split");


splitFilename($file, $name, $ext);
$filename = cleanupFilename($name) . ".$ext";
$inputFile = combine($relPath, $file);
$outputFile = combine($relPath, $filename);
if($file != $filename)
	rename($inputFile, $outputFile);

$output = diskPathToUrl($outputFile);

$cmdoutput = execCommand("ffprobe_scenes $outputFile", false, true, false);	
$frames = json_decode($cmdoutput);
$frames = objToArray($frames, false, false, true);
$frames = $frames["frames"];
writeTextFile("$relPath/scenes.log", $cmdoutput);

$nbFrames = count($frames);
$scenes = array();
$prev = 0;
foreach ($frames as $frame)
{
	$scenes[] = array("from" => $prev, "to" => $frame["pkt_pts_time"] );
	$prev = $frame["pkt_pts_time"];
}
$scenes[] = array("from" => $prev);

$response = array();
addVarsToArray($response, "file filename output nbFrames frames scenes");

//rename to original?
if($file != $filename)
	rename($outputFile, $inputFile);

echo jsValue($response);
?>