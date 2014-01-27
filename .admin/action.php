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
//move/rename options
$to = getParam("to");
$rename = getParam("rename");

//tag options
$tag = getParam("tag", $to);

//output options
$indent = getParam("indent", 1);
$includeEmpty = getParamBoolean("empty");

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

TODO: tag: add or remove
*/
$inputFile = combine($path, $file ? $file : $name);
$result=false;
$message="";
if(!$file && !$name)
	$message="No file selected.";
else if(!$mf)
{
	$message = "File $inputFile does not exist.";
}
else
	switch ($action)
	{
		case "rename":
		case "move":
			$result = $mf->move($to, $rename);
			break;
		case "delete":
			$result = $mf->delete();
			break;
		case "background":
			//TODO: apply .bg to other directory (parent or sub?)
			//copy .ss image file directly if same size exists
			$result = setBackgroundImage($relPath, $file);
			break;
		case "addtag":
		case "removetag":
			$result = $mf->tag($tag, ($action == "addtag"));
			break;
		case "refresh":
		default:
			$message="Invalid action $action.";
	}

if(!$message)
{
	$name = $mf->getName();
	$message =  "$action $name: " . ($result ? "success": "fail");
}

//return MediaFile after action
//should be null for delete and move
$mf = MediaFile::getMediaFile();
debugVar("mf");

//JSON response
$response["result"] = $result;
$response["message"] = $message;
$response["file"] = $mf;
$response["time"] = getTimer();
echo jsValue($response, $indent, $includeEmpty);
