<?php session_start(); 
require_once("../include/config.php");
define("LAST_CHUNK_SUFFIX", ".last.chunk");

startTimer();

$user = current_user();
$format=getParam('format','ajax');

debug("Get request", $_GET);
debug("Post request", $_POST);
debug("Post files", $_FILES);

if(isset($_POST['path']))
	$path=getPath($_POST['path']);
else
	$path=$_GET["path"]; //getPath();

$dataRoot = getRelPath("");
$relPath=getRelPath($path);
$index=@$_POST['index'];

$response=array();
addVarToArray($response,"path");
if($index)
	addVarToArray($response,"index");

	
$tmpFile = $_FILES['file']['tmp_name'];
$fileType = $_FILES['file']['type'];
$filename= $_FILES['file']['name'];

$getcwd=getcwd();
$freeSpace=disk_free_space("/");

//addVarToArray($response,"getcwd");
//addVarToArray($response,"freeSpace");

//addVarToArray($response,"tmpFile");
addVarToArray($response,"fileType");

$uploaded=is_uploaded_file($tmpFile);
$message="OK";
if(!$uploaded)
	$message = "File not found";

//verify file type
if(!is_admin() && strstr($fileType, "image")==false)
	$message="Uploaded file is not an image";

//cleanup file name
$filename=cleanupFilename($filename);

//move file to destination dir
createDir($dataRoot, $path); //depending on user permissions? // username/subdir
$target=combine($relPath, $filename);
$moved=move_uploaded_file($tmpFile, $target);
$filesize = $moved ? filesize($target) : 0;
$maxUploadSize = ini_get("upload_max_filesize");

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
	addVarToArray($response, "joinedFilename");
	addVarToArray($response, "nbChunks");	
}	

if($format=="ajax")
{
	$u=new User();
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