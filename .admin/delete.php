<?php
require_once("../include/config.php");

$path=getPath();
$relPath=getDiskPath($path);
$title = makePathTitle($path);
$file = getParam('file');
$format= getParam('format','html');
$result=false;
$message="";
if(empty($file))
{
	$message="No file selected.";
}
else if(!file_exists("$relPath/$file"))
{
	$message = "File $path/$file does not exist.";
}
else
{
	$message = "deleting file $path/$file...";
	$result=deleteFile($relPath,$file);
	if($result)
		$message .= "File $path/$file deleted.";
}

if($format=="ajax")
{
	echo $message;
	return;
}
?>
<html>
<head>
	<title><?php echo $title ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	<link type="text/css" rel="stylesheet" href="../MediaThingy.css"/>
	<?php addStylesheet($relPath); ?>
</head>
<body>
	<?php displayBackground($relPath); ?>
	<?php echo $message;?>
	<br/>
	<a href="../?path=<?php echo $path?>">back to index</a>		
	<?php if($result) { ?>
	<script type="text/javascript">window.location = "../?path=<?php echo $path?>";</script>
	<?php } ?>
</body>
</html>