<?php
error_reporting(E_ERROR | E_PARSE | E_WARNING | E_NOTICE);

require_once("../include/config.php");

//original file
$path=getPath();
$relPath=getRelPath($path);
$file = getParam('file');
$format= getParam('format','html');

//TODO: apply .bg to other directory (parent or sub?)
setBackgroundImage($relPath,$file);

$message= "File $path/$file set as background.";

if($format=="ajax")
{
	echo $message;
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
	
<script type="text/javascript">
	//window.location = "../?path=<?php echo $path?>";
</script>
</body>
</html>