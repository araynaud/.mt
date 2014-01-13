<?php
require_once("../include/config.php");

$path=getPath();
$relPath=getDiskPath($path);
$title = makePathTitle($path);
$file = getParam('file');
$lastFile = getParam('last');
$format= getParam('format','html');
//target folder
$defaultTarget=".bad";
$target = getParam('to',$defaultTarget);
$target=combine($path,$target);
$relTarget=getDiskPath($target);

$message="";
$message = "from " . realpath($path) . " to " .  realpath($target) . ". ";

$result=false;
if(empty($file))
{
	$message="No file selected.";
}
else if(!file_exists("$relPath/$file"))
{
	$message = "File $path/$file does not exist.";
}
else if(empty($lastFile) || $lastFile==$file)
{
	$result=moveMediaFile($relPath,$file,$relTarget);
	$message = "File $path/$file moved to $target.";
}
else
{
	//get files starting with $file
	//loop from file to lastFile
	$type=getFileType(combine($relPath,$file));	
	$files=listAllFiles($relPath);
	$files=selectFilesByType($files,$type);
	//$pos=array_search($file,$files);
	foreach($files as $f)
	{
		if( $f >= $file && $f <= $lastFile) 
		{
			moveMediaFile($relPath,$f,$relTarget);
			$message .= "File $path/$f moved to $target.<br/>";
		}
	} 
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
	<script type="text/javascript">//window.location = "../?path=<?php echo $path?>";</script>
	<?php } ?>
</body>
</html>