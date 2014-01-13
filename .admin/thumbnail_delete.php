<?php 
require_once("../include/config.php");

$path=getPath();
$relPath=getDiskPath($path);
$format= getParam('format','html');
$title = makePathTitle($path);
$subfolder=getParam("subfolder",".tn");

//list thumbnails from all picture files
$files=listAllFiles($relPath);
$files=selectFilesByType($files,"IMAGE");
//list thumbnails in subfolder
//keep video thumbs
//$files=array_diff($files,$videos);
$result=true;
$message="";
foreach($files as $file)
{
	$del=findThumbnail($relPath, $file, $subfolder);
	if(!$del) continue;
	$message.="deleting: $del\n";
	$result = $result && unlink($del);
}
if($format=="ajax")
{
	echo $message;
	return;
}
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title><?php echo $title ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	<link type="text/css" rel="stylesheet" href="../MediaThingy.css"/>
	<?php addStylesheet($relPath); ?>
</head>
<body>
	<?php displayBackground($relPath); ?>
	<div class="text">
	<?php echo $message;?>
	</div>
	<a href="../?path=<?php echo $path?>">back to index</a>		
	<?php if($result) { ?>
	<script type="text/javascript">//window.location = "../?path=<?php echo $path?>";</script>
	<?php } ?>
</body>
</html>