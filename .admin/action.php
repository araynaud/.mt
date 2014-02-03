<?php
require_once("../include/config.php");
setContentType("text", "plain");

// action on a MediaFile: delete, move, rotate image, convert, etc.
// accept GET or POST.
// create MediaFile by name
// AJAX response: new MediaFile after action + error or confirmation message

$path=getPath();
$file = getParam("file");
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
$inputFile = combine($path, $file ? $file : $name);
$result=false;
$message="";
$parameters=array();
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
//			$_GET["path"] = combine($path,$to);
			break;
		case "delete":
			$result = $mf->delete();
			break;
		case "description":
			$result = $mf->setDescription($to);
			break;
		case "background":
			//TODO: apply .bg to other directory (parent or sub?)
			//copy .ss image file directly if same size exists
			$result = setBackgroundImage($relPath, $file);
			break;
		case "addtag":
		case "removetag":
			$state = ($action == "addtag");
			$result = $mf->setTag($tag, $state);
			$parameters["tag"] = $tag;
			$parameters["state"] = $state;
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
//should be null for delete and new file for move

$mf = MediaFile::getMediaFile();
debugVar("mf");

//JSON response
$response["action"] = $action;
$response["parameters"] = $parameters;
$response["result"] = $result;
$response["message"] = $message;
$response["file"] = $mf;
$response["time"] = getTimer();
echo jsValue($response, $indent, $includeEmpty);
