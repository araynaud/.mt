<?php
require_once("../include/config.php");

// action on a MediaFile: delete, move, rotate image, convert, etc.
// accept GET or POST.
// create MediaFile by name
// AJAX response: new MediaFile after action + error or confirmation message

$path=getPath();
$relPath=getDiskPath($path);

//create album with name = filename
//get MediaFile

$title = makePathTitle($path);
$file = getParam('file');
$lastFile = getParam('last');
$format= getParam('format','html');
//target folder
$defaultTarget=".bad";
$target = getParam('to',$defaultTarget);
$target=combine($path,$target);
$relTarget=getDiskPath($target);

$message="";
$message = "from " . realpath($path) . " to " .  realpath($target) . ". ";

$result=false;
if(empty($file))
{
	$message="No file selected.";
}
else if(!file_exists("$relPath/$file"))
{
	$message = "File $path/$file does not exist.";
}
else if(empty($lastFile) || $lastFile==$file)
{
	$result=moveMediaFile($relPath,$file,$relTarget);
	$message = "File $path/$file moved to $target.";
}
else
{
	//get files starting with $file
	//loop from file to lastFile
	$type=getFileType(combine($relPath,$file));	
	$files=listAllFiles($relPath);
	$files=selectFilesByType($files,$type);
	//$pos=array_search($file,$files);
	foreach($files as $f)
	{
		if( $f >= $file && $f <= $lastFile) 
		{
			moveMediaFile($relPath,$f,$relTarget);
			$message .= "File $path/$f moved to $target.<br/>";
		}
	} 
}

echo $message;
return;
