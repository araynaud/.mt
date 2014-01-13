<?php session_start(); 

require_once("../include/config.php");

$user = current_user();
$path=getPath();
?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>Image upload</title>
	<link type="text/css" rel="stylesheet" href="../MediaThingy.css"/>
	<link type="text/css" rel="stylesheet" href="upload.css"/>
	<script src="../js/jquery-1.8.3.min.js"></script>
	<script type="text/javascript" src="../js/multiupload.js"></script>
	<script type="text/javascript">
	var uploadConfig = {
		support : "image/jpg,image/png,image/bmp,image/jpeg,image/gif,video/mp4,audio/mp3",		// Valid file formats
		form: "demoFiler",					// Form ID
		dragArea: "dragAndDropFiles",		// Upload Area ID
		uploadUrl: "upload.php"				// Server side upload url
//		uploadUrl: "fileupload.php"				// Server side upload url
	};
	$(document).ready(function()
	{
		initMultiUploader(uploadConfig);
	});
	</script>
</head>
<body lang="en">
	<?php echo "user: $user"?><br/>
	<h1 class="title">Multiple Drag and Drop File Upload</h1>
	<form name="demoFiler" id="demoFiler" enctype="multipart/form-data">
		Path: <input type="text" id="path" name="path" size="30" value="<?php echo $path?>"/>&nbsp;
		<input type="file" name="multiUpload" id="multiUpload" multiple />&nbsp;
		<input type="submit" name="upload" id="upload" value="Upload" class="buttonUpload" />
	</form>
	<div class="progressBar">
		<div class="status"></div>
	</div>
	<div id="dragAndDropFiles" class="uploadArea">
		<h1>Drop Files Here</h1>
	</div>
</body>
</html>