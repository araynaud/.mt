<?php session_start(); 
require_once("../include/config.php");

$user = session_login();
set_upload();

$path=getPath();
$relPath=getDiskPath($path);
$defaultFile="readme.txt";
$file=getParam("file"); // file or folder description file
$filePath=combine($relPath,$file);
if(is_dir($filePath))
{
	$path=combine($path,$file);
	$relPath=combine($relPath,$file);
	$file=$defaultFile;
	$filePath=combine($relPath,$file);
}
splitFilename($file,$filename,$ext);	

$add=getParam("add"); // file or folder description file
if($add) //make comment filename
{
	//count previous comments
	//image.txt or image.00.arthur.txt = description
	//image.01.arthur.txt = comments
	$comments=listFilesStartingWith($relPath,$filename,"txt");
	$nbComments=count($comments);
	$filename="$filename.$nbComments.$user";
}
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	<meta http-equiv="X-UA-Compatible" content="IE=9"/>
	<link type="text/css" rel="stylesheet" href="../MediaThingy.css"/>
	<?php addStylesheet($relPath); ?>
	<script type="text/javascript" src="../js/jquery.js"></script>
	<script type="text/javascript" src="../js/index.js"></script>
	<script type="text/javascript">
		$(document).ready(function()
		{
			UI.makeBackgroundGradients();		
		});
	</script>
</head>
<body>
<?php displayBackground($relPath); ?>
<?php echo "user: $user"?><br/>

	<form method="post" action="store_description.php">
		<input type="hidden" name="path" value="<?php echo $path?>"/>
		<input type="hidden" name="file" value="<?php echo $file ? $file : $defaultFile;?>"/>		
		Path: <?php echo $path?><br/>
		<?php if($file != $defaultFile)
			echo "File name: <input type=\"text\" name=\"newname\" size=\"30\" value=\"$filename\"/><br/>";
		?>
		Description: <textarea name="description" cols="50" rows="4" ><?php echo readTextFile("$relPath/$filename.txt");?></textarea>
		<br/>		
		<input type="button" name="back" value="<-" onclick="history.back();">
		&nbsp;
		<?php if(!empty($add)) {?>
		<input type="submit" name="upload" value="add"/>
		<?php } else {?>
		<input type="submit" name="upload" value="OK"/>		
		<?php }?>
	</form>

<?php
	if($exif=getExifData($filePath))
	{?>
	<div class="floatR text small">
	<?php displayExifData($exif);?>
	</div>
	<?php 
	}

	if($exif=@getimagesize($filePath))
	{?>
	<div class="floatR text small">
	<?php displayExifData($exif);?>
	</div>
	<?php 
	}
		
	$image=findThumbnail($relPath, $file, ".tn");
	if (empty($image))
		$image=findThumbnail($relPath, $file, ".ss");
	if (!empty($image))
	{?>
	<div class="floatR">	
		<a href="<?php echo $filePath?>">
			<img id="image" src="<?php echo $image?>"/>
		</a>
	</div>
<?php } 
else {?>
		<a href="<?php echo $filePath?>"><?php echo $file?></a>
<?php } ?>
</body>
</html>
