<?php
require_once("../include/config.php");

$path=getPath();
$relPath=getRelPath($path);

session_start(); 
$user=session_login();
set_upload();
?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	<link type="text/css" rel="stylesheet" href="../MediaThingy.css"/>
	<?php addStylesheet($relPath); ?>
</head>
<body>
	<?php displayBackground($relPath); ?>
	<form method="post" enctype="multipart/form-data" action="upload.php?debug=true">
	<p>
		<?php echo "user: $user"?><br/>
		Path: <input type="text" name="path" size="30" value="<?php echo $path?>"/>
		<br/>
		Image: <input type="file" name="file" size="30" multiple/>&nbsp;
		<input type="submit" name="upload" value="Upload"/>
	</p>
	</form>
</body>
</html>
