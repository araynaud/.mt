<?php session_start(); 
require_once("../include/config.php");
$user=session_login();
set_upload();

$format=getParam('format','html');
if($format=="ajax")
{
	$u=new User();
	echo $u->toJson();
	return;
}

$path=getPath();
$relPath=getDiskPath($path);

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
	"user: " <?=$user?><br/>
	<script type="text/javascript">
		window.location = "../?path=<?php echo $path?>";
	</script>
</body>
</html>
