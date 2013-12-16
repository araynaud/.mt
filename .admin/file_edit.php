<?php
require_once("../include/config.php");
session_start(); 
$user=session_login();
set_admin() ; 
$path=getPath();
$relPath=getRelPath($path);
$file=getParam("file","readme.txt"); // file or folder description file

splitFilename($file,$filename,$ext);	
//if file not present, find first with same extension
if (!file_exists("$relPath/$file"))
{
	$file=hasFiles($relPath,$ext);
}

$content=readTextFile("$relPath/$file");
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	<link type="text/css" rel="stylesheet" href="../MediaThingy.css"/>
	<?php addStylesheet($relPath); ?>
</head>
<body>
	<?php displayBackground($relPath); ?>
	<?php echo "user: " . current_user()?><br/>
	<form method="post" action="file_store.php">
	<p>
		<input type="hidden" name="path" value="<?php echo $path?>"/>
		<input type="hidden" name="file" value="<?php echo $file?>"/>
		Path: <?php echo $path?> - relPath: <?php echo $relPath?>  - pathToDataRoot: <?php echo pathToDataRoot()?>  <br/>
		<?php if(isset($_GET['file']))
			echo "File name: <input type=\"text\" name=\"newname\" size=\"30\" value=\"$filename\"/><br/>";
		?>
		<textarea name="description" cols="80" rows="10" ><?php echo $content?></textarea>
		<br/>
		<input type="submit" name="upload" value="OK"/>
		&nbsp;<input type="button" name="back" value="<-" onclick="history.back();">
	</p>
	</form>
</body>
</html>
