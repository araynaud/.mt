<?php
require_once("../include/config.php");
session_start(); 

$path=getPath();
$file = getParam("file");
$relPath = getDiskPath($path);
$urlPath = diskPathToUrl($relPath);
$filePath = combine($relPath, $file);
$fileUrl = combine($urlPath, $file);
$imageScript= "../image_auto.php"; //"?" . $_SERVER["QUERY_STRING"];
$imageParams=$_GET;
//$imageParams["url"] = "image_gd2.php";
deleteTempImage();
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	<link type="text/css" rel="stylesheet" href="../MediaThingy.css"/>
	<?php addStylesheet($relPath); ?>
	<script type="text/javascript" src="../js/lib/jquery-1.8.3.min.js"></script>
	<script type="text/javascript" src="../js/mt.extensions.js"></script>
	<script type="text/javascript" src="../js/mt.extensions.jquery.js"></script>
	<!--script type="text/javascript" src="../js/mt.transitions.js"></script-->
	<script type="text/javascript" src="../js/mt.color.js"></script>
	<script type="text/javascript" src="../js/mt.ui.js"></script>
	<script type="text/javascript" src="../js/mt.imageEdit.js"></script>
	<!--script type="text/javascript" src="../js/mt.keys.js"></script-->
	<script type="text/javascript">
	<?php echo jsVar("imageScript", true,  false, true, false); ?>
	<?php echo jsVar("imageParams", true, false, true, false); ?>
	imageParams.selectMode=0;

	$(document).ready(function()
	{
		img=$("img#originalImage");
		dstImg=$("img#image");
		if(!img.length) img = dstImg;

		UI.makeBackgroundGradients();		
		$("img#originalImage, div#selectZone").click(imageClick);
		$("img#originalImage, div#selectZone").mousemove(imageSelect);
		$("input:text, select").change(getFieldValue);
		$("input:checkbox").click(getFieldValue);
		$("input:button").click(selectTool);
		$("input:text").change();
		refreshImage();
	});
	</script>
</head>
<body>
	<?php displayBackground($relPath);?>
	<div id="toolbar">
		<a href="./?path=<?php echo $path?>"><img src="../icons/home.gif"/></a>
		<a id="imageLink" href="#">view</a>
		&nbsp;
		<input type="button" id="bt_select" value="Select"/>
		<input type="button" id="bt_crop" value="Transform" class="immediate"/>
		&nbsp;
		Size: <input type="text" id="tb_size" class="numericField"/>
		&nbsp;
		Angle: <input type="text" id="tb_angle" class="numericField"/>
		&nbsp;
		Filter: <select id="dd_filter">
			<option></option>
			<option>grayscale</option>
			<option>sepia</option>
			<option>negative</option>
		</select> 
		&nbsp;
		Debug info <input type="checkbox" id="cb_info"/>
		Save as: <input type="text" id="tb_save" value="<?php echo "edit-$file";?>"/>
		<input type="button" id="bt_save" value="Save" class="immediate"/>
	</div>
	<span id="status"><?php echo jsValue($_GET);?></span>
	<br/>
	<a id="editLink" href="?<?php echo  $_SERVER["QUERY_STRING"]?>">?<?php echo $_SERVER["QUERY_STRING"]?></a>
	<br/>
	<div class="centered inlineBlock photoBorder shadow margin">
		<img id="originalImage" src="<?php echo "/$fileUrl" ?>" />
	</div>
	<div class="centered inlineBlock photoBorder shadow margin">
		<img id="image" />
	</div>
	<div id="selectZone" class="selection hidden">S</div>
</body>
</html>
