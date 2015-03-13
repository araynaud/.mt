<?php
// video manipulation function calling FFMPEG
function isFfmpegEnabled()
{
	return getExePath() != false;
}

function getExePath($exe="FFMPEG", $key="_FFMPEG")
{
	global $config;
		
	if(!isset($config[$key])) return false;
	$exePath=combine(getConfig("$key.PATH"), getConfig("$key.$exe"));

debug("getExePath($exe, $key)", $exePath);

	if(!file_exists($exePath)) return false;
	return $exePath;
}

function quoteFilename($filename)
{
	if(contains($filename," "))
		$filename = escapeshellarg($filename);
	if(isWindows())
		$filename = str_replace("/", "\\", $filename);

	//TODO if windows, replace / by \\ in filenames
	return $filename;
}

//pass cmd and its args
//for each argument: quote if necessary
function makeCommand()
{
	$args = func_get_args();
	$cmd = array_shift($args);
	foreach($args as $n => $param)
	{
		$cmd = str_replace("[$n]", quoteFilename($param), $cmd);
	}
	//TODO: quote filenames only if [f$n]
	//replace unused [$n] arguments with empty
	return $cmd;
}

function execCommand($cmd, $background=false, $toString=true, $redirectError=true)
{
	if($redirectError)
		$cmd .= " 2>&1";
	if($background && isWindows())
		$cmd="start \"proc title\" $cmd";
	else if($background && isUnix())
		$cmd .= " &";
	debugText("execCommand", $cmd);
	exec($cmd, $output, $cmdReturn);
	if($output && $toString)
		$output=implode("\n", $output);
	debugText("Output", $output, true);
	debug("Return", $cmdReturn);
	debug();
	return $output;
}

function customError($errno, $errstr)
{
	echo "Custom Error: [$errno] $errstr\n";
}

function getMediaFileInfo($relPath, $file="")
{	
	$filePath = combine($relPath, $file);
	$fileType=getFileType($filePath);
	if($fileType=="IMAGE")
	{
		$data = getExifData($filePath);
		getImageInfo($filePath, null, $data);
		return $data;
	}

	$ffprobe=getExePath("PROBE");
	$cmd = makeCommand("[0] -i [1] -show_format -show_streams", $ffprobe, $filePath);
	$output = execCommand($cmd, false, false);	
	$data = parseFfprobeMetadata($output);
	return $data;
}

function getMediaFileInfoFormat($relPath, $file="", $format)
{	
	$ffprobe=getExePath("PROBE");
	$cmd = makeCommand("[0] -i [1] -show_format -show_streams -of [2]", $ffprobe, combine($relPath, $file), $format);
	$output = execCommand($cmd, false, true);	
	return $output;
}

//from ffprobe output
function parseFfprobeMetadata($output)
{
	$metadata=array();
	$sections=array();
	if(!$output) return $sections;
	foreach($output as $item)
	{
		if (preg_match('/^\[\/(.+)\]$/s', $item, $meta)) //end section [/STREAM]
		{
			$meta=$meta[1];
			if(!isset($sections[$meta]))
				$sections[$meta]=array();

			$sections[$meta][]=$metadata;
		}
		else if (preg_match('/^\[(.+)\]$/s', $item, $meta)) //start section [STREAM]
		{
			$meta=$meta[1];
			$metadata=array();
		}
		else if (preg_match('/(.+):(.+)=(.+)/s', $item, $meta))  //TAG:minor_version=0
		{
			$metadata[strtolower($meta[1])][strtolower($meta[2])] = $meta[3];
		}
		else if (preg_match('/(.+)=(.+)/s', $item, $meta)) 		//minor_version=0
		{
			$key=strtolower($meta[1]);
			if(!isset($metadata[$key])) 
				$metadata[strtolower($key)] = $meta[2];
		}
	}

	foreach($sections as $name => $section)
	{
debug("$name", count($section));
		//if section has only 1 element, $section=$section[0]
		if(count($section)==1)
			$sections[$name]=$section[0];
	}
	
	return $sections;
}


function parseFfmpegMetadata($output)
{
	$metadata=array();
	foreach($output as $item)
	{
		if (preg_match('/((\d{2,4})x(\d{2,4}))/s', $item, $res))
		{
			$metadata["width"] = $res[2];
			$metadata["height"] = $res[3];
		}
		if (preg_match('/Duration: ((\d+):(\d+):(\d+))/s', $item, $time))
		{
			$metadata["duration"] = ($time[2] * 3600) + ($time[3] * 60) + $time[4];
		}
		if (preg_match('/(.+): (.+)/s', $item, $meta))
		{
			if(contains($meta[2],", "))
				$metadata[trim($meta[1])] = explode(", ", trim($meta[2]));
			else
				$metadata[trim($meta[1])] = trim($meta[2]);
		}
	}
	return 	$metadata;
}

function getVideoDuration($output)
{
	foreach($output as $line)
		if (preg_match('/Duration: ((\d+):(\d+):(\d+))/s', $line, $time))
		{
			$total = ($time[2] * 3600) + ($time[3] * 60) + $time[4];
			return $total;
		}
	return 0;
}

function randomSecond($duration)
{
	return rand(1, ($duration - 1));
}

function makeVideoThumbnail($relPath, $video, $size, $subdir=".tn", $ext="jpg")
{	
	$ffmpeg=getExePath();
	// where ffmpeg is located, such as /usr/sbin/ffmpeg
	// the output image
	$imageDir = combine($relPath, $subdir);
	//create thumbs folder if necessary
	if (!empty($subdir) && !file_exists($imageDir))
		mkdir ($imageDir, 0700);

	$image = getFilename($video, $ext);
	$image = combine($imageDir, $image);	
	// the input video file
	$video = combine($relPath, $video);

	if (file_exists($image))
		unlink($image);

	// get the screenshot
	$cmd = "[0] -i [1] -an -t 00:00:01 -ss 1 -r 1 -y -vf scale=[3]:-1 [2]";
	$cmd = makeCommand($cmd, $ffmpeg, $video, $image, $size);
	$output = execCommand($cmd, true); //exec in background
	return $image;
}

//$cmd = "$ffmpeg -i $video -deinterlace -an -ss $second -t 00:00:01 -r 1 -y -vcodec mjpeg -f mjpeg $image";
//$cmd = "$ffmpeg -i $video -deinterlace -an -ss $second -t 00:00:01 -r 1 -y -vf scale=300:-1 -vcodec mjpeg -f mjpeg $image";
//$cmd = "$ffmpeg -i $video -an -t -ss $second -t 00:00:01 -r 1 -y -vf scale=300:-1 $image";
//$cmd = "$ffmpeg -i $video -an -t 00:00:01 -ss 2 -r 1 -y -vf scale=300:-1 $image";
//$cmd = "$ffmpeg -i $video -vf scale=300:-1 $image";

function getVideoProperties($relPath, $file="", $convertTo="")
{
	$metadata = getMediaFileInfo($relPath, $file);
//debug("getMediaFileInfo", $metadata, true);
	$data = array();

	$nbStreams = arrayGet($metadata, "FORMAT.nb_streams");
	//Find video stream: 0 or 1: with codec_type": "video", width and height > 0
	$streams = array();
	$codecType = arrayGetCoalesce($metadata, "STREAM.0.codec_type", "STREAM.codec_type");
	if($codecType) $streams[$codecType]=0;
debug("codecType 0", $codecType);

	$codecType = arrayGet($metadata, "STREAM.1.codec_type");
	if($codecType) $streams[$codecType]=1;
debug("codecType 1", $codecType);

	$audio=arrayGet($streams, "audio");
	$video=arrayGet($streams, "video");

	//$data["source"] = arrayGet($metadata, "source");
	$data["duration"] = arrayGet($metadata, "FORMAT.duration");
	$data["width"] = arrayGetCoalesce($metadata, "STREAM.$video.width", "STREAM.width");
	$data["height"] = arrayGetCoalesce($metadata, "STREAM.$video.height", "STREAM.height");
	$display_aspect_ratio = fractionValue(arrayGet($metadata, "STREAM.0.display_aspect_ratio"));
//	$data["ratio"] = $display_aspect_ratio;

	//$data["streams"] = $streams;
	$data["frameRate"] = fractionValue(arrayGet($metadata, "STREAM.$video.avg_frame_rate"));
	$data["frameRateInt"] = round($data["frameRate"]);

	if(!$convertTo) return $data;

	if($display_aspect_ratio)
	{
		$data["height2"] = $data["width"] / $display_aspect_ratio;
		$data["width2"] = $data["height"] * $display_aspect_ratio;
	}
	$videoBitrate = getConfig("_FFMPEG.convert.$convertTo.video_bitrate");
	$audioBitrate = getConfig("_FFMPEG.convert.$convertTo.audio_bitrate");
	$data["estimatedFileSize"] = estimateFileSize($data["duration"], $videoBitrate, $audioBitrate);
	return $data;
}

function fractionValue($fraction)
{
	$fr = explode(":", $fraction);
	if(count($fr) == 1)
		$fr = explode("/", $fraction);
	if(count($fr) == 1 || !$fr[1])
		return $fr[0];
	return $fr[0] / $fr[1];
}

function estimateFileSize($duration, $videoBitrate, $audioBitrate=0)
{
debug("estimateFileSize", "($duration, $videoBitrate, $audioBitrate)");	
	$bps = ($videoBitrate + $audioBitrate) / 8 * 1000; //kbits to bytes / sec
	return $bps * $duration;
}

function convertVideo($relPath, $inputFile, $to, $size=0, $start=0, $end=0)
{
//calculate height from display_aspect_ratio
	$convert = getConfig("_FFMPEG.convert.$to");
debug("convert $to", $convert);
	if(!$convert) return false;

//if input is MPEG or MPEG2 : deinterlace with yadif filter?
	$ffmpeg=getExePath();		// where ffmpeg is located, such as /usr/sbin/ffmpeg
	$prop = getVideoProperties($relPath, $inputFile, $to);
debug("getVideoProperties", $prop, true);
	if(!$size)
		$size = $convert["height"];
	$size = min($prop["height"], $size); //resize only if input video is larger than $size

	$inputExt = strtolower(getFilenameExtension($inputFile));
	$outputExt = arrayGetCoalesce($convert, "$inputExt.format", "format");

	$outputFile = getFilename($inputFile, $outputExt);
	$outputFilename = getFilename($outputFile);	
	$outputFilePath = combine($relPath, $outputFile);	
	// the input video file
	$inputFilePath = combine($relPath, $inputFile);

	debug("convertVideo $inputFile", $outputFile);
	debug("input ", realpath($inputFilePath));
	debug("output", realpath($outputFilePath));
	if(realpath($inputFilePath) == realpath($outputFilePath)) 
		return false;

	if (file_exists($outputFilePath))
		unlink($outputFilePath);
//use metadata display_aspect_ratio to calculate size
//round to multiples of 2 or 4

	$scriptDir = getConfig("_FFMPEG.scripts");
debug("scriptDir", $scriptDir);
	$scriptName = arrayGetCoalesce($convert, "$inputExt.script", "script");

//default extension: .bat / .sh	
	$ext = isUnix() ? "sh" : "bat";
	if(getFilenameExtension($scriptName)=="")
		$scriptName = getFilename($scriptName, $ext);

	$script = combine(pathToAppRoot(), $scriptDir, $scriptName);
debug("script", $script);
	$script = realpath($script);
debug("script", $script);
if(!$script) return;

	$cmd = "$script [0] [1] [2] [3] [4] [5]";
	$cmd = makeCommand($cmd, $relPath, $inputFile, $outputFilename, $size, $start, $end);
debug("command", $cmd);
	$output = execCommand($cmd, false); //exec in background
	
	if(file_exists($outputFilePath) && filesize($outputFilePath)==0) 
		unlink($outputFilePath);
	return $outputFilePath;
}

function convertVideoProgress($relPath, $inputFile, $to)
{
	$prop = getVideoProperties($relPath, $inputFile, $to);

	$convert = getConfig("_FFMPEG.convert.$to");
debug($to, $convert);
	if(!$convert) return false;

	$prop = getVideoProperties($relPath, $inputFile, $to);
debug("getVideoProperties", $prop, true);

	$outputFile = getFilename($inputFile, $convert["format"]);
	$outputFile = combine($relPath, $outputFile);	
	$tmpFile = getTempVideoFilename($inputFile, $convert["format"]);
	$tmpFile = combine($relPath, $tmpFile);
	$prop["tmpFile"] = $tmpFile;
	$prop["outputFile"] = $outputFile;
	$prop["outputFileSize"] = @filesize($outputFile);
	if(!$prop["outputFileSize"]) $prop["outputFileSize"] = @filesize($tmpFile);
	if($prop["estimatedFileSize"])
		$prop["progress"] = 100 * $prop["outputFileSize"] / $prop["estimatedFileSize"];
	return $prop;
}

function getTempVideoFilename($filename, $format="")
{
//set tmpFile=%1\\.tmp_%3.mp4
	if($format)
		$filename = getFilename($filename, $format);
	return ".tmp_$filename";	
}

//mkv or MTS to mp4: if video is H264, remux without re-encoding.
function remuxVideo($relPath, $video, $format)
{
	$ffmpeg=getExePath();		// where ffmpeg is located, such as /usr/sbin/ffmpeg

	$outputFile = getFilename($video, $format);
	$outputFile = combine($relPath, $outputFile);	
	// the input video file
	$video = combine($relPath, $video);

	if (file_exists($outputFile))
		unlink($outputFile);

	// get the output mp4 video
	//-acodec copy
	$cmd = "[0] -i [1] -vcodec copy -ab 128k -ac 2 [2]";

	$cmd = makeCommand($cmd, $ffmpeg, $video, $outputFile);
	$output = execCommand($cmd, false); //exec in background
	return $outputFile;
}

function makeSplitCommand($inputFile, $track, $outputDir="")
{
	$ext = getFilenameExtension($inputFile);
	$filename = getFilename($track['title'], $ext);
	if(isset($track['number']))
		$filename = $track['number'] . " $filename";
	$filename = combine($outputDir, $filename);

	$cmd = makeCommand("ffmpeg_split_to [0] [1] [2] [3]", $inputFile, $filename, @$track["start"], @$track["end"]);
	return $cmd;
}

function splitTracks($inputFile, $tracks, $outputDir="split")
{
	$batch="";
	foreach ($tracks as $i => $track)
	{
		$cmd = makeSplitCommand($inputFile, $track, $outputDir);
		$batch .= "$cmd\n";
		$output = execCommand($cmd);
	}

	return $batch;
}

/*
convert to mp4 no resize:
%ffmpeg% -i %1\\%2 -b 600k %1\\%3.mp4

convert to mp4 + no resize:
%ffmpeg% -i %1\\%2 -s 720x404 -b 800k %1\\%3_tmp.mp4
rem %mp4box% -hint %1\\%3.mp4
%qtfs% %1\\%3_tmp.mp4 %1\\%3.mp4
del %1\\%3_tmp.mp4
*/

//mkv to mp4: if video is H264, remux without re-encoding.
//"[0] -i [1] -vcodec copy -acodec copy [2]"


/*
Video options:
-vframes number     set the number of video frames to record
-r rate             set frame rate (Hz value, fraction or abbreviation)
-s size             set frame size (WxH or abbreviation)
-aspect aspect      set aspect ratio (4:3, 16:9 or 1.3333, 1.7777)

"C:\Program Files\ffmpeg\bin\ffmpeg" -i ..\..\Pictures\F1\2012_Bresil_Dimanche_F1.mkv -vcodec copy -acodec copy output.mp4

qt-FS on mp4:
qt-faststart.exe ..\..\Pictures\f1\2012_Bresil_Dimanche_F1.mp4 df1.mp4
*/

function isIrfanViewEnabled()
{
	$exePath=getExePath("EXE","_IRFANVIEW");
	return $exePath != false && getConfig("_IRFANVIEW.enabled");
}


function jpegLosslessRotate($relPath, $file="", $transform)
{	
	global $config;
		
	$exePath=getExePath("EXE","_IRFANVIEW");
	if(!$exePath) return false;	

	debug("IrfanView config",$config["_IRFANVIEW"]);
	if(!isset($config["_IRFANVIEW"]["JPG_TRANSFORMS"])) 
		$config["_IRFANVIEW"]["JPG_TRANSFORMS"] = array_flip($config["_IRFANVIEW"]["JPG_ROTATE"]);
	$trans = $config["_IRFANVIEW"]["JPG_TRANSFORMS"];
	if(isset($trans[$transform]))
		$transform = $trans[$transform];

	debug("transform", $transform);

$file = combine($relPath, $file);
$file = realpath ($file);

	$cmd = makeCommand("[0] [1] /jpg_rotate=([2],1,0,1,0,0,0,0) /cmdexit", $exePath, $file, $transform);
	$output = execCommand($cmd, false, false, false);	
	return $output;
}

//	i_view32.exe c:\test.bmp /resize=(100,100) /resample /aspectratio /convert=d:\$N_thumb.jpg
function jpegResize($relPath, $file, $saveDir, $size)
{	
	$exePath=getExePath("EXE","_IRFANVIEW");
	if(!$exePath || !$saveDir) return false;	

debug("relPath",$relPath);
debug("file",$file);
debug("saveDir",$saveDir);
debug("size",$size);

	$outputFile = combine($relPath, $saveDir, $file);
	$file = combine($relPath, $file);

debug("input",$file);
debug("output",$outputFile);
	$cmd = makeCommand("[0] [1] /resize=([2],[2]) /resample /aspectratio /jpgq=60 /convert=[3]", $exePath, $file, $size, $outputFile);
debug("command",$cmd);
	$output = execCommand($cmd, false, false, false);	
	return $output;
}

function openFile($program, $relPath, $file="")
{
	$program = "_" . strtoupper($program);
	$exePath=getExePath("EXE", $program);
	if(!$exePath) return false;	

	$inputFile = combine($relPath, $file);
	$cmd = "[0] [1]";
	$cmd = makeCommand($cmd, $exePath, $inputFile);
debug("command", $cmd);
	$output = execCommand($cmd, false); //exec in background
	return $output;
}

?>