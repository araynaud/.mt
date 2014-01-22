<?php
require_once("../include/config.php");
setContentType("text", "plain");

// action on a MediaFile: delete, move, rotate image, convert, etc.
// accept GET or POST.
// create MediaFile by name
// AJAX response: new MediaFile after action + error or confirmation message

$path=getPath();
$relPath=getDiskPath($path);
$file = getParam('file');
$name = getParam("name");
$mf = MediaFile::getMediaFile();

$defaultTarget=".bad";
$target = getParam('to',$defaultTarget);
$target=combine($path,$target);
$relTarget=getDiskPath($target);

$result=false;
$message="";
if(!$file && !$name)
{
	$message="No file selected.";
}
else if(!$mf)
{
	$message = "File $path/$name does not exist.";
}


// Do action
/*

//pages
UI.goToActionPage('.upload/imageEdit')
UI.goToActionPage('.upload/description')

//action
convert to audio: '.upload/video_convert', {debug:true, to:'stream'}, 'convert'
convert to video: '.upload/video_convert', {debug:true, to:'audio'}, 'convert'

move: UI.confirmFileAction('move','..')
UI.confirmFileAction('move','best')
delete: UI.confirmFileAction('delete')
refresh image: UI.refreshThumbnail(this)
background: UI.confirmFileAction('background')
*/

$response["message"] = $message;
$response["files"] = $mf;
$response["time"] = getTimer();
echo jsValue($response, true);
