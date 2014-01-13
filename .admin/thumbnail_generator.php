<?php 
require_once("../include/config.php");

$path=getPath();
$relPath=getDiskPath($path);
$subfolder=getParam("subfolder",".tn");
$size=getParam("size",250);
$force=getParamBoolean("force");
createThumbnails($relPath, $subfolder, $size, $force);
?>
<script type="text/javascript">
//	window.location = "../?path=<?php echo $_GET['path']?>";
</script>

