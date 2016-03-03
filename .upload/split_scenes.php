<?php
require_once("../include/config.php");
setContentType("text", "plain");

$path = reqPath();
$batch =  reqParamBoolean("batch"); //write batch file, do not convert immediately
$relPath = getDiskPath($path);
$urlPath = diskPathToUrl($relPath);
$file=getParam("file");
$debug=getParamBoolean("debug");

$subdir = createDir($relPath, "split");
debugVar("subdir");

splitFilename($file, $name, $ext);
$name = cleanupFilename($name);
$filename = "$name.$ext";
$inputFile = combine($relPath, $file);
$renamedFile = combine($relPath, $filename);
if($file != $filename)
	rename($inputFile, $renamedFile);

$cmdoutput = execCommand("ffprobe_scenes $renamedFile", false, true, false);	
$frames = json_decode($cmdoutput);
$frames = objToArray($frames, false, false, true);
if(isset($frames["frames"]))
	$frames = $frames["frames"];

$probeTime = getTimer();

$nbFrames = count($frames);
$scenes = array();
$prev = 0;
foreach ($frames as $frame)
{
	$scenes[] = array("from" => $prev, "to" => $frame["pkt_pts_time"], "key_frame" => $frame["key_frame"] );
	$prev = $frame["pkt_pts_time"];
}
if($nbFrames)
	$scenes[] = array("from" => $prev);

$nbDigits = strlen($nbFrames);
$batch = "";
foreach ($scenes as $i => &$scene)
{
	$ipad = zeroPad($i, $nbDigits);
	$outname = $name . "_$ipad.$ext";
	$outpath = combine($relPath, "split", $outname);
	$cmd = makeCommand("ffmpeg_split_to [0] [1] [2] [3]", $renamedFile, $outpath, @$scene["from"], @$scene["to"]);
	if($batch)
		$batch .= "$cmd\n";
	else
		$cmdoutput = execCommand($cmd, false, true, false);	
	$outUrl = combine($urlPath, "split", $outname);
	$duration = isset($scene["to"]) ? $scene["to"] - $scene["from"] : 0;
	addVarsToArray($scene, "duration cmd outname outUrl");
}

if($batch)
{
	writeJsonFile("$relPath/$name.scenes.json", $scenes);
	writeTextFile("$relPath/$name.bat", $batch);
}

//rename to original?
//if($file != $filename)
//	rename($renamedFile, $inputFile);

$response = array();
$time = getTimer();
addVarsToArray($response, "file filename probeTime time nbFrames scenes");

echo jsValue($response);
?>