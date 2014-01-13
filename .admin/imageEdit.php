<?php
require_once("../include/config.php");
session_start(); 

$path=getPath();
$relPath=getDiskPath($path);
$file=getParam("file");
$filePath=combine($relPath,$file);
$imageUrl= "../image.php?" . $_SERVER["QUERY_STRING"];

deleteTempImage();
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	<link type="text/css" rel="stylesheet" href="../MediaThingy.css"/>
	<?php addStylesheet($relPath); ?>
	<script type="text/javascript" src="../js/jquery.js"></script>
	<script type="text/javascript" src="../js/extensions.js"></script>
	<script type="text/javascript" src="../js/index.js"></script>
	<script type="text/javascript" src="../js/imageEdit.js"></script>
	<script type="text/javascript">
	var imageParams=<?php echo jsValue($_GET,false);?>;
	imageParams.selectMode=0;
	//imageParams.edit=true;

	$(document).ready(function()
	{
		makeBackgroundGradients();		
		$("img#image,div#selectZone").click(imageClick);
		$("img#image,div#selectZone").mousemove(imageSelect);
		$("input:text").change(getFieldValue);
		$("input:checkbox").click(getFieldValue);
		$("input:button").click(selectTool);
		$("input:text").change();
	});
	</script>
</head>
<body>
<?php displayBackground($relPath);?>
	<div id="toolbar">
		<a href="./?path=<?php echo $path?>"><img src="../icons/home.gif"/></a>
		<a id="imageLink" href="../image.php?<?php echo  $_SERVER["QUERY_STRING"]?>">view</a>
		&nbsp;
		<input type="button" id="bt_select" value="Select"/>
		<input type="button" id="bt_crop" value="Crop" class="immediate"/>
		&nbsp;
		Size: <input type="text" id="tb_size" class="numericField"/>
		<input type="button" id="bt_resize" value="Resize" class="immediate"/>
		&nbsp;
		Angle: <input type="text" id="tb_rotate" class="numericField"/>
		<input type="button" id="bt_rotate" value="Rotate" class="immediate"/>
		&nbsp;
		<input type="button" id="bt_clear" value="Clear"/>
		<input type="button" id="bt_replace" value="Replace"/>
		Tolerance: <input type="text" id="tb_tolerance" class="numericField" value="<?php echo DEFAULT_TOLERANCE?>"/>
		&nbsp;
		Debug info <input type="checkbox" id="cb_debug"/>
		<input type="button" id="bt_undo" value="Undo" class="immediate"/>

		Save as: <input type="text" id="tb_save" value="<?php echo getFilename($file,"png");?>"/>
		<input type="button" id="bt_save" value="Save" class="immediate"/>
	</div>
	<span id="status"><?php echo jsValue($_GET);?></span>
	<br/>
	<a id="editLink" href="?<?php echo  $_SERVER["QUERY_STRING"]?>">?<?php echo  $_SERVER["QUERY_STRING"]?></a>
	<br/>
	<img id="image" src="<?php echo $imageUrl?>" />
	<div id="selectZone" class="selection hidden">S</div>
</body>
</html>
