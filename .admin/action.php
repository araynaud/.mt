<?php
require_once("../include/config.php");
setContentType("text", "plain");
session_start(); 
// action on a MediaFile: delete, move, rotate image, convert, etc.
// accept GET or POST.
// create MediaFile by name
// AJAX response: new MediaFile after action + error or confirmation message
$search = getSearchParameters();
debugVar("search");
//for multipe files
if(@$search["name"])
	unset($search["file"]);

$username = session_login();
$user = new User();

$mediaFiles = MediaFile::getMediaFiles($search);
debugVar("mediaFiles", true);

$path = reqPath();

$action = reqParam("action");
debugVar("action");

$actions = getConfig("file.actions");
//move/rename options
$to = reqParam("to");
debugVar("to");

$rename = reqParam("rename");

//output options
$indent = reqParam("indent", 1);
$includeEmpty = reqParamBoolean("empty");
$overallResult = false;
$name = arrayGetCoalesce($search, "name", "file");

$parameters = array();
$messages = array();
$results = array();

if(!$user->hasAccess("edit"))
	$messages[] = "User $username has no access to $path.";
else if(!$name)
	$messages[] = "No file selected.";
else if(!$mediaFiles)
	$messages[] = "File $path / $name does not exist.";
else
{
	$overallResult = true;
	foreach ($mediaFiles as $mf)
	{
		$result=false;
		$message="";
		$name = $mf->getName();
		switch ($action)
		{
			case "rename":
				$result = $mf->move($to, $rename);
				$search["name"] = $rename;
				unset($search["file"]);
				break;
			case "move":
				$result = $mf->move($to, $rename);
				$search["path"] = combine($path, $to);
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
				$result = $mf->setBackground(); 
				break;
			case "addtag":
			case "removetag":
				$state = ($action == "addtag");
				$result = $mf->setTag($to, $state);
				$parameters["tag"] = $to;
				$parameters["state"] = $state;
				break;
			case "refresh":
			default:
				$message="Invalid action $action.";
		}
		if(!$message)
			$message =  "$action $name: " . ($result ? "success": "fail");
		$messages[] = $message;
		$results[] = $result;
		$overallResult = $overallResult && $result;
	}
}

//return MediaFile after action
//should be null for delete and new file for move
$mediaFiles = MediaFile::getMediaFiles($search);

//JSON response
$response["action"] = $action;
$response["parameters"] = $parameters;
$response["result"] = $overallResult;
$response["results"] = $results;
$response["message"] = $messages;
$response["files"] = $mediaFiles;
$response["time"] = getTimer();
echo jsValue($response, $indent, $includeEmpty);
?>