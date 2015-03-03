<?php
require_once("../include/config.php");
setContentType("text", "plain");

$mf = MediaFile::getMediaFile();
debugVar("mf", true);
$relPath = $mf->getRelPath();
$fileType=strtolower($mf->getFileType());
debugVar("fileType");
debug("time:", getTimer());

$duration = $mf->get("duration");
debug("duration = $duration", formatTime($duration));
$mode = getParam("mode");
$durationMode = startsWith($mode, "duration");

$listFile = getParam("list", $mf->getName());
$outputDir = reqParam("output", $listFile);
$listFile = getFilename($listFile, "csv");
$listFile = combine($relPath, $listFile);
debugVar("listFile");
$playlist = readPlaylistFile($listFile, $durationMode);
debugVar("playlist", true);
$outputDir = combine($relPath, $outputDir);

if(!is_dir($outputDir))
	mkdir($outputDir);

debug("inputFile", $mf->getFilePaths());

$batch = splitTracks($mf->getFilePath(), $playlist, $outputDir);
echo $batch;

debug("time:", getTimer());
?>