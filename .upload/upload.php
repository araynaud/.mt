<?php session_start(); 
require_once("../include/config.php");
define("CHUNK_SUFFIX", ".chunk");
define("LAST_CHUNK_SUFFIX", ".last.chunk");

startTimer();

$username = current_user();
$user = new User();

debugVar("user",true);
$access = $user->getAccessLevel();

$format=getParam("format", "ajax");

debug("Request", $_REQUEST);
debug("GET request", $_GET);
debug("POST request", $_POST);
debug("POST files", $_FILES, true);

$path = reqParam("path");
$dataRoot = getDiskPath("");
$relPath = getDiskPath($path);

$index = postParam("index");
$fileDate = postParam("fileDate");

$response=array();
addVarToArray($response,"path");
if($index)
	addVarToArray($response,"index");

if(empty($_FILES))
{
	$message =  "No File uploaded.";
	addVarToArray($response, "message");
	$response["time"] = getTimer();
	echo jsValue($response,true);
	return;
}
$tmpFile = $_FILES["file"]["tmp_name"];
$mimeType = $_FILES["file"]["type"];
$filename= $_FILES["file"]["name"];



$name = getFilename($filename);
$fileType = postParam("type");
$metadata = postParam("metadata");
$tags = postParam("tags");	

$getcwd=getcwd();
$freeSpace=disk_free_space("/");

//addVarToArray($response,"getcwd");
//addVarToArray($response,"freeSpace");

//addVarToArray($response,"tmpFile");
addVarToArray($response,"mimeType");

$uploaded=is_uploaded_file($tmpFile);
$message="OK";
if(!$uploaded)
	$message = "File not found.";


//verify dir access
if(!$access || $access=="read")
{
	if(!$access) $access = "no";
	$message = "$username has $access access to $path.";
	addVarToArray($response, "message");
	$response["time"] = getTimer();
	echo jsValue($response,true);
	return;
}

//verify file type
if(!is_admin() && strstr($mimeType, "image")==false)
	$message="Uploaded file is not an image";

//cleanup file name
$filename=cleanupFilename($filename);

//move file to destination dir
createDir($dataRoot, $path); //depending on user permissions? // username/subdir
$target=combine($relPath, $filename);
$moved=move_uploaded_file($tmpFile, $target);
$filesize = $moved ? filesize($target) : 0;
$maxUploadSize = ini_get("upload_max_filesize");
setFileDate($target, $fileDate);
addVarToArray($response, "target");
//addVarToArray($response, "moved");
addVarToArray($response, "filesize");
//addVarToArray($response, "maxUploadSize");
debug("moving to $target", $moved);
if(!$moved)
	$message = "Cannot move file into $relPath";
else
	$message =  "File uploaded.";
addVarToArray($response, "message");

if(endsWith($filename, LAST_CHUNK_SUFFIX))
{
	$joinedFilename = substringBefore($filename, LAST_CHUNK_SUFFIX);
	$nbChunks = joinChunks($relPath, $joinedFilename);
	$joinedFilePath=combine($relPath, $joinedFilename);
	setFileDate($joinedFilePath, $fileDate);
	addVarToArray($response, "joinedFilename");
	addVarToArray($response, "nbChunks");	
	$name = getFilename($joinedFilename);
}	

$isFileComplete = endsWith($filename, LAST_CHUNK_SUFFIX) || ! endsWith($filename, CHUNK_SUFFIX);

//add metadata from posted file to local index
if($isFileComplete)
{
	if($metadata)
	{
		$metadata = parseCsvTable($metadata, 0);
		addToMetadataIndex($relPath, $fileType, $name, $metadata);
	}	

	if($tags)
	{
		$tags = explode(";", $tags);
		foreach ($tags as $tag) 
			saveFileTag($relPath, $name, $tag, true);
	}	
}

if($format=="ajax")
{
	$response["time"] = getTimer();
	echo jsValue($response,true);
	return;
}
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	<link type="text/css" rel="stylesheet" href="../MediaThingy.css"/>
	<?php addStylesheet($relPath); ?>
</head>
<body>
	<?php displayBackground($relPath); ?>
	<a href="../?path=<?php echo $path?>">index</a>

	<?php echo jsValue($response,true); ?>
	
	<script type="text/javascript">
		window.location = "../?path=<?php echo $path?>";
	</script>
</body>
</html>