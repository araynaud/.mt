<?php
require_once("../include/config.php");
setContentType("text", "plain");

// action on a MediaFile: delete, move, rotate image, convert, etc.
// accept GET or POST.
// create MediaFile by name
// AJAX response: new MediaFile after action + error or confirmation message

//reqPathFile($path, $file);
$path = reqPath();
debugVar("path");
$files = reqParam("name");
$files = explode("|", $files);

$action = reqParam("action");
debugVar("action");

$actions = getConfig("file.actions");
//move/rename options
$to = reqParam("to");
debugVar("to");

$rename = reqParam("rename");

//tag options
$tag = reqParam("tag", $to);

//output options
$indent = reqParam("indent", 1);
$includeEmpty = reqParamBoolean("empty",false);

$album = new Album($path, true);

$messages=array();

foreach ($files as $file)
{
	# code... do action
	$mf = $album->getFileByName($file);
	debugVar("mf");
	// Do action
	$inputFile = combine($path, $file);
	$result=false;
	$message="";
	$parameters=array();
	if(!$file)
		$message="No file selected.";
	else if(!$mf)
	{
		$message = "File $inputFile does not exist.";
	}
	else
		switch ($action)
		{
			case "rename":
				$result = $mf->move($to, $rename);
				$_GET["name"] = $rename;
				unset($_GET["file"]);
				break;
			case "move":
				$result = $mf->move($to, $rename);
				$_GET["path"] = combine($path,$to);
				break;
			case "delete":
				$result = $mf->delete();
				break;
			case "date":
				$result = $mf->setDate($to);
				break;
			case "description":
				$result = $mf->setDescription($to);
				break;
			case "background":
				//TODO: apply .bg to other directory (parent or sub?)
				//copy .ss image file directly if same size exists
				$result = setBackgroundImage($mf->getRelPath(), $file);
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
		$message = "$action: " . ($result ? "success": "fail");

	$messages[$file] = $message;
}

//return MediaFile after action
//should be null for delete and new file for move

//$mf = MediaFile::getMediaFile();
debugVar("mf");

//JSON response
$response["action"] = $action;
$response["parameters"] = $parameters;
$response["result"] = $result;
$response["message"] = $messages;
$response["files"] = $files;
$response["mediaFiles"] = $album->getMediaFiles("IMAGE|VIDEO");
//$response["file"] = $mf;
$response["time"] = getTimer();
echo jsValue($response, $indent, $includeEmpty);
?>