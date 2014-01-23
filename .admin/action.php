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
$action = getParam("action");
$actions = getConfig("file.actions");
$to = getParam("to");
$rename = getParam("rename");

$mf = MediaFile::getMediaFile();
debugVar("mf");
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

TODO: tag, add or remove
*/

$result=false;
$message="";
if(!in_array($action, $actions))
	$message="Invalid action $action.";
if(!$file && !$name)
	$message="No file selected.";
else if(!$mf)
{
	$inputFile = combine($path, $file ? $file : $name);
	$message = "File $inputFile does not exist.";
}
else
	switch ($action)
	{
		case "move":
			$result = $mf->move($to, $rename);
			break;
		case "delete":
			$result = $mf->delete();	
			break;
		case "refresh":
		case "background":
		default:
			break;
	}

//JSON response
$response["result"] = $result;
$response["message"] = $message;
$response["file"] = $mf;
$response["time"] = getTimer();
echo jsValue($response, true);
